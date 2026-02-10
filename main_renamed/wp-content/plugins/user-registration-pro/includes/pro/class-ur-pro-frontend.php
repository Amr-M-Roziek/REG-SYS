<?php
/**
 * UserRegistrationPro Frontend.
 *
 * @class    User_Registration_Pro_Frontend
 * @version  1.0.0
 * @package  UserRegistrationPro/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User_Registration_Pro_Frontend Class
 */
class User_Registration_Pro_Frontend {

	/**
	 * Valid Form data.
	 *
	 * @var array
	 */
	private static $valid_form_data = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'register_scripts' ) );
		add_action( 'user_registration_enqueue_scripts', array( $this, 'load_scripts' ), 10, 2 );
		add_action( 'user_registration_my_account_enqueue_scripts', array( $this, 'load_scripts' ), 10, 2 );
		add_filter(
			'user_registration_handle_form_fields',
			array(
				$this,
				'user_registration_user_pass_form_field_filter',
			),
			10,
			2
		);
		add_action(
			'user_registration_after_form_fields',
			array(
				$this,
				'user_registration_form_field_honeypot',
			),
			10,
			2
		);
		add_action( 'wp_footer', array( $this, 'user_registration_pro_display_active_menu_popup' ) );
		add_action( 'user_registration_after_submit_buttons', array( $this, 'ur_pro_add_reset_button' ) );
		add_action( 'user_registration_enqueue_scripts', array( $this, 'enqueue_mailcheck_script' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'ur_process_privacy_tab' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_inactive_scripts' ) );

		$delete_account = get_option( 'user_registration_pro_general_setting_delete_account', 'disable' );

		if ( 'disable' !== $delete_account ) {
			add_action( 'init', array( $this, 'user_registration_add_delete_account_endpoint' ) );
			add_filter( 'user_registration_account_menu_items', array( $this, 'delete_account_item_tab' ) );
		}

		$privacy_tab_enable = get_option( 'user_registration_enable_privacy_tab', false );
		if ( ur_string_to_bool( $privacy_tab_enable ) ) {
			add_action( 'init', array( $this, 'user_registration_add_privacy_tab_endpoint' ) );
			add_filter( 'user_registration_account_menu_items', array( $this, 'ur_privacy_tab' ), 10, 1 );
			add_action(
				'user_registration_account_user-privacy_endpoint',
				array(
					$this,
					'user_registration_privacy_tab_endpoint_content',
				)
			);
		}
		$redirect_back_to_previous_page = ur_string_to_bool( get_option( 'user_registration_pro_general_setting_redirect_back_to_previous_page', false ) );

		if ( $redirect_back_to_previous_page ) {
			add_action(
				'user_registration_before_customer_login_form',
				array(
					$this,
					'user_registration_set_redirect_url',
				)
			);
			add_filter( 'user_registration_login_redirect', array( $this, 'user_registration_redirect_back' ), 10, 2 );
		}

		add_filter(
			'user_registration_handle_form_fields',
			array(
				$this,
				'user_registration_pro_auto_populate_form_field',
			),
			10,
			2
		);
		// Redirect prevent concurrent.
		add_action( 'template_redirect', array( __CLASS__, 'redirect_prevent_concurrent_link' ) );
		add_filter( 'user_registration_redirect_after_logout', array( $this, 'role_based_redirect_after_logout' ) );
		add_filter(
			'user_registration_login_redirect',
			array(
				$this,
				'user_registration_redirect_url_after_login',
			),
			10,
			2
		);
		add_filter(
			'user_registration_success_params_before_send_json',
			array(
				$this,
				'add_role_based_redirect_url_to_response',
			),
			10,
			4
		);

		add_action(
			'user_registration_check_token_complete',
			array(
				$this,
				'user_registration_send_admin_after_email_verified',
			),
			10,
			2
		);

		if ( isset( $_POST['action'] ) && ( 'save_profile_details' == $_POST['action'] || 'user_registration_update_profile_details' == $_POST['action'] ) ) {
			// Validate field as unique when user update their profile.
			add_action(
				'user_registration_after_save_profile_validation',
				array(
					$this,
					'validate_unique_field_after_profile_update',
				),
				10,
				2
			);
		} else {
			// Validate field as unique.
			$field_type = array( 'nickname', 'display_name', 'first_name', 'last_name', 'text', 'user_url', 'phone' );
			foreach ( $field_type as $field ) {
				add_action(
					'user_registration_validate_' . $field,
					array(
						$this,
						'user_registration_pro_validate_unique_field',
					),
					10,
					4
				);
			}
		}

		// Pattern Validation for fields
		$pattern_fields = user_registration_pro_pattern_validation_fields();
		foreach ( $pattern_fields as $field ) {
			add_action(
				'user_registration_validate_' . $field,
				array(
					$this,
					'user_registration_pro_pattern_validation',
				),
				10,
				4
			);
		}

		if ( isset( $_POST['action'] ) && ( 'save_profile_details' == $_POST['action'] || 'user_registration_update_profile_details' == $_POST['action'] ) ) {
			// Validate field as unique when user update their profile.
			add_action(
				'user_registration_after_save_profile_validation',
				array(
					$this,
					'validate_blacklist_words_field_after_profile_update',
				),
				10,
				2
			);
		} else {
			$blacklist_words_fields = user_registration_pro_blacklist_words_fields();
			foreach ( $blacklist_words_fields as $field ) {
				add_action(
					'user_registration_validate_' . $field,
					array(
						$this,
						'user_registration_blacklist_words_validation',
					),
					10,
					4
				);
			}
		}

		// Field Icon Hooks.
		add_filter(
			'user_registration_field_icon_enabled_class',
			array(
				$this,
				'ur_get_field_icon_enabled_class',
			),
			10,
			2
		);
		add_filter( 'user_registration_field_icon', array( $this, 'ur_get_field_icon' ), 10, 3 );

		$auto_login_after_reset_password = apply_filters( 'user_registration_auto_login_after_reset_password', false );
		if ( $auto_login_after_reset_password ) {
			add_action(
				'user_registration_reset_password',
				array(
					$this,
					'user_registration_auto_login_after_reset_password',
				),
				10,
				1
			);
		}

		add_action( 'user_registration_after_register_user_action', 'user_registration_pro_sync_external_field', 9, 3 );
		add_action(
			'user_registration_after_save_profile_validation',
			array( $this, 'user_registration_pro_sync_external_fields_after_save_profile_validation' ),
			10,
			2
		);
		add_action(
			'woocommerce_checkout_update_user_meta',
			array(
				$this,
				'user_registration_pro_sync_external_fields_checkout_process',
			),
			10,
			2
		);
		add_action( 'wp_loaded', array( $this, 'check_payments_process' ), 20 );
		// Render custom captcha field on frontend.
		add_filter( 'user_registration_form_field_captcha', array( $this, 'user_registration_render_captcha_field' ), 10, 4 );
		add_filter( 'user_registration_before_user_meta_update', array( $this, 'user_registration_exclude_fields_before_user_meta_update' ), 10, 3 );
		add_filter( 'user_registration_before_user_meta_update', array( $this, 'user_registration_process_signature_field_data_before_meta_update' ), 10, 3 );
		add_filter( 'user_registration_process_signature_field_data', array( $this, 'user_registration_process_signature_field_data' ) );

		// Render signature field on frontend
		add_filter( 'user_registration_form_field_signature', array( $this, 'user_registration_render_signature_field' ), 10, 4 );

		/**
		 * Update the booked slot data in user meta.
		 *
		 * @since 4.1.0
		 */
		add_action(
			'user_registration_after_register_user_action',
			array(
				$this,
				'ur_pro_update_booked_slot_in_user_meta',
			),
			10,
			3
		);
		/**
		 * Update the booked slot data in user meta while updating the user profile.
		 *
		 * @since 4.1.0
		 */
		add_action(
			'user_registration_save_profile_details',
			array(
				$this,
				'ur_pro_edit_profile_update_booked_slot',
			),
			10,
			2
		);
	}

	/**
	 * Exclude field to not update field value in usermeta table.
	 *
	 * @param mixed $valid_form_data Form Data.
	 * @param int   $user_id User Id.
	 * @param int   $form_id Form Id.
	 *
	 * @return $valid_form_data
	 * @since 4.1.0
	 */
	public function user_registration_exclude_fields_before_user_meta_update( $valid_form_data, $user_id, $form_id ) {
		$fields         = array( 'captcha' );
		$exclude_fields = apply_filters( 'user_registration_exclude_fields_before_user_meta_update', $fields );

		foreach ( $valid_form_data as $key => $value ) {
			$field_type = isset( $value->field_type ) ? $value->field_type : '';

			if ( ! empty( $field_type ) ) {

				if ( in_array( $field_type, $exclude_fields, true ) ) {
					unset( $valid_form_data[ $key ] );
				}
			}
		}

		return $valid_form_data;
	}

	/**
	 * Check Payments Process Compatibility.
	 *
	 * @since 4.0.5
	 */
	public function check_payments_process() {
		if ( ( ur_check_module_activation( 'payments' ) || is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' ) || is_plugin_active( 'user-registration-authorize-net/user-registration-authorize-net.php' ) ) ) {
			add_filter( 'user_registration_before_register_user_action', array( $this, 'modify_form_data' ), 10, 2 );
		}
	}

	/**
	 * Donot allow to modify default value for predefined and hidden values.
	 *
	 * @param array $valid_form_data Form Data.
	 * @param int   $form_id Form ID.
	 *
	 * @return array
	 */
	public function modify_form_data( $valid_form_data, $form_id ) {
		$content_post = get_post( $form_id );
		$post_content = isset( $content_post->post_content ) ? json_decode( $content_post->post_content ) : array();

		if ( ! is_null( $post_content ) ) {
			foreach ( $post_content as $post_content_row ) {
				foreach ( $post_content_row as $post_content_grid ) {
					foreach ( $post_content_grid as $fields ) {

						if ( isset( $fields->field_key ) && 'single_item' === $fields->field_key && ( isset( $fields->advance_setting->item_type ) && ( 'pre_defined' === $fields->advance_setting->item_type || 'hidden' === $fields->advance_setting->item_type ) ) ) {
							if ( isset( $fields->advance_setting->enable_selling_price_single_item ) && ur_string_to_bool( $fields->advance_setting->enable_selling_price_single_item ) ) {
								$predefined_hidden[ $fields->general_setting->field_name ] = $fields->advance_setting->selling_price;
							} else {
								$predefined_hidden[ $fields->general_setting->field_name ] = $fields->advance_setting->default_value;
							}
						}
					}
				}
			}
		}

		foreach ( $valid_form_data as $data ) {

			if ( isset( $data->extra_params['field_key'] ) && 'single_item' == $data->extra_params['field_key'] ) {
				$field_name = $data->field_name;
				if ( isset( $predefined_hidden[ $field_name ] ) ) {
					$data->value = $predefined_hidden[ $field_name ];
				}
			}
		}

		self::$valid_form_data = $valid_form_data;

		return $valid_form_data;
	}

	/**
	 * Send Payment Success Email to the user.
	 *
	 * @param int $user_id User Id.
	 */
	public static function send_success_email( $user_id ) {

		include_once __DIR__ . '/../admin/settings/emails/class-ur-settings-payment-success-email.php';

		$user      = get_user_by( 'ID', $user_id );
		$username  = $user->data->user_login;
		$email     = $user->data->user_email;
		$form_id   = ur_get_form_id_by_userid( $user_id );
		$form_data = self::$valid_form_data;

		list( $name_value, $data_html ) = ur_parse_name_values_for_smart_tags( $user_id, $form_id, $form_data );

		$values = array(
			'username'   => $username,
			'user_id'   => $user_id,
			'email'      => $email,
			'all_fields' => $data_html,
		);

		$header  = 'From: ' . UR_Emailer::ur_sender_name() . ' <' . UR_Emailer::ur_sender_email() . ">\r\n";
		$header .= 'Reply-To: ' . UR_Emailer::ur_sender_email() . "\r\n";
		$header .= "Content-Type: text/html\r\n; charset=UTF-8";

		$subject = get_option( 'user_registration_payment_success_email_subject', __( 'Congratulations! Payment Complete on {{blog_info}}', 'user-registration' ) );

		$settings                  = new UR_Settings_Payment_Success_Email();
		$message                   = $settings->ur_get_payment_success_email();
		$message                   = get_option( 'user_registration_payment_success_email', $message );
		list( $message, $subject ) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

		// Get selected email template id for specific form.
		$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

		$message = UR_Emailer::parse_smart_tags( $message, $values, $name_value );
		$subject = UR_Emailer::parse_smart_tags( $subject, $values, $name_value );
		$header  = UR_Emailer::parse_smart_tags( $header, $values, $name_value );

		if ( ur_string_to_bool( get_option( 'user_registration_enable_payment_success_email', true ) ) ) {
			UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
		}
	}

	/**
	 * Send Payment Success Email to the Admin.
	 *
	 * @param int $user_id User Id.
	 */
	public static function send_success_admin_email( $user_id ) {

		include_once __DIR__ . '/../admin/settings/emails/class-ur-settings-payment-success-admin-email.php';

		$user      = get_user_by( 'ID', $user_id );
		$username  = $user->data->user_login;
		$email     = $user->data->user_email;
		$form_id   = ur_get_form_id_by_userid( $user_id );
		$form_data = self::$valid_form_data;

		list( $name_value, $data_html ) = ur_parse_name_values_for_smart_tags( $user_id, $form_id, $form_data );

		$values = array(
			'user_id'    => $user_id,
			'username'   => $username,
			'email'      => $email,
			'all_fields' => $data_html,
		);

		$header  = "Reply-To: {{admin_email}} \r\n";
		$header .= 'Content-Type: text/html; charset=UTF-8';

		$admin_email = get_option( 'user_registration_payments_admin_email_receipents', get_option( 'admin_email' ) );
		$admin_email = explode( ',', $admin_email );
		$admin_email = array_map( 'trim', $admin_email );

		$subject = get_option( 'user_registration_payment_success_admin_email_subject', __( 'Payment Received', 'user-registration' ) );

		$settings                  = new UR_Settings_Payment_Success_Admin_Email();
		$message                   = $settings->ur_get_payment_success_admin_email();
		$message                   = get_option( 'user_registration_payment_success_admin_email', $message );
		list( $message, $subject ) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

		// Get selected email template id for specific form.
		$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

		$message = UR_Emailer::parse_smart_tags( $message, $values, $name_value );
		$subject = UR_Emailer::parse_smart_tags( $subject, $values, $name_value );
		$header  = UR_Emailer::parse_smart_tags( $header, $values, $name_value );

		if ( ur_string_to_bool( get_option( 'user_registration_enable_payment_success_admin_email', true ) ) ) {
			foreach ( $admin_email as $email ) {
				UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
			}
		}
	}

	/**
	 * Send Payment Pending Email to the user.
	 *
	 * @param int $user_id User Id.
	 */
	public static function send_pending_email( $user_id ) {

		require_once __DIR__ . '/admin/settings/emails/class-ur-settings-payment-pending-email.php';

		$user      = get_user_by( 'ID', $user_id );
		$username  = $user->data->user_login;
		$email     = $user->data->user_email;
		$form_id   = ur_get_form_id_by_userid( $user_id );
		$form_data = self::$valid_form_data;

		list( $name_value, $data_html ) = ur_parse_name_values_for_smart_tags( $user_id, $form_id, $form_data );

		$values = array(
			'username'   => $username,
			'email'      => $email,
			'all_fields' => $data_html,
		);

		$header  = 'From: ' . UR_Emailer::ur_sender_name() . ' <' . UR_Emailer::ur_sender_email() . ">\r\n";
		$header .= 'Reply-To: ' . UR_Emailer::ur_sender_email() . "\r\n";
		$header .= "Content-Type: text/html\r\n; charset=UTF-8";

		$subject = get_option( 'user_registration_payment_pending_email_subject', __( 'User Registered. Payment Pending on {{blog_info}}', 'user-registration' ) );

		$message = UR_Settings_Payment_Pending_Email::ur_get_payment_pending_email();
		$message = get_option( 'user_registration_payment_pending_email', $message );

		$settings                  = new UR_Settings_Payment_Pending_Email();
		list( $message, $subject ) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

		// Get selected email template id for specific form.
		$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

		$message = UR_Emailer::parse_smart_tags( $message, $values, $name_value );
		$subject = UR_Emailer::parse_smart_tags( $subject, $values, $name_value );
		$header  = UR_Emailer::parse_smart_tags( $header, $values, $name_value );

		if ( ur_string_to_bool( get_option( 'user_registration_enable_payment_pending_email', true ) ) ) {
			UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
		}
	}

	/**
	 * Sync External Field after user update profile.
	 *
	 * @param int   $user_id Userid.
	 * @param array $profile Form Data.
	 */
	public function user_registration_pro_sync_external_fields_after_save_profile_validation( $user_id, $profile ) {
		$form_id         = ur_get_form_id_by_userid( $user_id );
		$valid_form_data = array();
		$single_field    = array();
		// Handle if edit profile saving as ajax form submission.
		if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', true ) ) {
			$form_data = isset( $_POST['form_data'] ) ? json_decode( stripslashes( $_POST['form_data'] ) ) : array();

			foreach ( $form_data as $data ) {
				$single_field[ $data->field_name ] = isset( $data->value ) ? $data->value : '';
			}
		} else {
			$single_field = $_POST;
		}

		foreach ( $single_field as $post_key => $post_data ) {

			$pos = strpos( $post_key, 'user_registration_' );

			if ( false !== $pos ) {
				$new_string = substr_replace( $post_key, '', $pos, strlen( 'user_registration_' ) );

				if ( ! empty( $new_string ) ) {
					$tmp_array = ur_get_valid_form_data_format( $new_string, $post_key, $profile, $post_data );

					$valid_form_data = array_merge( $valid_form_data, $tmp_array );
				}
			}
		}
		if ( count( $valid_form_data ) < 1 ) {
			return;
		}
		user_registration_pro_sync_external_field( $valid_form_data, $form_id, $user_id );
	}

	/**
	 * Sync to External Fields After Woocommerce Checkout.
	 *
	 * @param int   $customer_id User ID.
	 * @param array $data Form Data.
	 */
	public function user_registration_pro_sync_external_fields_checkout_process( $customer_id, $data ) {
		$checkout = WC()->checkout();
		if ( ! $checkout->is_registration_required() && empty( $_POST['createaccount'] ) ) {
			return;
		}

		$form_id       = get_option( 'user_registration_woocommerce_settings_form', 0 );
		$checkout_sync = ur_option_checked( 'user_registration_woocommrece_settings_sync_checkout', false );

		if ( 0 < $form_id && $checkout_sync ) {

			$profile         = user_registration_form_data( $customer_id, $form_id );
			$valid_form_data = array();

			foreach ( $_POST as $post_key => $post_data ) {
				if ( 'billing_email' === $post_key ) {
					$post_key = 'user_registration_user_email';
				} elseif ( 'billing_first_name' === $post_key || 'billing_last_name' === $post_key ) {
					$post_key = 'billing_first_name' === $post_key ? 'user_registration_first_name' : 'user_registration_last_name';
				}

				$pos = strpos( $post_key, 'user_registration_' );

				if ( false !== $pos && isset( $profile[ $post_key ]['field_key'] ) ) {
					$new_string = substr_replace( $post_key, '', $pos, strlen( 'user_registration_' ) );

					if ( ! empty( $new_string ) ) {
						$tmp_array       = ur_get_valid_form_data_format( $new_string, $post_key, $profile, $post_data );
						$valid_form_data = array_merge( $valid_form_data, $tmp_array );
					}
				}
			}
			if ( count( $valid_form_data ) < 1 ) {
				return;
			}
			user_registration_pro_sync_external_field( $valid_form_data, $form_id, $customer_id );
		}
	}

	/**
	 * This function enables feature to auto login after reset password.
	 *
	 * @param object $user User Data.
	 */
	public function user_registration_auto_login_after_reset_password( $user ) {
		if ( isset( $user->ID ) ) {
			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user->ID );
			$ur_account_page_exists   = ur_get_page_id( 'myaccount' ) > 0;
			$ur_login_or_account_page = ur_get_page_permalink( 'myaccount' );

			if ( ! $ur_account_page_exists ) {
				$ur_login_or_account_page = ur_get_page_permalink( 'login' );
			}

			wp_redirect( $ur_login_or_account_page );
			exit;
		}
	}

	/**
	 * Send Admin Email when user verified thier email address.
	 *
	 * @param int  $user_id User Id.
	 * @param bool $user_reg_successful Flag which set as verified or not.
	 */
	public function user_registration_send_admin_after_email_verified( $user_id, $user_reg_successful ) {

		$form_id      = ur_get_form_id_by_userid( $user_id );
		$login_option = ur_get_user_login_option( $user_id );

		if ( 'admin_approval_after_email_confirmation' === $login_option && ur_string_to_bool( $user_reg_successful ) ) {
			$user       = get_user_by( 'id', $user_id );
			$name_value = ur_get_user_extra_fields( $user_id );
			// Get selected email template id for specific form.
			$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );
			UR_Emailer::send_approve_link_in_email( $user->user_email, $user->user_login, $user_id, '', $name_value, array(), $template_id );
//			User_Registration_Pro_Ajax::send_mail_to_admin_after_email_verified( $user->user_email, $user->user_login, $user_id, '', $name_value, array(), $template_id );
		} elseif ( 'email_confirmation' === $login_option && ur_string_to_bool( $user_reg_successful ) ) {
			/**
			 * Auto Login after Email Confirmation.
			 */
			$auto_login_after_email_confirmation = apply_filters( 'user_registration_auto_login_after_email_confirmation', false );

			if ( $auto_login_after_email_confirmation ) {
				wp_clear_auth_cookie();
				wp_set_auth_cookie( $user_id );
				$ur_account_page_exists   = ur_get_page_id( 'myaccount' ) > 0;
				$ur_login_or_account_page = ur_get_page_permalink( 'myaccount' );

				if ( ! $ur_account_page_exists ) {
					$ur_login_or_account_page = ur_get_page_permalink( 'login' );
				}

				wp_redirect( $ur_login_or_account_page );
				exit;
			}
		}
	}

	/**
	 * Redirect URL after login
	 *
	 * @param string $redirect_url URL.
	 * @param mixed  $user User details.
	 *
	 * @since 3.0.0
	 */
	public function user_registration_redirect_url_after_login( $redirect_url, $user ) {
		if ( ur_string_to_bool( get_option( 'user_registration_pro_role_based_redirection', false ) ) ) {
			$registration_redirect = get_option( 'ur_pro_settings_redirection_after_login', array() );

			foreach ( $registration_redirect as $role => $page_id ) {

				$roles = (array) $user->roles;
				if ( 0 !== $page_id && in_array( $role, $roles ) ) {
					$redirect_url = get_permalink( $page_id );
				}
			}
		}

		return $redirect_url;
	}

	/**
	 * Add Success Param after user registered.
	 *
	 * @param array $success_params Success Params.
	 * @param array $valid_form_data Form Data.
	 * @param int   $form_id Form id.
	 * @param int   $user_id User Id.
	 *
	 * @since 3.0.0
	 */
	public function add_role_based_redirect_url_to_response( $success_params, $valid_form_data, $form_id, $user_id ) {
		$login_option      = ur_get_form_setting_by_key( $form_id, 'user_registration_form_setting_login_options' );
		$paypal_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ) );
		$user              = get_user_by( 'id', absint( $user_id ) );

		if ( $paypal_is_enabled ) {
			return $success_params;
		}

		if ( ! empty( $form_id ) ) {
			$registration_redirect = get_option( 'ur_pro_settings_redirection_after_registration', array() );

			$redirect_option = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_redirect_after_registration', 'no-redirection' );

			if ( 'role-based-redirection' === $redirect_option ) {
				$registration_redirect = get_option( 'ur_pro_settings_redirection_after_registration', array() );
				$registration_redirect = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_role_based_redirection', $registration_redirect );

				if ( ! empty( $registration_redirect ) ) {
					foreach ( $registration_redirect as $role => $page_id ) {
						if ( 0 !== $page_id && in_array( $role, $user->roles ) ) {
							$success_params['role_based_redirect_url'] = get_permalink( $page_id );
							break;
						}
					}
				}
			}
		}

		return $success_params;
	}

	/**
	 * Role based redirect after
	 *
	 * @param mixed $redirect_url Redirect_url
	 *
	 * @since 3.0.0
	 */
	public function role_based_redirect_after_logout( $redirect_url ) {
		if ( ur_string_to_bool( get_option( 'user_registration_pro_role_based_redirection', false ) ) ) {
			$registration_redirect = get_option( 'ur_pro_settings_redirection_after_logout', array() );
			foreach ( $registration_redirect as $role => $page_id ) {

				if ( 0 !== $page_id && in_array( $role, wp_get_current_user()->roles ) ) {
					$redirect_url = get_permalink( $page_id );
				}
			}
		}

		return $redirect_url;
	}


	/**
	 * Add payment endpoint.
	 */
	public function user_registration_add_delete_account_endpoint() {
		$mask = Ur()->query->get_endpoints_mask();
		add_rewrite_endpoint( 'delete-account', $mask );
	}

	/**
	 * Add privacy tab endpoint.
	 */
	public function user_registration_add_privacy_tab_endpoint() {
		$mask = Ur()->query->get_endpoints_mask();
		add_rewrite_endpoint( 'user-privacy', $mask );
		flush_rewrite_rules();
	}


	/**
	 * Add the item to the $items array
	 *
	 * @param mixed $items Items.
	 */
	public function delete_account_item_tab( $items ) {
		$new_items                   = array();
		$new_items['delete-account'] = __( 'Delete Account', 'user-registration' );

		return $this->delete_account_insert_before_helper( $items, $new_items, 'user-logout' );
	}

	/**
	 * Add the item to $items array.
	 *
	 * @param array $items Items.
	 */
	public function ur_privacy_tab( $items ) {
		$new_items                 = array();
		$new_items['user-privacy'] = __( 'Privacy', 'user-registration' );
		$items                     = array_merge( $items, $new_items );

		return $this->delete_account_insert_before_helper( $items, $new_items, 'user-logout' );
	}

	/**
	 * Delete Account insert after helper.
	 *
	 * @param mixed $items Items.
	 * @param mixed $new_items New items.
	 * @param mixed $before Before item.
	 */
	public function delete_account_insert_before_helper( $items, $new_items, $before ) {

		// Search for the item position.
		$position = array_search( $before, array_keys( $items ), true );

		// Insert the new item.
		$return_items  = array_slice( $items, 0, $position, true );
		$return_items += $new_items;
		$return_items += array_slice( $items, $position, count( $items ) - $position, true );

		return $return_items;
	}

	/**
	 * Register script files and localization for js.
	 */
	public function register_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_style( 'user-registration-pro-frontend-style', UR()->plugin_url() . '/assets/css/user-registration-pro-frontend.css', UR_VERSION );
		wp_register_script(
			'user-registration-pro-frontend-script',
			UR()->plugin_url() . '/assets/js/pro/frontend/user-registration-pro-frontend' . $min . '.js',
			array(
				'jquery',
				'sweetalert2',
			),
			UR_VERSION
		);
		wp_register_script(
			'ur-inactive',
			UR()->plugin_url() . '/assets/js/pro/frontend/user-registration-inactive' . $min . '.js',
			array(
				'jquery',
				'sweetalert2',
			),
			UR_VERSION,
			true
		);
		wp_localize_script(
			'ur-inactive',
			'ur_inactive_params',
			array(
				'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				'inactive_logout_nonce'          => wp_create_nonce( 'inactive_logout_nonce' ),
				'inactive_time_period'           => get_option( 'user_registration_auto_logout_inactivity_time', '' ),
				'time_countdown_inactive_period' => get_option( 'user_registration_timeout_countdown_inactive_period', 10 ),
				'inactive_title'                 => __( 'Inactivity Detected', 'user-registration' ),
				'stay_signin'                    => sprintf( '%s <b></b>', esc_html__( 'Stay Signed In', 'user-registration' ) ),
				'inactive_message'               => sprintf( '%s <br> %s <b></b>', esc_html__( 'You are being timed-out out due to inactivity. Please choose to stay signed or to logoff.', 'user-registration' ), esc_html__( 'Otherwise, you will be logged off authomatically', 'user-registration' ) ),
				'inactive_logout_message'        => __( 'You have been logged out because of inactivity', 'user-registration' ),
				'reload_text'                    => __( 'Close without Reloading', 'user-registration' ),
				'inactive_ok'                    => __( 'Ok', 'user-registration' ),
			)
		);

		// Register and Enqueue scripts for signature pad.
		wp_register_script( 'user-registration-signature-pad-script', UR()->plugin_url() . '/assets/js/pro/signature_pad/signature_pad.umd.js', array( 'jquery' ), UR_VERSION, true );

		wp_register_script( 'user-registration-signature-frontend', UR()->plugin_url() . '/assets/js/pro/frontend/signature' . $min . '.js', array( 'jquery', 'user-registration-signature-pad-script' ), UR_VERSION, true );
	}

	/**
	 * Load script files and localization for js.
	 *
	 * @param array $form_data_array Form Data.
	 * @param int   $form_id Form Id.
	 */
	public function load_scripts( $form_data_array, $form_id ) {

		$delete_account_option      = get_option( 'user_registration_pro_general_setting_delete_account', 'disable' );
		$delete_account_popup_html  = '';
		$delete_account_popup_title = apply_filters( 'user_registration_pro_delete_account_popup_title', __( 'Are you sure you want to delete your account? ', 'user-registration' ) );

		if ( 'prompt_password' === $delete_account_option ) {
			$delete_account_popup_html = apply_filters( 'user_registration_pro_delete_account_popup_message', __( '<p>This will erase all of your account data from the site. To delete your account enter your password below.</p>', 'user-registration' ) ) . '<input type="password" id="password" class="swal2-input" placeholder="' . apply_filters( 'user_registration_pro_delete_account_password_placeholder', esc_attr__( 'Password', 'user-registration' ) ) . '">';
		} elseif ( 'direct_delete' === $delete_account_option ) {
			$delete_account_popup_html = apply_filters( 'user_registration_pro_delete_account_popup_message', __( '<p>This will erase all of your account data from the site.</p>.', 'user-registration' ) );
		}

		// check restrict copy/cut/paste option.
		$restricted_fields = array();
		$field_name        = '';
		global $restricted_fields, $captcha_message;
		foreach ( $form_data_array as $form_data ) {
			foreach ( $form_data as $field_data ) {
				foreach ( $field_data as $data ) {
					if ( isset( $data->advance_setting->disable_copy_paste ) && 'true' === $data->advance_setting->disable_copy_paste ) {
						$restricted_fields[] = $data->field_key;
					}
					if ( isset( $data->advance_setting->captcha_message ) ) {
						$field_name      = $data->general_setting->field_name;
						$captcha_message = ! empty( $data->advance_setting->captcha_message ) ? $data->advance_setting->captcha_message : esc_html__( 'Incorrect Answer', 'user-registration' );
					}
				}
			}
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		require_once 'form/class-ur-form-field-captcha.php';
		$ur_captcha_instance = UR_Form_Field_Captcha::get_instance();
		$math_data           = $ur_captcha_instance->math;

		// Tooltipster Styles.
		wp_enqueue_script( 'tooltipster', UR()->plugin_url() . '/assets/js/tooltipster/tooltipster.bundle' . $min . '.js', array( 'jquery' ), UR_VERSION );
		wp_enqueue_style( 'tooltipster', UR()->plugin_url() . '/assets/css/tooltipster/tooltipster.bundle.css', UR_VERSION );
		wp_enqueue_style( 'tooltipster', UR()->plugin_url() . '/assets/css/tooltipster/tooltipster-sideTip-borderless.min.css', UR_VERSION );

		// Signature Scripts.
		wp_enqueue_script( 'user-registration-signature-frontend' );

		wp_register_style( 'user-registration-pro-frontend-style', UR()->plugin_url() . '/assets/css/user-registration-pro-frontend.css', UR_VERSION );
		wp_enqueue_style( 'user-registration-pro-frontend-style' );
		wp_enqueue_script( 'user-registration-pro-frontend-script' );
		$symbol = '';
		$currency   = get_option( 'user_registration_payment_currency', 'USD' );

		if ( ur_check_module_activation( 'payments' ) || is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' ) || is_plugin_active( 'user-registration-authorize-net/user-registration-authorize-net.php' ) ) {
			$currencies = ur_payment_integration_get_currencies();
			$symbol     = $currencies[ $currency ]['symbol'];
		}
		wp_localize_script(
			'user-registration-pro-frontend-script',
			'user_registration_pro_frontend_data',
			array(
				'ajax_url'                        => admin_url( 'admin-ajax.php' ),
				'user_data_nonce'                 => wp_create_nonce( 'user_data_nonce' ),
				'is_user_logged_in'               => is_user_logged_in(),
				'has_create_user_capability'      => current_user_can( apply_filters( 'ur_registration_user_capability', 'create_users' ) ),
				'delete_account_option'           => get_option( 'user_registration_pro_general_setting_delete_account', 'disable' ),
				'delete_account_popup_title'      => $delete_account_popup_title,
				'delete_account_popup_html'       => $delete_account_popup_html,
				'delete_account_button_text'      => __( 'Delete Account', 'user-registration' ),
				'privacy_sending_text'            => __( 'Sending ...', 'user-registration' ),
				'applying_coupon_text'            => __( 'Applying ...', 'user-registration' ),
				'single_item_discount'            => __( 'Discount on Single Item : ', 'user-registration' ),
				'total_item_discount'             => __( 'Discount on Total : ', 'user-registration' ),
				'cancel_button_text'              => __( 'Cancel', 'user-registration' ),
				'please_enter_password'           => __( 'Please enter password', 'user-registration' ),
				'account_deleted_message'         => __( 'Account successfully deleted!', 'user-registration' ),
				'clear_button_text'               => __( 'Are you sure you want to clear this form?', 'user-registration' ),
				'message_email_suggestion_fields' => get_option( 'user_registration_form_submission_email_suggestion', esc_html__( 'Did you mean {suggestion}?', 'user-registration' ) ),
				'message_email_suggestion_title'  => esc_attr__( 'Click to accept this suggestion.', 'user-registration' ),
				'mailcheck_enabled'               => ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_email_suggestion', false ) ),
				'mailcheck_domains'               => array_map( 'sanitize_text_field', (array) apply_filters( 'user_registration_mailcheck_domains', array() ) ),
				'mailcheck_toplevel_domains'      => array_map( 'sanitize_text_field', (array) apply_filters( 'user_registration_mailcheck_toplevel_domains', array( 'dev' ) ) ),
				'keyboard_friendly_form_enabled'  => ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_keyboard_friendly_form', false ) ),
				'restrict_copy_paste_fields'      => $restricted_fields,
				'captcha_equation_param'          => array(
					'min' => $math_data['min'],
					'max' => $math_data['max'],
					'cal' => $math_data['cal'],
				),
				'captcha_error_message'           => ur_string_translation( $form_id, 'user_registration_' . $field_name . '_captcha_message', $captcha_message ),
				'email_sent_successfully_message' => esc_html__( 'Email has been Sent Successfully', 'user-registration' ),
				'currency_symbol'                 => $symbol,
				'currency_code'                   => $currency,
			)
		);
		if ( ur_check_module_activation( 'payments' ) || is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' ) || is_plugin_active( 'user-registration-authorize-net/user-registration-authorize-net.php' ) ) {
			$enabled_paypal_gateways        = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ) );
			$enabled_stripe_gateways        = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_stripe', false ) );
			$enabled_authorize_net_gateways = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_authorize_net', false ) );
			$enabled_mollie = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_mollie', false ) );

			if ( $enabled_paypal_gateways || $enabled_stripe_gateways || $enabled_authorize_net_gateways || $enabled_mollie ) {
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				// Enqueue scripts.
				wp_enqueue_style( 'user-registration-payment-my-account-style', plugins_url( '/assets/css/user-registration-payment-my-account.css', UR_PLUGIN_FILE ), array(), UR_VERSION, 'all' );
				wp_enqueue_style( 'user-registration-payment-invoice-style', plugins_url( '/assets/css/user-registration-payment-invoice.css', UR_PLUGIN_FILE ), array(), UR_VERSION, 'all' );
				wp_enqueue_script( 'user-registration-payment', plugins_url( "/assets/js/pro/frontend/user-registration-payment{$suffix}.js", UR_PLUGIN_FILE ), array( 'user-registration' ), UR_VERSION, true );
				wp_enqueue_script( 'user-registration-payment-my-account', plugins_url( "/assets/js/pro/frontend/user-registration-payment-my-account{$suffix}.js", UR_PLUGIN_FILE ), array( 'user-registration' ), UR_VERSION, true );
			}
		}
		// Slot booking.
		wp_localize_script(
			'user-registration-pro-frontend-script',
			'user_registration_pro_frontend_slot_booking_data',
			array(
				'ajax_url'                 => admin_url( 'admin-ajax.php' ),
				'slot_booking_data_nonce'  => wp_create_nonce( 'slot_booking_data_nonce' ),
				'date_slot_booking_notice' => sprintf(
					apply_filters(
						'date_slot_booking_notice',
						esc_html__(
							'Please also choose the time for slot booking',
							'user-registration'
						)
					),
				),
				'time_slot_booking_notice' => sprintf(
					apply_filters(
						'date_slot_booking_notice',
						esc_html__(
							'Please choose the date field first for slot booking',
							'user-registration'
						)
					),
				),
			)
		);
	}

	/**
	 * Load script files mailcheck.
	 *
	 * @param array $form_data_array Form Data.
	 * @param int   $form_id Form Id.
	 */
	public function enqueue_mailcheck_script( $form_data_array, $form_id ) {

		wp_register_script( 'mailcheck', UR()->plugin_url() . '/assets/js/pro/mailcheck/mailcheck.min.js', array( 'jquery' ), '1.1.2' );
		// Enqueue mailcheck
		if ( ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_email_suggestion', false ) ) ) {
			wp_enqueue_script( 'mailcheck' );
		}
	}

	/**
	 * Add honeypot field template to exisiting form in frontend.
	 *
	 * @param array $grid_data Grid data of Form parsed from form's post content.
	 * @param int   $form_id ID of the form.
	 */
	public function user_registration_user_pass_form_field_filter( $grid_data, $form_id ) {
		$enable_auto_password_generation = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_password_activate' ) );

		if ( $enable_auto_password_generation ) {
			foreach ( $grid_data as $grid_data_key => $single_item ) {
				if( isset( $single_item->field_key ) ) {
					if ( 'user_pass' === $single_item->field_key || 'user_confirm_password' === $single_item->field_key ) {
						unset( $grid_data[ $grid_data_key ] );
					}
				}
			}
		}

		return $grid_data;
	}

	/**
	 * Retrieves and displays all popups rendered in nav menu item.
	 */
	public function user_registration_pro_display_active_menu_popup() {
		$menus  = get_nav_menu_locations();
		$popups = array();

		foreach ( $menus as $key => $value ) {

			if ( isset( $value ) ) {

				$menu_item = wp_get_nav_menu_items( $menus[ $key ] );

				if ( is_array( $menu_item ) ) {

					foreach ( $menu_item as $item ) {

						if ( $item && 'user-registration-modal-link' === $item->classes[0] ) {
							$popup_id = substr( $item->classes[1], 29 );

							// Check if multiple popups with same id exists.
							if ( ! in_array( $popup_id, $popups ) ) {
								array_push( $popups, $popup_id );
								$post = get_post( $popup_id );

								if ( isset( $post->post_content ) ) {
									$popup_content = json_decode( $post->post_content );

									if ( ur_string_to_bool( $popup_content->popup_status ) ) {

										$current_user_capability = apply_filters( 'ur_registration_user_capability', 'create_users' );

										if ( ( is_user_logged_in() && current_user_can( $current_user_capability ) ) || ! is_user_logged_in() ) {
											$display = 'display:none;';
											ur_get_template(
												'pro/popup-registration.php',
												array(
													'display'       => $display,
													'popup_content' => $popup_content,
													'popup_id'      => $popup_id,
												),
												'user-registration-pro',
												UR_TEMPLATE_PATH
											);
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Add honeypot field template to exisiting form in frontend.
	 *
	 * @param array $form_data_array Form data parsed from form's post content.
	 * @param int   $form_id ID of the form.
	 *
	 * @since 1.0.0
	 */
	public function user_registration_form_field_honeypot( $form_data_array, $form_id ) {
		$enable_spam_protection = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_pro_spam_protection_by_honeypot_enable' ) );

		if ( $enable_spam_protection ) {
			$names = array( 'Name', 'Phone', 'Comment', 'Message', 'Email', 'Website' );
			$name  = $names[ array_rand( $names ) ];
			?>
<div class="ur-form-row ur-honeypot-container"
	style="display: none!important;position: absolute!important;left: -9000px!important;">
	<div class="ur-form-grid ur-grid-1" style="width:99%">
		<div class="ur-field-item field-honeypot">
			<div class="form-row " id="honeypot_field" data-priority="">
				<label for="honeypot" class="ur-label"><?php echo esc_html( $name ); ?>
				</label>
				<input data-rules="" data-id="honeypot" type="text" class="input-text input-text ur-frontend-field  "
					name="honeypot" id="honeypot" placeholder="" value="" data-label="<?php esc_html( $name ); ?>">
			</div>
		</div>
	</div>
</div>
			<?php
		}
	}

	/**
	 * Show Reset Button if it's enable from form settings.
	 *
	 * @param int $form_id Form ID.
	 */
	public function ur_pro_add_reset_button( $form_id ) {
		$enable_reset_button = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_reset_button' ) );

		if ( $enable_reset_button ) {
			$reset_btn_class = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_form_reset_class' );
			?>
<div class="reset-btn">
	<a href="javascript:void(0)" class="ur-reset-button <?php echo esc_attr( $reset_btn_class ); ?>"><span
			class="dashicons dashicons-image-rotate"></span>
			<?php
					$reset = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_form_reset_label' );
					echo ur_string_translation( $form_id, 'user_registration_form_setting_form_reset_label', $reset );
			?>
	</a>
</div>
			<?php
		}
	}

	/**
	 * Set Transient of redirect url which holds previous page for one day.
	 */
	public function user_registration_set_redirect_url() {
		// Set Transient for one day.
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			set_transient( 'originalLoginRefererURL', $_SERVER['HTTP_REFERER'], 60 * 60 * 24 );
		}
	}

	/**
	 * Redirect Back to previous page after login .
	 *
	 * @param string $redirect_url URL.
	 * @param mixed  $user User data.
	 *
	 * @since 3.0.0
	 */
	public function user_registration_redirect_back( $redirect_url, $user ) {

		if ( ! empty( get_transient( 'originalLoginRefererURL' ) ) ) {
			$redirect_url = get_transient( 'originalLoginRefererURL' );
			delete_transient( 'originalLoginRefererURL' );
		}

		return $redirect_url;
	}

	/**
	 * Auto populate form field via query string.
	 *
	 * @param array $grid_data Grid data.
	 * @param mixed $form_id Form id.
	 *
	 * @since 3.0.0
	 */
	public function user_registration_pro_auto_populate_form_field( $grid_data, $form_id ) {

		$get_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$get_url = parse_url( $get_url );
		if ( ! empty( $get_url['query'] ) ) {
			parse_str( $get_url['query'], $query_params );
			foreach ( $query_params as $key => $value ) {
				// populating form field value with query string paramter value
				foreach ( $grid_data as $grid_data_key => $single_item ) {

					if ( isset( $single_item->advance_setting->enable_prepopulate ) && ur_string_to_bool( $single_item->advance_setting->enable_prepopulate ) ) {
						$param_name = $single_item->advance_setting->parameter_name;

						if ( $param_name === $key ) {

							if ( $single_item->field_key === 'subscription_plan' || $single_item->field_key === 'multiple_choice' || $single_item->field_key === 'checkbox' || $single_item->field_key === 'multi_select2' ) {

								$selected = ! empty( $single_item->general_setting->default_value ) ? $single_item->general_setting->default_value : array();
								foreach ( $single_item->general_setting->options as $key => $option_value ) {

									$multi_val = explode( ',', $value );

									foreach ( $multi_val as $value ) {

										if ( $value == $option_value ) {
											array_push( $selected, $value );
										}
									}

									$single_item->general_setting->default_value = $selected;
								}
							} else {
								if ( $single_item->field_key === 'select2' ) {
									$single_item->general_setting->default_value = sanitize_text_field( $value );
								}
								$single_item->advance_setting->default_value = sanitize_text_field( $value );
							}
						}
					}
				}
			}
		}

		return $grid_data;
	}

	/**
	 * Remove login from querystring,  and redirect to account page to show the form.
	 *
	 * @since 3.0.0
	 */
	public static function redirect_prevent_concurrent_link() {

		if ( is_ur_account_page() && ! empty( $_GET['action'] ) && ! empty( $_GET['login'] ) ) {

			if ( 'force-logout' === $_GET['action'] ) {
				$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['action'] ) );
				wp_safe_redirect( add_query_arg( 'force-logout', $_GET['login'], user_registration_force_logout() ) );
				exit;
			}
		}
	}

	/**
	 * Validate unique field.
	 *
	 * @param array $single_form_field Form field.
	 * @param array $form_data Submit data.
	 * @param array $filter_hook Filter hook.
	 * @param int   $form_id Form Id.
	 *
	 * @since 4.0.3
	 */
	public function user_registration_pro_pattern_validation( $single_form_field, $form_data, $filter_hook, $form_id ) {
		$single_field    = array();
		$enable_pattern  = isset( $single_form_field->advance_setting->enable_pattern ) ? ur_string_to_bool( $single_form_field->advance_setting->enable_pattern ) : false;
		$pattern_value   = ! empty( $single_form_field->advance_setting->pattern_value ) ? $single_form_field->advance_setting->pattern_value : '';
		$pattern_message = ! empty( $single_form_field->advance_setting->pattern_message ) ? $single_form_field->advance_setting->pattern_message : __( 'Please provide a valid value for this field', 'user-registration' );
		$field_name      = isset( $form_data->field_name ) ? $form_data->field_name : '';
		$value           = isset( $form_data->value ) ? $form_data->value : '';

		if ( isset( $_POST['action'] ) && ( 'save_profile_details' == $_POST['action'] || 'user_registration_update_profile_details' == $_POST['action'] ) ) {
			// Handle if edit profile saving as ajax form submission.
			if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', false ) ) {
				$profile_data = isset( $_POST['form_data'] ) ? json_decode( stripslashes( $_POST['form_data'] ) ) : array();
				foreach ( $profile_data as $data ) {
					$single_field[ $data->field_name ] = isset( $data->value ) ? $data->value : '';
				}
			} else {
				$single_field = $_POST;
			}
			$profile_field_name = 'user_registration_' . $field_name;

			if ( ! $enable_pattern ) {
				return;
			}

			if ( in_array( $profile_field_name, array_keys( $single_field ) ) && ! empty( $single_field[ $profile_field_name ] ) ) {
				$validation_message = array(
					/* translators: %s - validation message */
					$profile_field_name => sprintf( __( '%s', 'user-registration' ), $pattern_message ),
					'individual'        => true,
				);
				if ( ! preg_match( '/' . $pattern_value . '/', $single_field[ $profile_field_name ] ) ) {
					if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', false ) ) {
						wp_send_json_error(
							array(
								'message' => $validation_message,
							)
						);
					} else {

						ur_add_notice( sprintf( __( '<strong>%1$s : </strong> %2$s', 'user-registration' ), $form_data->label, $pattern_message ), 'error' );
					}
				}
			}
		} elseif ( $enable_pattern && ! empty( $value ) ) {
			$validation_message = array(
				/* translators: %s - validation message */
				$field_name  => sprintf( __( '%s', 'user-registration' ), $pattern_message ),
				'individual' => true,
			);
			if ( ! preg_match( '/' . $pattern_value . '/', $value ) ) {
				wp_send_json_error(
					array(
						'message' => array(
							'message' => $validation_message,
						),
					)
				);
			}
		}
	}

	/**
	 * Validate Blacklist Words.
	 *
	 * @param array $single_form_field Form field.
	 * @param array $form_data Submit data.
	 * @param array $filter_hook Filter hook.
	 * @param int   $form_id Form Id.
	 *
	 * @since 4.0.3
	 */
	public function user_registration_blacklist_words_validation( $single_form_field, $form_data, $filter_hook, $form_id ) {

		$enabled_blacklisting = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_blacklist_words', false );

		if ( $enabled_blacklisting ) {
			$fields = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_blacklisted_words_field_settings', array() );
			$words  = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_blacklisted_words_settings', '' );

			if ( ! empty( $words ) && ! empty( $fields ) ) {
				$fields = maybe_unserialize( $fields );
				if ( ! is_array( $fields ) ) {
					$new_fields[] = $fields;
					$fields       = $new_fields;
				}

				$words = explode( ',', $words );
				foreach ( $words as $word ) {
					$new_words[] = trim( $word );
					$words       = $new_words;
				}
				$field_name = isset( $form_data->field_name ) ? $form_data->field_name : '';

				if ( in_array( $field_name, $fields ) ) {
					$value = isset( $form_data->value ) ? $form_data->value : '';

					$validation_message = array(
						/* translators: %s - validation message */
						$field_name  => sprintf( __( '%s', 'user-registration' ), apply_filters( 'user_registration_pro_blacklist_words_error_message', 'Please enter any other value.' ) ),
						'individual' => true,
					);

					if ( in_array( $value, $words ) ) {
						wp_send_json_error(
							array(
								'message' => array(
									'message' => $validation_message,
								),
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Validate field for blacklisted words after when user update their profile.
	 *
	 * @param int   $user_id User Id.
	 * @param array $profile Profile fields.
	 */
	public function validate_blacklist_words_field_after_profile_update( $user_id, $profile ) {
		$single_field    = array();
		$valid_form_data = array();
		$form_id         = get_user_meta( $user_id, 'ur_form_id', true );
		$field_name      = '';

		$enabled_blacklisting = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_blacklist_words', false );

		if ( ! $enabled_blacklisting ) {
			return;
		}

		$fields = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_blacklisted_words_field_settings', array() );
		$words  = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_blacklisted_words_settings', '' );

		if ( empty( $words ) && empty( $fields ) ) {
			return;
		}

		$fields = maybe_unserialize( $fields );
		if ( ! is_array( $fields ) ) {
			$new_fields[] = $fields;
			$fields       = $new_fields;
		}

		$words = explode( ',', $words );
		foreach ( $words as $word ) {
			$new_words[] = trim( $word );
			$words       = $new_words;
		}

		// Handle if edit profile saving as ajax form submission.
		if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', false ) ) {
			$form_data = isset( $_POST['form_data'] ) ? json_decode( stripslashes( $_POST['form_data'] ) ) : array();

			foreach ( $form_data as $data ) {
				$single_field[ $data->field_name ] = isset( $data->value ) ? $data->value : '';
			}
		} else {
			$single_field = $_POST;
		}

		foreach ( $single_field as $post_key => $post_data ) {

			$pos = strpos( $post_key, 'user_registration_' );

			if ( false !== $pos ) {
				$new_string = substr_replace( $post_key, '', $pos, strlen( 'user_registration_' ) );

				if ( ! empty( $new_string ) ) {
					$tmp_array       = ur_get_valid_form_data_format( $new_string, $post_key, $profile, $post_data );
					$valid_form_data = array_merge( $valid_form_data, $tmp_array );
				}
			}
		}

		if ( count( $valid_form_data ) < 1 ) {
			return;
		}

		foreach ( $profile as $key_name => $field_item ) {
			$field_name = str_replace( 'user_registration_', '', $key_name );
			if ( in_array( $field_name, $fields ) && isset( $valid_form_data[ $field_name ] ) ) {
				$field_value = isset( $valid_form_data[ $field_name ]->value ) ? $valid_form_data[ $field_name ]->value : '';

				if ( in_array( $field_value, $words ) ) {

					if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', false ) ) {
						$field_name         = 'user_registration_' . $field_name;
						$validation_message = array(
							/* translators: %s - validation message */
							$field_name  => sprintf( __( '%s', 'user-registration' ), apply_filters( 'user_registration_pro_blacklist_words_error_message', 'Please enter any other value.' ) ),
							'individual' => true,
						);

						wp_send_json_error(
							array(
								'message' => array(
									'message' => $validation_message,
								),
							)
						);
					} else {
						ur_add_notice( sprintf( __( '<strong>%1$s : </strong> %2$s', 'user-registration' ), $valid_form_data[ $field_name ]->label, apply_filters( 'user_registration_pro_blacklist_words_error_message', 'Please enter any other value.' ) ), 'error' );
					}
				}
			}
		}
	}

	/**
	 * Validate unique field.
	 *
	 * @param array $single_form_field Form field.
	 * @param array $form_data Submit data.
	 * @param array $filter_hook Filter hook.
	 * @param int   $form_id Form Id.
	 *
	 * @since 3.0.8
	 */
	public function user_registration_pro_validate_unique_field( $single_form_field, $form_data, $filter_hook, $form_id ) {
		$validate_unique = isset( $single_form_field->advance_setting->validate_unique ) ? ur_string_to_bool( $single_form_field->advance_setting->validate_unique ) : false;
		$message         = ! empty( $single_form_field->advance_setting->validation_message ) ? $single_form_field->advance_setting->validation_message : esc_html__( 'This field value need to be unique.', 'user-registration' );
		$message         = ur_string_translation( $form_id, 'ur_validation_message_for_duplicate', $message );
		$field_name      = isset( $form_data->field_name ) ? $form_data->field_name : '';
		$value           = isset( $form_data->value ) ? $form_data->value : '';
		if ( isset( $validate_unique ) && $validate_unique ) {
			$duplicates = ur_validate_unique_field(
				array(
					'ur_form_id' => $form_id,
					'search'     => $value,
					'field_name' => ur_get_field_name_with_prefix_usermeta( $field_name ),
				)
			);
		}
		$validation_message = array(
			/* translators: %s - validation message */
			$field_name  => sprintf( __( '%s', 'user-registration' ), $message ),
			'individual' => true,
		);
		if ( ! empty( $duplicates ) && ! empty( $value ) ) {
			if ( $validate_unique ) {
				wp_send_json_error(
					array(
						'message' => array(
							'message' => $validation_message,
						),
					)
				);
			}
		}
	}

	/**
	 * Validate field as unique after when user update their profile.
	 *
	 * @param int   $user_id User Id.
	 * @param array $profile Profile fields.
	 */
	public function validate_unique_field_after_profile_update( $user_id, $profile ) {
		$single_field    = array();
		$valid_form_data = array();

		// Handle if edit profile saving as ajax form submission.
		if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', false ) ) {
			$form_data = isset( $_POST['form_data'] ) ? json_decode( stripslashes( $_POST['form_data'] ) ) : array();

			foreach ( $form_data as $data ) {
				$single_field[ $data->field_name ] = isset( $data->value ) ? $data->value : '';
			}
		} else {
			$single_field = $_POST;
		}

		foreach ( $single_field as $post_key => $post_data ) {

			$pos = strpos( $post_key, 'user_registration_' );

			if ( false !== $pos ) {
				$new_string = substr_replace( $post_key, '', $pos, strlen( 'user_registration_' ) );

				if ( ! empty( $new_string ) ) {
					$tmp_array       = ur_get_valid_form_data_format( $new_string, $post_key, $profile, $post_data );
					$valid_form_data = array_merge( $valid_form_data, $tmp_array );
				}
			}
		}

		if ( count( $valid_form_data ) < 1 ) {
			return;
		}

		$form_id    = get_user_meta( $user_id, 'ur_form_id', true );
		$field_name = '';
		$message    = '';
		$duplicate  = '';
		foreach ( $profile as $key_name => $field_item ) {
			foreach ( $field_item as $key => $field_value ) {

				if ( isset( $key ) && 'validate_unique' === $key ) {
					if ( ur_string_to_bool( $field_value ) ) {
						$field_name = str_replace( 'user_registration_', '', $key_name );
						$message    = ur_string_translation( $form_id, 'ur_validation_message_for_duplicate', $field_item['validate_message'] );

						if ( in_array( $field_name, array_keys( $valid_form_data ), true ) ) {
							$duplicate = ur_validate_unique_field(
								array(
									'ur_form_id' => $form_id,
									'search'     => $valid_form_data[ $field_name ]->value,
									'field_name' => ur_get_field_name_with_prefix_usermeta( $field_name ),
								)
							);
						}

						if ( ! empty( $duplicate ) && ! in_array( $user_id, $duplicate ) ) {

							if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', false ) ) {
								$field_name         = 'user_registration_' . $field_name;
								$validation_message = array(
									/* translators: %s - validation message */
									$field_name  => sprintf( __( '%s', 'user-registration' ), $message ),
									'individual' => true,
								);

								wp_send_json_error(
									array(
										'message' => $validation_message,
									)
								);
							} else {
								ur_add_notice( sprintf( __( '<strong>%1$s : </strong> %2$s', 'user-registration' ), $valid_form_data[ $field_name ]->label, $message ), 'error' );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Class to show if field icon enabled.
	 *
	 * @param [string] $class Classnames.
	 * @param [int]    $form_id Form Id.
	 *
	 * @return [string] $class Classname.
	 */
	public function ur_get_field_icon_enabled_class( $class, $form_id ) {
		$enable_field_icon = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_field_icon' ) );

		if ( ! $enable_field_icon ) {
			$class .= 'without_icon';
		}

		return $class;
	}

	/**
	 * Output Field Icon html.
	 *
	 * @param [string] $field Field html.
	 * @param [int]    $form_id Form Id.
	 * @param [array]  $args Field Args.
	 *
	 * @return [string]  $field.
	 */
	public function ur_get_field_icon( $field, $form_id, $args ) {
		$enable_field_icon = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_field_icon' ) );

		if ( $enable_field_icon && 'file' !== $args['type'] ) {
			$field .= '<span class="' . esc_attr( $args['icon'] ) . '"></span>';
		}

		return $field;
	}

	/**
	 * Privacy tab content.
	 */
	public function user_registration_privacy_tab_endpoint_content() {
		$user_id         = get_current_user_id();
		$profile_noindex = ur_string_to_bool( get_user_meta( $user_id, 'ur_profile_noindex', true ) );

		/**
		 * Filter user registration show profile value.
		 *
		 * @since 4.0.1
		 */
		$show_profile = ur_string_to_bool( apply_filters( 'user_registration_show_profile_value', get_user_meta( $user_id, 'ur_show_profile', true ) ) );

		$enable_profile_privacy        = get_option( 'user_registration_enable_profile_privacy', true );
		$enable_profile_indexing       = get_option( 'user_registration_enable_profile_indexing', true );
		$enable_download_personal_data = get_option( 'user_registration_enable_download_personal_data', true );
		$enable_erase_personal_data    = get_option( 'user_registration_enable_erase_personal_data', true );
		/**
		 * Used to hide the Update privacy button.
		 *
		 * @since  4.1.2
		 */
		$is_update_privacy_required = false;
		if ( $enable_profile_privacy || $enable_profile_indexing ) {
			$is_update_privacy_required = true;
		}
		if ( ! ur_string_to_bool( $enable_profile_privacy ) && ! ur_string_to_bool( $enable_profile_indexing ) && ! ur_string_to_bool( $enable_download_personal_data ) && ! ur_string_to_bool( $enable_erase_personal_data ) ) {
			printf( '<p>%s</p>', esc_html__( 'To access the features of the privacy tab, please reach out to your Administrator to enable them.', 'user-registration' ) );
		} else {
			$layout = get_option( 'user_registration_my_account_layout', 'horizontal' );

			if ( 'vertical' === $layout && isset( ur_get_account_menu_items()['user-privacy'] ) ) {
				?>
				<div class="user-registration-MyAccount-content__header">
					<h1><?php echo wp_kses_post( ur_get_account_menu_items()['user-privacy'] ); ?></h1>
				</div>
				<?php
			}
			?>
			<div class="user-registration-MyAccount-content__body">
				<div class="ur-frontend-form login" id="ur-frontend-form" style="padding: 30px;">
					<form method="post" class="user-registration-PrivacyTab">
						<div class="ur-form-row">
							<div class="ur-form-grid">
								<?php
								if ( ur_string_to_bool( $enable_profile_privacy ) ) :
									?>
									<div class="user-registration-form-row user-registration-form-row--wide form-row form-row-wide">
										<div class="ur-privacy-field-label">
											<label>
												<?php esc_html_e( 'Profile Privacy', 'user-registration' ); ?>
												<span class='ur-portal-tooltip tooltipstered' data-tip="
														<?php
														esc_html_e(
															'You can choose to make your profile private, which will prevent it from appearing in the frontend listing.',
															'user-registration'
														)
														?>
														"></span>
											</label>
										</div>
										<div class="ur-privacy-input">
											<div class="ur-privacy-input--radio-box">
												<input type="radio" id="everyone" value="no" name="ur_show_profile"
													<?php echo ! $show_profile ? 'checked' : ''; ?> /><label
													for="everyone"><?php esc_html_e( 'Everyone', 'user-registration' ); ?></label>
											</div>
											<div class="ur-privacy-input--radio-box">
												<input type="radio" id="onlyme" value="yes" name="ur_show_profile"
													<?php echo $show_profile ? 'checked' : ''; ?> /><label
													for="onlyme"><?php esc_html_e( 'Only me', 'user-registration' ); ?></label>
											</div>
										</div>
									</div>
									<?php
								endif;

								if ( ur_string_to_bool( $enable_profile_indexing ) ) :
									?>
									<div class="user-registration-form-row user-registration-form-row--wide form-row form-row-wide">
										<div class="ur-privacy-field-label">
											<label>
										<?php esc_html_e( 'Disallow Search Engine Indexing', 'user-registration' ); ?>
												<span class='ur-portal-tooltip tooltipstered' data-tip="
												<?php
												esc_html_e(
													'Avoid indexing your profile in frontend listing from search engines robots.',
													'user-registration'
												)
												?>
														"></span>
											</label>
										</div>
										<div class="ur-privacy-input ur-toggle-section">
											<span class="user-registration-toggle-form">
												<input type="checkbox" id="ur-profile-indexing" name="ur_profile_index" value="yes"
											<?php echo $profile_noindex ? 'checked' : ''; ?> />
												<span class="slider round"></span>
											</span>
										</div>
									</div>
									<?php
								endif;
								do_action( 'user_registration_after_account_privacy', $enable_download_personal_data, $enable_erase_personal_data );

								if ( $is_update_privacy_required ) :
									?>
									<div class="user-registration-form-row form-row ur-privacy-button">
										<input type="submit" class="user-registration-Button button"
											value="<?php esc_attr_e( 'Update Privacy', 'user-registration' ); ?>" name="ur_privacy_tab" />
									</div>
									<?php
								endif;
								wp_nonce_field( 'ur_privacy_tab_nonce' );
								?>
							</div>
						</div>
					</form>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Privacy tab form handler.
	 */
	public function ur_process_privacy_tab() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'ur_privacy_tab_nonce' ) ) {
			return;
		}
		if ( isset( $_POST['ur_privacy_tab'] ) ) {
			$profile_index = isset( $_POST['ur_profile_index'] ) ? sanitize_text_field( wp_unslash( $_POST['ur_profile_index'] ) ) : '';
			$show_profile  = isset( $_POST['ur_show_profile'] ) ? sanitize_text_field( wp_unslash( $_POST['ur_show_profile'] ) ) : '';

			$user_id = get_current_user_id();
			update_user_meta( $user_id, 'ur_profile_noindex', $profile_index );
			update_user_meta( $user_id, 'ur_show_profile', $show_profile );

			return;
		}
	}

	/**
	 * Enqueue the inactive script if the inactive option is enabled.
	 */
	public function load_inactive_scripts() {
		if ( ( '' !== get_option( 'user_registration_auto_logout_inactivity_time', '' ) ) && is_user_logged_in() ) {
			$role_based_inactivity = get_option( 'user_registration_role_based_inactivity', array( 'subscriber' ) );
			$user                  = wp_get_current_user();
			$roles                 = $user->roles;

			// checking the role based inactivity.
			if ( in_array( $roles[0], $role_based_inactivity, true ) ) {
				$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_register_script(
					'ur-inactive',
					UR()->plugin_url() . '/assets/js/pro/frontend/user-registration-inactive' . $min . '.js',
					array(
						'jquery',
						'sweetalert2',
					),
					UR_VERSION,
					true
				);
				wp_enqueue_script( 'ur-inactive' );
				wp_localize_script(
					'ur-inactive',
					'ur_inactive_params',
					array(
						'ajax_url'                       => admin_url( 'admin-ajax.php' ),
						'inactive_logout_nonce'          => wp_create_nonce( 'inactive_logout_nonce' ),
						'inactive_time_period'           => get_option( 'user_registration_auto_logout_inactivity_time', '' ),
						'time_countdown_inactive_period' => get_option( 'user_registration_timeout_countdown_inactive_period', 10 ),
						'inactive_title'                 => __( 'Inactivity Detected', 'user-registration' ),
						'stay_signin'                    => sprintf( '%s <b></b>', esc_html__( 'Stay Signed In', 'user-registration' ) ),
						'inactive_message'               => sprintf( '%s <br> %s <b></b>', esc_html__( 'You are being timed-out out due to inactivity. Please choose to stay signed or to logoff.', 'user-registration' ), esc_html__( 'Otherwise, you will be logged off authomatically', 'user-registration' ) ),
						'inactive_logout_message'        => __( 'You have been logged out because of inactivity', 'user-registration' ),
						'reload_text'                    => __( 'Close without Reloading', 'user-registration' ),
						'inactive_ok'                    => __( 'Ok', 'user-registration' ),
					)
				);
			}
		}
	}

	/**
	 * Render Captcha field on frontend.
	 *
	 * @param string $field Field.
	 * @param string $key Field name.
	 * @param array  $args Arguments.
	 * @param mixed  $value Default value.
	 *
	 * @return void
	 */
	function user_registration_render_captcha_field( $field, $key, $args, $value ) {

		/* Conditional Logic codes */
		$rules                      = array();
		$rules['conditional_rules'] = isset( $args['conditional_rules'] ) ? $args['conditional_rules'] : '';
		$rules['logic_gate']        = isset( $args['logic_gate'] ) ? $args['logic_gate'] : '';
		$rules['rules']             = isset( $args['rules'] ) ? $args['rules'] : array();
		$rules['required']          = isset( $args['required'] ) ? $args['required'] : true;

		foreach ( $rules['rules'] as $rules_key => $rule ) {
			if ( empty( $rule['field'] ) ) {
				unset( $rules['rules'][ $rules_key ] );
			}
		}
		$rules['rules'] = array_values( $rules['rules'] );

		$rules = ( ! empty( $rules['rules'] ) && isset( $args['enable_conditional_logic'] ) ) ? wp_json_encode( $rules ) : '';
		/*Conditonal Logic codes end*/
		$custom_attributes = array();
		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( ! isset( $args['captcha_format'] ) ) {
			return;
		}

		$args['class'][] = 'validate-required';
		$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'user-registration' ) . '">*</abbr>';
		$attr_required   = 'required = "required"';

		$field_content = '';
		$field_label   = $field_content;
		$tooltip_html  = '';

		if ( isset( $args['tooltip'] ) && ur_string_to_bool( $args['tooltip'] ) ) {
			$tooltip_html = ur_help_tip( $args['tooltip_message'], false, 'ur-portal-tooltip' );
		} elseif ( isset( $args['tip'] ) ) {
			$tooltip_html = ur_help_tip( $args['tip'], false, 'user-registration-help-tip tooltipstered' );
		}

		$field_content .= '<div class="form-row " id="' . esc_attr( $args['id'] ) . '" data-priority="">';
		if ( $args['label'] ) {
			$field_label .= '<label class="ur-label" for="' . esc_attr( $args['label'] ) . '">' . wp_kses(
				$args['label'],
				array(
					'a'    => array(
						'href'  => array(),
						'title' => array(),
					),
					'span' => array(),
				)
			) . $required . $tooltip_html . '</label>';
		}
		$field_content .= $field_label;
		$field_content .= '<span class="input-wrapper ur-captcha-' . $args['captcha_format'] . '"> ';

		$custom_class     = isset( $args['custom_class'] ) ? $args['custom_class'] : '';
		$qid              = ur_captcha_random_question( $args['options'] );
		$image_captcha_id = isset( $args['image_captcha_options'] ) ? ur_captcha_random_image_group( $args['image_captcha_options'] ) : 'false';
		$question         = $args['options'][ $qid ]['question'];
		$answer           = $args['options'][ $qid ]['answer'];

		if ( 'false' === $image_captcha_id ) {
			$icon_group    = array(
				'icon-1'       => 'dashicons dashicons-menu',
				'icon-2'       => 'dashicons dashicons-admin-network',
				'icon-3'       => 'dashicons dashicons-admin-multisite',
				'correct_icon' => 'icon-1',
				'icon_tag'     => 'Menu',
			);
			$icon_group_id = 0;
		} else {
			$icon_group_id = $image_captcha_id;
			$icon_group    = isset( $args['image_captcha_options'][ $image_captcha_id ] ) ? (array) $args['image_captcha_options'][ $image_captcha_id ] : array();
		}

		switch ( $args['captcha_format'] ) {
			case 'math':
				$field_content .= '<span class="ur-captcha-equation" style = "margin-right:10px; display:inline-block">
							<span class="n1" value=""></span>
							<span class="cal"></span>
							<span class="n2"></span>
							<span class="e">=</span>
							</span>';
				$field_content .= '<input data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '" type="text" class="input-captcha ' . esc_attr( implode( ' ', $args['input_class'] ) ) . esc_attr( $custom_class ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';
				$field_content .= '<input type="hidden" name="' . esc_attr( $key ) . '[cal]" class="cal">';
				$field_content .= '<input type="hidden" name="' . esc_attr( $key ) . '[n2]" class="n2">';
				$field_content .= '<input type="hidden" name="' . esc_attr( $key ) . '[n1]" class="n1">';
				break;

			case 'qa':
				$field_content .= '<h7 style = "margin-right:10px">' . sprintf( __( '%s', 'user-registration' ), $question ) . '</h7><input type="text" data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="input-captcha ' . esc_attr( implode( ' ', $args['input_class'] ) ) . esc_attr( $custom_class ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" data-a = "' . $answer . '" value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . '/>';
				$field_content .= '<input type="hidden" name="' . esc_attr( $key ) . '[question]" class="qa" value="' . esc_attr( $qid ) . '">';
				break;
			case 'image':
				$field_content .= '<h7 style = "margin-right:10px">' . sprintf( __( 'Please select the correct <b>%s</b>', 'user-registration' ), $icon_group['icon_tag'] ) . '</h7>';
				$field_content .= '<div class="ur-icon-group" data-group="' . esc_attr( $icon_group_id ) . '">';
				$field_content .= '<label for="' . esc_attr( $args['id'] ) . '[icon-1]" class="ur-icon-wrap">';
				$field_content .= '<input type="radio" data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="input-captcha-icon-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . esc_attr( $custom_class ) . '" id="' . esc_attr( $args['id'] ) . '[icon-1]" value="' . esc_attr( $icon_group['icon-1'] ) . '" ' . implode( ' ', $custom_attributes ) . '/>';
				$field_content .= '<span class="' . esc_attr( $icon_group['icon-1'] ) . '"></span>';
				$field_content .= '</label>';
				$field_content .= '<label for="' . esc_attr( $args['id'] ) . '[icon-2]" class="ur-icon-wrap">';
				$field_content .= '<input type="radio" data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="input-captcha-icon-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . esc_attr( $custom_class ) . '" id="' . esc_attr( $args['id'] ) . '[icon-2]" value="' . esc_attr( $icon_group['icon-2'] ) . '" ' . implode( ' ', $custom_attributes ) . '/>';
				$field_content .= '<span class="' . esc_attr( $icon_group['icon-2'] ) . '"></span>';
				$field_content .= '</label>';
				$field_content .= '<label for="' . esc_attr( $args['id'] ) . '[icon-3]" class="ur-icon-wrap">';
				$field_content .= '<input type="radio" data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="input-captcha-icon-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . esc_attr( $custom_class ) . '" id="' . esc_attr( $args['id'] ) . '[icon-3]" value="' . esc_attr( $icon_group['icon-3'] ) . '" ' . implode( ' ', $custom_attributes ) . '/>';
				$field_content .= '<span class="' . esc_attr( $icon_group['icon-3'] ) . '"></span>';
				$field_content .= '</label>';
				$field_content .= '</div>';
				$field_content .= '<input type="hidden" name="' . esc_attr( $key ) . '[correct_icon]" class="captcha_correct_icon" value="' . esc_attr( $icon_group['correct_icon'] ) . '">';
				break;
		}
		if ( $args['description'] ) {
			$field_content .= '<span class="description">' . $args['description'] . '</span>';
		}
		$field_content .= '</span></div>';
		echo $field_content;
	}

	/**
	 * Updates booked slots in user meta data based on form data and settings.
	 *
	 * This function takes the form data, form ID, and user ID to extract and process
	 * slot booking information. It examines the slot booking fields' settings for
	 * date and time picker fields, considering dependencies between them, and then
	 * stores the booked slot information in the user's meta data.
	 *
	 * @param array $valid_form_data An array containing the validated form data.
	 * @param int   $form_id The ID of the form associated with the booking.
	 * @param int   $user_id The ID of the user making the booking.
	 */
	public function ur_pro_update_booked_slot_in_user_meta( $valid_form_data, $form_id, $user_id ) {
		$slot_booking_fields_settings = ur_pro_get_slot_booking_fields_settings( $form_id );
		$user_booked_slot             = array();
		$date_value                   = '';
		$time_value                   = '';
		$time_interval                = '';
		$mode                         = '';
		$mode_type                    = '';
		foreach ( $slot_booking_fields_settings as $field_name => $field_setting ) {
			if ( $field_setting['field_key'] === 'date' ) {
				if ( ur_string_to_bool( $field_setting['enable_date_slot_booking'] ) ) {
					$field_key = $field_name;
					$mode      = 'date';
					$mode_type = isset( $field_setting['enable_date_range'] ) ? $field_setting['enable_date_range'] : '';
					if ( ur_string_to_bool( $mode_type ) ) {
						$mode_type = 'range';
					}
					$date_value = $valid_form_data[ $field_name ]->value;

					if ( is_user_logged_in() ) {
						// For login user while updating profile.
						$users_slot_booked_meta_data = get_user_meta( $user_id, 'user_booked_slot' );

						$parse_arr_value = ur_pro_parse_date_time( $date_value, $time_value, $time_interval, $mode, '', $mode_type, '' );

						$users_slot_booked_meta_data[0][ $field_key ] = $parse_arr_value;
						$user_booked_slot                             = $users_slot_booked_meta_data[0];
					} else {
						// For none login user while registration.
						if ( ! in_array( $field_key, $user_booked_slot, true ) ) {
							$user_booked_slot[ $field_key ] = ur_pro_parse_date_time( $date_value, $time_value, $time_interval, $mode, '', $mode_type, '' );
						}
					}
				}
			} elseif ( $field_setting['field_key'] === 'timepicker' ) {
				if ( ur_string_to_bool( $field_setting['enable_time_slot_booking'] ) ) {
					$field_key     = $field_name;
					$mode          = 'time';
					$time_value    = $valid_form_data[ $field_name ]->value;
					$time_interval = isset( $field_setting['time_interval'] ) ? $field_setting['time_interval'] : '';

					if ( '' !== $field_setting['target_date_field'] ) {
						if ( array_key_exists( $field_setting['target_date_field'], $slot_booking_fields_settings ) ) {
							$target_field_name = $field_setting['target_date_field'];

							$mode       = 'date-time';
							$date_value = $valid_form_data[ $field_setting['target_date_field'] ]->value;
							$mode_type  = isset( $slot_booking_fields_settings[ $target_field_name ]['enable_date_range'] ) ? $slot_booking_fields_settings[ $target_field_name ]['enable_date_range'] : '';
							if ( ur_string_to_bool( $mode_type ) ) {
								$mode_type = 'range';
							}
						}
					}

					if ( is_user_logged_in() ) {
						// For login user while updating profile.
						$users_slot_booked_meta_data = get_user_meta( $user_id, 'user_booked_slot' );

						$parse_arr_value = ur_pro_parse_date_time( $date_value, $time_value, $time_interval, $mode, '', $mode_type, '' );

						$users_slot_booked_meta_data[0][ $field_key ] = $parse_arr_value;
						$user_booked_slot                             = $users_slot_booked_meta_data[0];
					} else {
						// For none login user while registration.
						if ( ! in_array( $field_key, $user_booked_slot, true ) ) {
							$user_booked_slot[ $field_key ] = ur_pro_parse_date_time( $date_value, $time_value, $time_interval, $mode, '', $mode_type, '' );
						}
					}
				}
			}
		}
		if ( ! empty( $user_booked_slot ) ) {
			update_user_meta( $user_id, 'user_booked_slot', $user_booked_slot );
		}
	}
	/**
	 * Render Signature field on frontend.
	 *
	 * @param  string $field Field.
	 * @param  string $key Field name.
	 * @param  array  $args Arguments.
	 * @param  mixed  $value Default value.
	 * @return void
	 */
	public function user_registration_render_signature_field( $field, $key, $args, $value ) {

		/* Conditional Logic codes */
		$rules                      = array();
		$rules['conditional_rules'] = isset( $args['conditional_rules'] ) ? $args['conditional_rules'] : '';
		$rules['logic_gate']        = isset( $args['logic_gate'] ) ? $args['logic_gate'] : '';
		$rules['rules']             = isset( $args['rules'] ) ? $args['rules'] : array();
		$rules['required']          = isset( $args['required'] ) ? $args['required'] : '';

		foreach ( $rules['rules'] as $rules_key => $rule ) {
			if ( empty( $rule['field'] ) ) {
				unset( $rules['rules'][ $rules_key ] );
			}
		}
		$rules['rules'] = array_values( $rules['rules'] );

		$rules = ( ! empty( $rules['rules'] ) && isset( $args['enable_conditional_logic'] ) ) ? wp_json_encode( $rules ) : '';
		/*Conditonal Logic codes end*/

		if ( true === $args['required'] ) {
			$args['class'][]  = 'validate-required';
			$required         = ' <abbr class="required" title="' . esc_attr__( 'required', 'user-registration-advanced-fields' ) . '">*</abbr>';
			$args['required'] = 'required="required"';
		} else {
			$args['required'] = $required = '';
		}
		$description   = '<span class="description">' . isset( $args['description'] ) ? $args['description'] : '' . '</span>';
		$field_content = $field_label = '';

		// Frontend tooltip.
		$tooltip_html = '';

		if ( isset( $args['tooltip'] ) && ur_string_to_bool( $args['tooltip'] ) ) {
			$tooltip_html = ur_help_tip( $args['tooltip_message'], false, 'ur-portal-tooltip' );
		}

		$field_wrapper = '<div class="form-row user-registration-field-signature" id="' . esc_attr( $args['id'] ) . '" data-priority="">';

		$custom_attributes = array();
		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( $args['label'] ) {

			$field_label .= '<label for="' . esc_attr( $args['label'] ) . '" class="ur-label">' . wp_kses(
				$args['label'],
				array(
					'a'    => array(
						'href'  => array(),
						'title' => array(),
					),
					'span' => array(),
				)
			) . $required . $tooltip_html . '</label>';
		}

		$field_content .= $field_wrapper . $field_label;
		$field_content .= $description;
		$form_id        = '';

		if ( isset( $args['form_id'] ) ) {
			$form_id = $args['form_id'];
		} else {
			$user_id = get_current_user_id();
			$form_id = ur_get_form_id_by_userid( $user_id );
		}

		if ( empty( $value ) || ! is_numeric( $value ) || ( is_numeric( $value ) && ! wp_get_attachment_url( $value ) ) ) {
			$args['signature_file_format'] = isset( $args['signature_file_format'] ) ? $args['signature_file_format'] : 'png';
			$field_content                .= '<div id="user_registration_signature_canvas_' . esc_html( $args['id'] ) . '" class="user_registration_signature_canvas-wrap" data-image-format="' . esc_html( $args['signature_file_format'] ) . '" data-form-id="' . esc_html( $form_id ) . '" data-field-id="' . esc_html( $args['id'] ) . '" >';
			$field_content                .= '<canvas id="user-registration-canvas-' . esc_html( $args['id'] ) . '" class="user-registration-signature-canvas" style="width:100%;height:200px;max-width:100%;max-height:100%;" ></canvas>';
			$field_content                .= ' <input type="hidden" data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '" value="' . $args['default'] . '" class=" ' . esc_attr( implode( ' ', $args['input_class'] ) ) . ' user-registration-signature-input" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . esc_attr( $args['required'] ) . ' /> ';
			$field_content                .= ' <a href="JavaScript:void(0);" title="' . esc_attr__( 'Clear Signature', 'user-registration' ) . '" style="text-decoration: none;" id="user-registration-signature-reset-' . esc_html( $args['id'] ) . '" class="user-registration-signature-reset"><span class="dashicons dashicons-no-alt"></span> </a> ';
			$field_content                .= '</div>';
		} else {
			$field_content .= '<div id="user_registration_signature_container_' . esc_html( $args['id'] ) . '" class="user-registration-signature-container"><img src="' . wp_get_attachment_url( $value ) . '" width="100%" /></div>';
		}
		$field_content .= '</div>';

		echo $field_content;
		return '';
	}


	/**
	 * Update the slot booking while after updating the user profile.
	 *
	 * @param [int] $user_id User ID.
	 * @param [int] $form_id Form ID.
	 *
	 * @since 4.1.0
	 */
	public function ur_pro_edit_profile_update_booked_slot( $user_id, $form_id ) {
		$profile         = user_registration_form_data( $user_id, $form_id );
		$valid_form_data = array();
		foreach ( $profile as $key => $value ) {
			if ( isset( $value['field_key'] ) && ( 'timepicker' === $value['field_key'] || 'date' === $value['field_key'] ) ) {

				$valid_data = array(
					'value' => $value['default'],
				);

				$valid_form_data[ trim( str_replace( 'user_registration_', '', $key ) ) ] = (object) $valid_data;
			}
		}

		if ( empty( $valid_form_data ) ) {
			return;
		}
		$this->ur_pro_update_booked_slot_in_user_meta( $valid_form_data, $form_id, $user_id );
	}

	/**
	 * Process signature field data before updating user meta.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $form_data Form Data.
	 * @param int   $user_id User Id.
	 * @param int   $form_id Form Id.
	 *
	 * @return $form_data
	 */
	public function user_registration_process_signature_field_data_before_meta_update( $form_data, $user_id, $form_id ) {
		foreach ( $form_data as $key => $value ) {
			if ( isset( $value->extra_params['field_key'] ) && 'signature' === $value->extra_params['field_key'] ) {
				// Create Image from blob and save it.
				$data_uri = isset( $value->value ) ? $value->value : '';

				$attachment_id            = apply_filters( 'user_registration_process_signature_field_data', $data_uri );
				$form_data[ $key ]->value = $attachment_id;
			}
		}
		return $form_data;
	}

	/**
	 * Process signature field data before updating user profile data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $value Signature field data.
	 *
	 * @return $form_data
	 */
	public function user_registration_process_signature_field_data( $value ) {

		// Define data.
		$uploads                    = wp_upload_dir();
		$ur_uploads_root            = trailingslashit( $uploads['basedir'] ) . 'user_registration_uploads';
		$signature_upload_directory = trailingslashit( $ur_uploads_root ) . 'signature';

		// Check for form upload directory destination.
		if ( ! file_exists( $signature_upload_directory ) ) {
			wp_mkdir_p( $signature_upload_directory );
		}

		// Check if the index.html exists in the root uploads director, if not create it.
		if ( ! file_exists( trailingslashit( $signature_upload_directory ) . 'index.html' ) ) {
			file_put_contents( trailingslashit( $signature_upload_directory ) . 'index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		}

		// Create Image from blob and save it.
		$data_uri = isset( $value ) ? $value : '';

		$file_format       = 'png';
		$check_file_format = "data:image/{$file_format};base64,";
		if ( false !== strpos( $data_uri, $check_file_format ) ) {
			$encoded_image = str_replace( $check_file_format, '', $data_uri );
			$decoded_image = base64_decode( $encoded_image ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$file_name     = 'signature_' . time() . wp_rand( 0, 10 ) . wp_rand( 0, 10 );
			$file          = trailingslashit( $signature_upload_directory ) . $file_name . ".{$file_format}";
			file_put_contents( $file, $decoded_image ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			$attachment_id = wp_insert_attachment(
				array(
					'guid'           => $file,
					'post_mime_type' => 'image/png',
					'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_file_name( $file_name ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				$file
			);

			$value = $attachment_id;
		}

		return $value;
	}
}
