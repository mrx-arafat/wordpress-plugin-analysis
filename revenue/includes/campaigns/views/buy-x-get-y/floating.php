<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Floating Discount Floating Template
 *
 * This file handles the display of floating discount offers in a Floating container.
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

$view_mode = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
if ( ! $view_mode ) {
	return;
}
$buider_data = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )['floating'][ $view_mode ];

$offer_product_output   = '';
$trigger_product_output = '';

$offers                   = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$on_cart_action           = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );
$on_offered_product_click = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_click_action', true );


$heading_text = isset( $campaign['banner_heading'] ) ? $campaign['banner_heading'] : '';
$suheading    = isset( $campaign['banner_subheading'] ) ? $campaign['banner_subheading'] : '';

$generated_styles = revenue()->campaign_style_generator( 'floating', $campaign, $placement );

$is_qty_selector_enabled = revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true );
$offer_length            = count( $offers );
$offer_data              = array();
$trigger_data              = array();


$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';

if ( empty( $trigger_product_relation ) ) {
	$trigger_product_relation = 'or';
}

$offer_products = revenue()->getOfferProductsData( $offers );

$is_category = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );

$trigger_items       = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id(), $is_category );
$total_sale_price    = 0;
$total_regular_price = 0;
$is_list_view        = 'list' === $view_mode;
if ( $is_list_view ) {

	$item_class = 'revx-campaign-item__content revx-align-center revx-campaign-item';
} else {

	$item_class = 'revx-campaign-item__content revx-campaign-item';
}
$output_content = '';
$product_style  = revenue()->get_style( $generated_styles, 'productGap' );

$total_trigger_product_count = 0;
$total_offer_product_count   = 0;

if ( is_array( $trigger_items ) ) {
	foreach ( $trigger_items as $idx => $trigger ) {
		$trigger_product_id            = $trigger['item_id'];
		$trigger_product_name          = $trigger['item_name'];
		$trigger_product_thumbnail     = $trigger['thumbnail'];
		$trigger_product_regular_price = $trigger['regular_price'];
		$trigger_qty                   = isset( $trigger['quantity'] ) ? $trigger['quantity'] : 1;


		$offered_product = wc_get_product( $trigger_product_id );
		if ( ! $offered_product ) {
			continue;
		}
		$is_last_product = ( count( $trigger_items ) - 1 ) == $idx;


		$total_trigger_product_count++;

		$product_title                 = $offered_product->get_title();
		$regular_price                 = $offered_product->get_price( );
		$trigger_product_regular_price = $regular_price;

		$offered_price = $regular_price;


		$total_regular_price += floatval( $trigger_product_regular_price ) * $trigger_qty;
		$total_sale_price    += floatval( $trigger_product_regular_price ) * $trigger_qty;

		if ( ! isset( $offer_data[ $trigger_product_id ]['regular_price'] ) ) {
			$offer_data[ $trigger_product_id ]['regular_price'] = $regular_price;
		}
		if ( ! isset( $offer_data[ $trigger_product_id ]['offer'] ) ) {
			$offer_data[ $trigger_product_id ]['offer'] = array();
		}
		$offer_data[ $trigger_product_id ]['offer'][] = array(
			'qty'   => $trigger_qty,
			'type'  => '',
			'value' => '',
		);

		ob_start();

		?>
		<div class="<?php echo esc_attr( $item_class ); ?>" style="
				<?php
				echo esc_attr( $is_list_view ? $product_style : '' );
				echo esc_attr( 'align-items: flex-start;' );
				?>
				">
			<?php
			if ( $is_list_view ) {
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
				<div class="revx-campaign-text-content revx-justify-space revx-full-width">
					<div class="revx-full-width">
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
									'quantity'         => $trigger_qty,
									'offered_product'  => $offered_product,
									'generated_styles' => $generated_styles,
									'regular_price'    => $regular_price,
									'offered_price'    => $offered_price,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
						echo wp_kses(
							revenue()->get_template_part(
								'badge_sticky',
								array(
									/* translators: %s: Trigger Quantity */
									'message'          => sprintf( __( 'Buy %s', 'revenue' ), $trigger_qty ),
									'generated_styles' => $generated_styles,
								)
							),
							revenue()->get_allowed_tag()
						);
						echo wp_kses(
							revenue()->get_template_part(
								'quantity_selector',
								array(
									'quantity'         => $trigger_qty,
									'min_quantity'     => $trigger_qty,
									'value'            => $trigger_qty,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
									'offered_product'  => $offered_product,
									'source' => 'trigger'

								)
							),
							revenue()->get_allowed_tag()
						);
						?>
					</div>
				</div>
				<?php
			} else {
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
							'quantity'         => $trigger_qty,
							'offered_product'  => $offered_product,
							'generated_styles' => $generated_styles,
							'regular_price'    => $regular_price,
							'offered_price'    => $offered_price,
							'current_campaign' => $campaign,
						)
					),
					revenue()->get_allowed_tag()
				);
				echo wp_kses(
					revenue()->get_template_part(
						'badge_sticky',
						array(
							/* translators: %s: Trigger Quantity */
							'message'          => sprintf( __( 'Buy %s', 'revenue' ), $trigger_qty ),
							'generated_styles' => $generated_styles,
						)
					),
					revenue()->get_allowed_tag()
				);
				echo wp_kses(
					revenue()->get_template_part(
						'quantity_selector',
						array(
							'quantity'         => $trigger_qty,
							'min_quantity'     => $trigger_qty,
							'value'            => $trigger_qty,
							'generated_styles' => $generated_styles,
							'current_campaign' => $campaign,
							'offered_product'  => $offered_product,
							'source' => 'trigger'

						)
					),
					revenue()->get_allowed_tag()
				);
			}
			?>

		</div>
		<?php
		if ( ! $is_last_product && $is_list_view ) {
			echo wp_kses( revenue()->get_template_part( 'product_separator', array( 'generated_styles' => $generated_styles ) ), revenue()->get_allowed_tag() );
		}

		if ( ! $is_last_product && ! ( $is_list_view ) ) {
			echo wp_kses( revenue()->get_template_part( 'grid_product_separator', array( 'generated_styles' => $generated_styles ) ), revenue()->get_allowed_tag() );
		}
		$trigger_product_output .= ob_get_clean();
	}
}

$trigger_data = $offer_data;

$offer_data = [];

$total_offer_products = 0;


if ( is_array( $offer_products ) ) {
	$offer_length = count( $offer_products );

	ob_start();
	foreach ( $offer_products as $offer_index => $offer ) {
		$offer_qty      = $offer['quantity'];
		$offer_value    = $offer['value'];
		$offer_type     = $offer['type'];
		$items_content  = '';
		$is_tag_enabled = isset( $offer['isEnableTag'] ) ? 'yes' === $offer['isEnableTag'] : false;

		$save_data = '';

		$offer_product_id = $offer['item_id'];

		$product_count = count( $offer_products );

		$is_last_product = ( $offer_length - 1 ) == $offer_index;

		$offered_product = wc_get_product( $offer_product_id );

		if ( ! $offered_product ) {
			continue;
		}

		$_data = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, true, $offer_qty );

		$_save_message_tag = '';

		switch ( $offer_type ) {
			case 'percentage':
				/* translators: %s: Discount percentage value */
				$_save_message_tag = sprintf( __( 'Get %s%% OFF', 'revenue' ), $offer_value );
				break;

			case 'fixed_discount':
			case 'amount':
				/* translators: %s: Discount amount value */
				$_save_message_tag = sprintf( __( 'Get %s OFF', 'revenue' ), wc_price( $offer_value ) );
				break;

			case 'fixed_price':
				/* translators: %s: Discount amount value */
				$_save_message_tag = sprintf( __( 'Get %s OFF', 'revenue' ), wc_price( floatval( $regular_price ) - floatval( $offer_value ) ) );
				break;

			case 'no_discount':
				$_save_message_tag = '';
				break;

			case 'free':
				$_save_message_tag = __( 'Get Free', 'revenue' );
				break;

			default:
				// code...
				break;
		}

		$save_data = $_data['message'];

		$total_offer_product_count++;

		$product_title           = $offered_product->get_title();
		$regular_price           = $offered_product->get_regular_price();
		$offered_price           = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price );
		$product_style           = revenue()->get_style( $generated_styles, 'productGap' );
		$product_separator_style = revenue()->get_style( $generated_styles, 'productSeparator' );

		$total_regular_price += floatval( $regular_price ) * intval( $offer_qty );
		$total_sale_price    += floatval( $offered_price ) * intval( $offer_qty );


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


		if ( ! $is_list_view ) {
			?>
				<div class="revx-flex <?php echo esc_attr( $item_class ); ?>" style="gap: inherit;"> 	<!-- testing div remove that  -->
			<?php
		}
		?>
		<div class="<?php echo esc_attr( $is_list_view ? $item_class : '' ); ?>" style="
			<?php
			echo esc_attr( $is_list_view ? $product_style : '' );
			echo esc_attr( 'align-items: flex-start;' );
			?>
		">
			<?php
			if ( $is_list_view ) {
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
				<div class="revx-justify-space revx-full-width">
					<div class="revx-full-width">
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
						echo wp_kses(
							revenue()->get_template_part(
								'badge_sticky',
								array(
									'message'          => $_save_message_tag,
									'generated_styles' => $generated_styles,
								)
							),
							revenue()->get_allowed_tag()
						);
						echo wp_kses(
							revenue()->get_template_part(
								'quantity_selector',
								array(
									'quantity'         => $offer_qty,
									'min_quantity'     => 'free' == $offer_type ? '1' : $offer_qty,
									'max_quantity'     => 'free' == $offer_type ? $offer_qty : '',
									'value'            => $offer_qty,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
									'offered_product'  => $offered_product,
									'source' => 'offer'

								)
							),
							revenue()->get_allowed_tag()
						);
						?>
					</div>
				</div>
				<?php
			} else {
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
				echo wp_kses(
					revenue()->get_template_part(
						'quantity_selector',
						array(
							'quantity'         => $offer_qty,
							'min_quantity'     => 'free' == $offer_type ? '1' : $offer_qty,
							'max_quantity'     => 'free' == $offer_type ? $offer_qty : '',
							'value'            => $offer_qty,
							'generated_styles' => $generated_styles,
							'current_campaign' => $campaign,
							'offered_product'  => $offered_product,
							'source' => 'offer'

						)
					),
					revenue()->get_allowed_tag()
				);
				echo wp_kses(
					revenue()->get_template_part(
						'badge_sticky',
						array(
							'message'          => $_save_message_tag,
							'generated_styles' => $generated_styles,
						)
					),
					revenue()->get_allowed_tag()
				);
			}
			?>
		</div>
		<?php
		if ( ! $is_last_product && ! ( $is_list_view ) ) {
			echo wp_kses( revenue()->get_template_part( 'grid_product_separator', array( 'generated_styles' => $generated_styles ) ), revenue()->get_allowed_tag() );
		}
		if ( ! $is_list_view ) {
			?>
			</div>
			<?php
		}
		?>

		<?php
		if ( ! $is_last_product && $is_list_view ) {
			echo wp_kses( revenue()->get_template_part( 'product_separator', array( 'generated_styles' => $generated_styles ) ), revenue()->get_allowed_tag() );
		}
	}

	$offer_product_output .= ob_get_clean();
}
ob_start();

$container_style       = revenue()->get_style( $generated_styles, 'container' );
$wrapper_style         = revenue()->get_style( $generated_styles, 'wrapper' );
$regular_product_style = revenue()->get_style( $generated_styles, 'regularProductWrapper' );
$trigger_product_style = revenue()->get_style( $generated_styles, 'triggerProductWrapper' );
$total_price_style     = revenue()->get_style( $generated_styles, 'totalPrice' );

$container_class = 'revx-buyx-gety ' . ( 'list' == $view_mode ? 'revx-campaign-list revx-buyx-gety-list' : 'revx-buyx-gety-grid revx-campaign-grid' );

if ( $is_list_view ) {
	?>
	<div class="revx-campaign-container__wrapper" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<div class="revx-campaign-view__items revx-bxgy-trigger-item" style="<?php echo esc_attr( $trigger_product_style ); ?> ">
			<?php
			echo wp_kses( $trigger_product_output, revenue()->get_allowed_tag() );
			?>
		</div>
		<?php
		echo wp_kses(
			revenue()->get_template_part(
				'add_campaign',
				array(
					'generated_styles' => $generated_styles,
				)
			),
			revenue()->get_allowed_tag()
		);
		?>
		<div class="revx-campaign-view__items revx-bxgy-offer-items" style="<?php echo esc_attr( $regular_product_style ); ?>">
			<?php
			echo wp_kses( $offer_product_output, revenue()->get_allowed_tag() );
			?>
		</div>
		<div class="revx-total-price revx-campaign-text-content" style="<?php echo esc_attr( $total_price_style ); ?>">
			<?php
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
				?>
			</div>
		</div>
		<?php
		echo wp_kses(
			revenue()->get_template_part(
				'add_to_cart',
				array(
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
	<div class="revx-campaign-container__wrapper" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<?php
		if ( $is_tag_enabled ) {
			echo wp_kses(
				revenue()->get_template_part(
					'badge_tag',
					array(
						'generated_styles' => $generated_styles,
						'current_campaign' => $campaign,
					)
				),
				revenue()->get_allowed_tag()
			);
			echo wp_kses(
				revenue()->get_template_part(
					'badge_shape',
					array(
						'generated_styles' => $generated_styles,
						'current_campaign' => $campaign,
					)
				),
				revenue()->get_allowed_tag()
			);
		}
		?>
		<div class="revx-flex">
			<div class="revx-slider-main" >
				<div class="revx-align-center revx-slider revx-buyx-product" style="<?php echo esc_attr( $trigger_product_style ); ?>">
					<div class="revx-campaign-view__items revx-bxgy-trigger-items" >

						<?php
						echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'left' ), revenue()->get_allowed_tag() );
						?>
							<div class="revx-slider-container" style="<?php echo esc_attr( $product_style ); ?>">
								<?php
									echo wp_kses( $trigger_product_output, revenue()->get_allowed_tag() );
								?>
							</div>
						<?php
						echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'right' ), revenue()->get_allowed_tag() );
						?>
					</div>
				</div>
			</div>

			<?php
			echo wp_kses(
				revenue()->get_template_part(
					'add_campaign',
					array(
						'generated_styles' => $generated_styles,
					)
				),
				revenue()->get_allowed_tag()
			);
			?>
			<div class="revx-slider-main">
				<div class="revx-align-center revx-slider revx-gety-product" style=" <?php echo esc_attr( $regular_product_style ); ?>">
					<div class=" revx-campaign-view__items revx-bxgy-offer-items" >
						<?php
							echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'left' ), revenue()->get_allowed_tag() );
						?>
						<div class="revx-slider-container" style="<?php echo esc_attr( $product_style ); ?>">
							<?php

							echo wp_kses( $offer_product_output, revenue()->get_allowed_tag() );

							?>
						</div>
						<?php
						echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'right' ), revenue()->get_allowed_tag() );
						?>
					</div>
				</div>
			</div>
		</div>

			<div class="revx-total-price revx-campaign-text-content" style="<?php echo esc_attr( $total_price_style ); ?>">
				<?php
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
					?>
				</div>
			</div>
			<?php
			echo wp_kses(
				revenue()->get_template_part(
					'add_to_cart',
					array(
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
}

?>
<input type="hidden" name="<?php echo esc_attr( 'revx-offer-data-' . $campaign['id'] ); ?>" value=" <?php echo esc_html( htmlspecialchars( wp_json_encode( $offer_data ) ) ); ?>" />
<input type="hidden" name="<?php echo esc_attr( 'revx-trigger-data-' . $campaign['id'] ); ?>" value=" <?php echo esc_html( htmlspecialchars( wp_json_encode( $trigger_data ) ) ); ?>" />

<?php
$output_content .= ob_get_clean();

revenue()->floating_container( $campaign, $generated_styles, $output_content, $container_class, false, $placement );
