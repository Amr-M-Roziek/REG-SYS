<?php
/**
 * Form analytics helpers
 *
 * @package User Registration form analytics.
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'DB/AbandonDB.php';
require_once 'DB/UserPostVisitsDB.php';

if ( ! function_exists( 'urfa_filter_abandoned_entries' ) ) {
	/**
	 * Filters abandoned data from abandon list.
	 * Returns an array of abandoned data.
	 *
	 * @since 1.0.0
	 *
	 * @param [array] $abandon Abandon list.
	 * @return array
	 */
	function urfa_filter_abandoned_entries( $abandon ) {
		$abandoned_data = array_filter(
			$abandon,
			function ( $abandon ) {
				if ( 'abandoned' === $abandon->status ) {
					return true;
				}

				return false;
			});

		return $abandoned_data;
	}
}

if ( ! function_exists( 'urfa_calculate_abandonment_rate' ) ) {
	/**
	 * Calculate abandonment rate.
	 *
	 * @since 1.0.0
	 *
	 * @param [array] $entries Entries list.
	 * @param [array] $abandoned_entries Abandoned entries list.
	 * @return int
	 */
	function urfa_calculate_abandonment_rate( $entries, $abandoned_entries ): int {
		$abandonment_rate = 0;

		if ( ! empty( $entries ) ) {
			$abandonment_rate = round( count( $abandoned_entries ) / count( $entries ) * 100 );
		}

		return $abandonment_rate;
	}
}

if ( ! function_exists( 'urfa_get_forms_list' ) ) {
	/**
	 * Returns the list of available everest forms in [id] => $title format.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	function urfa_get_forms_list(): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'user_registration',
				'posts_per_page' => -1,
			)
		);

		$forms = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$id           = get_the_ID();
			$title        = get_the_title();
			$forms[ $id ] = $title;
		}
		wp_reset_postdata();

		return $forms;
	}
}

if ( ! function_exists( 'urfa_get_entries' ) ) {
	/**
	 * Get list of entries since the time EVF Form Analytics addon was installed.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string $from From date.
	 * @param string $to To date.
	 * @return array
	 */
	function urfa_get_entries( $form_id = 0, $from = '', $to = '' ) {
		$db_handler = new AbandonDB();
		$entries = $db_handler->get_abandon_data( $form_id, $from, $to );

		return $entries;
	}
}

if ( ! function_exists( 'urfa_get_conversion_rate' ) ) {
	/**
	 * Calculates as returns the conversion rate.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string $from From date.
	 * @param string $to To date.
	 * @return integer
	 */
	function urfa_get_conversion_rate( $form_id = 0, $from = '', $to = '' ): int {
		$conversion_rate   = 0;
		$db_handler        = new UserPostVisitsDB();
		$page_visits       = $db_handler->get_form_page_visits( $form_id, $from, $to );
		$submission_counts = array_count_values(array_column( $page_visits, 'form_submitted' ) );

		if ( ! empty( $page_visits ) && isset( $submission_counts[1] ) ) {
			$conversion_rate = round( $submission_counts[1] / count( $page_visits ) * 100 );
		}

		return $conversion_rate;
	}
}

if ( ! function_exists( 'urfa_get_top_referer_pages' ) ) {
	/**
	 * Calculate and returns top referer pages for the specied form(s).
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string $from From date.
	 * @param string $to To date.
	 * @param integer $limit Number of items to return.
	 * @return array
	 */
	function urfa_get_top_referer_pages( $form_id = 0, $from = '', $to = '', $limit = 5 ): array {
		$db_handler = new UserPostVisitsDB();
		$page_visits = $db_handler->get_form_page_visits( $form_id, $from, $to );

		$pages = array_column( $page_visits, 'referer_url' );

		$page_counts = array_count_values( $pages );
		arsort( $page_counts );
		$top_pages = array_slice( array_keys( $page_counts ), 0, $limit );

		$response = array();

		foreach ( $top_pages as $url ) {
			$title = $url;

			$post_id = url_to_postid( $url );
			if ( $post_id > 0 ) {
				$page_title = get_the_title( $post_id );
				if ( ! empty( $page_title ) ) {
					$title = $page_title;
				}
			}

			$response[] = array(
				'title' => $title,
				'url' => $url
			);
		}

		return $response;
	}
}

if ( ! function_exists( 'urfa_get_isca_data' ) ) {
	/**
	 * Returns Impressions, Started, Conversions and Abandoned ( ISCA ) data.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $form_id Form Id.
	 * @param string $from Start date.
	 * @param string $to End date.
	 * @param string $duration Duration [ 'day', 'week', 'month' ]
	 * @return array
	 */
	function urfa_get_isca_data( $form_id = 0, $from = '', $to = '', $duration = 'Week' ) {
		$user_post_visits_hander = new UserPostVisitsDB();
		$data = $user_post_visits_hander->get_isca_data( $form_id, $from, $to, $duration );

		$isca_data = array();
		$isca_data['labels'] = array_map( function( $row ) {
			return $row->label;
		}, $data );

		$data = array_map( function( $row ) {
			$row->bounced_count = $row->total_count - $row->abandoned_count - $row->submitted_count;
			return $row;
		}, $data );

		$isca_data['data'] = $data;

		return $isca_data;
	}
}

if ( ! function_exists( 'urfa_get_forms_summary' ) ) {
	/**
	 * Returns summary of form Impressions, Conversions, Abandonment and Bounce Rates.
	 *
	 * @since 1.0.0
	 *
	 * @param string $from Start date.
	 * @param string $to End date.
	 * @return array
	 */
	function urfa_get_forms_summary( $from = '', $to = '' ) {
		$forms   = ur_get_all_user_registration_form();
		$forms[0] = __( 'Total', 'user-registration' );
		$summary = array();

		if ( empty( $forms ) ) {
			return $summary;
		}

		$db_handler = new UserPostVisitsDB();

		foreach ( $forms as $form_id => $form_name ) {
			$form_summary = $db_handler->get_isca_summary( $form_id, $from, $to );
			$form_summary->submitted_count = ! empty( $form_summary->submitted_count ) ? $form_summary->submitted_count : 0;
			$form_summary->abandoned_count = ! empty( $form_summary->abandoned_count ) ? $form_summary->abandoned_count : 0;

			$form_summary->name = $form_name;
			$form_summary->form_id = $form_id;

			$form_summary->conversion_rate = 0;
			$form_summary->abandonment_rate = 0;
			$form_summary->bounce_rate = 0;

			if ( ! empty( $form_summary->submitted_count ) ) {
				$form_summary->conversion_rate = round( $form_summary->submitted_count / $form_summary->total_count * 100 );
			}

			if ( ! empty( $form_summary->abandoned_count ) ) {
				$form_summary->abandonment_rate = round( $form_summary->abandoned_count / $form_summary->total_count * 100 );
			}

			if ( ! empty( $form_summary->total_count ) ) {
				$form_summary->bounce_rate = round( 100 - $form_summary->conversion_rate - $form_summary->abandonment_rate );
			}

			$summary[] = $form_summary;
		}

		return $summary;
	}
}
