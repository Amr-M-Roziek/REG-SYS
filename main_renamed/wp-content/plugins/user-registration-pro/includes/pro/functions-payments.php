<?php
/**
 * UserRegistrationPayments Functions.
 *
 * General core functions available on both the front-end and admin.
 *
 * @package UserRegistrationPayments/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'user_registration_field_keys', 'ur_get_payment_field_type', 10, 2 );
add_filter( 'user_registration_single_item_admin_template', 'ur_add_single_item_template' );
add_filter( 'user_registration_total_field_admin_template', 'ur_add_total_field_template' );
add_filter( 'user_registration_multiple_choice_admin_template', 'ur_add_multiple_choice_payment_template' );
add_filter( 'user_registration_subscription_plan_admin_template', 'ur_add_subscription_plan_payment_template' );
add_filter( 'user_registration_quantity_field_admin_template', 'ur_add_quantity_field_template' );
add_filter( 'user_registration_sanitize_field', 'ur_sanitize_payment_fields', 10, 2 );
add_filter( 'user_registration_payments_currencies', 'ur_support_extra_currencies' );

add_filter( 'user_registration_form_field_single_item_path', 'ur_add_single_item_field' );
add_filter( 'user_registration_form_field_total_field_path', 'ur_add_total_field' );
add_filter( 'user_registration_form_field_multiple_choice_path', 'ur_add_multiple_choice_payment_field' );
add_filter( 'user_registration_form_field_subscription_plan_path', 'ur_add_subscription_plan_payment_field' );
add_filter( 'user_registration_form_field_quantity_field_path', 'ur_add_quantity_field' );

// Register coupon field
// add_filter( 'user_registration_form_field_coupon', 'ur_register_coupon_field' );
if ( ur_check_module_activation( 'coupon' ) ) {
	add_filter( 'user_registration_form_field_coupon_path', 'ur_add_coupon_field_path' );
	add_filter( 'user_registration_coupon_admin_template', 'ur_add_coupon_template' );
	add_filter( 'user_registration_coupon_field_advance_class', 'coupon_field_advance_settings' );
}


/**
 * Add coupon field path
 */
function ur_add_coupon_field_path() {

		include_once __DIR__ . '/form/class-ur-form-field-coupon.php';
}

/**
 * Captcha field template
 *
 * @return  string
 */
function ur_add_coupon_template() {

	$path = __DIR__ . '/form/views/admin/admin-coupon-field.php';

	return $path;
}

/**
 * Sanitize payment fields on frontend submit
 *
 * @param mixed  $form_data Form Data.
 * @param string $field_key Field Key.
 *
 * @return array
 */
function ur_sanitize_payment_fields( $form_data, $field_key ) {
	switch ( $field_key ) {
		case 'single_item':
			$form_data->value = user_registration_sanitize_amount( $form_data->value, 'USD' );
			break;
	}

	return $form_data;
}

/**
 * Add single item field
 */
function ur_add_single_item_field() {
	include_once __DIR__ . '/form/class-ur-form-field-single-item.php';
}

/**
 * Add total field
 */
function ur_add_total_field() {
	include_once __DIR__ . '/form/class-ur-form-field-total.php';
}

/*
 * Add Multiple Choice Payment field
 */
function ur_add_multiple_choice_payment_field() {
	include_once __DIR__ . '/form/class-ur-form-field-multiple-choice.php';
}

/*
 * Add Subscription Plan field
 */
function ur_add_subscription_plan_payment_field() {
	include_once __DIR__ . '/form/class-ur-form-field-subscription-plan.php';
}

/**
 * Add quantity field
 */
function ur_add_quantity_field() {
	include_once __DIR__ . '/form/class-ur-form-field-quantity.php';
}

/**
 * Single item field template
 *
 * @return  string
 */
function ur_add_single_item_template() {
	$path = __DIR__ . '/form/views/admin/admin-single-item.php';

	return $path;
}

/**
 * Total field template
 *
 * @return  string
 */
function ur_add_total_field_template() {
	$path = __DIR__ . '/form/views/admin/admin-total-field.php';

	return $path;
}

/*
 * Multiple Choice field template
 *
 * @return  string
 */
function ur_add_multiple_choice_payment_template() {
	$path = __DIR__ . '/form/views/admin/admin-multiple-choice.php';

	return $path;
}

/*
 * Subscription Plan field template
 *
 * @return  string
 */
function ur_add_subscription_plan_payment_template() {
	$path = __DIR__ . '/form/views/admin/admin-subscription-plan.php';

	return $path;
}

/*
 * Quantity field template
 *
 * @return  string
 */
function ur_add_quantity_field_template() {
	$path = __DIR__ . '/form/views/admin/admin-quantity-field.php';

	return $path;
}

/**
 * Assign field type to single item
 *
 * @param string $field_type Field Type.
 * @param string $field_key Field Key.
 *
 * @return string
 */
function ur_get_payment_field_type( $field_type, $field_key ) {

	if ( 'single_item' === $field_key ) {
		$field_type = 'single_item';
	}
	if ( 'total_field' === $field_key ) {
		$field_type = 'total_field';
	}
	if ( 'multiple_choice' === $field_key ) {
		$field_type = 'multiple_choice';
	}
	if ( 'subscription_plan' === $field_key ) {
		$field_type = 'subscription_plan';
	}
	if ( 'quantity_field' === $field_key ) {
		$field_type = 'quantity_field';
	}

	return $field_type;
}

/**
 * All payment fields
 *
 * @return  array
 */
function user_registration_payment_fields() {
	return apply_filters(
		'user_registration_payment_fields',
		array(
			'single_item',
			'total_field',
			'multiple_choice',
			'subscription_plan',
			'quantity_field',
		)
	);
}



/**
 * Check if range is payment slider
 *
 * @param string $field_name Field Name.
 * @param int    $form_id Form ID.
 *
 * @return  boolean $payment_slider
 * @since 1.1.4
 */
function check_is_range_payment_slider( $field_name, $form_id ) {
	$post_content_array = ( $form_id ) ? UR()->form->get_form( $form_id, array( 'content_only' => true ) ) : array();
	$payment_slider     = false;

	if ( ! is_null( $post_content_array ) ) {
		foreach ( $post_content_array as $post_content_row ) {
			foreach ( $post_content_row as $post_content_grid ) {
				foreach ( $post_content_grid as $fields ) {
					if ( isset( $fields->general_setting->field_name ) && $field_name === $fields->general_setting->field_name && 'range' === $fields->field_key && ( isset( $fields->advance_setting->enable_payment_slider ) && ur_string_to_bool( $fields->advance_setting->enable_payment_slider ) ) ) {
						$payment_slider = true;
					}
				}
			}
		}
	}

	return $payment_slider;
}

/**
 * Support Extra currencies
 *
 * @param array $currencies currency.
 *
 * @return array $currencies.
 * @since 1.4.3
 */
function ur_support_extra_currencies( $currencies ) {
	$extra_currencies = array(
		'CNY' => array(
			'name'                => esc_html__( 'Chinese Renmenbi ', 'user-registration' ),
			'symbol'              => '&yen;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'RON' => array(
			'name'                => esc_html__( 'Romanian Leu', 'user-registration' ),
			'symbol'              => 'lei',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'HRK' => array(
			'name'                => esc_html__( 'Croatian kuna', 'user-registration' ),
			'symbol'              => 'kn',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'INR' => array(
			'name'                => esc_html__( 'Indian rupee', 'user-registration' ),
			'symbol'              => '&#8377;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'TRY' => array(
			'name'                => esc_html__( 'Turkish lira', 'user-registration' ),
			'symbol'              => '&#8378;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'NGN' => array(
			'name'                => esc_html__( 'Nigerian naira', 'user-registration' ),
			'symbol'              => '&#8358;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'ZMW' => array(
			'name'                => esc_html__( 'Zambian Kwacha', 'user-registration' ),
			'symbol'              => 'ZK',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'GHS' => array(
			'name'                => esc_html__( 'Ghanaian cedi', 'user-registration' ),
			'symbol'              => 'GH&#8373;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
	);

	$currencies = array_merge( $currencies, $extra_currencies );

	return $currencies;
}

if ( ! function_exists( 'ur_add_enable_selling_price_options' ) ) {
	/**
	 * Enable Discount price options.
	 */
	function ur_add_enable_selling_price_options( $general_setting, $id ) {

		if ( 'user_registration_multiple_choice' === $id ) {
			$setting_array   = array(
				'setting_id'  => 'selling-price',
				'type'        => 'toggle',
				'label'       => __( 'Enable Selling Price', 'user-registration' ),
				'name'        => 'ur_general_setting[selling_price]',
				'placeholder' => '',
				'required'    => true,
				'default'     => 'false',
				'tip'         => __( 'Check this option to enable selling price of this field.', 'user-registration' ),
			);
			$index           = array_search( 'description', array_keys( $general_setting ) );
			$general_setting = array_slice( $general_setting, 0, $index + 1, true ) + array( 'selling_price' => $setting_array ) + array_slice( $general_setting, $index + 1, null, true );
		}
		if ( 'user_registration_subscription_plan' === $id ) {
			$setting_array   = array(
				'setting_id'  => 'selling-price',
				'type'        => 'toggle',
				'label'       => __( 'Enable Selling Price', 'user-registration' ),
				'name'        => 'ur_general_setting[selling_price]',
				'placeholder' => '',
				'required'    => true,
				'default'     => 'false',
				'tip'         => __( 'Check this option to enable selling price of this field.', 'user-registration' ),
			);
			$index           = array_search( 'description', array_keys( $general_setting ) );
			$general_setting = array_slice( $general_setting, 0, $index + 1, true ) + array( 'selling_price' => $setting_array ) + array_slice( $general_setting, $index + 1, null, true );
		}

		return $general_setting;
	}
}
add_filter( 'user_registration_field_options_general_settings', 'ur_add_enable_selling_price_options', 10, 2 );

if ( ! function_exists( 'ur_pro_generate_pdf_file' ) ) {

	/**
	 * Generate pdf file for user.
	 *
	 * @param string $html HTML content.
	 */
	function ur_pro_generate_pdf_file( $html ) {

		$paper_size  = 'A4';
		$orientation = 'portrait';
		$fontname    = ur_add_pdf_fonts();
		$fontname    = apply_filters( 'user_registration_add_font', $fontname );
		$font_size   = 12;
		$rtl         = false;

		$tcpdf = new TCPDF( $orientation, PDF_UNIT, $paper_size, true, 'UTF-8', false );

		$tcpdf->setPrintHeader( false );
		$tcpdf->setPrintFooter( false );
		$tcpdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
		$tcpdf->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );
		$tcpdf->addPage( '', $paper_size );
		$tcpdf->SetFont( 'dejavusans', '', 10 );
		$tcpdf->setRtl( $rtl );
		$tcpdf->writeHTML( $html, true, false, true, false, '' );

		return $tcpdf;
	}
}
