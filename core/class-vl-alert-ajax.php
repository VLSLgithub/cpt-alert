<?php
/**
 * Alert AJAX.
 *
 * @since 1.0.0
 * @package CPT_Alert
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class VL_Alert_AJAX
 *
 * Handles all AJAX requests for alerts.
 *
 * @since 1.0.0
 */
class VL_Alert_AJAX {

	/**
	 * VL_Alert_AJAX constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		add_action( 'rest_api_init', array( $this, 'create_endpoint' ) );
	}

	public function create_endpoint() {

		register_rest_route( 'vibrant-life/v1', '/alerts/', array(
			'methods' => 'POST',
			'callback' => array( $this, 'ajax_get_alerts' ),
		) );

	}

	/**
	 * Gets the alerts via AJAX.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	function ajax_get_alerts( $request ) {

		global $post;

		// Get dynamic args
		$args = array();
		foreach ( vl_get_alerts_default_args() as $arg_name => $arg_value ) {
			if ( isset( $request[ $arg_name ] ) ) {
				$args[ $arg_name ] = $_POST[ $arg_name ];
			}
		}

		// Setup the post object
		if ( isset( $args['post_id'] ) && (int) $args['post_id'] > 0 ) {

			$post = get_post( $args['post_id'] );
		}

		$alerts = vl_get_alerts( $args );

		wp_send_json_success( array( 'alerts' => $alerts ) );
	}
}