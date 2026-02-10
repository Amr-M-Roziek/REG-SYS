/**
 * URFrontendListingAdmin JS
 *
 * global user_registration_frontend_listing_admin_script_data
 */

jQuery(function ($) {
	var URFL_Admin = {
		init: function () {
			this.general_initialization();
			this.handle_general_options();
			this.handle_filter_options();
			this.handle_pagination_options();
			this.handle_advanced_filter_options();
			this.handle_advanced_filters();
			this.handle_user_fields();
		},
		/**
		 * Initialization all the required settings.
		 *
		 * @since 1.0.0
		 */
		general_initialization: function () {
			$(".multiple-select").select2();
			$(".multiple-select").select2({
				dropdownAutoWidth: true,
				containerCss: { display: "block" },
				width: "20%",
			});
		},
		/**
		 * Handle all the changes inside general options metabox
		 *
		 * @since 1.0.0
		 */
		handle_general_options: function () {
			var view_profile_selector = $(
					"#user_registration_frontend_listings_view_profile"
				),
				card_fields_selector = $(
					".user_registration_frontend_listings_card_fields_field"
				),
				view_profile_label_selector = $(
					".user_registration_frontend_listings_view_profile_button_text_field "
				);

			URFL_Admin.settings_fields_toggler(
				view_profile_selector,
				card_fields_selector
			);

			// Check if view profile settings is checked to hide/show card_fields settings div.
			view_profile_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					card_fields_selector
				);
			});

			URFL_Admin.settings_fields_toggler(
				view_profile_selector,
				view_profile_label_selector
			);

			// Check if view profile settings is checked to hide/show view profile label settings div.
			view_profile_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					view_profile_label_selector
				);
			});
		},
		/**
		 * Handle all the changes inside general options metabox
		 *
		 * @since 1.0.0
		 */
		handle_filter_options: function () {
			var search_form_selector = $(
					"#user_registration_frontend_listings_search_form"
				),
				search_criteria_selector = $(
					".user_registration_frontend_listings_search_fields_field"
				),
				sort_by_selector = $(
					"#user_registration_frontend_listings_sort_by"
				),
				default_sorter_selector = $(
					".user_registration_frontend_listings_default_sorter_field"
				),
				only_ur_selector = $(
					"#user_registration_frontend_listings_ur_only"
				),
				only_ur_forms_selector = $(
					".user_registration_frontend_listings_ur_forms_field "
				);

			URFL_Admin.settings_fields_toggler(
				search_form_selector,
				search_criteria_selector
			);
			URFL_Admin.settings_fields_toggler(
				sort_by_selector,
				default_sorter_selector
			);
			URFL_Admin.settings_fields_toggler(
				only_ur_selector,
				only_ur_forms_selector
			);

			// Check if enable search form is checked to hide/show search criteria settings div.
			search_form_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					search_criteria_selector
				);
			});

			// Check if enable sort by settings is checked to hide/show default sorter settings div.
			sort_by_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					default_sorter_selector
				);
			});

			// Check if enable sort by settings is checked to hide/show default sorter settings div.
			only_ur_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					only_ur_forms_selector
				);
			});
		},
		/**
		 * Handle all the changes inside pagination and results options metabox
		 *
		 * @since 1.0.0
		 */
		handle_pagination_options: function () {
			var amount_filter_selector = $(
					"#user_registration_frontend_listings_amount_filter"
				),
				amount_field_selector = $(
					".user_registration_frontend_listings_default_page_filter_field"
				),
				quantity_message_selector = $(
					".user_registration_frontend_listings_filtered_user_message_field"
				);

			URFL_Admin.settings_fields_toggler(
				amount_filter_selector,
				amount_field_selector
			);

			// Check if view profile settings is checked to hide/show card_fields settings div.
			amount_filter_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					amount_field_selector
				);
			});

			URFL_Admin.settings_fields_toggler(
				amount_filter_selector,
				quantity_message_selector
			);

			// Check if view profile settings is checked to hide/show qunatity message settings div.
			amount_filter_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					quantity_message_selector
				);
			});
		},
		/**
		 * Handle all the changes inside advanced filter options options metabox
		 *
		 * @since 1.1.0
		 */
		handle_advanced_filter_options: function () {
			var display_advanced_filter_selector = $(
					"#user_registration_frontend_listings_advanced_filter"
				),
				advanced_filter_fields_selector = $(
					".user_registration_frontend_listings_advanced_filter_fields_field"
				);

			URFL_Admin.settings_fields_toggler(
				display_advanced_filter_selector,
				advanced_filter_fields_selector
			);

			// Check if display advanced filters settings is checked to hide/show advanced filter fields settings div.
			display_advanced_filter_selector.on("change", function () {
				URFL_Admin.settings_fields_toggler(
					$(this),
					advanced_filter_fields_selector
				);
			});
		},
		/**
		 * Toggle a field with reference to other field's value.
		 *
		 * @since 1.0.0
		 */
		settings_fields_toggler: function (setting, toggle_field) {
			if (setting.is(":checked")) {
				toggle_field.show();
			} else {
				toggle_field.hide();
			}
		},
		/**
		 * Handles the creation and remmoval of advanced filters.
		 *
		 * @since 1.1.0
		 */
		handle_advanced_filters: function () {
			var flag = false;

			$(
				"#user_registration_frontend_listings_advanced_filter_fields_selector"
			).focusout(function () {
				flag = false;
			});

			// Add a filter field when button is clicked.
			$(
				"#user_registration_frontend_listings_advanced_filter_fields_selector"
			).on("click", function (event) {
				event.preventDefault();

				if (flag) {
					var filter_options =
							user_registration_frontend_listing_admin_script_data.ur_frontend_listing_advanced_filter_options,
						count =
							$(
								".user_registration_frontend_listings_advanced_filter_fields_container"
							).length + 1,
						field =
							'<div class="user_registration_frontend_listings_advanced_filter_fields_container selected-options">';

					field += '<div class="ur-draggable-option">';
					field += '<div class="selected-option-container">';
					field +=
						'<svg width="12" height="20" viewBox="0 0 12 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="4" height="4" fill="#4C5477"></rect><rect x="8" width="4" height="4" fill="#4C5477"></rect><rect y="16" width="4" height="4" fill="#4C5477"></rect><rect x="8" y="16" width="4" height="4" fill="#4C5477"></rect><rect y="8" width="4" height="4" fill="#4C5477"></rect><rect x="8" y="8" width="4" height="4" fill="#4C5477"></rect></svg>';
					field +=
						'<select id="user_registration_frontend_listings_advanced_filter_fields_' +
						count +
						'" class="user_registration_frontend_listings_advanced_filter_fields_map ur-selected ur-input-group">';

					for (var option_key in filter_options) {
						var selected =
							$(this).val() === option_key ? "selected" : "";

						field +=
							'<option value="' +
							option_key +
							'" ' +
							selected +
							">" +
							filter_options[option_key] +
							"</option>";
					}

					field += "</select>";
					field += "</div>";
					field +=
						'<span class="user_registration_frontend_listings_advanced_filter_fields_remove delete-option"><svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="feather feather-trash-2" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-6 5v6m4-6v6"></path></svg></span>';
					field += "</div>";
					field += "</div>";

					$(field).prependTo(
						$(this)
							.parent()
							.find(
								".user_registration_frontend_listings_advanced_filter_fields_list"
							)
					);
					$(this)
						.closest(".ur-metabox-field-detail")
						.trigger("change");
					$(this)
						.closest(".ur-metabox-field-detail")
						.find(
							"#user_registration_frontend_listings_advanced_filter_fields_" +
								count
						)
						.trigger("change");
				}
				flag = !flag;
			});

			// Remove a filter field when button is clicked.
			$(document.body).on(
				"click",
				".user_registration_frontend_listings_advanced_filter_fields_remove",
				function (event) {
					event.preventDefault();
					var $this = $(this),
						$trigger = $this.closest(".ur-metabox-field-detail");
					$this.parent().remove();
					$trigger.trigger("change");
				}
			);

			// Add the filter mapped values to the input so that it can be saved on post update.
			$(".ur-metabox-field-detail").on("change", function () {
				if (
					$(this).find(
						"#user_registration_frontend_listings_advanced_filter_fields_selector"
					).length > 0
				) {
					var field_mapper = {};

					$(this)
						.find(
							".user_registration_frontend_listings_advanced_filter_fields_map"
						)
						.each(function () {
							if ("DIV" !== $(this).get(0).tagName) {
								field_mapper[$(this).val()] = $(this)
									.find("option:selected")
									.text();
							} else {
								field_mapper[
									$(document.body)
										.find(
											"#" +
												$(this).attr("id") +
												"_meta_key"
										)
										.val()
								] = $(document.body)
									.find(
										"#" + $(this).attr("id") + "_meta_label"
									)
									.val();
							}
						});

					$(
						"#user_registration_frontend_listings_advanced_filter_fields"
					).attr("value", JSON.stringify(field_mapper));
				}
			});

			$(document.body).on(
				"change",
				".user_registration_frontend_listings_advanced_filter_fields_map",
				function () {
					if ("other" === $(this).val()) {
						var field =
							'<div class="' +
							$(this).attr("class") +
							'" id="' +
							$(this).attr("id") +
							'">';
						field +=
							'<input type="text" id="' +
							$(this).attr("id") +
							'_meta_label" class="user-registration-frontend-listing-advance-filter-meta-mapper ur-select custom-input" placeholder="Field Label">';
						field +=
							'<input type="text" id="' +
							$(this).attr("id") +
							'_meta_key" class="user-registration-frontend-listing-advance-filter-meta-mapper ur-select custom-input" placeholder="Meta Key ( eg. meta_key_123, meta_key_456 )">';
						field += "</div>";

						$(field).insertBefore($(this));
						$(this).remove();
					}
				}
			);

			$(document.body).on(
				"change",
				".user-registration-frontend-listing-advance-filter-meta-mapper",
				function () {
					$(this)
						.closest(".ur-metabox-field-detail")
						.trigger("change");
				}
			);

			if (
				$("#user_registration_frontend_listings_allow_guest").is(
					":checked"
				)
			) {
				$(
					".user_registration_frontend_listings_access_denied_text_field "
				).hide();
			} else {
				$(
					".user_registration_frontend_listings_access_denied_text_field "
				).show();
			}

			$(document).on(
				"change",
				"#user_registration_frontend_listings_allow_guest",
				function (event) {
					event.preventDefault();

					if ($(this).is(":checked")) {
						$(
							".user_registration_frontend_listings_access_denied_text_field "
						).hide();
					} else {
						$(
							".user_registration_frontend_listings_access_denied_text_field "
						).show();
					}
				}
			);

			$(document).ready(function () {
				if (
					$("#user_registration_frontend_listings_layout").val() ===
					"1"
				) {
					$(
						".user_registration_frontend_listings_lists_fields_field "
					).hide();
				} else {
					$(
						".user_registration_frontend_listings_lists_fields_field"
					).show();
				}
			});

			$(document).on(
				"change",
				"#user_registration_frontend_listings_layout",
				function (event) {
					event.preventDefault();

					if ($(this).val() === "1") {
						$(
							".user_registration_frontend_listings_lists_fields_field "
						).hide();
					} else {
						$(
							".user_registration_frontend_listings_lists_fields_field"
						).show();
					}
				}
			);

			// Make advanced filter list sortable;
			if (typeof $.fn.sortable != "undefined") {
				$(
					".user_registration_frontend_listings_advanced_filter_fields_list"
				).sortable({
					containment:
						".user_registration_frontend_listings_advanced_filter_fields_list",
					tolerance: "pointer",
					revert: "invalid",
					forceHelperSize: true,
					stop: function (event, ui) {
						$(".ur-metabox-field-detail").trigger("change");
					},
				});
			}
		},
		/**
		 * Handles the Display user field.
		 *
		 * @since 1.1.0
		 */
		handle_user_fields: function () {
			var selected_forms = $(
				".user_registration_frontend_listings_ur_forms_field"
			).find("select");
			selected_forms.on("change", function () {
				var form_ids = $(this).val();
				$.ajax({
					url: user_registration_frontend_listing_admin_script_data.ajax_url,
					type: "POST",
					data: {
						action: "user_registration_frontend_listing_display_user_fields",
						form_ids: form_ids,
					},
					success: function (response) {
						if (true === response.success) {
							$(
								".user_registration_frontend_listings_lists_fields_field select"
							).empty();

							$(
								".user_registration_frontend_listings_card_fields_field select"
							).empty();
							$.each(response.data, function (key, val) {
								var result = "";
								result +=
									'<optgroup label="' + val.form_label + '">';
								$.each(val.field_list, function (key, val) {
									result +=
										'<option value="' +
										key +
										'">' +
										val +
										"</option>";
								});
								result += "</optgroup>";
								$(
									".user_registration_frontend_listings_lists_fields_field select"
								).append(result);
								$(
									".user_registration_frontend_listings_card_fields_field select"
								).append(result);
							});
						}
					},
				});
			});
		},
	};

	URFL_Admin.init();
});
