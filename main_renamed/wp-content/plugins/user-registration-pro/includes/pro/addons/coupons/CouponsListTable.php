<?php
/**
 *
 */

namespace WPEverest\URMembership\Coupons;

if ( ! class_exists( 'UR_List_Table' ) ) {
	include_once dirname( UR_PLUGIN_FILE ) . '/includes/abstracts/abstract-ur-list-table.php';
}

/**
 * Orders table list class.
 */
class CouponsListTable extends \UR_List_Table {

	/**
	 * Initialize the Orders table list.
	 */
	public function __construct() {
		$this->post_type       = 'ur_coupons';
		$this->page            = 'user-registration-coupons';
		$this->per_page_option = 'user_registration_coupons_per_page';
		$this->addnew_action   = 'add_new_coupon';
		$this->sort_by         = array(
			'title' => array( 'title', false ),
		);
		parent::__construct(
			array(
				'singular' => 'coupon',
				'plural'   => 'coupons',
				'ajax'     => true,
			)
		);
	}


	public function column_default( $item, $column_name ) {
		$meta_data = json_decode( get_post_meta( $item->ID, 'ur_coupon_meta', true ), true );

		switch ( $column_name ) {
			case 'code':
				return esc_html( $meta_data['coupon_code'] );
			case 'amount':
				return $this->show_column_amount( $meta_data );
			case 'status':
				return $this->show_column_status( $meta_data );
			case 'action':
				return $this->column_action( $item );
			case 'expires':
				echo date_i18n( get_option( 'date_format' ), strtotime( $meta_data['coupon_end_date'] ) );
				break;
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		$image_url = esc_url( plugin_dir_url( UR_PLUGIN_FILE ) . 'assets/images/empty-table.png' );
		?>
		<div class="empty-list-table-container">
			<img src="<?php echo $image_url; ?>" alt="">
			<h3><?php echo __( 'You don\'t have any Coupons yet.', 'user-registration-membership' ); ?></h3>
			<p><?php echo __( 'Please add Coupons and you are good to go.', 'user-registration-membership' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Returns the formatted amount for a coupon based on the discount type.
	 *
	 * @param array $meta_data An array of meta data containing the coupon discount type and amount.
	 *
	 * @return string The formatted coupon amount with the appropriate symbol.
	 */
	public function show_column_amount( $meta_data ) {
		$symbol = '';
		if ( ur_check_module_activation( 'payments' ) || is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' ) || is_plugin_active( 'user-registration-authorize-net/user-registration-authorize-net.php' ) ) {
			$currency   = get_option( 'user_registration_payment_currency', 'USD' );
			$currencies = ur_payment_integration_get_currencies();
			$symbol     = $currencies[ $currency ]['symbol'];
		}

		return ( isset( $meta_data['coupon_discount_type'] ) && 'fixed' === $meta_data['coupon_discount_type'] ) ? $symbol . esc_html( $meta_data['coupon_discount'] ) : esc_html( $meta_data['coupon_discount'] ) . '%';
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->prepare_column_headers();
		$per_page     = $this->get_items_per_page( $this->per_page_option );
		$current_page = $this->get_pagenum();

		// Query args.
		$args = array(
			'post_type'           => $this->post_type,
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'paged'               => $current_page,
		);

		// Handle the status query.
		if ( ! empty( $_REQUEST['status'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args['post_status'] = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );

		}

		// Handle the search query.
		if ( ! empty( $_REQUEST['s'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args['s']              = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
			$new_args['meta_query'] = array(
				array(
					'key'     => 'ur_coupon_meta',
					'value'   => $_REQUEST['s'],
					'compare' => 'LIKE',
				),
			);
		}

		$args['orderby'] = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'date_created'; //phpcs:ignore WordPress.Security.NonceVerification.Missing
		$args['order']   = isset( $_REQUEST['order'] ) && 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) ? 'ASC' : 'DESC'; //phpcs:ignore WordPress.Security.NonceVerification.Missing

		$first_items = get_posts( $args );

		$second_items = get_posts(
			array(
				'post_type'  => 'ur_coupons',
				'meta_query' => array(
					array(
						'key'     => 'ur_coupon_meta',
						'value'   => $args['s'] ?? '',
						'compare' => 'LIKE',
					),
				),
			)
		);

		$this->items = array_unique( array_merge( $first_items, $second_items ), SORT_REGULAR );

		// Set the pagination.
		$this->set_pagination_args(
			array(
				'total_items' => count( $this->items ),
				'per_page'    => $per_page,
				'total_pages' => ceil( count( $this->items ) / $per_page, ),
			)
		);
	}


	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'title'   => __( 'Coupon Title', 'user-registration-membership' ),
			'code'    => __( 'Coupon Code', 'user-registration-membership' ),
			'amount'  => __( 'Discount', 'user-registration-membership' ),
			'expires' => __( 'Expires', 'user-registration-membership' ),
			'status'  => __( 'Status', 'user-registration-membership' ),
			'action'  => __( 'Action', 'user-registration-membership' ),
		);
	}


	/**
	 * Post Edit Link.
	 *
	 * @param object $row
	 *
	 * @return string
	 */
	public function get_edit_links(
		$row
	) {
		return admin_url( 'admin.php?post_id=' . $row->ID . '&action=' . $this->addnew_action . '&page=' . $this->page );
	}

	/**
	 * Post Duplicate Link.
	 *
	 * @param object $row
	 *
	 * @return string
	 */
	public function get_duplicate_link(
		$row
	) {
		return admin_url( 'post.php?post=' . $row->ID . '&action=edit' );
	}

	/**
	 * @param $coupon
	 *
	 * @return array
	 */
	public function get_row_actions(
		$coupon
	) {

		return array();
	}

	/**
	 * @param $coupon
	 *
	 * @return string
	 */
	public function show_column_status(
		$meta
	) {

		if ( isset( $meta['coupon_status'] ) && ! empty( $meta['coupon_status'] ) ) {
			$status = $meta['coupon_end_date'] >= date( 'Y-m-d' ) ? 'active' : 'expired';
		} else {
			$status = 'inactive';
		}

		return sprintf( '<span class="coupon-%s">%s</span>', esc_attr( $status ), esc_html( ucfirst( $status ) ) );
	}

	/**
	 * Get the sortable columns for the table.
	 *
	 * @return array The list of columns that are sortable.
	 */
	public function get_sortable_columns() {
		return array(
			'amount' => array( 'amount' ),
			'status' => array( 'status' ),
		);
	}


	/**
	 * @param $coupon
	 *
	 * @return string
	 */
	public function column_action(
		$coupon
	) {
		return '
				<div class="row-actions ur-d-flex ur-align-items-center visible" style="gap: 5px">
					<span class="view">
						<a class="show-coupon-detail" value = ' . esc_attr( $coupon->ID ) . ' href="' . $this->get_edit_links( $coupon ) . '">' . __( 'View', 'user-registration-membership' ) . '</a>
					</span>
					&nbsp | &nbsp
					<span id="delete-coupon" class="trash">
						<a class="submitdelete" aria-label="' . esc_attr__( 'Move this item to the Trash', 'user-registration-membership' ) . '" href="' . get_delete_post_link( $coupon->ID ) . '">' . esc_html__( 'Delete', 'user-registration-membership' ) . '</a>
					</span>
					</div>
					';
	}

	/**
	 * Render the list table page, including header, notices, status filters and table.
	 */
	public function display_page() {
		$this->prepare_items();
		if ( ! isset( $_GET['add - new - membership'] ) ) { // phpcs:ignore Standard.Category.SniffName.ErrorCode: input var okay, CSRF ok.
			?>
			<div class="wrap">
				<form id="membership - list" method="get">
					<?php
					// $this->views();
					$this->display();
					?>
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Display the table in the admin area.
	 *
	 * This function renders the table in the admin area. It displays the table headers, rows, and table nav.
	 *
	 * @return void
	 */
	public function display() {
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>
			<tbody id="the-list">
			<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Displays the search box.
	 *
	 * @since 4.1
	 */
	public function display_search_box() {
		$input_id = 'user-registration-payment-history-search';

		?>
		<div class="search-box">
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s"
					value="<?php _admin_search_query(); ?>"
					placeholder="<?php esc_html_e( 'Search Coupon ...', 'user-registration' ); ?>"/>
			<input type="hidden" name="page" value="<?php echo $this->page; ?>">
			<?php wp_nonce_field( 'ur-filter-coupons' ); ?>
			<button type="submit" id="search-submit">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<path fill="#000" fill-rule="evenodd"
							d="M4 11a7 7 0 1 1 12.042 4.856 1.012 1.012 0 0 0-.186.186A7 7 0 0 1 4 11Zm12.618 7.032a9 9 0 1 1 1.414-1.414l3.675 3.675a1 1 0 0 1-1.414 1.414l-3.675-3.675Z"
							clip-rule="evenodd"/>
				</svg>
			</button>
		</div>

		<button type="submit" name="ur_coupon_filter" id="ur-coupon-filter-btn"
				class="button ur-button-primary">
			<?php esc_html_e( 'Filter', 'user-registration' ); ?>
		</button>
		<?php
	}

	/**
	 * @return array
	 */
	public function get_all_memberships() {
		$posts        = get_posts(
			array(
				'post_type'   => 'ur_membership',
				'numberposts' => - 1,
			)
		);
		$active_posts = array_filter(
			json_decode( json_encode( $posts ), true ),
			function ( $item ) {
				$content = json_decode( wp_unslash( $item['post_content'] ), true );

				return $content['status'];
			}
		);

		return wp_list_pluck( $active_posts, 'post_title', 'ID' );
	}

	/**
	 * @return array
	 */
	public function get_all_forms() {
		$posts = get_posts(
			array(
				'post_type'   => 'user_registration',
				'numberposts' => - 1,
				'post_status' => 'publish',

			)
		);

		return wp_list_pluck( $posts, 'post_title', 'ID' );
	}
}
