<?php
/**
 * User Registration User Journey Class.
 *
 * @class User Journey
 * @package UserRegistration\UserJourney
 * @since   1.0.0
 */


defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'User_Registration_Pro_User_Journey' ) ) {
	/**
	 * Frontend class.
	 */
	class User_Registration_Pro_User_Journey {
		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'ur_form_analytics_localization', array( $this, 'add_localization' ) );

			add_action( 'wp_ajax_ur_save_user_post_view', array( $this, 'save_post_visit' ) );
			add_action( 'wp_ajax_nopriv_ur_save_user_post_view', array( $this, 'save_post_visit' ) );
			add_action( 'user_registration_after_register_user_action', array( $this, 'handle_form_submission' ), 10, 3 );
		}

		/**
		 * Update user id in table when user registers.
		 *
		 * @since 1.0.0
		 *
		 * @param [array] $form_data Form Data.
		 * @param [array] $form_id Form ID.
		 * @param [int]   $user_id Entry ID.
		 * @return void
		 */
		public function handle_form_submission( $form_data, $form_id, $user_id ) {
			$session_id = isset( $_COOKIE['ur_fa_tracking_id'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['ur_fa_tracking_id'] ) ) : '';

			if ( ! empty( $session_id ) ) {
				require_once 'DB/UserPostVisitsDB.php';
				$db_handler = new UserPostVisitsDB();
				$db_handler->add_user_id( $session_id, $user_id );
			}
		}

		/**
		 * Add localization parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param [array] $localizations Localization variable.
		 * @return array
		 */
		public function add_localization( $localizations ) {
			if ( $this->is_post_visit_request() ) {
				$localizations['save_visited_nonce'] = wp_create_nonce( 'save_visited_nonce' );
				$localizations['referer_page']     = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
			}

			return $localizations;
		}

		/**
		 * Check whether the user is requesting for a post/page.
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		public function is_post_visit_request() {
			if ( wp_doing_ajax() || is_admin() ) {
				return false;
			}

			if ( '/favicon.ico' === $_SERVER['REQUEST_URI'] ) {
				return false;
			}

			if ( ! empty( get_current_user_id() ) ) {
				return false;
			}

			if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) {
				return false;
			}

			return true;
		}


		/**
		 * Save data when user visits a certain page.
		 *
		 * @since 1.0.0
		 *
		 * @return boolean true or false;
		 */
		public function save_post_visit() {
			check_ajax_referer( 'save_visited_nonce' );

			if ( ur_option_checked( 'user_registration_enable_user_activity', false ) ) {
				$post_data      = isset( $_POST['data'] ) ? ur_clean( $_POST['data'] ) : array();
				$page_url       = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
				$session_id     = isset( $_COOKIE['ur_fa_tracking_id'] ) ? wp_unslash( sanitize_text_field( $_COOKIE['ur_fa_tracking_id'] ) ) : '';
				$duration       = isset( $post_data['user_session_duration'] ) ? wp_unslash( sanitize_text_field( $post_data['user_session_duration'] ) ) : 0;
				$form_id        = isset( $post_data['form_id'] ) ? sanitize_text_field( $post_data['form_id'] ) : 0;
				$form_abandoned = isset( $post_data['form_abandoned'] ) ? ur_string_to_bool( $post_data['form_abandoned'] ) : 0;
				$form_submitted = isset( $post_data['form_submitted'] ) ? ur_string_to_bool( $post_data['form_submitted'] ) : 0;
				$referer_url    = isset( $post_data['referer'] ) ? $post_data['referer'] : '';

				require_once 'DB/UserPostVisitsDB.php';
				$db_handler     = new UserPostVisitsDB();
				$user_id        = $db_handler->get_user_id_from_session_id( $session_id );

				if ( ! empty( $session_id ) && ! empty( $duration ) && ( empty( $user_id ) || $form_submitted ) ) {
					$user_post_visits_data = array(
							'session_id'     => $session_id,
							'page_url'       => $page_url,
							'referer_url'    => $referer_url,
							'duration'       => $duration,
							'form_id'        => $form_id,
							'form_abandoned' => $form_abandoned,
							'form_submitted' => $form_submitted,
							'user_id'       => ! empty( $user_id ) ? $user_id : null,
					);

					$db_handler->insert( $user_post_visits_data );
				}
			}
		}
	}
}
