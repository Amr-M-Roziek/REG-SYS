/* global  user_registration_params */
/**
 * Script to handle total field in frontend.
 *
 * @since 1.2.0
 */
jQuery(function ($) {
	var ursL10n = user_registration_params.ursL10n;
	$(".ur-frontend-form").each(function () {
		var $form = $(this);
		var totalAmountField = {
			init: function () {
				$(document).ready(totalAmountField.ready);
				totalAmountField.bindUIActions();
			},
			/*
			 * load total amount on form load
			 */
			ready: function () {
				totalAmountField.loadTotal();
			},
			/*
			 * Update Total field with latest calculation.
			 */
			loadTotal: function () {
				$(".ur-frontend-form")
					.find(".ur-total-amount")
					.each(function () {
						totalAmountField.calculateTotalAmount(this);
					});
			},
			/*
			 * Payments: Update Total field(s) when latest calculation.
			 */
			bindUIActions: function () {
				var $paymentSingles = $form.find(
					".field-single_item:not([style*='display: none'])"
				);
				$(document).on("change input", $paymentSingles, function () {
					totalAmountField.calculateTotalAmount(this, true);
				});
				// Restrict user input payment fields
				$(document).on(
					"input keypress",
					".ur-payment-price",
					function (evt) {
						var $this = $(this),
							amount = $this.val();
						$this.val(amount.toString().replace(/[^0-9.]/g, ""));
					}
				);


				// Disallow Quantity field value to be empty which causes NaN in Total field.
				$(document).on('input keypress change', 'input.ur-quantity', function () {
					if (!$(this).val()) {
						$(this).val(0);
					}
				});
				$(document).on('click', '.ur-apply-coupon-btn', function () {
					var $this = $(this),
						coupon = $('.ur_coupon_field').val(),
						button_text = $this.text(),
						form_id = $('.ur-frontend-form').find('form').data('form-id'),
						error_div = $('#coupon-error');
					var request = {
						action: 'user_registration_pro_get_coupon_detail',
						security: user_registration_pro_frontend_data.user_data_nonce,
						coupon: coupon,
						form_id: form_id
					};
					total_amount = $('input[id^=total_field_]').val();
					if(total_amount <= 0) {
						error_div.hide();
						error_div.text(ursL10n.i18n_total_field_value_zero).css({'display': 'block'});
						return;
					}

					$.ajax({
						url: user_registration_pro_frontend_data.ajax_url,
						type: "POST",
						data: request,
						beforeSend: function () {
							$this.text(
								user_registration_pro_frontend_data.applying_coupon_text
							);
						},
						success: function (response) {
							if (response.success) {
								error_div.hide();
								$this.text(button_text);
								$this.hide();
								totalAmountField.calculate_coupon_details(response);
							} else {
								$('.user-registration-error').hide();
								error_div.hide();
								error_div.text(response.data).css({'display': 'block'});
							}

						},
						error: function (data) {
							console.log(data);

						},
						complete: function () {
							$this.text(button_text);
						}
					});
				});
				$(document).on('click', '.clear-coupon', function () {
					var coupon_input = $('.ur_coupon_field');
					$('.coupon-message').text('');
					$('#coupon-error').hide();
					coupon_input.val('');
					coupon_input.removeAttr('data-coupon-discount').removeData('coupon-discount');
					coupon_input.removeAttr('data-coupon-discount-type').removeData('coupon-discount-type');
					coupon_input.removeAttr('data-coupon-discount-target-field').removeData('coupon-discount-target-field');
					$('.ur-apply-coupon-btn').show();
					totalAmountField.calculateTotalAmount();

				});

			},
			calculate_coupon_total: function (coupon_discount_type, price, coupon_discount) {
				if (coupon_discount_type === 'percent') {
					return price - (price * (coupon_discount / 100));
				} else {
					return price - coupon_discount;
				}
			},
			/*
			 * calculate total amount
			 */
			calculateTotalAmount: function () {
				var total = 0,
					coupon_discount_type = '',
					coupon_discount = '',
					coupon_target_field = '';
				// calculate multiple choice payment amount
				var $couponInput = $form
					.find(
						'.field-coupon:not([style*="display: none"])'
					)
					.find('input');
				$.map($couponInput, function (coupon) {
					coupon_discount_type = $(coupon).data('coupon-discount-type');
					coupon_discount = $(coupon).data('coupon-discount');
					var target_field = $(coupon).data('coupon-discount-target-field'),
						reg = new RegExp('single_item');
					if (target_field) {
						coupon_target_field = (target_field.match(reg)) ? 'single_item' : 'total_field';
					}

				});

				var $paymentSingles = $form.find(
					".field-single_item:not([style*='display: none'])"
				);
				// calculate single item amount
				var paymentSingles = $.map(
					$paymentSingles,
					function (paymentSingle) {
						var price =
							"" !== $(paymentSingle).find("input").val()
								? $(paymentSingle).find("input").val()
								: 0;
						var fieldId = $(paymentSingle).find("input").attr("data-id");
						if (coupon_target_field === 'single_item') {
							price = totalAmountField.calculate_coupon_total(coupon_discount_type, price, coupon_discount);
						}
						total = parseFloat(total) + parseFloat(price) * totalAmountField.getQuantity(fieldId);
					}
				);
				// calculate range amount as payment slider
				var isPaymentSlider = $form
					.find(".field-range:not([style*='display: none'])")
					.find(".ur-currency-sign").length;
				if (isPaymentSlider > 0) {
					$rangeSlider = $form
						.find(".field-range:not([style*='display: none'])")
						.find(".ur-currency-sign")
						.parent()
						.parent();
					var paymentSlider = $.map($rangeSlider, function (range) {
						var price =
							"" !== $(range).find("input[type='number']").val()
								? $(range).find("input[type='number']").val()
								: 0;
						var fieldId = $(range).find("input[type='number']").attr('name');
						total = parseFloat(total) + parseFloat(price) * totalAmountField.getQuantity(fieldId);
					});
				}


				var $paymentMultipleChoice = $form
					.find(
						".field-multiple_choice:not([style*='display: none'])"
					)
					.find("input");
				var paymentMultipleChoice = $.map(
					$paymentMultipleChoice,
					function (paymentchoice) {
						if ("checkbox" === $(paymentchoice).attr("type")) {
							var price = $(paymentchoice).prop("checked")
								? $(paymentchoice).val()
								: 0;
						}

						var fieldId = $(paymentchoice).attr("data-id");

						total = parseFloat(total) + parseFloat(price) * totalAmountField.getQuantity(fieldId);
					}
				);
				// calculate subscription plan payment amount
				var $paymentSubscriptionPlan = $form
					.find(
						".field-subscription_plan:not([style*='display: none'])"
					)
					.find("input");

				var paymentSubscriptionPlan = $.map(
					$paymentSubscriptionPlan,
					function (paymentchoice) {
						if ("radio" === $(paymentchoice).attr("type")) {
							var price = $(paymentchoice).prop("checked")
								? $(paymentchoice).val()
								: 0;
						}

						var fieldId = $(paymentchoice).attr("data-id");

						total = parseFloat(total) + parseFloat(price) * totalAmountField.getQuantity(fieldId);
					}
				);
				if(coupon_discount != undefined) {
					total = (total) ? (this.calculate_coupon_total(coupon_discount_type, total, coupon_discount) <= 0 ? 0 : this.calculate_coupon_total(coupon_discount_type, total, coupon_discount)) : 0;
				}
				// render total calculated amount in total field
				$form.find(".ur-total-amount").each(function () {
					if (
						"hidden" === $(this).attr("type") ||
						"text" === $(this).attr("type")
					) {
						$(this).val(total);
					} else {
						$(this).text(total);
					}
				});

				return total;
			},

			/**
			 * Get quantity for field.
			 *
			 * @param {string} id
			 */
			getQuantity: function (id) {
				var quantity = 1;
				var quantityField = $("input[data-target=" + id);
				if (quantityField.length != 0) {
					quantity = quantityField.first().val();
				}
				return parseInt(quantity);
			},

			calculate_coupon_details: function (response) {
				var coupon_details = JSON.parse(response.data.coupon_details),
					coupon_input = $('.ur_coupon_field'),
					error_div = $('#coupon-error');
				coupon_input.attr('data-coupon-discount', coupon_details.coupon_discount);
				coupon_input.attr('data-coupon-discount-type', coupon_details.coupon_discount_type);
				coupon_input.attr('data-coupon-discount-target-field', coupon_details.target_field);
				var calculated_total = totalAmountField.calculateTotalAmount();

				if((coupon_details.coupon_discount_type === 'fixed') && calculated_total <= 0 ) {
					coupon_input.attr('data-coupon-discount', 0.00);
					coupon_input.attr('data-coupon-discount-type', 'fixed');
					coupon_input.attr('data-coupon-discount-target-field', coupon_details.target_field);
					totalAmountField.calculateTotalAmount();
					$('.user-registration-error').hide();
					error_div.hide();
					error_div.text(ursL10n.i18n_discount_total_zero).css({'display': 'block'});
					$('.ur-apply-coupon-btn').show();
					return;
				}
				error_div.hide();
				var reg = new RegExp('single_item'),
					discount = ((coupon_details.coupon_discount_type === 'fixed') ? user_registration_pro_frontend_data.currency_symbol : '') + coupon_details.coupon_discount + ((coupon_details.coupon_discount_type === 'percent') ? '%' : ''),
					message = coupon_details.target_field.match(reg) ? user_registration_pro_frontend_data.single_item_discount : user_registration_pro_frontend_data.total_item_discount;
					$('#coupon-success').text(message + discount)


			}
		};

		totalAmountField.init(jQuery);
	});

	$(document).ready(function () {

		$(document).on("user_registration_frontend_multiple_choice_data_filter", function (event, field_value, field) {
			var checkedValues = [];

			field.each(function () {
				if ($(this).is(":checked")) {
					var label = $(this)
						.siblings("label")
						.text();
					var value = $(this).val();
					checkedValues.push(
						label + ":" + value
					);
				}
			});
			field_value = checkedValues;
			field.closest(".field-multiple_choice").data("payment-value", field_value);
		});
	});
});
