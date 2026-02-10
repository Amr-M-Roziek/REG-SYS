<?php
/**
 * Plugin Name: User Registration & Membership ( Pro )
 * Plugin URI: https://wpuserregistration.com/
 * Description: Drag and Drop user registration form and login form builder.
 * Version: 5.3.1
 * Author: WPEverest
 * Author URI: https://wpuserregistration.com
 * Text Domain: user-registration
 * Domain Path: /languages/
 *
 * @package UserRegistration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

update_option( 'user-registration_license_active',  'valid' );
update_option( 'user-registration_errors',  [] );
update_option( 'user-registration_license_key', 'B5E0B5F8DD8689E6ACA49DD6E6E1A930' );

 // Create a stdClass object and set its properties
$license_data = new stdClass();
$license_data->success = true;
$license_data->license = 'valid';
$license_data->item_id = false;
$license_data->item_name = 'User Registration Plus';
$license_data->checksum = 'B5E0B5F8DD8689E6ACA49DD6E6E1A930';
$license_data->expires = '2030-01-01 11:11:11';
$license_data->payment_id = 00000;
$license_data->customer_name = 'GPL';
$license_data->customer_email = 'noreply@gmail.com';
$license_data->license_limit = 5;
$license_data->site_count = 1;
$license_data->activations_left = 4;
$license_data->price_id = false;
$license_data->item_plan = 'plus';

// Update the option in the database
update_option('_transient_ur_pro_license_plan', $license_data, 'yes');

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( 'UserRegistration' ) ) :

	/**
	 * Main UserRegistration Class.
	 *
	 * @class   UserRegistration
	 * @version 1.0.0
	 */
	final class UserRegistration {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = '5.3.1';

		/**
		 * Session instance.
		 *
		 * @var UR_Session|UR_Session_Handler
		 */
		public $session = null;

		/**
		 * Query instance.
		 *
		 * @var UR_Query
		 */
		public $query = null;

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $_instance = null;

		/**
		 * Instance of this form.
		 *
		 * @var object
		 */
		public $form = null;

		/**
		 * UTM Campaign.
		 *
		 * @var string
		 */
		public $utm_campaign = 'pro-version';

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function instance() {
			// If the single instance hasn't been set, set it now.
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'user-registration' ), '1.0' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'user-registration' ), '1.0' );
		}

		/**
		 * UserRegistration Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
			add_action( 'plugins_loaded', array( $this, 'objects' ), 1 );
			add_action( 'in_plugin_update_message-' . UR_PLUGIN_BASENAME, array( __CLASS__, 'in_plugin_update_message' ) );

			do_action( 'user_registration_loaded' );
		}

		/**
		 * Hook into actions and filters.
		 */
		private function init_hooks() {
			register_activation_hook( __FILE__, array( 'UR_Install', 'install' ) );
			register_shutdown_function( array( $this, 'log_errors' ) );
			add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
			add_action( 'init', array( $this, 'init' ), 0 );
			add_action( 'init', array( 'UR_Shortcodes', 'init' ) );

			add_filter( 'plugin_action_links_' . UR_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
			add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		}

		/**
		 * Ensures fatal errors are logged so they can be picked up in the status report.
		 *
		 * @since 3.0.5
		 */
		public function log_errors() {
			$error = error_get_last();

			if ( $error && in_array(
				$error['type'],
				array(
					E_ERROR,
					E_PARSE,
					E_COMPILE_ERROR,
					E_USER_ERROR,
					E_RECOVERABLE_ERROR,
				),
				true
			) ) {
				$logger = ur_get_logger();
				$logger->critical(
				/* translators: 1: error message 2: file name and path 3: line number */
					sprintf( __( '%1$s in %2$s on line %3$s', 'user-registration' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
					array(
						'source' => 'fatal-errors',
					)
				);
			}
		}

		/**
		 * Define FT Constants.
		 */
		private function define_constants() {
			$upload_dir = apply_filters( 'user_registration_upload_dir', wp_upload_dir() );
			$this->define( 'UR_LOG_DIR', $upload_dir['basedir'] . '/ur-logs/' );
			$this->define( 'UR_UPLOAD_PATH', $upload_dir['basedir'] . '/user_registration_uploads/' );
			$this->define( 'UR_UPLOAD_URL', $upload_dir['baseurl'] . '/user_registration_uploads/' );
			$this->define( 'UR_DS', DIRECTORY_SEPARATOR );
			$this->define( 'UR_PLUGIN_FILE', __FILE__ );
			$this->define( 'UR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'UR_ASSETS_URL', UR_PLUGIN_URL . 'assets' );
			$this->define( 'UR_ABSPATH', __DIR__ . UR_DS );
			$this->define( 'UR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'UR_VERSION', $this->version );
			$this->define( 'UR_TEMPLATE_DEBUG_MODE', false );
			$this->define( 'UR_TEMPLATE_PATH', UR_ABSPATH . 'templates/' );
			$this->define( 'UR_ASSET_PATH', plugins_url( 'assets/', UR_PLUGIN_FILE ) );
			$this->define( 'UR_FORM_PATH', UR_ABSPATH . 'includes' . UR_DS . 'form' . UR_DS );
			$this->define( 'UR_SESSION_CACHE_GROUP', 'ur_session_id' );
			$this->define( 'UR_PRO_ACTIVE', true );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param string      $name
		 * @param string|bool $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * What type of request is this?
		 *
		 * @param string $type admin, ajax, cron or frontend.
		 *
		 * @return bool
		 */
		private function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'cron':
					return defined( 'DOING_CRON' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}

		/**
		 * Includes.
		 */
		private function includes() {

			if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
				require_once __DIR__ . '/vendor/autoload.php';
			} else {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						sprintf(
						/* translators: 1: composer command. 2: plugin directory */
							esc_html__( 'Your installation of the User Registration is incomplete. Please run %1$s within the %2$s directory.', 'user-registration' ),
							'`composer install`',
							'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
						)
					);
				}

				/**
				 * Outputs an admin notice if composer install has not been ran.
				 */
				add_action(
					'admin_notices',
					function () {
						?>
						<div class="notice notice-error">
							<p>
								<?php
								printf(
								/* translators: 1: composer command. 2: plugin directory */
									esc_html__( 'Your installation of the  User Registration PRO is incomplete. Please run %1$s within the %2$s directory.', 'user-registration' ),
									'<code>composer install</code>',
									'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
								);
								?>
							</p>
						</div>
						<?php
					}
				);
			}

			/**
			 * Class autoloader.
			 */
			include_once UR_ABSPATH . 'includes/class-ur-autoloader.php';

			/**
			 * Interfaces.
			 */
			include_once UR_ABSPATH . 'includes/interfaces/class-ur-logger-interface.php';
			include_once UR_ABSPATH . 'includes/interfaces/class-ur-log-handler-interface.php';

			/**
			 * Abstract classes
			 */
			include_once UR_ABSPATH . 'includes/abstracts/abstract-ur-form-field.php';
			include_once UR_ABSPATH . 'includes/abstracts/abstract-ur-field-settings.php';
			include_once UR_ABSPATH . 'includes/abstracts/abstract-ur-log-handler.php';
			include_once UR_ABSPATH . 'includes/abstracts/abstract-ur-session.php';

			/**
			 * Core classes.
			 */
			include_once UR_ABSPATH . 'includes/functions-ur-core.php';
			include_once UR_ABSPATH . 'modules/functions-ur-modules.php';
			include_once UR_ABSPATH . 'includes/functions-ur-form.php';
			include_once UR_ABSPATH . 'includes/class-ur-install.php';
			include_once UR_ABSPATH . 'includes/class-ur-post-types.php'; // Registers post types
			include_once UR_ABSPATH . 'includes/class-ur-user-approval.php'; // User Approval class
			include_once UR_ABSPATH . 'includes/class-ur-smart-tags.php'; // User Approval class.
			include_once UR_ABSPATH . 'includes/class-ur-emailer.php';
			include_once UR_ABSPATH . 'includes/class-ur-ajax.php';
			include_once UR_ABSPATH . 'includes/class-ur-query.php';
			include_once UR_ABSPATH . 'includes/class-ur-email-confirmation.php';
			include_once UR_ABSPATH . 'includes/class-ur-email-approval.php';
			include_once UR_ABSPATH . 'includes/class-ur-privacy.php';
			include_once UR_ABSPATH . 'includes/class-ur-form-block.php';
			include_once UR_ABSPATH . 'includes/class-ur-cache-helper.php';

			/**
			 * Block classes.
			 */
			include_once UR_ABSPATH . 'includes/blocks/class-ur-blocks.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-abstract.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-registration-form.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-login-form.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-myaccount.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-edit-profile.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-edit-password.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-login-logout-menu.php';
			include_once UR_ABSPATH . 'includes/pro/blocks/class-ur-pro-blocks.php';
			include_once UR_ABSPATH . 'includes/pro/blocks/block-types/class-ur-pro-block-view-profile-details.php';
			include_once UR_ABSPATH . 'includes/pro/blocks/block-types/class-ur-pro-block-popup.php';
			include_once UR_ABSPATH . 'includes/pro/blocks/block-types/class-ur-pro-block-frontend-listing.php';
			include_once UR_ABSPATH . 'includes/pro/blocks/block-types/class-ur-pro-block-download-pdf-button.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-login-logout-menu.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-membership-listing.php';
			include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-thank-you.php';
			// Validation classes.
			include_once UR_ABSPATH . 'includes/validation/class-ur-validation.php';
			include_once UR_ABSPATH . 'includes/validation/class-ur-form-validation.php';
			include_once UR_ABSPATH . 'includes/validation/class-ur-setting-validation.php';

			include_once UR_ABSPATH . 'includes/RestApi/class-ur-rest-api.php';
			include_once UR_ABSPATH . 'includes/RestApi/class-ur-pro-rest-api.php';

			/**
			 * Include Pro Classes.
			 */
			include_once UR_ABSPATH . 'includes/pro/class-user-registration-pro.php';

			/**
			 * Config classes.
			 */
			include_once UR_ABSPATH . 'includes/admin/class-ur-config.php';

			/** include modules */
			if ( ur_check_module_activation( 'membership' ) ) {
				include_once UR_ABSPATH . 'modules/membership/user-registration-membership.php';
			}

			if (ur_check_module_activation( 'payment-history' ) || (( ur_check_module_activation( 'membership' ) || ur_check_module_activation( 'payments' ) ) && ur_check_module_activation( 'payment-history' )) ) {
				include_once UR_ABSPATH . 'modules/payment-history/Orders.php';
			}

			if ( ur_check_module_activation( 'content-restriction' ) ) {
				include_once UR_ABSPATH . 'modules/content-restriction/user-registration-content-restriction.php';
				include_once UR_ABSPATH . 'includes/blocks/block-types/class-ur-block-content-restriction.php';
			}

			/**
			 * Elementor classes.
			 */
			if ( class_exists( '\Elementor\Plugin' ) ) {
				include_once UR_ABSPATH . 'includes/3rd-party/elementor/class-ur-elementor.php';
			}

			/**
			 * Oxygen classes.
			 */
			if ( in_array( 'oxygen/functions.php', get_option( 'active_plugins', array() ), true ) ) {
				include_once UR_ABSPATH . 'includes/3rd-party/oxygen/class-ur-oxygen.php';
			}
			// Divi builder compatiblity.
			if ( class_exists( 'WPEverest\URM\Pro\External\DiviBuilder\Builder' ) ) {
				WPEverest\URM\Pro\External\DiviBuilder\Builder::init();
			}
			if ( class_exists( 'WPEverest\URM\DiviBuilder\Builder' ) ) {
				WPEverest\URM\DiviBuilder\Builder::init();
			}

			/**
			 * Plugin/Addon Updater.
			 */
			include_once UR_ABSPATH . 'includes/class-ur-plugin-updater.php';

			if ( $this->is_request( 'admin' ) ) {
				include_once UR_ABSPATH . 'includes/admin/class-ur-admin.php';
				include_once UR_ABSPATH . 'includes/abstracts/abstract-ur-meta-boxes.php';
				include_once UR_ABSPATH . 'includes/admin/class-ur-admin-embed-wizard.php';
			}

			if ( $this->is_request( 'frontend' ) ) {
				$this->frontend_includes();
			}

			if ( $this->is_request( 'frontend' ) || $this->is_request( 'cron' ) ) {
				include_once UR_ABSPATH . 'includes/class-ur-session-handler.php';
			}
			include_once UR_ABSPATH . 'includes/class-ur-cron.php';
			include_once UR_ABSPATH . 'includes/stats/class-ur-stats.php';

			$this->query = new UR_Query();
		}

		/**
		 * Include required frontend files.
		 */
		public function frontend_includes() {
			include_once UR_ABSPATH . 'includes/functions-ur-notice.php';
			include_once UR_ABSPATH . 'includes/class-ur-form-handler.php';                   // Form Handlers
			include_once UR_ABSPATH . 'includes/class-ur-frontend-scripts.php';               // Frontend Scripts
			include_once UR_ABSPATH . 'includes/frontend/class-ur-frontend.php';
			include_once UR_ABSPATH . 'includes/class-ur-preview.php';
		}

		/**
		 * Function used to Init UserRegistration Template Functions - This makes them pluggable by plugins and themes.
		 */
		public function include_template_functions() {
			include_once UR_ABSPATH . 'includes/functions-ur-template.php';
		}

		/**
		 * Setup Objects.
		 *
		 * @since 1.7.2
		 */
		public function objects() {
			$this->form = new UR_Form_Handler();
		}

		/**
		 * Init UserRegistration when WordPress Initialises.
		 */
		public function init() {
			// Before init action.
			do_action( 'before_user_registration_init' );

			// Set up localisation.
			$this->load_plugin_textdomain();

			// Session class, handles session data for users - can be overwritten if custom handler is needed.
			if ( $this->is_request( 'frontend' ) || $this->is_request( 'cron' ) || $this->is_request( 'admin' ) ) {
				$session_class = apply_filters( 'user_registration_session_handler', 'UR_Session_Handler' );
				$this->session = new $session_class();
			}

			// Init action.
			do_action( 'user_registration_init' );
		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 *
		 * Locales found in:
		 *      - WP_LANG_DIR/user-registration/user-registration-LOCALE.mo
		 *      - WP_LANG_DIR/plugins/user-registration-LOCALE.mo
		 */
		public function load_plugin_textdomain() {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'user-registration' );

			unload_textdomain( 'user-registration', true );
			load_textdomain( 'user-registration', WP_LANG_DIR . '/user-registration/user-registration-' . $locale . '.mo' );
			load_plugin_textdomain( 'user-registration', false, plugin_basename( __DIR__ ) . '/languages' );
		}

		/**
		 * Get the plugin url.
		 *
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Get the template path.
		 *
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'user_registration_template_path', 'user-registration/' );
		}

		/**
		 * Get Ajax URL.
		 *
		 * @return string
		 */
		public function ajax_url() {
			return admin_url( 'admin-ajax.php', 'relative' );
		}

		/**
		 * Display action links in the Plugins list table.
		 *
		 * @param array $actions Plugin Action links.
		 *
		 * @return array
		 */
		public static function plugin_action_links( $actions ) {
			$new_actions = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=user-registration-settings' ) . '" aria-label="' . esc_attr__( 'View User Registration settings', 'user-registration' ) . '">' . esc_html__( 'Settings', 'user-registration' ) . '</a>',
			);

			return array_merge( $new_actions, $actions );
		}

		/**
		 * Display row meta in the Plugins list table.
		 *
		 * @param array  $plugin_meta Plugin Row Meta.
		 * @param string $plugin_file Plugin Row Meta.
		 *
		 * @return array
		 */
		public static function plugin_row_meta( $plugin_meta, $plugin_file ) {
			if ( UR_PLUGIN_BASENAME === $plugin_file ) {
				$new_plugin_meta = array(
					'docs'    => '<a href="' . esc_url( apply_filters( 'user_registration_docs_url', 'https://docs.wpuserregistration.com' ) ) . '" area-label="' . esc_attr__( 'View User Registration documentation', 'user-registration' ) . '">' . esc_html__( 'Docs', 'user-registration' ) . '</a>',
					'support' => '<a href="' . esc_url( apply_filters( 'user_registration_support_url', 'https://wpuserregistration.com/support/' ) ) . '" area-label="' . esc_attr__( 'Visit free customer support', 'user-registration' ) . '">' . __( 'Free support', 'user-registration' ) . '</a>',
				);

				return array_merge( $plugin_meta, $new_plugin_meta );
			}

			return (array) $plugin_meta;
		}

		/**
		 * Update notice
		 *
		 * @param array $args Plugin args.
		 */
		public static function in_plugin_update_message( $args ) {
			$transient_name = 'ur_upgrade_notice_' . $args['Version'];
			$upgrade_notice = get_transient( $transient_name );
			if ( false === $upgrade_notice ) {
				$response = ur_file_get_contents( '/readme.txt' );

				if ( ! empty( $response ) ) {
					$upgrade_notice = self::parse_update_notice( $response, $args['new_version'] );
					set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
				}
			}
			echo wp_kses_post( $upgrade_notice );
		}

		/**
		 * Parse update notice from readme.
		 *
		 * @param string $content Readme content.
		 * @param string $new_version New version.
		 */
		private static function parse_update_notice( $content, $new_version ) {
			// Output Upgrade Notice.
			$matches        = null;
			$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( UR_VERSION ) . '\s*=|$)~Uis';
			$upgrade_notice = '';
			if ( preg_match( $regexp, $content, $matches ) ) {
				$version = trim( $matches[1] );
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );
				// Check the latest stable version and ignore trunk.
				if ( $version === $new_version && version_compare( UR_VERSION, $version, '<' ) ) {
					$upgrade_notice .= '<div class="ur_plugin_upgrade_notice">';
					$upgrade_notice .= '<div class="ur_plugin_upgrade_notice_body">';
					foreach ( $notices as $line ) {
						$line = trim( $line ); // Remove extra whitespace
						if ( empty( $line ) ) {
							continue; // Skip empty lines
						}
						$line = preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="$2">$1</a>', $line );
						$line = preg_replace( '~^###\s*(.*)~', '<p class="upgrade-title" style="font-size: 14px;font-weight: 600" >$1</p>', $line );
						if ( ! preg_match( '~^<h3>|<p>|<a |<ul>|<ol>|<li>~', $line ) ) {
							$line = '<p style="font-size: 12px;>' . $line . '</p>';
						}
						$upgrade_notice .= wp_kses_post( trim( $line ) );
					}
					$upgrade_notice .= '</div> ';
					$upgrade_notice .= '</div> ';
				}
			}
			return wp_kses_post( $upgrade_notice );
		}
	}

endif;

// Check if is_plugin_active function is defined or not and if not then include it.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Check to see if UR already defined and resolve conflicts while installing PRO version.
 *
 * @since 3.0.0
 */
if ( ! function_exists( 'UR' ) && ! is_plugin_active( 'user-registration/user-registration.php' ) ) {

	/**
	 * Main instance of UserRegistration.
	 *
	 * Returns the main instance of FT to prevent the need to use globals.
	 *
	 * @return UserRegistration
	 * @since  1.0.0
	 */
	function UR() {
		return UserRegistration::instance();
	}

	// Global for backwards compatibility.
	$GLOBALS['user-registration'] = UR();
}

if ( ! function_exists( 'is_user_registration_pro_compatible' ) ) {
	/**
	 * Print notice when pro is not compatible with Free's version.
	 */
	function is_user_registration_pro_compatible() {

		if ( get_transient( 'user_registration_pro_not_compatible' ) ) {
			$message = __( 'Please update your <code>user-registration</code> plugin (to at least 2.1.0 version) to use <code>user-registration-pro</code> addon.', 'user-registration' );
			echo '<div class="notice-warning notice is-dismissible"><p>' . sprintf( $message ) . '</p></div>';

			// Dectivate Pro and re-install free version when core version is less than 2.1.0;
			deactivate_plugins( 'user-registration-pro/user-registration.php' );
			activate_plugins( 'user-registration/user-registration.php' );
			delete_transient( 'user_registration_pro_not_compatible' );
		}
	}
}
add_action( 'admin_notices', 'is_user_registration_pro_compatible' );

if ( ! function_exists( 'user_registration_pro_activated' ) ) {
	/**
	 * When Pro version is activated, deactivate free version.
	 */
	function user_registration_pro_activated() {

		$plugins_path = WP_PLUGIN_DIR . '/user-registration-extras/user-registration-extras.php';

		if ( file_exists( $plugins_path ) ) {
			user_registration_migrate_extras_to_pro();
		}
		set_transient( 'user_registration_pro_activated', true );

		if ( is_plugin_active( 'user-registration/user-registration.php' ) ) {

			if ( version_compare( UR()->version, '2.1.0', '<' ) ) {
				set_transient( 'user_registration_pro_not_compatible', true );
			} else {
				user_registration_free_deactivate();
			}
		}
	}
}

add_action( 'activate_user-registration-pro/user-registration.php', 'user_registration_pro_activated' );

if ( ! function_exists( 'user_registration_free_activated' ) ) {
	/**
	 * When user activates free version, set the value that is to be used to handle both Free and Pro activation conflict.
	 */
	function user_registration_free_activated() {

		set_transient( 'user_registration_free_activated', true );
	}
}
add_action( 'activate_user-registration/user-registration.php', 'user_registration_free_activated' );

if ( ! function_exists( 'user_registration_free_deactivated' ) ) {
	/**
	 * When user deactivates free version, remove the value that was used to handle both Free and Pro activation conflict.
	 */
	function user_registration_free_deactivated() {

		global $user_registration_free_activated, $user_registration_free_deactivated;

		$user_registration_free_activated   = (bool) get_transient( 'user_registration_free_activated' );
		$user_registration_free_deactivated = true;

		delete_transient( 'user_registration_free_activated' );
	}
}
add_action( 'deactivate_user-registration/user-registration.php', 'user_registration_free_deactivated' );

if ( ! function_exists( 'user_registration_free_deactivate' ) ) {
	/**
	 * Deactivate Free version if Pro is already activated.
	 *
	 * @since 1.0.0
	 */
	function user_registration_free_deactivate() {

		$plugin = 'user-registration/user-registration.php';

		deactivate_plugins( $plugin );

		do_action( 'user_registration_free_deactivate', $plugin );
		delete_transient( 'user_registration_pro_activated' );
	}
}
add_action( 'admin_init', 'user_registration_free_deactivate' );

if ( ! function_exists( 'user_registration_free_notice' ) ) {
	/**
	 * When user wants to activate Free version alongside Pro, then display the message.
	 */
	function user_registration_free_notice() {

		global $user_registration_free_activated, $user_registration_free_deactivated;

		if (
			empty( $user_registration_free_activated ) ||
			empty( $user_registration_free_deactivated )
		) {
			return;
		}

		echo '<div class="notice-warning notice is-dismissible"><p>' . wp_kses_post( 'As <strong>User Registration Pro</strong> is active, <strong>User Registration Free</strong> is now not needed.', 'user-registration' ) . '</p></div>';

		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		unset( $user_registration_free_activated, $user_registration_free_deactivated );
	}
}
add_action( 'admin_notices', 'user_registration_free_notice' );

if ( ! function_exists( 'user_registration_migrate_extras_to_pro' ) ) {
	/**
	 * Migrate all the extras addon data and deactivate it if pro is activated.
	 *
	 * @since 1.0.0
	 */
	function user_registration_migrate_extras_to_pro() {
		global $wpdb;

		if ( ! get_option( 'user_registration_extras_migrated_to_pro', '' ) ) {

			$options = $wpdb->get_results( "SELECT * FROM $wpdb->options WHERE option_name LIKE 'user_registration_extras_%';" );

			if ( ! empty( $options ) ) {

				foreach ( $options as $option ) {
					$option_name     = $option->option_name;
					$option_value    = $option->option_value;
					$new_option_name = str_replace( 'user_registration_extras_', 'user_registration_pro_', $option_name );
					update_option( $new_option_name, $option_value, $option->autoload );
				}

				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'user_registration_extras_%';" );
			}

			$wpdb->query( "UPDATE $wpdb->posts SET post_type='ur_pro_popup' WHERE post_type='ur_extras_popup';" );

			// Check if honeypot is enabled in a form and update meta accordingly.
			if ( ! empty( get_option( 'user_registration_pro_spam_protection_by_honeypot_enabled_forms', array() ) ) ) {
				$honeypot_enabled_forms = maybe_unserialize( get_option( 'user_registration_pro_spam_protection_by_honeypot_enabled_forms', array() ) );

				foreach ( $honeypot_enabled_forms as $form ) {
					update_post_meta( $form, 'user_registration_pro_spam_protection_by_honeypot_enable', true );
				}
			}

			// Check if auto password generation is enabled in a form and update meta accordingly.
			if ( ! empty( get_option( 'user_registration_auto_password_activated_forms', array() ) ) ) {
				$auto_password_enabled_forms = maybe_unserialize( get_option( 'user_registration_auto_password_activated_forms', array() ) );

				foreach ( $auto_password_enabled_forms as $form ) {
					update_post_meta( $form, 'user_registration_pro_auto_password_activate', true );
					update_post_meta( $form, 'user_registration_pro_auto_generated_password_length', get_option( 'user_registration_extras_auto_generated_password_length' ) );
				}
			}

			update_option( 'user_registration_extras_migrated_to_pro', true );
		}

		if ( is_plugin_active( 'user-registration-extras/user-registration-extras.php' ) ) {
			deactivate_plugins( 'user-registration-extras/user-registration-extras.php' );
		}
	}
}

if ( ! function_exists( 'user_registration_extras_activated' ) ) {
	/**
	 * Deactivate extras and show notice when trying to activate extras when pro is still active
	 *
	 * @since 3.0.0
	 */
	function user_registration_extras_activated() {
		set_transient( 'user_registration_pro_need_to_deactivate_extras', true );
	}
}
add_action( 'activate_user-registration-extras/user-registration-extras.php', 'user_registration_extras_activated' );

if ( ! function_exists( 'user_registration_pro_need_to_deactivate_extras' ) ) {
	/**
	 * Deactivate extras and show notice when trying to activate extras when pro is still active
	 *
	 * @since 3.0.0
	 */
	function user_registration_pro_need_to_deactivate_extras() {
		echo '<div class="notice-warning notice is-dismissible"><p>' . wp_kses_post( 'As <strong>User Registration Pro</strong> is active, <strong>User Registration Extras</strong> is now not needed.', 'user-registration' ) . '</p></div>';
		deactivate_plugins( 'user-registration-extras/user-registration-extras.php' );

		delete_transient( 'user_registration_pro_need_to_deactivate_extras' );
	}
}

if ( get_transient( 'user_registration_pro_need_to_deactivate_extras' ) ) {
	add_action( 'admin_notices', 'user_registration_pro_need_to_deactivate_extras' );
}

if ( ! function_exists( 'user_registration_membership_addon_notice' ) ) {
	/**
	 * When user wants to activate Membership Addon alongside Pro, then display the message since membership has been shifted as a module.
	 */
	function user_registration_membership_addon_notice() {
		if ( ! isset( $_GET['plugin'] ) ) {
			return;
		}
		if ( 'user-registration-membership/user-registration-membership.php' === $_GET['plugin'] ) {
			echo '<div class="notice-warning notice is-dismissible"><p>' . wp_kses_post( __( 'Since membership has been integrated as a module in the main plugin itself, <strong>User Registration Membership</strong> is now not needed.', 'user-registration' ) ) . '</p></div>';

		}
	}
}
add_action( 'admin_notices', 'user_registration_membership_addon_notice' );
