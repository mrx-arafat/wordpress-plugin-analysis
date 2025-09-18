<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Analytics REST Controller
 *
 * @package Revenue
 */

namespace Revenue;

//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison

use DateTime;
use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

/**
 * Class Revenue_Analytics_REST_Controller
 *
 * This class extends the WP_REST_Controller class to provide custom REST API endpoints
 * for revenue analytics within the WordPress environment. It handles HTTP requests related
 * to revenue analytics and serves data in the JSON format.
 *
 * @package    Revenue
 * @subpackage REST
 * @since      1.0.0
 *
 * @uses WP_REST_Controller Core REST API controller class.
 * @uses WP_REST_Server Core REST API server class.
 * @uses WP_Error Core class for handling errors.
 *
 * @version 1.0.0
 */
class Revenue_Analytics_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'revenue/v1';

	/**
	 * Route name
	 *
	 * @var string
	 */
	protected $base = 'analytics';

	/**
	 * Register all routes related with analytics
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/total_stats',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_total_stats' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_analytics_permissions_check' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/campaigns_stats',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_campaigns_stats' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_analytics_permissions_check' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/conversion_stats',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_conversion_stats' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_analytics_permissions_check' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/most_performing_campaings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_most_performing_stats' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_analytics_permissions_check' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/recent_campaigns',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_recent_campaigns_stats' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_analytics_permissions_check' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/recent_orders',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_recent_order_stats' ),
					'args'                => $this->get_collection_params(),
					'permission_callback' => array( $this, 'get_analytics_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check permissions for analytics endpoint
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool
	 */
	public function get_analytics_permissions_check( $request ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_get_analytics_permission_check', $has_permission, $request );
	}




	/**
	 * Retrieves and processes total statistics for the specified date range and preset period.
	 *
	 * This method verifies the nonce for security, validates the date range, and calculates
	 * various statistics based on the provided data keys. It also calculates the growth or decline
	 * compared to a previous period based on the specified preset (e.g., last month, today, last week).
	 *
	 * @param array $request The request data, including security nonce, date range, data keys, and preset.
	 *
	 * @return WP_Error|WP_REST_Response
	 *    - WP_Error if nonce verification fails or invalid date range is provided.
	 *    - WP_REST_Response containing the current period results with growth data and a growth message.
	 */
	public function get_total_stats( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}
		$have_campaign_stats_keys = false;
		$have_order_stats_keys    = false;

		$is_datewise = isset( $request['datewise'] ) && sanitize_text_field( $request['datewise'] ) === 'yes';

		// Get date parameters from request.
		$from_date = isset( $request['range_query']['from'] ) ? sanitize_text_field( $request['range_query']['from'] ) : '';
		$to_date   = isset( $request['range_query']['to'] ) ? sanitize_text_field( $request['range_query']['to'] ) : '';

		if ( empty( $from_date ) || empty( $to_date ) ) {
			return new WP_Error( 'revenue_rest_invalid_date_range', __( 'Invalid date range provided!', 'revenue' ), array( 'status' => 400 ) );
		}

		// Convert dates to DateTime objects for comparison.
		$date_start = new DateTime( $from_date );
		$date_end   = new DateTime( $to_date );

		// Calculate the duration of the current period.
		$interval_days = $date_start->diff( $date_end )->format( '%a' );

		$preset = isset( $request['preset'] ) ? sanitize_text_field( $request['preset'] ) : 'last_week';

		$growth_message = '';
		switch ( $preset ) {
			case 'last_month':
				$previous_date_start = ( clone $date_start )->modify( 'first day of previous month' );
				$previous_date_end   = ( clone $date_start )->modify( 'last day of previous month' );
				$growth_message      = __( '{value} than last month', 'revenue' );
				break;
			case 'today':
				$growth_message      = __( '{value} than yesterday', 'revenue' );
				$previous_date_start = ( clone $date_start )->modify( '-1 days' );
				$previous_date_end   = ( clone $date_end )->modify( '-1 days' );
				break;
			case 'last_week':
				$growth_message      = __( '{value} than last week', 'revenue' );
				$previous_date_start = ( clone $date_start )->modify( '-7 days' );
				$previous_date_end   = ( clone $date_end )->modify( '-7 days' );

				break;

			default:
				$growth_message = __( '{value} than last period', 'revenue' );

				$previous_date_start = ( clone $date_start )->modify( '-' . ( $interval_days ) . ' days' );
				$previous_date_end   = ( clone $date_end )->modify( '-' . ( $interval_days ) . ' days' );
				break;
		}

		// Get requested data keys.
		$data_keys = isset( $request['data_keys'] ) ? array_map( 'sanitize_text_field', $request['data_keys'] ) : array();

		if ( empty( $data_keys ) ) {
			return rest_ensure_response( array() );
		}

		// Check if we need campaign or order stats keys.
		foreach ( $data_keys as $key ) {
			if ( in_array( $key, array( 'total_sales', 'orders_count', 'average_order_value', 'gross_sales' ) ) ) {
				$have_order_stats_keys = true;
			}
			if ( in_array( $key, array( 'add_to_cart_count', 'checkout_count', 'rejection_count', 'conversion_rate', 'impression_count' ) ) ) {
				$have_campaign_stats_keys = true;
			}
		}

		// Fetch and process data.
		$current_period_results  = $this->fetch_and_process_data( $request, $date_start, $date_end, $data_keys, $have_order_stats_keys, $have_campaign_stats_keys, $is_datewise );
	
		$previous_period_results = $this->fetch_and_process_data( $request, $previous_date_start, $previous_date_end, $data_keys, $have_order_stats_keys, $have_campaign_stats_keys, $is_datewise );



		if ( ! isset( $current_period_results['total'] ) ) {
			$current_period_results['total'] = 0;
		}
		if ( ! isset( $previous_period_results['total'] ) ) {
			$previous_period_results['total'] = 0;
		}

		if(defined('WPXPO_DEMO') && WPXPO_DEMO) {

			if(!isset($current_period_results['total']['total_sales'])) {
				$current_period_results['total']['total_sales']=0;
			}
			if(!isset($current_period_results['total']['average_order_value'])) {
				$current_period_results['total']['average_order_value']=0;
			}
			if(!isset($current_period_results['total']['add_to_cart_count'])) {
				$current_period_results['total']['add_to_cart_count']=0;
			}
			if(!isset($current_period_results['total']['impression_count'])) {
				$current_period_results['total']['impression_count']=0;
			}
			if(!isset($current_period_results['total']['conversion_rate'])) {
				$current_period_results['total']['conversion_rate']=0;
			}
			if(!isset($current_period_results['total']['orders_count'])) {
				$current_period_results['total']['orders_count']=0;
			}

			if(!isset($previous_period_results['total']['total_sales'])) {
				$previous_period_results['total']['total_sales']=0;
			}
			if(!isset($previous_period_results['total']['average_order_value'])) {
				$previous_period_results['total']['average_order_value']=0;
			}
			if(!isset($previous_period_results['total']['add_to_cart_count'])) {
				$previous_period_results['total']['add_to_cart_count']=0;
			}
			if(!isset($previous_period_results['total']['impression_count'])) {
				$previous_period_results['total']['impression_count']=0;
			}
			if(!isset($previous_period_results['total']['conversion_rate'])) {
				$previous_period_results['total']['conversion_rate']=0;
			}
			if(!isset($previous_period_results['total']['orders_count'])) {
				$previous_period_results['total']['orders_count']=0;
			}

			$this->populateRandomValues($current_period_results);
			$this->populateRandomValues($previous_period_results);

		}

		$current_period_results  = apply_filters('revenue_get_current_period_total_stats', $current_period_results, $date_start->format('Y-m-d'), $date_end->format('Y-m-d'));
		$previous_period_results = apply_filters('revenue_get_previous_period_total_stats', $previous_period_results, $previous_date_start->format('Y-m-d'), $previous_date_end->format('Y-m-d'));
		// Calculate percentage growth or decline
		$growth_data = revenue()->calculate_growth( $current_period_results['total'], $previous_period_results['total'], $data_keys );

		// Prepare final results array.
		$current_period_results['growth']         = $growth_data;
		$current_period_results['growth_message'] = $growth_message;

		return rest_ensure_response( $current_period_results );
	}

	public function populateRandomValues(&$array) {
		foreach ($array as $key => &$value) {
			if (is_array($value)) {
				$this->populateRandomValues($value); // Recursive call for nested arrays
			} else {
				// Populate with random values between 1 and 100
				$value = wp_rand(500, 10000);
			}
		}
	}

	/**
	 * Retrieves and processes conversion statistics for the specified date range and preset period.
	 *
	 * This method verifies the nonce for security, validates the date range, and calculates various
	 * conversion statistics based on the provided data keys. It also calculates growth or decline
	 * compared to a previous period based on the specified preset (e.g., last month, today, last week).
	 *
	 * @param array $request The request data, including security nonce, date range, data keys, and preset.
	 *
	 * @return WP_Error|WP_REST_Response
	 *    - WP_Error if nonce verification fails or invalid date range is provided.
	 *    - WP_REST_Response containing the conversion statistics for the current period with calculated growth.
	 */
	public function get_conversion_stats( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		$have_campaign_stats_keys = false;
		$have_order_stats_keys    = false;

		$is_datewise = isset( $request['datewise'] ) && sanitize_text_field( $request['datewise'] ) === 'yes';

		// Get date parameters from request.
		$from_date = isset( $request['range_query']['from'] ) ? sanitize_text_field( $request['range_query']['from'] ) : '';
		$to_date   = isset( $request['range_query']['to'] ) ? sanitize_text_field( $request['range_query']['to'] ) : '';

		if ( empty( $from_date ) || empty( $to_date ) ) {
			return new WP_Error( 'revenue_rest_invalid_date_range', __( 'Invalid date range provided!', 'revenue' ), array( 'status' => 400 ) );
		}

		// Convert dates to DateTime objects for comparison.
		$date_start = new DateTime( $from_date );
		$date_end   = new DateTime( $to_date );

		// Get requested data keys.
		$data_keys = isset( $request['data_keys'] ) ? array_map( 'sanitize_text_field', $request['data_keys'] ) : array();

		if ( empty( $data_keys ) ) {
			return rest_ensure_response( array() );
		}

		// Check if we need campaign or order stats keys.
		foreach ( $data_keys as $key ) {
			if ( in_array( $key, array( 'total_sales', 'orders_count', 'average_order_value', 'gross_sales' ) ) ) {
				$have_order_stats_keys = true;
			}
			if ( in_array( $key, array( 'add_to_cart_count', 'checkout_count', 'rejection_count', 'conversion_rate', 'impression_count' ) ) ) {
				$have_campaign_stats_keys = true;
			}
		}

		$interval_days = $date_start->diff( $date_end )->format( '%a' );

		$preset = isset( $request['preset'] ) ? sanitize_text_field( $request['preset'] ) : 'last_week';

		switch ( $preset ) {
			case 'last_month':
				$previous_date_start = ( clone $date_start )->modify( 'last day of previous month' );
				$previous_date_end   = ( clone $date_end )->modify( 'first day of previous month' );
				break;
			case 'today':
				$previous_date_start = ( clone $date_start )->modify( '-1 days' );
				$previous_date_end   = ( clone $date_end )->modify( '-1 days' );
				break;
			case 'last_week':
				$previous_date_start = ( clone $date_start )->modify( '-7 days' );
				$previous_date_end   = ( clone $date_end )->modify( '-7 days' );

				break;

			default:
				$previous_date_start = ( clone $date_start )->modify( '-' . ( $interval_days ) . ' days' );
				$previous_date_end   = ( clone $date_end )->modify( '-' . ( $interval_days ) . ' days' );
				break;
		}

		// Fetch and process data.
		$current_period_results  = $this->fetch_and_process_data( $request, $date_start, $date_end, $data_keys, $have_order_stats_keys, $have_campaign_stats_keys, $is_datewise );
		$previous_period_results = $this->fetch_and_process_data( $request, $previous_date_start, $previous_date_end, $data_keys, $have_order_stats_keys, $have_campaign_stats_keys, $is_datewise );

        if(defined('WPXPO_DEMO') && WPXPO_DEMO) {

            foreach (revenue()->get_campaign_types() as $campaign_type => $value) {
                $current_period_results[$campaign_type] = [
                    'date' => gmdate("Y-m-d"),
                    'type' => $campaign_type,
                    'add_to_cart_count' => wp_rand(50, 1000),
                    'impression_count' => wp_rand(50, 1000),
                    'conversion_rate' => wp_rand(50, 100),
                    'total_sales' => wp_rand(50, 1000),
                    'orders_count' => wp_rand(50, 1000),
                    'average_order_value' => wp_rand(50, 1000),
                    'contribute' => wp_rand(20, 100),
                ];
                $previous_period_results[$campaign_type] = [
                    'date' => gmdate("Y-m-d"),
                    'type' => $campaign_type,
                    'add_to_cart_count' => wp_rand(50, 1000),
                    'impression_count' => wp_rand(50, 1000),
                    'conversion_rate' => wp_rand(50, 100),
                    'total_sales' => wp_rand(50, 1000),
                    'orders_count' => wp_rand(50, 1000),
                    'average_order_value' => wp_rand(50, 1000),
                    'contribute' => wp_rand(20, 100),
                ];
            }
        }

		foreach ( $current_period_results as $type => $value ) {
			$previous_period_res                       = isset( $previous_period_res[ $type ] ) ? $previous_period_res[ $type ] : array();
			$current_period_res                        = $current_period_results[ $type ];
			$current_period_results[ $type ]['growth'] = revenue()->calculate_growth( $current_period_res, $previous_period_res, $data_keys );

			if ( '0.00' === $current_period_results[ $type ]['contribute'] ) {
				unset( $current_period_results[ $type ] );
			}
		}

		return rest_ensure_response( $current_period_results );
	}

	/**
	 * Retrieves and processes the most performing statistics for campaigns.
	 *
	 * This method verifies the nonce for security, executes a SQL query to gather statistics related
	 * to campaigns, including total sales and order counts. It aggregates data based on the campaigns
	 * and their performance over a specified date range. Additionally, it generates trend data for
	 * each campaign and returns the results.
	 *
	 * @param array $request The request data, including security nonce, datewise flag, and any other relevant parameters.
	 *
	 * @return WP_Error|WP_REST_Response
	 *    - WP_Error if nonce verification fails.
	 *    - WP_REST_Response containing the aggregated campaign statistics, including order counts, total sales, and trend data.
	 */
	public function get_most_performing_stats( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		global $wpdb;
		$have_campaign_stats_keys = false;
		$have_order_stats_keys    = false;

		$is_datewise = isset( $request['datewise'] ) && sanitize_text_field( $request['datewise'] ) === 'yes';

		$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT order_id, meta_value AS campaign_id FROM {$wpdb->prefix}wc_orders_meta " : "select  post_id as order_id, meta_value as campaign_id from {$wpdb->prefix}postmeta ";

		$sql = "SELECT
                DATE(order_stats.date_created) AS date,
                campaigns.id AS campaign_id,
                campaigns.campaign_name AS campaign_name,
                campaigns.campaign_placement AS page,
                COUNT(DISTINCT order_stats.order_id) AS orders_count,
                SUM(COALESCE(order_stats.total_sales, 0)) AS total_sales
            FROM
                {$wpdb->prefix}revenue_campaigns AS campaigns
            LEFT JOIN (
                $order_meta_select
                WHERE
                    meta_key = '_revx_campaign_id'
                    AND meta_value IS NOT NULL
            ) AS orders ON campaigns.id = orders.campaign_id
            LEFT JOIN {$wpdb->prefix}wc_order_stats AS order_stats ON
                order_stats.order_id = orders.order_id
            GROUP BY
                campaigns.id, DATE(order_stats.date_created)
            ORDER BY
                total_sales DESC;";

		$per_page     = isset( $request['per_page'] ) ? intval( sanitize_text_field( $request['per_page'] ) ) : 10;
		$current_page = isset( $request['paged'] ) ? absint( sanitize_text_field( $request['paged'] ) ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;

		$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT order_id, meta_value AS campaign_id FROM {$wpdb->prefix}wc_orders_meta " : "select  post_id as order_id, meta_value as campaign_id from {$wpdb->prefix}postmeta ";

		$sql = "
				SELECT
					DATE(order_stats.date_created) AS date,
					campaigns.id AS campaign_id,
					campaigns.campaign_name AS campaign_name,
					campaigns.campaign_placement AS page,
					COUNT(DISTINCT order_stats.order_id) AS orders_count,
					SUM(COALESCE(order_stats.total_sales, 0)) AS total_sales,
					COUNT(*) OVER() AS total_count
				FROM
					{$wpdb->prefix}revenue_campaigns AS campaigns
				LEFT JOIN (
					$order_meta_select
					WHERE
						meta_key = '_revx_campaign_id'
						AND meta_value IS NOT NULL
				) AS orders ON campaigns.id = orders.campaign_id
				LEFT JOIN {$wpdb->prefix}wc_order_stats AS order_stats ON
					order_stats.order_id = orders.order_id
				GROUP BY
					campaigns.id, DATE(order_stats.date_created)
				ORDER BY
					total_sales DESC
				LIMIT %d OFFSET %d;
			";

		// Prepare the query with the limits.
		$prepared_sql = $wpdb->prepare( $sql, $per_page, $offset ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Execute the query.
		$results = $wpdb->get_results( $prepared_sql ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Get the total number of records.
		$total_count = ! empty( $results ) ? $results[0]->total_count : 0;
		$total_pages = ceil( $total_count / $per_page );

		$paginations['per_page'] = $per_page;
		$paginations['paged']    = $current_page;
		$paginations['total']    = $total_count;

		$data = array();
		foreach ( $results as $datewise_data ) {

			$datewise_data = (array) $datewise_data;

			extract($datewise_data); //phpcs:ignore

			if ( ! isset( $data[ $campaign_id ] ) ) {
				$data[ $campaign_id ] = array();
			}

			$data[ $campaign_id ]['orders_count'] = revenue()->get_var( $data[ $campaign_id ]['orders_count'], 0 ) + $orders_count;
			$data[ $campaign_id ]['total_sales']  = revenue()->get_var( $data[ $campaign_id ]['total_sales'], 0 ) + $total_sales;

			$data[ $campaign_id ]['campaign_id'] = $campaign_id;

			if ( ! isset( $data[ $campaign_id ]['page'] ) ) {
				$data[ $campaign_id ]['page'] = $page;
			}

			if ( ! isset( $data[ $campaign_id ]['campaign_name'] ) ) {
				$data[ $campaign_id ]['campaign_name'] = $campaign_name;
			}

			if ( ! isset( $data[ $campaign_id ]['graph'] ) ) {
				$data[ $campaign_id ]['graph'] = $this->getLastNDaysGraphData( 7 );
			}
			$data[ $campaign_id ]['graph'][ $date ] = revenue()->get_var( $data[ $campaign_id ]['graph'][ $date ], 0 ) + $total_sales;

			$_campaign = revenue()->get_campaign_data( $campaign_id );
			$_campaign = revenue()->set_product_image_trigger_item_response( $_campaign );

			$trigger_items = array();

			if ( 'all_products' === $_campaign['campaign_trigger_type'] ) {
				$data[ $campaign_id ]['triggers'] = 'all_products';

				$data[ $campaign_id ]['campaign_trigger_type'] = 'all_products';
			} elseif ( 'products' === $_campaign['campaign_trigger_type'] ) {
				$trigger_items = $_campaign['campaign_trigger_items'];

				$data[ $campaign_id ]['triggers']              = $trigger_items;
				$data[ $campaign_id ]['campaign_trigger_type'] = 'products';
			} elseif ( 'category' === $_campaign['campaign_trigger_type'] ) {
				$trigger_items                                 = $_campaign['campaign_trigger_items'];
				$data[ $campaign_id ]['triggers']              = $trigger_items;
				$data[ $campaign_id ]['campaign_trigger_type'] = 'category';
			} else {
				$trigger_items                          = $_campaign['campaign_trigger_items'];
				$data[ $campaign_id ]['triggers']              = $trigger_items;
				$data[ $campaign_id ]['campaign_trigger_type'] = $_campaign['campaign_trigger_type'];
			}
		}

		foreach ( $data as $id => $stat ) {
			$data[ $id ]['trend'] = $this->calculateCampaignTrend( $stat['graph'] );
		}

		return rest_ensure_response(
			array(
				'paginations' => $paginations,
				'data'        => array_values( $data ),
			)
		);
	}


	/**
	 * Retrieves and calculates recent statistics for campaigns, including growth trends.
	 *
	 * This method verifies the nonce for security, retrieves campaign statistics for the current and
	 * previous periods based on provided date ranges. It calculates and returns growth trends in
	 * various metrics, such as orders count, total sales, impressions, add-to-cart counts, and conversion rates.
	 *
	 * @param array $request The request data, including security nonce, date range, and other relevant parameters.
	 *
	 * @return WP_Error|WP_REST_Response
	 *    - WP_Error if nonce verification fails or if the provided date range is invalid.
	 *    - WP_REST_Response containing the calculated growth trends for each campaign.
	 *
	 * @throws Exception Throws an exception if there are issues with date formatting or SQL queries.
	 */
	public function get_recent_campaigns_stats( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		global $wpdb;

		$is_datewise = isset( $request['datewise'] ) && sanitize_text_field( $request['datewise'] ) === 'yes';

		// Get date parameters from request.
		$from_date = isset( $request['range_query']['from'] ) ? sanitize_text_field( $request['range_query']['from'] ) : '';
		$to_date   = isset( $request['range_query']['to'] ) ? sanitize_text_field( $request['range_query']['to'] ) : '';

		if ( empty( $from_date ) || empty( $to_date ) ) {
			return new WP_Error( 'revenue_rest_invalid_date_range', __( 'Invalid date range provided!', 'revenue' ), array( 'status' => 400 ) );
		}

		$date_start = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( $from_date ) ) );
		$date_end   = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( '+1 Day', strtotime( $to_date ) ) - 1 ) );

		$date_diff       = strtotime( $to_date ) - strtotime( $from_date );
		$prev_date_start = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( $from_date ) - $date_diff ) );
		$prev_date_end   = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( $to_date ) - $date_diff ) );

		$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT order_id, meta_value AS campaign_id FROM {$wpdb->prefix}wc_orders_meta " : "select  post_id as order_id, meta_value as campaign_id from {$wpdb->prefix}postmeta ";

		$current_query = $wpdb->prepare(
			"
			SELECT
				campaigns.id AS campaign_id,
				campaigns.campaign_name AS campaign_name,
				campaigns.campaign_status AS status,
				campaigns.campaign_placement AS page,
				DATE({$wpdb->prefix}revenue_campaign_analytics.date) AS date,
				COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) AS orders_count,
				COALESCE(SUM(order_stats.total_sales), 0) AS total_sales,
				COALESCE(SUM({$wpdb->prefix}revenue_campaign_analytics.impression_count), 0) AS impression_count,
				COALESCE(SUM({$wpdb->prefix}revenue_campaign_analytics.add_to_cart_count), 0) AS add_to_cart_count,
				CASE
					WHEN COALESCE(SUM({$wpdb->prefix}revenue_campaign_analytics.impression_count), 0) > 0
						AND COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) > 0
					THEN (COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) / NULLIF(SUM({$wpdb->prefix}revenue_campaign_analytics.impression_count), 0)) * 100
					ELSE 0
				END AS conversion_rate
			FROM
				{$wpdb->prefix}revenue_campaigns AS campaigns
			LEFT JOIN (
				$order_meta_select
				WHERE
					meta_key = '_revx_campaign_id'
					AND meta_value IS NOT NULL
			) orders ON campaigns.id = orders.campaign_id
			LEFT JOIN {$wpdb->prefix}wc_order_stats order_stats ON (order_stats.order_id = orders.order_id OR order_stats.parent_id = orders.order_id)
			LEFT JOIN {$wpdb->prefix}revenue_campaign_analytics ON {$wpdb->prefix}revenue_campaign_analytics.campaign_id = campaigns.id
			WHERE
				DATE({$wpdb->prefix}revenue_campaign_analytics.date) BETWEEN %s AND %s
			GROUP BY
				campaigns.id, campaigns.campaign_name, campaigns.campaign_status, campaigns.campaign_placement
			LIMIT 6
			",
			$date_start,
			$date_end
		);

		$current_results = $wpdb->get_results( $current_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$previous_query = $wpdb->prepare(
			"
			SELECT
				campaigns.id AS campaign_id,
				campaigns.campaign_name AS campaign_name,
				campaigns.campaign_status AS status,
				campaigns.campaign_placement AS page,
				DATE({$wpdb->prefix}revenue_campaign_analytics.date) AS date,
				COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) AS orders_count,
				COALESCE(SUM(order_stats.total_sales), 0) AS total_sales,
				COALESCE(SUM({$wpdb->prefix}revenue_campaign_analytics.impression_count), 0) AS impression_count,
				COALESCE(SUM({$wpdb->prefix}revenue_campaign_analytics.add_to_cart_count), 0) AS add_to_cart_count,
				CASE
					WHEN COALESCE(SUM({$wpdb->prefix}revenue_campaign_analytics.impression_count), 0) > 0
						AND COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) > 0
					THEN (COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) / NULLIF(SUM({$wpdb->prefix}revenue_campaign_analytics.impression_count), 0)) * 100
					ELSE 0
				END AS conversion_rate
			FROM
				{$wpdb->prefix}revenue_campaigns AS campaigns
			LEFT JOIN (
				$order_meta_select
				WHERE
					meta_key = '_revx_campaign_id'
					AND meta_value IS NOT NULL
			) orders ON campaigns.id = orders.campaign_id
			LEFT JOIN {$wpdb->prefix}wc_order_stats order_stats ON (order_stats.order_id = orders.order_id OR order_stats.parent_id = orders.order_id)
			LEFT JOIN {$wpdb->prefix}revenue_campaign_analytics ON {$wpdb->prefix}revenue_campaign_analytics.campaign_id = campaigns.id
			WHERE
				DATE({$wpdb->prefix}revenue_campaign_analytics.date) BETWEEN %s AND %s
			GROUP BY
				campaigns.id, campaigns.campaign_name, campaigns.campaign_status, campaigns.campaign_placement
			LIMIT 6
			",
			$prev_date_start,
			$prev_date_end
		);

		$previous_results = $wpdb->get_results( $previous_query );  //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$growth_trends = array();

		foreach ( $current_results as $current ) {
			$previous = null;

			// Find matching previous data for the current campaign.
			foreach ( $previous_results as $prev ) {
				if ( $current->campaign_id === $prev->campaign_id ) {
					$previous = $prev;
					break;
				}
			}

			// Initialize growth trend data with current period values.
			$trend = array(
				'campaign_id'                => $current->campaign_id,
				'campaign_name'              => $current->campaign_name,
				'status'                     => $current->status,
				'page'                       => $current->page,
				'date'                       => $current->date,
				'orders_count'               => $current->orders_count,
				'previous_orders_count'      => $previous ? $previous->orders_count : 0,
				'total_sales'                => $current->total_sales,
				'previous_total_sales'       => $previous ? $previous->total_sales : 0,
				'impression_count'           => $current->impression_count,
				'previous_impression_count'  => $previous ? $previous->impression_count : 0,
				'add_to_cart_count'          => $current->add_to_cart_count,
				'previous_add_to_cart_count' => $previous ? $previous->add_to_cart_count : 0,
				'conversion_rate'            => $current->conversion_rate,
				'previous_conversion_rate'   => $previous ? $previous->conversion_rate : 0,
			);

			// Calculate growth values safely.
			if ( 0 != $trend['previous_orders_count'] ) {
				$trend['order_growth'] = ( ( $trend['orders_count'] - $trend['previous_orders_count'] ) / $trend['previous_orders_count'] ) * 100;
			} else {
				$trend['order_growth'] = 100;
			}

			if ( 0 != $trend['previous_total_sales'] ) {
				$trend['sales_growth'] = ( ( $trend['total_sales'] - $trend['previous_total_sales'] ) / $trend['previous_total_sales'] ) * 100;
			} else {
				$trend['sales_growth'] = 100;
			}

			if ( 0 != $trend['previous_impression_count'] ) {
				$trend['impression_growth'] = ( ( $trend['impression_count'] - $trend['previous_impression_count'] ) / $trend['previous_impression_count'] ) * 100;
			} else {
				$trend['impression_growth'] = 100;
			}

			if ( 0 != $trend['previous_add_to_cart_count'] ) {
				$trend['add_to_cart_growth'] = ( ( $trend['add_to_cart_count'] - $trend['previous_add_to_cart_count'] ) / $trend['previous_add_to_cart_count'] ) * 100;
			} else {
				$trend['add_to_cart_growth'] = 100;
			}

			if ( 0 != $trend['previous_conversion_rate'] ) {
				$trend['conversion_rate_growth'] = ( ( $trend['conversion_rate'] - $trend['previous_conversion_rate'] ) / $trend['previous_conversion_rate'] ) * 100;
			} else {
				$trend['conversion_rate_growth'] = 100;
			}

			$growth_trends[] = $trend;
		}

		return rest_ensure_response( $growth_trends );
	}

	/**
	 * Retrieves order statistics for campaigns, including total sales and order counts.
	 *
	 * This method verifies the nonce for security, constructs a SQL query to retrieve order statistics
	 * for campaigns based on the provided date range. It calculates the total sales and groups the results
	 * by campaign and order status. If no date range is provided, it retrieves statistics for all time.
	 *
	 * @param array $request The request data, including security nonce and optional date range.
	 *
	 * @return WP_Error|WP_REST_Response
	 *    - WP_Error if nonce verification fails.
	 *    - WP_REST_Response containing the order statistics for each campaign.
	 *
	 * @throws Exception Throws an exception if there are issues with date formatting or SQL queries.
	 */
	public function get_recent_order_stats( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		global $wpdb;

		// Get date parameters from request.
		$from_date = isset( $request['range_query']['from'] ) ? sanitize_text_field( $request['range_query']['from'] ) : '';
		$to_date   = isset( $request['range_query']['to'] ) ? sanitize_text_field( $request['range_query']['to'] ) : '';

		$date_start = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( $from_date ) ) );
		$date_end   = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( '+1 Day', strtotime( $to_date ) ) - 1 ) );

		$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "{$wpdb->prefix}wc_orders_meta co ON rc.id = co.meta_value
						LEFT JOIN
				{$wpdb->prefix}wc_order_stats wcos ON co.order_id = wcos.order_id OR wcos.parent_id = co.order_id" : "{$wpdb->prefix}postmeta co ON rc.id = co.meta_value
						LEFT JOIN
				{$wpdb->prefix}wc_order_stats wcos ON co.post_id = wcos.order_id OR wcos.parent_id = co.post_id";

		if ( $from_date && $to_date ) {
			$query = $wpdb->prepare(
				"SELECT
					MIN(rc.id) AS campaign_id,
					MIN(wcos.order_id) AS order_id,
					rc.campaign_name as campaign_name,
					rc.campaign_behavior as campaign_behavior,
					MIN(wcos.date_created) as order_date,
					wcos.status as status,
					SUM(COALESCE(wcos.total_sales, 0)) AS total
			FROM
				{$wpdb->prefix}revenue_campaigns rc
						INNER JOIN
				$order_meta_select
			WHERE
				co.meta_key = '_revx_campaign_id'
			GROUP BY
				rc.id,wcos.status,wcos.order_id
			HAVING  order_date between %s AND %s;
			",
				$date_start,
				$date_end
			);
		} else {
			$query = "
			SELECT
				MIN(rc.id) AS campaign_id,
				MIN(wcos.order_id) AS order_id,
				rc.campaign_name as campaign_name,
				rc.campaign_behavior as campaign_behavior,
				MIN(wcos.date_created) as order_date,
				wcos.status as status,
				SUM(COALESCE(wcos.total_sales, 0)) AS total
			FROM
				{$wpdb->prefix}revenue_campaigns rc
						INNER JOIN
				$order_meta_select co ON rc.id = co.meta_value
						LEFT JOIN
				{$wpdb->prefix}wc_order_stats wcos ON co.order_id = wcos.order_id OR wcos.parent_id = co.order_id
			WHERE
				co.meta_key = '_revx_campaign_id'
			GROUP BY
				rc.id,wcos.status,wcos.order_id
		";
		}

		$results = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $results as $key => $item ) {
			$order_id = $item->order_id;
			$order    = wc_get_order( $order_id );
			if ( $order ) {
				$results[ $key ]->edit_order_url = $order->get_edit_order_url();
			}
		}

		return rest_ensure_response( $results );
	}

	/**
	 * Fetches and processes analytics and order data based on the provided parameters.
	 *
	 * This method retrieves and combines data from campaigns and orders, processing it
	 * based on whether the results should be date-wise or aggregated. It handles different
	 * types of statistics, including campaign statistics, order statistics, and conversion data.
	 * The results are formatted based on the `is_campaign_stats`, `is_campaign_typewise`, and
	 * `is_datewise` flags, and returned accordingly.
	 *
	 * @param array    $request                  The request data containing parameters for fetching data.
	 * @param DateTime $start_date               The start date for the data range.
	 * @param DateTime $end_date                 The end date for the data range.
	 * @param array    $data_keys                Keys used for generating chart data.
	 * @param bool     $have_order_stats_keys    Whether order stats keys are present.
	 * @param bool     $have_campaign_stats_keys Whether campaign stats keys are present.
	 * @param bool     $is_datewise              Whether the results should be grouped by date.
	 *
	 * @return array
	 *    - If `$is_campaign_stats` is true, returns campaign statistics chart data.
	 *    - If `$is_campaign_typewise` is true, returns data grouped by campaign type with contributions.
	 *    - Otherwise, returns combined results of analytics and order data.
	 *
	 * @throws Exception Throws an exception if there are issues with data fetching or processing.
	 */
	private function fetch_and_process_data( $request, $start_date, $end_date, $data_keys, $have_order_stats_keys, $have_campaign_stats_keys, $is_datewise ) {
		$results  = array();
		$datewise = array();

		$is_campaigns_stats   = isset( $request['is_campaign_stats'] ) ? 'yes' == sanitize_text_field( $request['is_campaign_stats'] ) : false;
		$is_campaign_typewise = isset( $request['request_for'] ) ? 'conversion' == sanitize_text_field( $request['request_for'] ) : false;

		if ( $have_campaign_stats_keys ) {
			$results_analytics = $this->get_campaign_analytics_data( $request, $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ) );

			if ( ! $is_campaign_typewise ) {
				foreach ( $results_analytics as $res ) {
					$res = (array) $res;
					if ( $is_datewise ) {
						if ( ! isset( $results['total'] ) ) {
							$results['total'] = array();
						}
						foreach ( $res as $key => $value ) {
							if ( 'date' != $key ) {
								if ( ! isset( $datewise[ $key ] ) ) {
									$datewise[ $key ] = array();
								}
								$datewise[ $key ][ $res['date'] ] = $value;
								$results['total'][ $key ]         = ( $results['total'][ $key ] ?? 0 ) + $value;
							}
						}
					} else {
						$results = array_merge( $results, $res );
					}
				}
			}
		}

		if ( $have_order_stats_keys ) {
			$results_order = $this->get_order_stats_data( $request, $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ) );

			if ( ! $is_campaign_typewise ) {

				foreach ( $results_order as $res ) {
					$res = (array) $res;
					if ( $is_datewise ) {
						if ( ! isset( $results['total'] ) ) {
							$results['total'] = array();
						}
						foreach ( $res as $key => $value ) {
							if ( 'date' != $key ) {
								if ( ! isset( $datewise[ $key ] ) ) {
									$datewise[ $key ] = array();
								}
								$datewise[ $key ][ $res['date'] ] = $value;
								$results['total'][ $key ]         = ( $results['total'][ $key ] ?? 0 ) + $value;
							}
						}
					} else {
						$results = array_merge( $results, $res );
					}
				}
			}
		}

		if ( $is_datewise ) {
			$results['data'] = $this->generate_chart_data( $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ), $datewise, $data_keys );
		}

		if ( $is_campaigns_stats ) {

			$campaign_stats_chart_data = revenue()->generate_campaigns_stats_chart_data( $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ), array(), $data_keys );

			foreach ( $results_analytics as $campaign ) {
				$campaign_stats_chart_data[ $campaign->date ] = array_merge( $campaign_stats_chart_data[ $campaign->date ], (array) $campaign );
			}
			foreach ( $results_order as $order ) {
				$campaign_stats_chart_data[ $order->date ] = array_merge( $campaign_stats_chart_data[ $order->date ], (array) $order );
			}

			return $campaign_stats_chart_data;
		}

		if ( $is_campaign_typewise ) {
			$data        = array();
			$total_sales = 0;
			foreach ( $results_analytics as $res ) {
				$res = (array) $res;
				if ( ! isset( $data[ $res['type'] ] ) ) {
					$data[ $res['type'] ] = array();
				}
				$data[ $res['type'] ] = array_merge( $data[ $res['type'] ], $res );

				if ( ! isset( $res['total_sales'] ) ) {
					$res['total_sales'] = 0;
				}

				$total_sales += $res['total_sales'];
			}
			foreach ( $results_order as $res ) {
				$res = (array) $res;
				if ( ! isset( $data[ $res['type'] ] ) ) {
					$data[ $res['type'] ] = array();
				}
				$data[ $res['type'] ] = array_merge( $data[ $res['type'] ], $res );
				if ( ! isset( $res['total_sales'] ) ) {
					$res['total_sales'] = 0;
				}
				$total_sales += $res['total_sales'];
			}

			foreach ( $data as $key => $val ) {
				if ( ! isset( $val['total_sales'] ) ) {
					$val['total_sales'] = 0;
				}
				$data[ $key ]['contribute'] = 0 != $total_sales ? number_format( (float) ( ( $val['total_sales'] / $total_sales ) * 100 ), 2, '.', '' ) : number_format( 0, 2, '.', '' );
			}
			$results = $data;
		}

		return $results;
	}


	/**
	 * Retrieves order statistics data based on the provided request parameters and date range.
	 *
	 * This method constructs and executes an SQL query to fetch order statistics data, including totals, averages, and gross sales.
	 * It supports filtering by date range, campaign selection, and aggregation by date or campaign type.
	 * The data is aggregated and grouped according to the specified request parameters and returned as
	 * an array of order statistics data.
	 *
	 * @param array  $request The request data, including date range, selected campaign, and data keys.
	 * @param string $start   The start date for the data range.
	 * @param string $end     The end date for the data range.
	 *
	 * @return array An array of order statistics data based on the provided request parameters.
	 */
	public function get_campaign_analytics_data( $request, $start = false, $end = false ) {
		global $wpdb;

		$is_datewise = isset( $request['datewise'] ) ? 'yes' === $request['datewise'] : false;

		// Get date parameters from request.
		$from_date         = isset( $request['range_query']['from'] ) ? sanitize_text_field( $request['range_query']['from'] ) : '';
		$to_date           = isset( $request['range_query']['to'] ) ? sanitize_text_field( $request['range_query']['to'] ) : '';
		$selected_campaign = isset( $request['selected_campaign'] ) ? sanitize_text_field( $request['selected_campaign'] ) : '';

		$is_campaign_typewise = isset( $request['request_for'] ) ? 'conversion' === sanitize_text_field( $request['request_for'] ) : false;
		if ( $start ) {
			$from_date = $start;
		}
		if ( $end ) {
			$to_date = $end;
		}
		if ( empty( $from_date ) || empty( $to_date ) ) {
			return new WP_Error( 'revenue_rest_invalid_date_range', __( 'Invalid date range provided!', 'revenue' ), array( 'status' => 400 ) );
		}

		$data_keys = isset( $request['data_keys'] ) ? $request['data_keys'] : array();
		if ( empty( $data_keys ) ) {
			return rest_ensure_response( array() );
		}

		$group_by_clause = '';
		$order_by_clause = '';
		$select_clause   = '';
		$join            = '';

		if ( $is_datewise ) {
			$group_by_clause = 'DATE(date)';
			$order_by_clause = 'DATE(date)';
			$select_clause  .= 'DATE(date) as date, ';
		}

		if ( $is_campaign_typewise ) {
			$group_by_clause = 'campaign_type';
			$select_clause  .= 'campaign_type as type, ';
			$join           .= "LEFT JOIN {$wpdb->prefix}revenue_campaigns ON {$wpdb->prefix}revenue_campaign_analytics.campaign_id = {$wpdb->prefix}revenue_campaigns.id ";
		}

		$date_start = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( $from_date ) ) );
		$date_end   = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( '+1 Day', strtotime( $to_date ) ) - 1 ) );

		if ( in_array( 'add_to_cart_count', $data_keys ) ) {
			$select_clause .= 'COALESCE(SUM(add_to_cart_count), 0) AS add_to_cart_count, ';
		}
		if ( in_array( 'checkout_count', $data_keys ) ) {
			$select_clause .= 'COALESCE(SUM(checkout_count), 0) AS checkout_count, ';
		}
		if ( in_array( 'rejection_count', $data_keys ) ) {
			$select_clause .= 'COALESCE(SUM(rejection_count), 0) AS rejection_count, ';
		}
		if ( in_array( 'impression_count', $data_keys ) ) {
			$select_clause .= 'COALESCE(SUM(impression_count), 0) AS impression_count, ';
		}
		if ( in_array( 'conversion_rate', $data_keys ) ) {
			$select_clause .= 'CASE
				WHEN COALESCE(SUM(impression_count), 0) > 0 THEN (SUM(order_count) / SUM(impression_count)) * 100
				ELSE 0
			END AS conversion_rate, ';
		}

		$select_clause = rtrim( $select_clause, ', ' );

		// Prepare SQL query with placeholders for date range.
		$query = "SELECT $select_clause FROM {$wpdb->prefix}revenue_campaign_analytics $join WHERE date BETWEEN %s AND %s";

		// Add campaign filter if selected.
		if ( $selected_campaign ) {
			$query .= ' AND campaign_id = %s';
		}

		// Prepare group and order clauses if necessary.
		if ( ! empty( $group_by_clause ) ) {
			$query .= " GROUP BY $group_by_clause ";
		}
		if ( ! empty( $order_by_clause ) ) {
			$query .= " ORDER BY $order_by_clause";
		}

		$query .= ';';

		// Prepare the final query.
		$query = $wpdb->prepare($query, $date_start, $date_end, $selected_campaign); //phpcs:ignore

		// Execute the query.
		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results;
	}


	/**
	 * Retrieves order statistics data based on the provided request parameters and date range.
	 *
	 * This method constructs and executes an SQL query to fetch order statistics data, including totals, averages, and gross sales.
	 * It supports filtering by date range, campaign selection, and aggregation by date or campaign type.
	 * The data is aggregated and grouped according to the specified request parameters and returned as an array of results.
	 *
	 * @param array        $request The request parameters containing filter and grouping options.
	 * @param string|false $start   Optional. The start date for the data range. If not provided, uses the 'from' date from the request.
	 * @param string|false $end     Optional. The end date for the data range. If not provided, uses the 'to' date from the request.
	 *
	 * @return array|WP_Error An array of results from the query or a `WP_Error` if an invalid date range is provided.
	 *
	 * @throws WP_Error Throws a `WP_Error` if the date range is invalid.
	 */
	public function get_order_stats_data( $request, $start = false, $end = false ) {
		global $wpdb;

		$is_datewise = isset( $request['datewise'] ) ? 'yes' == sanitize_text_field( $request['datewise'] ) : false;
		$from_date   = isset( $request['range_query']['from'] ) ? sanitize_text_field( $request['range_query']['from'] ) : '';
		$to_date     = isset( $request['range_query']['to'] ) ? sanitize_text_field( $request['range_query']['to'] ) : '';

		$selected_campaign    = isset( $request['selected_campaign'] ) ? sanitize_text_field( $request['selected_campaign'] ) : '';
		$is_campaign_typewise = isset( $request['request_for'] ) ? 'conversion' == sanitize_text_field( $request['request_for'] ) : false;

		if ( $start ) {
			$from_date = $start;
		}

		if ( $end ) {
			$to_date = $end;
		}
		if ( empty( $from_date ) || empty( $to_date ) ) {
			return new WP_Error( 'revenue_rest_invalid_date_range', __( 'Invalid date range provided!', 'revenue' ), array( 'status' => 400 ) );
		}

		// Get requested data keys.
		$data_keys = isset( $request['data_keys'] ) ? array_map( 'sanitize_text_field', $request['data_keys'] ) : array();

		if ( empty( $data_keys ) ) {
			return rest_ensure_response( array() );
		}

		$date_start = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( $from_date ) ) );
		$date_end   = $this->build_mysql_datetime( gmdate( 'Y-m-d H:i:s', strtotime( '+1 Day', strtotime( $to_date ) ) - 1 ) );

		$group_by_clause = '';
		$order_by_clause = '';
		$select_clause   = '';
		$join            = '';
		$where_clause    = '';

		if ( $is_datewise ) {
			$group_by_clause = 'date';
			$order_by_clause = 'date ASC';
			$select_clause  .= "DATE({$wpdb->prefix}wc_order_stats.date_created) AS date, ";
		}
		if ( $is_campaign_typewise ) {
			$select_clause    .= "{$wpdb->prefix}revenue_campaigns.campaign_type as type, ";
			$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT {$wpdb->prefix}wc_orders_meta.order_id, {$wpdb->prefix}wc_orders_meta.meta_value AS campaign_id FROM {$wpdb->prefix}wc_orders_meta WHERE
					{$wpdb->prefix}wc_orders_meta.meta_key = '_revx_campaign_id'
					AND {$wpdb->prefix}wc_orders_meta.meta_value IS NOT NULL" : "select  {$wpdb->prefix}postmeta.post_id as order_id, {$wpdb->prefix}postmeta.meta_value as campaign_id from {$wpdb->prefix}postmeta WHERE
					{$wpdb->prefix}postmeta.meta_key = '_revx_campaign_id'
					AND {$wpdb->prefix}postmeta.meta_value IS NOT NULL";

			$join           .= "
			LEFT JOIN (
				$order_meta_select
			) AS campaign_order ON {$wpdb->prefix}revenue_campaigns.id = campaign_order.campaign_id
			LEFT JOIN {$wpdb->prefix}wc_order_stats AS {$wpdb->prefix}wc_order_stats ON campaign_order.order_id = {$wpdb->prefix}wc_order_stats.order_id
			";
			$group_by_clause = "{$wpdb->prefix}revenue_campaigns.campaign_type";
		}
		// Determine what to select based on requested data keys.
		if ( in_array( 'total_sales', $data_keys ) ) {
			$select_clause .= "SUM(COALESCE({$wpdb->prefix}wc_order_stats.total_sales, 0)) AS total_sales, ";
		}
		if ( in_array( 'orders_count', $data_keys ) ) {
			$select_clause .= " SUM(CASE WHEN {$wpdb->prefix}wc_order_stats.parent_id = 0 THEN 1 ELSE 0 END) as orders_count, ";
		}
		if ( in_array( 'average_order_value', $data_keys ) ) {
			$select_clause .= "SUM({$wpdb->prefix}wc_order_stats.net_total) / SUM(CASE WHEN {$wpdb->prefix}wc_order_stats.parent_id = 0 THEN 1 ELSE 0 END) AS average_order_value, ";
		}
		if ( in_array( 'gross_sales', $data_keys ) ) {
			$select_clause .= "(SUM({$wpdb->prefix}wc_order_stats.total_sales) + COALESCE(SUM(discount_amount), 0) - SUM({$wpdb->prefix}wc_order_stats.tax_total) - SUM({$wpdb->prefix}wc_order_stats.shipping_total) + ABS(SUM(CASE WHEN {$wpdb->prefix}wc_order_stats.net_total < 0 THEN {$wpdb->prefix}wc_order_stats.net_total ELSE 0 END))) as gross_sales, ";
		}

		$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT order_id FROM {$wpdb->prefix}wc_orders_meta " : "select  post_id as order_id from {$wpdb->prefix}postmeta ";

		// Remove trailing comma from select clause.
		$select_clause = rtrim( $select_clause, ', ' );
		$query         = $wpdb->prepare( " SELECT {$select_clause} FROM {$wpdb->prefix}wc_order_stats AS {$wpdb->prefix}wc_order_stats WHERE {$wpdb->prefix}wc_order_stats.status NOT IN ('wc-auto-draft', 'wc-trash', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-checkout-draft') AND {$wpdb->prefix}wc_order_stats.date_created BETWEEN %s AND %s AND {$wpdb->prefix}wc_order_stats.order_id IN ( $order_meta_select WHERE meta_key = '_revx_campaign_id' AND meta_value IS NOT NULL )", $date_start, $date_end ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $selected_campaign ) {
			$query = $wpdb->prepare( "SELECT {$select_clause} FROM {$wpdb->prefix}wc_order_stats AS {$wpdb->prefix}wc_order_stats WHERE {$wpdb->prefix}wc_order_stats.status NOT IN ('wc-auto-draft', 'wc-trash', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-checkout-draft') AND {$wpdb->prefix}wc_order_stats.date_created BETWEEN %s AND %s AND {$wpdb->prefix}wc_order_stats.order_id IN ( $order_meta_select WHERE meta_key = '_revx_campaign_id' AND meta_value <> {$selected_campaign} )", $date_start, $date_end ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( $is_campaign_typewise ) {
			$query = $wpdb->prepare( "SELECT {$select_clause} FROM {$wpdb->prefix}revenue_campaigns {$join} WHERE {$wpdb->prefix}wc_order_stats.status NOT IN ('wc-auto-draft', 'wc-trash', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-checkout-draft') AND {$wpdb->prefix}wc_order_stats.date_created BETWEEN %s AND %s", $date_start, $date_end ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( ! empty( $group_by_clause ) ) {
			$query .=
				"GROUP BY
				{$group_by_clause}
			";
		}
		if ( ! empty( $order_by_clause ) ) {
			$query .=
				"ORDER BY
				{$order_by_clause}
			";
		}

		if ( ! empty( $query ) ) {
			$query .= ';';
		}

		$results = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results;
	}

	/**
	 * Retrieves campaign statistics data based on the provided request parameters and date range.
	 *
	 * This method verifies the nonce for security, processes the request to determine the required statistics,
	 * and then fetches and returns the campaign and order statistics data based on the provided parameters.
	 * It supports date range filtering and aggregates data based on whether campaign or order statistics are requested.
	 *
	 * @param array $request The request parameters containing security nonce, date range, and data keys.
	 *
	 * @return WP_Error|WP_REST_Response A `WP_Error` object if there is a nonce verification failure or invalid date range,
	 *                                   or a `WP_REST_Response` containing the campaign statistics data.
	 *
	 * @throws WP_Error Throws a `WP_Error` if the nonce verification fails or if the date range is invalid.
	 */
	public function get_campaigns_stats( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_err0r', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		global $wpdb;

		$is_datewise = isset( $request['datewise'] ) ? 'yes' == sanitize_text_field( $request['datewise'] ) : false;
		$from_date   = isset( $request['range_query']['from'] ) ? sanitize_text_field( $request['range_query']['from'] ) : '';
		$to_date     = isset( $request['range_query']['to'] ) ? sanitize_text_field( $request['range_query']['to'] ) : '';

		if ( empty( $from_date ) || empty( $to_date ) ) {
			return new WP_Error( 'revenue_rest_invalid_date_range', __( 'Invalid date range provided!', 'revenue' ), array( 'status' => 400 ) );
		}

		$have_campaign_stats_keys = false;
		$have_order_stats_keys    = false;

		// Convert dates to DateTime objects for comparison.
		$date_start = new DateTime( $from_date );
		$date_end   = new DateTime( $to_date );

		// Calculate the duration of the current period.
		$interval_days = $date_start->diff( $date_end )->format( '%a' );

		// Get requested data keys.
		$data_keys = isset( $request['data_keys'] ) ? array_map( 'sanitize_text_field', $request['data_keys'] ) : array();

		if ( empty( $data_keys ) ) {
			return rest_ensure_response( array() );
		}

		// Check if we need campaign or order stats keys.
		foreach ( $data_keys as $key ) {
			if ( in_array( $key, array( 'total_sales', 'orders_count', 'average_order_value', 'gross_sales' ) ) ) {
				$have_order_stats_keys = true;
			}
			if ( in_array( $key, array( 'add_to_cart_count', 'checkout_count', 'rejection_count', 'conversion_rate', 'impression_count' ) ) ) {
				$have_campaign_stats_keys = true;
			}
		}

		// Fetch and process data.
		$current_period_results = $this->fetch_and_process_data( $request, $date_start, $date_end, $data_keys, $have_order_stats_keys, $have_campaign_stats_keys, $is_datewise );

		foreach ($current_period_results as $date => $data) {
			$current_period_results[$date] = [
				'total_sales' => wp_rand(20,1000),
				'rejection_count' => wp_rand(1,100),
				'add_to_cart_count' => wp_rand(50,100),
				'impression_count' => wp_rand(500,1000),
				'conversion_rate' => wp_rand(1,100),
				'orders_count' => wp_rand(20,100),
				'date' => $date
			];
		}

		return rest_ensure_response( $current_period_results );
	}

	/**
	 * Generates chart data for the specified date range, initializing data for each date and merging with provided data.
	 *
	 * This method creates a date-based array with zero values for each day in the specified range. It then merges this
	 * base array with the provided data for the specified keys, ensuring that all dates within the range are represented
	 * in the final chart data.
	 *
	 * @param string $start The start date of the range in 'Y-m-d' format.
	 * @param string $end   The end date of the range in 'Y-m-d' format.
	 * @param array  $data  An associative array containing data to be merged with the generated date range.
	 *                      The keys of this array should match those provided in the `$keys` parameter.
	 * @param array  $keys  An array of keys that should be included in the output data. Each key should correspond to an
	 *                      entry in the `$data` array.
	 *
	 * @return array An associative array containing the merged chart data. Each key will have data for all dates in
	 *               the specified range, with dates that were not initially present in `$data` being filled with zero values.
	 */
	public function generate_chart_data( $start, $end, $data, $keys ) {
		$date_array = array();
		$cur_date   = new DateTime( $start );
		$end_date   = new DateTime( $end );

		while ( $cur_date <= $end_date ) {
			$formated_date                = $cur_date->format( 'Y-m-d' ); // Format date as YYYY-MM-DD.
			$date_array[ $formated_date ] = 0;
			$cur_date->modify( '+1 day' ); // Increment current date by 1 day.
		}

		foreach ( $keys as $key ) {
			$data[ $key ] = array_merge( $date_array, isset( $data[ $key ] ) ? $data[ $key ] : array() );
		}

		return $data;
	}

	/**
	 * Build MySQL datetime format
	 *
	 * @param  string $datetime Date Time.
	 * @return string
	 */
	public function build_mysql_datetime( $datetime ) {
		return gmdate( 'Y-m-d H:i:s', strtotime( $datetime ) );
	}

	/**
	 * Get last N days graph data
	 *
	 * @param  int $days    Number of days.
	 * @param  int $def_val Default value.
	 * @return array
	 */
	public function getLastNDaysGraphData( $days = 7, $def_val = 0 ) {
		$dates    = array();
		$cur_date = new DateTime(); // Today's date.

		for ( $i = 0; $i < $days; $i++ ) {
			$dates[ $cur_date->format( 'Y-m-d' ) ] = $def_val;
			$cur_date->modify( '-1 day' );
		}

		return $dates;
	}


	/**
	 * Get Campaign Trend
	 *
	 * @param  array $data Data.
	 * @return array
	 */
	public function calculateCampaignTrend( $data ) {
		$cur_val  = null;
		$prev_sum = 0;

		// Traverse the data to calculate current value and sum of previous values.
		foreach ( $data as $date => $value ) {
			if ( null === $cur_val ) {
				$cur_val = $value; // Set current value (today's value).
			} else {
				$prev_sum += $value; // Accumulate sum of previous values.
			}
		}

		// Determine trend and return boolean.
		return $cur_val > $prev_sum ? 'up' : 'down';
	}

	/**
	 * Get Campaign Stats.
	 *
	 * @param  array $request Request.
	 * @return array
	 */
	public function get_campaign_total_stats( $request ) {
		global $wpdb;

		$campaign_id = isset( $request['campaign_id'] ) ? sanitize_text_field( $request['campaign_id'] ) : '';

		$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT order_id, meta_value AS campaign_id FROM {$wpdb->prefix}wc_orders_meta " : "select  post_id as order_id, meta_value as campaign_id from {$wpdb->prefix}postmeta ";

		$prepared_query = $wpdb->prepare(
			" SELECT
        DATE(analytics.date) AS date,
        COALESCE(SUM(order_stats.total_sales), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) AS orders_count,
        CASE
            WHEN COALESCE(SUM(analytics.impression_count), 0) > 0 THEN (SUM(analytics.order_count) / SUM(analytics.impression_count)) * 100
            ELSE 0
        END AS conversion_rate,
        COALESCE(SUM(analytics.impression_count), 0) AS impression_count,
        COALESCE(SUM(analytics.add_to_cart_count), 0) AS add_to_cart,
        COALESCE(SUM(analytics.rejection_count), 0) AS rejection_count,
        COALESCE(SUM(analytics.checkout_count), 0) AS checkout_count
    FROM
        {$wpdb->prefix}revenue_campaign_analytics AS analytics
    LEFT JOIN (
        $order_meta_select
        WHERE
            meta_key = '_revx_campaign_id'
            AND meta_value IS NOT NULL
    ) AS orders ON analytics.campaign_id = orders.campaign_id
    LEFT JOIN {$wpdb->prefix}wc_order_stats order_stats ON (order_stats.order_id = orders.order_id OR order_stats.parent_id = orders.order_id) AND orders.order_id IS NOT NULL
    WHERE
        analytics.campaign_id = %d
        AND order_stats.status NOT IN ('wc-auto-draft', 'wc-trash', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-checkout-draft')
    GROUP BY
        DATE(analytics.date)
",
			$campaign_id
		); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results        = $wpdb->get_results( $prepared_query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return rest_ensure_response( $results );
	}

	/**
	 * Get Campaign Stats.
	 *
	 * @param  array $request Request.
	 * @return array
	 */
	public function get_campaign_stats( $request ) {
		global $wpdb;

		$campaign_id = isset( $request['campaign_id'] ) ? sanitize_text_field( $request['campaign_id'] ) : '';

		// Get campaign start date.
		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaign_start_date = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT DATE(date_created) FROM {$wpdb->prefix}revenue_campaigns WHERE id = %d",
				$campaign_id
			)
		);

		$order_meta_select = revenue()->is_custom_orders_table_usages_enabled() ? "SELECT order_id, meta_value AS campaign_id FROM {$wpdb->prefix}wc_orders_meta " : "select  post_id as order_id, meta_value as campaign_id from {$wpdb->prefix}postmeta ";

		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
            DATE(analytics.date) AS date,
            COALESCE(SUM(order_stats.total_sales), 0) AS total_sales,
            COALESCE(SUM(CASE WHEN order_stats.parent_id = 0 THEN 1 ELSE 0 END), 0) AS orders_count,
            CASE
                WHEN COALESCE(SUM(analytics.impression_count), 0) > 0 THEN (SUM(analytics.order_count) / SUM(analytics.impression_count)) * 100
                ELSE 0
            END AS conversion_rate,
            COALESCE(SUM(analytics.impression_count), 0) AS impression_count,
            COALESCE(SUM(analytics.add_to_cart_count), 0) AS add_to_cart,
            COALESCE(SUM(analytics.rejection_count), 0) AS rejection_count,
            COALESCE(SUM(analytics.checkout_count), 0) AS checkout_count
        FROM
            {$wpdb->prefix}revenue_campaign_analytics AS analytics
        LEFT JOIN (
            $order_meta_select
            WHERE
                meta_key = '_revx_campaign_id'
                AND meta_value IS NOT NULL
        ) AS orders ON analytics.campaign_id = orders.campaign_id
        LEFT JOIN {$wpdb->prefix}wc_order_stats order_stats ON (order_stats.order_id = orders.order_id OR order_stats.parent_id = orders.order_id) AND orders.order_id IS NOT NULL
        WHERE
            analytics.campaign_id = %d
            AND order_stats.status NOT IN ('wc-auto-draft', 'wc-trash', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-checkout-draft')
        GROUP BY
            DATE(analytics.date)",
				$campaign_id
			)
		);

		$start_date = new DateTime( $campaign_start_date );
		$end_date   = new DateTime( 'now' );

		$data_keys = isset( $request['data_keys'] ) ? $request['data_keys'] : array();

		$campaign_stats_chart_data = revenue()->generate_campaigns_stats_chart_data( $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ), array(), $data_keys );

		foreach ( $results as $campaign ) {
			$campaign_stats_chart_data[ $campaign->date ] = array_merge( $campaign_stats_chart_data[ $campaign->date ], (array) $campaign );
		}

		$today     = gmdate( 'Y-m-d' );
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		$growth = revenue()->calculate_growth( $campaign_stats_chart_data[ $today ], $campaign_stats_chart_data[ $yesterday ], $data_keys );

		return array(
			'data'   => $campaign_stats_chart_data,
			'growth' => $growth,
		);
	}
}
