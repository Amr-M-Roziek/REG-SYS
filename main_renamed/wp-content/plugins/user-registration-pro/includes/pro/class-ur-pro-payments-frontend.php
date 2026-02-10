<?php
/**
 * User_Registration_Payments_Frontend
 *
 * @package  User_Registration_Payments_Frontend
 * @since  1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class User_Registration_Payments_Frontend
 */
class User_Registration_Payments_Frontend {

	/**
	 * User_Registration_Payments_Frontend Constructor
	 */
	public function __construct() {
		add_filter( 'user_registration_form_field_single_item', array( $this, 'user_registration_payment_form_field_render' ), 10, 4 );
		add_filter( 'user_registration_single_item_frontend_form_data', array( $this, 'user_registration_payment_form_field_filter' ), 10, 1 );
		add_filter( 'user_registration_form_field_total_field', array( $this, 'user_registration_payment_form_field_render' ), 10, 4 );
		add_filter( 'user_registration_total_field_frontend_form_data', array( $this, 'user_registration_payment_form_field_filter' ), 10, 1 );
		add_filter( 'user_registration_form_field_multiple_choice', array( $this, 'user_registration_payment_form_field_render' ), 10, 4 );
		add_filter( 'user_registration_multiple_choice_frontend_form_data', array( $this, 'user_registration_payment_form_field_filter' ), 10, 1 );
		add_filter( 'user_registration_form_field_subscription_plan', array( $this, 'user_registration_payment_form_field_render' ), 10, 4 );
		add_filter( 'user_registration_subscription_plan_frontend_form_data', array( $this, 'user_registration_payment_form_field_filter' ), 10, 1 );
		add_filter( 'user_registration_form_field_quantity_field', array( $this, 'user_registration_payment_form_field_render' ), 10, 4 );
		add_filter( 'user_registration_quantity_field_frontend_form_data', array( $this, 'user_registration_payment_form_field_filter' ), 10, 1 );

		$user_id = get_current_user_id();

		$payment_method = get_user_meta( $user_id, 'ur_payment_method', true );
		add_filter( 'user_registration_get_query_vars', array( $this, 'user_registration_add_payment_endpoint' ) );

		if ( '' !== $payment_method ) {
			add_filter( 'user_registration_account_menu_items', array( $this, 'payment_item_tab' ) );
			add_action( 'user_registration_account_payment_endpoint', array( $this, 'user_registration_payment_endpoint_content' ) );
		}
		// PayPal Process hooks.
		add_filter( 'user_registration_success_params', array( $this, 'payment_process_after_registration' ), 10, 4 );

		add_action( 'template_redirect', array( $this, 'download_invoice_action' ), 10 );
	}

	/**
	 * Download Payment Invoice.
	 *
	 * @since 4.0.5
	 */
	public function download_invoice_action() {

		if ( isset( $_GET['payment_action'] ) ) {

			// Condition for resending token.
			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'ur_payment_action' ) ) {
				die( __( 'Action failed. Please refresh the page and retry.', 'user-registration' ) );
			}

			if ( 'invoice_download' === $_GET['payment_action'] ) {
				ob_start();
				$this->download_invoice_pdf();

			}
		}
	}

	/**
	 * Download PDF for payment invoice.
	 *
	 * @since 4.0.5
	 */
	private function download_invoice_pdf() {
		$transaction_id  = isset( $_GET['transaction_id'] ) ? $_GET['transaction_id'] : '';
		$user_id         = get_current_user_id();
		$payment_invoice = get_user_meta( $user_id, 'ur_payment_invoices', true );
		$first_name      = get_user_meta( $user_id, 'first_name', true );
		$last_name       = get_user_meta( $user_id, 'last_name', true );
		$user            = get_user_by( 'ID', $user_id );

		$customer_name = ! empty( $first_name ) || ! empty( $last_name ) ? $first_name . ' ' . $last_name : $user->data->user_email;
		$form_id       = ur_get_form_id_by_userid( $user_id );

		foreach ( $payment_invoice as $key => $invoice ) {
			if ( $transaction_id == $invoice['invoice_no'] ) {
				$html      = $this->generate_invoice_html( $transaction_id, $customer_name, $invoice, $form_id );
				$file_name = $user->data->user_login;

				/**
				 * Disable SSL Certificate verification (only when the pdf is being rendered).
				 *
				 * Ref 1: https://github.com/PHPOffice/PHPWord/pull/988
				 * Ref 2: https://www.php.net/manual/en/migration56.openssl.php
				 */
				$context_options = array(
					'ssl' => array(
						'verify_peer'      => false,
						'verify_peer_name' => false,
					),
				);

				stream_context_set_default( $context_options );

				$tcpdf = ur_pro_generate_pdf_file( $html );
				$tcpdf->output( $file_name . '.pdf', 'D' );

				/**
				 * Enable SSL Certificate verification.
				 */
				$context_options = array(
					'ssl' => array(
						'verify_peer'      => true,
						'verify_peer_name' => true,
					),
				);
				stream_context_set_default( $context_options );
			}
		}
	}

	private function generate_invoice_html( $transaction_id, $customer_name, $invoice, $form_id ) {

		$subscription_plan_field = ur_get_field_data_by_field_name( $form_id, 'subscription_plan' );
		$invoice_plan            = '';
		if ( empty( $subscription_plan_field ) ) {
			$invoice_plan = ( isset( $invoice['invoice_plan'] ) ? $invoice['invoice_plan'] . ' <br>' : '' );
		}

		$custom_logo_id = apply_filters( 'user_registration_payment_invoice_custom_logo', get_theme_mod( 'custom_logo' ) );
		$site_logo      = wp_get_attachment_image_src( $custom_logo_id, 'full' );

		if ( $site_logo ) {
			$site_info = '<img src="' . esc_url( $site_logo[0] ) . '" alt="' . get_bloginfo( 'name' ) . '" style="width:50px;height:50px;">';
		} else {
			$site_info = '<h1>' . apply_filters( 'user_registration_payment_invoice_company_name', get_bloginfo( 'name' ) ) . '</h1>';
		}
		if ( is_array( $invoice['invoice_item'] ) ) {

			$invoice_data = json_decode( $invoice['invoice_item'][0] );

			$html  = '
		<style>
		table, tr, td {
			padding: 15px;
		}
		</style>
		<table>
		<tbody>
		<tr>
		<td>' . $site_info . '</td>
		<td align="right"><p>#Invoice : ' . apply_filters( 'user_registration_payment_invoice_number', $invoice['invoice_no'], $invoice, $customer_name ) . '</p></td>
		</tr>
		</tbody>
		</table>
		';
			$html .= '
		<table>
		<thead>
		<tr style="font-weight:bold;background-color: #D7E4E4;">
		<th style="border-bottom: 1px solid #222;">' . esc_html__( 'Invoice From', 'user-registration' ) . '</th>
		<th style="border-bottom: 1px solid #222;">' . esc_html__( 'Invoice To', 'user-registration' ) . '</th>
		<th style="border-bottom: 1px solid #222;">' . esc_html__( 'Date', 'user-registration' ) . '</th>
		</tr>
		</thead>
		<tbody>';
			$html .= '
		<tr>
		<td>' . get_bloginfo( 'name' ) . '</td>
		<td>' . $customer_name . '</td>
		<td>' . date( 'Y-m-d', strtotime( $invoice['invoice_date'] ) ) . '</td>
		</tr>
		</tbody>
		</table>
		';
			$html .= '
		<table>
		<thead>
		<tr style="font-weight:bold;background-color: #D7E4E4;">
		<th style="border-bottom: 1px solid #222;">' . esc_html__( 'Invoice Item', 'user-registration' ) . '</th>
		<th style="border-bottom: 1px solid #222;">' . esc_html__( 'Quantity', 'user-registration' ) . '</th>
		<th style="border-bottom: 1px solid #222;">' . esc_html__( 'Price', 'user-registration' ) . '</th>
		</tr>
		</thead>
		<tbody>';
			$html .= '
		<tr>
		<td style="border-bottom: 1px solid #222">' . $invoice_plan;

			foreach ( $invoice_data as $key => $items ) {
				$label = $items->extra_params->label;

				$html .= '<p>' . $label;
				if ( is_object( $items->value ) ) {

					foreach ( $items->value as $label => $value ) {
						$label = str_replace( 'u2013', '–', $label );
						$html .= '<p> ' . $label . '</p>';
					}
				}
				$html .= '</p>';
			}

			$html .= '</td>
		<td style="border-bottom: 1px solid #222">';

			foreach ( $invoice_data as $key => $items ) {
				$quantity = 1;
				if ( isset( $items->extra_params->quantity ) ) {
					$quantity = absint( $items->extra_params->quantity );
				}
				$html .= '<p>' . $quantity . '</p>';
			}

			$html .= '</td>
		<td style="border-bottom: 1px solid #222">';

			foreach ( $invoice_data as $key => $items ) {
				$html .= '<p>' . $items->amount . '</p>';
			}

			$html .= '</td>
		</tr>
		</tbody>
		</table>
		';
			$html .= '
		<table>
			<tfoot>
				<tr>
					<td></td>
					<td> ' . esc_html__( 'SubTotal', 'user-registration' ) . '<br><br> ' . esc_html__( 'Total', 'user-registration' ) . ' <br><br> ' . esc_html__( 'Payment Status', 'user-registration' ) . '</td>
					<td>
					<span>' . $invoice['invoice_currency'] . '' . $invoice['invoice_amount'] . '</span><br><br>
					<span>' . $invoice['invoice_currency'] . '' . $invoice['invoice_amount'] . '</span><br><br>
					<span style="color:#09bf52;">' . ucfirst( $invoice['invoice_status'] ) . '</span>
					</td>
				</tr>
			</tfoot>
		</table>
	';
		}

		return $html;
	}






	/**
	 * Add necessary query vars to registered query vars.
	 *
	 * @param array $vars Registered query_vars.
	 * @return array $vars
	 */
	public function user_registration_add_payment_endpoint( $vars ) {
		$vars['payment'] = 'payment';
		$rewrite_rules   = get_option( 'rewrite_rules', array() );

		if ( ! isset( $rewrite_rules['(.?.+?)/payment(/(.*))?/?$'] ) ) {
			flush_rewrite_rules();
		}

		return $vars;
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @param array $success_params Success Parameter.
	 * @param array $valid_form_data Form data.
	 * @param int   $form_id Form Id.
	 * @param int   $user_id User Id.
	 */
	public function payment_process_after_registration( $success_params, $valid_form_data, $form_id, $user_id ) {
		$field_extra_params_list     = wp_list_pluck( $valid_form_data, 'extra_params' );
		$field_type_list             = wp_list_pluck( $field_extra_params_list, 'field_key' );
		$is_required_payment_gateway = false;
		$is_single_available         = false;

		foreach ( $field_type_list as $key => $value ) {

			$payment_slider = check_is_range_payment_slider( $key, $form_id );

			if ( 'single_item' === $value || ( 'range' === $value && $payment_slider ) || 'multiple_choice' === $value || 'subscription_plan' === $value ) {
				$is_required_payment_gateway = true;
				$urcl_hide_fields            = isset( $_POST['urcl_hide_fields'] ) ? (array) json_decode( stripslashes( $_POST['urcl_hide_fields'] ), true ) : array();
				if ( ! in_array( $key, $urcl_hide_fields, true ) ) {
					$is_single_available = true;
					break;
				}
			}
		}

		if ( ! $is_single_available ) {
			return $success_params;
		}
		$success_params['form_login_option'] = isset( $success_params['form_login_option'] ) ? $success_params['form_login_option'] : ur_get_single_post_meta( $form_id, 'user_registration_form_setting_login_options', get_option( 'user_registration_general_setting_login_options', 'default' ) );
		// Check an user was created and passed.
		if ( empty( $user_id ) ) {
			return $success_params;
		}

		// Check if PayPal payment is enabled or not.
		$paypal_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ) );

		// Check if Stripe payment is enabled or not.
		$stripe_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_stripe', false ) );

		// Check if Authorize.net payment is enabled or not.
		$anet_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_authorize_net', false ) );

		// Check if Mollie payment is enabled or not.
		$mollie_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_mollie', false ) );
		
		// Filter to check if other payments are enabled.
		$payment_is_enabled = $paypal_is_enabled ? $paypal_is_enabled : ( $stripe_is_enabled || $anet_is_enabled || $mollie_is_enabled );

		// $payment_is_enabled = apply_filters( 'user_registration_form_enable_payment', $payment_is_enabled, $form_id );
		

		$is_valid_paypal = false;
		$is_valid_stripe = false;
		$is_valid_anet   = false;
		$is_valid_mollie   = false;

		// Only Authorize.net is enabled.
		if ( $payment_is_enabled && ! $paypal_is_enabled && ! $stripe_is_enabled && $anet_is_enabled && !$mollie_is_enabled ) {
			$is_valid_anet = true;
		}

		if ( ! $payment_is_enabled ) {
			if ( 'payment' === $success_params['form_login_option'] || $is_required_payment_gateway ) {
				wp_delete_user( absint( $user_id ) );
				wp_send_json_error(
					array(
						'message' => __( 'No payment gateway enabled!', 'user-registration' ),
					)
				);
			} else {
				return $success_params;
			}
		} elseif ( ! $paypal_is_enabled && $payment_is_enabled && ! $is_valid_anet && ! $mollie_is_enabled) {
			// PayPal is not enalbed but stripe is enabled.
			$is_valid_stripe = $this->stripe_conditional_logic( $form_id, $valid_form_data );
		} elseif ( $paypal_is_enabled && $stripe_is_enabled ) {
			// PayPal and Stripe both are enabled.
			$is_valid_paypal = $this->paypal_conditional_logic( $form_id, $valid_form_data );
			$is_valid_stripe = $this->stripe_conditional_logic( $form_id, $valid_form_data );
		} elseif ( $paypal_is_enabled && $mollie_is_enabled ) {
			// Only Paypal is enabled.
			$is_valid_paypal = $this->paypal_conditional_logic( $form_id, $valid_form_data );
			$is_valid_mollie = $this->mollie_conditional_logic( $form_id, $valid_form_data );
		} elseif ( $mollie_is_enabled ) {

			// Only Mollie is enabled.
			$is_valid_mollie = $this->mollie_conditional_logic( $form_id, $valid_form_data );
		}
		elseif ( $payment_is_enabled && $paypal_is_enabled && ! $stripe_is_enabled && ! $anet_is_enabled && !$mollie_is_enabled ) {
			// Only Paypal is enabled.
			$is_valid_paypal = $this->paypal_conditional_logic( $form_id, $valid_form_data );
		}
		
		if ( ! $is_valid_paypal && $is_valid_stripe ) {
			return apply_filters( 'user_registration_success_params_stripe_payment_process', $success_params, $valid_form_data, $form_id, $user_id );
		} elseif ( ( $is_valid_paypal && ! $is_valid_stripe ) || ( $is_valid_paypal && $is_valid_stripe ) ) {
			return apply_filters( 'user_registration_success_params_paypal_payment_process', $success_params, $valid_form_data, $form_id, $user_id );
		} elseif ( $is_valid_anet ) {
			return apply_filters( 'user_registration_success_params_authorize_net_payment_process', $success_params, $valid_form_data, $form_id, $user_id );
		} elseif ( ( $is_valid_mollie && ! $is_valid_paypal ) || ( $is_valid_paypal && $is_valid_mollie ) ) {
			return apply_filters( 'user_registration_success_params_mollie_payment_process', $success_params, $valid_form_data, $form_id, $user_id );
		} else {
			$success_params['stripe_process'] = false;
			$success_params['message']        = get_option( 'user_registration_payment_before_registration_pending_message', esc_html( 'User Registered. Proceed to Login.' ) );
			return $success_params;
		}
	}
	public function mollie_conditional_logic( $form_id, $valid_form_data ) {
		$mollie_integration = get_post_meta( $form_id, 'user_registration_mollie_conditional_integration', true );
		$is_valid           = true;

		if ( count( $mollie_integration ) > 0 ) {
			foreach ( $mollie_integration as $paypal_conditional_key => $paypal_conditonal_data ) {

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
	 * Add arguments to the payment fields for frontend filter
	 *
	 * @param  array $filter_data Data.
	 * @return array
	 */
	public function user_registration_payment_form_field_filter( $filter_data ) {
		$filter_data['form_data']['item_type'] = isset( $filter_data['data']['advance_setting']->item_type ) ? $filter_data['data']['advance_setting']->item_type : '';

		return $filter_data;
	}

	/**
	 * Render the payment fields on frontend
	 *
	 * @param  string $field Field.
	 * @param  string $key Field name.
	 * @param  array  $args Arguments.
	 * @param  mixed  $value Default value.
	 * @return void
	 */
	public function user_registration_payment_form_field_render( $field, $key, $args, $value ) {

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

		if ( ! isset( $args['item_type'] ) ) {
			return;
		}

		$attr = ( 'hidden' === $args['item_type'] || 'pre_defined' === $args['item_type'] ) ? 'disabled' : '';

		if ( true === $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'user-registration' ) . '">*</abbr>';
			$attr_required   = 'required = "required"';

		} else {
			$required         = '';
			$attr_required    = '';
			$args['required'] = $required;
		}

		$description   = '<span class="description">' . isset( $args['description'] ) ? $args['description'] : '</span>';
		$field_wrapper = '<p class="form-row " id="' . esc_attr( $args['id'] ) . '" data-priority="">';
		$field_content = '';
		$field_label   = $field_content;

		$tooltip_html = '';

		if ( isset( $args['tooltip'] ) && ur_string_to_bool( $args['tooltip'] ) ) {
			$tooltip_html = ur_help_tip( $args['tooltip_message'], false, 'ur-portal-tooltip' );
		}

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

		switch ( $args['type'] ) {
			case 'single_item':
				$currency   = get_option( 'user_registration_payment_currency', 'USD' );
				$currencies = ur_payment_integration_get_currencies();

				$field_content .= ( 'hidden' !== $args['item_type'] ) ? '<p>' . $field_label . '</p>' : '<p></p>';
				$field_content .= ( 'hidden' !== $args['item_type'] ) ? $description : '';
				$enable_calculations = $args['enable_calculations'] ?? '';
				$calculation_formula = $args['calculation_formula'] ?? '';
				$decimal_places = $args['decimal_places'] ?? '';

				$currency = $currency;
				$default  = '';
				$value    = '';
				if ( isset( $args['enable_selling_price_single_item'] ) && ur_string_to_bool( $args['enable_selling_price_single_item'] ) ) {
					$selling_price = isset( $args['selling_price'] ) ? $args['selling_price'] : '';
					$default       = isset( $args['default'] ) ? $args['default'] : '';
					$default       = '<del>' . $currencies[ $currency ]['symbol'] . $default . '</del>' . ' ' . $currencies[ $currency ]['symbol'] . $selling_price;
					$value         = $selling_price;
				} else {
					$default = isset( $args['default'] ) ? $args['default'] : '';
					$value   = $default;
				}
				switch ( $args['item_type'] ) {
					case 'pre_defined':
						$field_content .= '<h7>' . $currency . ' ' . $currencies[ $currency ]['symbol'] . $default . '</h7> <input '. (( '' !== $enable_calculations && $enable_calculations ) ? ('data-decimal-places="' . $decimal_places . '" data-calculation-formula="' . $calculation_formula.'"') : ""  ) .' data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '" type="hidden" value="' . $value . '" class="ur-payment-price ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . $attr . ' ' . $attr_required . ' /> ';
						break;

					case 'user_defined':
						$field_content .= '<h7 style = "margin-right:10px; display:inline-block">' . $currency . '</h7><input '.(( '' !== $enable_calculations && $enable_calculations ) ? ('data-decimal-places="' . $decimal_places . '" data-calculation-formula="' . $calculation_formula.'"') : ""  ) .' data-rules="' . $rules . '" style = "width: 80px; display:inline-block" data-id="' . esc_attr( $key ) . '" type="text" value="' . $args['default'] . '" class="ur-payment-price ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . $attr . ' ' . $attr_required . ' /> ';
						break;

					case 'hidden':
						$field_content .= ' <input '.(( '' !== $enable_calculations && $enable_calculations ) ? ('data-decimal-places="' . $decimal_places . '" data-calculation-formula="' . $calculation_formula.'"') : ""  ).' data-id="' . esc_attr( $key ) . '" type="hidden" value="' . $value . '" class="ur-payment-price ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . $attr . ' ' . $attr_required . ' /> ';
						break;
				}
				break;

			case 'total_field':
				$currency   = get_option( 'user_registration_payment_currency', 'USD' );
				$currencies = ur_payment_integration_get_currencies();

				$field_content .= ( 'hidden' !== $args['item_type'] ) ? '<p>' . $field_label . '</p>' : '<p></p>';
				$field_content .= ( 'hidden' !== $args['item_type'] ) ? $description : '';

				$currency       = $currency . ' ' . $currencies[ $currency ]['symbol'];
				$default        = '0.00';
				$field_content .= '<div>' . $currency . '<span class="ur-total-amount">' . $default . '</span></div> <input data-id="' . esc_attr( $key ) . '" type="hidden" value="' . $default . '" class="ur-total-amount ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . $attr . ' ' . $attr_required . ' /> ';
				break;

			case 'multiple_choice':
				$field_key         = isset( $args['field_key'] ) ? $args['field_key'] : '';
				$default_value     = isset( $args['default_value'] ) ? $args['default_value'] : '';    // Backward compatibility. Modified since 1.5.7
				$default           = ! empty( $value ) ? $value : $default_value;
				$select_all        = isset( $args['select_all'] ) ? $args['select_all'] : '';
				$options           = isset( $args['options'] ) ? $args['options'] : ( $args['choices'] ? $args['choices'] : array() ); // $args['choices'] for backward compatibility. Modified since 1.5.7.
				$choice_limit      = isset( $args['choice_limit'] ) ? $args['choice_limit'] : '';
				$choice_limit_attr = '';

				if ( '' !== $choice_limit ) {
					$choice_limit_attr = 'data-choice-limit="' . $choice_limit . '"';
				}

				$choices        = isset( $options ) ? $options : array();
				$field_content .= $field_label;
				$field_content .= $description;
				$checkbox_start = 0;
				$image_class    = ( isset( $args['image_choice'] ) && ur_string_to_bool( $args['image_choice'] ) ) ? 'user-registration-image-options' : '';
				$field_content .= '<ul ' . $choice_limit_attr . ' class="' . $image_class . '">';

				if ( ur_string_to_bool( $select_all ) ) {
					$field_content .= '<li class="ur-checkbox-list"><input type="checkbox"  id="checkall" class="ur-input-checkbox"  data-check="' . esc_attr( $key ) . '"/>';
					$field_content .= '<label class="ur-checkbox-label">  Select All</label></li>';
				}

				foreach ( $choices as $choice_index => $choice ) {
					$value      = abs( isset( $choice ) ? $choice['value'] : '' );
					$currency   = get_option( 'user_registration_payment_currency', 'USD' );
					$currencies = ur_payment_integration_get_currencies();
					$currency   = $currency;

					$new_choice = '';
					$new_value  = '';
					if ( isset( $args['selling_price'] ) && ur_string_to_bool( $args['selling_price'] ) ) {
						$selling_price = abs( isset( $choice['sell_value'] ) ? $choice['sell_value'] : '' );
						$new_choice    = '<del>' . $currencies[ $currency ]['symbol'] . $value . '</del> ' . $currencies[ $currency ]['symbol'] . $selling_price;
						$new_value     = $selling_price;
					} else {
						$new_choice = $currencies[ $currency ]['symbol'] . $value;
						$new_value  = $value;
					}

					$checked = '';
					if ( '' !== $default ) {
						if ( is_array( $default ) && in_array( trim( $choice['label'] ), $default ) ) {
							$checked = 'checked="checked"';
						} elseif ( $default === $choice_index ) {
							$checked = 'checked="checked"';
						}
					}

					$field_content .= '<li class="ur-checkbox-list">';
					$field_content .= '<input data-field="' . esc_attr( $args['type'] ) . '" data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '"  data-value="' . esc_attr( $choice_index ) . '" type="checkbox" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"  data-label="' . esc_attr( $args['label'] ) . '" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $choice['label'] ) . '" value="' . esc_attr( $new_value ) . '" ' . esc_attr( $checked ) . ' /> ';
					$field_content .= '<label class="ur-checkbox-label" for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $choice['label'] ) . '">';
					if ( isset( $args['image_choice'] ) && ur_string_to_bool( $args['image_choice'] ) && isset( $choice['image'] ) && ! empty( $choice['image'] ) ) {
						$field_content .= '<span class="user-registration-image-choice">';
						$field_content .= '<img src="' . esc_url( $choice['image'] ) . '" alt="' . esc_attr( trim( $choice['label'] ) ) . '" width="200px">';
						$field_content .= '</span>';
					}
					$field_content .= trim( $choice['label'] ) . ' - ' . $currency . ' ' . trim( $new_choice ) . '</label> </li>';
					++$checkbox_start;
				}
				$field .= '</ul>';
				break;
			case 'subscription_plan':
				$field_key     = isset( $args['field_key'] ) ? $args['field_key'] : '';
				$default_value = isset( $args['default_value'] ) ? $args['default_value'] : '';    // Backward compatibility. Modified since 1.5.7
				$default       = ! empty( $value ) ? $value : $default_value;
				$options       = isset( $args['options'] ) ? $args['options'] : ( $args['choices'] ? $args['choices'] : array() ); // $args['choices'] for backward compatibility. Modified since 1.5.7.

				$choices        = isset( $options ) ? $options : array();
				$field_content .= $field_label;
				$field_content .= $description;
				$checkbox_start = 0;

				$field_content .= '<ul>';

				foreach ( $choices as $choice_index => $choice ) {
					$value      = abs( isset( $choice ) ? $choice['value'] : '' );
					$currency   = get_option( 'user_registration_payment_currency', 'USD' );
					$currencies = ur_payment_integration_get_currencies();
					$currency   = $currency;

					$new_choice = '';
					$new_value  = '';
					if ( isset( $args['selling_price'] ) && ur_string_to_bool( $args['selling_price'] ) ) {
						$selling_price = absint( isset( $choice['sell_value'] ) ? $choice['sell_value'] : '' );
						$new_choice    = '<del>' . $currencies[ $currency ]['symbol'] . $value . '</del> ' . $currencies[ $currency ]['symbol'] . $selling_price;
						$new_value     = $selling_price;
					} else {
						$new_choice = $currencies[ $currency ]['symbol'] . $value;
						$new_value  = $value;
					}

					$checked = '';
					if ( '' !== $default ) {
						if ( is_array( $default ) && in_array( trim( $choice['label'] ), $default ) ) {
							$checked = 'checked="checked"';
						} elseif ( $default === $choice_index ) {
							$checked = 'checked="checked"';
						}
					}

					$field_content .= '<li class="ur-radio-list">';
					$field_content .= '<input data-field="' . esc_attr( $args['type'] ) . '" data-rules="' . esc_attr( $rules ) . '" data-id="' . esc_attr( $key ) . '"  data-value="' . esc_attr( $choice_index ) . '" type="radio" class="input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"  data-label="' . esc_attr( $args['label'] ) . '" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $choice['label'] ) . '" value="' . esc_attr( $new_value ) . '" ' . esc_attr( $checked ) . ' /> ';
					$field_content .= '<label class="ur-radio-label" for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $choice['label'] ) . '">' . trim( $choice['label'] ) . ' - ' . $currency . ' ' . trim( $new_choice ) . '</label> </li>';
					++$checkbox_start;
				}
				$field .= '</ul>';
				break;

			case 'quantity_field':
				$field_content .= ( 'hidden' !== $args['item_type'] ) ? '<p>' . $field_label . '</p>' : '<p></p>';
				$field_content .= ( 'hidden' !== $args['item_type'] ) ? $description : '';

				$form_id       = $args['form_id'];
				$target_field  = null;
				$form_settings = json_decode( get_post( $form_id )->post_content );

				foreach ( $form_settings as $section ) {
					foreach ( $section as $row ) {
						foreach ( $row as $setting ) {
							if ( isset( $setting->field_key ) && ( 'quantity_field' == $setting->field_key ) && ( $key == $setting->general_setting->field_name ) ) {
								$target_field = $setting->advance_setting->target_field;
							}
						}
					}
				}

				$default        = apply_filters( 'user_registration_quantity_field_default_value', 0 );
				$field_content .= '<input data-id="' . esc_attr( $key ) . '" data-target="' . $target_field . '" type="number" min=0 value="' . $default . '" class="ur-quantity ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . $attr . ' ' . $attr_required . ' /> ';

				break;
		}
		echo $field_content;
	}

	/**
	 * Payment Status on User Account Payment Details tab.
	 *
	 * @return void
	 */
	public function user_registration_payment_endpoint_content() {

		$user_id = get_current_user_id();

		// Get form id.
		$form_id = get_user_meta( $user_id, 'ur_form_id', true );

		// Check if PayPal payment is enabled or not.
		$paypal_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_paypal_standard', false ) );

		// Check if Stripe payment is enabled or not.
		$stripe_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_stripe', false ) );

		// Check if Stripe payment is enabled or not.
		$anet_is_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_authorize_net', false ) );

		// Filter to check if other payments are enabled.
		$payment_is_enabled = apply_filters( 'user_registration_enable_payment', $paypal_is_enabled ? $paypal_is_enabled : ( $stripe_is_enabled || $anet_is_enabled ) );

		if ( ! $payment_is_enabled ) {
			return;
		}

		$payment_status = array(
			'ur_payment_method'       => esc_html__( 'Payment Method', 'user-registration' ),
			'ur_payment_total_amount' => esc_html__( 'Total Amount', 'user-registration' ),
		);

		$ur_payment_subscription        = get_user_meta( $user_id, 'ur_payment_subscription', true );
		$ur_payment_subscription_status = get_user_meta( $user_id, 'ur_payment_subscription_status', true );

		if ( '' !== $ur_payment_subscription ) {
			$payment_status['ur_payment_subscription_plan_name'] = esc_html__( 'Subscription Plan Name', 'user-registration' );
			$payment_status['ur_payment_subscription_status']    = esc_html__( 'Subscription Status', 'user-registration' );
			if ( 'Trial' === $ur_payment_subscription_status ) {
				$payment_status['ur_payment_subscription_trail_start'] = esc_html__( 'Trial Start Date', 'user-registration' );
				$payment_status['ur_payment_subscription_trail_end']   = esc_html__( 'Trial End Date', 'user-registration' );
			}
			$payment_status['ur_payment_subscription_expiry'] = esc_html__( 'Subscription Expiry Date', 'user-registration' );
			$payment_status['ur_payment_interval']            = esc_html__( 'Subscription Period', 'user-registration' );
		}
		$payment_status['ur_payment_status'] = esc_html__( 'Payment Status', 'user-registration' );

		$ur_payment_method = get_user_meta( $user_id, 'ur_payment_method', true );
		if ( 'paypal' === $ur_payment_method ) {
			$payment_status['ur_payment_recipient'] = esc_html__( 'Payment Recipient', 'user-registration' );
			$payment_status['ur_payment_note']      = esc_html__( 'Payment Note', 'user-registration' );
		}
		$ur_paypal_subscription = ur_string_to_bool( get_user_meta( $user_id, 'ur_paypal_subscription_enabled', true ) );

		if ( $ur_paypal_subscription ) {
			$payment_status['ur_paypal_subscription_plan_name'] = esc_html__( 'Subscription Plan Name', 'user-registration' );
			$payment_status['ur_paypal_subscription_status']    = esc_html__( 'Subscription Status', 'user-registration' );
			$payment_status['ur_payment_subscription_expiry']   = esc_html__( 'Subscription Expiry Date', 'user-registration' );
			$payment_status['ur_paypal_recurring_period']       = esc_html__( 'Subscription Period', 'user-registration' );
		}
		do_action( 'user_registration_before_payment_details', $payment_status );

		$layout = get_option( 'user_registration_my_account_layout', 'horizontal' );

		if ( 'vertical' === $layout && isset( ur_get_account_menu_items()['payment'] ) ) {
			?>
			<div class="user-registration-MyAccount-content__header">
				<h1><?php echo wp_kses_post( ur_get_account_menu_items()['payment'] ); ?></h1>
			</div>
			<?php
		}
		?>
		<div class="user-registration-MyAccount-content__body">
			<div class="ur-payments-container">
				<div class="ur-payments-details">
					<table class="ur-payments-table">
						<?php

						$payment_method = get_user_meta( $user_id, 'ur_payment_method', true );
						if ( '' != $payment_method ) {

							$subscription_status = '';
							$subscription_id     = get_user_meta( $user_id, 'ur_payment_subscription', true );
							$customerid          = get_user_meta( $user_id, 'ur_payment_customer', true );
							foreach ( $payment_status as $meta_key => $label ) {

								$value = get_user_meta( $user_id, $meta_key, true );

								if ( 'ur_payment_subscription_status' === $meta_key ) {
									$subscription_status = $value;
									$value               = 'cancel_at_end_of_cycle' === $value ? 'active' : $value;
								} elseif ( 'ur_payment_subscription_expiry' === $meta_key && $payment_method != 'credit_card' ) {
									$u_data            = get_userdata( $user_id );
									$last_update       = $u_data->user_registered;
									$period            = get_user_meta( $user_id, 'ur_paypal_recurring_period', true );
									$interval          = get_user_meta( $user_id, 'ur_paypal_interval_count', true );
									$payment_date      = date( 'Y/m/d H:i:s', strtotime( $last_update ) );
									$exact_expiry_date = get_user_meta( $user_id, 'ur_payment_subscription_expiry', true );
									if ( 'completed' === get_user_meta( $user_id, 'ur_payment_status', true ) ) {
										if ( $exact_expiry_date ) {
											// Convert date format to display in my Account.
											$db_date = DateTime::createFromFormat( 'F j, Y H:i:s', $exact_expiry_date );
											$value   = $db_date->format( 'Y/m/d H:i:s' );
										} else {
											$value = date( 'Y/m/d H:i:s', strtotime( '+' . $interval . $period, strtotime( $payment_date ) ) );
										}
									}
								} elseif ( 'ur_payment_method' === $meta_key ) {
									$value = ( 'credit_card' == $value ) ? __( 'Stripe ( Credit Card )', 'user-registration' ) : $value;
									$value = ( 'ideal' == $value ) ? __( 'Stripe ( iDEAL )', 'user-registration' ) : $value;
									$value = ( 'paypal_standard' == $value ) ? __( 'PayPal Standard', 'user-registration' ) : $value;
								} elseif ( 'ur_payment_total_amount' === $meta_key ) {
									$currencies = ur_payment_integration_get_currencies();
									$currency   = get_user_meta( $user_id, 'ur_payment_currency', true );
									$value      = $currencies[ $currency ]['symbol'] . '' . $value . ' ' . $currency;
								}

								?>
								<tr class="ur-payment-table-row">
									<th><label for="<?php echo esc_attr( $meta_key ); ?>"> <?php echo esc_html( $label ); ?></label></th>
									<td>
									<?php
									if ( 'active' === $value ) {
										echo '<i class="ur_eclipse"></i><span class="ur_payment_active">' . ucfirst( $value ) . '</span>';
									} else {
										echo esc_html( ucfirst( $value ) );
									}
									?>
									</td>
								</tr>
								<?php
							}
						} else {
							echo '<tr><th><label>' . esc_html__( 'Payments Details not available.', 'user-registration' ) . '</label></th></tr>';
						}
						?>
					</table>

					<?php
					$url              = ( ! empty( $_SERVER['HTTPS'] ) ) ? 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] : 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
					$url              = substr( $url, 0, strpos( $url, '?' ) );
					$cancellation_url = wp_nonce_url( $url . '?stripe_action=cancellation&subscriptionid=' . $subscription_id, 'ur_stripe_action' );
					$reactivation_url = wp_nonce_url( $url . '?stripe_action=reactivation&subscriptionid=' . $subscription_id, 'ur_stripe_action' );
					if ( ur_string_to_bool( $stripe_is_enabled ) ) {
						$recurring_is_enabled = ur_get_single_post_meta( $form_id, 'user_registration_enable_stripe_recurring', '0' );
						if ( ur_string_to_bool( $recurring_is_enabled ) && ( 'active' === $subscription_status || 'cancel_at_end_of_cycle' === $subscription_status ) ) {
							?>
							<br/>
								<div class="ur-payment-actions"	>
									<?php
									if ( 'cancel_at_end_of_cycle' === $subscription_status ) {
										?>
										<input type="hidden" id="user_registraition_stripe_reactivate_url" value="<?php echo esc_url( $reactivation_url ); ?>"/>
										<a id="ur_reactivate_payment"> <?php esc_html_e( 'Reactivate', 'user-registration' ); ?></a>
										<?php
									} else {
										?>
										<input type="hidden" id="user_registraition_stripe_cancel_url" value="<?php echo esc_url( $cancellation_url ); ?>"/>
										<a id="ur_cancel_payment"> <?php esc_html_e( 'Cancel', 'user-registration' ); ?></a>
										<?php
									}
									?>
									<a id="ur_change_payment"> <?php esc_html_e( 'Change Payment', 'user-registration' ); ?> </a>
								</div>
							<?php
						}
					}
					?>
				</div>

				<?php
				$payment_invoice = get_user_meta( $user_id, 'ur_payment_invoices', true );
				if ( ! empty( $payment_invoice ) ) {
					?>
					<div class="ur-payment-invoices">
						<table class="ur-payment-invoice-table">
							<tr>
								<th>
									<?php esc_html_e( 'Invoice Date', 'user-registration' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Invoice No', 'user-registration' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Total', 'user-registration' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'PDF', 'user-registration' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Status', 'user-registration' ); ?>
								</th>
							</tr>

							<?php
							foreach ( $payment_invoice as $key => $invoice ) {
								?>
							<tr>
								<td>
									<?php
									/**
									 * Filter to modify the payment invoice date.
									 *
									 * @since xx.xx.xx
									 */
									$invoice_date = apply_filters( 'user_registration_payment_invoice_date', $invoice['invoice_date'] );
									echo wp_kses_post( $invoice_date );
									?>
								</td>
								<td style="width:140px">
									<?php echo wp_kses_post( $invoice['invoice_no'] ); ?>
								</td>
								<td>
									<?php echo wp_kses_post( $invoice['invoice_currency'] . ' ' . $invoice['invoice_amount'] ); ?>
								</td>
								<td>
									<?php
									$url          = ( ! empty( $_SERVER['HTTPS'] ) ) ? 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] : 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
									$url          = substr( $url, 0, strpos( $url, '?' ) );
									$download_url = wp_nonce_url( $url . '?payment_action=invoice_download&transaction_id=' . $invoice['invoice_no'], 'ur_payment_action' );

									?>
									<a id="ur_download_payment_invoice" href="<?php echo esc_url( $download_url ); ?>"> <?php esc_html_e( 'Download', 'user-registration' ); ?> </a>
								</td>
								<td>
									<?php echo wp_kses_post( $invoice['invoice_status'] ); ?>
								</td>
							</tr>
								<?php
							}
							?>
						</table>
					</div>
					<?php
				}
				?>
				<div class="ur-change-payment-container">
				<?php
				do_action( 'user_registration_before_change_payment_form' );
				?>
					<div class="ur-frontend-form change-payment" id="ur-frontend-form">
						<div class="ur-form-row">
							<div class="ur-form-grid">
								<div class="ur-change-payment-title">
									<label><?php esc_html_e( 'Change Your Card Information here', 'user-registration' ); ?></label>
								</div>
								<div  class="ur-card-information-box">
									<label class="ur-change-payment-label"><?php esc_html_e( 'Card Information', 'user-registration' ); ?></label>
									<div class="user-registration-change-payment">
									</div>
								</div>
								<div class="clear"></div>
								<div class="user-registration-save-change-payment">
									<?php wp_nonce_field( 'save_change_payment' ); ?>
									<span></span>
									<input type="button" class="ur-change-payment-update-button" id="save_change_payment" value="<?php esc_attr_e( 'Update', 'user-registration' ); ?>"/>
								</div>
							</div>
						</div>
					</div>

				<?php
				do_action( 'user_registration_after_change_payment_form' );
				?>

				</div>
			</div>
			<?php
			$payment_status['form_id'] = $form_id;
			do_action( 'user_registration_after_payment_details', $payment_status );
			?>
		</div>
		<?php
	}

	/**
	 * Add the item to the $items array
	 *
	 * @param mixed $items Items.
	 * @return $items
	 */
	public function payment_item_tab( $items ) {
		$new_items            = array();
		$new_items['payment'] = __( 'Payment Details', 'user-registration' );

		return $this->payment_insert_after_helper( $items, $new_items, 'edit-profile' );
	}

	/**
	 * Payment insert after helper.
	 *
	 * @param mixed $items Items.
	 * @param mixed $new_items New items.
	 * @param mixed $after After item.
	 */
	public function payment_insert_after_helper( $items, $new_items, $after ) {

		// Search for the item position and +1 since is after the selected item key.
		$position = array_search( $after, array_keys( $items ), true ) + 1;

		// Insert the new item.
		$return_items  = array_slice( $items, 0, $position, true );
		$return_items += $new_items;
		$return_items += array_slice( $items, $position, count( $items ) - $position, true );

		return $return_items;
	}
}

new User_Registration_Payments_Frontend();
