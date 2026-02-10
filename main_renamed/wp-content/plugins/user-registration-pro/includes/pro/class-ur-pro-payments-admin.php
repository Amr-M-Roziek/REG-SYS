<?php

/**
 * User_Registration_Payments_Admin
 *
 * @package  User_Registration_Payments_Admin
 * @since  1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class User_Registration_Payments_Admin
 */
class User_Registration_Payments_Admin {


	/**
	 * User_Registration_Payments_Admin Constructor
	 */
	public function __construct() {

		// Payment Status on users tab.
		add_filter( 'manage_users_columns', array( $this, 'add_column_head' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'add_column_cell' ), 10, 3 );

		// Payment Status Display.
		add_action( 'user_registration_after_user_extra_information', array( $this, 'show_payment_status' ), 99 );

		// Payment Settings Hooks.
		add_filter( 'user_registration_get_settings_pages', array( $this, 'add_payment_setting' ), 10, 1 );

		// Payment Fields Hooks.
		add_action( 'user_registration_extra_fields', array( $this, 'render_payment_fields_section' ) );

		// Update User Payment.
		add_filter( 'wp_pre_insert_user_data', array( $this, 'update_payment_status' ), 10, 4 );

		// total field one time dragable
		add_filter( 'user_registration_one_time_draggable_form_fields', array( $this, 'ur_total_field_one_time_drag' ), 10, 1 );
		add_filter( 'user_registration_one_time_draggable_form_fields', array( $this, 'enable_one_time_drag_for_coupon' ), 10, 1 );
		add_filter( 'user_registration_one_time_draggable_form_fields', array( $this, 'ur_subscription_plan_field_one_time_drag' ), 10, 1 );

		// Payment Fields Settings Hooks.
		add_filter( 'user_registration_single_item_advance_class', array( $this, 'field_advance_settings' ) );
		add_filter( 'user_registration_total_field_advance_class', array( $this, 'total_field_advance_settings' ) );
		add_filter( 'user_registration_coupon_advance_class', array( $this, 'coupon_field_advance_settings' ) );
		add_filter( 'user_registration_multiple_choice_advance_class', array( $this, 'multiple_choice_advance_settings' ) );
		add_filter( 'user_registration_subscription_plan_advance_class', array( $this, 'subscription_plan_advance_settings' ) );
		add_filter( 'user_registration_quantity_field_advance_class', array( $this, 'quantity_field_advance_settings' ) );
		add_filter( 'user_registration_field_options_general_settings', array( $this, 'field_general_settings' ), 10, 2 );
		add_filter( 'user_registration_login_options', array( $this, 'add_payment_login_option' ) );

		// Frontend message settings.
		add_filter( 'user_registration_frontend_messages_settings', array( $this, 'add_paypal_frontend_message' ) );

		// Range Fields Settings Hooks.
		add_filter( 'user_registration_range_field_advance_settings', array( $this, 'custom_advance_setting' ) );

		// Payment Fields Data Hooks.
		add_filter( 'user_registration_form_field_quantity_field_params', array( $this, 'add_target_field' ), 10, 2 );
		// add_filter( 'user_registration_form_field_coupon_params', array( $this, 'add_coupon_target_field' ), 10, 2 );

		add_filter( 'user_registration_radio_field_options', array( $this, 'get_subscription_plan_options' ), 10, 7 );
		// Sanitize Input values.
		add_filter( 'user_registration_field_setting_single_item', array( $this, 'sanitize_single_item_settings' ) );
		add_filter( 'user_registration_field_setting_multiple_choice', array( $this, 'sanitize_multiple_choice_settings' ) );
		add_filter( 'user_registration_field_setting_subscription_plan', array( $this, 'sanitize_subscription_plan_settings' ) );
		add_filter( 'user_registration_form_setting_user_registration_paypal_interval_count', 'absint' );

		add_action( 'user_registration_admin_backend_validation_before_form_save', array( $this, 'form_builder_backend_validation' ) );
	}

	public function get_subscription_plan_options( $subscription_plan_options, $option, $setting_key, $setting_value, $strip_prefix, $unique, $default_value ) {
		if ( 'subscription_plan' === $strip_prefix ) {
			$label      = is_array( $option ) ? $option['label'] : ( $option->label ?? '' );
			$value      = is_array( $option ) ? $option['value'] : ( $option->value ?? '' );
			$sell_value = ( is_array( $option ) && isset( $option['sell_value'] ) ) ? $option['sell_value'] : ( ( is_object( $option ) && isset( $option->sell_value ) ) ? $option->sell_value : null );

			$interval_count   = ( is_array( $option ) && isset( $option['interval_count'] ) ) ? $option['interval_count'] : ( ( is_object( $option ) && isset( $option->interval_count ) ) ? $option->interval_count : 1 );
			$recurring_period = ( is_array( $option ) && isset( $option['recurring_period'] ) ) ? $option['recurring_period'] : ( ( is_object( $option ) && isset( $option->recurring_period ) ) ? $option->recurring_period : null );

			$trail_interval_count       = ( is_array( $option ) && isset( $option['trail_interval_count'] ) ) ? $option['trail_interval_count'] : ( ( is_object( $option ) && isset( $option->trail_interval_count ) ) ? $option->trail_interval_count : 1 );
			$trail_recurring_period     = ( is_array( $option ) && isset( $option['trail_recurring_period'] ) ) ? $option['trail_recurring_period'] : ( ( is_object( $option ) && isset( $option->trail_recurring_period ) ) ? $option->trail_recurring_period : null );
			$currency                   = get_option( 'user_registration_payment_currency', 'USD' );
			$currencies                 = ur_payment_integration_get_currencies();
			$currency                   = $currency . ' ' . $currencies[ $currency ]['symbol'];
			$subscription_plan_options  = '<li class="ur-subscription-plan">';
			$subscription_plan_options .= '<div class="ur-subscription-plan-details">';
			$subscription_plan_options .= '<div class="editor-block-mover__control-drag-handle editor-block-mover__control">
			<svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" role="img" aria-hidden="true" focusable="false"><path d="M13,8c0.6,0,1-0.4,1-1s-0.4-1-1-1s-1,0.4-1,1S12.4,8,13,8z M5,6C4.4,6,4,6.4,4,7s0.4,1,1,1s1-0.4,1-1S5.6,6,5,6z M5,10 c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S5.6,10,5,10z M13,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S13.6,10,13,10z M9,6 C8.4,6,8,6.4,8,7s0.4,1,1,1s1-0.4,1-1S9.6,6,9,6z M9,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S9.6,10,9,10z"></path></svg>
			</div>';
			$subscription_plan_options .= '<input value="' . esc_attr( $label ) . '" data-field="default_value" class="ur-general-setting-field ur-type-' . esc_attr( $setting_value['type'] ) . '-value" type="radio" name="' . esc_attr( $unique ) . '_value" ';
			if ( true == $setting_value['required'] ) {
				$subscription_plan_options .= ' required ';
			}

			$subscription_plan_options .= '' . checked( $label, $default_value, false ) . ' />';
			$subscription_plan_options .= '<input value="' . esc_attr( $label ) . '" data-field="' . esc_attr( $setting_key ) . '" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field  ur-type-' . esc_attr( $setting_value['type'] ) . '-label" type="text" name="' . esc_attr( $setting_value['name'] ) . '_label" >';
			$subscription_plan_options .= '<div class="ur-regular-price"><span>Regular Price</span><input value="' . esc_attr( $value ) . '" data-field="' . esc_attr( $setting_key ) . '" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field  ur-type-' . esc_attr( $setting_value['type'] ) . '-money-input" type="text" name="' . esc_attr( $setting_value['name'] ) . '_value" data-currency=" ' . esc_attr( $currency ) . ' " ></div>';
			$subscription_plan_options .= '<div class="ur-selling-price"><span>Selling Price</span><input value="' . esc_attr( $sell_value ) . '" data-field="' . esc_attr( $setting_key ) . '" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field ur-' . esc_attr( $setting_value['type'] ) . '-selling-price-input" type="text" name="' . esc_attr( $setting_value['name'] ) . '_selling_value" data-currency=" ' . esc_attr( $currency ) . ' " placeholder="0.00"></div>';
			$subscription_plan_options .= '<a class="add" href="#"><i class="dashicons dashicons-plus"></i></a>';
			$subscription_plan_options .= '<a class="remove" href="#"><i class="dashicons dashicons-minus"></i></a>';

			$subscription_plan_options .= '</div>';
			$subscription_plan_options .= '<div class="ur-subscription-plan-sub-details">';
			$subscription_plan_options .= '<h2>Recurring Details</h2>';
			$subscription_plan_options .= '<p><input value="' . esc_attr( $interval_count ) . '" data-field="' . esc_attr( $setting_key ) . '" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field ur-' . esc_attr( $setting_value['type'] ) . '-interval-count-input" type="number" name="' . esc_attr( $setting_value['name'] ) . '_interval_count" >';
			$subscription_plan_options .= '<select data-field="' . esc_attr( $setting_key ) . '" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field ur-' . esc_attr( $setting_value['type'] ) . '-recurring-period" name="' . esc_attr( $setting_value['name'] ) . '_recurring_period">';
			$periods                    = array(
				'day'   => __( 'Day(s)', 'user-registration' ),
				'week'  => __( 'Week(s)', 'user-registration' ),
				'month' => __( 'Month(s)', 'user-registration' ),
				'year'  => __( 'Year(s)', 'user-registration' ),
			);
			foreach ( $periods as $key => $label ) {
				$selected = '';
				if ( $recurring_period === $key ) {
					$selected = 'selected=selected';
				}
				$subscription_plan_options .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
			}
			$subscription_plan_options .= '</select>';
			$subscription_plan_options .= '</div>';

			$trail_period_enable        = ( is_array( $option ) && isset( $option['trail_period_enable'] ) ) ? $option['trail_period_enable'] : ( ( is_object( $option ) && isset( $option->trail_period_enable ) ) ? $option->trail_period_enable : false );
			$subscription_plan_options .= '<div class="ur-toggle-section ur-form-builder-toggle">';
			$subscription_plan_options .= '<label for="ur-toggle-type-trail-period">Enable Trial Period</label>';
			$subscription_plan_options .= '<span class="user-registration-toggle-form">';
			$subscription_plan_options .= '<input type="checkbox" value="' . esc_attr( $trail_period_enable ) . '" data-field="options" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field ur-radio-enable-trail-period" name="' . esc_attr( $setting_value['name'] ) . '_trail_period_enable"';

			$checked = '';
			if ( 'on' === $trail_period_enable ) {
				$checked = 'checked';
			}

			$subscription_plan_options .= '' . $checked . '/>';
			$subscription_plan_options .= '<span class="slider round"></span>';
			$subscription_plan_options .= '</span>';
			$subscription_plan_options .= '</div>';

			$subscription_plan_options .= '<div class="ur-subscription-plan-sub-details ur-subscription-trail-period-option" style="display:none;">';
			$subscription_plan_options .= '<h2>Trail Period Details</h2>';
			$subscription_plan_options .= '<p><input value="' . esc_attr( $trail_interval_count ) . '" data-field="' . esc_attr( $setting_key ) . '" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field ur-' . esc_attr( $setting_value['type'] ) . '-trail-interval-count-input" type="number" name="' . esc_attr( $setting_value['name'] ) . '_trail_interval_count" >';
			$subscription_plan_options .= '<select data-field="' . esc_attr( $setting_key ) . '" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field ur-' . esc_attr( $setting_value['type'] ) . '-trail-recurring-period" name="' . esc_attr( $setting_value['name'] ) . '_trail_recurring_period">';
			$periods                    = array(
				'day'   => __( 'Day(s)', 'user-registration' ),
				'week'  => __( 'Week(s)', 'user-registration' ),
				'month' => __( 'Month(s)', 'user-registration' ),
				'year'  => __( 'Year(s)', 'user-registration' ),
			);
			foreach ( $periods as $key => $label ) {
				$selected = '';
				if ( $trail_recurring_period === $key ) {
					$selected = 'selected=selected';
				}
				$subscription_plan_options .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
			}
			$subscription_plan_options .= '</select>';
			$subscription_plan_options .= '</div>';
			// TO enable exact date for subscription expiry.
			$subscription_expiry_enable = ( isset( $option->subscription_expiry_enable ) ) ? $option->subscription_expiry_enable : '';
			$subscription_expiry_date   = ( isset( $option->subscription_expiry_date ) ) ? $option->subscription_expiry_date : '';
			$subscription_plan_options .= '<div class="ur-toggle-section ur-form-builder-toggle">';
			$subscription_plan_options .= '<label for="ur-toggle-type-expiry-date">Enable Expiry Date</label>';
			$subscription_plan_options .= '<span class="user-registration-toggle-form">';
			$subscription_plan_options .= '<input type="checkbox"  value="' . esc_attr( $subscription_expiry_enable ) . '" data-field="options" data-field-name="' . esc_attr( $strip_prefix ) . '" class="ur-general-setting-field ur-radio-enable-expiry-date" name="' . esc_attr( $setting_value['name'] ) . '_expiry_date"';
			$expiry_checked             = '';
			if ( 'on' === $subscription_expiry_enable ) {
				$expiry_checked = 'checked';
			}

			$subscription_plan_options .= '' . $expiry_checked . '/>';
			$subscription_plan_options .= '<span class="slider round"></span>';
			$subscription_plan_options .= '</div>';
			$subscription_plan_options .= '<div class="ur-subscription-expiry-date-field ur-subscription-expiry-option"  >';
			$subscription_plan_options .= '<input type="text"  value="' . esc_attr( $subscription_expiry_date ) . '" data-field="options" class="ur-general-setting-field ur-radio-subscription-expiry-input ur-subscription-expiry-date ur-flatpickr-field regular-text without_icon flatpickr-input" data-date-format="Y-m-d" data-locale="en" data-field-name="' . esc_attr( $strip_prefix ) . '" readonly="readonly" />';
			$subscription_plan_options .= '</div>';
			$subscription_plan_options .= '</li>';

		}

		return $subscription_plan_options;
	}

	/**
	 * Form builder Backend validation for Payment fields.
	 *
	 * @since 0
	 */
	public function form_builder_backend_validation() {
		$post_content_array = json_decode( wp_unslash( $_POST['data']['form_data'] ) ); //phpcs:ignore

		$field_data = array();

		foreach ( $post_content_array as $post_content_row ) {
			foreach ( $post_content_row as $post_content_grid ) {
				if ( is_array( $post_content_grid ) || is_object( $post_content_grid ) ) {
					foreach ( $post_content_grid as $field ) {
						if ( isset( $field->field_key ) && isset( $field->general_setting->field_name ) && $field->general_setting->field_name === 'subscription_plan' ) {
							$field_data = array(
								'field_key'       => $field->field_key,
								'general_setting' => $field->general_setting,
								'advance_setting' => $field->advance_setting,
							);
						}
					}
				}
			}
		}

		$isset_recurring = false;
		if ( isset( $_POST['data']['form_setting_data'] ) ) {
			foreach ( wp_unslash( $_POST['data']['form_setting_data'] ) as $setting_data ) { //phpcs:ignore
				if ( 'user_registration_enable_stripe_recurring' === $setting_data['name'] && ur_string_to_bool( $setting_data['value'] ) ) {
					$isset_recurring = true;
				}
				if ( 'user_registration_enable_paypal_standard_subscription' === $setting_data['name'] && ur_string_to_bool( $setting_data['value'] ) ) {
					$isset_recurring = true;
				}
			}
		}
		$subscription_options = isset( $field_data['general_setting']->options ) ? $field_data['general_setting']->options : '';
		if ( ! empty( $subscription_options ) ) {
			$current_date = date( 'Y-m-d' );
			foreach ( $subscription_options as $option ) {
				// Check if subscription expiry is enabled and the expiry date matches the current date
				if ( isset( $option->subscription_expiry_enable ) && $option->subscription_expiry_enable === 'on' && ! empty( $option->subscription_expiry_date ) ) {
					if ( $option->subscription_expiry_date <= $current_date ) {
						throw new Exception(
							esc_html__( 'Subscription expiry date should be the future date.', 'user-registration' )
						);
						return;
					}
				}
			}
		}

		if ( ! empty( $field_data ) && ! $isset_recurring ) {
			throw new Exception(
				esc_html__( 'You have dragged subscription plan field in the form but recurring subscription is not enabled. Please enable recurring subscription from form settings.', 'user-registration' ) ); //phpcs:ignore
		}
	}

	/**
	 * Add the column header for the email status column
	 *
	 * @param array $columns Columns.
	 *
	 * @return array
	 */
	public function add_column_head( $columns ) {
		if ( ! current_user_can( 'edit_user' ) ) {
			return $columns;
		}

		$the_columns['ur_user_payment_status'] = __( 'Payment Status', 'user-registration' );

		$newcol  = array_slice( $columns, 0, - 1 );
		$newcol  = array_merge( $newcol, $the_columns );
		$columns = array_merge( $newcol, array_slice( $columns, 1 ) );

		return $columns;
	}

	/**
	 * Payment Status display on user profile
	 *
	 * @param mixed $user User Data.
	 *
	 * @return void
	 * @throws Exception Error Messages.
	 */
	public function show_payment_status( $user ) {

		// Get form id.
		$form_id = get_user_meta( $user->ID, 'ur_form_id', true );

		// Check if PayPal payment is enabled or not.
		$paypal_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ) );

		// Check if Stripe payment is enabled or not.
		$stripe_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_stripe', false ) );

		$mollie_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_mollie', false ) );
		$anet_is_enabled   = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_authorize_net', false ) );

		$payment_is_enabled = $paypal_is_enabled ? $paypal_is_enabled : ( $stripe_is_enabled || $anet_is_enabled || $mollie_is_enabled );

		// // Filter to check if other payments are enabled.
		// $payment_is_enabled = apply_filters( 'user_registration_enable_payment', $paypal_is_enabled ? $paypal_is_enabled : $stripe_is_enabled );

		if ( ! $payment_is_enabled ) {
			return;
		}

		// Return if current user cannot edit users.
		if ( ! current_user_can( 'edit_user' ) ) {
			throw new Exception( 'You donot have enough permission to perform this action' );
		}

		$payment_status          = array(
			'ur_payment_transaction'  => esc_html__( 'Transaction ID', 'user-registration' ),
			'ur_payment_method'       => esc_html__( 'Payment Method', 'user-registration' ),
			'ur_payment_currency'     => esc_html__( 'Payment Currency', 'user-registration' ),
			'ur_payment_total_amount' => esc_html__( 'Total Amount', 'user-registration' ),
		);
		$ur_payment_subscription = get_user_meta( $user->ID, 'ur_payment_subscription', true );
		$ur_payment_method       = get_user_meta( $user->ID, 'ur_payment_method', true );

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
<h3><?php esc_html_e( 'Payment Status', 'user-registration' ); ?></h3>
<table class="form-table">
		<?php
			$payment_method = get_user_meta( $user->ID, 'ur_payment_method', true );
		if ( '' != $payment_method ) {

			$subscription_status = '';
			$subscription_id     = '';
			$customerid          = '';
			foreach ( $payment_status as $meta_key => $label ) {

				$value = get_user_meta( $user->ID, $meta_key, true );

				if ( 'ur_payment_subscription_status' === $meta_key ) {
					$value = 'cancel_at_end_of_cycle' === $value ? 'active' : $value;
				} elseif ( 'ur_payment_method' === $meta_key ) {
					$value = ( 'credit_card' == $value ) ? __( 'Stripe ( Credit Card )', 'user-registration' ) : $value;
					$value = ( 'ideal' == $value ) ? __( 'Stripe ( iDEAL )', 'user-registration' ) : $value;
					$value = ( 'paypal_standard' == $value ) ? __( 'PayPal Standard', 'user-registration' ) : $value;
				} elseif ( 'ur_payment_mode' === $meta_key ) {

					if ( 'test' == $value ) {
						$value = __( 'Test/Sandbox', 'user-registration' );
					} elseif ( 'production' === $value || 'live' == $value ) {
						$value = __( 'Production', 'user-registration' );
					}
				} elseif ( 'ur_payment_currency' === $meta_key ) {
					$currencies = ur_payment_integration_get_currencies();
					$value      = $currencies[ $value ]['name'] . ' ( ' . $value . ' ' . $currencies[ $value ]['symbol'] . ' )';
				} elseif ( 'ur_payment_status' === $meta_key ) {
					$completed_selected = 'completed' === $value ? 'selected="selected"' : '';
					$pending_selected   = 'pending' === $value ? 'selected="selected"' : '';
					echo '
								<tr>
									<th>
										<label for="' . esc_attr( $meta_key ) . '">' . esc_html( $label ) . '</label>
									</th>
									<td>
										<select name="' . esc_attr( $meta_key ) . '" id="' . esc_attr( $meta_key ) . '">
											<option ' . esc_attr( $completed_selected ) . ' value="completed">Completed</option>
											<option ' . esc_attr( $pending_selected ) . ' value="pending">Pending</option>
										</select>
									</td>
								</tr>';

					break;
				}

				echo '<tr>
						<th><label for="' . esc_attr( $meta_key ) . '">' . esc_html( $label ) . '</label>
						</th>
						<td>
							' . esc_html( $value ) . '
						</td>
					</tr>';
			}
		} else {
			echo '<tr><th><label>' . esc_html__( 'Payments Details not available.', 'user-registration' ) . '</label></th></tr>';
		}
		?>
</table>

		<?php
	}

	/**
	 * Update user payment status value.
	 *
	 * @param [array] $data Data.
	 * @param [bool]  $update Is update process.
	 * @param [int]   $user_id User Id.
	 * @param [array] $userdata User Data.
	 *
	 * @return array $data
	 */
	public function update_payment_status( $data, $update, $user_id, $userdata ) {
		if ( $update ) {
			if ( ! empty( $_POST['ur_payment_status'] ) ) {
				update_user_meta( $user_id, 'ur_payment_status', sanitize_text_field( $_POST['ur_payment_status'] ) );
			}
		}

		return $data;
	}

	/**
	 * Set the status value for each user in the users list
	 *
	 * @param string $val Value.
	 * @param string $column_name Column Name.
	 * @param int    $user_id User Id.
	 *
	 * @return string
	 */
	public function add_column_cell( $val, $column_name, $user_id ) {
		if ( ! current_user_can( 'edit_user' ) ) {
			return false;
		}

		if ( 'ur_user_payment_status' === $column_name ) {
			$val = get_user_meta( $user_id, 'ur_payment_status', true );
		}

		return $val;
	}

	/**
	 * Add payment setting.
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public function add_payment_setting( $settings ) {
		if ( class_exists( 'UR_Settings_Page' ) ) {

//			$settings[] = include_once __DIR__ . '/admin/settings/class-ur-pro-payment-settings.php';
		}

		return $settings;
	}

	/**
	 * Html for Payment Fields
	 *
	 * @return void
	 */
	public function render_payment_fields_section() {
		echo '<h2 class="ur-toggle-heading closed">' . esc_html__( 'Payment Fields', 'user-registration' ) . '</h2><hr/>';
		$this->get_payment_fields();
	}

	/**
	 * Payment fields Render
	 *
	 * @return void
	 */
	public function get_payment_fields() {

		$payment_fields = apply_filters( 'user_registration_payment_fields', user_registration_payment_fields() );

		echo ' <ul id = "ur-draggabled" class="ur-registered-list ur-payment-fields" > ';
		foreach ( $payment_fields as $field ) {
			$get_list = new UR_Admin_Menus();
			$get_list->ur_get_list( $field );
		}
		echo ' </ul > ';
	}

	/**
	 * Advance settings for single item.
	 *
	 * @param mixed $file_data File Data.
	 *
	 * @return array
	 */
	public function field_advance_settings( $file_data ) {

		$path                   = __DIR__ . '/form/settings/class-ur-setting-single-item.php';
		$file_data['file_path'] = $path;

		return $file_data;
	}

	/**
	 * Multiple Choice  Advance class.
	 *
	 * @param array $file_data File Data.
	 *
	 * @return array
	 */
	public function multiple_choice_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-multiple-choice.php';
		$file_data['file_path'] = $path;

		return $file_data;
	}

	/**
	 * Subscription Plan  Advance class.
	 *
	 * @param array $file_data File Data.
	 *
	 * @return array
	 */
	public function subscription_plan_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-subscription-plan.php';
		$file_data['file_path'] = $path;

		return $file_data;
	}

	/**
	 * Advance settings for Total.
	 *
	 * @param mixed $file_data File Data.
	 *
	 * @return array
	 */
	public function total_field_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-total-field.php';
		$file_data['file_path'] = $path;

		return $file_data;
	}

	/**
	 * Advance settings for coupon.
	 *
	 * @param mixed $file_data File Data.
	 *
	 * @return array
	 */
	function coupon_field_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-coupon.php';
		$file_data['file_path'] = $path;

		return $file_data;
	}

	/**
	 * Advance settings for Quantity.
	 *
	 * @param mixed $file_data File Data.
	 *
	 * @return array
	 */
	public function quantity_field_advance_settings( $file_data ) {
		$path                   = __DIR__ . '/form/settings/class-ur-setting-quantity-field.php';
		$file_data['file_path'] = $path;

		return $file_data;
	}

	/**
	 *  Modify general settings.
	 *
	 * @param array  $general_settings Setting for field.
	 * @param string $id field Id.
	 *
	 * @return  array $general_settings
	 */
	public function field_general_settings( $general_settings, $id ) {

		switch ( $id ) {
			case 'user_registration_single_item':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}
				break;
			case 'user_registration_total_field':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}
				break;
			case 'user_registration_multiple_choice':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}

				$new_settings     = array(
					'options' => array(
						'setting_id'  => 'options',
						'type'        => 'checkbox',
						'label'       => __( 'Options', 'user-registration' ),
						'name'        => 'ur_general_setting[options]',
						'placeholder' => '',
						'required'    => true,
						'options'     => array(
							array(
								'label' => __( 'First Choice', 'user-registration' ),
								'value' => '10.00',
							),
							array(
								'label' => __( 'Second Choice', 'user-registration' ),
								'value' => '20.00',
							),
							array(
								'label' => __( 'Third Choice', 'user-registration' ),
								'value' => '30.00',
							),
						),
						'tip'         => __( 'Add options to let users select from.', 'user-registration' ),
					),
				);
				$general_settings = ur_insert_after_helper( $general_settings, $new_settings, 'field_name' );
				break;
			case 'user_registration_subscription_plan':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}

				$new_settings     = array(
					'options' => array(
						'setting_id'  => 'options',
						'type'        => 'radio',
						'label'       => __( 'Options', 'user-registration' ),
						'name'        => 'ur_general_setting[options]',
						'placeholder' => '',
						'required'    => true,
						'options'     => array(
							array(
								'label' => __( 'First Choice', 'user-registration' ),
								'value' => '10.00',
							),
							array(
								'label' => __( 'Second Choice', 'user-registration' ),
								'value' => '20.00',
							),
							array(
								'label' => __( 'Third Choice', 'user-registration' ),
								'value' => '30.00',
							),
						),
						'tip'         => __( 'Add options to let users select from.', 'user-registration' ),
					),
				);
				$general_settings = ur_insert_after_helper( $general_settings, $new_settings, 'field_name' );
				break;
			case 'user_registration_quantity':
				$remove_keys = array( 'placeholder' );
				foreach ( $remove_keys as $remove_key ) {
					unset( $general_settings[ $remove_key ] );
				}
				break;
		}

		return $general_settings;
	}

	/**
	 * Add Payment Before Registration option.
	 *
	 * @param array $options Other login options.
	 *
	 * @return  array
	 */
	public function add_payment_login_option( $options ) {

		$options['payment'] = esc_html__( 'Payment before login', 'user-registration' );

		return $options;
	}

	/**
	 * Add paypal frontend messages.
	 *
	 * @param array $settings Settings.
	 */
	public function add_paypal_frontend_message( $settings ) {
		$settings['sections']['payment_pending_messages_settings'] = array(
			'title'    => __( 'Payment Messages', 'user-registration' ),
			'type'     => 'card',
			'desc'     => '',
			'settings' => array(
				array(
					'title'    => __( 'Payment Before Login', 'user-registration' ),
					'desc'     => __( 'Enter the text message for pending payment error message before login.', 'user-registration' ),
					'id'       => 'user_registration_pro_pending_payment_error_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'Your account is still pending payment. Process the payment by clicking on this: <a id="payment-link" href="%s">link</a>', 'user-registration' ),
				),
				array(
					'title'    => __( 'Payment Before Registration', 'user-registration' ),
					'desc'     => __( 'Enter the text message after for pending  payment.', 'user-registration' ),
					'id'       => 'user_registration_payment_before_registration_pending_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'User Registered. Payment Processing...', 'user-registration' ),
				),
				array(
					'title'    => __( 'Payment Completed', 'user-registration' ),
					'desc'     => __( 'Enter the text message after for payment completed.', 'user-registration' ),
					'id'       => 'user_registration_payment_completed_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'User Registered. Payment Completed.', 'user-registration' ),
				),
			),
		);

		return $settings;
	}

	/**
	 * Add custom advance setting
	 *
	 * @param array $settings Settings.
	 */
	public function custom_advance_setting( $fields ) {
		$custom_advance_setting = array(
			'enable_payment_slider' => array(
				'type'     => 'toggle',
				'data-id'  => 'range_advance_setting_enable_payment_slider',
				'label'    => __( 'Enable Payment Slider', 'user-registration' ),
				'name'     => 'range_advance_setting[enable_payment_slider]',
				'class'    => 'ur_advance_setting ur-settings-enable-payment-slider',
				'default'  => 'false',
				'required' => false,
				'tip'      => __( 'Enable this if you want use text Payment slider ', 'user-registration' ),
			),
		);
		$fields                 = array_merge( $fields, $custom_advance_setting );

		return $fields;
	}

	/**
	 * Make total field one time draggable.
	 *
	 * @param array $fields One time draggable fields.
	 *
	 * @return array    One time draggable fields.
	 * @since 1.2.0
	 */
	public function ur_total_field_one_time_drag( $fields ) {
		$fields[] = 'total_field';

		return $fields;
	}

	/**
	 * Make subscription plan field one time draggable.
	 *
	 * @param array $fields One time draggable fields.
	 *
	 * @return array    One time draggable fields.
	 * @since 1.2.0
	 */
	public function ur_subscription_plan_field_one_time_drag( $fields ) {
		$fields[] = 'subscription_plan';

		return $fields;
	}

	/*
	 * Make coupon field one time draggable.
	 *
	 * @param array $fields One time draggable fields.
	 *
	 * @return array    One time draggable fields.
	 */
	public static function enable_one_time_drag_for_coupon( $fields ) {
		$fields[] = 'coupon';

		return $fields;
	}
	/**
	 * Add Target Field Name for Quantity Field in form data.
	 *
	 * @param [array] $data Quantity Field Data.
	 * @param [array] $fields Quantity Field Settings.
	 *
	 * @return array $data
	 */
	public function add_target_field( $data, $fields ) {

		if ( ! isset( $fields->advance_setting->target_field ) ) {
			exit;
		}

		$data->extra_params['target_field'] = $fields->advance_setting->target_field;

		return $data;
	}

	/**
	 * Add Target Field Name for Quantity Field in form data.
	 *
	 * @param [array] $data Quantity Field Data.
	 * @param [array] $fields Quantity Field Settings.
	 *
	 * @return array $data
	 */
	public function add_coupon_target_field( $data, $fields ) {

		if ( ! isset( $fields->advance_setting->target_field ) ) {
			exit;
		}

		$data->extra_params['target_field'] = $fields->advance_setting->target_field;

		return $data;
	}

	/**
	 * Sanitize negative inputs for single item price value.
	 *
	 * @param [object] $setting Single Item Setting.
	 *
	 * @return object
	 */
	public function sanitize_single_item_settings( $setting ) {

		$default_value = abs( floatval( $setting->advance_setting->default_value ) );

		$setting->advance_setting->default_value = $default_value;

		return $setting;
	}


	/**
	 * Sanitize negative and invalid inputs for multiple choice price value.
	 *
	 * @param [object] $setting Multiple Choice Setting.
	 *
	 * @return object
	 */
	public function sanitize_multiple_choice_settings( $setting ) {
		foreach ( $setting->general_setting->options as $key => $item ) {
			if ( isset( $setting->general_setting->options[ $key ] ) && isset( $item->value ) ) {
				$setting->general_setting->options[ $key ]->value = abs( floatval( $item->value ) );
			}
		}

		return $setting;
	}

	/**
	 * Sanitize negative and invalid inputs for subscription plan price value.
	 *
	 * @param [object] $setting Subscription Plan Setting.
	 *
	 * @return object
	 */
	public function sanitize_subscription_plan_settings( $setting ) {
		foreach ( $setting->general_setting->options as $key => $item ) {
			if ( isset( $setting->general_setting->options[ $key ] ) && isset( $item->value ) ) {
				$setting->general_setting->options[ $key ]->value = abs( floatval( $item->value ) );
			}
		}

		return $setting;
	}
}

new User_Registration_Payments_Admin();
