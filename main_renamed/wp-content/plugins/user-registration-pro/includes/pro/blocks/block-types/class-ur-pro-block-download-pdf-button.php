<?php
/**
 * User registration download pdf button block.
 *
 * @since 3.1.5
 * @package user-registration
 */

defined( 'ABSPATH' ) || exit;
/**
 * Block registration form class.
 */
class UR_Pro_Block_Download_Pdf_Button extends UR_Block_Abstract {
	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'download-pdf-button';

	/**
	 * Build html.
	 *
	 * @param string $content
	 * @return string
	 */
	protected function build_html( $content ) {
		if ( function_exists( 'user_registration_download_pdf_button' ) ) {
			$content = user_registration_download_pdf_button();
		}

		return $content;
	}
}
