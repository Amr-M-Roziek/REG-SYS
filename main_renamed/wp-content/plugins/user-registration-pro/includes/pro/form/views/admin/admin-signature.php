<?php
/**
 * Form View: Signature
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$placeholder = ! $this->get_general_setting_data( 'placeholder' ) ? esc_attr( $this->get_general_setting_data( 'placeholder' ) ) : '';
?>
<div class="ur-input-type-signature ur-admin-template">

	<div class="ur-label">
		<label><?php echo esc_html( $this->get_general_setting_data( 'label' ) ); ?></label>

	</div>
	<div class="ur-field" data-field-key="signature">
		<input type="hidden" id="ur-input-type-signature" placeholder="<?php echo esc_attr( $placeholder ) ?>" disabled>
		<canvas style="width:100%;height:100px;max-width:100%;max-height:100%;"></canvas>
	</div>
	<?php
	UR_Form_Field_Signature::get_instance()->get_setting();

	?>
</div>
