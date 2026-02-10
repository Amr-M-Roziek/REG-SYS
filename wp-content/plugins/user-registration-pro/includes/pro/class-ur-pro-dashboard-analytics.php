<?php
/**
 * Admin class
 *
 * User_Registration_Pro Admin
 *
 * @package User_Registration_Pro_Dashboard_Analytics.
 * @since  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'form-analytics/class-ur-pro-form-analytics-helpers.php';

if ( ! class_exists( 'User_Registration_Pro_Dashboard_Analytics' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class User_Registration_Pro_Dashboard_Analytics {

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {}

		/**
		 * Builds Total Overview card template.
		 *
		 * @param array $overview Array of user datas at different settings.
		 */
		public function user_registration_pro_total_overview_report( $form_id, $overview ) {
			$body  = '';
			$body .= '<div class="ur-row ur-no-gutter ur-mb-2">';
			$body .= '<div class="ur-col-6 ur-border-bottom ur-border-right">';
			if ( 'all' === $form_id ) {
				$body .= '<h4> Total Form Visits:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_form_visits'] ) . '</div>';
			} else {
				$body .= '<h4> Total Form Visits:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['specific_form_visits'][ $form_id ] ) . '</div>';
			}
			$body .= '</div>';
			$body .= '<div class="ur-col-6 ur-border-bottom">';
			$body .= '<h4> Total Registration:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_registration'] ) . '</div>';
			$body .= '</div>';

			if ( 'all' === $form_id ) {
				$body .= '<div class="ur-col-6 ur-border-right">';
				$body .= '<h4> Form Registration:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_overview']['total_form_registration'] ) . '</div>';
				$body .= '</div>';
				$body .= '<div class="ur-col-6">';
				$body .= '<h4> Social Registration:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_overview']['total_social_registration'] ) . '</div>';
				$body .= '</div>';
			}

			$body .= '</div>';
			$body .= '<div class="user-registration-card ur-bg-light ur-border-0 ur-mb-2">';
			$body .= '<div class="user-registration-card__body">';
			$body .= '<div class="ur-row">';
			$body .= '<div class="ur-col">';
			$body .= '<h4 class="ur-mt-0"> Approved Users: </h4><span class="ur-h2">' . esc_html( $overview['total_overview']['approved_users'] ) . '</span>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '<a class="user-registration-card ur-bg-light ur-border-0 ur-mb-2" href="' . admin_url( '', 'admin' ) . 'users.php?s&action=-1&new_role&ur_user_approval_status=pending&ur_user_filter_action=Filter&paged=1&action2=-1&new_role2&ur_user_approval_status2" rel="noreferrer noopener" target="_blank">';
			$body .= '<div class="user-registration-card__body">';
			$body .= '<div class="ur-row ur-align-items-center">';
			$body .= '<div class="ur-col">';
			$body .= '<h4 class="ur-mt-0"> Pending Users:</h4><span class="ur-h2">' . esc_html( $overview['total_overview']['pending_users'] ) . '</span>';
			$body .= '</div>';
			$body .= '<div class="ur-col-auto"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg></div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</a>';
			$body .= '<div class="user-registration-card ur-bg-light ur-border-0 ur-mb-0">';
			$body .= '<div class="user-registration-card__body">';
			$body .= '<div class="ur-row">';
			$body .= '<div class="ur-col">';
			$body .= '<h4 class="ur-mt-0"> Denied Users:</h4><span class="ur-h2">' . esc_html( $overview['total_overview']['denied_users'] ) . '</span>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';

			$total_overview_card = user_registration_pro_dasboard_card( __( 'Total Overview', 'user-registration' ), '', $body );
			return $total_overview_card;
		}

		/**
		 * Builds New Registration Overview card template.
		 *
		 * @param array $overview Array of user datas at different settings.
		 */
		public function user_registration_pro_new_registration_overview_report( $overview ) {
			$body        = '';
			$body       .= '<div class="ur-row ur-align-items-center">';
			$body       .= '<div class="ur-col">';
			$body       .= '<h4 class="ur-text-muted ur-mt-0">' . esc_html__( 'Total Registration', 'user-registration' ) . '</h4>';
			$body       .= '<span class="ur-h2 ur-mr-1">' . esc_html( $overview['weekly_data']['new_registration'] ) . '</span>';
			$batch_class = ( 0 > $overview['new_registration_comparision_percentage'] ) ? 'user-registration-badge--danger-subtle' : 'user-registration-badge--success-subtle';
			$operator    = ( 0 > $overview['new_registration_comparision_percentage'] ) ? '' : '+';
			$body       .= '<span class="user-registration-badge ' . esc_attr( $batch_class ) . '">' . $operator . esc_html( $overview['new_registration_comparision_percentage'] ) . '%</span>';
			$body       .= '</div>';
			$body       .= '<div class="ur-col-auto">';
			$body       .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-plus"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>';
			$body       .= '</div>';
			$body       .= '<div class="ur-col-12">';
			$body       .= '<p class="ur-d-flex ur-mb-0 ur-mt-2">Over previous ' . esc_html( $overview['weekly_data']['date_difference'] ) . '</p>';
			$body       .= '</div>';
			$body       .= '</div>';

			$new_registration_overview_card  = '';
			$new_registration_overview_card .= '<div class="ur-col-lg-3 ur-col-md-6">';
			$new_registration_overview_card .= user_registration_pro_dasboard_card( '', '', $body );
			$new_registration_overview_card .= '</div>';
			return $new_registration_overview_card;
		}

		/**
		 * Builds chart card template for overall user registration.
		 */
		public function user_registration_pro_registration_overview_report() {
			$body = '
			<canvas id="user-registration-pro-registration-overview-chart-report-area">Your browser does not support the canvas element.</canvas>
			<div class="user-registration-pro-registration-overview-chart-report-legends ur-border-top ur-mt-3"></div>
			';

			$registration_overview_report_card  = '';
			$registration_overview_report_card .= user_registration_pro_dasboard_card( __( 'Registration Overview', 'user-registration' ), 'user-registration-total-registration-chart', $body );
			return $registration_overview_report_card;
		}

		/**
		 * Builds chart card template for user registered with specific form.
		 */
		public function user_registration_pro_specific_form_registration_overview() {
			$body = '
			<canvas id="user-registration-pro-specific-form-registration-overview-chart-report-area">Your browser does not support the canvas element.</canvas>
			<div class="user-registration-pro-specific-form-registration-overview-chart-report-legends ur-border-top ur-mt-3"></div>
			';

			$specific_form_registration_overview_card = user_registration_pro_dasboard_card( __( 'Registration Source', 'user-registration' ), 'user-registration-specific-registration-chart', $body );
			return $specific_form_registration_overview_card;
		}


		/**
		 * Calculates overall user registration datas for display in dashboard.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function registration_overview( $form_id, $selected_date ) {
			global $wpdb;
			$total_form_registration     = 0;
			$total_social_registration   = 0;
			$new_registration_percentage = 0;

			$batch_size = 5000;
			$paged      = 1;
			$form_users = array();

			if ( 'all' === $form_id ) {
				do {
					if ( $paged % 2500 === 0 ) { // Flush cache every 2500 users
						wp_cache_flush();
					}

					// Get a batch of users with the 'ur_form_id' meta key
					$form_users_batch = get_users(
						array(
							'meta_key' => 'ur_form_id',
							'number'   => $batch_size,
							'paged'    => $paged,
							'fields'   => array( 'ID', 'user_registered' ),
						)
					);

					$total_form_registration += count( $form_users_batch );
					++$paged;

					$form_users = array_merge( $form_users, $form_users_batch );
				} while ( count( $form_users_batch ) > 0 );

				$paged = 1;
				do {
					// Get social registration users in batches
					$social_user_results_batch = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT u.ID, um.meta_key
								FROM {$wpdb->prefix}users u
								JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id
								WHERE um.meta_key LIKE %s
								LIMIT %d OFFSET %d",
							'user_registration_social_connect_%_username',
							$batch_size,
							( $paged - 1 ) * $batch_size
						),
						ARRAY_N
					);

					$total_social_registration += count( $social_user_results_batch );

					// Check if social registration is synced to any of the forms and adjust count
					foreach ( $social_user_results_batch as $user ) {
						$user_form = get_user_meta( $user[0], 'ur_form_id', true );
						if ( isset( $user_form ) && ( 'all' === $form_id || (int) $form_id === (int) $user_form ) ) {
							--$total_form_registration; // Decrement form registration if synced
						}
					}

					++$paged;
				} while ( count( $social_user_results_batch ) > 0 );

				$total_registration = $total_form_registration + $total_social_registration;
			} else {
				$paged = 1;
				do {
					// Get a batch of users with specific 'ur_form_id'
					$form_users_batch = get_users(
						array(
							'meta_key'   => 'ur_form_id',
							'meta_value' => $form_id,
							'number'     => $batch_size,
							'paged'      => $paged,
							'fields'     => array( 'ID', 'user_registered' ), // Only get user IDs to reduce memory load
						)
					);

					$total_form_registration += count( $form_users_batch );
					++$paged;
					$form_users = array_merge( $form_users, $form_users_batch );

				} while ( count( $form_users_batch ) > 0 );

				$total_registration = $total_form_registration;
				$form_users         = array_merge( $form_users, $form_users_batch );

			}

			$date_range_data  = $this->user_registration_pro_user_list_by_date( $form_id, $selected_date );
			$comparision_data = $this->user_registration_pro_comparision_report( $form_id, $selected_date );

			if ( $total_registration !== 0 ) {
				$new_registration_percentage = round( ( $date_range_data['new_registration'] / $total_registration ) * 100 );
			}

			$new_registration_comparision_percentage = $this->user_registration_pro_calculate_percentage( $date_range_data['new_registration'], $comparision_data['new_registration'] );

			$date_range_data['new_registration_percentage'] = $new_registration_percentage;

			$report = array(
				'total_registration'                      => $total_registration,
				'total_overview'                          => array(
					'total_form_registration'   => $total_form_registration,
					'total_social_registration' => $total_social_registration,
				),
				'weekly_data'                             => $date_range_data,
				'new_registration_comparision_percentage' => $new_registration_comparision_percentage,
			);

			return $report;
		}

		/**
		 * Calculates overall user registration datas for display in dashboard based on date provided.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function user_registration_pro_user_list_by_date( $form_id, $selected_date ) {
			// Get last week date.
			if ( strpos( $selected_date, 'to' ) !== false ) {
				list( $date_range_start, $date_range_end ) = explode( 'to', $selected_date );
				$start_date                                = strtotime( $date_range_start );
				$end_date                                  = strtotime( $date_range_end );
				$incrementor                               = DAY_IN_SECONDS;
			} else {
				$end_date     = strtotime( 'now' );
				$current_time = current_time( 'timestamp' );

				if ( 'Day' === $selected_date ) {
					$start_date  = strtotime( date( 'm/d/Y' ) . '00:00:00' );
					$incrementor = HOUR_IN_SECONDS;
				} elseif ( 'Month' === $selected_date ) {
					$start_date  = strtotime( date( 'Y-m-01 00:00:00', $current_time ) );
					$end_date    = strtotime( date( 'Y-m-t 23:59:59', $current_time ) );
					$incrementor = DAY_IN_SECONDS;
				} else {
					$week_starts_at = apply_filters( 'user_registration_pro_week_starts_at', 'last Sunday' );
					$start_date     = strtotime( $week_starts_at, $current_time );
					$start_date     = strtotime( date( 'Y-m-d 00:00:00', $start_date ) );
					$incrementor    = DAY_IN_SECONDS;
				}
			}

			return $this->user_regsitartion_date_range_data( $form_id, $selected_date, $start_date, $end_date, $incrementor, 'all_data' );
		}

		/**
		 * Calculates overall user registration comparision datas for display in dashboard based on date provided.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function user_registration_pro_comparision_report( $form_id, $selected_date ) {
			global $wpdb;
			// Get last week date.
			if ( strpos( $selected_date, 'to' ) !== false ) {
				list( $date_range_start, $date_range_end ) = explode( 'to', $selected_date );
				$date_difference                           = human_time_diff( strtotime( $date_range_start ), strtotime( $date_range_end ) );
				$start_date                                = strtotime( ".$date_range_start - $date_difference." );
				$end_date                                  = strtotime( ". $date_range_end - $date_difference." );
				$incrementor                               = DAY_IN_SECONDS;
			} else {
				$end_date = strtotime( 'now' );
				if ( 'Day' === $selected_date ) {
					$start_date  = strtotime( 'now' ) - DAY_IN_SECONDS * 2;
					$end_date    = strtotime( 'now' ) - DAY_IN_SECONDS;
					$incrementor = HOUR_IN_SECONDS;
				} elseif ( 'Month' === $selected_date ) {
					$start_date  = strtotime( 'now' ) - MONTH_IN_SECONDS * 2;
					$end_date    = strtotime( 'now' ) - MONTH_IN_SECONDS;
					$incrementor = DAY_IN_SECONDS;
				} else {
					$start_date  = strtotime( 'now' ) - WEEK_IN_SECONDS * 2;
					$end_date    = strtotime( 'now' ) - WEEK_IN_SECONDS;
					$incrementor = DAY_IN_SECONDS;
				}
			}

			return $this->user_regsitartion_date_range_data( $form_id, $selected_date, $start_date, $end_date, $incrementor, 'registration_data' );
		}

		/**
		 * Calculates date range user registration datas for display in dashboard based on date provided.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 * @param string $start_date Start date selected by the user.
		 * @param string $end_date End date selected by the user.
		 * @param string $incrementor Time incrementor.
		 */
		public function user_regsitartion_date_range_data( $form_id, $selected_date, $start_date, $end_date, $incrementor, $type ) {
			global $wpdb;
			$date_format      = $selected_date === 'Day' ? 'Y-m-d H' : 'Y-m-d';
			$weekly_data      = array();
			$new_registration = 0;
			$approved_users   = 0;
			$pending_users    = 0;
			$denied_users     = 0;

			for ( $i = $start_date; $i <= $end_date;  $i = $i + $incrementor ) {
				if ( 'Day' === $selected_date ) {
					$date_format = 'h A';
				}

				$weekly_data[ date( $date_format, $i ) ] = array(
					'new_registration_in_a_day' => 0,
					'approved_users_in_a_day'   => 0,
					'pending_users_in_a_day'    => 0,
					'denied_users_in_a_day'     => 0,
				);
			}

			if ( 'all' === $form_id ) {
				$users = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT u.ID, u.user_registered
						 FROM {$wpdb->users} u
						 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
						 WHERE um.meta_key = %s
						 AND u.user_registered BETWEEN %s AND %s",
						'ur_form_id',
						date( 'Y-m-d H:i:s', $start_date ),
						date( 'Y-m-d H:i:s', $end_date )
					)
				);
			} else {
				$users = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT u.ID, u.user_registered
						 FROM {$wpdb->users} u
						 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
						 WHERE um.meta_key = %s AND um.meta_value = %s
						 AND u.user_registered BETWEEN %s AND %s",
						'ur_form_id',
						$form_id,
						date( 'Y-m-d H:i:s', $start_date ),
						date( 'Y-m-d H:i:s', $end_date )
					)
				);
			}

			if ( ! $users ) {
				return array(
					'new_registration' => 0,
					'approved_users'   => 0,
					'pending_users'    => 0,
					'denied_users'     => 0,
					'date_difference'  => human_time_diff( $start_date, $end_date ),
					'daily_data'       => array(),
				);
			}

			$user_ids       = wp_list_pluck( $users, 'ID' );
			$user_meta_data = $this->user_registration_pro_fetch_user_meta_bulk( $user_ids );

			foreach ( $users as $user ) {
				$user_id           = $user->ID;
				$registration_date = date( $date_format, strtotime( $user->user_registered ) );

				++$new_registration;

				$status       = $user_meta_data[ $user_id ]['user_status'];
				$email_status = $user_meta_data[ $user_id ]['user_email_status'];

				if ( $status === '' && $email_status === '' ) {
					++$approved_users;
				} elseif ( $status !== '' && $email_status === '' ) {
					if ( (int) $status === 1 ) {
						++$approved_users;
					} elseif ( (int) $status === 0 ) {
						++$pending_users;
					} else {
						++$denied_users;
					}
				} elseif ( $email_status !== '' ) {
					if ( (int) $email_status === 1 ) {
						++$approved_users;
					} else {
						++$pending_users;
					}
				}

				if ( $type === 'all_data' ) {
					$weekly_data[ $registration_date ]['new_registration_in_a_day'] = ( $weekly_data[ $registration_date ]['new_registration_in_a_day'] ?? 0 ) + 1;
					$weekly_data[ $registration_date ]['approved_users_in_a_day']   = ( $weekly_data[ $registration_date ]['approved_users_in_a_day'] ?? 0 ) + ( $status == 1 || $email_status == 1 ? 1 : 0 );
					$weekly_data[ $registration_date ]['pending_users_in_a_day']    = ( $weekly_data[ $registration_date ]['pending_users_in_a_day'] ?? 0 ) + ( $status === 0 || $email_status === 0 ? 1 : 0 );
					$weekly_data[ $registration_date ]['denied_users_in_a_day']     = ( $weekly_data[ $registration_date ]['denied_users_in_a_day'] ?? 0 ) + ( $status !== 1 && $status !== 0 ? 1 : 0 );
				}
			}

			$total_users = max( $new_registration, 1 );

			return array(
				'new_registration'          => $new_registration,
				'approved_users'            => $approved_users,
				'approved_users_percentage' => round( ( $approved_users / $total_users ) * 100 ),
				'pending_users'             => $pending_users,
				'pending_users_percentage'  => round( ( $pending_users / $total_users ) * 100 ),
				'denied_users'              => $denied_users,
				'denied_users_percentage'   => round( ( $denied_users / $total_users ) * 100 ),
				'date_difference'           => human_time_diff( $start_date, $end_date ),
				'daily_data'                => $weekly_data,
			);
		}

		/**
		 * Pre fetch user approval meta keys.
		 *
		 * @param array $user_ids IDs of the users.
		 */
		public function user_registration_pro_fetch_user_meta_bulk( $user_ids ) {
			update_meta_cache( 'user', $user_ids );

			$meta_data = array();
			foreach ( $user_ids as $user_id ) {
				$user_status       = get_user_meta( $user_id, 'ur_user_status', true );
				$user_email_status = get_user_meta( $user_id, 'ur_confirm_email', true );

				$meta_data[ $user_id ] = array(
					'user_status'       => $user_status,
					'user_email_status' => $user_email_status,
				);
			}

			return $meta_data;
		}

		/**
		 * Calculates Growth percentage for display in dashboard based on date provided.
		 *
		 * @param int $current_data New user registration data at date selected by the user.
		 * @param int $comparision_data Previous user registration data at date difference of the date selected by the user.
		 */
		public function user_registration_pro_calculate_percentage( $current_data, $comparision_data ) {
			$comparision_percentage = 0;

			if ( $comparision_data !== 0 ) {
				$comparision_percentage = round( ( ( $current_data - $comparision_data ) / $comparision_data ) * 100 );
			} else {
				$comparision_percentage = $current_data * 100;
			}
			return $comparision_percentage;
		}

		/**
		 * Builds chart card template for form analytics.
		 */
		public function user_registration_pro_form_analytics_overview() {
			$body = '
			<div style="height:350px;"><canvas id="user-registration-pro-form-analytics-overview-chart-report-area">Your browser does not support the canvas element.</canvas></div>
			<div class="user-registration-pro-form-analytics-overview-chart-report-legends ur-border-top ur-mt-3"></div>
			';

			$form_analytics_overview_card = user_registration_pro_dasboard_card( __( 'Form Analytics', 'user-registration' ), 'user-registration-form-analytics-chart', $body );
			return $form_analytics_overview_card;
		}

		/**
		 * Builds chart card template for form analytics.
		 */
		public function user_registration_pro_form_analytics_top_referer_pages() {
			$body = '<div class="user-registration-pro-form-analytics-top-referer-page-legends">
						<div class="user-registration-card__body">
							<ol class="urfa-list"></ol>
						</div>
					</div>';

			$form_analytics_overview_card = user_registration_pro_dasboard_card( __( 'Top Referer Pages', 'user-registration' ), 'user-registration-form-analytics-top-referer-pages', $body );
			return $form_analytics_overview_card;
		}

		/**
		 * Builds chart card template for form summary.
		 */
		public function user_registration_pro_form_summary_overview() {
					$body = '<div class="user-registration-pro-form-summary-legends">
						<div class="user-registration-card__body">
						<table class="wp-table wp-list-table widefat striped" id="urfa-forms-summary-table">
							<thead>
								<th>Form Name</th>
								<th class="manage-column sorted desc" data-column="total_count">Impressions <span class="sorting-indicator"></span></th>
								<th class="manage-column sorted desc" data-column="submitted_count">Conversions <span class="sorting-indicator"></span></th>
								<th class="manage-column sorted desc" data-column="conversion_rate">Conversion Rate <span class="sorting-indicator"></span></th>
								<th class="manage-column sorted desc" data-column="abandoned_count">Abandonments <span class="sorting-indicator"></span></th>
								<th class="manage-column sorted desc" data-column="abandonment_rate">Abandonment Rate <span class="sorting-indicator"></span></th>
								<th class="manage-column sorted desc" data-column="bounce_rate">Bounce Rate <span class="sorting-indicator"></span></th>
							</thead>
							<tbody>
							</tbody>
							<tfoot></tfoot>
						</table>
						<div class="tablenav bottom">
							<div class="tablenav-pages"><span class="displaying-num"><span id="urfa-summary-table-total-count"></span> items</span>
								<a class="button urfa-summary-table-pagination-link disabled" href="#" data-page="1">1</a>
							</div>
						</div>
						</div>
					</div>';

			$form_analytics_overview_card = user_registration_pro_dasboard_card( __( 'Form Summary', 'user-registration' ), 'user-registration-form-summary', $body );
			return $form_analytics_overview_card;
		}

		/**
		 * Generate registration count and overview report.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function user_registration_registration_count_report( $form_id, $selected_date ) {

			$overview  = $this->registration_overview( $form_id, $selected_date );
			$response  = $this->user_registration_pro_new_registration_overview_report( $overview );
			$response .= user_registration_pro_approval_status_registration_overview_report( $form_id, $overview['weekly_data']['approved_users'], __( 'Approved Users', 'user-registration' ), 'approved', __( 'View Approved Users', 'user-registration' ) );
			$response .= user_registration_pro_approval_status_registration_overview_report( $form_id, $overview['weekly_data']['pending_users'], __( 'Pending Users', 'user-registration' ), 'pending', __( 'View Pending Users', 'user-registration' ) );
			$response .= user_registration_pro_approval_status_registration_overview_report( $form_id, $overview['weekly_data']['denied_users'], __( 'Denied Users', 'user-registration' ), 'denied', __( 'View Denied Users', 'user-registration' ) );

			$registration_overview = $this->user_registration_pro_registration_overview_report();

			return array(
				'message'               => $response,
				'registration_overview' => $registration_overview,
				'user_report'           => $overview,
			);
		}

		/**
		 * Generate specific form registration report.
		 */
		public function user_registration_specific_form_users_report() {

			global $wpdb;
			$forms                      = ur_get_all_user_registration_form();
			$specific_form_registration = array();

			foreach ( $forms as $form_id => $form_title ) {

				$args = array(
					'meta_key'   => 'ur_form_id',
					'meta_value' => $form_id,
					'fields'     => array( 'ID' ),
				);

				$users                                     = get_users( $args );
				$specific_form_registration[ $form_title ] = count( $users );
			}

			return array(
				'message'                    => $this->user_registration_pro_specific_form_registration_overview(),
				'specific_form_registration' => $specific_form_registration,
			);
		}

		/**
		 * Generate registration form analytics report.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 * @param string $from Start date selected by the user.
		 * @param string $to End date selected by the user.
		 */
		public function user_registration_form_analytics_overview_report( $form_id, $selected_date, $from, $to ) {
			// Impressions, Started, Completed, Abandoned (ISCA) data.
			$isca = urfa_get_isca_data( $form_id, $from, $to, $selected_date );

			return array(
				'message' => $this->user_registration_pro_form_analytics_overview(),
				'isca'    => $isca,
			);
		}

		/**
		 * Generate top referer pages report.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $from Start date selected by the user.
		 * @param string $to End date selected by the user.
		 */
		public function user_registration_top_referer_report( $form_id, $from, $to ) {
			$top_referer_pages = urfa_get_top_referer_pages( $form_id, $from, $to, $limit = 5 );

			return array(
				'message'           => $this->user_registration_pro_form_analytics_top_referer_pages(),
				'top_referer_pages' => $top_referer_pages,
			);
		}

		/**
		 * Generate form summary report.
		 *
		 * @param string $from Start date selected by the user.
		 * @param string $to End date selected by the user.
		 * @param string $summary_sort_by Sorting command.
		 * @param string $summary_sort_order Sorting order.
		 */
		public function user_registration_form_summary_report( $from, $to, $summary_sort_by, $summary_sort_order ) {
			$forms_summary = urfa_get_forms_summary( $from, $to );

			if ( ! empty( $summary_sort_by ) ) {

				usort(
					$forms_summary,
					function ( $form1, $form2 ) use ( $summary_sort_by, $summary_sort_order ) {
						if ( 'asc' === $summary_sort_order ) {
							return $form1->$summary_sort_by - $form2->$summary_sort_by;
						} else {
							return $form2->$summary_sort_by - $form1->$summary_sort_by;
						}
					}
				);
			}

			return array(
				'message'      => $this->user_registration_pro_form_summary_overview(),
				'form_summary' => $forms_summary,
			);
		}
	}
}
