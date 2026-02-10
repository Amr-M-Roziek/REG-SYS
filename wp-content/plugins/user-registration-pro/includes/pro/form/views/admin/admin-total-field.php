<?php
/**
 * Form View: Total Field
 *
 * @package User_Registration_Payments
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ur-input-type-total-field ur-admin-template">
	<div class="ur-label">
		<label><?php echo esc_html( $this->get_general_setting_data( 'label' ) ); ?></label>
	</div>

	<div class="ur-field" data-field-key="total_field">
		<?php
		$form_id = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;

			$currency   = get_option( 'user_registration_payment_currency', 'USD' );
			$currencies = ur_payment_integration_get_currencies();
			$amount = '0.00';
			echo esc_html( $currency . ' ' . $currencies[ $currency ]['symbol'] . ' ' . $amount );
		?>

	</div>

	<?php
	UR_Form_Field_Total_Field::get_instance()->get_setting();
	?>

	<div style="clear:both"></div>
</div>
