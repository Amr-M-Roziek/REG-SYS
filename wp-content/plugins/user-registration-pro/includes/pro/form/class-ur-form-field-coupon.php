<?php
/**
 * UR_Form_Field_Coupon.
 *
 * @package  UserRegistrationPro/Form
 * @category Admin
 * @author   WPEverest
 *
 * @since 4.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * UR_Form_Field_Captcha Class
 */
class UR_Form_Field_Coupon extends UR_Form_Field {

	/**
	 * Coupon Field Key.
	 *
	 * @var string
	 */
	public $field_key;

	/**
	 * Coupon Type.
	 *
	 * @var string
	 */
	public $type;

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
	 * Constructor.
	 */
	public function __construct() {

		$this->id                       = 'user_registration_coupon';
		$this->form_id                  = 1;
		$this->field_key                = 'coupon';
		$this->type                     = 'coupon';
		$this->registered_fields_config = array(
			'label' => __( 'Coupon', 'user-registration-advanced-fields' ),
			'icon'  => 'ur-icon ur-icon-coupon-field',
		);

		$this->field_defaults = array(
			'default_label'      => __( 'Coupon', 'user-registration' ),
			'default_field_name' => 'coupon_' . ur_get_random_number(),
		);

		add_filter( 'user_registration_form_field_coupon', array( $this, 'render_coupon_field' ), 10, 4 );
	}

	/**
	 * Render Coupon field.
	 *
	 * @param mixed  $field Fields Data.
	 * @param string $key Key.
	 * @param mixed  $args Arguments.
	 * @param mixed  $value Value.
	 */
	public function render_coupon_field( $field, $key, $args, $value ) {

		return '
					<div class="form-row">
						<p><label class="ur-label" for="Total">Coupon</label></p>
						<label for="' . $key . '" class="coupon-label">
						<input id="' . $key . '"  data-id="' . $key . '" type="text" class="ur_coupon_field ur-frontend-field  " name="' . $key . '" id="' . $this->field_defaults['default_field_name'] . '">
						<span class="clear-coupon">x</span>
						</label>
						<span id="coupon-error" class="user-registration-coupon-error coupon-message" ></span>
						<span id="coupon-success" class="coupon-message"></span>
					</div>
					<button type="button" class="btn button ur-apply-coupon-btn" >
						Apply Coupon</button>
					';
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
	 * Validate Captcha field.
	 *
	 * @param mixed $single_form_field Single form field.
	 * @param mixed $form_data Form Data.
	 * @param mixed $filter_hook Filter hook.
	 * @param int   $form_id Form id.
	 */
	public function validation( $single_form_field, $form_data, $filter_hook, $form_id ) {

		$field_label    = $single_form_field->general_setting->field_name;
		$value          = isset( $form_data->value ) ? $form_data->value : '';
		$coupon_details = ur_get_coupon_details( $value );
		$required       = $single_form_field->general_setting->required;

		if ( ! ur_string_to_bool( $required ) ) {
			return;
		}

		$message = array(
			/* translators: %s - validation message */
			$field_label => ! empty( $single_form_field->advance_setting->invalid_coupon_message ) ? esc_html__( $single_form_field->advance_setting->invalid_coupon_message ) : __( 'Invalid Coupon.', 'user-registration' ),
			'individual' => true,
		);

		if ( empty( $coupon_details ) || 'form' !== $coupon_details['coupon_for'] || ! in_array( $form_id, json_decode( $coupon_details['coupon_form'], true ) ) || $coupon_details['coupon_start_date'] > date( 'Y-m-d' ) ) {

			add_filter(
				$filter_hook,
				function ( $msg ) use ( $message, $form_data ) {
					$message = apply_filters( 'user_registration_modify_field_validation_response', $message, $form_data );
					return $message;
				}
			);
		}
	}
}

return UR_Form_Field_Coupon::get_instance();
