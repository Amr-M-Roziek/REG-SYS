<?php
/**
 * Delete user.
 *
 * Uses https://github.com/A5hleyRich/wp-background-processing to handle DB
 * updates in the background.
 *
 * @class    UR_Background_Delete_User
 * @since xx.xx.xx
 * @package  UserRegistration/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once __DIR__ . '/libraries/wp-async-request.php';
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once __DIR__ . '/libraries/wp-background-process.php';
}

/**
 * UR_Background_Delete_User Class.
 */
class UR_Background_Delete_User extends WP_Background_Process {

	/**
	 * Action name.
	 *
	 * @var string ur_delete_user in default.
	 */
	protected $action = 'ur_delete_user';

	/**
	 * Dispatch updater.
	 *
	 * Updater will still run via cron job if this fails for any reason.
	 */
	public function dispatch() {
		parent::dispatch();
	}


	/**
	 * Perform task with queued item.
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since xx.xx.xx
	 */
	protected function task( $item ) {
		ur_get_logger()->debug( print_r( "Delete user in background stated...", true ) );
		// Actions to perform.
		self::delete_user( $item );
		return false;
	}

	/**
	 * Delete the selected user.
	 *
	 * @param $item The data.
	 * @return void
	 */
	public static function delete_user( $item ) {
		$user_status = get_option( 'user_registration_delete_user_schedular_status' );
		$user_roles  = get_option( 'user_registration_delete_user_schedular_roles' );

		$args = array(
			'role__in' => $user_roles,
			'fields'   => 'ID',
		);

		if ( ! empty( $user_status ) ) {
			$meta_query = array( 'relation' => 'OR' );

			foreach ( $user_status as $status ) {
				$meta_query[] = ur_get_user_meta_query_by_user_status( $status );
			}
			$args['meta_query'] = $meta_query;
		}

		$user_ids = get_users( $args );

		if ( empty( $user_ids ) ) {
			ur_get_logger()->debug( print_r( "There is not any user to delete.", true ) );
			return;
		}

		foreach ( $user_ids as $user_id ) {
			$res = wp_delete_user( $user_id );
		}

		ur_get_logger()->debug( print_r( $user_ids, true ) );
		ur_get_logger()->debug( print_r( "All users are deleted successfully!!", true ) );

	}

	/**
	 * Handle cron healthcheck.
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		if ( $this->is_process_running() ) {
			// Background process already running.
			return;
		}
		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();

			return;
		}
		$this->handle();
	}

	/**
	 * Schedule fallback event.
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Is the updater running?
	 *
	 * @return boolean
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
	}
}
