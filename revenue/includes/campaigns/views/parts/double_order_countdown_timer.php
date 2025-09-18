<?php
namespace Revenue;

/**
 * Display the price container
 *
 * This template is used to render the price container
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables used in this file.
 *
 * @var array $generated_style Array of styles data.
 * @var WC_Product  $offered_product product object
 */
if(!$generated_styles || !$current_campaign) {
    return;
}
$container_style = revenue()->get_style($generated_styles,'CountdownTimerContainer');
$countdown_timer = revenue()->get_style($generated_styles,'CountdownTimer');
$countdown_timer_prefix = $current_campaign['countdown_timer_prefix'] ?? __('Hurry! Offer Ends in ','revenue');
$countdown = isset($current_campaign['double_order_countdown_duration'])?  $current_campaign['double_order_countdown_duration']: 100;
if(!isset($current_campaign['countdown_timer_enabled']) || 'yes' != $current_campaign['countdown_timer_enabled']) {
	return;
}
?>
<div  id="revx-double-order-countdown-timer-<?php echo esc_attr($current_campaign['id']); ?>" data-campaign-id="<?php echo esc_attr($current_campaign['id']); ?>" data-countdown-duration="<?php echo esc_attr( $countdown ); ?>"  class="revx-double-order-countdown-timer-container revx-align-center revx-d-none" style="<?php echo esc_attr($container_style); ?>">
    <span class="revx-countdown-timer-prefix" ><?php echo esc_html($countdown_timer_prefix); ?></span>
    <div class="revx-countdown-timer" style="<?php echo esc_attr($countdown_timer); ?>">
        <span class="revx-minutes">--</span> <span class="revx-minutes-label">M :</span>
        <span class="revx-seconds">--</span><span>S</span>
    </div>
</div>
