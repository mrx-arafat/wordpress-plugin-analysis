<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Campaign: Bundle Discount
 *
 * @package Revenue
 */

namespace Revenue;

//phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison

/**
 * Revenue Campaign: Bundle Discount
 *
 * @hooked on init
 */
class Revenue_Bundle_Discount {
	use SingletonTrait;


	/**
	 * Stores the campaigns to be rendered on the page.
	 *
	 * @var array|null $campaigns
	 *    An array of campaign data organized by view types (e.g., in-page, popup, floating),
	 *    or null if no campaigns are set.
	 */
	public $campaigns = null;

	/**
	 * Defines the type of campaign being handled.
	 *
	 * @var string $campaign_type
	 *    The type of campaign, typically used to categorize or filter campaigns.
	 *    Default value is 'bundle_discount'.
	 */
	public $campaign_type = 'bundle_discount';

	/**
	 * Keeps track of the current position for rendering in-page campaigns.
	 *
	 * @var string $current_position
	 *    The position within the page where in-page campaigns should be displayed.
	 *    Default is an empty string, indicating no position is set.
	 */
	public $current_position = '';

	/**
	 * Holds the key of the parent cart item that is being removed.
	 *
	 * @var string|null $removing_parent_key
	 *    The cart item key of the parent product being removed, or null if no such removal is in process.
	 */
	public static $removing_parent_key = null;


	/**
	 * Initializes hooks and filters for managing WooCommerce and Revenue campaign interactions.
	 *
	 * This method sets up various hooks and filters that are crucial for handling custom logic and
	 * interactions related to the Revenue campaign within WooCommerce. It registers filters and actions
	 * to modify cart item attributes, handle cart item management, and adjust order line item formatting.
	 *
	 * The following hooks and filters are added:
	 *
	 * - `woocommerce_is_purchasable`: Filters whether a product is purchasable, using the `make_parent_product_purchasable` method.
	 * - `revenue_campaign_{$this->campaign_type}_cart_item_remove_link`: Filters the remove link for cart items, using the `cart_item_remove_link` method.
	 * - `revenue_campaign_{$this->campaign_type}_cart_item_quantity`: Filters the quantity of cart items, using the `cart_item_quantity` method.
	 * - `revenue_campaign_{$this->campaign_type}_cart_item_class`: Filters the CSS class of cart items, using the `cart_item_class` method.
	 * - `revenue_campaign_{$this->campaign_type}_cart_item_subtotal`: Filters the subtotal of cart items, using the `cart_item_subtotal` method.
	 * - `revenue_campaign_{$this->campaign_type}_cart_item_price`: Filters the price of cart items, using the `cart_item_price` method.
	 * - `revenue_campaign_{$this->campaign_type}_cart_item_data`: Filters the data of cart items, using the `cart_item_data` method.
	 * - `revenue_campaign_{$this->campaign_type}_remove_cart_item`: Handles the removal of cart items, using the `remove_cart_item` method.
	 * - `revenue_campaign_{$this->campaign_type}_restore_cart_item`: Handles the restoration of cart items, using the `restore_cart_item` method.
	 * - `revenue_campaign_{$this->campaign_type}_added_to_cart`: Triggered after a bundle parent is added to the cart, using the `after_bundle_parent_added_to_cart` method.
	 * - `revenue_campaign_{$this->campaign_type}_after_item_quantity_updated`: Triggered after an item’s quantity is updated, using the `after_item_quantity_update` method.
	 * - `revenue_check_cart_items`: Validates bundle items in the cart, using the `validate_bundle_items` method.
	 * - `revenue_campaign_{$this->campaign_type}_before_calculate_cart_totals`: Executes before calculating cart totals, using the `before_calculate_cart_totals` method.
	 * - `woocommerce_order_formatted_line_subtotal`: Filters the formatted line subtotal of orders, using the `order_formatted_line_subtotal` method.
	 *
	 * @return void
	 */
	public function init() {

		add_filter( 'woocommerce_is_purchasable', array( $this, 'make_parent_product_purchasable' ), 10, 2 );

		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_remove_link", array( $this, 'cart_item_remove_link' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_quantity", array( $this, 'cart_item_quantity' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_class", array( $this, 'cart_item_class' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_subtotal", array( $this, 'cart_item_subtotal' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_price", array( $this, 'cart_item_price' ), 9999, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_data", array( $this, 'cart_item_data' ), 10, 2 );

		add_action( "revenue_campaign_{$this->campaign_type}_remove_cart_item", array( $this, 'remove_cart_item' ), 10, 2 );
		add_action( "revenue_campaign_{$this->campaign_type}_restore_cart_item", array( $this, 'restore_cart_item' ), 10, 2 );
		add_action( "revenue_campaign_{$this->campaign_type}_added_to_cart", array( $this, 'after_bundle_parent_added_to_cart' ), 10, 4 );
		add_action( "revenue_campaign_{$this->campaign_type}_after_item_quantity_updated", array( $this, 'after_item_quantity_update' ), 10, 3 );
		add_action( 'revenue_check_cart_items', array( $this, 'validate_bundle_items' ) );
		add_action( "revenue_campaign_{$this->campaign_type}_before_calculate_cart_totals", array( $this, 'before_calculate_cart_totals' ), 10, 3 );
		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'order_formatted_line_subtotal' ), 10, 3 );

	}

	/**
	 * Adds a bundled product to the cart. This process does not update session data, recalculate totals, or trigger recursive 'woocommerce_add_to_cart' calls.
	 * For details on avoiding recursion issues, see: https://core.trac.wordpress.org/ticket/17817.
	 *
	 * @param  int|WC_Product $product         Product ID or WC_Product object to be added to the cart.
	 * @param  int            $quantity        Quantity of the product to add.
	 * @param  int            $variation_id    Variation ID of the product (if applicable).
	 * @param  array          $variation       Variation attributes (if applicable).
	 * @param  array          $cart_item_data  Additional cart item data.
	 * @return string|false    Returns the cart item key if the item was successfully added, or false on failure.
	 */
	private function bundled_add_to_cart( $product, $quantity = 1, $variation_id = '', $variation = array(), $cart_item_data = array() ) {

		if ( $quantity <= 0 ) {
			return false;
		}

		// Get the product / ID.
		if ( is_a( $product, 'WC_Product' ) ) {

			$product_id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$variation_id = $product->is_type( 'variation' ) ? $product->get_id() : $variation_id;
			$product_data = $product->is_type( 'variation' ) ? $product : wc_get_product( $variation_id ? $variation_id : $product_id );

		} else {

			$product_id   = absint( $product );
			$product_data = wc_get_product( $product_id );

			if ( $product_data->is_type( 'variation' ) ) {
				$product_id   = $product_data->get_parent_id();
				$variation_id = $product_data->get_id();
			} else {
				$product_data = wc_get_product( $variation_id ? $variation_id : $product_id );
			}
		}

		if ( ! $product_data ) {
			return false;
		}

		// Load cart item data when adding to cart.
		$cart_item_data = (array) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id, $quantity ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Generate a ID based on product ID, variation ID, variation data, and other cart item data.
		$cart_id = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

		// See if this product and its options is already in the cart.
		$cart_item_key = WC()->cart->find_product_in_cart( $cart_id );

		// If cart_item_key is set, the item is already in the cart and its quantity will be handled by 'update_quantity_in_cart()'.
		if ( ! $cart_item_key ) {

			$cart_item_key = $cart_id;

			// Add item after merging with $cart_item_data - allow plugins and 'add_cart_item_filter()' to modify cart item.
			WC()->cart->cart_contents[ $cart_item_key ] = apply_filters(
				'woocommerce_add_cart_item',
				array_merge(
					$cart_item_data,
					array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					'key'          => $cart_item_key,
					'product_id'   => absint( $product_id ),
					'variation_id' => absint( $variation_id ),
					'variation'    => $variation,
					'quantity'     => $quantity,
					'data'         => $product_data,
					)
				),
				$cart_item_key
			);
		}

		/**
		 * 'revenue_bundled_add_to_cart' action.
		 *
		 * @see 'woocommerce_add_to_cart' action.
		 *
		 * @param  string  $cart_item_key
		 * @param  mixed   $bundled_product_id
		 * @param  int     $quantity
		 * @param  mixed   $variation_id
		 * @param  array   $variation_data
		 * @param  array   $cart_item_data
		 * @param  mixed   $bundle_id
		 */
		do_action( 'revenue_bundled_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $cart_item_data['revx_campaign_id'] );

		return $cart_item_key;
	}

	/**
	 * Triggered after a bundle parent product is added to the cart.
	 * Adds bundled items to the cart based on the bundle configuration.
	 *
	 * @param  string $cart_item_key        The cart item key of the bundle parent product.
	 * @param  array  $cart_item_data       Cart item data for the bundle parent product.
	 * @param  int    $product_id           ID of the bundle parent product.
	 * @param  int    $bundle_quantity      Quantity of the bundle parent product added.
	 * @return void
	 */
	public function after_bundle_parent_added_to_cart( $cart_item_key, $cart_item_data, $product_id, $bundle_quantity ) {

		if ( isset( $cart_item_data['revx_bundle_type'], $cart_item_data['revx_bundle_data'] ) && 'trigger' === $cart_item_data['revx_bundle_type'] ) {

			$bundler_offer_data = $cart_item_data['revx_bundle_data'];

			$trigger_product_id   = isset( $cart_item_data['revx_trigger_product_id'] ) ? $cart_item_data['revx_trigger_product_id'] : false;
			$has_trigger_on_offer = false;

			foreach ( $bundler_offer_data as $offer ) {
				foreach ( $offer['products'] as $offer_product_id ) {
					if ( $trigger_product_id == $offer_product_id ) {
						$has_trigger_on_offer = true;
						break;
					}
				}
			}

			if ( isset( $cart_item_data['revx_bundle_with_trigger'], $cart_item_data['revx_trigger_product_id'] ) && 'yes' === $cart_item_data['revx_bundle_with_trigger'] && ! $has_trigger_on_offer ) {

				$bundle_cart_data = array(
					'revx_campaign_id'     => $cart_item_data['revx_campaign_id'],
					'revx_campaign_type'   => $cart_item_data['revx_campaign_type'],
					'revx_bundle_id'       => $cart_item_data['revx_bundle_id'],
					'revx_bundle_type'     => 'offer',
					'revx_bundle_data'     => $cart_item_data['revx_bundle_data'],
					'revx_bundled_by'      => $cart_item_key,
					'rev_is_free_shipping' => $cart_item_data['rev_is_free_shipping'],
					'revx_min_qty'         => 1,
				);

				if ( isset( $cart_item_data['revx_trigger_pid'] ) ) {
					$bundle_cart_data['revx_trigger_pid'] = $cart_item_data['revx_trigger_pid'];
				}

				$offer_product_id = $cart_item_data['revx_trigger_product_id'];

				$item_quantity = $bundle_quantity;

				$product    = wc_get_product( $offer_product_id );
				$product_id = $product->get_id();

				if ( $product->is_type( array( 'simple', 'subscription' ) ) ) {
					$variation_id = '';
					$variations   = array();
				}

				/**
				 * 'revenue_bundled_item_before_add_to_cart' action.
				 *
				 * @param  int    $product_id
				 * @param  int    $item_quantity
				 * @param  int    $variation_id
				 * @param  array  $variations
				 * @param  array  $bundled_item_cart_data
				 */
				do_action( 'revenue_bundled_item_before_add_to_cart', $product_id, $item_quantity, $variation_id, $variations, $cart_item_data );

				// Add to cart.
				$bundled_item_cart_key = $this->bundled_add_to_cart( $product, $item_quantity, $variation_id, $variations, $bundle_cart_data );

				if ( $bundled_item_cart_key && ! in_array( $bundled_item_cart_key, WC()->cart->cart_contents[ $cart_item_key ]['revx_bundled_items'] ) ) {
					WC()->cart->cart_contents[ $cart_item_key ]['revx_bundled_items'][] = $bundled_item_cart_key;
				}

				/**
				 * 'revenue_bundled_item_after_add_to_cart' action.
				 *
				 * @param  int    $product_id
				 * @param  int    $quantity
				 * @param  int    $variation_id
				 * @param  array  $variations
				 * @param  array  $bundled_item_cart_data
				 */
				do_action( 'revenue_bundled_item_after_add_to_cart', $product_id, $item_quantity, $variation_id, $variations, $bundle_cart_data );
			}

			foreach ( $bundler_offer_data as $offer ) {
				foreach ( $offer['products'] as $offer_product_id ) {

					$bundle_cart_data = array(
						'revx_campaign_id'     => $cart_item_data['revx_campaign_id'],
						'revx_campaign_type'   => $cart_item_data['revx_campaign_type'],
						'revx_bundle_id'       => $cart_item_data['revx_bundle_id'],
						'revx_bundle_type'     => 'offer',
						'revx_bundle_data'     => $cart_item_data['revx_bundle_data'],
						'revx_bundled_by'      => $cart_item_key,
						'revx_min_qty'         => $offer['quantity'],
						'rev_is_free_shipping' => $cart_item_data['rev_is_free_shipping'],
					);

					if ( isset( $cart_item_data['revx_trigger_pid'] ) ) {
						$bundle_cart_data['revx_trigger_pid'] = $cart_item_data['revx_trigger_pid'];

					}
					if ( isset( $offer['quantity'] ) && absint( $offer['quantity'] ) === 0 ) {
						continue;
					}

					$item_quantity = $offer['quantity'] * $bundle_quantity;

					$product    = wc_get_product( $offer_product_id );
					$product_id = $product->get_id();

					if ( $product->is_type( array( 'simple', 'subscription' ) ) ) {
						$variation_id = '';
						$variations   = array();
					}

					/**
					 * 'revenue_bundled_item_before_add_to_cart' action.
					 *
					 * @param  int    $product_id
					 * @param  int    $item_quantity
					 * @param  int    $variation_id
					 * @param  array  $variations
					 * @param  array  $bundled_item_cart_data
					 */
					do_action( 'revenue_bundled_item_before_add_to_cart', $product_id, $item_quantity, $variation_id, $variations, $cart_item_data );

					// Add to cart.
					$bundled_item_cart_key = $this->bundled_add_to_cart( $product, $item_quantity, $variation_id, $variations, $bundle_cart_data );

					if ( $bundled_item_cart_key && ! in_array( $bundled_item_cart_key, WC()->cart->cart_contents[ $cart_item_key ]['revx_bundled_items'] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ]['revx_bundled_items'][] = $bundled_item_cart_key;
					}

					/**
					 * 'revenue_bundled_item_after_add_to_cart' action.
					 *
					 * @param  int    $product_id
					 * @param  int    $quantity
					 * @param  int    $variation_id
					 * @param  array  $variations
					 * @param  array  $bundled_item_cart_data
					 */
					do_action( 'revenue_bundled_item_after_add_to_cart', $product_id, $item_quantity, $variation_id, $variations, $bundle_cart_data );
				}
			}
		}
	}


	/**
	 * Handles updates to the quantity of a bundle parent item in the cart.
	 * Adjusts the quantities of all bundled items based on the updated quantity of the bundle parent item.
	 *
	 * @param  array  $cart_item        The cart item data.
	 * @param  string $cart_item_key    The cart item key of the bundle parent item.
	 * @param  int    $quantity         The new quantity of the bundle parent item.
	 * @return void
	 */
	public function after_item_quantity_update( $cart_item, $cart_item_key, $quantity ) {
		if ( 0 == $quantity || $quantity < 0 ) {
			$quantity = 0;
		} else {
			$quantity = $cart_item['quantity'];
		}

		if ( self::is_bundle_parent_item( $cart_item ) && isset( $cart_item['revx_bundled_items'] ) ) {
			// Get bundled cart items.
			$bundled_cart_items = revenue()->get_bundled_cart_items( $cart_item );
			$bundle_qty         = $cart_item['quantity'];

			// Change the quantity of all bundled items that belong to the same bundle config.
			if ( ! empty( $bundled_cart_items ) ) {
				foreach ( $bundled_cart_items as $key => $value ) {
					if ( $value['data']->is_sold_individually() && $bundle_qty > 0 ) {
						WC()->cart->set_quantity( $key, 1, false );
					} else {
						WC()->cart->set_quantity( $key, $value['revx_min_qty'] * $bundle_qty, false );
					}
				}
			}
		}
	}


	/**
	 * Adjusts cart item prices based on campaign offers before calculating cart totals.
	 * Updates the price of cart items if they match the campaign offers.
	 *
	 * @param  array   $cart_item      The cart item data.
	 * @param  int     $campaign_id    ID of the campaign associated with the cart item.
	 * @param  WC_Cart $cart           The WooCommerce cart object.
	 * @return void
	 */
	public function before_calculate_cart_totals( $cart_item, $campaign_id, $cart ) {
		$cart_quantity = false;
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! isset( $cart_item['revx_campaign_id'] ) ) {
				continue;
			}
			$campaign_id   = intval( $cart_item['revx_campaign_id'] );
			$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
			$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$variation_id  = $cart_item['variation_id'];
			$cart_quantity = $cart_item['quantity'];

			$offered_price = $cart_item['data']->get_regular_price( 'edit' );

			if ( is_array( $offers ) ) {
				$offer_type  = '';
				$offer_value = '';

				// If bundle discount then check bundle with trigger product, if yes then add trigger product into offer.
				foreach ( $offers as $offer ) {

					$offered_products = $offer['products'];

					if ( isset( $cart_item['revx_trigger_pid'] ) && $product_id == $cart_item['revx_trigger_pid'] ) {

						$offered_products[] = $product_id;
					}

					if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
						$offer_type  = isset( $offer['type'] ) ? $offer['type'] : '';
						$offer_value = isset( $offer['value'] ) ? $offer['value'] : '';
					}
				}

				if ( $offer_type && ( 'free' == $offer_type || $offer_value ) ) {
					$regular_price = $cart_item['data']->get_regular_price('edit' );
					$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
				}
			}
			$cart_item['revx_offered_price'] = $offered_price;

			$cart_item['data']->set_price( $offered_price );

		}
	}

	/**
	 * Outputs views for in-page campaign placements.
	 *
	 * Renders campaign views based on their position within the page.
	 *
	 * @param  array $campaigns Array of campaign data to output.
	 * @param  array $data      Additional data to pass to the view templates.
	 * @return void
	 */
	public function output_inpage_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {

			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;
			$this->current_position                           = $data['position'];
			$this->render_views( $data );
		}

	}

	/**
	 * Outputs views for popup campaign placements.
	 *
	 * Enqueues necessary scripts and styles, then renders popup campaign views.
	 *
	 * @param  array $campaigns Array of campaign data to output.
	 * @param  array $data      Additional data to pass to the view templates.
	 * @return void
	 */
	public function output_popup_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$this->campaigns['popup'][] = $campaign;

			$this->render_views( $data );
		}

	}

	/**
	 * Outputs views for floating campaign placements.
	 *
	 * Enqueues necessary scripts and styles, then renders floating campaign views.
	 *
	 * @param  array $campaigns Array of campaign data to output.
	 * @param  array $data      Additional data to pass to the view templates.
	 * @return void
	 */
	public function output_floating_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['floating'][] = $campaign;
			$this->render_views( $data );
		}
	}

	/**
	 * Renders the views for in-page, popup, and floating campaigns.
	 *
	 * Includes the appropriate template files for each campaign type and outputs the rendered HTML.
	 *
	 * @param array $data Data.
	 *
	 * @return void
	 */
	public function render_views( $data = array() ) {
		global $product;
		global $post;

		$id = false;
		if ( ! $product ) {
			$id = $post->ID;
		} else {
			$id = $product->get_id();
		}

		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			foreach ( $campaigns as $campaign ) {

				revenue()->update_campaign_impression( $campaign['id'], $id );

				$file_path = REVENUE_PATH . 'includes/campaigns/views/bundle-discount/inpage.php';
				$file_path = apply_filters( 'revenue_bundle_discount_views_file_path', $file_path, 'inpage', $campaign );
				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'bundle_discount', 'inpage', $campaign );

				ob_start();
				if ( file_exists( $file_path ) ) {
					extract( $data ); //phpcs:ignore WordPress.PHP.DontExtract.extract_extract
					include $file_path;
				}

				$output .= ob_get_clean();
			}

			if ( $output ) {
				echo wp_kses( $output, revenue()->get_allowed_tag() );
			}
		}

		if ( ! empty( $this->campaigns['popup'] ) ) {

			wp_enqueue_script( 'revenue-popup' );
			wp_enqueue_style( 'revenue-popup' );

			$output    = '';
			$campaigns = $this->campaigns['popup'];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;

				revenue()->update_campaign_impression( $campaign['id'] );

				$file_path = REVENUE_PATH . 'includes/campaigns/views/bundle-discount/popup.php';
				$file_path = apply_filters( 'revenue_bundle_discount_views_file_path', $file_path, 'popup', $campaign );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'bundle_discount', 'popup', $campaign );

				ob_start();
				if ( file_exists( $file_path ) ) {
					extract( $data ); //phpcs:ignore WordPress.PHP.DontExtract.extract_extract
					include $file_path;
				}

				$output .= ob_get_clean();
			}

			if ( $output ) {
				echo wp_kses( $output, revenue()->get_allowed_tag() );
			}
		}

		if ( ! empty( $this->campaigns['floating'] ) ) {

			wp_enqueue_script( 'revenue-floating' );
			wp_enqueue_style( 'revenue-floating' );

			$output    = '';
			$campaigns = $this->campaigns['floating'];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;

				revenue()->update_campaign_impression( $campaign['id'] );

				$file_path = REVENUE_PATH . 'includes/campaigns/views/bundle-discount/floating.php';
				$file_path = apply_filters( 'revenue_bundle_discount_views_file_path', $file_path, 'floating', $campaign );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'bundle_discount', 'floating', $campaign );

				ob_start();
				if ( file_exists( $file_path ) ) {
					extract( $data ); //phpcs:ignore WordPress.PHP.DontExtract.extract_extract
					include $file_path;
				}

				$output .= ob_get_clean();
			}

			if ( $output ) {
				echo wp_kses( $output, revenue()->get_allowed_tag() );
			}
		}

	}


	/**
	 * Make parent product purchasable.
	 *
	 * @param bool      $status Status.
	 * @param WC_Prouct $product Product Object.
	 * @return bool
	 */
	public function make_parent_product_purchasable( $status, $product ) {
		$product_id = $this->get_bundle_product_id();
		if ( $product->get_id() == $product_id ) {
			$status = true;
		}

		return $status;
	}


	/**
	 * Get Bundle Product ID
	 *
	 * @return string
	 */
	public function get_bundle_product_id() {
		$product_id = get_option( 'revenue_bundle_parent_product_id', false ); // __revx_bundle_dummy_product_id
		$prod = wc_get_product($product_id);
		if($prod) {
			$post = get_post( $product_id );
			if ( $post->post_status === 'trash' ) {
				wp_untrash_post( $product_id );
			}
			return $product_id;
		} else {
			$product_id = $this->create_bundle_parent_product_if_not_created();
		}
		return $product_id;
	}

	/**
	 * Create Bundle Parent Product If Not Created
	 *
	 * @return string
	 */
	public function create_bundle_parent_product_if_not_created() {
		$product_id = get_option( 'revenue_bundle_parent_product_id', false );

		if ( $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				$product_id = 0;
			}
		}
		if ( ! $product_id ) {

			$sku                 = 'revenue_bundle_product';
			$existing_product_id = $this->get_product_id_by_sku( $sku );

			// If an existing product is found, use its ID.
			if ( $existing_product_id ) {
				update_option( 'revenue_bundle_parent_product_id', $existing_product_id );
				return $existing_product_id;
			}
			$product_attr = array(
				'post_title'   => wc_clean( 'Revenue Bundle' ),
				'post_status'  => 'private',
				'post_type'    => 'product',
				'post_excerpt' => '',
				'post_content' => 'Revenue Auto Generate Product For Bundle Discount',
				'post_author'  => get_current_user_id(),
			);
			$product_id   = wp_insert_post( $product_attr );
			if ( ! is_wp_error( $product_id ) ) {
				$product = wc_get_product( $product_id );
				wp_set_object_terms( $product_id, 'simple', 'product_type' );
				update_post_meta( $product_id, '_stock_status', 'instock' );
				update_post_meta( $product_id, 'total_sales', '0' );
				update_post_meta( $product_id, '_downloadable', 'no' );
				update_post_meta( $product_id, '_virtual', 'yes' );
				update_post_meta( $product_id, '_regular_price', '' );
				update_post_meta( $product_id, '_sale_price', '' );
				update_post_meta( $product_id, '_purchase_note', '' );
				update_post_meta( $product_id, '_featured', 'no' );
				update_post_meta( $product_id, '_weight', '' );
				update_post_meta( $product_id, '_length', '' );
				update_post_meta( $product_id, '_width', '' );
				update_post_meta( $product_id, '_height', '' );
				update_post_meta( $product_id, '_sku', 'revenue_bundle_product' );
				update_post_meta( $product_id, '_product_attributes', array() );
				update_post_meta( $product_id, '_sale_price_dates_from', '' );
				update_post_meta( $product_id, '_sale_price_dates_to', '' );
				update_post_meta( $product_id, '_price', '' );
				update_post_meta( $product_id, '_sold_individually', 'no' );
				update_post_meta( $product_id, '_manage_stock', 'no' );
				update_post_meta( $product_id, '_backorders', 'no' );
				update_post_meta( $product_id, '_stock', '' );
				$product->set_reviews_allowed( false );
				$product->set_catalog_visibility( 'hidden' );
				$product->save();

				update_option( 'revenue_bundle_parent_product_id', $product_id );
				return $product_id;
			}
		}
	}

	/**
	 * Get Product ID by SKU
	 *
	 * @param string $sku SKU.
	 * @return int
	 */
	private function get_product_id_by_sku( $sku ) {
		global $wpdb;
		$product_id = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key='_sku' AND meta_value=%s LIMIT 1",
				$sku
			)
		);
		return $product_id ? intval( $product_id ) : false;
	}
	/**
	 * Set Remove Link empty on Bundle Child Items as Child item cannot be removed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link Remove Link.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function cart_item_remove_link( $link, $cart_item ) {

		if ( self::is_bundle_child_item( $cart_item ) ) {
			$link = '';
		}

		return $link;
	}
	/**
	 * Set Bundle child item quantity static so that individual child quantity cannot be changed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $quantity Quantity.
	 * @param array  $cart_item Cart Item.
	 * @return string|int
	 */
	public function cart_item_quantity( $quantity, $cart_item ) {

		if ( self::is_bundle_child_item( $cart_item ) ) {
			$quantity = $cart_item['quantity'];
		}

		return $quantity;
	}

	/**
	 * Add custom class on bundle child and parent item
	 *
	 * @since 1.0.0
	 *
	 * @param string $classname Classname.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function cart_item_class( $classname, $cart_item ) {

		if ( self::is_bundle_child_item( $cart_item ) ) {
			$classname .= ' revx-bundle-child-item';
		}
		if ( self::is_bundle_parent_item( $cart_item ) ) {
			$classname .= ' revx-bundle-parent-item';
		}

		return $classname;
	}


	/**
	 * Set Cart Item price on bundle parent item
	 *
	 * @since 1.0.0
	 *
	 * @param string $price price.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function cart_item_subtotal( $price, $cart_item ) {

		if ( self::is_bundle_parent_item( $cart_item ) ) {
			$price = wc_price( self::get_parent_cart_item_price( $price, $cart_item, true ) );
		} elseif ( self::is_part_of_bundle( $cart_item ) ) {
			$price = '';
		}
		return $price;
	}
	/**
	 * Set Cart Item Subtotal on bundle parent item
	 *
	 * @since 1.0.0
	 *
	 * @param string $subtotal Subtotal.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function cart_item_price( $subtotal, $cart_item ) {

		if ( self::is_bundle_parent_item( $cart_item ) ) {
			$regular_price    = self::get_parent_cart_item_regular_price( $subtotal, $cart_item );
			$discounted_price = self::get_parent_cart_item_price( $subtotal, $cart_item );
			if ( $regular_price != $discounted_price ) {
				$subtotal = '<del>' . wc_price( $regular_price ) . '</del>' . wc_price( $discounted_price );
			}
		} elseif ( self::is_part_of_bundle( $cart_item ) ) {
			$subtotal = wc_price( self::get_bundle_item_price( $cart_item ) );
		}
		return $subtotal;
	}


	/**
	 * Get Bundle Item Price.
	 *
	 * @param array $cart_item Cart Item.
	 * @return string|float
	 */
	public static function get_bundle_item_price( $cart_item ) {
		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];

		$offered_price = $cart_item['data']->get_regular_price();

		if ( is_array( $offers ) ) {
			$offer_type  = '';
			$offer_value = '';

			// If bundle discount then check bundle with trigger product, if yes then add trigger product into offer.
			foreach ( $offers as $offer ) {

				$offered_products = $offer['products'];

				if ( isset( $cart_item['revx_trigger_pid'] ) && $product_id == $cart_item['revx_trigger_pid'] ) {

					$offered_products[] = $product_id;
				}

				if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
					$offer_type  = isset( $offer['type'] ) ? $offer['type'] : '';
					$offer_value = isset( $offer['value'] ) ? $offer['value'] : '';
				}
			}

			if ( $offer_type && ( 'free' == $offer_type || $offer_value ) ) {
				$regular_price = $cart_item['data']->get_regular_price();
				$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
			}
		}
		return $offered_price;
	}

	/**
	 * Set Custom Cart Item Data
	 *
	 * @param array $data Cart Item Data.
	 * @param array $cart_item Cart Item.
	 * @return array
	 */
	public function cart_item_data( $data, $cart_item ) {
		if ( self::is_bundle_child_item( $cart_item ) && isset( self::get_bundled_parent_cart_item_container( $cart_item )['data'] ) ) {
			$data[] = array(
				'key'   => __( 'Bundle of ', 'revenue' ),
				'value' => self::get_bundled_parent_cart_item_container( $cart_item )['data']->get_title(),
			);
		}

		return $data;

	}

	/**
	 * Remove Cart Item
	 * When Parent Item is being removed, then Remove all child item of this parent
	 *
	 * @param string $remove_item_key Remove Item Key.
	 * @param string $cart_item Cart item.
	 * @return void
	 */
	public function remove_cart_item( $remove_item_key, $cart_item ) {

		if ( self::is_bundle_parent_item( $cart_item ) && ! self::is_removing_bundle_parent_item( $remove_item_key ) ) {
			$cart     = WC()->cart;
			$children = self::get_bundled_child_cart_items( $cart_item, $cart->cart_contents, true );
			foreach ( $children as $child_key ) {

				$will_be_remove_key = $cart->cart_contents[ $child_key ];

				$cart->removed_cart_contents[ $child_key ] = $will_be_remove_key;

				unset( $cart->cart_contents[ $child_key ]['data'] );

				// Prevent Infinite Loop
				// @alert: Be careful, any changes might be occure infine loop.
				self::$removing_parent_key = $remove_item_key;

				do_action( 'woocommerce_remove_cart_item', $child_key, $cart ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				self::$removing_parent_key = null;

				unset( $cart->cart_contents[ $child_key ] );

				/** Triggered when bundled item is removed from the cart.
				 *
				 * @since  1.0.0
				 *
				 * @hint   Bypass WC_Cart::remove_cart_item to avoid issues with performance and loops.
				 *
				 * @param  string  $bundled_item_cart_key
				 * @param  WC_Cart $cart
				 */
				do_action( 'revenue_bundled_cart_item_removed', $child_key, $cart );
			}
		}

	}
	/**
	 * Restore Cart Item
	 * When Parent Item is being restore, then restore all child item of this parent
	 *
	 * @param string $restore_item_key Restore Item Key.
	 * @param string $cart_item Cart item.
	 * @return void
	 */
	public function restore_cart_item( $restore_item_key, $cart_item ) {

		if ( self::is_bundle_parent_item( $cart_item ) ) {
			$cart     = WC()->cart;
			$children = self::get_bundled_child_cart_items( $cart_item, $cart->removed_cart_contents, true );
			foreach ( $children as $child_key ) {

				$restore = $cart->removed_cart_contents[ $child_key ];

				if ( ! isset( $restore_item['data'] ) ) {
					$restore_item['data'] = wc_get_product( $restore['variation_id'] ? $restore['variation_id'] : $restore['product_id'] );
				}

				$cart->cart_contents[ $child_key ] = $restore;

				/** WC core action. */
				do_action( 'woocommerce_restore_cart_item', $child_key, $cart ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				unset( $cart->removed_cart_contents[ $child_key ] );

				/** WC core action. @see WC_Cart::restore_cart_item
				 *
				 * @since  1.0.0
				 *
				 * @param  string  $child_key
				 * @param  WC_Cart $cart
				 */
				do_action( 'woocommerce_cart_item_restored', $child_key, $cart ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			}
		}

	}

	/**
	 * Validate Bundle Items.
	 *
	 * @param object $cart Cart Great.
	 * @return void
	 */
	public function validate_bundle_items( $cart ) {
		foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
			if ( self::is_bundle_parent_item( $cart_item ) ) {
				$child_items = $cart_item['revx_bundled_items'];
				$no_found    = false;
				foreach ( $child_items as $child_key ) {
					if ( ! isset( $cart->cart_contents[ $child_key ] ) ) {
						$no_found = true;
						break;
					}
				}

				if ( $no_found ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			} elseif ( self::is_bundle_child_item( $cart_item ) ) {

				$parent_key = $cart_item['revx_bundled_by'];

				if ( ! isset( WC()->cart->cart_contents[ $parent_key ] ) ) {
					// Parent Items does not exist.
					WC()->cart->remove_cart_item( $cart_item_key );

				}
			}
		}
	}


	/**
	 * Set Bundle Parent Item Subtotal by aggregating child item price
	 *
	 * @param string   $subtotal Subtotal Price.
	 * @param array    $item Order item.
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public function order_formatted_line_subtotal( $subtotal, $item, $order ) {

		if ( self::is_bundle_parent_item( $item ) ) {
			$tax_display = get_option( 'woocommerce_tax_display_cart' );

			$children = self::get_bundled_order_child_items( $item, $order );

			$_subtotal = 0.0;
			foreach ( $children as $child ) {
				$_subtotal += $order->get_line_subtotal( $child, 'excl' === $tax_display ? false : true );
			}

			if ( 'excl' === $tax_display ) {
				$ex_tax_label = $order->get_prices_include_tax() ? 1 : 0;

				$subtotal = wc_price(
					$_subtotal,
					array(
						'ex_tax_label' => $ex_tax_label,
						'currency'     => $order->get_currency(),
					)
				);
			} else {
				$subtotal = wc_price( $_subtotal, array( 'currency' => $order->get_currency() ) );
			}
		}
		return $subtotal;
	}




	/**
	 * Check Given Cart item is Bundle child item or not.
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_item Cart Item to be check.
	 * @return boolean
	 */
	public static function is_bundle_child_item( $cart_item ) {
		return ( isset( $cart_item['revx_bundle_type'] ) && 'offer' === $cart_item['revx_bundle_type'] );
	}

	/**
	 * Check Given Cart item is Bundle parent item or not.
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_item Cart Item to be check.
	 * @return boolean
	 */
	public static function is_bundle_parent_item( $cart_item ) {
		return ( isset( $cart_item['revx_bundle_type'] ) && 'trigger' === $cart_item['revx_bundle_type'] );
	}


	/**
	 * Given a bundle container cart item, find and return its child cart items - or their cart ids when the $return_ids arg is true.
	 *
	 * @since  1.0.0
	 *
	 * @param  array   $container_cart_item Container Cart Item.
	 * @param  array   $cart_contents Cart Contents.
	 * @param  boolean $return_ids Return IDs.
	 * @return mixed
	 */
	public static function get_bundled_child_cart_items( $container_cart_item, $cart_contents = false, $return_ids = false ) {

		if ( ! $cart_contents ) {
			$cart_contents = isset( WC()->cart ) ? WC()->cart->cart_contents : array();
		}

		$bundled_cart_items = array();

		if ( self::is_bundle_parent_item( $container_cart_item ) ) {

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
	 * Get Bundle Parent Cart Item Price
	 *
	 * @param string  $price Price.
	 * @param array   $cart_item Cart Item.
	 * @param boolean $is_subtotal Is Subtotal.
	 * @return string
	 */
	public static function get_parent_cart_item_price( $price, $cart_item, $is_subtotal = false ) {
		if ( ! isset( WC()->cart ) ) {
			return 0.0;
		}
		if ( empty( $price ) ) {
			$price = 0.0;
		}
		$price = floatval( $price );
		$cart  = WC()->cart->get_cart();
		if ( self::is_bundle_parent_item( $cart_item ) ) {
			$bundle_items = self::get_bundled_child_items( $cart_item, false, true );

			foreach ( $bundle_items as $bundle_item_key ) {
				$bundle_cart_item = ( isset( $cart[ $bundle_item_key ] ) ) ? $cart[ $bundle_item_key ] : false;

				if ( $is_subtotal ) {
					$price += $bundle_cart_item ? $bundle_cart_item['quantity'] * floatval( self::get_bundle_item_price( $bundle_cart_item ) ) : 0;
				} else {
					$price += $bundle_cart_item ? floatval( self::get_bundle_item_price( $bundle_cart_item ) ) : 0;
				}
			}
		}

		return $price;
	}
	/**
	 * Get Bundle Parent Cart Item Regular Price
	 *
	 * @param string  $price Price.
	 * @param array   $cart_item Cart Item.
	 * @param boolean $is_subtotal Is Subtotal.
	 * @return string
	 */
	public static function get_parent_cart_item_regular_price( $price, $cart_item, $is_subtotal = false ) {
		if ( ! isset( WC()->cart ) ) {
			return 0.0;
		}
		if ( empty( $price ) ) {
			$price = 0.0;
		}
		$price = floatval( $price );
		$cart  = WC()->cart->get_cart();
		if ( self::is_bundle_parent_item( $cart_item ) ) {
			$bundle_items = self::get_bundled_child_items( $cart_item, false, true );

			foreach ( $bundle_items as $bundle_item_key ) {
				$bundle_cart_item = ( isset( $cart[ $bundle_item_key ] ) ) ? $cart[ $bundle_item_key ] : false;

				if ( $is_subtotal ) {
					$price += $bundle_cart_item ? $bundle_cart_item['quantity'] * floatval( $bundle_cart_item['data']->get_regular_price() ) : 0;
				} else {
					$price += $bundle_cart_item ? floatval( $bundle_cart_item['data']->get_regular_price() ) : 0;
				}
			}
		}

		return $price;
	}


	/**
	 * Given a bundle container cart item, find and return its child cart items - or their cart ids when the $return_ids arg is true.
	 *
	 * @since  1.0.0
	 *
	 * @param  array   $container_cart_item Container Cart Item.
	 * @param  array   $cart_contents Cart Contents.
	 * @param  boolean $return_ids Return Ids.
	 * @return mixed
	 */
	public static function get_bundled_child_items( $container_cart_item, $cart_contents = false, $return_ids = false ) {

		if ( ! $cart_contents ) {
			$cart_contents = isset( WC()->cart ) ? WC()->cart->cart_contents : array();
		}

		$bundled_cart_items = array();

		if ( self::is_bundle_parent_item( $container_cart_item ) ) {

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
	 * Given a bundle container order item, find and return its child order items - or their order item ids when the $return_ids arg is true.
	 *
	 * @since  1.0.0
	 *
	 * @param  WC_Order_Item $container_order_item Containre Oder Item.
	 * @param  WC_Order      $order Order.
	 * @param  boolean       $return_ids Return Ids.
	 * @return mixed
	 */
	public static function get_bundled_order_child_items( $container_order_item, $order = false, $return_ids = false ) {

		$bundled_order_items = array();

		if ( self::is_bundle_parent_item( $container_order_item ) ) {

			$bundled_cart_keys = maybe_unserialize( $container_order_item['revx_bundled_items'] );

			if ( ! empty( $bundled_cart_keys ) && is_array( $bundled_cart_keys ) ) {

				if ( false === $order ) {
					if ( is_callable( array( $container_order_item, 'get_order' ) ) ) {

						$order_id = $container_order_item->get_order_id();
						$order    = wc_get_order( $order_id );

						if ( null === $order ) {
							$order = $container_order_item->get_order();
						}
					}
				}

				$order_items = is_object( $order ) ? $order->get_items( 'line_item' ) : $order;

				if ( ! empty( $order_items ) ) {
					foreach ( $order_items as $order_item_id => $order_item ) {

						$is_child = false;

						if ( isset( $order_item['revx_cart_key'] ) ) {
							$is_child = in_array( $order_item['revx_cart_key'], $bundled_cart_keys ) ? true : false;
						} else {
							$is_child = isset( $order_item['revx_bundle_data'] ) && $order_item['revx_bundle_data'] == $container_order_item['revx_bundle_data'] && isset( $order_item['revx_bundled_by'] ) ? true : false;
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
	 * True if a cart item appears to be part of a bundle.
	 * The result is purely based on cart item data - the function does not check that a valid parent item actually exists.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $cart_item Cart Item.
	 * @return boolean
	 */
	public static function is_part_of_bundle( $cart_item ) {

		$is_bundled = false;

		if ( ! empty( $cart_item['revx_bundle_id'] ) && ! empty( $cart_item['revx_bundle_type'] ) ) {
			$is_bundled = true;
		}

		return $is_bundled;
	}

	/**
	 * Given a bundled cart item, find and return its container cart item - the Bundle - or its cart id when the $return_id arg is true.
	 *
	 * @since  1.0.0
	 *
	 * @param  array   $bundled_cart_item Bundled Cart Item.
	 * @param  array   $cart_contents Cart Contents.
	 * @param  boolean $return_id Return ids.
	 * @return mixed
	 */
	public static function get_bundled_parent_cart_item_container( $bundled_cart_item, $cart_contents = false, $return_id = false ) {

		if ( ! $cart_contents ) {
			$cart_contents = isset( WC()->cart ) ? WC()->cart->cart_contents : array();
		}

		$container = false;

		if ( self::is_part_of_bundle( $bundled_cart_item ) ) {

			$bundled_by = $bundled_cart_item['revx_bundled_by'];

			if ( isset( $cart_contents[ $bundled_by ] ) ) {
				$container = $return_id ? $bundled_by : $cart_contents[ $bundled_by ];
			}
		}

		return $container;
	}


	/**
	 * Check if given cart item is currenly removing or not
	 *
	 * @param string $cart_item_key Cart Item Key.
	 * @return boolean
	 */
	public static function is_removing_bundle_parent_item( $cart_item_key ) {
		return self::$removing_parent_key === $cart_item_key;
	}


}
