<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add countdown timer Template
 *
 * @package Revenue
 */

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
if ( ! $generated_styles || ! $current_campaign ) {
	return;
}
$container_style        = revenue()->get_style( $generated_styles, 'CountdownTimerContainer' );
$countdown_timer        = revenue()->get_style( $generated_styles, 'CountdownTimer' );
$countdown_timer_prefix = $current_campaign['countdown_timer_prefix'] ?? __( 'Hurry! Offer Ends in ', 'revenue' );
if ( ! isset( $current_campaign['countdown_timer_enabled'] ) || 'yes' != $current_campaign['countdown_timer_enabled'] ) {
	return;
}
?>
<div  id="revx-countdown-timer-<?php echo esc_attr( $current_campaign['id'] ); ?>"  class="revx-countdown-timer-container revx-align-center revx-d-none" style="<?php echo esc_attr( $container_style ); ?>">
	<span class="revx-countdown-timer-prefix" ><?php echo esc_html( $countdown_timer_prefix ); ?></span>
	<div class="revx-countdown-timer" style="<?php echo esc_attr( $countdown_timer ); ?>">
		<span class="revx-days">--</span> <span>:</span>
		<span class="revx-hours">--</span> <span>:</span>
		<span class="revx-minutes">--</span> <span>:</span>
		<span class="revx-seconds">--</span>
	</div>
</div>
