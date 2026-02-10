<?php
/**
 * Configure Email
 *
 * @class   User_Registration_Settings_Prevent_Concurrent_Email
 * @extends  User_Registration_Settings_Email
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UR_Settings_Prevent_Concurrent_Login_Email', false ) ) :

	/**
	 * User_Registration_Settings_Prevent_Concurrent_Email Class.
	 */
	class UR_Settings_Prevent_Concurrent_Login_Email {
		/**
		 * UR_Settings_Prevent_Concurrent_Login_Email Id.
		 *
		 * @var string
		 */
		public $id;

		/**
		 * UR_Settings_Prevent_Concurrent_Login_Email Title.
		 *
		 * @var string
		 */
		public $title;

		/**
		 * UR_Settings_Prevent_Concurrent_Login_Email Description.
		 *
		 * @var string
		 */
		public $description;

		/**
		 * UR_Settings_Prevent_Concurrent_Login_Email Receiver.
		 *
		 * @var string
		 */
		public $receiver;

		/**
		 * constructor
		 */
		public function __construct() {
			$this->id          = 'prevent_concurrent_login_email';
			$this->title       = esc_html__( 'Prevent Concurrent Login ', 'user-registration' );
			$this->description = esc_html__( 'Informs the user that simultaneous logins limits have reached or an unauthorized login attempt was blocked.', 'user-registration' );
			$this->receiver    = 'User';
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_prevent_concurrent_login_email',
				array(
					'title'    => __( 'Emails', 'user-registration' ),
					'sections' => array(
						'prevent_concurrent_email' => array(
							'title'        => __( 'Prevent Concurrent Login Email', 'user-registration' ),
							'type'         => 'card',
							'desc'         => '',
							'back_link'    => ur_back_link( __( 'Return to emails', 'user-registration' ), admin_url( 'admin.php?page=user-registration-settings&tab=email&section=to-user' ) ),
							'preview_link' => ur_email_preview_link(
								__( 'Preview', 'user-registration' ),
								$this->id
							),
							'settings'     => array(
								array(
									'title'    => __( 'Enable this email', 'user-registration' ),
									'desc'     => __( 'Enable this email sent to the user after succesfully email sent', 'user-registration' ),
									'id'       => 'user_registration_enable_prevent_concurrent_login_email',
									'default'  => 'yes',
									'type'     => 'toggle',
									'autoload' => false,
								),
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_prevent_concurrent_login_email_subject',
									'type'     => 'text',
									'default'  => __( 'Action Required: Force Logout Request for Your Account', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_prevent_concurrent_login_email_content',
									'type'     => 'tinymce',
									'default'  => $this->user_registration_get_prevent_concurrent_login_email(),
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
		public static function user_registration_get_prevent_concurrent_login_email() {

			$message = apply_filters(
				'user_registration_reset_password_email_message',
				sprintf(
					__(
						'Hi {{username}},
						A request has been made to force logout of your account.<br/>
						If this request was made by mistake, simply ignore this email, and no action will be taken.<br/>
						To proceed with the force logout, click the link below:<br/>
						<a href="{{home_url}}/{{ur_login}}?action=force-logout&login={{user_id}}" rel="noreferrer noopener" target="_blank">Click Here</a><br/>
						Thank You!',
						'user-registration'
					)
				)
			);

			return $message;
		}
	}
endif;

return new UR_Settings_Prevent_Concurrent_Login_Email();
