<?php
/**
 * User Registration Form Analytics AbandonMetaDB Class.
 *
 * @class AbandonMetaDB
 * @package UserRegistration\FormAnalytics\DB
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * AbandonMetaDB class.
 */
class AbandonMetaDB {

	/**
	 * Get table name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_table() {
		global $wpdb;

		return $wpdb->prefix . 'ur_abandoned_meta';
	}

	/**
	 * Insert Record.
	 *
	 * @since 1.0.0
	 *
	 * @param array $abandon_meta Abandon Meta Array.
	 * @return integer or WP_Error
	 */
	public function insert( array $abandon_meta ) {
		global $wpdb;

		$entry_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT abandon_id FROM ' . self::get_table() . ' WHERE meta_value=%s',
				$abandon_meta['meta_value']
				)
			);

		if ( empty( $entry_exists ) ) {
			return $wpdb->insert( self::get_table(), $abandon_meta);
		} else {
			return $wpdb->update( self::get_table(), $abandon_meta, array( 'abandon_id' => $abandon_meta['abandon_id'] ) );
		}
	}

	/**
	 * Returns Abandon id for particular tracking id if exists.
	 * Returns null if not found.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tracking_id
	 * @return integer|null
	 */
	public function get_abandon_id_from_tracking_id( string $tracking_id ) : int {
		global $wpdb;

		$abandon_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT abandon_id FROM ' . self::get_table() . ' WHERE meta_value=%s',
				$tracking_id
			)
		);

		return ! empty( $abandon_id ) ? $abandon_id : 0;
	}

	/**
	 * Returns the matched form_id for specified Abandon id.
	 *
	 * @since 1.0.0
	 *
	 * @param [int] $abandon_id Abandon id.
	 * @return int
	 */
	public function get_form_id_from_entry_id( $abandon_id ): int {
		global $wpdb;

		$form_id = 0;

		if ( 0 < intval( $abandon_id ) ) {
			$sql = $wpdb->prepare(
				"SELECT form_id FROM " . self::get_table() . " WHERE abandon_id=%d",
				intval($abandon_id)
			);

			$result = $wpdb->get_var( $sql );

			if ( !empty( $result ) ) {
				$form_id = $result;
			}
		}

		return $form_id;
	}
}
