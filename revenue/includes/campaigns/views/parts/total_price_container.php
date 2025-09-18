<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add total price container Template
 *
 * @package Revenue
 */

namespace Revenue;

/**
 * Display the Total price container
 *
 * This template is used to render the Total price container
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
$in_percentage  = revenue()->calculate_discount_percentage( $regular_price, $sale_price );
$has_sale_price = floatval( $sale_price ) < floatval( $regular_price );
?>

<div class="revx-campaign-item__prices revx-flex" style="<?php echo esc_attr( revenue()->get_style( $generated_styles, 'productPriceContainer' ) ); ?>">
	<?php
	if ( $has_sale_price ) {
		echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'productRegularPrice', wc_price( $regular_price ), 'revx-campaign-item__regular-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'productSalePrice', $regular_price != $sale_price ? wc_price( $sale_price ) : '', 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( ! $has_sale_price ) {
		echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'productRegularPrice', '', 'revx-campaign-item__regular-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'productSalePrice', wc_price( $regular_price ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>
