<?php
/**
 * User Registration Form Analytics UserPostVisitsDB Class.
 *
 * @class UserPostVisitsDB
 * @package UserRegistration\FormAnalytics\DB
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * UserPostVisitsDB class.
 */
class UserPostVisitsDB {

	/**
	 * Returns table name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'ur_user_post_visits';
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

		// Alter created_at date to create fake records within certain interval.
		// Needs to be removed before release.
		// $number = rand(1,10);

		// $model->created_at = date( 'Y-m-d h:i:s', strtotime( "-{$number} days" ) );

		$dataArray = (array) $data;

		return $wpdb->insert( self::get_table(), $dataArray ) ? $wpdb->insert_id : 0;
	}

	/**
	 * Add missing user id to all records with particular session id.
	 *
	 * @since 1.0.0
	 *
	 * @param [string] $session_id Session Id.
	 * @param [int]    $user_id User Id.
	 * @return integer or boolean
	 */
	public function add_user_id( $session_id, $user_id ) {
		global $wpdb;

		$data  = array( 'user_id' => $user_id );
		$where = array( 'session_id' => $session_id );

		return $wpdb->update( self::get_table(), $data, $where );
	}

	/**
	 * Returns entry id if the user has submitted a form and entry id is set for
	 * that particular session id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $session_id Session Id.
	 * @return integer
	 */
	public function get_user_id_from_session_id( string $session_id ): ?int {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT DISTINCT user_id FROM ' . self::get_table() . ' WHERE session_id=%s AND user_id IS NOT NULL',
			$session_id
		);

		$user_id = $wpdb->get_var( $sql );
		return ! empty( $user_id ) && ! is_wp_error( $user_id ) ? $user_id : 0;
	}

	/**
	 * Get complete user journey data from user id.
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $entry_id
	 * @return array
	 */
	public function get_user_journey_by_user_id( $user_id ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT * FROM ' . self::get_table() . ' WHERE user_id=%s',
			$user_id
		);

		$results = $wpdb->get_results( $sql );

		return is_wp_error( $results ) || empty( $results ) ? array() : $results;
	}

	/**
	 * Returns form page visits for specified form within specified duration.
	 * If form id = 0, it calculates for all available forms.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string  $from Start date.
	 * @param string  $to End date.
	 * @return array
	 */
	public function get_form_page_visits( $form_id = 0, $from = '', $to = '' ) {
		global $wpdb;

		$sql = 'SELECT id, form_submitted, referer_url FROM ' . self::get_table() . ' WHERE form_id != 0';

		if ( 'all' !== $form_id ) {
			$sql .= sprintf( ' AND form_id=%d', $form_id );
		}

		if ( ! empty( $from ) && ! empty( $to ) ) {
			$sql .= sprintf( ' AND created_at>"%s" AND created_at<"%s"', $from, $to );
		}

		$results = $wpdb->get_results( $sql );

		return $results;
	}

	/**
	 * Returns Impressions, Started, Conversions, Abandoned ( ISCA ) data.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string  $from Start date.
	 * @param string  $to End date.
	 * @param string  $duration Duration specifier.
	 * @return array
	 */
	public function get_isca_data( $form_id = 0, $from = '', $to = '', $duration = 'Week' ) {
		global $wpdb;

		$sql = 'SELECT %s as label, SUM(form_submitted) as submitted_count, SUM(form_abandoned) as abandoned_count, COUNT(*) AS total_count FROM ' . self::get_table() . ' WHERE form_id>0 %s GROUP BY label ORDER BY label';

		$filter_sql = '';

		if ( 'all' !== $form_id ) {
			$filter_sql .= sprintf( ' AND form_id=%d', $form_id );
		}

		if ( ! empty( $from ) && ! empty( $to ) ) {
			$filter_sql .= sprintf( ' AND created_at>"%s" AND created_at<"%s"', $from, $to );
		}

		$duration_filters = array(
			'Month' => 'DATE(created_at)',
			'Week'  => 'DATE(created_at)',
			'Day'   => "DATE_FORMAT(created_at, '%l %p')",
		);

		$duration = ! empty( $duration ) && isset( $duration_filters[ $duration ] ) ? $duration : 'Week';
		$sql      = sprintf( $sql, $duration_filters[ $duration ], $filter_sql );
		$results  = $wpdb->get_results( $sql );

		return $results;
	}

	/**
	 * Returns Impressions, Started, Conversions, Abandoned ( ISCA ) data.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string  $from Start date.
	 * @param string  $to End date.
	 * @return object
	 */
	public function get_isca_summary( $form_id = 0, $from = '', $to = '' ) {
		global $wpdb;

		$sql = 'SELECT SUM(form_submitted) as submitted_count, SUM(form_abandoned) as abandoned_count, COUNT(*) AS total_count FROM ' . self::get_table() . ' WHERE form_id>0 %s';

		$filter_sql = '';

		if ( ! empty( $form_id ) ) {
			$filter_sql .= sprintf( ' AND form_id=%d', $form_id );
		}

		if ( ! empty( $from ) && ! empty( $to ) ) {
			$filter_sql .= sprintf( ' AND created_at>"%s" AND created_at<"%s"', $from, $to );
		}

		$sql     = sprintf( $sql, $filter_sql );
		$results = $wpdb->get_row( $sql );

		return $results;
	}
}
