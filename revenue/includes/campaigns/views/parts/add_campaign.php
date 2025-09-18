<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add Campaign Icon Template
 *
 * @package Revenue
 */

namespace Revenue;

/**
 * Display the Add Campaign
 *
 * This template is used to render the Add Campaign
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
$style = revenue()->get_style( $generated_styles, 'addBundleIcon' );
?>

<div class="revx-product-bundle revx-justify-center" style="<?php echo esc_attr( $style ); ?>">
	<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor">
		<path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 5v14M5 12h14"></path>
	</svg>
</div>
