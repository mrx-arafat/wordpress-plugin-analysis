<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Countdown Timer Inpage View
 *
 * @package     Revenue
 * @subpackage  Revenue/includes/campaigns/views/countdown-timer
 * @since       1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
$animation_enable   = $campaign['animation_settings_enable'] ?? 'yes';
$animation_type     = $campaign['animation_type'] ?? 'none';
$animation_duration = $campaign['animation_duration'] ?? 1;
$animation_delay    = $campaign['delay_between_loop'] ?? 1;

$generated_styles = revenue()->campaign_style_generator( 'inpage', $campaign );

$container_style         = revenue()->get_style( $generated_styles, 'container' );
$heading_style           = revenue()->get_style( $generated_styles, 'heading' );
$subheading_style        = revenue()->get_style( $generated_styles, 'subHeading' );
$countdown_wrapper_style = revenue()->get_style( $generated_styles, 'countdownWrapper' );
$countdown_item_style    = revenue()->get_style( $generated_styles, 'countdownItem' );
$countdown_label_style   = revenue()->get_style( $generated_styles, 'countdownItemLabel' );

$shop_heading_style = revenue()->get_style( $generated_styles, 'shopHeading' );
$progress_bar_style = revenue()->get_style( $generated_styles, 'progressBar' );

$cart_heading_style    = revenue()->get_style( $generated_styles, 'cartHeading' );
$cart_subheading_style = revenue()->get_style( $generated_styles, 'cartSubHeading' );

$banner_heading = revenue()->get_campaign_meta( $campaign['id'], 'banner_heading', true ) ?? 'Limited time deals on top gadgets!';

$animation_style = '';
if ( 'yes' === $animation_enable ) {
	$animation_style = sprintf(
		'animation-duration: %dms; animation-iteration-count: infinite; animation-fill-mode: both; animation-timing-function: ease-in-out; animation-delay: %dms; cursor: pointer;',
		esc_attr( $animation_duration * 1000 ),
		esc_attr( $animation_delay * 1000 )
	);
}

?>
<div data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" class="rvex-countdown-timer-wrapper">
<?php
if ( is_cart() && isset( $campaign['placement_settings']['cart_page'] ) && ! empty( $campaign['placement_settings']['cart_page'] ) && 'yes' === $campaign['placement_settings']['cart_page']['status'] ) {
	?>
	<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'cartHeading', $banner_heading, '', 'div' ), revenue()->get_allowed_tag() ); ?>

	<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'cartSubHeading', '00H : 00M : 00S', 'rvex-cart-countdown', 'div' ), revenue()->get_allowed_tag(), ); ?>
	<?php
} elseif ( is_product() && isset( $campaign['placement_settings']['product_page'] ) && ! empty( $campaign['placement_settings']['product_page'] ) && 'yes' === $campaign['placement_settings']['product_page']['status'] ) {
	$campaign_heading    = $campaign['banner_heading'] ?? 'FLASH SALE ALERT!';
	$campaign_subheading = $campaign['banner_subheading'] ?? 'Limited-time deals on our top gadgets!';
	if ( 'after_shop_loop_item_title' === $this->current_position || 'shop_loop_item_title' === $this->current_position ) {
		?>
				<div class="rvex-shop-count-down" style="<?php echo $shop_heading_style; ?>">00H : 00M : 00S</div>
				<!-- check time progress bar enable rvexShopCountdown -->
		<?php
		if ( 'yes' === $campaign['countdown_timer_shop_progress_bar'] ) {
			?>
					<div class="rvex-progress-container" style="<?php echo $progress_bar_style; ?> margin-top: 8px; width: 100%; max-width: 380px; height: 8px; background-color:var(--revx-empty-color); border-radius: 4px; position: relative;">
					<div class="rvex-progress-bar" style="width: 0%; height: 8px; background: var(--revx-filled-color, #6E3FF3); border-radius: 4px; transition: width 0.5s;"></div>
					

					<div class="rvex-progress-bar-icon" style="margin-left: 18px; position: absolute; left: 0%; top: 45%; transform: translate(-50%, -50%); width: 24px; height: 24px; background: var(--revx-filled-color, #6E3FF3); border-radius: 50%; display: flex; align-items: center; justify-content: center;"><div style="position: absolute; top: 56%; left: 45%; transform-origin: left center 0px; background-color: white; width: 10px; height: 2px; border-radius: 36px; transform: translate(0px, -12%) rotate(271deg);"></div><div style="position: absolute; top: 56%; left: 45%; transform-origin: left center 0px; background-color: white; width: 7px; height: 2px; border-radius: 30px; transform: translate(0px, -68%) rotate(19deg);"></div></div>
				</div>
			<?php
		}
		return;
		?>
		
		<?php

	}
	?>
			<div class="rvex-flash-sale-container <?php echo 'yes' === $animation_enable ? 'revx-btn-animation revx-btn-' . esc_attr( $animation_type ) : ''; ?>"  id="rvexFlashSaleContainer" style="<?php echo esc_attr( $container_style ); ?> <?php echo esc_attr( $animation_style ); ?>">
			<div class="rvex-flash-sale-title" style="<?php echo esc_attr( $heading_style ); ?>"><?php echo esc_html( $campaign_heading ); ?></div>
				<div class="rvex-flash-sale-subtitle" style="<?php echo esc_attr( $subheading_style ); ?>"><?php echo esc_html( $campaign_subheading ); ?></div>
						
				<div class="rvex-countdown-container" style="display: flex; <?php echo esc_attr( $countdown_wrapper_style ); ?> justify-content: center;">
					<?php
					$labels = array(
						'days'    => __( 'Days', 'revenue' ),
						'hours'   => __( 'Hours', 'revenue' ),
						'minutes' => __( 'Minutes', 'revenue' ),
						'seconds' => __( 'Seconds', 'revenue' ),
					);

					foreach ( $labels as $key => $label ) :
						$class_name = 'rvex-product-' . $key;
						?>
						<div class="revx-focus revx-builder-controller">
							<div class="revx-countdown-item" style="<?php echo esc_attr( $countdown_item_style ); ?>">
								<span class="rvex-countdown-value <?php echo esc_attr( $class_name ); ?>">00</span>
							</div>
							<div class="revx-focus revx-builder-controller">
								<div style="text-align: center; margin-top: 4px;">
									<span class="rvex-countdown-label" style="<?php echo esc_attr( $countdown_label_style ); ?>">
										<?php echo esc_html( $label ); ?>
									</span>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

			</div>
			<?php
} elseif ( is_shop() && isset( $campaign['placement_settings']['shop_page'] ) && ! empty( $campaign['placement_settings']['shop_page'] ) && 'yes' === $campaign['placement_settings']['shop_page']['status'] ) {
	?>
				<div class="rvex-shop-count-down" style="<?php echo $shop_heading_style; ?>">00H : 00M : 00S</div>
				<!-- check time progress bar enable rvexShopCountdown -->
		<?php
		if ( 'yes' === $campaign['countdown_timer_shop_progress_bar'] ) {
			?>
					<div class="rvex-progress-container" style="<?php echo $progress_bar_style; ?> margin-top: 8px; width: 100%; max-width: 380px; height: 8px; background-color:var(--revx-empty-color); border-radius: 4px; position: relative;">
					<div class="rvex-progress-bar" style="width: 0%; height: 8px; background: var(--revx-filled-color, #6E3FF3); border-radius: 4px; transition: width 0.5s;"></div>
					

					<div class="rvex-progress-bar-icon" style="margin-left: 18px; position: absolute; left: 0%; top: 45%; transform: translate(-50%, -50%); width: 24px; height: 24px; background: var(--revx-filled-color, #6E3FF3); border-radius: 50%; display: flex; align-items: center; justify-content: center;"><div style="position: absolute; top: 56%; left: 45%; transform-origin: left center 0px; background-color: white; width: 10px; height: 2px; border-radius: 36px; transform: translate(0px, -12%) rotate(271deg);"></div><div style="position: absolute; top: 56%; left: 45%; transform-origin: left center 0px; background-color: white; width: 7px; height: 2px; border-radius: 30px; transform: translate(0px, -68%) rotate(19deg);"></div></div>
				</div>
			<?php
		}
		?>
				
	<?php
}
?>
</div>
<input type="hidden" name="<?php echo esc_attr( 'revx-countdown-data-' . $campaign['id'] ); ?>" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $this->count_down_localize_data( $campaign ) ) ) ); ?>" />
<?php
