/**
 * URFrontendListingFrontend JS.
 * global user_registration_frontend_listings_frontend_script_data
 */
jQuery(function ($) {
	var URFL_Frontend = {
		init: function () {
			$(".user-registration-frontend-listing-container").each(
				function () {
					URFL_Frontend.handle_advanced_filter($(this));
					URFL_Frontend.handle_user_details($(this));
				}
			);
		},
		/**
		 * Handle all the processes to retrieve user details
		 *
		 * @since 1.0.0
		 */
		handle_user_details: function (list) {
			var list_id = list
					.attr("id")
					.replace("user-registration-frontend-listing-", ""),
				paginator = list
					.find(
						".user-registration-frontend-listing-pagination-group"
					)
					.find(".active")
					.attr("id"),
				data = {
					action: "user_registration_frontend_listing_user_data",
					security:
						user_registration_frontend_listings_frontend_script_data.ur_frontend_listing_user_data_security,
					list_id: list_id,
					page:
						"undefined" !== typeof paginator
							? paginator.replace(
									"user-registration-frontend-listing-",
									""
							  )
							: 1,
					amount_filter: list
						.find(
							"#user-registration-frontend-listing-amount-filter"
						)
						.val(),
					sort_by: list
						.find("#user-registration-frontend-listing-sort-by")
						.val(),
					view_user_url: window.location.href.split("?")[0],
					search_param: list
						.find(
							"#user-registration-frontend-listing-search-field"
						)
						.val(),
				};

			//Run ajax when search button is clicked.
			list.find('input[name="search_user_profiles"').on(
				"click",
				function (e) {
					e.preventDefault();
					var search_param = $(this)
						.siblings(
							"#user-registration-frontend-listing-search-field"
						)
						.val();

					if ("" !== search_param) {
						data["search_param"] = search_param;
					} else {
						data["search_param"] = "";
					}

					data["page"] = 1;
					URFL_Frontend.parse_ajax(list, data);
				}
			);

			// Run ajax once the page loads.
			$(document).ready(function () {
				URFL_Frontend.parse_ajax(list, data);
			});

			//Run ajax when profiles per page filter is changed.
			list.find("#user-registration-frontend-listing-amount-filter").on(
				"change",
				function () {
					data.amount_filter = $(this).val();
					data.page = 1;
					URFL_Frontend.parse_ajax(list, data);
				}
			);

			//Run ajax when sort by filter is changed.
			list.find("#user-registration-frontend-listing-sort-by").on(
				"change",
				function () {
					data.sort_by = $(this).val();
					URFL_Frontend.parse_ajax(list, data);
				}
			);

			// Get advanced filters and rerun ajax.
			list.find(".ur-frontend-listing-advance-filter-apply").on(
				"click",
				function () {
					$(this)
						.closest(".frontend-listing-title-settings")
						.find(".ur-frontend-listing-filter-info-wrapper")
						.remove();

					data.advanced_filter = URFL_Frontend.get_advanced_filter(
						$(this)
					);

					$(this).closest(".ur-advance-setting-container").toggle();

					URFL_Frontend.parse_ajax(list, data);
				}
			);

			// Remove individual or all filters according to user action and rerun ajax.
			$(document.body).on(
				"click",
				".ur-frontend-listing-dismiss-filter, .ur-advance-setting-reset",
				function () {
					if ("all-filter" === $(this).data("id")) {
						$(this)
							.closest(".frontend-listing-title-settings")
							.find(".ur-frontend-listing-advance-filter-wrapper")
							.find(".ur-advance-settings-list")
							.find("input")
							.val("");
					} else {
						$(this)
							.closest(".frontend-listing-title-settings")
							.find(".ur-frontend-listing-advance-filter-wrapper")
							.find('input[name="' + $(this).data("id") + '"]')
							.val("");
					}

					data.advanced_filter = URFL_Frontend.get_advanced_filter(
						$(this)
					);

					if (
						$(this)
							.closest(".ur-frontend-listing-filter-info-wrapper")
							.find("button.ur-fl-dismiss-single-filter").length < 2
					) {
						$(this)
							.closest(".ur-frontend-listing-filter-info-wrapper")
							.find("button")
							.remove();
					}

					if (
						$(this).hasClass("ur-frontend-listing-dismiss-filter")
					) {
						$(this).parent().remove();
					}

					URFL_Frontend.parse_ajax(list, data);
				}
			);
		},
		/**
		 * Handle all the ajax processing to retrieve user details
		 *
		 * @since 1.0.0
		 */
		parse_ajax: function (node, data) {
			// Append a loader once the ajax process starts.
			if (node.find(".lds-dual-ring").length < 1) {
				$('<div class="lds-dual-ring"></div>').insertBefore(
					node.find(".user-registration-frontend-listing-body")
				);
			}
			node.find(".user-registration-frontend-listing-body").html("");

			$.ajax({
				url: user_registration_frontend_listings_frontend_script_data.ajax_url,
				data: data,
				type: "POST",
				success: function (response) {
					var response_type = response.success,
						profile_cards = response.data.profile_cards,
						pagination_template = response.data.pagination_template,
						displayed_users = response.data.displayed_users,
						total_users = response.data.total_users;

					if (response_type) {
						node.find(
							".user-registration-frontend-listing-body"
						).html(profile_cards);

						node.find(
							".user-registration-frontend-listing-footer"
						).html(pagination_template);

						var info_message =
							user_registration_frontend_listings_frontend_script_data.ur_frontend_listing_filtered_user_message
								.replace("%qty%", displayed_users)
								.replace("%total%", total_users);

						node.find(".ur-frontend-count").html(info_message);

						$(document).trigger(
							"user_registration_frontend_user_listing_loaded",
							node
						);

						if (
							node.find(
								".user-registration-frontend-listing-page"
							).length <= 3
						) {
							node.find(
								".user-registration-frontend-listing-pagination-group"
							).remove();
						}
					} else {
						node.find(
							".user-registration-frontend-listing-body"
						).html(
							"<div class='user-registration-error user-registration-frontend-listing-error' >" +
								response.data.message +
								"</div>"
						);

						node.find(".ur-frontend-count").html("");
						node.find(
							".user-registration-frontend-listing-pagination-group"
						).remove();
					}

					$(".lds-dual-ring").remove();
				},
			});
		},
		/**
		 * Handle all actions related to advanced filters.
		 *
		 * @since 1.1.0
		 * @param {Object} node
		 */
		handle_advanced_filter: function (node) {
			// Toggle advanced filter wrapper.
			$(".ur-frontend-advance-filter-open").on("click", function () {
				$(this)
					.closest(".frontend-listing-title-settings")
					.find(".ur-frontend-listing-advance-filter-wrapper")
					.toggle();
				$(this)
					.closest(".frontend-listing-title-settings")
					.find(".ur-frontend-listing-filter-info-wrapper")
					.remove();
			});
		},
		/**
		 * Create filter info wrapper after apply filter button is clicked.
		 *
		 * @since 1.1.0
		 * @param {object} node
		 */
		get_advanced_filter: function (node) {
			var advanced_filter = {};
			var filter_info_wrapper = "";

			if (
				node
					.closest(".frontend-listing-title-settings")
					.find(".ur-frontend-listing-filter-info-wrapper").length < 1
			) {
				filter_info_wrapper +=
					'<div class="ur-frontend-listing-filter-info-wrapper">';
			}

			node.closest(".frontend-listing-title-settings")
				.find(".ur-frontend-listing-advance-filter-wrapper")
				.find(".ur-advance-settings-list")
				.each(function () {
					var input_selector = $(this).find("input");

					if ("" !== input_selector.val()) {
						advanced_filter[
							input_selector
								.attr("name")
								.replace("ur_frontend_listing_", "")
						] = input_selector.val();
						filter_info_wrapper += '<button class="ur-fl-dismiss-single-filter" style="cursor:not-allowed;">';
						filter_info_wrapper +=
							"<strong>" +
							$(this).find("label").html() +
							": </strong>&nbsp;" +
							input_selector.val();
						filter_info_wrapper +=
							'<span class="dashicons dashicons-dismiss ur-frontend-listing-dismiss-filter" data-id="' +
							input_selector.attr("name") +
							'" style="margin-left: 5px; margin-right: 5px;"></span>';
						filter_info_wrapper += "</button>";
					}
				});

			if (Object.keys(advanced_filter).length > 0) {
				filter_info_wrapper +=
					'<button class="ur-frontend-listing-dismiss-filter" data-id="all-filter" >Clear All</button>';
				if (
					node
						.closest(".frontend-listing-title-settings")
						.find(".ur-frontend-listing-filter-info-wrapper")
						.length < 1
				) {
					filter_info_wrapper += "</div>";
					node.closest(".frontend-listing-title-settings").append(
						filter_info_wrapper
					);
				} else {
					node.closest(".frontend-listing-title-settings")
						.find(".ur-frontend-listing-filter-info-wrapper")
						.html(filter_info_wrapper);
				}
			}

			return advanced_filter;
		},
	};

	URFL_Frontend.init();

	// Handle pagination module.
	$(document).on(
		"user_registration_frontend_user_listing_loaded",
		function (event, node) {
			$(node)
				.find(".user-registration-frontend-listing-page")
				.on("click", function () {
					var current_active = $(this)
						.closest(
							".user-registration-frontend-listing-pagination-group"
						)
						.find(".active");

					$(node)
						.find(".user-registration-frontend-listing-page")
						.removeClass("active");

					var page_num = $(this)
						.attr("id")
						.replace("user-registration-frontend-listing-", "");

					if (!$.isNumeric(page_num)) {
						var next = current_active.next();
						var prev = current_active.prev();

						if (
							"next-page" === page_num &&
							"next-page" !==
								next
									.attr("id")
									.replace(
										"user-registration-frontend-listing-",
										""
									)
						) {
							page_num = next
								.attr("id")
								.replace(
									"user-registration-frontend-listing-",
									""
								);
						} else if (
							"previous-page" === page_num &&
							"previous-page" !==
								prev
									.attr("id")
									.replace(
										"user-registration-frontend-listing-",
										""
									)
						) {
							page_num = prev
								.attr("id")
								.replace(
									"user-registration-frontend-listing-",
									""
								);
						} else {
							page_num = 1;
						}
					}

					$(this)
						.closest(
							".user-registration-frontend-listing-pagination-group"
						)
						.find("#user-registration-frontend-listing-" + page_num)
						.addClass("active");

					// Scroll to the bottom on ajax submission complete.
					$(window).scrollTop(
						$(this)
							.closest(
								".user-registration-frontend-listing-container"
							)
							.offset().top
					);

					URFL_Frontend.handle_user_details($(node));
				});
		}
	);
});
