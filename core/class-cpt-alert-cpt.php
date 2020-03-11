<?php
/**
 * Creates and manages the Alert CPT.
 *
 * @since {{VERSION}}
 *
 * @package    CPTAlert
 * @subpackage CPTAlert/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class CPTAlert_CPT {

	private $post_type = 'alert';
	private $label_singular;
	private $label_plural;
	private $icon = 'info';

	function __construct() {

		$this->label_singular = __( 'Alert', 'vl-cpt-alert' );
		$this->label_plural = __( 'Alerts', 'vl-cpt-alert' );

		$this->add_actions();
	}

	private function add_actions() {

		add_action( 'init', array( $this, '_create_cpt' ) );
		add_action( 'init', array( $this, '_expire' ), 1 );

		add_filter( 'post_updated_messages', array( $this, '_post_messages' ) );
		add_action( 'add_meta_boxes', array( $this, '_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, '_save_alert_information' ) );
		add_action( 'delete_post', array( $this, '_update_alert_information' ) );
		add_action( 'rest_api_init', array( $this, 'alert_add_rest_fields' ) );

		add_action( 'current_screen', array( $this, '_page_actions' ) );
	}

	function _page_actions( $screen ) {

		if ( $screen->base != 'post' || $screen->id != 'alert' ) {
			return;
		}

		add_filter( 'rbm_load_select2', '__return_true' );
	}

	/**
	 * Creates the CPT.
	 *
	 * @since {{VERSION}}
	 */
	function _create_cpt() {

		$labels = array(
			'name'               => $this->label_plural,
			'singular_name'      => $this->label_singular,
			'menu_name'          => $this->label_plural,
			'name_admin_bar'     => $this->label_singular,
			'add_new'            => __( "Add New", 'vl-cpt-alert' ),
			'add_new_item'       => sprintf( __( "Add New %s", 'vl-cpt-alert' ), $this->label_singular ),
			'new_item'           => sprintf( __( "New %s", 'vl-cpt-alert' ), $this->label_singular ),
			'edit_item'          => sprintf( __( "Edit %s", 'vl-cpt-alert' ), $this->label_singular ),
			'view_item'          => sprintf( __( "View %s", 'vl-cpt-alert' ), $this->label_singular ),
			'all_items'          => sprintf( __( "All %s", 'vl-cpt-alert' ), $this->label_plural ),
			'search_items'       => sprintf( __( "Search %s", 'vl-cpt-alert' ), $this->label_plural ),
			'parent_item_colon'  => sprintf( __( "Parent %s:", 'vl-cpt-alert' ), $this->label_plural ),
			'not_found'          => sprintf( __( "No %s found.", 'vl-cpt-alert' ), $this->label_plural ),
			'not_found_in_trash' => sprintf( __( "No %s found in Trash.", 'vl-cpt-alert' ), $this->label_plural ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'menu_icon'          => 'dashicons-' . $this->icon,
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'show_in_rest'       => true,
			'supports'           => array( 'title', 'editor' ),
		);

		register_post_type( $this->post_type, $args );

		$labels = array(
			'name'                       => __( 'Categories', 'vl-cpt-alert' ),
			'singular_name'              => __( 'Category', 'vl-cpt-alert' ),
			'menu_name'                  => __( 'Categories', 'vl-cpt-alert' ),
			'name_admin_bar'             => __( 'Category', 'vl-cpt-alert' ),
			'add_new'                    => __( "Add New", 'vl-cpt-alert' ),
			'add_new_item'               => __( "Add New Category", 'vl-cpt-alert' ),
			'new_item'                   => __( "New Category", 'vl-cpt-alert' ),
			'edit_item'                  => __( "Edit Category", 'vl-cpt-alert' ),
			'view_item'                  => __( "View Category", 'vl-cpt-alert' ),
			'all_items'                  => __( "All categories", 'vl-cpt-alert' ),
			'search_items'               => __( "Search categories", 'vl-cpt-alert' ),
			'parent_item_colon'          => __( "Parent categories:", 'vl-cpt-alert' ),
			'not_found'                  => __( "No categories found.", 'vl-cpt-alert' ),
			'not_found_in_trash'         => __( "No categories found in Trash.", 'vl-cpt-alert' ),
			'separate_items_with_commas' => __( 'Seperate categories with commas', 'vl-cpt-alert' ),
			'choose_from_most_used'      => __( 'Choose from must used categories', 'vl-cpt-alert' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'vl-cpt-alert' ),
		);

		register_taxonomy( 'alert-category', 'alert', array(
			'labels'            => $labels,
			'show_admin_column' => true,
		) );
	}

	function _post_messages( $messages ) {

		$post             = get_post();
		$post_type_object = get_post_type_object( $this->post_type );

		$messages[ $this->post_type ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( '%s updated.', 'vl-cpt-alert' ), $this->label_singular ),
			2  => __( 'Custom field updated.', 'vl-cpt-alert' ),
			3  => __( 'Custom field deleted.', 'vl-cpt-alert' ),
			4  => sprintf( __( "%s updated.", 'vl-cpt-alert' ), $this->label_singular ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( "%s restored to revision from %s", 'vl-cpt-alert' ), $this->label_singular, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( "%s published.", 'vl-cpt-alert' ), $this->label_singular ),
			7  => sprintf( __( "%s saved.", 'vl-cpt-alert' ), $this->label_singular ),
			8  => sprintf( __( "%s submitted.", 'vl-cpt-alert' ), $this->label_singular ),
			9  => sprintf( __( "%s scheduled for: <strong>%s</strong>.", 'vl-cpt-alert' ), $this->label_singular, date( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
			10 => sprintf( __( "%s draft updated.", 'vl-cpt-alert' ), $this->label_singular ),
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' ' . __( '<a href="View %s">%s</a>', 'vl-cpt-alert' ), esc_url( $permalink ), $this->label_singular );
			$messages[ $this->post_type ][1] .= $view_link;
			$messages[ $this->post_type ][6] .= $view_link;
			$messages[ $this->post_type ][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link      = sprintf( ' ' . __( '<a target="_blank" href="%s">Preview %s</a>', 'vl-cpt-alert' ), esc_url( $preview_permalink ), $this->label_singular );
			$messages[ $this->post_type ][8] .= $preview_link;
			$messages[ $this->post_type ][10] .= $preview_link;
		}

		return $messages;
	}

	function _expire() {

		$current_time = current_time( 'Ymd H:i' );

		$alerts = new WP_Query( array(
			'post_type' => 'alert',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				'relationship' => 'AND',
				array(
					'key' => 'rbm_cpts_expire_alert',
					'value' => '"1"',
					'compare' => 'LIKE',
				),
				array(
					'key' => 'rbm_cpts_expire_time',
					'value' => $current_time,
					'compare' => '<',
				),
				array(
					'key' => 'rbm_cpts_expire_time',
					'value' => '',
					'compare' => '!=',
				),
				array(
					'key' => 'rbm_cpts_expire_time',
					'compare' => 'EXISTS',
				)
			)
		) );

		if ( ! $alerts->have_posts() ) return;

		foreach ( $alerts->posts as $post_id ) {

			wp_update_post( array(
				'ID' => $post_id,
				'post_status' => 'draft',
			) );

			delete_post_meta( $post_id, 'rbm_cpts_expire_time' );

		}

	}

	function _add_meta_boxes() {

		add_meta_box(
			'alert_expire',
			__( 'Automatically Remove Alert', 'vl-cpt-alert' ),
			array( $this, '_mb_expire' ),
			'alert',
			'side',
			'high'
		);

		add_meta_box(
			'alert_visibility',
			__( 'Visibility Settings', 'vl-cpt-alert' ),
			array( $this, '_mb_visibility' ),
			'alert',
			'normal'
		);

		/*

		add_meta_box(
			'type',
			__( 'Type', 'vl-cpt-alert' ),
			array( $this, '_mb_type' ),
			'alert',
			'side'
		);

		*/

		add_meta_box(
			'display',
			__( 'Display Options', 'vl-cpt-alert' ),
			array( $this, '_mb_display' ),
			'alert',
			'side'
		);

		/*

		add_meta_box(
			'popup',
			__( 'Popup Image', 'vl-cpt-alert' ),
			array( $this, '_mb_popup' ),
			'alert',
			'side'
		);

		*/

		add_meta_box(
			'user_interaction',
			__( 'User Interaction', 'vl-cpt-alert' ),
			array( $this, '_mb_user_interaction' ),
			'alert',
			'side'
		);
	}

	function _mb_expire() {

		rbm_cpts_do_field_checkbox( array(
			'name' => 'expire_alert',
			'group' => 'alert_expire',
			'label' => false,
			'options' => array(
				'1' => __( 'This Alert should automatically Expire', 'vl-cpt-alert' ),
			)
		) );

		rbm_cpts_do_field_datetimepicker( array(
			'name' => 'expire_time',
			'group' => 'alert_expire',
			'label' => '<strong>' . __( 'Automatically take down Alert at', 'vl-cpt-alert' ) . '</strong>',
			'description' => '<p class="description">' . __( 'If you do not want to have this Alert expire, leave this value alone', 'vl-cpt-alert' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
		) );

		rbm_cpts_init_field_group( 'alert_expire' );

	}

	function _mb_visibility() {

		$posts = get_posts( array(
			'post_type'   => 'any',
			'numberposts' => - 1,
		) );

		$post_options = array();
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {

				$post_type = get_post_type_object( $post->post_type );
				if ( ! isset( $post_options[ $post_type->labels->name ] ) ) {
					$post_options[ $post_type->labels->name ] = array();
				}

				$post_options[ $post_type->labels->name ][ $post->ID ] = $post->post_title;
			}
		}

		$post_types = get_post_types( array(
			'public' => true,
		) );

		$post_type_options = array();
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {

				$post_type                             = get_post_type_object( $post_type );
				$post_type_options[ $post_type->name ] = $post_type->labels->singular_name;
			}
		}

		$taxonomies = get_taxonomies( array(), 'objects' );

		$term_options = array();
		foreach ( $taxonomies as $taxonomy ) {

			if ( $terms = get_terms( $taxonomy->name, array( 'fields' => 'id=>name' ) ) ) {

				foreach ( $terms as $term_ID => $term_name ) {
					$term_options[ $term_ID ] = "{$taxonomy->labels->singular_name}: $term_name";
				}
			}
		}

		rbm_cpts_do_field_checkbox( array(
			'label' => false,
			'name' => 'visibility_everywhere',
			'group' => 'alert_visibility',
			'options' => array(
				'1' => __( 'Show Everywhere', 'vl-cpt-alert' ),
			)
		) );

		rbm_cpts_do_field_select( array(
			'name' => 'visibility_posts',
			'group' => 'alert_visibility',
			'label' => '<strong>' . __( 'Pages to Show On', 'vl-cpt-alert' ) . '</strong>',
			'options'     => $post_options,
			'opt_groups'  => true,
			'multiple'    => true,
			'multi_field' => true,
		) );

		if ( post_type_exists( 'facility' ) ) {

			$location_post_type_object = get_post_type_object( 'facility' );

			$locations = new WP_Query( array(
				'post_type' => 'facility',
				'posts_per_page' => -1,
				'meta_key' => 'rbm_cpts_short_name',
				'orderby' => 'meta_value',
				'order' => 'ASC',
			) );
			
			$location_options = array();
			if ( $locations->have_posts() ) {
				
				foreach ( $locations->posts as $location ) : 

					$location_options[ $location->ID ] = rbm_cpts_get_field( 'short_name', $location->ID );

				endforeach;

			}

			rbm_cpts_do_field_select( array(
				'name' => 'visibility_locations',
				'group' => 'alert_visibility',
				'label' => '<strong>' . sprintf( __( '%s to Show On', 'vl-cpt-alert' ), $location_post_type_object->labels->name ) . '</strong>',
				'options'     => $location_options,
				'multiple'    => true,
				'description' => '<p class="description">' . sprintf( __( 'This includes any pages that belong to these %s, such as Landing Pages.', 'vl-cpt-alert' ), $location_post_type_object->labels->name ) . '</p>',
				'description_tip' => false,
				'description_placement' => 'after_label',
			) );

		}

		rbm_cpts_do_field_select( array(
			'name' => 'visibility_terms',
			'group' => 'alert_visibility',
			'label' => '<strong>' . __( 'Taxonomy Terms to Show On', 'vl-cpt-alert' ) . '</strong>',
			'options'     => $term_options,
			'multiple'    => true,
			'multi_field' => true,
		) );
		
		rbm_cpts_do_field_checkbox( array(
			'name' => 'show_term_alerts_on_single',
			'group' => 'alert_visibility',
			'label' => false,
			'options' => array(
				'1' => __( 'Should Alerts for Taxonomy Terms also show on Single Posts assigned to that Term?', 'vl-cpt-alert' ),
			),
			'description' => '<p class="description">' . __( 'If you check this box, this Alert will show both in the Archive for the Term selected and the pages that are categorized with this Term.', 'vl-cpt-alert' ). '</p>',
			'description_tip' => false,
		) );

		rbm_cpts_do_field_select( array(
			'name' => 'visibility_post_types', 
			'group' => 'alert_visibility',
			'label' => '<strong>' . __( 'Post Types to Show On', 'vl-cpt-alert' ) . '</strong>',
			'options'     => $post_type_options,
			'multiple'    => true,
			'multi_field' => true,
		) );

		/*

		if ( is_multisite() && is_main_site() ) {

			$blogs = wp_get_sites();

			// Remove current site
			$current_blog_id = get_current_blog_id();
			foreach ( $blogs as $i => $site ) {
				if ( (int) $site['blog_id'] === $current_blog_id ) {
					unset( $blogs[ $i ] );
					break;
				}
			}

			$blog_options = wp_list_pluck( $blogs, 'domain', 'blog_id' );

			rbm_cpts_do_field_select( array(
				'name' => 'visibility_sites', 
				'group' => 'alert_visibility',
				'label' => '<strong>' . __( 'Sub-Sites to Show On', 'vl-cpt-alert' ) . '</strong>',
				'options'     => $blog_options,
				'multiple'    => true,
				'multi_field' => true,
				'description' => '<p class="description">' . __( 'Note: If a subsite is selected, the alert will show up on EVERY page on that subsite.', 'vl-cpt-alert' ) . '</p>',
				'description_tip' => false,
				'description_placement' => 'after_label',
			) );
		}

		*/

		rbm_cpts_init_field_group( 'alert_visibility' );

	}

	function _mb_type() {

		rbm_cpts_do_field_radio( array(
			'name' => 'type', 
			'group' => 'alert_type',
			'label' => false,
			'default' => 'inset-banner',
			'options' => array(
				'inset-banner' => __( 'Inset Banner', 'vl-cpt-alert' ),
				'pop-up' => __( 'Pop-up / Top Banner', 'vl-cpt-alert' ),
			)
		) );

		rbm_cpts_init_field_group( 'alert_type' );

	}

	function _mb_display() {

		rbm_cpts_do_field_select( array(
			'name' => 'color',
			'group' => 'alert_display',
			'label' => '<strong>' . __( 'Background Color', 'vl-cpt-alert' ) . '</strong>',
			'default' => 'primary',
			'options' => array(
				'primary' => _x( 'Blue', 'Primary Theme Color', 'vl-cpt-alert' ),
                'secondary' => _x( 'Orange', 'Secondary Theme Color', 'vl-cpt-alert' ),
                'tertiary' => _x( 'Yellow', 'Tertiary Theme Color', 'vl-cpt-alert' ),
				'quaternary' => _x( 'Dark Blue', 'Quaternary Theme Color', 'vl-cpt-alert' ),
				'quinary' => _x( 'Goldenrod', 'Quinary Theme Color', 'vl-cpt-alert' ),
			),
		) );

		rbm_cpts_do_field_select( array(
			'name' => 'icon', 
			'group' => 'alert_display',
			'label' => '<strong>' . __( 'Icon', 'vl-cpt-alert' ) . '</strong>',
			'default' => 'default',
			'options' => array(
				'default' => __( 'Default / Exclamation Mark', 'vl-cpt-alert' ),
				'fas fa-calendar' => __( "Calendar", 'vl-cpt-alert' ),
				'far fa-star' => __( "Star", 'vl-cpt-alert' ),
				'fas fa-ticket' => __( "Ticket", 'vl-cpt-alert' ),
				'fas fa-flag' => __( "Flag", 'vl-cpt-alert' ),
				'fas fa-sun' => __( "Sunset", 'vl-cpt-alert' ),
				'fas fa-location-arrow' => __( "Location Arrow", 'vl-cpt-alert' ),
				'far fa-heart' => __( "Heart", 'vl-cpt-alert' ),
				'fas fa-question-circle' => __( "Question Mark", 'vl-cpt-alert' ),
				'fas fa-leaf' => __( "Leaf", 'vl-cpt-alert' ),
				'fas fa-map-marker' => __( "Map Marker", 'vl-cpt-alert' ),
				'fas fa-wheelchair' => __( "Wheelchair", 'vl-cpt-alert' ),
			),
		) );

		rbm_cpts_do_field_text( array(
			'name' => 'time_range', 
			'group' => 'alert_display',
			'label' => '<strong>' . __( 'Only show the Alert during this time range each day', 'vl-cpt-alert' ) . '</strong>',
			'description' => '<p class="description">' . __( "Enter valid 24hr time format with dash, like so: <code>9:30-18:00</code>. This is dependent on the time set on the visitor's computer.", 'vl-cpt-alert' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
		) );

		rbm_cpts_init_field_group( 'alert_display' );

	}

	function _mb_popup() {

		rbm_cpts_do_field_media( array(
			'name' => 'popup_image',
			'group' > 'alert_popup',
			'type' => 'image',
			'label' => '<strong>' . __( 'Desktop Image', 'vl-cpt-alert' ) . '</strong>',
		) );
		
		rbm_cpts_do_field_media( array(
			'name' => 'popup_image_small', 
			'group' => 'alert_popup',
			'type' => 'image',
			'label' => '<strong>' . __( 'Mobile Image' , 'vl-cpt-alert' ) . '</strong>',
		) );

		rbm_cpts_init_field_group( 'alert_popup' );
		
	}

	function _mb_user_interaction() {

		global $post;

		rbm_cpts_do_field_select( array(
			'name' => 'user_interaction', 
			'group' => 'alert_user_interaction',
			'label' => '<strong>' . __( 'Method', 'vl-cpt-alert' ) . '</strong>',
			'default' => 'none',
			'options' => array(
				'none'           => __( 'None', 'vl-cpt-alert' ),
				'close_button'   => __( 'Close Button', 'vl-cpt-alert' ),
				'call_to_action' => __( 'Call To Action', 'vl-cpt-alert' ),
			),
		) );

		rbm_cpts_do_field_text( array(
			'name' => 'button_text',
			'group' => 'alert_user_interaction',
			'label' => '<strong>' . __( 'Button Text', 'vl-cpt-alert' ) . '</strong>',
			'description' => '<p class="description">' . __( 'Defaults to "Close" for "Close Button" or "Learn More" for "Call To Action"', 'vl-cpt-alert' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
		) );

		rbm_cpts_do_field_text( array(
			'name' => 'button_link', 
			'group' => 'alert_user_interaction',
			'label' => '<strong>' . __( 'Button Link', 'vl-cpt-alert' ) . '</strong>',
			'description' => '<p class="description">' . __( 'Only applies to Call To Action method.', 'vl-cpt-alert' ) . '</p>',
			'description_tip' => false,
			'description_placement' => 'after_label',
		) );

		rbm_cpts_do_field_checkbox( array(
			'name' => 'button_new_tab',
			'group' => 'alert_user_interaction',
			'label' => false,
			'options' => array(
				'1' => __( 'Open in New Tab', 'vl-cpt-alert' ),
			),
		) );

		rbm_cpts_init_field_group( 'alert_user_interaction' );

	}

	function _save_alert_information( $save_alert_ID ) {

		if ( get_post_type() === 'alert' && $_REQUEST['action'] == 'editpost' ) {

			$orig_alerts = get_option( 'vl_active_alerts' );
			$alerts      = $this->remove_alert( $save_alert_ID, $orig_alerts );

			if ( isset( $_POST['rbm_cpts_visibility_posts'] ) ) {

				$visibility_posts = $_POST['rbm_cpts_visibility_posts'];
				foreach ( $visibility_posts as $post_ID ) {

					$alerts['posts'][ $post_ID ][ $save_alert_ID ]           = true;
					$alerts['alerts'][ $save_alert_ID ]['posts'][ $post_ID ] = true;
				}
			}

			if ( isset( $_POST['rbm_cpts_visibility_taxonomies'] ) ) {

				$visibility_taxonomies = $_POST['rbm_cpts_visibility_taxonomies'];
				foreach ( $visibility_taxonomies as $taxonomy ) {

					$alerts['taxonomies'][ $taxonomy ][ $save_alert_ID ]           = true;
					$alerts['alerts'][ $save_alert_ID ]['taxonomies'][ $taxonomy ] = true;
				}
			}

			if ( isset( $_POST['rbm_cpts_visibility_post_types'] ) ) {

				$visibility_post_types = $_POST['rbm_cpts_visibility_post_types'];
				foreach ( $visibility_post_types as $post_type ) {

					$alerts['post_types'][ $post_type ][ $save_alert_ID ]           = true;
					$alerts['alerts'][ $save_alert_ID ]['post_types'][ $post_type ] = true;
				}
			}

			if ( $orig_alerts !== $alerts ) {
				update_option( 'vl_active_alerts', $alerts );
			}

			/*

			if ( isset( $_POST['rbm_cpts_visibility_sites'] ) ) {

				$orig_network_alerts = get_network_option( null, 'vl_alerts', array() );
				$network_alerts      = $this->remove_network_alert( $save_alert_ID, $orig_network_alerts );

				foreach ( $_POST['rbm_cpts_visibility_sites'] as $blog_ID ) {

					$network_alerts[ $blog_ID ][] = $save_alert_ID;

					// Prevent duplicates
					$network_alerts[ $blog_ID ] = array_unique( $network_alerts[ $blog_ID ] );
				}

				if ( $orig_network_alerts !== $network_alerts ) {
					update_network_option( null, 'vl_alerts', $network_alerts );
				}
			}

			*/

		}

		return;
	}

	function _update_alert_information( $delete_alert_ID ) {

		if ( get_post_type() === 'alert' ) {

			$alerts = $orig_alerts = get_option( 'vl_active_alerts', array() );
			$alerts = $this->remove_alert( $delete_alert_ID, $alerts );

			if ( $alerts !== $orig_alerts ) {
				update_option( 'vl_active_alerts', $alerts );
			}

			// Network alerts
			$network_alerts = $orig_network_alerts = get_network_option( null, 'vl_alerts', array() );
			$network_alerts = $this->remove_network_alert( $delete_alert_ID, $network_alerts );

			if ( $network_alerts !== $orig_network_alerts ) {
				update_network_option( null, 'vl_alerts', $network_alerts );
			}
		}
	}

	function alert_add_rest_fields() {

		register_rest_field( $this->post_type,
			'custom_meta',
			array(
				'get_callback' => array( $this, 'alert_rest_field_custom_meta' ),
			)
		);
	}

	function alert_rest_field_custom_meta( $object, $field_name, $request ) {

		$image = '';
		if ( $image_ID = rbm_cpts_get_field( 'popup_image', $object['id'] ) ) {
			if ( $image = wp_get_attachment_image_src( $image_ID, 'full' ) ) {
				$image = $image[0];
			}
		}

		return array(
			'content'          => $object['content']['raw'],
			'color'            => rbm_cpts_get_field( 'color', $object['id'] ),
			'type'             => rbm_cpts_get_field( 'type', $object['id'], 'inset-banner' ),
			'icon'             => rbm_cpts_get_field( 'icon', $object['id'] ),
			'time_range'       => rbm_cpts_get_field( 'time_range', $object['id'] ),
			'popup_image'      => $image,
			'user_interaction' => rbm_cpts_get_field( 'user_interaction', $object['id'] ),
			'button_text'      => rbm_cpts_get_field( 'button_text', $object['id'] ),
			'button_link'      => rbm_cpts_get_field( 'button_link', $object['id'] ),
			'button_new_tab'   => rbm_cpts_get_field( 'button_new_tab', $object['id'] ),
		);
	}

	private function remove_alert( $remove_alert_ID, $alerts ) {

		$skeleton = array(
			'posts'      => array(),
			'taxonomies' => array(),
			'post_types' => array(),
			'alerts'     => array(),
		);

		// Reset this alert's settings
		if ( ! empty( $alerts['alerts'][ $remove_alert_ID ] ) ) {

			foreach ( $alerts['alerts'][ $remove_alert_ID ] as $type => $type_IDs ) {
				foreach ( $type_IDs as $type_ID => $bool ) {
					unset( $alerts[ $type ][ $type_ID ][ $remove_alert_ID ] );
				}
			}

			// Clean empty values
			$alerts = wp_parse_args( array_remove_empty( $alerts ), $skeleton );

			$alerts['alerts'][ $remove_alert_ID ] = array();
		}

		return $alerts;
	}

	private function remove_network_alert( $remove_alert_ID, $alerts ) {

		foreach ( $alerts as $blog_ID => $blog_alerts ) {

			if ( ( $key = array_search( $remove_alert_ID, $blog_alerts ) !== false ) ) {
				unset( $alerts[ $blog_ID ][ $key ] );
			}
		}

		return $alerts;
	}
}