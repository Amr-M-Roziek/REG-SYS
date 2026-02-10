<?php
/**
 * User Registration Frontend List Layout
 *
 * Shows user lists in selected layout
 *
 * @author  WPEverest
 * @package URFrontendListing/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$layout  = get_post_meta( $post_id, 'user_registration_frontend_listings_layout', $single = true );
$show_search_field  = get_post_meta( $post_id, 'user_registration_frontend_listings_search_form', $single = true );
$show_amount_filter = get_post_meta( $post_id, 'user_registration_frontend_listings_amount_filter', $single = true );
$show_sort_by       = get_post_meta( $post_id, 'user_registration_frontend_listings_sort_by', $single = true );
$default_amount_filter = get_post_meta( $post_id, 'user_registration_frontend_listings_default_page_filter', $single = true );
$default_sorter        = get_post_meta( $post_id, 'user_registration_frontend_listings_default_sorter', $single = true );
$advanced_filter = get_post_meta( $post_id, 'user_registration_frontend_listings_advanced_filter', true );
$advanced_filter_fields = (array) json_decode( get_post_meta( $post_id, 'user_registration_frontend_listings_advanced_filter_fields', true ) );
?>

<div class="user-registration-frontend-listing-container" id="user-registration-frontend-listing-<?php echo esc_attr( $post_id ); ?>">

	<!-- User registration advance section heading -->
	<?php
	if ( $show_search_field ) {
		$search_field_placeholder = get_post_meta( $post_id, 'user_registration_frontend_listing_search_placeholder_text', $single = true );
		$search_field_button = get_post_meta( $post_id, 'user_registration_frontend_listings_search_button_text', $single = true );
		$search_field_placeholder = ( '' !== $search_field_placeholder ) ? $search_field_placeholder : __( 'Enter something to search.', 'user-registration-frontend-listing' );
		$search_field_button = ( '' !== $search_field_button ) ? $search_field_button : __( 'SEARCH', 'user-registration-frontend-listing' );

		?>
		<div class="ur-search-field">
			<input type="text" id="user-registration-frontend-listing-search-field" name="user-registration-frontend-listing-search-field" placeholder="<?php echo esc_attr( $search_field_placeholder ); ?>" class="user-registration-form-field" />
			<input type="button" class="user-registration-Button button" name="search_user_profiles" value="<?php echo esc_attr( $search_field_button ); ?>">
		</div>
		<?php
	}
	?>


	<div class="frontend-listing-title-settings">

	<!-- show result and advance filter options wrap -->

		<div class="ur-advance-setting-title-wrap">
			<div class="ur-list-results ur-frontend-count">
			</div>

			<div class="ur-filter-group">
				<?php
				if ( $show_amount_filter ) {
					$options = ur_frontend_listing_amount_filter();
					?>
					<!-- page amount filter -->
					<select id="user-registration-frontend-listing-amount-filter" name="user-registration-frontend-listing-amount-filter" class="user-registration-form-field ur-frontend-amout-filter">
						<?php
						foreach ( $options as $key => $value ) {
							?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php echo ( $key == $default_amount_filter ) ? 'selected' : ''; ?> ><?php echo esc_html( $value ); ?></option>
															<?php
						}
						?>
					</select>
					<?php
				}
				?>

				<!-- display sprter -->
				<?php
				if ( $show_sort_by ) {
					?>
					<div class="ur-frontend-listing-sort-by">
						<div class="select-wrap">
							<select id="user-registration-frontend-listing-sort-by" name="user-registration-frontend-listing-sort-by" class=" ut-btn ur-display-sorter user-registration-form-field">
							<?php
								$options = ur_frontend_listing_sort_filter();

							foreach ( $options as $key => $value ) {
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php echo ( $key === $default_sorter ) ? 'selected' : ''; ?> ><?php echo esc_html( $value ); ?></option>
								<?php
							}
							?>
							</select>
						</div>
					</div>
					<?php
				}
				?>

				<!-- advance filter -->
				<?php
				if ( $advanced_filter && ! empty( $advanced_filter_fields ) ) {
					?>
					<a class="ur-advance-filter ur-frontend-advance-filter-open">
						<svg xmlns="http://www.w3.org/2000/svg" class="ur-svg" data-name="Layer 1" viewBox="0 0 24 24">
							<path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round"
								stroke-width="2" d="M4 21v-7m0-4V3m8 18v-9m0-4V3m8 18v-5m0-4V3M1 14h6m2-6h6m2 8h6" />
						</svg>
						<span><?php esc_html_e( 'Advanced Filter', 'user-registration-frontend-listing' ); ?></span>
					</a>
					<?php
				}
				?>
			</div>
		</div>

		 <!-- UR advance filter options -->
		<?php
		if ( $advanced_filter && ! empty( $advanced_filter_fields ) ) {
			ur_frontend_list_advanced_filter_wrapper( $advanced_filter_fields );
		}
		?>
	</div>

	<?php
	if( '1' === $layout ) {
		?>
			<div class="ur-frontend-user-list-view user-registration-frontend-listing-body"></div>
		<?php
	} else {
		?>
			<div class="ur-frontend-user-listings user-registration-frontend-listing-body"></div>
		<?php
	}
	?>

	<div class="user-registration-card__footer user-registration-frontend-listing-footer"></div>
</div>
