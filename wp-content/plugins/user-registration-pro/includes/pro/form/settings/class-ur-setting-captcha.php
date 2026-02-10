<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * UR_Setting_Captcha Class
 *
 * @package  UserRegistrationPro/Form/Settings
 * @category Abstract Class
 * @author   WPEverest
 * @since 4.1.0
 */

class UR_Setting_Captcha extends UR_Field_Settings {

	public function __construct() {
		$this->field_id = 'captcha_advance_setting';
	}

	public function output( $field_data = array() ) {

		$this->field_data = $field_data;
		$this->register_fields();
		$field_html = $this->fields_html;

		return $field_html;
	}

	public function register_fields() {
		$fields = array(
			'captcha_message' => array(
				'label'       =>  esc_html__( 'Captcha Message', 'user-registration' ),
				'data-id'     => $this->field_id . '_captcha_message',
				'name'        => $this->field_id . '[captcha_message]',
				'class'       => $this->default_class . ' ur-settings-captcha_message',
				'type'        => 'text',
				'required'    => false,
				'default'     => __( 'Incorrect Answer', 'user-registration' ),
				'placeholder' => __( 'Enter captcha message', 'user-registration' ),
				'tip'         => __( 'If the captcha answer does not match. it will show this message', 'user-registration' ),
			),
			'custom_class' => array(
				'label'       => esc_html__( 'Custom Class', 'user-registration-advanced-fields' ),
				'data-id'     => $this->field_id . '_custom_class',
				'name'        => $this->field_id . '[custom_class]',
				'class'       => $this->default_class . ' ur-settings-custom-class',
				'type'        => 'text',
				'required'    => false,
				'default'     => '',
				'placeholder' => esc_html__( 'Custom Class', 'user-registration' ),
				'tip'         => __( 'Custom CSS class to embed in this field.', 'user-registration' ),
			),
		);

		$fields = apply_filters( 'user_registration_captcha_field_advance_settings', $fields, $this->field_id, $this->default_class );
		$this->render_html( $fields );
	}
}

return new UR_Setting_Captcha();
