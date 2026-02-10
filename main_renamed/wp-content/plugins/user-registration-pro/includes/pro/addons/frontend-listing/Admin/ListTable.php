<?php
/**
 * User Registration Frontend ListingTable List
 *
 * @version 1.0.0
 * @package  URFrontendListing/ListTable
 */

namespace WPEverest\URFrontendListing\Admin;

use WP_Query;

if ( ! class_exists( 'UR_List_Table' ) ) {
	include_once dirname( UR_PLUGIN_FILE ) . '/includes/abstracts/abstract-ur-list-table.php';
}

/**
 * Frontend Listing table list class.
 */
class ListTable extends \UR_List_Table {

	/**
	 * Initialize the Frontend Listing table list.
	 */
	public function __construct() {
		$this->post_type       = 'ur_frontend_listings';
		$this->page            = 'user-registration-frontend-list';
		$this->per_page_option = 'user_registration_frontend_listing_per_page';
		$this->sort_by         = array(
			'title'  => array( 'title', false ),
			'author' => array( 'author', false ),
			'date'   => array( 'date', false ),
		);
		$this->bulk_actions    = $this->urfl_bulk_actions();
		parent::__construct(
			array(
				'singular' => 'frontend-listing',
				'plural'   => 'frontend-listings',
				'ajax'     => false,
			)
		);
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		_e( 'No User Lists found.', 'user-registration' );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'title'     => __( 'Title', 'user-registration' ),
			'shortcode' => __( 'Shortcode', 'user-registration' ),
			'author'    => __( 'Author', 'user-registration' ),
			'date'      => __( 'Date', 'user-registration' ),
		);
	}

	/**
	 * Post Edit Link.
	 *
	 * @param  object $row
	 *
	 * @return string
	 */
	public function get_edit_links( $row ) {
		return admin_url( 'post.php?post=' . $row->ID . '&action=edit' );
	}

	/**
	 * Post Duplicate Link.
	 *
	 * @param  object $row
	 *
	 * @return string
	 */
	public function get_duplicate_link( $row ) {
		return admin_url( 'post.php?post=' . $row->ID . '&action=edit' );
	}

	/**
	 * Return title column.
	 *
	 * @param  object $frontend_listings Frontend List datas.
	 *
	 * @return string
	 */
	public function get_row_actions( $frontend_listings ) {

		$edit_link        = $this->get_edit_links( $frontend_listings );
		$post_status      = $frontend_listings->post_status;
		$post_type_object = get_post_type_object( $frontend_listings->post_type );
		// Get actions.
		$actions = array(
			'id' => sprintf( __( 'ID: %d', 'user-registration' ), $frontend_listings->ID ),
		);

		if ( current_user_can( $post_type_object->cap->edit_post, $frontend_listings->ID ) && 'trash' !== $post_status ) {
			$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit', 'user-registration' ) . '</a>';
		}

		if ( current_user_can( $post_type_object->cap->delete_post, $frontend_listings->ID ) ) {
			if ( 'trash' == $post_status ) {
				$actions['untrash'] = '<a aria-label="' . esc_attr__( 'Restore this item from the Trash', 'user-registration' ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $frontend_listings->ID ) ), 'untrash-post_' . $frontend_listings->ID ) . '">' . esc_html__( 'Restore', 'user-registration' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Move this item to the Trash', 'user-registration' ) . '" href="' . get_delete_post_link( $frontend_listings->ID ) . '">' . esc_html__( 'Trash', 'user-registration' ) . '</a>';
			}
			if ( 'trash' == $post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete this item permanently', 'user-registration' ) . '" href="' . get_delete_post_link( $frontend_listings->ID, '', true ) . '">' . esc_html__( 'Delete permanently', 'user-registration' ) . '</a>';
			}
		}
		return $actions;
	}

	/**
	 * Return shortcode column.
	 *
	 * @param  object $frontend_listings Frontend List datas.
	 *
	 * @return string
	 */
	public function column_shortcode( $frontend_listings ) {

		$shortcode = '[user_registration_frontend_list id="' . $frontend_listings->ID . '"]';

		printf( '<input type="text" onfocus="this.select();" readonly="readonly" value=\'%s\' class="widefat code"></span>', esc_attr( $shortcode ) );
		?>
		<button id="copy-shortcode" class="button ur-copy-shortcode " href="#" data-tip="<?php esc_attr_e( 'Copy Shortcode ! ', 'user-registration' ); ?>" data-copied="<?php esc_attr_e( 'Copied ! ', 'user-registration' ); ?>">
			<span class="dashicons dashicons-admin-page"></span>
		</button>
		<?php
	}

	/**
	 * Return author column.
	 *
	 * @param  object $frontend_listings Frontend List datas.
	 *
	 * @return string
	 */
	public function column_author( $frontend_listings ) {
		$user = get_user_by( 'id', $frontend_listings->post_author );

		if ( ! $user ) {
			return '<span class="na">&ndash;</span>';
		}

		$user_name = ! empty( $user->data->display_name ) ? $user->data->display_name : $user->data->user_login;

		if ( current_user_can( 'edit_user' ) ) {
			return '<a href="' . esc_url(
				add_query_arg(
					array(
						'user_id' => $user->ID,
					),
					admin_url( 'user-edit.php' )
				)
			) . '">' . esc_html( $user_name ) . '</a>';
		}

		return esc_html( $user_name );
	}


	/**
	 * Return created at date column.
	 *
	 * @param  object $frontend_listings Frontend List datas.
	 *
	 * @return string
	 */
	public function column_date( $frontend_listings ) {
		$post = get_post( $frontend_listings->ID );

		if ( ! $post ) {
			return;
		}

		$t_time = mysql2date(
			__( 'Y/m/d g:i:s A', 'user-registration' ),
			$post->post_date,
			true
		);
		$m_time = $post->post_date;
		$time   = mysql2date( 'G', $post->post_date )
				- get_option( 'gmt_offset' ) * 3600;

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 ) {
			$h_time = sprintf(
				__( '%s ago', 'user-registration' ),
				human_time_diff( $time )
			);
		} else {
			$h_time = mysql2date( __( 'Y/m/d', 'user-registration' ), $m_time );
		}

		return '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function urfl_bulk_actions() {
		if ( isset( $_GET['status'] ) && 'trash' == $_GET['status'] ) {
			return array(
				'untrash' => __( 'Restore', 'user-registration' ),
				'delete'  => __( 'Delete permanently', 'user-registration' ),
			);
		}

		return array(
			'trash' => __( 'Move to trash', 'user-registration' ),
		);
	}

	/**
	 * Render the list table page, including header, notices, status filters and table.
	 */
	public function display_page() {
		$this->prepare_items();
		if ( ! isset( $_GET['add-new-frontend-listing'] ) ) { // phpcs:ignore Standard.Category.SniffName.ErrorCode: input var okay, CSRF ok.
			?>
				<hr class="wp-header-end">
				<div class="ur-admin-page-topnav" id="ur-lists-page-topnav">
					<div class="ur-page-title__wrapper">
						<div class="ur-page-title__wrapper-logo">
							<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
								<path d="M29.2401 2.25439C27.1109 3.50683 25.107 5.13503 23.3536 6.88846C21.6002 8.64188 19.972 10.6458 18.7195 12.6497C19.5962 14.4031 20.3477 16.1566 20.9739 18.0352C22.1011 15.6556 23.4788 13.5264 25.2323 11.6477V18.4109C25.2323 22.544 22.4769 26.1761 18.4691 27.3033H18.2185C17.9681 24.047 17.2166 20.9158 16.0894 17.91C14.4612 13.7769 11.9563 10.0196 8.69995 6.88846C6.94652 5.13503 4.94263 3.63208 2.81347 2.25439L2.3125 2.00388V18.2857C2.3125 24.9237 7.07177 30.6849 13.7097 31.8121H13.835C15.3379 32.0626 16.8409 32.0626 18.2185 31.8121H18.3438C24.9818 30.6849 29.7411 24.9237 29.7411 18.2857V2.00388L29.2401 2.25439ZM6.82128 18.2857V11.6477C10.7039 16.0313 13.0835 21.4168 13.5845 27.1781C9.57669 26.0509 6.82128 22.4188 6.82128 18.2857ZM15.9642 0C14.0855 0 12.5825 1.50291 12.5825 3.38158C12.5825 5.26025 14.0855 6.7632 15.9642 6.7632C17.8428 6.7632 19.3457 5.26025 19.3457 3.38158C19.3457 1.50291 17.8428 0 15.9642 0Z" fill="#475BB2"/>
							</svg>
						</div>
						<div class="ur-page-title__wrapper-menu">
							<h2 class="ur-page-title">
								<?php esc_html_e( 'Frontend Listings', 'user-registration' ); ?>
							</h2>
						</div>
					</div>
					<div class="ur-page-actions">
						<button id="ur-lists-page-settings-button" class="ur-button-primary" title="<?php esc_html_e( 'Screen Options', 'user-registration' ); ?>">
							<?php esc_html_e( 'Screen Options', 'user-registration' ); ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
								<path d="M6 8.75C5.85 8.75 5.75 8.7 5.65 8.6L1.15 4.1C0.95 3.9 0.95 3.6 1.15 3.4C1.35 3.2 1.65 3.2 1.85 3.4L6 7.55L10.15 3.4C10.35 3.2 10.65 3.2 10.85 3.4C11.05 3.6 11.05 3.9 10.85 4.1L6.35 8.6C6.25 8.7 6.15 8.75 6 8.75Z" fill="#383838"/>
							</svg>
						</button>
					</div>
				</div>
				<div class="user-registration-list-table-container">
					<div id="user-registration-list-table-page">
						<div class="user-registration-list-table-header">
							<h2><?php esc_html_e( 'All Lists', 'user-registration' ); ?></h2>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ur_frontend_listings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'user-registration' ); ?></a>
						</div>
						<div class="user-registration-list-table-page__body">
							<form id="frontend-listing-list" method="get" class="user-registration-list-table-action-form" >
								<input type="hidden" name="page" value="user-registration-frontend-list" />
								<?php
								echo "<div id='user-registration-list-filters-row'>";
								$this->views();
								$this->search_box( __( 'Search Listings', 'user-registration' ), 'frontend-listings' );
								echo '</div>';
								$this->display();
								?>
							</form>
						</div>
					</div>
				</div>
				<?php
		}
	}

	/**
	 * Displays the search box.
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = 'user-registration-list-table-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
		<div id="user-registration-list-search-form">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_html_e( 'Search Listings ...', 'user-registration' ); ?>" />
			<button type="submit" id="search-submit">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path fill="#000" fill-rule="evenodd" d="M4 11a7 7 0 1 1 12.042 4.856 1.012 1.012 0 0 0-.186.186A7 7 0 0 1 4 11Zm12.618 7.032a9 9 0 1 1 1.414-1.414l3.675 3.675a1 1 0 0 1-1.414 1.414l-3.675-3.675Z" clip-rule="evenodd"/>
				</svg>
			</button>
		</div>
		<?php
	}
}
