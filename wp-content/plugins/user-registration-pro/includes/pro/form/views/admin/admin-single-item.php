<?php
/**
 * Form View: Sinlge Item
 *
 * @package User_Registration_Payments
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ur-input-type-single-item ur-admin-template">
	<div class="ur-label">
		<label><?php echo esc_html( $this->get_general_setting_data( 'label' ) ); ?></label>
	</div>

	<?php
	$item_type = $this->get_advance_setting_data( 'item_type' );
		$attr  = ( 'pre_defined' === $item_type ) ? 'disabled' : '';
	?>

	<div class="ur-field" data-field-key="single_item">
		<?php
		$form_id = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;

			$currency   = get_option( 'user_registration_payment_currency', 'USD' );
			$currencies = ur_payment_integration_get_currencies();

			echo esc_html( $currency . ' ' . $currencies[ $currency ]['symbol'] );

		?>

		<input id="ur-input-type-single-item" type="text" placeholder="<?php echo esc_attr( $this->get_advance_setting_data( 'default_value' ) ); ?>" value="<?php echo esc_attr( $this->get_advance_setting_data( 'default_value' ) ); ?>" <?php echo esc_attr( $attr ); ?> disabled/>

		<?php
		if ( 'hidden' === $item_type ) {
			echo '<br/>' . esc_html__( 'Note: Item type is set to hidden and will not be visible when viewing the form.', 'user-registration' );
		}
		?>
	</div>

	<?php
	UR_Form_Field_Single_Item::get_instance()->get_setting();
	?>

	<div style="clear:both"></div>
</div>
