<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Campaign: Volume Discount
 *
 * This class handles the volume discount campaigns, including setting discount prices on the cart,
 * rendering views for different campaign types (in-page, popup, floating), and processing shortcodes.
 *
 * @package Revenue
 * @hooked on init
 */

namespace Revenue;

//phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison


/**
 * Revenue Campaign: Volume Discount
 *
 * This class handles the volume discount campaigns, including setting discount prices on the cart,
 * rendering views for different campaign types (in-page, popup, floating), and processing shortcodes.
 *
 * @package Revenue
 * @hooked on init
 */
class Revenue_Volume_Discount {
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
	 *    Default value is 'volume_discount'.
	 */
	public $campaign_type = 'volume_discount';


	/**
	 * Initializes the class by setting up necessary hooks.
	 *
	 * This method adds actions related to the volume discount campaign type, such as setting the discounted
	 * price on the cart before calculating totals.
	 *
	 * @return void
	 */
	public function init() {

		// Set Discounted Price on Cart Before Calculate Totals.
		add_action( "revenue_campaign_{$this->campaign_type}_before_calculate_cart_totals", array( $this, 'set_price_on_cart' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_price", array( $this, 'cart_item_price' ), 9999, 2 );
	}


	/**
	 * Sets the discounted price for a cart item based on the active campaign.
	 *
	 * This method calculates the offered price for a cart item based on the campaign's offer type and value.
	 * It updates the item's price in the cart accordingly.
	 *
	 * @param array $cart_item    The cart item data.
	 * @param int   $campaign_id  The ID of the campaign applied to the cart item.
	 *
	 * @return void
	 */
	public function set_price_on_cart( $cart_item, $campaign_id ) {

		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];

		$offered_price = $cart_item['data']->get_regular_price( 'edit' );

		if ( is_array( $offers ) ) {
			$offer_type         = '';
			$offer_value        = '';
			$offer_qty          = '';
			$offered_products[] = $product_id;

			foreach ( $offers as $offer ) {

				if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
					$offer_type  = $offer['type'];
					$offer_value = isset( $offer['value'] ) ? $offer['value'] : null;
					$offer_qty   = intval( $offer['quantity'] );
				}
			}

			if ( $offer_type && ( 'free' === $offer_type || $offer_value ) ) {
				$regular_price = $cart_item['data']->get_regular_price( 'edit' );

				if ( 'fixed_total_price' === $offer_type ) {
					if ( $offer_qty == $cart_quantity ) {
						$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, false, 1, 'volume_discount' );
						$offered_price = $offered_price / $offer_qty;
					}
				} else {
					$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
				}
			}
		}

		$offered_price = apply_filters( 'revenue_campaign_volume_discount_price', $offered_price, $product_id );
		$cart_item['data']->set_price( $offered_price );
	}

	/**
	 * Get Discounted Price
	 *
	 * @param array $cart_item Cart Item.
	 *
	 * @return float
	 */
	public function get_discounted_price( $cart_item ) {
		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];
		$offer_qty     = '';

		$offered_price = $cart_item['data']->get_regular_price();

		if ( is_array( $offers ) ) {
			$offer_type         = '';
			$offer_value        = '';
			$offered_products[] = $product_id;

			foreach ( $offers as $offer ) {

				if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
					$offer_type  = $offer['type'];
					$offer_value = $offer['value'];
					$offer_qty   = intval( $offer['quantity'] );

				}
			}

			if ( $offer_type && ( 'free' === $offer_type || $offer_value ) ) {
				$regular_price = $cart_item['data']->get_regular_price();
				// $offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );

				if ( 'fixed_total_price' === $offer_type ) {
					if ( $offer_qty === $cart_quantity ) {
						$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, false, 1, 'volume_discount' );

						$offered_price = $offered_price / $offer_qty;
					}
				} else {
					$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
				}
			}
		}

		$offered_price = apply_filters( 'revenue_campaign_volume_discount_price', $offered_price, $product_id );
		return $offered_price;
	}


	/**
	 * Filters the cart item price to display the discounted price.
	 *
	 * This method filters the cart item price to display the discounted price if the cart item has a campaign ID.
	 *
	 * @param string $subtotal   The cart item price.
	 * @param array  $cart_item  The cart item data.
	 *
	 * @return string
	 */
	public function cart_item_price( $subtotal, $cart_item ) {
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$subtotal = $this->get_discounted_price( $cart_item );

			if ( $cart_item['data']->get_regular_price() != $subtotal ) {
				return '<del>' . wc_price( $cart_item['data']->get_regular_price() ) . '</del> ' . wc_price( $subtotal );
			}
			$subtotal = wc_price( $subtotal );
		}

		return $subtotal;
	}


	/**
	 * Outputs in-page views for the provided campaigns.
	 *
	 * This method processes and renders in-page views based on the provided campaigns.
	 * It adds each campaign to the `inpage` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data      An array of data to be passed to the view.
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
	 * Outputs popup views for the provided campaigns.
	 *
	 * This method processes and renders popup views based on the provided campaigns.
	 * It adds each campaign to the `popup` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data      An array of data to be passed to the view.
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
	 * Outputs floating views for the provided campaigns.
	 *
	 * This method processes and renders floating views based on the provided campaigns.
	 * It adds each campaign to the `floating` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data      An array of data to be passed to the view.
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
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function render_views( $data = array() ) {
		global $post;

		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			foreach ( $campaigns as $campaign ) {

				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = REVENUE_PATH . 'includes/campaigns/views/volume-discount/inpage.php';

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'volume_discount', 'inpage', $campaign );

				ob_start();
				if ( file_exists( $file_path ) ) {
					extract($data); //phpcs:ignore
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

				$file_path = REVENUE_PATH . 'includes/campaigns/views/volume-discount/popup.php';

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'volume_discount', 'popup', $campaign );

				ob_start();
				if ( file_exists( $file_path ) ) {
					extract($data); //phpcs:ignore
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

				$file_path = REVENUE_PATH . 'includes/campaigns/views/volume-discount/floating.php';

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'volume_discount', 'floating', $campaign );

				ob_start();
				if ( file_exists( $file_path ) ) {
					extract($data); //phpcs:ignore
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
