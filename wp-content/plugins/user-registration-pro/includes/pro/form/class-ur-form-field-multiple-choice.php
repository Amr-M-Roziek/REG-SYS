<?php
/**
 * @class UR_Form_Field_Multiple_Choice.
 * @version 1.2.0
 * @package  UserRegistration/Form
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * UR_Form_Field_Multiple_Choice Class
 */
class UR_Form_Field_Multiple_Choice extends UR_Form_Field {

	private static $_instance;

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

		$this->id                       = 'user_registration_multiple_choice';
		$this->form_id                  = 1;
		$this->registered_fields_config = array(
			'label' => __( 'Multiple Choice', 'user-registration' ),
			'icon'  => 'ur-icon ur-icon-multichoice',
		);

		$this->field_defaults = array(
			'default_label'      => __( 'Multiple Choice', 'user-registration' ),
			'default_field_name' => 'multiple_choice_' . ur_get_random_number(),
			'default_options'    => array(
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
		);
	}


	public function get_registered_admin_fields() {

		return '<li id="' . $this->id . '_list " class="ur-registered-item draggable" data-field-id="' . $this->id . '"><span class="' . $this->registered_fields_config['icon'] . '"></span>' . $this->registered_fields_config['label'] . '</li>';
	}


	public function validation( $single_form_field, $form_data, $filter_hook, $form_id ) {
		// Custom Field Validation here..
	}
}

return UR_Form_Field_Multiple_Choice::get_instance();
