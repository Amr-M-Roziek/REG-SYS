<?php
/**
 * User registration frontend listing block.
 *
 * @since 3.1.5
 * @package user-registration
 */

defined( 'ABSPATH' ) || exit;
/**
 * Block registration form class.
 */
class UR_Pro_Block_Frontend_Listing extends UR_Block_Abstract {
	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'frontend-listing';

	/**
	 * Build html.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	protected function build_html( $content ) {
		$attr       = $this->attributes;
		$parameters = array();
		if ( isset( $attr['id'] ) ) {
			$parameters['id'] = $attr['id'];
		}
		return  \WPEverest\URFrontendListing\Admin\Shortcodes::frontend_list( $parameters );
	}
}
