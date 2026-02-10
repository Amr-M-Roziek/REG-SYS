<?php
/**
 * URFrontendListing Admin Metabox Class
 *
 * @class    Metabox
 * @version  1.0.0
 * @package  URFrontendListing/Admin/Settings
 * @category Admin
 * @author   WPEverest
 */

namespace WPEverest\URFrontendListing\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Metabox Class.
 */
class Metabox extends \UR_Meta_Boxes {
	/**
	 * Meta Box ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Nonce for saving frontend_listing post metas.
	 *
	 * @var bool
	 */
	private $listings_nonce = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id = 'frontend_listing';

		if ( is_admin() ) {
			add_action( 'load-post.php', array( $this, 'init_metabox' ) );
			add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_styles' ) );
		}
	}

	/**
	 * Register and Enqueue scripts for metaboxes.
	 */
	public function register_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'ur_frontend_listings' === $screen_id ) {
			// enqueue scripts here.
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'user-registration-frontend-listing-admin', UR_ASSET_PATH . 'js/pro/admin/user-registration-frontend-listing-admin' . $suffix . '.js', array( 'jquery', 'selectWoo', 'jquery-ui-sortable' ), UR_VERSION );

			wp_localize_script(
				'user-registration-frontend-listing-admin',
				'user_registration_frontend_listing_admin_script_data',
				array(
					'ur_frontend_listing_advanced_filter_options' => ur_frontend_listing_advanced_filter(),
					'ajax_url' => admin_url( 'admin-ajax.php' ),

				)
			);
		}
	}

	/**
	 * Register and Enqueue styles for metaboxes.
	 */
	public function register_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'ur_frontend_listings' === $screen_id ) {
			// enqueue styles here.
			wp_enqueue_style( 'selectWoo' );
			wp_enqueue_style( 'user-registration-frontend-listing-admin-css', UR_ASSET_PATH . 'css/user-registration-frontend-listing-admin.css', array(), UR_VERSION );
		}
	}

	/**
	 * Meta box initialization.
	 */
	public function init_metabox() {
		global $current_screen;

		if ( 'ur_frontend_listings' === $current_screen->id ) {
			add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
			add_action( 'save_post', array( $this, 'save_metabox' ), 10, 2 );
		}
	}

	/**
	 * Adds the meta box.
	 */
	public function add_metabox() {

		add_meta_box(
			'user_registration_frontend_listing_general',
			__(
				'General Settings',
				'user-registration-frontend-listing'
			),
			array( $this, 'urfl_render_general_metabox' ),
			'ur_frontend_listings',
			'normal',
			'default'
		);

		add_meta_box(
			'user_registration_frontend_listing_filter',
			__(
				'Filter Settings',
				'user-registration-frontend-listing'
			),
			array( $this, 'urfl_render_filter_metabox' ),
			'ur_frontend_listings',
			'normal',
			'default'
		);

		add_meta_box(
			'user_registration_frontend_listing_search',
			__(
				'Search Settings',
				'user-registration-frontend-listing'
			),
			array( $this, 'urfl_render_search_metabox' ),
			'ur_frontend_listings',
			'normal',
			'default'
		);

		add_meta_box(
			'user_registration_frontend_listing_pagination',
			__( 'Pagination Settings', 'user-registration-frontend-listing' ),
			array( $this, 'urfl_render_pagination_metabox' ),
			'ur_frontend_listings',
			'normal',
			'default'
		);

		add_meta_box(
			'user_registration_frontend_listing_shortcode',
			__( 'Shortcode', 'user-registration-frontend-listing' ),
			array( $this, 'urfl_render_shortcode_metabox' ),
			'ur_frontend_listings',
			'side',
			'default'
		);

		do_action( 'render_metabox_complete' );
	}

	/**
	 * Renders the general settings meta box.
	 *
	 * @param object $post Frontend Listings Post.
	 */
	public function urfl_render_general_metabox( $post ) {

		if ( ! $this->listings_nonce ) {
			$this->listings_nonce = true;
			wp_nonce_field( 'ur_frontend_listings_id-' . $post->ID, 'ur_frontend_listings_nonce', false );
		}

		$this->ur_metabox_select(
			array(
				'id'      => 'user_registration_frontend_listings_layout',
				'label'   => __( 'Listings Layout', 'user-registration-frontend-listing' ),
				'class'   => 'ur-enhanced-select',
				'options' => array( 'Grid', 'Lists' ),
				'desc'    => __( 'Select the layout of the frontend lister', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_ur_only',
				'label' => __( 'Display only user registered through UR', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'This option enables you to list only users registered through User Registration Plugin.', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_multiple_select(
			array(
				'id'      => 'user_registration_frontend_listings_ur_forms[]',
				'label'   => __( 'Choose forms to display registered users', 'user-registration-frontend-listing' ),
				'class'   => 'ur-enhanced-select ur-select2-multiple',
				'options' => ur_get_all_user_registration_form(),
				'desc'    => __( 'Select all the details you want to display in profile card', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_allow_guest',
				'label' => __( 'Allow Access To Guest', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'Only select this if you want to allow guest users to be able to see the listing page', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_input(
			array(
				'id'    => 'user_registration_frontend_listings_access_denied_text',
				'label' => __( 'Access denied text', 'user-registration-frontend-listing' ),
				'value' => __( 'Please login to view this page.', 'user-registration-frontend-listing' ),
				'desc'  => __( 'Message to display when user with no access tries to view this page', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_display_profile_picture',
				'label' => __( 'Display Profile Picture', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'This option will display a profile picture list card', 'user-registration-frontend-listing' ),
			)
		);
	}

	/**
	 * Renders the filter settings meta box.
	 *
	 * @param object $post Frontend Listings Post.
	 */
	public function urfl_render_filter_metabox( $post ) {

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_sort_by',
				'label' => __( 'Display Sorter', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'Only select this if you want to allow users to be able to sort users', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_select(
			array(
				'id'      => 'user_registration_frontend_listings_default_sorter',
				'label'   => __( 'Default user sorter', 'user-registration-frontend-listing' ),
				'class'   => 'ur-enhanced-select',
				'options' => ur_frontend_listing_sort_filter(),
				'desc'    => __( 'Set default sorter for sorting results', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_multiple_select(
			array(
				'id'      => 'user_registration_frontend_listings_role_restriction[]',
				'label'   => __( 'Restrict By Role', 'user-registration-frontend-listing' ),
				'class'   => 'ur-select2-multiple ur-select2-multiple',
				'options' => ur_get_default_admin_roles(),
				'desc'    => __( 'Select the roles of users which you don\'t want to display in list', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_view_profile',
				'label' => __( 'Enable view profile', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'This option will display a view profile button in list', 'user-registration-frontend-listing' ),
			)
		);

		$this->urfl_metabox_multiple_select(
			array(
				'id'      => 'user_registration_frontend_listings_card_fields[]',
				'label'   => __( 'Details to display in View Profile page ', 'user-registration-frontend-listing' ),
				'class'   => 'ur-enhanced-select ur-select2-multiple',
				'options' => ur_frontend_listing_include_fields_in_view_profile(),
				'desc'    => __( 'Select all the details you want to display in view profile card', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_input(
			array(
				'id'    => 'user_registration_frontend_listings_view_profile_button_text',
				'label' => __( 'View profile button text', 'user-registration-frontend-listing' ),
				'value' => __( 'VIEW PROFILE', 'user-registration-frontend-listing' ),
				'desc'  => __( 'Text to display in View Profile button', 'user-registration-frontend-listing' ),
			)
		);

		$this->urfl_metabox_multiple_select(
			array(
				'id'      => 'user_registration_frontend_listings_lists_fields[]',
				'label'   => __( 'Details to display in Cards', 'user-registration-frontend-listing' ),
				'class'   => 'ur-select2-multiple',
				'options' => ur_frontend_listing_include_fields_in_view_profile(),
				'desc'    => __( 'Select all the details you want to display in list card', 'user-registration-frontend-listing' ),
			)
		);
	}

	/**
	 * Renders the search settings meta box.
	 *
	 * @param object $post Frontend Listings Post.
	 */
	public function urfl_render_search_metabox( $post ) {

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_search_form',
				'label' => __( 'Display Search Form', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'Only select this if you want to allow users to be able to search users', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_multiple_select(
			array(
				'id'      => 'user_registration_frontend_listings_search_fields[]',
				'label'   => __( 'Search User According To', 'user-registration-frontend-listing' ),
				'class'   => 'ur-enhanced-select ur-select2-multiple',
				'options' => ur_frontend_listing_user_search_fields(),
				'desc'    => __( 'Select the details you want user to be able to search in lists', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_advanced_filter',
				'label' => __( 'Display Advanced Filter', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'Enable this option to allow users to filter user lists according to specific user datas', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_custom_field(
			array(
				'id'      => 'user_registration_frontend_listings_advanced_filter_fields',
				'label'   => __( 'Advanced Filter', 'user-registration-frontend-listing' ),
				'type'    => 'text',
				'value'   => '',
				'options' => ur_frontend_listing_advanced_filter(),
				'desc'    => __( 'Select a lists of fields that will allow users to filter user lists according to specific user datas.', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_input(
			array(
				'id'    => 'user_registration_frontend_listing_search_placeholder_text',
				'label' => __( 'Search box placeholder text', 'user-registration-frontend-listing' ),
				'value' => __( 'Enter something to search.', 'user-registration-frontend-listing' ),
				'desc'  => __( 'Placeholder text to display in search box', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_input(
			array(
				'id'    => 'user_registration_frontend_listings_search_button_text',
				'label' => __( 'Search button text', 'user-registration-frontend-listing' ),
				'value' => __( 'SEARCH', 'user-registration-frontend-listing' ),
				'desc'  => __( 'Text to display in Search button', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_input(
			array(
				'id'    => 'user_registration_frontend_listings_no_users_found_text',
				'label' => __( 'Info Text', 'user-registration-frontend-listing' ),
				'value' => __( 'Sorry, No users are found.', 'user-registration-frontend-listing' ),
				'desc'  => __( 'Message to display amount of users displayed out of total users', 'user-registration-frontend-listing' ),
			)
		);
	}



	/**
	 * Renders the filter settings meta box.
	 *
	 * @param object $post Frontend Listings Post.
	 */
	public function urfl_render_pagination_metabox( $post ) {

		$this->ur_metabox_toggle(
			array(
				'id'    => 'user_registration_frontend_listings_amount_filter',
				'label' => __( 'Display Amount Filter', 'user-registration-frontend-listing' ),
				'type'  => 'Checkbox',
				'desc'  => __( 'Only select this if you want to allow users to be able to use amount filters', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_select(
			array(
				'id'      => 'user_registration_frontend_listings_default_page_filter',
				'label'   => __( 'Default number of profiles per page', 'user-registration-frontend-listing' ),
				'class'   => 'ur-enhanced-select',
				'options' => ur_frontend_listing_amount_filter(),
				'desc'    => __( 'Set default number of profiles per page', 'user-registration-frontend-listing' ),
			)
		);

		$this->ur_metabox_input(
			array(
				'id'    => 'user_registration_frontend_listings_filtered_user_message',
				'label' => __( 'Number of profiles per page', 'user-registration-frontend-listing' ),
				'value' => __( 'Showing %qty% out of %total% users . ', 'user-registration-frontend-listing' ),
				'desc'  => __( 'Message to display amount of users displayed out of total users', 'user-registration-frontend-listing' ),
			)
		);
	}

	/**
	 * Renders the filter settings meta box.
	 *
	 * @param object $post Frontend Listings Post.
	 */
	public function urfl_render_shortcode_metabox( $post ) {

		$shortcode = '[user_registration_frontend_list id="' . $post->ID . '"]';
		echo '<p class="description">Current frontend listing post\'s shortcode</p>';
		printf( '<input type="text" id="user_registration_frontend_listings_shortcode" name="user_registration_frontend_listings_shortcode" onfocus="this.select();" readonly="readonly" value=\'%s\' class="widefat code" disabled="disabled"></span>', esc_attr( $shortcode ) );

		?>
		<button id="copy-shortcode" class="button ur-copy-shortcode " href="#" data-tip="<?php esc_attr_e( 'Copy Shortcode ! ', 'user-registration-frontend-listing' ); ?>" data-copied="<?php esc_attr_e( 'Copied ! ', 'user-registration-frontend-listing' ); ?>">
			<span class="dashicons dashicons-admin-page"></span>
		</button>
		<?php
	}

	/**
	 * Handles saving the meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return null
	 */
	public function save_metabox( $post_id, $post ) {

		if ( empty( $_POST ) ) {
			return false;
		}

		$nonce = isset( $_POST['ur_frontend_listings_nonce'] ) ? $_POST['ur_frontend_listings_nonce'] : '';

		// validate nonce.
		if ( ! wp_verify_nonce( $nonce, 'ur_frontend_listings_id-' . $post_id ) ) {
			return;
		}

		// validate post type.
		if ( 'ur_frontend_listings' !== $post->post_type ) {
			return;
		}

		// Check if user has permissions to save data.
		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return;
		}

		$field_lists = ur_frontend_listings_metabox_field_ids();

		foreach ( $field_lists as $v ) {
			$value = '';

			if ( isset( $_POST[ $v ] ) ) {
				$value = wp_unslash( $_POST[ $v ] );
			}
			update_post_meta( $post_id, $v, $value );
		}

		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['custom_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_nonce'] ) ) : '';
		$nonce_action = 'custom_nonce_action';

		// Check if nonce is set.
		if ( ! isset( $nonce_name ) ) {
			return;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
	}

	/**
	 * Renders the Add field in metabox.
	 *
	 * @since 1.1.0
	 * @param array $field Metabox Field.
	 */
	public function ur_metabox_custom_field( $field ) {

		global $thepostid, $post;

		$get_meta_data = get_post_meta( $post->ID, $field['id'], true );

		$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'urfl-input';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['value']         = ( isset( $get_meta_data ) && '' !== $get_meta_data ) ? $get_meta_data : $field['value'];
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['desc']          = isset( $field['desc'] ) ? $field['desc'] : '';

		echo '<div class="ur-metabox-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">';
		echo '<div class="ur-metabox-field-row">';
		echo '<div class="ur-metabox-field-label">';
		echo '<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';
		echo ur_help_tip( $field['desc'] );
		echo '</div>';
		echo '<div class="ur-metabox-field-detail">';

		// Create a select field wrapper.
		$field_wrapper = '<select id="user_registration_frontend_listings_advanced_filter_fields_selector" class="ur-select">';

		foreach ( $field['options'] as $option_key => $option_value ) {
			$field_wrapper .= '<option value="' . esc_attr( $option_key ) . '" >' . esc_html( $option_value ) . '</option>';
		}
		$field_wrapper .= '</select>';
		$field_wrapper .= '<div class="user_registration_frontend_listings_advanced_filter_fields_list">';
		if ( ! empty( $field['value'] ) ) {
			$field_value = (array) json_decode( $field['value'] );
			$count       = 1;

			foreach ( $field_value as $key => $value ) {
				$field_wrapper .= '<div class="user_registration_frontend_listings_advanced_filter_fields_container selected-options">';
				$field_wrapper .= '<div class="ur-draggable-option">';
				$field_wrapper .= '<div class="selected-option-container">';
				$field_wrapper .= '<svg width="12" height="20" viewBox="0 0 12 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<rect width="4" height="4" fill="#4C5477"></rect>
									<rect x="8" width="4" height="4" fill="#4C5477"></rect>
									<rect y="16" width="4" height="4" fill="#4C5477"></rect>
									<rect x="8" y="16" width="4" height="4" fill="#4C5477"></rect>
									<rect y="8" width="4" height="4" fill="#4C5477"></rect>
									<rect x="8" y="8" width="4" height="4" fill="#4C5477"></rect>
								</svg>';

				if ( isset( $field['options'][ $key ] ) ) {
					// Create a select field wrapper.
					$field_wrapper .= '<select id="user_registration_frontend_listings_advanced_filter_fields_' . $count . '" class="user_registration_frontend_listings_advanced_filter_fields_map ur-selected">';

					foreach ( $field['options'] as $option_key => $option_value ) {
						$field_wrapper .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $key === $option_key, true, false ) . '>' . esc_html( $option_value ) . '</option>';

					}
					$field_wrapper .= '</select>';
				} else {
					// Create a Meta Key and Field Label input field pair.
					$field_wrapper .= '<div class="user_registration_frontend_listings_advanced_filter_fields_map ur-input-group" id="user_registration_frontend_listings_advanced_filter_fields_' . $count . '">';
					$field_wrapper .= '<input type="text" id="user_registration_frontend_listings_advanced_filter_fields_' . $count . '_meta_label" class="user-registration-frontend-listing-advance-filter-meta-mapper ur-select custom-input" placeholder="Field Label" value="' . esc_attr( $value ) . '">';
					$field_wrapper .= '<input type="text" id="user_registration_frontend_listings_advanced_filter_fields_' . $count . '_meta_key" class="user-registration-frontend-listing-advance-filter-meta-mapper ur-select custom-input" placeholder="Meta Key( eg. meta_key_123, meta_key_456 )" value="' . esc_attr( $key ) . '">';
					$field_wrapper .= '</div>';
				}

				$field_wrapper .= '</div>';
				$field_wrapper .= '<span class="user_registration_frontend_listings_advanced_filter_fields_remove delete-option">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="feather feather-trash-2" viewBox="0 0 24 24">
											<path d="M3 6h18m-2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-6 5v6m4-6v6"></path>
										</svg>
									</span>';
				$field_wrapper .= '</div>';
				$field_wrapper .= '</div>';

				++$count;
			}
		}
		echo $field_wrapper; // phpcs:ignore

		echo '</div>';
		echo '<input type="hidden" id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" value="' . esc_attr( $field['value'] ) . '">';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Renders the Multiple Select field in metabox.
	 *
	 * @param array $field Metabox Field.
	 * @since 1.1.0
	 */
	public function urfl_metabox_multiple_select( $field ) {

		global $thepostid, $post;

		$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'multiple-select';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['desc']          = isset( $field['desc'] ) ? $field['desc'] : '';

		$get_meta_data = get_post_meta( $post->ID, chop( $field['id'], '[]' ), true );
		$all_forms     = ur_get_all_user_registration_form();
		$form_ids      = get_post_meta( $post->ID, 'user_registration_frontend_listings_ur_forms', $single = true );
		if ( ! empty( $form_ids ) ) {
			$selected_forms = array_intersect_key( $all_forms, array_flip( $form_ids ) );
			array_push( $selected_forms, 'Basic Details' );
		}

		echo '<div class="ur-metabox-field ' . esc_attr( chop( $field['id'], '[]' ) ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">';
		echo '<div class="ur-metabox-field-row">';
		echo '<div class="ur-metabox-field-label">';
		echo '<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';
		echo wp_kses_post( ur_help_tip( $field['desc'] ) );
		echo '</div>';
		echo '<div class="ur-metabox-field-detail">';
		echo '<select multiple id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" >';

		foreach ( $field['options'] as $key => $value ) {

			if ( ! empty( $form_ids ) && ! in_array( $value['form_label'], $selected_forms, true ) ) {
				continue;
			}

			if ( isset( $value['field_list'] ) ) {
				?>
				<optgroup label="<?php echo esc_attr( $value['form_label'] ); ?>">
					<?php
					foreach ( $value['field_list'] as $field_key => $field_label ) {
						?>
							<option value="<?php echo esc_attr( $field_key ); ?>"
								<?php
								if ( is_array( $get_meta_data ) ) {
									if ( isset( $value['field_key'] ) && in_array( $value['field_key'][ $field_key ], $get_meta_data ) ) {
										selected( in_array( $value['field_key'][ $field_key ], $get_meta_data ), true );
									} else {
										selected( in_array( $field_key, $get_meta_data ), true );
									}
								} else {
									selected( $get_meta_data, $field_key );
								}

								?>
								><?php echo esc_html( $field_label ); ?></option>
						<?php
					}
					?>
				</optgroup>
				<?php
			}
		}

		echo '</select> ';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
}

new Metabox();
