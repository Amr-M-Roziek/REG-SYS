<?php
/**
 * User_Registration_Frontend_Listing setup
 *
 * @package User_Registration_Frontend_Listing
 * @since  1.0.0
 */

namespace WPEverest\URFrontendListing;

use WPEverest\URFrontendListing\Admin\Admin;
use WPEverest\URFrontendListing\Frontend\Frontend;
use WPEverest\URFrontendListing\Admin\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main FrontendListing Class
 *
 * @class FrontendListing
 */
class FrontendListing {

		/**
		 * Admin class instance
		 *
		 * @var \Admin
		 * @since 1.0.0
		 */
		public $admin = null;

		/**
		 * Frontend class instance
		 *
		 * @var \Frontend
		 * @since 1.0.0
		 */
		public $frontend = null;

		/**
		 * Ajax.
		 *
		 * @since 1.0.0
		 *
		 * @var use WPEverest\URFrontendListing\AJAX;
		 */
		public $ajax = null;

		/**
		 * Shortcodes.
		 *
		 * @since 1.0.0
		 *
		 * @var WPEverest\URFrontendListing\Admin\Shortcodes;
		 */
		public $shortcodes = null;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
	public function __construct() {

		// Define UR_FRONTEND_LISTING_TEMPLATE_PATH.
		if ( ! defined( ' UR_FRONTEND_LISTING_TEMPLATE_PATH' ) ) {
			define( 'UR_FRONTEND_LISTING_TEMPLATE_PATH', UR_TEMPLATE_PATH . 'pro/frontend-listing/' );
		}
		require __DIR__ .'/Functions/CoreFunctions.php';
		add_action( 'init', array( $this, 'create_post_type' ), 0 );
		add_action( 'init', array( $this, 'includes' ) );
	}

		/**
		 * Includes.
		 */
	public function includes() {

		$this->ajax       = new Ajax();
		$this->shortcodes = new Shortcodes();

		// Class admin.
		if ( $this->is_admin() ) {
			// require file.
			$this->admin = new Admin();
		} else {
			// require file.
			$this->frontend = new Frontend();
		}
	}

		/**
		 * Check if is admin or not and load the correct class
		 *
		 * @return bool
		 * @since 1.0.0
		 */
	public function is_admin() {
		$check_ajax    = defined( 'DOING_AJAX' ) && DOING_AJAX;
		$check_context = isset( $_REQUEST['context'] ) && $_REQUEST['context'] == 'frontend';

		return is_admin() && ! ( $check_ajax && $check_context );
	}

		/**
		 * Rgister Custom Post Type.
		 */
	public function create_post_type() {
		$raw_referer = wp_parse_args( wp_parse_url( wp_get_raw_referer(), PHP_URL_QUERY ) );
		$public      = isset( $_GET['add-new-frontend-listing'] ) || isset( $raw_referer['add-new-frontend-listing'] ) ? true : false;

		register_post_type(
			'ur_frontend_listings',
			apply_filters(
				'user_registration_frontend_listings_post_type',
				array(
					'labels'              => array(
						'name'                  => __( 'Listings', 'user-registration-frontend-listing' ),
						'singular_name'         => __( 'Listing', 'user-registration-frontend-listing' ),
						'all_items'             => __( 'All Listings', 'user-registration-frontend-listing' ),
						'menu_name'             => _x( 'Listings', 'Admin menu name', 'user-registration-frontend-listing' ),
						'add_new'               => __( 'Add New', 'user-registration-frontend-listing' ),
						'add_new_item'          => __( 'Add new', 'user-registration-frontend-listing' ),
						'edit'                  => __( 'Edit', 'user-registration-frontend-listing' ),
						'edit_item'             => __( 'Edit listing', 'user-registration-frontend-listing' ),
						'new_item'              => __( 'New listing', 'user-registration-frontend-listing' ),
						'view'                  => __( 'View listing', 'user-registration-frontend-listing' ),
						'view_item'             => __( 'View listings', 'user-registration-frontend-listing' ),
						'search_items'          => __( 'Search listings', 'user-registration-frontend-listing' ),
						'not_found'             => __( 'No listings found', 'user-registration-frontend-listing' ),
						'not_found_in_trash'    => __( 'No listings found in trash', 'user-registration-frontend-listing' ),
						'parent'                => __( 'Parent listing', 'user-registration-frontend-listing' ),
						'featured_image'        => __( 'Listing image', 'user-registration-frontend-listing' ),
						'set_featured_image'    => __( 'Set listing image', 'user-registration-frontend-listing' ),
						'remove_featured_image' => __( 'Remove listing image', 'user-registration-frontend-listing' ),
						'use_featured_image'    => __( 'Use as listing image', 'user-registration-frontend-listing' ),
						'insert_into_item'      => __( 'Insert into listing', 'user-registration-frontend-listing' ),
						'uploaded_to_this_item' => __( 'Uploaded to this listing', 'user-registration-frontend-listing' ),
						'filter_items_list'     => __( 'Filter listing', 'user-registration-frontend-listing' ),
						'items_list_navigation' => __( 'Listing navigation', 'user-registration-frontend-listing' ),
						'items_list'            => __( 'Listing list', 'user-registration-frontend-listing' ),

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
					'supports'            => false,
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => false,
					'supports'            => array( 'title' ),
				)
			)
		);
	}
}
new FrontendListing();
