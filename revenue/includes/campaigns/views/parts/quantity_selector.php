<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add quantity selector Template
 *
 * @package Revenue
 */

namespace Revenue;

/**
 * Display the Quantity input
 *
 * This template is used to render the Quantity input
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
if ( ! $product_id && $offered_product ) {
	$product_id = $offered_product->get_id();
}
$quantity_style          = revenue()->get_style( $generated_styles, 'quantitySelector' );
$quantity_input_style    = revenue()->get_style( $generated_styles, 'quantitySelector', 'input' );
$quantity_button_style   = revenue()->get_style( $generated_styles, 'quantitySelector', 'child' );
$classes                 = revenue()->get_style( $generated_styles, 'quantitySelector', 'classes' );
$is_qty_selector_enabled = 'yes' == revenue()->get_campaign_meta( $current_campaign['id'], 'quantity_selector_enabled', true );
if ( 'frequently_bought_together' === trim( $current_campaign['campaign_type'] ) ) {
	$input_name = 'revx-quantity-' . $current_campaign['id'] . '-' . $product_id;
} else {
	$input_name = $source
		? 'revx-quantity-' . $current_campaign['id'] . '-' . $product_id . '-' . $source
		: 'revx-quantity-' . $current_campaign['id'] . '-' . $product_id . '-' . $index;
}
?>

<div class="revx-builder__quantity revx-align-center revx-width-full <?php echo esc_attr( $classes ); ?> <?php echo esc_attr( ! $is_qty_selector_enabled ? 'revx-d-none' : '' ); ?>" style="<?php echo esc_attr( $quantity_style ); ?>">
	<div class="revx-quantity-minus revx-justify-center" style="<?php echo esc_attr( $quantity_button_style ); ?>">
		<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
			<path d="M3.33333 8H12.6667" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round" />
		</svg>
	</div>
	<?php
	if ( 'frequently_bought_together ' == $current_campaign['campaign_type'] ) {
		?>
		<input data-name="revx_quantity" max="<?php echo esc_attr( $max_quantity ); ?>" type="number" min="<?php echo esc_attr( $min_quantity ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" name="<?php echo esc_attr( $input_name ); ?>" style="<?php echo esc_attr( $quantity_input_style ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		<?php
	} else {
		?>
		<input data-name="revx_quantity" max="<?php echo esc_attr( $max_quantity ); ?>" type="number" min="<?php echo esc_attr( $min_quantity ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" name="<?php echo esc_attr( $input_name ); ?>" style="<?php echo esc_attr( $quantity_input_style ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		<?php
	}
	?>
	<div class="revx-quantity-plus revx-justify-center" style="<?php echo esc_attr( $quantity_button_style ); ?>">
		<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 3.33301V12.6663" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round" />
			<path d="M3.33334 8H12.6667" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round" />
		</svg>
	</div>
</div>
