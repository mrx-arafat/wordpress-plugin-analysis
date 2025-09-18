<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
namespace Revenue;

use WC_Shipping_Free_Shipping;
use DateTime;

/**
 * Revenue Campaign
 *
 * @hooked on init
 */
class Revenue_Campaign {

	/**
	 * Contains campaigns
	 *
	 * @var array
	 */
	public $campaigns = array();

	/**
	 * Contain stock scarcities
	 *
	 * @var array
	 */
	public $stock_scacities = array();

	/**
	 * Contain campaign additional data
	 *
	 * @var array
	 */
	public $campaign_additional_data = array();

	/**
	 * Contain countdown data
	 *
	 * @var array
	 */
	public $countdown_data = array();

	/**
	 * Contain animated button data.
	 *
	 * @var array
	 */
	public $animated_button_data = array();

	/**
	 * Contain free shipping status
	 *
	 * @var boolean
	 */
	public $is_free_shipping = false;

	/**
	 * Contain spending goals
	 *
	 * @var array
	 */
	public $spending_goals = array();

	/**
	 * Contain status of is script already enqueued or not.
	 *
	 * @var boolean
	 */
	public $is_enqueue_data_already = false;

	/**
	 * Contain is already render or not
	 *
	 * @var integer
	 */
	public $is_already_render = 0;

	/**
	 * Contain rendered campaings
	 *
	 * @var array
	 */
	public $renderd_campaigns = array();
	/**
	 * Flag to avoid infinite loops when removing a bundle parent via a child.
	 *
	 * @var string
	 */
	protected $removing_container_key = null;


	/**
	 * Contain revenue price
	 *
	 * @var array
	 */
	public $revenue_price = array();

	/**
	 * Checkout filters
	 *
	 * @var array
	 */
	private $checkout_filters = array(
		'revenue_block_checkout_before_order_review',
		'revenue_block_add_text_after_billing_form',
		'revenue_block_before_checkout_billing_form',
		'revenue_block_before_checkout_form',
		'revenue_block_review_order_before_payment',
		'revenue_block_review_order_after_payment',
		'revenue_block_after_checkout_form',
	);

	/**
	 * Cart filters
	 *
	 * @var array
	 */
	private $cart_filters = array(
		'revenue_block_before_cart_table',
		'revenue_block_after_cart_table',
		'revenue_block_before_cart_form',
		'revenue_block_after_cart_form',
		'revenue_block_before_cart_totals',
		'revenue_block_after_cart_totals',
		'revenue_block_proceed_to_checkout',
	);

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'revenue_item_added_to_cart', array( $this, 'after_add_to_cart' ), 10, 3 );

		add_action( 'woocommerce_remove_cart_item', array( $this, 'after_remove_cart_item' ), 10, 2 );

		add_action( 'woocommerce_cart_item_restored', array( $this, 'after_cart_item_restored' ), 10, 2 );

		add_action( 'woocommerce_cart_emptied', array( $this, 'after_cart_emptied' ) );

		add_action( 'woocommerce_add_to_cart', array( $this, 'woocommerce_after_add_to_cart' ), 10, 6 );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_before_calculate_totals' ) );

		add_action( 'woocommerce_check_cart_items', array( $this, 'woocommerce_check_cart_items' ) );

		add_action( 'woocommerce_cart_item_remove_link', array( $this, 'woocommerce_cart_item_remove_link' ), 10, 2 );

		add_action( 'woocommerce_cart_item_quantity', array( $this, 'woocommerce_cart_item_quantity' ), 10, 2 );

		add_action( 'woocommerce_cart_item_class', array( $this, 'woocommerce_cart_item_class' ), 10, 2 );

		add_action( 'woocommerce_cart_item_subtotal', array( $this, 'woocommerce_cart_item_subtotal' ), 10, 2 );

		add_action( 'woocommerce_cart_item_price', array( $this, 'woocommerce_cart_item_price' ), 10, 2 );

		add_action( 'woocommerce_get_item_data', array( $this, 'woocommerce_get_item_data' ), 10, 2 );

		add_action( 'woocommerce_cart_item_name', array( $this, 'woocommerce_cart_item_name' ), 10, 2 );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'woocommerce_after_cart_item_quantity_update' ), 10, 2 );

		add_action( 'woocommerce_store_api_product_quantity_minimum', array( $this, 'woocommerce_store_api_product_quantity_minimum' ), 10, 3 );

		add_action( 'woocommerce_store_api_product_quantity_maximum', array( $this, 'woocommerce_store_api_product_quantity_maximum' ), 10, 3 );

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'woocommerce_checkout_create_order' ), 10 );

		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'woocommerce_checkout_create_order' ), 10 );

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'woocommerce_checkout_create_order_line_item' ), 10, 4 );

		add_action( 'woocommerce_hidden_order_itemmeta', array( $this, 'woocommerce_hidden_order_itemmeta' ) );

		add_shortcode( revenue()->get_campaign_shortcode_tag(), array( $this, 'render_campaign_view_shortcode' ) );

		add_filter( 'woocommerce_package_rates', array( $this, 'handle_free_shipping' ), 10, 2 );

		add_action( 'wp', array( $this, 'run_all_page_campaigns' ) );

		add_action( 'wp_print_scripts', array( $this, 'localize_script' ) );

		add_action( 'revenue_campaign_before_header', array( $this, 'add_edit_campaign_link' ) );

		/**
		 * Commenting this hook to compatibility with woocommerce subscription plugin. without this hook, works fine.
		 */
		// add_filter( 'woocommerce_cart_get_subtotal', array( $this, 'get_cart_contents_total' ) );

		// add_filter( 'woocommerce_cart_get_cart_contents_total', array( $this, 'get_cart_contents_total' ) );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'recalculate_fees' ), 99 );

		$this->subscriptions_plugin_integrations();

		// add_action( 'woocommerce_calculate_totals', array( $this, 'remove_price_filter' ) );
		// add_action( 'woocommerce_after_calculate_totals', array( $this, 'remove_price_filter' ) );

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragment' ) );

		add_filter( 'woocommerce_blocks_checkout_block_registration', array( $this, 'custom_checkout_text_injection' ) );
		add_filter( 'woocommerce_blocks_cart_block_registration', array( $this, 'custom_cart_text_injection' ) );

		// Register all revenue block filters dynamically.
		foreach ( $this->checkout_filters as $filter ) {
			add_filter( $filter, array( $this, 'revenue_block_handler' ) );
		}
		// Register all revenue block filters dynamically.
		foreach ( $this->cart_filters as $filter ) {
			add_filter( $filter, array( $this, 'revenue_cart_block_handler' ) );
		}
	}

	/**
	 * Custom cart text injection for WooCommerce Cart blocks.
	 *
	 * @param array $block_registry Block registry.
	 * @return array
	 */
	public function custom_cart_text_injection( $block_registry ) {
		// Map WooCommerce blocks to their corresponding revenue filters.
		$block_mappings = array(
			'woocommerce/cart-line-items-block'     => array(
				'filters' => array(
					'revenue_block_before_cart_table' => 'before',
					'revenue_block_after_cart_table'  => 'after',
				),
			),
			'woocommerce/filled-cart-block'         => array(
				'filters' => array(
					'revenue_block_before_cart_form' => 'before',
					'revenue_block_after_cart_form'  => 'after',
				),
			),
			'woocommerce/cart-order-summary-block'  => array(
				'filters' => array(
					'revenue_block_before_cart_totals'  => 'before',
					'revenue_block_proceed_to_checkout' => 'after',
				),
			),
			'woocommerce/proceed-to-checkout-block' => array(
				'filter'   => 'revenue_block_after_cart_totals',
				'position' => 'after',
			),
		);
		foreach ( $block_mappings as $block_name => $config ) {
			if ( isset( $config['filters'] ) ) {
				// Handle blocks with multiple filters.
				foreach ( $config['filters'] as $filter => $position ) {
					add_filter(
						"render_block_{$block_name}",
						function ( $content, $block, $instance ) use ( $filter, $position ) {
							return $this->inject_content_checkout_block( $content, $filter, $position );
						},
						10,
						3
					);
				}
			} else {
				// Handle blocks with single filter.
				add_filter(
					"render_block_{$block_name}",
					function ( $content, $block, $instance ) use ( $config ) {
						return $this->inject_content_checkout_block( $content, $config['filter'], $config['position'] );
					},
					10,
					3
				);
			}
		}
		return $block_registry;
	}

	/**
	 * Custom checkout text injection for WooCommerce Checkout blocks.
	 *
	 * @param array $block_registry Block registry.
	 * @return array
	 */
	public function custom_checkout_text_injection( $block_registry ) {
		// Map WooCommerce blocks to their corresponding revenue filters.
		$block_mappings = array(
			'woocommerce/checkout-order-summary-cart-items-block' => array(
				'filter'   => 'revenue_block_checkout_before_order_review',
				'position' => 'before',
			),
			'woocommerce/checkout-billing-address-block' => array(
				'filter'   => 'revenue_block_add_text_after_billing_form',
				'position' => 'before',
			),
			'woocommerce/checkout-contact-information-block' => array(
				'filter'   => 'revenue_block_before_checkout_billing_form',
				'position' => 'before',
			),
			'woocommerce/checkout'                       => array(
				'filters' => array(
					'revenue_block_before_checkout_form' => 'before',
					'revenue_block_after_checkout_form'  => 'after',
				),
			),
			'woocommerce/checkout-payment-block'         => array(
				'filters' => array(
					'revenue_block_review_order_before_payment' => 'before',
					'revenue_block_review_order_after_payment' => 'after',
				),
			),
		);

		foreach ( $block_mappings as $block_name => $config ) {
			if ( isset( $config['filters'] ) ) {
				// Handle blocks with multiple filters.
				foreach ( $config['filters'] as $filter => $position ) {
					add_filter(
						"render_block_{$block_name}",
						function ( $content, $block, $instance ) use ( $filter, $position ) {
							return $this->inject_content_checkout_block( $content, $filter, $position );
						},
						10,
						3
					);
				}
			} else {
				// Handle blocks with single filter.
				add_filter(
					"render_block_{$block_name}",
					function ( $content, $block, $instance ) use ( $config ) {
						return $this->inject_content_checkout_block( $content, $config['filter'], $config['position'] );
					},
					10,
					3
				);
			}
		}

		return $block_registry;
	}

	/**
	 * Inject content into the block content based on the filter name and position.
	 *
	 * @param string $block_content The original block content.
	 * @param string $filter_name   The filter name to apply.
	 * @param string $position      The position to inject content ('before' or 'after').
	 * @return string Modified block content with injected content.
	 */
	private function inject_content_checkout_block( $block_content, $filter_name, $position ) {
		$revx_checkout_content = apply_filters( $filter_name, '' );
		if ( 'before' === $position ) {
			return $revx_checkout_content . $block_content;
		} else {
			return $block_content . $revx_checkout_content;
		}
	}

	/**
	 * Handle revenue Checkout block filters.
	 *
	 * @return string
	 */
	public function revenue_block_handler() {
		$filter_name = current_filter();

		// Map filter names to position names.
		$position_map = array(
			'revenue_block_checkout_before_order_review' => 'checkout_before_order_review',
			'revenue_block_add_text_after_billing_form'  => 'after_checkout_billing_form',
			'revenue_block_before_checkout_billing_form' => 'before_checkout_billing_form',
			'revenue_block_before_checkout_form'         => 'before_checkout_form',
			'revenue_block_review_order_before_payment'  => 'review_order_before_payment',
			'revenue_block_review_order_after_payment'   => 'review_order_after_payment',
			'revenue_block_after_checkout_form'          => 'after_checkout_form',
		);

		$position = $position_map[ $filter_name ] ?? '';
		ob_start();
		$this->checkout_common_position_callback( $position, 'checkout_page' );
		return ob_get_clean();
	}

	/**
	 * Handle revenue cart block filters.
	 *
	 * @return string
	 */
	public function revenue_cart_block_handler() {
		$filter_name = current_filter();

		// Map filter names to position names.
		$position_map = array(
			'revenue_block_before_cart_table'   => 'before_cart_table',
			'revenue_block_after_cart_table'    => 'after_cart_table',
			'revenue_block_before_cart_form'    => 'before_cart',
			'revenue_block_after_cart_form'     => 'after_cart',
			'revenue_block_before_cart_totals'  => 'before_cart_totals',
			'revenue_block_after_cart_totals'   => 'after_cart_totals',
			'revenue_block_proceed_to_checkout' => 'proceed_to_checkout',
		);

		$position = $position_map[ $filter_name ] ?? '';
		ob_start();
		$this->checkout_common_position_callback( $position, 'cart_page' );
		return ob_get_clean();
	}

	/**
	 * Callback for checkout common position.
	 *
	 * @param string $position Position.
	 */
	public function checkout_common_position_callback( $position = '', $which_page = 'checkout_page' ) {
		global $product;
		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
				$campaigns  = revenue()->get_available_campaigns( $product_id, $which_page, 'inpage', $position );
				if ( ! empty( $campaigns ) ) {
					// Run campaigns for the product in the checkout page.
					$this->run_campaigns( $campaigns, 'inpage', $which_page, $position );
					break; // Break after running campaigns for the first product.
				}
			}
		}
	}

	/**
	 * Cart fragment
	 *
	 * @param array $fragment Fragment.
	 * @return array
	 */
	public function cart_fragment( $fragment ) {

		WC()->cart->calculate_totals();

		$cart_total    = 0;
		$cart_subtotal = 0;

		if ( wc_prices_include_tax() ) {
			$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		} else {
			$cart_total = WC()->cart->get_cart_contents_total();
		}

		// $shipping_total = 0;

		// if ( WC()->cart->display_prices_including_tax() ) {
		// $shipping_total = WC()->cart->shipping_total + WC()->cart->shipping_tax_total ;
		// } else {
		// $shipping_total = WC()->cart->shipping_total;
		// }

		// $cart_total += $shipping_total;

		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		} else {
			$cart_subtotal = WC()->cart->get_subtotal();
		}

		$fragment['subtotal'] = $cart_subtotal;
		$fragment['total']    = $cart_total;
		return $fragment;
	}


	/**
	 * Recalculate Fees
	 *
	 * @return void
	 */
	public function recalculate_fees() {
		// Avoid infinite loop by checking if totals are already calculated.
		static $recalculating = false;

		// Prevent further recalculation if it's already happening.
		if ( $recalculating ) {
			return;
		}

		// Set flag to indicate we're recalculating.
		$recalculating = true;

		// Ensure the cart is updated, and check if it's in the front-end or during AJAX request.
		if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Check if the cart contains the specific product.
		$contains_specific_item = false;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$contains_specific_item = true;
				break;
			}
		}

		// Only recalculate if the specific item is found in the cart.
		if ( $contains_specific_item ) {
			// Recalculate taxes (force the cart to recalculate totals).
			WC()->cart->calculate_totals();  // This triggers recalculation of taxes.
			WC()->cart->calculate_shipping(); // This triggers recalculation of shipping costs.
		}

		// Reset the flag after the recalculation.
		$recalculating = false;
	}


	/**
	 * Get cart contents total
	 *
	 * @param string|float $sub_total Subtotal.
	 * @return mixed
	 */
	public function get_cart_contents_total( $sub_total ) {
		$total               = 0;
		$has_revenue_product = false;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			// Revenue Cart Item: Added through revenue (checking for campaign data).
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id   = $cart_item['revx_campaign_id'];

				/**
				 * Hook for updating the price on the cart from several campaigns
				 * Valid Campaign Types:
				 * - normal_discount
				 * - bundle_discount
				 * - volume_discount
				 * - buy_x_get_y
				 * - mix_match
				 * - frequently_bought_together
				 * - spending_goal
				 */

				// Fire the appropriate action based on the campaign type.
				do_action( "revenue_campaign_{$campaign_type}_before_calculate_cart_totals", $cart_item, $campaign_id, WC()->cart );
				$has_revenue_product = true;
			}
			$total += floatval( $cart_item['data']->get_price() ) * $cart_item['quantity'];
		}
		if ( $total && $has_revenue_product ) {
			if ( WC()->cart->display_prices_including_tax() ) {
				return $total - WC()->cart->get_subtotal_tax();
			} else {
				return $total;
			}
		}
		return $sub_total;
	}


	/**
	 * Add Edit Campaign link
	 *
	 * @param string $campaign_id Campaign Id.
	 * @return void
	 */
	public function add_edit_campaign_link( $campaign_id ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		if ( ! $has_permission ) {
			return;
		}
		?>
		<a class="revx-admin-edit" target="_blank" href="<?php echo esc_url( revenue()->get_edit_campaign_url( $campaign_id ) ); ?>"><?php echo esc_html__( 'Edit Campaign', 'revenue' ); ?></a>
		<?php
	}

	/**
	 * Force recalculate shipping charge
	 *
	 * @return void
	 */
	public function force_recalculate_shipping() {
		WC()->cart->calculate_shipping();
	}


	/**
	 * Localize script
	 *
	 * @return void
	 */
	public function localize_script() {

		$campaign_localize_data = array(
			'ajax'                         => admin_url( 'admin-ajax.php' ),
			'nonce'                        => wp_create_nonce( 'revenue-add-to-cart' ),
			'user'                         => get_current_user_id(),
			'data'                         => $this->campaign_additional_data,
			'currency_format_num_decimals' => wc_get_price_decimals(),
			'currency_format_symbol'       => get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'  => wc_get_price_decimal_separator(),
			'currency_format_thousand_sep' => wc_get_price_thousand_separator(),
			'currency_format'              => get_woocommerce_price_format(),
			'checkout_page_url'            => wc_get_checkout_url(),
		);

		if ( ! empty( $this->campaign_additional_data ) ) {
			$this->is_enqueue_data_already = true;
		}
		wp_localize_script( 'revenue-campaign', 'revenue_campaign', $campaign_localize_data );
	}


	/**
	 * Run all page campaigns.
	 *
	 * @return void
	 */
	public function run_all_page_campaigns() {

		$which_page = '';
		if ( is_product() ) {
			$which_page = 'product_page';
		} elseif ( is_cart() ) {
			$which_page = 'cart_page';
		} elseif ( is_checkout() ) {
			$which_page = 'checkout_page';
		} elseif ( is_shop() ) {
			$which_page = 'shop_page';
		}

		if ( ! empty( $which_page ) ) {

			$positions = revenue()->get_campaign_inpage_positions();

			$inpage_positions = isset( $positions[ $which_page ] ) ? array_keys( $positions[ $which_page ] ) : array();
			// echo '<pre>'; print_r($inpage_positions); echo '</pre>';
			// foreach ( $inpage_positions as $position ) {
			// if ( method_exists( $this, $position ) ) {
			// add_action( 'woocommerce_' . $position, array( $this, $position ), 1 );
			// }
			// }
			foreach ( $inpage_positions as $position ) {
				if ( strpos( $position, 'rvex_' ) === 0 ) {
					// Handle positions with the 'rvex_' prefix.
					add_action( $position, array( $this, $position ) );
				} elseif ( method_exists( $this, $position ) ) {
					// Handle other positions.
					add_action( 'woocommerce_' . $position, array( $this, $position ), 1 );
				}
			}

			if ( 'checkout_page' === $which_page ) {
				add_action( 'woocommerce_before_thankyou', array( $this, 'before_thankyou' ) );
				add_action( 'woocommerce_thankyou', array( $this, 'thankyou' ) );
			}
			if ( 'cart_page' === $which_page ) {
				add_action( 'woocommerce_after_cart_item_name', array( $this, 'cart_item_name' ), 10, 2 );
				add_filter( 'woocommerce_cart_item_price', array( $this, 'rvex_add_text_after_cart_item_price' ), 10, 3 );
			}
			if ( 'product_page' === $which_page ) {
				// This Hook is used to add the campaign Stock Scarcity in the product page.
				add_action( 'rvex_below_the_product_title', array( $this, 'rvex_below_the_product_title' ) );
				add_action( 'rvex_below_the_product_price', array( $this, 'rvex_below_the_product_price' ) );
				add_action( 'woocommerce_before_add_to_cart_quantity', array( $this, 'before_add_to_cart_quantity' ) );
			}
			if ( 'my_account' === $which_page ) {
				// This Hook is used to add the campaign Stock Scarcity in the my account page.
				add_action( 'rvex_above_my_account', array( $this, 'rvex_above_my_account' ) );
				add_action( 'rvex_below_my_account', array( $this, 'rvex_below_my_account' ) );
			}
		}

		add_action( 'woocommerce_review_order_before_submit', array( $this, 'review_order_before_submit' ) );
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'review_order_before_payment' ) );
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'review_order_before_shipping' ) );

		/**
		 * Filters the post content.
		 *
		 * @param string $content Content of the current post.
		 * @return string Content of the current post.
		 */
		add_filter( 'the_content', array( $this, 'run_cart_checkout_block_campaigns' ) );

		add_action( 'wp_head', array( $this, 'run_hellobar_campaings' ) );
		add_action( 'wp_footer', array( $this, 'run_global_campaigns' ) );

		do_action( 'revenue_run_campaign' );
	}

	public function run_hellobar_campaings() {
		global $post;

		$campaigns = revenue()->get_available_campaigns( 0, 'all_page', 'top' );

		$this->run_campaigns( $campaigns, 'top', 'all_page', 'top_left' );
	}

	public function run_global_campaigns() {
		global $post;
		$which_page = 'all_page';
		if ( is_product() ) {
			$which_page = 'product_page';
		} elseif ( is_cart() ) {
			$which_page = 'cart_page';
		} elseif ( is_checkout() ) {
			$which_page = 'checkout_page';
		}

		if ( $post ) {
			$campaigns     = revenue()->get_available_campaigns( $post->ID, $which_page, 'drawer', 'top_left' );
			$all_campaigns = revenue()->get_available_campaigns( $post->ID, 'all_page', 'bottom', 'top_left' );

			$this->run_campaigns( $campaigns, 'drawer', $which_page, 'top_left' );
			$this->run_campaigns( $all_campaigns, 'bottom', 'all_page', 'top_left' );
		}
	}


	/**
	 * Run cart and checkout block campaigns
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public function run_cart_checkout_block_campaigns( $content ) {
		global $product;
		$before_extra      = '';
		$after_extra       = '';
		$showed_product_id = array();
		ob_start();
		if ( is_cart() && has_block( 'woocommerce/cart', get_the_ID() ) ) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $cart_item['product_id'];
				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$campaigns                        = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', 'before_content' );
					$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'before_content' );
				}
			}
		}
		if ( is_checkout() && has_block( 'woocommerce/checkout', get_the_ID() ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $cart_item['product_id'];

				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$campaigns                        = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', 'before_content' );
					$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'before_content' );
				}
			}
		}
		$before_extra = ob_get_clean();
		ob_start();
		if ( is_cart() && has_block( 'woocommerce/cart', get_the_ID() ) ) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $cart_item['product_id'];
				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$campaigns                        = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', 'after_content' );
					$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'after_content' );
				}
			}
		}
		if ( is_checkout() && has_block( 'woocommerce/checkout', get_the_ID() ) ) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $cart_item['product_id'];
				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$campaigns                        = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', 'after_content' );
					$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'after_content' );
				}
			}
		}

		$after_extra = ob_get_clean();

		return $before_extra . $content . $after_extra;
	}

	/**
	 * Is campaign valid
	 *
	 * @param array $campaign Campaign.
	 * @return boolean
	 */
	private function is_campaign_valid( $campaign ) {
		$valid = false;
		if ( isset( $campaign['campaign_type'], $campaign['campaign_status'], $campaign['id'] ) && 'publish' === $campaign['campaign_status'] ) {
			$valid = true;
		}

		return $valid && ! revenue()->is_hide_campaign( $campaign['id'], $campaign['campaign_type'] );
	}

	/**
	 * Run campaigns.
	 *
	 * @param array  $campaigns Campaigns.
	 * @param string $display_type Display type.
	 * @param string $placement Placement.
	 * @param string $position Position.
	 * @return void
	 */
	public function run_campaigns( $campaigns, $display_type = 'inpage', $placement = '', $position = '' ) {
		$typewise_campaigns = array();

		wp_enqueue_style( 'revenue-campaign' );
		wp_enqueue_style( 'revenue-campaign-buyx_gety' );
		wp_enqueue_style( 'revenue-campaign-double_order' );
		wp_enqueue_style( 'revenue-campaign-fbt' );
		wp_enqueue_style( 'revenue-campaign-mix_match' );
		wp_enqueue_style( 'revenue-utility' );
		wp_enqueue_style( 'revenue-responsive' );
		wp_enqueue_script( 'revenue-campaign' );
		wp_enqueue_script( 'revenue-add-to-cart' );
		wp_enqueue_script( 'revenue-animated-add-to-cart' );
		wp_enqueue_style( 'revenue-animated-add-to-cart' );

		foreach ( $campaigns as $campaign ) {
			$campaign = (array) $campaign;

			if ( ! $this->is_campaign_valid( $campaign ) ) {
				continue;
			}

			$typewise_campaigns[ $campaign['campaign_type'] ][] = $campaign;

			if ( isset( $this->renderd_campaigns[ $campaign['id'] ] ) ) {
				continue;
			}

			if ( ! isset( $this->renderd_campaigns[ $campaign['id'] ] ) ) {
				$this->renderd_campaigns[ $campaign['id'] ] = true;
			}

			$this->campaign_additional_data[ $campaign['id'] ]['offered_product_click_action']       = $campaign['offered_product_click_action'];
			$this->campaign_additional_data[ $campaign['id'] ]['offered_product_on_cart_action']     = $campaign['offered_product_on_cart_action'];
			$this->campaign_additional_data[ $campaign['id'] ]['animated_add_to_cart_enabled']       = $campaign['animated_add_to_cart_enabled'];
			$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_trigger_type'] = $campaign['add_to_cart_animation_trigger_type'];
			$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_type']         = $campaign['add_to_cart_animation_type'];
			$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_start_delay']  = $campaign['add_to_cart_animation_start_delay'];
			$this->campaign_additional_data[ $campaign['id'] ]['free_shipping_enabled']              = $campaign['free_shipping_enabled'];
			if ( isset( $campaign['countdown_timer_enabled'] ) && 'yes' === $campaign['countdown_timer_enabled'] ) {

				$countdown_data = array();

				$end_date = revenue()->get_campaign_meta( $campaign['id'], 'countdown_end_date', true );
				$end_time = revenue()->get_campaign_meta( $campaign['id'], 'countdown_end_time', true );

				$end_date_time = $end_date . ' ' . $end_time;

				$have_start_date_time = ( 'schedule_to_later' === revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_time_status', true ) );

				$start_date_time = '';
				if ( $have_start_date_time ) {
					$start_date = revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_date', true );
					$start_time = revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_time', true );

					$start_date_time = $start_date . ' ' . $start_time;
				}

				// If start_date_time is empty, set it to current date and time.
				if ( empty( $start_date_time ) ) {
					$start_date_time = current_time( 'mysql' );
				}

				$current_date_time = new DateTime( current_time( 'mysql' ) );

				$end_date_time_obj = new DateTime( $end_date_time );
				if ( $end_date_time_obj >= $current_date_time ) {
					$countdown_data = array(
						'end_time'   => $end_date_time,
						'start_time' => $start_date_time,
					);
				}

				$this->campaign_additional_data[ $campaign['id'] ]['countdown_data'] = $countdown_data;
			}

			if ( isset( $campaign['campaign_placement'] ) && 'multiple' !== $campaign['campaign_placement'] ) {
				$campaign['placement_settings'] = array(
					$campaign['campaign_placement'] => array(
						'page'                     => $campaign['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $campaign['campaign_display_style'],
						'builder_view'             => $campaign['campaign_builder_view'],
						'inpage_position'          => $campaign['campaign_inpage_position'],
						'popup_animation'          => $campaign['campaign_popup_animation'],
						'popup_animation_delay'    => $campaign['campaign_popup_animation_delay'],
						'floating_position'        => $campaign['campaign_floating_position'],
						'floating_animation_delay' => $campaign['campaign_floating_animation_delay'],
					),
				);

				$campaign['placement_settings'] = $campaign['placement_settings'];
			}

			$placement_settings = isset( $campaign['placement_settings'][ $placement ] ) ? $campaign['placement_settings'][ $placement ] : '';

			if ( is_array( $placement_settings ) && isset( $placement_settings['display_style'] ) && 'popup' === $placement_settings['display_style'] ) {
				$this->campaign_additional_data[ $campaign['id'] ]['campaign_popup_animation']       = $placement_settings['popup_animation'] ?? '';
				$this->campaign_additional_data[ $campaign['id'] ]['campaign_popup_animation_delay'] = $placement_settings['popup_animation_delay'] ?? '';
			}

			if ( isset( $campaign['animated_add_to_cart_enabled'] ) && 'yes' === $campaign['animated_add_to_cart_enabled'] ) {

				$trigger_when    = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_trigger_type', true );
				$animation_type  = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_type', true );
				$animation_delay = 0;

				if ( 'loop' === $trigger_when && empty( $loop_animation ) ) {
					$animation_delay = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_start_delay', true ) ?? 0;
					$loop_animation  = array(
						'type'  => $animation_type,
						'delay' => $animation_delay,
					);
				} elseif ( 'on_hover' === $trigger_when && empty( $hover_animation ) ) {
					$hover_animation = $animation_type;
				}
				$this->animated_button_data[ $campaign['id'] ] = array(
					'loop_animation'  => $animation_type,
					'delay'           => $animation_delay,
					'hover_animation' => $animation_type,
				);
			}
		}
		$campaign_localize_data = array(
			'ajax'                         => admin_url( 'admin-ajax.php' ),
			'nonce'                        => wp_create_nonce( 'revenue-add-to-cart' ),
			'user'                         => get_current_user_id(),
			'data'                         => $this->campaign_additional_data,
			'currency_format_num_decimals' => wc_get_price_decimals(),
			'currency_format_symbol'       => get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'  => wc_get_price_decimal_separator(),
			'currency_format_thousand_sep' => wc_get_price_thousand_separator(),
			'currency_format'              => get_woocommerce_price_format(),
			'checkout_page_url'            => wc_get_checkout_url(),
			'added_to_cart'                => __( 'Added to cart', 'revenue' ),
			'adding'                       => __( 'Adding...', 'revenue' ),

		);

		if ( ! $this->is_enqueue_data_already ) {
			wp_localize_script( 'revenue-campaign', 'revenue_campaign', $campaign_localize_data );
		}

		$display_type_methods = array(
			'inpage'   => 'output_inpage_views',
			'floating' => 'output_floating_views',
			'popup'    => 'output_popup_views',
			'drawer'   => 'output_drawer_views',
			'top'      => 'output_top_views',
			'bottom'   => 'output_bottom_views',
		);

		$class = false;

		foreach ( $typewise_campaigns as $type => $_campaigns ) {
			// Check if the display type and campaign type are valid.
			if ( isset( $display_type_methods[ $display_type ] ) ) {
				$method = $display_type_methods[ $display_type ];

				// Determine the appropriate class based on the campaign type.
				switch ( $type ) {
					case 'normal_discount':
						$class = Revenue_Normal_Discount::instance();
						break;
					case 'bundle_discount':
						$class = Revenue_Bundle_Discount::instance();
						break;
					case 'volume_discount':
						$class = Revenue_Volume_Discount::instance();
						break;
					case 'buy_x_get_y':
						$class = Revenue_Buy_X_Get_Y::instance();
						break;
					case 'free_shipping_bar':
						$class = Revenue_Free_Shipping_Bar::instance();
						break;
					case 'countdown_timer':
						$class = Revenue_Countdown_Timer::instance();
						break;
					case 'stock_scarcity':
						$class = Revenue_Stock_Scarcity::instance();
						break;
					case 'next_order_coupon':
						$class = Revenue_Next_Order_Coupon::instance();
						break;
					default:
						$class = false;
						break;
				}
				$class = apply_filters( 'revenue_campaign_instance', $class, $type );

				if ( $class ) {
					do_action( "revenue_campaign_{$type}_{$display_type}_before_render_content" );
					// @TODO Backward Compatibility should be added for pro.
					if ( method_exists( $class, $method ) ) {
						$class->$method(
							$_campaigns,
							array(
								'display_type' => $display_type,
								'position'     => $position,
								'placement'    => $placement,
							)
						);
					}
					do_action( "revenue_campaign_{$type}_{$display_type}_after_render_content" );
				}
			}
		}
	}

	/**
	 * Run campaign on before add to cart button
	 *
	 * @return void
	 */
	public function before_add_to_cart_button() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );

		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on after add to cart button
	 *
	 * @return void
	 */
	public function after_add_to_cart_button() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );

		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on before add to cart quantity
	 *
	 * @return void
	 */
	public function after_add_to_cart_quantity() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on before add to cart quantity
	 *
	 * @return void
	 */
	public function before_add_to_cart_quantity() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on below the product title
	 *
	 * @return void
	 */
	public function rvex_below_the_product_title() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on below the product price
	 *
	 * @return void
	 */
	public function rvex_below_the_product_price() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on before add to cart form
	 *
	 * @return void
	 */
	public function before_add_to_cart_form() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );

		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on after add to cart form
	 *
	 * @return void
	 */
	public function after_add_to_cart_form() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}
	/**
	 * Run campaign on before shop loop item title
	 *
	 * @return void
	 */
	public function after_shop_loop_item_title() {
		global $product;

		$product_id = $product->get_id();
		$campaigns  = revenue()->get_available_campaigns( $product_id, 'shop_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'shop_page', __FUNCTION__ );
	}
	/**
	 * Run campaign on shop loop item title
	 *
	 * @return void
	 */
	public function shop_loop_item_title() {
		global $product;

		$product_id = $product->get_id();
		$campaigns  = revenue()->get_available_campaigns( $product_id, 'shop_page', 'inpage', __FUNCTION__ );

		$this->run_campaigns( $campaigns, 'inpage', 'shop_page', __FUNCTION__ );
	}
	/**
	 * Run campaign on Cart Item Name
	 *
	 * @param array $cart_item Cart Item.
	 * @return void
	 */
	public function cart_item_name( $cart_item ) {
		// Get product object from cart item.
		$product = wc_get_product( $cart_item['product_id'] );

		if ( ! $product ) {
			return;
		}
		$product_id = $product->get_id();
		$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
	}
	/**
	 * Run campaign on Cart Item Name
	 *
	 * @param string $price Price.
	 * @param array  $cart_item Cart Item.
	 * @return void
	 */
	public function rvex_add_text_after_cart_item_price( $price, $cart_item ) {
		// Get product object from cart item.
		global $product;
		$product           = wc_get_product( $cart_item['product_id'] );
		$cart_item_content = '';
		if ( ! $product ) {
			return;
		}
		$product_id = $product->get_id();
		$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', 'cart_item_price' );

		ob_start();
		$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'cart_item_price' );
		$cart_item_content = ob_get_clean();
		return $price . $cart_item_content;
	}

	/**
	 * Run campaign on before single product summary
	 *
	 * @return void
	 */
	public function before_single_product_summary() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on after single product summary
	 *
	 * @return void
	 */
	public function after_single_product_summary() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );

		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}


	/**
	 * Run campaign on after single product
	 *
	 * @return void
	 */
	public function after_single_product() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );

		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );

		// Popup.

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'popup' );
		$this->run_campaigns( $campaigns, 'popup', 'product_page', __FUNCTION__ );

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'floating' );
		$this->run_campaigns( $campaigns, 'floating', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on before single product
	 *
	 * @return void
	 */
	public function before_single_product() {
		global $product;

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'product_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on after cart item name
	 *
	 * @param array $cart_item Cart Item.
	 * @return void
	 */
	public function after_cart_item_name( $cart_item ) {
		global $product;
		$product = $cart_item['data'];

		$product_id = $product->get_id();

		$campaigns = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
		$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
	}

	/**
	 * Run campaign on before cart contents
	 *
	 * @return void
	 */
	public function before_cart_contents() {

		global $product;
		$already_running = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $product->get_id();

			$data            = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__, true );
			$campaigns       = $data['campaigns'];
			$already_running = array_merge( $already_running, $data['ids'] );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on before cart table
	 *
	 * @return void
	 */
	public function before_cart_table() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on before cart
	 *
	 * @return void
	 */
	public function before_cart() {
		global $product;

		$is_found = false;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );

			if ( ! $is_found ) {
				// Popup.
				$campaigns = revenue()->get_available_campaigns( $product_id, 'cart_page', 'popup', __FUNCTION__ );
				$this->run_campaigns( $campaigns, 'popup', 'cart_page', __FUNCTION__ );

				if ( ! empty( $campaigns ) ) {
					$is_found = true;
				}
				// Floating.
				$campaigns = revenue()->get_available_campaigns( $product_id, 'cart_page', 'floating', __FUNCTION__ );
				$this->run_campaigns( $campaigns, 'floating', 'cart_page', __FUNCTION__ );

				if ( ! empty( $campaigns ) ) {
					$is_found = true;
				}
			}
		}
	}

	/**
	 * Run campaign on after cart table
	 *
	 * @return void
	 */
	public function after_cart_table() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on after cart
	 *
	 * @return void
	 */
	public function after_cart() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on before cart totals
	 *
	 * @return void
	 */
	public function before_cart_totals() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on after cart totals
	 *
	 * @return void
	 */
	public function after_cart_totals() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on after proceed to checkout
	 *
	 * @return void
	 */
	public function proceed_to_checkout() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'cart_page', __FUNCTION__ );
		}
	}

	// Checkout.
	/**
	 * Run campaign on before checkout form
	 *
	 * @return void
	 */
	public function before_checkout_form() {
		global $product;

		$is_found = false;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );

			if ( ! $is_found ) {
				// Floating.
				$campaigns = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'floating', __FUNCTION__ );
				$this->run_campaigns( $campaigns, 'floating', 'checkout_page', __FUNCTION__ );

				if ( ! empty( $campaigns ) ) {
					$is_found = true;
				}
				// Popup.
				$campaigns = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'popup', __FUNCTION__ );
				$this->run_campaigns( $campaigns, 'popup', 'checkout_page', __FUNCTION__ );

				if ( ! empty( $campaigns ) ) {
					$is_found = true;
				}
			}
		}
	}

	/**
	 * Run campaign on before checkout billing form
	 *
	 * @return void
	 */
	public function before_checkout_billing_form() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	public function review_order_before_submit() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on after checkout billing form
	 *
	 * @return void
	 */
	public function after_checkout_billing_form() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on checkout after order review
	 *
	 * @return void
	 */
	public function checkout_after_order_review() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on checkout before order review
	 *
	 * @return void
	 */
	public function checkout_before_order_review() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on review order before order total
	 *
	 * @return void
	 */
	public function review_order_before_order_total() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on review order after order total
	 *
	 * @return void
	 */
	public function review_order_after_order_total() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on review order before payment
	 *
	 * @return void
	 */
	public function review_order_before_payment() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}
	public function review_order_before_shipping() {

		if ( $this->is_already_render ) {
			return;
		}
		$this->is_already_render = true;

		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on review order after payment
	 *
	 * @return void
	 */
	public function review_order_after_payment() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	/**
	 * Run campaign on after checkout form
	 *
	 * @return void
	 */
	public function after_checkout_form() {
		global $product;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product    = $cart_item['data'];
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$campaigns  = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'checkout_page', __FUNCTION__ );
		}
	}

	// Thankyou page.
	/**
	 * Run campaign on before thankyou
	 *
	 * @param int $order_id Order Id.
	 * @return void
	 */
	public function before_thankyou( $order_id ) {
		global $product;
		$_order   = wc_get_order( $order_id );
		$is_found = false;
		// Loop through order items.
		foreach ( $_order->get_items() as $item ) {
			// Get the product ID from the order item.
			$product_id = $item->get_product_id();

			$product = wc_get_product( $product_id );

			$campaigns = revenue()->get_available_campaigns( $product_id, 'thankyou_page', 'inpage', __FUNCTION__ );

			$this->run_campaigns( $campaigns, 'inpage', 'thankyou_page', __FUNCTION__ );

			if ( ! $is_found ) {
				// Popup.
				$campaigns = revenue()->get_available_campaigns( $product_id, 'thankyou_page', 'popup', __FUNCTION__ );

				$this->run_campaigns( $campaigns, 'popup', 'thankyou_page', __FUNCTION__ );

				if ( ! empty( $campaigns ) ) {
					$is_found = true;
				}
				// Floating.
				$campaigns = revenue()->get_available_campaigns( $product_id, 'thankyou_page', 'floating', __FUNCTION__ );

				$this->run_campaigns( $campaigns, 'floating', 'thankyou_page', __FUNCTION__ );

				if ( ! empty( $campaigns ) ) {
					$is_found = true;
				}
			}
		}
	}

	/**
	 * Run campaign on thankyou page page thankyou hook
	 *
	 * @param string|int $order_id Order ID.
	 * @return void
	 */
	public function thankyou( $order_id ) {
		global $product;

		$_order = wc_get_order( $order_id );
		// Loop through order items.
		foreach ( $_order->get_items() as $item ) {
			// Get the product ID from the order item.
			$product_id = $item->get_product_id();

			$product = wc_get_product( $product_id );

			$campaigns = revenue()->get_available_campaigns( $product_id, 'thankyou_page', 'inpage', __FUNCTION__ );
			$this->run_campaigns( $campaigns, 'inpage', 'thankyou_page', __FUNCTION__ );
		}
	}


	/**
	 * Handle Free Shipping
	 *
	 * @param Object $package_rates Shipping Package Rate.
	 * @param array  $package Package Data.
	 * @return mixed
	 */
	public function handle_free_shipping( $package_rates, $package ) {

		$is_free_shipping = true;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! ( isset( $cart_item['rev_is_free_shipping'] ) && 'yes' === $cart_item['rev_is_free_shipping'] ) ) {
				$is_free_shipping = false;
				break;
			}

			if ( isset( $cart_item['revx_campaign_type'] ) && 'buy_x_get_y' === $cart_item['revx_campaign_type'] ) {
				if ( ! Revenue_Buy_X_Get_Y::instance()->is_eligible_for_discount( $cart_item ) ) {
					$is_free_shipping = false;
					break;
				}
			}
		}

		$is_free_shipping = apply_filters( 'revenue_free_shipping', $is_free_shipping );

		if ( $is_free_shipping ) {
			$free_shipping        = new WC_Shipping_Free_Shipping( 'revenue_free_shipping' );
			$free_shipping->title = apply_filters( 'revenue_free_shipping_title', __( 'WoW Revenue Free Shipping', 'revenue' ) ); // Add Global Settings.
			$free_shipping->calculate_shipping( $package );
			return $free_shipping->rates;
		}

		return $package_rates;
	}

	/**
	 * Handle Animated Add to cart
	 *
	 * @return void
	 */
	public function handle_animated_add_to_cart() {
		wp_enqueue_script( 'revenue-animated-add-to-cart' );
		wp_enqueue_style( 'revenue-animated-add-to-cart' );
		wp_localize_script( 'revenue-animated-add-to-cart', 'revenue_animated_atc', array( 'data' => $this->animated_button_data ) );
	}

	/**
	 * WooCommerce check cart items
	 *
	 * @return void
	 */
	public function woocommerce_check_cart_items() {
		$cart_hash = isset( $_COOKIE['woocommerce_cart_hash'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['woocommerce_cart_hash'] ) ) : '';
		$revx_hash = isset( $_COOKIE['revenue_cart_checked_hash'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['revenue_cart_checked_hash'] ) ) : '';

		if ( $cart_hash !== $revx_hash ) {
			// Not Checked.
			$cart = WC()->cart;

			do_action( 'revenue_check_cart_items', $cart );

			if ( ! headers_sent() ) {
				setcookie( 'revenue_cart_checked_hash', $cart_hash, time() + ( 86400 * 30 ), '/' );
			}
		}
	}

	/**
	 * After Add to cart from revenue campaign
	 *
	 * Set Campaign and Product Id on session to check if the product already on cart or not
	 *
	 * @since 1.0.0
	 *
	 * @param string     $key Cart item hash.
	 * @param int|string $product_id Product Id.
	 * @param int|string $campaign_id Campaign id.
	 * @return void
	 */
	public function after_add_to_cart( $key, $product_id, $campaign_id ) {
		$cart_data = WC()->session->get( 'revenue_cart_data' );
		if ( ! is_array( $cart_data ) ) {
			$cart_data = array();
		}
		if ( ! isset( $cart_data[ $campaign_id ][ $product_id ] ) ) {
			$cart_data[ $campaign_id ][ $product_id ] = $key;
			WC()->session->set( 'revenue_cart_data', $cart_data );
		}
	}

	/**
	 * Actions After remove cart item
	 *
	 * Perform campaign wise action after remove a item from cart
	 *
	 * @param string  $key Cart Item Hash Key.
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function after_remove_cart_item( $key, $cart ) {
		$cart_item = $cart->removed_cart_contents[ $key ];
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];
			$item          = WC()->cart->get_cart()[ $key ];
			$cart_data     = WC()->session->get( 'revenue_cart_data' );
			if ( ! is_array( $cart_data ) ) {
				$cart_data = array();
			}
			if ( isset( $cart_data[ $item['revx_campaign_id'] ][ $item['product_id'] ] ) ) {
				unset( $cart_data[ $item['revx_campaign_id'] ][ $item['product_id'] ] );
				if ( empty( $cart_data[ $item['revx_campaign_id'] ] ) ) {
					unset( $cart_data[ $item['revx_campaign_id'] ] );
				}
				WC()->session->set( 'revenue_cart_data', $cart_data );
			}

			/**
			 * Hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_remove_cart_item", $key, $cart_item, $campaign_id );
		}
	}

	/**
	 * After cart item restored
	 *
	 * Set Campaign and Product Id on session to check if the product already on cart or not
	 *
	 * @param string  $key Restored item cart hash key.
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function after_cart_item_restored( $key, $cart ) {
		$cart_item = $cart->removed_cart_contents[ $key ];

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$cart_data = WC()->session->get( 'revenue_cart_data' );
			if ( ! is_array( $cart_data ) ) {
				$cart_data = array();
			}
			if ( ! isset( $cart_data[ $campaign_id ][ $cart_item['product_id'] ] ) ) {
				$cart_data[ $campaign_id ][ $cart_item['product_id'] ] = $key;
				WC()->session->set( 'revenue_cart_data', $cart_data );
			}

			/**
			 * Hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_restore_cart_item", $key, $cart_item, $campaign_id );
		}
	}

	/**
	 * Set Revenue cart data Null after cart empty
	 *
	 * @return void
	 */
	public function after_cart_emptied() {
		WC()->session->set( 'revenue_cart_data', null );
	}



	/**
	 * WooCommerce After Add to cart an item.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $cart_item_key Cart Item Key.
	 * @param int|string $product_id Product Id.
	 * @param int        $quantity Added Quantity.
	 * @param int        $variation_id Variation Id.
	 * @param array      $variation Variation Data.
	 * @param array      $cart_item_data Cart Item Data.
	 * @return void
	 */
	public function woocommerce_after_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
			return;
		}

		if ( isset( $cart_item_data['revx_campaign_id'] ) ) {
			$campaign_type = $cart_item_data['revx_campaign_type'];
			/**
			 * Hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_added_to_cart", $cart_item_key, $cart_item_data, $product_id, $quantity );
		}
	}

	/**
	 * WooCommerce Before Calculate Total
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function woocommerce_before_calculate_totals( $cart ) {
		if ( is_admin() ) {
			return;
		}

		wp_cache_delete( 'revx_cart_product_ids' );
		$has_revenue_price = false;
		foreach ( $cart->get_cart() as $cart_item ) {
			$is_from_revenue = false;

			if ( $cart_item['data']->is_type( 'etn' ) ) {
				continue;
			}

			// Revenue Cart Item : Added through revenue.
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id   = $cart_item['revx_campaign_id'];

				/**
				 * Hook for update price on cart from several campaigns
				 * Valid Campaign Type:
				 * normal_discount
				 * bundle_discount
				 * volume_discount
				 * buy_x_get_y
				 * mix_match
				 * frequently_bought_together
				 * spending_goal
				 */
				do_action( "revenue_campaign_{$campaign_type}_before_calculate_cart_totals", $cart_item, $campaign_id, $cart );

				$has_revenue_price = true;

				$pid = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

				$this->revenue_price[ $pid ] = $cart_item['data']->get_price();

			}
		}

		if ( $has_revenue_price ) {
			// $this->add_price_filter();
		}
	}

	/**
	 * Adds price filter hooks for WooCommerce products and variations.
	 *
	 * This method attaches filters to modify the price of both regular products
	 * and product variations using the 'set_product_price' callback.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_price_filter() {
		add_filter( 'woocommerce_product_get_price', array( $this, 'set_product_price' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'set_product_price' ), 10, 2 );
	}

	/**
	 * Removes previously added price filter hooks.
	 *
	 * This method removes the price modification filters that were added by add_price_filter().
	 * Use this method when you need to restore original product pricing.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function remove_price_filter() {
		remove_filter( 'woocommerce_product_get_price', array( $this, 'set_product_price' ), 10, 2 );
		remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'set_product_price' ), 10, 2 );
	}

	/**
	 * Sets the product price based on stored revenue prices.
	 *
	 * Checks if a custom revenue price exists for the given product ID in the
	 * revenue_price array and returns that price if found. Otherwise, returns
	 * the original price unchanged.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param float           $price   The original product price.
	 * @param WC_Product|null $product The product object.
	 *
	 * @return float The modified or original price.
	 */
	public function set_product_price( $price, $product ) {
		if ( isset( $this->revenue_price[ $product->get_id() ] ) ) {
			$price = $this->revenue_price[ $product->get_id() ];
		}

		return $price;
	}
	/**
	 * WooCommerce Before Calculate Total
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function woocommerce_cart_calculate_fees( $cart ) {
		if ( is_admin() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			// Revenue Cart Item : Added through revenue.
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id   = $cart_item['revx_campaign_id'];

				/**
				 * Hook for update price on cart from several campaigns
				 * Valid Campaign Type:
				 * normal_discount
				 * bundle_discount
				 * volume_discount
				 * buy_x_get_y
				 * mix_match
				 * frequently_bought_together
				 * spending_goal
				 */
				do_action( "revenue_campaign_{$campaign_type}_cart_calculate_fees", $cart_item, $campaign_id, $cart );
			}
		}
	}


	/**
	 * WooCommerce Cart Item Remove Link
	 *
	 * Used for any modification on remove link based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $link Remove Link.
	 * @param string $cart_item_key Cart Hash Key.
	 * @return string
	 */
	public function woocommerce_cart_item_remove_link( $link, $cart_item_key ) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$link = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_remove_link", $link, $cart_item, $campaign_id );
		}

		return $link;
	}


	/**
	 * WooCommerce Cart Item Quantity
	 *
	 * Used for any modification on cart item quantity based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $quantity Item Quantity.
	 * @param string $cart_item_key Cart Hash Key.
	 * @return string
	 */
	public function woocommerce_cart_item_quantity( $quantity, $cart_item_key ) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$quantity = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_quantity", $quantity, $cart_item, $campaign_id );
		}
		return $quantity;
	}

	/**
	 * Add Custom class name on cart item based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $classname Class Name.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function woocommerce_cart_item_class( $classname, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];
			$classname     = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_class", $classname, $cart_item, $campaign_id );
		}
		return $classname;
	}

	/**
	 * Change Cart Item Subtotal based on campaign
	 *
	 * @param string $subtotal Item Subtotal.
	 * @param array  $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_cart_item_subtotal( $subtotal, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$subtotal = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_subtotal", $subtotal, $cart_item, $campaign_id );
		}
		return $subtotal;
	}

	/**
	 * Change Cart Item Name based on campaign
	 *
	 * @param string $item_name Item Name.
	 * @param array  $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_cart_item_name( $item_name, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$item_name = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_name", $item_name, $cart_item, $campaign_id );
		}
		return $item_name;
	}

	/**
	 * Change Minimum Product quantity on Cart Block based on campaign
	 *
	 * @param string     $value Minimum Quantity Value.
	 * @param WC_Product $product Product Object.
	 * @param array      $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_store_api_product_quantity_minimum( $value, $product, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$value = apply_filters( "revenue_campaign_{$campaign_type}_store_api_product_quantity_minimum", $value, $cart_item, $campaign_id );
		}
		return $value;
	}
	/**
	 * Change maximum Product quantity on Cart Block based on campaign
	 *
	 * @param string     $value Maximum Quantity Value.
	 * @param WC_Product $product Product Object.
	 * @param array      $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_store_api_product_quantity_maximum( $value, $product, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$value = apply_filters( "revenue_campaign_{$campaign_type}_store_api_product_quantity_maximum", $value, $cart_item, $campaign_id );
		}
		return $value;
	}



	/**
	 * Change Cart Item Price based on campaign
	 *
	 * @since 1.0.0
	 *
	 * @param string $price Item Price.
	 * @param array  $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_cart_item_price( $price, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$price = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_price", $price, $cart_item, $campaign_id );
		}
		return $price;
	}

	/**
	 * Get Cart item Data
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Item Data.
	 * @param array $cart_item Cart Item.
	 * @return array
	 */
	public function woocommerce_get_item_data( $data, $cart_item ) {
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$data = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_data", $data, $cart_item, $campaign_id );
		}
		return $data;
	}


	/**
	 * WooCommerce create order line item
	 *
	 * @param object   $item Item Data.
	 * @param string   $cart_item_key Cart Item Key.
	 * @param array    $cart_item Cart Item.
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function woocommerce_checkout_create_order_line_item( $item, $cart_item_key, $cart_item, $order ) {

		// Item order through revenue.
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$item->add_meta_data( '_revx_campaign_id', $campaign_id, true );
			$item->add_meta_data( '_revx_campaign_type', $campaign_type, true );
			/**
			 * Hook for add item meta data
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_create_order_line_item", $item, $cart_item_key, $cart_item, $campaign_id, $order );
		}
	}


	/**
	 * Add Hidden Order Item Meta
	 *
	 * @param array $hidden_meta Hidden Meta.
	 * @return array
	 */
	public function woocommerce_hidden_order_itemmeta( $hidden_meta ) {
		$hidden_meta[] = '_revx_campaign_id';
		$hidden_meta[] = '_revx_campaign_type';

		$hidden_meta = apply_filters( 'revenue_hidden_order_item_meta', $hidden_meta );

		return $hidden_meta;
	}


	/**
	 * Perform action after create order on WooCommerce
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function woocommerce_checkout_create_order( $order ) {
		$order = wc_get_order( $order );

		if ( ! $order ) {
			return;
		}

		// Get all items from the order.
		$items = $order->get_items();

		$data = array();

		// Loop through each item in the order.
		foreach ( $items as $item ) {
			// Get the campaign ID from the item's meta data.
			$campaign_id = $item->get_meta( '_revx_campaign_id' );

			if ( $campaign_id ) {
				// Get the product associated with the item.
				$order->update_meta_data( '_revx_campaign_id', $campaign_id );
				$product = $item->get_product();
				// Add the product ID and campaign ID to the data array.
				$data[] = array(
					'item_id'     => $product->get_id(),
					'campaign_id' => $campaign_id,
				);
			}
		}

		// If data array is not empty, trigger the custom action.
		if ( ! empty( $data ) ) {
			$order->save();
			do_action( 'revenue_campaign_order_created', $data, $order );
		}
	}


	/**
	 * Render campaign view shortcode
	 *
	 * @param array $atts Attributes.
	 * @return mixed
	 */
	public function render_campaign_view_shortcode( $atts ) {

		if ( ! isset( $atts['id'] ) ) {
			return false;
		}

		$id = (int) $atts['id'];

		$campaign = revenue()->get_campaign_data( $id );

		if ( ! $this->is_campaign_valid( $campaign ) ) {
			return;
		}

		$class = false;

		switch ( $campaign['campaign_type'] ) {
			case 'normal_discount':
				$class = Revenue_Normal_Discount::instance();
				break;
			case 'bundle_discount':
				$class = Revenue_Bundle_Discount::instance();
				break;
			case 'volume_discount':
				$class = Revenue_Volume_Discount::instance();
				break;
			case 'buy_x_get_y':
				$class = Revenue_Buy_X_Get_Y::instance();
				break;
			case 'free_shipping_bar':
				$class = Revenue_Free_Shipping_Bar::instance();
				break;
			case 'countdown_timer':
				$class = Revenue_Countdown_Timer::instance();
				break;
			case 'stock_scarcity':
				$class = Revenue_Stock_Scarcity::instance();
				break;
			default:
				$class = false;
				break;
		}

		$class = apply_filters( 'revenue_campaign_instance', $class, $campaign['campaign_type'] );

		do_action( 'revenue_campaign_before_render_shortcode', $campaign );
		switch ( $campaign['campaign_type'] ) {
			case 'free_shipping_bar':
				$class->render_shortcode( $campaign );
				break;
			case 'stock_scarcity':
				$class->render_shortcode( $campaign );
				break;

			default:
				$this->render_shortcode( $campaign );
				break;
		}
		do_action( 'revenue_campaign_after_render_shortcode', $campaign );
	}

	/**
	 * Renders and outputs a shortcode view for a single campaign.
	 *
	 * This method generates HTML output for a campaign view by including the
	 * in-page view PHP file. It also updates the campaign impression count based on
	 * whether a product is available.
	 *
	 * @param array $campaign The campaign data to be rendered.
	 * @param array $data Data.
	 *
	 * @return void
	 */
	public function render_shortcode( $campaign, $data = array() ) {
		global $product;

		if ( is_product() ) {

			if ( $product && $product instanceof \WC_Product && is_array( $campaign ) ) {
				revenue()->update_campaign_impression( $campaign['id'], $product->get_id() );
			} else {
				return;
			}
			$this->run_shortcode(
				$campaign,
				array(
					'display_type' => 'inpage',
					'position'     => '',
					'placement'    => 'product_page',
				)
			);
		} elseif ( is_cart() && WC()->cart ) {
			$which_page = 'cart_page';

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $cart_item['product_id'];

				$this->run_shortcode(
					$campaign,
					array(
						'display_type' => 'inpage',
						'position'     => '',
						'placement'    => 'cart_page',
					)
				);
			}
		} elseif ( is_shop() ) {
			$which_page = 'shop_page';
		} elseif ( is_checkout() && function_exists( 'WC' ) && WC()->cart && method_exists( WC()->cart, 'get_cart' ) ) {
			// check if it is rendered once.
			$is_inside = false;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $cart_item['product_id'];

				if ( $is_inside ) {
					break;
				}
				$this->run_shortcode(
					$campaign,
					array(
						'display_type' => 'inpage',
						'position'     => '',
						'placement'    => 'checkout_page',
					)
				);
				$is_inside = true;
			}
		}
	}

	/**
	 * Run shortcode
	 *
	 * @param array $campaign Campaign.
	 * @param array $data Data.
	 * @return mixed
	 */
	public function run_shortcode( $campaign, $data = array() ) {
		wp_enqueue_style( 'revenue-campaign' );
		wp_enqueue_style( 'revenue-utility' );
		wp_enqueue_style( 'revenue-responsive' );
		wp_enqueue_style( 'revenue-campaign-buyx_gety' );
		wp_enqueue_style( 'revenue-campaign-double_order' );
		wp_enqueue_style( 'revenue-campaign-fbt' );
		wp_enqueue_style( 'revenue-campaign-mix_match' );
		wp_enqueue_script( 'revenue-campaign' );

		$file_path_prefix = apply_filters( 'revenue_campaign_file_path', REVENUE_PATH, $campaign['campaign_type'], $campaign );

		// Replace underscores with hyphens in the campaign type.
		$campaign_type = isset( $campaign['campaign_type'] ) ? str_replace( '_', '-', $campaign['campaign_type'] ) : 'normal-discount';

		$file_path = false;
		if ( isset( $campaign['campaign_display_style'] ) ) {

			switch ( $campaign['campaign_display_style'] ) {
				case 'inpage':
				case 'multiple':
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/inpage.php";
					break;
				case 'popup':
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/popup.php";
					break;
				case 'floating':
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/floating.php";
					break;
				default:
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/inpage.php";
					break;
			}
		}

		$file_path = apply_filters( 'revenue_campaign_shortcode_file_path', $file_path, $campaign );
		$file_path = false;
		if ( isset( $campaign['campaign_display_style'] ) ) {
			switch ( $campaign['campaign_display_style'] ) {
				case 'inpage':
				case 'multiple':
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/inpage.php";
					break;
				case 'popup':
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/popup.php";
					break;
				case 'floating':
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/floating.php";
					break;
				default:
					$file_path = $file_path_prefix . "includes/campaigns/views/{$campaign_type}/inpage.php";
					break;
			}
		}

		ob_start();
		if ( file_exists( $file_path ) ) {
			extract($data); //phpcs:ignore
			?>
				<div class="revenue-campaign-shortcode">
					<?php
						include $file_path;
					?>
				</div>

			<?php
		}

		$output = ob_get_clean();

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $request_uri, $rest_prefix ) );

		if ( $is_rest_api_request ) {
			return $output;
		} else {
			echo wp_kses( $output, revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}


	/**
	 * WooCommerce after item quantity update.
	 *
	 * @param string  $cart_item_key Cart item key.
	 * @param integer $quantity quantity.
	 * @return void
	 */
	public function woocommerce_after_cart_item_quantity_update( $cart_item_key, $quantity = 0 ) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			/**
			 * Hook for add item meta data
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_after_item_quantity_updated", $cart_item, $cart_item_key, $quantity );
		}
	}




	/**
	 * Integrates subscription plugin functionality by adding filters for recurring price calculations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function subscriptions_plugin_integrations() {
		add_filter( 'ywsbs_subscription_recurring_price', array( $this, 'yith_subscription_recurring_price' ), 10, 3 );
	}

	/**
	 * Filters the recurring price for YITH WooCommerce Subscription.
	 *
	 * @since 1.0.0
	 * @param float      $recurring_price The recurring price.
	 * @param WC_Product $product The product object.
	 * @param array      $subscription_info Subscription information.
	 * @return float Returns the product's price.
	 */
	public function yith_subscription_recurring_price( $recurring_price, $product, $subscription_info ) {

		if ( 0 == did_action( 'woocommerce_before_calculate_totals' ) ) {
			WC()->cart->calculate_totals();
		}
		return $product->get_price();
	}
}
