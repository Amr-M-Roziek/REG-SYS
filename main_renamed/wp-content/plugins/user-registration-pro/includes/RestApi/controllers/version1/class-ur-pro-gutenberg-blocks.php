<?php
/**
 * Pro Blocks controller class.
 *
 * @since 3.1.6
 *
 * @package  UserRegistration/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * UR_AddonsClass
 */
class UR_Pro_Gutenberg_Blocks {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'user-registration-pro/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'pro-gutenberg-blocks';

	/**
	 * Register routes.
	 *
	 * @since 2.1.4
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/popup-list',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'ur_get_popup_list' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/fronend-listing-list',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'ur_get_fronend_listing_list' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
			)
		);
	}

	/**
	 * Get Popup Lists.
	 *
	 * @since 3.1.6
	 *
	 * @return array Addon lists.
	 */
	public static function ur_get_popup_list() {
		$args       = array(
			'post_type'   => 'ur_pro_popup',
			'post_status' => 'public',
		);
		$popups     = new WP_Query( $args );
		$popup_list = array();
		foreach ( $popups->posts as $popup ) {
			$popup_list[ $popup->ID ] = $popup->post_title;
		}
		return new \WP_REST_Response(
			array(
				'success'     => true,
				'popup_lists' => $popup_list,
			),
			200
		);
	}
	/**
	 * Get Fronend Listing Lists.
	 *
	 * @since 3.1.6
	 *
	 * @return array Addon lists.
	 */
	public static function ur_get_fronend_listing_list() {
		$args           = array(
			'post_type'   => 'ur_frontend_listings',
			'post_status' => 'public',
		);
		$frontend_lists = get_posts( $args );
		$frontend_list  = array();
		foreach ( $frontend_lists as $frontend ) {
			$frontend_list[ $frontend->ID ] = $frontend->post_title;
		}
		return new \WP_REST_Response(
			array(
				'success'     => true,
				'popup_lists' => $frontend_list,
			),
			200
		);
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' );
	}
}
