(function ($, l10n) {
	var FormAnalytics = {
		/**
		 * Variable to store data.
		 *
		 * @since 1.0.0
		 */
		data: {
			formId: 0,
			userJourney: {
				loadTime: null,
			},
			formAbandonment: {
				formActivated: null,
				formSubmitted: null,
			},
		},

		init: function () {},

		/**
		 * Runs when document is ready.
		 *
		 * @since 1.0.0
		 */
		ready: function () {
			FormAnalytics.setTrackingId();
			FormAnalytics.setFormId();
			FormAnalytics.startUserJourney();

			if (
				"undefined" != typeof l10n.track_form_abandonment &&
				l10n.track_form_abandonment
			) {
				FormAnalytics.startFormAbandonmentCapture();
			}
		},

		/**
		 * Set form id in data variable if the page contains everest form.
		 *
		 * @since 1.0.0
		 */
		setFormId: function () {
			var $form = $(".user-registration form");

			if ($form.length) {
				FormAnalytics.data.formId = $form.data("form-id");
			}
		},

		/**
		 * Set Tracking Id if not exists.
		 *
		 * @since 1.0.0
		 *
		 * @returns void
		 */
		setTrackingId: function () {
			var cookies = document.cookie.split(";");
			var cookieValue = "";
			var cookieExists = cookies.some(function (cookie) {
				if (cookie.trim().startsWith("ur_fa_tracking_id" + "=")) {
					cookieValue = cookie.split("=")[1];
					return true;
				}
				return false;
			});

			if (!cookieExists) {
				var expirationDays = 100;
				var expirationDate = new Date();
				expirationDate.setDate(
					expirationDate.getDate() + expirationDays
				);

				var cookieName = "ur_fa_tracking_id";

				cookieValue =
					Math.floor(Math.random() * Date.now()).toString(36) +
					Math.floor(Math.random() * Date.now()).toString(36);

				var cookieString =
					encodeURIComponent(cookieName) +
					"=" +
					encodeURIComponent(cookieValue) +
					"; expires=" +
					expirationDate.toUTCString() +
					"; path=/";

				document.cookie = cookieString;
			}

			return;
		},

		/**
		 * Start User Journey tracker hooks.
		 *
		 * @since 1.0.0
		 */
		startUserJourney: function () {
			FormAnalytics.data.userJourney.loadTime = Date.now();

			$(window).on("beforeunload", function () {
				var userSessionDuration = Math.floor(
					(Date.now() - FormAnalytics.data.userJourney.loadTime) /
						1000
				);
				var formSubmitted =
					"undefined" !==
					FormAnalytics.data.formAbandonment.formSubmitted
						? FormAnalytics.data.formAbandonment.formSubmitted
						: false;

				var formAbandoned =
					"undefined" !==
					FormAnalytics.data.formAbandonment.formActivated
						? FormAnalytics.data.formAbandonment.formActivated &&
						  !formSubmitted
						: false;

				var data = {
					user_session_duration: userSessionDuration,
					form_submitted: formSubmitted,
					form_abandoned: formAbandoned,
					form_id: FormAnalytics.data.formId,
					referer: l10n.referer_page,
				};

				// Send the AJAX request
				$.ajax({
					url: l10n.ajax_url,
					type: "POST",
					data: {
						action: "ur_save_user_post_view",
						data: data,
						_wpnonce: l10n.save_visited_nonce,
					},
				});
			});
		},

		/**
		 * Start Form Abandonment Capture hooks.
		 *
		 * @since 1.0.0
		 */
		startFormAbandonmentCapture: function () {
			$(".ur-field-item *").on("focus change", function () {
				if (null == FormAnalytics.data.formAbandonment.formActivated) {
					FormAnalytics.data.formAbandonment.formActivated = true;
				}
			});

			$("form.register").on("submit", function () {
				FormAnalytics.data.formAbandonment.formSubmitted = true;
			});

			$(window).on(
				"user_registration_frontend_after_ajax_complete",
				function (e, ajax_response, type, $this) {
					FormAnalytics.data.formAbandonment.formSubmitted = true;
				}
			);

			$(window).on("beforeunload", function () {
				if (
					"undefined" !== typeof l10n.save_abandoned_value &&
					l10n.save_abandoned_value
				) {
					FormAnalytics.processAndSendAbandonedEntry();
				}
			});
		},

		/**
		 * Process and Send Abandoned Entry request.
		 *
		 * @since 1.0.0
		 */
		processAndSendAbandonedEntry: function () {
			if (
				null === FormAnalytics.data.formAbandonment.formSubmitted &&
				FormAnalytics.data.formAbandonment.formActivated
			) {
				var data = FormAnalytics.getAbandonedFormData();

				data.push(
					{
						name: "action",
						value: "ur_save_abandoned_data",
					},
					{
						name: "_wpnonce",
						value: l10n.save_abandoned_data_nonce,
					}
				);

				// Send the AJAX request
				$.ajax({
					url: l10n.ajax_url,
					type: "POST",
					data: data,
				});
			}
		},

		/**
		 * Get and return Abandoned Entry data in proper format.
		 *
		 * @since 1.0.0
		 */
		getAbandonedFormData: function () {
			var form = $("form.register");

			if (form.length) {
				return form.serializeArray();
			}

			return false;
		},
	};

	FormAnalytics.init();
	$(document).ready(FormAnalytics.ready);
})(jQuery, urFormAnalyticsl10n);
