<?php
/**
 * Configure Email
 *
 * @class    UR_Settings_Delete_Account_Admin_Email
 * @extends  User_Registration_Settings_Email
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UR_Settings_Delete_Account_Admin_Email', false ) ) :

	/**
	 * UR_Settings_Delete_Account_Admin_Email Class.
	 */
	class UR_Settings_Delete_Account_Admin_Email {
		/**
		 * UR_Settings_Delete_Account_Admin_Email Id.
		 *
		 * @var string
		 */
		public $id;

		/**
		 * UR_Settings_Delete_Account_Admin_Email Title.
		 *
		 * @var string
		 */
		public $title;

		/**
		 * UR_Settings_Delete_Account_Admin_Email Description.
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

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'delete_account_admin_email';
			$this->title       = esc_html__( 'Account – Deletion Notification', 'user-registration' );
			$this->description = esc_html__( 'Notifies admins that a user has deleted their account.', 'user-registration' );
			$this->receiver    = 'Admin';
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_delete_account_admin_email',
				array(
					'title'    => __( 'Emails', 'user-registration' ),
					'sections' => array(
						'delete_account_admin_email' => array(
							'title'        => __( 'Account – Deletion Notification Email', 'user-registration' ),
							'type'         => 'card',
							'desc'         => '',
							'back_link'    => ur_back_link( __( 'Return to emails', 'user-registration' ), admin_url( 'admin.php?page=user-registration-settings&tab=email' ) ),
							'preview_link' => ur_email_preview_link(
								__( 'Preview', 'user-registration' ),
								$this->id
							),
							'settings'     => array(
								array(
									'title'    => __( 'Enable this email', 'user-registration' ),
									'desc'     => __( 'Enable this email sent to the admin after user deletes thier own account', 'user-registration' ),
									'id'       => 'user_registration_enable_delete_account_admin_email',
									'default'  => 'yes',
									'type'     => 'toggle',
									'autoload' => false,
								),

								array(
									'title'    => __( 'Email Receipents', 'user-registration' ),
									'desc'     => __( 'Use comma to send emails to multiple receipents.', 'user-registration' ),
									'id'       => 'user_registration_pro_delete_account_email_receipents',
									'default'  => get_option( 'admin_email' ),
									'type'     => 'text',
									'css'      => 'min-width: 350px;',
									'autoload' => false,
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_delete_account_admin_email_subject',
									'type'     => 'text',
									'default'  => __( 'Account Deletion Notification – User Account Removed from {{blog_info}}', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_delete_account_admin_email_content',
									'type'     => 'tinymce',
									'default'  => $this->user_registration_get_delete_account_admin_email(),
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
		public static function user_registration_get_delete_account_admin_email() {

			$message = apply_filters(
				'user_registration_get_delete_account_admin_email',
				sprintf(
					__(
						'Hi Admin, </br>
						The user {{username}} has just deleted their account on {{blog_info}}. </br>
						Thank you!
						',
						'user-registration'
					)
				)
			);

			return $message;
		}
	}
endif;

return new UR_Settings_Delete_Account_Admin_Email();
