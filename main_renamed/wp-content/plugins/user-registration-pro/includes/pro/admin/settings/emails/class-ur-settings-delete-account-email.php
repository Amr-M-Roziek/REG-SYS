<?php
/**
 * Configure Email
 *
 * @class    UR_Settings_Delete_Account_Email
 * @extends  User_Registration_Settings_Email
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UR_Settings_Delete_Account_Email', false ) ) :

	/**
	 * UR_Settings_Delete_Account_Email Class.
	 */
	class UR_Settings_Delete_Account_Email {
		/**
		 * UR_Settings_Delete_Account_Email Id.
		 *
		 * @var string
		 */
		public $id;

		/**
		 * UR_Settings_Delete_Account_Email Title.
		 *
		 * @var string
		 */
		public $title;

		/**
		 * UR_Settings_Delete_Account_Email Description.
		 *
		 * @var string
		 */
		public $description;

		/**
		 * UR_Settings_Delete_Account_Email Receiver.
		 *
		 * @var string
		 */
		public $receiver;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'delete_account_email';
			$this->title       = esc_html__( 'Account Deletion Confirmation', 'user-registration' );
			$this->description = esc_html__( 'Informs the user their account deletion was successful.', 'user-registration' );
			$this->receiver    = 'User';
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_delete_account_email',
				array(
					'title'    => __( 'Emails', 'user-registration' ),
					'sections' => array(
						'delete_account_admin_email' => array(
							'title'        => __( 'Delete Account Email', 'user-registration' ),
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
									'desc'     => __( 'Enable this email sent to the user after succesfully deletes thier own account', 'user-registration' ),
									'id'       => 'user_registration_enable_delete_account_email',
									'default'  => 'yes',
									'type'     => 'toggle',
									'autoload' => false,
								),
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_delete_account_email_subject',
									'type'     => 'text',
									'default'  => __( 'Account Deletion Confirmation – Your Account Has Been Deleted', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_delete_account_email_content',
									'type'     => 'tinymce',
									'default'  => $this->user_registration_get_delete_account_email(),
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
		public static function user_registration_get_delete_account_email() {

			$message = apply_filters(
				'user_registration_get_delete_account_email',
				sprintf(
					__(
						'Hi {{username}},<br /><br />' .
										'We want to inform you that your account on {{blog_info}} has been successfully deleted. All of your personal information has been permanently removed, and you will no longer be able to log in to {{blog_info}}..<br /><br />' .
										'If this deletion was accidental, please contact us at {{admin_email}} for assistance.<br />' .
										'Thank you,<br />' .
										'{{blog_info}}',
						'user-registration'
					)
				)
			);

			return $message;
		}
	}
endif;

return new UR_Settings_Delete_Account_Email();
