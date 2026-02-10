<?php
require __DIR__ . '/header.php';

?>
<div class="ur-coupon-tab-contents-wrapper ur-p-8 ur-d-flex ur-align-items-center ur-justify-content-center">
	<form id="ur-coupon-create-form" method="post" style="width: 80%">
		<div class="user-registration-card">
			<div id="ur-coupon-form-container" class="ur-d-flex ur-p-4 ur-flex-column" style="gap: 20px;">
				<div id="left-title" class=" ur-d-flex ur-align-items-center">
					<a class="ur-text-muted ur-border-right ur-d-flex ur-mr-2 ur-pr-2"
					   href="<?php echo esc_attr( empty( $_SERVER['HTTP_REFERER'] ) ? '#' : $_SERVER['HTTP_REFERER'] ); ?>">
						<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2"
							 fill="none"
							 stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">
							<line x1="19" y1="12" x2="5" y2="12"></line>
							<polyline points="12 19 5 12 12 5"></polyline>
						</svg>
					</a>
					<h3>
						<?php echo isset( $_GET['post_id'] ) ? esc_html_e( 'Edit Coupon', 'user-registration' ) : esc_html_e( 'Create New Coupon', 'user-registration' ); ?>
					</h3>
				</div>
				<div id="left-body" class="">
					<!--						coupon name-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-name"><?php esc_html_e( 'Coupon Name', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>
						<div class="ur-input-type-coupon-name ur-admin-template" style="width: 100%">
							<div class="ur-field" data-field-key="coupon_name">
								<input
									class="ur-coupon-input"
									type="text"
									data-key-name="<?php echo esc_attr__( 'coupon_name', 'user-registration' ); ?>"
									id="ur-input-type-coupon-name" name="ur_coupon_name"
									style="width: 100%"
									required
									value="<?php echo ( isset( $coupon ) && ! empty( $coupon ) ) ? $coupon->post_title : ''; ?>"
								>
							</div>
						</div>
					</div>
					<!--						Coupon code-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-code"><?php esc_html_e( 'Coupon Code', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>
						<div class="ur-input-type-coupon-code ur-admin-template" style="width: 100%">
							<div class="ur-field">
								<input type="text"
									   class="ur-coupon-input"
									   data-key-name="<?php echo esc_attr__( 'coupon_code', 'user-registration-membership' ); ?>"
									   id="ur-input-type-coupon-code" name="ur_coupon_code"
									   style="width: 100%"
									   required
									   value="<?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) ) ? $coupon_details['coupon_code'] : ''; ?>"
								>
							</div>
						</div>
					</div>
					<!--						Discount-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-discount"><?php esc_html_e( 'Discount Amount/Percent', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>
						<div class="ur-input-type-coupon-discount ur-admin-template" style="width: 100%">
							<div class="ur-field">
								<input type="text"
									   class="ur-coupon-input"
									   data-key-name="<?php echo esc_attr__( 'coupon_discount', 'user-registration-membership' ); ?>"
									   id="ur-input-type-coupon-discount-type" name="ur_coupon_discount"
									   style="width: 100%"
									   min="0"
									   value="<?php echo isset( $coupon_details ) && ! empty( $coupon_details ) ? $coupon_details['coupon_discount'] : ''; ?>"
									   required>
							</div>
						</div>

					</div>

					<!--						Discount Type-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-discount-type"><?php echo esc_html_e( 'Discount Type', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>
						<div class="ur-input-type-coupon-discount-type ur-admin-template" style="width: 100%">
							<div class="ur-field ur-d-flex ur-justify-content-between"
								 data-field-key="radio" style="gap: 10px">
								<!--											discount type-->
								<div class="ur-coupon-discount-type"
									 style="border: 1px solid #e1e1e1; border-radius: 5px; background: white; padding: 10px;">
									<div class="ur-coupon-type-title ur-d-flex ur-align-items-center">
										<input
											data-key-name="<?php echo esc_attr__( 'coupon_discount_type_fixed', 'user-registration' ); ?>"
											id="ur-coupon-discount-type-fixed"
											type="radio"
											value="fixed"
											name="ur_coupon_discount_type"
											style="margin: 0"
											checked
											<?php echo isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_discount_type'] === 'fixed' ? 'checked' : ''; ?>
											required>
										<label class="ur-p-2" for="ur-coupon-discount-type-fixed">
											<b
												class="user-registration-image-label "><?php esc_html_e( 'Fixed Discount', 'user-registration' ); ?>
											</b>
										</label>
									</div>
									<div class="ur-membership-type-description">
										<p style="word-break: break-word; font-size: 12px;">
											<?php echo __( 'This involves a specific amount of money deducted from the original price of a product.', 'user-registration' ); ?>
										</p>
									</div>
								</div>
								<!--											percent based-->
								<div class="ur-coupon-discount-type"
									 style="border: 1px solid #e1e1e1; border-radius: 5px; background: white; padding: 10px;">
									<div class="ur-coupon-type-title ur-d-flex ur-align-items-center">
										<input
											data-key-name="<?php echo esc_attr__( 'coupon_discount_type_percent', 'user-registration' ); ?>"
											id="ur-coupon-discount-type-percent"
											type="radio"
											value="percent"
											name="ur_coupon_discount_type"
											style="margin: 0"
											<?php echo isset( $coupon_details['coupon_discount_type'] ) && $coupon_details['coupon_discount_type'] == 'percent' ? 'checked' : ''; ?>
											required>
										<label class="ur-p-2" for="ur-coupon-discount-type-percent">
											<b
												class="user-registration-image-label "><?php esc_html_e( 'Percent Based', 'user-registration' ); ?>
											</b>
										</label>
									</div>
									<div class="ur-membership-type-description">
										<p style="word-break: break-word; font-size: 12px;">
											<?php echo __( 'Discount is calculated as percentage of the original price of the product.', 'user-registration' ); ?>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!--						start date-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-start-date"><?php esc_html_e( 'Start Date', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>
						<div class="ur-input-type-coupon-start-date ur-admin-template" style="width: 100%">
							<div class="ur-field">
								<input
									data-key-name="<?php echo esc_attr__( 'start_date', 'user-registration' ); ?>"
									class="ur-coupon-input"
									type="date"
									id="ur-input-type-coupon-start-date" name="ur_start_date"
									style="width: 100%"
									value="<?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) ) ? $coupon_details['coupon_start_date'] : date( 'Y-m-d' ); ?>"
									required
								>
							</div>
						</div>
					</div>
					<!--						end date-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-end-date"><?php esc_html_e( 'End Date', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>
						<div class="ur-input-type-coupon-end-date ur-admin-template" style="width: 100%">
							<div class="ur-field">
								<input
									data-key-name="<?php echo esc_attr__( 'end_date', 'user-registration' ); ?>"
									class="ur-coupon-input"
									type="date"
									id="ur-input-type-coupon-end-date" name="ur_end_date"
									style="width: 100%"
									value="<?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) ) ? $coupon_details['coupon_end_date'] : date( 'Y-m-d' ); ?>"
									required>
							</div>
						</div>
					</div>
					<!--						status-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px">
						<div class="ur-label" style="width: 30%">
							<label class="ur-coupon-enable-status"
								   for="ur-coupon-status"><?php esc_html_e( 'Coupon Status', 'user-registration' ); ?></label>
						</div>
						<div class="user-registration-switch ur-ml-auto" style="width: 100%">

							<input
								data-key-name="<?php echo esc_attr__( 'coupon_status', 'user-registration-membership' ); ?>"
								id="ur-coupon-status" type="checkbox"
								class="user-registration-switch__control hide-show-check enabled ur-coupon-input"
								<?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) && true == $coupon_details['coupon_status'] ) ? 'checked' : ''; ?>
								name="ur_coupon_status"
								style="width: 100%; text-align: left">
						</div>

					</div>
					<?php
					$is_membership_active =  ur_check_module_activation('membership');
					?>
					<!--					coupon applicable for-->
					<div class="ur-coupon-input-container ur-d-flex ur-p-3" style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-for"><?php esc_html_e( 'Applicable For', 'user-registration' ); ?>
							</label>
						</div>
						<div class="ur-input-type-coupon-name ur-admin-template" style="width: 100%">
							<div class="ur-field">
								<select
									data-key-name="<?php echo esc_attr__( 'coupon_for', 'user-registration' ); ?>"
									id="ur-input-type-coupon-for"
									name="ur_coupon_for">
									<option
										value="empty"><?php echo __( 'Select an option.', 'user-registration' ); ?></option>
									<option value="form"
										<?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_for'] === 'form' ) ? 'selected="selected"' : ''; ?>>
										<?php echo __( 'Form', 'user-registration' ); ?>
									</option>
									<?php
										if($is_membership_active):
									?>
									<option value="membership"
										<?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_for'] === 'membership' ) ? 'selected="selected"' : ''; ?>>
										<?php echo __( 'Membership', 'user-registration' ); ?>
									</option>
									<?php
									endif;
									?>
								</select>
							</div>
						</div>
					</div>
					<!--						Membership-->
					<?php
					if($is_membership_active):
					?>
					<div
						class="ur-coupon-input-container coupon-hidden-select ur-p-3 <?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_for'] === 'membership' ) ? 'ur-d-flex' : 'ur-d-none'; ?> "
						data-value="membership"
						style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-membership"><?php esc_html_e( 'Applicable Membership', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>

						<div class="ur-input-type-coupon-name ur-admin-template" style="width: 100%">
							<div class="ur-field">
								<select
									data-key-name="<?php echo esc_attr__( 'coupon_membership', 'user-registration' ); ?>"
									id="ur-input-type-coupon-membership"
									name="ur_coupon_membership"
									class="coupon-enhanced-select2 ur-coupon-input"
									multiple="multiple"
								>
									<?php
									$selected_memberships = array();
									if ( isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_for'] === 'membership' ) {
										$selected_memberships = json_decode( $coupon_details['coupon_membership'], 'true' );
									}
									foreach ( $memberships as $k => $membership ) :
										?>
										<option
											value="<?php echo esc_attr( $k ); ?>" <?php echo in_array( $k, $selected_memberships ) ? 'selected' : ''; ?>>
											<?php echo esc_html( $membership ); ?>
										</option>
										<?php
									endforeach;
									?>
								</select>
							</div>
						</div>
					</div>
					<?php
					endif;
					?>
					<?php
					$selected_forms = array();
					if ( isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_for'] === 'form' ) {
						$selected_forms = json_decode( $coupon_details['coupon_form'], 'true' );
					}
					?>
					<!--					Forms-->
					<div
						class="ur-coupon-input-container coupon-hidden-select ur-p-3 <?php echo ( isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_for'] === 'form' ) ? 'ur-d-flex' : 'ur-d-none'; ?> "
						data-value="form"
						style="gap:20px;">
						<div class="ur-label" style="width: 30%">
							<label
								for="ur-input-type-coupon-form"><?php esc_html_e( 'Applicable Forms', 'user-registration' ); ?>
								<span style="color:red">*</span>
							</label>
						</div>
						<div class="ur-input-type-coupon-form ur-admin-template" style="width: 100%">
							<div class="ur-field">
								<select
									data-key-name="<?php echo esc_attr__( 'coupon_form', 'user-registration' ); ?>"
									id="ur-input-type-coupon-form"
									name="ur_coupon_form_id"
									class="coupon-enhanced-select2 ur-coupon-input"
									multiple="multiple"
								>
									<?php
									$selected_forms = array();
									if ( isset( $coupon_details ) && ! empty( $coupon_details ) && $coupon_details['coupon_for'] === 'form' ) {
										$selected_forms = json_decode( $coupon_details['coupon_form'], 'true' );
									}

									foreach ( $forms as $k => $form ) :
										?>
										<option
											value="<?php echo esc_attr( $k ); ?>" <?php echo in_array( $k, $selected_forms ) ? 'selected' : ''; ?>>
											<?php echo esc_html( $form ); ?>
										</option>
										<?php
									endforeach;
									?>
								</select>
							</div>
						</div>
					</div>

				</div>
			</div>
			<div class="submit ur-d-flex ur-justify-content-end ur-p-3" style="gap: 10px">
				<button class="button-secondary">
					<a href="<?php echo esc_attr( empty( $_SERVER['HTTP_REFERER'] ) ? '#' : $_SERVER['HTTP_REFERER'] ); ?>">
						<?php echo esc_attr__( 'Cancel', 'user-registration' ); ?>
					</a>
				</button>
				<button class="button-primary save-coupon-btn">
					<?php echo esc_html__( isset( $_REQUEST['post_id'] ) ? 'Save Coupon' : 'Create Coupon', 'user-registration' ); ?>
				</button>
			</div>
		</div>
	</form>
</div>
