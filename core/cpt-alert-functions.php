<?php
/**
 * Provides helper functions.
 *
 * @since {{VERSION}}
 *
 * @package    CPTAlert
 * @subpackage CPTAlert/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since {{VERSION}}
 *
 * @return CPTAlert
 */
function CPTALERT() {
	return CPTAlert::getInstance();
}

/**
 * Gets the default args for retrieving/showing alerts.
 *
 * @since {{VERSION}}
 *
 * @return array
 */
function vl_get_alerts_default_args() {

	$post_ID = get_the_ID();
	$terms   = false;

	if ( $post_ID ) {

		$taxonomies = get_taxonomies( '', 'names' );
		if ( ! ( $terms = wp_get_object_terms( $post_ID, $taxonomies, array( 'fields' => 'ids' ) ) ) ) {
			$terms = false;
		}
	}

	$args = array(
		'post_id'      => get_the_ID(),
		'terms'        => $terms,
		'post_type'    => get_post_type(),
		'auto_show'    => '1',
		'show_global'  => '1',
		'show_banners' => '1',
		'show_popups'  => '1',
		'is_tax' => false,
		'is_single' => false,
	);
	
	if ( is_tax() ) { 
		// If the first result in a Taxonomy Archive also had an Alert set to show on it, it would normally force that Alert to also show on the Taxonomy Archive
		// Unsetting the first result's Post ID prevents this
		unset( $args['post_id'] );
		$args['is_tax'] = true;
	}
	else if ( is_single() ) {
		$args['is_single'] = true;
	}
	
	/**
	 * This Filter runs before any Ajax occurs, so you still have access to $wp_query
	 * 
	 * @since {{VERSION}}
	 */
	return apply_filters( 'vl_get_alerts_default_args', $args );
	
}

/**
 * Individual alert default args.
 *
 * @since {{VERSION}}
 *
 * @return array
 */
function vl_alert_default_args() {

	/**
	 * Filters the default alert args.
	 *
	 * @since {{VERSION}}
	 */
	return apply_filters( 'vl_alert_default_args', array(
		'post_ID'          => 0,
		'content'          => '',
		'color'            => 'default',
		'type'             => 'inset-banner',
		'icon'             => 'default',
		'time_range'       => '',
		'popup_image'      => '',
		'popup_image_small'=> '',
		'user_interaction' => 'none',
		'button_text'      => '',
		'button_link'      => '',
		'button_new_tab'   => '',
	) );
}

/**
 * Retrieves alerts.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 *
 * @return array
 */
function vl_get_alerts( $args = array() ) {

	static $network_alerts;

	$args = wp_parse_args( $args, vl_get_alerts_default_args() );

	/*

	$network_alerts = ! $network_alerts ? get_network_option( null, 'vl_alerts', false ) : $network_alerts;

	if ( $network_alerts &&
		isset( $network_alerts[ get_current_blog_id() ] ) ) {
		$site_alerts = $network_alerts[ get_current_blog_id() ];
	}

	*/

	$get_posts_args = array(
		'post_type'   => 'alert',
		'numberposts' => - 1,
		'status' => 'publish',
		'meta_query'  => array(
			'relation' => 'AND', // This allows us to make conditions that HAVE to be true as well as conditions that CAN be true
			'and-queries' => array(
				'relation' => 'AND',
			),
			'or-queries' => array(
				'relation' => 'OR',
			),
		),
	);

	/*

	if ( $args['show_popups'] == '0' ) {

		$get_posts_args['meta_query']['and-queries'][] = array(
			array(
				'key'     => 'rbm_cpts_type',
				'value'   => 'pop-up',
				'compare' => '!=',
			),
		);

	}
	
	if ( $args['show_banners'] == '0' ) {
		
		$get_posts_args['meta_query']['and-queries'][] = array(
			array(
				'key'     => 'rbm_cpts_type',
				'value'   => 'inset-banner',
				'compare' => '!=',
			),
		);

	}

	*/

	// Global alerts
	if ( $args['show_global'] != '0' ) {

		$get_posts_args['meta_query']['or-queries'][] = array(
			'key'     => 'rbm_cpts_visibility_everywhere',
			'value'   => '"1"',
			'compare' => 'LIKE',
		);
	}

	// Get alerts for post IDs
	if ( $args['post_id'] ) {

		// Specific Posts
		$get_posts_args['meta_query']['or-queries'][] = array(
			'key'     => 'rbm_cpts_visibility_posts',
			'value'   => '"' . $args['post_id'] . '"',
			'compare' => 'LIKE',
		);

		$location_id = $args['post_id'];
		if ( ! $args['is_single'] || $args['post_type'] !== 'facility' ) {

			$location_id = rbm_cpts_get_p2p_parent( 'facility', $args['post_id'] );

		}

		// Locations
		$get_posts_args['meta_query']['or-queries'][] = array(
			'key'     => 'rbm_cpts_visibility_locations',
			'value'   => '"' . $location_id . '"',
			'compare' => 'LIKE',
		);

	}

	// Get alerts for taxonomies
	if ( $args['terms'] ) {
		
		// If this is a Single Post but Terms are also defined
		if ( $args['is_single'] ) {
			
			$get_posts_args['meta_query']['or-queries'][] = array(
				'relation' => 'AND',
				array(
					'key'     => 'rbm_cpts_visibility_terms',
					'value'   => $args['terms'],
					'compare' => 'IN',
				),
				array(
					'key'     => 'rbm_cpts_show_term_alerts_on_single',
					'value'   => '"1"',
					'compare' => 'LIKE',
				),
			);
			
		}
		else {
			
			$get_posts_args['meta_query']['or-queries'][] = array(
				'key'     => 'rbm_cpts_visibility_terms',
				'value'   => $args['terms'],
				'compare' => 'IN',
			);
			
		}
	}

	// Get alerts for post types
	if ( $args['post_type'] ) {
		$get_posts_args['meta_query']['or-queries'][] = array(
			'key'     => 'rbm_cpts_visibility_post_types',
			'value'   => '"' . $args['post_type'] . '"',
			'compare' => 'LIKE',
		);
	}

	$alerts = array();
	if ( $alert_posts = get_posts( $get_posts_args ) ) {
		foreach ( $alert_posts as $alert_post ) {

			$type = rbm_cpts_get_field( 'type', $alert_post->ID, 'inset-banner' );

			$alert = array(
				'post_ID'          => $alert_post->ID,
				'content'          => $type == 'inset-banner' ?
					$alert_post->post_content : do_shortcode( wpautop( $alert_post->post_content ) ),
				'color'            => rbm_cpts_get_field( 'color', $alert_post->ID ),
				'type'             => $type,
				'icon'             => rbm_cpts_get_field( 'icon', $alert_post->ID ),
				'time_range'       => rbm_cpts_get_field( 'time_range', $alert_post->ID ),
				'popup_image'      => rbm_cpts_get_field( 'popup_image', $alert_post->ID ),
				'popup_image_small'=> rbm_cpts_get_field( 'popup_image_small', $alert_post->ID ),
				'user_interaction' => rbm_cpts_get_field( 'user_interaction', $alert_post->ID ),
				'button_text'      => rbm_cpts_get_field( 'button_text', $alert_post->ID ),
				'button_link'      => rbm_cpts_get_field( 'button_link', $alert_post->ID ),
				'button_new_tab'   => rbm_cpts_get_field( 'button_new_tab', $alert_post->ID ),
			);

			if ( $alert['popup_image'] && $image_src = wp_get_attachment_image_src( $alert['popup_image'], 'full' ) ) {
				$alert['popup_image'] = $image_src[0];
			}
			
			if ( $alert['popup_image_small'] && $image_src = wp_get_attachment_image_src( $alert['popup_image_small'], 'full' ) ) {
				$alert['popup_image_small'] = $image_src[0];
			}

			$alerts[] = $alert;
		}
	}

	/*

	// Network alerts
	if ( isset( $site_alerts ) ) {
		foreach ( $site_alerts as $site_alert_ID ) {

			$alert = get_post( $site_alert_ID );

			$type = $alert_json->custom_meta->type;

			$alert = array(
				'post_ID'          => $site_alert_ID,
				'content'          => $type == 'inset-banner' ? $alert_json->custom_meta->content : $alert_json->content->rendered,
				'color'            => $alert_json->custom_meta->color,
				'type'             => $type,
				'icon'             => $alert_json->custom_meta->icon,
				'time_range'       => $alert_json->custom_meta->time_range,
				'popup_image'      => $alert_json->custom_meta->popup_image,
				'user_interaction' => $alert_json->custom_meta->user_interaction,
				'button_text'      => $alert_json->custom_meta->button_text,
				'button_link'      => $alert_json->custom_meta->button_link,
				'button_new_tab'   => $alert_json->custom_meta->button_new_tab,
			);

			$alerts[] = $alert;
		}
	}

	*/

	/**
	 * Alerts to be shown.
	 *
	 * @since {{VERSION}}
	 */
	$alerts = apply_filters( 'vl_alerts_show_alerts', $alerts );

	// Alert settings
	if ( $alerts ) {
		foreach ( $alerts as $i => $alert ) {

			if ( isset( $alert['user_interaction'] ) && 
					   $alert['user_interaction'] == 'close' && 
					   empty( $alert['button_text'] ) ) {
				$alert['button_text'] = __( 'Close', 'vl-cpt-alert' );
			}

			if ( isset( $alert['user_interaction'] ) && 
					   $alert['user_interaction'] == 'call_to_action' && 
					   empty( $alert['button_text'] ) ) {
				$alert['button_text'] = __( 'Learn More', 'vl-cpt-alert' );
			}

			if ( isset( $alert['icon'] ) && 
					   $alert['icon'] == 'default' ) {
				$alert['icon'] = 'fas fa-exclamation-triangle';
			}

			if ( isset( $alert['time_range'] ) && 
					   $alert['time_range'] ) {

				$times   = explode( '-', $alert['time_range'] );
				$times_0 = explode( ':', $times[0] );
				$times_1 = explode( ':', $times[1] );

				$alert['time_range'] = array();

				$alert['time_range']['start']['hrs'] = $times_0[0];
				$alert['time_range']['start']['min'] = $times_0[1];

				$alert['time_range']['end']['hrs'] = $times_1[0];
				$alert['time_range']['end']['min'] = $times_1[1];
			}

			// Fill out any missing args
			$alert = wp_parse_args( $alert, vl_alert_default_args() );

			$alerts[ $i ] = $alert;
		}
	}
	
	/**
	 * Alerts to be shown.
	 *
	 * @since {{VERSION}}
	 */
	$alerts = apply_filters( 'vl_alerts_show_alerts', $alerts );

	return $alerts;
}

function vl_show_top_alerts( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'show_popups' => '1',
		'show_banners' => '0',
	) );

	$args = wp_parse_args( $args, vl_get_alerts_default_args() );

	$data = '';
	foreach ( $args as $arg_name => $arg_value ) {

		if ( is_array( $arg_value ) ) {
			$arg_value = json_encode( $arg_value );
		}

		$data .= " data-$arg_name=\"" . esc_attr( $arg_value ) . '"';
	}
	?>
	<div class="vl-alerts-container" <?php echo $data; ?>>
		<div class="vl-alert pop-up vl-alert-dummy" style="display: none;">
			<div class="vl-alert-container">
				
				<div class="vl-alert-content">
					
					<div class="vl-alert-image show-for-medium"></div>
					
					<div class="vl-alert-image show-for-small-only"></div>
					
					<div class="row small-collapse">
					
						<div class="small-1 columns small-icon-container">
							<span class="vl-alert-icon show-for-small-only" aria-hidden="true"></span>
						</div>

						<div class="text-content small-9 small-pull-2 medium-pull-0 medium-12 columns">

							<div class="vl-alert-text"></div>

							<div class="show-for-small-only">
								<a href="#" class="vl-alert-button"></a>
							</div>

						</div>
						
					</div>
					
					<a href="#" class="vl-alert-button show-for-medium"></a>

				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Outputs alert container HTML.
 *
 * @since {{VERSION}}
 *
 * @param array $args
 */
function vl_show_inset_alerts( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'show_popups' => '0',
		'show_banners' => '1',
	) );

	$args = wp_parse_args( $args, vl_get_alerts_default_args() );

	$data = '';
	foreach ( $args as $arg_name => $arg_value ) {

		if ( is_array( $arg_value ) ) {
			$arg_value = json_encode( $arg_value );
		}

		$data .= " data-$arg_name=\"" . esc_attr( $arg_value ) . '"';
	}
	?>
	<div class="vl-alerts-container" <?php echo $data; ?>>
		<div class="vl-alert inset-banner vl-alert-dummy" style="display: none;">
			<div class="vl-alert-container">
				<div class="vl-alert-content">

					<div class="vl-alert-text-container">
						<span class="vl-alert-icon" aria-hidden="true"></span>
						<span class="vl-alert-text"></span>
					</div>

					<div class="vl-alert-button-container">
						<a href="#" class="vl-alert-button"></a>
					</div>

				</div>
			</div>
		</div>
	</div>
	<?php
}

if ( ! function_exists( 'array_remove_empty' ) ) {
	function array_remove_empty( $haystack ) {
		foreach ( $haystack as $key => $value ) {
			if ( is_array( $value ) ) {
				$haystack[ $key ] = array_remove_empty( $haystack[ $key ] );
			}

			if ( empty( $haystack[ $key ] ) ) {
				unset( $haystack[ $key ] );
			}
		}

		return $haystack;
	}
}