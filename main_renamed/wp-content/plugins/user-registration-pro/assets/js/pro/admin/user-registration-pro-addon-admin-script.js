/**
 * UserRegistrationProAdmin JS
 */
jQuery(function ($) {
	var UR_ADDON = {
		init: function () {
			this.initialize_conditional_logic_settings();
			this.initialize_prevent_active_login_settings();
			this.handle_row_options_toggle();
			this.handle_field_options_toggle();
			this.handle_users_filter_reset();
			this.handle_passwordless_login_toggle();
		},
		initialize_conditional_logic_settings: function () {
			/**
			 * Replace input field according to selected field in list field such as country, select etc.
			 */
			$(document).on(
				"change",
				".ur-conditional-wrapper .ur_conditional_field",
				function () {
					UR_ADDON.replaceFieldValues(
						".ur-conditional-wrapper",
						this
					);
				}
			);

			/**
			 * Default Hide show conditional logic container according to enable field.
			 */
			$(document)
				.find(".ur_use_conditional_logic_wrapper")
				.each(function () {
					var wrapper = $(this).closest(
						".ur_conditional_logic_container"
					);
					if (
						$(this).find("#ur_use_conditional_logic").is(":checked")
					) {
						wrapper.find(".ur_conditional_logic_wrapper").show();
					} else {
						wrapper.find(".ur_conditional_logic_wrapper").hide();
					}
				});

			/**
			 * Hide show conditional logic container on change of enable field.
			 */
			$(document).on("change", "#ur_use_conditional_logic", function () {
				var wrapper = $(this).closest(
					".ur_conditional_logic_container"
				);

				if ($(this).is(":checked")) {
					wrapper.find(".ur_conditional_logic_wrapper").show();
				} else {
					wrapper.find(".ur_conditional_logic_wrapper").hide();
				}
			});
		},
		/**
		 * Toggle a field with reference to other field's value.
		 */
		settings_fields_toggler: function (setting, toggle_field) {
			if (setting.is(":checked")) {
				toggle_field.show();
			} else {
				toggle_field.hide();
			}
		},
		/**
		 * Replace Input field with dropdown according to fields like checkbox, select, country, etc.
		 */
		replaceFieldValues: function ($class, $this) {
			var data_type = $("option:selected", $this).attr("data-type");
			var selected_val = $("option:selected", $this).val();
			var input_node = $($this)
				.closest($class)
				.find(".ur-conditional-input");

			//Grab input node attributes
			var nodeName = input_node.attr("name"),
				nodeClass = input_node.attr("class");

			if (
				data_type == "checkbox" ||
				data_type == "radio" ||
				data_type == "select" ||
				data_type == "country" ||
				data_type == "billing_country" ||
				data_type == "shipping_country" ||
				data_type == "select2" ||
				data_type == "multi_select2" ||
				data_type == "multi_choice"
			) {
				if (
					data_type == "select" ||
					data_type == "select2" ||
					data_type == "multi_select2"
				) {
					var values = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field option")
						.map(function () {
							return $(this).val();
						});
				} else if (
					data_type == "country" ||
					data_type == "billing_country" ||
					data_type == "shipping_country"
				) {
					var countryKey = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field option")
						.map(function () {
							return $(this).val();
						});
					var countryName = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field option")
						.map(function () {
							return $(this).text();
						});
				} else {
					var values = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field input")
						.map(function () {
							return $(this).val();
						});
				}
				var options = "<option value>--select--</option>";

				if (
					data_type == "country" ||
					data_type == "billing_country" ||
					data_type == "shipping_country"
				) {
					var countries = $(
						'.ur-general-setting-field-name input[value="' +
							selected_val +
							'"'
					)
						.closest(".ur-selected-item")
						.find(
							".ur-advance-selected_countries select option:selected"
						);
					var options_html = [];

					$(this)
						.find(".urcl-value select")
						.html('<option value="">--select--</option>');
					countries.each(function () {
						var country_iso = $(this).val();
						var country_name = $(this).text();

						options_html.push(
							'<option value="' +
								country_iso +
								'">' +
								country_name +
								"</option>"
						);
					});
					options = options_html.join("");
				} else {
					if (values.length == 1 && values[0] === "") {
						options =
							'<option value="1">' +
							urcl_data.checkbox_checked +
							"</option>";
					} else {
						$(values).each(function (index, el) {
							options =
								options +
								'<option value="' +
								el +
								'">' +
								el +
								"</option>";
						});
					}
				}

				input_node.replaceWith(
					'<select name="' +
						nodeName +
						'" class="' +
						nodeClass +
						'">' +
						options +
						"</select>"
				);
			} else {
				input_node.replaceWith(
					'<input type="text" name="' +
						nodeName +
						'" class="' +
						nodeClass +
						'">'
				);
			}
		},
		/**
		 * Handle prevent active login settings.
		 */
		initialize_prevent_active_login_settings: function () {
			$("#user_registration_pro_general_setting_prevent_active_login").on(
				"click",
				function () {
					UR_ADDON.showHideActiveLogin($(this));
				}
			);

			UR_ADDON.showHideActiveLogin(
				$("#user_registration_pro_general_setting_prevent_active_login")
			);
		},
		/**
		 * Show or hide active login limits option.
		 */
		showHideActiveLogin: function ($node) {
			if ($node.prop("checked")) {
				$("#user_registration_pro_general_setting_limited_login")
					.closest(".user-registration-global-settings")
					.show();
			} else {
				$("#user_registration_pro_general_setting_limited_login")
					.closest(".user-registration-global-settings")
					.addClass("userregistration-forms-hidden")
					.hide();
			}
		},
		/**
		 * Hide row settings if fields other than row is clicked.
		 */
		handle_field_options_toggle: function () {
			$(".ur-builder-wrapper .ur-selected-inputs .ur-selected-item").on(
				"click",
				function () {
					$(
						'.ur-tab-lists li[aria-controls="ur-row-settings"]'
					).hide();
					$(
						'.ur-tab-lists li[aria-controls="ur-tab-field-options"]'
					).show();
				}
			);
		},
		/**
		 * Resets the set filter in users table.
		 */
		handle_users_filter_reset: function () {
			$("#user-registration-users-filter-reset-btn").on(
				"click",
				function (e) {
					e.preventDefault();
					var url = window.location.href;

					var form = $(this).closest("form")[0];
					form.reset();

					$(form).find('input[type="hidden"]').val("");

					$(form)
						.find("select")
						.each(function () {
							$(this).prop("selectedIndex", 0);
						});

					window.location.href = url.split("&")[0];
				}
			);
		},
		/**
		 * Show row settings if row settings icon is clicked.
		 */
		handle_row_options_toggle: function () {
			$(document).on("click", ".ur-row-settings", function () {
				$(document).trigger("row_settings_clicked", $(this));
			});

			$(document).on("row_settings_clicked", function (e, node) {
				$(
					'.ur-tab-lists li[aria-controls="ur-tab-field-options"]'
				).hide();
				$(
					'.ur-tab-lists li[aria-controls="ur-multipart-page-settings"]'
				).hide();

				$('.ur-tab-lists li[aria-controls="ur-row-settings"]').show();
				$("#ur-row-options").trigger("click", ["triggered_click"]);

				var row_id = $(node)
					.closest(".ur-single-row")
					.attr("data-row-id");

				$("#ur-row-section-settings")
					.find(".ur-individual-row-settings")
					.each(function () {
						if (row_id === $(this).attr("data-row-id")) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});

				$(".ur-input-grids").
					find(".ur-single-row")
					.each(function() {
						if(row_id === $(this).attr("data-row-id")) {
							$(this).find('.ur-selected-item').addClass('ur-row-setting-active');
						}else{
							$(this).find('.ur-selected-item').removeClass('ur-row-setting-active');
						}
					})
			});
		},

		/**
		 * Handle password less login toggle.
		 *
		 * @since 5.0
		 */
		handle_passwordless_login_toggle: function () {
			$("#user_registration_pro_passwordless_login").on(
				"change",
				function () {
					var checked = UR_ADDON.showHidePasswordLessLogin(
						$(this).prop("checked")
					);
				}
			);

			var checked = $("#user_registration_pro_passwordless_login").prop(
				"checked"
			);

			UR_ADDON.showHidePasswordLessLogin(checked);
		},

		/**
		 * Handle show and hide passwordless login as default login.
		 *
		 * @since 5.0
		 */
		showHidePasswordLessLogin: function (checked) {
			if (checked) {
				$(
					"#user_registration_pro_passwordless_login_default_login_area"
				)
					.closest(".user-registration-global-settings")
					.show();
			} else {
				$(
					"#user_registration_pro_passwordless_login_default_login_area"
				)
					.closest(".user-registration-global-settings")
					.hide();
			}
		}
	};

	UR_ADDON.init();
});
