<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * REST API: Revenue_Campaign_REST_Controller class
 *
 * @package Revenue
 */

namespace Revenue;

//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison


use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use Exception;
use WC_DateTime;
use DateTimeZone;
use DateTime;

/**
 * REST API controller for managing revenue campaigns.
 *
 * Handles CRUD operations for campaigns through the WordPress REST API.
 *
 * @since 1.0.0
 */
class Revenue_Campaign_REST_Controller extends WP_REST_Controller {

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
	protected $base = 'campaigns';

	/**
	 * Post type
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $post_type = 'revenue-campaign';

	/**
	 * Post status
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $post_status = array( 'publish', 'draft' );


	/**
	 * Must exist campaign meta keys
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $must_exist_meta_keys = array();

	/**
	 * Total sales data
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $total_sales_data = array();

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
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'revenue' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_campaign_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					'permission_callback' => array( $this, 'create_campaign_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/bulk-delete',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_items' ),
				'permission_callback' => array( $this, 'delete_campaign_permissions_check' ),
				'args'                => array(
					'ids' => array(
						'description' => __( 'An array of campaign IDs to delete.', 'revenue' ),
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
						'required'    => true,
					),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/bulk-update-status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'bulk_update_campaign_status' ),
				'permission_callback' => array( $this, 'delete_campaign_permissions_check' ),
				'args'                => array(
					'ids'    => array(
						'description' => __( 'An array of campaign IDs to delete.', 'revenue' ),
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
						'required'    => true,
					),
					'status' => array(
						'description'       => __( 'The new status for the campaigns.', 'revenue' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => function ( $param, $request, $key ) {
							return in_array( $param, array( 'publish', 'draft', 'pending' ) );
						},
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)/',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'revenue' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_single_campaign_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
					'permission_callback' => array( $this, 'update_campaign_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_campaign_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			"/{$this->base}/(?P<id>[\d]+)/clone",
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Campaign id to be cloned.', 'revenue' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clone_item' ),
					'args'                => array(),
					'permission_callback' => array( $this, 'clone_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/support/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_support_callback' ),
					'args'                => array(),
					'permission_callback' => array( $this, 'get_campaign_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Get Support Callaback.
	 *
	 * @param  array $request Request.
	 * @return array
	 */
	public function get_support_callback( $request ) {
		$action = sanitize_text_field( $request['type'] );

		if ( 'support_data' === $action ) {
			$user_info = get_userdata( get_current_user_id() );
			$name      = $user_info->first_name . ( $user_info->last_name ? ' ' . $user_info->last_name : '' );
			return array(
				'success' => true,
				'data'    => array(
					'name'  => $name ? $name : $user_info->user_login,
					'email' => $user_info->user_email,
				),
			);
		} elseif ( 'support_action' === $action ) {
			$api_params    = array(
				'user_name'  => sanitize_text_field( $request['name'] ),
				'user_email' => sanitize_email( $request['email'] ),
				'subject'    => sanitize_text_field( $request['subject'] ),
				'desc'       => sanitize_textarea_field( $request['desc'] ),
			);
			$response      = wp_remote_get(
				'https://wpxpo.com/wp-json/v2/support_mail',
				array(
					'method'  => 'POST',
					'timeout' => 120,
					'body'    => $api_params,
				)
			);
			$response_data = json_decode( $response['body'] );
			$success       = ( isset( $response_data->success ) && $response_data->success ) ? true : false;

			return array(
				'success' => $success,
				'message' => $success ? __( 'New Support Ticket has been Created.', 'revenue' ) : __( 'New Support Ticket is not Created Due to Some Issues.', 'revenue' ),
			);
		}
	}

	/**
	 * Get campaign permissions check
	 *
	 * @param bool $request Request.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function get_campaign_permissions_check( $request = false ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_get_campaigns_permission_check', $has_permission, $request );
	}

	/**
	 * Create_campaign_permissions_check
	 *
	 * @param bool $request Request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function create_campaign_permissions_check( $request = false ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_create_campaign_permission_check', $has_permission, $request );
	}

	/**
	 * Get_single_campaign_permissions_check
	 *
	 * @param bool $request Request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function get_single_campaign_permissions_check( $request = false ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_get_campaign_permission_check', $has_permission, $request );
	}

	/**
	 * Update_campaign_permissions_check
	 *
	 * @param bool $request Request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function update_campaign_permissions_check( $request = false ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_update_campaign_permission_check', $has_permission, $request );
	}

	/**
	 * Delete campaign permission checking
	 *
	 * @param bool $request Request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function delete_campaign_permissions_check( $request = false ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_delete_campaign_permission_check', $has_permission, $request );
	}
	/**
	 * Clone campaign permission checking
	 *
	 * @param bool $request Request.
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function clone_item_permissions_check( $request = false ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_clone_campaign_permission_check', $has_permission, $request );
	}


	/**
	 * Prepare a single campaign for create or update.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_Error|stdClass $data Campaign data.
	 */
	protected function prepare_item_for_database( $request ) {
		$data = array();

		$schema = $this->get_item_schema();

		$data_keys = array_keys( $schema['properties'] );

		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( isset( $request[ $key ] ) ) {
				if ( 'name' === $key ) {
					$data['name'] = wp_filter_post_kses( $value );
				} else {
					$data[ sanitize_key( $key ) ] = $value;
				}
			}
		}

		if ( isset( $request['schedule_start_date'], $request['schedule_start_time'] ) && ! empty( $request['schedule_start_date'] ) && ! empty( $request['schedule_start_time'] ) ) {
			$start_date_time                  = sanitize_text_field( $request['schedule_start_date'] ) . ' ' . sanitize_text_field( $request['schedule_start_time'] );
			$date                             = new DateTime( $start_date_time );
			$timestamp                        = $date->getTimestamp();
			$data['campaign_start_date_time'] = gmdate( 'Y-m-d H:i:s', $timestamp );
		}

		if ( isset( $request['schedule_end_time_enabled'], $request['schedule_end_date'], $request['schedule_end_time'] ) && 'yes' === sanitize_key( $request['schedule_end_time_enabled'] ) && ! empty( $request['schedule_end_date'] ) && ! empty( $request['schedule_end_time'] ) ) {
			$end_date_time                  = sanitize_text_field( $request['schedule_end_date'] ) . ' ' . sanitize_text_field( $request['schedule_end_time'] );
			$date                           = new DateTime( $end_date_time );
			$timestamp                      = $date->getTimestamp(); // Convert to milliseconds.
			$data['campaign_end_date_time'] = gmdate( 'Y-m-d H:i:s', $timestamp );
		}

		/**
		 * Filter the query_vars used in `get_items` for the constructed query.
		 *
		 * @param array       $data An array representing a single item prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( 'revenue_rest_pre_insert_campaign', $data, $request );
	}

	/**
	 * Prepare a single campaign output for response.
	 *
	 * @since 1.0.0
	 *
	 * @param  int             $campaign_id Campaign id.
	 * @param  WP_REST_Request $request     Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $campaign_id, $request ) {
		$data = revenue()->get_campaign_data( $campaign_id, 'raw' );

		$data = revenue()->set_product_image_trigger_item_response( $data );

		if ( ! isset( $data['campaign_builder_view'] ) ) {
			$data['campaign_builder_view'] = 'list';
		}

		if ( isset( $data['campaign_start_date_time'] ) ) {
			$start_date_time = new DateTime( $data['campaign_start_date_time'] );

			$start_date = $start_date_time->format( 'Y-m-d' );
			$start_time = $start_date_time->format( 'H:i' );

			$data['schedule_start_date'] = $start_date;
			$data['schedule_start_time'] = $start_time;
		}
		if ( isset( $data['campaign_end_date_time'] ) ) {
			$end_date_time = new DateTime( $data['campaign_end_date_time'] );
			$end_date      = $end_date_time->format( 'Y-m-d' );
			$end_time      = $end_date_time->format( 'H:i' );

			$data['schedule_end_date'] = $end_date;
			$data['schedule_end_time'] = $end_time;
		}

		$data    = apply_filters( 'revenue_rest_before_prepare_campaign', $data, $campaign_id );
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param $post       Campaign id.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( 'revenue_rest_prepare_campaign', $response, $data, $request );
	}



	/**
	 * Create a single campaign.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		if ( ! empty( $request['id'] ) ) {
			unset( $request['id'] );
		}

		$campaign_id = 0;

		try {

			// Set Default Value.
			if ( isset( $request['campaign_placement'] ) && empty( $request['campaign_placement'] ) ) {
				$request['campaign_placement'] = 'multiple';
			}
			if ( isset( $request['campaign_recommendation'] ) && empty( $request['campaign_recommendation'] ) ) {
				$request['campaign_recommendation'] = 'manual';
			}
			if ( isset( $request['campaign_trigger_type'] ) && empty( $request['campaign_trigger_type'] ) ) {
				$request['campaign_trigger_type'] = 'products';
			}

			if ( isset( $request['campaign_type'] ) && 'spending_goal' == $request['campaign_type'] || 'free_shipping_bar' == $request['campaign_type'] ) {
				$request['campaign_trigger_type'] = 'all_products';
			}

			$campaign_id = $this->save_campaign( $request );
			/**
			 * Fires after a single item is created or updated via the REST API.
			 *
			 * @param int             $campaign_id      campaign data.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating item, false when updating.
			 */
			do_action( 'revenue_rest_insert_campaign', $campaign_id, $request, true );
			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $campaign_id, $request );
			$response = rest_ensure_response( $response );
			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $campaign_id ) ) );

			return $response;
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Delete a single item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}
		$id = (int) $request['id'];

		if ( empty( $id ) ) {
			return new WP_Error( 'revenue_rest_campaign_invalid_id', __( 'Invalid campaign ID.', 'revenue' ), array( 'status' => 404 ) );
		}

		if ( ! $this->delete_campaign_permissions_check() ) {
			return new WP_Error( 'revenue_rest_user_cannot_delete_campaign', __( 'Sorry, you are not allowed to delete this campaign.', 'revenue' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$campaign = revenue()->get_campaign_data( $id );

		$response = $this->prepare_item_for_response( $id, $request );

		do_action( 'revenue_before_delete_campaign', $id );

		$campaign['campaign_trigger_exclude_items'] = array();
		$campaign['campaign_trigger_items	']      = array();

		$this->update_campaign_triggers( $id, $campaign, true );

		$result = revenue()->delete_campaign( $id );

		do_action( 'revenue_delete_campaign', $id );

		if ( ! $result ) {
			return new WP_Error( 'revenue_rest_cannot_delete', __( 'The campaign cannot be deleted.', 'revenue' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single item is deleted or trashed via the REST API.
		 *
		 * @param object           $campaign     The deleted item.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'revenue_rest_delete_campaign', $campaign, $response, $request );

		revenue()->clear_campaign_runtime_cache( $id );

		return $response;
	}

	/**
	 * Delete multiple items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_items( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_error', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		$ids = $request->get_param( 'ids' );
		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return new WP_Error( 'revenue_rest_invalid_ids', __( 'Invalid campaign IDs.', 'revenue' ), array( 'status' => 404 ) );
		}

		if ( ! $this->delete_campaign_permissions_check() ) {
			return new WP_Error( 'revenue_rest_user_cannot_delete_campaigns', __( 'Sorry, you are not allowed to delete these campaigns.', 'revenue' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$response_data = array();
		foreach ( $ids as $id ) {
			$id       = (int) $id;
			$campaign = revenue()->get_campaign_data( $id );

			do_action( 'revenue_before_delete_campaign', $id );

			$campaign['campaign_trigger_exclude_items'] = array();
			$campaign['campaign_trigger_items	']      = array();

			$this->update_campaign_triggers( $id, $campaign, true );

			$result = revenue()->delete_campaign( $id );

			if ( $result ) {
				do_action( 'revenue_delete_campaign', $id );
				revenue()->clear_campaign_runtime_cache( $id );
				$response_data[] = $id;
			}
		}

		return rest_ensure_response( $response_data );
	}


	/**
	 * Update a single product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}
		$campaign_id = (int) $request['id'];

		if ( empty( $campaign_id ) ) {
			return new WP_Error( 'revenue_rest_campaign_invalid_id', __( 'Campaign id is invalid.', 'revenue' ), array( 'status' => 400 ) );
		}

		try {

			$campaign_id = $this->update_campaign( $request );
			revenue()->clear_campaign_runtime_cache( $campaign_id );

			$campaign = revenue()->get_campaign_data( $campaign_id );

			/**
			 * Fires after a single item is created or updated via the REST API.
			 *
			 * @param $campaign  Post data.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating item, false when updating.
			 */
			do_action( 'revenue_rest_update_campaign', $campaign, $request, false );
			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $campaign_id, $request );

			return rest_ensure_response( $response );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Get a single campaign.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		$id = (int) $request['id'];

		if ( empty( $id ) ) {
			return new WP_Error( "revenue_rest_invalid_{$this->post_type}_id", __( 'Invalid ID.', 'revenue' ), array( 'status' => 404 ) );
		}

		$data = $this->prepare_item_for_response( $id, $request );

		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Clone a campaign.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function clone_item( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}
		$id = (int) $request['id'];
		if ( empty( $id ) ) {
			return new WP_Error( "revenue_rest_invalid_{$this->post_type}_id", __( 'Invalid ID.', 'revenue' ), array( 'status' => 404 ) );
		}

		$old_campaign = revenue()->get_campaign_data( $id );
		$old_campaign = revenue()->set_product_image_trigger_item_response( $old_campaign, true );

		if ( ! $old_campaign ) {
			return new WP_Error( "revenue_rest_invalid_{$this->post_type}_campaign", __( 'Invalid Campaign.', 'revenue' ), array( 'status' => 404 ) );
		}

		unset( $old_campaign['id'] );

		$old_campaign['campaign_name'] = __( 'Duplicate of ', 'revenue' ) . $old_campaign['campaign_name'];

		try {
			$campaign_id = $this->save_campaign( $old_campaign, true );
			/**
			 * Fires after a single item is created or updated via the REST API.
			 *
			 * @param int             $campaign_id      campaign data.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating item, false when updating.
			 */
			do_action( 'revenue_rest_clone_campaign', $campaign_id, $request, true );
			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $campaign_id, $request );
			$response = rest_ensure_response( $response );
			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $campaign_id ) ) );

			return $response;
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Get a collection of campaigns.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		global $wpdb;
		$total_campaign = $wpdb->get_row( "SELECT COUNT(*) as total FROM {$wpdb->prefix}revenue_campaigns" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$paginations    = array( 'total' => $total_campaign->total );
		$args           = array();
		$where_clause   = array();
		if ( isset( $request['s'] ) && ! empty( $request['s'] ) ) {
			$args['s'] = sanitize_text_field( $request['s'] );
			$args['s'] = stripslashes( $args['s'] );
			$args['s'] = str_replace( array( "\r", "\n" ), '', $args['s'] );
			$like      = '%' . $wpdb->esc_like( $args['s'] ) . '%';

			if ( is_numeric( $args['s'] ) ) {
				$where_clause[] = "(campaigns.campaign_name LIKE '{$like}' OR campaigns.id = {$args['s']})";
			} else {
				$where_clause[] = "campaigns.campaign_name LIKE '{$like}'";
			}
		}

		if ( isset( $request['campaign_type'] ) && ! empty( $request['campaign_type'] ) ) {

			$campaign_type = sanitize_text_field( $request['campaign_type'] );
			$campaign_type = str_replace( array( "\r", "\n" ), '', $campaign_type );

			if ( in_array( $campaign_type, array_keys( revenue()->get_campaign_types() ) ) ) {
				$where_clause[] = $wpdb->prepare( 'campaigns.campaign_type=%s', $campaign_type );
			} else {
				return new WP_Error( 'revenue_rest_campaign_type_not_exist', __( 'This campaign type does not exist.', 'revenue' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['campaign_status'] ) && ! empty( $request['campaign_status'] ) ) {

			$campaign_status = sanitize_text_field( $request['campaign_status'] );
			$campaign_status = str_replace( array( "\r", "\n" ), '', $campaign_status );

			if ( in_array( $campaign_status, array_keys( revenue()->get_campaign_statuses() ) ) ) {
				$where_clause[] = $wpdb->prepare( 'campaigns.campaign_status=%s', $campaign_status );
			} else {
				return new WP_Error( 'revenue_rest_campaign_invalid_status', __( 'Campaign status is invalid!.', 'revenue' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['campaign_author'] ) && ! empty( $request['campaign_author'] ) ) {

			$campaign_author = intval( sanitize_text_field( $request['campaign_author'] ) );

			if ( $campaign_author ) {
				$where_clause[] = $wpdb->prepare( 'campaigns.campaign_author=%s', $campaign_author );
			} else {
				return new WP_Error( 'revenue_rest_campaign_invalid_author', __( 'Campaign author id is invalid!.', 'revenue' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['campaign_recommendation'] ) && ! empty( $request['campaign_recommendation'] ) ) {

			$campaign_recommendation = sanitize_text_field( $request['campaign_recommendation'] );
			$campaign_recommendation = str_replace( array( "\r", "\n" ), '', $campaign_recommendation );

			if ( $campaign_recommendation ) {
				$where_clause[] = $wpdb->prepare( 'campaigns.campaign_recommendation=%s', $campaign_recommendation );
			} else {
				return new WP_Error( 'revenue_rest_campaign_recommendation_is_empty', __( 'Campaign recommendation is empty!', 'revenue' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['campaign_inpage_position'] ) && ! empty( $request['campaign_inpage_position'] ) ) {

			$campaign_inpage_position = sanitize_text_field( $request['campaign_inpage_position'] );
			$campaign_inpage_position = str_replace( array( "\r", "\n" ), '', $campaign_inpage_position );

			if ( $campaign_inpage_position ) {
				$where_clause[] = $wpdb->prepare( 'campaigns.campaign_inpage_position=%s', $campaign_inpage_position );
			} else {
				return new WP_Error( 'revenue_rest_campaign_inpage_position_is_empty', __( 'Campaign in page position is empty!', 'revenue' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['campaign_display_style'] ) && ! empty( $request['campaign_display_style'] ) ) {

			$campaign_display_style = sanitize_text_field( $request['campaign_display_style'] );
			$campaign_display_style = str_replace( array( "\r", "\n" ), '', $campaign_display_style );

			if ( $campaign_display_style ) {
				$where_clause[] = $wpdb->prepare( 'campaigns.campaign_display_style=%s', $campaign_display_style );
			} else {
				return new WP_Error( 'revenue_rest_campaign_display_style_is_empty', __( 'Campaign display style is empty!', 'revenue' ), array( 'status' => 400 ) );
			}
		}

		if ( isset( $request['range_query'] ) && ! empty( $request['range_query'] ) ) {
			$from = array();
			$to   = array();

			if ( isset( $request['range_query']['from'] ) && ! empty( $request['range_query']['from'] ) ) {

				if ( isset( $request['range_query']['from']['date_created'] ) && ! empty( $request['range_query']['from']['date_created'] ) ) {
					$date_created = sanitize_text_field( $request['range_query']['from']['date_created'] );
					$date_created = str_replace( array( "\r", "\n" ), '', $date_created );
					if ( $date_created ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.date_created>=%s', $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $date_created ) ) ) );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign created date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}

				if ( isset( $request['range_query']['from']['date_modified'] ) && ! empty( $request['range_query']['from']['date_modified'] ) ) {
					$date_modified = sanitize_text_field( $request['range_query']['from']['date_modified'] );
					$date_modified = str_replace( array( "\r", "\n" ), '', $date_modified );
					if ( $date_modified ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.date_modified>=%s', $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $date_modified ) ) ) );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign modified date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}

				if ( isset( $request['range_query']['from']['start_date'] ) && ! empty( $request['range_query']['from']['start_date'] ) ) {
					$start_date = sanitize_text_field( $request['range_query']['from']['start_date'] );
					$start_date = str_replace( array( "\r", "\n" ), '', $start_date );
					$start_date = $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $start_date ) ) );

					if ( $start_date ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.campaign_start_date_time>=%s', $start_date );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign start date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}

				if ( isset( $request['range_query']['from']['end_date'] ) && ! empty( $request['range_query']['from']['end_date'] ) ) {
					$end_date = sanitize_text_field( $request['range_query']['from']['end_date'] );
					$end_date = str_replace( array( "\r", "\n" ), '', $end_date );
					$end_date = $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $end_date ) ) );

					if ( $end_date ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.campaign_end_date_time>=%s', $end_date );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign end date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}
			}

			if ( isset( $request['range_query']['to'] ) && ! empty( $request['range_query']['to'] ) ) {
				if ( isset( $request['range_query']['to']['date_created'] ) && ! empty( $request['range_query']['to']['date_created'] ) ) {
					$date_created = sanitize_text_field( $request['range_query']['to']['date_created'] );
					$date_created = str_replace( array( "\r", "\n" ), '', $date_created );
					if ( $date_created ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.date_created<=%s', $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $date_created ) ) ) );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign created date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}

				if ( isset( $request['range_query']['to']['date_modified'] ) && ! empty( $request['range_query']['to']['date_modified'] ) ) {
					$date_modified = sanitize_text_field( $request['range_query']['to']['date_modified'] );
					$date_modified = str_replace( array( "\r", "\n" ), '', $date_modified );
					if ( $date_modified ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.date_modified<=%s', $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $date_modified ) ) ) );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign modified date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}

				if ( isset( $request['range_query']['to']['start_date'] ) && ! empty( $request['range_query']['to']['start_date'] ) ) {
					$start_date = sanitize_text_field( $request['range_query']['to']['start_date'] );
					$start_date = str_replace( array( "\r", "\n" ), '', $start_date );
					$start_date = $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $start_date ) ) );

					if ( $start_date ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.campaign_start_date_time<=%s', $start_date );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign start date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}

				if ( isset( $request['range_query']['to']['end_date'] ) && ! empty( $request['range_query']['to']['end_date'] ) ) {
					$end_date  = sanitize_text_field( $request['range_query']['to']['end_date'] );
					$end_date  = str_replace( array( "\r", "\n" ), '', $end_date );
					$end_date  = $this->build_mysql_datetime( gmdate( 'Y-m-d', strtotime( $end_date ) ) );
					$end_date  = new DateTime( $end_date );
					$timestamp = $end_date->getTimestamp() * 1000; // Convert to milliseconds.
					if ( $end_date ) {
						$where_clause[] = $wpdb->prepare( 'campaigns.campaign_end_date_time<=%s', $timestamp );
					} else {
						return new WP_Error( 'revenue_rest_campaign_invalid_date', __( 'Campaign end date is invalid!', 'revenue' ), array( 'status' => 400 ) );
					}
				}
			}
		}

		$limit_query = '';
		// Per page.
		if ( isset( $request['per_page'] ) && ! empty( $request['per_page'] ) ) {
			$limit                   = intval( sanitize_text_field( $request['per_page'] ) );
			$limit_query             = $wpdb->prepare( 'limit %d;', $limit );
			$paginations['per_page'] = $limit;
		}

		if ( isset( $request['paged'] ) && ! empty( $request['paged'] ) ) {

			$limit       = isset( $request['per_page'] ) && ! empty( $request['per_page'] ) ? intval( sanitize_text_field( $request['per_page'] ) ) : 10;
			$page_number = intval( sanitize_text_field( $request['paged'] ) );
			$offset      = ( $page_number - 1 ) * $limit;
			$limit_query = $wpdb->prepare( 'limit %d offset %d;', $limit, $offset );

			$paginations['paged'] = $page_number;
		}

		$order_by_query        = '';
		$valid_order_by_colums = array( 'campaign_name', 'date_created', 'date_modified', 'start_date', 'end_date', 'id', 'campaign_total_order', 'conversion_rate', 'total_impressions', 'total_add_to_cart', 'total_checkout', 'total_rejections', 'total_orders', 'total_sales' );

		if ( isset( $request['order_by'] ) && in_array( $request['order_by'], $valid_order_by_colums ) ) {
			$order_by = sanitize_text_field( $request['order_by'] );
			$is_asc   = isset( $request['order'] ) ? 'asc' === sanitize_text_field( $request['order'] ) : false;

			$order_by_query = $is_asc ? $wpdb->prepare( "order by {$order_by} asc" ) : $wpdb->prepare( "order by {$order_by} desc" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$data_keys     = isset( $request['data_keys'] ) ? $request['data_keys'] : array();
		$select_clause = 'campaigns.id,campaigns.campaign_type, campaigns.campaign_name, campaigns.campaign_status, ';
		$join_clause   = '';
		$from          = "{$wpdb->prefix}revenue_campaigns AS campaigns";

		$has_analytics_data   = false;
		$has_order_stats_data = false;

		foreach ( $data_keys as $key => $data_key ) {
			switch ( $data_key ) {
				case 'add_to_cart':
					$select_clause     .= 'analytics.total_add_to_cart, ';
					$has_analytics_data = true;

					break;
				case 'checkout_count':
					$select_clause     .= 'analytics.total_checkout, ';
					$has_analytics_data = true;
					break;
				case 'total_sales':
					$select_clause       .= 'stats.total_sales as total_sales, ';
					$has_order_stats_data = true;
					break;
				case 'orders_count':
					$select_clause       .= 'stats.order_count as total_orders, ';
					$has_order_stats_data = true;
					break;
				case 'rejection_count':
					$select_clause     .= 'analytics.total_rejections, ';
					$has_analytics_data = true;
					break;
				case 'conversion_rate':
					$select_clause       .= 'ROUND(
						CASE
							WHEN COALESCE(analytics.total_impressions, 0) > 0 THEN (stats.order_count / analytics.total_impressions) * 100
							ELSE 0
						END,
						2
					) AS conversion_rate, ';
					$has_analytics_data   = true;
					$has_order_stats_data = true;
					break;
				case 'impression_count':
					$select_clause     .= esc_sql( 'analytics.total_impressions, ' );
					$has_analytics_data = true;
					break;

				default:
					// code...
					break;
			}
		}

		if ( $has_analytics_data ) {
			$join_clause .= " LEFT JOIN (
			SELECT
			    campaign_id,
			    SUM(add_to_cart_count) AS total_add_to_cart,
			    SUM(checkout_count) AS total_checkout,
			    SUM(order_count) AS total_orders,
			    SUM(impression_count) AS total_impressions,
			    SUM(rejection_count) AS total_rejections
			FROM
                {$wpdb->prefix}revenue_campaign_analytics
			GROUP BY
			    campaign_id
            ) AS analytics
            ON campaigns.id = analytics.campaign_id ";
		}

		if ( $has_order_stats_data ) {
			$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT order_id, meta_value AS campaign_id FROM {$wpdb->prefix}wc_orders_meta " : "select  post_id as order_id, meta_value as campaign_id from {$wpdb->prefix}postmeta ";

			$join_clause .= " LEFT JOIN (
                            select count(DISTINCT orders.order_id) as order_count,SUM(COALESCE(order_stats.total_sales, 0)) as total_sales, orders.campaign_id as campaign_id from {$wpdb->prefix}wc_order_stats as order_stats
                            inner join (
                            $order_meta_select where meta_key='_revx_campaign_id' and meta_value IS NOT NULL
                            )
                            orders ON (order_stats.order_id = orders.order_id OR order_stats.parent_id = orders.order_id)
                            group by orders .campaign_id
                            )
                            stats ON campaigns.id = stats.campaign_id

							";
		}

		$select_clause = rtrim( $select_clause, ', ' );

		$sql = "
			SELECT
				$select_clause
			FROM
				$from
			$join_clause
		";

		if ( ! empty( $where_clause ) ) {
			$where = implode( ' AND ', $where_clause );
			$sql  .= "where {$where}"; //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( ! empty( $order_by_query ) ) {
			$sql .= ' ' . $order_by_query;
		}

		if ( ! empty( $limit_query ) ) {
			$sql .= ' ' . $limit_query;
		}
		// echo '<pre>'; print_r($sql); echo '</pre>';

		if ( empty( $select_clause ) || 0 == $total_campaign->total ) {
			$results = array();
		} else {
			$results = $wpdb->get_results( $sql ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		foreach ( $results as $key => $value ) {
			$id                          = $value->id;
			$results[ $key ]->stats_data = $this->get_campaign_stats( $id, $data_keys );

			$_campaign = revenue()->get_campaign_data( $id );
			$_campaign = revenue()->set_product_image_trigger_item_response( $_campaign );

			$trigger_items     = array();
			$excluded_products = array();

			if ( 'all_products' == $_campaign['campaign_trigger_type'] ) {
				$results[ $key ]->triggers = 'all_products';

				$results[ $key ]->campaign_trigger_type = 'all_products';
			} elseif ( 'products' == $_campaign['campaign_trigger_type'] ) {
				$trigger_items = $_campaign['campaign_trigger_items'];

				$results[ $key ]->triggers              = $trigger_items;
				$results[ $key ]->campaign_trigger_type = 'products';
			} elseif ( 'category' == $_campaign['campaign_trigger_type'] ) {
				$trigger_items                          = $_campaign['campaign_trigger_items'];
				$results[ $key ]->triggers              = $trigger_items;
				$results[ $key ]->campaign_trigger_type = 'category';
			} else {
				$trigger_items                          = $_campaign['campaign_trigger_items'];
				$results[ $key ]->triggers              = $trigger_items;
				$results[ $key ]->campaign_trigger_type = $_campaign['campaign_trigger_type'];
			}

			$results[ $key ] = apply_filters( 'revenue_campaign_get_items', $results[ $key ], $id );
		}

		$response = array(
			'paginations' => $paginations,
			'campaigns'   => $results,
		);

		$response = rest_ensure_response( $response );

		return $response;
	}


	/**
	 * Get Campaign Stats.
	 *
	 * @param  string|int $campaign_id Campaign Id.
	 * @param  array      $data_keys   Data keys.
	 * @return array.
	 */
	public function get_campaign_stats( $campaign_id, $data_keys ) {
		global $wpdb;

		// Get campaign start date.
		$campaign_start_date = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT DATE(date_created) FROM {$wpdb->prefix}revenue_campaigns WHERE id = %d",
				$campaign_id
			)
		);

		$is_custom_orders = revenue()->is_custom_orders_table_usages_enabled();
		$meta_table       = $is_custom_orders ? "{$wpdb->prefix}wc_orders_meta" : "{$wpdb->prefix}postmeta";
		$id_column        = $is_custom_orders ? 'order_id' : 'post_id';

		$analytics_subquery = "
			SELECT DATE(`date`) AS date,
				SUM(impression_count) AS impression_count,
				SUM(add_to_cart_count) AS add_to_cart_count,
				SUM(rejection_count) AS rejection_count,
				SUM(checkout_count) AS checkout_count
			FROM {$wpdb->prefix}revenue_campaign_analytics
			WHERE campaign_id = %d
			GROUP BY DATE(`date`)
		";

			$orders_subquery = "
			SELECT DATE(os.date_created) AS date,
				SUM(os.total_sales) AS total_sales,
				SUM(CASE WHEN os.parent_id = 0 THEN 1 ELSE 0 END) AS orders_count
			FROM {$wpdb->prefix}wc_order_stats os
			INNER JOIN $meta_table meta
				ON os.order_id = meta.$id_column
				AND meta.meta_key = '_revx_campaign_id'
			WHERE meta.meta_value = %d
			AND os.status NOT IN ('wc-auto-draft', 'wc-trash', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-checkout-draft')
			GROUP BY DATE(os.date_created)
		";

			$query = "
			SELECT
				a.date,
				COALESCE(os.total_sales, 0) AS total_sales,
				COALESCE(os.orders_count, 0) AS orders_count,
				ROUND(
					CASE
						WHEN COALESCE(a.impression_count, 0) > 0 THEN
							(COALESCE(os.orders_count, 0) / a.impression_count) * 100
						ELSE 0
					END,
					2
				) AS conversion_rate,
				COALESCE(a.impression_count, 0) AS impression_count,
				COALESCE(a.add_to_cart_count, 0) AS add_to_cart,
				COALESCE(a.rejection_count, 0) AS rejection_count,
				COALESCE(a.checkout_count, 0) AS checkout_count
			FROM ($analytics_subquery) a
			LEFT JOIN ($orders_subquery) os ON a.date = os.date
			ORDER BY a.date
		";

		$prepared_query = $wpdb->prepare( $query, $campaign_id, $campaign_id ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results        = $wpdb->get_results( $prepared_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$start_date = new DateTime( $campaign_start_date );
		$end_date   = new DateTime( 'now' );

		$campaign_stats_chart_data = revenue()->generate_campaigns_stats_chart_data( $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ), array(), $data_keys );

		foreach ( $results as $campaign ) {
			if ( is_array( $campaign_stats_chart_data[ $campaign->date ] ) ) {
				$campaign_stats_chart_data[ $campaign->date ] = array_merge( $campaign_stats_chart_data[ $campaign->date ], (array) $campaign );
			}
		}

		$today     = gmdate( 'Y-m-d' );
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		if ( ! isset( $campaign_stats_chart_data[ $today ], $campaign_stats_chart_data[ $yesterday ] ) ) {
			return array(
				'data'   => $campaign_stats_chart_data,
				'growth' => array(),
			);
		}

		$growth = revenue()->calculate_growth( $campaign_stats_chart_data[ $today ], $campaign_stats_chart_data[ $yesterday ], $data_keys );

		return array(
			'data'   => $campaign_stats_chart_data,
			'growth' => $growth,
		);
	}



	/**
	 * Builds a MySQL format date/time based on some query parameters.
	 *
	 * You can pass an array of values (year, month, etc.) with missing parameter values being defaulted to
	 * either the maximum or minimum values (controlled by the $default_to parameter). Alternatively you can
	 * pass a string that will be passed to date_create().
	 *
	 * @since 3.7.0
	 *
	 * @param  string|array $datetime       An array of parameters or a strtotime() string.
	 * @param  bool         $default_to_max Whether to round up incomplete dates. Supported by values
	 *                                      of $datetime that are arrays, or string values that are a
	 *                                      subset of MySQL date format ('Y', 'Y-m', 'Y-m-d', 'Y-m-d H:i').
	 *                                      Default: false.
	 * @return string|false A MySQL format date/time or false on failure.
	 */
	public function build_mysql_datetime( $datetime, $default_to_max = false ) {
		if ( ! is_array( $datetime ) ) {
			/*
			* Try to parse some common date formats, so we can detect
			* the level of precision and support the 'inclusive' parameter.
			*/
			if ( preg_match( '/^(\d{4})$/', $datetime, $matches ) ) {
				$datetime = array(
					'year' => (int) $matches[1],
				);
			} elseif ( preg_match( '/^(\d{4})\-(\d{2})$/', $datetime, $matches ) ) {
				$datetime = array(
					'year'  => (int) $matches[1],
					'month' => (int) $matches[2],
				);
			} elseif ( preg_match( '/^(\d{4})\-(\d{2})\-(\d{2})$/', $datetime, $matches ) ) {
				$datetime = array(
					'year'  => (int) $matches[1],
					'month' => (int) $matches[2],
					'day'   => (int) $matches[3],
				);
			} elseif ( preg_match( '/^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2})$/', $datetime, $matches ) ) {
				$datetime = array(
					'year'   => (int) $matches[1],
					'month'  => (int) $matches[2],
					'day'    => (int) $matches[3],
					'hour'   => (int) $matches[4],
					'minute' => (int) $matches[5],
				);
			}

			// If no match is found, we don't support default_to_max.
			if ( ! is_array( $datetime ) ) {
				$wp_timezone = wp_timezone();

				// Assume local timezone if not provided.
				$dt = date_create( $datetime, $wp_timezone );

				if ( false === $dt ) {
					return gmdate( 'Y-m-d H:i:s', false );
				}

				return $dt->setTimezone( $wp_timezone )->format( 'Y-m-d H:i:s' );
			}
		}

		$datetime = array_map( 'absint', $datetime );

		if ( ! isset( $datetime['year'] ) ) {
			$datetime['year'] = current_time( 'Y' );
		}

		if ( ! isset( $datetime['month'] ) ) {
			$datetime['month'] = ( $default_to_max ) ? 12 : 1;
		}

		if ( ! isset( $datetime['day'] ) ) {
			$datetime['day'] = ( $default_to_max ) ? (int) gmdate( 't', mktime( 0, 0, 0, $datetime['month'], 1, $datetime['year'] ) ) : 1;
		}

		if ( ! isset( $datetime['hour'] ) ) {
			$datetime['hour'] = ( $default_to_max ) ? 23 : 0;
		}

		if ( ! isset( $datetime['minute'] ) ) {
			$datetime['minute'] = ( $default_to_max ) ? 59 : 0;
		}

		if ( ! isset( $datetime['second'] ) ) {
			$datetime['second'] = ( $default_to_max ) ? 59 : 0;
		}

		return sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $datetime['year'], $datetime['month'], $datetime['day'], $datetime['hour'], $datetime['minute'], $datetime['second'] );
	}

	/**
	 * Saves a campaign to the database.
	 *
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $is_clone Whether the request is for a clone operation.
	 * @return int
	 */
	public function save_campaign( $request, $is_clone = false ) {
		$campaign_data = $this->prepare_item_for_database( $request );

		$id = $this->insert_update_campaign( $campaign_data, true );

		if ( $id && ! is_wp_error( $id ) ) {

			$updates = $this->update_campaign_meta( $id, $campaign_data );

			$this->update_campaign_triggers( $id, $campaign_data );

			do_action( 'revenue_new_campaign', $id, $campaign_data );
		}

		return $id;
	}
	/**
	 * Updatge a campaign to the database.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return int
	 */
	public function update_campaign( $request ) {
		$id = isset( $request['id'] ) ? $request['id'] : 0;

		if ( ! $id ) {
			return;
		}

		$campaign_data = $this->prepare_item_for_database( $request );

		// Update Campaign Table.
		$update_status = $this->insert_update_campaign( $campaign_data, true );

		// Update Campaign Meta.
		$updates = $this->update_campaign_meta( $id, $campaign_data );

		// Update Campaign Triggers and trigger items.
		if ( ! ( isset( $request['source'] ) && 'campaign_list' == $request['source'] ) ) {
			$this->update_campaign_triggers( $id, $campaign_data );
		}

		do_action( 'revenue_update_campaign', $id, $campaign_data, $updates );

		revenue()->clear_campaign_runtime_cache( $id );

		return $id;
	}


	/**
	 * Update Campaign Triggers.
	 *
	 * @param int   $id   Campaign ID.
	 * @param array $data Campaign Data.
	 */
	protected function update_campaign_meta( $id, $data ) {
		$campaign_data = revenue()->get_campaign_data( $id );
		$updated_props = array();
		foreach ( revenue()->get_campaign_keys( 'meta' ) as $key ) {
			// Perform update meta operation only changes.
			if ( 'placement_settings' == $key ) {
				continue;
			}
			if ( ( isset( $campaign_data[ $key ], $data[ $key ] ) && $data[ $key ] != $campaign_data[ $key ] ) || ( ! isset( $campaign_data[ $key ] ) && isset( $data[ $key ] ) ) ) {
				$updated = $this->update_or_delete_post_meta( $id, $key, $data[ $key ] );

				if ( $updated ) {
					$updated_props[] = $key;
				}
			}
		}

		return $updated_props;
	}


	/**
	 * Get meta keys
	 *
	 * @param array $data Data.
	 */
	private function get_meta_key( $data ) {
		$position         = $data['campaign_inpage_position'];
		$display_type     = $data['campaign_display_style'];
		$placement        = $data['campaign_placement'];
		$include_meta_key = '';
		$exclude_meta_key = '';

		$to_be_delete = array(
			'include_meta_key' => array(),
			'exclude_meta_key' => array(),
		);
		$to_be_add    = array(
			'include_meta_key' => array(),
			'exclude_meta_key' => array(),
		);

		if ( isset( $data['placement_settings'] ) && ! empty( $data['placement_settings'] ) ) {
			foreach ( $data['placement_settings'] as $page => $placement_setting ) {
				$placement    = $page;
				$display_type = isset( $placement_setting['display_style'] ) ? $placement_setting['display_style'] : $placement_setting['display_style'];
				$position     = isset( $placement_setting['inpage_position'] ) ? $placement_setting['inpage_position'] : '';

				if ( 'inpage' == $display_type ) {
					$include_meta_key = "revx_camp_{$placement}_{$display_type}_{$position}_in";
					$exclude_meta_key = "revx_camp_{$placement}_{$display_type}_{$position}_ex";
				} else {
					$include_meta_key = "revx_camp_{$placement}_{$display_type}_in";
					$exclude_meta_key = "revx_camp_{$placement}_{$display_type}_ex";
				}

				if ( 'yes' == $placement_setting['status'] ) {
					$to_be_add['include_meta_key'][] = $include_meta_key;
					$to_be_add['exclude_meta_key'][] = $exclude_meta_key;
				} else {
					$to_be_delete['include_meta_key'][] = $include_meta_key;
					$to_be_delete['exclude_meta_key'][] = $exclude_meta_key;
				}
			}
		}

		return array(
			'to_be_add'    => $to_be_add,
			'to_be_delete' => $to_be_delete,
		);
	}

	/**
	 * Delete and Update Meta
	 *
	 * @param  string $campaign_id Campaign ID.
	 * @param  array  $ids         IDs.
	 * @param  string $type        Type.
	 * @param  string $meta_key    Meta key.
	 * @param  string $for         For which(delete or update).
	 * @return void
	 */
	private function delete_update_meta( $campaign_id, $ids, $type, $meta_key, $for = 'delete' ) {
		switch ( $type ) {
			case 'category':
				foreach ( $ids as $id ) {
					if ( 'delete' == $for ) {
						delete_term_meta( $id, $meta_key, $campaign_id );
					} else {
						add_term_meta( $id, $meta_key, $campaign_id );
					}
				}
				break;
			case 'product':
				foreach ( $ids as $id ) {
					if ( 'delete' == $for ) {
						delete_post_meta( $id, $meta_key, $campaign_id );
					} else {
						add_post_meta( $id, $meta_key, $campaign_id );
					}
				}
				break;

			default:
				break;
		}
	}

	/**
	 * Process Triggers
	 *
	 * @param  array  $data Data.
	 * @param  string $type Type.
	 * @return array
	 */
	private function processTriggers( $data, $type = 'include' ) {
		$res = array(
			'is_all_products'    => isset( $args['campaign_trigger_type'] ) && 'all_products' == $args['campaign_trigger_type'],
			'include_products'   => array(),
			'exclude_products'   => array(),
			'include_categories' => array(),
			'exclude_categories' => array(),
		);

		if ( ! is_array( $data ) ) {
			return $res;
		}

		foreach ( $data as $trigger ) {

			if ( 'include' == $trigger['trigger_action'] ) {

				switch ( $trigger['trigger_type'] ) {

					case 'products':
						$res['include_products'][] = intval( $trigger['item_id'] );
						break;

					// case 'eventin_events':
					// $res['include_products'][] = intval( $trigger['item_id'] );
					// break;

					case 'category':
						$res['include_categories'][] = intval( $trigger['item_id'] );

						break;

					default:
						$res = apply_filters( 'revenue_process_campaign_trigger', $res, $trigger['trigger_type'], $trigger['trigger_action'], $trigger['item_id'] );
						// code...
						break;
				}
			} elseif ( 'exclude' == $trigger['trigger_action'] ) {

				switch ( $trigger['trigger_type'] ) {

					case 'products':
						$res['exclude_products'][] = intval( $trigger['item_id'] );
						break;
					// case 'eventin_events':
					// $res['exclude_products'][] = intval( $trigger['item_id'] );
					// break;
					case 'category':
						$res['exclude_categories'][] = intval( $trigger['item_id'] );
						break;

					default:
						$res = apply_filters( 'revenue_process_campaign_trigger', $res, $trigger['trigger_type'], $trigger['trigger_action'], $trigger['item_id'] );

						break;
				}
			}
		}

		// Ensure unique IDs and add to result if necessary.
		$res['exclude_products']   = array_unique( $res['exclude_products'] );
		$res['include_products']   = array_unique( $res['include_products'] );
		$res['exclude_categories'] = array_unique( $res['exclude_categories'] );
		$res['include_categories'] = array_unique( $res['include_categories'] );

		return $res;
	}

	/**
	 * Campaign Campaign Triggers
	 *
	 * @param  string|int $campaign_id Campaign ID.
	 * @param  array      $args        Args.
	 * @param  boolean    $is_delete   Should delete.
	 * @return void
	 */
	protected function update_campaign_triggers( $campaign_id, $args, $is_delete = false ) {
		global $wpdb;

		if ( isset( $args['campaign_trigger_type'] ) && 'all_products' == $args['campaign_trigger_type'] ) {
			$args['campaign_trigger_items'] = array();
		}

		if ( $is_delete ) {
			$args['campaign_trigger_items']         = array();
			$args['campaign_trigger_exclude_items'] = array();
			$args['campaign_trigger_type']          = '';
		}

		$deleted_trigger_id = array();

		$previous_data = revenue()->get_campaign_data( $campaign_id );

		$before_update_campaign = $previous_data;
		$is_update              = false;
		if ( $previous_data ) {
			$is_update     = true;
			$previous_data = $previous_data['campaign_trigger_items'];
		}

		if ( $is_update ) {

			$meta_keys = $this->get_meta_key( $before_update_campaign ); // Get Include and Exclude Meta keys form old campaign(Before Updated), so that we delete this.

			$exclude_meta_keys = $meta_keys['to_be_add']['exclude_meta_key'];
			$include_meta_keys = $meta_keys['to_be_add']['include_meta_key'];

			$tbd_exclude_meta_keys = $meta_keys['to_be_delete']['exclude_meta_key'];
			$tbd_include_meta_keys = $meta_keys['to_be_delete']['include_meta_key'];

			// $to_be_add
			$pre_trigger_item = $this->processTriggers( $previous_data );

			$prev_exclude_item = $this->processTriggers( $before_update_campaign['campaign_trigger_exclude_items'] );

			foreach ( $exclude_meta_keys as $exclude_meta_key ) {
				$this->delete_update_meta( $campaign_id, $prev_exclude_item['exclude_products'], 'product', $exclude_meta_key, 'delete' );
				$this->delete_update_meta( $campaign_id, $prev_exclude_item['exclude_categories'], 'category', $exclude_meta_key, 'delete' );
			}

			foreach ( $tbd_exclude_meta_keys as $exclude_meta_key ) {
				$this->delete_update_meta( $campaign_id, $prev_exclude_item['exclude_products'], 'product', $exclude_meta_key, 'delete' );
				$this->delete_update_meta( $campaign_id, $prev_exclude_item['exclude_categories'], 'category', $exclude_meta_key, 'delete' );
			}

			$include_cats     = $pre_trigger_item['include_categories'];
			$include_products = $pre_trigger_item['include_products'];

			foreach ( $include_meta_keys as $include_meta_key ) {
				$this->delete_update_meta( $campaign_id, $include_cats, 'category', $include_meta_key, 'delete' );
				$this->delete_update_meta( $campaign_id, $include_products, 'product', $include_meta_key, 'delete' );
			}

			foreach ( $tbd_include_meta_keys as $include_meta_key ) {
				$this->delete_update_meta( $campaign_id, $include_cats, 'category', $include_meta_key, 'delete' );
				$this->delete_update_meta( $campaign_id, $include_products, 'product', $include_meta_key, 'delete' );
			}
		}

		// update placement_settings.

		if ( ! empty( $args['placement_settings'] ) ) {
			revenue()->update_campaign_meta( $campaign_id, 'placement_settings', $args['placement_settings'] );

			if ( isset( $args['campaign_trigger_items'] ) && is_array( $args['campaign_trigger_items'] ) ) {

				$meta_keys = $this->get_meta_key( $args );

				$exclude_meta_keys = $meta_keys['to_be_add']['exclude_meta_key'];
				$include_meta_keys = $meta_keys['to_be_add']['include_meta_key'];

				$tbd_exclude_meta_keys = $meta_keys['to_be_delete']['exclude_meta_key'];
				$tbd_include_meta_keys = $meta_keys['to_be_delete']['include_meta_key'];

				$updated_trigger = $this->processTriggers( $args['campaign_trigger_items'] );

				$excluded_trigger = false;
				if ( isset( $args['campaign_trigger_exclude_items'] ) && is_array( $args['campaign_trigger_exclude_items'] ) ) {
					$excluded_trigger = $this->processTriggers( $args['campaign_trigger_exclude_items'] );
				}

				if ( isset( $args['campaign_trigger_type'] ) && 'all_products' == $args['campaign_trigger_type'] && $excluded_trigger && is_array( $excluded_trigger ) ) {
					// Excluded Product and Categories.
					$excluded_products  = isset( $excluded_trigger['exclude_products'] ) ? $excluded_trigger['exclude_products'] : array();
					$exclude_categories = isset( $excluded_trigger['exclude_categories'] ) ? $excluded_trigger['exclude_categories'] : array();

					foreach ( $exclude_meta_keys as $exclude_meta_key ) {
						$this->delete_update_meta( $campaign_id, $excluded_products, 'product', $exclude_meta_key, 'update' );
						$this->delete_update_meta( $campaign_id, $exclude_categories, 'category', $exclude_meta_key, 'update' );
					}
				} else {

					$include_cats     = $updated_trigger['include_categories'];
					$include_products = $updated_trigger['include_products'];

					foreach ( $include_meta_keys as $include_meta_key ) {
						$this->delete_update_meta( $campaign_id, $include_products, 'product', $include_meta_key, 'update' );
						$this->delete_update_meta( $campaign_id, $include_cats, 'category', $include_meta_key, 'update' );
					}

					$exclude_cat      = isset( $excluded_trigger['exclude_categories'] ) ? $excluded_trigger['exclude_categories'] : array();
					$exclude_products = isset( $excluded_trigger['exclude_products'] ) ? $excluded_trigger['exclude_products'] : array();

					foreach ( $exclude_meta_keys as $exclude_meta_key ) {
						if ( ! empty( $exclude_cat ) ) {
							$this->delete_update_meta( $campaign_id, $exclude_cat, 'category', $exclude_meta_key, 'update' );
						}
						if ( ! empty( $exclude_products ) ) {
							$this->delete_update_meta( $campaign_id, $exclude_products, 'product', $exclude_meta_key, 'update' );
						}
					}
				}
			}

			if ( isset( $args['campaign_trigger_items'] ) && is_array( $args['campaign_trigger_items'] ) ) {

				$updated_trigger_data = array();

				foreach ( $args['campaign_trigger_items'] as $trigger ) {

					$trigger_id = $trigger['trigger_id'] ?? 0;

					if ( $trigger_id ) {
						$updated_trigger_data[ $trigger_id ] = $trigger;
					}

					if ( ! ( isset( $args['campaign_trigger_type'] ) && $campaign_id ) ) {
						continue;
					}

					if ( ! ( ( 'all_products' == $args['campaign_trigger_type'] ) || ( ! empty( $trigger ) ) ) ) {
						continue;
					}

					$trigger_data = array(
						'campaign_id'    => $campaign_id,
						'trigger_action' => 'include',
						'trigger_type'   => $args['campaign_trigger_type'],
						'item_quantity'  => isset( $trigger['item_quantity'] ) ? $trigger['item_quantity'] : 1,
						'item_id'        => isset( $trigger['item_id'] ) ? $trigger['item_id'] : '',
					);

					if ( $trigger_id && $is_update ) {
						if ( ! isset( $previous_data[ $trigger_id ] ) || empty( $previous_data[ $trigger_id ] ) ) {
							continue;
						}
						$trigger_data = array();

						$trigger_previous_data = $previous_data[ $trigger_id ];

						if ( $trigger_previous_data['trigger_action'] != $trigger['trigger_action'] ) {
							$trigger_data['trigger_action'] = $trigger['trigger_action'];
						}
						if ( $trigger_previous_data['trigger_type'] != $args['campaign_trigger_type'] ) {
							$trigger_data['trigger_type'] = $args['campaign_trigger_type'];
						}
						if ( $trigger_previous_data['item_quantity'] != $trigger['item_quantity'] ) {
							$trigger_data['item_quantity'] = $trigger['item_quantity'];
						}
						if ( $trigger_previous_data['item_id'] != $trigger['item_id'] ) {
							$trigger_data['item_id'] = $trigger['item_id'];
						}

						if ( ! empty( $trigger_data ) ) {
							$wpdb->update( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
								$wpdb->prefix . 'revenue_campaign_triggers',
								$trigger_data,
								array( 'trigger_id' => $trigger_id )
							);
						}
					} else {

						$trigger_insert = $wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prefix . 'revenue_campaign_triggers',
							$trigger_data
						);

						if ( $trigger_insert ) {
							$trigger_id = $wpdb->insert_id;
						}
					}
				}
				if ( $is_update && is_array( $updated_trigger_data ) && is_array( $previous_data ) ) {
					$deleted_trigger_id = array_merge( $deleted_trigger_id, array_diff( array_keys( $previous_data ), array_keys( $updated_trigger_data ) ) );
				}
			}
		}

		$is_update     = false;
		$previous_data = revenue()->get_campaign_data( $campaign_id );

		if ( $previous_data ) {
			$is_update     = true;
			$previous_data = $previous_data['campaign_trigger_exclude_items'];
		}

		if ( ( isset( $args['campaign_trigger_type'] ) && ( 'all_products' == $args['campaign_trigger_type'] || 'category' == $args['campaign_trigger_type'] ) ) && isset( $args['campaign_trigger_exclude_items'] ) && is_array( $args['campaign_trigger_exclude_items'] ) ) {

			$updated_trigger_data = array();

			foreach ( $args['campaign_trigger_exclude_items'] as $trigger ) {

				$trigger_id = $trigger['trigger_id'] ?? 0;

				if ( $trigger_id ) {
					$updated_trigger_data[ $trigger_id ] = $trigger;
				}

				if ( empty( $trigger ) ) {
					continue;
				}

				$trigger_data = array(
					'campaign_id'    => $campaign_id,
					'trigger_action' => 'exclude',
					'trigger_type'   => 'products',
					'item_quantity'  => 1,
					'item_id'        => isset( $trigger['item_id'] ) ? $trigger['item_id'] : '',
				);

				if ( $trigger_id && $is_update ) {
					if ( ! isset( $previous_data[ $trigger_id ] ) || empty( $previous_data[ $trigger_id ] ) ) {
						continue;
					}
					$trigger_data = array();

					$trigger_previous_data = $previous_data[ $trigger_id ];

					$trigger_data['trigger_action'] = 'exclude';

					$trigger_data['trigger_type'] = 'products';

					if ( $trigger_previous_data['item_quantity'] != $trigger['item_quantity'] ) {
						$trigger_data['item_quantity'] = $trigger['item_quantity'];
					}
					if ( $trigger_previous_data['item_id'] != $trigger['item_id'] ) {
						$trigger_data['item_id'] = $trigger['item_id'];
					}

					if ( ! empty( $trigger_data ) ) {
						$wpdb->update( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prefix . 'revenue_campaign_triggers',
							$trigger_data,
							array( 'trigger_id' => $trigger_id )
						);
					}
				} else {

					$trigger_insert = $wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prefix . 'revenue_campaign_triggers',
						$trigger_data
					);

					if ( $trigger_insert ) {
						$trigger_id = $wpdb->insert_id;
					}
				}
			}
			if ( $is_update && is_array( $updated_trigger_data ) && is_array( $previous_data ) ) {
				$deleted_trigger_id = array_merge( $deleted_trigger_id, array_diff( array_keys( $previous_data ), array_keys( $updated_trigger_data ) ) );
			}
		}

		if ( ! empty( $deleted_trigger_id ) ) {
			foreach ( $deleted_trigger_id as $tid ) {
				revenue()->delete_campaign_trigger( $campaign_id, $tid );
			}
		}
	}

	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array.
	 * Other empty values such as numeric 0 and null should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param int    $id         The campaign id.
	 * @param string $meta_key   Meta key to update.
	 * @param mixed  $meta_value Value to save.
	 *
	 * @since 1.0.0 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_post_meta( $id, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = revenue()->delete_campaign_meta( $id, $meta_key );
		} else {
			$updated = revenue()->update_campaign_meta( $id, $meta_key, $meta_value );
		}

		return (bool) $updated;
	}
	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @since 1.0.0
	 * @param string|integer $value Value of the prop.
	 */
	protected function get_wc_date( $value ) {
		try {
			if ( empty( $value ) || '0000-00-00 00:00:00' === $value ) {

				return null;
			}

			if ( is_a( $value, 'WC_DateTime' ) ) {
				$datetime = $value;
			} elseif ( is_numeric( $value ) ) {
				// Timestamps are handled as UTC timestamps in all cases.
				$datetime = new WC_DateTime( "@{$value}", new DateTimeZone( 'UTC' ) );
			} else {
				// Strings are defined in local WP timezone. Convert to UTC.
				if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
				} else {
					$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $value ) ) ) );
				}
				$datetime = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );
			}

			// Set local timezone or offset.
			if ( get_option( 'timezone_string' ) ) {
				$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
			} else {
				$datetime->set_utc_offset( wc_timezone_offset() );
			}

			return $datetime;
		} catch ( Exception $e ) {
			return null;
		} // @codingStandardsIgnoreLine.
	}

	/**
	 * Only return writable props from schema.
	 *
	 * @param  array $schema Schema.
	 * @return bool
	 */
	protected function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
	}


	/**
	 * Insert and Update Campaign.
	 *
	 * @param  array   $args     Args.
	 * @param  boolean $wp_error wp error.
	 * @return mixed
	 */
	public function insert_update_campaign( $args, $wp_error = false ) {
		global $wpdb;
		$campaign_id = 0;
		$user_id     = get_current_user_id();
		$update      = false;
		if ( isset( $args['id'] ) && ! empty( $args['id'] ) ) {
			$update      = true;
			$campaign_id = $args['id'];

			$campaign_before = revenue()->get_campaign_data( $campaign_id );
			if ( is_null( $campaign_before ) ) {
				if ( $wp_error ) {
					return new WP_Error( 'invalid_campaign', __( 'Invalid campaign ID.', 'revenue' ) );
				} else {
					return 0;
				}
			}
		}

		if ( $update ) {
			$campaign_table_data = array();
			if ( isset( $args['campaign_name'] ) && $campaign_before['campaign_name'] != $args['campaign_name'] ) {
				$campaign_table_data['campaign_name'] = wp_kses_post( $args['campaign_name'] );
			}
			if ( isset( $args['campaign_status'] ) && $campaign_before['campaign_status'] != $args['campaign_status'] ) {
				$campaign_table_data['campaign_status'] = sanitize_text_field( $args['campaign_status'] );
			}
			if ( isset( $args['campaign_type'] ) && $campaign_before['campaign_type'] != $args['campaign_type'] ) {
				$campaign_table_data['campaign_type'] = sanitize_text_field( $args['campaign_type'] );
			}
			if ( isset( $args['campaign_placement'] ) && $campaign_before['campaign_placement'] != $args['campaign_placement'] ) {
				$campaign_table_data['campaign_placement'] = sanitize_text_field( $args['campaign_placement'] );
			}
			if ( isset( $args['campaign_behavior'] ) && $campaign_before['campaign_behavior'] != $args['campaign_behavior'] ) {
				$campaign_table_data['campaign_behavior'] = sanitize_text_field( $args['campaign_behavior'] );
			}
			if ( isset( $args['campaign_recommendation'] ) && $campaign_before['campaign_recommendation'] != $args['campaign_recommendation'] ) {
				$campaign_table_data['campaign_recommendation'] = sanitize_text_field( $args['campaign_recommendation'] );
			}
			if ( isset( $args['campaign_inpage_position'] ) && $campaign_before['campaign_inpage_position'] != $args['campaign_inpage_position'] ) {
				$campaign_table_data['campaign_inpage_position'] = sanitize_text_field( $args['campaign_inpage_position'] );
			}
			if ( isset( $args['campaign_display_style'] ) && $campaign_before['campaign_display_style'] != $args['campaign_display_style'] ) {
				$campaign_table_data['campaign_display_style'] = sanitize_text_field( $args['campaign_display_style'] );
			}
			if ( isset( $args['campaign_trigger_type'] ) && $campaign_before['campaign_trigger_type'] != $args['campaign_trigger_type'] ) {
				$campaign_table_data['campaign_trigger_type'] = sanitize_text_field( $args['campaign_trigger_type'] );
			}
			if ( isset( $args['campaign_trigger_relation'] ) && $campaign_before['campaign_trigger_relation'] != $args['campaign_trigger_relation'] ) {
				$campaign_table_data['campaign_trigger_relation'] = sanitize_text_field( $args['campaign_trigger_relation'] );
			}
			if ( isset( $args['campaign_start_date_time'] ) && $campaign_before['campaign_start_date_time'] != $args['campaign_start_date_time'] ) {
				$timestamp = strtotime( sanitize_text_field( $args['campaign_start_date_time'] ) );
				if ( $timestamp ) {
					$campaign_table_data['campaign_start_date_time'] = gmdate( 'Y-m-d H:i:s', $timestamp );
				}
			}
			if ( isset( $args['campaign_end_date_time'] ) && $campaign_before['campaign_end_date_time'] != $args['campaign_end_date_time'] ) {
				$timestamp = strtotime( sanitize_text_field( $args['campaign_end_date_time'] ) );
				if ( $timestamp ) {
					$campaign_table_data['campaign_end_date_time'] = gmdate( 'Y-m-d H:i:s', $timestamp );
				}
			}
			$campaign_table_data['date_modified']     = current_time( 'mysql' );
			$campaign_table_data['date_modified_gmt'] = current_time( 'mysql', 1 );
		} else {
			$campaign_table_data = array(
				'campaign_name'             => isset( $args['campaign_name'] ) ? wp_kses_post( $args['campaign_name'] ) : __( 'Untitled Campaign', 'revenue' ),
				'campaign_author'           => $user_id,
				'campaign_status'           => isset( $args['campaign_status'] ) ? sanitize_text_field( $args['campaign_status'] ) : 'draft',
				'campaign_type'             => isset( $args['campaign_type'] ) ? sanitize_text_field( $args['campaign_type'] ) : '',
				'campaign_placement'        => isset( $args['campaign_placement'] ) ? sanitize_text_field( $args['campaign_placement'] ) : revenue()->get_campaign_default_placement( $args['campaign_type'] ),
				'campaign_behavior'         => isset( $args['campaign_behavior'] ) ? sanitize_text_field( $args['campaign_behavior'] ) : 'cross_sell',
				'campaign_recommendation'   => isset( $args['campaign_recommendation'] ) ? sanitize_text_field( $args['campaign_recommendation'] ) : 'manual',
				'campaign_inpage_position'  => isset( $args['campaign_inpage_position'] ) ? sanitize_text_field( $args['campaign_inpage_position'] ) : '',
				'campaign_display_style'    => isset( $args['campaign_display_style'] ) ? sanitize_text_field( $args['campaign_display_style'] ) : '',
				'campaign_trigger_type'     => isset( $args['campaign_trigger_type'] ) ? sanitize_text_field( $args['campaign_trigger_type'] ) : '',
				'campaign_trigger_relation' => isset( $args['campaign_trigger_relation'] ) ? sanitize_text_field( $args['campaign_trigger_relation'] ) : '',
			);
			if ( isset( $args['schedule_start_date'], $args['schedule_start_time'] ) && ! empty( $args['schedule_start_date'] ) && ! empty( $args['schedule_start_time'] ) ) {
				$start_date_time = sanitize_text_field( $args['schedule_start_date'] ) . ' ' . sanitize_text_field( $args['schedule_start_time'] );
				$date            = new DateTime( $start_date_time );
				$timestamp       = $date->getTimestamp();
				$campaign_table_data['campaign_start_date_time'] = gmdate( 'Y-m-d H:i:s', $timestamp );
			} elseif ( isset( $args['campaign_start_date_time'] ) ) {
				$campaign_table_data['campaign_start_date_time'] = sanitize_text_field( $args['campaign_start_date_time'] );
			}

			if ( isset( $args['schedule_end_time_enabled'], $args['schedule_end_date'], $args['schedule_end_time'] ) && 'yes' === sanitize_key( $args['schedule_end_time_enabled'] ) && ! empty( $args['schedule_end_date'] ) && ! empty( $args['schedule_end_time'] ) ) {
				$end_date_time = sanitize_text_field( $args['schedule_end_date'] ) . ' ' . sanitize_text_field( $args['schedule_end_time'] );
				$date          = new DateTime( $end_date_time );
				$timestamp     = $date->getTimestamp() * 1000; // Convert to milliseconds.
				$campaign_table_data['campaign_end_date_time'] = gmdate( 'Y-m-d H:i:s', $timestamp );
			} elseif ( isset( $args['campaign_end_date_time'] ) ) {
				$campaign_table_data['campaign_end_date_time'] = sanitize_text_field( $args['campaign_end_date_time'] );
			}
			// Insert New Campaign.
			$campaign_table_data['date_created']      = current_time( 'mysql' );
			$campaign_table_data['date_modified']     = current_time( 'mysql' );
			$campaign_table_data['date_created_gmt']  = current_time( 'mysql', 1 );
			$campaign_table_data['date_modified_gmt'] = current_time( 'mysql', 1 );
		}

		$campaign_table_data = wp_unslash( $campaign_table_data );

		$where = array( 'id' => $campaign_id );

		if ( $update ) {
			if ( false === $wpdb->update( $wpdb->prefix . 'revenue_campaigns', $campaign_table_data, $where ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $wp_error ) {
					return new WP_Error( 'db_update_error', __( 'Could not update campaign into the database.', 'revenue' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}
		} else {
			if ( false === $wpdb->insert( $wpdb->prefix . 'revenue_campaigns', $campaign_table_data ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $wp_error ) {
					return new WP_Error( 'db_insert_error', __( 'Could not insert campaign into the database.', 'revenue' ), $wpdb->last_error );
				} else {
					return 0;
				}
			}

			$campaign_id = (int) $wpdb->insert_id;
		}

		do_action( 'revenue_campaign_inserted' );

		return $campaign_id;
	}

	/**
	 * Bulk Update Campaign Status
	 *
	 * @param  object $request Request.
	 * @return mixed
	 */
	public function bulk_update_campaign_status( $request ) {
		global $wpdb;

		$campaign_ids = $request->get_param( 'ids' );
		$new_status   = sanitize_text_field( $request->get_param( 'status' ) );
		$updated      = 0;

		$data = array();
		foreach ( $campaign_ids as $campaign_id ) {
			$campaign_id = intval( $campaign_id );

			// Get previous data.
			$campaign_before = revenue()->get_campaign_data( $campaign_id );

			if ( is_null( $campaign_before ) ) {
				continue; // Skip if campaign doesn't exist.
			}

			// Check if status is different, if yes, update it.
			if ( $campaign_before['campaign_status'] !== $new_status ) {
				$updated_data = array(
					'campaign_status'   => $new_status,
					'date_modified'     => current_time( 'mysql' ),
					'date_modified_gmt' => current_time( 'mysql', 1 ),
				);

				$where = array( 'id' => $campaign_id );

				//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->update( $wpdb->prefix . 'revenue_campaigns', $updated_data, $where );

				if ( false !== $result ) {
					++$updated;
					$data[] = array(
						'id'              => $campaign_id,
						'campaign_status' => $new_status,
					);
				}
			}
		}

		return rest_ensure_response( $data );
	}


	/**
	 * Sanitize Campaign.
	 *
	 * @param  array $campaign Campaign.
	 * @return array
	 */
	public function sanitize_campaign( $campaign ) {
		if ( ! is_array( $campaign ) ) {
			return false;
		}
		foreach ( $campaign as $field => $value ) {
			$campaign[ $field ] = $this->sanitize_campaign_field( $field, $value );
		}

		return $campaign;
	}


	/**
	 * Sanitize Campaign Field.
	 *
	 * @param  string $field   Field.
	 * @param  mixed  $value   Value.
	 * @param  string $context Context.
	 * @return mixed
	 */
	public function sanitize_campaign_field( $field, $value, $context = 'display' ) {
		switch ( $field ) {
			case 'campaign_name':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_author':
			case 'id':
				$value = (int) sanitize_text_field( $value );
				// code...
				break;
			case 'campaign_status':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_type':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_placement':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_behavior':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_recommendation':
			case 'campaign_inpage_position':
			case 'campaign_display_style':
			case 'campaign_trigger_type':
			case 'campaign_trigger_relation':
				$value = sanitize_text_field( $value );
				break;

			default:
				// code...
				break;
		}

		return $value;
	}

	/**
	 * Get the campaign schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'                                       => array(
					'description' => __( 'Unique identifier for campaign.', 'revenue' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'campaign_name'                            => array(
					'description' => __( 'Campaign name.', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'                             => array(
					'description' => __( "The date the campaign was created, in the site's timezone.", 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'                         => array(
					'description' => __( 'The date the campaign was created, as GMT.', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'                            => array(
					'description' => __( "The date the campaign was last modified, in the site's timezone.", 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'                        => array(
					'description' => __( 'The date the campaign was last modified, as GMT.', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'campaign_type'                            => array(
					'description' => __( 'campaign type.', 'revenue' ),
					'type'        => 'string',
					'default'     => 'simple',
					'enum'        => array_keys( revenue()->get_campaign_types() ),
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_status'                          => array(
					'description' => __( 'campaign status', 'revenue' ),
					'type'        => 'string',
					'default'     => 'draft',
					'enum'        => array_keys( revenue()->get_campaign_statuses() ),
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_trigger_type'                    => array(
					'description' => __( 'campaign trigger type', 'revenue' ),
					'type'        => 'string',
					// 'enum'        => array( 'all_products', 'products', 'category' ),
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_trigger_relation'                => array(
					'description' => __( 'campaign trigger relation', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_trigger_items'                   => array(
					'description' => __( 'Trigger items', 'revenue' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'item_id'       => array(
								'description' => __( 'Item Description', 'revenue' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'item_name'     => array(
								'description' => __( 'Item Name', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'item_quantity' => array(
								'description' => __( 'Item quantity', 'revenue' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),

				),
				'campaign_trigger_exclude_items'           => array(
					'description' => __( 'Trigger exclude items', 'revenue' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'item_id'   => array(
								'description' => __( 'Item Description', 'revenue' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'item_name' => array(
								'description' => __( 'Item Name', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),

				),
				'placement_settings'                       => array(
					'description' => __( 'Placement Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'page'                     => array(
								'description' => __( 'Page Name', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'status'                   => array(
								'description' => __( 'Page Status', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'display_style'            => array(
								'description' => __( 'Campaign display types', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'enum'        => array_keys( revenue()->get_campaign_display_types() ),
							),
							'inpage_position'          => array(
								'description' => __( 'In page display position', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'builder_view'             => array(
								'description' => __( 'In page display position', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'popup_animation'          => array(
								'description' => __( 'In page campaign popup animation', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'enum'        => array_keys( revenue()->get_campaign_popup_animation_types() ),
							),
							'popup_animation_delay'    => array(
								'description' => __( 'campaign popup animation trigger delay in second', 'revenue' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'floating_position'        => array(
								'description' => __( 'Floating positon', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'enum'        => array_keys( revenue()->get_campaign_floating_positions() ),
							),
							'floating_animation_delay' => array(
								'description' => __( 'campaign floating animation trigger delay in second', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),

				),
				'campaign_placement'                       => array(
					'description' => __( 'campaign placement', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_display_style'                   => array(
					'description' => __( 'Campaign display types', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_inpage_position'                 => array(
					'description' => __( 'In page display position', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_popup_animation'                 => array(
					'description' => __( 'In page campaign popup animation', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( revenue()->get_campaign_popup_animation_types() ),
				),
				'campaign_popup_animation_delay'           => array(
					'description' => __( 'campaign popup animation trigger delay in second', 'revenue' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_floating_position'               => array(
					'description' => __( 'Floating positon', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( revenue()->get_campaign_floating_positions() ),
				),
				'campaign_floating_animation_delay'        => array(
					'description' => __( 'campaign popup animation trigger delay in second', 'revenue' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_behavior'                        => array(
					'description' => __( 'Campaign behavior', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'cross_sell', 'upsell', 'downsell' ),
				),
				'campaign_recommendation'                  => array(
					'description' => __( 'Campaign recommendation', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'manual', 'automatic' ),
				),
				'offers'                                   => array(
					'description' => __( 'List of Offered items', 'revenue' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'       => array(
								'description' => __( 'Offers Row ID.', 'revenue' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
							'products' => array(
								'description' => __( 'Offered Products.', 'revenue' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
							),
							'quantity' => array(
								'description' => __( 'Offer quantity', 'revenue' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'value'    => array(
								'description' => __( 'Offer value', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'type'     => array(
								'description' => __( 'Offer type.', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'tags'     => array(
								'description' => __( 'Offer tags', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'desc'     => array(
								'description' => __( 'Offer description', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),

						),
					),
				),
				'spending_goal_upsell_discount_configuration' => array(
					'description' => __( 'List of Offered items', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'       => array(
								'description' => __( 'Offers Row ID.', 'revenue' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'products' => array(
								'description' => __( 'Offered Products.', 'revenue' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
							),
							'quantity' => array(
								'description' => __( 'Offer quantity', 'revenue' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'value'    => array(
								'description' => __( 'Offer value', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'type'     => array(
								'description' => __( 'Offer type.', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'tags'     => array(
								'description' => __( 'Offer tags', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'desc'     => array(
								'description' => __( 'Offer description', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),

						),
					),
				),

				// Normal Discount - No Settings.

				// Buy X Get Y - No Settings.

				// Frequenty Bought Together - No Settings.

				// Bundle Discount Settings.
				'bundle_with_trigger_products_enabled'     => array(
					'description' => __( 'Bundle discount campaign allow bundle with trigger product', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				// Volume Discount Settings.
				'allow_more_than_required_quantity'        => array(
					'description' => __( 'Volumne Discount Campaign Allow more than required quantity', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				// Mix & Match Settings.
				'is_required_products'                     => array(
					'description' => __( 'Mix Match Campaign Is Required Products', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'initial_product_selection'                => array(
					'description' => __( 'Mix Match Campaign Initial Products Selection', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'all_product', 'no_product' ),
				),

				// Spending Goal Settings.
				'reward_type'                              => array(
					'description' => __( 'Spending Goal Campaign Reward Type', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'free_shipping', 'discount' ),
				),
				'spending_goal'                            => array(
					'description' => __( 'Spending Goal', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_upsell_product_selection_strategy' => array(
					'description' => __( 'Spending Goal', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_on_cta_click'               => array(
					'description' => __( 'Spending Goal', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_calculate_based_on'         => array(
					'description' => __( 'Spending Goal calculate based on', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_discount_type'              => array(
					'description' => __( 'Spending Goal Discount Type', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_discount_value'             => array(
					'description' => __( 'Spending Goal Discount Value', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				// Banner Heading and Subheading.
				'banner_heading'                           => array(
					'description' => __( 'Campaign Banner heading', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'banner_subheading'                        => array(
					'description' => __( 'Campaign Banner sub heading', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				// Stock Scarcity.
				'stock_scarcity_enabled'                   => array(
					'description' => __( 'Campaign stock scarcity enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				'stock_scarcity_actions'                   => array(
					'description' => __( 'Campaign stock scarcity actions', 'revenue' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'            => array(
								'description' => __( 'Stock scarcity actions Row ID.', 'revenue' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'action'        => array(
								'description' => __( 'Stock scarcity row action.', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'stock_status'  => array(
								'description' => __( 'Stock scarcity action stock status', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'stock_message' => array(
								'description' => __( 'Stock scarcity action message.', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'stock_value'   => array(
								'description' => __( 'Stock scarcity action stock value.', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'maxItems'    => 3,
				),
				'spending_goal_free_shipping_progress_messages' => array(
					'description' => __( 'Campaign spending progress messages', 'revenue' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'status'  => array(
								'description' => __( 'Spending Progress Status', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'message' => array(
								'description' => __( 'Spending Progress message', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'maxItems'    => 3,
				),
				'spending_goal_discount_progress_messages' => array(
					'description' => __( 'Campaign spending progress messages', 'revenue' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'status'  => array(
								'description' => __( 'Spending Progress Status', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'message' => array(
								'description' => __( 'Spending Progress message', 'revenue' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'maxItems'    => 3,
				),

				// Countdown Timer.
				'countdown_timer_enabled'                  => array(
					'description' => __( 'Campaign countdown timer enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_is_upsell_enable'           => array(
					'description' => __( 'Is spending goal upsell enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_is_upsell_enable'           => array(
					'description' => __( 'Is spending goal upsell enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_start_time_status'              => array(
					'description' => __( 'Does campaign coundown start right now ot schedule to later', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'right_now', 'schedule_to_later' ),
				),
				'countdown_start_date'                     => array(
					'description' => __( 'Campaign countdown start date', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_start_time'                     => array(
					'description' => __( 'Campaign countdown start time', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_end_date'                       => array(
					'description' => __( 'Campaign countdown end date', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_end_time'                       => array(
					'description' => __( 'Campaign countdown end time', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),

				// Animated Add to cart.
				'animated_add_to_cart_enabled'             => array(
					'description' => __( 'Campaign animated add to cart enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'add_to_cart_animation_trigger_type'       => array(
					'description' => __( 'Campaign animated add to cart animation trigger type', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'loop', 'on_hover' ),
				),
				'add_to_cart_animation_type'               => array(
					'description' => __( 'Campaign animated add to cart animation type', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array_keys( revenue()->get_campaign_animated_add_to_cart_animation_types() ),
				),
				'add_to_cart_animation_start_delay'        => array(
					'description' => __( 'Campaign animated add to cart animation start delay', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				// Free shipping.
				'free_shipping_enabled'                    => array(
					'description' => __( 'Campaign free shipping enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				// Time Schedule Settings.
				'schedule_end_time_enabled'                => array(
					'description' => __( 'Campaign Time Schedule End Time status', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'schedule_start_date'                      => array(
					'description' => __( 'Campaign Schedule start date', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'schedule_start_time'                      => array(
					'description' => __( 'Campaign Schedule start time', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'schedule_end_date'                        => array(
					'description' => __( 'Campaign Schedule end date', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'schedule_end_time'                        => array(
					'description' => __( 'Campaign Schedule end time', 'revenue' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),

				// Additional Settings.
				'skip_add_to_cart'                         => array(
					'description' => __( 'Skip Add to cart button for offered products', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'quantity_selector_enabled'                => array(
					'description' => __( 'Enabled Quantity selector for offered products', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'offered_product_on_cart_action'           => array(
					'description' => __( 'If the offered products are already in cart action', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'offered_product_click_action'             => array(
					'description' => __( 'action if click on product title or image', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				'builder'                                  => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'builderdata'                              => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'buildeMobileData'                         => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_builder_view'                    => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'product_tag_text'                         => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'save_discount_ext'                        => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'bundle_label_badge'                       => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'no_thanks_button_text'                    => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'add_to_cart_btn_text'                     => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'checkout_btn_text'                        => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'total_price_text'                         => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'mix_match_is_required_products'           => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'mix_match_initial_product_selection'      => array(
					'description' => __( 'Builder Data', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'mix_match_required_products'              => array(
					'description' => __( 'Trigger items', 'revenue' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_view_id'                         => array(
					'description' => __( 'Builder unique id', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'campaign_view_class'                      => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'buy_x_get_y_trigger_qty_status'           => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'fbt_is_trigger_product_required'          => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_prefix'                   => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'double_order_animation_type'              => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'double_order_animation_delay_between'     => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'double_order_animation_enabled'           => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'double_order_success_message'             => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'double_order_countdown_duration'          => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'free_shipping_label'                      => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				'spending_goal_upsell_products'            => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_upsell_product_status'      => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'upsell_products'                          => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
				),
				'upsell_products_status'                   => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'show_confetti'                            => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'spending_goal_progress_show_icon'         => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'is_show_free_shipping_bar'                => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'show_close_icon'                          => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'enable_cta_button'                        => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'cta_button_text'                          => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'all_goals_complete_message'               => array(
					'description' => __( 'Builder view class', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_type'                     => array(
					'description' => __( 'Countdown Timer Campaign Countdown Type', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_static_settings'          => array(
					'description' => __( 'Countdown Timer Campaign Static Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_evergreen_settings'       => array(
					'description' => __( 'Countdown Timer Campaign EverGreen Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_daily_recurring_settings' => array(
					'description' => __( 'Countdown Timer Campaign Daily Recurring Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_shop_progress_bar'        => array(
					'description' => __( 'Countdown Timer Campaign Shop page progress Bar', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_entire_site_action_type'  => array(
					'description' => __( 'Countdown Timer Campaign Entire Site Action Type', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_entire_site_action_link'  => array(
					'description' => __( 'Countdown Timer Campaign Entire Site Action Link', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_entire_site_action_enable' => array(
					'description' => __( 'Countdown Timer Campaign Entire Site Action Enable', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'countdown_timer_enable_close_button'      => array(
					'description' => __( 'Countdown Timer Campaign Entire Site Enable Close Button', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'animation_settings_enable'                => array(
					'description' => __( 'Campaign countdown timer enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'animation_type'                           => array(
					'description' => __( 'Campaign countdown timer enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'animation_duration'                       => array(
					'description' => __( 'Campaign countdown timer enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'delay_between_loop'                       => array(
					'description' => __( 'Campaign countdown timer enabled or not', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'stock_scarcity_message_type'              => array(
					'description' => __( 'Stock Scarcity Message Type', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'stock_scarcity_enable_fake_stock'         => array(
					'description' => __( 'Stock Scarcity Enable Fake Stock', 'revenue' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'stock_scarcity_general_message_settings'  => array(
					'description' => __( 'Stock Scarcity General Message Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'stock_scarcity_flip_message_settings'     => array(
					'description' => __( 'Stock Scarcity Flip Message Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'stock_scarcity_animation_settings'        => array(
					'description' => __( 'Stock Scarcity Animation Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'revx_next_order_coupon'                   => array(
					'description' => __( 'Next Order Coupon Settings', 'revenue' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		// get_campaign_keys

		return $this->add_additional_fields_schema( $schema );
	}
}
