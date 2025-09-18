<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Campaign: Stock Scarcity
 *
 * @package Revenue
 */

namespace Revenue;

//phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison


/**
 * Revenue Campaign: Normal Discount
 *
 * @hooked on init
 */
class Revenue_Stock_Scarcity {
	use SingletonTrait;

	/**
	 * Stores the campaigns to be rendered on the page.
	 *
	 * @var array|null $campaigns
	 *    An array of campaign data organized by view types (e.g., in-page, popup, floating),
	 *    or null if no campaigns are set.
	 */
	public $campaigns = array();

	/**
	 * Keeps track of the current position for rendering in-page campaigns.
	 *
	 * @var string $current_position
	 *    The position within the page where in-page campaigns should be displayed.
	 *    Default is an empty string, indicating no position is set.
	 */
	public $current_position = '';

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'wp', array( $this, 'wsx_get_product_id_after_everything_loaded' ) );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'wsx_store_campaign_data_for_cart' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'rvex_add_hidden_page_type_field' ) );
		add_action( 'wp_ajax_update_product_views', array( $this, 'update_product_views_ajax' ) );
		add_action( 'wp_ajax_nopriv_update_product_views', array( $this, 'update_product_views_ajax' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_single_product_view_script' ) );
		// add_action( 'woocommerce_order_status_completed', array( $this, 'update_user_purchase_count' ) );
		// add_action( 'woocommerce_order_status_processing', array( $this, 'update_user_purchase_count' ) );
		// add_action( 'woocommerce_order_status_on-hold', array( $this, 'update_user_purchase_count' ) );
		// add_action( 'woocommerce_order_status_cancelled', array( $this, 'decrease_user_purchase_count' ) );
	}

	/**
	 * Enqueue the single product view script.
	 *
	 * @return void
	 */
	public function enqueue_single_product_view_script() {
		if ( is_product() ) {
			wp_enqueue_script( 'single-product-view', plugin_dir_url( __FILE__ ) . 'ajax-single-product-view.js', array( 'jquery' ), null, true );
			wp_localize_script(
				'single-product-view',
				'single_product_data',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'product_id'  => 0,
					'campaign_id' => 0,
				)
			);
		}
	}

	/**
	 * Update product views count via AJAX.
	 *
	 * @return void
	 */
	public function update_product_views_ajax() {
		if ( ! isset( $_POST['product_id'], $_POST['campaign_id'] ) ) {
			wp_send_json_error( 'Missing parameters' );
			return;
		}

		$product_id  = intval( $_POST['product_id'] );
		$campaign_id = sanitize_text_field( $_POST['campaign_id'] );

		if ( ! $product_id || empty( $campaign_id ) ) {
			wp_send_json_error( 'Invalid data' );
			return;
		}

		// Load the product object.
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( 'Product not found' );
			return;
		}

		// Update view count.
		$count = (int) $product->get_meta( $campaign_id . '_views_counter', true );
		update_post_meta( $product_id, $campaign_id . '_views_counter', $count + 1 );
		wp_send_json_success( array( 'new_count' => $count + 1 ) );
	}
	/**
	 * Add hidden field to store current page type.
	 *
	 * @return void
	 */
	public function rvex_add_hidden_page_type_field() {
		$current_page = 'unknown';

		if ( is_shop() ) {
			$current_page = 'shop_page';
		} elseif ( is_product() ) {
			$current_page = 'product_page';
		} elseif ( is_cart() ) {
			$current_page = 'cart_page';
		}

		echo '<input type="hidden" id="wsx_current_page" value="' . esc_attr( $current_page ) . '">';
	}
	/**
	 * Store campaign data for cart.
	 *
	 * @param array $cart_item_data The cart item data.
	 * @param int   $product_id The product ID.
	 *
	 * @return array
	 */
	public function wsx_store_campaign_data_for_cart( $cart_item_data, $product_id ) {

		$positions = array(
			'rvex_below_the_product_title',
			'rvex_below_the_product_price',
			'before_add_to_cart_quantity',
			'cart_item_price',
			'after_cart_item_name',
			'after_shop_loop_item_title',
			'shop_loop_item_title',
		);
		// check current page is shop page or product page and cart page.
		$current_page = '';
		if ( is_product() ) {
			$current_page = 'product_page';
		} elseif ( is_shop() ) {
			$current_page = 'shop_page';
		} elseif ( is_cart() ) {
			$current_page = 'cart_page';
		} elseif ( ! empty( $_POST['wsx_current_page'] ) ) {
			$current_page = sanitize_text_field( $_POST['wsx_current_page'] );
		} else {
			$current_page = 'shop_page';
		}
		// Loop through each position and fetch campaigns.
		foreach ( $positions as $position ) {
			$campaigns = revenue()->get_available_campaigns( $product_id, $current_page, 'inpage', $position, false, false, 'stock_scarcity' );

			if ( ! empty( $campaigns ) ) {
				foreach ( $campaigns as $key => $campaign ) {
					$cart_item_data['revx_campaign_id']   = $campaign['id'];
					$cart_item_data['revx_campaign_type'] = $campaign['campaign_type'];
					revenue()->increment_campaign_add_to_cart_count( $campaign['id'] );
					break; // Stop after setting campaign data for one position.
				}
			}
		}
		return $cart_item_data;
	}


	/**
	 * Get product ID after everything is loaded.
	 *
	 * @return void
	 */
	public function wsx_get_product_id_after_everything_loaded() {
		if ( is_product() ) {
			global $wp_query;

			if ( ! empty( $wp_query->post ) ) {
				$product_id = $wp_query->post->ID;

				// List of positions.
				$positions = array(
					'rvex_below_the_product_title',
					'rvex_below_the_product_price',
					'before_add_to_cart_quantity',
					'cart_item_price',
					'after_cart_item_name',
					'after_shop_loop_item_title',
					'shop_loop_item_title',
				);

				// Loop through each position and fetch campaigns.
				foreach ( $positions as $position ) {
					$campaigns = revenue()->get_available_campaigns( $product_id, 'product_page', 'inpage', $position, false, false, 'stock_scarcity' );

					if ( ! empty( $campaigns ) ) {
						add_filter( 'woocommerce_get_stock_html', fn( $html, $product ) => '', 10, 2 );
					}
				}
			}
		}
	}

	/**
	 * Outputs in-page views for a list of campaigns.
	 *
	 * This method processes and renders in-page views based on the provided campaigns.
	 * It adds each campaign to the `inpage` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_inpage_views( $campaigns, $data ) {
		foreach ( $campaigns as $campaign ) {

			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;

			$this->current_position = $data['position'];
			do_action( 'revenue_campaign_stock_scarcity_inpage_before_render_content' );
			$this->render_views( $data );
			do_action( 'revenue_campaign_stock_scarcity_inpage_after_render_content' );
		}
	}

	/**
	 * Renders and outputs views for the campaigns.
	 *
	 * This method generates HTML output for different types of campaign views:
	 * - In-page views
	 * - Popup views
	 * - Floating views
	 *
	 * It includes the respective PHP files for each view type and processes them.
	 * The method also enqueues necessary scripts and styles for popup and floating views.
	 *
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function render_views( $data = array() ) {

		global $product;
		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			wp_enqueue_script( 'revenue-campaign-stock-scarcity' );
			wp_enqueue_style( 'revenue-campaign-stock-scarcity' );
			wp_enqueue_script( 'single-product-view' );
			foreach ( $campaigns as $campaign ) {
				revenue()->update_campaign_impression( $campaign['id'], $product->get_id() );
				$output = '';

				$file_path = apply_filters( 'revenue_campaign_view_path', REVENUE_PATH . 'includes/campaigns/views/stock-scarcity/inpage.php', 'stock_scarcity', 'inpage', $campaign );
				ob_start();
				?>
				<article class="upsells">
				<?php
				if ( file_exists( $file_path ) ) {
					extract($data); //phpcs:ignore
					include $file_path;
				}
				?>
				</article>
				<?php

				$output .= ob_get_clean();
			}

			if ( $output ) {
				echo wp_kses( $output, revenue()->get_allowed_tag() );
			}
		}
	}

	/**
	 * Localize data for countdown timer using Hiding File .
	 *
	 * @param array $campaign The campaign data.
	 *
	 * @return array
	 */
	public function stock_scarcity_hidden_data( $campaign = array() ) {
		global $product;
		$stock_quantity = null;
		if ( $product && is_a( $product, 'WC_Product' ) ) {
			$stock_quantity = $product->get_stock_quantity();
			// echo 'Available Stock: ' . ( $stock_quantity !== null ? $stock_quantity : 'Out of Stock' );
		}

		$message_type         = $campaign['stock_scarcity_message_type'] ?? 'generalMessage';
		$general_settings     = $campaign['stock_scarcity_general_message_settings'] ?? array();
		$in_stock_message     = $general_settings['in_stock_message'] ?? '';
		$low_stock_message    = $general_settings['low_stock_message'] ?? '';
		$urgent_stock_message = $general_settings['urgent_stock_message'] ?? '';
		$is_low_stock         = $general_settings['isLowStockChecked'] ?? 'no';
		$is_urgent_stock      = $general_settings['isUrgentStockChecked'] ?? 'no';
		$enable_stock_bar     = $general_settings['enable_stock_bar'] ?? 'no';
		$enable_fake_stock    = $general_settings['enable_fake_stock'] ?? 'no';
		$repeat_interval      = $general_settings['repeat_interval'] ?? 'no';
		$low_stock_amount     = $general_settings['low_stock_amount'] ?? 0;
		$urgent_stock_amount  = $general_settings['urgent_stock_amount'] ?? 0;
		$in_stock_fake_amount = $general_settings['in_stock_fake_amount'] ?? 0;
		$low_fake_amount      = $general_settings['low_fake_amount'] ?? 0;
		$urgent_fake_amount   = $general_settings['urgent_fake_amount'] ?? 0;

		$flip_settings      = $campaign['stock_scarcity_flip_message_settings'] ?? array();
		$animation_settings = $campaign['stock_scarcity_animation_settings'] ?? array();
		$data               = array(
			'message_type'         => $message_type,
			'in_stock_message'     => $in_stock_message,
			'low_stock_message'    => $low_stock_message,
			'urgent_stock_message' => $urgent_stock_message,
			'is_low_stock'         => $is_low_stock,
			'is_urgent_stock'      => $is_urgent_stock,
			'enable_stock_bar'     => $enable_stock_bar,
			'enable_fake_stock'    => $enable_fake_stock,
			'repeat_interval'      => $repeat_interval,
			'low_stock_amount'     => $low_stock_amount,
			'urgent_stock_amount'  => $urgent_stock_amount,
			'in_stock_fake_amount' => $in_stock_fake_amount,
			'low_fake_amount'      => $low_fake_amount,
			'urgent_fake_amount'   => $urgent_fake_amount,
			'flip_settings'        => $flip_settings,
			'animation_settings'   => $animation_settings,
			'stock_quantity'       => $stock_quantity,
		);

		return $data;
	}

	/**
	 * Get distinct user count by product ID.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return int The distinct user count for the specified product.
	 */
	public function get_distinct_user_count_by_product( $product_id ) {
		global $wpdb;

		$product_id = intval( $product_id );

		$query = $wpdb->prepare(
			"
			SELECT COUNT(DISTINCT customer_id)
			FROM {$wpdb->prefix}wc_order_product_lookup
			WHERE product_id = %d
			  AND customer_id IS NOT NULL
		",
			$product_id
		);

		return (int) $wpdb->get_var( $query );
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
		} elseif ( is_cart() ) {
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
		} elseif ( is_checkout() ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product    = $cart_item['data'];
				$product_id = $cart_item['product_id'];

				$this->run_shortcode(
					$campaign,
					array(
						'display_type' => 'inpage',
						'position'     => '',
						'placement'    => 'checkout_page',
					)
				);
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
						$position = 'inpage';
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
			echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
