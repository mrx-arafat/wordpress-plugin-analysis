<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add Product separator Template
 *
 * @package Revenue
 */

namespace Revenue;

/**
 * Display the Product Separator
 *
 * This template is used to render the Product Separator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables used in this file.
 *
 * @var array $generated_style Array of styles data.
 * @var WC_Product  $offered_product product object
 */
if ( ! $generated_styles ) {
	return;
}
$product_separator_style = revenue()->get_style( $generated_styles, 'productSeparator' );
?>

<div class="revx-builder-element__content_separator" style="<?php echo esc_attr( $product_separator_style ); ?>"></div>
