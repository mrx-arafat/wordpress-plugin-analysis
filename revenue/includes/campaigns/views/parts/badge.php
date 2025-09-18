<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add Badge Template
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
?>
<?php
	echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'productTag', '', 'revx-builder__popular-tag', 'div' ), revenue()->get_allowed_tag() );
