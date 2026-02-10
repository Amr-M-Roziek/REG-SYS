<?php
namespace WPEverest\URM\Pro\External\DiviBuilder\Modules;

use WPEverest\URM\DiviBuilder\BuilderAbstract;

defined( 'ABSPATH' ) || exit;

/**
 * Popup Module class.
 *
 * @since xx.xx.xx
 */
class Popup extends BuilderAbstract {
	/**
	 * Popup Module slug.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $slug = 'urm-popup';

	/**
	 * Module title.
	 *
	 * @since xx.xx.xx
	 * @var string
	 */
	public $title = 'URM Popup';

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
					'main_content' => esc_html__( 'Popup', 'user-registration' ),
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
		$popup_list = array( esc_html__( '-- Select Popup --', 'user-registration' ) );

		$args = array(
			'post_type'   => 'ur_pro_popup',
			'post_status' => 'public',
		);

		$popups = new \WP_Query( $args );

		foreach ( $popups->posts as $popup ) {
			$popup_list[ $popup->ID ] = $popup->post_title;
		}

		$fields = array(
			'popup_id'         => array(
				'label'            => esc_html__( 'Popup', 'user-registration' ),
				'type'             => 'select',
				'option_category'  => 'basic_option',
				'toggle_slug'      => 'main_content',
				'options'          => $popup_list,
				'default'          => '',
				'computed_affects' => array(
					'__render_popup',
				),
			),
			'is_use_as_button' => array(
				'label'            => esc_html__( 'Use as Button', 'user-registration' ),
				'type'             => 'yes_no_button',
				'option_category'  => 'basic_option',
				'toggle_slug'      => 'main_content',
				'options'          => array(
					'on'  => __( 'On', 'user-registration' ),
					'off' => __( 'Off', 'user-registration' ),
				),
				'default'          => 'on',
				'computed_affects' => array(
					'__render_popup',
				),
			),
			'button_text'      => array(
				'label'            => esc_html__( 'Button Text', 'user-registration' ),
				'type'             => 'text',
				'option_category'  => 'basic_option',
				'description'      => esc_html__( 'This option let you to add the custom button text.', 'user-registration' ),
				'toggle_slug'      => 'main_content',
				'default'          => esc_html__( 'Popup', 'user-registration' ),
				'computed_affects' => array(
					'__render_popup',
				),
			),
			'__render_popup'   => array(
				'type'                => 'computed',
				'computed_callback'   => 'WPEverest\URM\Pro\External\DiviBuilder\Modules\Popup::render_module',
				'computed_depends_on' => array(
					'is_use_as_button',
					'popup_id',
					'button_text',
				),
				'computed_minimum'    => array(
					'is_use_as_button',
					'popup_id',
					'button_text',
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

		if ( isset( $props['popup_id'] ) ) {
			$parameters['id'] = $props['popup_id'];
		}

		if ( isset( $props['is_use_as_button'] ) && ur_string_to_bool( $props['is_use_as_button'] ) ) {
			$parameters['type'] = 'button';
		}

		if ( isset( $props['button_text'] ) ) {
			$parameters['button_text'] = $props['button_text'];
		}

		$ouput = \User_Registration_Pro_Shortcodes::popup(
			$parameters
		);

		return $ouput;
	}
}
