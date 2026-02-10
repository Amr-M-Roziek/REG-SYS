<?php
/**
 * Coupons
 *
 * Coupons Main Page
 *
 * @class    COUPONS
 * @package  Coupons
 * @author   WPEverest
 */

namespace WPEverest\URMembership\Coupons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupons
 */
class Coupons {
	/**
	 * Page name.
	 *
	 * @var string
	 */
	private $page;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Initialize hooks and assign protected variables;
	 */
	public function __construct() {
		$this->page      = 'user-registration-coupons';
		$this->post_type = 'ur_coupons';
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_coupons_menu' ), 70 );
		add_action( 'in_admin_header', array( __CLASS__, 'hide_unrelated_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'init', array( $this, 'register_coupon_post_type' ) );
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_filter( 'user_registration_payment_fields', array( $this, 'add_coupon_field' ), 10, 1 );

	}

	public function add_coupon_field( $fields ) {
		if ( ! in_array( 'coupon', $fields ) ) {
			$fields[] = 'coupon';
		}

		return $fields;
	}

	/**
	 * Include Used Classes
	 *
	 * @return void
	 */
	public function includes() {
		new Ajax();
	}

	/**
	 * Remove Notices.
	 */
	public static function hide_unrelated_notices() {
		if ( empty( $_REQUEST['page'] ) || 'member-payment-history' !== $_REQUEST['page'] ) {
			return;
		}
		global $wp_filter;
		foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
			if ( ! empty( $wp_filter[ $wp_notice ]->callbacks ) && is_array( $wp_filter[ $wp_notice ]->callbacks ) ) {
				foreach ( $wp_filter[ $wp_notice ]->callbacks as $priority => $hooks ) {
					foreach ( $hooks as $name => $arr ) {
						// Remove all notices except user registration plugins notices.
						if ( ! strstr( $name, 'user_registration_' ) ) {
							unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
						}
					}
				}
			}
		}
	}

	/**
	 * Add coupons menu to the menu list under user registration.
	 *
	 * @return void
	 */
	public function add_coupons_menu() {
		$coupons_page = add_submenu_page(
			'user-registration',
			__( 'Coupons', 'user-registration' ), // page title.
			__( 'Coupons', 'user-registration' ), // menu title.
			'edit_posts', // Capability required to access.
			$this->page, // Menu slug.
			array(
				$this,
				'render_coupons_page',
			),
			6
		);
		add_action( 'load-' . $coupons_page, array( $this, 'coupons_initialization' ) );
	}

	/**
	 * Initialize coupons page before the actual page load.
	 *
	 * @return void
	 */
	public function coupons_initialization() {
		if ( isset( $_GET['page'] ) && 'user-registration-coupons' === $_GET['page'] ) {

			$action_page = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
			switch ( $action_page ) {
				case 'add_new_coupon':
					break;
				default:
					global $coupons_list_table;
					$coupons_list_table = new CouponsListTable();
					break;
			}
		}
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( empty( $_GET['page'] ) || 'user-registration-coupons' !== $_GET['page'] ) {
			return;
		}
		$suffix = defined( 'SCRIPT_DEBUG' ) ? '' : '.min';
		if ( ! wp_script_is( 'ur-snackbar', 'registered' ) ) {

			wp_register_script( 'ur-snackbar', UR()->plugin_url() . '/assets/js/ur-snackbar/ur-snackbar' . $suffix . '.js', array(), '1.0.0', true );
		}

		wp_register_script( 'ur-coupons', UR()->plugin_url() . '/assets/js/pro/admin/ur-coupons' . $suffix . '.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'ur-snackbar' );
		wp_enqueue_script( 'sweetalert2' );
		wp_enqueue_script( 'ur-coupons' );
		$this->localize_scripts();
	}

	/**
	 * Enqueue styles
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( empty( $_GET['page'] ) || 'user-registration-coupons' !== $_GET['page'] ) {
			return;
		}
		if ( ! wp_style_is( 'ur-snackbar', 'reqistered' ) ) {
			wp_register_style( 'ur-snackbar', UR()->plugin_url() . '/assets/css/ur-snackbar/ur-snackbar.css', array(), '1.0.0' );
			wp_enqueue_style( 'ur-snackbar' );
		}
		wp_register_style( 'ur-core-builder-style', UR()->plugin_url() . '/assets/css/admin.css', array(), UR_VERSION );
		wp_register_style( 'ur-coupon-css', UR()->plugin_url() . '/assets/css/ur-coupons.css', array(), UR_VERSION );
		wp_enqueue_style( 'ur-coupon-css' );
		wp_enqueue_style( 'sweetalert2' );
		wp_enqueue_style( 'ur-core-builder-style' );
		wp_enqueue_style( 'ur-membership-admin-style' );
	}

	/**
	 * Render coupons page
	 *
	 * @return void
	 */
	public function render_coupons_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		switch ( $action ) {
			case 'add_new_coupon':
				$post_id = isset( $_GET['post_id'] ) ? sanitize_text_field( $_GET['post_id'] ) : '';
				$coupon  = get_post( $post_id );

				$coupon_details = json_decode( get_post_meta( $post_id, 'ur_coupon_meta', true ), true );

				$this->render_coupon_create_page( $coupon, $coupon_details );
				break;
			default:
				$this->render_coupons_listing_page();
				break;
		}
	}

	/**
	 * Render coupons listing page
	 *
	 * @return void
	 */
	public function render_coupons_listing_page() {
		global $coupons_list_table;

		if ( ! $coupons_list_table ) {
			return;
		}
		$enable_members_button = true;
		require __DIR__ . '/views/header.php';
		?>
		<div id="user-registration-list-table-page">
			<div class="user-registration-list-table-heading" id="ur-users-page-topnav">
				<div class="ur-page-title__wrapper ">
					<h1>
						<?php esc_html_e( 'Coupons', 'user-registration' ); ?>
					</h1>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->page . '&action=add_new_coupon' ) ); ?>"
				   id="ur-coupon-add-btn" class="button button-primary">
					+ <?php esc_html_e( 'Create New Coupon', 'user-registration' ); ?>
				</a>
			</div>
			<div id="user-registration-pro-filters-row" style="align-items: center;">
				<div class="ur-membership-filter-container" style="display: flex;align-items: center; gap: 10px">
					<form method="get" id="user-registration-users-search-form"
						  style="display: flex; width: auto; gap: 20px">
						<input type="hidden" name="page" value="member-payment-history"/>
						<?php
						$coupons_list_table->display_search_box();
						?>
					</form>
				</div>

			</div>
			<hr>
			<form method="get" id="ur-coupon-list-form">
				<input type="hidden" name="page" value="<?php echo esc_attr( $this->page ); ?>"/>
				<?php
				$coupons_list_table->display_page();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * render_coupon_create_page
	 *
	 * @param array $coupon Coupon from post table
	 * @param array $coupon_details Coupon Details from post meta table
	 *
	 * @return void
	 */
	public function render_coupon_create_page( $coupon = array(), $coupon_details = array() ) {
		$coupons_list_table = new CouponsListTable();
		$memberships        = $coupons_list_table->get_all_memberships();
		$forms              = $coupons_list_table->get_all_forms();
		include __DIR__ . '/views/create-coupon.php';
	}

	/**
	 * localize scripts
	 *
	 * @return void
	 */
	public function localize_scripts() {
		$title     = esc_html__( 'Untitled', 'user-registration' );
		$coupon_id = ( $_REQUEST['post_id'] ) ?? '';
		$symbol    = '$';
		if ( ur_check_module_activation( 'payments' ) || is_plugin_active( 'user-registration-stripe/user-registration-stripe.php' ) || is_plugin_active( 'user-registration-authorize-net/user-registration-authorize-net.php' ) ) {
			$currency   = get_option( 'user_registration_payment_currency', 'USD' );
			$currencies = ur_payment_integration_get_currencies();
			$symbol     = $currencies[ $currency ]['symbol'];
		}

		wp_localize_script(
			'ur-coupons',
			'ur_coupons_localized_data',
			array(
				'_nonce'                 => wp_create_nonce( 'ur_member_coupons' ),
				'coupon_id'              => $coupon_id,
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'labels'                 => $this->get_i18_labels(),
				'delete_icon'            => plugins_url( 'assets/images/users/delete-user-red.svg', UR_PLUGIN_FILE ),
				'global_currency_symbol' => $symbol,
			)
		);
	}

	/**
	 * Get i18 labels
	 *
	 * @return array
	 */
	public function get_i18_labels() {

		return array(
			'network_error'                             => esc_html__( 'Network error', 'user-registration' ),
			'i18n_select_dropdown'                      => __( 'Select an option.', 'user-registration' ),
			'i18n_start_date_validation'                => __( 'Start date must be greater than today.', 'user-registration' ),
			'i18n_end_date_validation'                  => __( 'End date must be greater than the Start Date.', 'user-registration' ),
			'i18n_field_is_required'                    => __( 'field is required.', 'user-registration' ),
			'i18n_error'                                => __( 'Error', 'user-registration' ),
			'i18n_applicable_for_form_validation'       => __( 'At least one applicable forms must be selected.', 'user-registration' ),
			'i18n_applicable_for_membership_validation' => __( 'At least one applicable membership must be selected.', 'user-registration' ),
			'i18n_percent_limit_validation'             => __( 'Percent cannot be more than 100.', 'user-registration' ),
			'i18n_amount_type_validation'               => __( 'Discount amount must be a valid integer.', 'user-registration' ),
			'i18n_prompt_title'                         => __( 'Delete Coupon', 'user-registration-membership' ),
			'i18n_prompt_bulk_subtitle'                 => __( 'Are you sure you want to delete these coupons permanently?', 'user-registration-membership' ),
			'i18n_prompt_single_subtitle'               => __( 'Are you sure you want to delete this coupon permanently?', 'user-registration-membership' ),
			'i18n_prompt_delete'                        => __( 'Delete', 'user-registration-membership' ),
			'i18n_prompt_cancel'                        => __( 'Cancel', 'user-registration-membership' ),
			'i18n_prompt_no_membership_selected'        => __( 'Please select at least one coupon.', 'user-registration-membership' ),
		);
	}

	/**
	 * Register coupon post type
	 *
	 * @return void
	 */
	public function register_coupon_post_type() {
		$raw_referer = wp_parse_args( wp_parse_url( wp_get_raw_referer(), PHP_URL_QUERY ) );
		$public      = isset( $_GET['add_new_coupon'] ) || isset( $raw_referer['add_new_coupon'] );
		register_post_type(
			$this->post_type,
			apply_filters(
				'user_registration_membership_post_type',
				array(
					'labels'              => array(
						'name'                  => __( 'Coupons', 'user-registration' ),
						'singular_name'         => __( 'Coupon', 'user-registration' ),
						'all_items'             => __( 'All Coupon', 'user-registration' ),
						'menu_name'             => _x( 'Coupons', 'Admin menu name', 'user-registration' ),
						'add_new'               => __( 'Add New', 'user-registration' ),
						'add_new_item'          => __( 'Add new', 'user-registration' ),
						'edit'                  => __( 'Edit', 'user-registration' ),
						'edit_item'             => __( 'Edit coupon', 'user-registration' ),
						'new_item'              => __( 'New coupon', 'user-registration' ),
						'view'                  => __( 'View coupon', 'user-registration' ),
						'view_item'             => __( 'View coupon', 'user-registration' ),
						'search_items'          => __( 'Search coupon', 'user-registration' ),
						'not_found'             => __( 'No coupon found', 'user-registration' ),
						'not_found_in_trash'    => __( 'No coupon found in trash', 'user-registration' ),
						'parent'                => __( 'Parent coupon', 'user-registration' ),
						'featured_image'        => __( 'Coupon image', 'user-registration' ),
						'set_featured_image'    => __( 'Set membership coupon', 'user-registration' ),
						'remove_featured_image' => __( 'Remove coupon image', 'user-registration' ),
						'use_featured_image'    => __( 'Use as coupon image', 'user-registration' ),
						'insert_into_item'      => __( 'Insert into coupon', 'user-registration' ),
						'uploaded_to_this_item' => __( 'Uploaded to this coupon', 'user-registration' ),
						'filter_items_list'     => __( 'Filter coupon', 'user-registration' ),
						'items_list_navigation' => __( 'Coupon navigation', 'user-registration' ),
						'items_list'            => __( 'Coupon list', 'user-registration' ),

					),
					'public'              => $public,
					'show_ui'             => true,
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'publicly_queryable'  => $public,
					'exclude_from_search' => $public,
					'show_in_menu'        => false,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => false,
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => false,
					'supports'            => array( 'title' ),
				)
			)
		);
	}

	/**
	 * Coupons Listing admin actions.
	 */
	public function actions() {
		if ( isset( $_GET['page'] ) && 'user-registration-coupons' === $_GET['page'] ) {
			// Bulk actions.
			if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['coupon'] ) ) {
				$this->bulk_actions();
			}
			// Empty trash.
			if ( isset( $_GET['empty_trash'] ) ) {
				$this->empty_trash();
			}
		}
	}

	/**
	 * Bulk actions.
	 */
	private function bulk_actions() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permissions to edit user registration coupon lists!', 'user-registration' ) );
		}

		$coupon_list = array_map( 'absint', ! empty( $_REQUEST['coupon'] ) ? (array) $_REQUEST['coupon'] : array() );

		$action = isset( $_REQUEST['action'] ) ? wp_unslash( $_REQUEST['action'] ) : array();

		switch ( $action ) {
			case 'trash':
				$this->bulk_trash( $coupon_list );
				break;
			case 'untrash':
				$this->bulk_untrash( $coupon_list );
				break;
			case 'delete':
				$this->bulk_trash( $coupon_list, true );
				break;
			default:
				break;
		}
	}

	/**
	 * Bulk trash/delete.
	 *
	 * @param array $coupon_list Membership List post id.
	 * @param bool $delete Delete action.
	 */
	private function bulk_trash( $coupon_list, $delete = false ) {
		foreach ( $coupon_list as $coupon_id ) {
			if ( $delete ) {
				wp_delete_post( $coupon_id, true );
			} else {
				wp_trash_post( $coupon_id );
			}
		}
		$type   = ! EMPTY_TRASH_DAYS || $delete ? 'deleted' : 'trashed';
		$qty    = count( $coupon_list );
		$status = isset( $_GET['status'] ) ? '&status=' . sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=' . $this->page . $status . '&' . $type . '=' . $qty ) ) );
		exit();
	}

	/**
	 * Bulk untrash.
	 *
	 * @param array $coupon_list Coupon List.
	 */
	private function bulk_untrash( $coupon_list ) {
		foreach ( $coupon_list as $coupon_id ) {
			wp_untrash_post( $coupon_id );
		}
		$qty = count( $coupon_list );
		// Redirect to Frontend Listings page.
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=' . $this->page . '&status=trashed&untrashed=' . $qty ) ) );
		exit();
	}


	/**
	 * Empty Trash.
	 */
	private function empty_trash() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'empty_trash' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'user-registration' ) );
		}

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_die( esc_html__( 'You do not have permissions to delete coupons!', 'user-registration' ) );
		}

		$coupon_lists = get_posts(
			array(
				'post_type'           => $this->post_type,
				'ignore_sticky_posts' => true,
				'nopaging'            => true,
				'post_status'         => 'trash',
				'fields'              => 'ids',
			)
		);

		foreach ( $coupon_lists as $coupon ) {
			wp_delete_post( $coupon, true );
		}

		$qty = count( $coupon_lists );

		// Redirect to Frontend Listings page.
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=page=' . $this->page . '&deleted=' . $qty ) ) );
		exit();
	}
}
