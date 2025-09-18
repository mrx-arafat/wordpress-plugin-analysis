<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Campaign: Buy X Get Y
 *
 * @package Revenue
 */

namespace Revenue;

//phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison


/**
 * Revenue Campaign: Buy X Get Y
 *
 * @hooked on init
 */
class Revenue_Buy_X_Get_Y {

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
	 * Defines the type of campaign being handled.
	 *
	 * @var string $campaign_type
	 *    The type of campaign, typically used to categorize or filter campaigns.
	 *    Default value is 'bundle_discount'.
	 */
	public $campaign_type = 'buy_x_get_y';



	/**
	 * Initializes actions and filters for handling revenue campaigns.
	 *
	 * This method sets up various hooks for managing campaign-related functionality:
	 * - **Before calculating cart totals**: Sets the discounted price on the cart items.
	 * - **After adding a trigger product to the cart**: Performs additional actions.
	 * - **Cart item quantity**: Adjusts the quantity of cart items based on campaign rules.
	 * - **Store API product quantity**: Sets minimum and maximum product quantities for the store API.
	 *
	 * Actions:
	 * - `revenue_campaign_{campaign_type}_before_calculate_cart_totals`: Calls `set_price_on_cart()` to set discounted prices before cart totals are calculated.
	 * - `revenue_campaign_{campaign_type}_added_to_cart`: Calls `after_trigger_product_added_to_cart()` when a trigger product is added to the cart.
	 *
	 * Filters:
	 * - `revenue_campaign_{campaign_type}_cart_item_quantity`: Applies `set_cart_item_quantity()` to adjust cart item quantities.
	 * - `revenue_campaign_{campaign_type}_store_api_product_quantity_minimum`: Applies `set_cart_item_quantity()` to set the minimum quantity in the store API.
	 * - `revenue_campaign_{campaign_type}_store_api_product_quantity_maximum`: Applies `set_cart_item_quantity()` to set the maximum quantity in the store API.
	 *
	 * @return void
	 */
	public function init() {
		// Set Discounted Price on Cart Before Calculate Totals.
		add_action( "revenue_campaign_{$this->campaign_type}_before_calculate_cart_totals", array( $this, 'set_price_on_cart' ), 10, 2 );
		add_action( "revenue_campaign_{$this->campaign_type}_added_to_cart", array( $this, 'after_trigger_product_added_to_cart' ), 10, 4 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_price", array( $this, 'cart_item_price' ), 9999, 2 );

		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_quantity", array( $this, 'set_cart_item_quantity' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_store_api_product_quantity_minimum", array( $this, 'set_cart_item_quantity' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_store_api_product_quantity_maximum", array( $this, 'set_cart_item_quantity' ), 10, 2 );
	}

	/**
	 * Adds a bundled product to the cart. Must be done without updating session data, recalculating totals or calling 'woocommerce_add_to_cart' recursively.
	 * For the recursion issue, see: https://core.trac.wordpress.org/ticket/17817.
	 *
	 * @param  mixed $product Product Object.
	 * @param  int   $quantity Quantity.
	 * @param  int   $variation_id Variation ID.
	 * @param  array $variation Variation Array.
	 * @param  array $cart_item_data Cart item Data.
	 * @return boolean
	 */
	private function add_to_cart( $product, $quantity = 1, $variation_id = '', $variation = array(), $cart_item_data = array() ) {

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
	 * Handles actions and updates when a trigger product is added to the cart.
	 *
	 * This method performs the following tasks:
	 * - Updates the list of trigger keys associated with the cart item.
	 * - Processes and adds offer products to the cart based on the trigger product.
	 * - Executes actions before and after adding bundled items to the cart.
	 *
	 * @param string $cart_item_key The unique key for the cart item being processed.
	 * @param array  $cart_item_data The data associated with the cart item.
	 * @param int    $product_id The ID of the product being processed.
	 * @param int    $bundle_quantity The quantity of the product being processed.
	 *
	 * @return void
	 */
	public function after_trigger_product_added_to_cart( $cart_item_key, $cart_item_data, $product_id, $bundle_quantity ) {

		if ( isset( $cart_item_data['revx_bxgy_last_trigger'], $cart_item_data['revx_offer_data'] ) ) {

			$trigger_keys   = $cart_item_data['revx_bxgy_all_triggers_key'];
			$trigger_keys[] = $cart_item_key;

			foreach ( $trigger_keys as $key ) {
				WC()->cart->cart_contents[ $key ]['revx_bxgy_all_triggers_key'] = $trigger_keys;
			}

			$offers = $cart_item_data['revx_offer_products'];

			foreach ( $offers as $offer_product_id => $qty ) {

				$item_quantity = $qty;

				$bundle_cart_data = array(
					'revx_campaign_id'     => $cart_item_data['revx_campaign_id'],
					'revx_bxgy_offer_qty'  => $item_quantity,
					'revx_campaign_type'   => $cart_item_data['revx_campaign_type'],
					'revx_bxgy_parents_id' => $cart_item_data['revx_bxgy_trigger_products'],
					'revx_offer_data'      => $cart_item_data['revx_offer_data'],
					'revx_bxgy_by'         => $trigger_keys,
					'revx_quantity_type'   => '',
					'rev_is_free_shipping' => $cart_item_data['rev_is_free_shipping'],
				);

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
				do_action( 'revenue_bxgy_item_before_add_to_cart', $product_id, $item_quantity, $variation_id, $variations, $cart_item_data );

				// Add to cart.
				$bundled_item_cart_key = $this->add_to_cart( $product, $item_quantity, $variation_id, $variations, $bundle_cart_data );

				foreach ( $trigger_keys as $key ) {
					if ( $bundled_item_cart_key && ! in_array( $bundled_item_cart_key, WC()->cart->cart_contents[ $key ]['revx_bxgy_items'] ) ) {
						WC()->cart->cart_contents[ $key ]['revx_bxgy_items'][] = $bundled_item_cart_key;
					}
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
				do_action( 'revenue_bxgy_item_after_add_to_cart', $product_id, $item_quantity, $variation_id, $variations, $bundle_cart_data );
			}
		}
	}

	/**
	 * Determines if a cart item is eligible for a discount based on parent items.
	 *
	 * This method checks whether the conditions for applying a discount are met based on the
	 * quantity of parent items in the cart. If all parent items meet the required conditions,
	 * the item is considered eligible for a discount.
	 *
	 * @param array $cart_item The data associated with the cart item being checked.
	 *
	 * @return bool True if the cart item is eligible for a discount, false otherwise.
	 */
	public function is_eligible_for_discount( $cart_item ) {

		$parents = isset( $cart_item['revx_bxgy_by'] ) ? $cart_item['revx_bxgy_by'] : array();

		// For Parent item.
		if ( isset( $cart_item['revx_required_qty'] ) && $cart_item['quantity'] >= $cart_item['revx_required_qty'] ) {
			return true;
		}

		if ( ! is_array( $parents ) ) {
			return false;
		}

		// Now check for parent exist and fulfill the conditions.

		$status = true;

		foreach ( $parents as $parent_key ) {
			$parent_item = revenue()->get_var( WC()->cart->cart_contents[ $parent_key ] );


			if ( ! ( $parent_item && isset( $parent_item['revx_required_qty'] ) && $parent_item['quantity'] >= $parent_item['revx_required_qty'] ) ) {
				$status = false;
				break;
			}
		}

		return $status;
	}


	/**
	 * Set Price on Cart
	 *
	 * @param array $cart_item Cart Item.
	 * @param int   $campaign_id Campaign ID.
	 * @return void
	 */
	public function set_price_on_cart( $cart_item, $campaign_id ) {

		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];

		if ( isset( $cart_item['revx_bxgy_by'] ) && $this->is_eligible_for_discount( $cart_item ) ) {
			

			$cart_item['revx_eligibility_status'] = 'yes';
			$offered_price                        = $cart_item['data']->get_regular_price( 'edit' );

			if ( is_array( $offers ) ) {
				foreach ( $offers as $offer ) {
					$offer_type  = '';
					$offer_value = '';

					if ( in_array( $product_id, $offer['products'] ) && $offer['quantity'] <= $cart_quantity ) {
						$offer_type  = $offer['type'];
						$offer_value = $offer['value'];
					} else {
						continue;
					}

					if ( 'free' == $offer['type'] ) {
						if ( $offer['quantity'] >= $cart_quantity ) {
							$offer_type  = $offer['type'];
							$offer_value = $offer['value'];
						} else {
							$offer_type  = '';
							$offer_value = '';
						}
					}
					if ( ( $offer_type && ( $offer_value || 'free' == $offer_type ) ) ) {
						$regular_price = $cart_item['data']->get_regular_price('edit');
						$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
					}

					$offered_price = apply_filters( 'revenue_campaign_buy_x_get_y_price', $offered_price, $product_id );
					$cart_item['data']->set_price( $offered_price );
				}
			}
		} else {
			$cart_item['revx_eligibility_status'] = 'no';
		}
	}

	/**
	 * Cart Item Price
	 *
	 * @param string $subtotal Subtotal.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function cart_item_price( $subtotal, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$subtotal = $this->get_y_item_price( $cart_item );

			if ( $cart_item['data']->get_regular_price() != $subtotal && $this->is_eligible_for_discount( $cart_item ) && isset( $cart_item['revx_bxgy_by'] ) ) {
				return '<del>' . wc_price( $cart_item['data']->get_regular_price() ) . '</del> ' . wc_price( $subtotal );
			}
			$subtotal = wc_price( $subtotal );
		}

		return $subtotal;

	}
	/**
	 * Get Y Item Price
	 *
	 * @param array $cart_item Cart Item.
	 * @return string|float
	 */
	public function get_y_item_price( $cart_item ) {

		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];

		$offered_price = $cart_item['data']->get_price( 'edit' );

		if ( isset( $cart_item['revx_bxgy_by'] ) && $this->is_eligible_for_discount( $cart_item ) ) {

			$cart_item['revx_eligibility_status'] = 'yes';
			$offered_price                        = $cart_item['data']->get_regular_price( );

			if ( is_array( $offers ) ) {
				foreach ( $offers as $offer ) {
					$offer_type  = '';
					$offer_value = '';

					if ( in_array( $product_id, $offer['products'] ) && $offer['quantity'] <= $cart_quantity ) {
						$offer_type  = $offer['type'];
						$offer_value = $offer['value'];
					} else {
						continue;
					}

					if ( 'free' == $offer['type'] ) {
						if ( $offer['quantity'] >= $cart_quantity ) {
							$offer_type  = $offer['type'];
							$offer_value = $offer['value'];
						} else {
							$offer_type  = '';
							$offer_value = '';
						}
					}
					if ( ( $offer_type && ( $offer_value || 'free' == $offer_type ) ) ) {
						$regular_price = $cart_item['data']->get_regular_price(  );
						$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
					}

					$offered_price = apply_filters( 'revenue_campaign_buy_x_get_y_price', $offered_price, $product_id );
				}
			}
		}

		return $offered_price;
	}

	/**
	 * Sets the quantity of a cart item based on its type and offer quantity.
	 *
	 * This method adjusts the quantity of a cart item if its quantity type is 'fixed'.
	 * If so, the quantity is set to the value specified in 'revx_bxgy_offer_qty'.
	 *
	 * @param int   $quantity   The current quantity of the cart item.
	 * @param array $cart_item The data associated with the cart item.
	 *
	 * @return int The adjusted quantity of the cart item.
	 */
	public function set_cart_item_quantity( $quantity, $cart_item ) {

		if ( isset( $cart_item['revx_quantity_type'], $cart_item['revx_bxgy_offer_qty'] ) && 'fixed' == $cart_item['revx_quantity_type'] ) {
			$quantity = $cart_item['revx_bxgy_offer_qty'];
		}

		return $quantity;
	}


	/**
	 * Outputs in-page views for a list of campaigns.
	 *
	 * This method processes and renders in-page views based on the provided campaigns.
	 * It adds each campaign to the `inpage` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data Additional data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_inpage_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;

			$this->current_position = $data['position'];

			$this->render_views( $data );
		}
	}

	/**
	 * Outputs popup views for a list of campaigns.
	 *
	 * This method processes and renders popup views based on the provided campaigns.
	 * It adds each campaign to the `popup` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data Additional data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_popup_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['popup'][] = $campaign;
			$this->render_views( $data );
		}
	}
	/**
	 * Outputs floating views for a list of campaigns.
	 *
	 * This method processes and renders floating views based on the provided campaigns.
	 * It adds each campaign to the `floating` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data Additional data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_floating_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['floating'][] = $campaign;
			$this->render_views( $data );
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
	 * @param array $data Additional data to be passed to the view.
	 *
	 * @return void
	 */
	public function render_views( $data = array() ) {
		global $product;
		global $post;

		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			foreach ( $campaigns as $campaign ) {

				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = REVENUE_PATH . 'includes/campaigns/views/buy-x-get-y/inpage.php';

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'buy_x_get_y', 'inpage', $campaign );


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

				$file_path = REVENUE_PATH . 'includes/campaigns/views/buy-x-get-y/popup.php';

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'buy_x_get_y', 'popup', $campaign );


				ob_start();
				if ( file_exists( $file_path ) ) {
					extract( $data );  //phpcs:ignore WordPress.PHP.DontExtract.extract_extract
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

				$file_path = REVENUE_PATH . 'includes/campaigns/views/buy-x-get-y/floating.php';

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'buy_x_get_y', 'floating', $campaign );


				ob_start();
				if ( file_exists( $file_path ) ) {
					extract( $data );  //phpcs:ignore WordPress.PHP.DontExtract.extract_extract
					include $file_path;
				}

				$output .= ob_get_clean();
			}

			if ( $output ) {
				echo wp_kses( $output, revenue()->get_allowed_tag() );
			}
		}
	}

}
