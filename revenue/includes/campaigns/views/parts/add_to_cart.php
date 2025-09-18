<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Add to cart Template
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

global $product;
global $post;

if(!$generated_styles || !$current_campaign) {
	return;
}

$class_name = '';
if(!$product_id) {
	$product_id = $offered_product? $offered_product->get_id():$post->ID;
}
switch ( $current_campaign['campaign_type'] ) {
	case 'bundle_discount':
		$class_name = 'revenue-campaign-add-bundle-to-cart';
		// code...
		break;
	case 'frequently_bought_together':
		$product_id = $product? $product->get_id():$post->ID;
		$class_name = 'revx-campaign-add-to-cart-btn';

		break;

	default:
		$class_name = 'revx-campaign-add-to-cart-btn';
		break;
}


$is_animated_atc_enabled   = isset( $current_campaign['animated_add_to_cart_enabled'] ) && 'yes' == $current_campaign['animated_add_to_cart_enabled'];
$animated_atc_trigger_type = isset( $current_campaign['add_to_cart_animation_trigger_type'] ) ? sanitize_text_field( $current_campaign['add_to_cart_animation_trigger_type'] ) : '';
$animated_type             = isset( $current_campaign['add_to_cart_animation_type'] ) ? sanitize_text_field( $current_campaign['add_to_cart_animation_type'] ) : '';
$animation_delay           = isset( $current_campaign['add_to_cart_animation_start_delay'] ) ? sanitize_text_field( $current_campaign['add_to_cart_animation_start_delay'] ) : '0.8s';
$animation_base_class      = $is_animated_atc_enabled ? 'revx-btn-animation ' : '';
$animation_class           = '';

switch ( $animated_type ) {
	case 'wobble':
		$animation_class = 'revx-btn-wobble';
		break;
	case 'shake':
		$animation_class = 'revx-btn-shake';
		break;
	case 'zoom':
		$animation_class = 'revx-btn-zoomIn';
		break;
	case 'pulse':
		$animation_class = 'revx-btn-pulse';
		break;

	default:
		// code...
		break;
}

$animation_base_class = 'loop' === $animated_atc_trigger_type ? "$animation_base_class $animation_class" : $animation_base_class;

if ( $is_animated_atc_enabled ) {
	wp_enqueue_script( 'revenue-animated-add-to-cart' );
	wp_enqueue_style( 'revenue-animated-add-to-cart' );
}

$which_page = '';
if ( is_product() ) {
	$which_page = 'product_page';
} elseif ( is_cart() ) {
	$which_page = 'cart_page';
} elseif ( is_shop() ) {
	$which_page = 'shop_page';
} elseif ( is_checkout() ) {
	$which_page = 'checkout_page';
}

$class_name = apply_filters( 'revenue_campaign_frontend_add_to_cart_button_class', $class_name, $current_campaign );


if ( isset( $current_campaign['skip_add_to_cart'] ) && 'yes' === $current_campaign['skip_add_to_cart'] ) {
	echo wp_kses(
		revenue()->tag_wrapper(
			$current_campaign,
			$generated_styles,
			'addToCartButton',
			__( 'Checkout', 'revenue' ),
			"$class_name $class $animation_base_class revx-builder-atc-btn revx-builder-atc-skip revx-cursor-pointer",
			'button',
			array_merge(
				$data,
				array(
					'product-id'             => $product_id,
					'campaign-id'            => $current_campaign['id'],
					'campaign-type'          => $current_campaign['campaign_type'],
					'animation-class'        => $animation_class,
					'animation-delay'        => $animation_delay,
					'animation-trigger-type' => $animated_atc_trigger_type,
				)
			)
		),
		revenue()->get_allowed_tag()
	);
} else {
	echo wp_kses(
		revenue()->tag_wrapper(
			$current_campaign,
			$generated_styles,
			'addToCartButton',
			__( 'Add to Cart', 'revenue' ),
			"$class_name $class $animation_base_class revx-builder-atc-btn revx-cursor-pointer",
			'button',
			array_merge(
				$data,
				array(
					'product-id'             => $product_id,
					'campaign-id'            => $current_campaign['id'],
					'campaign-type'          => $current_campaign['campaign_type'],
					'animation-class'        => $animation_class,
					'animation-delay'        => $animation_delay,
					'animation-trigger-type' => $animated_atc_trigger_type,
					'campaign_source_page'   => $which_page,
				)
			)
		),
		revenue()->get_allowed_tag()
	);
}
