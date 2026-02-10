<?php
/**
 * Admin Notification class
 *
 * User_Registration_Pro Admin Notification
 *
 * @package User_Registration_Pro
 * @since  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Pro_Admin_Notification' ) ) {
	/**
	 * Admin Notification class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class User_Registration_Pro_Admin_Notification {

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// License Expiry Notice.
			add_action( 'admin_notices', array( $this, 'user_registration_license_expiry_notice' ) );
			add_action( 'admin_notices', array( $this, 'user_registration_translation_migration_admin_notice' ), 10 );
			add_filter( 'user_registration_get_remote_notices', array( $this, 'remote_notices' ) );
		}

		public function remote_notices( $notices ) {
			$notices = get_transient( 'user_registration_remote_admin_notices' );
			if ( false === $notices ) {

				$raw_section = wp_remote_get( 'https://stats.wpeverest.com/notice-json/user-registration/notices.json' );

				if ( is_wp_error( $raw_section ) ) {
					return new \WP_REST_Response(
						array(
							'success' => false,
							'message' => $raw_section->get_error_message(),
						),
						400
					);
				}

				// Get Notices Lists.
				$notices = json_decode( wp_remote_retrieve_body( $raw_section ), true );
				$notices = $notices['notices'];
				set_transient( 'user_registration_remote_admin_notices', $notices, DAY_IN_SECONDS );
			}
			return $notices;
		}

		/**
		 * Send admin notice if user have translated payments, content-restriction and Frontend Listing.
		 *
		 * @since 4.1.5
		 */
		public function user_registration_translation_migration_admin_notice() {
			$merge_addons     = array( 'payments', 'content-restriction', 'frontend-listing' );
			$addon_names      = array();
			$move_translation = '';
			$i                = 0;

			foreach ( $merge_addons as $addon ) {

				$current_language = ur_get_current_language();

				if ( ( strpos( $current_language, 'en' ) !== 0 && $current_language !== 'en' ) && is_plugin_active( 'user-registration-' . $addon . '/user-registration-' . $addon . '.php' ) ) {

					$plugin_source_dir = ABSPATH . 'wp-content/plugins/user-registration-' . $addon . '/languages';
					$global_source_dir = ABSPATH . 'wp-content/languages/plugins/';
					$source_paths      = array( $plugin_source_dir, $global_source_dir );

					$translation_found = false;

					foreach ( $source_paths as $source_dir ) {
						$source_files = glob( $source_dir . '/*.po' );

						foreach ( $source_files as $source_file ) {
							$language_code = basename( $source_file, '.po' );
							if ( strpos( $language_code, 'user-registration-' . $addon ) !== false ) {
								$translation_found = true;
								break 2;
							}
						}
					}

					if ( ! $translation_found ) {
						++$i;
						$addon_names[]     = 'User Registration ' . ucwords( str_replace( '-', ' ', $addon ) );
						$move_translation .= '<br>' . $i . ". Please move 'user-registration-" . $addon . ".po' and 'user-registration-" . $addon . ".mo' files to either 'wp-content/plugins/user-registration-" . $addon . "/languages' or 'wp-content/languages/plugins'";
					}
				}
			}

			if ( ! empty( $addon_names ) ) {
				$addons = implode( ', ', $addon_names );

				$class   = 'notice notice-warning is-dismissible user-registration-notice';
				$message = "<br>We're combining the <b>" . $addons . "</b> addons into the <b>User Registration Pro</b>. To retain your translated addons, we've created a migration script to be implemented from the upcoming update. If you have translated the listed addons then <br>

				" . $move_translation;

				$thumbnail = '<div class="user-registration-notice-thumbnail">
							<img src="' . esc_url( UR()->plugin_url() . '/assets/images/UR-Logo.gif' ) . '" alt="">
							</div>';

				printf( '<div class="%1$s">%2$s<div class="user-registration-notice-text"><div class="user-registration-notice-header"><h3>Attention Users,</h3></div><p>%3$s</p></div></div>', esc_attr( $class ), wp_kses_post( $thumbnail ), wp_kses_post( $message ) );
			} else {
				return;
			}
		}


		/**
		 * Display Expiry notice.
		 *
		 * @since 3.2.4
		 */
		public function user_registration_license_expiry_notice() {
			// Get the license expiry date.
			$license_data = ur_get_license_plan();

			if ( empty( $license_data ) || 'lifetime' === $license_data->expires ) {
				return;
			}

			$license_expiry_date = strtotime( $license_data->expires );

			// Check if the expiration date has passed.
			if ( $license_expiry_date < time() ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// Get the last dismissed time.
				$last_dismissed_time = get_option( 'user_registration_license_expiry_notice_last_dismissed_time' );
				$last_notice_count   = get_option( 'user_registration_license_expiry_notice_last_notice_count', 0 );
				// Check if the notice has been dismissed before.
				if ( ! $last_dismissed_time && ! $last_notice_count ) {
					$this->user_registration_expiry_notice_content();
				} elseif ( '1' === $last_notice_count && strtotime( '+1 days', strtotime( $last_dismissed_time ) ) <= time() ) {
					$this->user_registration_expiry_notice_content();
				} elseif ( '2' === $last_notice_count && strtotime( '+7 days', strtotime( $last_dismissed_time ) ) <= time() ) {
					$this->user_registration_expiry_notice_content();
				} elseif ( '3' === $last_notice_count && strtotime( '+15 days', strtotime( $last_dismissed_time ) ) <= time() ) {
					$this->user_registration_expiry_notice_content();
				} elseif ( '4' === $last_notice_count && strtotime( '+30 days', strtotime( $last_dismissed_time ) ) <= time() ) {
					$this->user_registration_expiry_notice_content();
				} elseif ( '5' === $last_notice_count && strtotime( '+60 days', strtotime( $last_dismissed_time ) ) <= time() ) {
					$this->user_registration_expiry_notice_content();
				}
			}
		}

		/**
		 * License expiry notice message.
		 *
		 * @since 3.2.4
		 */
		public function user_registration_expiry_notice_content() {
			?>
			<div class="ur-license-expiry-notice notice notice-error is-dismissible">
				<p>
					<?php
					/* translators:My Account Page */
					echo wp_kses_post( sprintf( __( ' Your license has been expired. Please renew the license from %1$sMy Account Page%2$s.', 'user-registration' ), '<a href="https://wpeverest.com/login/" rel="noreferrer noopener" target="_blank">', '</a>' ) );
					?>
				</p>
			</div>
			<?php
		}
	}

}

return new User_Registration_Pro_Admin_Notification();
