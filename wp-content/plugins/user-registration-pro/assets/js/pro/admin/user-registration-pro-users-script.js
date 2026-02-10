jQuery(function ($) {
	var l10n = urUsersl10n;

	var URProUsers = {
		init: function () {
			URProUsers.initUIBindings();
		},
		/**
		 * Bind UI changes.
		 */
		initUIBindings: function () {
			$("#bulk-action-selector-top").on(
				"change",
				URProUsers.handleActionsChange
			);
			URProUsers.handleActionsChange();

			$("#user-registration-users-advanced-filter-btn").on(
				"click",
				URProUsers.hideShowAdvancedFilters
			);
			$("#user_registration_pro_users_date_range_filter").on(
				"change",
				URProUsers.hideShowAdvancedFilters
			);
			$(
				"#user_registration_pro_start_date_filter, #user_registration_pro_end_date_filter"
			).on("change", URProUsers.handleCustomDateRangeInput);

			$(".ur-back-button").on("click", URProUsers.handleBackButton);

			$("#user-registration-user-action-delete a").on(
				"click",
				URProUsers.handleSingleUserDelete
			);
			$("#doaction.button.action").on(
				"click",
				URProUsers.handleBulkDelete
			);

			$(".hide-column-tog").on(
				"click",
				URProUsers.handleColumnStateChange
			);
		},

		/**
		 * Send ajax request when the user changes the visibility of
		 * form specific columns from screen options.
		 *
		 * @param {event} e
		 */
		handleColumnStateChange: function (e) {
			var $this = $(e.target);

			var default_columns = [
				"email",
				"role",
				"user_status",
				"user_source",
				"user_registered"
			];

			var form_id_el = $("#user-registration-users-form-id");

			if (!default_columns.includes($this.val()) && form_id_el.length) {
				var form_id = form_id_el.val();

				$.post(l10n.ajax_url, {
					action: "user_registration_pro_users_table_change_column_state",
					form: form_id,
					_wpnonce: l10n.change_column_nonce
				});
			}
		},

		handleActionsChange: function () {
			var action = $("#bulk-action-selector-top").val();

			if ("update_role" === action) {
				$("select#new_role").closest(".alignleft.actions").show();
				$("#doaction").hide();
			} else {
				$("select#new_role").closest(".alignleft.actions").hide();
				$("#doaction").show();
			}
		},

		/**
		 * Handle traversing to previous page when clicked on back button.
		 */
		handleBackButton: function () {
			window.history.back();
		},

		/**
		 * Handler for custom date range inputs.
		 */
		handleCustomDateRangeInput: function () {
			$("#user_registration_pro_users_date_range_filter").val("custom");
		},

		/**
		 * Handler to hide/show advanced filters.
		 *
		 * @param {event} e
		 * @returns void
		 */
		hideShowAdvancedFilters: function (e) {
			if (
				$("#user_registration_pro_users_date_range_filter").is(
					$(e.target)
				)
			) {
				// Case: Date Range value changed.

				if (
					"custom" ===
					$("#user_registration_pro_users_date_range_filter").val()
				) {
					$("#user-registration-users-advanced-filters").slideDown(
						600
					);
				} else {
					$("#user-registration-users-advanced-filters").slideUp(600);
				}

				return;
			} else if (
				$("#user-registration-users-advanced-filter-btn").is(
					$(e.target)
				) ||
				$("#user-registration-users-advanced-filter-btn").is(
					$(e.target).parent()
				)
			) {
				// Case: Advanced filters button clicked.

				$("#user-registration-users-advanced-filters").slideToggle(600);

				return;
			}
		},

		/**
		 * Handler for bulk delete action.
		 * @param {event} e
		 */
		handleBulkDelete: function (e) {
			var $this = $(e.target);
			var action = $this.parent().find("#bulk-action-selector-top").val();

			if ("delete" === action) {
				e.preventDefault();
				e.stopPropagation();

				var form = document.getElementById(
					"user-registration-users-action-form"
				);

				var formData = new FormData(form);
				var searchParams = new URLSearchParams(formData);

				// Get the target URL and append query parameters
				var targetURL =
					window.location.origin + window.location.pathname;
				var fullURL = targetURL + "?" + searchParams.toString();

				URProUsers.handleDeletePrompt(fullURL, "bulk");
			}
		},

		/**
		 * Handler for single user delete from user view screen.
		 * @param {event} e
		 */
		handleSingleUserDelete: function (e) {
			e.preventDefault();
			e.stopPropagation();

			var deleteUrl = $(e.target)
				.closest("#user-registration-user-action-delete")
				.find("a")
				.attr("href");

			URProUsers.handleDeletePrompt(deleteUrl, "single");
		},

		/**
		 * Show prompt and redirect to delete url on confirmation.
		 * @param {string} deleteUrl The url to redirect to delete users.
		 * @param {string} deleteType The type of delete operation: single or bulk.
		 */
		handleDeletePrompt: function (deleteUrl, deleteType) {
			var prompt_data = l10n.delete_prompt;
			var confirm_message =
				"single" == deleteType
					? prompt_data.confirm_message_single
					: prompt_data.confirm_message_bulk;

			Swal.fire({
				title:
					"<img src='" +
					prompt_data.icon +
					"' id='delete-user-icon'>" +
					prompt_data.title,
				html:
					'<p id="html_1">' +
					confirm_message +
					"</p>" +
					'<p id="html_2">' +
					prompt_data.warning_message +
					"</p>",
				showCancelButton: true,
				confirmButtonText: prompt_data.delete_label,
				cancelButtonText: prompt_data.cancel_label
			}).then(function (result) {
				if (result.isConfirmed) {
					if (deleteUrl) {
						window.location.href = deleteUrl;
					}
				}
			});
		},
	};

	$(document).ready(function () {
		if (
			$(
				".user-registration-users-page, #user-registration-pro-single-user-view"
			).length
		) {
			URProUsers.init();
		}
	});
});
