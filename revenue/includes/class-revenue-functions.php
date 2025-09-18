<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

namespace Revenue;

use WC_DateTime;
use DateTimeZone;
use Exception;
use DateTime;
use Automattic\WooCommerce\Utilities\OrderUtil;

//phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid, WordPress.PHP.DontExtract.extract_extract, WordPress.PHP.StrictInArray.MissingTrueStrict

/**
 * Contains Common Functions
 */
class Revenue_Functions {

	/**
	 * Store the cart product IDs.
	 *
	 * @var array|null
	 */
	private static $cart_product_ids = null;

	/**
	 * Store the cart product IDs.
	 *
	 * @var array|null
	 */
	public function __construct() {
		$this->register_hooks();
	}
	/**
	 * Get campaign shortcode tag
	 *
	 * @return string
	 */
	public function get_campaign_shortcode_tag() {
		return apply_filters( 'revenue_get_campaign_shortcode_tag', 'revenue_campaign' );
	}
	/**
	 * Get revenue admin menu position
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_admin_menu_position() {
		return apply_filters( 'revenue_menu_position', '58' );
	}

	/**
	 * Get revenue admin menu capability
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_admin_menu_capability() {
		return apply_filters( 'revenue_menu_capability', 'manage_options' );
	}

	/**
	 * Is user can see revenue admin menu
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function is_user_allowed_to_revenue_dashboard() {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_show_dashboard', $has_permission );
	}

	/**
	 * Get revenue admin menu slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_admin_menu_slug() {
		return apply_filters( 'revenue_menu_slug', 'revenue' );
	}

	/**
	 * Get revenue admin menu slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_admin_menu_title() {
		return apply_filters( 'revenue_menu_title', __( 'WowRevenue', 'revenue' ) );
	}

	/**
	 * Get revenue admin menu slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function is_whitelabel_enabled() {
		return 'yes' === apply_filters( 'revenue_whitelabel_status', 'no' );
	}
	/**
	 * Get revenue campaign types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_types() {
		$types = array(
			'normal_discount'            => _x( 'Normal Discounts', 'Campaign Types', 'revenue' ),
			'bundle_discount'            => _x( 'Bundle Discounts', 'Campaign Types', 'revenue' ),
			'volume_discount'            => _x( 'Volume Discounts', 'Campaign Types', 'revenue' ),
			'buy_x_get_y'                => _x( 'Buy X Get Y Discounts', 'Campaign Types', 'revenue' ),
			'mix_match'                  => _x( 'Product Mix Match', 'Campaign Types', 'revenue' ),
			'frequently_bought_together' => _x( 'Frequently Bought Together', 'Campaign Types', 'revenue' ),
			'double_order'               => _x( 'Double Order', 'Campaign Types', 'revenue' ),
			'spending_goal'              => _x( 'Spending Goal', 'Campaign Types', 'revenue' ),
			'free_shipping_bar'          => _x( 'Free Shipping', 'Campaign Types', 'revenue' ),
			'stock_scarcity'             => _x( 'Stock Scarcity', 'Campaign Types', 'revenue' ),
			'countdown_timer'            => _x( 'Countdown Timer', 'Campaign Types', 'revenue' ),
			'next_order_coupon'          => _x( 'Next Order Coupon', 'Campaign Types', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_types', $types );
	}
	/**
	 * Get revenue campaign statuses
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_statuses() {
		$types = array(
			'draft'   => _x( 'Draft', 'Campaign Statuses', 'revenue' ),
			'publish' => _x( 'Publish', 'Campaign Statuses', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_statuses', $types );
	}
	/**
	 * Get revenue trigger types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_trigger_types() {
		$types = array(
			'all_products' => _x( 'All Products', 'Trigger Types', 'revenue' ),
			'products'     => _x( 'Specific Products', 'Trigger Types', 'revenue' ),
			'category'     => _x( 'Specific Category', 'Trigger Types', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_trigger_types', $types );
	}
	/**
	 * Get revenue display types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_display_types() {
		$types = array(
			'inpage'   => _x( 'In Page', 'Display Types', 'revenue' ),
			'popup'    => _x( 'Pop-up', 'Display Types', 'revenue' ),
			'floating' => _x( 'Floating', 'Display Types', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_display_types', $types );
	}
	/**
	 * Get revenue display types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_floating_positions() {
		$types = array(
			'bottom-right' => _x( 'Bottom Right', 'Floating positions', 'revenue' ),
			'bottom-left'  => _x( 'Bottom Left', 'Floating positions', 'revenue' ),
			'top-right'    => _x( 'Top Right', 'Floating positions', 'revenue' ),
			'top-left'     => _x( 'Top Left', 'Floating positions', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_display_types', $types );
	}

	/**
	 * Get Stock scarcity notice positions
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_stock_scarcity_notice_positions() {
		$positions = array(
			'before_add_to_cart_button'     => _x( 'Before add to cart button', 'Stock Scarcity Positions', 'revenue' ),
			'after_add_to_cart_button'      => _x( 'After add to cart button', 'Stock Scarcity Positions', 'revenue' ),
			'after_add_to_cart_quantity'    => _x( 'After add to cart quantity', 'Stock Scarcity Positions', 'revenue' ),
			'before_add_to_cart_quantity'   => _x( 'Before add to cart quantity', 'Stock Scarcity Positions', 'revenue' ),
			'before_add_to_cart_form'       => _x( 'Before add to cart button', 'Stock Scarcity Positions', 'revenue' ),
			'after_add_to_cart_form'        => _x( 'After add to cart button', 'Stock Scarcity Positions', 'revenue' ),
			'before_single_product_summary' => _x( 'Before single product summary', 'Stock Scarcity Positions', 'revenue' ),
			'after_single_product_summary'  => _x( 'After single product summary', 'Stock Scarcity Positions', 'revenue' ),
			'after_single_product'          => _x( 'After single product', 'Stock Scarcity Positions', 'revenue' ),
			'before_single_product'         => _x( 'Before single product', 'Stock Scarcity Positions', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_stock_scarcity_notice_positions', $positions );
	}
	/**
	 * Get revenue campaign placements
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_placements() {

		$placement_options = array(
			'normal_discount'            => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'bundle_discount'            => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'volume_discount'            => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),

			'buy_x_get_y'                => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),

			'mix_match'                  => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'frequently_bought_together' => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'double_order'               => array(
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
			),
			'spending_goal'              => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'all_page'      => _x( 'Entire Site', 'Page Type', 'revenue' ),
			),
			'free_shipping_bar'          => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'all_page'      => _x( 'Entire Site', 'Page Type', 'revenue' ),
			),
			'stock_scarcity'             => array(
				'product_page' => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'    => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'shop_page'    => _x( 'Shop Page', 'Page Type', 'revenue' ),
			),
			'countdown_timer'            => array(
				'product_page' => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'    => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'all_page'     => _x( 'Entire Site', 'Page Type', 'revenue' ),
				'shop_page'    => _x( 'Shop Page', 'Page Type', 'revenue' ),
			),
			'next_order_coupon'          => array(
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
				'to_email'      => _x( 'To Email', 'Page Type', 'revenue' ),
				'my_account'    => _x( 'My Account', 'Page Type', 'revenue' ),
			),
		);
		return apply_filters( 'revenue_campaign_placements', $placement_options );
	}
	/**
	 * Get revenuex display in page campaignions
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_inpage_positions() {
		// Normal Discounts.
		$product_page_positions = array(
			'none'                         => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_add_to_cart_form'      => _x( 'Before add to cart Button', 'Display Positions', 'revenue' ),
			'after_add_to_cart_form'       => _x( 'After add to cart Button', 'Display Positions', 'revenue' ),
			'after_single_product_summary' => _x( 'After single product summary', 'Display Positions', 'revenue' ),
			'before_single_product'        => _x( 'Before single product', 'Stock Scarcity Positions', 'revenue' ),
			'after_single_product'         => _x( 'After single product', 'Stock Scarcity Positions', 'revenue' ),
		);

		$theme = get_option( 'template' );

		if ( 'astra' === $theme ) {
			unset( $product_page_positions['after_single_product_summary'] );
		}

		$cart_page_positions = array(
			'none'                => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_cart_table'   => _x( 'Before cart table', 'Display Positions', 'revenue' ),
			'before_cart'         => _x( 'Before cart', 'Display Positions', 'revenue' ),
			'after_cart_table'    => _x( 'After cart table', 'Display Positions', 'revenue' ),
			'after_cart'          => _x( 'After cart', 'Display Positions', 'revenue' ),
			'before_cart_totals'  => _x( 'Before Cart Total', 'Display Positions', 'revenue' ),
			'after_cart_totals'   => _x( 'After Cart Total', 'Display Positions', 'revenue' ),
			'proceed_to_checkout' => _x( 'Proceed to Checkout', 'Display Positions', 'revenue' ),

		);
		$checkout_page_positions = array(
			'none'                         => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_checkout_form'         => _x( 'Before checkout form', 'Display Positions', 'revenue' ),
			'before_checkout_billing_form' => _x( 'Before checkout billing form', 'Display Positions', 'revenue' ),
			'after_checkout_billing_form'  => _x( 'After checkout billing form', 'Display Positions', 'revenue' ),
			'checkout_before_order_review' => _x( 'Before order review', 'Display Positions', 'revenue' ),
			'review_order_before_payment'  => _x( 'Before payment', 'Display Positions', 'revenue' ),
			'review_order_after_payment'   => _x( 'After payment', 'Display Positions', 'revenue' ),
			'after_checkout_form'          => _x( 'After checkout form', 'Display Positions', 'revenue' ),

		);

		$thankyou_page_positions = array(
			'none'            => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_thankyou' => _x( 'Before Thank You Message', 'Display Positions', 'revenue' ),
			'thankyou'        => _x( 'After Thank You Message', 'Display Positions', 'revenue' ),
		);
		$shop_page_positions     = array(
			'none'                       => __( 'None (Select if using shortcode)', 'revenue' ),
			'after_shop_loop_item_title' => _x( 'Below the Product Title', 'Display Positions', 'revenue' ),
			'shop_loop_item_title'       => _x( 'Before the Product Title', 'Display Positions', 'revenue' ),
		);

		// Email positions.
		$email_positions = array(
			'email_before_order_table' => _x( 'Before Order Table', 'Display Positions', 'revenue' ),
			'email_after_order_table'  => _x( 'After Order Table', 'Display Positions', 'revenue' ),
			'email_after_order_meta'   => _x( 'After Order Meta', 'Display Positions', 'revenue' ),
			'email_after_order_items'  => _x( 'After Order Items', 'Display Positions', 'revenue' ),
		);

		// my account positions.
		$my_account_positions = array(
			'my_account_before_order_table' => _x( 'Bottom of the Page', 'Display Positions', 'revenue' ),
		);
		$types                = array(
			'product_page'  => $product_page_positions,
			'cart_page'     => $cart_page_positions,
			'checkout_page' => $checkout_page_positions,
			'thankyou_page' => $thankyou_page_positions,
			'shop_page'     => $shop_page_positions,
			'to_email'      => $email_positions,
			'my_account'    => $my_account_positions,
		);

		return apply_filters( 'revenue_campaign_in_page_display_positions', $types );
	}

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_hooks() {
		if ( 'astra' === get_option( 'template' ) ) {
			add_action(
				'astra_woo_single_title_after',
				function () {
					do_action( 'rvex_below_the_product_title' );
				}
			);
		} else {
			add_action(
				'woocommerce_single_product_summary',
				function () {
					do_action( 'rvex_below_the_product_title' );
				},
				10
			);
		}
		if ( 'astra' === get_option( 'template' ) ) {
			add_action(
				'astra_woo_single_price_after',
				function () {
					do_action( 'rvex_below_the_product_price' );
				},
				11
			);
		} else {
			add_action(
				'woocommerce_single_product_summary',
				function () {
					do_action( 'rvex_below_the_product_price' );
				},
				11
			);
		}
		add_action(
			'woocommerce_account_content',
			function () {
				do_action( 'rvex_above_my_account' );
			},
			5
		);
		add_action(
			'woocommerce_account_content',
			function () {
				do_action( 'rvex_below_my_account' );
			},
			25
		);
	}

	/**
	 * Get revenue display in page campaignions
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_popup_positions() {
		$types = array(
			'product_page'  => 'before_single_product',
			'shop_page'     => '',
			'cart_page'     => 'woocommerce_before_cart',
			'checkout_page' => 'woocommerce_before_checkout_form',
			'thankyou_page' => 'woocommerce_before_thankyou',
		);

		return apply_filters( 'revenue_campaign_popup_display_positions', $types );
	}
	/**
	 * Get revenue display in page campaignions
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_floating_positions_hook() {
		$types = array(
			'product_page'  => 'before_single_product',
			'shop_page'     => '',
			'cart_page'     => 'woocommerce_before_cart',
			'checkout_page' => 'woocommerce_before_checkout_form',
			'thankyou_page' => 'woocommerce_before_thankyou',
		);

		return apply_filters( 'revenue_campaign_floating_display_positions', $types );
	}
	/**
	 * Get revenue animated add to cart animation types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_popup_animation_types() {
		$types = array(
			// ''        => __( 'None', 'revenue' ),
			'fade'    => __( 'Fade', 'revenue' ),
			'slide'   => __( 'Slide', 'revenue' ),
			'zoom'    => __( 'Zoom', 'revenue' ),
			'bounce'  => __( 'Bounce', 'revenue' ),
			'shake'   => __( 'Shake', 'revenue' ),
			'swing'   => __( 'Swing', 'revenue' ),
			'wobble'  => __( 'Wobble', 'revenue' ),
			'vibrate' => __( 'Flash', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_popup_animation_types', $types );
	}
	/**
	 * Get revenue animated add to cart animation types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_animated_add_to_cart_animation_types() {
		$types = array(

			'wobble' => __( 'Wobble', 'revenue' ),
			'shake'  => __( 'Shake', 'revenue' ),
			'zoom'   => __( 'Zoom', 'revenue' ),
			'pulse'  => __( 'Pulse', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_animated_add_to_cart_animation_types', $types );
	}

	/**
	 * Get Pricing Page URL
	 *
	 * @return string
	 */
	public function get_pricing_page_url() {
		return 'https://www.wowrevenue.com/#pricing';
	}

	/**
	 * Get campaign data
	 *
	 * @param string $id ID.
	 * @param string $context Context.
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_data( $id, $context = 'raw' ) {
		if ( 'raw' === $context ) {
			$GLOBALS['revenue_campaign_data'][ $id ] = $this->read_campaign_data( $id, false, $context );
		}
		if ( ! isset( $GLOBALS['revenue_campaign_data'][ $id ] ) ) {
			if ( ! isset( $GLOBALS['revenue_campaign_data'] ) ) {
				$GLOBALS['revenue_campaign_data'] = array();
			}
			if ( ! is_array( $GLOBALS['revenue_campaign_data'] ) ) {
				$GLOBALS['revenue_campaign_data'] = array();
			}

			$GLOBALS['revenue_campaign_data'][ $id ] = $this->read_campaign_data( $id, false, $context );

			if ( ! $GLOBALS['revenue_campaign_data'][ $id ] ) {
				unset( $GLOBALS['revenue_campaign_data'][ $id ] );
			}
		}

		return $this->get_var( $GLOBALS['revenue_campaign_data'][ $id ] ) ?? false;
	}

	/**
	 * Retrieve a list of campaigns based on provided arguments, with caching.
	 *
	 * This method queries the `revenue_campaigns` table to fetch campaign IDs based on the
	 * specified display type, position, page, and exclusion list. It uses caching to store
	 * the results and improve performance by reducing the number of database queries.
	 *
	 * @param array $args {
	 *     Optional. Arguments to filter the campaigns.
	 *
	 *     @type string $page          The page where the campaign is displayed. Default is 'product_page'.
	 *     @type string $display_type  The display type of the campaign. Default is 'inpage'.
	 *     @type string $position      The position of the campaign on the page. Default is 'before_add_to_cart_button'.
	 *     @type string $product_id    The product ID associated with the campaign. Default is an empty string.
	 *     @type array  $exclude       An array of campaign IDs to exclude from the results. Default is an empty array.
	 * }
	 *
	 * @return array An array of campaign IDs that match the specified criteria. Each item in the array is an associative array with the 'id' key.
	 */
	public function get_campaigns( $args ) {
		global $wpdb;

		// Parse arguments
		$args = wp_parse_args(
			$args,
			array(
				'page'          => 'product_page',
				// 'display_type' => 'inpage',
				// 'position'     => 'before_add_to_cart_button',
				'product_id'    => '',
				'exclude'       => array(), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'trigger_type'  => 'all_products',
				'campaign_type' => '',
			)
		);

		if ( ! is_array( $args['exclude'] ) ) {
			$args['exclude'] = array();
		}

		// Extract arguments.
		extract( $args );

		// Generate cache key.
		$cache_key = 'revenue_campaigns_' . md5( serialize( $args ) );

		// Attempt to get cached results.
		$res = wp_cache_get( $cache_key, 'revenue_campaigns' );

		if ( false === $res ) {
			// Prepare SQL query
			// Execute the query
			//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( $args['campaign_type'] ) {
				$res = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}revenue_campaigns WHERE campaign_trigger_type= %s AND campaign_type=%s AND campaign_status='publish' AND id NOT IN (%s);",
						$trigger_type,
						$args['campaign_type'],
						implode( ',', $exclude )
					),
					ARRAY_A
				);
			} else {
				$res = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}revenue_campaigns WHERE campaign_trigger_type= %s AND campaign_status='publish' AND id NOT IN (%s);",
						$trigger_type,
						implode( ',', $exclude )
					),
					ARRAY_A
				);
			}

			// Cache the results
			wp_cache_set( $cache_key, $res, 'revenue_campaigns', 3600 ); // Cache for 1 hour
		}

		return $res;
	}
	public function get_countdown_timer_campaigns() {
		global $wpdb;

		// Define cache key and group
		// $cache_key   = 'revenue_countdown_campaigns';
		// $cache_group = 'revenue_countdown_campaigns';

		// Attempt to retrieve from cache
		// $res = wp_cache_get( 'revenue_countdown_campaigns', $cache_group );

		// if ( false === $res ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			$query = $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}revenue_campaigns WHERE campaign_type = %s AND campaign_status = %s",
				'countdown_timer',
				'publish'
			);

			$res = $wpdb->get_results( $query, ARRAY_A );

			// Cache for 1 hour (3600 seconds)
			// wp_cache_set( 'revenue_countdown_campaigns', $res, $cache_group, 3600 );
		// }

		return $res;
	}



	/**
	 * Retrieves available campaigns for a given product based on various criteria.
	 *
	 * This function checks both product-level and category-level campaign
	 * inclusions and exclusions, then returns a list of eligible campaigns.
	 *
	 * @param int    $product_id    The ID of the product for which to retrieve campaigns.
	 * @param string $placement      The placement context for the campaigns.
	 * @param string $display_type   The type of display (e.g., inpage).
	 * @param string $position       The position of the display within the placement.
	 * @param bool   $with_ids      Whether to return an array with campaign IDs as well.
	 *
	 * @return array An array of available campaigns. If $with_ids is true,
	 *               an array containing 'campaigns' and 'ids' is returned.
	 */
	public function get_available_campaigns( $product_id, $placement, $display_type = '', $position = '', $with_ids = false, $is_cart = false, $campaign_type = '' ) {
		if ( 'inpage' === $display_type ) {
			$include_meta_key = "revx_camp_{$placement}_{$display_type}_{$position}_in";
			$exclude_meta_key = "revx_camp_{$placement}_{$display_type}_{$position}_ex";
		} else {
			$include_meta_key = "revx_camp_{$placement}_{$display_type}_in";
			$exclude_meta_key = "revx_camp_{$placement}_{$display_type}_ex";
		}

		$included = get_post_meta( $product_id, $include_meta_key );

		$excluded = get_post_meta( $product_id, $exclude_meta_key );

		if ( ! is_array( $included ) ) {
			$included = array();
		}
		if ( ! is_array( $excluded ) ) {
			$excluded = array();
		}

		$categories = revenue()->get_product_category_ids( $product_id );

		foreach ( $categories as $cat_id ) {
			$exclude_cat = get_term_meta( $cat_id, $exclude_meta_key );
			$include_cat = get_term_meta( $cat_id, $include_meta_key );
			$excluded    = array_merge( $excluded, $exclude_cat );
			$included    = array_merge( $included, $include_cat );
		}

		$args = array(
			'page'    => $placement,
			'exclude' => $excluded, //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		);

		if ( $campaign_type ) {
			$args['campaign_type'] = $campaign_type;
		}

		$all_product_campaigns = $this->get_campaigns( $args );

		foreach ( $all_product_campaigns as $camp ) {
			$camp_id = false;
			if ( is_object( $camp ) ) {
				$camp_id = $camp->id;
			} elseif ( is_array( $camp ) && isset( $camp['id'] ) ) {
				$camp_id = $camp['id'];
			}
			$included[] = $camp_id;
		}

		$included = array_diff( $included, $excluded );

		if ( $placement == 'all_page' ) {
			$published_countdown_timer = $this->get_countdown_timer_campaigns();

			foreach ( $published_countdown_timer as $c_id ) {
				$placement_settings = $this->get_placement_settings( $c_id['id'], 'all_page' );
				if ( isset( $placement_settings['status'] ) && 'yes' === $placement_settings['status'] ) {
					$included[] = $c_id['id'];
				}
			}
		}

		$campaigns = array();

		foreach ( $included as $camp_id ) {
			if ( ! isset( $campaigns[ $camp_id ] ) ) {
				$campaigns[ $camp_id ] = $this->read_campaign_data( $camp_id );
			}

			if ( ! $campaigns[ $camp_id ] ) {
				unset( $campaigns[ $camp_id ] );
				continue;
			}

			if ( isset( $campaigns[ $camp_id ]['campaign_status'] ) && 'publish' !== $campaigns[ $camp_id ]['campaign_status'] ) {
				unset( $campaigns[ $camp_id ] );
			}
			if ( isset( $campaigns[ $camp_id ] ) && ! $this->is_campaign_eligible( $campaigns[ $camp_id ] ) ) {
				unset( $campaigns[ $camp_id ] );
			}

			if ( $campaign_type && isset( $campaigns[ $camp_id ]['campaign_type'] ) && $campaigns[ $camp_id ]['campaign_type'] !== $campaign_type ) {
				unset( $campaigns[ $camp_id ] );
			}

			$placement_settings = $this->get_placement_settings( $camp_id, $placement );

			if ( ! $is_cart ) {
				if ( isset( $placement_settings['status'], $placement_settings['display_style'] ) ) {
					$status    = $placement_settings['status'];
					$dis_style = $placement_settings['display_style'];

					if ( 'drawer' === $dis_style ) {
						$inpage_position = isset( $placement_settings['drawer_position'] ) ? $placement_settings['drawer_position'] : '';

					} elseif ( 'inpage' === $dis_style ) {

						$inpage_position = isset( $placement_settings['inpage_position'] ) ? $placement_settings['inpage_position'] : '';

					}

					if ( $status === 'yes' && $display_type === $dis_style ) {
						if ( $display_type === 'inpage' && $position !== $inpage_position ) {
							unset( $campaigns[ $camp_id ] );
						}
					} else {
						unset( $campaigns[ $camp_id ] );
					}
				} else {
					unset( $campaigns[ $camp_id ] );
				}
			}
		}

		if ( $with_ids ) {
			return array(
				'campaigns' => $campaigns,
				'ids'       => $included,
			);
		}

		return $campaigns;
	}

	/**
	 * Checks if a campaign is eligible based on the current date-time and campaign date-time range.
	 *
	 * @param array $campaign The campaign data array with optional 'campaign_start_date_time' and 'campaign_end_date_time'.
	 * @return bool True if the campaign is eligible, otherwise false.
	 */
	public function is_campaign_eligible( array $campaign ) {
		$current_date_time = new DateTime( current_time( 'mysql' ) );

		$start_date_time = isset( $campaign['campaign_start_date_time'] )
			? new DateTime( $campaign['campaign_start_date_time'] )
			: null;
		$end_date_time   = isset( $campaign['campaign_end_date_time'] )
			? new DateTime( $campaign['campaign_end_date_time'] )
			: null;

		// Both start and end date-times are null, campaign is always available.
		if ( is_null( $start_date_time ) && is_null( $end_date_time ) ) {
			return true;
		}

		// Only end date-time is provided
		if ( is_null( $start_date_time ) && isset( $campaign['schedule_end_time_enabled'] ) && 'yes' === $campaign['schedule_end_time_enabled'] ) {
			return $current_date_time <= $end_date_time;
		}

		// Only start date-time is provided
		if ( is_null( $end_date_time ) || ! isset( $campaign['schedule_end_time_enabled'] ) || 'no' === $campaign['schedule_end_time_enabled'] ) {
			return $current_date_time >= $start_date_time;
		}
		// Both start and end date-times are provided.
		return $current_date_time >= $start_date_time && $current_date_time <= $end_date_time;
	}
	/**
	 * Read campaign data.
	 *
	 * @param int    $id Campaign id.
	 * @param mixed  $campaign Campaign.
	 * @param string $context Context.
	 * @since 1.0.0
	 */
	protected function read_campaign_data( $id, $campaign = false, $context = '' ) {
		if ( ! $campaign ) {
			$campaign = (object) $this->get_raw_campaign( $id );
		}

		if ( ! is_object( $campaign ) ) {
			return false;
		}

		$set_props = array();

		foreach ( array_keys( get_object_vars( $campaign ) ) as $field ) {
			$set_props[ $field ] = $campaign->$field;
		}

		foreach ( $this->get_campaign_keys( 'meta' ) as $meta_key ) {
			$set_props[ $meta_key ] = $this->get_campaign_meta( $id, $meta_key, true );
		}

		$set_props['campaign_trigger_items']         = $this->get_raw_campaign_triggers( $id, $context );
		$set_props['campaign_trigger_exclude_items'] = $this->get_raw_campaign_triggers_exclude_items( $id, $context );

		return $set_props;
	}

	/**
	 * Clear campaign runtime cache
	 *
	 * @param string $id Campaign ID.
	 * @return void
	 */
	public function clear_campaign_runtime_cache( $id ) {
		if ( isset( $GLOBALS['revenue_campaign_data'][ $id ] ) ) {
			unset( $GLOBALS['revenue_campaign_data'][ $id ] );
		}

		wp_cache_delete( $id, 'revenue_campaign_triggers' );
		wp_cache_delete( $id, 'revenue_campaign_triggers_exclude_items' );
		wp_cache_delete( $id, 'revenue_campaign_meta' );
		wp_cache_delete( $id, 'revenue_campaigns' );
		wp_cache_delete( $id, 'revenue_campaign_data' );
	}



	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @since 1.0.0
	 * @param string|integer $value Value of the prop.
	 */
	public function get_revenue_date( $value ) {
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
	 * Get Campaign Keys
	 *
	 * @param string $type Type.
	 * @return array
	 */
	public function get_campaign_keys( $type = '' ) {
		$keys = array();
		switch ( $type ) {
			case 'campaign_table':
				$keys = array(
					'campaign_name',
					'campaign_author',
					'date_created',
					'date_created_gmt',
					'date_modified',
					'campaign_status',
					'campaign_placement',
					'campaign_behavior',
					'campaign_recommendation',
					'campaign_start_date_time',
					'campaign_end_date_time',
					'campaign_trigger_type',
					'campaign_trigger_relation',
				);
				break;
			case 'meta':
				$keys = array(
					'campaign_popup_animation',
					// 'is_campaign_popup_animation_trigger_immediate',
					'campaign_popup_animation_delay',
					'campaign_floating_position',
					// 'is_campaign_floating_animation_trigger_immediate',
					'campaign_floating_animation_delay',
					'offers',
					'bundle_with_trigger_products_enabled',
					'allow_more_than_required_quantity',
					'mix_match_is_required_products',
					'mix_match_initial_product_selection',
					'reward_type',
					'spending_goal',
					'spending_goal_calculate_based_on',
					'spending_goal_discount_type',
					'spending_goal_discount_value',
					'banner_heading',
					'banner_subheading',
					'stock_scarcity_enabled',
					'stock_scarcity_actions',
					'countdown_timer_enabled',
					'countdown_start_time_status',
					'countdown_start_date',
					'countdown_start_time',
					'countdown_end_date',
					'countdown_end_time',
					'animated_add_to_cart_enabled',
					'add_to_cart_animation_trigger_type',
					'add_to_cart_animation_type',
					'add_to_cart_animation_start_delay',
					'free_shipping_enabled',
					'schedule_end_time_enabled',
					'skip_add_to_cart',
					'quantity_selector_enabled',
					'offered_product_on_cart_action',
					'offered_product_click_action',
					'spending_goal_free_shipping_progress_messages',
					'spending_goal_discount_progress_messages',
					'spending_goal_is_upsell_enable',
					'spending_goal_upsell_product_selection_strategy',
					'spending_goal_upsell_discount_configuration',
					'spending_goal_on_cta_click',
					'spending_goal_discount_type',
					'spending_goal_discount_value',
					'builder',
					'buildeMobileData',
					'campaign_builder_view',
					'builderdata',
					'product_tag_text',
					'save_discount_ext',
					'bundle_label_badge',
					'add_to_cart_btn_text',
					'checkout_btn_text',
					'no_thanks_button_text',
					'total_price_text',
					'mix_match_required_products',
					'mix_match_is_required_products',
					'mix_match_initial_product_selection',
					'campaign_view_id',
					'campaign_view_class',
					'campaign_trigger_exclude_items',
					'campaign_trigger_items',
					'buy_x_get_y_trigger_qty_status',
					'fbt_is_trigger_product_required',
					'placement_settings',
					'countdown_timer_prefix',
					'double_order_animation_type',
					'double_order_animation_delay_between',
					'double_order_animation_enabled',
					'double_order_success_message',
					'double_order_countdown_duration',
					'free_shipping_label',
					'upsell_products',
					'upsell_products_status',
					'is_show_free_shipping_bar',
					'show_close_icon',
					'enable_cta_button',
					'cta_button_text',
					'spending_goal_upsell_products',
					'spending_goal_upsell_product_status',
					'spending_goal_progress_show_icon',
					'show_confetti',
					'all_goals_complete_message',
					'countdown_timer_type',
					'countdown_timer_static_settings',
					'countdown_timer_evergreen_settings',
					'countdown_timer_daily_recurring_settings',
					'countdown_timer_shop_progress_bar',
					'countdown_timer_entire_site_action_type',
					'countdown_timer_entire_site_action_link',
					'countdown_timer_entire_site_action_enable',
					'countdown_timer_enable_close_button',
					'stock_scarcity_general_message_settings',
					'stock_scarcity_flip_message_settings',
					'stock_scarcity_message_type',
					'stock_scarcity_enable_fake_stock',
					'stock_scarcity_animation_settings',
					'animation_settings_enable',
					'animation_type',
					'animation_duration',
					'delay_between_loop',
					'builderdata',					'revx_next_order_coupon',

				);
				// code...
				break;
			case 'triggers':
				$keys = array(
					'id',
					'trigger_id',
					'item_id',
					'item_name',
				);
				break;
			case 'trigger_items':
				$keys = array(
					'id',
					'trigger_id',
					'item_id',
					'item_name',
				);

				break;
			case 'analytics':
				$keys = array(
					// 'total_order',
					'daywise_order_stats',
					// 'total_revenue',
					'daywise_revenue_stats',
					// 'total_impression',
					'daywise_impression_stats',
					// 'total_atc',
					'daywise_atc_stats',
					// 'total_checkout',
					'daywise_checkout_stats',
					// 'total_rejection',
					'daywise_rejection_stats',
					'animated_add_to_cart_enabled',
					'free_shipping_enabled',
					'countdown_timer_enabled',
					'stock_scarcity_enabled',
				);
				break;

			default:
				// code...
				break;
		}

		return $keys;
	}


	/**
	 * Trashes or deletes a campaign or page.
	 *
	 * When the campaign and page is permanently deleted, everything that is tied to
	 * it is deleted also. This includes comments, campaign meta fields, and terms
	 * associated with the campaign.
	 *
	 * The campaign or page is moved to Trash instead of permanently deleted unless
	 * Trash is disabled, item is already in the Trash, or $force_delete is true.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $campaign_id   campaign ID. Default 0.
	 * @return WP_campaign|false|null campaign data on success, false or null on failure.
	 */
	public function delete_campaign( $campaign_id ) {
		global $wpdb;

		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaign = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}revenue_campaigns WHERE ID = %d",
				$campaign_id
			)
		);
		if ( ! $campaign ) {
			return $campaign;
		}

		$campaign = $this->get_campaign_data( $campaign_id );

		/**
		 * Fires before a campaign is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int     $campaign_id campaign ID.
		 * @param object  $campaign    campaign object.
		 */
		do_action( 'revenue_before_delete_campaign', $campaign_id, $campaign );

		// Delete campaign meta.
		$campaign_meta_ids  = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE campaign_id = %d ", $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_delete_result = false;
		foreach ( $campaign_meta_ids as $mid ) {
			$meta_delete_result = (bool) $wpdb->delete( $wpdb->prefix . 'revenue_campaign_meta', array( 'meta_id' => $mid ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		if ( $meta_delete_result ) {
			wp_cache_delete( $campaign_id, 'revenue_campaign_meta' );
		}

		// Delete campaign triggers.
		$campaign_trigger_ids  = $wpdb->get_col( $wpdb->prepare( "SELECT trigger_id FROM {$wpdb->prefix}revenue_campaign_triggers WHERE campaign_id = %d ", $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$trigger_delete_result = false;
		foreach ( $campaign_trigger_ids as $tid ) {
			$trigger_delete_result = $this->delete_campaign_trigger( $campaign_id, $tid );
		}
		if ( $trigger_delete_result ) {
			wp_cache_delete( $campaign_id, 'revenue_campaign_triggers' );
		}

		// Delete campaign analytics
		$analytics_data_delete = $wpdb->delete( $wpdb->prefix . 'revenue_campaign_analytics', array( 'campaign_id' => $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$result = $wpdb->delete( $wpdb->prefix . 'revenue_campaigns', array( 'id' => $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $result ) {
			return false;
		}

		wp_cache_delete( $campaign_id, 'revenue_campaigns' );

		do_action( 'revenue_after_delete_campaign', $campaign_id, $campaign );

		return $campaign;
	}

	/**
	 * Delete campaign trigger
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign id.
	 * @param int $trigger_id Trigger id.
	 * @return int|false
	 */
	public function delete_campaign_trigger( $campaign_id, $trigger_id ) {
		global $wpdb;

		if ( ! $campaign_id || ! is_numeric( $campaign_id ) ) {
			return 0;
		}

		if ( ! $trigger_id || ! is_numeric( $trigger_id ) ) {
			return 0;
		}

		$result = (bool) $wpdb->delete( $wpdb->prefix . 'revenue_campaign_triggers', array( 'trigger_id' => $trigger_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result;
	}

	/**
	 * Deletes a campaign meta field for the given campaign ID.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching the key, if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id    campaign ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. If provided,
	 *                           rows will only be removed that match the value.
	 *                           Must be serializable if non-scalar. Default empty.
	 * @param bool   $delete_all Is delete all.
	 * @return bool True on success, false on failure.
	 */
	public function delete_campaign_meta( $campaign_id, $meta_key, $meta_value = '', $delete_all = false ) {
		global $wpdb;
		if ( ! $meta_key || ! is_numeric( $campaign_id ) && ! $delete_all ) {
			return false;
		}
		$meta_key   = wp_unslash( $meta_key );
		$meta_value = wp_unslash( $meta_value );
		$meta_value = maybe_serialize( $meta_value );
		$params     = array( $meta_key );

		$query = "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s";

		if ( ! $delete_all ) {
			$query   .= ' AND campaign_id = %d';
			$params[] = $campaign_id;
		}

		if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
			$query   .= ' AND meta_value = %s';
			$params[] = $meta_value;
		}

		$meta_ids = $wpdb->get_col( $wpdb->prepare( $query, ...$params ) ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! count( $meta_ids ) ) {
			return false;
		}

		if ( $delete_all ) {
			if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
				$campaign_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
				$campaign_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s", $meta_key ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}

		$meta_ids = array_map( 'absint', $meta_ids );

		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_id IN(" . implode( ',', array_fill( 0, count( $meta_ids ), '%d' ) ) . ')',
				...$meta_ids
			)
		); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $count ) {
			return false;
		}

		if ( $delete_all ) {
			$data = (array) $campaign_ids;
		} else {
			$data = array( $campaign_id );
		}
		wp_cache_delete_multiple( $data, 'revenue_campaign_meta' );

		return true;
	}


	/**
	 * Retrieves a campaign meta field for the given campaign ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id campaign ID.
	 * @param string $meta_key     Optional. The meta key to retrieve. By default,
	 *                        returns data for all keys. Default empty.
	 * @param bool   $single  Optional. Whether to return a single value.
	 *                        This parameter has no effect if `$key` is not specified.
	 *                        Default false.
	 * @return mixed An array of values if `$single` is false.
	 *               The value of the meta field if `$single` is true.
	 *               False for an invalid `$campaign_id` (non-numeric, zero, or negative value).
	 *               An empty string if a valid but non-existing campaign ID is passed.
	 */
	public function get_campaign_meta( $campaign_id, $meta_key = '', $single = false ) {
		if ( ! $campaign_id ) {
			return null;
		}
		$meta_cache = wp_cache_get( $campaign_id, 'revenue_campaign_meta' );

		if ( ! $meta_cache ) {
			$meta_cache = $this->update_campaign_meta_cache( array( $campaign_id ) );
			if ( isset( $meta_cache[ $campaign_id ] ) ) {
				$meta_cache = $meta_cache[ $campaign_id ];
			} else {
				$meta_cache = null;
			}
		}

		if ( ! $meta_key ) {
			return $meta_cache;
		}

		if ( isset( $meta_cache[ $meta_key ] ) ) {
			if ( $single ) {
				return maybe_unserialize( $meta_cache[ $meta_key ][0] );
			} else {
				return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
			}
		}

		return null;
	}


	/**
	 * Updates the campaign meta cache for the specified campaign ID.
	 *
	 * This function retrieves metadata for campaigns that are not currently
	 * cached and updates the cache accordingly. It fetches campaign metadata
	 * from the database and organizes it into an associative array.
	 *
	 * @param int $campaign_id The ID of the campaign whose metadata needs to be updated.
	 *
	 * @return array An associative array of cached campaign metadata, where the keys
	 *               are campaign IDs and the values are arrays of meta data indexed
	 *               by meta keys.
	 */
	public function update_campaign_meta_cache( $campaign_id ) {
		global $wpdb;

		$cache_key      = 'revenue_campaign_meta';
		$non_cached_ids = array();
		$cache          = array();

		$cache_values = wp_cache_get_multiple( $campaign_id, $cache_key );

		foreach ( $cache_values as $id => $cached_object ) {
			if ( false === $cached_object ) {
				$non_cached_ids[] = $id;
			} else {
				$cache[ $id ] = $cached_object;
			}
		}

		if ( empty( $non_cached_ids ) ) {
			return $cache;
		}

		$id_list = implode( ',', $non_cached_ids );

		$meta_list = $wpdb->get_results( "SELECT campaign_id, meta_key, meta_value FROM {$wpdb->prefix}revenue_campaign_meta WHERE campaign_id IN ($id_list) ORDER BY meta_id ASC", ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $meta_list ) ) {
			foreach ( $meta_list as $metarow ) {
				$mpid = (int) $metarow['campaign_id'];
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];

				// Force subkeys to be array type.
				if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
					$cache[ $mpid ] = array();
				}
				if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
					$cache[ $mpid ][ $mkey ] = array();
				}

				// Add a value to the current pid/key.
				$cache[ $mpid ][ $mkey ][] = $mval;
			}
		}

		$data = array();
		foreach ( $non_cached_ids as $id ) {
			if ( ! isset( $cache[ $id ] ) ) {
				$cache[ $id ] = array();
			}
			$data[ $id ] = $cache[ $id ];
		}

		wp_cache_add_multiple( $data, $cache_key );

		return $cache;
	}


	/**
	 * Updates a campaign meta field based on the given campaign ID.
	 *
	 * Use the `$prev_value` parameter to differentiate between meta fields with the
	 * same key and campaign ID.
	 *
	 * If the meta field for the campaign does not exist, it will be added and its ID returned.
	 *
	 * Can be used in place of add_campaign_meta().
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id    campaign ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before updating.
	 *                           If specified, only update existing metadata entries with
	 *                           this value. Otherwise, update all entries. Default empty.
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure or if the value passed to the function
	 *                  is the same as the one that is already in the database.
	 */
	public function update_campaign_meta( $campaign_id, $meta_key, $meta_value, $prev_value = '' ) {
		global $wpdb;

		$meta_key   = wp_unslash( $meta_key );
		$meta_value = wc_clean( wp_unslash( $meta_value ) );

		if ( 'stock_scarcity_actions' === $meta_key ) {

			$charset = $wpdb->get_col_charset( $wpdb->prefix . 'revenue_campaign_meta', 'meta_value' );

			if ( is_array( $meta_value ) ) {
				foreach ( $meta_value as $key => $mv ) {
					if ( 'utf8' === $charset ) {
						$meta_value[ $key ]['stock_message'] = wp_encode_emoji( $mv );
					}
				}
			}
		}

		if ( 'offers' === $meta_key ) {
			$campaign = $this->get_campaign_data( $campaign_id );

			$campaign_type = $campaign['campaign_type'];
			switch ( $campaign_type ) {
				case 'normal_discount':
					break;
				case 'bundle_discount':
				case 'buy_x_get_y':
					$valid_meta_value = array();

					foreach ( $meta_value as $data ) {
						if ( isset( $data['products'], $data['quantity'], $data['type'] ) && ! empty( $data['products'] ) && ! empty( $data['quantity'] ) && ! empty( $data['type'] ) ) {
							if ( 'free' == $data['type'] || 'no_discount' === $data['type'] ) {
								$data['value'] = '';
							}
							$valid_meta_value[] = $data;
						}
					}

					$meta_value = $valid_meta_value;

					break;
				case 'volume_discount':
					break;

				case 'mix_match':
					break;
				case 'frequently_bought_together':
					break;
				case 'spending_goal':
					// code...
					break;

				default:
					// code...
					break;
			}
		}

		// Compare existing value to new value if no prev value given and the key exists only once.
		if ( empty( $prev_value ) ) {
			$old_value = $this->get_campaign_meta( $campaign_id, $meta_key, false );
			if ( is_countable( $old_value ) && count( $old_value ) === 1 ) {
				if ( $old_value[0] === $meta_value ) {
					return false;
				}
			}
		}

		$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s AND campaign_id = %d", $meta_key, $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $meta_ids ) ) {
			return $this->add_campaign_meta( $campaign_id, $meta_key, $meta_value );
		}

		$meta_value = maybe_serialize( $meta_value );

		$data  = compact( 'meta_value' );
		$where = array(
			'campaign_id' => $campaign_id,
			'meta_key'    => $meta_key, //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		);

		if ( ! empty( $prev_value ) ) {
			$prev_value          = maybe_serialize( $prev_value );
			$where['meta_value'] = $prev_value; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$result = $wpdb->update( $wpdb->prefix . 'revenue_campaign_meta', $data, $where ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $result ) {
			return false;
		}

		wp_cache_delete( $campaign_id, 'revenue_campaign_meta' );

		return update_metadata( 'revenue_campaign', $campaign_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Adds metadata for the specified object.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int    $campaign_id   ID of the object metadata is for.
	 * @param string $meta_key      Metadata key.
	 * @param mixed  $meta_value    Metadata value. Must be serializable if non-scalar.
	 * @return int|false The meta ID on success, false on failure.
	 */
	public function add_campaign_meta( $campaign_id, $meta_key, $meta_value ) {
		global $wpdb;
		$meta_key = sanitize_key( $meta_key );

		$meta_value = wc_clean( wp_unslash( $meta_value ) );
		$meta_value = maybe_serialize( $meta_value );

		$result = $wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'revenue_campaign_meta',
			array(
				'campaign_id' => $campaign_id,
				'meta_key'    => $meta_key, //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $meta_value, //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( ! $result ) {
			return false;
		}

		$mid = (int) $wpdb->insert_id;

		wp_cache_delete( $campaign_id, 'revenue_campaign_meta' );

		return $mid;
	}


	/**
	 * Get raw campaign
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign id.
	 * @return object
	 */
	public function get_raw_campaign( $campaign_id ) {
		global $wpdb;

		$campaign_id = (int) $campaign_id;
		if ( ! $campaign_id ) {
			return false;
		}

		$campaign = wp_cache_get( $campaign_id, 'revenue_campaigns' );

		if ( ! $campaign ) {
			$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}revenue_campaigns WHERE ID = %d LIMIT 1", $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( ! $campaign ) {
				return false;
			}

			$campaign = $this->sanitize_campaign( $campaign );

			wp_cache_add( $campaign->id, $campaign, 'revenue_campaigns' );
		}

		return $campaign;
	}

	/**
	 * Get raw campaign triggers
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id Campaign id.
	 * @param string $context Optional context.
	 * @param bool   $exclude Optional flag to exclude items.
	 * @return array|false
	 */
	private function get_raw_campaign_triggers( $campaign_id, $context = '', $exclude = false ) {
		global $wpdb;

		$campaign_id = (int) $campaign_id;

		if ( ! $campaign_id ) {
			return false;
		}

		// Use cache.
		$cache_key = $exclude ? 'revenue_campaign_triggers_exclude_items' : 'revenue_campaign_trigger_items';
		$triggers  = wp_cache_get( $campaign_id, $cache_key );

		if ( ! $triggers ) {
			$action_condition = $exclude ? "AND trigger_action = 'exclude'" : '';
			$triggers         = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}revenue_campaign_triggers WHERE campaign_id = %d $action_condition;", //phpcs:ignore  WordPress.DB.PreparedSQL.InterpolatedNotPrepared 
					$campaign_id
				)
			);

			if ( empty( $triggers ) ) {
				return false;
			}

			$data = array();

			foreach ( $triggers as $trigger ) {
				$trigger = (array) $trigger;

				$data[ $trigger['trigger_id'] ]                   = $trigger;
				$data[ $trigger['trigger_id'] ]['trigger_action'] = $trigger['trigger_action'];
				$data[ $trigger['trigger_id'] ]['trigger_type']   = $trigger['trigger_type'];
				$data[ $trigger['trigger_id'] ]['item_quantity']  = $trigger['item_quantity'];
				$data[ $trigger['trigger_id'] ]['item_id']        = $trigger['item_id'];
				if ( 'category' == $trigger['trigger_type'] ) {
					$term                                  = get_term( $trigger['item_id'], 'product_cat' );
					$data[ $trigger['trigger_id'] ]['url'] = get_term_link( $term );
				} else {
					$data[ $trigger['trigger_id'] ]['url'] = get_permalink( $trigger['item_id'] );
				}
			}

			$triggers = $data;

			// Add to cache.
			wp_cache_set( $campaign_id, $data, $cache_key );
		}

		return $triggers;
	}


	/**
	 * Get raw campaign triggers exclude items
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign id.
	 * @return array|false
	 */
	public function get_raw_campaign_triggers_exclude_items( $campaign_id, $context = '' ) {
		return $this->get_raw_campaign_triggers( $campaign_id, $context, true );
	}

	/**
	 * Get raw campaign trigger items
	 *
	 * @since 1.0.0
	 *
	 * @param int $trigger_id  Trigger id.
	 * @return object
	 */
	public function get_raw_campaign_trigger_items( $trigger_id ) {
		global $wpdb;

		$trigger_items = wp_cache_get( $trigger_id, 'revenue_campaign_trigger_items' );

		if ( ! $trigger_items ) {

			$trigger_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}revenue_campaign_trigger_items WHERE trigger_id=%d", $trigger_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( ! $trigger_items ) {
				return false;
			}

			$data = array();

			foreach ( $trigger_items as $item ) {
				$item                     = (array) $item;
				$data[ $item['item_id'] ] = $item;
			}

			$trigger_items = $data;

			wp_cache_add( $trigger_id, $trigger_items, 'revenue_campaign_trigger_items' );
		}

		return $trigger_items;
	}

	/**
	 * Sanitize campaign
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $campaign Campaign.
	 * @return mixed
	 */
	public function sanitize_campaign( $campaign ) {
		if ( is_object( $campaign ) ) {

			if ( ! isset( $campaign->id ) ) {
				$campaign->id = 0;
			}
			foreach ( array_keys( get_object_vars( $campaign ) ) as $field ) {
				$campaign->$field = $this->sanitize_campaign_field( $field, $campaign->$field, $campaign->id );
			}
		} elseif ( is_array( $campaign ) ) {

			if ( ! isset( $campaign['id'] ) ) {
				$campaign['id'] = 0;
			}
			foreach ( array_keys( $campaign ) as $field ) {
				$campaign[ $field ] = $this->sanitize_campaign_field( $field, $campaign[ $field ], $campaign['id'] );
			}
		}

		return $campaign;
	}


	/**
	 * Sanitize campaign field
	 *
	 * @since 1.0.0
	 *
	 * @param string $field Field name.
	 * @param string $value Field value.
	 * @param string $context Context.
	 * @return string
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
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_apply_on':
				$value = sanitize_text_field( $value );
				break;

			default:
				break;
		}

		return $value;
	}


	/**
	 * Calculates the offered price based on the specified offer type and value.
	 *
	 * This function computes the price offered to the customer based on different
	 * types of discounts or offers (percentage, fixed discount, fixed price, etc.).
	 * It can also return a message indicating the savings or offer details.
	 *
	 * @param string $offer_type        The type of offer (e.g., 'percentage', 'fixed_discount', 'fixed_price', 'no_discount', 'free').
	 * @param float  $offer_value       The value associated with the offer, which can be a percentage or fixed amount.
	 * @param float  $regular_price     The regular price of the product before any discounts.
	 * @param bool   $with_save_data    Whether to return additional save data (message and offer details).
	 * @param int    $offer_qty         The quantity associated with the offer (default is 1).
	 *
	 * @return array|float The calculated offered price. If $with_save_data is true,
	 *                     an array containing offer details (type, value, message, price) is returned.
	 *                     Otherwise, the final offered price is returned as a float.
	 */
	public function calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, $with_save_data = false, $offer_qty = 1, $campaign_type = '' ) {
		$offered_price        = 0.0;
		$regular_price        = floatval( $regular_price );
		$offer_value          = floatval( $offer_value );
		$offer_qty            = floatval( $offer_qty );
		$save_data            = array();
		$save_data['message'] = '';

		if ( ! $offer_type ) {
			return $regular_price;
		}

		switch ( $offer_type ) {
			case 'percentage':
				$offered_price      = $regular_price - ( $regular_price * ( ( $offer_value * 1.0 ) / 100 ) );
				$save_data['type']  = 'percentage';
				$save_data['value'] = $offer_value;
				if ( $offer_value ) {
					/* translators: %s: Discount percentage value */
					$save_data['message'] = sprintf( __( 'Save %s%%', 'revenue' ), $offer_value );
				}
				break;

			case 'fixed_discount':
			case 'amount':
				$offered_price     = max( floatval( 0 ), ( $regular_price - $offer_value ) );
				$save_data['type'] = 'amount';
				/* translators: %s: Discount percentage value */
				$save_data['message'] = sprintf( __( 'Save %s', 'revenue' ), wc_price( $offer_qty * $offer_value ) );
				break;
			case 'fixed_price':
				$offered_price      = $offer_value;
				$save_data['type']  = 'amount';
				$save_data['value'] = $offer_value;
				/* translators: %s: Discount percentage value */

				$total_save = $regular_price - $offer_value;

				if($campaign_type==='volume_discount') {
					$total_save = $total_save * $offer_qty;
				}

				$save_data['message'] = sprintf( __( 'Save %s', 'revenue' ), wc_price( $total_save ) );
				break;
			case 'fixed_total_price':
				$offered_price = $offer_value;
				$temp_price    = $offer_value * $offer_qty;

				if ( $campaign_type === 'volume_discount' ) {
					$regular_price = $regular_price * $offer_qty;
					$offer_value   = $offer_value;
				}

				$save_data['type']  = 'fixed_total_price';
				$save_data['value'] = $offer_value;
				/* translators: %s: Discount percentage value */
				$save_data['message'] = sprintf( __( 'Save %s', 'revenue' ), wc_price( $regular_price - $offer_value ) );

				break;

			case 'no_discount':
				$offered_price        = $regular_price - $offer_value;
				$save_data['type']    = 'amount';
				$save_data['value']   = 0;
				$save_data['message'] = '';
				break;

			case 'free':
				$offered_price        = 0.0;
				$save_data['type']    = 'percentage';
				$save_data['value']   = '100';
				$save_data['message'] = __( 'Get Free', 'revenue' );
				break;

			default:
				// code...
				break;
		}

		if ( $with_save_data ) {
			$save_data['price'] = max( 0.0, $offered_price );

			return $save_data;
		}

		return max( 0.0, $offered_price );
	}


	/**
	 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
	 *
	 * @since  1.0.0
	 * @param  mixed  $var     Variable.
	 * @param  string $default Default value.
	 * @return mixed
	 */
	public function get_var( &$var, $default = null ) {
		return isset( $var ) ? $var : $default;
	}

	/**
	 * Increment Campaign Add to cart count
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $product_id Product id.
	 * @return void
	 */
	public function increment_campaign_add_to_cart_count( $campaign_id, $product_id = false ) {
		$user_id = get_current_user_id();

		$campaign = $this->get_campaign_data( $campaign_id );

		if ( ! $campaign ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'add_to_cart_count' );
	}
	/**
	 * Increment Campaign checkout page count
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $product_id Product id.
	 * @return void
	 */
	public function increment_campaign_checkout_count( $campaign_id, $product_id = false ) {
		$user_id = get_current_user_id();

		$campaign = $this->get_campaign_data( $campaign_id );

		if ( ! $campaign ) {
			return;
		}
		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'add_to_cart_count' );
	}
	/**
	 * Increment Campaign order page count
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $product_id Product id.
	 * @param int $order Order id.
	 * @return void
	 */
	public function increment_campaign_order_count( $campaign_id, $product_id = false, $order_id = false ) {
		$user_id = get_current_user_id();

		$campaign = $this->get_campaign_data( $campaign_id );
		if ( ! $campaign ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'order_count' );
	}
	/**
	 * Increment Campaign  count
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $order Order id.
	 * @return void
	 */
	public function track_campaign_order_ids( $campaign_id, $order_id ) {
		if ( ! $campaign_id ) {
			return;
		}
		$user_id = get_current_user_id();

		$campaign = $this->get_campaign_data( $campaign_id );

		$added_to_cart = $campaign['campaign_order_ids'];
		++$added_to_cart;
		$this->update_campaign( $campaign_id, 'campaign_order_ids', $added_to_cart );
		$campaign['campaign_order_ids'] = $added_to_cart;
		wp_cache_set( $campaign_id, $campaign, 'revenue_campaigns' );

		$daywise_stats = $this->get_campaign_meta( $campaign_id, 'daywise_order_id_stats', true );
		if ( ! is_array( $daywise_stats ) ) {
			$daywise_stats = array();
		}

		$date                   = gmdate( 'Y-m-d' );
		$count                  = ( isset( $daywise_stats[ $date ] ) ? $daywise_stats[ $date ] : array() );
		$count[]                = $order_id;
		$daywise_stats[ $date ] = $count;
		$this->update_campaign_meta( $campaign_id, 'daywise_order_id_stats', $daywise_stats );
	}
	/**
	 * Increment Campaign popup rejection count
	 *
	 * @param int $campaign_id Campaign.
	 * @return void
	 */
	public function increment_campaign_rejection_count( $campaign_id ) {
		$user_id  = get_current_user_id();
		$campaign = $this->get_campaign_data( $campaign_id );

		if ( ! $campaign ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'rejection_count' );

		WC()->session->set( 'revx_should_check_order_for_campaign', true );
	}


	/**
	 * Get a specific setting by key or return all settings merged with defaults if no key is provided.
	 *
	 * @param string $key Optional. The key of the setting to retrieve. If not provided,
	 *                    all settings merged with default settings are returned.
	 * @return mixed The value of the specific setting if a key is provided, or all settings
	 *               merged with default settings if no key is provided.
	 */
	public function get_setting( $key = '' ) {
		// Get the saved settings from the database with a default empty array.
		$settings = get_option( 'revenue_settings', array() );

		// If a specific key is provided, return its value or the default setting if not found.
		if ( $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : $this->get_default_settings( $key );
		}

		// If no key is provided, return all settings merged with default settings.
		return array_merge( $this->get_default_settings(), $settings );
	}

	/**
	 * Get the default settings or a specific default setting value.
	 *
	 * This function retrieves default settings, either as a whole or for a specific key.
	 * It applies the 'revenue_get_default_settings' filter, allowing other developers
	 * to override or extend the default settings.
	 *
	 * @param string $key Optional. The key of the specific default setting to retrieve.
	 *                    If empty, all default settings are returned.
	 * @return mixed The value of the specific default setting if a key is provided,
	 *               all default settings if no key is provided, or false if the key is not found.
	 */
	public function get_default_settings( $key = '' ) {
		// Define default settings and allow filtering.

		$defaults = apply_filters(
			'revenue_get_default_settings',
			array(
				'campaign_list_columns_visible' => array( 'check_column', 'campaign_name', 'id', 'campaign_status', 'triggers', 'total_impression', 'total_add_to_cart', 'conversion_rate', 'total_sales', 'campaign_progress', 'actions' ),
			),
			$key
		);

		// If a key is provided, return its value from the defaults. If not found, return false.
		return $key ? ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : false ) : $defaults;
	}


	/**
	 * Set a specific setting by key and update the database.
	 *
	 * @param string $key The key of the setting to update.
	 * @param mixed  $val The value to set for the specified key.
	 * @return bool True if the option value has changed, false if not or if update failed.
	 */
	public function set_setting( $key, $val ) {
		// Get the saved settings from the database or initialize as an empty array.
		$settings = get_option( 'revenue_settings', array() );

		// Ensure $settings is an array.
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Check if the new value is different from the existing value.
		if ( isset( $settings[ $key ] ) && $settings[ $key ] === $val ) {
			// No need to update if the value hasn't changed.
			return true;
		}

		// Update the specific key with the new value.
		$settings[ $key ] = $val;

		// Save the updated settings array back to the database.
		return update_option( 'revenue_settings', $settings );
	}



	/**
	 * True if a cart item appears to be a bundle container item.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $cart_item Cart item.
	 * @return boolean
	 */
	public function is_bundle_container_cart_item( $cart_item ) {
		$is_bundle = false;

		if ( isset( $cart_item['revx_bundled_items'] ) ) {
			$is_bundle = true;
		}

		return $is_bundle;
	}


	/**
	 * Check if given cart item is bundle trigger/parent product or not
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_item Cart Item.
	 * @return boolean
	 */
	public function is_bundle_trigger_product( $cart_item ) {
		return isset( $cart_item['revx_bundle_type'] ) && 'trigger' === $cart_item['revx_bundle_type'] && get_option( 'revenue_bundle_parent_product_id', false ) == $cart_item['product_id'];
	}

	/**
	 * Given a bundle container cart item, find and return its child cart items - or their cart ids when the $return_ids arg is true.
	 *
	 * @since  1.0.0
	 *
	 * @param  array   $container_cart_item Container Cart item.
	 * @param  array   $cart_contents Cart contents.
	 * @param  boolean $return_ids Return ids.
	 * @return mixed
	 */
	public function get_bundled_cart_items( $container_cart_item, $cart_contents = false, $return_ids = false ) {
		if ( ! $cart_contents ) {
			$cart_contents = isset( WC()->cart ) ? WC()->cart->cart_contents : array();
		}

		$bundled_cart_items = array();

		if ( $this->is_bundle_container_cart_item( $container_cart_item ) ) {

			$bundled_items = $container_cart_item['revx_bundled_items'];

			if ( ! empty( $bundled_items ) && is_array( $bundled_items ) ) {
				foreach ( $bundled_items as $bundled_cart_item_key ) {
					if ( isset( $cart_contents[ $bundled_cart_item_key ] ) ) {
						$bundled_cart_items[ $bundled_cart_item_key ] = $cart_contents[ $bundled_cart_item_key ];
					}
				}
			}
		}

		return $return_ids ? array_keys( $bundled_cart_items ) : $bundled_cart_items;
	}



	/**
	 * Get bundle container cart item price
	 *
	 * @param string|float $price Price.
	 * @param array        $cart_item Cart item.
	 * @param boolean      $is_subtotal Is subtotal.
	 * @return string|float
	 */
	public function get_bundle_container_cart_item_price( $price, $cart_item, $is_subtotal = false ) {
		if ( ! isset( WC()->cart ) ) {
			return 0.0;
		}
		if ( empty( $price ) ) {
			$price = 0.0;
		}
		$price = floatval( $price );
		$cart  = WC()->cart->get_cart();
		if ( $this->is_bundle_container_cart_item( $cart_item ) ) {
			$bundle_items = revenue()->get_bundled_cart_items( $cart_item, false, true );

			foreach ( $bundle_items as $bundle_item_key ) {
				$bundle_cart_item = ( isset( $cart[ $bundle_item_key ] ) ) ? $cart[ $bundle_item_key ] : false;

				if ( $is_subtotal ) {
					$price += $bundle_cart_item ? $bundle_cart_item['quantity'] * $bundle_cart_item['data']->get_price() : 0;
				} else {
					$price += $bundle_cart_item ? $bundle_cart_item['data']->get_price() : 0;
				}
			}
		}

		return $price;
	}


	/**
	 * True if an order item appears to be a bundle container item.
	 *
	 * @since  1.0.0
	 *
	 * @param  WC_Order_Item $order_item Order item.
	 * @return boolean
	 */
	public function is_bundle_container_order_item( $order_item ) {
		$is_bundle = false;

		if ( isset( $order_item['revx_bundled_items'] ) ) {
			$is_bundle = true;
		}

		return $is_bundle;
	}
	/**
	 * Given a bundle container order item, find and return its child order items - or their order item ids when the $return_ids arg is true.
	 *
	 * @since  1.0.0
	 *
	 * @param  WC_Order_Item $container_order_item Container Order Item.
	 * @param  WC_Order      $order Order.
	 * @param  boolean       $return_ids Return ids.
	 * @return mixed
	 */
	public function get_bundled_order_items( $container_order_item, $order = false, $return_ids = false ) {
		$bundled_order_items = array();

		if ( $this->is_bundle_container_order_item( $container_order_item ) ) {

			$bundled_cart_keys = maybe_unserialize( $container_order_item['revx_bundled_items'] );

			if ( ! empty( $bundled_cart_keys ) && is_array( $bundled_cart_keys ) ) {

				if ( false === $order ) {
					if ( is_callable( array( $container_order_item, 'get_order' ) ) ) {

						$order_id = $container_order_item->get_order_id();
						$order    = wc_get_order( $order_id );

						if ( null === $order ) {
							$order = $container_order_item->get_order();
						}
					} else {
						$msg = 'get_order() is not callable on the supplied $order_item. No $order object given.';
						_doing_it_wrong( __FUNCTION__ . '()', esc_html( $msg ), '1.0.0' );
					}
				}

				$order_items = is_object( $order ) ? $order->get_items( 'line_item' ) : $order;

				if ( ! empty( $order_items ) ) {
					foreach ( $order_items as $order_item_id => $order_item ) {

						$is_child = false;

						if ( isset( $order_item['revx_cart_key'] ) ) {
							$is_child = in_array( $order_item['revx_cart_key'], $bundled_cart_keys ) ? true : false;
						} else {
							$is_child = isset( $order_item['revx_bundle_data'] ) && $order_item['revx_bundle_data'] === $container_order_item['revx_bundle_data'] && isset( $order_item['revx_bundled_by'] ) ? true : false;
						}

						if ( $is_child ) {
							$bundled_order_items[ $order_item_id ] = $order_item;
						}
					}
				}
			}
		}

		return $return_ids ? array_keys( $bundled_order_items ) : $bundled_order_items;
	}


	/**
	 * Calculate discount percentage, from regular and sale price
	 *
	 * @param string|float $regular_price Regular Price.
	 * @param string|float $sale_price Sale price.
	 *
	 * @since 1.0.0
	 */
	public function calculate_discount_percentage( $regular_price, $sale_price ) {
		// Check if the regular price is greater than zero to avoid division by zero error.
		if ( $regular_price > 0 ) {
			// Calculate the discount amount.
			$discount_amount = floatval( $regular_price ) - floatval( $sale_price );
			// Calculate the discount percentage.
			$dis_per = ( $discount_amount / $regular_price ) * 100;

			// Return the discount percentage.
			return $dis_per;
		} else {
			// Return 0 if regular price is not greater than zero.
			return 0;
		}
	}


	public function set_product_image_trigger_item_response( $data, $is_clone = false ) {
		$keys = array( 'campaign_trigger_items', 'campaign_trigger_exclude_items' );

		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				continue;
			}

			$updated_data = array();
			foreach ( $data[ $key ] as $item ) {
				$item = (array) $item;
				if ( $key === 'campaign_trigger_items' && $item['trigger_action'] !== 'include' ) {
					continue;
				}
				$image_url = '';

				$updated_item = $item; // No need to use array_merge, direct assignment works

				if ( $item['trigger_type'] === 'products' ) {
					$product = wc_get_product( $item['item_id'] );
					if ( ! $product ) {
						continue;
					}
					$image_url                      = wp_get_attachment_url( $product->get_image_id() ) ?: wc_placeholder_img_src();
					$updated_item['item_name']      = $product->get_name();
					$updated_item['regular_price']  = $product->get_regular_price();
					$updated_item['sale_price']     = $product->get_sale_price();
					$updated_item['show_attribute'] = 'variable' == $product->get_type();
				} elseif ( $item['trigger_type'] === 'category' ) {
					$category                  = get_term( $item['item_id'] );
					$thumbnail_id              = get_term_meta( $category->term_id, 'thumbnail_id', true );
					$image_url                 = wp_get_attachment_url( $thumbnail_id ) ?: wc_placeholder_img_src();
					$updated_item['item_name'] = rawurldecode( wp_strip_all_tags( $category->name ) );
				}

				$updated_item = apply_filters( 'revenue_trigger_item_response', $updated_item, $item['trigger_type'], $data['campaign_type'] );

				$updated_item['thumbnail'] = $image_url;

				if ( $is_clone ) {
					if ( isset( $updated_item['campaign_id'] ) ) {
						unset( $updated_item['campaign_id'] );
					}
					if ( isset( $updated_item['trigger_id'] ) ) {
						unset( $updated_item['trigger_id'] );
					}
				}

				$updated_data[] = $updated_item;
			}

			$data[ $key ] = $updated_data;
		}
		return $data;
	}




	public function update_campaign_impression( $campaign_id, $product_id = false ) {
		$campaign = $this->get_campaign_data( $campaign_id );
		if ( ! $campaign || empty( $campaign ) ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'impression_count' );
	}


	/**
	 * Update campaign table by campaign id and specific column and value.
	 *
	 * @since 1.0.0
	 */
	public function update_campaign( $campaign_id, $key, $value ) {
		global $wpdb;
		$data = array( sanitize_key( $key ) => sanitize_text_field( $value ) );

		$data = wp_unslash( $data );

		$where = array( 'id' => $campaign_id );

		if ( false === $wpdb->update( $wpdb->prefix . 'revenue_campaigns', $data, $where ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return false;
		}

		$this->clear_campaign_runtime_cache( $campaign_id );
	}


	public function get_item_ids_from_triggers( $triggers ) {
		$item_ids = array();

		// Check if the 'triggers' key exists and is an array
		if ( isset( $triggers['triggers'] ) && is_array( $triggers['triggers'] ) ) {
			// Loop through each trigger
			foreach ( $triggers['triggers'] as $trigger ) {
				// Check if 'items' key exists and is an array
				if ( isset( $trigger['items'] ) && is_array( $trigger['items'] ) ) {
					// Loop through each item and get the 'item_id'
					foreach ( $trigger['items'] as $item ) {
						if ( isset( $item['item_id'] ) ) {
							$item_ids[] = $item['item_id'];
						}
					}
				}
			}
		}

		return $item_ids;
	}


	public function get_cart_product_ids() {
		if ( ! isset( WC()->cart ) ) {
			return array();
		}
		// Check if the product IDs are already stored in the cache
		// $cached_product_ids = wp_cache_get( 'revx_cart_product_ids' );
		// if ( $cached_product_ids !== false ) {

		// return $cached_product_ids;
		// }

		// If not cached, get the cart items and their product IDs
		$product_ids = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_ids[] = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		}

		// Cache the product IDs for future use
		// wp_cache_set( 'revx_cart_product_ids', $product_ids );

		return $product_ids;
	}

	public function campaign_style_generator( $type = 'inpage', $campaign = array(), $placement = '' ) {
		$view_mode = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
		if ( 'drawer' == $type ) {
			$styles = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )[ $type ];
		} elseif ( 'spending_goal' === $campaign['campaign_type'] || 'free_shipping_bar' === $campaign['campaign_type'] || 'stock_scarcity' === $campaign['campaign_type'] || 'countdown_timer' === $campaign['campaign_type'] || 'next_order_coupon' === $campaign['campaign_type'] || 'next_order_coupon' === $campaign['campaign_type'] ) {
				$styles = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )[ $type ];

		} else {
			$styles = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )[ $type ][ $view_mode ];
		}

		$data = array();

		foreach ( $styles as $key => $style ) {
			if ( ( 'leftSliderIcon' == $key || 'rightSliderIcon' == $key ) && 'inpage' != $type ) {
				$data[ $key ] = $this->generate_style( $style, true );
			} else {
				$data[ $key ] = $this->generate_style( $style );
			}
		}

		return $data;
	}


	public function generate_style( $style, $use_calc = false ) {

		$generated_styles = array();
		$tag              = array();
		$input            = array();
		$child            = array();
		$classes          = array();

		foreach ( $style as $sectionKey => $sectionVal ) {
			switch ( $sectionKey ) {
				case 'color':
					foreach ( $sectionVal as $property => $value ) {
						switch ( $property ) {
							case 'background':
								if ( '' != $value ) {
									$generated_styles['background-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'border':
								if ( '' != $value ) {
									$generated_styles['border-color'] = $this->colorStringToHex( $value );
									$generated_styles['border-style'] = 'solid';
									// $generated_styles['border-width'] ="1px";
								}
								break;
							case 'text':
								if ( '' != $value ) {
									$generated_styles['color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'input':
								if ( '' != $value ) {
									$input['color'] = $this->colorStringToHex( $value );
								}
							case 'button':
								if ( '' != $value ) {
									$child['background-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'iconColor':
								if ( '' != $value ) {
									$generated_styles['--revx-icon-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'icon':
								if ( '' != $value ) {
									$child['color'] = $this->colorStringToHex( $value );

									// $generated_styles['--revx-icon-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'hover':
								if ( '' != $value ) {
									$generated_styles['--revx-hover-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'hoverText':
								if ( '' != $value ) {
									$generated_styles['--revx-hover-text-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'emptyColor':
								if ( '' != $value ) {
									$generated_styles['--revx-empty-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'filledColor':
								if ( '' != $value ) {
									$generated_styles['--revx-filled-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'couponBackground':
								if ( '' != $value ) {
									$generated_styles['--revx-background-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'couponBorder':
								if ( '' != $value ) {
									$generated_styles['--revx-border-color'] = $this->colorStringToHex( $value );
								}
								break;
							case 'couponIcon':
								if ( '' != $value ) {
									$generated_styles['--revx-coupon-color'] = $this->colorStringToHex( $value );
								}
							case 'couponSeparator':
								if ( '' != $value ) {
									$generated_styles['--revx-separator-color'] = $this->colorStringToHex( $value );
								}
								break;

							default:
								// code...
								break;
						}
					}
					break;
				case 'typographyControl':
					foreach ( $sectionVal as $property => $value ) {
						switch ( $property ) {
							case 'borderWidth':
								if ( '' != $value ) {
									$generated_styles['border-width'] = "{$value}px";
									if ( '' != $value ) {
										$generated_styles['border-style'] = 'solid';
									}
								}
								break;
							case 'borderRadius':
								if ( '' != $value ) {
									$generated_styles['border-radius'] = "{$value}px";
								}
								break;

							case 'padding':
								if ( '' != $value ) {
									$generated_styles['padding'] = "{$value}px";
								}
								break;
							case 'paddingTopBottom':
								if ( '' != $value ) {
									$generated_styles['padding-top']    = "{$value}px";
									$generated_styles['padding-bottom'] = "{$value}px";
								}
								break;
							case 'paddingTop':
								if ( '' != $value ) {
									$generated_styles['padding-top'] = "{$value}px";
								}
								break;
							case 'paddingBottom':
								if ( '' != $value ) {
									$generated_styles['padding-bottom'] = "{$value}px";
								}
								break;
							case 'paddingLeftRight':
								if ( '' != $value ) {
									$generated_styles['padding-left']  = "{$value}px";
									$generated_styles['padding-right'] = "{$value}px";
								}
								break;
							case 'gap':
								if ( '' != $value && intval( $value ) > 0 ) {
									$generated_styles['gap']     = "{$value}px";
									$generated_styles['display'] = 'flex';
								}
								break;
							case 'buttonStyle':
								if ( '' != $value ) {
									$classes[] = 'revx-btn-' . $value;
								}
								break;
							case 'size':
								if ( '' != $value ) {
									$classes[] = 'revx-btn-size-' . $value;
								}
								break;
							case 'maxHeight':
								$generated_styles['max-height'] = "{$value}px";
								break;
							case 'maxWidth':
								$generated_styles['max-width'] = "{$value}px";
								break;
							case 'height':
								$generated_styles['height'] = "{$value}px";
								break;
							case 'width':
								$generated_styles['width'] = "{$value}px";
								break;
							case 'top':
								if ( '' != $value ) {
									$generated_styles['margin-top'] = "{$value}px";
								}
								break;
							case 'bottom':
								if ( '' != $value ) {
									$generated_styles['margin-bottom'] = "{$value}px";
								}
								break;
							case 'left':
								if ( '' != $value ) {
									$generated_styles['margin-left'] = "{$value}px";
								}
								break;
							case 'right':
								if ( '' != $value ) {
									$generated_styles['margin-right'] = "{$value}px";
								}
								break;
							case 'numberOfColumn':
								if ( '' != $value ) {
									$generated_styles['--revx-grid-column'] = "{$value}";
								}
								break;
							case 'containerSize':
								if ( '' != $value ) {
									$generated_styles['--revx-coupon-size'] = "{$value}";
								}
								break;

							default:
								// code...
								break;
						}
					}
					break;
				case 'padding':
					foreach ( $sectionVal as $property => $value ) {
						if ( '' != $value ) {
							$generated_styles[ "padding-$property" ] = "{$value}px";
						}
					}
					break;
				case 'spacing':
					foreach ( $sectionVal as $property => $value ) {
						if ( '' != $value ) {
							if ( 'gap' == $property && intval( $value ) > 0 ) {
								$generated_styles['gap']     = "{$value}px";
								$generated_styles['display'] = 'flex';
							} else {
								$generated_styles[ "margin-$property" ] = "{$value}px";
							}
						}

						if ( $use_calc ) {
							if ( $property == 'left' || $property == 'right' ) {
								$generated_styles[ "margin-$property" ] = "calc({$value}px/2)";
							}
						}
					}
					break;
				case 'typography':
					foreach ( $sectionVal as $property => $value ) {
						switch ( $property ) {
							case 'fontSize':
								if ( '' != $value ) {
									$generated_styles['font-size']   = "{$value}px";
									$generated_styles['line-height'] = 'inherit';
								}
								// code...
								break;
							case 'fontStyle':
								foreach ( $value as $val ) {
									switch ( $val ) {
										case 'strike':
											$tag[] = 'strike';
											break;

										case 'bold':
											$generated_styles['font-weight'] = 'bold';
											break;
										case 'underline':
											$generated_styles['text-decoration'] = 'underline';
											break;
										case 'italic':
											$tag[] = 'i';
											break;

										default:
											// code...
											break;
									}
								}
								break;
							case 'tag':
								$tag[] = $value;
								break;

							default:
								// code...
								break;
						}
					}
					break;
				case 'align':
					if ( $sectionVal ) {
						$generated_styles['text-align']      = $sectionVal;
						$generated_styles['justify-content'] = $sectionVal;
					}
					break;
				case 'direction':
					if ( $sectionVal ) {
						$generated_styles['flex-direction'] = $sectionVal;
					}
					break;
				case 'tabs':
					foreach ( $sectionVal as $key => $value ) {
						switch ( $key ) {
							case 'typography':
								if ( isset( $value['fontSize'] ) && ! empty( $value['fontSize'] ) ) {
									$fz                 = $value['fontSize'];
									$input['font-size'] = "{$fz}px";
								}
								break;
							case 'plusMinus':
								if ( isset( $value['iconSize'] ) && ! empty( $value['iconSize'] ) ) {
									$fz                 = $value['iconSize'];
									$child['font-size'] = "{$fz}px";
								}
								break;
							case 'typographyControl':
								foreach ( $value as $property => $val ) {

									switch ( $property ) {
										case 'borderWidth':
											if ( '' != $val ) {
												$generated_styles['border-width'] = "{$val}px";
												$generated_styles['border-style'] = 'solid';
											}
											break;
										case 'borderRadius':
											if ( '' != $val ) {
												$generated_styles['border-radius'] = "{$val}px";
											}
											break;
										case 'padding':
											if ( '' != $val ) {
												$generated_styles['padding'] = "{$val}px";
											}
											break;
										case 'gap':
											if ( '' != $val && intval( $val ) > 0 ) {
												$generated_styles['gap']     = "{$val}px";
												$generated_styles['display'] = 'flex';
											}
											break;
										case 'size':
											if ( '' != $val ) {
												$classes[] = 'revx-btn-size-' . $val[0];
											}

											break;

										default:
											// code...
											break;
									}
								}
								break;

							default:
								// code...
								break;
						}
					}
					break;
				case 'position':
					foreach ( $sectionVal as $property => $value ) {
						if ( '' != $value ) {
							$generated_styles[ "{$property}" ] = "{$value}px";
						}
					}
					break;

				default:
					// code...
					break;
			}
		}

		$cachedData = array(
			'css'     => $this->convert_to_inline( $generated_styles ),
			'tag'     => $tag,
			'input'   => $this->convert_to_inline( $input ),
			'child'   => $this->convert_to_inline( $child ),
			'classes' => $classes,
		);

		return $cachedData;
	}

	public function convert_to_inline( $styles ) {
		$css = '';
		foreach ( $styles as $property => $value ) {
			if ( is_array( $value ) ) {
				$css .= $this->convert_to_inline( $value );
			} else {
				$css .= "$property: $value;";
			}
		}
		return $css;
	}


	public function tag_wrapper( $current_campaign, $styles, $name, $content = '', $class = '', $tag = 'div', $customData = array() ) {
		$style      = isset( $styles[ $name ] ) ? $styles[ $name ] : array();
		$css        = isset( $style['css'] ) ? $style['css'] : '';
		$classes    = isset( $style['classes'] ) ? $style['classes'] : array();
		$classes    = implode( ' ', $classes );
		$tags       = array();
		$p_name     = wp_strip_all_tags( $content );
		$wrapperTag = '';

		switch ( $name ) {
			case 'bundleLabel':
				$content = $this->get_campaign_meta( $current_campaign['id'], 'bundle_label_badge', true ) ?? __( 'BUNDLE OFFER', 'revenue' );
				break;
			case 'totalPriceText':
				$content = $this->get_campaign_meta( $current_campaign['id'], 'total_price_text', true ) ?? __( 'Total', 'revenue' );
				break;
			case 'addToCartButton':
				if ( isset( $current_campaign['skip_add_to_cart'] ) && 'yes' == $current_campaign['skip_add_to_cart'] ) {
					$content = $this->get_campaign_meta( $current_campaign['id'], 'checkout_btn_text', true ) ?? __( 'Checkout', 'revenue' );
				} else {
					$content = $this->get_campaign_meta( $current_campaign['id'], 'add_to_cart_btn_text', true ) ?? __( 'Add To Cart', 'revenue' );
				}
				break;
			case 'noThanksButton':
				$content = $this->get_campaign_meta( $current_campaign['id'], 'no_thanks_button_text', true ) ?? __( 'No, Thanks', 'revenue' );
				break;
			case 'productTag':
				$content = $this->get_campaign_meta( $current_campaign['id'], 'product_tag_text', true ) ?? __( 'Most Popular', 'revenue' );
				break;
			case 'discount_amount':
				break;
			default:
				// code...
				break;
		}
		$wrappedContent = $content;
		if ( isset( $style['tag'] ) && ! empty( $style['tag'] ) && is_array( $style['tag'] ) ) {
			$tags = $style['tag'];
		}

		if ( ! $content && ! ( in_array( $name, array( 'selectedProdTitle', 'selectedProductPrice', 'productregular_price', 'regular_price', 'salePrice', 'rewardedMessage' ) ) ) ) {
			return '';
		}

		foreach ( $tags as $idx => $tag ) {
			if ( $idx == 0 ) {
				$wrapperTag = $tag;
			} else {
				$wrappedContent = "<$tag title='$p_name'>" . $wrappedContent . "</$tag>";
			}
		}

		if ( empty( $wrapperTag ) ) {
			$wrapperTag = $tag;
		}

		$data_attribute = '';
		if ( ! empty( $customData ) ) {
			foreach ( $customData as $key => $value ) {
				$safe_value = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
				$data_attribute .= "data-$key='" . $safe_value . "' ";
			}
		}

		// Special case for product title link
		if ( 'double_order' !== $current_campaign['campaign_type'] ) {
			$click_action = revenue()->get_campaign_meta( $current_campaign['id'], 'offered_product_click_action', true ) ?? 'go_to_product';

			if ( $name == 'productTitle' && isset( $customData['product_url'] ) && 'go_to_product' == $click_action ) {
				ob_start();
				?>
			<a target="_blank" href="<?php echo esc_url( $customData['product_url'] ); ?>">
				<?php
				echo wp_kses(
					"<$wrapperTag title='$p_name' style='$css' class='$class $classes' $data_attribute>$wrappedContent</$wrapperTag>",
					revenue()->get_allowed_tag()
				);
				?>
			</a>
				<?php
				return ob_get_clean();
			}
		}

		// Add spinner for addToCartButton
		if ( 'addToCartButton' === $name ) {
			$wrappedContent = '<span class="spinner" style="display: none;"></span>' . $wrappedContent;
		}

		return "<$wrapperTag title='$p_name' style='$css' class='$class $classes' $data_attribute>$wrappedContent</$wrapperTag>";
	}


	public function popup_container( $current_campaign, $generated_styles, $output_content, $class = '', $without_heading = false, $placement = '' ) {

		$placement_settings = $this->get_placement_settings( $current_campaign['id'], $placement );
		$view_mode          = $placement_settings['builder_view'] ?? 'list';

		$container_style = revenue()->get_style( $generated_styles, 'container' );
		$heading_text    = isset( $current_campaign['banner_heading'] ) ? $current_campaign['banner_heading'] : '';
		$subheading_text = isset( $current_campaign['banner_subheading'] ) ? $current_campaign['banner_subheading'] : '';
		$campaign_type   = $current_campaign['campaign_type'];
		$view_id         = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_id', true ) ?? '';
		$view_class      = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_class', true ) ?? '';
		$class          .= " $view_class ";

		$animation_delay = 0;
		$animation_name  = isset( $placement_settings['popup_animation'] ) ? esc_attr( $placement_settings['popup_animation'] ) : '';
		$animation_class = "revx-animation-$animation_name";

		$animation_delay = isset( $placement_settings['popup_animation_delay'] ) ? $placement_settings['popup_animation_delay'] : 0;

		do_action( "revenue_campaign_{$campaign_type}_inpage_before_rendered_content", $current_campaign );
		?>
		<div id="<?php echo esc_attr( $view_id ); ?>" class="revx-popup revx-all-center revx-campaign-<?php echo esc_attr( $current_campaign['id'] ); ?> revx-campaign-view-<?php echo esc_attr( $current_campaign['id'] ); ?> <?php echo esc_attr( $animation_class ); ?>">
			<div id="revx-popup-overlay" class="revx-popup__overlay"></div>
			<div class="revx-popup__container" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" data-animation-name="<?php echo esc_attr( $animation_name ); ?>" data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>" id="revx-popup">
				<div data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" data-animation-name="<?php echo esc_attr( $animation_name ); ?>" data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>" class="revx-popup__content revx-campaign-container <?php echo esc_attr( $class ); ?> revx-campaign-<?php echo esc_attr( $view_mode ); ?>" style="<?php echo esc_attr( $container_style ); ?>">
					<?php
						echo wp_kses(
							revenue()->get_template_part(
								'campaign_close',
								array(
									'generated_styles' => $generated_styles,
									'current_campaign' => $current_campaign,
								)
							),
							revenue()->get_allowed_tag()
						); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					<?php
					/**
					 * Executes actions before the header of a revenue campaign.
					 *
					 * @param int $current_campaign_id The ID of the current campaign.
					 * @param string $campaign_type The type of the campaign.
					 * @param string $position The position of the campaign, in this case 'popup'.
					 * @param array $current_campaign The current campaign data.
					 */
					do_action( 'revenue_campaign_before_header', $current_campaign['id'], $campaign_type, 'popup', $current_campaign );

					?>

					<div class="revx-campaign-header">
								<?php
								if ( ! $without_heading ) {
									if ( $heading_text ) {
										echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'heading', $heading_text, 'revx-campaign-view__title' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}

									if ( $subheading_text ) {
										echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'subHeading', $subheading_text, 'revx-campaign-view__title' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
								}

								?>
					</div>
					<?php

					echo wp_kses(
						revenue()->get_template_part(
							'free_shipping',
							array(
								'generated_styles' => $generated_styles,
								'current_campaign' => $current_campaign,
							)
						),
						revenue()->get_allowed_tag()
					);
					echo wp_kses(
						revenue()->get_template_part(
							'countdown_timer',
							array(
								'generated_styles' => $generated_styles,
								'current_campaign' => $current_campaign,
							)
						),
						revenue()->get_allowed_tag()
					);

					do_action( "revenue_campaign_{$campaign_type}_inpage_after_header" );
					echo wp_kses( $output_content, revenue()->get_allowed_tag() );
					do_action( "revenue_campaign_{$campaign_type}_inpage_after_content" );

					?>
				</div>
			</div>
		</div>
		<?php
		do_action( "revenue_campaign_{$campaign_type}_inpage_after_rendered_content", $current_campaign );
	}
	public function floating_container( $current_campaign, $generated_styles, $output_content, $class = '', $without_heading = false, $placement = '' ) {
		$animation_delay = 0;
		$container_style = revenue()->get_style( $generated_styles, 'container' );

		$placement_settings = $this->get_placement_settings( $current_campaign['id'], $placement );
		$view_mode          = $placement_settings['builder_view'] ?? 'list';

		$heading_text    = isset( $current_campaign['banner_heading'] ) ? $current_campaign['banner_heading'] : '';
		$subheading_text = isset( $current_campaign['banner_subheading'] ) ? $current_campaign['banner_subheading'] : '';
		$campaign_type   = $current_campaign['campaign_type'];

		$view_id    = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_id', true ) ?? '';
		$view_class = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_class', true ) ?? '';
		$class     .= " $view_class ";

		$animation_delay = isset( $placement_settings['floating_animation_delay'] ) ? $placement_settings['floating_animation_delay'] : 0;

		$position = isset( $placement_settings['floating_position'] ) ? $placement_settings['floating_position'] : '';
		// Determine position class based on $position variable
		switch ( $position ) {
			case 'top-left':
			case 'top-right':
			case 'bottom-left':
			case 'bottom-right':
				$position_class = 'revx-floating-' . esc_attr( $position );
				break;
			default:
				$position_class = 'revx-floating-bottom-right'; // Default to bottom-right if position is not specified
				break;
		}
		?>
			<div id="<?php echo esc_attr( $view_id ); ?>" class="revx-floating-main revx-all-center revx-campaign-<?php echo esc_attr( $current_campaign['id'] ); ?> revx-campaign-view-<?php echo esc_attr( $current_campaign['id'] ); ?> "  data-position-class="<?php echo esc_attr( $position_class ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>" >
				<div class="revx-floating-container">
					<div id="revx-floating" class="revx-floating revx-campaign-container <?php echo esc_attr( $class ); ?> revx-campaign-<?php echo esc_attr( $view_mode ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" style="<?php echo esc_attr( $container_style ); ?>">
							<?php
								echo wp_kses(
									revenue()->get_template_part(
										'campaign_close',
										array(
											'generated_styles' => $generated_styles,
											'current_campaign' => $current_campaign,
										)
									),
									revenue()->get_allowed_tag()
								); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						<?php
						/**
						 * Executes actions before the header of a revenue campaign.
						 *
						 * @param int $current_campaign_id The ID of the current campaign.
						 * @param string $campaign_type The type of the campaign.
						 * @param string $position The position of the campaign, in this case 'floating'.
						 * @param array $current_campaign The current campaign data.
						 */
						do_action( 'revenue_campaign_before_header', $current_campaign['id'], $campaign_type, 'floating', $current_campaign );

						?>
					<div class="revx-campaign-header">
						<?php
						if ( ! $without_heading ) {
							if ( $heading_text ) {
								echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'heading', $heading_text, 'revx-campaign-view__title' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}

							if ( $subheading_text ) {
								echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'subHeading', $subheading_text, 'revx-campaign-view__title' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}

						?>
					</div>
					<?php

					echo wp_kses(
						revenue()->get_template_part(
							'free_shipping',
							array(
								'generated_styles' => $generated_styles,
								'current_campaign' => $current_campaign,
							)
						),
						revenue()->get_allowed_tag()
					);
					echo wp_kses(
						revenue()->get_template_part(
							'countdown_timer',
							array(
								'generated_styles' => $generated_styles,
								'current_campaign' => $current_campaign,
							)
						),
						revenue()->get_allowed_tag()
					);

					do_action( "revenue_campaign_{$campaign_type}_inpage_after_header" );
					echo wp_kses( $output_content, revenue()->get_allowed_tag() );
					do_action( "revenue_campaign_{$campaign_type}_inpage_after_content" );

					?>
				</div>
			</div>
		</div>
		<?php
		do_action( 'revenue_campaign_after_container', $current_campaign['id'], $campaign_type, 'floating', $current_campaign );
	}

	public function inpage_container( $current_campaign, $generated_styles, $output_content, $class = '', $placement = '' ) {
		$placement_settings = $this->get_placement_settings( $current_campaign['id'], $placement );
		$view_mode          = $placement_settings['builder_view'] ?? 'list';

		$container_style = revenue()->get_style( $generated_styles, 'container' );

		$view_id    = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_id', true ) ?? '';
		$view_class = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_class', true ) ?? '';
		$class     .= " $view_class ";

		$heading_text    = isset( $current_campaign['banner_heading'] ) ? $current_campaign['banner_heading'] : '';
		$subheading_text = isset( $current_campaign['banner_subheading'] ) ? $current_campaign['banner_subheading'] : '';
		$campaign_type   = $current_campaign['campaign_type'];

		$theme      = wp_get_theme();
		$theme_name = get_stylesheet();

		$class .= " revx-theme-$theme_name ";

		$position = $placement_settings['inpage_position'];

		do_action( 'revenue_campaign_before_container', $current_campaign['id'], $campaign_type, 'inpage', $current_campaign );

		ob_start();
		?>
		<div id="<?php echo esc_attr( $view_id ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" class="revx-inpage-container revx-campaign-container <?php echo esc_attr( $class ); ?> revx-campaign-<?php echo esc_attr( $view_mode ); ?>  revx-campaign-<?php echo esc_attr( $current_campaign['id'] ); ?>" style="<?php echo esc_attr( $container_style ); ?>">



			<?php
			/**
			 * Executes actions before the header of a revenue campaign.
			 *
			 * @param int $current_campaign_id The ID of the current campaign.
			 * @param string $campaign_type The type of the campaign.
			 * @param string $position The position of the campaign, in this case 'inpage'.
			 * @param array $current_campaign The current campaign data.
			 */
			do_action( 'revenue_campaign_before_header', $current_campaign['id'], $campaign_type, 'inpage', $current_campaign );

			?>

			<?php

			if ( $heading_text ) {
				echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'heading', $heading_text, 'revx-campaign-view__title' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( $subheading_text ) {
				echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'subHeading', $subheading_text, 'revx-campaign-view__title' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo wp_kses(
				revenue()->get_template_part(
					'free_shipping',
					array(
						'generated_styles' => $generated_styles,
						'current_campaign' => $current_campaign,
					)
				),
				revenue()->get_allowed_tag()
			);
			echo wp_kses(
				revenue()->get_template_part(
					'countdown_timer',
					array(
						'generated_styles' => $generated_styles,
						'current_campaign' => $current_campaign,
					)
				),
				revenue()->get_allowed_tag()
			);

			?>
			<?php echo wp_kses( $output_content, revenue()->get_allowed_tag() ); ?>
		</div>
		<?php
		$output = ob_get_clean();

		?>
		<div class="revx-container <?php echo esc_attr( $position ); ?> revx-justify-center">
			<?php echo wp_kses( $output, revenue()->get_allowed_tag() ); ?>
		</div>
		<?php
		do_action( 'revenue_campaign_after_container', $current_campaign['id'], $campaign_type, 'inpage', $current_campaign );
	}


	public function get_style( $styles, $name, $type = '' ) {
		$style = isset( $styles[ $name ] ) ? $styles[ $name ] : array();
		$css   = isset( $style['css'] ) ? $style['css'] : '';

		if ( $type ) {
			switch ( $type ) {
				case 'input':
					if ( isset( $style['input'] ) ) {
						return $style['input'];
					}
				case 'child':
					if ( isset( $style['child'] ) ) {
						return $style['child'];
					}
				case 'classes':
					$classes = isset( $style['classes'] ) ? $style['classes'] : array();
					$classes = implode( ' ', $classes );
					return $classes;

				default:
					// code...
					break;
			}
		}

		return $css;
	}

	/**
	 * Converts RGB or RGBA color values from string format to hexadecimal color representation.
	 *
	 * @param string $colorString Color string in format 'rgb(r, g, b)' or 'rgba(r, g, b, a)'
	 * @return string Hexadecimal color string, e.g., '#RRGGBB' or '#RRGGBBAA'
	 */
	public function colorStringToHex( $colorString ) {

		if ( 'transparent' == $colorString ) {
			return $colorString;
		}

		if ( preg_match( '/^#([a-f0-9]{3}([a-f0-9]{3})?([a-f0-9]{2})?)$/i', $colorString ) ) {
			// If shorthand notation, expand it to full form
			if ( strlen( $colorString ) == 4 || strlen( $colorString ) == 5 ) {
				$colorString = preg_replace( '/^#([a-f0-9])([a-f0-9])([a-f0-9])([a-f0-9])?$/i', '#$1$1$2$2$3$3$4$4', $colorString );
			}
			return $colorString; // Return the valid hex color
		}
		// Remove spaces and convert to lowercase
		$colorString = strtolower( str_replace( ' ', '', $colorString ) );

		// Check if the input string matches 'rgba(r,g,b,a)' format
		if ( preg_match( '/^rgba\((\d+),(\d+),(\d+),([\d.]+)\)$/', $colorString, $matches ) ) {
			$r = $matches[1];
			$g = $matches[2];
			$b = $matches[3];
			$a = $matches[4];
		}
		// Check if the input string matches 'rgb(r,g,b)' format
		elseif ( preg_match( '/^rgb\((\d+),(\d+),(\d+)\)$/', $colorString, $matches ) ) {
			$r = $matches[1];
			$g = $matches[2];
			$b = $matches[3];
			$a = 1.0; // Default alpha value for RGB is 1.0 (fully opaque)
		} else {
			return ''; // Return empty string if the input format is invalid
		}

		// Convert RGB or RGBA values to hexadecimal
		$hex = $this->rgbToHex( $r, $g, $b, $a );

		return $hex;
	}


	/**
	 * Converts RGB or RGBA color values to hexadecimal color representation.
	 *
	 * @param int   $r Red component (0-255)
	 * @param int   $g Green component (0-255)
	 * @param int   $b Blue component (0-255)
	 * @param float $a Alpha component (0.0-1.0), defaults to 1.0 (opaque)
	 * @return string Hexadecimal color string, e.g., '#RRGGBB' or '#RRGGBBAA'
	 */
	public function rgbToHex( $r, $g, $b, $a = 1.0 ) {
		// Validate input values
		$r = max( 0, min( 255, (int) $r ) );
		$g = max( 0, min( 255, (int) $g ) );
		$b = max( 0, min( 255, (int) $b ) );
		$a = max( 0.0, min( 1.0, (float) $a ) );

		// Convert RGB to hexadecimal
		$hex = sprintf( '#%02x%02x%02x', $r, $g, $b );

		// If alpha is provided and it's not fully opaque, add alpha to the hex string
		if ( $a < 1.0 ) {
			$alphaHex = str_pad( dechex( (int) round( $a * 255 ) ), 2, '0', STR_PAD_LEFT );
			$hex     .= $alphaHex;
		}

		return $hex;
	}

	/**
	 * Retrive from cache.
	 *
	 * @param string $cache_key Cache key.
	 * @return mixed
	 */
	public function retrieveFromCache( $cache_key ) {
		// Attempt to retrieve cached data from object cache first.
		$cached_data = wp_cache_get( 'revenue_' . $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data; // Return cached data if found in object cache.
		}

		// If not found in object cache, attempt to retrieve from transient.
		$transient_data = get_transient( 'revenue_' . $cache_key );

		if ( false !== $transient_data ) {
			// Set retrieved transient data in object cache for future quick access.
			wp_cache_set( 'revenue_' . $cache_key, $transient_data );
			return $transient_data; // Return cached data if found in transient.
		}

		return false; // Return false if data not found in either cache.
	}

	/**
	 * Store in cache
	 *
	 * @param string $cache_key Cache key.
	 * @param array  $data Data.
	 * @return void
	 */
	public function storeInCache( $cache_key, $data ) {
		// Store data in both object cache and transient
		wp_cache_set( 'revenue_' . $cache_key, $data );

		// Set cached data for 1 days
		set_transient( 'revenue_' . $cache_key, $data, DAY_IN_SECONDS );
	}

	/**
	 * Get template part
	 *
	 * @param string $name Template part name.
	 * @param array  $args Template args.
	 * @return string
	 */
	public function get_template_part( $name, $args = array() ) {
		if ( ! $name ) {
			return;
		}
		$args = wp_parse_args(
			$args,
			array(
				'min_quantity'     => 1,
				'max_quantity'     => false,
				'value'            => 1,
				'quantity'         => 1,
				'regular_price'    => 0,
				'sale_price'       => 0,
				'quantity'         => 1,
				'data'             => array(),
				'view_mode'        => '',
				'campaign_type'    => '',
				'class'            => '',
				'required'         => false,
				'selected'         => false,
				'message'          => '',
				'current_campaign' => false,
				'generated_styles' => false,
				'offered_product'  => false,
				'campaign_type'    => '',
				'force_show'       => false,
				'title'            => '',
				'image'            => '',
				'product_id'       => false,
				'offer_qty'        => 0,
				'offered_price'    => 0,
				'regular_price'    => 0,
				'source'           => '',
				'index'            => ''
			)
		);

		$file_path = REVENUE_PATH . "includes/campaigns/views/parts/$name.php";
		$output    = '';
		ob_start();
		if ( file_exists( $file_path ) ) {
			extract( $args ); //phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $file_path;
		}

		$output .= ob_get_clean();

		return $output;
	}


	/**
	 * Render templates
	 *
	 * @param array  $template_names Template Names.
	 * @param string $wrapper_class Wrapper Class.
	 * @return void
	 */
	public function render_templates( $template_names = array(), $wrapper_class = '' ) {

		$output = '';
		ob_start();
		foreach ( $template_names as $template_name ) {
			echo wp_kses( revenue()->get_template_part( $template_name ), revenue()->get_allowed_tag() );
		}

		$output .= ob_get_clean();

		if ( $wrapper_class ) {
			?>
			<div class="<?php echo esc_attr( $wrapper_class ); ?>">
				<?php echo wp_kses( $output, revenue()->get_allowed_tag() ); ?>
			</div>
			<?php
		} else {
			echo wp_kses( $output, revenue()->get_allowed_tag() );
		}
	}

	/**
	 * Get Slider icon
	 *
	 * @param array  $generated_styles Generated Style.
	 * @param string $direction Slider direction.
	 * @return string
	 */
	public function get_slider_icon( $generated_styles, $direction = 'left' ) {
		if ( ! $generated_styles ) {
			return '';
		}
		$slide_icon_style = revenue()->get_style( $generated_styles, $direction . 'SliderIcon' );
		ob_start();
		?>
		<div class="revx-builderSlider-<?php echo esc_attr( $direction ); ?> revx-builderSlider-icon revx-justify-center" style="<?php echo esc_attr( $slide_icon_style ); ?>">
			<svg width="8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M1 13L7 7L1 1" stroke="#868C98" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Calculate sale price based on offer price
	 *
	 * @param string  $type Type.
	 * @param string  $value Value.
	 * @param string  $regular_price Regular Price.
	 * @param integer $quantity Quantity.
	 * @return array
	 */
	function calculateSalePrice( $type, $value, $regular_price, $quantity = 1 ) {
		$value           = floatval( $value );
		$save            = array(
			'type'    => '',
			'value'   => 0,
			'content' => '',
		);
		$currency_symbol = '$';

		switch ( $type ) {
			case 'percentage':
				$save = array(
					'type'    => 'percentage',
					'value'   => $value,
					/* translators: %s: Discount percentage value */
					'content' => sprintf( __( 'Save %s%%', 'revenue' ), $value ),
				);
				break;
			case 'fixed_discount':
				$save = array(
					'type'    => 'amount',
					'value'   => $value,
					/* translators: %1s: Currency Symbol, %2s: Value */
					'content' => sprintf( __( 'Save %1$s%2$s', 'revenue' ), $currency_symbol, number_format( $value * $quantity, 2 ) ),
				);
				break;
			case 'no_discount':
				$price = $regular_price;
				break;
			case 'free':
				$price = 0;
				$save  = array(
					'type'    => 'free',
					'value'   => 100,
					'content' => __( 'Free', 'revenue' ),
				);
				break;
			case 'fixed_price':
				$price = $value;
				$save  = array(
					'type'    => 'amount',
					'value'   => abs( $regular_price - $value ),
					'content' => sprintf( __( 'Save %1$s%2$s', 'revenue' ), $currency_symbol, number_format( abs( $regular_price - $value ) * $quantity, 2 ) ),
				);
				break;
			default:
				break;
		}

		return $save;
	}


	/**
	 * Function to get volume discount builder items data based on campaign triggers and offers.
	 *
	 * @param array $campaign Campaign data containing triggers and offers.
	 * @return array Array of volume discount builder items data.
	 */
	public function getMixMatchQuantities( $campaign ) {
		$offers = $campaign['offers'];

		$regular_price = 100;

		$data = array();

		// Process each offer and calculate sale price.
		foreach ( $offers as $offer ) {
			if ( isset( $offer['type'], $offer['value'] ) ) {

				$sale_data = revenue()->calculateSalePrice( $offer['type'], $offer['value'], $regular_price, $offer['quantity'] );
				$data[]    = array(
					'saveData'    => $sale_data,
					'type'        => $offer['type'],
					'value'       => $offer['value'],
					'quantity'    => $offer['quantity'],
					'isEnableTag' => isset( $offer['isEnableTag'] ) ? $offer['isEnableTag'] : '',
				);
			}
		}

		return $data;
	}

	/**
	 * Function to get mix-match products based on campaign triggers.
	 *
	 * @param array $campaign Campaign data containing triggers.
	 * @return array Array of products matching the campaign triggers.
	 */
	public function getMixMatchProducts( $campaign ) {
		$triggers = $campaign['triggers'];
		$products = array();

		if ( $triggers ) {
			foreach ( $triggers as $idx => $trigger ) {
				$items          = $trigger['items'];
				$trigger_type   = $trigger['trigger_type'];
				$trigger_action = $trigger['trigger_action'];

				switch ( $trigger_type ) {
					case 'products':
						if ( 'include' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $product_id => $item ) {
								$product = wc_get_product( $product_id );
								if ( $product ) {
									$products[] = array(
										'id'            => $product->get_id(),
										'name'          => $product->get_name(),
										'regular_price' => $product->get_regular_price(),
										'thumbnail'     => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
									);
								}
							}
						} elseif ( 'exclude' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $product_id => $item ) {

								$excluded_product_ids[] = $product_id;
							}
							// Remove excluded products from $products array.
							$products = array_filter(
								$products,
								function ( $product ) use ( $excluded_product_ids ) {
									return ! in_array( $product['id'], $excluded_product_ids ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								}
							);
						}
						break;

					case 'category':
						if ( 'include' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $category_id => $category_item ) {
								$products_in_category = wc_get_products(
									array(
										'category' => array( $category_id ),
										'status'   => 'publish',
									)
								);

								foreach ( $products_in_category as $product ) {
									$products[] = array(
										'id'            => $product->get_id(),
										'name'          => $product->get_name(),
										'regular_price' => $product->get_regular_price(),
										'thumbnail'     => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
									);
								}
							}
						}
						break;

					case 'tag':
						if ( 'include' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $tag_id => $tag_item ) {
								$products_with_tag = wc_get_products(
									array(
										'tag'    => array( $tag_id ),
										'status' => 'publish',
									)
								);

								foreach ( $products_with_tag as $product ) {
									$products[] = array(
										'id'            => $product->get_id(),
										'name'          => $product->get_name(),
										'regular_price' => $product->get_regular_price(),
										'thumbnail'     => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
									);
								}
							}
						}
						break;

					default:
						break;
				}
			}
		}
		return $products;
	}
	/**
	 * Function to get mix-match products based on campaign triggers.
	 *
	 * @param array $campaign Campaign data containing triggers.
	 * @return array Array of products matching the campaign triggers.
	 */
	public function getBuyXGetYTriggerProducts( $campaign ) {
		$triggers = $campaign['triggers'];
		$products = array();

		if ( $triggers ) {
			foreach ( $triggers as $idx => $trigger ) {
				$items          = $trigger['items'];
				$trigger_type   = $trigger['trigger_type'];
				$trigger_action = $trigger['trigger_action'];

				if ( ! is_array( $items ) ) {
					return;
				}

				switch ( $trigger_type ) {
					case 'products':
						if ( 'include' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $product_id => $item ) {
								$product = wc_get_product( $product_id );
								if ( $product ) {
									$products[] = array(
										'id'            => $product->get_id(),
										'name'          => $product->get_name(),
										'regular_price' => $product->get_regular_price(),
										'thumbnail'     => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
									);
								}
							}
						} elseif ( 'exclude' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $product_id => $item ) {

								$excluded_product_ids[] = $product_id;
							}
							// Remove excluded products from $products array.
							$products = array_filter(
								$products,
								function ( $product ) use ( $excluded_product_ids ) {
									return ! in_array( $product['id'], $excluded_product_ids ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								}
							);
						}
						break;

					case 'category':
						if ( 'include' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $category_id => $category_item ) {
								$products_in_category = wc_get_products(
									array(
										'category' => array( $category_id ),
										'status'   => 'publish',
									)
								);

								foreach ( $products_in_category as $product ) {
									$products[] = array(
										'id'            => $product->get_id(),
										'name'          => $product->get_name(),
										'regular_price' => $product->get_regular_price(),
										'thumbnail'     => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
									);
								}
							}
						}
						break;

					case 'tag':
						if ( 'include' === $trigger_action && count( $items ) > 0 ) {
							foreach ( $items as $tag_id => $tag_item ) {
								$products_with_tag = wc_get_products(
									array(
										'tag'    => array( $tag_id ),
										'status' => 'publish',
									)
								);

								foreach ( $products_with_tag as $product ) {
									$products[] = array(
										'id'            => $product->get_id(),
										'name'          => $product->get_name(),
										'regular_price' => $product->get_regular_price(),
										'thumbnail'     => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
									);
								}
							}
						}
						break;

					default:
						break;
				}
			}
		}
		return $products;
	}


	/**
	 * Calculate growth
	 *
	 * @param array $current_totals Current totals.
	 * @param array $previous_totals Previous totals.
	 * @param array $data_keys Data keys.
	 * @return array
	 */
	public function calculate_growth( $current_totals, $previous_totals, $data_keys ) {
		$growth_data = array();
		foreach ( $data_keys as $key ) {
			$current_value  = $current_totals[ $key ] ?? 0;
			$previous_value = $previous_totals[ $key ] ?? 0;

			if ( $previous_value != 0 ) {
				$growth_data[ $key ] = ( ( $current_value - $previous_value ) / $previous_value ) * 100;
			} else {
				$growth_data[ $key ] = 0 == $current_value ? 0 : 100; //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			}
		}
		return $growth_data;
	}

	/**
	 * Generte campaigns stats chart data
	 *
	 * @param string $start Dtart Date.
	 * @param string $end End Date.
	 * @param array  $data Data.
	 * @param array  $keys Keys.
	 * @return array
	 */
	public function generate_campaigns_stats_chart_data( $start, $end, $data, $keys ) {
		$date_array = array();
		$cur_date   = new DateTime( $start );
		$end_date   = new DateTime( $end );

		$key_data = array();
		foreach ( $keys as $key ) {
			$key_data[ $key ] = 0;
		}
		while ( $cur_date <= $end_date ) {
			$formated_date                = $cur_date->format( 'Y-m-d' ); // Format date as YYYY-MM-DD.
			$key_data['date']             = $formated_date;
			$date_array[ $formated_date ] = $key_data;

			$cur_date->modify( '+1 day' ); // Increment current date by 1 day.
		}

		return $date_array;
	}

	/**
	 * Get allowed tag
	 *
	 * @return array
	 */
	public function get_allowed_tag() {
		$allowed_tags = array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'svg'     => array(
					'xmlns'   => true,
					'width'   => true,
					'height'  => true,
					'fill'    => true,
					'viewbox' => true,
				),
				'path'    => array(
					'stroke'         => true,
					'strokeLinecap'  => true,
					'strokeLinejoin' => true,
					'strokeWidth'    => true,
					'd'              => true,
				),
				'select'  => array(
					'class'  => true,
					'style'  => true,
					'data-*' => true,
				),
				'option'  => array(
					'value' => true,
				),
				'input'   => array(
					'name'   => true,
					'type'   => true,
					'class'  => true,
					'data-*' => true,
					'style'  => true,
					'value'  => true,
					'min'    => true,
					'max'    => true,
				),
				'checked' => array(),
			)
		);
		return apply_filters( 'revenue_kses_notice_allowed_tags', $allowed_tags );
	}


	/**
	 * Filters out the same tags as wp_kses_post, but allows tabindex for <a> element.
	 *
	 * @since 1.0.0
	 * @param string $message Content to filter through kses.
	 * @return string
	 */
	public function kses_campaign_view( $message ) {
		$allowed_tags = array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'svg'    => array(
					'xmlns'   => true,
					'width'   => true,
					'height'  => true,
					'fill'    => true,
					'viewbox' => true,
				),
				'path'   => array(
					'stroke'         => true,
					'strokeLinecap'  => true,
					'strokeLinejoin' => true,
					'strokeWidth'    => true,
					'd'              => true,
				),
				'select' => array(
					'class'  => true,
					'style'  => true,
					'data-*' => true,

				),
				'option' => array(
					'value' => true,
				),
				'input'  => array(
					'name'    => true,
					'type'    => true,
					'class'   => true,
					'data-*'  => true,
					'style'   => true,
					'value'   => true,
					'min'     => true,
					'max'     => true,
					'checked' => true,
				),
			)
		);

		/**
		 * Kses notice allowed tags.
		 *
		 * @since 3.9.0
		 * @param array[]|string $allowed_tags An array of allowed HTML elements and attributes, or a context name such as 'post'.
		 */
		return wp_kses( $message, apply_filters( 'revenue_kses_notice_allowed_tags', $allowed_tags ) );
	}

	/**
	 * Dropdown Variation attribute options
	 *
	 * @param array $generated_styles Generated styles.
	 * @param array $args Args
	 * @return string
	 */
	public function dropdown_variation_attribute_options( $generated_styles, $args = array() ) {
		$args = wp_parse_args(
			apply_filters( 'revenue_dropdown_variation_attribute_options_args', $args ),
			array(
				'options'          => false,
				'attribute'        => false,
				'product'          => false,
				'selected'         => false,
				'required'         => false,
				'name'             => '',
				'id'               => '',
				'class'            => '',
				'show_option_none' => __( 'Choose an option', 'revenue' ),
			)
		);

		$attribute_field_style = revenue()->get_style( $generated_styles, 'productAttrField' );

		// Get selected value.
		if ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {
			$selected_key = 'attribute_' . sanitize_title( $args['attribute'] );
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['product']->get_variation_default_attribute( $args['attribute'] );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		$options               = $args['options'];
		$product               = $args['product'];
		$attribute             = $args['attribute'];
		$name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
		$id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
		$class                 = $args['class'];
		$required              = (bool) $args['required'];
		$show_option_none      = (bool) $args['show_option_none'];
		$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'revenue' );

		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		$html  = '<select id="' . esc_attr( $id ) . '" class="revx-productAttr-wrapper__field revx-full-width ' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '"' . ( $required ? ' required' : '' ) . ' style="' . esc_attr( $attribute_field_style ) . '">';
		$html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

		if ( ! empty( $options ) ) {
			if ( $product && taxonomy_exists( $attribute ) ) {
				// Get terms if this is a taxonomy - ordered. We need the names too.
				$terms = wc_get_product_terms(
					$product->get_id(),
					$attribute,
					array(
						'fields' => 'all',
					)
				);

				foreach ( $terms as $term ) {
					if ( in_array( $term->slug, $options, true ) ) {
						$html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'revenue_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
					}
				}
			} else {
				foreach ( $options as $option ) {
					// This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
					$selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
					$html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'revenue_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
				}
			}
		}

		$html .= '</select>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses( $html, revenue()->get_allowed_tag() );
	}


	/**
	 * Get Product Category ids
	 *
	 * @param string $product_id Product id.
	 * @return array
	 */
	public function get_product_category_ids( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$category_ids = array();
			foreach ( $terms as $term ) {
				$category_ids[] = $term->term_id;
			}
			return $category_ids;
		}
		return array();
	}

	/**
	 * Get Offer Products Data.
	 *
	 * @param array $data Data.
	 * @return array
	 */
	public function getOfferProductsData( $data ) {
		$result = array();

		foreach ( $data as $order ) {
			// Ensure 'products' is an array of product IDs.
			if ( ! isset( $order['products'] ) || ! is_array( $order['products'] ) ) {
				continue;
			}

			// Process each product ID.
			foreach ( $order['products'] as $product_id ) {
				if ( ! $product_id ) {
					continue;
				}

				$regularPrice = false;
				$thumbnail    = false;
				$itemName     = '';

				// Fetch the product using WC function
				// Fetch the product using WC function.
				$product = wc_get_product( $product_id );

				// Ensure product was retrieved successfully.
				if ( ! $product ) {
					continue;
				}

				$regular_price    = (float) $product->get_regular_price( 'edit' );
				$quantity         = isset( $order['quantity'] ) ? (int) $order['quantity'] : 0;
				$discounted_price = $regular_price;
				$discount_amount  = isset( $order['value'] ) ? (float) $order['value'] : 0;

				// Calculate discounted price based on order type.
				if ( isset( $order['type'] ) ) {
					switch ( $order['type'] ) {
						case 'percentage':
							$discount_value   = $discount_amount;
							$discount_amount  = number_format( ( floatval( $regular_price ) * floatval( $discount_value ) ) / 100, 2 );
							$discounted_price = number_format( floatval( $regular_price ) - floatval( $discount_amount ), 2 );

							break;

						case 'fixed_discount':
							$discount_amount  = number_format( $discount_amount, 2 );
							$discounted_price = number_format( floatval( $regular_price ) - floatval( $discount_amount ), 2 );
							break;

						case 'free':
							$discounted_price = '0.00';
							$discount_amount  = '100%';
							break;

						case 'no_discount':
							$discount_amount  = '0';
							$discounted_price = number_format( $regular_price, 2 );
							break;
					}
				}

				// Prepare product data.
				$result[] = array(
					'item_id'       => $product_id,
					'item_name'     => $product->get_name(),
					'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ), // Get the product thumbnail URL.
					'regular_price' => number_format( $regular_price, 2 ),
					'sale_price'    => $discounted_price,
					'save'          => ( 'percentage' === $order['type'] ? ( isset( $order['value'] ) ? $order['value'] : '' ) . '%' : $discount_amount ),
					'quantity'      => $quantity,
					'type'          => $order['type'],
					'value'         => isset( $order['value'] ) ? $order['value'] : '',
					'isEnableTag'   => isset( $order['isEnableTag'] ) ? $order['isEnableTag'] : 'no',
				);
			}
		}

		return $result;
	}

	/**
	 * Get trigger products data.
	 *
	 * @param array  $triggers Triggers Data.
	 * @param string $relation Relation.
	 * @param string $trigger_product_id Trigger product id.
	 * @param string $is_current_product Is current product.
	 * @return array
	 */
	public function getTriggerProductsData( $triggers, $relation = 'or', $trigger_product_id = '', $is_current_product = '' ) {
		$result = array();
		if ( $is_current_product ) {
			$product          = wc_get_product( $trigger_product_id );
			$regular_price    = (float) $product->get_regular_price();
			$discounted_price = $regular_price;
			$discount_amount  = '0';
			$quantity         = 1;
			$result[]         = array(
				'item_id'       => $trigger_product_id,
				'item_name'     => $product->get_name(),
				'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ), // Get the product thumbnail URL.
				'regular_price' => number_format( $regular_price, 2 ),
				'sale_price'    => number_format( $discounted_price, 2 ),
				'save'          => $discount_amount,
				'quantity'      => $quantity,
				'type'          => 'percentage',
				'value'         => '0',
				'isEnableTag'   => 'no',
				'trigger'       => true,
			);

			return $result;
		}
		if ( ! is_array( $triggers ) ) {
			return $result;
		}

		foreach ( $triggers as $trigger ) {
			if ( 'or' === $relation ) {
				if ( $trigger_product_id && $trigger['item_id'] == $trigger_product_id ) { //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

					$product_id = isset( $trigger['item_id'] ) ? (int) $trigger['item_id'] : 0;
					$quantity   = isset( $trigger['item_quantity'] ) ? (int) $trigger['item_quantity'] : 1;

					$product = wc_get_product( $product_id );

					if ( ! $product ) {
						continue;
					}

					$regular_price    = (float) $product->get_regular_price();
					$discounted_price = $regular_price;
					$discount_amount  = '0';

					$result = array();

					$result[] = array(
						'item_id'       => $product_id,
						'item_name'     => $product->get_name(),
						'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ), // Get the product thumbnail URL.
						'regular_price' => number_format( $regular_price, 2 ),
						'sale_price'    => number_format( $discounted_price, 2 ),
						'save'          => $discount_amount,
						'quantity'      => $quantity,
						'type'          => 'percentage',
						'value'         => '0',
						'isEnableTag'   => 'no',
						'trigger'       => true,
					);

					return $result;
				}
			}
			$product_id = isset( $trigger['item_id'] ) ? (int) $trigger['item_id'] : 0;
			$quantity   = isset( $trigger['item_quantity'] ) ? (int) $trigger['item_quantity'] : 1;

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$regular_price    = (float) $product->get_regular_price();
			$discounted_price = $regular_price;
			$discount_amount  = '0';

			// Prepare product data.
			$result[] = array(
				'item_id'       => $product_id,
				'item_name'     => $product->get_name(),
				'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ), // Get the product thumbnail URL.
				'regular_price' => number_format( $regular_price, 2 ),
				'sale_price'    => number_format( $discounted_price, 2 ),
				'save'          => $discount_amount,
				'quantity'      => $quantity,
				'type'          => 'percentage',
				'value'         => '0',
				'isEnableTag'   => 'no',
				'trigger'       => true,
			);
		}

		return $result;
	}

	/**
	 * Calculate percentage difference.
	 *
	 * @param float|string $old_price Old Price.
	 * @param float|string $new_price New Price.
	 * @return float
	 */
	public function calculate_percentage_difference( $old_price, $new_price ) {
		if ( 0 == $old_price ) { //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			return 0; // Avoid division by zero.
		}
		$difference = $old_price - $new_price;
		$per_diff   = ( $difference / $old_price ) * 100;
		return number_format( $per_diff, 2 );
	}

	/**
	 * Get page url buy name
	 *
	 * @param string $name Page name.
	 * @return string
	 */
	public function get_page_url( $name = '' ) {
		$slug = revenue()->get_admin_menu_slug();

		return esc_url( admin_url( 'admin.php?page=' . $slug . '#/' . $name ) );
	}

	/**
	 * Get edit campaign url
	 *
	 * @param string $campaign_id campaign id.
	 * @return string
	 */
	public function get_edit_campaign_url( $campaign_id = '' ) {
		$slug = revenue()->get_admin_menu_slug();

		return esc_url( admin_url( 'admin.php?page=' . $slug . '#/campaigns/' . $campaign_id ) );
	}

	// Licensing System.

	/**
	 * Check is pro installed or not
	 */
	public function is_pro_installed() {
		return in_array( 'revenue-pro/revenue-pro.php', array_keys( get_plugins() ), true );
	}

	/**
	 * Check pro ready or not
	 *
	 * @return boolean
	 */
	public function is_pro_ready() {

		return function_exists( 'is_plugin_active' ) && is_plugin_active( 'revenue-pro/revenue-pro.php' );
	}

	/**
	 * Check is pro active or not
	 *
	 * @return boolean
	 */
	public function is_pro_active() {
		$licese_data = get_option( 'edd_revenue_license_data', array() );
		return $this->is_pro_ready() && isset( $licese_data['license'] ) && 'valid' === $licese_data['license'];
	}

	/**
	 * Get price data.
	 *
	 * @param string       $offer_type Offer type.
	 * @param string|float $offer_value Offer value.
	 * @param int|string   $offer_qty Offer qty.
	 * @return string
	 */
	public function get_price_data( $offer_type, $offer_value, $offer_qty ) {
		$save_data = '';
		switch ( $offer_type ) {
			case 'percentage':
				$save_data = 'Save ' . $offer_value . '%';
				break;
			case 'fixed_discount':
				/* translators: %s: Discount percentage value */
				$save_data = sprintf( __( 'Save $%s', 'revenue' ), $offer_value );
				break;
			case 'amount':
			case 'fixed_price':
				$save_data = wc_price( intval( $offer_qty ) * floatval( $offer_value ) );
				break;
			case 'no_discount':
				break;
			case 'free':
				$save_data = 'Free';
				break;
			default:
				$save_data = '';
		}
		return $save_data;
	}


	/**
	 * Is block based cart page.
	 *
	 * @return boolean
	 */
	public function is_block_based_cart_page() {
		return has_block( 'woocommerce/cart', intval( get_option( 'woocommerce_cart_page_id' ) ) );
	}

	/**
	 * Is block based checkout page.
	 *
	 * @return boolean
	 */
	public function is_block_based_checkout_page() {
		return has_block( 'woocommerce/checkout', intval( get_option( 'woocommerce_checkout_page_id' ) ) );
	}

	/**
	 * Get campaign position default values.
	 *
	 * @param string $page Page.
	 * @return array
	 */
	public function get_campaign_position_default_values( $page = '' ) {
		$is_cart_page_use_block = has_block( 'woocommerce/cart', intval( get_option( 'woocommerce_cart_page_id' ) ) );

		$is_checkout_page_use_block = has_block( 'woocommerce/checkout', intval( get_option( 'woocommerce_checkout_page_id' ) ) );

		$data = array(
			'product_page'  => array(
				'campaign_inpage_position' => 'before_add_to_cart_form',
			),
			'cart_page'     => array(
				'campaign_inpage_position' => $is_cart_page_use_block ? 'before_content' : 'before_cart',
			),
			'checkout_page' => array(
				'campaign_inpage_position' => $is_checkout_page_use_block ? 'before_content' : 'before_checkout_form',
			),
			'thankyou_page' => array(
				'campaign_inpage_position' => 'before_thankyou',
			),
		);

		return apply_filters( 'revenue_campaign_positions_default_value', $data );
	}


	/**
	 * Check if a specific product ID exists in the WooCommerce cart.
	 *
	 * @param int $campaign_id The campaign id.
	 * @param int $product_id The product ID to check.
	 * @return bool True if the product is in the cart, false otherwise.
	 */
	public function is_product_in_cart( $campaign_id, $product_id ) {
		// Load cart product IDs only once.
		if ( is_null( self::$cart_product_ids ) ) {
			self::load_cart_product_ids();
		}

		// Check if the given product ID exists in the cart.
		return isset( self::$cart_product_ids[ $campaign_id ][ $product_id ] );
	}

	/**
	 * Load product IDs from the cart.
	 */
	private static function load_cart_product_ids() {
		$cart_items             = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart() : array();
		self::$cart_product_ids = array();

		foreach ( $cart_items as $cart_item ) {
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				self::$cart_product_ids[ $cart_item['revx_campaign_id'] ][ $cart_item['product_id'] ] = true;
			}
		}
	}

	/**
	 * Is hide campaign
	 *
	 * @param int|string $campaign_id Campaign id.
	 * @return boolean
	 */
	public function is_hide_campaign( $campaign_id, $campaign_type = '' ) {
		$is_hide = 'hide_campaign' === revenue()->get_campaign_meta( $campaign_id, 'offered_product_on_cart_action', true );

		$is_hidden_appicable = false;
		switch ( $campaign_type ) {
			case 'normal_discount':
			case 'bundle_discount':
			case 'buy_x_get_y':
			case 'volume_discount':
			case 'mix_match':
			case 'frequently_bought_together':
				$is_hidden_appicable = true;
				break;
			case 'spending_goal':
				$is_hidden_appicable = false;
				break;
			case 'free_shipping_bar':
				$is_hidden_appicable = false;
				break;

			default:
				break;
		}
		return $is_hide && $this->is_campaign_on_cart( $campaign_id ) && $is_hidden_appicable;
	}

	/**
	 * Is Hide product
	 *
	 * @param int|string $campaign_id Campaign ID.
	 * @param int|string $product_id Product ID.
	 * @return boolean
	 */
	public function is_hide_product( $campaign_id, $product_id ) {
		$is_hide = 'hide_products' === revenue()->get_campaign_meta( $campaign_id, 'offered_product_on_cart_action', true );
		return $is_hide && $this->is_product_in_cart( $campaign_id, $product_id );
	}

	/**
	 * Is campaign or cart.
	 *
	 * @param string|int $campaign_id Campaign id.
	 * @return boolean
	 */
	public function is_campaign_on_cart( $campaign_id ) {
		// Load cart product IDs only once.
		if ( is_null( self::$cart_product_ids ) ) {
			self::load_cart_product_ids();
		}
		return isset( self::$cart_product_ids[ $campaign_id ] ) && ! empty( self::$cart_product_ids[ $campaign_id ] );
	}

	/**
	 * Get Placement Settings
	 *
	 * @param int|string $campaign_id Campaign ID.
	 * @param string     $placement Placement.
	 * @param string     $key Key.
	 * @return array
	 */
	public function get_placement_settings( $campaign_id, $placement = '', $key = '' ) {

		if ( empty( $placement ) ) {
			$placement = $this->get_current_page();
		}

		$campaign = revenue()->get_campaign_data( $campaign_id );
		if ( isset( $campaign['campaign_placement'] ) && 'multiple' != $campaign['campaign_placement'] ) {
			$campaign['placement_settings'] = array(
				$campaign['campaign_placement'] => array(
					'page'                     => $campaign['campaign_placement'],
					'status'                   => 'yes',
					'display_style'            => $campaign['campaign_display_style'],
					'builder_view'             => $campaign['campaign_builder_view'],
					'inpage_position'          => $campaign['campaign_inpage_position'],
					'popup_animation'          => $campaign['campaign_popup_animation'],
					'popup_animation_delay'    => $campaign['campaign_popup_animation_delay'],
					'floating_position'        => $campaign['campaign_floating_position'] ?? 'top_left',
					'floating_animation_delay' => $campaign['campaign_floating_animation_delay'],
				),
			);

			$campaign['placement_settings'] = $campaign['placement_settings'];
		}

		$placement_settings = $campaign['placement_settings'];

		if ( ! empty( $placement ) ) {
			$placement_settings = $placement_settings[ $placement ] ?? array();

			if ( empty( $placement_settings ) && isset( $campaign['placement_settings']['all_page'] ) && 'yes' === $campaign['placement_settings']['all_page']['status'] ) {
				$placement_settings = $campaign['placement_settings']['all_page'];
			}

			if ( ! empty( $key ) ) {
				$placement_settings = $placement_settings[ $key ] ?? '';
			}
		}

		return $placement_settings;
	}


	/**
	 * Get current page is what (product,cart or checkout page)
	 *
	 * @return string
	 */
	public function get_current_page() {
		$which_page = '';
		if ( is_product() ) {
			$which_page = 'product_page';
		} elseif ( is_cart() ) {
			$which_page = 'cart_page';
		} elseif ( is_checkout() ) {
			$which_page = 'checkout_page';
		} elseif ( is_shop() ) {
			$which_page = 'shop_page';
		} elseif ( is_order_received_page() ) {
			$which_page = 'thankyou_page';
		} elseif ( is_account_page() ) {
			$which_page = 'my_account';
		}

		return $which_page;
	}

	/**
	 * Is Custom order table usages enabled or not
	 *
	 * @return boolean
	 */
	public function is_custom_orders_table_usages_enabled() {
		return OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Retrieve an SVG file content from the assets folder.
	 *
	 * @param string $filename The name of the SVG file (without extension).
	 * @param string $subfolder Optional. A subfolder within the assets directory (e.g., "icons").
	 * @return string|false The SVG content if found, or false if the file doesn't exist or isn't valid.
	 */
	public function get_svg_from_assets( $filename, $subfolder = '' ) {
		// Define the base assets path.
		$base_dir = REVENUE_PATH . 'assets/images/icons';

		// Sanitize the filename and subfolder to prevent directory traversal.
		$sanitized_filename  = sanitize_file_name( $filename ) . '.svg';
		$sanitized_subfolder = sanitize_file_name( $subfolder );

		// Build the full path to the SVG file.
		$filepath = trailingslashit( $base_dir );
		if ( ! empty( $sanitized_subfolder ) ) {
			$filepath .= trailingslashit( $sanitized_subfolder );
		}
		$filepath .= $sanitized_filename;

		// Check if the file exists and is an SVG.
		if ( ! file_exists( $filepath ) || strtolower( pathinfo( $filepath, PATHINFO_EXTENSION ) ) !== 'svg' ) {
			return false; // File not found or not an SVG.
		}

		// Validate the file contents for security.
		$svg_content = file_get_contents( $filepath );
		if ( ! $svg_content || stripos( $svg_content, '<svg' ) === false ) {
			return false; // Invalid or corrupted SVG file.
		}

		// Return the SVG content, safe to output.
		return $svg_content;
	}

	public function get_buyx_gety_individual_product_quantity_trigger_types() {

		$trigger_types = array( 'products' );

		return apply_filters( 'revenue_get_buy_x_get_y_individual_product_quantity_trigger_types', $trigger_types );
	}

	public function get_mix_match_required_product_trigger_types() {

		$trigger_types = array( 'products' );

		return apply_filters( 'revenue_get_mix_match_required_product_trigger_types', $trigger_types );
	}


	public function get_trigger_placeholder_message() {
		$trigger_messages = array(
			'products' => __( 'Search and select products...', 'revenue' ),
			'category' => __( 'Search and select categories...', 'revenue' ),
		);

		return apply_filters( 'revenue_trigger_items_placeholder_messages', $trigger_messages );
	}

	public function get_offered_items_type() {

		$types = array(
			'products'     => 'products',
			'all_products' => 'products',
			'category'     => 'products',
		);

		return apply_filters( 'revenue_offered_items_type', $types );
	}

	public function get_selected_trigger_item_suffix() {

		// type => suffix
		$suffix = array(
			'products' => __( 'Product', 'revenue' ),
			'category' => __( 'Category', 'revenue' ),
		);

		return apply_filters( 'revenue_trigger_selected_item_suffix', $suffix );
	}

	public function get_selected_offer_item_suffix() {

		// type => suffix
		$suffix = array(
			'products' => __( 'Product', 'revenue' ),
			'category' => __( 'Category', 'revenue' ),
		);

		return apply_filters( 'revenue_offer_selected_item_suffix', $suffix );
	}

	public function get_item_not_found_messages() {

		$messages = array(
			'products' => __( 'Oops, Product not found!', 'revenue' ),
			'category' => __( 'Oops, Category not found', 'revenue' ),
		);

		return apply_filters( 'revenue_get_item_not_found_messages', $messages );
	}

	public function get_template( $part_name, $args ) {
		echo wp_kses( revenue()->get_template_part( $part_name, $args ), revenue()->get_allowed_tag() );
	}

	public function is_show_bundle_with_trigger_product() {
		$data = array(
			'products',
			'category',
			'all_products',
		);

		return apply_filters( 'revenue_is_show_bundle_with_trigger_product', $data );
	}

	public function get_campaign_list_trigger_row() {
		$data = array(
			'products' => array(
				'singular' => 'Product',
				'plural'   => 'Products',
			),
			'category' => array(
				'singular' => 'Category',
				'plural'   => 'Categories',
			),
		);

		return apply_filters( 'revenue_get_campaign_list_trigger_row_message', $data );
	}


	public function show_quantity_selector_on_campaigns() {

		$allowed_campaign_types = array(
			'normal_discount',
			'mix_match',
			'bundle_discount',
			'frequently_bought_together',
			'buy_x_get_y',
		);

		return apply_filters( 'revenue_get_show_quantity_selector_on_campaigns', $allowed_campaign_types );
	}

	/**
	 * Get campaign default placement page
	 *
	 * @param string $campaign_type Campaign Type.
	 */
	public function get_campaign_default_placement( $campaign_type = false ) {
		$data = array(
			'normal_discount'            => 'product_page',
			'buy_x_get_y'                => 'product_page',
			'volume_discount'            => 'product_page',
			'frequently_bought_together' => 'product_page',
			'mix_match'                  => 'product_page',
			'bundle_discount'            => 'product_page',
			'double_order'               => 'checkout_page',
			'spending_goal'              => 'product_page',
			'free_shipping_bar'          => 'product_page',
			'stock_scarcity'             => 'product_page',
			'countdown_timer'            => 'product_page',
			'next_order_coupon'          => 'thankyou_page',
		);

		$data = apply_filters( 'revenue_get_campaign_default_placement', $data );

		return $campaign_type ? $data[ $campaign_type ] : $data;
	}

	public function get_campaign_placement_options( $campaign_type, $campaign_page = '' ) {
		$data = array(
			'normal_discount' => array(
				'product_page'  => 'before_add_to_cart_form',
				'cart_page'     => 'before_content',
				'checkout_page' => 'before_content',
				'thankyou_page' => 'before_thankyou',
			),
			'normal_discount' => array(
				'product_page'  => 'before_add_to_cart_form',
				'cart_page'     => 'before_content',
				'checkout_page' => 'before_content',
				'thankyou_page' => 'before_thankyou',
			),
		);
	}


	/**
	 * Get campaign counts
	 *
	 * @return mixed
	 */
	public function get_campaign_counts() {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$res = $wpdb->get_row(
			"SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN campaign_type = 'normal_discount' THEN 1 ELSE 0 END) AS normal_discount,
                SUM(CASE WHEN campaign_type = 'volume_discount' THEN 1 ELSE 0 END) AS volume_discount,
                SUM(CASE WHEN campaign_type = 'bundle_discount' THEN 1 ELSE 0 END) AS bundle_discount,
                SUM(CASE WHEN campaign_type = 'buy_x_get_y' THEN 1 ELSE 0 END) AS buy_x_get_y,
				SUM(CASE WHEN campaign_type = 'stock_scarcity' THEN 1 ELSE 0 END) AS stock_scarcity,
                SUM(CASE WHEN campaign_type = 'free_shipping_bar' THEN 1 ELSE 0 END) AS free_shipping_bar,
                SUM(CASE WHEN campaign_type = 'next_order_coupon' THEN 1 ELSE 0 END) AS next_order_coupon,
                SUM(CASE WHEN campaign_type = 'countdown_timer' THEN 1 ELSE 0 END) AS countdown_timer
            FROM {$wpdb->prefix}revenue_campaigns;"
		); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $res;
	}
}
