
<ul class="" id='user-registration-sms-verification-message-container'>
	<li></li>
</ul>

<div class="ur-frontend-form" id="ur-frontend-form">
	<form method="post" class="">
		<div class="ur-form-row">
			<div class="ur-form-grid">
			<h2 id="user-registration-otp-box-title">
			<?php echo esc_html( apply_filters( 'user_registration_sms_otp_verification_text', __( 'OTP Verification', 'user-registration' ) ) ); ?></h2>
				<p class="user-registration-form-row user-registration-form-row--first form-row form-row-first">
					<label for="user_otp">
					<?php echo esc_html( apply_filters( 'user_registration_sms_enter_otp_label_text', __( 'Enter Your OTP Below:', 'user-registration' ) ) ); ?></label>
					<input class="user-registration-Input user-registration-Input--text input-text" type="text" name="user_otp" id="user-registration-sms-verification-otp-field" placeholder="<?php echo esc_html( apply_filters( 'user_registration_sms_enter_otp_placeholder_text', __( 'Enter OTP', 'user-registration' ) ) ); ?>">
					<input type="hidden" name="redirect_on_login" id="user-registration-sms-verification-validate-otp-redirect" value="<?php echo isset( $wp->query_vars['redirect-on-login'] ) ? $wp->query_vars['redirect-on-login'] : '' ;?>">
				</p>

				<div class="clear"></div>

				<p class="user-registration-form-row form-row">
					<div id="user-registration-footer">
						<button id="user-registration-sms-verification-otp-resend-btn" class="user-registration-Button button button-secondary">
						<?php echo esc_html( apply_filters( 'user_registration_sms_resend_code_button_text', __( 'Resend Code', 'user-registration' ) ) ); ?><span id="user-registration-sms-verification-resend-limit"></span></button>
						<div id="user-registration-sms-verification-submit-container">
							<div id="user-registration-sms-verification-spinner" class=""></div>
							<input type="submit" class="user-registration-Button button button-primary" id="user-registration-sms-verification-otp-submit-btn" value="<?php echo esc_html( apply_filters( 'user_registration_sms_verify_otp_button_text', __( 'Verify OTP', 'user-registration' ) ) ); ?>">
						</div>
					</div>
				</p>
		</div>
	</form>
</div>
