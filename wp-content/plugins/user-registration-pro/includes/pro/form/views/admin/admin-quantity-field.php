<?php
/**
 * Form View: Quantity Field
 *
 * @package User_Registration_Payments
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ur-input-type-quantity-field ur-admin-template">
	<div class="ur-label">
		<label><?php echo esc_html( $this->get_general_setting_data( 'label' ) ); ?></label>
	</div>

	<div class="ur-field" data-field-key="quantity_field">
		<input type="number" name="" id="" value='0' disabled>
	</div>

	<?php
	UR_Form_Field_Quantity_Field::get_instance()->get_setting();
	?>

	<div style="clear:both"></div>
</div>
