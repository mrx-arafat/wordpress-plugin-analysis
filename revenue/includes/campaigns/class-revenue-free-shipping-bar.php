<?php

namespace Revenue;

use Revenue;
use WC_Shipping_Free_Shipping;

/**
 * RevenueX Campaign: Product Mix Match
 *
 * @hooked on init
 */
class Revenue_Free_Shipping_Bar {
	use Revenue\SingletonTrait;

	/**
	 * Store all campaigns
	 *
	 * @var array
	 */
	public $campaigns = array();

	/**
	 * Store current position
	 *
	 * @var string
	 */
	public $current_position = '';

	/**
	 * Store campaign type
	 *
	 * @var string
	 */
	public $campaign_type = 'free_shipping_bar';

	/**
	 * Track rendered campaigns to prevent rendering them multiple times
	 *
	 * @var array
	 */
	private static $rendered_campaigns = array();

	/**
	 * Contain All Campaigns
	 *
	 * @var array
	 */
	public $all_campaigns = array();

	/**
	 * Contains Current Campaing
	 *
	 * @var array
	 */
	public $cur_campaign = array();

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function init() {

		add_filter( 'woocommerce_package_rates', array( $this, 'modify_shipping_rates' ), 100, 2 );

		add_action( 'revenue_campaign_free_shipping_bar_before_calculate_cart_totals', array( $this, 'apply_upsell_product_discount' ), 10, 2 );
	}


	/**
	 * Set Current Campaign
	 *
	 * @param array $campaign Campaign.
	 * @return void
	 */
	public function set_cur_campaign( $campaign ) {
		$this->cur_campaign = $campaign;
	}

	/**
	 * Fetch Campaigns
	 *
	 * @return array
	 */
	public function fetch_campaigns() {
		global $post;
		$id            = $post ? $post->ID : 0;
		$all_campaigns = revenue()->get_available_campaigns( $id, '', '', '', false, true );

		$filted_data = array();

		foreach ( $all_campaigns as $cmp ) {
			if ( isset( $cmp['campaign_type'] ) && 'free_shipping_bar' === $cmp['campaign_type'] ) {
				$filted_data[] = $cmp;
			}
		}

		return $filted_data;
	}

	/**
	 * Apply Upsell Product Discounts
	 *
	 * @param array      $cart_item Cart Item.
	 * @param int|string $campaign_id Campaign ID.
	 * @return void
	 */
	public function apply_upsell_product_discount( $cart_item, $campaign_id ) {
		$data = revenue()->get_campaign_meta( $campaign_id, 'upsell_products', true );

		$offered_product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		foreach ( $data as $pd ) {
			// Ensure 'products' is an array of product IDs.
			if ( ! isset( $pd['products'] ) || ! is_array( $pd['products'] ) ) {
				continue;
			}

			// Process each product ID.
			foreach ( $pd['products'] as $item_data ) {

				$regular_price    = (float) $item_data['regular_price'];
				$quantity         = isset( $pd['quantity'] ) ? (int) $pd['quantity'] : 0;
				$discounted_price = $regular_price;
				$discount_amount  = isset( $pd['value'] ) ? (float) $pd['value'] : 0;

				// Calculate discounted price based on pd type.
				if ( isset( $pd['type'] ) ) {
					switch ( $pd['type'] ) {
						case 'percentage':
							$discount_value   = $discount_amount;
							$discount_amount  = number_format( ( $regular_price * $discount_value ) / 100, 2 );
							$discounted_price = number_format( $regular_price - $discount_amount, 2 );
							break;

						case 'fixed_discount':
							$discount_amount  = number_format( $discount_amount, 2 );
							$discounted_price = number_format( $regular_price - $discount_amount, 2 );
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

				if ( intval( $offered_product_id ) === intval( $item_data['item_id'] ) ) {
					$cart_item['data']->set_price( max( 0, $discounted_price * $quantity ) );
				}
			}
		}

	}

	/**
	 * Modify Shipping Rates
	 *
	 * @param mixed $rates Shipping Rates.
	 * @param mixed $package Shipping Package.
	 * @return mixed
	 */
	public function modify_shipping_rates( $rates, $package ) {

		if ( empty( $this->all_campaigns ) ) {
			$this->all_campaigns = $this->fetch_campaigns();
		}

		$is_free_shipping = false;

		foreach ( $this->all_campaigns as $campaign ) {
			if ( $is_free_shipping ) {
				continue;
			}
			$offers = $campaign['offers'];

			$required_goal          = isset( $offers[0]['required_goal'] ) ? $offers[0]['required_goal'] : 0;
			$free_shipping_based_on = isset( $offers[0]['free_shipping_based_on'] ) ? $offers[0]['free_shipping_based_on'] : '';
			$cart_total             = $this->get_eligible_cart_total( $free_shipping_based_on );

			if ( $cart_total >= $required_goal ) {
				$is_free_shipping = true;
			}
		}

		if ( $is_free_shipping ) {
			$free_shipping        = new WC_Shipping_Free_Shipping( 'revenue_free_shipping' );
			$free_shipping->title = apply_filters( 'revenue_free_shipping_title', __( 'Free Shipping (Reward)', 'revenue' ) ); // Add Global Settings.
			$free_shipping->calculate_shipping( $package );
			return $free_shipping->rates;
		}

		return $rates;
	}

	/**
	 * Get Eligible Cart Total
	 *
	 * @param string  $type Type.
	 * @param boolean $rc Is force recalculate.
	 */
	private function get_eligible_cart_total( $type = 'subtotal', $rc = false ) {

		static $recalculating = false;

		if ( ! $recalculating && $rc ) {
			$recalculating = true;
			WC()->cart->calculate_totals();
		}

		$value = 0;

		$cart_total    = 0;
		$cart_subtotal = 0;

		// if ( wc_prices_include_tax() ) {
		// 	$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		// } else {
		// 	$cart_total = WC()->cart->get_cart_contents_total();
		// }

		$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();


		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		} else {
			$cart_subtotal = WC()->cart->get_subtotal();
		}

		if ( WC()->cart ) {
			if ( 'cart_total' === $type ) {
				$value = $cart_total;

			} else {
				$value = $cart_subtotal;
			}
		}

		return $value;
	}



	/**
	 * Output Inpage View
	 *
	 * @param array $campaigns Campaigns.
	 * @param array $data Data.
	 * @return void
	 */
	public function output_inpage_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;

			$this->all_campaigns[ $campaign['id'] ] = $campaign;

			$this->current_position = $data['position'];
			$this->render_views( $data,'inpage' );
		}
	}

	/**
	 * Output Drawer View
	 *
	 * @param array $campaigns Campaigns.
	 * @param array $data Data.
	 * @return void
	 */
	public function output_drawer_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {

			$placement_settings_all = $campaign['placement_settings'];
			$is_all_page_enabled = isset($placement_settings_all['all_page']['status'])? 'yes' ==$placement_settings_all['all_page']['status'] : false;
			$is_all_page_drawer = isset($placement_settings_all['all_page']['display_style'])? 'drawer' ==$placement_settings_all['all_page']['display_style'] : false;

		

			$current_page = revenue()->get_current_page();
			$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

			if('drawer' === $placement_settings['display_style'] || ($is_all_page_drawer && $is_all_page_enabled)) {
				$this->campaigns['drawer'][ $data['position'] ][] = $campaign;
				$this->all_campaigns[ $campaign['id'] ]           = $campaign;

				$this->current_position = $data['position'];
				
				$this->render_views( $data , 'drawer');
			}

			
		}
	}

	/**
	 * Output Top View
	 *
	 * @param array $campaigns Campaigns.
	 * @param array $data Data.
	 * @return void
	 */
	public function output_top_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$this->campaigns['hellobar']['top'][]   = $campaign;
			$this->all_campaigns[ $campaign['id'] ] = $campaign;

			$data['position']       = 'top';
			$this->current_position = $data['position'];
			$this->render_views( $data , 'top');
		}
	}

	/**
	 * Output Bottom View
	 *
	 * @param array $campaigns Campaigns.
	 * @param array $data Data.
	 * @return void
	 */
	public function output_bottom_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$placement_settings_all = $campaign['placement_settings'];
			$is_all_page_enabled = isset($placement_settings_all['all_page']['status'])? 'yes' ==$placement_settings_all['all_page']['status'] : false;
			// $is_all_page_drawer = isset($placement_settings_all['all_page']['display_style'])? 'drawer' ==$placement_settings_all['all_page']['display_style'] : false;

			// print_r('<pre>'); print_r($placement_settings_all['all_page']); print_r('</pre>');

			if($placement_settings_all['all_page']['status'] == 'yes' && $placement_settings_all['all_page']['display_style'] == 'bottom') {
				$this->campaigns['hellobar']['bottom'][] = $campaign;
				$this->all_campaigns[ $campaign['id'] ]  = $campaign;

				$data['position']                        = 'bottom';
				$data['drawer_position'] = '';
				$this->current_position = $data['position'];

				
				$this->render_views( $data,'bottom' );
			}
			

		

			// $this->campaigns['hellobar']['bottom'][] = $campaign;
			// $data['position']                        = 'bottom';
			// $this->all_campaigns[ $campaign['id'] ]  = $campaign;

			// $this->current_position = $data['position'];
			// $this->render_views( $data );
		}
	}

	/**
	 * Render Views
	 *
	 * @param array $data Data.
	 * @return void
	 */
	public function render_views( $data = array(), $render_for='' ) {
		global $current_campaign;

		wp_enqueue_style( 'revenue-campaign-fsb' );

		$data = wp_parse_args(
			$data,
			array(
				'placement' => 'product_page',
			)
		);

		wp_enqueue_script( 'revenue-upsell-slider' );
		wp_enqueue_script( 'revenue-free-shipping-bar' );

		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) && 'inpage' === $render_for ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];

			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;

				revenue()->update_campaign_impression( $campaign['id'] );

				$file_path = REVENUE_PATH . 'includes/campaigns/views/free-shipping-bar/inpage.php';

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'free_shipping_bar', 'inpage', $campaign );

				$data['position'] = 'inpage';

				ob_start();
				if ( file_exists( $file_path ) ) {
					extract( $data );
					include $file_path;
				}

				$output .= ob_get_clean();
			}

			if ( $output ) {
				echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		if ( ! empty( $this->campaigns['drawer'] ) && 'drawer' === $render_for ) {
			

			$output    = '';
			$campaigns = $this->campaigns['drawer']['top_left'];

			
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;
				

				$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

				$current_page = revenue()->get_current_page();

				if ( $current_page && ! empty( $placement_settings ) ) {
					

					$data['position'] = $placement_settings['drawer_position'];

					$file_path = REVENUE_PATH . 'includes/campaigns/views/free-shipping-bar/drawer.php';

					$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'free_shipping_bar', 'drawer', $campaign );

					ob_start();
					if ( file_exists( $file_path ) ) {
						extract( $data );
						include $file_path;
					}

					$output .= ob_get_clean();
				} else {
					foreach ( $placement_settings as $page => $value ) {

						if ( 'yes' === $value['status'] && ( $page === $current_page || $page == 'all_page' ) ) {
							$data['position'] = $value['drawer_position'];
							$file_path        = REVENUE_PATH . 'includes/campaigns/views/free-shipping-bar/drawer.php';

							$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'free_shipping_bar', 'drawer', $campaign );

							ob_start();
							if ( file_exists( $file_path ) ) {
								extract( $data );
								include $file_path;
							}

							$output .= ob_get_clean();
						}
					}
				}

				revenue()->update_campaign_impression( $campaign['id'] );

			}

			if ( $output ) {
				echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		if ( ! empty( $this->campaigns['hellobar'] ) ) {

			if ( isset( $this->campaigns['hellobar']['top'] ) && ! empty( $this->campaigns['hellobar']['top'] ) && 'top' === $render_for ) {

				$output    = '';
				$campaigns = $this->campaigns['hellobar']['top'];

				foreach ( $campaigns as $campaign ) {
					$current_campaign = $campaign;

					$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

					$file_path = REVENUE_PATH . 'includes/campaigns/views/free-shipping-bar/hellobar.php';

					$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'free_shipping_bar', 'hellobar', $campaign );

					ob_start();
					if ( file_exists( $file_path ) ) {
						extract( $data );
						include $file_path;
					}

					$output .= ob_get_clean();

					revenue()->update_campaign_impression( $campaign['id'] );

					if ( $output ) {
						echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				}
			}

			if ( isset( $this->campaigns['hellobar']['bottom'] ) && ! empty( $this->campaigns['hellobar']['bottom'] ) && 'bottom' === $render_for ) {

				$output    = '';
				$campaigns = $this->campaigns['hellobar']['bottom'];

				foreach ( $campaigns as $campaign ) {
					$current_campaign = $campaign;

					$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

					$file_path = REVENUE_PATH . 'includes/campaigns/views/free-shipping-bar/hellobar.php';

					$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'spending_goal', 'hellobar', $campaign );

					ob_start();
					if ( file_exists( $file_path ) ) {
						extract( $data );
						include $file_path;
					}

					$output .= ob_get_clean();

					revenue()->update_campaign_impression( $campaign['id'] );

				}

				if ( $output ) {
					echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}
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
