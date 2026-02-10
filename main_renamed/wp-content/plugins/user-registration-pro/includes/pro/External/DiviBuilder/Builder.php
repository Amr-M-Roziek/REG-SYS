<?php
namespace WPEverest\URM\Pro\External\DiviBuilder;

use WPEverest\URM\Pro\External\DiviBuilder\Modules\DownloadPdfButton;
use WPEverest\URM\Pro\External\DiviBuilder\Modules\FrontendListing;
use WPEverest\URM\Pro\External\DiviBuilder\Modules\ViewProfileDetails;
use WPEverest\URM\Pro\External\DiviBuilder\Modules\Popup;

if ( file_exists( UR()->plugin_path() . '/vendor/autoload.php' ) ) {
	require_once UR()->plugin_path() . '/vendor/autoload.php';
}

defined( 'ABSPATH' ) || exit;

/**
 * Builder.
 *
 * @since xx.xx.xx
 */
class Builder {

	/**
	 * Holds single instance of the class.
	 *
	 * @var null|static
	 */
	private static $instance = null;

	/**
	 * Get instance of the class.
	 *
	 * @return static
	 */
	final public static function init() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since xx.xx.xx
	 */
	public function __construct() {
		$this->setup();
	}
	/**
	 * Init.
	 *
	 * @since xx.xx.xx
	 */
	public function setup() {
		add_filter( 'urm_divi_modules', array( $this, 'register_pro_modules' ), 10, 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	/**
	 * Function to add the pro modules.
	 *
	 * @param Array $modules The module list.
	 * @since xx.xx.xx
	 */
	public function register_pro_modules( $modules ) {

		return array_merge(
			$modules,
			array(
				'view-profile-details' => ViewProfileDetails::class,
				'popup'                => Popup::class,
				'frontend-listing'     => FrontendListing::class,
				'download-pdf-button'  => DownloadPdfButton::class,
			)
		);
	}

	/**
	 * Enqueue Divi Builder JavaScript.
	 *
	 * @since xx.xx.xx
	 */
	public function load_scripts() {
		if( ur_check_module_activation( 'frontend-listing' ) ) {
			wp_register_style( 'urm-frontend-listing-frontend-style', UR_ASSET_PATH . 'css/user-registration-frontend-listing-frontend.css', array(), UR_VERSION );
			wp_enqueue_style( 'urm-frontend-listing-frontend-style' );
		}

		wp_register_style( 'user-registration-pro-admin-style', UR()->plugin_url() . '/assets/css/user-registration-pro-admin.css', array(), UR_VERSION );

		wp_enqueue_style( 'user-registration-pro-admin-style' );
	}
}
