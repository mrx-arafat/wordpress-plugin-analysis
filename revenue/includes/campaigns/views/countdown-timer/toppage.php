<?php
/**
 * Countdown Timer Toppage View
 *
 * @package     Revenue
 * @subpackage  Revenue/includes/campaigns/views/countdown-timer
 * @since       1.0.0
 */
$animation_enable   = $campaign['animation_settings_enable'] ?? 'yes';
$animation_type     = $campaign['animation_type'] ?? 'none';
$animation_duration = $campaign['animation_duration'] ?? 1;
$animation_delay    = $campaign['delay_between_loop'] ?? 1;
$animation_style    = '';
if ( 'yes' === $animation_enable ) {
	$animation_style = sprintf(
		'animation-duration: %dms; animation-iteration-count: infinite; animation-fill-mode: both; animation-timing-function: ease-in-out; animation-delay: %dms; cursor: pointer;',
		esc_attr( $animation_duration * 1000 ),
		esc_attr( $animation_delay * 1000 )
	);
}

$action_type   = $campaign['countdown_timer_entire_site_action_type'] ?? 'makeFullAction';
$action_link   = $campaign['countdown_timer_entire_site_action_link'] ?? '#';
$action_enable = $campaign['countdown_timer_entire_site_action_enable'] ?? 'yes';

$generated_styles = revenue()->campaign_style_generator( 'top', $campaign );

$container_style = revenue()->get_style( $generated_styles, 'container' );

$all_site_countdown_wrapper    = revenue()->get_style( $generated_styles, 'allSiteCountdownWrapper' );
$all_site_countdown_item       = revenue()->get_style( $generated_styles, 'allSiteCountdownItem' );
$all_site_countdown_item_label = revenue()->get_style( $generated_styles, 'allSiteCountdownItemLabel' );
$cancel_button                 = revenue()->get_style( $generated_styles, 'shopAllSiteCancelButton' );

$is_close_button_enable = $campaign['countdown_timer_enable_close_button'] ?? 'no';
$is_all_page_enable     = ( isset( $campaign['placement_settings']['all_page']['status'] ) && 'yes' === $campaign['placement_settings']['all_page']['status'] ) ? 'yes' : 'no';

$banner_heading    = revenue()->get_campaign_meta( $campaign['id'], 'banner_heading', true ) ?? 'FLASH SALE ALERT!';
$banner_subheading = revenue()->get_campaign_meta( $campaign['id'], 'banner_subheading', true ) ?? 'Limited-time deals on our top gadgets!';
$button_text       = revenue()->get_campaign_meta( $campaign['id'], 'cta_button_text', true ) ?? 'Shop Now';
$button_url        = revenue()->get_campaign_meta( $campaign['id'], 'countdown_timer_entire_site_action_link', true ) ?? '#';
?>



<div data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" id="revx-countdown-top" style="display: none;" class="rvex-countdown-timer-wrapper rvex-countdown-wrapper rvex-banner-top-container rvex-banner-countdown">
	<div class="revx-countdown-timer-hellobar-wrapper" data-display="true" style=" <?php echo esc_attr( $container_style ); ?> max-width:100%; width:100%; position: relative;">
<?php
if ( 'yes' === $action_enable && 'makeFullAction' === $action_type ) {
	?>
		<a href="<?php echo esc_url( $button_url ); ?>" class="revx-countdown-button" >
	<?php
}
?>
	<div class="revx-campaign-countdown-timer-hellobar" style="
	display: flex;
	align-items: center;
	justify-content: space-between;
">
		<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'allSiteHeading', $banner_heading, '', 'div' ), revenue()->get_allowed_tag() ); ?>

		<div class="rvex-countdown-container  <?php echo 'yes' === $animation_enable ? 'revx-btn-animation revx-btn-' . esc_attr( $animation_type ) : ''; ?>" style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: center; align-items: center; <?php echo esc_attr( $all_site_countdown_wrapper ); ?> <?php echo esc_attr( $animation_style ); ?>">
		<?php
		$labels = array( 'Days', 'Hours', 'Minutes', 'Seconds' );
		foreach ( $labels as $label ) :
			$slug = strtolower( $label );
			?>
				<div>
				<div class="revx-countdown-item rvex-banner-<?php echo esc_attr( $slug ); ?>" style="display: flex; flex-direction: column; align-items: center; <?php echo esc_attr( $all_site_countdown_item ); ?>">
					<span class="rvex-countdown-value rvex-banner-<?php echo esc_attr( $slug ); ?>" style="padding: 5px 10px; border-radius: 5px; font-size: 20px; font-weight: bold; min-width: 50px; text-align: center;">00</span>
				</div>
					<div class="rvex-countdown-label" style="<?php echo esc_attr( $all_site_countdown_item_label ); ?>  margin-top: 5px; text-align: center;">
					<?php echo esc_html( $label ); ?>
					</div>
			</div>
			<?php endforeach; ?>
		</div>

		<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'allSiteSubHeading', $banner_subheading, 'revx-all-site-subheading ', 'div' ), revenue()->get_allowed_tag() ); ?>

		<?php
		if ( 'yes' === $action_enable && 'addCtaAction' === $action_type ) {
			?>
			<a href="<?php echo esc_url( $button_url ); ?>" class="revx-countdown-button" >
			<?php
					echo wp_kses(
						revenue()->tag_wrapper(
							$campaign,
							$generated_styles,
							'shopAllSiteButton',
							$button_text,
							'revx-cursor-pointer revx-builder-btn',
							'button',
							array(
								'campaign-id'   => $campaign['id'],
								'campaign-type' => $campaign['campaign_type'],
							)
						),
						revenue()->get_allowed_tag()
					);
			?>
		</a>
			<?php
		}
		?>

	</div>

	<?php
	if ( 'yes' === $action_enable && 'makeFullAction' === $action_type ) {
		?>
		</a>
		<?php
	}
	?>
	<?php

	if ( 'yes' === $is_close_button_enable ) {
			echo wp_kses(
				revenue()->get_template_part(
					'campaign_close',
					array(
						'generated_styles' => $generated_styles,
						'current_campaign' => $campaign,
					)
				),
				revenue()->get_allowed_tag()
			);
	}
	?>
	</div>

		<div class="revx-countdown-mobile" style=" <?php echo esc_attr( $container_style ); ?> display: none; max-width:100%; width:100%; position: relative;">
		<?php
		if ( 'yes' === $action_enable && 'makeFullAction' === $action_type ) {
			?>
		<a href="<?php echo esc_url( $button_url ); ?>" class="revx-countdown-button" >
			<?php
		}
		?>
	<div class="revx-campaign-countdown-timer-hellobar" style="
	display: flex;
	align-items: center;
	justify-content: space-between;
">
		<div class="rvex-countdown-banner-mobile" style="display: flex; flex-direction: column; align-items: center; justify-content: center; max-width: 140px;">
		<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'allSiteHeading', $banner_heading, '', 'div' ), revenue()->get_allowed_tag() ); ?>
		<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'allSiteSubHeading', $banner_subheading, 'revx-all-site-subheading ', 'div' ), revenue()->get_allowed_tag() ); ?>
		</div>
		<div class="rvex-countdown-container  <?php echo 'yes' === $animation_enable ? 'revx-btn-animation revx-btn-' . esc_attr( $animation_type ) : ''; ?>" style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: center; align-items: center; <?php echo esc_attr( $all_site_countdown_wrapper ); ?> <?php echo esc_attr( $animation_style ); ?>">
		<?php
		$labels = array( 'Days', 'Hours', 'Minutes', 'Seconds' );
		$labels_mobile = array( 'D', 'H', 'M', 'S' );
		foreach ( $labels as $index => $label ) :
			$slug = strtolower( $label );
			?>
				<div>
				<div class="revx-countdown-item rvex-banner-<?php echo esc_attr( $slug ); ?>" style="display: flex; flex-direction: column; align-items: center; padding: 4px 10px !important;  <?php echo esc_attr( $all_site_countdown_item ); ?>">
					<span class="rvex-countdown-value rvex-banner-<?php echo esc_attr( $slug ); ?>" style="padding: 5px 10px; border-radius: 5px; font-size: 20px; font-weight: bold; min-width: 50px; text-align: center;">00</span>
				</div>
					<div class="rvex-countdown-label" style="<?php echo esc_attr( $all_site_countdown_item_label ); ?>  margin-top: 5px; text-align: center;">
					<?php echo esc_html( $labels_mobile[$index] ); ?>
					</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
		if ( 'yes' === $action_enable && 'addCtaAction' === $action_type ) {
			?>
			<a href="<?php echo esc_url( $button_url ); ?>" class="revx-countdown-button" style="font-size: 14px !important; padding: 0px 0px !important;" >
			<?php
					echo wp_kses(
						revenue()->tag_wrapper(
							$campaign,
							$generated_styles,
							'shopAllSiteButton',
							$button_text,
							'revx-cursor-pointer revx-builder-btn revx-mobile-button',
							'button',
							array(
								'campaign-id'   => $campaign['id'],
								'campaign-type' => $campaign['campaign_type'],
							)
						),
						revenue()->get_allowed_tag()
					);
			?>
		</a>
			<?php
		}
		?>
	</div>

		<?php
		if ( 'yes' === $action_enable && 'makeFullAction' === $action_type ) {
			?>
		</a>
			<?php
		}
		?>
		<?php
		if ( 'yes' === $is_close_button_enable ) {
			echo wp_kses(
				revenue()->get_template_part(
					'campaign_close',
					array(
						'generated_styles' => $generated_styles,
						'current_campaign' => $campaign,
						'class'            => 'revx-close-button',
					)
				),
				revenue()->get_allowed_tag()
			);
		}
		?>
	</div>
	<input type="hidden" name="<?php echo esc_attr( 'revx-countdown-data-' . $campaign['id'] ); ?>" value="<?php echo esc_attr( htmlspecialchars( wp_json_encode( $this->count_down_localize_data( $campaign ) ) ) ); ?>" />
</div>
