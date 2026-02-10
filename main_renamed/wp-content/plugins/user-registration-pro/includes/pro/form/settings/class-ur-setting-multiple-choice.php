<?php
/**
 * UR_Setting_Multiple_Choice_Payment Class.
 *
 * @since  1.2.0
 * @package  UserRegistrationPayments/Form/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UR_Setting_Multiple_Choice_Payment.
 */
class UR_Setting_Multiple_Choice extends UR_Field_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->field_id = 'mulitple_choice_advance_setting';
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
			'choice_limit' => array(
				'label'       => __( 'Choice Limit', 'user-registration' ),
				'data-id'     => $this->field_id . '_choice_limit',
				'name'        => $this->field_id . '[choice_limit]',
				'class'       => $this->default_class . ' ur-settings-min',
				'type'        => 'number',
				'required'    => false,
				'default'     => '',
				'placeholder' => __( 'Choice Limit', 'user-registration' ),
				'tip'         => __( 'Enter maximum number choices that can be selected.', 'user-registration' ),
			),
			'select_all'   => array(
				'label'       => __( 'Select All ', 'user-registration' ),
				'data-id'     => $this->field_id . '_select_all',
				'name'        => $this->field_id . '[select_all]',
				'class'       => $this->default_class . ' ur-settings-select',
				'type'        => 'toggle',
				'required'    => false,
				'default'     => 'false',
				'placeholder' => '',
				'tip'         => __( 'Enable this option to select all the options', 'user-registration' ),
			),
		);

		$this->render_html( $fields );
	}
}

return new UR_Setting_Multiple_Choice();
