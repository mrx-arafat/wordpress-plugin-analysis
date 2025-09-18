<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Bundle Discount Popup Template
 *
 * This file handles the display of bundle discount offers in a popup container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

namespace Revenue;

/**
 * The Template for displaying revenue view
 *
 * @package Revenue
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

//phpcs:disable WordPress.PHP.StrictComparisons.LooseComparison


$offered_product = false;
$regular_price   = false;
$offered_price   = false;
global $product;

$output_content = '';


$view_mode = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
if ( ! $view_mode ) {
	return;
}
$buider_data = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )['popup'][ $view_mode ];


$offers                   = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$on_cart_action           = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );
$on_offered_product_click = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_click_action', true );
$generated_styles         = revenue()->campaign_style_generator( 'popup', $campaign, $placement );


$items_content = '';

$total_offer_products = 0;

$total_sale_price    = 0;
$total_regular_price = 0;
$save_data           = '';
$offer_data          = array();

$has_outof_stock = false;

$trigger_items = array();

$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
if ( empty( $trigger_product_relation ) ) {
	$trigger_product_relation = 'or';
}

$offer_products = revenue()->getOfferProductsData( $offers );


if ( 'yes' == $campaign['bundle_with_trigger_products_enabled'] ) {
	$is_category = ( 'category' == $campaign['campaign_trigger_type'] ) || ( 'all_products' == $campaign['campaign_trigger_type'] );
	if ( ! $product->is_type( 'variable' ) ) {

		$has_trigger_on_offer = false;
		foreach ( $offer_products as $offer_item ) {
			if ( $offer_item['item_id'] == $product->get_id() ) {
				$has_trigger_on_offer = true;
			}
		}

		if ( ! $has_outof_stock ) {
			$has_outof_stock = ! $product->is_in_stock();
		}

		if ( ! $has_trigger_on_offer ) {
			$trigger_items = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id(), $is_category );
		}
		$trigger_items = array_merge( $trigger_items, $offer_products );

	} else {
		$trigger_items = $offer_products;
	}
} else {
	$trigger_items = $offer_products;
}

$total_offer_products = 0;
$total_sale_price     = 0;
$total_regular_price  = 0;
$items_content        = '';

ob_start();
if ( is_array( $offers ) ) {
	$offer_length = count( $offers );

	foreach ( $trigger_items as $offer_index => $offer ) {
		$offer_qty   = $offer['quantity'];
		$offer_value = $offer['value'];
		$offer_type  = $offer['type'];

		$save_data = '';
		if ( 'percentage' === $offer_type ) {
			/* translators: %s: Discount percentage value */
			$save_data = sprintf( __( '%s%% OFF', 'revenue' ), $offer_value );
		}
		$product_count   = count( $trigger_items );
		$is_last_product = ( $product_count - 1 ) == $offer_index;

		$offer_product_id = $offer['item_id'];

		$offered_product = wc_get_product( $offer_product_id );
		if ( ! $offered_product ) {
			continue;
		}
		if ( ! $has_outof_stock ) {
			$has_outof_stock = ! $offered_product->is_in_stock();
		}
		++$total_offer_products;

		$image         = wp_get_attachment_image_src( get_post_thumbnail_id( $offer_product_id ), 'single-post-thumbnail' );
		$product_title = $offered_product->get_title();
		$regular_price = $offered_product->get_regular_price();

		$offered_price           = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
		$in_percentage           = revenue()->calculate_discount_percentage( $regular_price, $offered_price );
		$is_qty_selector_enabled = revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true );
		$product_style           = revenue()->get_style( $generated_styles, 'product' );

		if ( $offer_product_id == $product->get_id() ) {
			$offer_qty     = 1;
			$offered_price = $product->get_regular_price();
		}

		$total_sale_price    += ( intval( $offer_qty ) * floatval( $offered_price ) );
		$total_regular_price += ( intval( $offer_qty ) * floatval( $regular_price ) );


		$builder_separator = revenue()->get_style( $generated_styles, 'bundleSeparator' );

		if ( ! isset( $offer_data[ $offer_product_id ]['regular_price'] ) ) {
			$offer_data[ $offer_product_id ]['regular_price'] = $regular_price;
		}
		if ( ! isset( $offer_data[ $offer_product_id ]['offer'] ) ) {
			$offer_data[ $offer_product_id ]['offer'] = array();
		}
		$offer_data[ $offer_product_id ]['offer'][] = array(
			'qty'   => $offer_qty,
			'type'  => $offer_type,
			'value' => $offer_value,
		);
		$grid_class                                 = 'revx-campaign-view__items ' . ( 'list' === $view_mode ? 'revx-align-center' : 'revx-slider-container' );
		$slider_selector                            = 'revx-align-center   revx-slider';

		if ( 'list' == $view_mode ) {
			?>
			<div id="revenue-campaign-item-<?php echo esc_attr( $offer_product_id ) . '-' . esc_attr( $campaign['id'] ); ?>" class="revx-campaign-item" data-product-id="<?php echo esc_attr( $offer_product_id ); ?>" data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" style="<?php echo esc_attr( $product_style ); ?>">
				<?php
				echo wp_kses(
					revenue()->get_template_part(
						'image',
						array(
							'offered_product'  => $offered_product,
							'generated_styles' => $generated_styles,
							'current_campaign' => $campaign,
						)
					),
					revenue()->get_allowed_tag()
				);
				?>
				<div class="revx-campaign-text-content">
					<?php

					echo wp_kses(
						revenue()->get_template_part(
							'product_title',
							array(
								'offered_product'  => $offered_product,
								'generated_styles' => $generated_styles,
								'current_campaign' => $campaign,
							)
						),
						revenue()->get_allowed_tag()
					);

					echo wp_kses(
						revenue()->get_template_part(
							'price_container',
							array(
								'quantity'         => $offer_qty,
								'offered_product'  => $offered_product,
								'generated_styles' => $generated_styles,
								'regular_price'    => $regular_price,
								'offered_price'    => $offered_price,
								'current_campaign' => $campaign,
							)
						),
						revenue()->get_allowed_tag()
					);
					?>
				</div>
			</div>

			<?php if ( ! ( $is_last_product ) ) { ?>
				<div class="revx-builder__middle_element revx-align-center " style="<?php echo esc_attr( $builder_separator ); ?>">
					<?php

					echo wp_kses(
						revenue()->get_template_part(
							'add_campaign',
							array(
								'view_mode'        => 'list',
								'campaign_type'    => 'bundle_discount',
								'generated_styles' => $generated_styles,
							)
						),
						revenue()->get_allowed_tag()
					);
					?>
				</div>
				<?php
			}
		} else {


			?>
			<div class="revx-campaign-item revx-campaign-text-content">
				<div id="revenue-campaign-item-<?php echo esc_attr( $offer_product_id ) . '-' . esc_attr( $campaign['id'] ); ?>"
					data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>">
					<?php
					echo wp_kses(
						revenue()->get_template_part(
							'image',
							array(
								'offered_product'  => $offered_product,
								'generated_styles' => $generated_styles,
								'current_campaign' => $campaign,
							)
						),
						revenue()->get_allowed_tag()
					);
					if ( 'redirect_to_product_page' === $on_offered_product_click ) {
						echo wp_kses(
							revenue()->get_template_part(
								'product_title',
								array(
									'offered_product'  => $offered_product,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
					} else {
						echo wp_kses(
							revenue()->get_template_part(
								'product_title',
								array(
									'offered_product'  => $offered_product,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
					}
					echo wp_kses(
						revenue()->get_template_part(
							'price_container',
							array(
								'quantity'         => $offer_qty,
								'offered_product'  => $offered_product,
								'generated_styles' => $generated_styles,
								'regular_price'    => $regular_price,
								'offered_price'    => $offered_price,
								'current_campaign' => $campaign,
							)
						),
						revenue()->get_allowed_tag()
					);
					?>

				</div>

			</div>

			<?php if ( ! ( $is_last_product ) ) { ?>
				<div class="revx-builder__middle_element revx-align-center " style="<?php echo esc_attr( $builder_separator ); ?>">
					<?php
						echo wp_kses(
							revenue()->get_template_part(
								'add_campaign',
								array(
									'view_mode'        => 'list',
									'campaign_type'    => 'bundle_discount',
									'generated_styles' => $generated_styles,
								)
							),
							revenue()->get_allowed_tag()
						);
					?>
				</div>

				<?php
			}
		}
	}
}
$items_content .= ob_get_clean();

$container_class = 'revx-bundle-discount ' . ( $total_offer_products < 3 ? 'revx-cmp-limited' : '' ) . '  ' . ( 'list' == $view_mode ? 'revx-campaign-list revx-bundle-discount-list' : 'revx-bundle-discount-grid revx-campaign-grid' );

if ( $has_outof_stock ) {
	return;
}
if ( ! $items_content ) {
	return;
}
$container_style   = revenue()->get_style( $generated_styles, 'container' );
$wrapper_style     = revenue()->get_style( $generated_styles, 'wrapper' );
$heading_text      = isset( $campaign['banner_heading'] ) ? $campaign['banner_heading'] : '';
$total_price_style = revenue()->get_style( $generated_styles, 'totalPrice' );

$product_wrapper_style = revenue()->get_style( $generated_styles, 'productWrapper' );
$add_to_cart_qty_style = revenue()->get_style( $generated_styles, 'addToCartQuantity' );

/* translators: %s: Discount percentage value */
$save_data = sprintf( __( '%s%% OFF', 'revenue' ), revenue()->calculate_percentage_difference( $total_regular_price, $total_sale_price ) );


ob_start();
if ( 'list' == $view_mode ) {
	?>
	<div class="revx-campaign-container__wrapper" data-bundle_products="<?php echo esc_html( htmlspecialchars( wp_json_encode( $trigger_items ) ) ); ?>" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<?php
			echo wp_kses( $items_content, revenue()->get_allowed_tag() );
		?>
	</div>

	<div class="revx-total-price revx-campaign-text-content" style="<?php echo esc_attr( $total_price_style ); ?>">
		<div class="revx-align-center" style="gap: inherit;">
			<?php
			echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'bundleLabel', '', 'revx-bundle-offer-label' ), revenue()->get_allowed_tag() );
			echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'totalPriceText', __( 'Total', 'revenue' ), 'revx-total-price-title', 'h4' ), revenue()->get_allowed_tag() );
			?>
		</div>
		<div class="revx-total-price__offer-price revx-align-center">
			<?php
			echo wp_kses(
				revenue()->get_template_part(
					'total_price_container',
					array(
						'regular_price'    => $total_regular_price,
						'sale_price'       => $total_sale_price,
						'generated_styles' => $generated_styles,
						'current_campaign' => $campaign,
					)
				),
				revenue()->get_allowed_tag()
			);
			echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'discountAmount', $save_data, 'revx-builder-savings-tag' ), revenue()->get_allowed_tag() );
			?>
		</div>
	</div>
	<div class="revx-justify-space revx-campaign-text-content" style="<?php echo esc_attr( $add_to_cart_qty_style ); ?>">
		<?php
		echo wp_kses(
			revenue()->get_template_part(
				'quantity_selector',
				array(
					'quantity'         => $offer_qty,
					'min_quantity'     => $offer_qty,
					'value'            => $offer_qty,
					'generated_styles' => $generated_styles,
					'current_campaign' => $campaign,
					'offered_product'  => $offered_product,
				)
			),
			revenue()->get_allowed_tag()
		);
		echo wp_kses(
			revenue()->get_template_part(
				'add_to_cart',
				array(
					'quantity'         => $offer_qty,
					'generated_styles' => $generated_styles,
					'current_campaign' => $campaign,
					'offered_product'  => $offered_product,

				)
			),
			revenue()->get_allowed_tag()
		);
		?>
	</div>
	<?php

} else {
	?>
	<div class="revx-campaign-container__wrapper-main" style="position: relative;">
		<div class="revx-campaign-container__wrapper" style="<?php echo esc_attr( $wrapper_style ); ?>" data-bundle_products="<?php echo esc_html( htmlspecialchars( wp_json_encode( $trigger_items ) ) ); ?>">
			<div class="revx-campaign-container__wrapper__product">
				<div class="<?php echo esc_attr( $slider_selector ); ?>">
					<?php
						echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'left' ), revenue()->get_allowed_tag() );
					?>
					<div class="<?php echo esc_attr( $grid_class ); ?>" style="<?php echo esc_attr( $product_wrapper_style ); ?>">
						<?php
							echo wp_kses( $items_content, revenue()->get_allowed_tag() );
						?>
					</div>
					<?php
					echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'right' ), revenue()->get_allowed_tag() );
					?>
				</div>
			</div>
		</div>

		<div class="revx-total-price revx-campaign-text-content" style="<?php echo esc_attr( $total_price_style ); ?>">
			<?php
			echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'bundleLabel', '', 'revx-bundle-offer-label' ), revenue()->get_allowed_tag() );
			echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'totalPriceText', __( 'Total', 'revenue' ), 'revx-total-price-title', 'h4' ), revenue()->get_allowed_tag() );
			?>
			<div class="revx-total-price__offer-price revx-align-center">
				<?php
				echo wp_kses(
					revenue()->get_template_part(
						'total_price_container',
						array(
							'regular_price'    => $total_regular_price,
							'sale_price'       => $total_sale_price,
							'generated_styles' => $generated_styles,
							'current_campaign' => $campaign,
						)
					),
					revenue()->get_allowed_tag()
				);
				echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'discountAmount', $save_data, 'revx-builder-savings-tag' ), revenue()->get_allowed_tag() );
				?>
			</div>
		</div>
		<div class="revx-justify-space revx-align-center revx-campaign-text-content" style="<?php echo esc_attr( $add_to_cart_qty_style ); ?>">
		<?php
		echo wp_kses(
			revenue()->get_template_part(
				'quantity_selector',
				array(
					'quantity'         => $offer_qty,
					'min_quantity'     => $offer_qty,
					'value'            => $offer_qty,
					'generated_styles' => $generated_styles,
					'current_campaign' => $campaign,
					'offered_product'  => $offered_product,
				)
			),
			revenue()->get_allowed_tag()
		);
		echo wp_kses(
			revenue()->get_template_part(
				'add_to_cart',
				array(
					'quantity'         => $offer_qty,
					'generated_styles' => $generated_styles,
					'current_campaign' => $campaign,
					'offered_product'  => $offered_product,

				)
			),
			revenue()->get_allowed_tag()
		);
		?>
	</div>
			</div>


	<?php

}

$output_content .= ob_get_clean();
ob_start();
?>
<input type="hidden" name="<?php echo esc_attr( 'revx-offer-data-' . $campaign['id'] ); ?>" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $offer_data ) ) ); ?>" />
<input type="hidden" name="<?php echo esc_attr( 'revx-trigger-product-id-' . $campaign['id'] ); ?>" value="<?php echo esc_html( $product->get_id() ); ?>" />

<?php
$output_content .= ob_get_clean();
revenue()->popup_container( $campaign, $generated_styles, $output_content, $container_class, false, $placement );
