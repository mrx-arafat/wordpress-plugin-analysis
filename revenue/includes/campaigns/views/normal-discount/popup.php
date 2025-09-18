<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Normal Discount popup Template
 *
 * This file handles the display of normal discount offers in a popup container.
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

$output_content  = '';
$offered_product = false;
$regular_price   = false;
$offered_price   = false;

$view_mode = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
if ( ! $view_mode ) {
	return;
}
$buider_data = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )['popup'][ $view_mode ];

$offers                   = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$on_cart_action           = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );
$on_offered_product_click = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_click_action', true );

$generated_styles = revenue()->campaign_style_generator( 'popup', $campaign, $placement );

$total_offer_products = 0;

$offer_data = array();

if ( is_array( $offers ) ) {
	$offer_length = count( $offers );

	foreach ( $offers as $offer_index => $offer ) {

		$offered_product_ids = isset( $offer['products'] ) ? $offer['products'] : array();
		$offer_qty           = isset( $offer['quantity'] ) ? $offer['quantity'] : '';
		$offer_value         = isset( $offer['value'] ) ? $offer['value'] : '';
		$offer_type          = isset( $offer['type'] ) ? $offer['type'] : '';


		foreach ( $offered_product_ids as $product_index => $offer_product_id ) {
			$offered_product = wc_get_product( $offer_product_id );
			if ( ! $offered_product ) {
				continue;
			}
			if ( ! $offered_product->is_in_stock() ) {
				continue;
			}
			if ( revenue()->is_hide_product( $campaign['id'], $offer_product_id ) ) {
				continue;
			}

			$total_offer_products++;
			$product_count   = count( $offered_product_ids );
			$is_last_product = ( $offer_length - 1 ) == $offer_index && ( $product_count - 1 ) == $product_index;
			$is_tag_enabled  = isset( $offer['isEnableTag'] ) ? 'yes' == $offer['isEnableTag'] : false;
			$offered_product = $offered_product;
			$product_title   = $offered_product->get_title();
			$regular_price   = $offered_product->get_regular_price();

			$price_data           = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, true );
			$offered_price        = $price_data['price'];
			$product_style        = revenue()->get_style( $generated_styles, 'product' );
			$tagged_product_style = revenue()->get_style( $generated_styles, 'taggedProduct' );
			$container_class      = 'revx-campaign-item' . ( 'list' === $view_mode ? '' : ' revx-campaign-text-content' );

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


			ob_start(); ?>

			<div class="<?php echo esc_attr( $container_class ); ?>" data-product-id="<?php echo esc_attr( ! $offered_product->is_type( 'variable' ) ? $offer_product_id : '' ); ?>" style="<?php echo esc_attr( $is_tag_enabled ? $tagged_product_style : $product_style ); ?>">
				<?php
				if ( 'list' == $view_mode ) {
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
					<div class="revx-campaign-text-content revx-justify-space-sm revx-full-width">
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
							?>
							<div class="revx-pricing-wrapper revx-align-center">
								<?php
								echo wp_kses(
									revenue()->get_template_part(
										'price_container',
										array(
											'offered_product' => $offered_product,
											'generated_styles' => $generated_styles,
											'regular_price' => $regular_price,
											'offered_price' => $offered_price,
											'current_campaign' => $campaign,
											'quantity' => $offer_qty,
										)
									),
									revenue()->get_allowed_tag()
								);
								echo wp_kses(
									revenue()->get_template_part(
										'save',
										array(
											'generated_styles' => $generated_styles,
											'regular_price' => $regular_price,
											'offered_price' => $offered_price,
											'current_campaign' => $campaign,
											'quantity' => $offer_qty,
											'message'  => $price_data['message'],
										)
									),
									revenue()->get_allowed_tag()
								);
								?>
							</div>
							<div class="revx-flex revx-quantity-addToCart-container revx-flex-wrap">
								<?php
								echo wp_kses(
									revenue()->get_template_part(
										'quantity_selector',
										array(
											'quantity'     => $offer_qty,
											'min_quantity' => 'free' == $offer_type ? 1 : $offer_qty,
											'max_quantity' => 'free' == $offer_type ? $offer_qty : '',
											'value'        => $offer_qty,
											'generated_styles' => $generated_styles,
											'current_campaign' => $campaign,
											'offered_product' => $offered_product,
										)
									),
									revenue()->get_allowed_tag()
								);
								if ( ! ( 1 == $total_offer_products && $is_last_product ) ) {
									echo wp_kses(
										revenue()->get_template_part(
											'add_to_cart',
											array(
												'generated_styles' => $generated_styles,
												'current_campaign' => $campaign,
												'offered_product' => $offered_product,
											)
										),
										revenue()->get_allowed_tag()
									);
								}
								?>
							</div>
						</div>
						<?php
						if ( $is_tag_enabled ) {
							echo wp_kses(
								revenue()->get_template_part(
									'badge',
									array(
										'current_campaign' => $campaign,
										'generated_styles' => $generated_styles,
									)
								),
								revenue()->get_allowed_tag()
							);
						}

						?>
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
							'save',
							array(
								'generated_styles' => $generated_styles,
								'regular_price'    => $regular_price,
								'offered_price'    => $offered_price,
								'current_campaign' => $campaign,
								'quantity'         => $offer_qty,
								'message'          => $price_data['message'],
							)
						),
						revenue()->get_allowed_tag()
					);
					echo wp_kses(
						revenue()->get_template_part(
							'quantity_selector',
							array(
								'quantity'         => $offer_qty,
								'min_quantity'     => 'free' == $offer_type ? 1 : $offer_qty,
								'max_quantity'     => 'free' == $offer_type ? $offer_qty : '',
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
								'generated_styles' => $generated_styles,
								'current_campaign' => $campaign,
								'offered_product'  => $offered_product,
							)
						),
						revenue()->get_allowed_tag()
					);
					if ( $is_tag_enabled ) {
						echo wp_kses(
							revenue()->get_template_part(
								'badge',
								array(
									'current_campaign' => $campaign,
									'generated_styles' => $generated_styles,
								)
							),
							revenue()->get_allowed_tag()
						);
					}
				}
				?>
			</div>
			<?php
			if ( ( 1 == $total_offer_products && $is_last_product && 'list' == $view_mode ) ) {
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
			}
			$output_content .= ob_get_clean();
		}
	}
}


if ( ! $output_content ) {
	return;
}

$wrapper_style   = revenue()->get_style( $generated_styles, 'wrapper' );
$product_gap     = revenue()->get_style( $generated_styles, 'productGap' );
$container_class = 'revx-normal-discount  ' . ( 'list' === $view_mode ? 'revx-campaign-list revx-normal-discount-list' : 'revx-normal-discount-grid revx-campaign-grid' );
$grid_class      = 'revx-campaign-view__items ' . ( 'list' === $view_mode ? 'revx-align-center' : 'revx-slider-container' );
ob_start();
if ( 'list' == $view_mode ) {
	?>
	<div class="revx-campaign-container__wrapper" style="<?php echo esc_attr( $wrapper_style ); ?>">
		<?php
			echo wp_kses( $output_content, revenue()->get_allowed_tag() );
		?>
	</div>
	<?php
} else {
	$slider_container = 'revx-align-center revx-slider';
	?>
		<div class="revx-campaign-container__wrapper" style="<?php echo esc_attr( $wrapper_style ); ?>">
			<div class="<?php echo esc_attr( $slider_container ); ?>" >
				<?php echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'left' ), revenue()->get_allowed_tag() ); ?>
				<div class="<?php echo esc_attr( $grid_class ); ?>" style="<?php echo esc_attr( $product_gap ); ?>">
					<?php
						echo wp_kses( $output_content, revenue()->get_allowed_tag() );
					?>
				</div>
					<?php echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'right' ), revenue()->get_allowed_tag() ); ?>
			</div>
		</div>
	<?php
}

?>
<input type="hidden" name="<?php echo esc_attr( 'revx-offer-data-' . $campaign['id'] ); ?>" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $offer_data ) ) ); ?>" />
<?php


$output_content = ob_get_clean();

revenue()->popup_container( $campaign, $generated_styles, $output_content, $container_class, false, $placement );
