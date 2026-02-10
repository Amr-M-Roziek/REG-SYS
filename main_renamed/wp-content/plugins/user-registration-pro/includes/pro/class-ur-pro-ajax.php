<?php
/**
 * User_Registration_Pro_Ajax
 *
 * AJAX Event Handler
 *
 * @class    User_Registration_Pro_Ajax
 * @version  1.0.0
 * @package  UserRegistrationPro/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEverest\URMembership\Admin\Repositories\SubscriptionRepository;

/**
 * User_Registration_Pro_Ajax Class
 */
class User_Registration_Pro_Ajax {

	/**
	 * Hooks in ajax handlers
	 */
	public static function init() {

		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax)
	 */
	public static function add_ajax_events() {

		$ajax_events = array(
			'dashboard_analytics'             => true,
			'delete_account'                  => false,
			'send_email_logout'               => true,
			'extension_install'               => true,
			'get_db_columns_by_table'         => true,
			'get_form_fields_list_by_form_id' => true,
			'request_user_data'               => false,
			'cancel_subscription'             => false,
			'get_license_expiry_count'        => false,
			'users_table_change_column_state' => false,
			'inactive_logout'                 => true,
			'user_slot_booking'               => true,
			'get_coupon_detail'               => true,
			'disable_user'                    => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {

			add_action( 'wp_ajax_user_registration_pro_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {

				add_action(
					'wp_ajax_nopriv_user_registration_pro_' . $ajax_event,
					array(
						__CLASS__,
						$ajax_event,
					)
				);
			}
		}
	}

	/**
	 * Get License expiry count
	 */
	public static function get_license_expiry_count() {
		check_ajax_referer( 'ur_pro_get_license_expiry_count_nonce', 'security' );
		$last_notice_count = get_option( 'user_registration_license_expiry_notice_last_notice_count', 0 );
		update_option( 'user_registration_license_expiry_notice_last_dismissed_time', date_i18n( 'Y-m-d H:i:s' ) );
		update_option( 'user_registration_license_expiry_notice_last_notice_count', $last_notice_count + 1 );
		wp_die();
	}

	/**
	 * Get Column list by table name
	 */
	public static function get_db_columns_by_table() {
		check_ajax_referer( 'ur_pro_get_db_columns_by_table_nonce', 'security' );

		$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
		if ( ! empty( $table ) ) {
			$columns = user_registration_get_columns_by_table( $table );
			wp_send_json_success(
				array(
					'columns' => json_encode( $columns, true ),
				)
			);
		}
	}

	/**
	 * Get Form Fields list by form id.
	 */
	public static function get_form_fields_list_by_form_id() {
		check_ajax_referer( 'ur_pro_get_form_fields_by_form_id_nonce', 'security' );
		$form_id    = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
		$field_list = array();
		if ( ! empty( $form_id ) ) {
			$fields = ur_pro_get_form_fields( $form_id );
			foreach ( $fields as $post_key => $post_data ) {

				$pos = strpos( $post_key, 'user_registration_' );

				if ( false !== $pos ) {
					$new_string = substr_replace( $post_key, '', $pos, strlen( 'user_registration_' ) );

					if ( ! empty( $new_string ) ) {
						$field_list[ $new_string ] = $post_data['label'];
					}
				}
			}
		}
		wp_send_json_success(
			array(
				'form_field_list' => json_encode( $field_list, true ),
			)
		);
	}

	/**
	 * Ajax call when user clicks on delete account menu/tab.
	 */
	public static function delete_account() {
		check_ajax_referer( 'user_data_nonce', 'security' );
		$delete_account_option = get_option( 'user_registration_pro_general_setting_delete_account', 'disable' );

		if ( 'disable' === $delete_account_option ) {
			return;
		}
		$user     = new stdClass();
		$user->ID = (int) get_current_user_id();

		$form_id   = ur_get_form_id_by_userid( $user->ID );
		$form_data = user_registration_form_data( $user->ID, $form_id );

		$user_extra_fields = ur_get_user_extra_fields( $user->ID );
		$user_data         = array_merge( (array) get_userdata( $user->ID )->data, $user_extra_fields );

		// Get form data as per need by the {{all_fields}} smart tag.
		$valid_form_data = array();
		foreach ( $form_data as $key => $value ) {
			$new_key = trim( str_replace( 'user_registration_', '', $key ) );

			if ( isset( $user_data[ $new_key ] ) ) {
				$valid_form_data[ $new_key ] = (object) array(
					'field_type'   => $value['type'],
					'label'        => $value['label'],
					'field_name'   => $value['field_key'],
					'value'        => $user_data[ $new_key ],
					'extra_params' => array(
						'label'     => $value['label'],
						'field_key' => $value['field_key'],
					),
				);
			}
		}

		$current_user = get_user_by( 'id', get_current_user_id() );

		if ( $user->ID <= 0 ) {
			return;
		}

		$delete_account_flag = false;

		if ( isset( $_POST['password'] ) && ! empty( $_POST['password'] ) && 'prompt_password' === $delete_account_option ) {

			// Authenticate Current User.
			if ( ! wp_check_password( $_POST['password'], $current_user->user_pass, $current_user->ID ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Your current password is incorrect.', 'user-registration' ),
					)
				);
			}
			$delete_account_flag = true;

		} elseif ( 'direct_delete' === $delete_account_option ) {
			$delete_account_flag = true;
		}

		if ( $delete_account_flag ) {

			do_action( 'user_registration_pro_before_delete_account', $user );

			self::send_delete_account_email( $user->ID, $form_id, $valid_form_data );
			self::send_delete_account_admin_email( $user->ID, $form_id, $valid_form_data );

			if ( is_multisite() ) {

				if ( ! function_exists( 'wpmu_delete_user' ) ) {
					require_once ABSPATH . 'wp-admin/includes/ms.php';
				}

				wpmu_delete_user( $user->ID );

			} else {

				if ( ! function_exists( 'wp_delete_user' ) ) {
					require_once ABSPATH . 'wp-admin/includes/user.php';
				}

				wp_delete_user( $user->ID );

			}
			// TODO : Remove uploaded Files.
			do_action( 'user_registration_pro_after_delete_account', $user );
			wp_logout();
			wp_send_json_success(
				array(
					'message' => 'Deleted',
				)
			);
		}
	}

	/**
	 * Ajax call when user clicks force logout link .
	 *
	 * @since 3.0.0
	 */
	public static function send_email_logout() {

		include __DIR__ . '/admin/settings/emails/class-ur-settings-prevent-concurrent-login-email.php';

		$email   = sanitize_email( isset( $_POST['user_email'] ) ? $_POST['user_email'] : '' );
		$user_id = intval( $_POST['user_id'] );


		$header  = 'From: ' . UR_Emailer::ur_sender_name() . ' <' . UR_Emailer::ur_sender_email() . ">\r\n";
		$header .= 'Reply-To: ' . UR_Emailer::ur_sender_email() . "\r\n";
		$header .= "Content-Type: text/html\r\n; charset=UTF-8";

		$subject                   = get_option( 'user_registration_prevent_concurrent_login_email_subject', 'Force logout' );
		$values                    = array(
			'email'   => $email,
			'user_id' => $user_id,
		);
		$settings                  = new UR_Settings_Prevent_Concurrent_Login_Email();
		$message                   = $settings->user_registration_get_prevent_concurrent_login_email();
		$message                   = get_option( 'user_registration_prevent_concurrent_login_email_content', $message );
		$form_id                   = ur_get_form_id_by_userid( $user_id );
		list( $message, $subject ) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

		$message = UR_Emailer::parse_smart_tags( $message, $values );
		$subject = UR_Emailer::parse_smart_tags( $subject, $values );

		// Get selected email template id for specific form.
		$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

		if ( ur_option_checked( 'user_registration_enable_prevent_concurrent_login_email', true ) ) {
				UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
		}
	}


	/**
	 * Dashboard Analytics.
	 */
	public static function dashboard_analytics() {
		$form_id            = isset( $_POST['form_id'] ) ? $_POST['form_id'] : 'all';
		$selected_date      = isset( $_POST['selected_date'] ) ? $_POST['selected_date'] : 'Week';
		$from               = isset( $_POST['from'] ) ? ur_clean( $_POST['from'] ) : '';
		$to                 = isset( $_POST['to'] ) ? ur_clean( $_POST['to'] ) : '';
		$summary_sort_by    = isset( $_POST['summary_sort_by'] ) ? ur_clean( $_POST['summary_sort_by'] ) : '';
		$summary_sort_order = isset( $_POST['summary_sort_order'] ) ? ur_clean( $_POST['summary_sort_order'] ) : '';
		$chart_type         = isset( $_POST['chart_type'] ) ? ur_clean( $_POST['chart_type'] ) : 'registration_count';

		$user_registration_pro_dashboard = new User_Registration_Pro_Dashboard_Analytics();
		$message                         = '';

		switch ( $chart_type ) {

			case 'registration_count':
				$message = $user_registration_pro_dashboard->user_registration_registration_count_report( $form_id, $selected_date );
				break;
			case 'specific_form_users':
				$message = $user_registration_pro_dashboard->user_registration_specific_form_users_report();
				break;
			case 'form_analytics_report':
				$message = $user_registration_pro_dashboard->user_registration_form_analytics_overview_report( $form_id, $selected_date, $from, $to );
				break;
			case 'top_referer_report':
				$message = $user_registration_pro_dashboard->user_registration_top_referer_report( $form_id, $from, $to );
				break;
			case 'form_summary_report':
				$message = $user_registration_pro_dashboard->user_registration_form_summary_report( $from, $to, $summary_sort_by, $summary_sort_order );
				break;
		}

		wp_send_json_success(
			$message
		);
	}

	/**
	 * Extenstion Install.
	 */
	public static function extension_install() {
		check_ajax_referer( 'ur_pro_install_extension_nonce', 'security' );

		$name = isset( $_POST['name'] ) ? $_POST['name'] : '';
		$slug = isset( $_POST['slug'] ) ? $_POST['slug'] : '';

		$status = ur_install_extensions( $name, $slug );

		wp_send_json( $status );
	}

	/**
	 * Send email to user when user deleted thier own account.
	 *
	 * @param int   $user_id ID of the user.
	 * @param int   $form_id Form ID.
	 * @param array $form_data Form Data.
	 */
	public static function send_delete_account_email( $user_id, $form_id, $form_data ) {

		include __DIR__ . '/admin/settings/emails/class-ur-settings-delete-account-email.php';

		$user     = get_user_by( 'ID', $user_id );
		$username = $user->data->user_login;
		$email    = $user->data->user_email;

		list( $name_value, $data_html ) = ur_parse_name_values_for_smart_tags( $user_id, $form_id, $form_data );
		$values                         = array(
			'username'   => $username,
			'email'      => $email,
			'all_fields' => $data_html,
		);

		$header  = 'From: ' . UR_Emailer::ur_sender_name() . ' <' . UR_Emailer::ur_sender_email() . ">\r\n";
		$header .= 'Reply-To: ' . UR_Emailer::ur_sender_email() . "\r\n";
		$header .= "Content-Type: text/html\r\n; charset=UTF-8";

		$subject = get_option( 'user_registration_pro_delete_account_email_subject', 'Your account has been deleted' );

		$settings                  = new UR_Settings_Delete_Account_Email();
		$message                   = $settings->user_registration_get_delete_account_email();
		$message                   = get_option( 'user_registration_pro_delete_account_email_content', $message );
		$form_id                   = ur_get_form_id_by_userid( $user_id );
		list( $message, $subject ) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

		$message = UR_Emailer::parse_smart_tags( $message, $values, $name_value );
		$subject = UR_Emailer::parse_smart_tags( $subject, $values, $name_value );

		// Get selected email template id for specific form.
		$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

		if ( ur_option_checked( 'user_registration_enable_delete_account_email', true ) ) {
			UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
		}
	}

	/**
	 * Send email to admin when user deleted thier own account.
	 *
	 * @param int   $user_id ID of the user.
	 * @param int   $form_id ID of the user.
	 * @param array $form_data Form Data.
	 */
	public static function send_delete_account_admin_email( $user_id, $form_id, $form_data ) {

		include __DIR__ . '/admin/settings/emails/class-ur-settings-delete-account-admin-email.php';

		$user     = get_user_by( 'ID', $user_id );
		$username = $user->data->user_login;
		$email    = $user->data->user_email;

		list( $name_value, $data_html ) = ur_parse_name_values_for_smart_tags( $user_id, $form_id, $form_data );
		$values                         = array(
			'username'   => $username,
			'email'      => $email,
			'all_fields' => $data_html,
		);

		$header  = 'From: ' . UR_Emailer::ur_sender_name() . ' <' . UR_Emailer::ur_sender_email() . ">\r\n";
		$header .= 'Reply-To: ' . UR_Emailer::ur_sender_email() . "\r\n";
		$header .= "Content-Type: text/html\r\n; charset=UTF-8";

		$admin_email = get_option( 'user_registration_pro_delete_account_email_receipents', get_option( 'admin_email' ) );
		$admin_email = explode( ',', $admin_email );
		$admin_email = array_map( 'trim', $admin_email );

		$subject = get_option( 'user_registration_pro_delete_account_admin_email_subject', '{{blog_info}} Account deleted.' );

		$settings                  = new UR_Settings_Delete_Account_Admin_Email();
		$message                   = $settings->user_registration_get_delete_account_admin_email();
		$message                   = get_option( 'user_registration_pro_delete_account_admin_email_content', $message );
		$form_id                   = ur_get_form_id_by_userid( $user_id );
		list( $message, $subject ) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

		$message = UR_Emailer::parse_smart_tags( $message, $values, $name_value );
		$subject = UR_Emailer::parse_smart_tags( $subject, $values, $name_value );

		// Get selected email template id for specific form.
		$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

		if ( ur_option_checked( 'user_registration_enable_delete_account_admin_email', true ) ) {
			foreach ( $admin_email as $email ) {
				UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
			}
		}
	}
	/**
	 * Privacy request.
	 */
	public static function request_user_data() {
		$security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
		if ( '' === $security || ! wp_verify_nonce( $security, 'user_data_nonce' ) ) {
			wp_send_json_error( 'Nonce verification failed' );
			return;
		}
		if ( ! isset( $_POST['request_action'] ) ) {
			wp_send_json_error( __( 'Wrong request.', 'user-registration' ) );
		}

		$user_id        = get_current_user_id();
		$password       = ! empty( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';
		$user           = get_userdata( $user_id );
		$hash           = $user->data->user_pass;
		$request_action = sanitize_key( $_POST['request_action'] );

		if ( ! wp_check_password( $password, $hash ) ) {
			$answer = sprintf( '<div class="ur-field-error ur-erase-data"><span class="ur-privacy-password-error"><i class="ur-faicon-caret-up"></i>%s</span></div>', esc_html__( 'The password you entered is incorrect.', 'user-registration' ) );
			wp_send_json_success(
				array(
					'success' => 0,
					'answer'  => $answer,
				)
			);
		}

		if ( 'ur-export-data' === $request_action ) {
			$request_id   = wp_create_user_request( $user->data->user_email, 'export_personal_data', array(), 'confirmed' );
			$request_name = __( 'Export Personal Data', 'user-registration' );
		} elseif ( 'ur-erase-data' === $request_action ) {
			$request_id   = wp_create_user_request( $user->data->user_email, 'remove_personal_data', array(), 'confirmed' );
			$request_name = __( 'Export Erase Data', 'user-registration' );
		}

		if ( ! isset( $request_id ) || empty( $request_id ) ) {
			wp_send_json_error( __( 'Wrong request.', 'user-registration' ) );
		}

		if ( is_wp_error( $request_id ) ) {
			$answer = esc_html( $request_id->get_error_message() );
		} else {
			if ( 'ur-export-data' === $request_action ) {
				$visit_url = admin_url() . 'export-personal-data.php';
				$answer    = sprintf( '<h3>%s</h3> %s', __( 'Download Your Data', 'user-registration' ), esc_html__( 'The administrator has not yet approved downloading the data. Pleas wait for approval.', 'user-registration' ) );
			} elseif ( 'ur-erase-data' === $request_action ) {
				$visit_url = admin_url() . 'erase-personal-data.php';
				$answer    = sprintf( '<h3>%s</h3> %s', __( 'Erase Of Your Data', 'user-registration' ), esc_html__( 'The administrator has not yet approved deleting your data. Pleas wait for approval.', 'user-registration' ) );
			}
			$subject    = sprintf( '%s %s', __( 'Approval Action:', 'user-registration' ), $request_name );
			$request    = wp_get_user_request( $request_id );
			$user_email = $request->email;
			$headers    = array(
				'From: ' . get_bloginfo( 'name' ) . ' <' . get_bloginfo( 'admin_email' ) . '>',
				'Reply-To: ' . $user_email,
			);
			$message    = sprintf(
				'%s  %s %s %s %s %s',
				__( 'Hi,', 'user-registration' ),
				__(
					'A user data privacy request has been confirmed:',
					'user-registration'
				),
				__( 'user:', 'user-registration' ),
				$user_email,
				__( 'You can view and manage these data privacy requests here:', 'user-registration' ),
				$visit_url
			);
			wp_mail( get_bloginfo( 'admin_email' ), $subject, $message, $headers );
		}

		wp_send_json_success(
			array(
				'success' => 1,
				'answer'  => $answer,
			)
		);
	}

	/**
	 * Add meta when field specific columns visibility is changed in User Listing table.
	 *
	 * @return bool
	 */
	public static function users_table_change_column_state() {

		check_ajax_referer( 'ur-users-column-change' );

		if ( isset( $_POST['form'] ) ) {
			$form_id = (int) sanitize_text_field( $_POST['form'] );

			if ( $form_id ) {
				update_user_meta( get_current_user_id(), "ur_users_hidden_columns_{$form_id}", true );
			}
		}
		return true;
	}

	/**
	 * Auto logout if the certain inactive time is over.
	 */
	public static function inactive_logout() {
		$security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
		if ( '' === $security || ! wp_verify_nonce( $security, 'inactive_logout_nonce' ) ) {
			wp_send_json_error( __( 'Nonce verification failed', 'user-registration' ) );
			return;
		}
		$user_id = get_current_user_id();
		// logout the current login user.
		wp_logout( $user_id );
		wp_send_json_success( __( 'Logout successfully', 'user-registration' ) );
	}

	/**
	 * User Slot booking
	 * To the slot is booked or not.
	 *
	 * @since 4.1.0
	 */
	public static function user_slot_booking() {
		$security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';

		if ( '' === $security || ! wp_verify_nonce( $security, 'slot_booking_data_nonce' ) ) {
			wp_send_json_error( __( 'Nonce verification failed', 'user-registration' ) );
			return;
		}

		$enable_date_slot_booking = isset( $_POST['enableDateSlotBooking'] ) ? sanitize_text_field( wp_unslash( $_POST['enableDateSlotBooking'] ) ) : '';
		$enable_time_slot_booking = isset( $_POST['enableTimeSlotBooking'] ) ? sanitize_text_field( wp_unslash( $_POST['enableTimeSlotBooking'] ) ) : '';

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';

		if ( $mode === '' || ( ! ur_string_to_bool( $enable_date_slot_booking ) && ! ur_string_to_bool( $enable_time_slot_booking ) ) ) {
			wp_send_json_error( __( 'Invalid request', 'user-registration' ) );
			return;
		}

		$date_value    = isset( $_POST['dateValue'] ) ? sanitize_text_field( wp_unslash( $_POST['dateValue'] ) ) : '';
		$date_locale   = isset( $_POST['dateLocal'] ) ? sanitize_text_field( wp_unslash( $_POST['dateLocal'] ) ) : '';
		$time_value    = isset( $_POST['timeValue'] ) ? sanitize_text_field( wp_unslash( $_POST['timeValue'] ) ) : '';
		$time_interval = isset( $_POST['timeInterval'] ) ? sanitize_text_field( wp_unslash( $_POST['timeInterval'] ) ) : '';
		$format        = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : '';
		$form_id       = isset( $_POST['formId'] ) ? sanitize_text_field( wp_unslash( $_POST['formId'] ) ) : '';
		$mode_type     = isset( $_POST['modeType'] ) ? sanitize_text_field( wp_unslash( $_POST['modeType'] ) ) : '';
		$field_key     = isset( $_POST['fieldKey'] ) ? sanitize_text_field( wp_unslash( $_POST['fieldKey'] ) ) : '';

		$date_time_arr = ur_pro_parse_date_time( $date_value, $time_value, $time_interval, $mode, $format, $mode_type, $date_locale );

		$users_slot_booked_meta_data = get_users_slot_booked_meta_data( $form_id );

		if ( empty( $date_time_arr ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please select at least one date time.', 'user_registration' ),
				)
			);
		}

		$is_booked = false;

		foreach ( $date_time_arr as $arr ) {
			foreach ( $users_slot_booked_meta_data as $id => $booked_slot ) {
				if ( empty( $booked_slot ) ) {
					continue;
				}
				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
					if ( $user_id === $id ) {
						continue;
					}
				}
				if ( array_key_exists( $field_key, $booked_slot ) ) {
					$slot_arrs = $booked_slot[ $field_key ];
					if ( empty( $slot_arrs ) ) {
						continue;
					}
					$slot = $slot_arrs[0];
					if ( $arr[0] >= $slot[0] && $arr[1] <= $slot[1] ) {
						$is_booked = true;
						break;
					} elseif ( $arr[0] >= $slot[0] && $arr[0] < $slot[1] && $arr[1] >= $slot[1] ) {
						$is_booked = true;
						break;
					}
				}
			}
		}

		if ( $is_booked ) {
			wp_send_json_success(
				array(
					'message' => apply_filters( 'ur_pro_slot_booking_message', esc_html__( 'This slot is already booked. Please choose other slot', 'user-registration' ) ),
				)
			);
		}
		wp_send_json_error(
			array(
				'message' => __( 'This slot is not booked.', 'user-registration' ),
			)
		);
	}

	/**
	 * cancel_subscription
	 */
	public static function cancel_subscription() {
		$security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
		if ( '' === $security || ! wp_verify_nonce( $security, 'user_data_nonce' ) ) {
			wp_send_json_error( 'Nonce verification failed' );

			return;
		}
		$is_membership_active = is_plugin_active( 'user-registration-membership/user-registration-membership.php' );
		if ( ! $is_membership_active ) {
			wp_send_json_error( __( 'Membership addon is not active.', 'user-registration' ) );
		}
		if ( ! isset( $_POST['subscription_id'] ) ) {
			wp_send_json_error( __( 'Wrong request.', 'user-registration' ) );
		}

		$subscription_id = absint( $_POST['subscription_id'] );

		$subscription_repository = new SubscriptionRepository();
		$cancel_status           = $subscription_repository->cancel_subscription_by_id( $subscription_id );

		if ( $cancel_status['status'] ) {
			wp_destroy_current_session();
			wp_clear_auth_cookie();
			wp_set_current_user( 0 );
			wp_send_json_success(
				array(
					'message' => $cancel_status['message'],
				)
			);
		} else {
			$message = $cancel_status['message'] ?? esc_html__( 'Something went wrong while cancelling your subscription. Please contact support', 'user-registration-membership' );
			wp_send_json_error(
				array(
					'message' => $message,
				)
			);

		}
	}

	public static function get_coupon_detail() {
		$security = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
		if ( '' === $security || ! wp_verify_nonce( $security, 'user_data_nonce' ) ) {
			wp_send_json_error( 'Nonce verification failed' );

			return;
		}
		if ( ! isset( $_POST['coupon'] ) ) {
			wp_send_json_error( __( 'Coupon field is required.', 'user-registration' ) );

			return;
		}
		if ( ! isset( $_POST['form_id'] ) ) {
			wp_send_json_error( __( 'Form Id field is required.', 'user-registration' ) );

			return;
		}
		$coupon = sanitize_text_field( $_POST['coupon'] );

		$coupon_details = ur_get_coupon_details( $coupon );

		$form_id = absint( $_POST['form_id'] );
		$data    = UR_Form_Handler::get_form_data_from_post( $form_id );

		$array = array_filter(
			$data,
			function ( $item ) {
				return ( $item->field_type === 'coupon' );
			}
		);

		if ( isset( $coupon_details['coupon_status'] ) && ! $coupon_details['coupon_status'] ) {
			wp_send_json_error( __( 'Coupon is Inactive.', 'user-registration' ) );

			return;
		}

		if ( isset( $coupon_details['coupon_end_date'] ) && $coupon_details['coupon_end_date'] < date( 'Y-m-d' ) ) {
			wp_send_json_error( __( 'Coupon expired.', 'user-registration' ) );

			return;
		}

		$coupon_settings = array_pop( $array );
		$settings        = json_decode( $coupon_settings->value, true );
		$message         = esc_html( $settings['invalid_coupon_message'] );

		$invalid_coupon_message = isset( $message ) && ! empty( $message ) ? $message : __( 'Coupon is Invalid.', 'user-registration' );

		// if('' == $settings['target_field'] ) {
		// wp_send_json_error( __('No target field selected.', 'user-registration') );

		// return;
		// }

		if ( empty( $coupon_details ) ) {
			wp_send_json_error( $invalid_coupon_message );

			return;
		}
		$coupon_details['invalid_coupon_message'] = $invalid_coupon_message;
		$coupon_details['target_field']           = esc_html( $settings['target_field'] );

		// validate if coupon is for form or notCOUPON123
		// validate if coupon is not empty and has details
		// validate if provided coupon was applied to the specific form from where the request is being sent

		if ( 'form' !== $coupon_details['coupon_for'] || ! in_array( $form_id, json_decode( $coupon_details['coupon_form'], true ) ) || $coupon_details['coupon_start_date'] > date( 'Y-m-d' ) ) {
			wp_send_json_error( $invalid_coupon_message );

			return;
		}

		wp_send_json_success(
			array(
				'message'        => __( 'Coupon applied successfully', 'user-registration' ),
				'error_message'  => $invalid_coupon_message,
				'coupon_details' => json_encode( $coupon_details ),
			)
		);
	}

	/**
	 * Handle disable user ajax request.
	 *
	 * @since 4.3.0
	 */
	public static function disable_user() {
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_send_json_error( array( 'message' => 'Sorry, you are not allowed to delete users.' ) );
		}

		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'bulk-users' ) ) { // phpcs:ignore
			wp_send_json_error( array( 'message' => 'Invalid nonce or unauthorized request.' ) );
		}
		$user_id        = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
		$duration_value = isset( $_GET['duration_value'] ) ? intval( $_GET['duration_value'] ) : 0;
		$duration_unit  = isset( $_GET['duration_unit'] ) ? sanitize_text_field( wp_unslash( $_GET['duration_unit'] ) ) : '';

		if ( 0 === $user_id || 0 === $duration_value || '' === $duration_unit ) {
			wp_send_json_error( array( 'message' => 'Invalid time duration.' ) );
		}
		if ( isset( $_GET['action'] ) && 'user_registration_pro_disable_user' === $_GET['action'] ) {

			switch ( $duration_unit ) {
				case 'days':
					$auto_enable_time = gmdate( 'Y-m-d H:i:s', strtotime( "+$duration_value days" ) );
					break;

				case 'weeks':
					$auto_enable_time = gmdate( 'Y-m-d H:i:s', strtotime( "+$duration_value weeks" ) );
					break;

				case 'months':
					$auto_enable_time = gmdate( 'Y-m-d H:i:s', strtotime( "+$duration_value months" ) );
					break;

				case 'years':
					$auto_enable_time = gmdate( 'Y-m-d H:i:s', strtotime( "+$duration_value years" ) );
					break;

				default:
					wp_send_json_error( array( 'message' => 'Invalid duration unit.' ) );

			}
			update_user_meta( $user_id, 'ur_disable_users', true );
			update_user_meta( $user_id, 'ur_auto_enable_time', $auto_enable_time );
			wp_send_json_success( array( 'message' => 'User disabled successfully.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Invalid request.' ) );
		}
	}
}


User_Registration_Pro_Ajax::init();
