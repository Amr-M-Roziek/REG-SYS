<?php
/**
 * User_Registration_Pro_PayPal_Standard
 *
 * @package  User_Registration_Pro_PayPal_Standard
 * @since  4.1.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class User_Registration_Pro_PayPal_Standard
 */
class User_Registration_Pro_PayPal_Standard {

	/**
	 * User_Registration_Pro_PayPal_Standard Constructor
	 */
	public function __construct() {

		// Paypal Settings Hooks.
		if ( is_admin() ) {
			add_action( 'user_registration_after_form_settings', array( $this, 'render_paypal_section' ) );
			add_filter( 'user_registration_form_settings_save', array( $this, 'save_paypal_settings' ), 10, 2 );
			add_filter(
				'user_registration_form_settings_save',
				array(
					$this,
					'save_paypal_conditional_settings',
				),
				10,
				2
			);
		}
		add_filter(
			'user_registration_success_params_paypal_payment_process',
			array(
				$this,
				'paypal_payment_process',
			),
			10,
			4
		);
		add_action( 'init', array( $this, 'paypal_process_ipn' ) );
		add_action( 'init', array( $this, 'handle_paypal_response_after_registration' ) );

		add_action( 'delete_user', array( $this, 'unsubscribe_payment_gateway_on_user_delete' ), 10, 3 );
	}

	/**
	 * Settings for PayPal Standard.
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return array $settings
	 */
	public function get_paypal_settings( $form_id ) {
		$global_settings_url = get_admin_url() . 'admin.php?page=user-registration-settings&tab=payment#user_registration_global_paypal_mode';
		$arguments           = array(
			'form_id'      => $form_id,

			'setting_data' => array(
				array(
					'type'              => 'toggle',
					'label'             => __( 'Enable PayPal Payment', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_enable_paypal_standard',
					'class'             => array( 'ur-enhanced-select' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ),
					'tip'               => __( 'Enable payment for this form with PayPal.', 'user-registration' ),
				),
				array(
					'type'              => 'toggle',
					'label'             => __( 'Override Global Settings', 'user-registration' ),
					'description'       => sprintf( 'Enabling this will override global PayPal settings configured %s', "<a href='" . $global_settings_url . "'>here.</a>" ),
					'required'          => false,
					'id'                => 'user_registration_override_paypal_global_settings',
					'class'             => array( 'ur-enhanced-select' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_override_paypal_global_settings', false ),
				),

				array(
					'type'              => 'select',
					'label'             => __( 'Mode', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_mode',
					'options'           => array(
						'production' => __( 'Production', 'user-registration' ),
						'test'       => __( 'Test/Sandbox', 'user-registration' ),
					),
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_mode', 'test' ),
					'tip'               => __( 'Select a mode to run this form.', 'user-registration' ),
				),
				array(
					'type'              => 'text',
					'label'             => __( 'PayPal Email Address', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_email_address',
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_email_address', get_option( 'admin_email' ) ),
					'tip'               => __( 'Enter you PalPal email address.', 'user-registration' ),
				),
				array(
					'type'              => 'text',
					'label'             => __( 'Cancel URL', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_cancel_url',
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_cancel_url', home_url() ),
					'tip'               => __( 'Redirect URL if the user cancels after redirecting to PayPal.', 'user-registration' ),
				),
				array(
					'type'              => 'text',
					'label'             => __( 'Return URL', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_return_url',
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_return_url', wp_login_url() ),
					'tip'               => __( 'Redirect URL after the payment process.', 'user-registration' ),
				),
				array(
					'type'              => 'text',
					'label'             => __( 'Client ID', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_client_id',
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_client_id', '' ),
					'tip'               => __( 'Enter your PalPal client id.', 'user-registration' ),
				),

				array(
					'type'              => 'text',
					'label'             => __( 'Client Secret', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_client_secret',
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_client_secret', '' ),
					'tip'               => __( 'Enter your PalPal client secret.', 'user-registration' ),
				),
				array(
					'type'              => 'select',
					'label'             => __( 'Payment Type', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_type',
					'options'           => array(
						'products' => __( 'Products and Services', 'user-registration' ),
						'donation' => __( 'Donation', 'user-registration' ),
					),
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_type', 'products' ),
					'tip'               => __( 'Select type of payments you want to receive.', 'user-registration' ),
				),
				array(
					'type'  => 'title',
					'id'    => 'subscribtion_section',
					'title' => __( 'Subscription Settings', 'user-registration' ),
					'class' => array( 'paypal-setting-group' ),
				),
				array(
					'type'              => 'toggle',
					'label'             => __( 'Enable Recurring Subscription Payment', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_enable_paypal_standard_subscription',
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard_subscription', false ),
					'tip'               => __( 'Enable subscription with periodical/recurring payments.', 'user-registration' ),
				),
				array(
					'type'              => 'text',
					'label'             => __( 'Plan Name', 'user-registration' ),
					'description'       => 'Note: We have introduced a new subscription plan field. If you drag and drop this field, the current plan will no longer be active, giving priority to the new field. We also recommend switching to the subscription plan field, as this plan option will no longer be available after a few updates.',
					'required'          => false,
					'id'                => 'user_registration_paypal_plan_name',
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_plan_name', '' ),
					'tip'               => __( 'Enter a plan name for this form.', 'user-registration' ),
				),
				array(
					'type'              => 'number',
					'label'             => __( 'Recurring Period', 'user-registration' ),
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_interval_count',
					'custom_attributes' => array(),
					'class'             => array( 'paypal-setting-group' ),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_interval_count', '' ),
					'tip'               => __( 'Interval between recurring payments.', 'user-registration' ),
				),
				array(
					'type'              => 'select',
					'description'       => '',
					'required'          => false,
					'id'                => 'user_registration_paypal_recurring_period',
					'options'           => array(
						'DAY'   => __( 'Day(s)', 'user-registration' ),
						'WEEK'  => __( 'Week(s)', 'user-registration' ),
						'MONTH' => __( 'Month(s)', 'user-registration' ),
						'YEAR'  => __( 'Year(s)', 'user-registration' ),
					),
					'class'             => array( 'ur-enhanced-select', 'paypal-setting-group' ),
					'custom_attributes' => array(),
					'default'           => ur_get_single_post_meta( $form_id, 'user_registration_paypal_recurring_period', 'year' ),
				),
			),
		);
		$arguments                 = apply_filters( 'user_registration_get_paypal_settings', $arguments );
		$arguments['setting_data'] = apply_filters( 'user_registration_settings_text_format', $arguments['setting_data'] );

		return $arguments['setting_data'];
	}

	/**
	 * Render PayPal Section
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return void
	 */
	public function render_paypal_section( $form_id = 0 ) {
		$integration_settings = get_post_meta( $form_id, 'user_registration_paypal_conditional_integration', true );
		// $has_membership_field = check_membership_field_in_form( $form_id );
		// if ( $has_membership_field ) {
		// 	return;
		// }
		echo '<div id="paypal-standard-settings" data-field-group="payments" ><h3>' . esc_html__( 'PayPal Standard', 'user-registration' ) . '</h3>';

		$arguments = $this->get_paypal_settings( $form_id );

		foreach ( $arguments as $args ) {
			user_registration_form_field( $args['id'], $args );
		}
		if ( ! empty( $integration_settings ) && is_array( $integration_settings ) ) {

			foreach ( $integration_settings as $connection ) {
				printf( '%s', $this->output_paypal_conditional_logic( $form_id, $connection ) );
			}
		} else {
			printf( '%s', $this->output_paypal_conditional_logic( $form_id, array() ) );
		}
		echo '</div>';
	}

	/**
	 * Save PayPal Payment Settings
	 *
	 * @param array $settings Settings.
	 * @param int   $form_id Form Id.
	 *
	 * @return array $settings
	 */
	public function save_paypal_settings( $settings, $form_id = 0 ) {

		$payment_setting = $this->get_paypal_settings( $form_id );
		$settings        = array_merge( $settings, $payment_setting );

		return $settings;
	}

	/**
	 * Save Paypal conditional settings.
	 *
	 * @param array $settings Settings.
	 * @param int   $form_id Form Id.
	 *
	 * @return array $settings
	 */
	public function save_paypal_conditional_settings( $settings, $form_id = 0 ) {
		$form_id                     = absint( $_POST['data']['form_id'] );
		$paypal_conditional_settings = isset( $_POST['data']['ur_paypal_conditional_integration'] ) ? wp_unslash( $_POST['data']['ur_paypal_conditional_integration'] ) : array();
		update_post_meta( $form_id, 'user_registration_paypal_conditional_integration', $paypal_conditional_settings );

		return $settings;
	}

	/**
	 * Process entry for paypal payment.
	 *
	 * @param array $success_params Success Params.
	 * @param array $valid_form_data Form Data.
	 * @param int   $form_id Form Id.
	 * @param int   $user_id User Id.
	 */
	public function paypal_payment_process( $success_params, $valid_form_data, $form_id, $user_id ) {

		$saved_currency = get_option( 'user_registration_payment_currency', 'USD' );
		if ( ! in_array( $saved_currency, paypal_supported_currencies_list() ) ) {
			wp_delete_user( absint( $user_id ) );
			wp_send_json_error(
				array(
					'message' => __( 'CURRENCY_NOT_SUPPORTED Currency code ' . $saved_currency . ' is not currently supported. Please contact site administrator.', 'user-registration' ),
				)
			);
		}
		// Get data from form settings.
		$currency                        = get_option( 'user_registration_payment_currency', 'USD' );
		$override_global_paypal_settings = ur_get_single_post_meta( $form_id, 'user_registration_override_paypal_global_settings', false );
		$receiver_email                  = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_email_address', get_option( 'admin_email' ) ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_email_address', get_option( 'admin_email' ) );
		$payment_mode                    = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_mode', 'test' ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_mode', 'test' );
		$cancel_url                      = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_cancel_url', home_url() ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_cancel_url', home_url() );
		$return_url                      = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_return_url', wp_login_url() ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_return_url', wp_login_url() );

		$payment_type    = ur_get_single_post_meta( $form_id, 'user_registration_paypal_type', 'products' );
		$amount          = user_registration_sanitize_amount( 0, $currency );
		$items           = array();
		$subscribed_plan = '';

		$paypal_subscription = ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard_subscription', 'no' );

		$quantities     = array();
		$coupon_details = array();
		foreach ( $valid_form_data as $field_data ) {
			if ( isset( $field_data->extra_params['field_key'] ) && ( 'quantity_field' === $field_data->extra_params['field_key'] ) ) {
				$target_name                = $field_data->extra_params['target_field'];
				$quantities[ $target_name ] = $field_data->value;
			}
			if ( isset( $field_data->extra_params['field_key'] ) && ( 'coupon' === $field_data->extra_params['field_key'] ) && ur_check_module_activation( 'coupon' ) ) {
				// $coupon_target_field = preg_filter( "/_(\d)+$/", "", $field_data->extra_params['target_field'] );
				$coupon_details = ur_get_coupon_details( $field_data->value );

				if ( ! empty( $coupon_details ) && ( 'form' !== $coupon_details['coupon_for'] || ! in_array( $form_id, json_decode( $coupon_details['coupon_form'], true ) ) ) ) {
					$coupon_details = array();
				}
			}
		}

		foreach ( $valid_form_data as $form_data ) {
			$payment_slider   = check_is_range_payment_slider( $form_data->field_name, $form_id );
			$urcl_hide_fields = isset( $_POST['urcl_hide_fields'] ) ? (array) json_decode( stripslashes( $_POST['urcl_hide_fields'] ), true ) : array();
			if ( ! in_array( $form_data->field_name, $urcl_hide_fields, true ) ) {
				if ( isset( $form_data->extra_params['field_key'] ) && ( 'single_item' === $form_data->extra_params['field_key'] || ( 'range' === $form_data->extra_params['field_key'] && $payment_slider ) || 'multiple_choice' === $form_data->extra_params['field_key'] || 'subscription_plan' === $form_data->extra_params['field_key'] ) ) {
					$item_amount = json_decode( $form_data->value );

					if ( 'subscription_plan' === $form_data->extra_params['field_key'] ) {
						$subscription_data = ! is_array( $item_amount ) ? explode( ':', $item_amount ) : $item_amount;
						if ( isset( $subscription_data[1] ) ) {
							$selected_options                     = array();
							$subscribed_plan                      = isset( $subscription_data[0] ) ? $subscription_data[0] : '';
							$subscription_amount                  = isset( $subscription_data[1] ) ? $subscription_data[1] : 0;
							$selected_options[ $subscribed_plan ] = $subscription_amount;
							$form_data->value                     = $selected_options;
							$item_amount                          = $subscription_amount;

						}
					}
					if ( is_array( $item_amount ) ) {
						$multiple_choice_amount = 0;
						$selected_options       = array();
						$multiple_choice_data   = ur_get_field_data_by_field_name( $form_id, $form_data->field_name );
						$multiple_choice_option = $multiple_choice_data['general_setting']->options;

						foreach ( $item_amount as $key => $option_choice ) {
							$options_data                      = explode( ':', $option_choice );
							$option_label                      = isset( $options_data[0] ) ? $options_data[0] : '';
							$option_amount                     = isset( $options_data[1] ) ? $options_data[1] : $option_choice;
							$multiple_choice_amount            = $multiple_choice_amount + $option_amount;
							$selected_options[ $option_label ] = $option_amount;
						}
						$form_data->value = $selected_options;
						$item_amount      = $multiple_choice_amount;
					}

					// if ( ! empty( $coupon_details ) && 'single_item' === $coupon_target_field && 'single_item' === $form_data->extra_params['field_key'] ) {
					// $item_amount = $this->calculate_coupon_discount( $coupon_details, $item_amount );
					// }
					if ( array_key_exists( $form_data->field_name, $quantities ) ) {
						$quantity = 0;
						if ( absint( $quantities[ $form_data->field_name ] ) > - 1 ) {
							$quantity = absint( $quantities[ $form_data->field_name ] );
						}

						$form_data->quantity = $quantity;
						$item_amount         = $item_amount * $quantity;
					}

					$form_data->amount = $item_amount;
					$items[]           = $form_data;
					$amount            = $amount + user_registration_sanitize_amount( $item_amount, $currency );
					$has_payment_field = true;
				}
			}
		}

		// Return if form doesnot contain any payment fields.
		if ( ! isset( $has_payment_field ) ) {
			wp_delete_user( absint( $user_id ) );
			wp_send_json_error(
				array(
					'message' => __( 'PayPal Standard Payment stopped, missing payment fields', 'user-registration' ),
				)
			);
		}

		// Return if invalid amount.
		if ( ( empty( $amount ) || user_registration_sanitize_amount( 0, $currency ) == $amount ) ) {
			wp_delete_user( absint( $user_id ) );
			wp_send_json_error(
				array(
					'message' => __( 'PayPal Standard Payment stopped, Invalid/Empty amount', 'user-registration' ),
				)
			);
		} elseif ( ( empty( $amount ) || user_registration_sanitize_amount( 0, $currency ) == $amount ) ) {
			return $success_params;
		}

		$post_data               = ur_get_post_content( $form_id );
		$subscription_plan_field = ur_get_form_data_by_key( $post_data, 'subscription_plan' );
		$plan_name               = ur_get_single_post_meta( $form_id, 'user_registration_paypal_plan_name', '' );
		$recurring_period        = ur_get_single_post_meta( $form_id, 'user_registration_paypal_recurring_period' );
		$interval_count          = ur_get_single_post_meta( $form_id, 'user_registration_paypal_interval_count', '1' );

		$subscription_expiry_date = '';
		if ( ! empty( $subscription_plan_field ) ) {
			if ( isset( $subscription_plan_field['subscription_plan']->general_setting->options ) ) {
				$plan_lists = $subscription_plan_field['subscription_plan']->general_setting->options;
				if ( ! empty( $plan_lists ) ) {
					foreach ( $plan_lists as $plan ) {
						if ( $subscribed_plan === $plan->label ) {
							$interval_count              = ! empty( $plan->interval_count ) ? $plan->interval_count : 1;
							$plan_name                   = $plan->label;
							$recurring_period            = $plan->recurring_period;
							$enabled_subscription_expiry = isset( $plan->subscription_expiry_enable ) ? $plan->subscription_expiry_enable : '';
							$subscription_expiry_date    = isset( $plan->subscription_expiry_date ) ? $plan->subscription_expiry_date : '';
						}
					}
				}
			}
		}
		if ( ! empty( $coupon_details ) ) {
			update_user_meta( $user_id, 'ur_coupon_discount_type', $coupon_details['coupon_discount_type'] );
			update_user_meta( $user_id, 'ur_coupon_discount', $coupon_details['coupon_discount'] );
			update_user_meta( $user_id, 'ur_coupon_code', $coupon_details['coupon_code'] );
		}
		if ( '' !== $subscription_expiry_date ) {
			$subscription_expiry_date = date( 'F j, Y H:i:s', strtotime( $subscription_expiry_date ) );
		}

		if ( ur_string_to_bool( get_option( 'user_registration_enable_payment_pending_email', true ) ) ) {
			User_Registration_Pro_Frontend::send_pending_email( $user_id );
		}

		// Build the return URL with hash.
		$query_args = 'form_id=' . absint( $form_id ) . '&user_id=' . absint( $user_id ) . '&hash=' . wp_hash( $form_id . ',' . $user_id );

		$return_url = esc_url_raw(
			add_query_arg(
				array(
					'user_registration_return' => base64_encode( $query_args ),
				),
				apply_filters( 'user_registration_paypal_return_url', $return_url, $valid_form_data )
			)
		);

		$redirect   = ( 'production' === $payment_mode ) ? 'https://www.paypal.com/cgi-bin/webscr/?' : 'https://www.sandbox.paypal.com/cgi-bin/webscr/?';
		$cancel_url = ! empty( $cancel_url ) ? esc_url_raw( $cancel_url ) : home_url();

		// Subscription.
		$paypal_recurring_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard_subscription', false ) );
		if ( $paypal_recurring_enabled ) {
			$transaction = '_xclick-subscriptions';
		} elseif ( 'donation' === $payment_type ) {
			$transaction = '_donations';
		} else {
			$transaction = '_cart';
		}
		// Setup PayPal arguments.
		$paypal_args = array(
			'bn'            => 'UserRegistration_SP',
			'business'      => sanitize_email( $receiver_email ),
			'cancel_return' => $cancel_url,
			'cbt'           => get_bloginfo( 'name' ),
			'charset'       => get_bloginfo( 'charset' ),
			'cmd'           => $transaction,
			'currency_code' => strtoupper( $currency ),
			'custom'        => absint( $form_id ),
			'invoice'       => absint( $user_id ),
			'notify_url'    => add_query_arg( 'user-registration-listener', 'IPN', home_url( 'index.php' ) ),
			'return'        => $return_url,
			'rm'            => '2',
			'tax'           => 0,
			'upload'        => '1',
			'sra'           => '1',
			'src'           => '1',
			'no_note'       => '1',
			'no_shipping'   => '1',
			'shipping'      => '0',
		);

		// Add cart items.
		if ( '_cart' === $transaction ) {

			// Product/service.
			$i                  = 1;
			$total_discount     = isset( $coupon_details['coupon_discount'] ) ? $coupon_details['coupon_discount'] : 0;
			$remaining_discount = $total_discount;
			$whole_discount     = 0;
			$discount_amount    = 0;
			if ( ! empty( $coupon_details ) ) {

				foreach ( $items as $key => &$item ) {
					if ( 'fixed' === $coupon_details['coupon_discount_type'] ) {
						if ( $remaining_discount >= $item->amount ) {
							$remaining_discount -= $item->amount;
							$item->amount        = 0.00;
						} else {
							$item->amount      -= $remaining_discount;
							$remaining_discount = 0.00;
						}
						if ( $item->amount == 0 ) {
							++$whole_discount;
						}
					} else {

						$item->amount = $item->amount - ( $item->amount * $coupon_details['coupon_discount'] / 100 );
					}
					$discount_amount += $item->amount;

				}
			}

			if ( $whole_discount == count( $items ) ) {
				wp_delete_user( absint( $user_id ) );
				wp_send_json_error(
					array(
						'message' => __( 'PayPal Standard Payment stopped, Total amount cannot be less than or equals to Zero.', 'user-registration' ),
					)
				);
			}

			unset( $item );
			foreach ( $items as $key => $item ) {
				$item_amount = user_registration_sanitize_amount( $item->amount, $currency );
				// if ( ! empty( $coupon_details ) && 'total_field' === $coupon_target_field ) {

				$item_name = isset( $item->extra_params['label'] ) ? $item->extra_params['label'] : '';

				$paypal_args[ 'item_name_' . $i ] = stripslashes_deep( html_entity_decode( $item_name, ENT_COMPAT, 'UTF-8' ) );
				$paypal_args[ 'amount_' . $i ]    = $item_amount;
				++$i;
			}
		} elseif ( '_donations' === $transaction ) {

			// Combine a donation name from all payment fields names.
			$item_names = array();

			foreach ( $items as $item ) {

				$item_name    = isset( $item->extra_params['label'] ) ? $item->extra_params['label'] : '';
				$item_names[] = stripslashes_deep( html_entity_decode( $item_name, ENT_COMPAT, 'UTF-8' ) );
			}

			$paypal_args['item_name'] = implode( '; ', $item_names );
			if ( ! empty( $coupon_details ) ) {
				$amount = $this->calculate_coupon_discount( $coupon_details, $amount );
			}
			$paypal_args['amount'] = $amount;
		} else {

			if ( ! empty( $coupon_details ) ) {
				$discount_amount   = $this->calculate_coupon_discount( $coupon_details, $amount );
				$paypal_args['t2'] = ! empty( $recurring_period ) ? strtoupper( substr( $recurring_period, 0, 1 ) ) : '';
				$paypal_args['p2'] = ! empty( $interval_count ) ? $interval_count : 1;
				$paypal_args['a2'] = $discount_amount;
			}

			if ( ! empty( $coupon_details ) && $discount_amount <= 0 ) {
				wp_delete_user( absint( $user_id ) );
				wp_send_json_error(
					array(
						'message' => __( 'PayPal Standard Payment stopped, Total amount after discount cannot be less than or equals to Zero.', 'user-registration' ),
					)
				);
			}
			$customer_email           = isset( $valid_form_data['user_email']->value ) ? $valid_form_data['user_email']->value : '';
			$paypal_args['email']     = $customer_email;
			$paypal_args['a3']        = $amount;
			$paypal_args['item_name'] = ! empty( $plan_name ) ? $plan_name : '';
			$paypal_args['t3']        = ! empty( $recurring_period ) ? strtoupper( substr( $recurring_period, 0, 1 ) ) : '';
			$paypal_args['p3']        = ! empty( $interval_count ) ? $interval_count : 1;

		}

		if ( $amount <= 0 ) {
			wp_delete_user( absint( $user_id ) );
			wp_send_json_error(
				array(
					'message' => __( 'PayPal Standard Payment stopped, Total amount cannot be less than or equals to Zero.', 'user-registration' ),
				)
			);
		}
		$total_amount = ! empty( $coupon_details ) ? $discount_amount : $amount;

		// Initially update payment status to pending.
		update_user_meta( $user_id, 'ur_payment_status', 'pending' );
		update_user_meta( $user_id, 'ur_payment_total_amount', $total_amount );
		update_user_meta( $user_id, 'ur_payment_product_amount', $amount );
		update_user_meta( $user_id, 'ur_payment_recipient', $receiver_email );
		update_user_meta( $user_id, 'ur_payment_currency', $currency );
		update_user_meta( $user_id, 'ur_payment_method', 'paypal_standard' );
		update_user_meta( $user_id, 'ur_payment_type', $payment_type );
		update_user_meta( $user_id, 'ur_payment_mode', $payment_mode );
		update_user_meta( $user_id, 'ur_cart_items', json_encode( $items ) );
		update_user_meta( $user_id, 'ur_paypal_subscription_enabled', $paypal_subscription );
		update_user_meta( $user_id, 'ur_paypal_subscription_plan_name', $plan_name );
		update_user_meta( $user_id, 'ur_paypal_recurring_period', $recurring_period );
		update_user_meta( $user_id, 'ur_paypal_interval_count', $interval_count );
		update_user_meta( $user_id, 'ur_paypal_subscription_status', 'active' );
		update_user_meta( $user_id, 'ur_payment_subscription_expiry', $subscription_expiry_date );

		// Last change to filter args.
		$paypal_args = apply_filters( 'user_registration_paypal_redirect_args', $paypal_args, $valid_form_data, $user_id );

		// Build query.
		$redirect .= http_build_query( $paypal_args );

		$redirect = str_replace( '&amp;', '&', $redirect );
		// redirect to paypal based on conditional rules when both payment method is enabled.
		$success_params['paypal_redirect'] = $redirect;
		$success_params['message']         = get_option( 'user_registration_payment_before_registration_pending_message', esc_html( 'User Registered. Payment Processing...' ) );

		return $success_params;
	}

	/**
	 * @param array $coupon_details details regarding applied coupon
	 * @param mixed $amount amount to be calculated with
	 *
	 * @return float|int|mixed
	 */
	public function calculate_coupon_discount( $coupon_details, $amount ) {
		if ( 'fixed' === $coupon_details['coupon_discount_type'] ) {
			$amount = $amount - $coupon_details['coupon_discount'];
		} elseif ( 'percent' === $coupon_details['coupon_discount_type'] ) {

			$amount = $amount - ( $amount * $coupon_details['coupon_discount'] / 100 );
		}

		return $amount;
	}

	/**
	 * Process PayPal IPN.
	 *
	 * Adapted from EDD and the PHP PayPal IPN Class.
	 *
	 * @link https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/gateways/paypal-standard.php
	 * @link https://github.com/WadeShuler/PHP-PayPal-IPN/blob/master/src/IpnListener.php
	 * @since 1.0.0
	 */
	public function paypal_process_ipn() {

		// Verify the call back query and its method.
		if ( ! isset( $_GET['user-registration-listener'] ) || 'IPN' !== $_GET['user-registration-listener'] ) {
			return;
		}

		$data = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Check if $post_data_array has been populated.
		if ( ! is_array( $data ) || empty( $data ) || empty( $data['invoice'] ) ) {
			return;
		}

		$error          = '';
		$payment_id     = absint( $data['invoice'] );
		$form_id        = get_user_meta( $payment_id, 'ur_form_id', true );
		$payment_status = strtolower( $data['payment_status'] );

		// Get payment (entry).
		if ( empty( $data['invoice'] ) ) {

			$logger = ur_get_logger();
			$logger->notice( 'Data Invoice Not Found. Payment ID:' . $payment_id, array( 'source' => 'ur-paypal-standard' ) );

			return;
		}

		// Return if payment or form doesn't exist.
		if ( empty( $payment_id ) || empty( $form_id ) ) {

			$logger = ur_get_logger();
			$logger->notice( 'Payment ID Not Found.', array( 'source' => 'ur-paypal-standard' ) );

			return;
		}

		// Verify IPN with PayPal unless specifically disabled.
		$remote_post_args = array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'headers'     => array(
				'host'         => 'www.paypal.com',
				'connection'   => 'close',
				'content-type' => 'application/x-www-form-urlencoded',
				'post'         => '/cgi-bin/webscr HTTP/1.1',
				'user-agent'   => 'User Registration IPN Verification',
			),
			'body'        => $data,
		);

		// Get settings from form_id.
		$currency                        = get_option( 'user_registration_payment_currency', 'USD' );
		$override_global_paypal_settings = ur_get_single_post_meta( $form_id, 'user_registration_override_paypal_global_settings', false );
		$receiver_email                  = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_email_address', get_option( 'admin_email' ) ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_email_address', get_option( 'admin_email' ) );
		$payment_mode                    = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_mode', 'test' ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_mode', 'test' );
		$cancel_url                      = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_cancel_url', home_url() ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_cancel_url', home_url() );
		$return_url                      = ! $override_global_paypal_settings ? get_option( 'user_registration_global_paypal_return_url', wp_login_url() ) : ur_get_single_post_meta( $form_id, 'user_registration_paypal_return_url', wp_login_url() );

		$payment_type = ur_get_single_post_meta( $form_id, 'user_registration_paypal_type' );
		$amount       = get_user_meta( $payment_id, 'ur_payment_total_amount', true );

		if ( ! $this->validate_ipn( $payment_mode ) ) {
			return;
		}
		$logger = ur_get_logger();
		$logger->notice( 'Before transaction type:', array( 'source' => 'ur-paypal-standard' ) );
		$logger->notice( print_r( $data, true ), array( 'source' => 'ur-paypal-standard' ) );
		// Verify transaction type.
		if ( 'web_accept' !== $data['txn_type'] && 'cart' !== $data['txn_type'] && 'subscr_signup' !== $data['txn_type'] && 'subscr_payment' !== $data['txn_type'] ) {
			return;
		}
		$logger = ur_get_logger();
		$logger->notice( 'After transaction type:', array( 'source' => 'ur-paypal-standard' ) );
		$logger->notice( print_r( $error, true ), array( 'source' => 'ur-paypal-standard' ) );

		// Verify receiver's email address.
		if ( empty( $receiver_email ) || ! is_email( $receiver_email ) || strtolower( $data['business'] ) !== strtolower( trim( $receiver_email ) ) ) {
			$error = esc_html__( 'Payment failed: recipient emails do not match', 'user-registration' );
		} elseif ( empty( $currency ) && strtolower( $data['mc_currency'] ) !== strtolower( $currency ) ) {
			// Verify currency.
			$error = esc_html__( 'Payment failed: currency formats do not match', 'user-registration' );
		} elseif ( empty( $amount ) || number_format( (float) $data['mc_gross'] ) !== number_format( (float) $amount ) ) {
			// Verify amount.
			$error = esc_html__( 'Payment failed: payment amounts do not match', 'user-registration' );
		}
		$logger = ur_get_logger();
		$logger->notice( 'Before Error:', array( 'source' => 'ur-paypal-standard' ) );
		$logger->notice( print_r( $error, true ), array( 'source' => 'ur-paypal-standard' ) );
		// Failed Status.
		if ( ! empty( $error ) ) {

			$logger = ur_get_logger();
			$logger->notice( $error . ' Payment ID:' . $payment_id, array( 'source' => 'ur-paypal-standard' ) );

			update_user_meta( $payment_id, 'ur_payment_status', 'failed' );

			return;
		}

		$logger = ur_get_logger();
		$logger->notice( 'Data:', array( 'source' => 'ur-paypal-standard' ) );
		$logger->notice( print_r( $payment_status, true ), array( 'source' => 'ur-paypal-standard' ) );
		$logger->notice( print_r( $data, true ), array( 'source' => 'ur-paypal-standard' ) );
		// Update usermeta as completed status.
		if ( 'completed' === $payment_status || 'production' !== $payment_mode ) {

			$logger = ur_get_logger();
			$logger->notice( 'Successfully completed the payment. Payment ID:' . $payment_id . ' ', array( 'source' => 'ur-paypal-standard' ) );

			update_user_meta( $payment_id, 'ur_payment_status', 'completed' );
			update_user_meta( $payment_id, 'ur_payment_transaction', $data['txn_id'] );
			update_user_meta( $payment_id, 'ur_payment_note', '' );

			/**
			 * Invoice code started.
			 */

			$payment_invoice = array();

			$invoice['invoice_date'] = date( 'Y-m-d H:i:s' );
			$invoice['invoice_no']   = isset( $data['txn_id'] ) ? $data['txn_id'] : '';

			$invoice_item = get_user_meta( $payment_id, 'ur_cart_items' );

			$invoice['invoice_item']     = $invoice_item;
			$invoice['invoice_currency'] = $currency;
			$invoice['invoice_amount']   = get_user_meta( $payment_id, 'ur_payment_total_amount', true );
			$invoice['invoice_status']   = $payment_status;

			array_push( $payment_invoice, $invoice );
			update_user_meta( $payment_id, 'ur_payment_invoices', $payment_invoice );

			/**
			 * Invoice code ended.
			 */

		} elseif ( 'refunded' === $payment_status ) {
			// Refunded payment.

			/* translators: %s - Paypal payment transaction ID. */
			$note = sprintf( esc_html__( 'Payment refunded: PayPal refund transaction ID: %s', 'user-registration' ), $data['txn_id'] );

			$logger = ur_get_logger();
			$logger->notice( 'Payment refunded. Payment ID:' . $payment_id . ' ', array( 'source' => 'ur-paypal-standard' ) );

			update_user_meta( $payment_id, 'ur_payment_status', 'refunded' );
			update_user_meta( $payment_id, 'ur_payment_note', $note );

			// Pending payment.
		} elseif ( 'pending' === $payment_status && isset( $data['pending_reason'] ) ) {
			$note = '';
			switch ( strtolower( $data['pending_reason'] ) ) {
				case 'echeck':
					$note = esc_html__( 'Payment made via eCheck and will clear automatically in 5-8 days', 'user-registration' );
					break;
				case 'address':
					$note = esc_html__( 'Payment requires a confirmed customer address and must be accepted manually through PayPal', 'user-registration' );
					break;
				case 'intl':
					$note = esc_html__( 'Payment must be accepted manually through PayPal due to international account regulations', 'user-registration' );
					break;
				case 'multi-currency':
					$note = esc_html__( 'Payment received in non-shop currency and must be accepted manually through PayPal', 'user-registration' );
					break;
				case 'paymentreview':
				case 'regulatory_review':
					$note = esc_html__( 'Payment is being reviewed by PayPal staff as high-risk or in possible violation of government regulations', 'user-registration' );
					break;
				case 'unilateral':
					$note = esc_html__( 'Payment was sent to non-confirmed or non-registered email address.', 'user-registration' );
					break;
				case 'upgrade':
					$note = esc_html__( 'PayPal account must be upgraded before this payment can be accepted', 'user-registration' );
					break;
				case 'verify':
					$note = esc_html__( 'PayPal account is not verified. Verify account in order to accept this payment', 'user-registration' );
					break;
				case 'other':
					$note = esc_html__( 'Payment is pending for unknown reasons. Contact PayPal support for assistance', 'user-registration' );
					break;
				default:
					$note = esc_html( $data['pending_reason'] );
					break;

			}
			update_user_meta( $payment_id, 'ur_payment_status', 'pending' );
			update_user_meta( $payment_id, 'ur_payment_note', $note );
		}

		// Completed PayPal IPN call.
		do_action( 'user_registration_paypal_standard_process_complete', $payment_id, $data );
		exit;
	}

	/**
	 * Check PayPal IPN validity.
	 *
	 * @param mixed $payment_meta Payment Meta.
	 *
	 * @since 1.2.1
	 */
	public function validate_ipn( $payment_mode ) {
		$logger = ur_get_logger();
		$logger->notice( 'Checking IPN response is valid' );
		// Get received values from post data.
		$validate_ipn        = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$validate_ipn['cmd'] = '_notify-validate';
		// Send back post vars to paypal.
		$params = array(
			'body'        => $validate_ipn,
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'User Registration IPN Verification',
		);

		$remote_post_url = ( ! empty( $payment_mode ) && 'production' === $payment_mode ) ? 'https://ipnpb.paypal.com/cgi-bin/webscr' : 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
		// Post back to get a response.
		$response = wp_safe_remote_post( $remote_post_url, $params );
		$logger   = ur_get_logger();
		$logger->notice( 'IPN Response: ' . print_r( $response, true ) );
		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED' ) ) {
			$logger = ur_get_logger();
			$logger->notice( 'Received valid response from PayPal IPN' );

			return true;
		}
		$logger = ur_get_logger();
		$logger->notice( 'Received invalid response from PayPal IPN' );
		if ( is_wp_error( $response ) ) {
			$logger = ur_get_logger();
			$logger->notice( 'Error response: ' . $response->get_error_message() );
		}

		return false;
	}

	/**
	 * Validate conditional logic for paypal.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $valid_form_data Form Data.
	 */
	public function paypal_conditional_logic( $form_id, $valid_form_data ) {
		$paypal_integration = get_post_meta( $form_id, 'user_registration_paypal_conditional_integration', true );
		$is_valid           = true;

		if ( count( $paypal_integration ) > 0 ) {
			foreach ( $paypal_integration as $paypal_conditional_key => $paypal_conditonal_data ) {

				if ( isset( $paypal_conditonal_data['enable_conditional_logic'] ) && ur_string_to_bool( $paypal_conditonal_data['enable_conditional_logic'] ) ) {

					switch ( $paypal_conditonal_data['conditional_logic_data']['conditional_operator'] ) {
						case 'is':
							if ( $valid_form_data[ $paypal_conditonal_data['conditional_logic_data']['conditional_field'] ]->value === $paypal_conditonal_data['conditional_logic_data']['conditional_value'] ) {
								$is_valid = true;
							} else {
								$is_valid = false;
							}
							break;
						case 'is_not':
							if ( $valid_form_data[ $paypal_conditonal_data['conditional_logic_data']['conditional_field'] ]->value !== $paypal_conditonal_data['conditional_logic_data']['conditional_value'] ) {
								$is_valid = true;
							} else {
								$is_valid = false;
							}
							break;
						default:
							break;
					}
				}
			}
		}

		return $is_valid;
	}

	/**
	 * Validate Conditional Logic for Stripe.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $valid_form_data Form Data.
	 */
	public function stripe_conditional_logic( $form_id, $valid_form_data ) {
		$stripe_integration = get_post_meta( $form_id, 'user_registration_stripe_conditional_integration', true );
		$is_valid           = true;

		if ( count( $stripe_integration ) > 0 ) {
			foreach ( $stripe_integration as $stripe_conditional_key => $stripe_conditonal_data ) {

				if ( isset( $stripe_conditonal_data['enable_conditional_logic'] ) && ur_string_to_bool( $stripe_conditonal_data['enable_conditional_logic'] ) ) {

					switch ( $stripe_conditonal_data['conditional_logic_data']['conditional_operator'] ) {
						case 'is':
							if ( $valid_form_data[ $stripe_conditonal_data['conditional_logic_data']['conditional_field'] ]->value === $stripe_conditonal_data['conditional_logic_data']['conditional_value'] ) {
								$is_valid = true;
							} else {
								$is_valid = false;
							}
							break;
						case 'is_not':
							if ( $valid_form_data[ $stripe_conditonal_data['conditional_logic_data']['conditional_field'] ]->value !== $stripe_conditonal_data['conditional_logic_data']['conditional_value'] ) {
								$is_valid = true;
							} else {
								$is_valid = false;
							}
							break;
						default:
							break;
					}
				}
			}
		}
		$urcl_hide_fields = isset( $_POST['urcl_hide_fields'] ) ? (array) json_decode( stripslashes( $_POST['urcl_hide_fields'] ), true ) : array(); //phpcs:ignore;

		if ( in_array( 'stripe_gateway', $urcl_hide_fields, true ) ) {
			$is_valid = false;
		}

		return $is_valid;
	}

	/**
	 * handle paypal process after registration
	 */
	public function handle_paypal_response_after_registration() {
		// verify its callback query
		if ( ! isset( $_GET['user_registration_return'] ) ) {
			return;
		}
		$return  = base64_decode( $_GET['user_registration_return'] );
		$return  = explode( '&', $return );
		$return  = explode( '=', $return[1] );
		$user_id = isset( $return[1] ) ? $return[1] : '';
		$form_id = ur_get_form_id_by_userid( $user_id );
		// Check if PayPal payment is enabled or not.
		$paypal_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ) );
		// Check if Stripe payment is enabled or not.
		$stripe_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_stripe', false ) );
		// Check if Authorize.net payment is enabled or not.
		$anet_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_authorize_net', false ) );
		// Filter to check if other payments are enabled.
		$payment_is_enabled = apply_filters( 'user_registration_enable_payment', $paypal_is_enabled ? $paypal_is_enabled : ( $stripe_is_enabled || $anet_is_enabled ) );

		if ( ! $payment_is_enabled ) {
			return;
		}
		if ( ! $paypal_is_enabled && $payment_is_enabled ) {
			// PayPal is not enalbed but other payment methods are enabled.
			return;
		}

		if ( 'pending' === get_user_meta( $user_id, 'ur_payment_status', true ) ) {
			update_user_meta( $user_id, 'ur_payment_status', 'completed' );
		}

		/**
		 * Invoice code started.
		 */

		$payment_invoice = array();

		$invoice['invoice_date'] = date( 'Y-m-d H:i:s' );
		$invoice['invoice_no']   = get_user_meta( $user_id, 'ur_payment_transaction', true );

		$invoice_item = get_user_meta( $user_id, 'ur_cart_items' );

		$invoice['invoice_item']     = $invoice_item;
		$invoice['invoice_currency'] = get_option( 'user_registration_payment_currency', 'USD' );
		$invoice['invoice_amount']   = get_user_meta( $user_id, 'ur_payment_total_amount', true );
		$invoice['invoice_status']   = get_user_meta( $user_id, 'ur_payment_status', true );

		array_push( $payment_invoice, $invoice );
		update_user_meta( $user_id, 'ur_payment_invoices', $payment_invoice );

		/**
		 * Invoice code ended.
		 */

		// Update usermeta as completed status.
		if ( 'completed' === get_user_meta( $user_id, 'ur_payment_status', true ) ) {
			$logger = ur_get_logger();
			$logger->notice( 'Successfully completed the payment. Payment ID:' . $user_id . ' ', array( 'source' => 'ur-paypal-standard' ) );
			// Send email after successful payment.
			if ( ur_string_to_bool( get_option( 'user_registration_enable_payment_email', true ) ) ) {
				User_Registration_Pro_Frontend::send_success_email( $user_id );
				User_Registration_Pro_Frontend::send_success_admin_email( $user_id );
			}
		}
		$login_option = ur_get_user_login_option( $user_id );

		if ( 'auto_login' === $login_option ) {
			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user_id );
			$success_params['auto_login'] = true;
		}
	}

	/**
	 *  output conditional logic
	 *
	 * @param array   $connection
	 * @param integer $form_id
	 */
	public function output_paypal_conditional_logic( $form_id, $connection = array() ) {
		$output = user_registration_pro_render_conditional_logic( $connection, 'paypal', $form_id );

		return $output;
	}

	/**
	 * Unsubscribe the payment gateway on the user deletion.
	 *
	 * @param [type] $user_id User Id.
	 * @param [type] $reassign  Reassign to another user ( admin ).
	 * @param [type] $user User Data.
	 */
	public function unsubscribe_payment_gateway_on_user_delete( $user_id, $reassign, $user ) {

		// Return if reassign is set.
		if ( null !== $reassign ) {
			return;
		}

		$subscription_id = get_user_meta( $user_id, 'ur_payment_subscription', true );

		if ( '' === $subscription_id ) {
			ur_get_logger()->debug( 'Missing subscription id.' );

			return;
		}

		$form_id = ur_get_form_id_by_userid( $user_id );

		$override_global = ur_get_single_post_meta( $form_id, 'user_registration_override_paypal_global_settings', false );

		$client_id     = $override_global ? ur_get_single_post_meta( $form_id, 'user_registration_paypal_client_id', '' ) : get_option( 'user_registration_global_paypal_client_id' );
		$client_secret = $override_global ? ur_get_single_post_meta( $form_id, 'user_registration_paypal_client_secret', '' ) : get_option( 'user_registration_global_paypal_client_secret' );
		$payment_mode  = get_user_meta( $user_id, 'ur_payment_mode', true );
		$url           = ( 'production' === $payment_mode ) ? 'https://api-m.paypal.com/' : 'https://api-m.sandbox.paypal.com/';
		$login_request = self::get_login_access( $url, $client_id, $client_secret );
		$url          .= sprintf( 'v1/billing/subscriptions/%s/cancel', $subscription_id );

		$bearerToken = $login_request['access_token']; // Replace with your actual Bearer token

		$headers = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Bearer ' . $bearerToken,
		);

		try {

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			$response    = curl_exec( $ch );
			$result      = json_decode( $response );
			$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			ur_get_logger()->debug( __( 'Paypal subscription cancel successfully !!', 'user-registration' ) );

		} catch ( \Exception $e ) {

			ur_get_logger()->debug( $e->getMessage() );
		}
	}
	/**
	 * Get login access.
	 *
	 * @param string $url The url.
	 * @param string $client_id The client id.
	 * @param string $client_secret The client secret.
	 */
	public static function get_login_access( $url, $client_id, $client_secret ) {
		$url .= 'v1/oauth2/token';
		try {
			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_USERPWD, $client_id . ':' . $client_secret );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials' );
			curl_setopt( $ch, CURLOPT_POST, true );

			$response    = curl_exec( $ch );
			$result      = json_decode( $response );
			$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );

			return array(
				'access_token' => $result->access_token,
				'status_code'  => $status_code,
			);
		} catch ( \Exception $e ) {

			ur_get_logger()->debug( $e->getMessage() );
		}
	}
}

new User_Registration_Pro_PayPal_Standard();
