<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

/**
 * Stock Scarcity Inpage View
 *
 * @package Revenue
 */
global $product;
$stock_quantity = null;
$campaign_id    = $campaign['id'];
$product_id     = $product->get_id();

$generated_styles = revenue()->campaign_style_generator( 'inpage', $campaign );


$progress_bar_style      = revenue()->get_style( $generated_styles, 'progressBar' );
$paragraph_wrapper_style = revenue()->get_style( $generated_styles, 'paragraphWrapper' );


if ( $product && is_a( $product, 'WC_Product' ) ) {
	$stock_quantity = $product->get_stock_quantity();
}

$sold_stock = $product->get_total_sales();
$view_count = get_post_meta( $product_id, $campaign_id . '_views_counter', true );
if ( empty( $view_count ) ) {
	$view_count = 0;
}
// $user_count = (int) $product->get_meta( $product->get_id() . '_user_purchase_count', true );
$user_count = (int) $this->get_distinct_user_count_by_product( $product->get_id() );

$total_stock = $sold_stock + $stock_quantity;
$percentage  = round( $total_stock > 0 ? ( $stock_quantity / $total_stock ) * 100 : 0 );
if ( ! $product->managing_stock() || 0 === $stock_quantity ) {
	return;
}

// Save one time sold quantity.
$fixed_quantity_meta_key = $campaign_id . '_fixed_quantity';
$fixed_sold_meta_key     = $campaign_id . '_fixed_sold_quantity';
$fixed_view_meta_key     = $campaign_id . '_fixed_view';
$fixed_user_meta_key     = $campaign_id . '_fixed_user';
$fixed_quantity          = get_post_meta( $product_id, $fixed_quantity_meta_key, true );
$fixed_sold_quantity     = get_post_meta( $product_id, $fixed_sold_meta_key, true );
$fixed_views             = get_post_meta( $product_id, $fixed_view_meta_key, true );
$fixed_users             = get_post_meta( $product_id, $fixed_user_meta_key, true );
// If the fixed sold quantity is not set, save it as the current sold quantity.

if ( empty( $fixed_sold_quantity ) ) {
	$fixed_sold_quantity = $sold_stock;
	update_post_meta( $product_id, $fixed_sold_meta_key, $fixed_sold_quantity );
}
	// If the fixed quantity is not set, save it as the current quantity.
if ( empty( $fixed_quantity ) ) {
	$fixed_quantity = $stock_quantity;
	update_post_meta( $product_id, $fixed_quantity_meta_key, $fixed_quantity );
}
	// If the fixed views is not set, save it as the current views.
if ( empty( $fixed_views ) ) {
	$fixed_views = $view_count;
	update_post_meta( $product_id, $fixed_view_meta_key, $fixed_views );
}
	// If the fixed users is not set, save it as the current users.
if ( empty( $fixed_users ) ) {
	$fixed_users = $user_count;
	update_post_meta( $product_id, $fixed_user_meta_key, $fixed_users );
}

	$quantity_diff = (int) $stock_quantity - (int) $fixed_quantity;
	$sold_diff     = (int) $sold_stock - (int) $fixed_sold_quantity;
	$view_diff     = (int) $view_count - (int) $fixed_views;
	$user_diff     = (int) $user_count - (int) $fixed_users;


	$message_type     = $campaign['stock_scarcity_message_type'] ?? 'generalMessage';
	$general_settings = $campaign['stock_scarcity_general_message_settings'] ?? array();
	$flip_settings    = $campaign['stock_scarcity_flip_message_settings'] ?? array();



	// for general message.
	$general_message     = '';
	$flip_first_message  = '';
	$flip_second_message = '';
if ( 'generalMessage' === $message_type ) {
	// General Message Settings.
	$in_stock_message     = $general_settings['in_stock_message'] ?? '{stock_number} units available now!😊';
	$low_stock_message    = $general_settings['low_stock_message'] ?? "Only {stock_number} left - don't miss out!🏃‍♀️";
	$urgent_stock_message = $general_settings['urgent_stock_message'] ?? 'Only {stock_number} left - restock uncertain!❗';
	$is_low_stock         = $general_settings['isLowStockChecked'] ?? 'no';
	$is_urgent_stock      = $general_settings['isUrgentStockChecked'] ?? 'no';
	$enable_stock_bar     = $general_settings['enable_stock_bar'] ?? 'no';
	$enable_fake_stock    = $general_settings['enable_fake_stock'] ?? 'no';
	$repeat_interval      = $general_settings['repeat_interval'] ?? 'no';
	$low_stock_amount     = $general_settings['low_stock_amount'] ?? 0;
	$urgent_stock_amount  = $general_settings['urgent_stock_amount'] ?? 0;
	$in_stock_fake_amount = $general_settings['in_stock_fake_amount'] ?? 0;
	$low_fake_amount      = $general_settings['low_fake_amount'] ?? 0;
	$urgent_fake_amount   = $general_settings['urgent_fake_amount'] ?? 0;


	// If Fake stock is not enabled.
	if ( null !== $stock_quantity && 'yes' !== $enable_fake_stock ) {
		if ( 'yes' === $is_low_stock && $stock_quantity <= $low_stock_amount && $stock_quantity > $urgent_stock_amount ) {
			$general_message = $low_stock_message;
		} elseif ( 'yes' === $is_urgent_stock && $stock_quantity <= $urgent_stock_amount && $stock_quantity <= $low_stock_amount ) {
			$general_message = $urgent_stock_message;
		} else {
			$general_message = $in_stock_message;
		}
		$general_message = str_replace( '{stock_number}', $stock_quantity, $general_message );
	}
	if ( null !== $stock_quantity && 'yes' === $enable_fake_stock ) {

		// If the fixed sold quantity is not set, save it as the current sold quantity.
		$fixed_general_quantity_meta_key = $campaign_id . '_fixed_gen_quantity';
		$fixed_gen_stock_quantity        = get_post_meta( $product_id, $fixed_general_quantity_meta_key, true );
		if ( empty( $fixed_gen_stock_quantity ) ) {
			$fixed_gen_stock_quantity = $stock_quantity;
			update_post_meta( $product_id, $fixed_general_quantity_meta_key, $fixed_gen_stock_quantity );
		}
		$quantity_gen_diff   = (int) $stock_quantity - (int) $fixed_gen_stock_quantity;
		$fake_stock_quantity = (int) $in_stock_fake_amount + (int) $quantity_gen_diff;

		// If Fake is enable and repeat interval.
		if ( 'yes' === $repeat_interval && $stock_quantity >= $in_stock_fake_amount && 0 === $fake_stock_quantity ) {
			$fake_stock_quantity = $in_stock_fake_amount;
			update_post_meta( $product_id, $fixed_general_quantity_meta_key, $stock_quantity );
		}

		if ( 'yes' === $is_low_stock && $fake_stock_quantity <= $low_fake_amount && $fake_stock_quantity > $urgent_fake_amount ) {
			$general_message = $low_stock_message;
		} elseif ( 'yes' === $is_urgent_stock && $fake_stock_quantity <= $urgent_fake_amount && $fake_stock_quantity <= $low_fake_amount ) {
			$general_message = $urgent_stock_message;
		} else {
			$general_message = $in_stock_message;
		}
		$general_message = str_replace( '{stock_number}', $fake_stock_quantity, $general_message );
	}
} elseif ( 'flipMessage' === $message_type ) {
	// Flip Message Settings.
	$enable_fake_stock_flip      = $flip_settings['enable_fake_stock'] ?? 'no';
	$first_repeat_interval_flip  = $flip_settings['first_repeat_interval'] ?? 'no';
	$second_repeat_interval_flip = $flip_settings['second_repeat_interval'] ?? 'no';
	$select_first_message_flip   = $flip_settings['select_first_message'] ?? 'stockNumber';
	$first_sale_message_flip     = $flip_settings['first_sale_message'] ?? '{sales_number} units SOLD already! Grab Yours👍';
	$select_second_message_flip  = $flip_settings['select_second_message'] ?? 'stockNumber';
	$second_view_message_flip    = $flip_settings['second_view_message'] ?? '{view_number} people are looking at this right now! Act Fast⚡';
	$first_stock_message_flip    = $flip_settings['first_stock_message'] ?? '{stock_number} units available now!😊';
	$first_view_message_flip     = $flip_settings['first_view_message'] ?? '{view_number} people are looking at this right now! Act Fast⚡';
	$first_user_message_flip     = $flip_settings['first_user_message'] ?? '{shopper_number} PEOPLE just bought this! Will you be next?🔥';
	$first_fake_amount_flip      = $flip_settings['first_fake_amount'] ?? 0;
	$second_fake_amount_flip     = $flip_settings['second_fake_amount'] ?? 0;
	$second_stock_message_flip   = $flip_settings['second_stock_message'] ?? '{stock_number} units available now!😊!';
	$second_sale_message_flip    = $flip_settings['second_sale_message'] ?? '{sales_number} units SOLD already! Grab Yours👍';
	$second_user_message_flip    = $flip_settings['second_user_message'] ?? '{shopper_number} PEOPLE just bought this! Will you be next?🔥';
	// If Fake stock is not enabled.
	if ( null !== $stock_quantity && 'yes' !== $enable_fake_stock_flip ) {
		switch ( $select_first_message_flip ) {
			case 'stockNumber':
				$flip_first_message = str_replace( '{stock_number}', $stock_quantity, $first_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_first_message = str_replace( '{sales_number}', $sold_stock, $first_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_first_message = str_replace( '{view_number}', $view_count, $first_view_message_flip );
				break;
			case 'userNumber':
				$flip_first_message = str_replace( '{shopper_number}', $user_count, $first_user_message_flip );
				break;
			default:
				$flip_first_message = str_replace( '{stock_number}', $stock_quantity, $first_stock_message_flip );
		}

		switch ( $select_second_message_flip ) {
			case 'stockNumber':
				$flip_second_message = str_replace( '{stock_number}', $stock_quantity, $second_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_second_message = str_replace( '{sales_number}', $sold_stock, $second_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_second_message = str_replace( '{view_number}', $view_count, $second_view_message_flip );
				break;
			case 'userNumber':
				$flip_second_message = str_replace( '{shopper_number}', $user_count, $second_user_message_flip );
				break;
			default:
				$flip_second_message = str_replace( '{stock_number}', $stock_quantity, $second_stock_message_flip );
		}
	}

	// If Fake stock is enabled.
	if ( null !== $stock_quantity && 'yes' === $enable_fake_stock_flip ) {

		$fake_stock_quantity_one = $first_fake_amount_flip + $quantity_diff;
		// If Fake is enable and first repeat interval.
		if ( 'yes' === $first_repeat_interval_flip && $stock_quantity >= $first_fake_amount_flip && 'stockNumber' === $select_first_message_flip && 0 === $fake_stock_quantity_one ) {
			$fake_stock_quantity_one = $first_fake_amount_flip;
			update_post_meta( $product_id, $fixed_quantity_meta_key, $stock_quantity );
		}
		$fake_sold_stock_one = $first_fake_amount_flip + $sold_diff;
		$fake_view_count_one = $first_fake_amount_flip + $view_diff;
		$fake_user_count_one = $first_fake_amount_flip + $user_diff;

		if ( empty( $fake_view_count_one ) ) {
			$fake_view_count_one = 0;
		}

		if ( empty( $fake_user_count_one ) ) {
			$fake_user_count_one = 0;
		}

		$fake_stock_quantity_two = $second_fake_amount_flip + $quantity_diff;
		// If Fake is enable and second repeat interval.
		if ( 'yes' === $second_repeat_interval_flip && $stock_quantity >= $second_fake_amount_flip && 'stockNumber' === $select_second_message_flip && 0 === $fake_stock_quantity_two ) {
			$fake_stock_quantity_two = $first_fake_amount_flip;
			update_post_meta( $product_id, $fixed_quantity_meta_key, $stock_quantity );
		}
		$fake_sold_stock_two = $second_fake_amount_flip + $sold_diff;
		$fake_view_count_two = $second_fake_amount_flip + $view_diff;
		$fake_user_count_two = $second_fake_amount_flip + $user_diff;

		if ( empty( $fake_view_count_two ) ) {
			$fake_view_count_two = 0;
		}

		if ( empty( $fake_user_count_two ) ) {
			$fake_user_count_two = 0;
		}

		switch ( $select_first_message_flip ) {
			case 'stockNumber':
				$flip_first_message = str_replace( '{stock_number}', $fake_stock_quantity_one, $first_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_first_message = str_replace( '{sales_number}', $fake_sold_stock_one, $first_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_first_message = str_replace( '{view_number}', $fake_view_count_one, $first_view_message_flip );
				break;
			case 'userNumber':
				$flip_first_message = str_replace( '{shopper_number}', $fake_user_count_one, $first_user_message_flip );
				break;
		}

		switch ( $select_second_message_flip ) {
			case 'stockNumber':
				$flip_second_message = str_replace( '{stock_number}', $fake_stock_quantity_two, $second_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_second_message = str_replace( '{sales_number}', $fake_sold_stock_two, $second_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_second_message = str_replace( '{view_number}', $fake_view_count_two, $second_view_message_flip );
				break;
			case 'userNumber':
				$flip_second_message = str_replace( '{shopper_number}', $fake_user_count_two, $second_user_message_flip );
				break;
		}
	}
}


?>
<div data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" class="rvex-stock-scarcity-wrapper">
<?php
if ( is_cart() && isset( $campaign['placement_settings']['cart_page'] ) && ! empty( $campaign['placement_settings']['cart_page'] ) && 'yes' === $campaign['placement_settings']['cart_page']['status'] ) {
	if ( 'generalMessage' === $message_type ) {
		?>
		<div style="<?php echo esc_attr( $paragraph_wrapper_style ); ?>">
			<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $general_message ), '', 'div' ), revenue()->get_allowed_tag() ); ?>
		</div>
		<?php
		if ( 'yes' === $enable_stock_bar ) {
			?>
			<div class="rvex-progress-container" style="<?php echo esc_attr( $progress_bar_style ); ?> height:8px; background:var(--revx-empty-color); display: flex; border-radius: 6px; max-width: 100%;">
				<div class="rvex-stock-bar" style="width: <?php echo esc_attr( $percentage ); ?>%; height: inherit; background: var(--revx-filled-color, #6E3FF3); border-radius: inherit; transition: width 0.5s;"></div>
			</div>
			<?php
		}
		?>
		<?php
	}
	?>
	<?php
	if ( 'flipMessage' === $message_type ) {
		?>
		<div class="rvex-flip-container">
			<div class="rvex-flip-wrapper" style="<?php echo esc_attr( $paragraph_wrapper_style ); ?>">
				<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $flip_first_message ), 'rvex-flip-text', 'div' ), revenue()->get_allowed_tag() ); ?>
				<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $flip_second_message ), 'rvex-flip-text', 'div' ), revenue()->get_allowed_tag() ); ?>
			</div>
		</div>
		<?php
	}
} elseif ( is_product() && isset( $campaign['placement_settings']['product_page'] ) && ! empty( $campaign['placement_settings']['product_page'] ) && 'yes' === $campaign['placement_settings']['product_page']['status'] ) {
	if ( 'generalMessage' === $message_type ) {
		?>
		<div class="rvex-stock-scarcity-container rvex-stock-product">
		<div style="<?php echo esc_attr( $paragraph_wrapper_style ); ?>">
			<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $general_message ), '', 'div' ), revenue()->get_allowed_tag() ); ?>
		</div>
			<?php
			if ( 'yes' === $enable_stock_bar ) {
				?>
				<div class="rvex-progress-container" style="<?php echo esc_attr( $progress_bar_style ); ?> height:8px; background:var(--revx-empty-color); display: flex; border-radius: 6px; max-width: 100%;">
				<div class="rvex-stock-bar" style="width: <?php echo esc_attr( $percentage ); ?>%; height: inherit; background: var(--revx-filled-color, #6E3FF3); border-radius: inherit; transition: width 0.5s;"></div>
			</div>
				<?php
			}
			?>
		</div>
		<?php
	}
	?>
	<?php
	if ( 'flipMessage' === $message_type ) {
		?>
		<div class="rvex-flip-container">
			<div class="rvex-flip-wrapper" style="<?php echo esc_attr( $paragraph_wrapper_style ); ?>">
					<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $flip_first_message ), 'rvex-flip-text', 'div' ), revenue()->get_allowed_tag() ); ?>
					<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $flip_second_message ), 'rvex-flip-text', 'div' ), revenue()->get_allowed_tag() ); ?>
			</div>
		</div>
		<?php
	}
} elseif ( is_shop() && isset( $campaign['placement_settings']['shop_page'] ) && ! empty( $campaign['placement_settings']['shop_page'] ) && 'yes' === $campaign['placement_settings']['shop_page']['status'] ) {

	if ( 'generalMessage' === $message_type ) {
		?>
		<div style="<?php echo esc_attr( $paragraph_wrapper_style ); ?>">
			<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $general_message ), '', 'div' ), revenue()->get_allowed_tag() ); ?>
		</div>
		<?php
		if ( 'yes' === $enable_stock_bar ) {
			?>
			<div class="rvex-progress-container" style="<?php echo esc_attr( $progress_bar_style ); ?> height:8px; background:var(--revx-empty-color); display: flex; border-radius: 6px; max-width: 100%;">
				<div class="rvex-stock-bar" style="width: <?php echo esc_attr( $percentage ); ?>%; height: inherit; background: var(--revx-filled-color, #6E3FF3); border-radius: inherit; transition: width 0.5s;"></div>
			</div>
			<?php
		}
		?>
		<?php
	}
	?>
	<?php
	if ( 'flipMessage' === $message_type ) {
		?>
		<div class="rvex-flip-container">
			<div class="rvex-flip-wrapper" style="<?php echo esc_attr( $paragraph_wrapper_style ); ?>">
					<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $flip_first_message ), 'rvex-flip-text', 'div' ), revenue()->get_allowed_tag() ); ?>
					<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', esc_attr( $flip_second_message ), 'rvex-flip-text', 'div' ), revenue()->get_allowed_tag() ); ?>
			</div>
		</div>
		<?php
	}
}
?>
</div>
<input type="hidden" name="<?php echo esc_attr( 'revx-stock-scarcity-data-' . $campaign['id'] ); ?>" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $this->stock_scarcity_hidden_data( $campaign ) ) ) ); ?>" />
