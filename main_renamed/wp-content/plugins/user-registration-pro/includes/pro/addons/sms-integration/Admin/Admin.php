<?php
/**
 * UserRegistrationSMSIntegration Admin.
 *
 * @class    Admin
 * @version  1.0.0
 * @package  UserRegistrationSMSIntegration/Admin
 * @category Admin
 * @author   WPEverest
 */

namespace WPEverest\URSMSIntegration\Admin;

use Twilio\Rest\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Class
 */
class Admin {
	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_filter( 'user_registration_integrations_classes', array( $this, 'get_integrations' ), 11, 1 );
		add_filter( 'user_registration_login_options', array( $this, 'add_sms_verification_login_option' ) );
		// add_filter( 'user_registration_get_login_options_settings_general', array( $this, 'add_sms_verification_settings' ) );
	}




	/**
	 * SMS Verification settings.
	 *
	 * @param  array $settings Other Settings.
	 * @return  array
	 */
	public function add_sms_verification_settings( $settings ) {
		$settings['sections']['user_registration_otp_settings'] = array(
			'title'    => __( 'OTP Settings', 'user-registration' ),
			'type'     => 'card',
			'desc'     => '',
			'settings' => array(
				array(
					'title'    => __( 'OTP Length', 'user-registration' ),
					'desc'     => __( 'Choose the length of OTP code.', 'user-registration' ),
					'desc_tip' => true,
					'id'       => 'user_registration_otp_length',
					'default'  => 6,
					'type'     => 'select',
					'class'    => 'ur-enhanced-select',
					'css'      => 'min-width: 350px;',
					'options'  => array(
						'4' => 4,
						'6' => 6,
						'8' => 8
					)
				),

				array(
					'title'             => __( 'OTP Expiry Time', 'user-registration' ),
					'desc'              => __( 'Enter the time to expire the generated OTP ( in minutes ).', 'user-registration' ),
					'desc_tip'          => true,
					'id'                => 'user_registration_otp_expiry_time',
					'default'           => 10,
					'type'              => 'number',
					'css'               => 'min-width: 350px;',
					'autoload'          => false,
					'class'             => 'user_registration_number_input',
					'custom_attributes' => array(
						'min' => 1
					)
				),

				array(
					'title'             => __( 'OTP Resend Limit', 'user-registration' ),
					'desc'              => __( 'Enter the number of times to allow resending OTP.', 'user-registration' ),
					'desc_tip'          => true,
					'id'                => 'user_registration_otp_resend_limit',
					'default'           => 3,
					'type'              => 'number',
					'css'               => 'min-width: 350px;',
					'autoload'          => false,
					'class'             => 'user_registration_number_input',
					'custom_attributes' => array(
						'min' => 1
					)
				),

				array(
					'title'             => __( 'Resend OTP Hold Period', 'user-registration' ),
					'desc'              => __( 'Enter the time to prevent user from requesting resend OTP when user hits incorrect otp resend submission limit ( in minutes ).', 'user-registration' ),
					'desc_tip'          => true,
					'id'                => 'user_registration_resend_otp_hold_period',
					'default'           => 60,
					'type'              => 'number',
					'css'               => 'min-width: 350px;',
					'autoload'          => false,
					'class'             => 'user_registration_number_input',
					'custom_attributes' => array(
						'min' => 1
					)
				),

				array(
					'title'             => __( 'Incorrect OTP Limit', 'user-registration' ),
					'desc'              => __( 'Enter the number of times to allow incorrect OTP submission.', 'user-registration' ),
					'desc_tip'          => true,
					'id'                => 'user_registration_incorrect_otp_limit',
					'default'           => 5,
					'type'              => 'number',
					'css'               => 'min-width: 350px;',
					'autoload'          => false,
					'class'             => 'user_registration_number_input',
					'custom_attributes' => array(
						'min' => 1
					)
				),

				array(
					'title'             => __( 'Login Hold Period', 'user-registration' ),
					'desc'              => __( 'Enter the time to prevent user from logging in when user hits incorrect otp submission limit ( in minutes ).', 'user-registration' ),
					'desc_tip'          => true,
					'id'                => 'user_registration_login_hold_period',
					'default'           => 60,
					'type'              => 'number',
					'css'               => 'min-width: 350px;',
					'autoload'          => false,
					'class'             => 'user_registration_number_input',
					'custom_attributes' => array(
						'min' => 1
					)
				)
			)
					);
		$settings['sections']['user_registration_otp_message_settings'] = array(
			'title'    => __( 'OTP Messages', 'user-registration' ),
			'type'     => 'card',
			'desc'     => '',
			'settings' => array(
				array(
					'title'       => __( 'OTP Sent Message', 'user-registration' ),
					'desc'        => __( 'Enter the message to display when OTP has been sent to user email.', 'user-registration' ),
					'id'          => 'user_registration_otp_sent_message',
					'placeholder' => '',
					'default'     => __( 'An email with a One Time Password(OTP) has been sent to your registered email address. Enter the OTP below to continue.', 'user-registration' ),
					'type'        => 'textarea',
					'rows'        => 1,
					'cols'        => 40,
					'css'         => 'min-width: 350px; max-width: 350px; min-height: 100px;',
					'desc_tip'    => true
				),

				array(
					'title'       => __( 'OTP Resent Message', 'user-registration' ),
					'desc'        => __( 'Enter the message to display when OTP is resent to user email.', 'user-registration' ),
					'id'          => 'user_registration_otp_resent_message',
					'placeholder' => '',
					'default'     => __( 'An OTP has been resent. Please also check the spam folder. Enter the OTP below to continue.', 'user-registration' ),
					'type'        => 'textarea',
					'rows'        => 1,
					'cols'        => 40,
					'css'         => 'min-width: 350px; max-width: 350px; min-height: 100px;',
					'desc_tip'    => true
				),

				array(
					'title'    => __( 'Invalid OTP Message', 'user-registration' ),
					'desc'     => __( 'Enter the message to display when the entered OTP is incorrect.', 'user-registration' ),
					'id'       => 'user_registration_invalid_otp_message',
					'default'  => __( 'The OTP you entered is invalid. Please try again.', 'user-registration' ),
					'type'     => 'textarea',
					'rows'     => 1,
					'cols'     => 40,
					'css'      => 'min-width: 350px; max-width: 350px; min-height: 100px;',
					'desc_tip' => true
				),

				array(
					'title'    => __( 'Resend Limit Reached Message', 'user-registration' ),
					'desc'     => __( 'Enter the message to display when the user reaches resend limit. Use smart tag <code style="font-weight: bold">{{resend_otp_hold_time}}</code> to show remaining hold time.', 'user-registration' ),
					'id'       => 'user_registration_resend_limit_message',
					'default'  => __( 'You have reached the OTP resend limit. Please try again after {{resend_otp_hold_time}}.', 'user-registration' ),
					'type'     => 'textarea',
					'rows'     => 1,
					'cols'     => 40,
					'css'      => 'min-width: 350px; max-width: 350px; min-height: 100px;',
					'desc_tip' => true
				),

				array(
					'title'    => __( 'Invalid Submission Limit Reached Message', 'user-registration' ),
					'desc'     => __( 'Enter the message to show when the user reaches the invalid submission limit.', 'user-registration' ),
					'id'       => 'user_registration_invalid_otp_limit_message',
					'default'  => __( 'You have reached the invalid submission limit. Please try to login again after {{otp_hold_time}}.', 'user-registration' ),
					'type'     => 'textarea',
					'rows'     => 1,
					'cols'     => 40,
					'css'      => 'min-width: 350px; max-width: 350px; min-height: 100px;',
					'desc_tip' => true
				),

				array(
					'title'    => __( 'Empty OTP Submission Message', 'user-registration' ),
					'desc'     => __( 'Enter the message to show when the user tries to submit with empty OTP.', 'user-registration' ),
					'id'       => 'user_registration_empty_otp_message',
					'default'  => __( 'Please enter the OTP and try again.', 'user-registration' ),
					'type'     => 'textarea',
					'rows'     => 1,
					'cols'     => 40,
					'css'      => 'min-width: 350px; max-width: 350px; min-height: 100px;',
					'desc_tip' => true
				)
			)
				);
		return $settings;
	}

	/**
	 * Add Combine two login option.
	 *
	 * @param  array $options Other login options.
	 * @return  array
	 */
	public function add_sms_verification_login_option( $options ) {
		$options['sms_verification'] = esc_html__( 'Auto approval after SMS verification', 'user-registration' );
		return $options;
	}

	/**
	 * Get all integration triggered.
	 *
	 * @param array $integration Integration.
	 * @return array $integration
	 */
	public function get_integrations( $integration ) {

		$sms_integration                            = (object) array(
			'id'    => 'sms_integration',
			'title' => 'SMS Integration',
		);
		$integration['UR_Settings_SMS_Integration'] = array(
			'id'       => 'sms_integration',
			'type'     => 'accordian',
			'title'    => 'Twilio',
			'desc'     => '',
			'settings' => ur_integration_settings_template( $sms_integration ),
		);

		return $integration;
	}

	/**
	 * Load Script
	 */
	public function admin_enqueue_scripts() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'ur-sms-integration-admin-script', plugins_url( '/assets/js/pro/admin/user-registration-sms-integration-admin' . $suffix . '.js', UR_PLUGIN_FILE ), array( 'jquery' ), UR_VERSION, true );
		wp_enqueue_script( 'ur-sms-integration-admin-script' );
		wp_enqueue_style( 'user-registration-sms-integration-admin-style', plugins_url( '/assets/css/user-registration-sms-integration-admin-style.css', UR_PLUGIN_FILE ), array(), UR_VERSION );
		wp_localize_script(
			'ur-sms-integration-admin-script',
			'ur_sms_integration_params',
			array(
				'ajax_url'                                 => admin_url( 'admin-ajax.php' ),
				'ur_sms_integration_connection_save'       => wp_create_nonce( 'ur_sms_integration_connection_save_nonce' ),
				'ur_sms_integration_connection_disconnect' => wp_create_nonce( 'ur_sms_integration_connection_disconnect_nonce' ),
				'i18n_cancel'                              => __( 'CANCEL', 'user-registration' ),
				'i18n_ok'                                  => __( 'OK', 'user-registration' ),
				'i18n_disconnect'                          => __( 'Disconnect', 'user-registration' ),
				'i18n_confirmation'                        => __( 'Are you sure you want to delete this connection?', 'user-registration' ),
			)
		);
	}
}
