<?php
/**
 * Form View: Input Type Multiple Choice
 *
 * @package User_Registration_Payments
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Compatibility for older version. Get string value from options in advanced settings. Modified since @1.5.7
$default_options = isset( $this->field_defaults['default_options'] ) ? $this->field_defaults['default_options'] : array();
$options         = isset( $this->admin_data->general_setting->options ) ? $this->admin_data->general_setting->options : $default_options;
$default_values  = isset( $this->admin_data->general_setting->default_value ) ? $this->admin_data->general_setting->default_value : array();
$placeholder     = UR()->plugin_url() . '/assets/images/UR-placeholder.png';
$image_class     = ( isset( $this->admin_data->general_setting->image_choice ) && ur_string_to_bool( $this->admin_data->general_setting->image_choice ) ) ? 'user-registration-image-options' : '';
?>

<div class="ur-input-type-multiple_choice ur-admin-template">
	<div class="ur-label">
		<label><?php echo esc_html( $this->get_general_setting_data( 'label' ) ); ?></label>
	</div>
	<div class="ur-field <?php esc_attr_e( $image_class ); ?>" data-field-key="multiple_choice">
		<?php
		if ( count( $options ) < 1 ) {
			echo "<label><input type = 'checkbox'  value='1' disabled/></label>";
		}

		foreach ( $options as $option ) {
			$label         = is_array( $option ) ? $option['label'] : $option->label;
			$value         = is_array( $option ) ? $option['value'] : $option->value;
			$image         = ( is_array( $option ) && isset( $option['image'] ) ) ? $option['image'] : ( ( is_object( $option ) && isset( $option->image ) ) ? $option->image : null );
			$currency      = get_option( 'user_registration_payment_currency', 'USD' );
			$currencies    = ur_payment_integration_get_currencies();
			$currency      = $currency . ' ' . $currencies[ $currency ]['symbol'];
			$checked       = in_array( $label, $default_values ) ? 'checked' : '';
			$checked_class = '';
			if ( isset( $this->admin_data->general_setting->image_choice ) && ur_string_to_bool( $this->admin_data->general_setting->image_choice ) ) {
				$checked_class = in_array( $label, $default_values ) ? 'ur-image-choice-checked' : '';
			}

			echo "<label class='" . $checked_class . "'><span class='user-registration-image-choice'>";
			if ( isset( $this->admin_data->general_setting->image_choice ) && ur_string_to_bool( $this->admin_data->general_setting->image_choice ) ) {
				if ( ! empty( $image ) ) {
					echo "<img src='" . esc_url( $image ) . "' alt='" . esc_attr( trim( $label ) ) . "' width='200px'>";
				} else {
					echo "<img src='" . esc_url( $placeholder ) . "' alt='" . esc_attr( trim( $label ) ) . "' width='200px'>";
				}
			} else {
				echo "<img src='" . esc_url( $placeholder ) . "' alt='" . esc_attr( trim( $label ) ) . "' width='200px' style='display:none'>";
			}
			echo "</span><input type = 'checkbox'  value='" . esc_attr( trim( $label ) ) . "' " . $checked . ' disabled/>' . esc_html( trim( $label ) ) . ' - ' . $currency . ' ' . esc_html( $value ) . '</label>';
		}
		?>
	</div>
</div>
