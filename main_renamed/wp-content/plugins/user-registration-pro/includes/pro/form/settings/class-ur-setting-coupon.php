<?php
/**
 * UR_Setting_Quantity_Field Class.
 *
 * @since  1.2.0
 * @package  UserRegistrationPayments/Form/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UR_Setting_Quantity_Field Class.
 */
class UR_Setting_Coupon extends UR_Field_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->field_id = 'coupon_field_advance_setting';
	}

	/**
	 * Render output.
	 *
	 * @param array $field_data Field Data.
	 */
	public function output( $field_data = array() ) {

		$this->field_data = $field_data;

		$this->register_fields();

		$field_html = $this->fields_html;

		return $field_html;
	}

	/**
	 * Get Register fields.
	 */
	public function register_fields() {
		$fields = array(
			'invalid_coupon_message' => array(
				'label'       => __( 'Invalid Coupon Message', 'user-registration' ),
				'data-id'     => $this->field_id . '_invalid_coupon_message',
				'name'        => $this->field_id . '[invalid_coupon_message]',
				'class'       => $this->default_class . ' ur-coupon-settings-invalid_coupon_message',
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => '',
				'tip'         => __( 'Add text to be displayed when coupon code is invalid.', 'user-registration' ),
			),
			// ,
			// 'target_field' => array(
			// 	'label'       => __( 'Target Field', 'user-registration' ),
			// 	'data-id'     => $this->field_id . '_target_field',
			// 	'name'        => $this->field_id . '[target_field]',
			// 	'class'       => $this->default_class . ' ur-coupon-settings-target_field',
			// 	'type'        => 'select',
			// 	'required'    => true,
			// 	'default'     => '0',
			// 	'placeholder' => '',
			// 	'options'     => $this->get_payment_items(),
			// 	'tip'         => __( 'Select the target field for applying the coupon.', 'user-registration' ),
			// )
		);
		$this->render_html( $fields );
	}

	/**
	 * Return array of payment fields of the form
	 *
	 * @return array
	 */
	public function get_payment_items() {
		$form_id = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;

		$form_settings  = ! empty( get_post( $form_id ) ) ? json_decode( get_post( $form_id )->post_content ) : array();
		$payment_fields = array( '' => __( '-- Select target field --', 'user-registration' ) );
		
		foreach ( $form_settings as $section ) {
			foreach ( $section as $row ) {
				foreach ( $row as $setting ) {
					if ( isset( $setting->field_key ) && in_array( $setting->field_key, array(
							'single_item',
							'total_field'
						) ) ) {
						$payment_fields[ $setting->general_setting->field_name ] = $setting->general_setting->label;
					}
				}
			}
		}


		return $payment_fields;
	}
}

return new UR_Setting_Coupon();
