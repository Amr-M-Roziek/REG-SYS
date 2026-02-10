<?php
/**
 * Admin class
 *
 * User_Registration_Pro Admin
 *
 * @package User_Registration_Pro
 * @since  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Pro_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class User_Registration_Pro_Admin {

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_menu', array( $this, 'analytics_menu' ), 11 );
			add_action( 'user_registration_auto_generate_password', array( $this, 'user_registration_pro_auto_generate_password' ) );
			add_filter( 'user_registration_success_params', array( $this, 'user_registration_after_register_mail' ), 10, 4 );

			// Frontend message settings.
			add_filter( 'user_registration_frontend_messages_settings', array( $this, 'add_auto_generated_password_frontend_message' ) );
			add_action( 'admin_init', array( $this, 'actions' ) );
			add_action( 'admin_print_scripts', array( $this, 'hide_unrelated_notices' ) );
			add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );
			add_filter( 'user_registration_frontend_messages_settings', array( $this, 'add_email_suggestion_error_message' ) );

			// Set Email Templates settings in form builder.
			add_action( 'user_registration_after_form_settings', array( $this, 'render_pro_section' ) );
			add_filter( 'user_registration_form_settings_save', array( $this, 'save_pro_settings' ), 10, 2 );
			add_action( 'user_registration_after_form_settings_save', array( $this, 'save_pro_form_settings' ), 10, 1 );

			// Add admin settings.
			add_filter( 'user_registration_general_settings', array( $this, 'ur_pro_add_general_settings' ) );
			add_filter( 'user_registration_login_options_settings', array( $this, 'ur_pro_add_login_options_settings' ) );
			add_filter( 'user_registration_login_options', array( $this, 'add_admin_approval_after_email_confirmation_login_option' ) );
			add_filter( 'user_registration_advanced_settings', array( $this, 'ur_pro_add_advanced_settings' ) );

			add_action( 'user_registration_after_addons_description', array( $this, 'ur_pro_add_addons_page_footer' ) );
			add_action( 'init', array( $this, 'addon_actions' ) );
			add_action( 'admin_init', array( $this, 'handle_backward_compatibility' ) );
			// auto populate setting in advance settings.
			$fields = user_registration_pro_auto_populate_supported_fields();
			foreach ( $fields as $field ) {
				add_filter( $field . '_custom_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			}

			add_filter( 'user_registration_multi_select2_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			add_filter( 'user_registration_timepicker_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			add_filter( 'user_registration_phone_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			add_filter( 'user_registration_select2_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			// Validate as unique field in advance settings.
			$field_type = array( 'nickname', 'display_name', 'first_name', 'last_name', 'text', 'user_url' );
			foreach ( $field_type as $field ) {
				add_filter( $field . '_custom_advance_settings', array( $this, 'ur_pro_validate_as_unique' ), 10, 3 );
			}
			add_filter( 'user_registration_phone_field_advance_settings', array( $this, 'ur_pro_validate_as_unique' ), 10, 3 );
			add_filter( 'user_registration_field_options_general_settings', array( $this, 'add_form_field_tooltip_options' ), 10, 2 );
			add_filter( 'user_registration_field_options_general_settings', array( $this, 'add_form_field_captcha_options' ), 10, 2 );
			add_filter( 'user_registration_field_options_general_settings', array( $this, 'add_form_field_image_choice_options' ), 11, 2 );
			// Pattern validation in advance settings.
			$pattern_fields = user_registration_pro_pattern_validation_fields();
			foreach ( $pattern_fields as $field ) {
				if ( 'phone' == $field || 'custom_url' == $field ) {
					add_filter( 'user_registration_' . $field . '_field_advance_settings', array( $this, 'ur_pro_pattern_validation' ), 10, 3 );
				} else {
					add_filter( $field . '_custom_advance_settings', array( $this, 'ur_pro_pattern_validation' ), 10, 3 );
				}
			}

			/**
			 * Slot booking in the avanced setting of date field.
			 *
			 * @since 4.1.0
			 */
			add_filter( 'date_custom_advance_settings', array( $this, 'ur_pro_date_slot_booking_settings' ), 10, 3 );
			/**
			 * Slot booking in the avanced setting of timepicker field.
			 *
			 * @since 4.1.0
			 */
			add_filter( 'user_registration_timepicker_field_advance_settings', array( $this, 'ur_pro_time_slot_booking_settings' ), 10, 3 );

			// Validate field as unique when admin update the user profile from admin users table.
			add_action( 'user_registration_after_admin_save_profile_validation', array( $this, 'validate_unique_field_profile_update_by_admin' ), 10, 2 );
			add_action( 'user_profile_update_errors', array( $this, 'check_unique_fields' ), 10, 3 );

			add_action( 'user_registration_custom_export_template', array( $this, 'display_custom_fields_options' ), 10, 1 );
			// Restrict copy,cut and paste on confirm email and confirm password fields.
			$restricted_fields = array( 'user_confirm_email', 'user_confirm_password' );
			foreach ( $restricted_fields as $field ) {
				add_filter( $field . '_custom_advance_settings', array( $this, 'ur_pro_restrict_copy_paste' ), 10, 3 );
			}
			add_action(
				'user_registration_after_admin_save_profile_validation',
				array( $this, 'user_registration_pro_sync_external_fields_after_admin_save_profile_validation' ),
				10,
				2
			);

			add_filter( 'user_registration_redirect_after_registration_options', array( $this, 'add_role_based_redirection_option' ) );
			add_filter( 'user_registration_get_form_settings', array( $this, 'add_role_based_redirection_setting' ), 1, 1 );
			add_filter( 'user_registration_form_settings_save', array( $this, 'save_role_based_redirection_form_settings' ), 10, 3 );

			add_action( 'init', array( $this, 'init_users_menu' ) );

			add_filter( 'user_registration_exclude_fields_for_admin_profile', array( $this, 'get_exclude_fields_for_admin_profile' ) );

			add_action( 'user_registration_user_view_sidebar', array( $this, 'show_payment_status_in_single_user_view' ), 99 );

			add_filter( 'user_registration_get_payment_details', array( $this, 'get_payment_details' ), 10, 1 );

			add_filter( 'user_registration_one_time_draggable_form_fields', array( $this, 'ur_signature_field_one_time_drag' ), 10, 1 );

			add_action( 'user_registration_update_options_misc', array( $this, 'add_delete_user_schedular' ) );
		}

		/**
		 * get_payment_details
		 *
		 * @param $user_id
		 *
		 * @return array|void
		 */
		public function get_payment_details( $user_id ) {
			$ur_payment_method       = get_user_meta( $user_id, 'ur_payment_method', true );
			$ur_payment_subscription = get_user_meta( $user_id, 'ur_payment_subscription', true );

			if ( ! $ur_payment_method ) {
				return;
			}

			$payment_status = array(
				'ur_payment_transaction'  => esc_html__( 'Transaction Id', 'user-registration' ),
				'ur_payment_method'       => esc_html__( 'Method', 'user-registration' ),
				'ur_payment_total_amount' => esc_html__( 'Total Amount', 'user-registration' ),
			);

			if ( '' !== $ur_payment_subscription ) {
				$payment_status['ur_payment_interval']               = esc_html__( 'Subscription Period', 'user-registration' );
				$payment_status['ur_payment_customer']               = esc_html__( 'Customer ID', 'user-registration' );
				$payment_status['ur_payment_subscription']           = esc_html__( 'Subscription ID', 'user-registration' );
				$payment_status['ur_payment_subscription_status']    = esc_html__( 'Subscription Status', 'user-registration' );
				$payment_status['ur_payment_subscription_plan_name'] = esc_html__( 'Subscription Plan Name', 'user-registration' );
				$payment_status['ur_payment_subscription_expiry']    = esc_html__( 'Subscription Expiry Date', 'user-registration' );
			}

			$payment_status['ur_payment_status'] = esc_html__( 'Payment Status', 'user-registration' );

			if ( 'paypal_standard' === $ur_payment_method ) {
				$payment_status['ur_payment_recipient'] = esc_html__( 'Payment Recipient', 'user-registration' );
				$payment_status['ur_payment_note']      = esc_html__( 'Payment Note', 'user-registration' );
			}

			$payment_status['ur_payment_mode'] = esc_html__( 'Payment Mode', 'user-registration' );

			return $payment_status;
		}

		/**
		 * Payment Status display on Single User View page.
		 *
		 * @param mixed $user User id.
		 * @return void
		 * @throws Exception Error Messages.
		 */
		public function show_payment_status_in_single_user_view( $user_id ) {
			$ur_payment_method       = get_user_meta( $user_id, 'ur_payment_method', true );
			$ur_payment_subscription = get_user_meta( $user_id, 'ur_payment_subscription', true );

			if ( ! $ur_payment_method ) {
				return;
			}

			$payment_status = array(
				'ur_payment_transaction'  => esc_html__( 'Transaction Id', 'user-registration' ),
				'ur_payment_method'       => esc_html__( 'Method', 'user-registration' ),
				'ur_payment_items'        => esc_html__( 'Payment Items', 'user-registration' ),
				'ur_payment_total_amount' => esc_html__( 'Total Amount', 'user-registration' ),
			);

			if ( '' !== $ur_payment_subscription ) {
				$payment_status['ur_payment_interval']               = esc_html__( 'Subscription Period', 'user-registration' );
				$payment_status['ur_payment_customer']               = esc_html__( 'Customer ID', 'user-registration' );
				$payment_status['ur_payment_subscription']           = esc_html__( 'Subscription ID', 'user-registration' );
				$payment_status['ur_payment_subscription_status']    = esc_html__( 'Subscription Status', 'user-registration' );
				$payment_status['ur_payment_subscription_plan_name'] = esc_html__( 'Subscription Plan Name', 'user-registration' );
				$payment_status['ur_payment_subscription_expiry']    = esc_html__( 'Subscription Expiry Date', 'user-registration' );
			}

			$payment_status['ur_payment_status'] = esc_html__( 'Payment Status', 'user-registration' );

			if ( 'paypal_standard' === $ur_payment_method ) {
				$payment_status['ur_payment_recipient'] = esc_html__( 'Payment Recipient', 'user-registration' );
				$payment_status['ur_payment_note']      = esc_html__( 'Payment Note', 'user-registration' );
			}

			$payment_status['ur_payment_mode'] = esc_html__( 'Payment Mode', 'user-registration' );

			?>

			<div class="sidebar-box" id="user-registration-user-view-payment-details">
				<h2 class="box-title"><?php esc_html_e( 'Payment Status', 'user-registration' ); ?></h2>
				<ul>
				<?php
				foreach ( $payment_status as $meta_key => $label ) {
					$currencies = ur_payment_integration_get_currencies();
					$currency   = get_user_meta( $user_id, 'ur_payment_currency', true );
					$symbol     = $currencies[ $currency ]['symbol'];

					if ( 'ur_payment_items' === $meta_key ) {
						printf(
							'<li id="user-registration-user-payment-detail-%s"><p><span>%s:&nbsp;</span></p>',
							esc_attr( $meta_key ),
							esc_html( $label ),
						);

						$ur_cart_items = json_decode( get_user_meta( $user_id, 'ur_cart_items', true ) );
						if ( ! empty( $ur_cart_items ) ) {
							echo '<ul class="ur-user-payment-items">';
							foreach ( $ur_cart_items as $key => $payment_items ) {
								$quantity                           = isset( $payment_items->quantity ) ? $payment_items->quantity : '';
								$amount                             = isset( $payment_items->amount ) ? $payment_items->amount : '';
								$payment_items->extra_params->label = str_replace( 'u2013', '–', $payment_items->extra_params->label );

								if ( is_object( $payment_items->value ) ) {
									printf(
										'<li><p><span>%s:&nbsp;</span></p>',
										esc_html( $payment_items->extra_params->label ),
									);
									echo '<ul class="ur-user-payment-multiple-choice">';
									foreach ( $payment_items->value as $label => $value ) {
										$label = str_replace( 'u2013', '–', $label );

										if ( ! empty( $quantity ) ) {
											printf(
												'<li><p><span>%s &nbsp; X %s </span> <span> = %s</span></p></li>',
												esc_html( $label ),
												esc_html( $quantity ),
												esc_html( $symbol . '' . ( $quantity * $value ) ),
											);
										} else {
											printf(
												'<li><p><span>%s &nbsp;</span> <span> = %s</span></p></li>',
												esc_html( $label ),
												esc_html( $symbol . '' . $value ),
											);
										}
									}
									echo '</ul>';
									echo '</li>';
								} elseif ( ! empty( $quantity ) ) {
										printf(
											'<li><p><span>%s &nbsp; %s X %s </span> <span> = %s</span></p></li>',
											esc_html( $payment_items->extra_params->label ),
											esc_html( $symbol . '' . $payment_items->value ),
											esc_html( $quantity ),
											esc_html( $symbol . '' . $amount ),
										);
								} elseif ( 0 !== $payment_items->amount ) {
										printf(
											'<li><p><span>%s &nbsp;</span> <span> = %s</span></p></li>',
											esc_html( $payment_items->extra_params->label ),
											esc_html( $symbol . '' . $payment_items->value ),
										);
								}
							}
							echo '</ul>';
						}
						printf( '</li>' );
					} else {
						$value = get_user_meta( $user_id, $meta_key, true );

						if ( 'ur_payment_total_amount' === $meta_key ) {
							$value = $symbol . '' . $value;

						} elseif ( 'ur_payment_subscription_status' === $meta_key ) {
							$value = 'cancel_at_end_of_cycle' === $value ? 'active' : $value;
						} elseif ( 'ur_payment_method' === $meta_key ) {
							$value = ( 'credit_card' == $value ) ? __( 'Stripe ( Credit Card )', 'user-registration' ) : $value;
							$value = ( 'ideal' == $value ) ? __( 'Stripe ( iDEAL )', 'user-registration' ) : $value;
							$value = ( 'paypal_standard' == $value ) ? __( 'PayPal Standard', 'user-registration' ) : $value;
							$value = ( 'authorize.net' == $value ) ? __( 'Authorize.net Card', 'user-registration' ) : $value;
						} elseif ( 'ur_payment_mode' === $meta_key ) {

							if ( 'test' == $value ) {
								$value = __( 'Test/Sandbox', 'user-registration' );
							} elseif ( 'production' === $value || 'live' == $value ) {
								$value = __( 'Production', 'user-registration' );
							}
						} elseif ( 'ur_payment_currency' === $meta_key ) {
							$currencies = ur_payment_integration_get_currencies();
							$value      = $currencies[ $value ]['name'] . ' ( ' . $value . ' ' . $currencies[ $value ]['symbol'] . ' )';
						}

						printf(
							'<li id="user-registration-user-payment-detail-%s"><p><span>%s:&nbsp;</span><span>%s</span></p></li>',
							esc_attr( $meta_key ),
							esc_html( $label ),
							esc_html( $value )
						);
					}
				}
				?>
				</ul>
			</div>

			<?php
		}

		/**
		 * Exclude Fields for admin user edit section.
		 *
		 * @since 3.1.0
		 *
		 * @param array $exclude_fields Exclude Fields.
		 *
		 * @return array
		 */
		public function get_exclude_fields_for_admin_profile( $exclude_fields ) {
			return array_merge( $exclude_fields, array( 'captcha' ) );
		}

		/**
		 * Sync External Field when update user from admin side.
		 *
		 * @param int   $user_id User ID.
		 * @param array $profile Form Details.
		 */
		public function user_registration_pro_sync_external_fields_after_admin_save_profile_validation( $user_id, $profile ) {

			if ( isset( $_POST['ur_user_user_status'] ) && 1 != $_POST['ur_user_user_status'] ) {
				return;
			}

			$form_id = ur_get_form_id_by_userid( $user_id );

			$valid_form_data = array();

			foreach ( $_POST as $post_key => $post_data ) {

				$pos = strpos( $post_key, 'user_registration_' );

				if ( false !== $pos ) {
					$new_string = substr_replace( $post_key, '', $pos, strlen( 'user_registration_' ) );

					if ( ! empty( $new_string ) ) {
						$valid_form_data[ $new_string ]               = new stdClass();
						$valid_form_data[ $new_string ]->value        = $post_data;
						$valid_form_data[ $new_string ]->field_type   = isset( $profile[ $post_key ]['type'] ) ? $profile[ $post_key ]['type'] : '';
						$valid_form_data[ $new_string ]->label        = isset( $profile[ $post_key ]['label'] ) ? $profile[ $post_key ]['label'] : '';
						$valid_form_data[ $new_string ]->field_name   = $new_string;
						$valid_form_data[ $new_string ]->extra_params = array(
							'field_key' => isset( $profile[ $post_key ]['field_key'] ) ? $profile[ $post_key ]['field_key'] : '',
							'label'     => isset( $profile[ $post_key ]['label'] ) ? $profile[ $post_key ]['label'] : '',
						);
					}
				} else {
					$key        = 'email' === $post_key ? 'user_email' : $post_key;
					$field_data = 'user_registration_' . $key;
					$data       = isset( $profile[ $field_data ] ) ? $profile[ $field_data ] : array();

					if ( ! empty( $data ) ) {
						$valid_form_data[ $key ]               = new stdClass();
						$valid_form_data[ $key ]->value        = $post_data;
						$valid_form_data[ $key ]->field_type   = isset( $profile[ $post_key ]['type'] ) ? $profile[ $post_key ]['type'] : '';
						$valid_form_data[ $key ]->label        = isset( $profile[ $post_key ]['label'] ) ? $profile[ $post_key ]['label'] : '';
						$valid_form_data[ $key ]->field_name   = $key;
						$valid_form_data[ $key ]->extra_params = array(
							'field_key' => isset( $profile[ $post_key ]['field_key'] ) ? $profile[ $post_key ]['field_key'] : '',
							'label'     => isset( $profile[ $post_key ]['label'] ) ? $profile[ $post_key ]['label'] : '',
						);
					}
				}
			}

			if ( count( $valid_form_data ) < 1 ) {
				return;
			}
			user_registration_pro_sync_external_field( $valid_form_data, $form_id, $user_id );
		}

		/**
		 * Handles Backward Compatibility for exits users who already whitelisted domain..
		 *
		 * @since 3.2.1
		 */
		public function handle_backward_compatibility() {

			$is_already_compatible = get_option( 'user_registration_pro_whitelist_compatibility', false );

			if ( ! $is_already_compatible ) {
				$ur_pro_whitelist_option = get_option( 'user_registration_pro_domain_restriction_settings', '' );

				if ( ! empty( $ur_pro_whitelist_option ) ) {
					$this->handle_backward_compatibility_for_individual_form( $ur_pro_whitelist_option );
				}
			}
		}

		/**
		 * Handle backward compatibility for individual form
		 *
		 * @param mixed $ur_pro_whitelist_option Previous Domain Data..
		 */
		public function handle_backward_compatibility_for_individual_form( $ur_pro_whitelist_option ) {

			$registration = get_posts(
				array(
					'post_type' => 'user_registration',
				)
			);

			foreach ( $registration as $form ) {
				update_post_meta( $form->ID, 'user_registration_form_setting_enable_whitelist_domain', true );
				update_post_meta( $form->ID, 'user_registration_form_setting_whitelist_domain', 'allowed' );
				$whitelist = array_map( 'trim', explode( PHP_EOL, $ur_pro_whitelist_option ) );
				update_post_meta( $form->ID, 'user_registration_form_setting_domain_restriction_settings', implode( ',', $whitelist ) );
			}

			update_option( 'user_registration_pro_whitelist_compatibility', true );
		}

		/**
		 * Add Combine two login option.
		 *
		 * @param  array $options Other login options.
		 * @return  array
		 */
		public function add_admin_approval_after_email_confirmation_login_option( $options ) {
			$options['admin_approval_after_email_confirmation'] = esc_html__( 'Admin approval after email confirmation', 'user-registration' );
			return $options;
		}

		/**
		 * Enqueue scripts
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			global $wpdb;
			$min = ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) ? '.min' : '';
			wp_register_style( 'user-registration-pro-admin-style', UR()->plugin_url() . '/assets/css/user-registration-pro-admin.css', array( 'flatpickr', 'user-registration-admin' ), UR_VERSION );

			if ( isset( $_GET['page'] ) && ( 'user-registration-settings' === $_GET['page'] || 'user-registration-analytics' === $_GET['page'] || 'user-registration-addons' === $_GET['page'] ) ) {

				wp_register_script(
					'user-registration-pro-dashboard',
					UR()->plugin_url() . '/assets/js/pro/admin/user-registration-pro-dashboard-script' . $min . '.js',
					array(
						'jquery',
						'flatpickr',
						'ur-chartjs',
					),
					UR_VERSION
				);

				wp_enqueue_script( 'user-registration-pro-dashboard' );
				wp_enqueue_style( 'user-registration-pro-admin-style' );

				wp_localize_script(
					'user-registration-pro-dashboard',
					'user_registration_pro_dashboard_script_data',
					array(
						'ajax_url'                => admin_url( 'admin-ajax.php' ),
						'user_registration_analytics_nonce' => wp_create_nonce( 'user_registration_analytics_nonce' ),
						'dashboard_page_template' => user_registration_pro_analytics_body(),
					)
				);
			}

			if ( isset( $_GET['page'] ) && ( 'add-new-registration' === $_GET['page'] || 'user-registration-addons' === $_GET['page'] || 'user-registration-settings' === $_GET['page'] || 'user-registration-users' === $_GET['page'] ) ) {
				wp_register_script(
					'user-registration-pro-admin',
					UR()->plugin_url() . '/assets/js/pro/admin/user-registration-pro-admin-script' . $min . '.js',
					array(
						'jquery',
						'flatpickr',
						'ur-chartjs',
						'user-registration-admin',
						'sweetalert2',
					),
					UR_VERSION,
					true
				);

				wp_enqueue_style( 'sweetalert2' );

				wp_enqueue_script( 'user-registration-pro-admin' );
				wp_enqueue_style( 'user-registration-pro-admin-style' );
				wp_register_script(
					'user-registration-pro-addon-admin',
					UR()->plugin_url() . '/assets/js/pro/admin/user-registration-pro-addon-admin-script' . $min . '.js',
					array(
						'jquery',
					),
					UR_VERSION
				);

				wp_enqueue_script( 'user-registration-pro-addon-admin' );
				wp_localize_script(
					'user-registration-pro-admin',
					'user_registration_pro_admin_script_data',
					array(
						'ajax_url'                         => admin_url( 'admin-ajax.php' ),
						'ur_pro_external_fields_mapping_output' => $this->ur_pro_external_fields_mapping_output(),
						'ur_pro_form_fields'               => $this->get_forms_all_fields_data(),
						'ur_pro_db_tables'                 => user_registration_get_all_db_tables(),
						'ur_pro_install_extension'         => wp_create_nonce( 'ur_pro_install_extension_nonce' ),
						'ur_pro_get_db_columns_by_table'   => wp_create_nonce( 'ur_pro_get_db_columns_by_table_nonce' ),
						'ur_pro_get_license_expiry_count'  => wp_create_nonce( 'ur_pro_get_license_expiry_count_nonce' ),
						'ur_pro_get_form_fields_by_form_id' => wp_create_nonce( 'ur_pro_get_form_fields_by_form_id_nonce' ),
						'ur_pro_extension_installed_failed_text' => __( 'Installation Failed !!', 'user-registration' ),
						'ur_pro_db_prefix'                 => $wpdb->prefix,
						'ur_placeholder'                   => UR()->plugin_url() . '/assets/images/UR-placeholder.png',
						'disable_user_title'               => __( 'Disable User', 'user-registration' ),
						'cancel'                           => __( 'Cancel', 'user-registration' ),
						'disable'                          => __( 'Disable', 'user-registration' ),
						'disable_user_placeholder'         => __( 'Enter Value', 'user-registration' ),
						'disable_user_success_message_title' => __( 'User Disabled Successfully', 'user-registration' ),
						'disable_user_success_message'     => __( 'The user has beed disabled successfully. He/She will not be able to login for the specified timeframe.', 'user-registration' ),
						'disable_user_error_message_title' => __( 'User cannot be disabled.', 'user-registration' ),
						'disable_user_error_message'       => __( 'There was an error disabling the user.', 'user-registration' ),
						'disable_user_popup_content'       => __( 'Please specify the timeframe to disable this user', 'user-registration' ),
						'after_disable_redirect_url'       => admin_url( 'admin.php?page=user-registration-users' ),
					)
				);

				wp_enqueue_script(
					'user-registration-pro-dashicons-picker',
					UR()->plugin_url() . '/assets/js/pro/admin/dashicons-picker' . $min . '.js',
					array(
						'jquery',
						'user-registration-admin',
					),
					UR_VERSION,
					true
				);
				wp_enqueue_style(
					'user-registration-pro-dashicons-picker-style',
					UR()->plugin_url() . '/assets/css/dashicons-picker.css',
					array(
						'user-registration-admin',
					),
					UR_VERSION,
				);
			}
			$is_payment_activated =
				ur_check_module_activation( 'payments' )
				|| is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' )
				|| is_plugin_active( 'user-registration-authorize-net/user-registration-authorize-net.php' );
			/**
			 * Filter that holds whether any payment feature is active or not.
			 *
			 *@param bool $is_payment_activated
    		*/
			if ( $is_payment_activated || apply_filters( 'is_payment_feature_active', $is_payment_activated ) ) {
				$screen    = get_current_screen();
				$screen_id = $screen ? $screen->id : '';
				if ( in_array( $screen_id, ur_get_screen_ids(), true ) ) {
					wp_enqueue_script( 'user-registration-payment-admin', plugins_url( "/assets/js/pro/admin/user-registration-payment-admin{$min}.js", UR_PLUGIN_FILE ), array( 'jquery', 'user-registration-form-builder' ), UR_VERSION, true );
					wp_enqueue_style( 'user-registration-payment-invoice-style', plugins_url( '/assets/css/user-registration-payment-invoice.css', UR_PLUGIN_FILE ), array(), UR_VERSION, 'all' );

          $payment_setting_url = admin_url('admin.php?page=user-registration-settings&tab=payment'); //phpcs:ignore;
					wp_localize_script(
						'user-registration-payment-admin',
						'ur_payment_params',
						array(
							'ajax_url'                  => admin_url( 'admin-ajax.php' ),
							'is_valid_currency'         => self::check_is_valid_currency(),
							'invalid_currency_message' => _x('Selected currency %CODE% is not supported by Paypal. ' . '<a href="' . esc_url($payment_setting_url) . '" rel="noreferrer noopener" target="_blank">' . 'Click here </a> to change the currency', 'user registration admin', 'user-registration'), // phpcs:ignore
							'select_field_text'         => __( '-- Select target field --', 'user-registration' ),
							'compare_selling_regular_price_message' => __( 'Please enter value less than regular price', 'user-registration' ),
							'payments_disabled_message' => __( 'Please enable atleast one payment method.', 'user-registration' ),
						)
					);
				}
			}
		}

		/**
		 * Check valid currency.
		 */
		public function check_is_valid_currency() {
			$saved_currency   = get_option( 'user_registration_payment_currency', 'USD' );
			$invalid_currency = array();
			if ( ! in_array( $saved_currency, paypal_supported_currencies_list() ) ) {
				$invalid_currency['is_invalid'] = true;
				$invalid_currency['currency']   = $saved_currency;
			}
			return $invalid_currency;
		}

		/**
		 * Render External field mapping section.
		 */
		public function ur_pro_external_fields_mapping_output() {
			global $wpdb;
			$form_id                = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;
			$get_all_fields         = user_registration_pro_get_conditional_fields_by_form_id( $form_id, '' );
			$external_fields_mapped = $this->get_external_field_mapping_list( $form_id, $get_all_fields );

			if ( $external_fields_mapped ) {
				return $external_fields_mapped;
			} else {
				$output  = '<div class="ur-pro-fields-mapping-container">';
				$output .= '<div class="ur-pro-external-fields-mapping-container">';

				$output .= '<div class="ur-pro-field-mapping-wrap" >';
				$output .= '<div class="ur-pro-mapping-warning-wrap" ><br/>';
				$output .= '<div class="ur-pro-mapping-db-table-selection" >';
				$output .= '<label>' . esc_html__( 'Select table where you want to store data of selected fields.', 'user-registration' ) . '</label>';

				$output .= '<select class="ur-pro-db-table-section" name="ur-pro-db-table-section">';
				$output .= '<option value="usermeta_table">Usermeta Table</option>';
				$output .= '<option value="external_table">External Table</option>';
				$output .= '</select>';
				$output .= '</div>';

				$output .= '</div><br/>';

				$output .= '<div class="ur-pro-external-field-table-column-selection">';
				$output .= '<div class="ur-pro-mapping-warning-notice" >';
				$output .= '<span style="color:red;font-weight:bold" >' . esc_html__( 'Please make sure all fields are valid before saving the form.', 'user-registration' ) . '</span>';

				$output    .= '</div><br/>';
				$get_tables = user_registration_get_all_db_tables();
				if ( ! empty( $get_tables ) ) {
					// $selected_table = $wpdb->prefix . 'usermeta';
					$output .= '<label class="user_registration_db_table_label">' . esc_html__( 'Table Name', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table name where data will be stored.", 'user-registration' ) . '"></span></label><select name="user_registration_db_table" class="ur_pro_db_table">';
					$output .= '<option value="">-- Select Table Name --</option>';
					foreach ( $get_tables as $key => $table_name ) {
						// $selected = $selected_table === $table_name ? 'selected="selected"' : '';
						$output .= '<option value="' . esc_attr( $table_name ) . '"> ' . $table_name . ' </option>';
					}
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_user_id_db_column_label">' . esc_html__( 'Column for User ID', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table user id column name where user id will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_user_id_db_column" class="ur_pro_user_id_db_column">';
					$output .= '<option value="">-- Select Column for User ID --</option>';
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_key_db_column_label"> ' . esc_html__( 'Column for Field Key', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field key column name where field key will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_key_db_column" class="ur_pro_field_key_db_column">';
					$output .= '<option value="">-- Select Column for Field Key --</option>';
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_value_db_column_label">  ' . esc_html__( 'Column for Field Value', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field value column name where field value will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_value_db_column" class="ur_pro_field_value_db_column">';
					$output .= '<option value="">-- Select Column for Field Value --</option>';
					$output .= '</select><br/>';
				}
				$output .= '</div>';
				$output .= '<ul class="ur-pro-field-mapping-box" data-last-key="1">';

				$data_key = 1;
				$output  .= '<li class="ur-pro-external-field-map-group">';
				$output  .= '<div class="ur-pro-external-field-map-form-group" style="text-align:center;" >';
				$output  .= '<b>' . esc_html__( 'Form Fields', 'user-registration' ) . '</b>';
				$output  .= '</div>';
				$output  .= '<div class="ur-pro-operator"></div>';
				$output  .= '<div class="ur-pro-value">';
				$output  .= '<b>' . esc_html__( 'External Field Keys', 'user-registration' ) . '</b>';
				$output  .= '</div>';
				$output  .= '</li>';
				$output  .= '<li class="ur-pro-external-field-map-group" data-key="' . $data_key . '">';
				$output  .= '<div class="ur-pro-external-field-map-form-group">';
				$output  .= '<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' . $data_key . ']">';
				$output  .= '<option value="">' . esc_html__( '-- Select Field --', 'user-registration' ) . '</option>';

				foreach ( $get_all_fields as $ind_field_key => $ind_field_value ) {
					$output .= '<option value="' . esc_attr__( $ind_field_key, 'user-registration' ) . '" data-type="' . esc_attr__( $ind_field_value['field_key'], 'user-registration' ) . '"> ' . $ind_field_value['label'] . ' </option>';
				}
				$output .= '</select></div>';
				$output .= '<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
				$output .= '<div class="ur-pro-value">';
				$output .= '<input name="user_registration_form_value[' . $data_key . ']" class="ur_pro_external_field_name" placeholder="Enter Field Key" type="text" />';
				$output .= '</div>';
				$output .= '<span class="add">';
				$output .= '<i class="dashicons dashicons-plus"></i>';
				$output .= '</span>';
				$output .= '<span class="remove">';
				$output .= '<i class="dashicons dashicons-minus"></i>';
				$output .= '</span></li>';
				$output .= '</ul>';
				$output .= '</div>';
				$output .= '</div>';
				$output .= '</div>';
				return $output;
			}
		}

		/**
		 *  Get All form fields of the individual form in Form Settings.
		 *
		 * @return array
		 */
		public function get_forms_all_fields_data() {
			$form_id        = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;
			$get_all_fields = user_registration_pro_get_conditional_fields_by_form_id( $form_id, '' );
			return array(
				'all_form_fields' => $get_all_fields,
			);
		}

		/**
		 * Fetch Already mapped external fields.
		 *
		 * @param int   $form_id Form_id.
		 * @param array $get_all_fields All fields.
		 */
		public function get_external_field_mapping_list( $form_id, $get_all_fields ) {
			global $wpdb;
			$field_mapping_settings = maybe_unserialize( get_post_meta( $form_id, 'user_registration_pro_external_fields_mapping', true ) );

			if ( ! empty( $field_mapping_settings ) ) {
				$output  = '<div class="ur-pro-fields-mapping-container">';
				$output .= '<div class="ur-pro-external-fields-mapping-container">';

				$output .= '<div class="ur-pro-field-mapping-wrap" >';
				$output .= '<div class="ur-pro-mapping-warning-wrap" ><br/>';
				$output .= '<div class="ur-pro-mapping-db-table-selection" >';
				$output .= '<label>' . esc_html__( 'Select table where you want to store data of selected fields.', 'user-registration' ) . '</label>';

				$output .= '<select class="ur-pro-db-table-section" name="ur-pro-db-table-section">';

				$usermeta_table          = $wpdb->prefix . 'usermeta';
				$selected_db_table       = isset( $field_mapping_settings[0]['db_table'] ) ? $field_mapping_settings[0]['db_table'] : $usermeta_table;
				$selected_meta_table     = $usermeta_table === $selected_db_table ? 'selected="selected"' : '';
				$selected_external_table = $usermeta_table !== $selected_db_table ? 'selected="selected"' : '';

				$output .= '<option value="usermeta_table" ' . $selected_meta_table . '>Usermeta Table</option>';
				$output .= '<option value="external_table" ' . $selected_external_table . '>External Table</option>';
				$output .= '</select>';
				$output .= '</div>';

				$output .= '</div><br/>';

				$output .= '<div class="ur-pro-external-field-table-column-selection">';
				$output .= '<div class="ur-pro-mapping-warning-notice" >';
				$output .= '<span style="color:red;font-weight:bold" > ' . esc_html__( 'Please make sure all fields are valid before saving the form.', 'user-registration' ) . '</span> </div> <br/>';

				$get_tables = user_registration_get_all_db_tables();
				if ( ! empty( $get_tables ) ) {
					$output .= '<label class="user_registration_db_table_label">' . esc_html__( 'Table Name', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table name where data will be stored.", 'user-registration' ) . '"></span></label><select name="user_registration_db_table" class="ur_pro_db_table">';
					$output .= '<option value="">-- Select Table Name --</option>';
					foreach ( $get_tables as $key => $table_name ) {
						$selected = $selected_db_table === $table_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $table_name ) . '" ' . $selected . '> ' . $table_name . ' </option>';
					}
					$output .= '</select><br/>';

					$get_columns                    = user_registration_get_columns_by_table( $selected_db_table );
					$selected_user_id_db_column     = isset( $field_mapping_settings[0]['user_id_db_column'] ) ? $field_mapping_settings[0]['user_id_db_column'] : '';
					$selected_field_key_db_column   = isset( $field_mapping_settings[0]['field_key_db_column'] ) ? $field_mapping_settings[0]['field_key_db_column'] : '';
					$selected_field_value_db_column = isset( $field_mapping_settings[0]['field_value_db_column'] ) ? $field_mapping_settings[0]['field_value_db_column'] : '';

					$output .= '<label class="user_registration_user_id_db_column_label">  ' . esc_html__( 'Column for User ID', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table user id column name where user id will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_user_id_db_column" class="ur_pro_user_id_db_column">';
					$output .= '<option value="">-- Select Column for User ID --</option>';
					foreach ( $get_columns as $key => $column_name ) {
						$selected = $selected_user_id_db_column === $column_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $column_name ) . '" ' . $selected . '> ' . $column_name . ' </option>';
					}
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_key_db_column_label">  ' . esc_html__( 'Column for Field Key', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field key column name where field key will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_key_db_column" class="ur_pro_field_key_db_column">';
					$output .= '<option value="">-- Select Column for Field Key --</option>';
					foreach ( $get_columns as $key => $column_name ) {
						$selected = $selected_field_key_db_column === $column_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $column_name ) . '" ' . $selected . '> ' . $column_name . ' </option>';
					}
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_value_db_column_label">  ' . esc_html__( 'Column for Field Value', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field value column name where field value will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_value_db_column" class="ur_pro_field_value_db_column">';
					$output .= '<option value="">-- Select Column for Field Value --</option>';
					foreach ( $get_columns as $key => $column_name ) {
						$selected = $selected_field_value_db_column === $column_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $column_name ) . '" ' . $selected . '> ' . $column_name . ' </option>';
					}
					$output .= '</select><br/>';
				}
				$output .= '</div>';

				$row_id = 1;

				if ( isset( $field_mapping_settings[0]['mapped_fields'] ) ) {

					$data_key = 1;

					foreach ( $field_mapping_settings[0]['mapped_fields'] as $fields_row ) {
						$output .= '<ul class="ur-pro-field-mapping-box" data-last-key="' . count( $fields_row ) . '">';
						$output .= '<li class="ur-pro-external-field-map-group">';
						$output .= '<div class="ur-pro-external-field-map-form-group" style="text-align:center;" >';
						$output .= '<b>' . esc_html__( 'Form Fields', 'user-registration' ) . '</b>';
						$output .= '</div>';
						$output .= '<div class="ur-pro-operator"></div>';
						$output .= '<div class="ur-pro-value">';
						$output .= '<b>' . esc_html__( 'External Field Keys', 'user-registration' ) . '</b>';
						$output .= '</div>';
						$output .= '</li>';
						foreach ( $fields_row as $key => $mapping_row ) {
							$output .= '<li class="ur-pro-external-field-map-group" data-key="' . $data_key . '">';
							$output .= '<div class="ur-pro-external-field-map-form-group">';
							$output .= '<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' . $data_key . ']">';
							$output .= '<option value="">' . esc_html__( '-- Select --', 'user-registration' ) . '</option>';

							foreach ( $get_all_fields as $ind_field_key => $ind_field_value ) {
								$selectedField = $mapping_row['ur_field'] == $ind_field_key ? 'selected="selected"' : '';
								$output       .= '<option value="' . esc_attr__( $ind_field_key, 'user-registration' ) . '" data-type="' . esc_attr__( $ind_field_value['field_key'], 'user-registration' ) . '" ' . $selectedField . '> ' . $ind_field_value['label'] . ' </option>';
							}
							$output .= '</select></div>';
							$output .= '<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
							$output .= '<div class="ur-pro-value">';
							$output .= '<input name="user_registration_form_value[' . $data_key . ']" value="' . esc_attr( $mapping_row['external_field'] ) . '" class="ur_pro_external_field_name" type="text" />';
							$output .= '</div>';
							$output .= '<span class="add">';
							$output .= '<i class="dashicons dashicons-plus"></i>';
							$output .= '</span>';
							$output .= '<span class="remove">';
							$output .= '<i class="dashicons dashicons-minus"></i>';
							$output .= '</span></li>';
							++$data_key;
						}
						$output .= '</ul>';
					}
				} else {
					$output .= '<ul class="ur-pro-field-mapping-box" data-last-key="1">';

					$data_key = 1;
					$output  .= '<li class="ur-pro-external-field-map-group">';
					$output  .= '<div class="ur-pro-external-field-map-form-group" style="text-align:center;" >';
					$output  .= '<b>' . esc_html__( 'Form Fields', 'user-registration' ) . '</b>';
					$output  .= '</div>';
					$output  .= '<div class="ur-pro-operator"></div>';
					$output  .= '<div class="ur-pro-value">';
					$output  .= '<b>' . esc_html__( 'External Field Keys', 'user-registration' ) . '</b>';
					$output  .= '</div>';
					$output  .= '</li>';
					$output  .= '<li class="ur-pro-external-field-map-group" data-key="' . $data_key . '">';
					$output  .= '<div class="ur-pro-external-field-map-form-group">';
					$output  .= '<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' . $data_key . ']">';
					$output  .= '<option value="">' . esc_html__( '-- Select Field --', 'user-registration' ) . '</option>';

					foreach ( $get_all_fields as $ind_field_key => $ind_field_value ) {
						$output .= '<option value="' . esc_attr__( $ind_field_key, 'user-registration' ) . '" data-type="' . esc_attr__( $ind_field_value['field_key'], 'user-registration' ) . '"> ' . $ind_field_value['label'] . ' </option>';
					}
					$output .= '</select></div>';
					$output .= '<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
					$output .= '<div class="ur-pro-value">';
					$output .= '<input name="user_registration_form_value[' . $data_key . ']" class="ur_pro_external_field_name" placeholder="Enter Field Key" type="text" />';
					$output .= '</div>';
					$output .= '<span class="add">';
					$output .= '<i class="dashicons dashicons-plus"></i>';
					$output .= '</span>';
					$output .= '<span class="remove">';
					$output .= '<i class="dashicons dashicons-minus"></i>';
					$output .= '</span></li>';
					$output .= '</ul>';
				}

				$output .= '</div>';
				$output .= '</div>';
				$output .= '</div>';
				return $output;
			}
		}

		/**
		 * Include auto generated password success message into frontend messages.
		 *
		 * @param array $settings Frontend messages settings array.
		 */
		public function add_auto_generated_password_frontend_message( $settings ) {

			$auto_password_generation = array(
				array(
					'title'    => __( 'Auto generated password success message', 'user-registration' ),
					'desc'     => __( 'Enter the text message after user is registered.', 'user-registration' ),
					'id'       => 'user_registration_pro_auto_password_generation_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'An email with a password to access your account has been sent to your email.', 'user-registration' ),
				),
				array(
					'title'    => __( 'Email verified admin approval pending', 'user-registration' ),
					'desc'     => __( 'Enter the text message after email successfully verified but admin approval is pending.', 'user-registration' ),
					'id'       => 'user_registration_pro_email_verified_admin_approval_await_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'Email successfully verified. But Admin has to approve you to give access to login. Please contact to your administrator for your approval.', 'user-registration' ),
				),
			);

			$settings['sections']['frontend_success_messages_settings']['settings'] = array_merge( $settings['sections']['frontend_success_messages_settings']['settings'], $auto_password_generation );

			return $settings;
		}

		/**
		 * Include email suggestion message into frontend messages.
		 *
		 * @param array $settings error messages settings array.
		 */
		public function add_email_suggestion_error_message( $settings ) {

			$email_suggestion_message = array(
				array(
					'title'    => __( 'Email Suggestion', 'user-registration' ),
					'desc'     => __( 'Enter the message for valid email suggestion.', 'user-registration' ),
					'id'       => 'user_registration_form_submission_email_suggestion',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'Did you mean {suggestion}?', 'user-registration' ),
				),
			);

			$settings['sections']['frontend_error_message_messages_settings']['settings'] = array_merge( array_slice( $settings['sections']['frontend_error_message_messages_settings']['settings'], 0, 3 ), $email_suggestion_message, array_slice( $settings['sections']['frontend_error_message_messages_settings']['settings'], 3 ) );
			return $settings;
		}

		/**
		 * Generate a random password with length provided by the user.
		 *
		 * @since 1.0.0
		 */
		public function user_registration_pro_auto_generate_password( $form_id ) {
			$password_length = ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_generated_password_length' );
			$user_pass       = trim( wp_generate_password( $password_length, true, true ) );
			add_filter(
				'user_registration_auto_generated_password',
				function ( $msg ) use ( $user_pass ) {
					return $user_pass;
				}
			);

			add_filter(
				'user_registration_required_form_fields',
				function ( $required_fields ) {
					$index = array_search( 'user_pass', $required_fields );
					unset( $required_fields[ $index ] );
					return $required_fields;
				}
			);
		}

		/**
		 * Process and submit entry to provider.
		 *
		 * @param array   $valid_form_data Form data submitted
		 * @param integer $form_id ID of the form.
		 * @param int     $user_id ID of the user
		 */
		public function user_registration_after_register_mail( $success_params, $valid_form_data, $form_id, $user_id ) {
			$enable_auto_password_generation = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_password_activate' ) );

			if ( $enable_auto_password_generation ) {
				$this->send_auto_generated_password_email( $user_id, $form_id, $valid_form_data );
				$success_params['auto_password_generation_success_message'] = get_option( 'user_registration_pro_auto_password_generation_message', esc_html( 'An email with a password to access your account has been sent to your email.' ) );
			}
			return $success_params;
		}

		/**
		 * Send mail with auto generated password.
		 *
		 * @param int $user_id ID of the user
		 */
		private function send_auto_generated_password_email( $user_id, $form_id, $form_data ) {

			include __DIR__ . '/admin/settings/emails/class-ur-settings-generated-password-email.php';

			$user                         = get_user_by( 'ID', $user_id );
			$username                     = $user->data->user_login;
			$email                        = apply_filters( 'user_registration_auto_generated_password_recipient', $user->data->user_email );
			$user_pass                    = apply_filters( 'user_registration_auto_generated_password', 'user_pass' );
			list($name_value, $data_html) = ur_parse_name_values_for_smart_tags( $user_id, $form_id, $form_data );

			$values = array(
				'username'   => $username,
				'email'      => $email,
				'all_fields' => $data_html,
			);

			$header  = 'From: ' . UR_Emailer::ur_sender_name() . ' <' . UR_Emailer::ur_sender_email() . ">\r\n";
			$header .= 'Reply-To: ' . UR_Emailer::ur_sender_email() . "\r\n";
			$header .= "Content-Type: text/html\r\n; charset=UTF-8";

			$subject = get_option( 'user_registration_pro_auto_generated_password_email_subject', 'Your password for logging into {{blog_info}}' );

			$settings                = new UR_Settings_Auto_Generated_Password_Email();
			$message                 = $settings->user_registration_get_auto_generated_password_email();
			$message                 = get_option( 'user_registration_pro_auto_generated_password_email_content', $message );
			$form_id                 = ur_get_form_id_by_userid( $user_id );
			list($message, $subject) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

			$message = UR_Emailer::parse_smart_tags( $message, $values, $name_value );
			$subject = UR_Emailer::parse_smart_tags( $subject, $values, $name_value );

			// Get selected email template id for specific form.
			$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

			if ( ur_option_checked( 'user_registration_enable_auto_generated_password_email', true ) ) {
				UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
			}
		}

		/**
		 * Popups admin actions.
		 */
		public function actions() {
			global $user_registration_pro_popup_table_list;
			$user_registration_pro_popup_table_list = new User_Registration_Pro_Popup_Table_List();
		}

		/**
		 * Table list output.
		 */
		public static function user_registration_pro_popup_list_table_output() {

			global $user_registration_pro_popup_table_list;
			$user_registration_pro_popup_table_list->process_actions();
			$user_registration_pro_popup_table_list->display_page();
		}

		/**
		 * Hide Notices From WPList table for Popup list table section.
		 * For Clear appearance.
		 *
		 * @return void
		 */
		public function hide_unrelated_notices() {
			global $wp_filter;

			// Return on other than user registraion builder page.
			if ( empty( $_REQUEST['page'] ) || ( 'user-registration-settings' !== $_REQUEST['page'] || empty( $_REQUEST['tab'] ) || 'user-registration-pro' !== $_REQUEST['tab'] ) && 'user-registration-analytics' !== $_REQUEST['page'] ) {
				return;
			}

			foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
				if ( ! empty( $wp_filter[ $wp_notice ]->callbacks ) && is_array( $wp_filter[ $wp_notice ]->callbacks ) ) {
					foreach ( $wp_filter[ $wp_notice ]->callbacks as $priority => $hooks ) {
						foreach ( $hooks as $name => $arr ) {
							// Remove all notices except user registration plugins notices.
							if ( ! strstr( $name, 'user_registration_' ) ) {
								unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
							}
						}
					}
				}
			}
		}

		/**
		 * Add custom nav meta box.
		 *
		 * Adapted from http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/.
		 */
		public function add_nav_menu_meta_boxes() {
			$args = array(
				'post_type'   => 'ur_pro_popup',
				'post_status' => array( 'publish' ),
			);

			$popups             = new WP_Query( $args );
			$active_popup_count = 0;

			// Check if there is at least one active popup.
			if ( ! empty( $popups->posts ) ) {
				foreach ( $popups->posts as $popup ) {
					$popup_content = json_decode( $popup->post_content );

					if ( ur_string_to_bool( $popup_content->popup_status ) ) {
						++$active_popup_count;
					}
				}
			}

			if ( $active_popup_count > 0 ) {
				add_meta_box(
					'user_registration_pro_popup_nav_link',
					__( 'User Registration Pro Popup', 'user-registration' ),
					array(
						$this,
						'nav_menu_links',
					),
					'nav-menus',
					'side',
					'low'
				);
			}
		}

		/**
		 * Output menu links.
		 */
		public function nav_menu_links() {
			// Get items from account menu.
			$menus   = array();
			$post_id = array();
			$args    = array(
				'post_type'     => 'ur_pro_popup',
				'post_status'   => array( 'publish' ),
				'__post_not_in' => $post_id,
			);

			$popups = new WP_Query( $args );

			foreach ( $popups->posts as $popup ) {
				$post_id[]     = $popup->ID;
				$popup_content = json_decode( $popup->post_content );

				if ( ur_string_to_bool( $popup_content->popup_status ) ) {
					$menus[ 'user-registration-modal-link-' . $popup->ID ] = sprintf( __( '%s', 'user-registration' ), $popup_content->popup_title );
				}
			}

			?>
			<div id="posttype-user-registration-modal" class="posttypediv">
				<div id="tabs-panel-user-registration-modal" class="tabs-panel tabs-panel-active">
					<ul id="user-registration-modal-checklist" class="categorychecklist form-no-clear">
						<?php
						$i = -1;
						foreach ( $menus as $key => $value ) :
							?>
							<li>
								<label class="menu-item-title">
									<input type="checkbox" class="menu-item-checkbox"
											name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]"
											value="<?php echo esc_attr( $i ); ?>"/> <?php echo esc_html( $value ); ?>
								</label>
								<input type="hidden" class="menu-item-type"
										name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom"/>
								<input type="hidden" class="menu-item-title"
										name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]"
										value="<?php echo esc_html( $value ); ?>"/>
								<input type="hidden" class="menu-item-url"
										name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]"
										value="<?php echo esc_url( '#user-registration-modal' ); ?>"/>
								<input type="hidden" class="menu-item-classes"
										name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]"
										value="user-registration-modal-link <?php echo $key; ?>"/>
							</li>
							<?php
							--$i;
					endforeach;
						?>
					</ul>
				</div>
				<p class="button-controls">
					<span class="list-controls">
						<input type="checkbox" id="ur-pro-popups-tab" class="select-all">
						<label for="ur-pro-popups-tab">
							<?php esc_html_e( 'Select All', 'user-registration' ); ?>
						</label>
					</span>
					<span class="add-to-menu">
					<input type="submit" class="button-secondary submit-add-to-menu right"
							value="<?php esc_attr_e( 'Add to menu', 'user-registration' ); ?>"
							name="add-post-type-menu-item" id="submit-posttype-user-registration-modal">
					<span class="spinner"></span>
					</span>
				</p>
			</div>
			<?php
		}

		/**
		 * Add analytics menu item.
		 */
		public function analytics_menu() {
			add_submenu_page(
				'user-registration',
				__( 'User Registration Analytics', 'user-registration' ),
				__( 'Analytics', 'user-registration' ),
				'manage_user_registration',
				'user-registration-analytics',
				array(
					$this,
					'analytics_page',
				),
				1
			);
		}

		/*
		*  Init the analytics page.
		*/
		public function analytics_page() {
			// User_Registration_Pro_Dashboard_Analytics::output();
			include_once UR_ABSPATH . 'templates/pro/dashboard.php';
		}

		/**
		 * Initialize Users Menu.
		 *
		 * @return void
		 */
		public function init_users_menu() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			require_once UR_ABSPATH . 'includes/pro/admin/notifications/class-ur-pro-admin-notification.php';
			require_once UR_ABSPATH . 'includes/pro/admin/settings/class-ur-pro-users-menu.php';
		}

		/**
		 * Render Pro Section
		 *
		 * @since 1.0.7
		 * @param  int $form_id Form Id.
		 * @return void
		 */
		public function render_pro_section( $form_id = 0 ) {

			echo '<div id="pro-settings" ><h3>' . esc_html__( 'Extras', 'user-registration' ) . '</h3>';
			$arguments = $this->get_pro_settings( $form_id );

			foreach ( $arguments as $args ) {
				user_registration_form_field( $args['id'], $args, get_post_meta( $form_id, $args['id'], true ) );
			}

			echo '</div>';
		}

		public function get_pro_settings( $form_id ) {
			$arguments                 = array(
				'form_id'      => $form_id,

				'setting_data' => array(
					array(
						'type'              => 'toggle',
						'label'             => __( 'Enable Keyboard Friendly Form', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_keyboard_friendly_form',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_keyboard_friendly_form', false ),
						'tip'               => __( 'Fill the form easily using keyboard quick keys.', 'user-registration' ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Enable Reset Button', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_enable_reset_button',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_reset_button', false ),
						'tip'               => __( 'To reset the default values of user in registration form', 'user-registration' ),
					),
					array(
						'type'              => 'text',
						'label'             => __( 'Form Reset Button Label', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_form_reset_label',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_form_reset_label', 'Reset' ),
						'tip'               => __( 'Set label for the reset button.', 'user-registration' ),
					),
					array(
						'type'              => 'text',
						'label'             => __( 'Form Reset Button Custom Class', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_form_reset_class',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_form_reset_class', '' ),
						'tip'               => __( 'Custom css class to embed in the reset button. You can enter multiple classes seperated with space.', 'user-registration' ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Enable Form Field Icon', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_enable_field_icon',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_enable_field_icon', false ),
						'tip'               => __( 'To show the field icon in user registration form', 'user-registration' ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Enable Email Suggestion', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_enable_email_suggestion',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_enable_email_suggestion', false ),
						'tip'               => __( 'To show email suggestion in user registration form', 'user-registration' ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Activate Auto Generated Password', 'user-registration' ),
						'tip'               => __( 'Enable auto generated password', 'user-registration' ),
						'id'                => 'user_registration_pro_auto_password_activate',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_password_activate', false ),
					),
					array(
						'type'    => 'number',
						'label'   => __( 'Password Length', 'user-registration' ),
						'tip'     => __( 'The length of password you want to generate.', 'user-registration' ),
						'id'      => 'user_registration_pro_auto_generated_password_length',
						'default' => ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_generated_password_length', 10 ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Activate Spam Protection By Honeypot', 'user-registration' ),
						'tip'               => __( 'Select forms where you want to enable this feature.', 'user-registration' ),
						'id'                => 'user_registration_pro_spam_protection_by_honeypot_enable',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_pro_spam_protection_by_honeypot_enable', false ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Enable Whitelist/Blacklist Domain', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_enable_whitelist_domain',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_whitelist_domain', false ),
						'tip'               => __( 'Enable to whitelist or blacklist email domain.', 'user-registration' ),
					),
					array(
						'label'             => __( 'Whitelist/Blacklist Domain', 'user-registration' ),
						'description'       => '',
						'id'                => 'user_registration_form_setting_whitelist_domain',
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_whitelist_domain', 'allowed' ),
						'type'              => 'select',
						'class'             => array( 'ur-enhanced-select' ),
						'custom_attributes' => array(),
						'input_class'       => array(),
						'required'          => false,
						'options'           => array(
							'allowed' => esc_html__( 'Allowed Domains', 'user-registration' ),
							'denied'  => esc_html__( 'Denied Domains', 'user-registration' ),
						),
						'tip'               => __( 'Choose Allowed/Denied to Whitelist/Blacklist domains respectively.', 'user-registration' ),
					),
					array(
						'label'       => __( 'Domains Entries', 'user-registration' ),
						'description' => __( 'Multiple domain must be separated by comma.', 'user-registration' ),
						'id'          => 'user_registration_form_setting_domain_restriction_settings',
						'placeholder' => 'for eg. gmail.com, outlook.com',
						'default'     => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_domain_restriction_settings', '' ),
						'type'        => 'textarea',
						'rows'        => 8,
						'cols'        => 40,
						'css'         => 'min-width: 350px; min-height: 100px;',
						'tip'         => __( 'Add the domain lists to whitelist/blacklist them.', 'user-registration' ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Enable Blacklist Words', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_enable_blacklist_words',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_blacklist_words', false ),
						'tip'               => __( 'Enable to blacklist words for username and other fields.', 'user-registration' ),
					),
					array(
						'label'             => __( 'Blacklisted Fields', 'user-registration' ),
						'description'       => '',
						'id'                => 'user_registration_form_setting_blacklisted_words_field_settings',
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_blacklisted_words_field_settings', 'allowed' ),
						'type'              => 'multiselect',
						'class'             => array( 'ur-enhanced-select', 'ur-select2-multiple' ),
						'custom_attributes' => array(),
						'input_class'       => array(),
						'required'          => false,
						'options'           => ur_get_input_fields_for_blacklisting( $form_id ),
						'css'               => 'width:100px;',
						'tip'               => __( 'Choose fields for blacklisting the words.', 'user-registration' ),
					),
					array(
						'label'       => __( 'Blacklisted Words', 'user-registration' ),
						'description' => __( 'Multiple words must be separated by comma.', 'user-registration' ),
						'id'          => 'user_registration_form_setting_blacklisted_words_settings',
						'placeholder' => 'for eg. admin, administrator',
						'default'     => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_blacklisted_words_settings', '' ),
						'type'        => 'textarea',
						'rows'        => 8,
						'cols'        => 40,
						'css'         => 'min-width: 350px; min-height: 100px;',
						'tip'         => __( 'Add the words to blacklist them.', 'user-registration' ),
					),
					array(
						'type'              => 'toggle',
						'label'             => __( 'Enable form field mapping with external fields', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_enable_external_fields_mapping',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_enable_external_fields_mapping', false ),
						'tip'               => __( 'To enable mapping the external fields with user registration fields', 'user-registration' ),
					),
					array(
						'label'             => __( 'Enable Email blocking', 'user-registration' ),
						'description'       => '',
						'id'                => 'user_registration_form_setting_email_blocking',
						'type'              => 'toggle',
						'description'       => '',
						'required'          => false,
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_email_and_ip_blocking', false ),
						'tip'               => __( 'Enable to email or ip blocking.', 'user-registration' ),
					),
					array(
						'label'       => __( 'Email Black List', 'user-registration' ),
						'description' => __( 'Multiple email must be separated by comma.', 'user-registration' ),
						'placeholder' => 'for eg. demo@gmail.com, test@yahoo.com',
						'id'          => 'user_registration_form_setting_email_black_list',
						'default'     => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_email_black_list', '' ),
						'type'        => 'textarea',
						'rows'        => 8,
						'cols'        => 40,
						'css'         => 'min-width: 350px; min-height: 100px;',
						'required'    => false,
						'tip'         => __( 'Add the emails to blacklist them.', 'user-registration' ),
					),
				),
			);
			$arguments                 = apply_filters( 'user_registration_get_pro_settings', $arguments );
			$arguments['setting_data'] = apply_filters( 'user_registration_settings_text_format', $arguments['setting_data'] );
			return $arguments['setting_data'];
		}

		/**
		 * Save Pro Form Settings
		 *
		 * @param  array   $settings Settings.
		 * @param  integer $form_id Form Id.
		 * @return array $settings
		 */
		public function save_pro_settings( $settings, $form_id = 0 ) {

			$pro_setting = $this->get_pro_settings( $form_id );
			$settings    = array_merge( $settings, $pro_setting );

			return $settings;
		}

		/**
		 * Save External field mappingsettings.
		 *
		 * @param array $post Post.
		 * @return void.
		 */
		public function save_pro_form_settings( $post ) {
			$form_id                          = absint( $post['form_id'] );
			$ur_pro_external_mapping_settings = isset( $post['ur_pro_external_mapping_settings'] ) ? wp_unslash( $post['ur_pro_external_mapping_settings'] ) : array();

			// conditional user role settings save.
			if ( ! empty( $ur_pro_external_mapping_settings ) ) {
				update_post_meta( $form_id, 'user_registration_pro_external_fields_mapping', $ur_pro_external_mapping_settings );
			}
		}

		/**
		 * Add to general settings of User Registration.
		 *
		 * @param array $general_settings General settings array from Core.
		 */
		public function ur_pro_add_general_settings( $general_settings ) {

			// Add new settings to my account section.
			$my_account_options = $general_settings['sections']['my_account_options']['settings'];
			$my_account_options = array_merge(
				$my_account_options,
				array(
					array(
						'title'    => __( 'Delete Account Action ', 'user-registration' ),
						'desc'     => __( 'Allow your users to delete their account from their account page directly or after password confirmation.', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_delete_account',
						'default'  => 'disable',
						'type'     => 'select',
						'class'    => 'ur-enhanced-select',
						'css'      => 'min-width: 350px;',
						'desc_tip' => true,
						'options'  => array(
							'disable'         => __( 'Disable', 'user-registration' ),
							'direct_delete'   => __( 'Direct Delete', 'user-registration' ),
							'prompt_password' => __( 'Prompt password popup before delete account.', 'user-registration' ),
						),
					),
					array(
						'title'    => __( 'Auto Logout After Inactivity', 'user-registration' ),
						'desc'     => __( 'Logout users automatically after a certain period of inactivity.', 'user-registration' ),
						'id'       => 'user_registration_auto_logout_inactivity_time',
						'type'     => 'select',
						'class'    => 'ur-enhanced-select-nostd',
						'desc_tip' => true,
						'default'  => get_option( 'user_registration_auto_logout_inactivity_time', '' ),
						'options'  => apply_filters(
							'user_registration_auto_logout_inactivity_period',
							array(
								''   => __( 'None', 'user-registration' ),
								'5'  => __( '5 minutes', 'user-registration' ),
								'10' => __( '10 minutes', 'user-registration' ),
								'15' => __( '15 minutes', 'user-registration' ),
								'20' => __( '20 minutes', 'user-registration' ),
								'30' => __( '30 minutes', 'user-registration' ),
								'60' => __( '60 minutes', 'user-registration' ),
							)
						),
						'css'      => 'min-width: 350px;',
					),
					array(
						'title'    => __( 'Logout Timeout Period', 'user-registration' ),
						'desc'     => __( 'Time to show the countdown screen before logging out the user.', 'user-registration' ),
						'id'       => 'user_registration_timeout_countdown_inactive_period',
						'type'     => 'select',
						'class'    => 'ur-enhanced-select-nostd',
						'desc_tip' => true,
						'default'  => get_option( 'user_registration_timeout_countdown_inactive_period' ),
						'options'  => apply_filters(
							'user_registration_timeout_countdown_inactive_period',
							array(
								'10' => __( '10 seconds', 'user-registration' ),
								'15' => __( '15 seconds', 'user-registration' ),
								'20' => __( '20 seconds', 'user-registration' ),
								'30' => __( '30 seconds', 'user-registration' ),
								'60' => __( '60 seconds', 'user-registration' ),
							)
						),
						'css'      => 'min-width: 350px;',
					),
					array(
						'title'    => __( 'Logout Roles', 'user-registration' ),
						'desc'     => __( 'Select the roles to apply auto logout after inactivity.', 'user-registration' ),
						'id'       => 'user_registration_role_based_inactivity',
						'type'     => 'multiselect',
						'class'    => 'ur-enhanced-select',
						'desc_tip' => true,
						'options'  => apply_filters(
							'user_registration_role_based_inactivity',
							ur_get_default_admin_roles()
						),
						'css'      => 'min-width: 350px; select2',
						'default'  => array( 'subscriber' ),
					),
				)
			);

			$general_settings['sections']['my_account_options']['settings'] = $my_account_options;

			return $general_settings;
		}

		/**
		 * Add to advanced settings of User Registration.
		 *
		 * @param array $advanced_settings Advanced settings array from Core.
		 */
		public function ur_pro_add_advanced_settings( $advanced_settings ) {
			// Add new settings to advanced options.
			$advanced_options = $advanced_settings['sections']['advanced_settings']['settings'];

			foreach ( $advanced_options as $option_index => $option ) {
				if ( $option['id'] === 'user_registration_allow_usage_tracking' ) {
					$advanced_options[ $option_index ]['desc'] = sprintf( __( 'Help us improve the plugin\'s features by sharing %1$snon-sensitive plugin data%2$s with us.', 'user-registration' ), '<a href="https://docs.wpuserregistration.com/docs/miscellaneous-settings/#1-toc-title" rel="noreferrer noopener" target="_blank">', '</a>' );
				}
			}

			$advanced_options = array_merge(
				$advanced_options,
				array(
					array(
						'title'    => __( 'Webhook Submission URL', 'user-registration' ),
						'desc'     => __( 'This option lets you send form data to a custom URL of your choice', 'user-registration' ),
						'id'       => 'user_registration_pro_general_post_submission_settings',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
					),
					array(
						'title'   => __( 'Profile Details Update', 'user-registration' ),
						'desc'    => __( 'Allows <strong>Webhook Submission</strong> to custom url on <strong>Profile Details</strong> update', 'user-registration' ),
						'id'      => 'user_registration_pro_general_post_submission_profile_update',
						'type'    => 'toggle',
						'css'     => 'min-width: 350px;',
						'default' => 'no',
					),
					array(
						'title'    => __( 'Webhook Submission Method', 'user-registration' ),
						'desc'     => __( 'This option lets you choose how you want the form submitted data request to be sent.', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_post_submission',
						'default'  => 'disable',
						'type'     => 'select',
						'class'    => 'ur-enhanced-select',
						'css'      => 'min-width: 350px;',
						'desc_tip' => true,
						'options'  => array(
							'post'      => __( 'POST', 'user-registration' ),
							'post_json' => __( 'POST(JSON)', 'user-registration' ),
							'get'       => __( 'GET', 'user-registration' ),
						),
					),
					array(
						'title'    => __( 'Enable User Activity Tracker', 'user-registration' ),
						'desc'     => __( 'Enable tracker to analyze user behaviours regarding your forms and pages.', 'user-registration' ),
						'id'       => 'user_registration_enable_user_activity',
						'type'     => 'toggle',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
						'class'    => '',
						'default'  => 'false',
					),
				)
			);

			$advanced_settings['sections']['advanced_settings']['settings'] = $advanced_options;

			$advanced_settings_section = $advanced_settings['sections'];
			$privacy_settings_section  = array(
				'privacy_settings' =>
				array(
					'title'    => __( 'Privacy', 'user-registration' ),
					'type'     => 'card',
					'desc'     => '',
					'settings' => array(
						array(
							'title'    => __( 'Enable Privacy Tab', 'user-registration' ),
							'desc_tip' => __( 'Check to enable privacy tab in the my account.', 'user-registration' ),
							'id'       => 'user_registration_enable_privacy_tab',
							'type'     => 'toggle',
							'css'      => 'min-width: 350px;',
							'default'  => 'false',
						),
						array(
							'title'    => __( 'Allow Profile Privacy', 'user-registration' ),
							'desc'     => __( 'Enable user to select their profile to be private or public', 'user-registration' ),
							'id'       => 'user_registration_enable_profile_privacy',
							'type'     => 'toggle',
							'desc_tip' => true,
							'css'      => 'min-width: 350px;',
							'class'    => 'privacy-tab-settings',
							'default'  => 'true',
						),
						array(
							'title'    => __( 'Allow Search Engine Indexing', 'user-registration' ),
							'desc'     => __( 'Enable user to either allow or disallow search engine indexing of their profile', 'user-registration' ),
							'id'       => 'user_registration_enable_profile_indexing',
							'type'     => 'toggle',
							'desc_tip' => true,
							'css'      => 'min-width: 350px;',
							'class'    => 'privacy-tab-settings',
							'default'  => 'true',
						),
						array(
							'title'    => __( 'Allow Download Personal Data', 'user-registration' ),
							'desc'     => __( 'Enable users to send a request to download personal data', 'user-registration' ),
							'id'       => 'user_registration_enable_download_personal_data',
							'type'     => 'toggle',
							'desc_tip' => true,
							'css'      => 'min-width: 350px;',
							'class'    => 'privacy-tab-settings',
							'default'  => 'true',
						),
						array(
							'title'    => __( 'Allow Erase Personal Data', 'user-registration' ),
							'desc'     => __( 'Enable users to send a request to erase their personal data', 'user-registration' ),
							'id'       => 'user_registration_enable_erase_personal_data',
							'type'     => 'toggle',
							'desc_tip' => true,
							'css'      => 'min-width: 350px;',
							'class'    => 'privacy-tab-settings',
							'default'  => 'true',
						),
					),
				),
			);

			$all_roles = ur_get_default_admin_roles();

			$all_roles_except_admin = $all_roles;

			unset( $all_roles_except_admin['administrator'] );

			$delete_user_sch_settings_section = array(
				'delete_user_schedular_settings' =>
				array(
					'title'    => __( 'Auto Delete Users', 'user-registration' ),
					'type'     => 'card',
					'desc'     => '',
					'settings' => array(
						array(
							'title'    => __( 'Enable Auto Delete Users ', 'user-registration' ),
							'desc_tip' => __( 'Enable automatic deletion of users based on status, role, and schedule.', 'user-registration' ),
							'id'       => 'user_registration_enable_delete_user_schedular',
							'type'     => 'toggle',
							'css'      => 'min-width: 350px;',
							'default'  => 'false',
						),
						array(
							'title'    => __( 'Duration', 'user-registration' ),
							'desc'     => __( 'Choose how often users should be deleted (e.g., monthly, weekly).', 'user-registration' ),
							'id'       => 'user_registration_delete_user_schedular_duration',
							'default'  => 'disable',
							'type'     => 'select',
							'class'    => 'ur-enhanced-select',
							'css'      => 'min-width: 350px;',
							'desc_tip' => true,
							'multiple' => true,
							'options'  => apply_filters(
								'user_registration_delete_user_schedular_duration',
								array(
									''         => __( '--Select Duration--', 'user-registration' ),
									'+1 day'   => __( 'Daily', 'user-registration' ),
									'+1 week'  => __( 'Weekly', 'user-registration' ),
									'+1 month' => __( 'Monthly', 'user-registration' ),
									'+1 year'  => __( 'Yearly', 'user-registration' ),
								)
							),
						),
						array(
							'title'    => __( 'User Status', 'user-registration' ),
							'desc'     => __( 'Choose users based on their status to delete.', 'user-registration' ),
							'id'       => 'user_registration_delete_user_schedular_status',
							'default'  => 'disable',
							'type'     => 'multiselect',
							'class'    => 'ur-enhanced-select',
							'css'      => 'min-width: 350px;',
							'desc_tip' => true,
							'options'  => apply_filters(
								'user_registration_delete_user_schedular_status',
								array(
									'pending'       => __( 'Pending', 'user-registration' ),
									'denied'        => __( 'Denied', 'user-registration' ),
									'pending_email' => __( 'Awaiting Email Confirmation', 'user-registration' ),
								)
							),
						),
						array(
							'title'    => __( 'User Role', 'user-registration' ),
							'desc'     => __( 'Only users with these roles will be considered for deletion.', 'user-registration' ),
							'id'       => 'user_registration_delete_user_schedular_roles',
							'default'  => 'disable',
							'type'     => 'multiselect',
							'class'    => 'ur-enhanced-select',
							'css'      => 'min-width: 350px;',
							'desc_tip' => true,
							'options'  => apply_filters( 'user_registration_delete_user_schedular_roles', $all_roles_except_admin ),
						),
					),
				),
			);

			$advanced_settings['sections'] = array_merge( $advanced_settings_section, $privacy_settings_section, $delete_user_sch_settings_section );

			return $advanced_settings;
		}

		/**
		 * Add to general settings of User Registration.
		 *
		 * @param array $login_option Login option settings array from Core.
		 */
		public function ur_pro_add_login_options_settings( $login_option ) {

			// Add new settings to general options.
			$login_options_settings = $login_option['sections']['login_options_settings']['settings'];
			$login_options_settings = array_merge(
				$login_options_settings,
				array(
					array(
						'title'    => __( 'Enable Login Icon Field', 'user-registration' ),
						'desc'     => __( 'This option lets you to enable icon field in login form', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_login_form',
						'type'     => 'toggle',
						'desc_tip' => __( 'Check to field to enable icon field in login form', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'false',
					),
					array(
						'title'    => __( 'Prevent Active Login', 'user-registration' ),
						'desc'     => __( 'Enable Prevent Concurrent Login', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_prevent_active_login',
						'type'     => 'toggle',
						'desc_tip' => __( 'Check this option to prevent the active logins.', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'false',

					),
					array(
						'title'    => __( 'Maxmium Active Login', 'user-registration' ),
						'desc'     => __( 'This option lets you to choose the number of active logins a user account have.', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_limited_login',
						'type'     => 'number',
						'desc_tip' => true,
						'class'    => 'ur-active-login',
						'default'  => 5,
					),
					array(
						'title'    => __( 'Enable Redirect Back to Previous Page', 'user-registration' ),
						'desc'     => __( 'This option lets you to enable redirect back to previous page after login', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_redirect_back_to_previous_page',
						'type'     => 'toggle',
						'desc_tip' => __( 'Check to field to enable redirect back to previous page after login', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'false',
					),
					array(
						'title'    => __( 'Enable Passwordless Login', 'user-registration' ),
						'desc'     => __( 'This option lets you to enable passwordless login.', 'user-registration' ),
						'id'       => 'user_registration_pro_passwordless_login',
						'type'     => 'toggle',
						'desc_tip' => __( 'This feature allows users to log in via email link instead of a password.', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'false',
					),

					/**
					 * Set passwordless login as Default Login.
					 *
					 * @since 5.0
					 */
					array(
						'title'    => __( 'Set Passwordless login as Default Login Page', 'user-registration' ),
						'desc'     => __( 'This feature allows users to log in via email link instead of a password.', 'user-registration' ),
						'id'       => 'user_registration_pro_passwordless_login_default_login_area',
						'type'     => 'toggle',
						'desc_tip' => __( 'This feature allows users to log in via email link instead of a password.', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'false',
					),
				)
			);

			$login_option['sections']['login_options_settings']['settings'] = $login_options_settings;

			return $login_option;
		}

		/**
		 * Add activate, deactivate and install addon button below addon card in addons page.
		 *
		 * @param object $addon Addons details.
		 * @since 3.0.1
		 */
		public function ur_pro_add_addons_page_footer( $addon ) {
			$license_data = ur_get_license_plan();
			$license_plan = isset( $license_data->item_plan ) ? $license_data->item_plan : '';
			?>
			<?php if ( in_array( trim( $license_plan ), $addon->plan, true ) ) : ?>
				<div class="action-buttons">
					<?php if ( is_plugin_active( $addon->slug . '/' . $addon->slug . '.php' ) && file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
						<?php
						/* translators: %s: Add-on title */
						$aria_label  = sprintf( esc_html__( 'Deactivate %s now', 'user-registration' ), $addon->title );
						$plugin_file = plugin_basename( $addon->slug . '/' . $addon->slug . '.php' );
						$url         = wp_nonce_url(
							add_query_arg(
								array(
									'page'   => 'user-registration-addons',
									'action' => 'deactivate',
									'plugin' => $plugin_file,
								),
								admin_url( 'admin.php' )
							),
							'deactivate-plugin_' . $plugin_file
						);
						?>
						<a class="button button-danger deactivate-now" href="<?php echo esc_url_raw( $url ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Deactivate', 'user-registration' ); ?></a>
					<?php elseif ( file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
						<?php
						/* translators: %s: Add-on title */
						$aria_label  = sprintf( esc_html__( 'Activate %s now', 'user-registration' ), $addon->title );
						$plugin_file = plugin_basename( $addon->slug . '/' . $addon->slug . '.php' );
						$url         = wp_nonce_url(
							add_query_arg(
								array(
									'page'   => 'user-registration-addons',
									'action' => 'activate',
									'plugin' => $plugin_file,
								),
								admin_url( 'admin.php' )
							),
							'activate-plugin_' . $plugin_file
						);
						?>
						<a class="button button-primary activate-now" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Activate', 'user-registration' ); ?></a>
					<?php else : ?>
							<?php
							if ( strpos( $addon->slug, 'payments' ) && strpos( $addon->slug, 'stripe' ) ) {
								$slug = explode( ' ', $addon->slug );
								$slug = explode( ' ', $addon->slug );

								if ( ! is_plugin_active( $slug[0] . '/' . $slug[0] . '.php' ) && ! is_plugin_active( $slug[1] . '/' . $slug[1] . '.php' ) ) {

									if ( ! file_exists( WP_PLUGIN_DIR . '/' . $slug[0] . '/' . $slug[0] . '.php' ) || ! file_exists( WP_PLUGIN_DIR . '/' . $slug[1] . '/' . $slug[1] . '.php' ) ) {
										?>
										<?php
										/* translators: %s: Add-on title */
										$aria_label = sprintf( esc_html__( 'Install %s now', 'user-registration' ), $addon->title );
										?>
										<a href="#" class="button install-now user-registration-install-extensions" data-name="<?php echo esc_attr( $addon->name ); ?>" data-slug="<?php echo esc_attr( $addon->slug ); ?>" data-name="<?php echo esc_attr( $addon->name ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Install Addon', 'user-registration' ); ?></a>
										<?php
									} else {
										$plugins = array(
											plugin_basename( $slug[0] . '/' . $slug[0] . '.php' ),
											plugin_basename( $slug[1] . '/' . $slug[1] . '.php' ),
										);
										$action  = 'activate-selected';

										$url = add_query_arg(
											array(
												'action'   => $action,

												'_wpnonce' => wp_create_nonce( 'bulk-plugins' ),
											),
											admin_url( 'plugins.php' )
										);

										?>
									<form method="post" class="activate-now" action="<?php echo esc_url( $url ); ?>">
										<?php
										foreach ( $plugins as $plugin ) {
											echo '<input type="hidden" name="checked[]" value="' . esc_attr( $plugin ) . '"/>';
										}
										?>

									<button type="submit" class="button button-primary activate-now form" href="<?php echo esc_url( $url ); ?>" ><?php esc_html_e( 'Activate', 'user-registration' ); ?></button>
									</form>
										<?php
									}
								}
							} else {
								?>
								<?php
								/* translators: %s: Add-on title */
								$aria_label = sprintf( esc_html__( 'Install %s now', 'user-registration' ), $addon->title );
								?>
								<a href="#" class="button install-now user-registration-install-extensions" data-name="<?php echo esc_attr( $addon->name ); ?>" data-slug="<?php echo esc_attr( $addon->slug ); ?>" data-name="<?php echo esc_attr( $addon->name ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Install Addon', 'user-registration' ); ?></a>
								<?php
							}
						endif;
					?>
				</div>
			<?php else : ?>
				<div class="action-buttons upgrade-plan">
					<a class="button upgrade-now" href="https://wpuserregistration.com/pricing/?utm_source=addons-page&utm_medium=upgrade-button&utm_campaign=ur-upgrade-to-pro" rel="noreferrer noopener" target="_blank"><?php esc_html_e( 'Upgrade Plan', 'user-registration' ); ?></a>
				</div>
			<?php endif; ?>
			<?php
		}

		/**
		 * Handle redirects after addon activate/deactivate.
		 *
		 * @since 3.0.1
		 */
		public function addon_actions() {
			if ( ! is_admin() && ! is_user_logged_in() ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( isset( $_GET['page'], $_REQUEST['action'] ) && 'user-registration-addons' === $_GET['page'] && 'user-registration-addons-refresh' !== $_GET['action'] ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
				$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : false;

				if ( $plugin && in_array( $action, array( 'activate', 'deactivate' ), true ) ) {

					if ( 'activate' === $action ) {
						if ( ! current_user_can( 'activate_plugin', $plugin ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to activate this plugin.', 'user-registration' ) );
						}

						check_admin_referer( 'activate-plugin_' . $plugin );

						activate_plugin( $plugin );
					} elseif ( 'deactivate' === $action ) {
						if ( ! current_user_can( 'deactivate_plugins' ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to deactivate plugins for this site.', 'user-registration' ) );
						}

						check_admin_referer( 'deactivate-plugin_' . $plugin );

						deactivate_plugins( $plugin );
					}
				}

				// Redirect to the add-ons page.
				wp_safe_redirect( admin_url( 'admin.php?page=user-registration-addons' ) );
				exit;
			}
		}

		/**
		 * Add query string setting in advance setting
		 *
		 * @param string $field_id Identifier for the field to which advance settings are to be added.
		 * @param string $field_class Class for the setting.
		 * @param array  $fields List of settings to be appended in advance settings of fields.
		 *
		 * @since 3.0.1
		 */
		public function ur_pro_auto_populate_advance_setting( $fields, $field_id, $field_class ) {

			$custom_setting = array(

				'enable_prepopulate' => array(
					'label'    => __( 'Allow field to be populated dynamically', 'user-registration' ),
					'data-id'  => $field_id . '_enable_prepopulate',
					'name'     => $field_id . '[enable_prepopulate]',
					'class'    => $field_class . ' ur-settings-field-prepopulate',
					'type'     => 'toggle',
					'required' => false,
					'default'  => 'false',
					'tip'      => __( 'Enable this option to allow field to be populated dynamically', 'user-registration' ),
				),
				'parameter_name'     => array(
					'label'       => __( 'Parameter Name', 'user-registration' ),
					'data-id'     => $field_id . '_parameter_name',
					'name'        => $field_id . '[parameter_name]',
					'class'       => $field_class . ' ur-settings-parameter_name',
					'type'        => 'text',
					'required'    => false,
					'default'     => '',
					'placeholder' => __( 'Enter parameter name', 'user-registration' ),
					'tip'         => __( 'Name of the parameter to populate the field.', 'user-registration' ),
				),
			);

			$fields = array_merge( $fields, $custom_setting );
			return $fields;
		}

		/**
		 * Validate unique field setting.
		 *
		 * @param array  $fields list of fields.
		 * @param int    $field_id Field ID.
		 * @param string $field_class Field class.
		 *
		 * @since 3.0.8
		 */
		public function ur_pro_validate_as_unique( $fields, $field_id, $field_class ) {
			$unique_field_setting = array(
				'validate_unique'    => array(
					'label'    => __( 'Validate as unique', 'user-registration' ),
					'data-id'  => $field_id . '_validate_unique',
					'name'     => $field_id . '[validate_unique]',
					'class'    => $field_class . ' ur-settings-field-validate-unique',
					'type'     => 'toggle',
					'required' => false,
					'default'  => 'false',
					'tip'      => __( 'Limit user input to unique values only. It will require that a value entered in the field is unique and doesn\'t previously exist for this field in the entry database.', 'user-registration' ),
				),
				'validation_message' => array(
					'label'    => __( 'Validation message for duplicate', 'user-registration' ),
					'data-id'  => $field_id . '_validation_message',
					'name'     => $field_id . '[validation_message]',
					'class'    => $field_class . ' ur-settings-validation_message',
					'type'     => 'text',
					'required' => false,
					'default'  => __( 'This field value needs to be unique.', 'user-registration' ),
					'tip'      => __( 'Message that shows when validation as unique fails on form submission.', 'user-registration' ),
				),
			);
			$fields               = array_merge( $fields, $unique_field_setting );
			return $fields;
		}

		/**
		 * Pattern validation field setting.
		 *
		 * @param array  $fields list of fields.
		 * @param int    $field_id Field ID.
		 * @param string $field_class Field class.
		 *
		 * @since 3.0.8
		 */
		public function ur_pro_pattern_validation( $fields, $field_id, $field_class ) {
			$pattern_setting = array(
				'enable_pattern'  => array(
					'label'    => __( 'Enable Pattern Validation', 'user-registration' ),
					'data-id'  => $field_id . '_enable_pattern',
					'name'     => $field_id . '[enable_pattern]',
					'class'    => $field_class . ' ur-settings-custom-class',
					'type'     => 'toggle',
					'required' => false,
					'default'  => 'false',
					'tip'      => __( 'Enable this option to allow pattern validation for this field.', 'user-registration' ),
				),
				'pattern_value'   => array(
					'label'       => __( 'Pattern Value', 'user-registration' ),
					'data-id'     => $field_id . '_pattern_value',
					'name'        => $field_id . '[pattern_value]',
					'class'       => $field_class . ' ur-settings-pattern_value',
					'type'        => 'text',
					'required'    => false,
					'default'     => '',
					'placeholder' => __( 'Enter pattern value', 'user-registration' ),
					'tip'         => __( 'Pattern value is checked against.', 'user-registration' ),
				),
				'pattern_message' => array(
					'label'       => __( 'Pattern Message', 'user-registration' ),
					'data-id'     => $field_id . '_pattern_message',
					'name'        => $field_id . '[pattern_message]',
					'class'       => $field_class . ' ur-settings-pattern_message',
					'type'        => 'text',
					'required'    => false,
					'default'     => __( 'Please provide a valid value for this field.', 'user-registration' ),
					'placeholder' => __( 'Enter pattern message', 'user-registration' ),
					'tip'         => __( 'If the pattern value does not match it will show this message.', 'user-registration' ),
				),
			);
			$fields          = array_merge( $fields, $pattern_setting );
			return $fields;
		}

		/**
		 * Restrict copy/paste field setting.
		 *
		 * @param array  $fields list of fields.
		 * @param int    $field_id Field ID.
		 * @param string $field_class Field class.
		 *
		 * @since 3.0.8
		 */
		public function ur_pro_restrict_copy_paste( $fields, $field_id, $field_class ) {
			$restrict_copy_settings = array(
				'disable_copy_paste' => array(
					'label'    => __( 'Restrict copy/cut/paste', 'user-registration' ),
					'data-id'  => $field_id . '_restrict_copy_paste',
					'name'     => $field_id . '[restrict_copy_paste]',
					'class'    => $field_class . ' ur-settings-field-restrict_copy_paste',
					'type'     => 'select',
					'required' => false,
					'default'  => 'false',
					'options'  => array(
						'true'  => 'Yes',
						'false' => 'No',
					),
					'tip'      => __( 'Restrict copy/cut/paste on this field', 'user-registration' ),
				),
			);

			$fields = array_merge( $fields, $restrict_copy_settings );
			return $fields;
		}

		/**
		 * Add Enable Tooltip option in general field settings.
		 *
		 * @param array $setting General Setting array.
		 * @param int   $field_id Form Id.
		 * @return array.
		 */
		public function add_form_field_tooltip_options( $setting, $field_id ) {
			$exclude_tooltip = apply_filters(
				'user_registration_exclude_tooltip',
				array(
					'section_title',
					'hidden',
				)
			);
			$strip_id        = str_replace( 'user_registration_', '', $field_id );

			if ( in_array( $strip_id, $exclude_tooltip, true ) ) {
				return $setting;
			}

			$custom_options = array(
				'tooltip'         => array(
					'setting_id'  => 'tooltip',
					'type'        => 'toggle',
					'label'       => __( 'Enable Tooltip', 'user-registration' ),
					'name'        => 'ur_general_setting[tooltip]',
					'placeholder' => '',
					'required'    => true,
					'tip'         => __( 'Show tooltip icon beside field label.', 'user-registration' ),
					'default'     => 'false',
				),
				'tooltip_message' => array(
					'setting_id'  => 'tooltip-message',
					'type'        => 'textarea',
					'label'       => __( 'Tooltip Message', 'user-registration' ),
					'name'        => 'ur_general_setting[tooltip_message]',
					'placeholder' => 'Placeholder',
					'required'    => true,
					'tip'         => __( 'The Message to display when user hovers over tooltip icon.', 'user-registration' ),
				),
			);

			$setting = array_merge( $setting, $custom_options );

			return $setting;
		}

		/**
		 * Add Captcha option in general field settings.
		 *
		 * @param array $setting General Setting array.
		 * @param int   $field_id Form Id.
		 * @return array.
		 */
		public function add_form_field_captcha_options( $setting, $field_id ) {

			switch ( $field_id ) {
				case 'user_registration_captcha':
					unset( $setting['required'] );
					$captcha_settings = array(
						'captcha_format'        => array(
							'setting_id'  => 'captcha-format',
							'type'        => 'select',
							'label'       => __( 'Select Format', 'user-registration' ),
							'name'        => 'ur_general_setting[captcha_format]',
							'placeholder' => '',
							'options'     => array(
								'math'  => __( 'Math', 'user-registration' ),
								'qa'    => __( 'Question and Answer', 'user-registration' ),
								'image' => __( 'Image', 'user-registration' ),
							),
							'required'    => true,
							'tip'         => __( 'Choose a captcha format to be displayed on frontend.', 'user-registration' ),
						),
						'options'               => array(
							'setting_id'  => 'options',
							'type'        => 'captcha',
							'label'       => __( 'Questions and Answers', 'user-registration' ),
							'name'        => 'ur_general_setting[captcha_question]',
							'placeholder' => '',
							'options'     => array(
								array(
									'question' => __( 'What is 2+3?', 'user-registration' ),
									'answer'   => '5',
								),
							),
							'required'    => true,
							'tip'         => __( 'Add multiple questions below to ask the user. It will select one question randomly.', 'user-registration' ),
						),
						'image_captcha_options' => array(
							'setting_id'  => 'image-captcha-options',
							'type'        => 'captcha',
							'label'       => __( 'Image Group', 'user-registration' ),
							'name'        => 'ur_general_setting[captcha_image]',
							'placeholder' => '',
							'options'     => array(
								array(
									'icon-1'       => 'dashicons dashicons-menu',
									'icon-2'       => 'dashicons dashicons-admin-network',
									'icon-3'       => 'dashicons dashicons-admin-multisite',
									'correct_icon' => 'icon-1',
									'icon_tag'     => 'Menu',
								),
							),
							'required'    => true,
							'tip'         => __( 'Add multiple icons group below to ask the user. It will select one group randomly.', 'user-registration' ),
						),
					);

					return ur_insert_after_helper( $setting, $captcha_settings, 'field_name' );
					break;
			}

			return $setting;
		}

		/**
		 * Add Enable Tooltip option in general field settings.
		 *
		 * @param array $setting General Setting array.
		 * @param int   $field_id Form Id.
		 * @return array.
		 */
		public function add_form_field_image_choice_options( $setting, $field_id ) {

			switch ( $field_id ) {
				case 'user_registration_checkbox':
				case 'user_registration_radio':
				case 'user_registration_multiple_choice':
					$image_choice_settings = array(
						'image_choice' => array(
							'setting_id'  => 'image_choice',
							'type'        => 'toggle',
							'label'       => __( 'Enable Image Choice', 'user-registration' ),
							'name'        => 'ur_general_setting[image_choice]',
							'placeholder' => '',
							'required'    => true,
							'tip'         => __( 'Enable this option to use image with the choices.', 'user-registration' ),
							'default'     => 'false',
						),
					);
					$setting               = ur_insert_after_helper( $setting, $image_choice_settings, 'options' );
					break;
			}
			return $setting;
		}

		/**
		 * Validate field as profile save from admin.
		 *
		 * @param int   $user_id User Id.
		 * @param array $profile form data.
		 */
		public function validate_unique_field_profile_update_by_admin( $user_id, $profile ) {
			$single_field = array();
			$field_name   = '';
			$label        = '';
			$message      = '';
			$duplicate    = '';
			$form_id      = get_user_meta( $user_id, 'ur_form_id', true );
			if ( isset( $_POST ) ) {
				$form_data = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				foreach ( $form_data as $key => $value ) {
					if ( 'url' === $key ) {
						$key = 'user_' . $key;
					}
					$field_key                  = str_replace( 'user_registration_', '', $key );
					$single_field[ $field_key ] = $value;
				}
			}

			foreach ( $profile as $key_name => $field_item ) {
				foreach ( $field_item as $key => $field_value ) {
					if ( isset( $key ) && 'validate_unique' === $key ) {
						if ( ur_string_to_bool( $field_value ) ) {
							$field_name = str_replace( 'user_registration_', '', $key_name );
							$message    = $field_item['validate_message'];
							$label      = $field_item['label'];
							if ( in_array( $field_name, array_keys( $single_field ), true ) ) {
								$duplicate = ur_validate_unique_field(
									array(
										'ur_form_id' => $form_id,
										'search'     => $single_field[ $field_name ],
										'field_name' => $field_name,
									)
								);
							}

							if ( ! empty( $duplicate ) && ! in_array( $user_id, $duplicate ) ) {
								set_transient( 'ur_unique_field_name', $label, 60 );
								set_transient( 'ur_unique_error_msg', $message, 60 );
								add_filter( 'update_user_metadata', array( $this, 'prevent_update_meta_value' ), 10, 4 );
							}
						}
					}
				}
			}
		}

		/** Show errors message.
		 *
		 * @param object $errors WP_Error.
		 * @param bool   $update user update.
		 * @param object $user user.
		 */
		public function check_unique_fields( $errors, $update, $user ) {
			$name    = get_transient( 'ur_unique_field_name' );
			$message = get_transient( 'ur_unique_error_msg' );
			if ( ! empty( $name ) && ! empty( $message ) ) {
				/* translators: %s - validation message */
				$errors->add( $name, sprintf( __( '<strong>%1$1s:</strong> %2$2s', 'user-registration' ), $name, $message ) );
			}
			delete_transient( 'ur_unique_field_name' );
			delete_transient( 'ur_unique_error_msg' );
		}

		/** Prevent update meta value.
		 *
		 * @param bool   $check check.
		 * @param int    $object_id Id.
		 * @param string $meta_key meta key.
		 * @param mixed  $meta_value meta value.
		 */
		public function prevent_update_meta_value( $check, $object_id, $meta_key, $meta_value ) {
			if ( $meta_key ) {
				return false;
			}
			return $check;
		}

		/**
		 * Outputs Custom Fields Selection area to the Export User page.
		 *
		 * @param int $form_id Form Id.
		 * @return void
		 */
		public function display_custom_fields_options( $form_id = '' ) {

			echo '<div class="ur-export-custom-fields">
					<p>' . esc_html__( 'Select Fields to Export', 'user-registration' ) . '</p>
					<div class="ur-form-fields-container">';

			$fields_dict = UR()->form->get_form_fields(
				$form_id,
				array(
					'content_only' => true,
					'hide_fields'  => true,
				)
			);
			echo '<select name= "csv-export-custom-fields[]" class="ur-custom-fields-input forms-list ur-select2-multiple" multiple>';
			foreach ( $fields_dict as $field_id => $field_label ) {
				echo '<option class="ur-field-option" value="' . esc_attr( $field_id ) . '">' . esc_html( $field_label ) . '</option>';
			}
			echo '</select>';
			echo '</div><div>';
			if ( ! empty( $fields_dict ) ) {

				$additional_info_fields = array(
					'user_id'          => __( 'User ID', 'user-registration' ),
					'user_role'        => __( 'User Role', 'user-registration' ),
					'ur_user_status'   => __( 'User Status', 'user-registration' ),
					'date_created'     => __( 'User Registered', 'user-registration' ),
					'date_created_gmt' => __( 'User Registered GMT', 'user-registration' ),
				);
				echo '<p>' . esc_html__( 'Select Additional to Export', 'user-registration' ) . '</p>
						<div class="ur-form-additional-fields-container">';
				echo '<input type="hidden" name="all_fields_dict" class="ur_export_csv_additional_fields_dict" value="' . esc_attr( wp_json_encode( $fields_dict ) ) . '"/>';
				echo '<select name= "all_selected_fields_dict[]" class="forms-list ur-select2-multiple" multiple>';

				foreach ( $additional_info_fields as $field_id => $field_label ) {
					echo '<option class="" value="' . esc_attr( $field_id ) . '">' . esc_html( $field_label ) . '</option>';
				}
				echo '</select></div>';
				echo '<p>' . esc_html__( 'Export Formats', 'user-registration' ) . '</p><div>';
				echo '<select name = "export_format">
				<option value = "csv">' . esc_html__( 'Export as CSV', 'user-registration' ) . '</option>
				</select>';
				// Export JSON option has been remove for now but can be added later.
				echo '</div>';
				echo '<p>' . esc_html__( 'Registered Date Range', 'user-registration' ) . '</p><div>';
				echo '<input type = "button" id = "date_range" name = "date_range"/>
				<input type="hidden" id="from_date" value="" name="from_date"/><input type="hidden" value="" id="to_date" name="to_date" />';
				echo '</div></div><br>';
			}
		}


		/**
		 * Add Role Based Redirection option in Redirection Settings dropdown.
		 *
		 * @param [array] $options Options.
		 * @return array
		 */
		public function add_role_based_redirection_option( $options ) {

			$options['role-based-redirection'] = __( 'Role Based Redirection', 'user-registration' );

			return $options;
		}


		/**
		 * Add Role Based Redirection settings to the form settings array.
		 *
		 * @param [array] $form_settings Form Settings.
		 * @return array
		 */
		public function add_role_based_redirection_setting( $form_settings ) {

			$form_id = $form_settings['form_id'];

			$form_settings['setting_data'][] = array(
				'type'              => 'html',
				'label'             => __( 'Role Based Redirection', 'user-registration' ),
				'id'                => 'user_registration_form_setting_role_based_redirection',
				'input_class'       => array(),
				'custom_attributes' => array(),
				'html_content'      => $this->ur_get_form_setting_role_based_redirection_html( $form_id ),
				'tip'               => __( 'Set role based redirection pages for this form.', 'user-registration' ),
			);

			return $form_settings;
		}


		/**
		 * Returns the html for role based redirection form settings.
		 *
		 * @param [integer] $form_id Form Id.
		 * @return string
		 */
		public function ur_get_form_setting_role_based_redirection_html( $form_id ) {

			$selected_roles_pages = get_option( 'ur_pro_settings_redirection_after_registration', array() );
			$selected_roles_pages = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_role_based_redirection', $selected_roles_pages );
			$pages                = get_pages();

			$settings  = '<table class="ur_emails widefat" cellspacing="0">';
			$settings .= '<tbody>';

			foreach ( ur_get_default_admin_roles() as $key => $value ) {

				$settings .= '<tr><td class="">';
				$settings .= __( $value, 'user-registration' );
				$settings .= '</td>';
				$settings .= '<td class="">';
				$settings .= '<select name="user_registration_form_setting_role_based_redirection-' . $key . '" id="' . $key . '" >';
				$settings .= '<option value="" >---Select a page---</option>';

				foreach ( $pages as $page ) {

					if ( ! empty( $selected_roles_pages ) && isset( $selected_roles_pages[ $key ] ) && $selected_roles_pages[ $key ] === $page->ID ) {
						$selected = 'selected=selected';
					} else {
						$selected = '';
					}
					$settings .= '<option value="' . $page->ID . '" ' . $selected . ' >' . $page->post_title . '</option>';
				}
				$settings .= '</select>';
				$settings .= '</td>';
				$settings .= '</tr>';
			}

			$settings .= '</tbody>';
			$settings .= '</table>';

			return $settings;
		}


		/**
		 * Save Role Based Redirection Settings.
		 *
		 * @param [array]   $fields Fields.
		 * @param [integer] $form_id Form Id.
		 * @param [array]   $settings Settings.
		 * @return array
		 */
		public function save_role_based_redirection_form_settings( $fields, $form_id, $settings ) {

			$redirection_settings = array();

			foreach ( $settings as $key => $setting ) {
				$key = $setting['name'];
				if ( 0 === strpos( $key, 'user_registration_form_setting_role_based_redirection-' ) ) {
					$role                          = str_replace( 'user_registration_form_setting_role_based_redirection-', '', $key );
					$value                         = empty( $setting['value'] ) ? 0 : intval( $setting['value'] );
					$redirection_settings[ $role ] = $value;
				}
			}

			$fields = array_filter(
				$fields,
				function ( $setting ) {
					return 'user_registration_form_setting_role_based_redirection' !== $setting['id'];
				}
			);

			if ( ! empty( $redirection_settings ) ) {
				update_post_meta( absint( $form_id ), 'user_registration_form_setting_role_based_redirection', $redirection_settings );
			}

			return $fields;
		}
		/**
		 * Slot date booking setting.
		 *
		 * @param array  $fields list of fields.
		 * @param int    $field_id Field ID.
		 * @param string $field_class Field class.
		 *
		 * @since 4.1.0
		 */
		public function ur_pro_date_slot_booking_settings( $fields, $field_id, $field_class ) {
			$slot_booking_fields = array(
				'enable_date_slot_booking' => array(
					'label'    => __( 'Enable Slot Booking', 'user-registration' ),
					'data-id'  => $field_id . '_enable_date_slot_booking',
					'name'     => $field_id . '[enable_date_slot_booking]',
					'class'    => $field_class . ' ur-settings-custom-class ur-enable-date-slot-booking',
					'type'     => 'toggle',
					'required' => false,
					'default'  => 'false',
					'tip'      => __( 'Enable this option to use this field as slot booking.', 'user-registration' ),
				),
			);

			$fields = array_merge( $fields, $slot_booking_fields );

			return $fields;
		}
		/**
		 * Slot time booking setting.
		 *
		 * @param array  $fields list of fields.
		 * @param int    $field_id Field ID.
		 * @param string $field_class Field class.
		 *
		 * @since 4.1.0
		 */
		public function ur_pro_time_slot_booking_settings( $fields, $field_id, $field_class ) {
			$slot_booking_fields = array(
				'enable_time_slot_booking' => array(
					'label'    => __( 'Enable Slot Booking', 'user-registration' ),
					'data-id'  => $field_id . '_enable_time_slot_booking',
					'name'     => $field_id . '[enable_time_slot_booking]',
					'class'    => $field_class . ' ur-settings-custom-class ur-enable-time-slot-booking',
					'type'     => 'toggle',
					'required' => false,
					'default'  => 'false',
					'tip'      => __( 'Enable this option to use this field as slot booking.', 'user-registration' ),
				),
				'target_date_field'        => array(
					'label'       => __( 'Target Date Field', 'user-registration' ),
					'data-id'     => $field_id . '_target_date_field',
					'name'        => $field_id . '[target_date_field]',
					'class'       => $field_class . ' ur-settings-date-target_field',
					'type'        => 'select',
					'required'    => true,
					'default'     => '1',
					'placeholder' => '',
					'options'     => $this->get_target_fields( 'date' ),
					'tip'         => __( 'Please select the target date field, enable slot booking, and choose the target time picker field within that date field.', 'user-registration' ),
				),
			);

			$fields = array_merge( $fields, $slot_booking_fields );

			return $fields;
		}

		/**
		 * Return array of target fields of the form for slot booking.
		 *
		 * @since 4.1.0
		 *
		 * @param string $field_key The field key.
		 *
		 * @return array $target_fields The target field list.
		 */
		public function get_target_fields( $field_key ) {
			$form_id = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;

			$form_settings = ! empty( get_post( $form_id ) ) ? json_decode( get_post( $form_id )->post_content ) : array();
			$target_field  = array( '' => __( '-- Select target field --', 'user-registration' ) );

			foreach ( $form_settings as $section ) {
				foreach ( $section as $row ) {
					foreach ( $row as $setting ) {
						if ( $setting->field_key === $field_key ) {
							$target_field[ $setting->general_setting->field_name ] = $setting->general_setting->label;
						}
					}
				}
			}

			return $target_field;
		}
		/**
		 * Make signature field one time draggable.
		 *
		 * @since 4.2.2
		 * @param array $fields One time draggable fields.
		 * @return array    One time draggable fields.
		 */
		public function ur_signature_field_one_time_drag( $fields ) {
			$fields[] = 'signature';
			return $fields;
		}

		/**
		 * Set/Update the next date to delete the user.
		 *
		 * @since xx.xx.xx
		 * @return void
		 */
		public function add_delete_user_schedular() {

			$enable_delete_usr_sch = get_option( 'user_registration_enable_delete_user_schedular', false );

			if ( ! $enable_delete_usr_sch ) {
				return;
			}

			$duration    = get_option( 'user_registration_delete_user_schedular_duration', '' );
			$user_status = get_option( 'user_registration_delete_user_schedular_status', array() );

			if ( $duration == '' || empty( $user_status ) ) {
				update_option( 'user_registration_delete_user_schedular_next_date', '' );

				return;
			}

			$del_usr_trasnt_duration = get_transient( 'user_registration_delete_user_schedular_duration' );

			// Initially setup next date.
			if ( ! $del_usr_trasnt_duration ) {

				$next_date = strtotime( $duration );
				update_option( 'user_registration_delete_user_schedular_next_date', $next_date );

				set_transient( 'user_registration_delete_user_schedular_duration', $duration );

				return;
			}

			if ( $duration === $del_usr_trasnt_duration ) {
				return;
			}

			delete_transient( 'user_registration_delete_user_schedular_duration' );
			// Updating next value.
			$next_date = strtotime( $duration );
			update_option( 'user_registration_delete_user_schedular_next_date', $next_date );

			set_transient( 'user_registration_delete_user_schedular_duration', $duration, );
		}
	}
}
