<?php
/**
 * User registration Pro blocks.
 *
 * @since 3.1.5
 * @package user-registration
 */

defined( 'ABSPATH' ) || exit;
/**
 * User registration blocks class.
 */
class UR_Pro_Blocks {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @since 3.1.5
	 */
	private function init_hooks() {
		add_filter( 'user_registration_block_types', array( $this, 'get_pro_block_types' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_pro_gutenberg_blocks' ) );
	}

	/**
	 * Get block types.
	 *
	 * @return AbstractBlock[]
	 */
	public function get_pro_block_types( $blocks ): array {
		$pro_blocks_classes = array(
			UR_Pro_Block_View_Profile_Details::class,
			UR_Pro_Block_Popup::class,
		);

		if ( is_plugin_active( 'user-registration-pdf-form-submission/user-registration-pdf-form-submission.php' ) ) {
			$pro_blocks_classes[] = UR_Pro_Block_Download_Pdf_Button::class;
		}
		if ( ur_check_module_activation( 'frontend-listing' ) ) {
			$pro_blocks_classes[] = UR_Pro_Block_Frontend_Listing::class;
		}
		return array_merge(
			$blocks,
			apply_filters(
				'user_registration_pro_block_types',
				$pro_blocks_classes
			)
		);
	}
	/**
	 * Function to register the block scripts.
	 */
	public function register_pro_gutenberg_blocks() {
		global $wp_scripts;

		wp_localize_script(
			'user-registration-blocks-editor',
			'_UR_PRO_BLOCKS_',
			array(
				'logoUrl'               => UR()->plugin_url() . '/assets/images/logo.png',
				'urRestApiNonce'        => wp_create_nonce( 'wp_rest' ),
				'restURL'               => rest_url(),
				'isPro'                 => is_plugin_active( 'user-registration-pro/user-registration.php' ),
				'iscRestrictionActive'  => ur_check_module_activation( 'content-restriction' ),
				'isPdfSubmissionActive' => is_plugin_active( 'user-registration-pdf-form-submission/user-registration-pdf-form-submission.php' ),
				'isFrontendListing'     => ur_check_module_activation( 'frontend-listing' ),
			)
		);
	}
}
return new UR_Pro_Blocks();
