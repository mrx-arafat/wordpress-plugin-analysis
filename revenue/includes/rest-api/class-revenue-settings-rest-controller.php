<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * REST API for settings.
 *
 * @package Revenue
 */

namespace Revenue;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

/**
 * REST API for settings.
 */
class Revenue_Settings_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $namespace = 'revenue/v1';

	/**
	 * Route name
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $base = 'settings';



	/**
	 * Register all routes related with stores
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'get_settings_permission_check' ), // Provide a permission callback or remove if not needed.
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_setting' ),
				'permission_callback' => array( $this, 'get_update_setting_permission_check' ),
			)
		);
	}

	/**
	 * Get setting permission check.
	 *
	 * @return bool
	 */
	public function get_settings_permission_check() {
		return current_user_can( 'read' );
	}

	/**
	 * Get update setting permission check.
	 *
	 * @return bool
	 */
	public function get_update_setting_permission_check() {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return $has_permission;
	}


	/**
	 * Get all settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		return rest_ensure_response( revenue()->get_setting() );
	}

	/**
	 * Update a setting.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_setting( $request ) {
		$key   = $request->get_param( 'key' );
		$value = $request->get_param( 'value' );

		if ( empty( $key ) ) {
			return new WP_Error( 'invalid_key', 'Invalid setting key', array( 'status' => 400 ) );
		}

		// Update the setting using update_option.
		$updated = revenue()->set_setting( $key, $value );

		if ( $updated ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => 'Setting updated successfully',
				)
			);
		} else {
			return new WP_Error( 'update_failed', 'Failed to update setting', array( 'status' => 500 ) );
		}
	}

}
