<?php
/**
 * User Registration Frontend Listing Shortcodes.
 *
 * @class    Shortcodes
 * @version  1.0.0
 * @package  URFrontendListing/Classes
 * @category Class
 * @author   WPEverest
 */

namespace WPEverest\URFrontendListing\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes Class
 */
class Shortcodes {

	public static $parts = false;

	/**
	 * Init Shortcodes.
	 */
	public function __construct() {
		$shortcodes = array(
			'user_registration_frontend_list' => __CLASS__ . '::frontend_list',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function
	 * @param array    $atts (default: array())
	 * @param array    $wrapper
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'user-registration-frontend-list',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		echo empty( $wrapper['before'] ) ? '<div id="user-registration-frontend_list" class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

		return ob_get_clean();
	}

	/**
	 * User Registration Frontend List shortcode.
	 *
	 * @param mixed $atts
	 */
	public static function frontend_list( $atts ) {

		if ( empty( $atts ) || ! isset( $atts['id'] ) ) {
			return '';
		}

		ob_start();
		self::render_frontend_list( $atts['id'] );
		return ob_get_clean();
	}

	/**
	 * Output for frontend list.
	 *
	 * @since 1.0.1 Recaptcha only
	 */
	public static function render_frontend_list( $list_id ) {
		$post             = get_post( $list_id );
		$post_id          = $list_id;
		$display_to_guest = get_post_meta( $post_id, 'user_registration_frontend_listings_allow_guest', true );
		$access_denied    = get_post_meta( $post_id, 'user_registration_frontend_listings_access_denied_text', true );

		$script_data = array(
			'ajax_url'                                  => admin_url( 'admin-ajax.php' ),
			'ur_frontend_listing_user_data_security'    => wp_create_nonce( 'ur_frontend_listing_user_data_nonce' ),
			'ur_frontend_listing_filtered_user_message' => get_post_meta( $post_id, 'user_registration_frontend_listings_filtered_user_message', $single = true ),
		);

		wp_enqueue_style( 'user-registration-frontend-listing-frontend-style' );

		if ( ! $display_to_guest && ! is_user_logged_in() ) {
			echo '<div class="user-registration-error user-registration-frontend-listing-error">' . esc_html( $access_denied ) . '</div>';
		} elseif ( isset( $_GET['user_id'] ) && intval( $_GET['user_id'] ) ) {

				$view_id = wp_unslash( intval( $_GET['list_id'] ) );

				// Check to see if the list id from shortcode matches with the list id from view profile button.
			if ( $view_id == $list_id ) {
				$user_id = wp_unslash( intval( $_GET['user_id'] ) );
				wp_enqueue_style( 'user-registration-pro-frontend-style' );

				$show_profile_picture = get_post_meta( $post_id, 'user_registration_frontend_listings_display_profile_picture', $single = true );

				$user_extra_fields        = ur_get_user_extra_fields( $user_id );
				$user_data                = (array) get_userdata( $user_id )->data;
				$user_data['first_name']  = get_user_meta( $user_id, 'first_name', true );
				$user_data['last_name']   = get_user_meta( $user_id, 'last_name', true );
				$user_data['description'] = get_user_meta( $user_id, 'description', true );
				$user_data['nickname']    = get_user_meta( $user_id, 'nickname', true );
				$user_data                = array_merge( $user_data, $user_extra_fields );
				$form_id                  = ur_get_form_id_by_userid( $user_id );
				$fields_to_include        = get_post_meta( $post_id, 'user_registration_frontend_listings_card_fields', $single = true );
				$fields_to_include        = ! empty( $fields_to_include ) ? $fields_to_include : array_keys( ur_frontend_listing_include_fields_in_view_profile() );
				$form_field_data_array    = user_registration_pro_profile_details_form_fields( $form_id, $fields_to_include );
				$field_keys_to_include    = user_registration_pro_profile_details_form_keys_to_include( $fields_to_include, $form_field_data_array );

				foreach ( $field_keys_to_include as $key => $value ) {
					if ( preg_match( '/^(billing_|shipping_).+|.+_shipping$/', $value ) ) {
						$woocommerce_user_meta = get_user_meta( $user_id, $value );
						$user_data[ $value ]   = isset( $woocommerce_user_meta[0] ) ? $woocommerce_user_meta[0] : '';
					}
				}

				$user_data_to_show = user_registration_pro_profile_details_form_field_datas( $form_id, $user_data, $form_field_data_array, $field_keys_to_include );

				ur_get_template(
					'pro/user-registration-pro-view-user.php',
					array(
						'user_data_to_show'    => $user_data_to_show,
						'show_profile_picture' => $show_profile_picture,
						'user_id'              => $user_id,
					),
					'user-registration-pro',
					UR_TEMPLATE_PATH
				);
			}
		} else {
			wp_enqueue_script( 'user-registration-frontend-listing-frontend-script' );
			?>
				<script id="user-registration-frontend-listing-frontend-script">
					const user_registration_frontend_listings_frontend_script_data = <?php echo wp_json_encode( $script_data ); ?>
				</script>
				<?php
				if ( isset( $post ) ) {
					ur_get_template(
						'pro/frontend-listing/user-registration-frontend-listing-layout.php',
						array( 'post_id' => $post_id ),
						'user-registration-pro',
						UR_TEMPLATE_PATH
					);
				} else {
					echo '<p>' . esc_html__( 'Frontend List not found', 'user-registration-frontend-listing' ) . '</p>';
				}
		}
	}
}
