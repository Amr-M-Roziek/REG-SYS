<div class="ur-membership-header ur-d-flex ur-mr-0 ur-p-3 ur-align-items-center" id=""
	 style="gap: 20px">
	<img style="max-width: 30px"
		 src="<?php echo UR()->plugin_url() . '/assets/images/logo.svg'; ?>" alt="">

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->page ) ); ?>"
	   class="<?php echo esc_attr( ( $_GET['page'] == $this->page ) ? 'row-title' : '' ); ?>"
	   style="text-decoration: none"
	>
		<?php esc_html_e( 'Coupons', 'user-registration' ); ?>
	</a>

</div>
