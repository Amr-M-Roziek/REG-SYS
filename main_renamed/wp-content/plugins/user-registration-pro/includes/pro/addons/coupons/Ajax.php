<?php
/**
 * Coupons AJAX
 *
 * AJAX Event Handler
 *
 * @class    AJAX
 * @package  Coupons/Ajax
 * @author   WPEverest
 */

namespace WPEverest\URMembership\Coupons;

use ProfilePress\Core\Membership\Repositories\CouponRepository;
use WPEverest\URMembership\Admin\Services\Stripe\StripeService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Class
 */
class Ajax {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax)
	 */
	public static function add_ajax_events() {

		$ajax_events = array(
			'create'         => false,
			'update'         => false,
			'delete_coupons' => false,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {

			add_action( 'wp_ajax_user_registration_coupons_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {

				add_action(
					'wp_ajax_nopriv_user_registration_coupons_' . $ajax_event,
					array(
						__CLASS__,
						$ajax_event,
					)
				);
			}
		}
	}

	/**
	 * Create Coupons
	 *
	 * @return void
	 */
	public static function create() {
		if ( ! check_ajax_referer( 'ur_member_coupons', 'security' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce error please reload.', 'user-registration' ),
				)
			);
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Forbidden.', 'user-registration' ),
				)
			);
		}
		$validate_coupon_data = self::validate_coupon_data();

		if ( ! $validate_coupon_data['status'] ) {
			wp_send_json_error(
				$validate_coupon_data
			);
		}
		$data = self::prepare_coupon_data();

		$new_post_id = wp_insert_post( $data['post_data'] );

		if ( $new_post_id ) {
			if ( is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' ) && is_plugin_active( 'user-registration-membership/user-registration-membership.php' ) && "membership" === $data['post_meta_data']['coupon_for'] ) {
				$stripe_service = new StripeService();

				$stripe_settings = $stripe_service->get_stripe_settings();
				if ( empty( $stripe_settings['publishable_key'] ) || empty( $stripe_settings['secret_key'] ) ) {
					wp_delete_post( $new_post_id );
					wp_send_json_error(
						array(
							'message' => esc_html__( 'Stripe settings incomplete. Please complete before proceeding.', 'user-registration' ),
						)
					);
				}
				$data = $stripe_service->sync_coupon( $data, array() );
			}
			add_post_meta( $new_post_id, 'ur_coupon_meta', wp_json_encode( $data['post_meta_data'] ) );
			add_post_meta( $new_post_id, 'ur_coupon_code', $data['post_meta_data']['coupon_code'] );
			wp_send_json_success(
				array(
					'coupon_id' => $new_post_id,
					'message'   => esc_html__( 'Successfully created the coupon.', 'user-registration' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Sorry! There was an unexpected error while saving the coupon data.', 'user-registration' ),
				)
			);
		}
	}

	/**
	 * Update coupon
	 *
	 * @return void
	 */
	public static function update() {

		if ( ! check_ajax_referer( 'ur_member_coupons', 'security' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce error please reload.', 'user-registration' ),
				)
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Forbidden.', 'user-registration' ),
				)
			);
		}

		if ( ! isset( $_POST['coupon_id'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please provide a valid Coupon ID.', 'user-registration' ),
				)
			);
		}
		$validate_coupon_data = self::validate_coupon_data( $_POST['coupon_id'] );

		if ( ! $validate_coupon_data['status'] ) {
			wp_send_json_error(
				$validate_coupon_data
			);
		}
		$old_coupon_data    = get_post( $_POST['coupon_id'] );
		$old_coupon_details = ur_get_coupon_details( $old_coupon_data->post_content );

		$data      = self::prepare_coupon_data();
		$coupon_id = absint( $_POST['coupon_id'] );

		$sync_stripe = is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' ) && is_plugin_active( 'user-registration-membership/user-registration-membership.php' ) && "membership" === $data['post_meta_data']['coupon_for'];

		if ( $sync_stripe ) {
			$stripe_service  = new StripeService();
			$stripe_settings = $stripe_service->get_stripe_settings();
			if ( empty( $stripe_settings['publishable_key'] ) || empty( $stripe_settings['secret_key'] ) ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Stripe settings incomplete. Please complete before proceeding.', 'user-registration' ),
					)
				);
			}

		}

		$updated_post_id = wp_insert_post( $data['post_data'] );

		if ( $updated_post_id ) {
			if ( $sync_stripe ) {
				$data = $stripe_service->sync_coupon( $data, $old_coupon_details );
			}

			update_post_meta( $coupon_id, 'ur_coupon_meta', wp_json_encode( $data['post_meta_data'] ) );
			update_post_meta( $coupon_id, 'ur_coupon_code', $data['post_meta_data']['coupon_code'] );
			wp_send_json_success(
				array(
					'coupon_id' => $coupon_id,
					'message'   => esc_html__( 'Successfully updated the coupon.', 'user-registration' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Sorry! There was an unexpected error while updating the coupon data.', 'user-registration' ),
				)
			);
		}
	}

	/**
	 * validate_coupon_data
	 *
	 * @return array|bool[]
	 */
	public static function validate_coupon_data( $coupon_id = null ) {
		$post_data      = ( isset( $_POST['coupons_data'] ) && ! empty( $_POST['coupons_data'] ) ) ? json_decode( wp_unslash( $_POST['coupons_data'] ), true ) : array();
		$coupon_details = ur_get_coupon_details( $post_data['coupon_code'] );

		if ( ! empty( $coupon_details ) ) {
			if ( isset( $coupon_details['coupon_id'] ) && $coupon_details['coupon_id'] != $coupon_id ) {
				return array(
					'status'  => false,
					'message' => __( "Duplicate Coupon Code", "user-registration" ),
				);
			}
		}

		return array(
			'status' => true,
		);

	}

	/**
	 * Prepare coupon data
	 *
	 * @return array[]
	 */
	public static function prepare_coupon_data() {
		$post_data         = ( isset( $_POST['coupons_data'] ) && ! empty( $_POST['coupons_data'] ) ) ? json_decode( wp_unslash( $_POST['coupons_data'] ), true ) : array();
		$post_id           = isset( $_POST['coupon_id'] ) ? absint( $_POST['coupon_id'] ) : '';
		$coupon_form       = isset( $post_data['coupon_form'] ) ? wp_unslash( $post_data['coupon_form'] ) : '';
		$coupon_membership = isset( $post_data['coupon_membership'] ) ? wp_unslash( $post_data['coupon_membership'] ) : '';
		$stripe_coupon_id  = '';
		if ( ! empty( $post_id ) ) {
			$post_meta        = json_decode( get_post_meta( $post_id, 'ur_coupon_meta', true ), true );
			$stripe_coupon_id = $post_meta['stripe_coupon_id'] ?? '';
		}

		return array(
			'post_data'      => array(
				'ID'             => absint( $post_id ),
				'post_title'     => sanitize_text_field( $post_data['coupon_name'] ),
				'post_status'    => 'publish',
				'post_type'      => 'ur_coupons',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_content'   => sanitize_text_field( $post_data['coupon_code'] ),
			),
			'post_meta_data' => array(
				'coupon_code'          => sanitize_text_field( $post_data['coupon_code'] ),
				'coupon_discount'      => number_format( $post_data['coupon_discount'], 2 ),
				'coupon_start_date'    => date( 'Y-m-d', strtotime( $post_data['start_date'] ) ),
				'coupon_end_date'      => date( 'Y-m-d', strtotime( $post_data['end_date'] ) ),
				'coupon_status'        => sanitize_text_field( $post_data['coupon_status'] ),
				'coupon_for'           => sanitize_text_field( $post_data['coupon_for'] ),
				'coupon_form'          => sanitize_text_field( wp_slash( wp_json_encode( $coupon_form ) ) ),
				'coupon_membership'    => sanitize_text_field( wp_slash( wp_json_encode( $coupon_membership ) ) ),
				'coupon_discount_type' => sanitize_text_field( $post_data['coupon_discount_type'] ),
				'stripe_coupon_id'     => sanitize_text_field( $stripe_coupon_id ),
			),
		);

	}

	/**
	 * delete_multiple
	 *
	 * @return void
	 */
	public static function delete_coupons() {

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permission not allowed.', 'user-registration' ),
				),
				403
			);
		}

		if ( ! check_ajax_referer( 'ur_member_coupons', 'security' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce error please reload.', 'user-registration' ),
				)
			);
		}

		if ( ! isset( $_POST ) && ! isset( $_POST['coupons_ids'] ) && ! empty( $_POST['coupons_ids'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Field coupons_ids is required.', 'user-registration' ),
				),
				422
			);
		}
		$coupons_ids = wp_unslash( $_POST['coupons_ids'] );
		$coupons_ids = json_decode( $coupons_ids, true );
		foreach ( $coupons_ids as $coupon_id ) {
			$deleted = wp_delete_post( $coupon_id, true );
			if ( ! $deleted ) {
				$deleted = false;
				break;
			}
		}
		if ( ! $deleted ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Sorry! There was an unexpected error while deleting the selected coupons.', 'user-registration' ),
				)
			);
		}
		wp_send_json_success(
			array(
				'message' => esc_html__( 'Coupons deleted successfully.', 'user-registration' ),
			)
		);


	}


}
