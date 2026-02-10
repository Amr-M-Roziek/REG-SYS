<?php
/**
 * User Registration Form Analytics AbandonDB Class.
 *
 * @class AbandonDB
 * @package UserRegistration\FormAnalytics\DB
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * AbandonDB class.
 */
class AbandonDB {

	/**
	 * Returns table name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'ur_abandoned_data';
	}

	/**
	 * Insert Record.
	 *
	 * @since 1.0.0
	 *
	 * @param $data Data.
	 * @return integer or WP_Error
	 */
	public function insert( $data ) {
		global $wpdb;

		$abandon_id = 0;

		if ( ! empty( $data ) ) {
			$success = $wpdb->insert( self::get_table(), $data );

			if ( $success ) {
				$abandon_id = $wpdb->insert_id;
			}
		}

		return $abandon_id;

	}

	/**
	 * Update abandoned data on repetitive abandonment of the same form.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data Abandon Data.
	 * @param int   $id Abandon Id.
	 * @return bool Returns true or false.
	 */
	public function update( $data, $id ) {
		global $wpdb;

		if ( ! empty( $id ) ) {
			return $wpdb->update( self::get_table(), $data, array( 'id' => $id ) );
		}

		return false;
	}


	/**
	 * Returns AbandonData.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string  $from Start date.
	 * @param string  $to End date.
	 * @return array
	 */
	public function get_abandon_data( $form_id = 0, $from = '', $to = '' ) : ? array {
		global $wpdb;

		$sql          = 'SELECT * FROM ' . self::get_table();

		if ( 0 < absint( $form_id ) ) {
			$sql .= " WHERE form_id=$form_id";
		}

		if ( ! empty( $from ) && ! empty( $to ) ) {
			$sql .= sprintf( ' AND created_at>"%s" AND created_at<"%s"', $from, $to );
		}

		$results = $wpdb->get_results( $sql );

		return $results;
	}
}
