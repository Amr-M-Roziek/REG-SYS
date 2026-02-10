/**
 * UserRegistrationProPopup JS
 * global user_registration_pro_frontend_data
 * global user_registration_params
 */

"use strict";

(function ($) {
	// Hide the menu item for logged in users.
	$(document).ready(function () {
		if (
			user_registration_pro_frontend_data.has_create_user_capability &&
			user_registration_pro_frontend_data.is_user_logged_in
		) {
			$(document).find(".user-registration-modal-link").show();
		} else if (
			!user_registration_pro_frontend_data.has_create_user_capability &&
			user_registration_pro_frontend_data.is_user_logged_in
		) {
			$(document).find(".user-registration-modal-link").hide();
		}
	});

	// Change Cursor to pointer when modal link is hovered.
	$(document).on("hover", ".user-registration-modal-link", function (e) {
		$(".user-registration-modal-link").css("cursor", "pointer");
	});

	// When user clicks on the menu item open the popup.
	$(document)
		.find(".user-registration-modal-link")
		.each(function () {
			$(this).on("click", function (e) {
				e.preventDefault();
				var $this = $(this);
				var classes = $.map($(this)[0].classList, function (cls, i) {
					if (cls.indexOf("user-registration-modal-link-") === 0) {
						var popup_id = cls.replace(
							"user-registration-modal-link-",
							""
						);

						var lastItemIndex =
							$(".user-registration-modal-" + popup_id).length -
							1;
						$(".user-registration-modal-" + popup_id).each(
							function (index) {
								var isLastElement = index == lastItemIndex;

								if (isLastElement) {
									$(this)
										.find(".user-registration-error")
										.remove();

									$(this).show();
									return false;
								} else {
									$(this).remove();
									return true;
								}
							}
						);

						// Add user-registration-modal-open class to body when popup is rendered on menu click.
						$(document.body).addClass(
							"user-registration-modal-open"
						);
					}
				});
			});
		});

	// Catch submit event and store values in localStorage so that error can be shown in that specific login form.
	$(".ur-frontend-form.login").on("submit", function () {
		if ($(this).closest(".user-registration-modal").length) {
			var classes = $.map(
				$(this).closest(".user-registration-modal")[0].classList,
				function (cls, i) {
					if (cls.indexOf("user-registration-modal-") === 0) {
						var popup_id = cls.replace(
							"user-registration-modal-",
							""
						);

						var ur_popup_details = {};
						ur_popup_details["popup_id"] = popup_id;

						if (
							$(".user-registration-modal-" + popup_id).closest(
								".entry-content"
							).length
						) {
							ur_popup_details["inner_popup"] = true;
						} else {
							ur_popup_details["inner_popup"] = false;
						}

						localStorage.setItem(
							"ur_pro_popup",
							JSON.stringify(ur_popup_details)
						);
					}
				}
			);
		} else {
			localStorage.removeItem("ur_pro_popup");
		}
	});

	// Add user-registration-modal-open class to body when popup is rendered from shortcode.
	$(".user-registration-modal").ready(function () {
		if (
			$(".entry-content").find(".user-registration-modal").length &&
			0 ===
				$(".entry-content").find(".user-registration-popup-button")
					.length
		) {
			$(document.body).addClass("user-registration-modal-open");
		}

		var popup_details = JSON.parse(localStorage.getItem("ur_pro_popup"));

		if (popup_details && popup_details.popup_id) {
			var popup_id = popup_details.popup_id,
				popup_div = $(".user-registration-modal-" + popup_id);

			if ($(".entry-content").find(".ur-frontend-form.login").length) {
				$(".entry-content")
					.find(".ur-frontend-form.login")
					.each(function () {
						if (
							$(this)
								.closest("body")
								.find(".entry-content .user-registration-error")
								.length
						) {
							if (true === popup_details.inner_popup) {
								$(this)
									.closest("body")
									.find(".entry-content")
									.find(".user-registration-error")
									.prependTo(
										popup_div.find(".user-registration")
									);
							} else {
								$(this)
									.closest(".user-registration")
									.find(".user-registration-error")
									.prependTo(
										popup_div.find(".user-registration")
									);
							}
							$(this)
								.closest("body")
								.find(".user-registration-modal")
								.hide();
							popup_div.show();
						}
					});
			} else {
				$(".user-registration-modal")
					.find(".ur-frontend-form.login")
					.each(function () {
						if (
							$(this).siblings(".user-registration-error").length
						) {
							$(this)
								.closest(".user-registration")
								.find(".user-registration-error")
								.prependTo(
									popup_div.find(".user-registration")
								);
						}
						$(this)
							.closest("body")
							.find(".user-registration-modal")
							.hide();
						popup_div.show();
					});
			}

			localStorage.removeItem("ur_pro_popup");

			// Add user-registration-modal-open class to body when popup is rendered on menu click.
			$(document.body).addClass("user-registration-modal-open");
		}

		/**
		 * Compatibility for rendering recaptcha on popup.
		 *
		 * @since 1.0.7
		 */
		$(".user-registration-modal")
			.find(".ur-frontend-form.login")
			.each(function () {
				if ($(this).find("#ur-recaptcha-node").length > 0) {
					var popup_id = $(this)
						.closest(".user-registration-modal")
						.attr("class")
						.split(" ")[1]
						.replace("user-registration-modal-", "");

					$(this)
						.find("#node_recaptcha_login")
						.attr("id", "node_recaptcha_login_popup_" + popup_id);
				}
			});
	});

	// When the user clicks on <span> (x), close the modal
	$(document).on(
		"click",
		".user-registration-modal__close-icon, .user-registration-modal__backdrop",
		function () {
			$(this)
				.closest(".user-registration-modal")
				.css({ display: "none", opacity: "1" });

			// Remove user-registration-modal-open class from body when popup is closed.
			$(document.body).removeClass("user-registration-modal-open");
		}
	);

	/** Triggers when user click on delete account menu for deleting their own account.
	 *
	 * @since v1.0.4
	 */
	$(document).on(
		"click",
		".user-registration-MyAccount-navigation-link--delete-account",
		function () {
			// Code for Delete Account Feature
			var delete_account_option =
				user_registration_pro_frontend_data.delete_account_option;
			// var icon = '<i class="dashicons dashicons-trash"></i>';
			var title =
				'<span class="user-registration-swal2-modal__title">' +
				user_registration_pro_frontend_data.delete_account_popup_title;

			swal.fire({
				title: title,
				html: user_registration_pro_frontend_data.delete_account_popup_html,
				confirmButtonText:
					user_registration_pro_frontend_data.delete_account_button_text,
				confirmButtonColor: "#FF4149",
				showConfirmButton: true,
				showCancelButton: true,
				cancelButtonText:
					user_registration_pro_frontend_data.cancel_button_text,
				customClass: {
					container: "user-registration-swal2-container"
				},
				focusConfirm: false,
				showLoaderOnConfirm: true,
				preConfirm: function () {
					return new Promise(function (resolve) {
						var data = "";
						if ("prompt_password" === delete_account_option) {
							var password =
								Swal.getPopup().querySelector(
									"#password"
								).value;

							if (!password) {
								Swal.showValidationMessage(
									user_registration_pro_frontend_data.please_enter_password
								);

								Swal.hideLoading();

								$(".swal2-actions")
									.find("button")
									.prop("disabled", false);
							} else {
								data = {
									action: "user_registration_pro_delete_account",
									password: password,
									security: user_registration_pro_frontend_data.user_data_nonce
								};
							}
						} else {
							data = {
								action: "user_registration_pro_delete_account",
								security: user_registration_pro_frontend_data.user_data_nonce
							};
						}
						if ("" !== data) {
							$.ajax({
								url: user_registration_pro_frontend_data.ajax_url,
								method: "POST",
								data: data
							}).done(function (response) {
								if (response.success) {
									Swal.fire({
										icon: "success",
										title: user_registration_pro_frontend_data.account_deleted_message,
										customClass:
											"user-registration-swal2-modal user-registration-swal2-modal--center",
										showConfirmButton: false,
										timer: 1000
									}).then(function (result) {
										window.location.reload();
									});
								} else {
									Swal.showValidationMessage(
										response.data.message
									);

									Swal.hideLoading();

									$(".swal2-actions")
										.find("button")
										.prop("disabled", false);
								}
							});
						}
					});
				}
			});
			return false;
		}
	);

	/** Triggers when user tries to reset the form.
	 *
	 * @since v1.0.7
	 */
	$(document).on("click", ".ur-reset-button", function (e) {
		e.preventDefault();
		Swal.fire({
			html: user_registration_pro_frontend_data.clear_button_text,
			icon: "warning",
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Yes",
			allowOutsideClick: false,
			showCancelButton: true,
			customClass: {
				container: "user-registration-swal2-container"
			}
		}).then(function (result) {
			if (result.value === true) {
				$(document).trigger("user_registration_frontend_reset_button");
				$(".ur-field-item.field-select2 select")
					.val(null)
					.trigger("change");
				$(".ur-field-item.field-multi_select2 select")
					.val(null)
					.trigger("change");
				$(".ur-frontend-form")
					.find("form.register")
					.validate()
					.resetForm();
				$(".user-registration-error").remove();
				$(".ur-frontend-form").find("form.register").trigger("reset");
			}
		});
	});

	/** Triggers when user tries to forcelogout.
	 *
	 * @since v1.0.0
	 */
	$(document).on("click", ".user-registartion-force-logout", function (e) {
		e.preventDefault();
		var user_id = $(".user-registartion-force-logout").data("user-id");
		var user_email = $(".user-registartion-force-logout").data("email");
		$.ajax({
			type: "POST",
			url: user_registration_pro_frontend_data.ajax_url,
			data: {
				action: "user_registration_pro_send_email_logout",
				user_email: user_email,
				user_id: user_id
			},
			success: function (response) {
				$("#user-registration")
					.find(".user-registration-error")
					.remove();
				$("#user-registration").prepend(
					'<ul class="user-registration-message">' +
						user_registration_pro_frontend_data.email_sent_successfully_message +
						"</ul>"
				);
			}
		});
	});

	$(document).ready(function () {
		if (
			typeof $.fn.mailcheck === "undefined" ||
			!user_registration_pro_frontend_data.mailcheck_enabled
		) {
			return;
		}
		// Setup default domains for Mailcheck.
		if (user_registration_pro_frontend_data.mailcheck_domains.length > 0) {
			Mailcheck.defaultDomains = Mailcheck.defaultDomains.concat(
				user_registration_pro_frontend_data.mailcheck_domains
			);
		}

		// Setup default top level domains for Mailcheck.
		if (
			user_registration_pro_frontend_data.mailcheck_toplevel_domains
				.length > 0
		) {
			Mailcheck.defaultTopLevelDomains =
				Mailcheck.defaultTopLevelDomains.concat(
					user_registration_pro_frontend_data.mailcheck_toplevel_domains
				);
		}

		// mailcheck suggestion
		$(document).on("blur", ".ur-field-item .input-email", function () {
			var $el = $(this),
				id = $el.attr("id");

			$el.mailcheck({
				suggested: function (el, suggestion) {
					$("#" + id + "_suggestion").remove();
					var suggestion_msg =
						user_registration_pro_frontend_data.message_email_suggestion_fields.replace(
							"{suggestion}",
							'<a href="#" class="mailcheck-suggestion" data-id="' +
								id +
								'" title="' +
								user_registration_pro_frontend_data.message_email_suggestion_title +
								'">' +
								suggestion.full +
								"</a>"
						);
					$(el).after(
						'<label class="user-registration-error mailcheck-error" id="' +
							id +
							'_suggestion">' +
							suggestion_msg +
							"</label>"
					);
				},
				empty: function () {
					$("#" + id + "_suggestion").remove();
				}
			});
			// Apply Mailcheck suggestion.
			$(document).on(
				"click",
				".ur-field-item .mailcheck-suggestion",
				function (e) {
					var $el = $(this),
						id = $el.attr("data-id");
					e.preventDefault();
					$("#" + id).val($el.text());
					$el.parent().remove();
				}
			);
		});
	});

	// Add padding when display icon option enabled
	$(document).ready(function () {
		$(".input-wrapper").each(function () {
			if ($(this).children().length == 2) {
				var firstEl = $(this).children(":first");
				if (firstEl.hasClass("password-input-group")) {
					firstEl
						.children(":first")
						.addClass("ur-pro-input-icon-padding");
				} else {
					firstEl.addClass("ur-pro-input-icon-padding");
				}
			}
		});
	});

	$(document).on("click","#billing_state, #shipping_state", function(){
		$(this).addClass("ur-pro-input-icon-padding");
	});

	$(window).on("user_registration_repeater_modified", function () {
		render_tooltips();
	});
	$(document).ready(function () {
		enable_keyboard_friendly_form();
		render_tooltips();
		restrict_copy_paste();
		load_custom_captcha();
		validate_custom_captcha();
		slot_booking_init();

		$(document).on(
			"user_registration_frontend_before_form_submit",
			function (event, data, pointer, $error_message) {
				event.preventDefault();
				var n1,
					n2,
					cal,
					captchamathInput,
					captchaqaInput,
					captchaimageInput,
					captchamathData = [],
					captchaimageData = [],
					captchaqaData = [];
				var $this = pointer;
				var $captchaInputs = $this.find(".input-captcha");
				var $qa = $this.find('input:hidden[class="qa"]');
				var $icap = $this.find(
					'input:hidden[class="captcha_correct_icon"]'
				);

				$captchaInputs.each(function () {
					n1 = $(this).attr("data-n1");
					n2 = $(this).attr("data-n2");
					cal = $(this).attr("data-cal");

					captchamathInput = {
						n1: n1,
						n2: n2,
						cal: cal
					};
					if (
						$(this)
							.closest(".input-wrapper")
							.hasClass("ur-captcha-math")
					) {
						var id,
							captchamathDataObject = {};
						id = $(this).data("id");
						captchamathDataObject[id] = captchamathInput;
						captchamathData.push(captchamathDataObject);
					}
				});

				$qa.each(function () {
					captchaqaInput = {
						qa: $(this).val()
					};
					if (
						$(this)
							.closest(".input-wrapper")
							.hasClass("ur-captcha-qa")
					) {
						var qid,
							captchaqaDataObject = {};
						qid = $(this).siblings(".input-captcha").data("id");
						captchaqaDataObject[qid] = captchaqaInput;
						captchaqaData.push(captchaqaDataObject);
					}
				});

				$icap.each(function () {
					captchaimageInput = {
						i_captcha: $(this).val(),
						i_captcha_group: $(this)
							.siblings(".ur-icon-group")
							.attr("data-group")
					};
					if (
						$(this)
							.closest(".input-wrapper")
							.hasClass("ur-captcha-image")
					) {
						var icap_id,
							captchaimageDataObject = {};
						icap_id = $(this)
							.siblings(".ur-icon-group")
							.find(".input-captcha-icon-radio")
							.data("id");
						captchaimageDataObject[icap_id] = captchaimageInput;
						captchaimageData.push(captchaimageDataObject);
					}
				});
				if (captchamathData.length > 0) {
					data["ur_captcha_math"] = captchamathData;
				}
				if (captchaqaData.length > 0) {
					data["ur_captcha_qa"] = captchaqaData;
				}
				if (captchaimageData.length > 0) {
					data["ur_captcha_image"] = captchaimageData;
				}
			}
		);
	});

	// Keyboard Friendly Form
	function enable_keyboard_friendly_form() {
		if (
			user_registration_pro_frontend_data.keyboard_friendly_form_enabled
		) {
			var ur_form = $("form.register");
			var ur_fields = ur_form.find(".ur-frontend-field");
			ur_fields.first().trigger("focus");

			$("body").on("keydown", function (e) {
				if (e.ctrlKey || e.metaKey) {
					if (13 === e.which) {
						e.preventDefault();
						ur_form.submit();
					}
				}

				if (ur_fields.last().is(":focus")) {
					if (9 === e.which) {
						e.preventDefault();
						$("form.register")
							.find(".ur-submit-button")
							.first()
							.trigger("focus");
					}
				} else if (
					$("form.register")
						.find(".ur-submit-button.button")
						.is(":focus")
				) {
					if (9 === e.which) {
						e.preventDefault();
						ur_fields.first().trigger("focus");
					}
				}
			});
		}
	}

	// Render Tooltips.
	function render_tooltips() {
		var args = {
			theme: "tooltipster-borderless",
			maxWidth: 200,
			multiple: true,
			interactive: true,
			position: "bottom",
			contentAsHTML: true,
			functionInit: function (instance, helper) {
				var $origin = jQuery(helper.origin),
					dataTip = $origin.attr("data-tip");

				if (dataTip) {
					instance.content(dataTip);
				}
			}
		};

		$(".ur-portal-tooltip").tooltipster(args);
	}

	// Restrict copy/paste on confirm email and confirm password fields.
	function restrict_copy_paste() {
		var fields =
			user_registration_pro_frontend_data.restrict_copy_paste_fields;
		if (Array.isArray(fields) && fields.length) {
			$(fields).each(function (i, field) {
				$("#" + field).on("cut copy paste", function (e) {
					e.preventDefault();
				});
			});
		}
		if ($("#password_2").hasClass("restrict-copy-paste")) {
			$("#password_2").on("cut copy paste", function (e) {
				e.preventDefault();
			});
		}
	}

	// load custom captcha
	function load_custom_captcha() {
		$(".ur-frontend-form")
			.find(".ur-captcha-equation")
			.each(function () {
				var n1_value, n2_value, cal_value;
				var $captcha = $(this).closest(".input-wrapper"),
					captcha_param =
						user_registration_pro_frontend_data.captcha_equation_param;

				do {
					n1_value = randomNumber(
						captcha_param.min,
						captcha_param.max
					);
					n2_value = randomNumber(
						captcha_param.min,
						captcha_param.max
					);
					cal_value =
						captcha_param.cal[
							Math.floor(Math.random() * captcha_param.cal.length)
						];
				} while (cal_value === "-" && n1_value < n2_value);

				$captcha.find("span.n1").text(n1_value);
				$captcha.find("input.n1").val(n1_value);
				$captcha.find("span.n2").text(n2_value);
				$captcha.find("input.n2").val(n2_value);
				$captcha.find("input.cal").val(cal_value);

				if ($("html").attr("dir") === "rtl") {
					$captcha.find("span.cal").text(cal_value);
				} else {
					$captcha.find("span.cal").text(cal_value);
				}

				$captcha.find("input.input-captcha").attr({
					"data-cal": cal_value,
					"data-n1": n1_value,
					"data-n2": n2_value
				});
			});

		// generate random number.
		function randomNumber(min, max) {
			return (
				Math.floor(Math.random() * (Number(max) - Number(min) + 1)) +
				Number(min)
			);
		}
	}

	// validate custom captcha.
	function validate_custom_captcha() {
		$("form.register").each(function () {
			$(document).on(
				"keyup",
				".ur-field-item .input-captcha",
				function () {
					var result,
						value,
						$el = $(this);
					var $format = $el.closest(".input-wrapper");
					if ($format.hasClass("ur-captcha-math")) {
						var n1 = Number($el.attr("data-n1")),
							n2 = Number($el.attr("data-n2")),
							cal = $el.attr("data-cal");

						value = Number($el.val());
						result = false;
						switch (cal) {
							case "-":
								result = n1 - n2;
								break;
							case "+":
								result = n1 + n2;
								break;
							case "*":
								result = n1 * n2;
								break;
						}
					} else {
						value = $el.val().toString().toLowerCase().trim();
						result = $el
							.attr("data-a")
							.toString()
							.toLowerCase()
							.trim();
					}

					if (value == result) {
						$el.closest(".ur-field-item")
							.find(".user-registration-error")
							.remove();
						$el.siblings(".captcha-error").remove();
						return;
					} else {
						$el.closest(".ur-field-item")
							.find(".user-registration-error")
							.remove();
						$el.siblings(".captcha-error").remove();
						$($el).after(
							'<label class="user-registration-error captcha-error">' +
								user_registration_pro_frontend_data.captcha_error_message +
								"</label>"
						);
					}
				}
			);
		});

		$(document).on(
			"ur_handle_field_error_messages",
			function (event, $this, field_name) {
				var wrapper = $this.find(
					'.field-captcha[data-field-id="' + field_name + '"]'
				);

				if (wrapper.find(".ur-captcha-image").length > 0) {
					wrapper.find(".user-registration-error").remove();
					wrapper.siblings(".captcha-error").remove();
					wrapper
						.find(".ur-captcha-image")
						.append(
							'<label class="user-registration-error captcha-error">' +
								user_registration_pro_frontend_data.captcha_error_message +
								"</label>"
						);
				}
			}
		);

		$(document).on(
			"user_registration_frontend_after_ajax_complete",
			function (event, ajax_response, type, $this) {
				if ($(".captcha-error").length > 0) {
					event.preventDefault();
				} else {
					load_custom_captcha();
				}
			}
		);
	}

	//cancel membership button
	$(document).on("click", ".cancel-membership-button", function () {
		var $this = $(this),
			error_div = $("#membership-error-div"),
			button_text = $this.text();

		Swal.fire({
			icon: "warning",
			title: user_registration_pro_frontend_data.cancel_membership_text,
			text: user_registration_pro_frontend_data.cancel_membership_subtitle,
			customClass:
				"user-registration-swal2-modal user-registration-swal2-modal--center",
			showConfirmButton: true,
			showCancelButton: true,
		}).then(function (result) {
			if (result.isConfirmed) {
				$.ajax({
					url: user_registration_pro_frontend_data.ajax_url,
					type: "POST",
					data: {
						action: "user_registration_pro_cancel_subscription",
						security: user_registration_pro_frontend_data.user_data_nonce,
						subscription_id: $this.data("id")
					},
					beforeSend: function () {
						$this.text(
							user_registration_pro_frontend_data.privacy_sending_text
						);
					},
					success: function (response) {
						if (response.success) {
							if (error_div.hasClass("btn-error")) {
								error_div.removeClass("btn-error");
								error_div.addClass("btn-success");
							}
							error_div.text(response.data.message);
							error_div.show();
							location.reload();
						} else {
							if (error_div.hasClass("btn-success")) {
								error_div.removeClass("btn-success");
								error_div.addClass("btn-error");
							}
							error_div.text(response.data.message);
							error_div.show();
						}
					},
					complete: function () {
						$this.text(button_text);
					}
				});
			}
		});
	});

	$(document).on("click", ".ur-request-button", function (e) {
		e.preventDefault();
		var request_action = $(this).data("action"),
			$this = $(this);
		var password = $("#" + request_action).val();
		$(".ur-field-area-response." + request_action).hide();

		if ($("#" + request_action).length && password === "") {
			$(".ur-field-error." + request_action).show();
		} else {
			$(".ur-field-error." + request_action).hide();
			var request = {
				action: "user_registration_pro_request_user_data",
				request_action: request_action,
				security: user_registration_pro_frontend_data.user_data_nonce
			};

			if ($("#" + request_action).length) {
				request.password = password;
			}
			var target_tag = e.target;
			var button_text = $this.text();
			$.ajax({
				url: user_registration_pro_frontend_data.ajax_url,
				type: "POST",
				data: request,
				beforeSend: function () {
					$this.text(
						user_registration_pro_frontend_data.privacy_sending_text
					);
				},
				success: function (response) {
					if (response.data.success === 1) {
						if (request_action === "ur-export-data") {
							$(document)
								.find('label[name="ur-export-data"]')
								.hide();
							$(document).find("#ur-export-data").hide();
							$(document).find(".ur-export-data-button").hide();
						}
						if (request_action === "ur-erase-data") {
							$(document)
								.find('label[name="ur-erase-data"]')
								.hide();
							$(document).find("#ur-erase-data").hide();
							$(document).find(".ur-erase-data-button").hide();
						}
					}
					$(document)
						.find(".ur-field-area-response." + request_action)
						.html(response.data.answer)
						.show();
					$this.text(button_text);
				},
				error: function (data) {}
			});
		}
	});
	$(document).on("click", "#ur-new-erase-request", function (e) {
		e.preventDefault();
		$("#ur-erase-personal-data-request-input").show();
		$(document).find(".ur-erase-personal-data").hide();
	});
	$(document).on("click", "#ur-new-download-request", function (e) {
		e.preventDefault();
		$("#ur-download-personal-data-request-input").show();
		$(document).find(".ur-download-personal-data").hide();
	});

	//Slot booking init.
	var slot_booking_init = function () {
		/**
		 * Slot booking for date field.
		 *
		 * @since 4.1.0
		 */
		$(document).on("change", ".date-slot-booking", function () {
			var enableDateSlotBooking = $(this).data(
				"enable-date-slot-booking"
			);
			//Checks date slot booking is enable.
			if (enableDateSlotBooking === 1) {
				var mode = "date",
					format = $(this).data("date-format"),
					modeType = $(this).data("mode"),
					fieldKey = $(this)
						.data("id")
						.replace("user_registration_", "");
				$(document)
					.find("#" + $(this).data("id") + "-slot-booking-error")
					.remove();
				//Data object for ajax.
				var data = {
					action: "user_registration_pro_user_slot_booking",
					security:
						user_registration_pro_frontend_slot_booking_data.slot_booking_data_nonce
				};

				//Checks time slot booking is enable and target date field.

				//Collecting the date field data's.
				var dateValue = $(this).val(),
					dateLocal = $(this).data("local"),
					formId = $(document).find(".register").data("form-id");

				if (dateValue === "") {
					return;
				}

				data.dateValue = dateValue;
				data.dateLocal = dateLocal;
				data.formId = formId;
				data.mode = mode;
				data.modeType = modeType;
				data.format = format;
				data.fieldKey = fieldKey;
				data.enableDateSlotBooking = enableDateSlotBooking;

				slot_booking(data, $(this));
			}
		});
		/**
		 * Slot booking for time picker field.
		 *
		 * @since 4.1.0
		 */
		$(document).on("change", ".time-slot-booking", function () {
			var enableTimeSlotBooking = $(this).data(
				"enable-time-slot-booking"
			);
			var prefix = "";
			var formType = $(this).closest("form");
			if (formType.hasClass("user-registration-EditProfileForm")) {
				var prefix = "user_registration_";
			}
			//Checks time slot booking is enable.
			if (enableTimeSlotBooking === 1) {
				var targetDateFieldId =
					prefix + $(this).data("target-date-field");
				var targetDateFieldDiv = $("#" + targetDateFieldId + "_field"),
					targetDateField = $(
						"#" + targetDateFieldId + "_field"
					).find(".flatpickr-input"),
					mode = "time",
					modeType = "",
					format = $(this).data("time-format");

				var timePickerFieldId = $(this)
						.data("id")
						.replace(/-(start|end)$/, ""),
					fieldKey = timePickerFieldId.replace(
						"user_registration_",
						""
					);

				$(document)
					.find("#" + timePickerFieldId + "-slot-booking-error")
					.remove();

				//Data object for ajax.
				var data = {
					action: "user_registration_pro_user_slot_booking",
					security:
						user_registration_pro_frontend_slot_booking_data.slot_booking_data_nonce
				};

				//Checks date slot booking is enable and target timepicker field.
				if (targetDateFieldDiv.length > 0) {
					var dateValue = $(targetDateField).val();
					$("#" + targetDateFieldId + "_field")
						.find("#" + targetDateFieldId + "-slot-booking-error")
						.remove();
					//If date field value is blank then show notice message.
					if (dateValue === "") {
						var html =
							'<label id="' +
							targetDateFieldId +
							'-slot-booking-error"class="user-registration-error" for="user_login">' +
							user_registration_pro_frontend_slot_booking_data.time_slot_booking_notice +
							"</label>";
						$(this).val("");
						$(document)
							.find("#" + targetDateFieldId + "_field")
							.append(html);
						return;
					}

					//Collecting the date field data's.
					var dateFormat = $(targetDateField).data("date-format"),
						dateLocal = $(targetDateField).data("locale"),
						dateMode = $(targetDateField).data("mode"),
						mode = "date-time",
						modeType = dateMode,
						format = dateFormat + " " + format;

					data.dateValue = dateValue;
					data.dateLocal = dateLocal;
				}
				//Collecting the time field data's.
				var formId = $(document).find(".register").data("form-id"),
					timeInterval = $(this).data("time-interval");

				var timeStart = $("#" + timePickerFieldId + "_field")
						.find('input[name="' + timePickerFieldId + '-start"]')
						.val(),
					timeEnd = $("#" + timePickerFieldId + "_field")
						.find('input[name="' + timePickerFieldId + '-end"]')
						.val(),
					timeValue = timeStart + " to " + timeEnd;
				if (timeStart === "" || timeEnd === "") {
					return;
				}
				data.timeValue = timeValue;
				data.timeInterval = timeInterval;
				data.formId = formId;
				data.mode = mode;
				data.modeType = modeType;
				data.format = format;
				data.fieldKey = fieldKey;
				data.enableTimeSlotBooking = enableTimeSlotBooking;

				slot_booking(data, $(this));
			}
		});

		/**
		 * Function for slot booking ajax request to check the slot is booked or not.
		 *
		 * @since 4.1.0
		 *
		 * @param {object} data slot booking object data for ajax request.
		 */
		function slot_booking(data, $this) {
			$.ajax({
				url: user_registration_pro_frontend_slot_booking_data.ajax_url,
				data: data,
				type: "POST",
				dataType: "JSON",
				beforeSend: function () {
					var submitButton = $(document).find(".ur-submit-button");
					$(submitButton).prop("disabled", true);

					var editProfileSubmit = $(document).find(
						".user-registration-submit-Button"
					);
					$(editProfileSubmit).prop("disabled", true);
				},
				success: function (res) {
					if (res.success === true) {
						$(document)
							.find(
								"#" +
									$this
										.data("id")
										.replace(/-(start|end)$/, "") +
									"-slot-booking-error"
							)
							.remove();
						var html =
							'<label id="' +
							$this.data("id").replace(/-(start|end)$/, "") +
							'-slot-booking-error" class="user-registration-error" for="user_login">' +
							res.data.message +
							"</label>";
						$(document)
							.find(
								"#" +
									$this
										.data("id")
										.replace(/-(start|end)$/, "") +
									"_field"
							)
							.append(html);
					} else {
						var submitButton =
							$(document).find(".ur-submit-button");
						$(submitButton).prop("disabled", false);

						var editProfileSubmit = $(document).find(
							".user-registration-submit-Button"
						);
						$(editProfileSubmit).prop("disabled", false);
					}
				}
			});
		}
	};
})(jQuery);
