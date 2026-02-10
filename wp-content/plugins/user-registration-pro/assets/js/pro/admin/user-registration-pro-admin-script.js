/**
 * UserRegistrationProAdmin JS
 * global user_registration_pro_admin_script_data
 */
jQuery(function ($) {
	var UR_PRO = {
		init: function () {
			this.initalize_form_reset_settings();
			this.initalize_blacklist_words_form_settings();
			this.initalize_whitelist_domain_settings();
			this.initalize_form_auto_password_generation_settings();
			this.initialize_conditional_logic_settings();
			this.initialize_extension_page();
			this.initialize_external_fields_mapping();
			this.init_all_tooltips();
			this.manage_tooltip_fields();
			this.init_export_custom_fields();
			this.init_render_dynamic_form_fields();
			this.init_integration_settings();
			this.init_hide_show_redirection_settings();
			this.init_hide_show_role_based_redirection_settings();
			this.initalize_email_blocking_settings();
			this.init_general_field_settings();
			this.init_slot_booking();
			this.image_choice_uploader();
			this.trigger_image_captcha_settings();
			this.disable_users();
			this.misc_settings();
			$("#user_registration_form_setting_enable_blacklist_words").on('click', this.initalize_blacklist_words_form_settings);
		},
		/**
		 * Init Custom Captcha Field.
		 */
		init_general_field_settings: function () {
			/**
			 *	Hide show captcha type based on captcha format.
			 */
			$(document).on('ur_new_field_created', function (event, data) {
				var fieldName = data.fieldName;
				$('.ur-grid-lists .ur-selected-item')
					.find("[name='" + fieldName + "'")
					.find('.ur-captcha-question')
					.hide();
				$('.ur-grid-lists .ur-selected-item')
					.find("[name='" + fieldName + "'")
					.find('.ur-captcha-image-label')
					.hide();
				$('.ur-grid-lists .ur-selected-item')
					.find("[name='" + fieldName + "'")
					.find('.ur-captcha-image-icons-group')
					.hide();
			});

			$('select[data-field="captcha_format"]').each(function () {
				if ($(this).val() == 'math') {
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-equation')
						.show();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-question')
						.hide();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-image-label')
						.hide();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-image-icons-group')
						.hide();
				} else if ($(this).val() == 'qa') {
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-equation')
						.hide();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-question')
						.show();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-image-label')
						.hide();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-image-icons-group')
						.hide();
				} else {
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-equation')
						.hide();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-question')
						.hide();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('#ur-input-type-captcha-math')
						.hide();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-image-label')
						.show();
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-field')
						.find('.ur-captcha-image-icons-group')
						.show();
				}
			});

			$(document).on(
				'ur_general_field_settings_to_update_form_fields_in_builder',
				function (event, $field) {
					switch ($field.attr('data-field')) {
						case 'default_value':
							$field.on('change', function () {
								if ('default_value' === $field.attr('data-field')) {
									if (
										$field
											.closest('.ur-general-setting-block')
											.hasClass('ur-general-setting-captcha-question')
									) {
										UR_PRO.render_captcha_question($(this));
									}
								}
							});
							break;
						case 'options':
							$field.on('keyup', function () {
								if (
									$field
										.closest('.ur-general-setting-block')
										.hasClass('ur-general-setting-captcha-question')
								) {
									UR_PRO.render_captcha_question($field);
								}
							});
							break;
						case 'captcha_format':
							if ($field.val() === 'math') {
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-general-setting-options')
									.hide();
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-general-setting-image-captcha-options')
									.hide();
							} else if ('qa' === $field.val()) {
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-general-setting-options')
									.show();
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-general-setting-image-captcha-options')
									.hide();
							} else {
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-general-setting-options')
									.hide();
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-general-setting-image-captcha-options')
									.show();
							}

							$field.on('change', function () {
								var value = $field.val();

								if ('math' === value) {
									$('.ur-selected-item.ur-item-active')
										.find('.ur-general-setting-block')
										.find('.ur-general-setting-options')
										.hide();
									$('.ur-selected-item.ur-item-active')
										.find('.ur-general-setting-block')
										.find('.ur-general-setting-image-captcha-options')
										.hide();
								} else if ('qa' === value) {
									$('.ur-selected-item.ur-item-active')
										.find('.ur-general-setting-block')
										.find('.ur-general-setting-options')
										.show();
									$('.ur-selected-item.ur-item-active')
										.find('.ur-general-setting-block')
										.find('.ur-general-setting-image-captcha-options')
										.hide();
								} else {
									$('.ur-selected-item.ur-item-active')
										.find('.ur-general-setting-block')
										.find('.ur-general-setting-options')
										.hide();
									$('.ur-selected-item.ur-item-active')
										.find('.ur-general-setting-block')
										.find('.ur-general-setting-image-captcha-options')
										.show();
								}

								UR_PRO.trigger_general_setting_captcha_format($field);
							});

							$field.on('change', function () {
								if ($field.val() === 'math') {
									$field
										.closest('.ur-general-setting-block')
										.find('.ur-general-setting-options')
										.hide();
									$field
										.closest('.ur-general-setting-block')
										.find('.ur-general-setting-image-captcha-options')
										.hide();
								} else if ('qa' === $field.val()) {
									$field
										.closest('.ur-general-setting-block')
										.find('.ur-general-setting-options')
										.show();
									$field
										.closest('.ur-general-setting-block')
										.find('.ur-general-setting-image-captcha-options')
										.hide();
								} else {
									$field
										.closest('.ur-general-setting-block')
										.find('.ur-general-setting-options')
										.hide();
									$field
										.closest('.ur-general-setting-block')
										.find('.ur-general-setting-image-captcha-options')
										.show();
								}
							});
							break;
						case 'image_choice':
							if ($field.is(':checked')) {
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-image-choice-wrapper')
									.show();
								$('.ur-selected-item.ur-item-active')
									.find('.ur-general-setting-block')
									.find('.ur-image-choice-wrapper')
									.show();
							} else {
								$field
									.closest('.ur-general-setting-block')
									.find('.ur-image-choice-wrapper')
									.hide();
								$('.ur-selected-item.ur-item-active')
									.find('.ur-general-setting-block')
									.find('.ur-image-choice-wrapper')
									.hide();
							}

							$field.on('change', function () {
								UR_PRO.trigger_general_setting_image_choice($(this));
							});
					}
				}
			);

			$(document).on(
				'ur_sync_textarea_field_settings_in_selected_field_of_form_builder',
				function (event, $field_type, value) {
					switch ($field_type.attr('data-field-key')) {
						case 'captcha':
							UR_PRO.render_captcha_question(value);
							break;
					}
				}
			);
		},
		/**
		 * Reflects changes in captcha format field of field settings into selected field in form builder area.
		 *
		 * @param object $label captcha format field of fields from field settings.
		 */
		trigger_general_setting_captcha_format: function ($label) {
			var wrapper = $('.ur-selected-item.ur-item-active'),
				value = $.trim($label.val());
			wrapper
				.find('.ur-general-setting-captcha-format select option')
				.prop('selected', false);
			wrapper
				.find(
					'.ur-general-setting-captcha-format select option[value="' +
						value +
						'"]'
				)
				.prop('selected', true);

			if ('math' === value) {
				wrapper.find('.ur-field').find('.ur-captcha-question').hide();
				wrapper.find('.ur-field').find('.ur-captcha-equation').show();
				wrapper.find('.ur-field').find('#ur-input-type-captcha-math').show();
				wrapper.find('.ur-field').find('.ur-captcha-image-label').hide();
				wrapper.find('.ur-field').find('.ur-captcha-image-icons-group').hide();
			} else if ('qa' === value) {
				wrapper.find('.ur-field').find('.ur-captcha-equation').hide();
				wrapper.find('.ur-field').find('.ur-captcha-question').show();
				wrapper.find('.ur-field').find('#ur-input-type-captcha-math').show();
				wrapper.find('.ur-field').find('.ur-captcha-image-label').hide();
				wrapper.find('.ur-field').find('.ur-captcha-image-icons-group').hide();
			} else {
				wrapper.find('.ur-field').find('.ur-captcha-equation').hide();
				wrapper.find('.ur-field').find('.ur-captcha-question').hide();
				wrapper.find('.ur-field').find('#ur-input-type-captcha-math').hide();
				wrapper.find('.ur-field').find('.ur-captcha-image-label').show();
				wrapper.find('.ur-field').find('.ur-captcha-image-icons-group').show();
			}
		},

		/**
		 * Reflects changes in enable image choice field of field settings into selected field in form builder area.
		 *
		 * @param object $label enable image choice field of fields from field settings.
		 */
		trigger_general_setting_image_choice: function ($label) {
			var wrapper = $('.ur-selected-item.ur-item-active');
			wrapper
				.find('.ur-general-setting-block')
				.find('input[data-field="' + $label.attr('data-field') + '"]')
				.prop('checked', $label.is(':checked'));

			if ($label.is(':checked')) {
				$label
					.closest('.ur-general-setting-block')
					.find('.ur-image-choice-wrapper')
					.show();
				wrapper
					.find('.ur-general-setting-block')
					.find('.ur-image-choice-wrapper')
					.show();
				wrapper
					.find('.ur-admin-template')
					.find('.ur-field')
					.addClass('user-registration-image-options');
				wrapper
					.find('.ur-admin-template .user-registration-image-choice img')
					.each(function () {
						$(this).show();
					});
			} else {
				$label
					.closest('.ur-general-setting-block')
					.find('.ur-image-choice-wrapper')
					.hide();
				wrapper
					.find('.ur-general-setting-block')
					.find('.ur-image-choice-wrapper')
					.hide();
				wrapper
					.find('.ur-admin-template')
					.find('.ur-field')
					.removeClass('user-registration-image-options');
				wrapper
					.find('.ur-admin-template .user-registration-image-choice img')
					.each(function () {
						$(this).hide();
					});
			}
		},

		/**
		 * Reflects changes in captcha field of field settings into selected field in form builder area.
		 *
		 * @param object this_node  captcha  field from field settings.
		 *
		 * @since 4.0.5
		 */
		render_captcha_question: function (this_node) {
			var captcha_value = [];
			var li_elements = this_node.closest('ul').find('li');
			li_elements.each(function (index, element) {
				var question = $(element).find('input.ur-type-captcha-question').val();
				var answer = $(element).find('input.ur-type-captcha-answer').val();

				question = question.trim();
				answer = answer.trim();
				if (
					captcha_value.every(function (each_value) {
						return each_value.question !== question;
					})
				) {
					captcha_value.push({
						question: question,
						answer: answer
					});
				}
			});
			var wrapper = $('.ur-selected-item.ur-item-active');
			var captcha = wrapper.find('.ur-field');
			captcha.html('');

			for (var i = 0; i < captcha_value.length; i++) {
				if (captcha_value[i] !== '') {
					captcha.append(
						'<label><input value="' +
							captcha_value[i].question.trim() +
							'" type="text" ' +
							' disabled>' +
							captcha_value[i].question.trim() +
							'</label>'
					);
				}
			}
		},
		render_image_captcha_tag: function (this_node) {
			var this_name = this_node.attr('name');

			$('.ur-selected-item.ur-item-active')
				.find('.ur-general-setting-captcha')
				.find('.ur-general-setting-image-captcha-options')
				.find('input[name="' + this_name + '"]')
				.val(this_node.val());
		},
		render_image_captcha_radio: function (this_node) {
			var this_name = this_node.attr('name');
			var this_value = this_node.val();
			var field_name = this_node
				.closest('.ur-general-setting-image-captcha-options')
				.siblings('.ur-general-setting-field-name')
				.find("input[data-field='field_name']")
				.val();

			this_node
				.closest('.icons-group')
				.find('.ur-captcha-icon-radio')
				.each(function () {
					$(this).removeAttr('checked');
				});
			this_node.prop('checked', true);

			var wrapper = $('.ur-selected-item.ur-item-active')
				.find(
					'.ur-general-setting-field-name input[value="' + field_name + '"]'
				)
				.closest('.ur-general-setting-field-name')
				.siblings('.ur-general-setting-image-captcha-options');

			wrapper
				.find('.ur-captcha-icon-radio[name="' + this_name + '"]')
				.closest('.icons-group')
				.find('.ur-captcha-icon-radio')
				.each(function () {
					$(this).removeAttr('checked');
				});

			wrapper
				.find('input[name="' + this_name + '"][value="' + this_value + '"]')
				.prop('checked', true);
		},
		/**
		 * Initialize integration_setting elements.
		 */
		init_integration_settings: function () {
			$('.ur-integration-connected-accounts').each(function () {
				$(this)
					.closest('.user-registration-card')
					.find('.integration-status')
					.addClass('ur-integration-account-connected');
			});
		},
		/**
		 * Initialize all tooltip elements.
		 */
		init_all_tooltips: function () {
			var args = {
				theme: 'tooltipster-borderless',
				maxWidth: 200,
				multiple: true,
				interactive: true,
				position: 'bottom',
				contentAsHTML: true,
				functionInit: function (instance, helper) {
					var $origin = jQuery(helper.origin),
						dataTip = $origin.attr('data-tip');

					if (dataTip) {
						instance.content(dataTip);
					}
				}
			};

			$('.user-registration-help-tip').tooltipster(args);
		},
		/**
		 * Initialize External Plugin fields mapping.
		 */
		initialize_external_fields_mapping: function () {
			$(document).ready(function () {
				// Save payment condition to $_POST
				$(document).on(
					'user_registration_admin_before_form_submit',
					function (event, data) {
						var external_mapping_settings =
							UR_PRO.save_external_mapping_settings();
						if (external_mapping_settings.length > 0) {
							data.data['ur_pro_external_mapping_settings'] =
								external_mapping_settings;
						}
					}
				);
				UR_PRO.hide_show_external_fields_mapping();

				$(document).on(
					'change',
					'#user_registration_enable_external_fields_mapping_field',
					function () {
						UR_PRO.hide_show_external_fields_mapping();
					}
				);
				$(document).on('change', '.ur-pro-db-table-section', function () {
					UR_PRO.hide_show_external_table_selection();
				});
				var ur_pro_form_fields =
					user_registration_pro_admin_script_data.ur_pro_form_fields;

				$(document).on(
					'click',
					'.ur-pro-external-field-map-group .add',
					function () {
						var $data_key = $(this)
							.closest('.ur-pro-field-mapping-box')
							.attr('data-last-key');
						$data_key++;

						var $output =
							'<li class="ur-pro-external-field-map-group" data-key="' +
							$data_key +
							'">';
						$output += '<div class="ur-pro-external-field-map-form-group">';
						$output +=
							'<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' +
							$data_key +
							']">';
						$output += '<option value="">-- Select Field --</option>';
						$output += UR_PRO.get_fields_list();
						$output += '</select></div>';
						$output +=
							'<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
						$output += '<div class="ur-pro-value">';
						$output +=
							'<input name="user_registration_form_value[' +
							$data_key +
							']" class="ur_pro_external_field_name" placeholder="Enter Field Key" type="text" />';
						$output += '</div>';
						$output += '<span class="add">';
						$output += '<i class="dashicons dashicons-plus"></i>';
						$output += '</span>';
						$output += '<span class="remove">';
						$output += '<i class="dashicons dashicons-minus"></i>';
						$output += '</span></li>';

						$(this).closest('.ur-pro-field-mapping-box').append($output);
						$(this)
							.closest('.ur-pro-field-mapping-box')
							.attr('data-last-key', $data_key);
					}
				);

				$(document).on(
					'click',
					'.ur-pro-external-field-map-group .remove',
					function () {
						var row_count = $('.ur-pro-field-mapping-box').find(
							'.ur-pro-external-field-map-group'
						).length;
						if (row_count > 1) {
							$(this).parent().remove();
						}
					}
				);

				$(document).on('change', '.ur_pro_db_table', function () {
					$table = $(this).val();
					$('.ur-spinner').remove();
					UR_PRO.retrive_db_table_columns($table);
				});

				$(document).on(
					'click',
					'.ur-license-expiry-notice .notice-dismiss',
					function () {
						var $this = $(this);
						UR_PRO.get_license_expiry_count($this);
					}
				);
			});
		},
		/**
		 * Initialize whitelist domain form settings in form builder.
		 */
		initalize_whitelist_domain_settings: function () {
			$(document).ready(function () {
				var enable_whitelist_domain_field = $(
						'#user_registration_form_setting_enable_whitelist_domain'
					),
					whitelist_domain_field = $(
						'#user_registration_form_setting_whitelist_domain_field'
					),
					whitelist_domain_entries_field = $(
						'#user_registration_form_setting_domain_restriction_settings_field'
					);

				UR_PRO.settings_fields_toggler(
					enable_whitelist_domain_field,
					whitelist_domain_field
				);
				UR_PRO.settings_fields_toggler(
					enable_whitelist_domain_field,
					whitelist_domain_entries_field
				);

				// Check if enable search form is checked to hide/show search criteria settings div.
				enable_whitelist_domain_field.on('change', function () {
					UR_PRO.settings_fields_toggler($(this), whitelist_domain_field);
					UR_PRO.settings_fields_toggler(
						$(this),
						whitelist_domain_entries_field
					);
				});
			});
		},
		/**
		 * Initialize reset form settings in form builder.
		 */
		initalize_form_reset_settings: function () {
			$(document).ready(function () {
				var form_reset_field = $(
						'#user_registration_form_setting_enable_reset_button'
					),
					form_reset_class_field = $(
						'#user_registration_form_setting_form_reset_class_field'
					),
					form_reset_label_field = $(
						'#user_registration_form_setting_form_reset_label_field'
					);

				UR_PRO.settings_fields_toggler(
					form_reset_field,
					form_reset_class_field
				);
				UR_PRO.settings_fields_toggler(
					form_reset_field,
					form_reset_label_field
				);

				// Check if enable search form is checked to hide/show search criteria settings div.
				form_reset_field.on('change', function () {
					UR_PRO.settings_fields_toggler($(this), form_reset_class_field);
					UR_PRO.settings_fields_toggler($(this), form_reset_label_field);
				});
			});
		},
		/**
		 * Initialize blacklisting words form settings in form builder.
		 */
		initalize_blacklist_words_form_settings: function () {

			var enable_blacklisting_words = $("#user_registration_form_setting_enable_blacklist_words").is(":checked");

			if ( enable_blacklisting_words ) {
				$("#user_registration_form_setting_enable_blacklist_words").closest("#user_registration_form_setting_enable_blacklist_words_field").siblings("#user_registration_form_setting_blacklisted_words_settings_field").show();
				$("#user_registration_form_setting_enable_blacklist_words").closest("#user_registration_form_setting_enable_blacklist_words_field").siblings("#user_registration_form_setting_blacklisted_words_field_settings_field").show();
			} else {
				$("#user_registration_form_setting_enable_blacklist_words").closest("#user_registration_form_setting_enable_blacklist_words_field").siblings("#user_registration_form_setting_blacklisted_words_settings_field").hide();
				$("#user_registration_form_setting_enable_blacklist_words").closest("#user_registration_form_setting_enable_blacklist_words_field").siblings("#user_registration_form_setting_blacklisted_words_field_settings_field").hide();
			}

		},
		initalize_form_auto_password_generation_settings: function () {
			$(document).ready(function () {
				var auto_password_activate = $(
						'#user_registration_pro_auto_password_activate'
					),
					auto_generated_password_length_field = $(
						'#user_registration_pro_auto_generated_password_length_field'
					);

				UR_PRO.settings_fields_toggler(
					auto_password_activate,
					auto_generated_password_length_field
				);

				// Check if enable search form is checked to hide/show search criteria settings div.
				auto_password_activate.on('change', function () {
					UR_PRO.settings_fields_toggler(
						$(this),
						auto_generated_password_length_field
					);
				});
			});
		},
		initialize_conditional_logic_settings: function () {
			/**
			 * Replace input field according to selected field in list field such as country, select etc.
			 */
			$(document).on(
				'focus',
				'.ur-conditional-wrapper .ur_conditional_field',
				function () {
					UR_PRO.replaceFormFields($(this));
				}
			);
			$(document).on(
				'change',
				'.ur-conditional-wrapper .ur_conditional_field',
				function () {
					UR_PRO.replaceFieldValues('.ur-conditional-wrapper', this);
				}
			);

			/**
			 * Default Hide show conditional logic container according to enable field.
			 */
			$(document)
				.find('.ur_use_conditional_logic_wrapper')
				.each(function () {
					var wrapper = $(this).closest('.ur_conditional_logic_container');
					if ($(this).find('#ur_use_conditional_logic').is(':checked')) {
						wrapper.find('.ur_conditional_logic_wrapper').show();
					} else {
						wrapper.find('.ur_conditional_logic_wrapper').hide();
					}
				});

			/**
			 * Hide show conditional logic container on change of enable field.
			 */
			$(document).on('change', '#ur_use_conditional_logic', function () {
				var wrapper = $(this).closest('.ur_conditional_logic_container');

				if ($(this).is(':checked')) {
					wrapper.find('.ur_conditional_logic_wrapper').show();
				} else {
					wrapper.find('.ur_conditional_logic_wrapper').hide();
				}
			});
		},
		/**
		 * Hide Show External Table Selection according to user selection on the dropdown.
		 */
		hide_show_external_table_selection: function () {
			$selected_option = $('.ur-pro-db-table-section').val();
			if ('external_table' === $selected_option) {
				UR_PRO.init_all_tooltips();
				$('.ur-pro-external-field-table-column-selection').show();
			} else {
				$('.ur_pro_db_table').val(
					user_registration_pro_admin_script_data.ur_pro_db_prefix + 'usermeta'
				);
				$('.ur_pro_user_id_db_column').val('');
				$('.ur_pro_field_key_db_column').val('');
				$('.ur_pro_field_value_db_column').val('');
				$('.ur-pro-external-field-table-column-selection').hide();
			}
		},
		/**
		 * Hide Show External Field Mapping Section when enabled disabled.
		 */
		hide_show_external_fields_mapping: function () {
			var enable_assign_user_role = $(
				'#user_registration_enable_external_fields_mapping'
			).is(':checked');

			if (enable_assign_user_role) {
				if ($('.ur-pro-fields-mapping-container').length === 0) {
					var $html = $(
						user_registration_pro_admin_script_data.ur_pro_external_fields_mapping_output
					);
					var $fields_list = $html.find('.ur-pro-field-map-select');
					var $output =
						'<option value="">-- Select Field --</option>' +
						UR_PRO.get_fields_list();
					$fields_list.each(function () {
						var selected_value = $(this).val();
						$(this).html($output);
						$(this).val(selected_value);
						$(this)
							.find('option[value="' + selected_value + '"]')
							.prop('selected', true);
					});

					$('#user_registration_enable_external_fields_mapping_field').after(
						$html
					);
					UR_PRO.hide_show_external_table_selection();
				} else {
					$('.ur-pro-fields-mapping-container').show();
					UR_PRO.hide_show_external_table_selection();
				}
			} else {
				$('.ur-pro-fields-mapping-container').hide();
			}
		},
		/**
		 * Get list of fields wrapped in `option` tag.
		 * Warning: The output will NOT be wrapped with the `select` tag.
		 */
		get_fields_list: function () {
			var output = '';
			$('.ur-grid-lists .ur-selected-item .ur-general-setting').each(
				function () {
					var field_label = $(this)
						.closest('.ur-selected-item')
						.find(' .ur-admin-template .ur-label label')
						.text();
					var field_key = $(this)
						.closest('.ur-selected-item')
						.find(' .ur-admin-template .ur-field')
						.data('field-key');

					//strip certain fields
					if (
						'section_title' == field_key ||
						'html' == field_key ||
						'wysiwyg' == field_key ||
						'billing_address_title' == field_key ||
						'shipping_address_title' == field_key
					) {
						return;
					}

					var field_name = $(this).find("[data-field='field_name']").val();

					if (typeof field_name !== 'undefined') {
						output +=
							'<option value="' +
							field_name +
							'" data-type="' +
							field_key +
							'">' +
							field_label +
							'</option>';
					}
				}
			);
			return output;
		},
		/**
		 * Toggle a field with reference to other field's value.
		 */
		settings_fields_toggler: function (setting, toggle_field) {
			if (setting.is(':checked')) {
				toggle_field.show();
			} else {
				toggle_field.hide();
			}
		},
		/**
		 * Replace Input field with dropdown according to fields like checkbox, select, country, etc.
		 */
		replaceFieldValues: function ($class, $this) {
			var data_type = $('option:selected', $this).attr('data-type');
			var selected_val = $('option:selected', $this).val();
			var input_node = $($this).closest($class).find('.ur-conditional-input');

			//Grab input node attributes
			var nodeName = input_node.attr('name'),
				nodeClass = input_node.attr('class');

			if (
				data_type == 'checkbox' ||
				data_type == 'radio' ||
				data_type == 'select' ||
				data_type == 'country' ||
				data_type == 'billing_country' ||
				data_type == 'shipping_country' ||
				data_type == 'select2' ||
				data_type == 'multi_select2' ||
				data_type == 'multi_choice' ||
				data_type == 'multiple_choice' ||
				data_type == 'subscription_plan'
			) {
				if (
					data_type == 'select' ||
					data_type == 'select2' ||
					data_type == 'multi_select2'
				) {
					var values = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest('.ur-selected-item')
						.find('.ur-field option')
						.map(function () {
							return $(this).val();
						});
				} else if (
					data_type == 'country' ||
					data_type == 'billing_country' ||
					data_type == 'shipping_country'
				) {
					var countryKey = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest('.ur-selected-item')
						.find('.ur-field option')
						.map(function () {
							return $(this).val();
						});
					var countryName = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest('.ur-selected-item')
						.find('.ur-field option')
						.map(function () {
							return $(this).text();
						});
				} else {
					var values = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest('.ur-selected-item')
						.find('.ur-field input')
						.map(function () {
							return $(this).val();
						});
				}
				var options = '<option value>--select--</option>';

				if (
					data_type == 'country' ||
					data_type == 'billing_country' ||
					data_type == 'shipping_country'
				) {
					var countries = $(
						'.ur-general-setting-field-name input[value="' + selected_val + '"'
					)
						.closest('.ur-selected-item')
						.find('.ur-advance-selected_countries select option:selected');
					var options_html = [];

					$(this)
						.find('.urcl-value select')
						.html('<option value="">--select--</option>');
					countries.each(function () {
						var country_iso = $(this).val();
						var country_name = $(this).text();

						options_html.push(
							'<option value="' +
								country_iso +
								'">' +
								country_name +
								'</option>'
						);
					});
					options = options_html.join('');
				} else {
					if (values.length == 1 && values[0] === '') {
						options =
							'<option value="1">' + urcl_data.checkbox_checked + '</option>';
					} else {
						$(values).each(function (index, el) {
							options =
								options + '<option value="' + el + '">' + el + '</option>';
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
						'</select>'
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
		 * Get Columns list by Table.
		 * @param {string} $table Table Name
		 */
		retrive_db_table_columns: function ($table) {
			var data = {
				action: 'user_registration_pro_get_db_columns_by_table',
				security:
					user_registration_pro_admin_script_data.ur_pro_get_db_columns_by_table,
				table: $table
			};

			$('.user_registration_user_id_db_column_label').append(
				'<span class="ur-spinner"></span>'
			);
			$('.user_registration_field_key_db_column_label').append(
				'<span class="ur-spinner"></span>'
			);
			$('.user_registration_field_value_db_column_label').append(
				'<span class="ur-spinner"></span>'
			);

			$.ajax({
				url: user_registration_pro_admin_script_data.ajax_url,
				data: data,
				type: 'POST',
				success: function (response) {
					if (response.success) {
						if ('undefined' != typeof response.data.columns) {
							var columns_list = JSON.parse(response.data.columns);

							$('.ur_pro_user_id_db_column option').remove();
							$('.ur_pro_field_key_db_column option').remove();
							$('.ur_pro_field_value_db_column option').remove();

							$('.ur_pro_user_id_db_column').append(
								$('<option>', {
									value: '',
									text: '-- Select Column for User ID --'
								})
							);
							$('.ur_pro_field_key_db_column').append(
								$('<option>', {
									value: '',
									text: '-- Select Column for Field Key --'
								})
							);
							$('.ur_pro_field_value_db_column').append(
								$('<option>', {
									value: '',
									text: '-- Select Column for Field Value --  '
								})
							);

							var $output_options = '';
							$.each(columns_list, function (key, value) {
								$output_options +=
									'<option value="' + value + '">' + value + '</option>';
								$('.ur_pro_user_id_db_column').append(
									$('<option>', {
										value: value,
										text: value
									})
								);
								$('.ur_pro_field_key_db_column').append(
									$('<option>', {
										value: value,
										text: value
									})
								);
								$('.ur_pro_field_value_db_column').append(
									$('<option>', {
										value: value,
										text: value
									})
								);
							});
						}
					}
					$('.ur-spinner').remove();
				}
			});
		},
		/**
		 * Get the license expiry count
		 */
		get_license_expiry_count: function ($this) {
			var data = {
				action: 'user_registration_pro_get_license_expiry_count',
				security:
					user_registration_pro_admin_script_data.ur_pro_get_license_expiry_count
			};

			$.ajax({
				url: user_registration_pro_admin_script_data.ajax_url,
				data: data,
				type: 'POST'
			});
		},
		/**
		 * save external mapping settings from form builder
		 */
		save_external_mapping_settings: function () {
			var form_data = [];
			var single_row = $('.ur-pro-field-mapping-wrap');

			$.each(single_row, function () {
				var grid_list_item = $(this).find('.ur-pro-external-field-map-group');
				var all_field_data = [];
				var inner_mapped_fields = [];
				$.each(grid_list_item, function () {
					var mapped_fields = {
						ur_field: $(this).find('.ur-pro-field-map-select').val(),
						external_field: $(this).find('.ur_pro_external_field_name').val()
					};
					inner_mapped_fields.push(mapped_fields);
				});
				all_field_data.push(inner_mapped_fields);
				var all_fields = {
					db_table: $('.ur_pro_db_table').val(),
					user_id_db_column: $('.ur_pro_user_id_db_column').val(),
					field_key_db_column: $('.ur_pro_field_key_db_column').val(),
					field_value_db_column: $('.ur_pro_field_value_db_column').val(),
					mapped_fields: all_field_data
				};
				form_data.push(all_fields);
			});
			return form_data;
		},
		initialize_extension_page: function () {
			$('.ur_addons_wrap')
				.find('.install-now')
				.on('click', function (event) {
					var this_node = $(this),
						data = {
							action: 'user_registration_pro_extension_install',
							security:
								user_registration_pro_admin_script_data.ur_pro_install_extension,
							slug: this_node.data('slug'),
							name: this_node.data('name')
						};

					event.preventDefault();

					this_node.text('Installing...');
					this_node.append('<span class="ur-spinner"></span>');
					$.ajax({
						url: user_registration_pro_admin_script_data.ajax_url,
						data: data,
						type: 'POST',
						success: function (response) {
							var notice_type = response.success
									? 'notice-success'
									: 'notice-error',
								notice =
									'<div class="notice ' +
									notice_type +
									'"><p>' +
									response.message +
									'</p></div>';

							$(notice).insertBefore(
								this_node.closest('.ur_addons_wrap').find('.subsubsub')
							);

							if (response.success) {
								this_node.text('Installed');

								if (response.activateUrl) {
									this_node.text('Activate');
									this_node
										.removeClass('install-now')
										.addClass('activate-now button-primary')
										.attr('href', response.activateUrl);

									this_node
										.closest('.ur-plugin-card-bottom')
										.find('.status-label')
										.removeClass('status-install-now')
										.addClass('status-inactive')
										.text('Inactive');
								} else if (response.deActivateUrl) {
									this_node.text('Deactivate');
									this_node
										.removeClass('install-now')
										.addClass('deactivate-now button-danger')
										.attr('href', response.deActivateUrl);

									this_node
										.closest('.ur-plugin-card-bottom')
										.find('.status-label')
										.removeClass('status-install-now')
										.addClass('status-inactive')
										.text('Inactive');
								}
							} else {
								this_node
									.text(
										user_registration_pro_admin_script_data.ur_pro_extension_installed_failed_text
									)
									.prop('disabled', true);
							}

							$('.ur-spinner').remove();
							this_node.off('click');
						}
					});
				});
		},

		/**
		 * Initialize Tooltip Enanbled Fields
		 */
		manage_tooltip_fields: function () {
			$('input[data-field="tooltip"]').each(function () {
				if ($(this).is(':checked')) {
					$(this)
						.closest('.ur-selected-item')
						.find('.ur-label')
						.find('label')
						.append('<span class="ur-portal-tooltip"></span>');

					// Toggle Tooltip Message Field
					$(this)
						.closest('.ur-toggle-content')
						.find('.ur-general-setting-tooltip-message')
						.show(500);
				} else {
					$(this)
						.closest('.ur-toggle-content')
						.find('.ur-general-setting-tooltip-message')
						.hide(500);
				}

				$(document).on('click', '.ur-selected-item', function (e) {
					e.stopImmediatePropagation();

					var general_setting = $('.ur-general-setting-field');

					$.each(general_setting, function () {
						var $this_obj = $(this);
						switch ($this_obj.attr('data-field')) {
							case 'tooltip':
								$this_obj.on('change', function () {
									UR_PRO.trigger_general_setting_tooltip($this_obj);
								});
								break;

							case 'tooltip_message':
								$this_obj.on('input', function () {
									UR_PRO.trigger_general_setting_tooltip_message($this_obj);
								});
								break;
						}
					});
				});
			});
		},

		/**
		 * Reflects changes in enable tooltip field of field settings
		 * into selected field in form builder area.
		 *
		 * @param object $label Tooltip field of fields from field settings.
		 */
		trigger_general_setting_tooltip: function ($label) {
			var wrapper = $('.ur-selected-item.ur-item-active'),
				selector_field_name = $label
					.closest('#ur-setting-form')
					.find("[data-field='field_name']")
					.val(),
				active_field_name = wrapper.find("[data-field='field_name']").val();

			if (selector_field_name === active_field_name) {
				wrapper
					.find('.ur-general-setting-block')
					.find('input[data-field="' + $label.attr('data-field') + '"]')
					.prop('checked', $label.is(':checked'));
				if (wrapper.find('.ur-label').find('.ur-portal-tooltip').length > 0) {
					wrapper.find('.ur-label').find('.ur-portal-tooltip').remove();
				}

				if ($label.is(':checked')) {
					wrapper
						.find('.ur-label')
						.find('label')
						.append('<span class="ur-portal-tooltip"></span>');

					// Show Tooltip Message Field
					$label
						.closest('.ur-toggle-content')
						.find('.ur-general-setting-tooltip-message')
						.show(500);
				} else {
					wrapper
						.find('.ur-label')
						.find('label')
						.find('span.ur-portal-tooltip')
						.remove();

					// Hide Tooltip Message Field
					$label
						.closest('.ur-toggle-content')
						.find('.ur-general-setting-tooltip-message')
						.hide(500);
				}
			}
		},

		/**
		 * Reflects changes in tooltip message field of field settings into selected field in form builder area.
		 *
		 * @param object $label Tooltip field message fields from field settings.
		 */
		trigger_general_setting_tooltip_message: function ($label) {
			var wrapper = $('.ur-selected-item.ur-item-active');
			wrapper
				.find('.ur-general-setting-block')
				.find('textarea[data-field="' + $label.attr('data-field') + '"]')
				.val($label.val());
		},
		/**
		 * Export custom field handler
		 *
		 * @since 3.1.3
		 */
		init_export_custom_fields: function () {
			var form_selector = $('select.ur-input.forms-list');
			form_selector.on('change', function () {
				var ur_spinner = $('<span class="ur-spinner"></span>');
				$('.ur-export-custom-fields > p').append(ur_spinner);
				var form_id = $(this).val();
				if (form_id != '') {
					$.ajax({
						url: user_registration_pro_admin_script_data.ajax_url,
						data: {
							action: 'user_registration_pro_get_form_fields_list_by_form_id',
							form_id: form_id,
							security:
								user_registration_pro_admin_script_data.ur_pro_get_form_fields_by_form_id
						},
						type: 'post',
						success: function (response) {
							$('.ur-export-custom-fields').show();
							// Remove all fields in the select box
							$('.ur-custom-fields-input').html('');

							var fields_dict = JSON.parse(response.data.form_field_list);
							option = '';
							$.each(fields_dict, function (key, value) {
								option +=
									'<option class="ur-field-option" value="' +
									key +
									'">' +
									value +
									'</option>';
							});
							$('.ur-custom-fields-input').html(option);
							$('.ur_export_csv_additional_fields_dict').val(
								response.data.form_field_list
							);
							ur_spinner.remove();
						}
					});
				} else {
					$('.ur-export-custom-fields').hide();
					ur_spinner.remove();
				}
			});

			$('.ur-export-custom-fields').hide();
			$(function () {
				flatpickr('#date_range', {
					mode: 'range',
					dateFormat: 'Y-m-d',
					maxDate: 'today',
					onChange: function (dates) {
						if (dates.length == 2) {
							var start = dates[0];
							var format_start =
								start.toLocaleString('default', {
									year: 'numeric'
								}) +
								'-' +
								start.toLocaleString('default', {
									month: '2-digit'
								}) +
								'-' +
								start.toLocaleString('default', {
									day: '2-digit'
								});
							var end = dates[1];
							var format_end =
								end.toLocaleString('default', {
									year: 'numeric'
								}) +
								'-' +
								end.toLocaleString('default', {
									month: '2-digit'
								}) +
								'-' +
								end.toLocaleString('default', {
									day: '2-digit'
								});
							$('#from_date').val(format_start);
							$('#to_date').val(format_end);
						}
					}
				});
			});
		},

		// Render form field dynamically on drag and drop.
		init_render_dynamic_form_fields: function () {
			$(document).on(
				'focus',
				'.ur_pro_fields_wrapper .column-form-fields select',
				function () {
					UR_PRO.replaceFormFields($(this));
				}
			);
		},

		replaceFormFields: function ($targetField) {
			var form_fields = {};
			$('.ur-grid-lists .ur-selected-item .ur-admin-template').each(
				function () {
					var field_label = $(this)
						.closest('.ur-selected-item')
						.find(' .ur-admin-template .ur-label label')
						.text();
					var field_key = $(this)
						.closest('.ur-selected-item')
						.find(' .ur-admin-template .ur-field')
						.data('field-key');
					var field_name = $(this)
						.parent()
						.find('.ur-general-setting-block')
						.find("input[data-field='field_name'")
						.val();
					var key = field_name + '.' + field_key;

					//strip certain fields
					if (
						'section_title' == field_key ||
						'html' == field_key ||
						'wysiwyg' == field_key ||
						'billing_address_title' == field_key ||
						'shipping_address_title' == field_key ||
						'stripe_gateway' == field_key ||
						'profile_picture' == field_key ||
						'file' == field_key ||
						'user_confirm_password' == field_key ||
						'user_pass' == field_key ||
						'signature' == field_key
					) {
						return;
					}

					if (typeof field_key !== 'undefined') {
						form_fields[key] = field_label;
					}
				}
			);

			var selected_value = $targetField.val();
			$targetField.empty();
			$targetField.html('<option value>Ignore this field</option>');
			$.each(form_fields, function (key, value) {
				var field_name = key.substring(0, key.indexOf('.'));
				var field_key = key.slice(key.indexOf('.') + 1);
				$targetField.append(
					$(
						"<option data-type='" +
							field_key +
							"' data-lable='" +
							value +
							"'></option>"
					)
						.attr('value', field_name)
						.text(value)
				);
			});
			$targetField
				.find("option[value='" + selected_value + "']")
				.attr('selected', 'selected');
			$targetField.on('change', function () {
				selectedOption = $(this).val();
				UR_PRO.replaceFormFields($(this));
			});
			return form_fields;
		},
		init_hide_show_redirection_settings: function () {
			this.hide_show_redirection_options();
			$('#user_registration_form_setting_redirect_after_registration').on(
				'change',
				this.hide_show_redirection_options
			);

			this.hide_show_redirection_messages();
			$(
				'#user_registration_form_setting_redirect_after_registration, select[data-id="user_registration_form_setting_login_options"], input[data-id="user_registration_enable_paypal_standard"], input[data-id="user_registration_form_setting_enable_conditional_redirection"]'
			).on('change', this.hide_show_redirection_messages);
		},

		/**
		 * Hide or Show Redirection settings.
		 */
		hide_show_redirection_options: function () {
			var redirect_after_registration = $(
				'#user_registration_form_setting_redirect_after_registration'
			);
			var selected_redirection_option =
				redirect_after_registration.find(':selected');
			var role_based_redirection = $(
				'#user_registration_form_setting_role_based_redirection_field'
			).slideUp(800);

			if (redirect_after_registration.length) {
				if ('role-based-redirection' === selected_redirection_option.val()) {
					role_based_redirection.slideDown(800);
				}
			}
		},

		hide_show_redirection_messages: function () {
			var redirect_after_registration = $(
				'#user_registration_form_setting_redirect_after_registration'
			);
			var login_option = $(
				'select[data-id="user_registration_form_setting_login_options"]'
			);
			var paypal_enabled = $(
				'input[data-id="user_registration_enable_paypal_standard"]'
			).is(':checked');
			var conditional_redirection = $(
				'input[data-id="user_registration_form_setting_enable_conditional_redirection"]'
			);

			var conditional_redirection_enabled = false;
			if (conditional_redirection.length) {
				conditional_redirection_enabled =
					conditional_redirection.is(':checked');
			}

			if (
				('no-redirection' !== redirect_after_registration.val() ||
					conditional_redirection_enabled) &&
				paypal_enabled
			) {
				if (
					!redirect_after_registration
						.parent()
						.find('#user-registration-redirection-message').length
				) {
					var message = $(
						'<div class="user-registration-form-setting-info" id="user-registration-redirection-message">Warning: Redirection options will not work with Paypal payment enabled.</div>'
					);
					redirect_after_registration.before(message);
				}
			} else {
				var existing_message = redirect_after_registration
					.parent()
					.find('#user-registration-redirection-message');
				if (existing_message.length) {
					existing_message.remove();
				}
			}
		},

		/**
		 * Hide and show role based redirection settings.
		 */
		init_hide_show_role_based_redirection_settings: function () {
			var redirection_setting_table = $(
				'#user_registration_pro_role_based_redirection'
			)
				.closest('.user-registration-global-settings')
				.next();
			if (
				$('#user_registration_pro_role_based_redirection').prop('checked') ===
				true
			) {
				$(redirection_setting_table).show();
			}

			$('#user_registration_pro_role_based_redirection').on(
				'change',
				function () {
					$(redirection_setting_table).toggle();
				}
			);
		},
		/**
		 * Initialize email/ip blocking form settings in form builder.
		 */
		initalize_email_blocking_settings: function () {
			$(document).ready(function () {
				var enable_email_blocking = $(
						'#user_registration_form_setting_email_blocking'
					),
					email_blacklist = $(
						'#user_registration_form_setting_email_black_list_field'
					);

				UR_PRO.settings_fields_toggler(enable_email_blocking, email_blacklist);
				// Check if enable search form is checked to hide/show search criteria settings div.
				enable_email_blocking.on('change', function () {
					UR_PRO.settings_fields_toggler($(this), email_blacklist);
				});
			});
		},
		/**
		 * List of target field for slot booking when new date or timepicker field is added.
		 *
		 * @since 4.1.0
		 */
		init_slot_booking: function () {
			//Date target field list.
			$(document).on(
				'focus',
				'.ur_advance_setting.ur-settings-date-target_field',
				function () {
					var selectedItems = $('.ur-selected-inputs').find(
						'.ur-selected-item .ur-general-setting-date'
					);
					UR_PRO.slotbooking_target_field_list($(this), selectedItems);
				}
			);
		},
		slotbooking_target_field_list: function ($this, selectedItems) {
			var html = '<option value=""> -- Select target field -- </option>';
			$.each(selectedItems, function (index, element) {
				var content = $(element).find('.ur-toggle-content');
				var label = $(content).find('.ur-general-setting-label input').val();
				var fieldName = $(content)
					.find('.ur-general-setting-field-name input')
					.val();

				html += '<option value=' + fieldName + '>' + label + '</option>';
			});
			$($this).html(html);

			var activeItem = $('.ur-registered-from').find('.ur-item-active'),
				advaceSetting = activeItem.find('.ur-advance-setting-block'),
				targetFieldWrapper = advaceSetting.find(
					'.ur-advance-target_date_field'
				),
				targetField = targetFieldWrapper.find('.ur-settings-date-target_field');
			targetField.html(html);
		},
		image_choice_uploader: function () {
			// on upload button click
			$('body').on('click', '.ur-media-btn', function (event) {
				event.preventDefault();
				var button = $(this);

				var imageUploader = wp
					.media({
						title: 'Upload image',
						library: {
							type: 'image'
						},
						button: {
							text: 'Use this image'
						},
						multiple: false
					})
					.on('select', function () {
						var attachment = imageUploader
							.state()
							.get('selection')
							.first()
							.toJSON();
						if (Object.keys(attachment).length) {
							button
								.closest('li')
								.find('.ur-thumbnail-image img')
								.attr('src', attachment.url);
							button
								.closest('li')
								.find('input.ur-type-image-choice')
								.val(attachment.url);
							var wrapper = $('.ur-selected-item.ur-item-active');
							var index = button.closest('li').index();
							wrapper
								.find(
									'.ur-general-setting-block li:nth(' +
										index +
										') input[type="hidden"][data-field-name="image-choice"]'
								)
								.val(attachment.url);
							wrapper
								.find(
									'.ur-general-setting-block li:nth(' +
										index +
										') input[type="hidden"][data-field-name="multiple_choice"]'
								)
								.val(attachment.url);
							wrapper
								.find(
									'.ur-admin-template .ur-field label:nth(' + index + ') img'
								)
								.attr('src', attachment.url);
							wrapper
								.find(
									'.ur-general-setting-block li:nth(' +
										index +
										') .ur-thumbnail-image img'
								)
								.attr('src', attachment.url);
							wrapper
								.find(
									'.ur-general-setting-block li:nth(' +
										index +
										') .ur-image-choice-wrapper .ur-remove-btn'
								)
								.show();
							button
								.closest('.ur-image-choice-wrapper')
								.find('.ur-remove-btn')
								.show();
							button.hide();
						}
					});

				imageUploader.open();
			});
			// on remove button click
			$('body').on('click', '.ur-remove-btn', function (event) {
				event.preventDefault();
				var button = $(this);
				button
					.closest('.ur-image-choice-wrapper')
					.find('.ur-type-image-choice')
					.val('');
				var wrapper = $('.ur-selected-item.ur-item-active');
				var index = button.closest('li').index();
				wrapper
					.find(
						'.ur-general-setting-block li:nth(' +
							index +
							') input[type="hidden"][data-field-name="image-choice"]'
					)
					.val('');
				wrapper
					.find('.ur-admin-template .ur-field label:nth(' + index + ') img')
					.attr('src', user_registration_pro_admin_script_data.ur_placeholder);
				button
					.closest('.ur-image-choice-wrapper')
					.find('.ur-thumbnail-image img')
					.attr('src', '');
				button.closest('.ur-image-choice-wrapper').find('.ur-media-btn').show();
				button
					.closest('.ur-image-choice-wrapper')
					.find('.ur-remove-btn')
					.hide();
			});
		},
		trigger_image_captcha_settings: function () {
			$(document).on('keyup', '.ur-type-captcha-icon-tag', function () {
				if (
					$(this)
						.closest('.ur-general-setting')
						.hasClass('ur-general-setting-image-captcha-options')
				) {
					UR_PRO.render_image_captcha_tag($(this));
				}
			});
			$(document).on('change', '.ur-captcha-icon-radio', function () {
				if (
					$(this)
						.closest('.ur-general-setting')
						.hasClass('ur-general-setting-image-captcha-options')
				) {
					UR_PRO.render_image_captcha_radio($(this));
				}
			});
		},
		disable_users: function () {
			$('body').on('click', '.disable-user-link', function () {
				var $user_id = $(this).attr('id').split('-').pop();
				var nonce = $(this).data('nonce');
				var icon = '<i class="dashicons dashicons-warning"></i>';

				var disable_user_content =
					'<span>' +
					user_registration_pro_admin_script_data.disable_user_popup_content +
					'</span>';
				disable_user_content +=
					'<form id="disable-user-form-' +
					$user_id +
					'" class="disable-users-form" style="text-align:center">';
				disable_user_content +=
					'<input type="hidden" name="action" value="user_registration_pro_disable_user">';
				disable_user_content +=
					'<input type="hidden" name="user_id" value="' + $user_id + '">';
				disable_user_content +=
					'<input type="hidden" name="_wpnonce" value="' + nonce + '">';
				disable_user_content +=
					'<input type="number" name="duration_value" min="1" placeholder="' +
					user_registration_pro_admin_script_data.disable_user_placeholder +
					'" style="margin-right:10px;">';
				disable_user_content +=
					'<select name="duration_unit" style="margin-right:10px;"><option value="days">Day(s)</option><option value="weeks">Week(s)</option><option value="months">Month(s)</option><option value="years">Year(s)</option></select>';
				disable_user_content += '</form>';

				swal.fire({
					title:
						icon +
						'<span class="user-registration-swal2-modal__title" >' +
						user_registration_pro_admin_script_data.disable_user_title +
						'</span>',
					html: disable_user_content,
					confirmButtonText: user_registration_pro_admin_script_data.disable,
					confirmButtonColor: '#3085d6',
					showConfirmButton: true,
					showCancelButton: true,
					cancelButtonText: user_registration_pro_admin_script_data.cancel,
					customClass: {
						container: 'user-registration-swal2-container'
					},
					customClass:
						'user-registration-swal2-modal user-registration-swal2-modal--centered',
					focusConfirm: false,
					showLoaderOnConfirm: true,
					preConfirm: function () {
						return new Promise(function (resolve) {
							var duration_value = Swal.getPopup().querySelector(
								'input[name="duration_value"]'
							).value;
							var duration_unit = Swal.getPopup().querySelector(
								'select[name="duration_unit"]'
							).value;

							if (!duration_value || !duration_unit) {
								Swal.showValidationMessage(
									'Please enter duration value and unit.'
								);
								Swal.hideLoading();
								$('.swal2-actions').find('button').prop('disabled', false);
							} else {
								$.ajax({
									type: 'get',
									url: user_registration_pro_admin_script_data.ajax_url,
									data: {
										action: 'user_registration_pro_disable_user',
										user_id: $user_id,
										nonce: nonce,
										duration_value: duration_value,
										duration_unit: duration_unit
									},
									success: function (response) {
										if (response.success) {
											Swal.fire({
												icon: 'success',
												title:
													'<span class="user-registration-swal2-modal__title" >' +
													user_registration_pro_admin_script_data.disable_user_success_message_title +
													'</span>',
												customClass:
													'user-registration-swal2-modal user-registration-swal2-modal--centered',
												html: user_registration_pro_admin_script_data.disable_user_success_message
											}).then(function () {
												window.location.href = user_registration_pro_admin_script_data.after_disable_redirect_url;
											});
										} else {
											Swal.fire({
												icon: 'error',
												title:
													'<span class="user-registration-swal2-modal__title" >' +
													user_registration_pro_admin_script_data.disable_user_error_message_title +
													'</span>',
												customClass:
													'user-registration-swal2-modal user-registration-swal2-modal--centered',
												html:
													response.data.message +
													' ' +
													user_registration_pro_admin_script_data.disable_user_error_message
											});
										}
									},
									error: function (response) {
										Swal.fire({
											icon: 'error',
											title:
												'<span class="user-registration-swal2-modal__title" >' +
												user_registration_pro_admin_script_data.disable_user_error_message_title +
												'</span>',
											customClass:
												'user-registration-swal2-modal user-registration-swal2-modal--centered',
											html:
												response.data.message +
												' ' +
												user_registration_pro_admin_script_data.disable_user_error_message
										});
									}
								});
								Swal.close();
							}
						});
					}
				});
			});
		},
		misc_settings: function () {
			function hide_user_schedular_settings() {
				$('#user_registration_delete_user_schedular_duration').closest('.user-registration-global-settings').hide();
				$('#user_registration_delete_user_schedular_status').closest('.user-registration-global-settings').hide();
				$('#user_registration_delete_user_schedular_roles').closest('.user-registration-global-settings').hide();
			}

			function show_user_schedular_settings() {
				$('#user_registration_delete_user_schedular_duration').closest('.user-registration-global-settings').show();
				$('#user_registration_delete_user_schedular_status').closest('.user-registration-global-settings').show();
				$('#user_registration_delete_user_schedular_roles').closest('.user-registration-global-settings').show();
			}

			// Initial Check
			if ($('#user_registration_enable_delete_user_schedular').is(':checked')) {
				show_user_schedular_settings();
			} else {
				hide_user_schedular_settings();
			}

			$(document).on('change', '#user_registration_enable_delete_user_schedular', function () {
				$(this).is(':checked') ? show_user_schedular_settings() : hide_user_schedular_settings();
			});
		},
	};

	if (!$('#user_registration_enable_privacy_tab').is(':checked')) {
		$('.privacy-tab-settings')
			.closest('.user-registration-global-settings')
			.hide();
	}

	$('#user_registration_enable_privacy_tab').on('change', function () {
		$('.privacy-tab-settings')
			.closest('.user-registration-global-settings')
			.toggle();
	});

	if ($('#user_registration_auto_logout_inactivity_time').val() == '') {
		$('#user_registration_timeout_countdown_inactive_period')
			.parent()
			.parent()
			.hide();
		$('#user_registration_role_based_inactivity').parent().parent().hide();
	}

	//Real-time changes in autlogout inactivity time.
	$(document).on(
		'change',
		'#user_registration_auto_logout_inactivity_time',
		function () {
			$('#user_registration_timeout_countdown_inactive_period')
				.parent()
				.parent()
				.show();
			$('#user_registration_role_based_inactivity').parent().parent().show();

			if ($(this).val() == '') {
				$('#user_registration_timeout_countdown_inactive_period')
					.parent()
					.parent()
					.hide();
				$('#user_registration_role_based_inactivity').parent().parent().hide();
			}
		}
	);

	// Hide Sharing on Profile Update Setting if Custom URL is not set
	if (
		$('#user_registration_pro_general_post_submission_settings').val() == ''
	) {
		$('#user_registration_pro_general_post_submission_profile_update')
			.closest('div.user-registration-global-settings')
			.hide();
	}

	$('#user_registration_pro_general_post_submission_settings').on(
		'keyup',
		function () {
			if (
				$('#user_registration_pro_general_post_submission_settings').val() == ''
			) {
				$('#user_registration_pro_general_post_submission_profile_update')
					.closest('div.user-registration-global-settings')
					.hide();
			} else {
				$('#user_registration_pro_general_post_submission_profile_update')
					.closest('div.user-registration-global-settings')
					.show();
			}
		}
	);
	$(document).ready(function () {
		UR_PRO.init();
	});
});
