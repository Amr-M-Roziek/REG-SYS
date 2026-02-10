<?php
/**
 * Configure Email
 *
 * @package  UR_Settings_Passwordless_Login_Email
 * @extends  UR_Settings_Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'UR_Settings_Passwordless_Login_Email', false ) ) :

	/**
	 * UR_Settings_Passwordless_Login_Email Class.
	 */
	class UR_Settings_Passwordless_Login_Email {
		/**
		 * UR_Settings_Passwordless_Login_Email Id.
		 *
		 * @var string
		 */
		public $id;

		/**
		 * UR_Settings_Passwordless_Login_Email Title.
		 *
		 * @var string
		 */
		public $title;

		/**
		 * UR_Settings_Passwordless_Login_Email Description.
		 *
		 * @var string
		 */
		public $description;

		/**
		 * UR_Settings_Passwordless_Login_Email Receiver.
		 *
		 * @var string
		 */
		public $receiver;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'passwordless_login_email';
			$this->title       = __( 'Passwordless Login', 'user-registration' );
			$this->description = __( 'Provides the user a secure one-time login link for a passwordless authentication process.', 'user-registration' );
			$this->receiver    = 'User';
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_passwordless_login',
				array(
					'title'    => __( 'Emails', 'user-registration' ),
					'sections' => array(
						'passwordless_login_email' => array(
							'title'        => __( 'Passwordless Login Email', 'user-registration' ),
							'type'         => 'card',
							'desc'         => '',
							'back_link'    => ur_back_link( __( 'Return to emails', 'user-registration' ), admin_url( 'admin.php?page=user-registration-settings&tab=email&section=to-user' ) ),
							'preview_link' => ur_email_preview_link(
								__( 'Preview', 'user-registration' ),
								$this->id
							),
							'settings'     => array(
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_passwordless_login_email_subject',
									'type'     => 'text',
									'default'  => __( 'Passwordless Login Request for {{blog_info}}', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),

								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_passwordless_login_email_content',
									'type'     => 'tinymce',
									'default'  => $this->ur_get_passwordless_login_email(),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
							),
						),
					),
				)
			);

			return apply_filters( 'user_registration_get_settings_' . $this->id, $settings );
		}

		/**
		 * Email Format.
		 */
		public function ur_get_passwordless_login_email() {

			$message = apply_filters(
				'ur_magic_login_link_email_message',
				sprintf(
					__(
						'Hello {{username}},<br><br>We received a request to log in to your account without a password.<br>To log in, simply click the link below: <br/>
 						<a href={{passwordless_login_link}}>Log In</a><br><br>
 						If you did not request this login, please ignore this email, and no further action will be taken.<br><br>
 						Thank you,<br>
 						{{blog_info}}',
						'user-registration'
					)
				)
			);
			return $message;
		}
	}
endif;

return new UR_Settings_Passwordless_Login_Email();
