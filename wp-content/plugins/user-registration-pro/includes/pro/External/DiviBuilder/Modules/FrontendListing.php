<?php
namespace WPEverest\URM\Pro\External\DiviBuilder\Modules;

use WPEverest\URM\DiviBuilder\BuilderAbstract;
use WPEverest\URFrontendListing\Admin\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * FrontendListing Module class.
 *
 * @since xx.xx.xx
 */
class FrontendListing extends BuilderAbstract {
	/**
	 * FrontendListing Module slug.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $slug = 'urm-frontend-listing';

	/**
	 * Module title.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $title = 'URM Frontend Listing';

	/**
	 * Settings
	 *
	 * @since xx.xx.xx
	 * @return void
	 */
	public function settings_init() {

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Frontend Listing', 'user-registration' ),
				),
			),
		);
	}

	/**
	 * Displays the module setting fields.
	 *
	 * @since xx.xx.xx
	 * @return array $fields Array of settings fields.
	 */
	public function get_fields() {
		$frontend_list = array( esc_html__( '-- Select Frontend Listing --', 'user-registration' ) );

		$args = array(
			'post_type'   => 'ur_frontend_listings',
			'post_status' => 'public',
		);

		$frontend_lists = get_posts( $args );

		foreach ( $frontend_lists as $frontend ) {
			$frontend_list[ $frontend->ID ] = $frontend->post_title;
		}

		$fields = array(
			'id'                        => array(
				'label'            => esc_html__( 'Frontend Listing', 'user-registration' ),
				'type'             => 'select',
				'option_category'  => 'basic_option',
				'toggle_slug'      => 'main_content',
				'options'          => $frontend_list,
				'default'          => '',
				'computed_affects' => array(
					'__render_frontend_listing',
				),
			),
			'__render_frontend_listing' => array(
				'type'                => 'computed',
				'computed_callback'   => 'WPEverest\URM\Pro\External\DiviBuilder\Modules\FrontendListing::render_module',
				'computed_depends_on' => array(
					'id',
				),
				'computed_minimum'    => array(
					'id',
				),
			),

		);

		return $fields;
	}

	/**
	 * Render content.
	 *
	 * @param array $props The attributes values.
	 * @return void
	 */
	public static function render_module( $props = array() ) {

		$parameters = array();

		if ( isset( $props['id'] ) ) {
			$parameters['id'] = $props['id'];
		}

		if ( ! defined( 'UR_FRONTEND_LISTING_TEMPLATE_PATH' ) ) {
			return sprintf( '<div class="user-registration ur-frontend-form"><div class="user-registration-info">%s</div></div>', esc_html__( 'Please active the frontend listing.', 'user-registration' ) );
		}

		return Shortcodes::frontend_list( $parameters );
	}
}
