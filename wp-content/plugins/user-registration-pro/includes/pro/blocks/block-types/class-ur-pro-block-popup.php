<?php
/**
 * User registration popup block.
 *
 * @since 3.1.5
 * @package user-registration
 */

defined( 'ABSPATH' ) || exit;
/**
 * Block registration form class.
 */
class UR_Pro_Block_Popup extends UR_Block_Abstract {
	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'popup';

	/**
	 * Build html.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function build_html( $content ) {
		$attr       = $this->attributes;
		$parameters = array();
		if ( isset( $attr['id'] ) ) {
			$parameters['id'] = $attr['id'];
		}
		if ( isset( $attr['isUseAsButton'] ) && ur_string_to_bool( $attr['isUseAsButton'] ) ) {
			$parameters['type'] = 'button';
		}

		if ( isset( $attr['buttonText'] ) ) {
			$parameters['button_text'] = $attr['buttonText'];
		}

		return User_Registration_Pro_Shortcodes::popup(
			$parameters
		);
	}
}
