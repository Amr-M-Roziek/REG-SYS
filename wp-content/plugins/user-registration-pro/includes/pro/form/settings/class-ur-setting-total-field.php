<?php
/**
 * UR_Setting_Total_Field Class.
 *
 * @since  1.2.0
 * @package  UserRegistrationPayments/Form/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UR_Setting_Total_Field Class.
 */
class UR_Setting_Total_Field extends UR_Field_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->field_id = 'total_field_advance_setting';
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

			'custom_class' => array(
				'label'       => __( 'Custom Class', 'user-registration' ),
				'data-id'     => $this->field_id . '_custom_class',
				'name'        => $this->field_id . '[custom_class]',
				'class'       => $this->default_class . ' ur-settings-custom-class',
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => __( 'Custom Class', 'user-registration' ),
				'tip'         => __( 'Class name to embed in this field.', 'user-registration' ),
			),
		);

		$this->render_html( $fields );
	}
}

return new UR_Setting_Total_Field();
