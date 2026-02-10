/* global  user_registration_params */
/**
 * Script to handle myaccount page in frontend.
 *
 * @since 1.2.1
 */
(function ($) {
	var ursL10n = user_registration_params.ursL10n;

	var UR_Payment_MyAccount = {
		init: function () {
			$(".ur-payments-details").show();
			$(".ur-change-payment-container").hide();
		},
	};
	$(document).ready(function () {
		UR_Payment_MyAccount.init();
	});
})(jQuery);
