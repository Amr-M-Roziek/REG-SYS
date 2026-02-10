<?php
/**
 * URFrontendListing CoreFunctions.
 *
 * General core functions available on both the front-end and admin.
 *
 * @author   WPEverest
 * @category Core
 * @package  URFrontendListing/Handler
 * @version  1.0.0
 */

if ( ! function_exists( 'ur_frontend_listing_is_compatible' ) ) {

	/**
	 * Check if Frontend Listing addon is compatible.
	 *
	 * @return string
	 */
	function ur_frontend_listing_is_compatible() {

		$ur_plugins_path     = WP_PLUGIN_DIR . UR_FRONTEND_LISTING_DS . 'user-registration' . UR_FRONTEND_LISTING_DS . 'user-registration.php';
		$ur_pro_plugins_path = WP_PLUGIN_DIR . UR_FRONTEND_LISTING_DS . 'user-registration-pro' . UR_FRONTEND_LISTING_DS . 'user-registration.php';

		if ( ! file_exists( $ur_plugins_path ) && ! file_exists( $ur_pro_plugins_path ) ) {
			return __( 'Please install <code>user-registration-pro' . '</code> plugin to use <code>user-registration-frontend-listing</code> addon.', 'user-registration-frontend-listing' );
		}

		$ur_plugin_file_path     = 'user-registration/user-registration.php';
		$ur_pro_plugin_file_path = 'user-registration-pro/user-registration.php';

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( $ur_plugin_file_path ) && ! is_plugin_active( $ur_pro_plugin_file_path ) ) {
			return __( 'Please activate <code>user-registration-pro</code> plugin to use <code>user-registration-frontend-listing</code> addon.', 'user-registration-frontend-listing' );
		}

		if ( function_exists( 'UR' ) ) {
			$user_registration_version = UR()->version;
		} else {
			$user_registration_version = get_option( 'user_registration_version' );
		}

		if ( ! is_plugin_active( $ur_pro_plugin_file_path ) ) {

			if ( version_compare( $user_registration_version, '1.9.5', '<' ) ) {
				return __( 'Please update your <code>user-registration-pro</code> plugin(to at least 1.9.5 version) to use <code>user-registration-frontend-listing</code> addon.', 'user-registration-frontend-listing' );
			}
		} else {

			if ( version_compare( $user_registration_version, '3.0.0', '<' ) ) {
				return __( 'Please update your <code>user registration-pro</code> plugin to at least 3.0.0 version to use <code>user-registration-frontend-listing</code> addon.', 'user-registration-frontend-listing' );
			}
		}

		return 'YES';

	}
}

if ( ! function_exists( 'ur_frontend_listing_check_plugin_compatibility' ) ) {

	/**
	 * Check Plugin Compatibility.
	 */
	function ur_frontend_listing_check_plugin_compatibility() {

		add_action( 'admin_notices', 'ur_frontend_listing_admin_notice', 10 );

	}
}

if ( ! function_exists( 'ur_frontend_listing_admin_notice' ) ) {

	/**
	 * Print Admin Notice.
	 */
	function ur_frontend_listing_admin_notice() {

		$class = 'notice notice-error';

		$message = ur_frontend_listing_is_compatible();

		if ( 'YES' !== $message && '' !== $message ) {

			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
		}
	}
}

if ( ! function_exists( 'ur_frontend_listing_user_search_fields' ) ) {

	/**
	 * Fields to search when searching is enabled.
	 *
	 * @return string
	 */
	function ur_frontend_listing_user_search_fields() {

		$fields =
			array(
				'user_login'   => __( 'Username', 'user-registration-frontend-listing' ),
				'user_email'   => __( 'Email', 'user-registration-frontend-listing' ),
				'user_url'     => __( 'User URL', 'user-registration-frontend-listing' ),
				'display_name' => __( 'Display Name', 'user-registration-frontend-listing' ),
				'first_name'   => __( 'First Name', 'user-registration-frontend-listing' ),
				'last_name'    => __( 'Last Name', 'user-registration-frontend-listing' ),
				'nickname'     => __( 'Nickname', 'user-registration-frontend-listing' ),
			);

		$fields = apply_filters( 'user_registration_frontend_listing_user_search_fields', $fields );

		return $fields;
	}
}

if ( ! function_exists( 'ur_frontend_listing_exclude_fields_in_view_profile' ) ) {

	/**
	 * Get the user registration form fields to exclude in view profile.
	 *
	 * @return array
	 */
	function ur_frontend_listing_exclude_fields_in_view_profile() {
		$fields_to_exclude = array(
			'user_pass',
			'user_confirm_password',
			'user_confirm_email',
			'password',
			'user_registered',
			'profile_picture',
			'privacy_policy',
			'section_title',
			'html',
			'stripe_gateway',
		);

		$fields_to_exclude = apply_filters( 'ur_frontend_listing_exclude_fields_in_view_profile', $fields_to_exclude );
		return $fields_to_exclude;
	}
}

if ( ! function_exists( 'ur_frontend_listing_include_fields_in_view_profile' ) ) {

	/**
	 * Get the user registration form fields to include in view profile.
	 *
	 * @return array
	 */
	function ur_frontend_listing_include_fields_in_view_profile() {
		$default_fields            = array_merge( ur_get_user_table_fields(), ur_get_registered_user_meta_fields() );
		$fields_to_exclude         = array_merge( $default_fields, ur_frontend_listing_exclude_fields_in_view_profile() );
		$default_fields_with_label = array(
			'form_label' => 'Basic Details',
			'field_list' => array(),
		);

		// Default field Exclude.
		foreach ( $default_fields as $field_key => $field_value ) {
			$default_fields_with_label['field_list'][ $field_value ] = ucwords( implode( ' ', explode( '_', $field_value ) ) );
			if ( 'user_pass' === $field_value ) {
				unset( $default_fields_with_label['field_list'][ $field_value ] );
			}
		}

		if ( function_exists( 'ur_get_all_form_fields' ) ) {
			$form_fields = ur_get_all_form_fields( $fields_to_exclude );
		} else {
			$form_fields = array();
		}

		 array_unshift( $form_fields, $default_fields_with_label );
		 return $form_fields;
	}
}

if ( ! function_exists( 'ur_frontend_listings_metabox_field_ids' ) ) {

	/**
	 * Get the metabox fields ids of the frontend_listings post..
	 *
	 * @return array
	 */
	function ur_frontend_listings_metabox_field_ids() {
		$metabox_field_ids = array(
			'user_registration_frontend_listings_layout',
			'user_registration_frontend_listings_allow_guest',
			'user_registration_frontend_listings_display_profile_picture',
			'user_registration_frontend_listings_view_profile',
			'user_registration_frontend_listings_card_fields',
			'user_registration_frontend_listings_lists_fields',
			'user_registration_frontend_listings_search_form',
			'user_registration_frontend_listings_search_fields',
			'user_registration_frontend_listings_sort_by',
			'user_registration_frontend_listings_default_sorter',
			'user_registration_frontend_listings_role_restriction',
			'user_registration_frontend_listings_ur_only',
			'user_registration_frontend_listings_ur_forms',
			'user_registration_frontend_listings_advanced_filter_fields',
			'user_registration_frontend_listings_amount_filter',
			'user_registration_frontend_listings_default_page_filter',
			'user_registration_frontend_listings_filtered_user_message',
			'user_registration_frontend_listings_no_users_found_text',
			'user_registration_frontend_listings_access_denied_text',
			'user_registration_frontend_listing_search_placeholder_text',
			'user_registration_frontend_listings_search_button_text',
			'user_registration_frontend_listings_view_profile_button_text',
			'user_registration_frontend_listings_advanced_filter',
		);

		$metabox_field_ids = apply_filters( 'ur_frontend_listings_metabox_ids', $metabox_field_ids );
		return $metabox_field_ids;
	}
}


if ( ! function_exists( 'ur_frontend_listing_amount_filter' ) ) {

	/**
	 * Get the amount filters for users per page.
	 *
	 * @return array
	 */
	function ur_frontend_listing_amount_filter() {

		$amount_filter = array(
			'10'  => 10,
			'20'  => 20,
			'50'  => 50,
			'100' => 100,
		);

		$amount_filter = apply_filters( 'ur_frontend_listing_amount_filter', $amount_filter );
		return $amount_filter;
	}
}

if ( ! function_exists( 'ur_frontend_listing_sort_filter' ) ) {

	/**
	 * Get the sort filters for sorting users.
	 *
	 * @return array
	 */
	function ur_frontend_listing_sort_filter() {

		$sort_filter = array(
			'user_registered' => __( 'Latest Users', 'user-registration-frontend-listing' ),
			'first_name'      => __( 'First Name', 'user-registration-frontend-listing' ),
			'last_name'       => __( 'Last Name', 'user-registration-frontend-listing' ),
			'display_name'    => __( 'Display Name', 'user-registration-frontend-listing' ),
		);

		$sort_filter = apply_filters( 'ur_frontend_listing_sort_filter', $sort_filter );
		return $sort_filter;
	}
}

if ( ! function_exists( 'ur_frontend_listing_advanced_filter' ) ) {
	/**
	 * Get the advanced filters for narrowing down user search.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	function ur_frontend_listing_advanced_filter() {

		$default_fields  = ur_frontend_listing_default_advanced_filter();
		$default_labels  = ur_get_registered_form_fields_with_default_labels();
		$advanced_filter = array();

		foreach ( $default_fields as $field_key ) {
			if ( 'user_pass' === $field_key ) {
				continue;
			}

			if ( isset( $default_labels[ $field_key ] ) ) {
				$advanced_filter[ $field_key ] = $default_labels[ $field_key ];
			}
		}

		$advanced_filter['other'] = esc_html__( 'Other ( Custom field )', 'user-registration-frontend-listing' );

		$advanced_filter = apply_filters( 'ur_frontend_listing_advanced_filter', $advanced_filter );
		return $advanced_filter;
	}
}


if ( ! function_exists( 'ur_frontend_listing_default_advanced_filter' ) ) {
	/**
	 * Get the default advanced filters for narrowing down user search.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	function ur_frontend_listing_default_advanced_filter() {

		$table_field            = ur_get_user_table_fields();
		$registered_meta_fields = ur_get_registered_user_meta_fields();

		$default_fields = array_merge( $table_field, $registered_meta_fields );
		return $default_fields;
	}
}

if ( ! function_exists( 'ur_frontend_list_advanced_filter_wrapper' ) ) {

	/**
	 * Generates the advanced filter wrapper to show in frontend list page.
	 *
	 * @param array $advanced_filter_fields Advanced Filter fields wrapper.
	 * @since 1.1.0
	 */
	function ur_frontend_list_advanced_filter_wrapper( $advanced_filter_fields ) {
		$filter_wrapper = '<div class="ur-advance-setting-container ur-frontend-listing-advance-filter-wrapper" style="display:none;">';

		$filter_wrapper .= '<div class="ur-advance-setting-lists">';

		foreach ( $advanced_filter_fields as $filter_key => $filter_label ) {
			$filter_wrapper .= '<span class="ur-advance-settings-list">';
			$filter_wrapper .= '<label for="">' . esc_attr( $filter_label ) . '</label>';
			$filter_wrapper .= '<input type="text" name="ur_frontend_listing_' . esc_attr( $filter_key ) . '" />';
			$filter_wrapper .= '</span>';
		}
		$filter_wrapper .= '</div>';

		$filter_wrapper .= '<div class="ur-advance-setting-button">';
		$filter_wrapper .= '<div class="ur-advance-setting-reset" data-id="all-filter">';
		$filter_wrapper .= '<span>' . esc_html__( 'Start Over? ', 'user-registration-frontend-listing' ) . '</span><a>' . esc_html__( 'Reset Filter', 'user-registration-frontend-listing' ) . '</a>';
		$filter_wrapper .= '</div>';

		$filter_wrapper .= ' <div class="ur-advance-setting-button-group">';
		$filter_wrapper .= '<input type="submit" value="' . esc_html__( 'Apply Filter', 'user-registration-frontend-listing' ) . '" class="ur-btn btn-primary ur-frontend-listing-advance-filter-apply">';
		$filter_wrapper .= '</div>';
		$filter_wrapper .= '</div>';
		$filter_wrapper .= '</div>';

		echo $filter_wrapper; // phpcs:ignore

	}
}
