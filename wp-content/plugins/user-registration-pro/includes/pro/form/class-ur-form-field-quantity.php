<?php
/**
 * UserRegistrationPayments Standard Quantity Field.
 *
 * @class    UR_Form_Field_Quantity_Field
 * @version  1.2.0
 * @package  UserRegistrationPayments/Form
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UR_Form_Field_Quantity_Field Class
 */
class UR_Form_Field_Quantity_Field extends UR_Form_Field {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $_instance;

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		$this->id = 'user_registration_quantity_field';

		$this->form_id = 1;

		$this->registered_fields_config = array(

			'label' => __( 'Quantity', 'user-registration' ),

			'icon'  => 'ur-icon ur-icon-quantity',
		);
		$this->field_defaults           = array(

			'default_label'      => __( 'Quantity', 'user-registration' ),

			'default_field_name' => 'quantity_field_' . ur_get_random_number(),
		);
	}


	/**
	 * Get registered admin fields.
	 *
	 * @return string
	 */
	public function get_registered_admin_fields() {

		return '<li id="' . $this->id . '_list "

				class="ur-registered-item draggable"

                data-field-id="' . $this->id . '"><span class="' . $this->registered_fields_config['icon'] . '"></span>' . $this->registered_fields_config['label'] . '</li>';
	}

	/**
	 * Get advance setting data.
	 *
	 * @param  mixed $key Key.
	 * @return mixed
	 */
	public function get_advance_setting_data( $key ) {
		if ( isset( $this->admin_data->advance_setting->$key ) ) {
			return $this->admin_data->advance_setting->$key;
		}

		if ( isset( $this->field_defaults[ 'default_' . $key ] ) ) {
			return $this->field_defaults[ 'default_' . $key ];
		}

		return '';
	}


	/**
	 * Quantity Field validation.
	 *
	 * @param mixed $single_form_field Total field.
	 * @param mixed $form_data Form Data.
	 * @param mixed $filter_hook Filter Hook.
	 * @param int   $form_id Form ID.
	 */
	public function validation( $single_form_field, $form_data, $filter_hook, $form_id ) {
		// Custom Field Validation here..
	}
}

return UR_Form_Field_Quantity_Field::get_instance();
