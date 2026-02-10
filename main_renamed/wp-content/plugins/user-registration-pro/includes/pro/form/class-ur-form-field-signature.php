<?php
/**
 * UR_Form_Field_Captcha.
 *
 * @package  UserRegistrationPro/Form
 * @category Admin
 * @author   WPEverest
 *
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * UR_Form_Field_Signature Class
 */
class UR_Form_Field_Signature extends UR_Form_Field {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $_instance;

	/**
	 * Get Instance of this class.
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

		$this->id = 'user_registration_signature';

		$this->form_id = 1;

		$this->registered_fields_config = array(

			'label' => __( 'Signature', 'user-registration' ),

			'icon'  => 'ur-icon ur-icon-e-signature',
		);
		$this->field_defaults           = array(

			'default_label'      => __( 'Signature', 'user-registration' ),

			'default_field_name' => 'signature_' . ur_get_random_number(),

		);

		add_filter( "{$this->id}_advance_class", array( $this, 'settings_override' ), 10, 1 );
	}

	public function settings_override( $file_path_override ) {
		$file_path_override['file_path'] = UR_ABSPATH . 'includes' . UR_DS . 'pro' . UR_DS . 'form' . UR_DS . 'settings' . UR_DS . 'class-ur-setting-signature.php';
		return $file_path_override;
	}

	/**
	 * Get registered admin fields.
	 */
	public function get_registered_admin_fields() {

		return '<li
				id="' . $this->id . '_list "
				class="ur-registered-item draggable"
                data-field-id="' . $this->id . '"><span class="' . $this->registered_fields_config['icon'] . '"></span>' .
				$this->registered_fields_config['label'] .
				'</li>';
	}


	/**
	 * Validate signature field.
	 *
	 * @param mixed $single_form_field Single form field.
	 * @param mixed $form_data Form Data.
	 * @param mixed $filter_hook Filter hook.
	 * @param int   $form_id Form id.
	 */
	public function validation( $single_form_field, $form_data, $filter_hook, $form_id ) {
		// Custom field validation here.
	}
}

return UR_Form_Field_Signature::get_instance();
