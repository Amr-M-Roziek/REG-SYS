/*global console, UR_Snackbar, Swal*/
(function ($, urc_localized_data) {
	if (UR_Snackbar) {
		var snackbar = new UR_Snackbar();
	}

	//extra utils for coupon add ons
	var ur_coupon_utils = {
		format_data_key_name: function ($object) {
			return $object.replace('_', ' ').split(' ')
				.map(function (word) {
					return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
				})
				.join(' ');
		},
		convert_to_array: function ($object) {
			return Object.values($object).reverse().slice(2).reverse();
		},
		append_spinner: function ($element) {
			if ($element && $element.append) {
				var spinner = '<span class="ur-spinner is-active"></span>';

				$element.append(spinner);
				return true;
			}
			return false;
		},
		prepend_spinner: function ($element) {
			if ($element && $element.prepend) {
				var spinner = '<span class="ur-spinner is-active"></span>';

				$element.prepend(spinner);
				return true;
			}
			return false;
		},
		/**
		 * Remove spinner elements from a element.
		 *
		 * @param {jQuery} $element
		 */
		remove_spinner: function ($element) {
			if ($element && $element.remove) {
				$element.find('.ur-spinner').remove();
				return true;
			}
			return false;
		},

		if_empty: function (value, _default) {
			if (null === value || undefined === value || '' === value) {
				return _default;
			}
			return value;
		},
		/**
		 * Enable/Disable save buttons i.e. 'Save' button and 'Save as Draft' button.
		 *
		 * @param {Boolean} disable Whether to disable or enable.
		 */
		toggleSaveButtons: function (disable) {
			disable = this.if_empty(disable, true);
			$('.save-coupon-btn').prop('disabled', !!disable);
		},

		/**
		 * Show success message using snackbar.
		 *
		 * @param {String} message Message to show.
		 */
		show_success_message: function (message) {
			if (snackbar) {
				snackbar.add({
					type: 'success',
					message: message,
					duration: 5
				});
				return true;
			}
			return false;
		},

		/**
		 * Show failure message using snackbar.
		 *
		 * @param {String} message Message to show.
		 */
		show_failure_message: function (message) {
			if (snackbar) {
				snackbar.add({
					type: 'failure',
					message: message,
					duration: 6
				});
				return true;
			}
			return false;
		}

	};
	//utils related with ajax requests
	var ur_coupon_ajax_utils = {
		/**
		 *
		 * @returns {{}}
		 */
		prepare_coupons_data: function () {
			var user_data = {},
				form_inputs = $('.ur-coupon-input'),
				matched_item = [];
			form_inputs = ur_coupon_utils.convert_to_array(form_inputs);
			form_inputs.forEach(function (item) {
				var $this = $(item),
					name = $this.data('key-name').toLowerCase();
				user_data[name] = $this.is('input[type="checkbox"]') ? $this.prop('checked') : $this.val();
			});

			user_data.coupon_discount_type = $('input[name="ur_coupon_discount_type"]:checked').val();
			user_data.coupon_for = $('#ur-input-type-coupon-for option:selected').val();

			if (user_data.coupon_discount_type === 'fixed') {
				matched_item = user_data.coupon_discount.match(/(?<=\$)\d+(\.\d+)?/);
			} else {
				matched_item = user_data.coupon_discount.match(/^\d+(\.\d+)?%$/);
			}

			user_data.coupon_discount = parseFloat(matched_item[0]).toFixed(2);
			if (user_data.coupon_for === 'empty') {
				delete user_data.coupon_form;
				delete user_data.coupon_membership;
			} else {
				if (user_data.coupon_for === 'form') {
					delete user_data.coupon_membership;
				} else if (user_data.coupon_for === 'membership') {
					delete user_data.coupon_form;
				}
			}
			return user_data;
		},
		/**
		 * validate coupon form before submit
		 * @returns {boolean}
		 */
		validate_coupon_form: function () {
			var form_inputs = $('#ur-coupon-create-form').find('input'),
				no_errors = true;
			//main fields validation
			form_inputs = ur_coupon_utils.convert_to_array(form_inputs);

			form_inputs.every(function (item) {

				var $this = $(item),
					value = $this.val(),
					is_required = $this.attr('required'),
					name = $this.data('key-name');

				if (is_required && value === '') {
					no_errors = false;
					name = ur_coupon_utils.format_data_key_name(name);
					ur_coupon_utils.show_failure_message(urc_localized_data.labels.i18n_error + '! ' + name + ' ' + urc_localized_data.labels.i18n_field_is_required);
					return false;
				}

				if (name === 'end_date') {
					var start_date = $('#ur-coupon-create-form').find('#ur-input-type-coupon-start-date').val();
					if (value < start_date) {
						no_errors = false;
						ur_coupon_utils.show_failure_message(urc_localized_data.labels.i18n_end_date_validation);
						return false;
					}
				}
				if (name === 'coupon_discount_type_percent' && $this.is(':checked')) {
					var amount = $('#ur-input-type-coupon-discount-type').val(),
						matched_item = amount.match(/^\d+(\.\d+)?(?=%$)/);
					if (matched_item === null) {
						no_errors = false;
						ur_coupon_utils.show_failure_message(urc_localized_data.labels.i18n_amount_type_validation);
						return false;
					}

					if (parseInt(matched_item[0]) > 100) {
						no_errors = false;
						ur_coupon_utils.show_failure_message(urc_localized_data.labels.i18n_percent_limit_validation);
						return false;
					}
				}
				if (name === 'coupon_discount_type_fixed' && $this.is(':checked')) {
					var amount = $('#ur-input-type-coupon-discount-type').val(),
						matched_item = amount.match(/(?<=\$)\d+(\.\d+)?/);

					if (isNaN(parseFloat(matched_item))) {
						no_errors = false;
						ur_coupon_utils.show_failure_message(urc_localized_data.labels.i18n_amount_type_validation);
						return false;
					}
				}
				return true;
			});

			// select validation
			var applicable_for = $('#ur-input-type-coupon-for option:selected').val();

			if (applicable_for !== 'empty') {
				if (applicable_for === 'form') {
					var form_select = $('#ur-input-type-coupon-form option:selected').val();
					if (undefined === form_select || '' === form_select) {
						no_errors = false;
						ur_coupon_utils.show_failure_message(urc_localized_data.labels.i18n_applicable_for_form_validation);
					}
				} else if (applicable_for === 'membership') {
					var membership_select = $('#ur-input-type-coupon-membership option:selected').val();
					if (undefined === membership_select || '' === membership_select) {
						no_errors = false;
						ur_coupon_utils.show_failure_message(urc_localized_data.labels.i18n_applicable_for_membership_validation);
					}
				}
			}
			return no_errors;
		},

		/**
		 * called to create a new coupon
		 * @param $this
		 */
		create_coupon: function ($this) {
			ur_coupon_utils.toggleSaveButtons(true);
			ur_coupon_utils.append_spinner($this);

			if (this.validate_coupon_form()) {
				var prepare_coupons_data = this.prepare_coupons_data();

				this.send_data(
					{
						action: 'user_registration_coupons_create',
						coupons_data: JSON.stringify(prepare_coupons_data)
					},
					{
						success: function (response) {
							if (response.success) {
								urc_localized_data.coupon_id = response.data.coupon_id;
								$('.save-coupon-btn').text('Save Coupon');
								ur_coupon_utils.show_success_message(
									response.data.message
								);
							} else {
								ur_coupon_utils.show_failure_message(
									response.data.message
								);
							}
						},
						failure: function (xhr, statusText) {
							ur_coupon_utils.show_failure_message(
								urc_localized_data.labels.network_error +
								'(' +
								statusText +
								')'
							);
						},
						complete: function () {
							ur_coupon_utils.remove_spinner($this);
							ur_coupon_utils.toggleSaveButtons(false);
						}
					}
				);
			} else {
				ur_coupon_utils.remove_spinner($this);
				ur_coupon_utils.toggleSaveButtons(false);
			}
		},

		/**
		 * called to update an existing coupon
		 * @param $this
		 */
		update_coupon: function ($this) {
			ur_coupon_utils.toggleSaveButtons(true);
			ur_coupon_utils.append_spinner($this);

			if (this.validate_coupon_form()) {
				var prepare_coupons_data = this.prepare_coupons_data();

				this.send_data(
					{
						action: 'user_registration_coupons_update',
						coupon_id: urc_localized_data.coupon_id,
						coupons_data: JSON.stringify(prepare_coupons_data)
					},
					{
						success: function (response) {
							if (response.success) {
								urc_localized_data.coupon_id = response.data.coupon_id;
								ur_coupon_utils.show_success_message(
									response.data.message
								);
							} else {
								ur_coupon_utils.show_failure_message(
									response.data.message
								);
							}
						},
						failure: function (xhr, statusText) {
							ur_coupon_utils.show_failure_message(
								urc_localized_data.labels.network_error +
								'(' +
								statusText +
								')'
							);
						},
						complete: function () {
							ur_coupon_utils.remove_spinner($this);
							ur_coupon_utils.toggleSaveButtons(false);
						}
					}
				);
			} else {
				ur_coupon_utils.remove_spinner($this);
				ur_coupon_utils.toggleSaveButtons(false);
			}
		},

		/**
		 * Send data to the backend API.
		 *
		 * @param {JSON} data Data to send.
		 * @param {JSON} callbacks Callbacks list.
		 */
		send_data: function (data, callbacks) {
			var success_callback =
					'function' === typeof callbacks.success ? callbacks.success : function () {
					},
				failure_callback =
					'function' === typeof callbacks.failure ? callbacks.failure : function () {
					},
				beforeSend_callback =
					'function' === typeof callbacks.beforeSend ? callbacks.beforeSend : function () {
					},
				complete_callback =
					'function' === typeof callbacks.complete ? callbacks.complete : function () {
					};

			// Inject default data.
			if (!data._wpnonce && urc_localized_data) {
				data._wpnonce = urc_localized_data._nonce;
			}
			$.ajax({
				type: 'post',
				dataType: 'json',
				url: urc_localized_data.ajax_url,
				data: data,
				beforeSend: beforeSend_callback,
				success: success_callback,
				error: failure_callback,
				complete: complete_callback
			});
		},

		handle_bulk_delete_action: function (form) {
			Swal.fire({
				title:
					'<img src="' +
					urc_localized_data.delete_icon +
					'" id="delete-user-icon">' +
					urc_localized_data.labels.i18n_prompt_title,
				html: '<p id="html_1">' +
					urc_localized_data.labels.i18n_prompt_bulk_subtitle +
					'</p>',
				showCancelButton: true,
				confirmButtonText: urc_localized_data.labels.i18n_prompt_delete,
				cancelButtonText: urc_localized_data.labels.i18n_prompt_cancel,
				allowOutsideClick: false
			}).then(function (result) {
				if (result.isConfirmed) {
					var selected_coupons = form.find('input[name="coupon[]"]:checked'),
						coupons_ids = [];

					if (selected_coupons.length < 1) {
						ur_coupon_utils.show_failure_message(
							urc_localized_data.labels.i18n_prompt_no_membership_selected
						);
						return;
					}
					//prepare orders data
					selected_coupons.each(function () {
						if ($(this).val() !== '') {
							coupons_ids.push($(this).val());
						}
					});

					//send request
					ur_coupon_ajax_utils.send_data(
						{
							action: 'user_registration_coupons_delete_coupons',
							coupons_ids: JSON.stringify(coupons_ids)
						},
						{
							success: function (response) {
								if (response.success) {

									ur_coupon_utils.show_success_message(
										response.data.message
									);
									ur_coupon_ajax_utils.remove_deleted_coupons(selected_coupons, true);
								} else {
									ur_coupon_utils.show_failure_message(
										response.data.message
									);
								}
							},
							failure: function (xhr, statusText) {
								ur_coupon_utils.show_failure_message(
									urc_localized_data.labels.network_error +
									'(' +
									statusText +
									')'
								);
							},
							complete: function () {
								window.location.reload();
							}
						}
					);
				}
			});
		},

		/**
		 *
		 * @param selected_coupons
		 * @param is_multiple
		 */
		remove_deleted_coupons: function (selected_coupons, is_multiple) {
			if (is_multiple) {
				selected_coupons.each(function () {
					$(this).parents('tr').remove();
				});
			} else {
				$(selected_coupons).parents('tr').remove();
			}
		}
	};


	$(document).ready(function () {
		var coupon_input_for = $('#ur-input-type-coupon-for');
		//initialize select 2 for applicable for field with single select
		coupon_input_for.select2({
			minimumResultsForSearch: -1,
			placeholder: urc_localized_data.labels.i18n_select_dropdown
		});
		$('.coupon-enhanced-select2').select2({
			maximumSelectionSize: 1,
			minimumResultsForSearch: -1,
			multiple: true
		});

		coupon_input_for.on('change', function () {
			var value = $(this).find('option:selected').val(),
				available_container = $('.coupon-hidden-select');
			available_container.each(function (key, item) {
				$(item).removeClass('ur-d-flex').addClass('ur-d-none');
			});
			available_container.each(function (key, item) {
				if ($(item).data('value') === value) {
					$(item).removeClass('ur-d-none').addClass('ur-d-flex');
				}
			});
		});

		$('.save-coupon-btn').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $this = $(this);
			if (urc_localized_data.coupon_id === '') {
				ur_coupon_ajax_utils.create_coupon($this);
			} else {
				ur_coupon_ajax_utils.update_coupon($this);
			}
		});

		$('input[name="ur_coupon_discount_type"]').on('change', function () {
			var discount_amount = $('#ur-input-type-coupon-discount-type'),
				$this = $(this),
				discount_val = discount_amount.val();
			if (discount_val === '') {
				return;
			}
			if ($this.val() === 'percent') {
				var matched_string = discount_val.match(/(?<=\$)\d+(\.\d+)?/);
				if (matched_string == null) {
					matched_string = discount_val.match(/^\d+(\.\d+)?%$/);
				}

				var amount = matched_string === null ? discount_val : matched_string;

				discount_amount.val(parseFloat(amount).toFixed(2) + '%');
			} else {
				var matched = discount_val.match(/\$.*/),
					match_percent = [];
				if (matched == null) {
					match_percent = discount_val.match(/^\d+(\.\d+)?%$/);
					if (match_percent !== null) {
						discount_amount.val(urc_localized_data.global_currency_symbol + parseFloat(match_percent[0]).toFixed(2));
					} else {
						discount_amount.val(urc_localized_data.global_currency_symbol + parseFloat(discount_val).toFixed(2));
					}
				}


			}
		}).filter(':checked').trigger('change');

		$('#ur-input-type-coupon-discount-type').on('change', function () {
			$('input[name="ur_coupon_discount_type"]:checked').trigger('change');
		});

		$('#delete-coupon .submitdelete').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $this = $(this);
			Swal.fire({
				title:
					'<img src="' +
					urc_localized_data.delete_icon +
					'" id="delete-user-icon">' +
					urc_localized_data.labels.i18n_prompt_title,
				html: '<p id="html_1">' +
					urc_localized_data.labels.i18n_prompt_single_subtitle +
					'</p>',
				showCancelButton: true,
				confirmButtonText: urc_localized_data.labels.i18n_prompt_delete,
				cancelButtonText: urc_localized_data.labels.i18n_prompt_cancel,
				allowOutsideClick: false
			}).then(function (result) {
				if (result.isConfirmed) {
					$(location).attr('href', $this.attr('href'));
				}
			});
		});

		$('#ur-coupon-list-form #doaction,#doaction2').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var form = $('#ur-coupon-list-form'),
				selectedAction = form.find('select#bulk-action-selector-top option:selected').val();
			switch (selectedAction) {
				case 'trash' :
					ur_coupon_ajax_utils.handle_bulk_delete_action(form);
					break;
				default:
					break;
			}

		});
	});
})
(jQuery, window.ur_coupons_localized_data);
