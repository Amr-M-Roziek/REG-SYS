<?php
/**
 * UR_Form_Field_Captcha.
 *
 * @package  UserRegistrationPro/Form
 * @category Admin
 * @author   WPEverest
 *
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * UR_Form_Field_Captcha Class
 */
class UR_Form_Field_Captcha extends UR_Form_Field {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $_instance;

	/**
	 * Math equation and operators.
	 *
	 * @var array
	 */
	public $math;

	/**
	 * Captcha questions to ask for.
	 *
	 * @var array
	 */
	public $questions;

	/**
	 * Captcha images to select for.
	 *
	 * @var array
	 */
	public $captcha_images;

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

		$this->id                       = 'user_registration_captcha';
		$this->form_id                  = 1;
		$this->registered_fields_config = array(
			'label' => __( 'Custom Captcha', 'user-registration-advanced-fields' ),
			'icon'  => 'ur-icon ur-icon-captcha-field',
		);

		$this->field_defaults = array(
			'default_label'         => __( 'Captcha', 'user-registration' ),
			'default_field_name'    => 'captcha_' . ur_get_random_number(),
			'default_required'      => 'yes',
			'default_options'       => array(
				array(
					'question' => esc_html__( 'What is 2+3?', 'user-registration' ),
					'answer'   => esc_html__( '5', 'user-registration' ),
				),
			),
			'default_image_options' => array(
				array(
					'icon-1'       => 'dashicons dashicons-menu',
					'icon-2'       => 'dashicons dashicons-admin-network',
					'icon-3'       => 'dashicons dashicons-admin-multisite',
					'correct_icon' => 'icon-1',
					'icon_tag'     => 'Menu',
				),
			),
		);

		// Allow customizing math captcha.
		$this->math = apply_filters(
			'user_registration_math_captcha',
			array(
				'min' => 1,
				'max' => 15,
				'cal' => array( '+', '*', '-' ),
			)
		);
		add_filter( "{$this->id}_advance_class", array( $this, 'settings_override' ), 10, 1 );
	}

	/**
	 * Settings Override
	 *
	 * @param mixed $file_path_override file path.
	 * @since 1.1.8
	 */
	public function settings_override( $file_path_override ) {
		$file_path_override['file_path'] = UR_ABSPATH . 'includes' . UR_DS . 'pro' . UR_DS . 'form' . UR_DS . 'settings' . UR_DS . 'class-ur-setting-captcha.php';
		return $file_path_override;
	}

	/**
	 * Get registered admin fields.
	 */
	public function get_registered_admin_fields() {

		return '<li id="' . $this->id . '_list " class="ur-registered-item draggable" data-field-id="' . $this->id . '"><span class="' . $this->registered_fields_config['icon'] . '"></span>' . $this->registered_fields_config['label'] . '</li>';
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
		$captcha_math   = isset( $_POST['ur_captcha_math'] ) ? ur_clean( $_POST['ur_captcha_math'] ) : array();
		$captcha_qa     = isset( $_POST['ur_captcha_qa'] ) ? ur_clean( $_POST['ur_captcha_qa'] ) : array();
		$captcha_image  = isset( $_POST['ur_captcha_image'] ) ? ur_clean( $_POST['ur_captcha_image'] ) : array();
		$field_label    = $single_form_field->general_setting->field_name;
		$captcha_format = $single_form_field->general_setting->captcha_format;
		$message        = ! empty( $single_form_field->advance_setting->captcha_message ) ? $single_form_field->advance_setting->captcha_message : esc_html__( 'Incorrect Answer', 'user-registration' );
		$urcl_fields    = isset( $_POST['urcl_hide_fields'] ) ? (array) json_decode( stripslashes( $_POST['urcl_hide_fields'] ), true ) : array();

		if ( 'math' === $captcha_format ) {
			if ( empty( $captcha_math ) ) {
				return $form_data;
			}

			foreach ( $captcha_math as $math ) {
				$field_name = isset( $form_data->field_name ) ? $form_data->field_name : '';
				if ( in_array( $field_name, array_keys( $math ) ) && ! in_array( $field_name, $urcl_fields ) ) {
					$value = isset( $form_data->value ) ? trim( $form_data->value ) : '';
					$cal   = $math[ $field_name ]['cal'];
					$n1    = $math[ $field_name ]['n1'];
					$n2    = $math[ $field_name ]['n2'];
					$flag  = false;

					switch ( $cal ) {
						case '+':
							$flag = ( $n1 + $n2 );
							break;
						case '-':
							$flag = ( $n1 - $n2 );
							break;
						case '*':
							$flag = ( $n1 * $n2 );
							break;
					}

					if ( $flag !== (int) $value ) {
						$message = array(
							/* translators: %s - validation message */
							$field_label => sprintf( __( '%s.', 'user-registration' ), $message ),
							'individual' => true,
						);
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
		}

		// Question answer captcha.
		if ( 'qa' === $captcha_format ) {
			if ( empty( $captcha_qa ) ) {
				return $form_data;
			}

			foreach ( $captcha_qa as $qa ) {
				$field_name = isset( $form_data->field_name ) ? $form_data->field_name : '';
				$value      = isset( $form_data->value ) ? trim( $form_data->value ) : '';

				if ( in_array( $field_name, array_keys( $qa ) ) && ! in_array( $field_name, $urcl_fields ) ) {
					$searchqa = $qa[ $field_name ]['qa'];
					$options  = $single_form_field->general_setting->options;

					foreach ( $options as $option ) {
						if ( $option->question === $searchqa ) {
							if ( strtolower( trim( $option->answer ) ) !== strtolower( trim( $value ) ) ) {
								$message = array(
									/* translators: %s - validation message */
									$field_label => sprintf( __( '%s.', 'user-registration' ), $message ),
									'individual' => true,
								);
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
				}
			}
		}

		// Image Captcha.
		if ( 'image' === $captcha_format ) {

			if ( empty( $captcha_image ) ) {
				return $form_data;
			}

			foreach ( $captcha_image as $image ) {
				$field_name = isset( $form_data->field_name ) ? $form_data->field_name : '';
				$value      = isset( $form_data->value ) ? trim( $form_data->value ) : '';

				if ( in_array( $field_name, array_keys( $image ) ) && ! in_array( $field_name, $urcl_fields ) ) {
					$searchimage = $image[ $field_name ]['i_captcha'];
					$option      = $single_form_field->general_setting->image_captcha_options[ $image[ $field_name ]['i_captcha_group'] ];

					$option = (array) $option;

					if ( $option['correct_icon'] === $searchimage ) {
						if ( strtolower( trim( $option[ $searchimage ] ) ) !== strtolower( trim( $value ) ) ) {
							$message = array(
								/* translators: %s - validation message */
								$field_label => sprintf( __( '%s.', 'user-registration' ), $message ),
								'individual' => true,
							);
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
			}
		}
	}
}

return UR_Form_Field_Captcha::get_instance();
