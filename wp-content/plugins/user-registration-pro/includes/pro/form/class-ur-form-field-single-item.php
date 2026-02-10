<?php
/**
 * UserRegistrationPayments Standard Single Item.
 *
 * @class    UR_Form_Field_Single_Item
 * @version  1.0.0
 * @package  UserRegistrationPayments/Form
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UR_Form_Field_Single_Item Class
 */
class UR_Form_Field_Single_Item extends UR_Form_Field {

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

		$this->id = 'user_registration_single_item';

		$this->form_id = 1;

		$this->registered_fields_config = array(

			'label' => __( 'Single Item', 'user-registration' ),

			'icon'  => 'ur-icon ur-icon-file-dollar',
		);
		$this->field_defaults           = array(

			'default_label'      => __( 'Single Item', 'user-registration' ),

			'default_field_name' => 'single_item_' . ur_get_random_number(),
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
	 * Single Items Field validation.
	 *
	 * @param mixed $single_form_field Single field.
	 * @param mixed $form_data Form Data.
	 * @param mixed $filter_hook Filter Hook.
	 * @param int   $form_id Form ID.
	 */
	public function validation( $single_form_field, $form_data, $filter_hook, $form_id ) {
		// Custom Field Validation here..
	}
}

return UR_Form_Field_Single_Item::get_instance();
