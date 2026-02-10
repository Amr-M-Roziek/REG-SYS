<?php
/**
 * URFrontendListing AJAX
 *
 * AJAX Event Handler
 *
 * @class    AJAX
 * @version  1.0.0
 * @package  URFrontendListing/Ajax
 * @category Class
 * @author   WPEverest
 */

namespace  WPEverest\URFrontendListing;

use WPEverest\URFrontendListing\Frontend\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Class
 */
class AJAX {

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
			'user_data'           => true,
			'display_user_fields' => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {

			add_action( 'wp_ajax_user_registration_frontend_listing_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {

				add_action(
					'wp_ajax_nopriv_user_registration_frontend_listing_' . $ajax_event,
					array(
						__CLASS__,
						$ajax_event,
					)
				);
			}
		}
	}

	/**
	 * New API Key for FrontendListing
	 *
	 * @throws Exception Post data set.
	 */
	public static function user_data() {

		if ( ! check_ajax_referer( 'ur_frontend_listing_user_data_nonce', 'security' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce error please reload.', 'user-registration-frontend-listing' ),
				)
			);
		}

		$filters  = isset( $_POST['data'] ) ? $_POST['data'] : '';
		$post_id  = isset( $_POST['list_id'] ) ? $_POST['list_id'] : '';
		$frontend = new Frontend();
		$data     = $frontend->ur_frontend_listing_get_users_data( $post_id, $_POST );

		if ( 0 !== $data['total_users'] ) {
			wp_send_json_success(
				$data
			);
		} else {
			wp_send_json_error(
				array(
					'message' => get_post_meta( $post_id, 'user_registration_frontend_listings_no_users_found_text', $single = true ),
				)
			);
		}

	}

	/**
	 * List the Forms Fields.
	 *
	 * @since 1.0.0
	 */
	public static function display_user_fields() {
		$form_ids  = isset( $_POST['form_ids'] ) ? $_POST['form_ids'] : '';  // phpcs:ignore
		$all_forms = ur_get_all_user_registration_form();
		if ( ! empty( $form_ids ) ) {
			$selected_forms = array_intersect_key( $all_forms, array_flip( $form_ids ) );
			array_push( $selected_forms, 'Basic Details' );
		}
		$field_data = ur_frontend_listing_include_fields_in_view_profile();
		$data       = array();
		foreach ( $field_data as $key => $value ) {
			if ( ! empty( $form_ids ) && ! in_array( $value['form_label'], $selected_forms, true ) ) {
				continue;
			}

			$data[] = $value;
		}

		wp_send_json_success(
			$data
		);
	}

}
