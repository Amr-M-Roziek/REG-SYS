/**
 * UserRegistrationProDashboard JS
 * global user_registration_pro_dashboard_script_data
 */

jQuery(function ($) {
	var URE_Dashboard = {
		/**
		 * Variable to store various state data.
		 *
		 * @since 1.0.0
		 */
		data: {
			formId: 0,
			duration: "",
			from: "",
			to: "",
			summarySortBy: "",
			summarySortOrder: "desc",
			summaryCurrentPage: 1,
			summaryLimit: 10,
			popupActive: false
		},

		/**
		 * Variable to store charts.
		 *
		 * @since 1.0.0
		 */
		charts: {},

		/**
		 * Property to store response data accessible by all methods.
		 *
		 * @since 1.0.0
		 */
		responseData: {},

		init: function () {
			this.dashboard_load_event();
		},
		/**
		 * Sends the form and date-time data selected by the user to be processed through ajax call
		 * and receives datas and users chartjs library to draw charts.
		 *
		 * @since  1.0.0
		 *
		 */
		dashboard_load_event: function () {
			$(document).ready(function () {
				// Trigger change event to load all forms datas.
				$(".user-registration-pro-dashboard-select-form").trigger(
					"change"
				);

				// When Date switcher ( Day, Week and Month ) is clicked make clicked switch active.
				$(".user-registration-pro-date-switcher").on(
					"click",
					function () {
						// Check if the Date switcher is previously clicked.
						if ($(this).hasClass("is-active")) {
							return;
						} else {
							$(this).addClass("is-active");
							var switcher_class = $(this)
								.attr("class")
								.split(" ")[3];
							$(
								".user-registration-pro-date-switcher:not(." +
									switcher_class +
									")"
							).removeClass("is-active");
							$("#date-range-selector").val("");

							// Again trigger change event to sync and toggle forms data.
							$(
								".user-registration-pro-dashboard-select-form"
							).trigger("change");
						}
					}
				);

				var date_flatpickrs = {};

				$(".user-registration-pro-date-range-selector").on(
					"click",
					function () {
						var field_id = $(this).data("id");
						var date_flatpickr = date_flatpickrs[field_id];

						// Load a flatpicker for the field, if hasn't been loaded.
						if (!date_flatpickr) {
							date_flatpickr = $(this).flatpickr({
								mode: "range",
								disableMobile: true,
								dateFormat: "Y-m-d",
								static: true,
								onClose: function (
									selectedDates,
									dateString,
									instance
								) {
									// Remove active class from date-switcher and trigger change event only if a date range is selected.
									if (0 !== selectedDates.length) {
										$(
											".user-registration-pro-date-switcher"
										).removeClass("is-active");

										// Verify if the selected date is previously selected or not.
										if (
											dateString !==
											$(
												".user-registration-pro-date-checker"
											).val()
										) {
											$(
												".user-registration-pro-date-checker"
											).val(dateString);
											$(
												".user-registration-pro-dashboard-select-form"
											).trigger("change");
										}
									}
								},
								onOpen: function (
									selectedDates,
									dateStr,
									instance
								) {
									instance.clear();
									$(
										".user-registration-pro-date-checker"
									).val("");
								}
							});
							date_flatpickrs[field_id] = date_flatpickr;
						}

						if (date_flatpickr) {
							date_flatpickr.open();
						}
					}
				);
			});

			// Start the loading process when change event triggers.
			$(".user-registration-pro-dashboard-select-form").on(
				"change",
				function () {
					$(".user-registration-pro-dashboard__body").html(
						user_registration_pro_dashboard_script_data.dashboard_page_template
					);

					if ("all" !== $(this).val()) {
						$(document)
							.find(
								".user-registration-registration-source-report"
							)
							.remove();
					}
					// Set default selected date to Week in order to load weekly data as default.
					URE_Dashboard.data.duration = "Week";
					var to_date = "";
					var periodSelection = "";

					// Check if any of the date switcher is set to active by click event and set selected date.
					$(".user-registration-pro-date-switcher").each(function () {
						var date_range = $("#date-range-selector").val();
						var dates = date_range.split("to");
						URE_Dashboard.data.from = dates[0];
						URE_Dashboard.data.to = dates[1];

						if ("" !== date_range && date_range.indexOf("to") < 0) {
							URE_Dashboard.data.duration = "";
						} else {
							if ($(this).hasClass("is-active")) {
								periodSelection = $(this).html();
							} else {
								URE_Dashboard.data.duration = date_range;
							}
						}
					});
					if ("" !== periodSelection) {
						URE_Dashboard.data.duration = periodSelection;
					}

					URE_Dashboard.data.formId = $(this).val();

					var data = {
						action: "user_registration_pro_dashboard_analytics",
						form_id: URE_Dashboard.data.formId,
						selected_date: URE_Dashboard.data.duration,
						duration: URE_Dashboard.data.duration,
						from: URE_Dashboard.data.from,
						to: URE_Dashboard.data.to,
						summary_sort_by: URE_Dashboard.data.summarySortBy,
						summary_sort_order: URE_Dashboard.data.summarySortOrder,
						chart_type: "registration_count"
					};

					URE_Dashboard.fetch_analytics_report(data);
				}
			);
		},
		/**
		 * Recursively fetch and load analytics data and contents to reduce server load.
		 *
		 * @param {array} requestData Data for ajax action.
		 */
		fetch_analytics_report: function (requestData) {
			$.ajax({
				url: user_registration_pro_dashboard_script_data.ajax_url,
				data: requestData,
				type: "POST",
				success: function (response) {
					URE_Dashboard.responseData = response.data;

					var message = response.data.message;

					switch (requestData.chart_type) {
						case "registration_count":
							$(".user-registration-pro-dashboard__body")
								.find(
									".user-registration-registration-count-report"
								)
								.html(message);
							$(".user-registration-pro-dashboard__body")
								.find(
									".user-registration-registration-overview-report"
								)
								.html(response.data.registration_overview);

							var user_report = response.data.user_report;
							var date_range_data =
								user_report["weekly_data"]["daily_data"];

							var chart_canvas_area = $(document).find(
								"#user-registration-pro-registration-overview-chart-report-area"
							);

							var chart_options = {
								responsive: true,
								legend: {
									display: false
								},
								scales: {
									xAxes: [
										{
											ticks: {
												maxTicksLimit: 10
											}
										}
									]
								},
								legendCallback: function (chart) {
									var text = [];
									text.push(
										'<ul class="user-registration-pro-legend-' +
											chart.id +
											' ur-d-flex ur-flex-wrap ur-mt-3 ur-mb-0">'
									);

									for (var i = 0; i < 3; i++) {
										text.push(
											'<li><span class="user-registration-pro-color-tag" style="background-color:' +
												chart.data.datasets[i]
													.borderColor +
												'"></span>'
										);
										if (chart.data.datasets[i].label) {
											text.push(
												chart.data.datasets[i].label
											);
										}
										text.push("</li>");
									}
									text.push("</ul>");
									return text.join("");
								}
							};

							// Restructure the format of the data sent through ajax call to be compatible with chart's data requirements.
							var weekly_data = Object.keys(date_range_data),
								new_registration_datas = [],
								approved_users_datas = [],
								pending_users_datas = [],
								denied_users_datas = [];

							weekly_data.forEach(function (daily_data) {
								new_registration_datas.push(
									date_range_data[daily_data]
										.new_registration_in_a_day
								);
								approved_users_datas.push(
									date_range_data[daily_data]
										.approved_users_in_a_day
								);
								pending_users_datas.push(
									date_range_data[daily_data]
										.pending_users_in_a_day
								);
								denied_users_datas.push(
									date_range_data[daily_data]
										.denied_users_in_a_day
								);
							});

							var chart_data = {
								datasets: [
									{
										label: "New User Registration",
										fill: false,
										borderColor: "red", // The main line color
										data: new_registration_datas
									},
									{
										label: "Approved Users",
										fill: false,
										borderColor: "blue",
										data: approved_users_datas
									},
									{
										label: "Pending Users",
										fill: false,
										borderColor: "#800020",
										data: pending_users_datas
									}
								],
								labels: weekly_data
							};

							var chart = new Chart(chart_canvas_area, {
								type: "line",
								data: chart_data,
								options: chart_options
							});

							$(".user-registration-total-registration-chart")
								.find(
									".user-registration-pro-registration-overview-chart-report-legends"
								)
								.html(chart.setDatasetVisibility(1, true));

							if ("all" === requestData.form_id) {
								requestData.chart_type = "specific_form_users";
								URE_Dashboard.fetch_analytics_report(
									requestData
								);
							} else {
								requestData.chart_type =
									"form_analytics_report";
								URE_Dashboard.fetch_analytics_report(
									requestData
								);
							}
							break;
						case "specific_form_users":
							URE_Dashboard.specific_registration_source_chart(
								response.data
							);
							requestData.chart_type = "form_analytics_report";
							URE_Dashboard.fetch_analytics_report(requestData);
							break;
						case "form_analytics_report":
							URE_Dashboard.populateISCALineChart(response);
							requestData.chart_type = "top_referer_report";
							URE_Dashboard.fetch_analytics_report(requestData);
							break;
						case "top_referer_report":
							URE_Dashboard.populateTopRefererPages(response);
							requestData.chart_type = "form_summary_report";
							URE_Dashboard.fetch_analytics_report(requestData);
							break;
						case "form_summary_report":
							URE_Dashboard.populateSummaryTable();
							break;
					}
				}
			});
		},
		/**
		 * Populate top referer pages.
		 *
		 * @since 1.0.0
		 */
		populateTopRefererPages: function (responseData) {
			var html = "";

			$(".user-registration-form-analytics-referer-report").html(
				responseData.data.message
			);

			if (responseData.data.top_referer_pages.length > 0) {
				responseData.data.top_referer_pages.forEach(function (page) {
					html +=
						"<li>" +
						page.title +
						"<a href='" +
						page.url +
						"' rel='noreferrer noopener' target='_blank'> <i class='dashicons dashicons-external'></i></a></li>";
				});
			} else {
				html += "No referer pages found.";
			}
			$(".urfa-list").html(html);
		},

		/**
		 * Populate ISCA Line Chart.
		 *
		 * @since 1.0.0
		 */
		populateISCALineChart: function (responseData) {
			$(".user-registration-form-analytics-overview-report").html(
				responseData.data.message
			);
			var ctx = $(
				"#user-registration-pro-form-analytics-overview-chart-report-area"
			);
			var config = {
				type: "line",
				data: {},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						title: {
							display: true
						}
					}
				}
			};

			URE_Dashboard.charts.iscaChart = new Chart(ctx, config);
			var chartData = responseData.data.isca.data;
			var chartLabels = responseData.data.isca.labels;

			var data = {
				labels: chartLabels,
				datasets: [
					{
						label: "Completed",
						data: chartData.map(function (entry) {
							return entry.submitted_count;
						}),
						borderWidth: 2,
						fill: true,
						borderColor: "#2ecc71",
						backgroundColor: "#e8faf0"
					},
					{
						label: "Abandoned",
						data: chartData.map(function (entry) {
							return entry.abandoned_count;
						}),
						borderWidth: 2,
						fill: true,
						borderColor: "#9b59b6",
						backgroundColor: "#f2e9ff"
					},
					{
						label: "Bounced",
						data: chartData.map(function (entry) {
							return entry.bounced_count;
						}),
						borderWidth: 2,
						fill: true,
						borderColor: "#3498db",
						backgroundColor: "#f6fbfc"
					},
					{
						label: "Impressions",
						data: chartData.map(function (entry) {
							return entry.total_count;
						}),
						borderColor: "#6c757d",
						backgroundColor: "#e3e8ec",
						borderWidth: 2,
						fill: true
					}
				]
			};
			URE_Dashboard.charts.iscaChart.data = data;
			URE_Dashboard.charts.iscaChart.update();
		},

		/**
		 * Populate Summary table.
		 *
		 * @since 1.0.0
		 */
		populateSummaryTable: function () {
			$(".user-registration-form-analytics-summary-report").html(
				URE_Dashboard.responseData.message
			);
			var $table = $(document).find("#urfa-forms-summary-table");
			var summary = URE_Dashboard.responseData.form_summary;

			if (URE_Dashboard.data.formId === "all") {
				URE_Dashboard.data.formId = 0;
			}

			var totalSummary = summary.filter(function (form) {
				return form.form_id == 0;
			});

			summary = summary.filter(function (form) {
				return form.form_id != 0;
			});

			$(document)
				.find("#urfa-summary-table-total-count")
				.html(summary.length);

			var limit = URE_Dashboard.data.summaryLimit;

			if (summary.length > limit) {
				var page = URE_Dashboard.data.summaryCurrentPage;

				var startIndex = parseInt(page - 1) * limit;

				var endIndex = startIndex + limit;
				endIndex =
					endIndex < summary.length ? endIndex : summary.length;

				summary = summary.slice(startIndex, endIndex);

				URE_Dashboard.populateSummaryTablePagination();
			}

			var tableBody = "";

			$.each(summary, function (index, form) {
				var rowClass = "";

				if (form.form_id == URE_Dashboard.data.formId) {
					rowClass = "urfa-active-form-row";
				}
				var html =
					'<tr class="' +
					rowClass +
					'"' +
					'data-summary-form-id="' +
					form.form_id +
					'">';
				html += "<td>" + form.name + "</td>";
				html += "<td>" + form.total_count + "</td>";
				html += "<td>" + form.submitted_count + "</td>";
				html += "<td>" + form.conversion_rate + "%</td>";
				html += "<td>" + form.abandoned_count + "</td>";
				html += "<td>" + form.abandonment_rate + "%</td>";
				html += "<td>" + form.bounce_rate + "%</td>";
				html += "</tr>";

				tableBody += html;
			});

			$table.find("tbody").html(tableBody);

			var tableFoot = "";

			$.each(totalSummary, function (index, form) {
				var rowClass = "";

				if (form.form_id == URE_Dashboard.data.formId) {
					rowClass = "urfa-active-form-row";
				}

				var html =
					'<tr class="' +
					rowClass +
					'" ' +
					'data-summary-form-id="' +
					form.form_id +
					'">';
				html += "<td>" + form.name + "</td>";
				html += "<td>" + form.total_count + "</td>";
				html += "<td>" + form.submitted_count + "</td>";
				html += "<td>" + form.conversion_rate + "%</td>";
				html += "<td>" + form.abandoned_count + "</td>";
				html += "<td>" + form.abandonment_rate + "%</td>";
				html += "<td>" + form.bounce_rate + "%</td>";
				html += "</tr>";

				tableFoot += html;
			});

			$table.find("tfoot").html(tableFoot);
		},

		/**
		 * Populate Summary Table Pagination content.
		 *
		 * @since 1.0.0
		 */
		populateSummaryTablePagination: function () {
			var page = URE_Dashboard.data.summaryCurrentPage;
			var summary = URE_Dashboard.responseData.form_summary;
			var limit = URE_Dashboard.data.summaryLimit;
			var totalItems = summary.length;
			var totalPages = Math.ceil(totalItems / limit);

			var html = "";

			for (var i = 0; i < totalPages; i++) {
				var pageId = i + 1;
				var linkClass = page == pageId ? "disabled" : "";
				html +=
					'<a class="button urfa-summary-table-pagination-link ' +
					linkClass +
					'" href="#" data-page="' +
					pageId +
					'">' +
					pageId +
					"</a>";
			}

			$(".user-registration-form-summary .tablenav-pages").html(html);

			$(".user-registration-form-summary .tablenav-pages")
				.find(".urfa-summary-table-pagination-link")
				.on("click", function (e) {
					e.preventDefault();
					e.stopPropagation();

					var targetPage = $(e.target).data("page");

					if (
						targetPage !=
						URE_Dashboard.getState("summaryCurrentPage")
					) {
						URE_Dashboard.updateState(
							{ summaryCurrentPage: targetPage },
							false
						);
						URE_Dashboard.populateSummaryTable();
					}
				});
		},

		/**
		 * Update state.
		 *
		 * @since 1.0.0
		 *
		 * @param {array} changes State changes array.
		 * @param {boolean} refresh Whether to refresh charts.
		 */
		updateState: function (changes, refresh) {
			if (refresh === undefined) {
				refresh = true;
			}

			var changed = false;

			$.each(changes, function (key, value) {
				if (URE_Dashboard.data[key] !== value) {
					URE_Dashboard.data[key] = value;
					changed = true;
				}
			});

			if (changed && refresh) {
				URE_Dashboard.dashboard_load_event();
			}
		},

		/**
		 * Get Current state value for specified key.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} key State key.
		 * @returns mixed
		 */
		getState: function (key) {
			if (typeof URE_Dashboard.data[key] !== "undefined") {
				return URE_Dashboard.data[key];
			} else {
				return false;
			}
		},
		/**
		 * Process the data sent through ajax call and
		 * draws pie chart for specific registration source data.
		 *
		 * @since  1.0.0
		 *
		 */
		specific_registration_source_chart: function (user_report) {
			$(".user-registration-registration-source-report").html(
				user_report.message
			);
			var $user_report = user_report;
			var chart_canvas_area = $(document).find(
				"#user-registration-pro-specific-form-registration-overview-chart-report-area"
			);

			var chart_options = {
				responsive: true,
				legend: {
					display: false
				},
				legendCallback: function (chart) {
					var text = [];
					text.push(
						'<ul class="user-registration-pro-legend-' +
							chart.id +
							' ur-d-flex ur-flex-wrap ur-mt-3 ur-mb-0">'
					);

					for (var i = 0; i < chart.data.labels.length; i++) {
						text.push(
							'<li><span class="user-registration-pro-color-tag" style="background-color:' +
								chart.data.datasets[0].backgroundColor[i] +
								'"></span>'
						);
						if (chart.data.labels[i]) {
							text.push(chart.data.labels[i]);
						}
						text.push("</li>");
					}
					text.push("</ul>");
					return text.join("");
				}
			};

			var specific_form_registration_data = Object.keys(
				$user_report["specific_form_registration"]
			).sort();

			var data_lable = [],
				data_value = [];

			specific_form_registration_data.forEach(function (daily_data) {
				data_lable.push($user_report["specific_form_registration"]);
				data_value.push(
					$user_report["specific_form_registration"][daily_data]
				);
			});

			var colors = [];
			for (var i = 0; i < specific_form_registration_data.length; i++) {
				colors.push(URE_Dashboard.getRandomColor());
			}

			var chart_data = {
				datasets: [
					{
						backgroundColor: colors, // The main line color
						data: data_value
					}
				],
				labels: specific_form_registration_data
			};

			var chart = new Chart(chart_canvas_area, {
				type: "doughnut",
				data: chart_data,
				options: chart_options
			});

			$(".user-registration-specific-registration-chart")
				.find(
					".user-registration-pro-specific-form-registration-overview-chart-report-legends"
				)
				.html(chart.setDatasetVisibility(1, true));
		},
		/**
		 * Generate a random colour hex code to be used for filling chart areas.
		 *
		 * @since  1.0.0
		 *
		 */
		getRandomColor: function () {
			var letters = "0123456789ABCDEF";
			var color = "#";

			for (var i = 0; i < 6; i++) {
				color += letters[Math.floor(Math.random() * 16)];
			}

			return color;
		}
	};
	URE_Dashboard.init();

	// Toggles Registration form selection element based upon the popup type selected.
	hideShowRegistrationFormSelector(
		$(".user-registration-pro-select-popup-type")
	);
	$("select[name='user_registration_pro_popup_type']").on(
		"change",
		function () {
			hideShowRegistrationFormSelector($(this));
		}
	);

	function hideShowRegistrationFormSelector(node) {
		var popup_type = node.val();

		if ("login" === popup_type) {
			node.closest("#mainform")
				.find(
					"select[name='user_registration_pro_popup_registration_form']"
				)
				.closest(".user-registration-global-settings")
				.hide();
		} else {
			node.closest("#mainform")
				.find(
					"select[name='user_registration_pro_popup_registration_form']"
				)
				.closest(".user-registration-global-settings")
				.show();
		}
	}
});
