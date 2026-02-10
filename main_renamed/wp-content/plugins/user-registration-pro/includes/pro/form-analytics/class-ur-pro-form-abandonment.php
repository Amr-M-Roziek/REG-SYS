<?php
/**
 * User Registration Pro Form Abandonment Class.
 *
 * @class User Journey
 * @package UserRegistration\FormAbandonment
 * @since   1.0.0
 */


defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'User_Registration_Pro_Form_Abandonment' ) ) {
	/**
	 * Frontend class.
	 */
	class User_Registration_Pro_Form_Abandonment {
		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'ur_form_analytics_localization', array( $this, 'add_localization' ) );

			add_action( 'wp_ajax_ur_save_abandoned_data', array( $this, 'save_abandoned_data' ) );
		    add_action( 'wp_ajax_nopriv_ur_save_abandoned_data', array( $this, 'save_abandoned_data' ) );
		}


		/**
		 * Add localization parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param [array] $localizations Localization variable.
		 * @return array
		 */
		public function add_localization( $localization ) {
			$localization['track_form_abandonment']     = true;
			$localization['save_abandoned_data_nonce'] = wp_create_nonce( 'save_abandoned_data_nonce' );

			if ( ur_option_checked( 'user_registration_enable_user_activity', false ) ) {
				$localization['save_abandoned_value'] = true;
			}

			return $localization;
		}

		/**
		 * Save Abandonment Data.
		 *
		 * @since 1.0.0
		 *
		 * @return boolean true or false;
		 */
		public function save_abandoned_data() {
			check_ajax_referer( 'save_abandoned_data_nonce' );

			if ( ur_option_checked( 'user_registration_enable_user_activity', false ) ) {
				if ( ! empty( $_POST['ur-user-form-id'] ) && isset( $_COOKIE['ur_fa_tracking_id'] ) ) {
					$sanitized_data = ur_clean( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$form_id        = ur_clean( $_POST['ur-user-form-id'] );
					$form           = UR()->form->get_form( $form_id );
					$referer        = ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
					$fields         = ur_pro_get_form_fields( $form_id );

					$form_fields    = array();
					$form_data      = apply_filters( 'user_registration_process_before_form_data', $fields, $sanitized_data );
					foreach ( $form_data as $key => $data ) {
						$field_name = str_replace( 'user_registration_','', $key );
						$exclude = array( 'section_title', 'html', 'captcha', 'file', 'hidden', 'stripe', 'invite_code' );
						if ( in_array( $field_name, array_keys( $sanitized_data ) ) && ! in_array( $data['field_key'], $exclude, true ) ) {
							$data['value'] = sanitize_text_field( $sanitized_data[$field_name] );
							$form_fields[$key] = $data;

						}
					}

					$abandon_data = array(
						'form_id'         => $form_id,
						'referer'         => $referer,
						'user_id'         => get_current_user_id(),
						'fields'          => wp_json_encode( $form_fields ),
						'status'          => 'abandoned',
						'created_at'      => current_time( 'mysql', true ),
					);
					require_once 'DB/AbandonDB.php';
					require_once 'DB/AbandonMetaDB.php';

					$abandon_db      = new AbandonDB();
					$abandon_meta_db = new AbandonMetaDB();

					$tracking_id = ur_clean( $_COOKIE['ur_fa_tracking_id'] );
					$abandon_id  = $abandon_meta_db->get_abandon_id_from_tracking_id( $tracking_id );

					if ( 0 < intval( $abandon_id ) ) {
						$update_status = $abandon_db->update( $abandon_data, $abandon_id );
					} else {
						$abandon_id = $abandon_db->insert( $abandon_data );

						$meta_data = array(
							'abandon_id' => $abandon_id,
							'meta_key'   => 'tracking_id',
							'meta_value' => $tracking_id,
						);

						$abandon_meta_db->insert( $meta_data );
					}
					$this->save_abandoned_fields( $abandon_id, $abandon_data );
				}
			}
		}

		/**
		 * Add an abandon meta that store which fields were empty while
		 * the user abandoned the form.
		 *
		 * @since 1.0.0
		 *
		 * @param integer $abandon_id Abandon Id.
		 * @param array   $abandon_data Abandon Data.
		 * @return void
		 */
		private function save_abandoned_fields( int $abandon_id, array $abandon_data ) {
			if ( ! empty( $abandon_id ) ) {
				$abandoned_fields         = get_post_meta( $abandon_data['form_id'], 'ur_abandoned_fields', true );
				$abandoned_fields_parsed  = (array) json_decode( $abandoned_fields, true );
				$fields                   = json_decode( $abandon_data['fields'] );
				$current_abandoned_fields = array();

				foreach ( $fields as $field_name => $field ) {
					if ( '' === $field->value ) {
						$current_abandoned_fields[] = $field_name;
					}
				}

				$abandoned_fields_parsed[ $abandon_id ]['abandoned_fields'] = $current_abandoned_fields;

				update_post_meta( $abandon_data['form_id'], 'ur_abandoned_fields', wp_json_encode( $abandoned_fields_parsed ) );
			}
		}
	}
}
