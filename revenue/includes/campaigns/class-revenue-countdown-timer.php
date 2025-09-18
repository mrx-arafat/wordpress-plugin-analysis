<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Campaign: Countdown Timer
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
class Revenue_Countdown_Timer {
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
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'wsx_store_campaign_data_for_cart' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'rvex_add_hidden_page_type_field' ) );
		// add_filter( 'woocommerce_get_item_data', array( $this, 'wsx_display_cart_item_meta' ), 10, 2 );
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
			'before_add_to_cart_form',
			'after_add_to_cart_form',
			'after_single_product_summary',
			'before_single_product',
			'after_single_product',
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
			$campaigns = revenue()->get_available_campaigns( $product_id, $current_page, 'inpage', $position, false, false, 'countdown_timer' );

			if ( ! empty( $campaigns ) ) {
				foreach ( $campaigns as $key => $campaign ) {
					// $cart_item_data['revx_campaign_id']   = $campaign['id'];
					// $cart_item_data['revx_campaign_type'] = $campaign['campaign_type'];

					$cart_item_data['revx_campaign_countdown_timer_id'] = $campaign['id'];
					// $cart_item_data['revx_campaign_countdown_timer_'] = $campaign['id'];
					revenue()->increment_campaign_add_to_cart_count( $campaign['id'] );
					break; // Stop after setting campaign data for one position.
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
			do_action( 'revenue_campaign_countdown_timer_inpage_before_render_content' );
			$this->render_views( $data );
			do_action( 'revenue_campaign_countdown_timer_inpage_after_render_content' );
		}
	}

	/**
	 * Outputs top views for a list of campaigns.
	 *
	 * This method processes and renders top views based on the provided campaigns.
	 * It adds each campaign to the `top` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_top_views( $campaigns, $data ) {
		foreach ( $campaigns as $campaign ) {

			$this->campaigns['top'][ $data['position'] ][] = $campaign;

			$this->current_position = $data['position'];
			do_action( 'revenue_campaign_countdown_timer_top_before_render_content' );
			$this->render_views( $data );
			do_action( 'revenue_campaign_countdown_timer_top_after_render_content' );
		}
	}

	/**
	 * Outputs bottom views for a list of campaigns.
	 *
	 * This method processes and renders bottom views based on the provided campaigns.
	 * It adds each campaign to the `bottom` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_bottom_views( $campaigns, $data ) {
		foreach ( $campaigns as $campaign ) {

			$this->campaigns['bottom'][ $data['position'] ][] = $campaign;

			$this->current_position = $data['position'];
			do_action( 'revenue_campaign_countdown_timer_bottom_before_render_content' );
			$this->render_views( $data );
			do_action( 'revenue_campaign_countdown_timer_bottom_after_render_content' );
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
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			wp_enqueue_script( 'revenue-campaign-countdown' );
			wp_enqueue_style( 'revenue-campaign-countdown' );

			foreach ( $campaigns as $campaign ) {
				revenue()->update_campaign_impression( $campaign['id'], $post->ID );
				$output = '';

				$file_path = apply_filters( 'revenue_campaign_view_path', REVENUE_PATH . 'includes/campaigns/views/countdown-timer/inpage.php', 'countdown_timer', 'inpage', $campaign );

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
				echo $output;
			}
		}
		if ( ! empty( $this->campaigns['top'][ $this->current_position ] ) ) {
			$campaigns = $this->campaigns['top'][ $this->current_position ];
			wp_enqueue_script( 'revenue-campaign-countdown' );
			wp_enqueue_style( 'revenue-campaign-countdown' );			

			foreach ( $campaigns as $campaign ) {
				$output = '';

				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = apply_filters( 'revenue_campaign_view_path', REVENUE_PATH . 'includes/campaigns/views/countdown-timer/toppage.php', 'countdown_timer', 'toppage', $campaign );

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
				echo $output;
			}
		}
		if ( ! empty( $this->campaigns['bottom'][ $this->current_position ] ) ) {
			$campaigns = $this->campaigns['bottom'][ $this->current_position ];
			wp_enqueue_script( 'revenue-campaign-countdown' );
			wp_enqueue_style( 'revenue-campaign-countdown' );

			foreach ( $campaigns as $campaign ) {
				$output = '';

				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = apply_filters( 'revenue_campaign_view_path', REVENUE_PATH . 'includes/campaigns/views/countdown-timer/bottompage.php', 'countdown_timer', 'bottompage', $campaign );

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
				echo $output;
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
	public function count_down_localize_data( $campaign = array() ) {

		$countdown_timer_type = $campaign['countdown_timer_type'] ?? 'static';
		$evergreen_settings   = $campaign['countdown_timer_evergreen_settings'] ?? array();

		$evergreen_days        = $evergreen_settings['evergreen_days'] ?? 0;
		$evergreen_hours       = $evergreen_settings['evergreen_hours'] ?? 0;
		$evergreen_minutes     = $evergreen_settings['evergreen_minutes'] ?? 0;
		$evergreen_seconds     = $evergreen_settings['evergreen_seconds'] ?? 0;
		$repeat_after_finished = $evergreen_settings['repeatAfterFinished'] ?? 'no';

		$daily_recurring_settings = $campaign['countdown_timer_daily_recurring_settings'] ?? array();
		$set_select_mode          = $daily_recurring_settings['setSelectMode'] ?? 'dailyMode'; // weekdaysMode.
		$daily_time_slots         = $daily_recurring_settings['dailySlotsTimes'] ?? array();
		$weekly_time_slots        = $daily_recurring_settings['weeklyTimeSlot'] ?? array();
		$is_saturday              = $daily_recurring_settings['issaturdaySlot'] ?? 'no';
		$is_sunday                = $daily_recurring_settings['issundaySlot'] ?? 'no';
		$is_monday                = $daily_recurring_settings['ismondaySlot'] ?? 'no';
		$is_tuesday               = $daily_recurring_settings['istuesdaySlot'] ?? 'no';
		$is_wednesday             = $daily_recurring_settings['iswednesdaySlot'] ?? 'no';
		$is_thursday              = $daily_recurring_settings['isthursdaySlot'] ?? 'no';
		$is_friday                = $daily_recurring_settings['isfridaySlot'] ?? 'no';

		$action_type   = $campaign['countdown_timer_entire_site_action_type'] ?? 'makeFullAction';
		$action_link   = $campaign['countdown_timer_entire_site_action_link'] ?? '#';
		$action_enable = $campaign['countdown_timer_entire_site_action_enable'] ?? 'yes';

		$static_settings     = $campaign['countdown_timer_static_settings'] ?? array();
		$current_active_page = is_shop() ? 'shop_page' : ( is_product() ? 'product_page' : ( is_cart() ? 'cart_page' : 'others' ) );

		$static_time_frame_mode    = $static_settings['setTimeFrame'] ?? 'startNow';
		$static_end_date_time      = $static_settings['setEndDateTime'] ?? '2025-03-03';
		$static_end_select_time    = $static_settings['setEndSelectTime'] ?? '00:30';
		$static_timer_end_behavior = $static_settings['timerEndBehavior'] ?? 'hideTimer';
		$static_start_date_time    = $static_settings['setStartDateTime'] ?? '2025-02-27';
		$static_start_select_time  = $static_settings['setStartSelectTime'] ?? '01:00';
		$is_product_page_enable    = $campaign['placement_settings']['product_page']['status'] ?? 'no';
		$is_shop_page_enable       = $campaign['placement_settings']['shop_page']['status'] ?? 'no';
		$is_cart_page_enable       = $campaign['placement_settings']['cart_page']['status'] ?? 'no';

		// Format the dates for JavaScript.
		$end_date_time   = gmdate( 'Y-m-d H:i:s', strtotime( "$static_end_date_time $static_end_select_time" ) );
		$start_date_time = gmdate( 'Y-m-d H:i:s', strtotime( "$static_start_date_time $static_start_select_time" ) );

		$is_close_button_enable = $campaign['countdown_timer_enable_close_button'] ?? 'no';
		$is_all_page_enable     = 'no';
		if ( isset( $campaign['placement_settings']['all_page'] ) && ! empty( $campaign['placement_settings']['all_page'] ) && 'yes' === $campaign['placement_settings']['all_page']['status'] ) {
			$is_all_page_enable = 'yes';
		}


		$data = array(
			'timeFrameMode'       => esc_js( $static_time_frame_mode ),
			'endDateTime'         => esc_js( $end_date_time ),
			'startDateTime'       => esc_js( $start_date_time ),
			'timerEndBehavior'    => esc_js( $static_timer_end_behavior ),
			'countdownTimerType'  => esc_js( $countdown_timer_type ),
			'currentPage'         => esc_js( $current_active_page ),
			'actionType'          => esc_js( $action_type ),
			'actionLink'          => esc_js( $action_link ),
			'actionEnable'        => esc_js( $action_enable ),
			'isProductPageEnable' => esc_js( $is_product_page_enable ),
			'isShopPageEnable'    => esc_js( $is_shop_page_enable ),
			'isCartPageEnable'    => esc_js( $is_cart_page_enable ),
			'isAllPageEnable'     => esc_js( $is_all_page_enable ),
			'evergreenDays'       => esc_js( $evergreen_days ),
			'evergreenHours'      => esc_js( $evergreen_hours ),
			'evergreenMinutes'    => esc_js( $evergreen_minutes ),
			'evergreenSeconds'    => esc_js( $evergreen_seconds ),
			'repeatAfterFinished' => esc_js( $repeat_after_finished ),
			'setSelectMode'       => esc_js( $set_select_mode ),
			'dailyTimeSlots'      => $daily_time_slots,
			'weeklyTimeSlots'     => $weekly_time_slots,
			'isSaturdaySlot'      => esc_js( $is_saturday ),
			'isSundaySlot'        => esc_js( $is_sunday ),
			'isMondaySlot'        => esc_js( $is_monday ),
			'isTuesdaySlot'       => esc_js( $is_tuesday ),
			'isWednesdaySlot'     => esc_js( $is_wednesday ),
			'isThursdaySlot'      => esc_js( $is_thursday ),
			'isFridaySlot'        => esc_js( $is_friday ),
		);

		return $data;
	}
}
