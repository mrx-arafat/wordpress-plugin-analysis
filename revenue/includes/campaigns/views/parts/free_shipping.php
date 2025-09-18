<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add free shipping Template
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

$container_tyle = revenue()->get_style( $generated_styles, 'FreeShippingContainer' );
$icon_style     = revenue()->get_style( $generated_styles, 'FreeShippingIcon' );
$label_style    = revenue()->get_style( $generated_styles, 'FreeShippingLabel' );
$free_shipping_label = isset($current_campaign['free_shipping_label'])?$current_campaign['free_shipping_label']:__( 'Free Shipping', 'revenue' );

if ( ! isset( $current_campaign['free_shipping_enabled'] ) || 'yes' != $current_campaign['free_shipping_enabled'] ) {
	return;
}
?>
<div class="revx-free-shipping revx-align-center" style="<?php echo esc_attr( $container_tyle ); ?>">
	<div class="revx-free-shipping-icon" style="<?php echo esc_attr( $icon_style ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 20 20">
			<path stroke="var(--revx-icon-color)" strokeLinecap="round" strokeWidth="1.5" d="M10 17.5a7.5 7.5 0 1 0-5.303-2.197"></path>
			<path stroke="var(--revx-icon-color)" strokeLinecap="round" strokeWidth="1.5" d="m13.334 8.333-2.765 3.318c-.655.786-.983 1.18-1.424 1.2s-.802-.343-1.526-1.067l-.952-.951"></path>
		</svg>
	</div>
	<span class=" revx-total-price-text"  style="<?php echo esc_attr( $label_style ); ?>"><?php echo esc_html( $free_shipping_label ); ?></span>
</div>
<?php
