<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add Badge Sticky Template
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
$style = revenue()->get_style( $generated_styles, 'productTag' );

$popular_tag = isset( $current_campaign['product_tag_text'] ) ? $current_campaign['product_tag_text'] : __( 'Most Popular', 'revenue' );

?>
<div class="revx-builder__popular-tag" style="<?php echo esc_attr( $style ); ?>"><?php echo esc_html( $popular_tag ); ?></div>
