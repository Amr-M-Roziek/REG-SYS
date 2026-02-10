<?php
/**
 * UR_Setting_Single_Item Class.
 *
 * @since  1.0.0
 * @package  UserRegistrationPayments/Form/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UR_Setting_Single_Item Class.
 */
class UR_Setting_Single_Item extends UR_Field_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->field_id = 'single_item_advance_setting';
	}

	/**
	 * Render output.
	 *
	 * @param array $field_data Field Data.
	 */
	public function output( $field_data = array() ) {

		$this->field_data = $field_data;

		$this->register_fields();

		$field_html = $this->fields_html;

		return $field_html;
	}

	/**
	 * Get Register fields.
	 */
	public function register_fields() {

		$fields = array(

			'enable_selling_price_single_item' => array(
				'label'       => __( 'Enable Selling Price', 'user-registration' ),
				'data-id'     => $this->field_id . '_enable_selling_price_single_item',
				'name'        => $this->field_id . '[enable_selling_price_single_item]',
				'class'       => $this->default_class . ' ur-settings-item-type',
				'type'        => 'toggle',
				'required'    => false,
				'default'     => 'false',
				'placeholder' => '',
				'tip'         => __( 'Enable selling for this item.', 'user-registration' ),
			),
			'default_value'                    => array(
				'label'       => __( 'Regular Price', 'user-registration' ),
				'data-id'     => $this->field_id . '_default_value',
				'name'        => $this->field_id . '[default_value]',
				'class'       => $this->default_class . ' ur-settings-default-value ur-price-input',
				'type'        => 'text',
				'required'    => true,
				'default'     => '0.00',
				'placeholder' => '',
				'tip'         => __( 'Set Regular price for this item.', 'user-registration' ),
			),
			'selling_price'                    => array(
				'label'       => __( 'Selling Price', 'user-registration' ),
				'data-id'     => $this->field_id . '_selling_price',
				'name'        => $this->field_id . '[selling_price]',
				'class'       => $this->default_class . ' ur-settings-default-value ur-selling-price-input',
				'type'        => 'text',
				'required'    => true,
				'default'     => '0.00',
				'placeholder' => '',
				'tip'         => __( 'Set Selling price for this item.', 'user-registration' ),
			),
			'item_type'                        => array(
				'label'       => __( 'Item Type', 'user-registration' ),
				'data-id'     => $this->field_id . '_item_type',
				'name'        => $this->field_id . '[item_type]',
				'class'       => $this->default_class . ' ur-settings-item-type',
				'type'        => 'select',
				'required'    => true,
				'default'     => 'pre_defined',
				'options'     => array(
					'pre_defined'  => __( 'Pre Defined', 'user-registration' ),
					'user_defined' => __( 'User Defined', 'user-registration' ),
					'hidden'       => __( 'Hidden', 'user-registration' ),
				),
				'placeholder' => '',
				'tip'         => __( 'Choose type of item.', 'user-registration' ),
			),
		);

		$this->render_html( $fields );
	}
}

return new UR_Setting_Single_Item();
