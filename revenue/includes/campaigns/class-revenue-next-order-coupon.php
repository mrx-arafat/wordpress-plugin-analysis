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
class Revenue_Next_Order_Coupon {
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
	 * The current position for rendering campaigns.
	 *
	 * @var string
	 */
	public $current_position = '';

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'wp_ajax_custom_save_coupon_action', array( $this, 'custom_save_coupon_action' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'edit_form_after_editor', array( $this, 'render_checkbox_after_description' ) );
		add_action( 'save_post', array( $this, 'save_custom_coupon_checkbox' ), 10, 2 );
		// add_action( 'wp_ajax_sync_triggered_products_to_coupon', array( $this, 'sync_triggered_products_to_coupon' ) );
		// add_filter( 'woocommerce_get_item_data', array( $this, 'revx_display_custom_cart_item_meta' ), 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'revx_validate_coupon_eligibility' ), 10, 3 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'update_user_coupon_eligibility' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'update_user_coupon_eligibility' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'update_user_coupon_eligibility' ) );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'revx_store_campaign_data_for_cart_next_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_campaign_data_to_order_items' ), 10, 4 );
		add_action( 'template_redirect', array( $this, 'revx_auto_apply_coupon_and_show_notice' ) );
		add_action( 'woocommerce_before_cart_table', array( $this, 'revx_show_next_order_coupon_message' ) );
		$this->is_campaign_applicable();
	}

	/**
	 * Validate the coupon by its ID.
	 *
	 * @param int $coupon_id The coupon ID.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	public function revx_validate_coupon_by_id( $coupon_id ) {
		if ( ! $coupon_id ) {
			return false;
		}

		$coupon = new \WC_Coupon( $coupon_id );

		// Check if coupon exists and is valid.
		if ( ! $coupon->get_id() ) {
			return false;
		}

		// Check if coupon is expired.
		$expiry_date = $coupon->get_date_expires();
		if ( $expiry_date && $expiry_date->getTimestamp() < current_time( 'timestamp' ) ) {
			return false;
		}

		// Check if usage limit is reached.
		$usage_limit = $coupon->get_usage_limit();
		$usage_count = $coupon->get_usage_count();
		if ( $usage_limit && $usage_count >= $usage_limit ) {
			return false;
		}

		// Check if user usage limit is reached (for logged-in users).
		if ( is_user_logged_in() ) {
			$user_id          = get_current_user_id();
			$user_usage_limit = $coupon->get_usage_limit_per_user();
			$user_usage_count = $coupon->get_usage_count( $user_id );
			if ( $user_usage_limit && $user_usage_count >= $user_usage_limit ) {
				return false;
			}
		}

		// Add more checks here if needed (min/max spend, individual use, etc.).

		return true;
	}

	/**
	 * Render the coupon shortcode.
	 *
	 * @return void The rendered coupon HTML.
	 */
	public function is_campaign_applicable() {

		if ( is_user_logged_in() ) {
			$user_id         = get_current_user_id();
			$coupon_id       = get_user_meta( $user_id, '_revx_next_order_coupon_id', true );
			$campaign_id     = get_user_meta( $user_id, '_revx_next_order_campaign_id_' . $coupon_id, true );
			$campaign        = revenue()->get_campaign_data( $campaign_id, 'campaign_status', true );
			$campaign_status = isset( $campaign['campaign_status'] ) ? $campaign['campaign_status'] : '';
		} else {
			$coupon_id       = $this->get_guest_meta( '_revx_next_order_coupon_id' );
			$campaign_id     = $this->get_guest_meta( '_revx_next_order_campaign_id_' . $coupon_id );
			$campaign        = revenue()->get_campaign_data( $campaign_id, 'campaign_status', true );
			$campaign_status = isset( $campaign['campaign_status'] ) ? $campaign['campaign_status'] : '';
			// echo 'd<pre>'; print_r($campaign_status); echo '</pre>';
		}
		$is_revx_campaign = get_post_meta( $coupon_id, '_revx_next_order_coupon_enable', true );
		if ( 'publish' === $campaign_status && 'yes' === $is_revx_campaign ) {

			if ( isset( $campaign['placement_settings']['thankyou_page'] ) ) {
				$thankyou_page = $campaign['placement_settings']['thankyou_page'];
				if ( isset( $thankyou_page['status'] ) && 'yes' === $thankyou_page['status'] && $this->revx_validate_coupon_by_id( $coupon_id ) ) {
					add_action( 'woocommerce_thankyou', array( $this, 'display_next_order_coupon_message_below' ), 20 );
					add_action( 'woocommerce_before_thankyou', array( $this, 'display_next_order_coupon_message_top' ), 5 );
				}
			}
			if ( isset( $campaign['placement_settings']['my_account'] ) && $this->revx_validate_coupon_by_id( $coupon_id ) ) {
				$my_account = $campaign['placement_settings']['my_account'];
				if ( isset( $my_account['status'] ) && 'yes' === $my_account['status'] ) {
					add_action( 'woocommerce_account_content', array( $this, 'display_next_order_coupon_message_my_account_top' ), 5 );
					add_action( 'woocommerce_account_content', array( $this, 'display_next_order_coupon_message_my_account_bottom' ), 25 );
				}
			}
			if ( isset( $campaign['placement_settings']['to_email'] ) && $this->revx_validate_coupon_by_id( $coupon_id ) ) {
				$to_email = $campaign['placement_settings']['to_email'];
				if ( isset( $to_email['status'] ) && 'yes' === $to_email['status'] ) {
					add_shortcode( 'revenue_coupon', array( $this, 'render_coupon_shortcode' ) );

					$customer_email_types = array(
						'customer_processing_order',
						'customer_completed_order',
						'customer_on_hold_order',
						'customer_refunded_order',
						'customer_invoice',
						'customer_note',
					);

					foreach ( $customer_email_types as $email_type ) {
						add_filter( 'woocommerce_email_additional_content_' . $email_type, array( $this, 'revx_process_email_additional_content' ), 10, 2 );
					}
					// add_filter( 'woocommerce_email_additional_content_new_order', array( $this, 'revx_process_email_additional_content' ), 10, 2 );
				}
			}
		}
	}

	/**
	 * Update user coupon eligibility after order completion and load in Email.
	 *
	 * @param mixed  $content The content of the email.
	 * @param object $email The email object.
	 */
	public function revx_process_email_additional_content( $content, $email ) {
		return do_shortcode( $content );
	}

	/**
	 * Adds campaign data to order items.
	 *
	 * @param object $item The order item object.
	 * @param string $cart_item_key The cart item key.
	 * @param array  $values The cart item values.
	 * @param object $order The order object.
	 */
	public function add_campaign_data_to_order_items( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values['_revx_campaign_next_id'] ) ) {
			$item->add_meta_data( '_revx_campaign_next_id', $values['_revx_campaign_next_id'], true );
		}

		if ( isset( $values['_revx_campaign_next_type'] ) ) {
			$item->add_meta_data( '_revx_campaign_next_type', $values['_revx_campaign_next_type'], true );
		}
	}

	/**
	 * Store campaign data for cart items.
	 *
	 * @param array $cart_item_data The cart item data.
	 * @param int   $product_id     The product ID.
	 *
	 * @return array The modified cart item data.
	 */
	public function revx_store_campaign_data_for_cart_next_order( $cart_item_data, $product_id ) {

		$positions = array(
			'before_thankyou',
			'thankyou',
			'rvex_above_my_account',
			'rvex_below_my_account',
			'before_add_to_cart_form',
		);
		$pages     = array(
			'thankyou_page',
			'my_account',
		);

		// Loop through each position.
		foreach ( $positions as $position ) {
			foreach ( $pages as $page ) {
				$campaigns = revenue()->get_available_campaigns( $product_id, $page, 'inpage', $position, false, false, 'next_order_coupon' );
				if ( ! empty( $campaigns ) ) {
					foreach ( $campaigns as $campaign ) {
						$cart_item_data['_revx_campaign_next_id']   = $campaign['id'];
						$cart_item_data['_revx_campaign_next_type'] = $campaign['campaign_type'];
						revenue()->increment_campaign_add_to_cart_count( $campaign['id'] );
						break 3; // Break out of all loops once a campaign is found
					}
				}
			}
		}
		return $cart_item_data;
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
			// wp_enqueue_script( 'revenue-campaign-stock-scarcity' );
			// wp_enqueue_style( 'revenue-campaign-stock-scarcity' );
			foreach ( $campaigns as $campaign ) {
				revenue()->update_campaign_impression( $campaign['id'], $product->get_id() );
				$output    = '';
				$file_path = apply_filters( 'revenue_campaign_view_path', REVENUE_PATH . 'includes/campaigns/views/next-order-coupon/inpage.php', 'next_order_coupon', 'inpage', $campaign );
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
	 * Retrieves triggered items for a specific campaign.
	 *
	 * @param int $campaign_id The ID of the campaign.
	 *
	 * @return array An associative array containing triggered items categorized by type.
	 */
	public function get_triggered_items_by_campaign( $campaign_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'revenue_campaign_triggers';

		$query = $wpdb->prepare(
			"
			SELECT trigger_action, trigger_type, item_id
			FROM {$table_name}
			WHERE campaign_id = %d
		",
			$campaign_id
		);

		$results = $wpdb->get_results( $query );

		// Initialize return structure.
		$triggered_items = array(
			'include_products'   => array(),
			'exclude_products'   => array(),
			'include_categories' => array(),
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				$action  = $row->trigger_action;
				$type    = $row->trigger_type;
				$item_id = intval( $row->item_id );

				if ( $type === 'products' ) {
					if ( $action === 'include' ) {
						$triggered_items['include_products'][] = $item_id;
					} elseif ( $action === 'exclude' ) {
						$triggered_items['exclude_products'][] = $item_id;
					}
				} elseif ( $type === 'category' && $action === 'include' ) {
					$triggered_items['include_categories'][] = $item_id;
				}
			}

			// Optional: Remove duplicates.
			$triggered_items['include_products']   = array_unique( $triggered_items['include_products'] );
			$triggered_items['exclude_products']   = array_unique( $triggered_items['exclude_products'] );
			$triggered_items['include_categories'] = array_unique( $triggered_items['include_categories'] );
		}

		return $triggered_items;
	}

	/**
	 * Adds a custom checkbox to the coupon edit screen.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_checkbox_after_description( $post ) {
		if ( $post->post_type !== 'shop_coupon' ) {
			return;
		}

		$is_checked = get_post_meta( $post->ID, '_revx_next_order_coupon_enable', true );
		?>
		<div class="wsx-coupon-checkbox" style="margin: 20px 0; padding: 10px; background: #fff; border: 1px solid #ccd0d4;">
			<label>
				<input type="checkbox" name="revx_next_order_coupon_enable" value="yes" <?php checked( $is_checked, 'yes' ); ?> />
				<?php _e( 'Enable Custom Coupon Option', 'your-textdomain' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Save the custom checkbox value.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_custom_coupon_checkbox( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( get_post_type( $post_id ) !== 'shop_coupon' ) {
			return;
		}

		if ( isset( $_POST['revx_next_order_coupon_enable'] ) ) {
			update_post_meta( $post_id, '_revx_next_order_coupon_enable', 'yes' );
		} else {
			delete_post_meta( $post_id, '_revx_next_order_coupon_enable' );
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'revenue/v1',
			'/custom-coupons',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_custom_coupons' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get custom coupons.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_custom_coupons( $request ) {
		$coupons = $this->revenue_get_custom_coupons();
		return rest_ensure_response( $coupons );
	}

	/**
	 * Filter coupons created from the plugin.
	 */
	public function revenue_get_custom_coupons() {
		global $wpdb;

		$query = "
			SELECT 
				p.ID,
				p.post_title AS coupon_code,
				(
					SELECT meta_value 
					FROM {$wpdb->postmeta} 
					WHERE post_id = p.ID AND meta_key = 'coupon_amount' 
					LIMIT 1
				) AS coupon_amount
			FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS pm
				ON p.ID = pm.post_id
			WHERE p.post_type = 'shop_coupon'
			  AND pm.meta_key = '_revx_next_order_coupon_enable'
			  AND pm.meta_value = 'yes'
			GROUP BY p.ID
		";

		$results = $wpdb->get_results( $query );

		$coupons = array();
		foreach ( $results as $row ) {
			$coupons[] = array(
				'id'    => $row->ID,
				'title' => $row->coupon_code,
			);
		}

		return $coupons;
	}

	/**
	 * Custom AJAX action to save the coupon.
	 *
	 * This method handles the AJAX request to save a coupon.
	 * It checks if the request is valid and updates the coupon status to 'publish'.
	 *
	 * @return void
	 */
	public function custom_save_coupon_action() {
		// Check if it's a valid coupon ID.
		if ( isset( $_POST['post_ID'] ) && is_numeric( $_POST['post_ID'] ) ) {
			$coupon_id = intval( $_POST['post_ID'] );

			// Get the coupon post object.
			$coupon = get_post( $coupon_id );

			if ( 'yes' !== $_POST['revx_next_order_coupon_enable'] ) {
				wp_send_json_error( array( 'message' => 'You must select Enable Custom Coupon Option' ) );
			}

			// Perform the update action for an existing coupon.
			if ( 'shop_coupon' === $coupon->post_type ) {
				// Update post title and description if provided.
				$post_data = array(
					'ID'          => $coupon_id,
					'post_status' => isset( $_POST['post_status'] ) ? sanitize_text_field( $_POST['post_status'] ) : 'publish',
				);

				if ( isset( $_POST['post_title'] ) ) {
					$post_data['post_title'] = sanitize_text_field( $_POST['post_title'] );
				}

				if ( isset( $_POST['excerpt'] ) ) {
					$post_data['post_excerpt'] = sanitize_textarea_field( $_POST['excerpt'] );
				}

				wp_update_post( $post_data );

				// Update custom meta fields (example)
				if ( isset( $_POST['revx_custom_coupon_text'] ) ) {
					update_post_meta( $coupon_id, '_revx_custom_coupon_text', sanitize_text_field( $_POST['revx_custom_coupon_text'] ) );
				}

				// You can add more update_post_meta() calls here as needed for other custom fields

				wp_send_json_success(
					array(
						'message'   => 'Coupon updated successfully',
						'coupon_id' => $coupon_id,
					)
				);
			}
		} else {
			// Handle creating a new coupon.
			$new_coupon_id = wp_insert_post(
				array(
					'post_title'   => isset( $_POST['post_title'] ) ? sanitize_text_field( $_POST['post_title'] ) : '',
					'post_content' => isset( $_POST['post_content'] ) ? sanitize_textarea_field( $_POST['post_content'] ) : '',
					'post_excerpt' => isset( $_POST['excerpt'] ) ? sanitize_textarea_field( $_POST['excerpt'] ) : '', // Save the excerpt
					'post_type'    => 'shop_coupon',
					'post_status'  => isset( $_POST['post_status'] ) ? $_POST['post_status'] : 'publish',
				)
			);
			if ( $new_coupon_id ) {
				update_post_meta( $new_coupon_id, '_revx_next_order_coupon_enable', 'yes' );

				wp_send_json_success(
					array(
						'message'   => 'Coupon created successfully',
						'coupon_id' => $new_coupon_id,
					)
				);
			} else {
				wp_send_json_error( array( 'message' => 'Failed to create coupon' ) );
			}
		}

		wp_send_json_error( array( 'message' => 'Invalid coupon ID or data' ) );
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
		$data = array();

		return $data;
	}

	/**
	 * Validate coupon eligibility for the next order.
	 *
	 * @param bool   $is_valid Whether the coupon is valid.
	 * @param object $coupon   The coupon object.
	 * @param object $discount The discount object.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	public function revx_validate_coupon_eligibility( $is_valid, $coupon, $discount ) {

		$is_revx_campaign = get_post_meta( $coupon->get_id(), '_revx_next_order_coupon_enable', true );
		if ( 'yes' === $is_revx_campaign && is_user_logged_in() ) {
				$user_id = get_current_user_id();

				// Support both classic and block cart: check $_POST, then fallback to cart object (blocks).
				$coupon_code = '';
				$coupon_id   = '';
			if ( isset( $_POST['coupon_code'] ) ) {
				$coupon_code = $_POST['coupon_code'];
			} elseif ( function_exists( 'WC' ) && WC()->cart ) {
				$applied_coupons = WC()->cart->get_applied_coupons();

				if ( ! empty( $applied_coupons ) ) {
					foreach ( $applied_coupons as $applied_coupon_code ) {
						// Compare with your target coupon.
						if ( strtolower( $coupon->get_code() ) === strtolower( $applied_coupon_code ) ) {
							$coupon_code = $applied_coupon_code;

							// Get WC_Coupon object.
							$coupon_obj = new \WC_Coupon( $applied_coupon_code );

							// Get coupon ID.
							$coupon_id = $coupon_obj->get_id();

							break;
						}
					}
				}
			}
				$coupon_id     = isset( $_POST['coupon_code'] ) ? $coupon->get_id() : $coupon_id;
				$campaign_id   = get_user_meta( $user_id, '_revx_next_order_campaign_id_' . $coupon_id, true );
				$eligible      = get_user_meta( $user_id, '_revx_next_order_coupon_eligible_' . $campaign_id, true );
				$p_coupon_code = isset( $_POST['coupon_code'] ) ? $_POST['coupon_code'] : $coupon_code;

			if ( $p_coupon_code && strtolower( $coupon->get_code() ) === strtolower( $p_coupon_code ) && 'yes' !== $eligible ) {
				return false; // Invalidate the coupon if not eligible.
			}
		} else {
			// Guest user logic.
			if ( 'yes' === $is_revx_campaign && ! is_user_logged_in() ) {
				$coupon_id     = $coupon->get_id();
				$campaign_id   = $this->get_guest_meta( '_revx_next_order_campaign_id_' . $coupon_id );
				$eligible      = $this->get_guest_meta( '_revx_next_order_coupon_eligible_' . $campaign_id );
				$p_coupon_code = isset( $_POST['coupon_code'] ) ? $_POST['coupon_code'] : $coupon->get_code();

				if ( $p_coupon_code && strtolower( $coupon->get_code() ) === strtolower( $p_coupon_code ) && 'yes' !== $eligible ) {
					return false;
				}
			}
		}
		return $is_valid;
	}

	/**
	 * Display next order coupon message on the thank-you page.
	 *
	 * @param int    $order_id The order ID.
	 * @param string $position The position to display the coupon ('thankyou' or 'before_thankyou').
	 */
	public function display_next_order_coupon_message( $order_id, $position ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( is_user_logged_in() ) {
			// Try to get campaign_id from any of the order items.
			$campaign_id = null;
			foreach ( $order->get_items() as $item ) {
				$maybe_campaign_id = $item->get_meta( '_revx_campaign_next_id' );
				if ( ! empty( $maybe_campaign_id ) ) {
					$campaign_id = $maybe_campaign_id;
					break;
				}
			}

			// Stop if no campaign ID found.
			if ( ! $campaign_id ) {
				return;
			}

			$campaign_setting  = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$campaign_position = revenue()->get_campaign_meta( $campaign_id, 'placement_settings', true );
			$thankyou_page     = $campaign_position['thankyou_page']['inpage_position'] ?? 'before_thankyou';

			$user_id  = get_current_user_id();
			$eligible = get_user_meta( $user_id, '_revx_next_order_coupon_eligible_' . $campaign_id, true );

			if ( isset( $campaign_position['thankyou_page'] ) && $eligible === 'yes' ) {
				$coupon_id = get_user_meta( $user_id, '_revx_next_order_coupon_id', true );
				if ( $coupon_id ) {
					$coupon          = new \WC_Coupon( $coupon_id );
					$discount_type   = $coupon->get_discount_type();
					$amount          = $coupon->get_amount();
					$currency_symbol = get_woocommerce_currency_symbol();
					$coupon_amount   = '';
					switch ( $discount_type ) {
						case 'percent':
							$coupon_amount = $amount . '%';
							break;

						case 'fixed_cart':
							$coupon_amount = $amount . $currency_symbol;
							break;

						case 'fixed_product':
							$coupon_amount = $amount . $currency_symbol;
							break;

						default:
							$coupon_amount = '';
							break;
					}
					if ( $coupon->get_id() && $coupon->get_status() === 'publish' && $thankyou_page === $position ) {
						$this->render_coupon_banner( $campaign_setting, $coupon->get_code(), $coupon_amount, $campaign_id );
					}
				}
			}
		} else {
			// Guest user logic.
			$email       = $order->get_billing_email();
			$coupon_id   = $this->get_guest_meta( '_revx_next_order_coupon_id' );
			$campaign_id = $this->get_guest_meta( '_revx_next_order_campaign_id_' . $coupon_id );

			$campaign_setting  = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$campaign_position = revenue()->get_campaign_meta( $campaign_id, 'placement_settings', true );
			$thankyou_page     = $campaign_position['thankyou_page']['inpage_position'] ?? 'before_thankyou';

			$eligible = $this->get_guest_meta( '_revx_next_order_coupon_eligible_' . $campaign_id );
			if ( isset( $campaign_position['thankyou_page'] ) && $eligible === 'yes' ) {
				if ( $coupon_id ) {
					$coupon          = new \WC_Coupon( $coupon_id );
					$discount_type   = $coupon->get_discount_type();
					$amount          = $coupon->get_amount();
					$currency_symbol = get_woocommerce_currency_symbol();
					$coupon_amount   = '';
					switch ( $discount_type ) {
						case 'percent':
							$coupon_amount = $amount . '%';
							break;
						case 'fixed_cart':
							$coupon_amount = $amount . $currency_symbol;
							break;
						case 'fixed_product':
							$coupon_amount = $amount . $currency_symbol;
							break;
						default:
							$coupon_amount = '';
							break;
					}
					if ( $coupon->get_id() && $coupon->get_status() === 'publish' && $thankyou_page === $position ) {
						$this->render_coupon_banner( $campaign_setting, $coupon->get_code(), $coupon_amount, $campaign_id );
					}
				}
			}
		}
	}

	/**
	 * Display next order coupon message below the thank-you page.
	 *
	 * @param int $order_id The order ID.
	 */
	public function display_next_order_coupon_message_below( $order_id ) {
		$this->display_next_order_coupon_message( $order_id, 'thankyou' );
	}

	/**
	 * Display next order coupon message at the top of the thank-you page.
	 *
	 * @param int $order_id The order ID.
	 */
	public function display_next_order_coupon_message_top( $order_id ) {
		$this->display_next_order_coupon_message( $order_id, 'before_thankyou' );
	}

	/**
	 * Display next order coupon message on the My Account page.
	 *
	 * @param string $position The position to display the coupon ('my_account_top' or 'my_account_bottom').
	 */
	public function display_next_order_coupon_message_my_account( $position ) {
		// if ( ! is_user_logged_in() ) {
		// return;
		// }
		if ( is_user_logged_in() ) {
			$user_id     = get_current_user_id();
			$coupon_id   = get_user_meta( $user_id, '_revx_next_order_coupon_id', true );
			$campaign_id = get_user_meta( $user_id, '_revx_next_order_campaign_id_' . $coupon_id, true );
			$eligible    = get_user_meta( $user_id, '_revx_next_order_coupon_eligible_' . $campaign_id, true );
		} else {
			$coupon_id   = $this->get_guest_meta( '_revx_next_order_coupon_id' );
			$campaign_id = $this->get_guest_meta( '_revx_next_order_campaign_id_' . $coupon_id );
			$eligible    = $this->get_guest_meta( '_revx_next_order_coupon_eligible_' . $campaign_id );
		}
		if ( $eligible === 'yes' ) {
			if ( $coupon_id ) {
				$coupon          = new \WC_Coupon( $coupon_id );
				$discount_type   = $coupon->get_discount_type();
				$amount          = $coupon->get_amount();
				$currency_symbol = get_woocommerce_currency_symbol();
				$coupon_amount   = '';
				switch ( $discount_type ) {
					case 'percent':
						$coupon_amount = $amount . '%';
						break;

					case 'fixed_cart':
						$coupon_amount = $amount . $currency_symbol;
						break;

					case 'fixed_product':
						$coupon_amount = $amount . $currency_symbol;
						break;

					default:
						$coupon_amount = '';
						break;
				}
				if ( $coupon->get_id() && $coupon->get_status() === 'publish' ) {
					$campaign_setting  = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
					$campaign_position = revenue()->get_campaign_meta( $campaign_id, 'placement_settings', true );
					$my_account_page   = $campaign_position['my_account']['inpage_position'];

					if ( $my_account_page === $position && is_user_logged_in() ) {
						$this->render_coupon_banner( $campaign_setting, $coupon->get_code(), $coupon_amount, $campaign_id );
					} elseif ( ! is_user_logged_in() ) {
						$this->render_coupon_banner( $campaign_setting, $coupon->get_code(), $coupon_amount, $campaign_id );
					}
				}
			}
		}
	}

	/**
	 * Display next order coupon message at the top of the My Account page.
	 */
	public function display_next_order_coupon_message_my_account_top() {
		$this->display_next_order_coupon_message_my_account( 'rvex_above_my_account' );
	}

	/**
	 * Display next order coupon message at the bottom of the My Account page.
	 */
	public function display_next_order_coupon_message_my_account_bottom() {
		$this->display_next_order_coupon_message_my_account( 'rvex_below_my_account' );
	}
	/**
	 * Display next order coupon message at the before cart page.
	 */
	public function revx_show_next_order_coupon_message() {
		$coupon_id = $this->get_guest_meta( '_revx_next_order_coupon_id' );
		if ( ! is_user_logged_in() && $this->revx_validate_coupon_by_id( $coupon_id ) ) {
			$this->display_next_order_coupon_message_my_account( 'rvex_above_cart_page' );
		}
	}

	/**
	 * Update user eligibility for the next order coupon after placing an order.
	 *
	 * @param int $order_id The order ID.
	 */
	public function update_user_coupon_eligibility( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( is_user_logged_in() ) {
			$user_id = $order->get_user_id();

			if ( ! $user_id ) {
				return;
			}

			$campaign_id = null;

			foreach ( $order->get_items() as $item ) {
				$maybe_campaign_id = $item->get_meta( '_revx_campaign_next_id' );
				if ( ! empty( $maybe_campaign_id ) ) {
					$campaign_id = $maybe_campaign_id;
					break;
				}
			}

			if ( ! $campaign_id ) {
				return;
			}

			$triggered_items      = $this->get_triggered_items_by_campaign( $campaign_id );
			$campaign_setting     = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$campaign             = revenue()->get_campaign_data( $campaign_id );
			$relation             = $campaign['campaign_trigger_relation'] ?? 'or';
			$ordered_product_ids  = array();
			$ordered_category_ids = array();
			$campaign_all_product = isset( $campaign['campaign_trigger_type'] ) ? $campaign['campaign_trigger_type'] : 'no_products';

			foreach ( $order->get_items() as $item ) {
				$product_id            = $item->get_product_id();
				$ordered_product_ids[] = $product_id;

				$product = wc_get_product( $product_id );
				if ( $product ) {
					$product_cat_ids      = wc_get_product_term_ids( $product_id, 'product_cat' );
					$ordered_category_ids = array_merge( $ordered_category_ids, $product_cat_ids );
				}
			}

			$ordered_category_ids = array_unique( $ordered_category_ids );

			// Exclude products check — if any match, user is NOT eligible.
			if ( ! empty( $triggered_items['exclude_products'] ) && array_intersect( $triggered_items['exclude_products'], $ordered_product_ids ) ) {
				return;
			}

			// Include products validation.
			$product_match = false;
			if ( $relation === 'or' ) {
				$product_match = ! empty( array_intersect( $triggered_items['include_products'], $ordered_product_ids ) );
			} elseif ( $relation === 'and' ) {
				$product_match = empty( array_diff( $triggered_items['include_products'], $ordered_product_ids ) );
			} else {
				$product_match = false;
			}

			// Include categories validation.
			$category_match = false;
			if ( ! empty( $triggered_items['include_categories'] ) ) {
				$category_match = ! empty( array_intersect( $triggered_items['include_categories'], $ordered_category_ids ) );
			} else {
				$category_match = false; // If not set, don't restrict.
			}

			// Final eligibility: must match include_products/categories and NOT match excluded products.
			$is_eligible_specific_products = $product_match || $category_match;
			$is_eligible                   = $is_eligible_specific_products || 'all_products' === $campaign_all_product;

			if ( $is_eligible ) {
				update_user_meta( $user_id, '_revx_next_order_coupon_eligible_' . $campaign_id, 'yes' );
				update_user_meta( $user_id, '_revx_next_order_coupon_id', $campaign_setting['choose_next_order_coupon'] );
				update_user_meta( $user_id, '_revx_next_order_campaign_id_' . $campaign_setting['choose_next_order_coupon'], $campaign_id );
			}
		} else {
			// Guest users logic.
			$email = $order->get_billing_email();
			if ( ! $email ) {
				return;
			}
			$guest_key   = 'revx_guest_' . md5( strtolower( $email ) );
			$campaign_id = null;
			foreach ( $order->get_items() as $item ) {
				$maybe_campaign_id = $item->get_meta( '_revx_campaign_next_id' );
				if ( ! empty( $maybe_campaign_id ) ) {
					$campaign_id = $maybe_campaign_id;
					break;
				}
			}
			if ( ! $campaign_id ) {
				return;
			}
			$triggered_items      = $this->get_triggered_items_by_campaign( $campaign_id );
			$campaign_setting     = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$campaign             = revenue()->get_campaign_data( $campaign_id );
			$relation             = $campaign['campaign_trigger_relation'] ?? 'or';
			$ordered_product_ids  = array();
			$ordered_category_ids = array();
			$campaign_all_product = isset( $campaign['campaign_trigger_type'] ) ? $campaign['campaign_trigger_type'] : 'no_products';
			foreach ( $order->get_items() as $item ) {
				$product_id            = $item->get_product_id();
				$ordered_product_ids[] = $product_id;
				$product               = wc_get_product( $product_id );
				if ( $product ) {
					$product_cat_ids      = wc_get_product_term_ids( $product_id, 'product_cat' );
					$ordered_category_ids = array_merge( $ordered_category_ids, $product_cat_ids );
				}
			}
			$ordered_category_ids = array_unique( $ordered_category_ids );
			if ( ! empty( $triggered_items['exclude_products'] ) && array_intersect( $triggered_items['exclude_products'], $ordered_product_ids ) ) {
				return;
			}
			$product_match = false;
			if ( $relation === 'or' ) {
				$product_match = ! empty( array_intersect( $triggered_items['include_products'], $ordered_product_ids ) );
			} elseif ( $relation === 'and' ) {
				$product_match = empty( array_diff( $triggered_items['include_products'], $ordered_product_ids ) );
			} else {
				$product_match = false;
			}
			$category_match = false;
			if ( ! empty( $triggered_items['include_categories'] ) ) {
				$category_match = ! empty( array_intersect( $triggered_items['include_categories'], $ordered_category_ids ) );
			} else {
				$category_match = false;
			}
			$is_eligible_specific_products = $product_match || $category_match;
			$is_eligible                   = $is_eligible_specific_products || 'all_products' === $campaign_all_product;
			if ( $is_eligible ) {
				// Store in session for guest.
				$this->set_guest_meta( '_revx_next_order_coupon_eligible_' . $campaign_id, 'yes' );
				$this->set_guest_meta( '_revx_next_order_coupon_id', $campaign_setting['choose_next_order_coupon'] );
				$this->set_guest_meta( '_revx_next_order_campaign_id_' . $campaign_setting['choose_next_order_coupon'], $campaign_id );
			}
		}
	}



	/**
	 * Render the coupon banner.
	 *
	 * @param string $coupon_settings The coupon code to display.
	 * @param string $coupon_code    The coupon code to display.
	 * @param string $discount       The discount percentage to display.
	 */
	public function render_coupon_banner( $coupon_settings, $coupon_code = '', $discount = '', $campaign_id = '' ) {
		$campaign         = revenue()->get_campaign_data( $campaign_id );
		$coupon_settings  = $campaign['revx_next_order_coupon'];
		$generated_styles = revenue()->campaign_style_generator( 'inpage', $campaign );

		$container_style                 = revenue()->get_style( $generated_styles, 'CouponContainer' );
		$coupon_button_style             = revenue()->get_style( $generated_styles, 'CouponButton' );
		$coupon_buttons_style            = revenue()->get_style( $generated_styles, 'CouponButtons' );
		$coupon_content_style            = revenue()->get_style( $generated_styles, 'couponContent' );
		$coupon_content_container_style  = revenue()->get_style( $generated_styles, 'couponContentContainer' );
		$paragraph_coupon_title_style    = revenue()->get_style( $generated_styles, 'paragraphCouponTitle' );
		$paragraph_coupon_subtitle_style = revenue()->get_style( $generated_styles, 'paragraphCouponSubTitle' );
		$discount_with_title             = str_replace( '{discount_value}', $discount, $coupon_settings['coupon_title'] );
		$coupon_banner_message           = $coupon_settings['coupon_icon_text'] ?? 'COUPON';
		?>

		<div style="width: 100%; margin-left: auto; margin-right: auto;">
			<div>
				<div style="<?php echo esc_attr( $container_style ); ?> border: 0px; background-color: unset; position: relative; z-index: 1; margin: 0 auto; overflow: hidden; display: flex; align-items: center; width: fit-content;">
					<div style="height: 42px; width: 52px; background-color: transparent; border-radius: 50%; position: absolute; left: -28px; border: 1px dashed var(--revx-border-color, #6c5ce7);"></div>
					<div style="height: 42px; width: 52px; background-color: transparent; border-radius: 50%; position: absolute; right: -28px; border: 1px dashed var(--revx-border-color, #6c5ce7);"></div>
					<div style="box-sizing: border-box; padding: 4px; width: 100%; max-width: 440px; border: 1px dashed var(--revx-border-color, #6c5ce7);mask-image: radial-gradient(circle at 0% 50%, transparent 25px, black 26px), radial-gradient(circle at 100% 50%, transparent 25px, black 26px); mask-composite: intersect;">
						<div class="revx-coupon-template-wrapper" style="position: relative; z-index: 0; box-sizing: border-box; width: auto; height: 100%; display: flex; max-width: 440px; border: 1px dashed var(--revx-border-color, #6c5ce7);">
							<div class="revx-coupon-template-container" style="background-color: var(--revx-background-color, #6c5ce7);">
							<?php	echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'couponIconText', $coupon_banner_message, 'revx-coupon-icon-rich-text' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<div class="revx-coupon-template-1-content" style="<?php echo esc_attr( $coupon_content_container_style ); ?> border-left: 2px dashed var(--revx-separator-color, #ffffff);">
									<div class="revx-coupon-content" style="<?php echo esc_attr( $coupon_content_style ); ?>">
										<div class="paragraphCouponTitle" style="<?php echo esc_attr( $paragraph_coupon_title_style ); ?>">
											<?php echo $discount_with_title; ?>
										</div>
										<p class="paragraphCouponSubTitle" style="<?php echo esc_attr( $paragraph_coupon_subtitle_style ); ?>">
											<?php echo $coupon_settings['coupon_subheading']; ?>
										</p>
									</div>

									<div class="revx-coupon-buttons" style="<?php echo esc_attr( $coupon_buttons_style ); ?>">
										<div class="revx-Coupon-button" style="<?php echo esc_attr( $coupon_button_style ); ?> text-transform: uppercase;">
											<?php echo htmlspecialchars( $coupon_code ); ?>
											<span class="revx-coupon-copy-btn" style="display: flex; align-items: center; cursor:pointer;" >
												<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
													<g clipPath="url(#clip0_1180_32578)">
													<path d="M13.3333 6H7.33333C6.59695 6 6 6.59695 6 7.33333V13.3333C6 14.0697 6.59695 14.6667 7.33333 14.6667H13.3333C14.0697 14.6667 14.6667 14.0697 14.6667 13.3333V7.33333C14.6667 6.59695 14.0697 6 13.3333 6Z" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
													<path d="M3.33301 9.99967H2.66634C2.31272 9.99967 1.97358 9.8592 1.72353 9.60915C1.47348 9.3591 1.33301 9.01996 1.33301 8.66634V2.66634C1.33301 2.31272 1.47348 1.97358 1.72353 1.72353C1.97358 1.47348 2.31272 1.33301 2.66634 1.33301H8.66634C9.01996 1.33301 9.3591 1.47348 9.60915 1.72353C9.8592 1.97358 9.99967 2.31272 9.99967 2.66634V3.33301" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
													</g>
													<defs>
													<clipPath id="clip0_1180_32578">
													<rect width="16" height="16" fill="currentColor"/>
													</clipPath>
													</defs>
												</svg>
											</span>
										</div>
										<a href="<?php echo esc_url( $coupon_settings['coupon_button_link'] ); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
											<div style="font-size: 14px; font-weight: 500; background-color: #FFFFFF; color: #6E3FF3; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; transition: all 0.2s ease-in-out; padding: 8px 24px; cursor: pointer;">
												<?php echo esc_html( $campaign['cta_button_text'] ?? __( 'Shop Now', 'revenue' ) ); ?>
											</div>
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the coupon banner for email.
	 *
	 * @param string $coupon_settings The coupon code to display.
	 * @param string $coupon_code    The coupon code to display.
	 * @param string $discount       The discount percentage to display.
	 */
	public function render_coupon_banner_for_email( $coupon_settings, $coupon_code = '', $discount = '', $campaign_id = '' ) {
		$campaign             = revenue()->get_campaign_data( $campaign_id );
		$discount_with_title  = str_replace( '{discount_value}', $discount, $coupon_settings['coupon_title'] );
		$generated_styles     = revenue()->campaign_style_generator( 'inpage', $campaign );
		$container_style      = revenue()->get_style( $generated_styles, 'containerEmail' );
		$paragraph_main_style = revenue()->get_style( $generated_styles, 'paragraphMainEmail' );
		$paragraph_sub_style  = revenue()->get_style( $generated_styles, 'paragraphSubEmail' );
		$coupon_button_style  = revenue()->get_style( $generated_styles, 'emailCouponButton' );
		$shop_button_style    = revenue()->get_style( $generated_styles, 'emailShopButton' );

		ob_start();
		?>
		<p>
		<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; max-width: 600px; width: 100%; margin: 0 auto;">
		<tr>
			<td style="<?php echo esc_attr( $container_style ); ?> border: 2px dashed #ffffff; padding: 20px; text-align: center; position: relative;">
				<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
					<tr>
						<td style="text-align: center;">
							<h2 style="<?php echo esc_attr( $paragraph_main_style ); ?>  margin: 0 0 12px 0; font-weight: bold;">
								<?php echo $discount_with_title; ?>
							</h2>

							<p style="<?php echo esc_attr( $paragraph_sub_style ); ?> margin: 0 0 12px 0;">
							<?php echo $coupon_settings['coupon_subheading']; ?>
							</p>

							<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 10px auto; border-collapse: separate;">
								<tr>
									<td style="<?php echo esc_attr( $coupon_button_style ); ?> border: 2px dashed #ffffff; border-radius: 8px; padding: 12px 24px;">
										<?php echo $coupon_code; ?>
									</td>
									<td style="padding-left: 15px;"><a target="_blank" href="<?php echo esc_url( $coupon_settings['coupon_button_link'] ); ?>" style="<?php echo esc_attr( $shop_button_style ); ?> text-decoration: none; padding: 12px 24px; display: inline-block;"><?php echo esc_html( $campaign['cta_button_text'] ?? 'Shop Now' ); ?></a></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</p>
		<?php
		$html = ob_get_clean();
		// Minify the HTML before returning or sending in email.
		echo $this->minify_email_html( $html );
	}

	/**
	 * Automatically apply the coupon on the cart page if eligible.
	 */
	public function revx_auto_apply_coupon_and_show_notice() {
		if ( ! is_cart() || ! is_user_logged_in() || is_admin() || wc_notice_count() > 0 ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$user_id           = get_current_user_id();
			$coupon_id         = get_user_meta( $user_id, '_revx_next_order_coupon_id', true );
			$campaign_id       = get_user_meta( $user_id, '_revx_next_order_campaign_id_' . $coupon_id, true );
			$campaign_setting  = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$auto_coupon_apply = $campaign_setting['coupon_apply_automatically'] ?? 'no';
			$eligible          = get_user_meta( $user_id, '_revx_next_order_coupon_eligible_' . $campaign_id, true );
			if ( $eligible === 'yes' && $auto_coupon_apply === 'yes' && $coupon_id ) {
				$coupon = new \WC_Coupon( $coupon_id );
				if ( $coupon->get_id() && $coupon->get_status() === 'publish' ) {
					$coupon_code = $coupon->get_code();

					// Apply coupon if not already applied.
					if ( ! WC()->cart->has_discount( $coupon_code ) ) {
						WC()->cart->apply_coupon( $coupon_code );

						// Add notice.
						// wc_add_notice( sprintf( __( 'Coupon "%s" has been automatically applied hmd.', 'revenue' ), $coupon_code ), 'success' );
					}
				}
			}
		} else {
			// Guest user logic.
			$coupon_id         = $this->get_guest_meta( '_revx_next_order_coupon_id' );
			$campaign_id       = $this->get_guest_meta( '_revx_next_order_campaign_id_' . $coupon_id );
			$campaign_setting  = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$auto_coupon_apply = $campaign_setting['coupon_apply_automatically'] ?? 'no';
			$eligible          = $this->get_guest_meta( '_revx_next_order_coupon_eligible_' . $campaign_id );
			if ( $eligible === 'yes' && $auto_coupon_apply === 'yes' && $coupon_id ) {
				$coupon = new \WC_Coupon( $coupon_id );
				if ( $coupon->get_id() && $coupon->get_status() === 'publish' ) {
					$coupon_code = $coupon->get_code();
					if ( ! WC()->cart->has_discount( $coupon_code ) ) {
						WC()->cart->apply_coupon( $coupon_code );
					}
				}
			}
		}
	}

	/**
	 * Register the [revenue_coupon] shortcode to render the coupon banner in emails.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered HTML for the coupon banner.
	 */
	public function render_coupon_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => '', // Coupon ID.
			),
			$atts,
			'revenue_coupon'
		);

		$coupon_id = intval( $atts['id'] );
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( ! $coupon_id ) {
				return ''; // Return empty if no coupon ID is provided.
			}

			$coupon          = new \WC_Coupon( $coupon_id );
			$discount_type   = $coupon->get_discount_type();
			$amount          = $coupon->get_amount();
			$currency_symbol = get_woocommerce_currency_symbol();
			$coupon_amount   = '';
			switch ( $discount_type ) {
				case 'percent':
					$coupon_amount = $amount . '%';
					break;

				case 'fixed_cart':
					$coupon_amount = $amount . $currency_symbol;
					break;

				case 'fixed_product':
					$coupon_amount = $amount . $currency_symbol;
					break;

				default:
					$coupon_amount = '';
					break;
			}
			if ( ! $coupon->get_id() || $coupon->get_status() !== 'publish' ) {
				return ''; // Return empty if the coupon is invalid or not published.
			}

			// Fetch campaign settings if available.
			$campaign_id      = get_user_meta( $user_id, '_revx_next_order_campaign_id_' . $coupon->get_id(), true );
			$campaign_setting = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$eligible         = get_user_meta( $user_id, '_revx_next_order_coupon_eligible_' . $campaign_id, true );
			// Render the coupon banner.
			ob_start();
			if ( 'yes' === $eligible ) {
				$this->render_coupon_banner_for_email( $campaign_setting, $coupon->get_code(), $coupon_amount, $campaign_id );
			}
			return ob_get_clean();
		} else {
			// Guest user logic
			if ( ! $coupon_id ) {
				return '';
			}
			$coupon          = new \WC_Coupon( $coupon_id );
			$discount_type   = $coupon->get_discount_type();
			$amount          = $coupon->get_amount();
			$currency_symbol = get_woocommerce_currency_symbol();
			$coupon_amount   = '';
			switch ( $discount_type ) {
				case 'percent':
					$coupon_amount = $amount . '%';
					break;
				case 'fixed_cart':
					$coupon_amount = $amount . $currency_symbol;
					break;
				case 'fixed_product':
					$coupon_amount = $amount . $currency_symbol;
					break;
				default:
					$coupon_amount = '';
					break;
			}
			if ( ! $coupon->get_id() || $coupon->get_status() !== 'publish' ) {
				return '';
			}
			$campaign_id      = $this->get_guest_meta( '_revx_next_order_campaign_id_' . $coupon->get_id() );
			$campaign_setting = revenue()->get_campaign_meta( $campaign_id, 'revx_next_order_coupon', true );
			$eligible         = $this->get_guest_meta( '_revx_next_order_coupon_eligible_' . $campaign_id );
			ob_start();
			if ( 'yes' === $eligible ) {
				$this->render_coupon_banner_for_email( $campaign_setting, $coupon->get_code(), $coupon_amount, $campaign_id );
			}
			return ob_get_clean();
		}
	}

	/**
	 * Helper: Get guest key (email or session).
	 *
	 * @param WC_Order|null $order The order object.
	 */
	private function get_guest_key( $order = null ) {
		if ( $order && is_object( $order ) ) {
			$email = $order->get_billing_email();
			if ( $email ) {
				return sanitize_email( $email );
			}
		}
		if ( isset( $_REQUEST['billing_email'] ) ) {
			return sanitize_email( $_REQUEST['billing_email'] );
		}
		if ( WC()->session ) {
			return WC()->session->get_customer_id();
		}
		return null;
	}

	/**
	 * Helper: Set guest meta in session.
	 *
	 * @param string $key   The meta key.
	 * @param mixed  $value The meta value.
	 */
	private function set_guest_meta( $key, $value ) {
		if ( WC()->session ) {
			WC()->session->set( $key, $value );
		}
	}

	/**
	 * Helper: Get guest meta from session.
	 *
	 * @param string $key The meta key.
	 */
	private function get_guest_meta( $key ) {
		if ( WC()->session ) {
			return WC()->session->get( $key );
		}
		return null;
	}

	/**
	 * Minify HTML for email.
	 *
	 * @param string $html The HTML content to minify.
	 * @return string Minified HTML.
	 */
	public function minify_email_html( $html ) {
		return preg_replace( '/>\s+(?=<)/', '>', $html );
	}
}
