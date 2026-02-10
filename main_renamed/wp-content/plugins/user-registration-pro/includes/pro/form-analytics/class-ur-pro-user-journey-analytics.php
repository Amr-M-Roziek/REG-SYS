<?php
/**
 * User Registration Pro User Journey Analytics Class.
 *
 * @class User Journey
 * @package UserRegistration\UserJourneyAnalytics
 * @since   1.0.0
 */


defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'User_Registration_Pro_User_Journey_Analytics' ) ) {
	/**
	 * Frontend class.
	 */
	class User_Registration_Pro_User_Journey_Analytics {
		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'user_registration_single_user_details_content', array( $this, 'render_user_journey_template' ), 10, 2 );
		}

		/**
		 * Render User Journey data in User Details.
		 *
		 * @param int $user_id
		 * @param int $form_id
		 */
		public function render_user_journey_template( $user_id, $form_id ) {
			?>
			<div id="user-registration-user-journey-details" class="stuffbox">
				<h2 class="handle">
					<span>
					<?php
					echo esc_html__( 'User Journey', 'user-registration' );
					?>
					</span>
				</h2>
				<div class="inside">
					<table class="wp-list-table widefat fixed striped posts">
						<thead>
							<tr>
								<th><?php _e( 'Date', 'user-registration' ); ?></th>
								<th><?php _e( 'Page', 'user-registration' ); ?></th>
								<th><?php _e( 'Duration', 'user-registration' ); ?></th>
								<th><?php _e( 'Action', 'user-registration' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php

						$records = $this->get_user_journey_data( $user_id );

						if ( ! empty( $records ) ) {
							foreach ( $records as $record ) {
								echo <<<EOT
								<tr>
									<td>{$record['request_time']}</td>
									<td>
										{$record['page_title']}
										<a href='{$record['page_url']}' rel="noreferrer noopener" target='_blank'>
											<span class="dashicons dashicons-external"></span>
										</a>
									</td>
									<td>{$record['duration']}</td>
									<td>{$record['status']}</td>
								</tr>
								EOT;
							}
						} else {
							echo '<tr><td colspan="4">' . esc_html__('No data found.', 'user-registration') . '</td></tr>';
						}
						?>
						</tbody>
					</table>
				</div>
			</div>
		<?php
		}

		/**
		 * Fetch and return user journey data for specific entry.
		 *
		 * @since 1.0.0
		 *
		 * @param [obj] $entry Entry details.
		 *
		 * @return array
		 */
		public function get_user_journey_data( $user_id ) {
			require_once 'DB/UserPostVisitsDB.php';
			$db_handler = new UserPostVisitsDB();
			$records = $db_handler->get_user_journey_by_user_id( $user_id );
			$data = array();

			$date_time_format = apply_filters( 'user_registration_user_journey_date_time_format', 'M j, Y @ g:ia' );
			foreach ( $records as $record ) {
				$request_timestamp = strtotime( $record->created_at ) - $record->duration;

				// Find page title.
				$page_id    = url_to_postid( $record->page_url );
				$page_title = $record->page_url;

				if ( ! empty( $page_id ) ) {
					$page_title = get_the_title( $page_id );
				} elseif( untrailingslashit( $record->page_url ) === untrailingslashit( get_home_url() ) ) {
					$page_title = __( 'Home', 'user-registration' );
				}


				// Find page activity status.
				$status = '';

				if ( ur_string_to_bool( $record->form_abandoned ) ) {
					$status = __( 'Abandoned', 'user-registration' );
				} elseif ( ur_string_to_bool( $record->form_submitted ) ) {
					$status = __( 'Submitted', 'user-registration' );
				} elseif( empty( $status ) && ! empty( $record->form_id ) ) {
					$status= __( 'Bounced', 'user-registration' );
				}

				$data[ $request_timestamp ] = array(
					'page_title' => $page_title,
					'page_url'   => $record->page_url,
					'duration'   => $this->urfa_convert_sec_to_time( $record->duration ),
					'request_time' => date( $date_time_format, $request_timestamp ),
					'status'       => $status
				);
			}

			return $data;
		}

		/**
		 * Convert time in seconds to standard time.
		 *
		 * @since 1.0.0
		 *
		 * @param [int] $seconds Seconds.
		 * @param string $depth The smallest time unit to show.
		 * @return string
		 */
		function urfa_convert_sec_to_time( $seconds, $depth = 's' ) {
			$days     = floor( $seconds / 86400 );
			$hours    = floor( $seconds / 3600 );
			$minutes  = floor( ( $seconds % 3600 ) / 60 );
			$seconds %= 60;

			$formatted_time  = '';
			$formatted_time .= ! empty( $days ) ? $days . ' Days' : '';
			if ( 'd' !== $depth || empty( $formatted_time ) ) {
				$formatted_time .= ! empty( $hours ) ? $hours . ' Hours' : '';
				if ( 'h' !== $depth || empty( $formatted_time ) ) {
					$formatted_time .= ! empty( $minutes ) ? ' ' . $minutes . ' Minutes' : '';
					if ( 'm' !== $depth || empty( $formatted_time ) ) {
						$formatted_time .= ! empty( $seconds ) ? ' ' . $seconds . ' Seconds' : '';
					}
				}
			}

			return empty( $formatted_time ) ? 'sometime' : $formatted_time;
		}
	}
}
