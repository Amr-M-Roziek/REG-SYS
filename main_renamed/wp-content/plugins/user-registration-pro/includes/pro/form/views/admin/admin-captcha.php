<?php
/**
 * Form View: Captcha
 *
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$placeholder = ! $this->get_general_setting_data( 'placeholder' ) ? esc_attr( $this->get_general_setting_data( 'placeholder' ) ) : '';
$format      = ! empty( $this->get_general_setting_data( 'captcha_format' ) ) ? $this->get_general_setting_data( 'captcha_format' ) : 'math';
$field_name  = isset( $this->field_defaults['default_field_name'] ) ? $this->field_defaults['default_field_name'] : array();
$number1     = wp_rand( $this->math['min'], $this->math['max'] );
$number2     = wp_rand( $this->math['min'], $this->math['max'] );
$cal         = $this->math['cal'][ wp_rand( 0, count( $this->math['cal'] ) - 1 ) ];
$questions   = isset( $this->field_defaults['default_options'] ) ? $this->field_defaults['default_options'] : array();
$icons   = isset( $this->field_defaults['default_image_options'] ) ? $this->field_defaults['default_image_options'] : array();
$question    = current( $questions );
$icon    = current( $icons );
?>
<div class="ur-input-type-captcha ur-admin-template">
	<div class="ur-label">
		<label><?php echo esc_html( $this->get_general_setting_data( 'label' ) ); ?><span style="color:red">*</span></label>
	</div>
	<div class="ur-field" data-field-key="captcha" name= "<?php echo esc_attr( $field_name ); ?>" captcha-format ="<?php echo esc_attr( $format ); ?>" >
		<span class="ur-captcha-equation">
				<?php printf( '%s %s %s = ', $number1, $cal, $number2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>
		<p class="ur-captcha-question"><?php echo esc_html( $question['question'] ); ?></p>
		<input id="ur-input-type-captcha-math" type="text" placeholder="<?php echo esc_attr( $placeholder ) ?>" value="" disabled/>
		<p class="ur-captcha-image-label" type="text"> <?php echo __( sprintf( "Please select the correct <b>%s</b>", $icon['icon_tag'] ), 'user-registration' ) ?> </p>
		<div class="ur-captcha-image-icons-group">
			<span class="<?php echo $icon['icon-1'] ?>"></span>
			<span class="<?php echo $icon['icon-2'] ?>"></span>
			<span class="<?php echo $icon['icon-3'] ?>"></span>
		</div>
	</div>


<?php
	UR_Form_Field_Captcha::get_instance()->get_setting();
	?>
	<div style="clear:both"></div>
</div>
