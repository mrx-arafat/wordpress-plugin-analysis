<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Ajax
 *
 * @package Revenue
 */

namespace Revenue;

use WC_AJAX;
use WC_Data_Store;
use WP_Query;

/**
 * Revenue Campaign
 *
 * @hooked on init
 */
class Revenue_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wc_ajax_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_revenue_add_bundle_to_cart', array( $this, 'add_bundle_to_cart' ) );
		add_action( 'wp_ajax_nopriv_revenue_add_bundle_to_cart', array( $this, 'add_bundle_to_cart' ) );
		add_action( 'wp_ajax_revenue_close_popup', array( $this, 'close_popup' ) );
		add_action( 'wp_ajax_nopriv_revenue_close_popup', array( $this, 'close_popup' ) );
		add_action( 'wp_ajax_revenue_count_impression', array( $this, 'count_impression' ) );
		add_action( 'wp_ajax_nopriv_revenue_count_impression', array( $this, 'count_impression' ) );

		add_filter( 'revenue_rest_before_prepare_campaign', array( $this, 'modify_campaign_rest_response' ) );

		add_action( 'wp_ajax_revenue_get_product_price', array( $this, 'get_product_price' ) );

		add_action( 'wp_ajax_revx_get_next_campaign_id', array( $this, 'get_next_campaign_id' ) );

		add_action( 'wp_ajax_revx_get_campaign_limits', array( $this, 'get_campaign_limits' ) );

		add_action( 'wp_ajax_revx_activate_woocommerce', array( $this, 'activate_woocommerce' ) );

		add_action( 'wp_ajax_revx_install_woocommerce', array( $this, 'install_woocommerce' ) );

		add_action( 'wp_ajax_revenue_get_search_suggestion', array( $this, 'get_search_suggestion' ) );
		add_action( 'wp_ajax_revenue_get_cart_total', array( $this, 'get_cart_total' ) );
		add_action( 'wp_ajax_no_prev_revenue_get_cart_total', array( $this, 'get_cart_total' ) );

		add_action( 'wp_ajax_revenue_get_campaign_offer_items', array( $this, 'get_offer_items' ) );

		add_action( 'wp_ajax_revenue_get_trigger_items', array( $this, 'get_trigger_items' ) );

		add_action( 'wp_ajax_nopriv_revenue_get_trigger_items', array( $this, 'get_trigger_items' ) );
	}

	public function get_cart_total() {
		if ( WC()->cart ) {
			// Recalculate totals before getting the cart total
			WC()->cart->calculate_totals();

			do_action( 'woocommerce_before_calculate_totals', WC()->cart );
		}

		$cart_total    = 0;
		$cart_subtotal = 0;

		if ( wc_prices_include_tax() ) {
			$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		} else {
			$cart_total = WC()->cart->get_cart_contents_total();
		}

		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		} else {
			$cart_subtotal = WC()->cart->get_subtotal();
		}

		wp_send_json_success(
			array(
				'cart_total'  => $cart_total,
				'subtotal'    => $cart_subtotal,
				'items_count' => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
			)
		);
	}


	public function get_trigger_items() {

		$nonce = '';
		if ( isset( $_GET['security'] ) ) {
			$nonce = sanitize_key( $_GET['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

		$trigger_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

		$search_keyword = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		$data = array();

		$source = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';

		$campaign_type = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : '';

		$response_data = array();

		switch ( $trigger_type ) {
			case 'products':
				$response_data = $this->search_products( $search_keyword );
				break;
			case 'category':
				$response_data = $this->search_categories( $search_keyword );
				break;
			default:
				// code...
				break;
		}

		$response_data = apply_filters( 'revenue_campaign_trigger_items', $response_data, $search_keyword, $trigger_type, $campaign_type );

		wp_send_json( $response_data );
	}

	public function search_products( $term, $include_variations = false ) {

		if ( ! empty( wp_unslash( $_GET['limit'] ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$limit = absint( wp_unslash( $_GET['limit'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
		}
		$source         = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$trigger_action = isset( $_GET['trigger_action'] ) ? sanitize_text_field( wp_unslash( $_GET['trigger_action'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$include_cats   = isset( $_GET['include_cats'] ) ? array_map( 'absint', wp_unslash( $_GET['include_cats'] ) ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$data_store = WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit * 2 ); // Fetch more than the limit to account for exclusions.

		$products      = array();
		$campaign_type = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product && $product->is_in_stock() ) {

				// Check if trigger_action is "exclude" and validate include_cats.
				if ( $trigger_action === 'exclude' && ! empty( $include_cats ) ) {
					$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
					if ( empty( array_intersect( $product_categories, $include_cats ) ) ) {
						continue; // Skip products not in the included categories.
					}
				}

				$chilren    = $product->get_children();
				$child_data = array();

				if ( is_array( $chilren ) ) {
					foreach ( $chilren as $child_id ) {
						$child = wc_get_product( $child_id );
						if ( $child && $child->is_in_stock() ) {
							$child_data[] = array(
								'item_id'       => $child_id,
								'item_name'     => rawurldecode( wp_strip_all_tags( $child->get_name() ) ),
								'thumbnail'     => wp_get_attachment_url( $child->get_image_id() ),
								'regular_price' => $child->get_regular_price(),
								'sale_price'    => $child->get_sale_price(),
								'parent'        => $product_id,
								'url'           => $child->get_permalink(),
							);
						}
					}
				}

				$product_data = array(
					'item_id'       => $product_id,
					'url'           => get_permalink( $product_id ),
					'item_name'     => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
					'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ),
					'regular_price' => $product->get_regular_price(),
					'sale_price'    => $product->get_sale_price(),
					'children'      => $child_data,
				);

				if ( $source === 'trigger' && $campaign_type !== 'mix_match' && 'buy_x_get_y' !== $campaign_type && 'frequently_bought_together' !== $campaign_type ) {
					if ( 'double_order' === $campaign_type ) {
						if ( 'variable' === $product->get_type() ) {
							$products = array_merge( $products, $child_data );
						} else {
							$products[] = $product_data;
						}
					} else {
						$products[] = $product_data;
					}
				} elseif ( ! empty( $child_data ) ) {
						$products = array_merge( $products, $child_data );
				} else {
					$products[] = $product_data;
				}

				// Break if we reach the limit.
				if ( count( $products ) >= $limit ) {
					break;
				}
			}
		}

		return array_slice( $products, 0, $limit ); // Ensure the final result respects the limit.
	}

	public function search_categories( $term ) {

		$found_categories = array();
		$args             = array(
			'taxonomy'   => array( 'product_cat' ),
			'orderby'    => 'id',
			'order'      => 'ASC',
			'hide_empty' => false,
			'fields'     => 'all',
			'name__like' => $term,
		);

		$terms = get_terms( $args );

		$data = array();

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$term->formatted_name = '';

				$ancestors = array();
				if ( $term->parent ) {
					$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
					foreach ( $ancestors as $ancestor ) {
						$ancestor_term = get_term( $ancestor, 'product_cat' );
						if ( $ancestor_term ) {
							$term->formatted_name .= $ancestor_term->name . ' > ';
						}
					}
				}

				$term->parents                      = $ancestors;
				$term->formatted_name              .= $term->name . ' (' . $term->count . ')';
				$found_categories[ $term->term_id ] = $term;

				$data[] = array(
					'item_id'   => $term->term_id,
					'item_name' => $term->name,
					'url'       => get_term_link( $term ),
					'thumbnail' => get_term_meta( $term->term_id, 'thumbnail_id', true )
						? wp_get_attachment_url( get_term_meta( $term->term_id, 'thumbnail_id', true ) )
						: wc_placeholder_img_src(),
				);
			}
		}

		return $data;
	}


	/**
	 * Get next campaign id.
	 *
	 * @return mixed
	 */
	public function get_next_campaign_id() {
		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		global $wpdb;
		$res = $wpdb->get_row( "SELECT COALESCE(MAX(id), 0) + 1 AS next_campaign_id FROM {$wpdb->prefix}revenue_campaigns;" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return wp_send_json_success( array( 'next_campaign_id' => $res->next_campaign_id ) );
	}

	/**
	 * Get Product price
	 *
	 * @return mixed
	 */
	public function get_product_price() {
		check_ajax_referer( 'revenue-get-product-price', false );

		$product_id = isset( $_GET['product_id'] ) ? sanitize_text_field( wp_unslash( $_GET['product_id'] ) ) : '';

		$product = wc_get_product( $product_id );
		$data    = array();
		if ( $product ) {
			$data['sale_price']    = $product->get_sale_price();
			$data['regular_price'] = $product->get_regular_price();
		}

		return wp_send_json_success( $data );
	}

	/**
	 * Modify campaign rest response
	 *
	 * @param array $data Data.
	 * @return mixed
	 */
	public function modify_campaign_rest_response( $data ) {

		if ( empty( $data['is_show_free_shipping_bar'] ) ) {
			$data['is_show_free_shipping_bar'] = 'yes';
		}

		if ( empty( $data['all_goals_complete_message'] ) ) {
			$data['all_goals_complete_message'] = __( 'Awesome! 😊 You’ve unlocked the ultimate reward! 🏆', 'revenue' );
		}

		if ( empty( $data['campaign_display_style'] ) ) {
			$data['campaign_display_style'] = 'inpage';
		}
		if ( empty( $data['campaign_builder_view'] ) ) {
			$data['campaign_builder_view'] = 'list';
		}
		if ( is_null( $data['offers'] ) ) {
			$data['offers'] = array();
		}

		if ( ! isset( $data['add_to_cart_animation_type'] ) ) {
			$data['add_to_cart_animation_type'] = 'shake';
		}
		if ( isset( $data['offers'] ) ) {

			foreach ( $data['offers'] as $idx => $offer ) {

				$products_data = array();
				foreach ( $offer['products'] as $product_id ) {
					if ( ! $product_id ) {
						continue;
					}

					$product = wc_get_product( $product_id );

					if ( $product ) {
						$products_data[] = array(
							'item_id'       => $product_id,
							'item_name'     => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
							'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ),
							'regular_price' => $product->get_regular_price(),
							'url'           => get_permalink( $product_id ),
						);
					} else {
						// Eventin Ticket
						$products_data[] = $this->get_eventin_ticket_data_by_id( $product_id );
					}
				}
				$data['offers'][ $idx ]['products'] = $products_data;
			}
		}

		if ( is_null( $data['campaign_start_date_time'] ) ) {
			$data['campaign_start_date'] = gmdate( 'Y-m-d', time() );
			$data['campaign_start_time'] = gmdate( 'H:00', time() );
		} else {
			$timestamp                   = strtotime( $data['campaign_start_date_time'] );
			$data['campaign_start_date'] = gmdate( 'Y-m-d', $timestamp );
			$data['campaign_start_time'] = gmdate( 'H:i', $timestamp );
		}

		if ( isset( $data['schedule_end_time_enabled'] ) && 'yes' === $data['schedule_end_time_enabled'] ) {
			if ( is_null( $data['campaign_end_date_time'] ) ) {
				$data['campaign_end_date'] = gmdate( 'Y-m-d', time() );
				$data['campaign_end_time'] = gmdate( 'H:00', time() );
			} else {
				$timestamp                 = strtotime( $data['campaign_end_date_time'] );
				$data['campaign_end_date'] = gmdate( 'Y-m-d', $timestamp );
				$data['campaign_end_time'] = gmdate( 'H:i', $timestamp );
			}
		}

		if ( is_null( $data['builder'] ) ) {
			unset( $data['builder'] );
		}

		if ( is_null( $data['builderdata'] ) ) {
			unset( $data['builderdata'] );
		}

		if ( isset( $data['campaign_type'] ) && 'mix_match' === $data['campaign_type'] ) {
			$data['campaign_trigger_relation'] = 'and';
		} elseif ( empty( $data['campaign_trigger_relation'] ) ) {
				$data['campaign_trigger_relation'] = 'or';
		}

		if ( empty( $data['campaign_placement'] ) && 'next_order_coupon' == $data['campaign_type'] ) {
			$data['campaign_placement']       = 'thankyou_page';
			$data['campaign_inpage_position'] = 'before_thankyou';
		}

		if ( empty( $data['campaign_placement'] ) ) {
			$data['campaign_placement'] = 'multiple';
		}

		if ( empty( $data['campaign_trigger_type'] ) ) {
			$data['campaign_trigger_type'] = 'products';
		}
		if ( empty( $data['offered_product_click_action'] ) ) {
			$data['offered_product_click_action'] = 'go_to_product';
		}

		if ( empty( $data['add_to_cart_animation_trigger_type'] ) ) {
			$data['add_to_cart_animation_trigger_type'] = 'on_hover';
		}
		if ( empty( $data['countdown_start_time_status'] ) ) {
			$data['countdown_start_time_status'] = 'right_now';
		}

		if ( isset( $data['campaign_placement'] ) && 'multiple' != $data['campaign_placement'] ) {
			if ( 'double_order' == $data['campaign_type'] ) {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => $data['campaign_inpage_position'] ? $data['campaign_inpage_position'] : 'review_order_before_payment',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			} elseif ( 'stock_scarcity' == $data['campaign_type'] ) {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => 'rvex_below_the_product_title',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			} elseif ( 'next_order_coupon' == $data['campaign_type'] ) {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => 'before_thankyou',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			} else {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => $data['campaign_inpage_position'] ? $data['campaign_inpage_position'] : 'before_add_to_cart_form',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			}

			$data['placement_settings']       = $data['placement_settings'];
			$data['campaign_placement']       = 'multiple';
			$data['campaign_display_style']   = 'multiple';
			$data['campaign_inpage_position'] = 'multiple';
		}

		if ( empty( $data['offered_product_on_cart_action'] ) ) {
			$data['offered_product_on_cart_action'] = 'do_nothing';
		}
		if ( empty( $data['active_page'] ) && ! empty( $data['placement_settings'] ) ) {
			$placement_setting   = (array) $data['placement_settings'];
			$data['active_page'] = ! empty( $placement_setting ) ? array_keys( $placement_setting )[0] : 'product_page';
		}

		if ( ! isset( $data['double_order_animation_type'] ) ) {
			$data['double_order_animation_type'] = 'shake';
		}

		if ( ! isset( $data['double_order_animation_type'] ) ) {
			$data['double_order_animation_type'] = 'shake';
		}

		if ( ! isset( $data['double_order_animation_type'] ) ) {
			$data['double_order_animation_type'] = 'shake';
		}

		if ( ! isset( $data['double_order_animation_type'] ) ) {
			$data['double_order_animation_type'] = 'shake';
		}

		return $data;
	}


	/**
	 * Reveneux Add to cart
	 *
	 * @return mixed
	 */
	public function add_to_cart() {

		check_ajax_referer( 'revenue-add-to-cart', false );

		$product_id                = isset( $_POST['productId'] ) ? sanitize_text_field( wp_unslash( $_POST['productId'] ) ) : '';
		$campaign_id               = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';
		$quantity                  = isset( $_POST['quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) : '';
		$index                     = isset( $_POST['index'] ) ? sanitize_text_field( wp_unslash( $_POST['index'] ) ) : '';
		$has_free_shipping_enabled = revenue()->get_campaign_meta( $campaign_id, 'free_shipping_enabled', true ) ?? 'no';

		$campaign = (array) revenue()->get_campaign_data( $campaign_id );

		$offers = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );

		$status = false;

		$cart_item_data = array(
			'rev_is_free_shipping' => $has_free_shipping_enabled,
			'revx_campaign_id'     => $campaign_id,
			'revx_campaign_type'   => $campaign['campaign_type'],
		);

		$product_index = 0;
		if ( 'buy_x_get_y' === $campaign['campaign_type'] ) {

			$bxgy_data         = isset( $_POST['bxgy_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bxgy_data'] ) ) : array();
			$bxgy_trigger_data = isset( $_POST['bxgy_trigger_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bxgy_trigger_data'] ) ) : array();
			$bxgy_offer_data   = isset( $_POST['bxgy_offer_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bxgy_offer_data'] ) ) : array();

			$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';

			if ( empty( $trigger_product_relation ) ) {
				$trigger_product_relation = 'or';
			}

			$is_category = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );

			$trigger_items       = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product_id, $is_category );
			$trigger_product_ids = array();
			$trigger_product_qty = array();
			foreach ( $trigger_items as $titem ) {
				$trigger_product_ids[ $titem['item_id'] ] = $bxgy_trigger_data[ $titem['item_id'] ] ?? 1;
				// unset( $bxgy_data[ $titem['item_id'] ] );

				$trigger_product_qty[ $titem['item_id'] ] = $titem['quantity'];
			}

			$parent_keys    = array();
			$cart_item_data = array_merge(
				$cart_item_data,
				array(
					'revx_bxgy_trigger_products' => $bxgy_trigger_data,
					'revx_bxgy_items'            => array(),
					'revx_offer_data'            => $offers,
					'revx_offer_products'        => $bxgy_offer_data,
					'revx_bxgy_all_triggers_key' => array(),
					'revx_required_qty'          => 1,
				)
			);

			$all_passed = true;
			$i          = 0;
			foreach ( $trigger_product_ids as $id => $qty ) {
				++$i;
				$cart_item_data['revx_required_qty'] = $trigger_product_qty[ $id ];

				if ( count( $trigger_product_ids ) === $i ) {
					// Last Product.
					$cart_item_data['revx_bxgy_last_trigger']     = true;
					$cart_item_data['revx_bxgy_all_triggers_key'] = $parent_keys;
					$status                                       = WC()->cart->add_to_cart( $id, $qty, 0, array(), $cart_item_data );
				} else {
					$status = WC()->cart->add_to_cart( $id, $qty, 0, array(), $cart_item_data );
				}
				if ( $status ) {
					$parent_keys[] = $status;

					if ( $status ) {
						do_action( 'revenue_item_added_to_cart', $status, $id, $campaign_id );
					}
				} else {
					$all_passed = false;
				}
			}

			if ( $all_passed ) {
				do_action( 'revenue_campaign_buy_x_get_y_after_added_trigger_products', $parent_keys, $cart_item_data, $trigger_product_ids );
			} else {
				$status = false;
			}

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );
		} elseif ( 'mix_match' === $campaign['campaign_type'] ) {
			$required_products          = revenue()->get_campaign_meta( $campaign['id'], 'mix_match_required_products', true ) ?? array();
			$mix_match_trigger_products = revenue()->get_item_ids_from_triggers( $campaign );
			$mix_match_data             = isset( $_POST['mix_match_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mix_match_data'] ) ) : array();

			$cart_item_data = array_merge(
				$cart_item_data,
				array(
					'revx_campaign_id'        => $campaign_id,
					'revx_campaign_type'      => $campaign['campaign_type'],
					'revx_required_products'  => $required_products,
					'revx_mix_match_products' => array_keys( $mix_match_data ),
					'revx_offer_data'         => $offers,
					'rev_is_free_shipping'    => $has_free_shipping_enabled,
				)
			);

			foreach ( $mix_match_data as $pid => $qty ) {
				$status = WC()->cart->add_to_cart(
					$pid,
					$qty,
					0,
					array(),
					$cart_item_data
				);
				revenue()->increment_campaign_add_to_cart_count( $campaign_id, $pid );

				if ( $status ) {
					do_action( 'revenue_item_added_to_cart', $status, $pid, $campaign_id );
				}
			}
		} elseif ( 'frequently_bought_together' === $campaign['campaign_type'] ) {
			$required_product = isset( $_POST['requiredProduct'] ) ? sanitize_text_field( wp_unslash( $_POST['requiredProduct'] ) ) : '';
			$ftb_data         = isset( $_POST['fbt_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['fbt_data'] ) ) : array();

			$is_required_trigger_product = revenue()->get_campaign_meta( $campaign_id, 'fbt_is_trigger_product_required', true );

			if ( 'yes' === $is_required_trigger_product ) {
				if ( ! isset( $ftb_data[ $required_product ] ) ) {
					return wp_send_json_success();
				}
			}

			$cart_item_data = array_merge(
				$cart_item_data,
				array(
					'revx_campaign_id'          => $campaign_id,
					'revx_campaign_type'        => $campaign['campaign_type'],
					'revx_fbt_required_product' => $required_product,
					'revx_fbt_data'             => $ftb_data,
					'revx_offer_data'           => $offers,
					'rev_is_free_shipping'      => $has_free_shipping_enabled,
				)
			);

			foreach ( $ftb_data as $pid => $qty ) {

				$status = WC()->cart->add_to_cart(
					$pid,
					$qty,
					0,
					array(),
					$cart_item_data
				);
				if ( $status ) {
					do_action( 'revenue_item_added_to_cart', $status, $pid, $campaign_id );
				}
			}

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );
		} elseif ( 'spending_goal' === $campaign['campaign_type'] ) {
			$cart_item_data['revx_spending_goal_upsell'] = 'yes';
			$status                                      = WC()->cart->add_to_cart(
				$product_id,
				$quantity,
				0,
				array(),
				$cart_item_data
			);

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );

			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		} elseif ( 'free_shipping_bar' === $campaign['campaign_type'] ) {
			$cart_item_data['revx_free_shipping_bar_upsell'] = 'yes';
			$status = WC()->cart->add_to_cart(
				$product_id,
				$quantity,
				0,
				array(),
				$cart_item_data
			);

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );

			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		} else {

			$offer_qty  = '';
			$flag_check = true;
			if ( is_array( $offers ) ) {
				foreach ( $offers as $offer_idx => $offer ) {

					$offered_product_ids = $offer['products'];
					$offer_qty           = $offer['quantity'];

					if ( 'volume_discount' === $campaign['campaign_type'] ) {
						$offered_product_ids   = array();
						$offered_product_ids[] = $product_id;
					}

					foreach ( $offered_product_ids as $offer_product_id ) {
						$offered_product = wc_get_product( $offer_product_id );
						if ( ! $offered_product ) {
							continue;
						}
						if ( 'volume_discount' === $campaign['campaign_type'] ) {
							$flag_check = (string) $offer_idx === (string) $index;
						}
						if ( $offer_product_id === $product_id && $flag_check ) {
							if ( 'yes' === revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true ) ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

							if ( 'volume_discount' === $campaign['campaign_type'] ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

							if ( ! ( 'volume_discount' === $campaign['campaign_type'] ) && $product_index == $index ) {
								$status = WC()->cart->add_to_cart(
									$product_id,
									$offer_qty,
									0,
									array(),
									$cart_item_data
								);
								revenue()->increment_campaign_add_to_cart_count( $campaign_id );
							}
						}
						$product_index++;
					}
				}
			}

			if ( ( 'volume_discount' === $campaign['campaign_type'] ) ) {
				$status = WC()->cart->add_to_cart(
					$product_id,
					$quantity,
					0,
					array(),
					$cart_item_data
				);
				revenue()->increment_campaign_add_to_cart_count( $campaign_id );
			}
			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		}

		$on_cart_action = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );

		$campaign_source_page = isset( $_POST['campaignSourcePage'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignSourcePage'] ) ) : '';

		$response_data = array(
			'add_to_cart'    => $status,
			'on_cart_action' => $on_cart_action,
		);
		switch ( $campaign_source_page ) {
			case 'cart_page':
				$response_data['is_reload'] = true;
				break;
			case 'checkout_page':
				$response_data['is_reload'] = true;
				break;

			default:
				// code...
				break;
		}

		WC()->cart->calculate_totals();

		$cart_total    = 0;
		$cart_subtotal = 0;

		if ( wc_prices_include_tax() ) {
			$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		} else {
			$cart_total = WC()->cart->get_cart_contents_total();
		}

		$shipping_total = 0;

		if ( WC()->cart->display_prices_including_tax() ) {
			$shipping_total = WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
		} else {
			$shipping_total = WC()->cart->shipping_total;
		}

		$cart_total += $shipping_total;

		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		} else {
			$cart_subtotal = WC()->cart->get_subtotal();
		}

		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		$data = array(
			'fragments' => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				)
			),
			'cart_hash' => WC()->cart->get_cart_hash(),
		);
		wp_send_json_success( array_merge( $response_data, $data ) );
	}

	/**
	 * Reveneux Add Bundle to cart
	 *
	 * @return mixed
	 */
	public function add_bundle_to_cart() {

		check_ajax_referer( 'revenue-add-to-cart', false );

		$campaign_id = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';
		$quantity    = isset( $_POST['quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) : '';

		$bundle_product_id = get_option( 'revenue_bundle_parent_product_id', false );

		if ( ! $bundle_product_id ) {
			wp_send_json_error();
		}

		$campaign                  = (array) revenue()->get_campaign_data( $campaign_id );
		$has_free_shipping_enabled = revenue()->get_campaign_meta( $campaign_id, 'free_shipping_enabled', true ) ?? 'no';

		$offers         = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		$is_qty_enabled = revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true );

		if ( 'yes' !== $is_qty_enabled ) {
			$quantity = 1;
		}

		$bundle_id = $campaign['id'] . '_' . wp_rand( 1, 9999999 );

		$bundle_data = array(
			'revx_campaign_id'     => $campaign_id,
			'revx_bundle_id'       => $bundle_id,
			'revx_bundle_data'     => $offers,
			'revx_bundle_type'     => 'trigger',
			'revx_bundled_items'   => array(),
			'revx_campaign_type'   => $campaign['campaign_type'],
			'rev_is_free_shipping' => $has_free_shipping_enabled,
		);

		if ( 'yes' === $campaign['bundle_with_trigger_products_enabled'] ) {
			$trigger_product_id = isset( $_POST['trigger_product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['trigger_product_id'] ) ) : '';
			$trigger_product    = wc_get_product( $trigger_product_id );
			if ( $trigger_product && $trigger_product->is_type( 'simple' ) ) {
				$bundle_data['revx_bundle_with_trigger'] = 'yes';
				$bundle_data['revx_trigger_product_id']  = $trigger_product_id;
				$bundle_data['revx_min_qty']             = 1;
			}
		}

		$status = WC()->cart->add_to_cart( $bundle_product_id, $quantity, 0, array(), $bundle_data );

		if ( $status ) {
			revenue()->increment_campaign_add_to_cart_count( $campaign_id );
		}

		$on_cart_action = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );

		$campaign_source_page = isset( $_POST['campaignSrcPage'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignSrcPage'] ) ) : '';

		$response_data = array(
			'add_to_cart'    => $status,
			'on_cart_action' => $on_cart_action,
		);
		switch ( $campaign_source_page ) {
			case 'cart_page':
				$response_data['is_reload'] = true;
				break;
			case 'checkout_page':
				$response_data['is_reload'] = true;
				break;

			default:
				// code...
				break;
		}
		WC()->cart->calculate_totals();

		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		$data = array(
			'fragments' => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				)
			),
			'cart_hash' => WC()->cart->get_cart_hash(),
		);
		wp_send_json_success( array_merge( $response_data, $data ) );
	}


	/**
	 * Close popup
	 *
	 * @return mixed
	 */
	public function close_popup() {
		check_ajax_referer( 'revenue-add-to-cart', false ); // Add this nonce on js and also localize this.

		$campaign_id = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';

		$cart_data = WC()->session->get( 'revenue_cart_data' );

		if ( ! ( is_array( $cart_data ) && isset( $cart_data[ $campaign_id ] ) ) ) {
			revenue()->increment_campaign_rejection_count( $campaign_id );
		}

		wp_send_json_success( array( 'rejection_updated' => true ) );
	}

	/**
	 * Count impression.
	 *
	 * @return mixed
	 */
	public function count_impression() {
		check_ajax_referer( 'revenue-add-to-cart', false ); // Add this nonce on js and also localize this.

		$campaign_id = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';

		revenue()->update_campaign_impression( $campaign_id );

		wp_send_json_success( array( 'impression_count_updated' => true ) );
	}


	/**
	 * Get campaign limits.
	 *
	 * @return array.
	 */
	public function get_campaign_limits() {
		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$res = $wpdb->get_row(
			"SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN campaign_type = 'normal_discount' THEN 1 ELSE 0 END) AS normal_discount,
                SUM(CASE WHEN campaign_type = 'volume_discount' THEN 1 ELSE 0 END) AS volume_discount,
                SUM(CASE WHEN campaign_type = 'bundle_discount' THEN 1 ELSE 0 END) AS bundle_discount,
                SUM(CASE WHEN campaign_type = 'buy_x_get_y' THEN 1 ELSE 0 END) AS buy_x_get_y
            FROM {$wpdb->prefix}revenue_campaigns;"
		); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return wp_send_json_success( $res );
	}



	/**
	 * Activate WC
	 *
	 * @return mixed
	 */
	public function activate_woocommerce() {

		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to activate plugins.', 'revenue' ) );
		}
		$result = activate_plugin( 'woocommerce/woocommerce.php' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success();
	}


	/**
	 * Install WC
	 *
	 * @return mixed
	 */
	public function install_woocommerce() {

		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to install plugins.', 'revenue' ) );
		}

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'plugins_api' ) ) {
			include ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$plugin_slug = 'woocommerce';
		$api         = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			wp_send_json_error( $api->get_error_message() );
		}
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		if ( ! $result ) {
			wp_send_json_error( __( 'Plugin installation failed.', 'revenue' ) );
		}
		wp_send_json_success();
	}


	/**
	 * Get trigger and offer search suggestion.
	 *
	 * @return mixed
	 */
	public function get_search_suggestion() {
		$nonce = isset( $_GET['security'] ) ? sanitize_key( $_GET['security'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		$type           = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$source         = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$campaign_type  = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$trigger_action = isset( $_GET['trigger_action'] ) ? sanitize_text_field( wp_unslash( $_GET['trigger_action'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$include_cats   = isset( $_GET['include_cats'] ) ? array_map( 'absint', wp_unslash( $_GET['include_cats'] ) ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$data = array();

		if ( 'products' === $type ) {
			$args = array(
				'limit'   => 10, // Fetch more than necessary to account for exclusions.
				'orderby' => 'date',
				'order'   => 'ASC',
			);

			$products = wc_get_products( $args );

			foreach ( $products as $product ) {
				if ( $product && $product->is_in_stock() ) {
					// Handle trigger_action and include_cats filtering.
					if ( 'exclude' === $trigger_action && ! empty( $include_cats ) ) {
						$product_categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
						if ( empty( array_intersect( $product_categories, $include_cats ) ) ) {
							continue; // Skip products not in the included categories.
						}
					}

					$children     = $product->get_children();
					$child_data   = array();
					$product_link = get_permalink( $product->get_id() );

					if ( is_array( $children ) ) {
						foreach ( $children as $child_id ) {
							$child = wc_get_product( $child_id );
							if ( $child && $child->is_in_stock() ) {
								$child_data[] = array(
									'item_id'       => $child_id,
									'item_name'     => rawurldecode( wp_strip_all_tags( $child->get_name() ) ),
									'thumbnail'     => wp_get_attachment_url( $child->get_image_id() ),
									'regular_price' => $child->get_regular_price(),
									'parent'        => $product->get_id(),
									'url'           => $child->get_permalink(),
								);
							}
						}
					}

					$product_data = array(
						'item_id'        => $product->get_id(),
						'url'            => $product_link,
						'item_name'      => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
						'thumbnail'      => wp_get_attachment_url( $product->get_image_id() ),
						'regular_price'  => $product->get_regular_price(),
						'children'       => $child_data,
						'show_attribute' => 'variable' === $product->get_type(),
					);

					if ( 'trigger' === $source && 'mix_match' !== $campaign_type && 'buy_x_get_y' !== $campaign_type && 'frequently_bought_together' !== $campaign_type ) {
						if ( 'double_order' === $campaign_type ) {
							if ( 'variable' === $product->get_type() ) {
								$data = array_merge( $data, $child_data );
							} else {
								$data[] = $product_data;
							}
						} else {
							$data[] = $product_data;
						}
					} elseif ( ! empty( $child_data ) ) {
							$data = array_merge( $data, $child_data );
					} else {
						$data[] = $product_data;
					}
				}
			}
		} elseif ( 'category' === $type ) {
			$category_args = array(
				'taxonomy' => 'product_cat',
				'number'   => 5,
				'orderby'  => 'name',
				'order'    => 'ASC',
			);

			$categories = get_terms( $category_args );

			foreach ( $categories as $category ) {
				if ( ! is_wp_error( $category ) ) {
					$data[] = array(
						'item_id'   => $category->term_id,
						'item_name' => $category->name,
						'url'       => get_term_link( $category ),
						'thumbnail' => get_term_meta( $category->term_id, 'thumbnail_id', true )
							? wp_get_attachment_url( get_term_meta( $category->term_id, 'thumbnail_id', true ) )
							: wc_placeholder_img_src(),
					);
				}
			}
		}

		// Limit the final output to ensure it respects the requested number.
		$data = array_slice( $data, 0, 5 ); // Adjust to your desired limit.

		$data = apply_filters( 'revenue_campaign_search_suggestion_data', $data, $type, $campaign_type, $source );

		wp_send_json_success( $data );
	}




	public function get_eventin_ticket_data_by_id( $variation_id ) {
		$id = explode( '_', $variation_id )[0];

		$data       = array();
		$event_logo = get_post_meta( $id, 'etn_event_logo', true );
		$variations = get_post_meta( $id, 'etn_ticket_variations', true );
		$child_data = array();

		if ( is_array( $variations ) && ! empty( $variations ) ) {

			foreach ( $variations as $variation ) {
				if ( $id . '_' . $variation['etn_ticket_slug'] == $variation_id ) {
					$data = array(
						'item_id'       => $id . '_' . $variation['etn_ticket_slug'],
						'item_name'     => $variation['etn_ticket_name'],
						'regular_price' => $variation['etn_ticket_price'],
						'thumbnail'     => wc_placeholder_img_src(),
						'_type'         => 'eventin_events',
					);
				}
			}
		}

		return $data;
	}


	public function get_offer_items() {
		$nonce = '';
		if ( isset( $_GET['security'] ) ) {
			$nonce = sanitize_key( $_GET['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

		$data = array();

		if ( 'products' == $type ) {

			$args = array(
				'limit'   => 5, // Limit to 5 products.
				'orderby' => 'date', // Order by date.
				'order'   => 'ASC', // Ascending order.
			);

			$products = wc_get_products( $args );

			$source = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';

			$campaign_type = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : '';
			foreach ( $products as $product ) {
				if ( $product ) {

					$chilren      = $product->get_children();
					$child_data   = array();
					$product_link = get_permalink( $product );
					if ( is_array( $chilren ) ) {
						foreach ( $chilren as $child_id ) {
							$child        = wc_get_product( $child_id );
							$child_data[] = array(
								'item_id'        => $child_id,
								'item_name'      => rawurldecode( wp_strip_all_tags( $child->get_name() ) ),
								'thumbnail'      => wp_get_attachment_url( $child->get_image_id() ),
								'regular_price'  => $child->get_regular_price(),
								'parent'         => $product->get_id(),
								'url'            => $product_link,
								'show_attribute' => 'variable' === $product->get_type(),
							);
						}
					}

					if ( 'trigger' === $source && 'mix_match' !== $campaign_type && 'buy_x_get_y' !== $campaign_type && 'frequently_bought_together' !== $campaign_type ) {
						$data[] = array(
							'item_id'        => $product->get_id(),
							'url'            => get_permalink( $product ),
							'item_name'      => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
							'thumbnail'      => wp_get_attachment_url( $product->get_image_id() ),
							'regular_price'  => $product->get_regular_price(),
							'children'       => array(),
							'show_attribute' => 'variable' === $product->get_type(),
						);
					} elseif ( ! empty( $child_data ) ) {
							$data = array_merge( $data, $child_data );
					} else {

						$data[] = array(
							'item_id'        => $product->get_id(),
							'url'            => get_permalink( $product ),
							'item_name'      => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
							'thumbnail'      => wp_get_attachment_url( $product->get_image_id() ),
							'regular_price'  => $product->get_regular_price(),
							'children'       => array(),
							'show_attribute' => 'variable' === $product->get_type(),
						);
					}
				}
			}
		} elseif ( 'category' === $type ) {
			$category_args = array(
				'taxonomy' => 'product_cat', // Taxonomy for WooCommerce product categories.
				'number'   => 5, // Limit to 5 categories.
				'orderby'  => 'name', // Order by name.
				'order'    => 'ASC', // Ascending order.
			);

			$categories = get_terms( $category_args );

			foreach ( $categories as $category ) {
				if ( ! is_wp_error( $category ) ) {
					$data[] = array(
						'item_id'   => $category->term_id,
						'item_name' => $category->name,
						'url'       => get_term_link( $category ), // Get the category link.
						'thumbnail' => get_term_meta( $category->term_id, 'thumbnail_id', true ) ? wp_get_attachment_url( get_term_meta( $category->term_id, 'thumbnail_id', true ) ) : wc_placeholder_img_src(), // Get category thumbnail.
					);
				}
			}
		}

		wp_send_json_success( $data );
	}
}
