<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add save Template
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

$force_show = 'volume_discount'==$current_campaign['campaign_type'] && floatval( $regular_price ) != floatval( $offered_price );

if ( floatval( $regular_price ) >= floatval( $offered_price ) || $force_show ) {
	echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'discountAmount', $message, 'revx-builder-savings-tag' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	echo wp_kses(revenue()->tag_wrapper($current_campaign,$generated_styles,'discountAmount', $message, "revx-builder-savings-tag revx-d-none"),revenue()->get_allowed_tag()); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
