<?php
/**
 * UserRegistrationSMSIntegration Ajax
 *
 * AJAX Event Handler
 *
 * @class    Ajax
 * @package  UserRegistrationSMSIntegration/Ajax
 * @category Ajax
 * @author   WPEverest
 * @since  1.0.0
 */

namespace WPEverest\URSMSIntegration;

use Exception;
use WPEverest\URSMSIntegration\SMSOTP;
use Twilio\Rest\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax Class
 */
class Ajax {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax)
	 */
	public static function add_ajax_events() {
		add_action( 'wp_ajax_sms_otp_submit', array( __CLASS__, 'validate_otp' ) );
		add_action( 'wp_ajax_nopriv_sms_otp_submit', array( __CLASS__, 'validate_otp' ) );
		add_action( 'wp_ajax_sms_otp_resend', array( __CLASS__, 'resend_otp' ) );
		add_action( 'wp_ajax_nopriv_sms_otp_resend', array( __CLASS__, 'resend_otp' ) );

		$ajax_events = array(
			'sms_integration_connection_action'            => false,
			'sms_integration_connection_disconnect_action' => false,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {

			add_action( 'wp_ajax_user_registration_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {

				add_action(
					'wp_ajax_nopriv_user_registration_' . $ajax_event,
					array(
						__CLASS__,
						$ajax_event,
					)
				);
			}
		}
	}



	/**
	 * New API Key for SMS Integration
	 *
	 * @throws Exception Post data set.
	 */
	public static function sms_integration_connection_action() {
		try {
			check_ajax_referer( 'ur_sms_integration_connection_save_nonce', 'security' );

			if ( ! isset( $_POST['ur_twilio_client_number'] ) || empty( $_POST['ur_twilio_client_number'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Please enter Client Number.', 'user-registration' ),
					)
				);
			}
			if ( ! isset( $_POST['ur_twilio_client_id'] ) || empty( $_POST['ur_twilio_client_id'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Please enter Account SID.', 'user-registration' ),
					)
				);
			}
			if ( ! isset( $_POST['ur_twilio_client_auth'] ) || empty( $_POST['ur_twilio_client_auth'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Please enter Auth Token.', 'user-registration' ),
					)
				);
			}
			$authorized = self::ur_check_twilio_auth( $_POST['ur_twilio_client_id'], $_POST['ur_twilio_client_auth'] );

			if ( $authorized ) {
				$connected_accounts = get_option( 'ur_sms_integration_accounts', array() );
				if ( ! in_array( $_POST['ur_twilio_client_number'], array_column( $connected_accounts, 'client_number' ), true ) ) {
					$id             = count( $connected_accounts ) + 1;
					$new_connection = array(
						'client_number' => trim( $_POST['ur_twilio_client_number'] ),
						'client_id'     => trim( $_POST['ur_twilio_client_id'] ),
						'client_auth'   => trim( $_POST['ur_twilio_client_auth'] ),
						'label'         => ! empty( $_POST['ur_twilio_client_number'] ) ? sanitize_text_field( $_POST['ur_twilio_client_number'] ) : 'Connection ' . $id,
						'date'          => date_i18n( 'Y-m-d H:i:s' ),
					);
					array_push( $connected_accounts, $new_connection );
					update_option( 'ur_sms_integration_accounts', $connected_accounts );
					wp_send_json_success(
						array(
							'new_connection' => $new_connection,
							'message'        => __( 'Connected', 'user-registration' ),
						)
					);
				} else {
					wp_send_json_error(
						array(
							'message' => __( 'Client Number Already Exits', 'user-registration' ),
						)
					);
				}
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}


	/**
	 * Delete API Key for SMS Integration
	 *
	 * @throws Exception Post data set.
	 */
	public static function sms_integration_connection_disconnect_action() {
		try {
			check_ajax_referer( 'ur_sms_integration_connection_disconnect_nonce', 'security' );
			if ( isset( $_POST['client_number'] ) ) {
				$connected_accounts = get_option( 'ur_sms_integration_accounts', array() );
				$key                = array_search( $_POST['client_number'], array_column( $connected_accounts, 'client_number' ) );
				$account_exits      = array_filter(
					$connected_accounts,
					function ( $accounts ) {
						if ( $accounts['client_number'] !== $_POST['client_number'] ) {
							return $accounts;
						}
					}
				);

				if ( count( $account_exits ) >= 0 ) {

					update_option( 'ur_sms_integration_accounts', $account_exits );
					wp_send_json_success(
						array(
							'message' => __( 'Disconnected Successfully', 'user-registration' ),
						)
					);
				}
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Nonce Error.', 'user-registration' ),
				)
			);
		}
	}

	/**
	 * Validate API Key from Twilio Library.
	 *
	 * @param string $api_key API Key.
	 */
	public static function ur_check_twilio_auth( $client_secret, $client_auth ) {
		if ( ! class_exists( '\Twilio\Rest\Client' ) ) {
			require_once dirname( UR_ABSPATH ) . '/vendor/autoload.php';
		}
		try {
			$auth    = new \Twilio\Rest\Client( $client_secret, $client_auth );
			$account = $auth->api->v2010->accounts( $client_secret )->fetch();
		} catch ( \Twilio\Exceptions\RestException $e ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Could not connect to Twilio: ' . $e->getMessage(), 'user-registration' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'An unexpected error occurred.', 'user-registration' ),
				)
			);
		}

		return true;
	}

	/**
	 * Send One Time Password sms to user.
	 *
	 * @param [int]  $user_id User ID.
	 * @param string $otp OTP Code.
	 * @return void
	 */
	public static function send_sms_otp( $user_id, $otp = '' ) {
		if ( 'VERIFIED' === get_user_meta( $user_id, 'user_registration_sms_verification_status', true ) ) {
			return;
		}
		if ( 'PENDING' !== get_user_meta( $user_id, 'user_registration_sms_verification_status', true ) ) {
			return;
		}
		$otp_expiry_time = get_option( 'user_registration_sms_verification_otp_expiry_time', 10 );
		update_user_meta( $user_id, 'user_registration_sms_verification_otp_last_generated_on', time() );
		self::process_sms_otp( $user_id, $otp, $otp_expiry_time );
	}

	/**
	 * Send one Time Password sms to user while 2fa.
	 *
	 * @param  [int] $user_id User ID.
	 * @param  [int] $otp OTP.
	 */
	public static function send_2fa_sms_otp( $user_id, $otp ) {
		if ( 'PENDING' !== get_user_meta( $user_id, 'user_registration_tfa_status', true ) ) {
			return;
		}
		update_user_meta( $user_id, 'user_registration_tfa_otp_last_generated_on', time() );
		$otp_expiry_time = get_option( 'user_registration_tfa_otp_expiry_time', 10 );
		self::process_sms_otp( $user_id, $otp, $otp_expiry_time );
	}

	/**
	 * Process SMS OTP for user registration.
	 *
	 * This function sends a One Time Password (OTP) via SMS to the user for account verification.
	 *
	 * @param int    $user_id          The ID of the user.
	 * @param string $otp              The OTP code.
	 * @param int    $otp_expiry_time  The expiration time of the OTP in minutes.
	 * @return void
	 */
	public static function process_sms_otp( $user_id, $otp, $otp_expiry_time ) {
		$user     = get_user_by( 'ID', $user_id );
		$username = $user->data->user_login;
		$email    = $user->data->user_email;
		$form_id  = ur_get_form_id_by_userid( $user_id );

		$phone_field_name = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_default_phone_field', '' );
		$sms              = array();
		$phone_no         = get_user_meta( $user_id, 'user_registration_' . $phone_field_name, true );

		$message        = apply_filters(
			'user_registration_get_otp_sms', ur_get_single_post_meta( $form_id, 'user_registration_form_setting_sms_verification_msg', ur_get_sms_verification_default_message_content() ), $user_id);
		$message = apply_filters('user_registration_process_smart_tags', $message, array('username'=>$username, 'sms_otp'=>$otp, 'sms_otp_validity'=>$otp_expiry_time));

		$sms['number']  = $phone_no;
		$sms['message'] = $message;

		$connected_accounts = get_option( 'ur_sms_integration_accounts', array() );
		foreach ( $connected_accounts as $key => $providers ) {
			$account_number = ! empty( $providers['client_number'] ) ? $providers['client_number'] : '';
			$account_sid    = ! empty( $providers['client_id'] ) ? $providers['client_id'] : '';
			$account_token  = ! empty( $providers['client_auth'] ) ? $providers['client_auth'] : '';
			if ( empty( $account_number ) || empty( $account_sid ) || empty( $account_token ) ) {
				return;
			}
			try {
				$client  = new Client( $account_sid, $account_token );
				$message = $client->messages->create(
					$sms['number'],
					array(
						'from' => $account_number,
						'body' => $sms['message'],
					)
				);
			} catch ( \Exception $e ) {
				ur_get_logger()->critical(
					$e->getMessage(),
					array( 'source' => 'sms-notifications' )
				);
			}
		}
	}

	/**
	 * Validate Submitted OTP.
	 *
	 * @return void
	 */
	public static function validate_otp() {
		$nonce = isset( $_REQUEST['security'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'sms_otp_submit' ) ) {
			wp_send_json_error(
				array(
					'status'  => 'invalid_nonce',
					'message' => __( 'Security Error. Please try logging again.', 'user-registration' ),
				)
			);
		} else {
			$user_id     = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : 0;
			$remember_me = isset( $_POST['remember_me'] ) ? sanitize_text_field( wp_unslash( $_POST['remember_me'] ) ) : 0;
			if ( 'PENDING' !== get_user_meta( $user_id, 'user_registration_sms_verification_status', true ) ) {
				wp_send_json_error(
					array(
						'status'   => 'otp_expired',
						'message' => __( 'The OTP is expired. Please refresh this page to continue.', 'user-registration-two-factor-authentication' ),
					)
				);
			}

				update_user_meta( $user_id, 'user_registration_sms_verification_last_otp_submitted_on', time() );

			if ( self::is_otp_expired( $user_id ) ) {
				wp_send_json_error(
					array(
						'status'  => 'otp_expired',
						'message' => __( 'The OTP is expired. Please resend new otp and try again..', 'user-registration' ),
					)
				);
				exit;
			}

				$submitted_otp     = isset( $_POST['otp_code'] ) ? sanitize_text_field( wp_unslash( $_POST['otp_code'] ) ) : '';
				$redirect_on_login = isset( $_POST['redirect_on_login'] ) ? $_POST['redirect_on_login'] : '';

				$otp_matched = SMSOTP::verify_otp( $user_id, $submitted_otp );

			if ( $otp_matched ) {
				update_user_meta( $user_id, 'user_registration_sms_verification_status', 'VERIFIED' );
				$user_manager = new \UR_Admin_User_Manager( $user_id );
				$user_manager->save_status( \UR_Admin_User_Manager::APPROVED, true );

				$allow_automatic_user_login = apply_filters( 'user_registration_allow_automatic_user_login_sms_verification', true );
				if ( $allow_automatic_user_login ) {
					$user = get_user_by( 'ID', $user_id );
					ur_automatic_user_login( $user );
				} else {
					$login_nonce = wp_create_nonce( 'user_registration_sms_verification_login_' . $user_id );

					$redirect_url  = ur_get_my_account_url() . '/sms-otp-login?';
					$redirect_url .= 'uid=' . $user_id;
					$redirect_url .= '&remember_me=' . $remember_me;
					$redirect_url .= '&_wpnonce=' . $login_nonce;
					$redirect_url .= '&redirect_on_login=' . $redirect_on_login;

					wp_send_json_success(
						array(
							'status'   => 'verified',
							'message'  => __( 'OTP Matched. Redirecting...', 'user-registration' ),
							'redirect' => $redirect_url,
						)
					);
				}
			} else {
				wp_send_json_error(
					array(
						'status'  => 'invalid_otp',
						'message' => get_option( 'user_registration_sms_verification_invalid_otp_message', __( 'Invalid OTP entered.', 'user-registration' ) ),
					)
				);
			}
		}
		exit;
	}



	/**
	 * Check if the generated OTP code has expired.
	 *
	 * @param [int] $user_id User ID.
	 * @return boolean
	 */
	public static function is_otp_expired( $user_id ) {
		$otp_expiry_time    = (int) get_option( 'user_registration_sms_verification_otp_expiry_time', 10 );
		$otp_generated_time = (int) get_user_meta( $user_id, 'user_registration_sms_verification_otp_last_generated_on', true );
		$otp_lifetime       = time() - $otp_generated_time;

		if ( $otp_lifetime > $otp_expiry_time * 60 ) {
			return true;
		}
		return false;
	}

	/**
	 * Generate new OTP and resend to user email.
	 *
	 * @return void
	 */
	public static function resend_otp() {
		$nonce = isset( $_REQUEST['security'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sms_otp_resend' ) ) {
			die( 'Invalid nonce.' );
		} else {
			$user_id = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : 0;

			if ( 'PENDING' !== get_user_meta( $user_id, 'user_registration_sms_verification_status', true ) ) {
				return;
			}
			try {
				// 1. Generate One Time Password.
				$length   = get_option( 'user_registration_sms_verification_otp_length', 6 );
				$validity = get_option( 'user_registration_sms_verification_otp_expiry_time', 10 );
				$otp      = SMSOTP::generate_otp( $user_id, $length, $validity );

				// 2. Send One Time Password to user email.
				self::send_sms_otp( $user_id, $otp );

				update_user_meta( $user_id, 'user_registration_sms_verification_last_resend_otp_submitted_on', time() );

				wp_send_json_success(
					array(
						'status'  => 'resent',
						'message' => get_option( 'user_registration_sms_verification_otp_resent_message', __( 'OTP Sent successfully.', 'user-registration' ) ),
					)
				);

				exit;
			} catch ( Exception $e ) {
				wp_send_json_error(
					array(
						'status'  => 'error',
						'message' => $e->getMessage(),
					)
				);
			}

			exit;
		}
	}
}
