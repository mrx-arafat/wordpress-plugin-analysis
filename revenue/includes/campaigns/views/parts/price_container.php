<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add Price Container Template
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
if ( ! $generated_styles || ( ! $regular_price && ! $force_show ) || ! $current_campaign ) {
	return;
}

$quantity      = (int) $quantity > 0 ? (int) $quantity : 1;
$offered_price = $quantity * floatval( $offered_price );
$regular_price = $quantity * floatval( $regular_price );
$in_percentage = revenue()->calculate_discount_percentage( $regular_price, $offered_price );

$is_display_none  = false;
$offer_price_hide = false;
$has_sale_price   = floatval( $offered_price ) < floatval( $regular_price );
$is_display_none  = ! $regular_price;

$force_show = 'volume_discount' == $current_campaign['campaign_type'] && floatval( $regular_price ) != floatval( $offered_price );

$has_sale_price = ( $force_show || $has_sale_price ) && $offered_price;
?>

<div class="revx-campaign-item__prices revx-flex" style="<?php echo esc_attr( revenue()->get_style( $generated_styles, 'priceContainer' ) ); ?>">
	<?php
	if ( ! $has_sale_price ) {
		if ( 'volume_discount' === $campaign_type ) {
			echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', '', 'revx-campaign-item__regular-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', '', 'revx-campaign-item__regular-price revx-d-none', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( 'volume_discount' === $campaign_type || 'mix_match' === $campaign_type || intval( $quantity ) === 1 ) {
			echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', $is_display_none ? '' : wc_price( $regular_price ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', $quantity . ' x ' . wc_price( $regular_price / $quantity ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	if ( $has_sale_price ) {
		if ( $offered_price == $regular_price ) {
			if ( 'volume_discount' === $campaign_type || 'mix_match' === $campaign_type || intval( $quantity ) === 1 ) {
				echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', $is_display_none ? '' : wc_price( $regular_price ), 'revx-campaign-item__regular-price revx-d-none', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', $is_display_none ? '' : $quantity . ' x ' . wc_price( $regular_price / $quantity ), 'revx-campaign-item__regular-price revx-d-none', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} elseif ( 'volume_discount' === $campaign_type || 'mix_match' === $campaign_type || intval( $quantity ) === 1 ) {
				echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', $is_display_none ? '' : wc_price( $regular_price ), 'revx-campaign-item__regular-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', $is_display_none ? '' : $quantity . ' x ' . wc_price( $regular_price / $quantity ), 'revx-campaign-item__regular-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}

		if ( 'volume_discount' === $campaign_type || 'mix_match' === $campaign_type || intval( $quantity ) === 1 ) {
			echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', $is_display_none || $offer_price_hide ? '' : wc_price( $offered_price ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', $is_display_none || $offer_price_hide ? '' : $quantity . ' x ' . wc_price( $offered_price / $quantity ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	?>
</div>
