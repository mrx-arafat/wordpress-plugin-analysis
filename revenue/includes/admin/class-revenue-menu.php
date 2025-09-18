<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Menu Admin Class
 *
 * @package    Revenue
 * @since      1.0.0
 * @version    1.0.0
 */

namespace Revenue;

use REVX\Includes\Durbin\Xpo;

/**
 * Revenue Menu
 *
 * This file contains the `Revenue_Menu` class, which is responsible for managing custom menu items
 * in the WordPress admin area related to the Revenue plugin. The class includes functionality to
 * add custom admin menus, modify WooCommerce search responses, and perform other administrative tasks
 * specific to the plugin.
 *
 * Class Revenue_Menu
 *
 * @package    Revenue
 * @subpackage Admin
 * @since      1.0.0
 */
class Revenue_Menu {


	/**
	 * Constructor method for initializing the class.
	 *
	 * This method sets up the necessary hooks and filters to modify the default WooCommerce search responses
	 * for products and categories. It also adds an admin menu and clears the admin interface as needed.
	 *
	 * The hooks and filters added include:
	 * - `admin_menu`: Adds a custom admin menu via the `add_admin_menu` method.
	 * - `woocommerce_json_search_found_products`: Modifies the WooCommerce product search response using
	 *   the `modify_woocommerce_product_search_response` method.
	 * - `woocommerce_json_search_found_categories`: Modifies the WooCommerce category search response using
	 *   the `modify_woocommerce_category_search_response` method.
	 * - `woocommerce_json_search_found_product_attribute_terms`: Also modifies the WooCommerce category search
	 *   response using the `modify_woocommerce_category_search_response` method.
	 * - `admin_head`: Clears the admin interface via the `clear_interface` method.
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'woocommerce_json_search_found_products', array( $this, 'modify_woocommerce_product_search_response' ) );
		add_filter( 'woocommerce_json_search_found_categories', array( $this, 'modify_woocommerce_category_search_response' ) );
		add_action( 'admin_head', array( $this, 'clear_interface' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}



	/**
	 * Enqueue scripts
	 *
	 * @param string $page Page
	 * @return void
	 */
	public function enqueue_admin_scripts( $page ) {
		if ( 'toplevel_page_revenue' == $page ) {
			wp_enqueue_style( 'revenue-admin', REVENUE_URL . 'assets/css/backend/revenue-admin.css', array(), REVENUE_VER );
			wp_enqueue_script( 'revenue-notice', REVENUE_URL . 'assets/js/backend/revenue-notice.js', array( 'jquery' ), REVENUE_VER, true );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
		}
	}
	/**
	 * Remove All Kind of admin notices on revenue page
	 *
	 * @since v1.0.0
	 */
	public function clear_interface() {
		$screen = get_current_screen();
		if ( 'toplevel_page_revenue' === $screen->id ) {
			remove_all_actions( 'admin_notices' );
		}
		echo '<style>
		.revx-menu-upgrade-to-pro:hover {
			// background-color: #005539 !important;
		}
		</style>';
	}

	/**
	 * Add revenue admin menu
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		global $submenu;

     //phpcs:disable
     $menu_position = revenue()->get_admin_menu_position();
     $menu_title    = revenue()->get_admin_menu_title();
	 
     $slug          = revenue()->get_admin_menu_slug();
	/**
	  * By default, both WordPress administrators and WooCommerce shop managers 
	  * have the 'import' capability. This capability is assigned to ensure the 
	  * functionality is visible to the shop manager. However, in the output callback, 
	  * we need to verify the current user's capabilities to determine if they are 
	  * an administrator (with the 'manage_options' capability) or a shop manager 
	  * (with the 'manage_woocommerce' capability). Access is only granted to 
	  * administrators and shop managers.
	  */
	$capability   = 'import'; 
    //  $menu_icon     ='<svg xmlns="http://www.w3.org/2000/svg" width="50px" height="50px" fill="none" viewBox="0 0 50 50"><path fill="#00A464" d="M49 49H37L25 1h12zM37 49H25L13 17h12zM25 49H13L1 33h12z"></path></svg>';

		$menu_icon = REVENUE_URL . '/assets/images/icons/wowrevenue_logo_sm.svg'; // base64 not provided color logo

		$dashboard_hook = add_menu_page($menu_title, $menu_title, $capability, $slug, array($this, 'dashboard'), $menu_icon, $menu_position);

		if (revenue()->is_user_allowed_to_revenue_dashboard()) {
			$submenu[$slug][] = array(__('Overview', 'revenue'), $capability, 'admin.php?page=' . $slug . '#/');
			$submenu[$slug][] = array(__('Campaigns', 'revenue'), $capability, 'admin.php?page=' . $slug . '#/campaigns');
			$submenu[$slug][] = array(__('Analytics', 'revenue'), $capability, 'admin.php?page=' . $slug . '#/analytics');
			// $submenu[ $slug ][] = array( __( 'Global Settings', 'revenue' ), $capability, 'admin.php?page=' . $slug . '#/settings' );

			if (! revenue()->is_whitelabel_enabled()) {
				if (revenue()->is_pro_ready()) {
					$submenu[$slug][] = array(__('License', 'revenue'), $capability, 'admin.php?page=' . $slug . '#/license');
				}
				$submenu[$slug][] = array(__('Suggest Features', 'revenue'), $capability, 'https://www.wowrevenue.com/roadmap/');
			}

			if ( ! Xpo::is_lc_active() || Xpo::is_lc_expired() ) {
				$icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px; vertical-align: middle;">
					<path d="M2.85952 6.55725C2.74933 6.07978 3.32046 5.74522 3.68305 6.07485L6.70249 8.81979C6.8986 8.99808 7.20913 8.95036 7.34268 8.72143L9.64009 4.783C9.80087 4.50737 10.1991 4.50737 10.3599 4.783L12.6573 8.72143C12.7909 8.95036 13.1014 8.99808 13.2975 8.81979L16.317 6.07485C16.6795 5.74522 17.2507 6.07977 17.1405 6.55725L15.1491 15.1867C15.0618 15.5648 14.7251 15.8327 14.3371 15.8327H5.66293C5.27488 15.8327 4.93819 15.5648 4.85093 15.1867L2.85952 6.55725Z" stroke="white" stroke-width="1.25"/>
				</svg>';

				$name = sprintf(
					'<span class="revx-menu-upgrade-to-pro custom-upgrade-btn" style="background-color:#00A464; color:#ffffff; padding: 10px 12px; margin: 0px -3px; display: flex; align-items: center;border-radius:8px;">%s%s</span>',
					$icon,
					Xpo::is_lc_expired() ? __('Renew License', 'revenue') : __('Upgrade Pro', 'revenue')
				);
				$license_key   = Xpo::get_lc_key();
				$pro_link = !Xpo::is_lc_expired() ? 'https://account.wpxpo.com/checkout/?edd_license_key=' . $license_key : Xpo::generate_utm_link(array('utmKey' => 'submenu'));
				$submenu[$slug][] = array($name, $capability, $pro_link);
			}

			$submenu[$slug] = apply_filters('revenue_submenu_slugs', $submenu[$slug], $slug); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited

			add_action($dashboard_hook, array($this, 'dashboard_scripts'));
		}
	}

	/**
	 * Dashboard scripts and styles
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function dashboard_scripts() {
		$current_user = wp_get_current_user();
		wp_enqueue_script('revenue-admin', REVENUE_URL . 'assets/js/backend/revenue-admin.js', array('react', 'react-dom', 'wp-api-fetch', 'wp-url', 'wp-i18n'), REVENUE_VER, true);
		wp_set_script_translations('revenue-admin', 'revenue');

		wp_enqueue_style('revx-atc', REVENUE_URL . 'assets/css/frontend/animated-atc.css', array(), 588);
		$user_info = get_userdata( get_current_user_id() );


		$localize_data = array(
			'url'                          => REVENUE_URL,
			'version'               	     => REVENUE_VER,
			'ajax'                         => admin_url('admin-ajax.php'),
			'product_search_nonce'         => wp_create_nonce('search-products'),
			'category_search_nonce'        => wp_create_nonce('search-categories'),
			'taxonomy_search_nonce'        => wp_create_nonce('search-taxonomy-terms'),
			'inpage_positions'             => revenue()->get_campaign_inpage_positions(),
			'floating_positions'           => revenue()->get_campaign_floating_positions(),
			'popup_animations'             => revenue()->get_campaign_popup_animation_types(),
			'atc_animations'               => revenue()->get_campaign_animated_add_to_cart_animation_types(),
			'display_types'                => revenue()->get_campaign_display_types(),
			'campaign_placements'          => revenue()->get_campaign_placements(),
			'campaign_position_default_values' => revenue()->get_campaign_position_default_values(),
			'placeholder_image_url'        => function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src() : '',
			'display_name'                 => $current_user->display_name,
			'nonce'                        => wp_create_nonce('revenue-dashboard'),
			'currency_format_num_decimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : "2",
			'currency_format_symbol'       => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : "$",
			'currency_format_decimal_sep'  => function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : ".",
			'currency_format_thousand_sep' => function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ",",
			'currency_format'              => function_exists('get_woocommerce_price_format') ? get_woocommerce_price_format() : '%1$s%2$s',
			'pro_ready'                    => revenue()->is_pro_ready(),
			'is_pro_active'                => revenue()->is_pro_active(),
			'is_woo_ready'				 => class_exists('WooCommerce') ? true : false,
			'is_woo_installed'			 => file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php') ? true : false,
			'woo_logo'					 => esc_url(REVENUE_URL . 'assets/images/woocommerce_logo.png'),
			'campaign_default_placement'   => revenue()->get_campaign_default_placement(),
			'campaign_trigger_types' 		 => revenue()->get_campaign_trigger_types(), // Should change for mix match and some other type of campaigns
			'bxgy_required_qty_get_allowed_trigger_types' => revenue()->get_buyx_gety_individual_product_quantity_trigger_types(),
			'mix_match_required_product_get_trigger_types' => revenue()->get_mix_match_required_product_trigger_types(),
			'trigger_placeholder_messages' => revenue()->get_trigger_placeholder_message(),
			'offered_item_types' => revenue()->get_offered_items_type(),
			'selected_trigger_item_suffix' => revenue()->get_selected_trigger_item_suffix(),
			'selected_offer_item_suffix' => revenue()->get_selected_offer_item_suffix(),
			'item_not_found_messages'    => revenue()->get_item_not_found_messages(),
			'is_show_bundle_with_trigger_product' => revenue()->is_show_bundle_with_trigger_product(),
			'campaign_list_trigger_row_message' => revenue()->get_campaign_list_trigger_row(),
			'campaign_enabled_quantity_selector' => revenue()->show_quantity_selector_on_campaigns(),
			'campaign_counts'                  => revenue()->get_campaign_counts(),
			'helloBar'                  => Xpo::get_transient_without_cache('revx_helloBar'),
			'license'                  => Xpo::get_lc_key(),
			'userInfo'          => array(
				'name'  => $user_info->first_name ? $user_info->first_name . ( $user_info->last_name ? ' ' . $user_info->last_name : '' ) : $user_info->user_login,
				'email' => $user_info->user_email,
			),
 
		);

		if(!revenue()->is_pro_active()) {
			$localize_data['campaigns_stats'] = revenue()->get_campaign_counts();
		}
		wp_localize_script(
			'revenue-admin',
			'revenue',
			array_merge($localize_data, Xpo::get_wow_products_details())
		);

		// Add inline script.
		$inline_script = "
			 function updateActiveLink() {
				const currentUrl = window.location.href;
				const menuItems = document.querySelectorAll('.wp-submenu a');

				menuItems.forEach(menuItem => {
					if (menuItem.href === currentUrl) {
						menuItem.classList.add('active');
					} else {
						menuItem.classList.remove('active');
					}
				});
			}

			document.addEventListener('DOMContentLoaded', () => {
				updateActiveLink();

				// Listen for hash changes (when the URL changes)
				window.addEventListener('hashchange', updateActiveLink);

				// Listen for popstate events (when navigating back/forward)
				window.addEventListener('popstate', updateActiveLink);
			});

			// Sub Menu Redirecting
			// const menuElement = document.getElementById('toplevel_page_revenue');
			// const menuLink = menuElement.querySelectorAll('ul li a');
			// if(menuLink.length > 0 ) {
			// 	menuLink.forEach(url => {
			// 		console.log();
			// 		if(url.getAttribute('href').includes('https:')) {
			// 			url.setAttribute('target','_blank');
			// 		}
			// 	})
			// }
			";

		wp_add_inline_script('revenue-admin', $inline_script);
		do_action('revenue_enqueue_admin_dashboard_scripts');
	}

	/**
	 * Load Dashboard
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function dashboard() {

		if(!revenue()->is_user_allowed_to_revenue_dashboard()) {
			die("Sorry, you are not allowed to access WowRevenue.");
		}


		// Hello bar notice
		$current_time = gmdate( 'U' );

		

		$is_activate =  class_exists('WooCommerce') ? true : false;
		$is_woo_installed = file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php') ? true : false;


		if (!$is_woo_installed && !$is_activate) {
?>

			<div class="revx-wc-install">
				<img
					loading="lazy"
					width="200"
					src="<?php echo esc_url(REVENUE_URL . 'assets/images/woocommerce_logo.png'); ?>"
					alt="WooCommerce logo"
					class="revx-wc-install__img" />
				<div class="revx-wc-install__body">
					<div class="revx-wc-install__content">
						<div class="revx-wc-install__heading">
							<?php echo esc_html(
								"Thank you for installing WowRevenue"
							); ?>
						</div>
						<p class="revx-wc-install__message">
							<?php echo esc_html(
								"It's the most powerful AOV booster for WooCommerce. Please install and activate WooCommerce to use this plugin."
							); ?>
						</p>
					</div>
					<div
						id="revx-install-woocommerce"
						class="revx-wc-install__btn">
						<?php esc_html_e('Install WooCommerce', 'revenue'); ?>
						<span class="revx-wc-install__spinner spinner" style="display:none;"></span>
					</div>

					<div id="installation-msg" class="revx-wc-install__msg"></div>
				</div>
			</div>

		<?php
		} else if ($is_woo_installed && !$is_activate) {
		?>
			<div class="revx-wc-install">
				<img
					loading="lazy"
					width="200"
					src="<?php echo esc_url(REVENUE_URL . 'assets/images/woocommerce_logo.png'); ?>"
					alt="WooCommerce logo"
					class="revx-wc-install__img" />
				<div class="revx-wc-install__body">
					<div class="revx-wc-install__content">
						<div class="revx-wc-install__heading">
		  <?php echo esc_html("Thank you for installing WowRevenue"); ?>
						</div>
						<p class="revx-wc-install__message">
							<?php echo esc_html( "It's the most powerful AOV booster for WooCommerce. Please activate WooCommerce to use this plugin."); ?>
						</p>
					</div>


						<div
							id="revx-activate-woocommerce"
							class="revx-wc-install__btn"
						>
						<?php esc_html_e( 'Activate WooCommerce', 'revenue' ); ?>
							<span class="revx-wc-install__spinner spinner" style="display:none;"></span>
					</div>

					<div id="installation-msg" class="revx-wc-install__msg"></div>
				</div>
			</div>
		<?php
		}
		?>



		<div id="revenue-root"> </div>
<?php

	}


	/**
	 * Modify the WooCommerce product search response for internal requests.
	 *
	 * This function filters the search response for products based on a specific query parameter. It modifies
	 * the response to include additional product details, such as the product's thumbnail, regular price,
	 * and child products (if any). This modification is applied only when the request is identified as coming
	 * from the 'revenue_internal' source.
	 *
	 * @param array $products The original array of products from the search response. Each element is an
	 *                        associative array with product ID as the key and product name as the value.
	 *
	 * @return array The modified array of products with additional details. Each element is an associative
	 *               array with product information including item ID, item name, thumbnail URL, regular price,
	 *               and child products (for variable products). Returns the original products array if the
	 *               request is not from 'revenue_internal'.
	 */
	public function modify_woocommerce_product_search_response($products) {
		check_ajax_referer('search-products', 'security');

		if (isset($_GET['request_from']) && 'revenue_internal' === sanitize_text_field(wp_unslash($_GET['request_from']))) {

			$source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
			$source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';

			$campaign_type = isset($_GET['campaign_type']) ? sanitize_text_field($_GET['campaign_type']) : '';
			$data = array();
			$campaign_type = isset($_GET['campaign_type']) ? sanitize_text_field($_GET['campaign_type']) : '';
			$data = array();

         foreach ($products as $product_id => $name) {
                $product = wc_get_product($product_id);
                if ($product) {

					$chilren    = $product->get_children();
					$child_data = array();
					$product_link = get_permalink($product_id);
					if (is_array($chilren)) {
						foreach ($chilren as $child_id) {
							$child        = wc_get_product($child_id);
							$child_data[] = array(
								'item_id'       => $child_id,
								'item_name'     => rawurldecode(wp_strip_all_tags($child->get_name())),
								// 'product_title_with_sku' => rawurldecode( wp_strip_all_tags($with_sku? $child->get_formatted_name(): $child->get_title())),
								'thumbnail'     => wp_get_attachment_url($child->get_image_id()),
								'regular_price' => $child->get_regular_price(),
								'sale_price' => $child->get_sale_price(),
								'parent'        => $product_id,
								'url'			=> $product_link,
								'show_attribute' => 'variable' == $product->get_type()
							);
						}
					}

					if ($source == 'trigger' && $campaign_type != 'mix_match') {

						$product_data = array(
							'item_id'       => $product->get_id(),
							'url'			=> get_permalink($product),
							'item_name'     => rawurldecode(wp_strip_all_tags($product->get_name())),
							'thumbnail'     => wp_get_attachment_url($product->get_image_id()),
							'regular_price' => $product->get_regular_price(),
							'sale_price' => $product->get_sale_price(),
							'url'			=> $product_link,
							'children'      =>  [],
							'show_attribute' => 'variable' == $product->get_type()
						);

						if ('double_order' == $campaign_type) {
							if ('variable' == $product->get_type()) {
								$data = array_merge($data, $child_data);
							} else {
								$data[] = $product_data;
							}
						} else {

							$data[] = $product_data;
						}
					} else {
						if (!empty($child_data)) {
							$data = array_merge($data, $child_data);
						} else {

							$data[] = array(
								'item_id'       => $product_id,
								'url'			=> get_permalink($product_id),
								'item_name'     => rawurldecode(wp_strip_all_tags($product->get_name())),
								'thumbnail'     => wp_get_attachment_url($product->get_image_id()),
								'regular_price' => $product->get_regular_price(),
								'sale_price' => $product->get_sale_price(),
								'children'      =>  [],
								'url'			=> $product_link,
								'show_attribute' => 'variable' == $product->get_type()
							);
						}
					}
					  }
			}

         return $data;
              }
        return $products;
	}

	/**
	 * Modify the WooCommerce category search response for internal requests.
	 *
	 * This function filters the search response for categories based on a specific query parameter. It modifies
	 * the response to include additional category details, such as the category's thumbnail. This modification
	 * is applied only when the request is identified as coming from the 'revenue_internal' source.
	 *
	 * @param array $categories The original array of categories from the search response. Each element is an
	 *                          object representing a category.
	 *
	 * @return array The modified array of categories with additional details. Each element is an associative
	 *               array with category information including item ID, item name, and thumbnail URL. Returns
	 *               the original categories array if the request is not from 'revenue_internal'.
	 */
	public function modify_woocommerce_category_search_response($categories) {
		check_ajax_referer('search-categories', 'security');

		if (isset($_GET['request_from']) && 'revenue_internal' === sanitize_text_field(wp_unslash($_GET['request_from']))) {

			$data = array();

			foreach ($categories as $category) {
				$thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
				$image_url    = wp_get_attachment_url($thumbnail_id);

				if (! $image_url) {
					$image_url = wc_placeholder_img_src();
				}

				$data[] = array(
					'item_id'   => $category->term_id,
					'item_name' => rawurldecode(wp_strip_all_tags($category->name)),
					'thumbnail' => $image_url,
					'url'		=> get_term_link($category)
				);
			}

			if (! empty($data)) {
				return $data;
			}
		}

		return $categories;
	}
}
