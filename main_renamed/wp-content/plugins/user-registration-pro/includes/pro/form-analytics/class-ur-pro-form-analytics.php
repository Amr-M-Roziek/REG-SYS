<?php
/**
 * Class
 *
 * User_Registration_Pro_Form_Analytics
 *
 * @package User_Registration_Pro
 * @since  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Pro_Form_Analytics' ) ) {

	/**
	 * User_Registration_Pro_Form_Analytics class.
	 */
	class User_Registration_Pro_Form_Analytics {

		/**
		 * Constructor of the class.
		 */
		public function __construct() {
			$this->includes();
			add_action( 'init', array( $this, 'create_user_activity_tracker_table' ), 4 );
		}

		/**
		 * Include necessary files and classes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function includes() {
			require_once 'class-ur-pro-form-analytics-helpers.php';
			if ( $this->is_request( 'admin' ) ) {
				require_once 'class-ur-pro-user-journey-analytics.php';
				new User_Registration_Pro_User_Journey_Analytics();
			}

			if ( $this->is_request( 'frontend' ) ) {
				require_once 'class-ur-pro-user-journey.php';
				require_once 'class-ur-pro-form-abandonment.php';
				new User_Registration_Pro_User_Journey();
				new User_Registration_Pro_Form_Abandonment();
			}
			add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_assets' ) );
		}

		/**
		 * Enqueue frontend styles and scripts.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function add_frontend_assets() {
			$min = ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) ? '.min' : '';

			wp_register_script(
				'ur-form-analytics',
				UR()->plugin_url() . '/assets/js/pro/frontend/user-registration-form-analytics' . $min . '.js',
				array( 'jquery' ),
				time(),
				true
			);

			wp_localize_script(
				'ur-form-analytics',
				'urFormAnalyticsl10n',
				apply_filters(
					'ur_form_analytics_localization',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
					)
				)
			);

			if ( ur_option_checked( 'user_registration_enable_user_activity', false ) ) {
				wp_enqueue_script( 'ur-form-analytics' );
			}
		}

		/**
		 * What type of request is this?
		 *
		 * @since 1.0.0
		 *
		 * @param string $type admin, ajax, cron or frontend.
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
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
			}
		}

		//create user activity tracker table.
		public function create_user_activity_tracker_table() {
			global $wpdb;

			$table_name      = $wpdb->prefix . 'ur_user_post_visits';
			$abandoned_table = $wpdb->prefix . 'ur_abandoned_data';
			$abandoned_meta  = $wpdb->prefix . 'ur_abandoned_meta';
			$charset_collate = '';

			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			if ( $wpdb->has_cap( 'collation' ) ) {
				$charset_collate = $wpdb->get_charset_collate();
			}

			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				session_id VARCHAR(255) NOT NULL,
				page_url VARCHAR(255) NOT NULL,
				referer_url VARCHAR(255) NOT NULL,
				duration INT(11) NULL,
				user_id BIGINT(20) NULL,
				form_id INT(11) NULL,
				form_abandoned BOOLEAN DEFAULT 0,
				form_submitted BOOLEAN DEFAULT 0,
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) $charset_collate;";

			$abandoned = "CREATE TABLE IF NOT EXISTS $abandoned_table (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				form_id INT(11) NULL,
				referer VARCHAR(255) NULL,
				user_id BIGINT(20) NULL,
				fields LONGTEXT NULL,
				status varchar(20),
				created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) $charset_collate;";

			$meta = "CREATE TABLE IF NOT EXISTS $abandoned_meta (
				meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				abandon_id VARCHAR(255) NOT NULL,
				meta_key VARCHAR(255) NULL,
				meta_value LONGTEXT NULL,
				PRIMARY KEY (meta_id)
			) $charset_collate;";

			maybe_create_table( $table_name, $sql );
			maybe_create_table( $abandoned_table, $abandoned );
			maybe_create_table( $abandoned_meta, $meta );

		}

	}
}

new User_Registration_Pro_Form_Analytics();
