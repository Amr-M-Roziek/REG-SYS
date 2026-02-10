<?php
namespace WPEverest\URM\Pro\External\DiviBuilder\Modules;

use WPEverest\URM\DiviBuilder\BuilderAbstract;

defined( 'ABSPATH' ) || exit;

/**
 * Donwload PDF Button Module class.
 *
 * @since xx.xx.xx
 */
class DownloadPdfButton extends BuilderAbstract {
	/**
	 * Donwload PDF Button Module slug.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $slug = 'urm-download-pdf-button';

	/**
	 * Module title.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $title = 'URM Donwload PDF Button';

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
					'main_content' => esc_html__( 'Donwload PDF Button', 'user-registration' ),
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

		$fields = array(
			'preview_state'                => array(
				'label'            => esc_html__( 'Preview', 'user-registration' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'basic_option',
				'toggle_slug'      => 'main_content',
				'options'          => array(
					'on'  => __( 'On', 'user-registration' ),
					'off' => __( 'Off', 'user-registration' ),
				),
				'default'          => 'on',
				'computed_affects' => array(
					'__render_download_pdf_button',
				),
			),
			'__render_download_pdf_button' => array(
				'type'                => 'computed',
				'computed_callback'   => 'WPEverest\URM\Pro\External\DiviBuilder\Modules\DownloadPdfButton::render_module',
				'computed_depends_on' => array(
					'preview_state',
				),
				'computed_minimum'    => array(
					'preview_state',
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

		if ( ! function_exists( 'user_registration_download_pdf_button' ) ) {
			return sprintf( '<div class="user-registration ur-frontend-form"><div class="user-registration-info">%s</div></div>', esc_html__( 'Please active the pdf submission addon.', 'user-registration' ) );
		}

		return user_registration_download_pdf_button();
	}
}
