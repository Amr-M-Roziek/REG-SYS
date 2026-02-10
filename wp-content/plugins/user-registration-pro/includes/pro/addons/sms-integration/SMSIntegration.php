<?php
namespace WPEverest\URSMSIntegration;

use WPEverest\URSMSIntegration\Admin\Admin;
use WPEverest\URSMSIntegration\Frontend;

/**
 * Main SMSIntegratioin Class.
 *
 * @class MailerLite
 */
class SMSIntegration {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Admin.
	 *
	 * @since 1.0.0
	 *
	 * @var WPEverest\URSMSIntegration\Admin;
	 */
	public $admin = null;

	/**
	 * Frontend.
	 *
	 * @since 1.0.0
	 *
	 * @var WPEverest\URSMSIntegration\Frontend;
	 */
	public $frontend = null;

	/**
	 * Admin Ajax.
	 *
	 * @since 1.0.0
	 *
	 * @var WPEverest\URSMSIntegration\Admin;
	 */
	public $ajax = null;

	/**
	 * Initialize the plugin.
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
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Initialize SMSIntegration when WordPress initializes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		// Load class instances.
		if ( is_admin() ) {
			$this->admin = new Admin();
		}
		$this->frontend = new Frontend();
		$this->ajax = new Ajax();
	}

	/**
	 * Install MailerLite.
	 */
	public static function install() {
		global $wpdb;
		self::migration_script();
		// Trigger action.
		do_action( 'user_registration_sms_integration_installed' );
	}

	/**
	 * Migration Script Accourding to version.
	 */
	private static function migration_script() {

		$migrations = array();

		foreach ( $migrations as $migration_version => $function ) {

			if ( UR_SMS_INTEGRATION_VERSION >= $migration_version ) {
				if ( function_exists( $function ) ) {
					call_user_func( $function );
				}
			}
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

new SMSIntegration();
