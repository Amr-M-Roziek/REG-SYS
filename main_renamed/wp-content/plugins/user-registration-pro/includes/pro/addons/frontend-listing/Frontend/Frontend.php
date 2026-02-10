<?php
/**
 * URFrontendListing Frontend.
 *
 * @class    Frontend
 * @version  1.0.0
 * @package  URFrontendListing/Frontend
 * @category Frontend
 * @author   WPEverest
 */

namespace WPEverest\URFrontendListing\Frontend;

use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class
 */
class Frontend {


	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 10, 2 );
		add_filter( 'body_class', array( $this, 'ur_frontend_listing_add_body_classes' ) );
	}

	/**
	 * Enqueue scripts
	 *
	 * @since 1.0.0
	 */
	public function load_scripts() {
		// Enqueue frontend scripts here.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'user-registration-frontend-listing-frontend-script', UR_ASSET_PATH . 'js/pro/frontend/user-registration-frontend-listing-frontend' . $suffix . '.js', array( 'jquery' ), '3.5.4' );
		// Enqueue frontend styles here.
		wp_register_style( 'user-registration-frontend-listing-frontend-style', UR_ASSET_PATH . 'css/user-registration-frontend-listing-frontend.css', array(), UR_VERSION );
	}

	/**
	 * Get all users data.
	 *
	 * @param array $post_id Frontend listing post id.
	 * @param array $details User details to display in list or profile cards.
	 * @return array
	 */
	public function ur_frontend_listing_get_users_data( $post_id, $details ) {
		global $user_registration_frontend_listings_search_fields;

		$data                  = array();
		$table_fields          = ur_get_user_table_fields();
		$get_search_fields     = get_post_meta( $post_id, 'user_registration_frontend_listings_search_fields', $single = true );
		$default_amount_filter = get_post_meta( $post_id, 'user_registration_frontend_listings_default_page_filter', $single = true );
		$default_sorter        = get_post_meta( $post_id, 'user_registration_frontend_listings_default_sorter', $single = true );

		$meta_query_container = array(
			'relation' => 'AND',
		);

		$filter = array();

		$search_param = '';

		if ( ! empty( $get_search_fields ) ) {

			$general_search_fields = array();
			$meta_search_fields    = array();

			foreach ( $get_search_fields as $key => $value ) {
				if ( in_array( $value, $table_fields ) ) {
					array_push( $general_search_fields, $value );
				} else {
					array_push( $meta_search_fields, $value );
				}
			}
		}

		$all_search_fields = array();
		// Handle search users process.
		if ( isset( $details['search_param'] ) && '' !== $details['search_param'] ) {

			$search_param = sanitize_text_field( esc_attr( trim( $details['search_param'] ) ) );

			$filter['search'] = '*' . esc_attr( $search_param ) . '*';

			if ( ! empty( $general_search_fields ) ) {
				$all_search_fields['general_search_fields'] = $general_search_fields;
				$filter['search_column']                    = $general_search_fields;
			}
		}

		$meta_query_container = $this->ur_frontend_listing_meta_query_handler( $post_id, $meta_query_container );
		$meta_query_container = $this->ur_frontend_listing_advanced_filter_handler( $post_id, $meta_query_container, $details );

		// Handle search users process when meta_key is present.
		if ( ! empty( $meta_search_fields ) ) {
			$all_search_fields['meta_search_fields']           = $meta_search_fields;
			$user_registration_frontend_listings_search_fields = $all_search_fields;

			add_action(
				'pre_user_query',
				function ( $query ) use ( $meta_search_fields ) {
					$search = '';

					if ( isset( $query->query_vars['search'] ) ) {
						$search = trim( str_replace( '*', '', $query->query_vars['search'] ) );
					}

					if ( $search ) {
						global $user_registration_frontend_listings_search_fields;
						global $wpdb;

						$user_query = '';
						if ( ! empty( $user_registration_frontend_listings_search_fields ) && ! empty( $meta_search_fields ) ) {

							$query->query_fields   = " DISTINCT {$wpdb->users}.*";
							$query->query_from     = ' FROM ' . $wpdb->users . ' LEFT OUTER JOIN ' . $wpdb->usermeta . ' AS alias ON (' . $wpdb->users . '.ID = alias.user_id)';
							$general_search_fields = $user_registration_frontend_listings_search_fields['general_search_fields'];

							// Modify query if general search fields like user_login, user_email, etc is present.
							if ( ! empty( $general_search_fields ) ) {
								$user_query .= '(';

								foreach ( $general_search_fields as $key => $value ) {
									// Last key of the array.
									$last_key = array_keys( $general_search_fields )[ count( $general_search_fields ) - 1 ];

									// Append OR statement at last unless its the last element.
									$OR          = ( $last_key === $key ) ? '' : 'OR';
									$user_query .= ' ' . $value . ' LIKE  "%' . $search . '%" ' . $OR;
								}
								$user_query .= ')';

								// Append OR statement at the end of modified query if meta search fields is not empty.
								if ( ! empty( $meta_search_fields ) ) {
									$user_query .= ' OR';
								}
							}
							$user_query .= ' (';

							foreach ( $meta_search_fields as $key => $value ) {
								// Append OR statement at the first key of modified query.
								$OR          = ( 0 === $key ) ? '' : 'OR';
								$user_query .= $OR . ' alias.meta_key = "' . $value . '" AND alias.meta_value LIKE  "%' . $search . '%" ';
							}
							$user_query .= ')';

						}

						// Append the modified query to the final query.
						$query->query_where   = 'WHERE 1=1 AND ' . $user_query;
						$query->query_orderby = '';
					}

					// Remove action after all processing in order to prevent the above code from conflicting other plugins.
					remove_action( 'pre_user_query', $this );
				}
			);
		}

		if ( ! defined( 'WP_User_Query' ) ) {
			include_once ABSPATH . './wp-includes/class-wp-user-query.php';
		}

		$users_per_page = ( isset( $details['amount_filter'] ) && intval( $details['amount_filter'] ) ) ? $details['amount_filter'] : $default_amount_filter;
		$sort_by        = ( isset( $details['sort_by'] ) ) ? $details['sort_by'] : $default_sorter;

		// Update pagination and offset users.
		$paged  = isset( $details['page'] ) ? $details['page'] : 1;
		$offset = 0;

		if ( 1 !== $paged ) {
			$offset = ( $paged - 1 ) * $users_per_page;
		}

		// Check if default sorter is present.
		if ( '' !== $default_sorter ) {
			// Sort user list according to sorter.
			switch ( $sort_by ) {
				case 'user_registered':
					$filter['orderby'] = 'registered';
					$filter['order']   = 'DESC';
					break;
				case 'first_name':
					$filter['meta_key'] = 'first_name';
					$filter['orderby']  = 'meta_value';
					$filter['order']    = 'ASC';
					break;
				case 'last_name':
					$filter['meta_key'] = 'last_name';
					$filter['orderby']  = 'meta_value';
					$filter['order']    = 'ASC';
					break;
				case 'display_name':
					$filter['orderby'] = 'display_name';
					$filter['order']   = 'ASC';
					break;
			}
		}

		$filter['offset']     = $offset;
		$filter['paged']      = $paged;
		$filter['meta_query'] = $meta_query_container;

		$user_query = new WP_User_Query( $filter );

		$users              = $user_query->get_results();
		$only_ur_users      = get_post_meta( $post_id, 'user_registration_frontend_listings_ur_only', $single = true );
		$only_ur_form_users = get_post_meta( $post_id, 'user_registration_frontend_listings_ur_forms', $single = true );

		// Filter users according to registered user registration forms.
		if ( $only_ur_users ) {

			foreach ( $users as $key => $user ) {
				if ( ! $only_ur_form_users ) {
					if ( ! get_user_meta( $user->ID, 'ur_form_id', true ) ) {
						if ( ! get_user_meta( $user->ID, 'user_registration_social_connect_bypass_current_password', true ) ) {
							unset( $users[ $key ] );
						}
					}
				} elseif ( ! in_array( get_user_meta( $user->ID, 'ur_form_id', true ), $only_ur_form_users ) ) {
					if ( ! get_user_meta( $user->ID, 'user_registration_social_connect_bypass_current_password', true ) ) {
						unset( $users[ $key ] );
					}
				}
			}
		}

		foreach ( $users as $user ) {

			if ( isset( $details['advanced_filter'] ) && ! empty( $details['advanced_filter'] ) ) {
				foreach ( $details['advanced_filter'] as $key => $value ) {

					if ( in_array( $key, $table_fields, true ) ) {
						if ( $value !== $user->data->$key ) {
							continue 2;
						}
					}
				}
			}

			$individual_data            = array();
			$individual_data['user_id'] = $user->ID;

			foreach ( $table_fields as $value ) {
				$individual_data[ $value ] = $user->data->$value;
			}

			$individual_data['view_user_url'] = $details['view_user_url'] . '?list_id=' . $post_id . '&user_id=' . $user->ID;

			$individual_data['view_user_url'] = apply_filters( 'user_registration_frontend_listing_view_user_url', $individual_data['view_user_url'], $user->ID );

			// Extract full name of users.
			$first_name = get_user_meta( $user->ID, 'first_name', true );
			$last_name  = get_user_meta( $user->ID, 'last_name', true );
			$full_name  = '';

			if ( '' !== $first_name && '' !== $last_name ) {
				$full_name .= $first_name . ' ' . $last_name;
			} elseif ( '' === $first_name && '' !== $last_name ) {
				$full_name .= $last_name;
			} elseif ( '' !== $first_name && '' === $last_name ) {
				$full_name .= $first_name;
			} else {
				$full_name .= '_ _ _ _';
			}

			$individual_data['full_name'] = $full_name;

			// Extract profile picture of users.
			$gravatar_image                     = get_avatar_url( $user->ID, null );
			$profile_picture_url                = get_user_meta( $user->ID, 'user_registration_profile_pic_url', true );
			$individual_data['profile_picture'] = ( ! empty( $profile_picture_url ) ) ? $profile_picture_url : $gravatar_image;

			if ( is_numeric( $individual_data['profile_picture'] ) ) {
				$individual_data['profile_picture'] = wp_get_attachment_url( $individual_data['profile_picture'] );
			}

			array_push( $data, $individual_data );
		}

		$users_to_be_displayed = $users_per_page * $paged;
		if ( ! current_user_can( 'administrator' ) ) {
			$privacy_tab_enable     = get_option( 'user_registration_enable_privacy_tab', false );
			$enable_profile_privacy = get_option( 'user_registration_enable_profile_privacy', true );

			if ( ur_string_to_bool( $privacy_tab_enable ) && ur_string_to_bool( $enable_profile_privacy ) ) {
				foreach ( $data as $key => $single_user_data ) {
					if ( get_current_user_id() === $single_user_data['user_id'] ) {
						continue;
					}
					$show_profile = ur_string_to_bool( get_user_meta( $single_user_data['user_id'], 'ur_show_profile', true ) );
					if ( $show_profile ) {
						unset( $data[ $key ] );
					}
				}
			}
		}
		$total_users       = count( $data );
		$total_pages       = ceil( $total_users / $users_per_page );
		$updated_user_data = array_chunk( $data, $users_per_page );
		$profile_cards     = '';
		if ( count( $data ) > 0 ) {
			$profile_cards = $this->ur_frontend_listing_render_user_list( $post_id, $updated_user_data[ $paged - 1 ] );
		}
		$displayed_users = 0;
		if ( $total_users <= $users_to_be_displayed ) {
			$displayed_users = $total_users;
		} else {
			$displayed_users = $users_to_be_displayed;
		}

		$pagination_template = $this->ur_frontend_listing_pagination_handler( $details, $paged, $total_pages );

		return array(
			'profile_cards'       => $profile_cards,
			'pagination_template' => $pagination_template,
			'displayed_users'     => $displayed_users,
			'total_users'         => $total_users,
		);
	}

	/**
	 * Get all users data.
	 *
	 * @param array $post_id Frontend listing post id.
	 * @param array $meta_queries Frontend listing meta queries.
	 * @return array
	 */
	public function ur_frontend_listing_meta_query_handler( $post_id, $meta_queries ) {

		$restricted_user_role = get_post_meta( $post_id, 'user_registration_frontend_listings_role_restriction', $single = true );

		// Add meta query when users with restricted user roles are not to be displayed.
		if ( ! empty( $restricted_user_role ) ) {
			$restrict_role_query = array(
				'relation' => 'AND',
			);
			foreach ( $restricted_user_role as $key => $value ) {
				array_push(
					$restrict_role_query,
					array(
						'key'     => "{$GLOBALS['wpdb']->prefix}capabilities",
						'value'   => $value,
						'compare' => 'NOT LIKE',
					)
				);

			}

			array_push(
				$meta_queries,
				$restrict_role_query
			);
		}

		return $meta_queries;
	}

	/**
	 * Advanced Filter query handler.
	 *
	 * @param array $post_id Frontend listing post id.
	 * @param array $meta_queries Frontend listing meta queries.
	 * @param array $details User query details.
	 *
	 * @since 1.1.0
	 */
	public function ur_frontend_listing_advanced_filter_handler( $post_id, $meta_queries, $details ) {

		if ( isset( $details['advanced_filter'] ) && ! empty( $details['advanced_filter'] ) ) {
			$advanced_filter_query       = array(
				'relation' => 'AND',
			);
			$advanced_filter_query_array = array();
			foreach ( $details['advanced_filter'] as $meta_key => $value ) {
				$table_fields                            = ur_get_user_table_fields();
				$advanced_filter_query_array['relation'] = 'OR';

				if ( strpos( $meta_key, ',' ) !== false ) {

					$keys = explode( ',', $meta_key );

					foreach ( $keys as $meta_key_index ) {

						$meta_key_index = trim( $meta_key_index );

						if ( ! in_array( $meta_key_index, $table_fields ) ) {
							$registered_meta_fields = ur_get_registered_user_meta_fields();

							if ( ! in_array( $meta_key_index, $registered_meta_fields ) ) {

								if ( strpos( 'user_registration_', $meta_key_index ) === false ) {
									$meta_key_index = 'user_registration_' . $meta_key_index;
								}

								if ( strpos( $meta_key_index, 'country' ) !== false ) {
									$country_class = ur_load_form_field_class( 'country' );
									$countries     = $country_class::get_instance()->get_country();
									$value         = array_search( ucwords( $value ), $countries );
								}

								array_push(
									$advanced_filter_query_array,
									array(
										'key'     => $meta_key_index,
										'value'   => $value,
										'compare' => 'LIKE',
									)
								);

							}
						}
					}

					array_push( $advanced_filter_query, $advanced_filter_query_array );
				} elseif ( ! in_array( $meta_key, $table_fields ) ) {
						$advanced_filter_query['relation'] = 'AND';
						$registered_meta_fields            = ur_get_registered_user_meta_fields();

					if ( ! in_array( $meta_key, $table_fields ) && ! in_array( $meta_key, $registered_meta_fields ) ) {

						if ( strpos( 'user_registration_', $meta_key ) === false ) {
							$meta_key = 'user_registration_' . $meta_key;
						}
					}

					if ( strpos( $meta_key, 'country' ) !== false ) {
						$country_class = ur_load_form_field_class( 'country' );
						$countries     = $country_class::get_instance()->get_country();
						$value         = array_search( ucwords( $value ), $countries );
					}

						array_push(
							$advanced_filter_query,
							array(
								'key'     => $meta_key,
								'value'   => $value,
								'compare' => 'LIKE',
							)
						);
				}
			}

			if ( ! empty( $advanced_filter_query ) ) {
				array_push(
					$meta_queries,
					$advanced_filter_query
				);
			}
		}

		return $meta_queries;
	}

	/**
	 * Get all users data.
	 *
	 * @param array $details Frontend listing post id.
	 * @param int   $paged Page number.
	 * @param int   $total_pages Total number of pages .
	 * @return array
	 */
	public function ur_frontend_listing_pagination_handler( $details, $paged, $total_pages ) {
		$current_page = isset( $details['page'] ) ? $details['page'] : 1;

		$pagination_template = '';

		$pagination_template .= '<div class="user-registration-frontend-listing-pagination-group">';

		if ( ! empty( $total_pages ) ) {

			$pages_to_show = array();
			$first_index   = 1;
			$last_index    = 5;

			if ( $total_pages > 5 && $current_page >= 5 ) {
				$first_index = $current_page - 2;

				if ( $current_page <= $total_pages - 2 ) {
					$last_index = $current_page + 2;
				} else {
					$last_index  = $total_pages;
					$first_index = $first_index - 2;
				}
			}

			$pages_to_show = range(
				( $first_index > 0 ) ? $first_index : 1,
				( $last_index <= $total_pages ) ? $last_index : $total_pages
			);

			$pagination_template .= '<a class="user-registration-frontend-listing-page" id="user-registration-frontend-listing-previous-page"> <span class="dashicons dashicons-arrow-left-alt2"></span>  </a>';

			foreach ( $pages_to_show as $value ) {
				$active = '';

				if ( $value == $current_page ) {
					$active = 'active';
				}

				$pagination_template .= '<a class="user-registration-frontend-listing-page ' . esc_attr( $active ) . '" id="user-registration-frontend-listing-' . $value . '" >' . $value . '</a>';
			}

			$pagination_template .= '<a class="user-registration-frontend-listing-page" id="user-registration-frontend-listing-next-page" > <span class="dashicons dashicons-arrow-right-alt2"></span> </a>';

		}

		$pagination_template .= '</div>';
		return $pagination_template;
	}


	/**
	 * Get all users data.
	 *
	 * @param array $post_id Frontend listing post id.
	 * @param array $user_data User Datas to list.
	 * @return array
	 */
	public function ur_frontend_listing_render_user_list( $post_id, $user_data ) {
		$layout = get_post_meta( $post_id, 'user_registration_frontend_listings_layout', $single = true );

		$profile_cards = '';

		if ( '0' === $layout ) {
			$profile_cards .= $this->ur_frontend_listing_render_grid_layout( $post_id, $user_data );
		} else {
			$profile_cards .= $this->ur_frontend_listing_render_list_layout( $post_id, $user_data );
		}

		return $profile_cards;
	}

	/**
	 * Get all users data in grid layout.
	 *
	 * @param array $post_id Frontend listing post id.
	 * @param array $user_data User Datas to list.
	 * @return array
	 */
	public function ur_frontend_listing_render_grid_layout( $post_id, $user_data ) {
		$show_profile_picture = get_post_meta( $post_id, 'user_registration_frontend_listings_display_profile_picture', $single = true );
		$show_view_profile    = get_post_meta( $post_id, 'user_registration_frontend_listings_view_profile', $single = true );
		$view_profile_button  = get_post_meta( $post_id, 'user_registration_frontend_listings_view_profile_button_text', $single = true );
		$view_profile_button  = ( '' !== $view_profile_button ) ? $view_profile_button : __( 'VIEW PROFILE', 'user-registration-frontend-listing' );

		$profile_cards = '';
		foreach ( $user_data as $user ) {
			$profile_cards           .= '<div class="ur-frontend-user-list" >';
			$user_id                  = $user['user_id'];
			$user_extra_fields        = ur_get_user_extra_fields( $user_id );
			$user_data                = (array) get_userdata( $user_id )->data;
			$user_data['first_name']  = get_user_meta( $user_id, 'first_name', true );
			$user_data['last_name']   = get_user_meta( $user_id, 'last_name', true );
			$user_data['description'] = get_user_meta( $user_id, 'description', true );
			$user_data['nickname']    = get_user_meta( $user_id, 'nickname', true );
			$user_data                = array_merge( $user_data, $user_extra_fields );
			$form_id                  = ur_get_form_id_by_userid( $user_id );
			$fields_to_include        = get_post_meta( $post_id, 'user_registration_frontend_listings_lists_fields', $single = true );
			$fields_to_include        = ! empty( $fields_to_include ) ? $fields_to_include : array_keys( ur_frontend_listing_include_fields_in_view_profile() );
			$form_field_data_array    = user_registration_pro_profile_details_form_fields( $form_id, $fields_to_include );
			$field_keys_to_include    = user_registration_pro_profile_details_form_keys_to_include( $fields_to_include, $form_field_data_array );

			foreach ( $field_keys_to_include as $key => $value ) {
				if ( preg_match( '/^(billing_|shipping_).+|.+_shipping$/', $value ) ) {
					$woocommerce_user_meta = get_user_meta( $user_id, $value );
					$user_data[ $value ]   = isset( $woocommerce_user_meta[0] ) ? $woocommerce_user_meta[0] : '';
				}
			}

			$user_data_to_show = user_registration_pro_profile_details_form_field_datas( $form_id, $user_data, $form_field_data_array, $field_keys_to_include );
			$user_details      = '';

			if ( $show_profile_picture ) {
				$profile_cards .= '<div class="ur-list-image">';
				$profile_cards .= '<img alt="profile-picture" src="' . esc_url( $user['profile_picture'] ) . '">';
				$profile_cards .= '</div>';
			}
			$user_details .= '<h4 class="ur-list-title">' . esc_html( $user['full_name'] ) . '</h4>';

			foreach ( $user_data_to_show as $data ) {

				if ( 'user_login' === $data['field_key'] ) {
					$user_details .= '<div class="ur-list-nickname">@' . esc_html( $data['value'] ) . '</div>';
				} elseif ( 'user_email' === $data['field_key'] ) {
					$user_details .= '<div class="ur-list-email">' . esc_html( $data['value'] ) . '</div>';
				} elseif ( 'user_url' === $data['field_key'] ) {
					$user_details .= '<div class="ur-list-website"><a href="' . esc_attr( $data['value'] ) . '">' . esc_html( explode( '//', $data['value'], 2 )[1] ) . '</a></div>';
				} elseif ( 'file' === $data['field_key'] ) {
					$values = explode( ',', $data['value'] );
					foreach ( $values as $value ) {
						$user_details .= '<div class="ur-list-file-upload">' . wp_kses_post( $value ) . '</div>';
					}
				} else {
					$user_details .= '<div class="ur-list-display-name">' . esc_html( $data['value'] ) . '</div>';
				}
			}

			$profile_cards .= '' . $user_details . '';

			if ( $show_view_profile ) {
				$profile_cards .= '<a href="' . $user['view_user_url'] . '" class=" ur-btn btn-view-details" rel="noreferrer noopener" target="_blank">' . esc_html( $view_profile_button ) . '</a>';
			}

			$profile_cards .= '</div>';
		}
		return $profile_cards;
	}

	/**
	 * Get all users data in list layout.
	 *
	 * @param array $post_id Frontend listing post id.
	 * @param array $user_data User Datas to list.
	 * @return array
	 */
	public function ur_frontend_listing_render_list_layout( $post_id, $user_data ) {
		$show_profile_picture = get_post_meta( $post_id, 'user_registration_frontend_listings_display_profile_picture', $single = true );
		$show_view_profile    = get_post_meta( $post_id, 'user_registration_frontend_listings_view_profile', $single = true );
		$view_profile_button  = get_post_meta( $post_id, 'user_registration_frontend_listings_view_profile_button_text', $single = true );
		$view_profile_button  = ( '' !== $view_profile_button ) ? $view_profile_button : __( 'VIEW PROFILE', 'user-registration-frontend-listing' );

		$profile_cards = '';

		foreach ( $user_data as $user ) {
			$profile_cards .= '<div class="ur-frontend-user-list-card">';
			$profile_cards .= '<div class="user-info-wrap">';

			if ( $show_profile_picture ) {
				$profile_cards .= '<div class="ur-list-image">';
				$profile_cards .= '<img alt="profile-picture" src="' . esc_url( $user['profile_picture'] ) . '">';
				$profile_cards .= '</div>';
			}

			$profile_cards .= '<div class="user-description">';
			$profile_cards .= '<h4 class="ur-list-title">';
			$profile_cards .= '<p>' . esc_html( $user['full_name'] ) . '</p>';
			$profile_cards .= '</h4>';
			$profile_cards .= '<div class="ur-list-nickname"> @' . esc_html( $user['user_login'] ) . ' </div>';
			$profile_cards .= '</div>';
			$profile_cards .= '</div>';

			if ( $show_view_profile ) {
				$profile_cards .= '<a href="' . $user['view_user_url'] . '" class="ur-btn btn-view-details" rel="noreferrer noopener" target="_blank">' . esc_html( $view_profile_button ) . '</a>';
			}
			$profile_cards .= '</div>';
		}

		return $profile_cards;
	}

	/**
	 * Add body class to frontend listing page.
	 *
	 * @since 1.0.4.
	 * @return array
	 */
	public function ur_frontend_listing_add_body_classes( $classes ) {
		if ( isset( $_GET['user_id'] ) && intval( $_GET['user_id'] ) ) {
			$classes[] = 'user-registration-frontend-listing-view-profile';
		}

		return $classes;
	}
}
