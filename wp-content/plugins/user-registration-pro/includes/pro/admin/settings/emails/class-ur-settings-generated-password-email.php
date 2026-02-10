<?php
/**
 * Configure Email
 *
 * @class    User_Registration_Settings_Admin_Email
 * @extends  User_Registration_Settings_Email
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UR_Settings_Auto_Generated_Password_Email', false ) ) :

	/**
	 * UR_Settings_Auto_Generated_Password_Email Class.
	 */
	class UR_Settings_Auto_Generated_Password_Email {
		/**
		 * UR_Settings_Auto_Generated_Password_Email Id.
		 *
		 * @var string
		 */
		public $id;

		/**
		 * UR_Settings_Auto_Generated_Password_Email Title.
		 *
		 * @var string
		 */
		public $title;

		/**
		 * UR_Settings_Auto_Generated_Password_Email Description.
		 *
		 * @var string
		 */
		public $description;

		/**
		 * UR_Settings_Delete_Account_Admin_Email Receiver.
		 *
		 * @var string
		 */
		public $receiver;

		public function __construct() {
			$this->id          = 'auto_generated_password_email';
			$this->title       = esc_html__( 'Auto-Generated Password Notification', 'user-registration' );
			$this->description = esc_html__( 'Email sent to the user on enabling auto password generation', 'user-registration' );
			$this->receiver    = 'User';
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_generated_password_email',
				array(
					'title'    => __( 'Emails', 'user-registration' ),
					'sections' => array(
						'generated_password_email' => array(
							'title'        => __( 'Auto-Generated Password Email', 'user-registration' ),
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
									'desc'     => __( 'Enable this email sent to the user after succesful registration.', 'user-registration' ),
									'id'       => 'user_registration_enable_auto_generated_password_email',
									'default'  => 'yes',
									'type'     => 'toggle',
									'autoload' => false,
								),
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_auto_generated_password_email_subject',
									'type'     => 'text',
									'default'  => __( 'Your Auto-Generated Password for {{blog_info}}', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_auto_generated_password_email_content',
									'type'     => 'tinymce',
									'default'  => $this->user_registration_get_auto_generated_password_email(),
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
		public static function user_registration_get_auto_generated_password_email() {

			$message = apply_filters(
				'user_registration_get_auto_generated_password_email',
				sprintf(
					__(
						'Hi {{username}}, <br/>

						Your registration on <a href="{{home_url}}">{{blog_info}}</a>  has been successfully completed. <br/>

						You can now log into your account using the following auto-generated password: <br/>
						{{auto_pass}} <br/>
						For security reasons, we recommend changing your password after logging in. <br>
						Thank You!',
						'user-registration'
					)
				)
			);

			return $message;
		}
	}
endif;

return new UR_Settings_Auto_Generated_Password_Email();
