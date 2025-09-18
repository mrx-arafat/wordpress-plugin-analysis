<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! isset( revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )[ $position ] ) ) {
	return;
}


$buider_data = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )[ $position ];

$generated_styles = revenue()->campaign_style_generator( $position, $campaign );

$progress_bar_style = revenue()->get_style( $generated_styles, 'progressBar' );
$wrapper_style      = revenue()->get_style( $generated_styles, 'container' );


$offers = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );

if ( ! isset( $offers[0]['free_shipping_based_on'] ) ) {
	return;
}
$based_on   = $offers[0]['free_shipping_based_on'];
$cart_total = $this->get_eligible_cart_total( $offers[0]['free_shipping_based_on'], true );

$step_width = 100;

$total_goal = 0;
foreach ( $offers as $offer ) {
	if ( isset( $offer['required_goal'] ) ) {
		$total_goal += floatval( $offer['required_goal'] );
	}
}

$progress = $total_goal > 0 ? min( ( $cart_total / $total_goal ) * 100, 100 ) : 0;

$img_style = revenue()->get_style( $generated_styles, 'productImage' );

$required_goal   = 0;
$current_message = '';
$reward_message  = '';




foreach ( $offers as $index => $offer ) {
	if ( ! isset( $offer['required_goal'] ) ) {
		continue;
	}
	$required_goal += floatval( $offer['required_goal'] );
	if ( 0 == $cart_total ) {
		$current_message  = isset( $offer['promo_message'] ) ? $offer['promo_message'] : '';
		$remaining_amount = $cart_total - $required_goal;

		$current_message = str_replace( '{remaining_amount}', wc_price( abs( $remaining_amount ) ), $current_message );

	} elseif ( $cart_total < $required_goal ) {
		$current_message = isset( $offer['before_message'] ) ? $offer['before_message'] : '';

		$remaining_amount = $cart_total - $required_goal;

		$current_message = str_replace( '{remaining_amount}', wc_price( abs( $remaining_amount ) ), $current_message );

		break;
	} else {
		$reward_message  = isset( $offer['after_message'] ) ? $offer['after_message'] : '';
		$current_message = $reward_message;
	}
}


$upsell_products = array();

$quantity_style        = revenue()->get_style( $generated_styles, 'quantitySelector' );
$quantity_input_style  = revenue()->get_style( $generated_styles, 'quantitySelector', 'input' );
$quantity_button_style = revenue()->get_style( $generated_styles, 'quantitySelector', 'child' );
$classes               = revenue()->get_style( $generated_styles, 'quantitySelector', 'classes' );

if ( 'yes' === $campaign['upsell_products_status'] ) {

	$data            = $campaign['upsell_products'];
	$upsell_products = array();

	if ( ! is_array( $data ) ) {
		$data = array();
	}

	foreach ( $data as $order ) {
		// Ensure 'products' is an array of product IDs.
		if ( ! isset( $order['products'] ) || ! is_array( $order['products'] ) ) {
			continue;
		}

		// Process each product ID.
		foreach ( $order['products'] as $item_data ) {

			$regular_price    = (float) $item_data['regular_price'];
			$quantity         = isset( $order['quantity'] ) ? (int) $order['quantity'] : 0;
			$discounted_price = $regular_price;
			$discount_amount  = isset( $order['value'] ) ? (float) $order['value'] : 0;



			// Calculate discounted price based on order type.
			if ( isset( $order['type'] ) ) {
				switch ( $order['type'] ) {
					case 'percentage':
						$discount_value   = $discount_amount;
						$discount_amount  = number_format( ( $regular_price * $discount_value ) / 100, 2 );
						$discounted_price = number_format( $regular_price - $discount_amount, 2 );
						break;

					case 'fixed_discount':
						$discount_amount  = number_format( $discount_amount, 2 );
						$discounted_price = number_format( $regular_price - $discount_amount, 2 );
						break;

					case 'free':
						$discounted_price = '0.00';
						$discount_amount  = '100%';
						break;

					case 'no_discount':
						$discount_amount  = '0';
						$discounted_price = number_format( $regular_price, 2 );
						break;
				}
			}

			// Prepare product data.
			$upsell_products[] = array(
				'item_id'       => $item_data['item_id'],
				'item_name'     => $item_data['item_name'],
				'thumbnail'     => $item_data['thumbnail'], // Get the product thumbnail URL.
				'regular_price' => number_format( $regular_price, 2 ),
				'url'           => get_permalink( $item_data['item_id'] ),
				'sale_price'    => 0 == $discount_amount ? false : $discounted_price,
				'quantity'      => $quantity,
				'type'          => $order['type'],
				'value'         => isset( $order['value'] ) ? $order['value'] : '',
			);
		}
	}
}

$left_slider_style = revenue()->get_style( $generated_styles, 'leftSliderIcon' );

$right_slider_style = revenue()->get_style( $generated_styles, 'rightSliderIcon' );

$add_to_cart_class = revenue()->get_style( $generated_styles, 'addToCartButton', 'classes' );

$cta_class = revenue()->get_style( $generated_styles, 'ctaButton', 'classes' );

$cta_button_text = isset( $campaign['cta_button_text'] ) ? $campaign['cta_button_text'] : __( 'Shop Now', 'revenue' );

$separator_style = revenue()->get_style( $generated_styles, 'separator' );

$paragraph_wrapper_style = revenue()->get_style( $generated_styles, 'paragraphWrapper' );

$bk_total = $cart_total;

$cta_link = isset( $offers[0]['cta_link'] ) ? sanitize_url( $offers[0]['cta_link'] ) : '#';

$is_show_progress_bar = isset( $campaign['is_show_free_shipping_bar'] ) ? sanitize_text_field( $campaign['is_show_free_shipping_bar'] ) : 'no';

$is_show_close_icon = isset( $campaign['show_close_icon'] ) ? sanitize_text_field( $campaign['show_close_icon'] ) : 'no';

$is_show_cta_button = isset( $campaign['enable_cta_button'] ) ? $campaign['enable_cta_button'] : 'no';
?>



<div id="revx-progress-inpage"
	data-cart-total="<?php echo esc_attr( $cart_total ); ?>"
	data-progress="<?php echo esc_attr( $progress ); ?>"
	style="
	<?php
	echo esc_attr( $wrapper_style );
	echo esc_attr( 'display:none;' );
	?>
	"
	data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
	data-position=<?php echo esc_attr( $position ); ?>
	class="revx-campaign-container revx-campaign-fsb <?php echo esc_attr( $cart_total <= 0 ? 'hide' : '' ); ?> revx-campaign-<?php echo esc_attr( $campaign['id'] ); ?> revx-campaign-fsb-<?php echo esc_attr( $position ); ?>"
	data-final-message="<?php echo esc_attr( $campaign['all_goals_complete_message'] ); ?>"
	data-show-confetti="<?php echo esc_attr( $campaign['show_confetti'] ); ?>"
	data-based-on="<?php echo esc_attr( $based_on ); ?>"

	> <!-- Add data-progress here -->

	<div>
		<!-- Expanded Content -->
		<div>
			<div class="revx-paragraph-wrapper" style="<?php echo esc_attr( $paragraph_wrapper_style ); ?>">
				<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', $current_message, 'revx-message', 'div' ), revenue()->get_allowed_tag() ); ?>
				<?php
				if ( 'yes' === $is_show_cta_button ) {
					?>
							<a href="<?php echo esc_url_raw( $cta_link ); ?>">
						<?php
							echo wp_kses(
								revenue()->tag_wrapper(
									$current_campaign,
									$generated_styles,
									'ctaButton',
									$cta_button_text,
									"revx-cursor-pointer revx-builder-btn $cta_class",
									'button',
									array(
										'campaign-id'   => $current_campaign['id'],
										'campaign-type' => $current_campaign['campaign_type'],
									)
								),
								revenue()->get_allowed_tag()
							);
						?>
							</a>
						<?php
				}
				?>
				
			</div>

			<?php
			if ( 'yes' === $is_show_progress_bar ) {
				?>
			
			<div class="revx-progress-container ">
				<div class="revx-progress-bar" style="<?php echo esc_attr( $progress_bar_style ); ?>">
					<div class="revx-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>
				</div>
			</div>
				<?php
			}
			?>


		</div>
		

		<?php
		if ( 'inpage' === $position && ! empty( $upsell_products ) && 'yes' === $is_show_progress_bar ) {
			?>
				<div
				className="revx-spg-separator"
				style="
				<?php
				echo esc_attr( $separator_style );
				echo 'height: 1px';
				?>
				"
				></div>
			<?php
		}
		?>

		<?php

		if ( ! empty( $upsell_products ) ) {
			?>
			<div class="revx-upsell-slider">
				<button class="revx-upsell-slider-nav-button revx-upsell-slider-prev" aria-label="Previous" style="<?php echo esc_attr( $left_slider_style ); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" transform="matrix(-1,1.2246467991473532e-16,-1.2246467991473532e-16,-1,0,0)">
						<path d="M9 18L15 12L9 6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"></path>
					</svg>
				</button>

				<div class="revx-upsell-slider-content">
					<div class="revx-upsell-slider-track">
						<?php

						foreach ( $upsell_products as $upsell_product ) {
							?>
								<div class="revx-upsell-slider-product-card">
									<div class="revx-upsell-slider-product-image" style="<?php echo esc_attr( $img_style ); ?>">
										<img src="<?php echo esc_attr( $upsell_product['thumbnail'] ); ?>" alt="<?php echo esc_attr( $upsell_product['item_name'] ); ?>" />
									</div>
									<div class="revx-upsell-slider-product-details">
										<div class="revx-upsell-slider-product-meta">
										<?php echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'productTitle', $upsell_product['item_name'], 'revx-spending-goal-product-title', 'div', array( 'product_url' => $upsell_product['url'] ) ), revenue()->get_allowed_tag() ); ?>
											
											<div class="revx-campaign-item__prices revx-flex" style="<?php echo esc_attr( revenue()->get_style( $generated_styles, 'priceContainer' ) ); ?>"> 
											<?php
											if ( isset( $upsell_product['sale_price'] ) && $upsell_product['sale_price'] ) {
												echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', wc_price( max( 0, $upsell_product['regular_price'] ) ), 'revx-campaign-item__regular-price', 'div' ), revenue()->get_allowed_tag() );
												echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', wc_price( max( 0, $upsell_product['sale_price'] ) ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											} else {
												echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', wc_price( max( 0, $upsell_product['regular_price'] ) ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() );
											}
											?>
											</div>
										</div>
										<div class="revx-upsell-slider-actions revx-flex revx-align-center">
                                            <div class="revx-builder__quantity revx-align-center revx-width-full  <?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $quantity_style ); ?>">
                                                <div class="revx-quantity-minus revx-justify-center" style="<?php echo esc_attr( $quantity_button_style ); ?>">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3.33333 8H12.6667" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
                                                    </svg>
                                                </div>
                                                
                                                    <input  data-name="revx_quantity"  type="number"  data-product-id="<?php echo esc_attr( $upsell_product['item_id'] ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" name="<?php echo esc_attr( 'revx-quantity-' . $current_campaign['id'] . '-' . $upsell_product['item_id'] ); ?>" style="<?php echo esc_attr( $quantity_input_style ); ?>" value="1"/>
                                                    
                                                <div class="revx-quantity-plus revx-justify-center" style="<?php echo esc_attr( $quantity_button_style ); ?>">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M8 3.33301V12.6663" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
                                                        <path d="M3.33334 8H12.6667" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
                                                    </svg>
                                                </div>
                                            </div>

										<?php
										echo wp_kses(
											revenue()->tag_wrapper(
												$current_campaign,
												$generated_styles,
												'addToCartButton',
												__( 'Add to Cart', 'revenue' ),
												'revx-cursor-pointer revx-upsell-slider-add-cart revx-builder-btn ' . $add_to_cart_class,
												'button',
												array(
													'product-id'             => $upsell_product['item_id'],
													'campaign-id'            => $current_campaign['id'],
													'campaign-type'          => $current_campaign['campaign_type'],
												)
											),
											revenue()->get_allowed_tag()
										);

										?>
										</div>
										
									</div>
								</div>
							   <?php
						}
						?>
					</div>
				</div>

				<button class="revx-upsell-slider-nav-button revx-upsell-slider-next" aria-label="Next" style="<?php echo esc_attr( $right_slider_style ); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M9 18L15 12L9 6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
					</svg>
				</button>
			</div>

			<?php
		}

		if ( 'yes' === $is_show_close_icon ) {
			echo wp_kses(
				revenue()->get_template_part(
					'campaign_close',
					array(
						'generated_styles' => $generated_styles,
						'current_campaign' => $current_campaign,
					)
				),
				revenue()->get_allowed_tag()
			);
		}

		?>
	</div>
	<input type="hidden" name="revenue_free_shipping_offer" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $offers ) ) ); ?>" />
	<input type="hidden" name="revenue_upsell_products" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $upsell_products ) ) ); ?>" />
</div>
