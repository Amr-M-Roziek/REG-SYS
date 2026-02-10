<?php
/**
 * UserRegistrationSMSIntegration Frontend.
 *
 * @class    Frontend
 * @version  1.0.0
 * @package  UserRegistrationSMSIntegration/Frontend
 * @category Frontend
 * @author   WPEverest
 */

namespace WPEverest\URSMSIntegration;

use Twilio\Rest\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class
 */
class Frontend {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_filter( 'user_registration_get_query_vars', array( $this, 'add_otp_endpoint' ), 10, 1 );
		add_filter( 'authenticate', array( $this, 'start_sms_verification' ), 49999, 3 );
		$currentUrl = esc_url_raw( $_SERVER['REQUEST_URI'] );
		$priority   = 10;

		if ( strpos( $currentUrl, '/sms-otp/' ) !== false ) {
			$priority = 11;
		}
		add_filter( 'user_registration_my_account_render_default', array( $this, 'prevent_default_render' ), $priority, 2 );
		add_filter( 'user_registration_login_render_default', array( $this, 'prevent_default_render' ), $priority, 2 );
		add_action( 'user_registration_my_account_custom_render', array( $this, 'render_otp_section' ) );
		add_action( 'user_registration_login_custom_render', array( $this, 'render_otp_section' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_filter( 'user_registration_success_params_before_send_json', array( $this, 'set_pending_status' ), 10, 4 );
	}

	/**
	 * Set the pending of the user and update it to usermeta table in database.
	 *
	 * @param array $success_params Success data.
	 * @param array $valid_form_data Form filled data.
	 * @param int   $form_id         Form ID.
	 * @param int   $user_id         User ID.
	 */
	public function set_pending_status( $success_params, $valid_form_data, $form_id, $user_id ) {
		$login_option = ur_get_user_login_option( $user_id );

		if ( ( 'sms_verification' == $login_option ) ) {
			$user_manager = new \UR_Admin_User_Manager( $user_id );
			$user_manager->save_status( \UR_Admin_User_Manager::PENDING, true );
			$user = get_user_by( 'id', $user_id );
			$this->start_sms_verification( $user, '', '', $success_params );
		} else {
			return $success_params;
		}
	}

	/**
	 * This is the main entry point for sms verification. Here, we check
	 * if sms verification is enabled and start the second factor
	 * authentication if the role matches.
	 *
	 * We add user metas, generate otp, send email and redirect to otp page.
	 *
	 * @param object $user WP_User cookie.
	 *
	 * @return string WP_User
	 */
	public function start_sms_verification( $user, $username, $password, $success_params = array() ) {
		global $wp;
		if ( ! in_array( 'sms-otp', $wp->public_query_vars ) || isset( $wp->query_vars['sms-otp-login'] ) || is_wp_error( $user ) ) {
			return $user;
		}

		// Check if the token matches the token value stored in db.
		$user_id      = $user->ID;
		$login_option = ur_get_user_login_option( $user_id );

		if ( 'sms_verification' === $login_option ) {

			if ( 'VERIFIED' === get_user_meta( $user_id, 'user_registration_sms_verification_status', true ) ) {
				return $user;
			}
			$remember_me = isset( $_POST['rememberme'] ) ? sanitize_text_field( wp_unslash( $_POST['rememberme'] ) ) : 0;
			$this->redirect_to_otp_page( $user_id, $remember_me, $success_params );
		}

		return $user;
	}

	/**
	 * Redirect user to otp page.
	 *
	 * @param int $user_id User Id.
	 * @return void
	 */
	public function redirect_to_otp_page( $user_id = 0, $remember_me = false, $success_params = array() ) {
		$nonce_action = 'user_registration_sms_verification_' . $user_id;
		$nonce        = wp_create_nonce( $nonce_action );
		$redirect_url = add_query_arg(
			array(
				'uid'          => $user_id,
				'_wpnonce'     => $nonce,
				'remember_me'  => $remember_me,
				'redirect_url' => esc_url_raw( isset( $success_params['redirect_url'] ) ? $success_params['redirect_url'] : ''  ),
			),
			ur_get_my_account_url() . 'sms-otp'
		);

		if ( defined( 'DOING_AJAX' ) ) {
			if ( ! empty( $success_params ) ) {
				$success_params['redirect_url'] = isset( $success_params['redirect_url'] ) ? $success_params['redirect_url'] : $redirect_url;
				wp_send_json_success( $success_params );
			} else {
				wp_send_json_success(
					array(
						'message' => $redirect_url,
					)
				);
			}
		} else {
			wp_safe_redirect( $redirect_url );
		}
		exit;
	}



	/**
	 * Start Fresh sms verification Session.
	 *
	 * @param [int] $user_id User ID.
	 * @return void
	 */
	public function start_fresh_session( $user_id ) {
		$this->set_init_metas( $user_id );
		$this->generate_and_send_otp( $user_id );
	}


	/**
	 * Prevent rendering of default login form to show otp form.
	 *
	 * @param [bool]  $default Default value.
	 * @param [array] $atts Attributes.
	 * @return bool
	 */
	public function prevent_default_render( $default, $atts ) {
		// When user is not logged in.
		global $wp;
		if ( ! isset( $wp->query_vars['sms-otp'] ) ) {
			return true;
		}

		$user_id = $this->get_user_id();

		$login_option = ur_get_user_login_option( $user_id );

		if ( 'sms_verification' === $login_option ) {

			if ( 'PENDING' === get_user_meta( $user_id, 'user_registration_sms_verification_status', true ) ) {
				return false;
			}
			$this->start_fresh_session( $user_id );
			return false;
		}

		return true;
	}


	/**
	 * Render OTP template.
	 *
	 * @return void
	 */
	public function render_otp_section() {
		global $wp;
		if ( isset( $wp->query_vars['otp'] ) ) {
			return true;
		}
		ur_get_template( 'sms-otp-login.php', array(), 'user-registration-pro', UR_ABSPATH . '/templates/pro/sms-integration/' );
	}


	/**
	 * Set user metas to initialize OTP verification.
	 *
	 * @param [int] $user_id User ID.
	 * @return void
	 */
	public function set_init_metas( $user_id ) {
		update_user_meta( $user_id, 'user_registration_sms_verification_status', 'PENDING' );
	}

	/**
	 * Generate OTP and email it to user.
	 *
	 * @param [int] $user_id User id.
	 * @return void
	 */
	public function generate_and_send_otp( $user_id ) {
		// // 1. Generate One Time Password.
		$length   = get_option( 'user_registration_sms_verification_otp_length', 6 );
		$validity = get_option( 'user_registration_sms_verification_otp_expiry_time', 10 );
		$otp      = SMSOTP::generate_otp( $user_id, $length, $validity );
		Ajax::send_sms_otp( $user_id, $otp );
	}


	/**
	 * Validate and return current user id.
	 *
	 * @return int
	 */
	public function get_user_id() {
		$user_id = 0;

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} elseif ( isset( $_GET['uid'] ) ) {
			$user_id      = sanitize_text_field( wp_unslash( $_GET['uid'] ) );
			$nonce        = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			$nonce_action = 'user_registration_sms_verification_' . $user_id;
			if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
				ur_add_notice( __( 'Some error occured. Please login again.', 'user-registration' ), 'error' );
				$user_id = -1;
			}
		} else {
			$user_id = 0;
			ur_add_notice( __( 'User not found. Please login again.', 'user-registration' ), 'error' );
		}
		return $user_id;
	}


	/**
	 * Set cookies and log in the user.
	 *
	 * @return void
	 */
	public function login_user() {
		global $wp;

		if ( ! isset( $wp->query_vars['sms-otp-login'] ) ) {
			return;
		}

		$remember_me = isset( $_GET['remember_me'] ) ? sanitize_text_field( wp_unslash( $_GET['remember_me'] ) ) : 0;
		$user_id     = isset( $_GET['uid'] ) ? sanitize_text_field( wp_unslash( $_GET['uid'] ) ) : 0;

		if ( ! is_user_logged_in() && 0 !== $user_id ) {
			$login_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			if ( wp_verify_nonce( $login_nonce, 'user_registration_sms_verification_login_' . $user_id ) ) {
				$user = get_user_by( 'ID', $user_id );
				wp_set_current_user( $user_id, $user->user_login );

				wp_set_auth_cookie( $user_id, $remember_me );
				do_action( 'wp_login', $user->user_login, $user );

				$redirect_url = ( isset( $_GET['redirect_on_login'] ) && ! empty( $_GET['redirect_on_login'] ) ) ? esc_url_raw( $_GET['redirect_on_login'] ) : ur_get_my_account_url();
				$redirect_url = apply_filters( 'user_registration_login_redirect', $redirect_url, $user );

				if ( ! empty( $redirect_url ) ) {
					wp_redirect( wp_validate_redirect( $redirect_url, $redirect_url ) );
					exit;
				}
			} else {
				ur_add_notice( __( 'Some error occured. Please login again.', 'user-registration' ), 'error' );
			}
		} else {
			ur_add_notice( __( 'User not found. Please login again.', 'user-registration' ), 'error' );
		}

		if ( ur_notice_count() > 0 ) {
			ur_print_notices();
			return true;
		}
	}

	/**
	 * Add necessary query vars to registered query vars.
	 *
	 * @param [array] $vars Registered query_vars.
	 * @return array $vars
	 */
	public function add_otp_endpoint( $vars ) {
		$vars['sms-otp']       = 'sms-otp';
		$vars['sms-otp-login'] = 'sms-otp-login';
		$rewrite_rules         = get_option( 'rewrite_rules', array() );

		if ( ! isset( $rewrite_rules['(.?.+?)/sms-otp(/(.*))?/?$'] ) || ! isset( $rewrite_rules['(.?.+?)/sms-otp-login(/(.*))?/?$'] ) ) {
			flush_rewrite_rules();
		}

		return $vars;
	}

	/**
	 * Register scripts and styles.
	 *
	 * @return void
	 */
	public function add_scripts() {
		global $wp;

		if ( ! isset( $wp->query_vars['sms-otp'] ) ) {
			return;
		}
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'user-registration-sms-verification',
			plugins_url( '/assets/js/pro/frontend/sms-verification' . $suffix . '.js', UR_PLUGIN_FILE ),
			array( 'jquery' ),
			UR_VERSION,
			true
		);

		wp_enqueue_style(
			'user-registration-sms-verification',
			plugins_url( '/assets/css/ur-sms-verification.css', UR_PLUGIN_FILE ),
			array(),
			UR_VERSION
		);

		$user_id = $this->get_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$user             = get_user_by( 'ID', $user_id );
		$current_language = ur_get_current_language();
		$values           = array(
			'otp_length'            => get_option( 'user_registration_sms_verification_otp_length', 6 ),
			'otp_expiry_time'       => get_option( 'user_registration_sms_verification_otp_expiry_time', 10 ),
			'otp_sent_message'      => apply_filters('user_registration_sms_verification_otp_sent_message_modify', get_option('user_registration_sms_verification_otp_sent_message', __( 'Otp has been sent to you registered phone number.', 'user-registration' ) ) ),
			'otp_resent_message'    => get_option( 'user_registration_sms_verification_otp_resent_message', '' ),
			'otp_invalid_message'   => get_option( 'user_registration_sms_verification_invalid_otp_message', __( 'Invalid otp.', 'user-registration' ) ),
			'otp_empty_message'     => get_option( 'user_registration_sms_verification_empty_otp_message', __( 'Please enter otp.', 'user-registration' ) ),
			'ajax_url'              => admin_url( 'admin-ajax.php' ) . '?lang=' . $current_language,
			'sms_otp_submit_action' => esc_attr( 'sms_otp_submit' ),
			'sms_otp_submit_nonce'  => wp_create_nonce( 'sms_otp_submit' ),
			'sms_otp_resend_action' => esc_attr( 'sms_otp_resend' ),
			'sms_otp_resend_nonce'  => wp_create_nonce( 'sms_otp_resend' ),
			'user_id'               => $user_id,
			'login_page_url'        => ur_get_my_account_url(),
		);

		wp_localize_script(
			'user-registration-sms-verification',
			'user_registration_sms_verification_parameters',
			$values
		);
	}
}
