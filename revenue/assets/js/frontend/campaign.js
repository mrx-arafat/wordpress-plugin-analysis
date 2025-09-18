( function ( $ ) {
	'use strict';

	function calculateCurrentPrice( offerData, productId, quantity ) {
		const product = offerData[ productId ];

		if ( ! product ) {
			throw new Error( 'Product not found' );
		}

		const regularPrice = parseFloat( product.regular_price );
		let currentPrice = regularPrice;

		if ( product.offer && Array.isArray( product.offer ) ) {
			for ( let i = 0; i < product.offer.length; i++ ) {
				const offer = product.offer[ i ];
				const minQty = parseInt( offer.qty, 10 );

				if ( offer.type == 'free' ) {
					if ( quantity >= minQty ) {
						switch ( offer.type ) {
							case 'free':
								currentPrice = 0;

								break;

							default:
								break;
						}
						// Add more offer types if needed (e.g., fixed discount)
					}
				} else if ( quantity >= minQty ) {
					switch ( offer.type ) {
						case 'percentage':
							currentPrice =
								regularPrice -
								( parseFloat( offer.value ) / 100 ) *
									regularPrice;
							break;
						case 'amount':
						case 'fixed_discount':
							currentPrice =
								regularPrice - parseFloat( offer.value );

							break;
						case 'fixed_price':
							currentPrice = parseFloat( offer.value );

							break;
						case 'no_discount':
							currentPrice = regularPrice;

							break;
						case 'free':
							currentPrice = 0;

							break;

						default:
							break;
					}
					// Add more offer types if needed (e.g., fixed discount)
				}
			}
		}

		return parseFloat( currentPrice * quantity );
	}

	const formatPrice = ( price ) => {
		const currencyFormat = revenue_campaign?.currency_format;
		const currencySymbol = revenue_campaign?.currency_format_symbol;
		const decimalSeparator = revenue_campaign?.currency_format_decimal_sep;
		const thousandSeparator =
			revenue_campaign?.currency_format_thousand_sep;
		const numDecimals = revenue_campaign?.currency_format_num_decimals;

		const fixedPrice = parseFloat( price ).toFixed( numDecimals );

		const parts = fixedPrice.split( '.' );
		let integerPart = parts[ 0 ];
		const decimalPart = parts[ 1 ];

		integerPart = integerPart.replace(
			/\B(?=(\d{3})+(?!\d))/g,
			thousandSeparator
		);

		const formattedPrice = integerPart + decimalSeparator + decimalPart;

		return currencyFormat
			.replace( '%1$s', currencySymbol )
			.replace( '%2$s', formattedPrice );
	};

	// Volume Discount
	$( '.revx-volume-discount .revx-campaign-item' ).on(
		'click',
		function ( e ) {
			// Remove selected style from all items
			e.stopPropagation();
			const that = $( this );
			$( '.revx-volume-discount .revx-campaign-item' ).each( function () {
				const item = $( this ).find( '.revx-volume-discount__tag' );
				const defaultStyle = item.data( 'default-style' );
				$( item ).attr( 'style', defaultStyle );
				$( this ).attr( 'data-revx-selected', false );
			} );
			const clickedItem = that.find( '.revx-volume-discount__tag' );

			// Apply selected style to the clicked item
			const selectedStyle = clickedItem.data( 'selected-style' );
			clickedItem.attr( 'style', selectedStyle );
			that.attr( 'data-revx-selected', true );

			$( '.revx-ticket-type' ).trigger( 'change' );
		}
	);

	$( 'select.revx-productAttr-wrapper__field' ).on( 'change', function () {
		const attributeData =
			$( '.variations_form' ).data( 'product_variations' );

		const attributeName = $( this ).data( 'attribute_name' );

		const parentWrapper = $( this ).closest( '.revx-productAttr-wrapper' );
		const fieldsInParent = parentWrapper.find(
			'.revx-productAttr-wrapper__field'
		);

		let allFieldsHaveValue = true;
		const values = {};
		fieldsInParent.each( function () {
			if ( $( this ).val() === '' ) {
				allFieldsHaveValue = false;
				return false; // Break the loop
			}
			values[ $( this ).data( 'attribute_name' ) ] = $( this ).val();
		} );

		let selectedVariation = false;

		if ( allFieldsHaveValue ) {
			attributeData.forEach( ( element ) => {
				if (
					JSON.stringify( element.attributes ) ==
					JSON.stringify( values )
				) {
					selectedVariation = element;
				}
			} );
		}

		$( '.revx-campaign-item' ).removeAttr( 'data-product-id' );
		if ( selectedVariation ) {
			const parent = $( this ).closest( '.revx-campaign-item' );
			const campaignID = parent.data( 'campaignId' );
			const quantity = parent.data( 'quantity' );

			const offerData = JSON.parse(
				$( `input[name="revx-offer-data-${ campaignID }"]` ).val()
			);

			const variation_id = selectedVariation.variation_id;

			const offer = offerData[ variation_id ];

			let nearestOffer = offer.offer[ 0 ];
			for ( let i = 1; i < offer.offer.length; i++ ) {
				if ( offer.offer[ i ].qty <= quantity ) {
					nearestOffer = offer.offer[ i ];
				} else {
					break;
				}
			}

			let regular_price = offer.regular_price;
			let sale_price = '';

			switch ( nearestOffer.type ) {
				case 'percentage':
					sale_price =
						parseFloat( offer.regular_price * nearestOffer.qty ) *
						( 1 - nearestOffer.value / 100 );

					break;
				case 'amount':
				case 'fixed_discount':
					sale_price =
						Math.max(
							0,
							parseFloat( offer.regular_price ) -
								parseFloat( nearestOffer.value )
						) * nearestOffer.qty;

					break;
				case 'fixed_price':
					sale_price =
						parseFloat( nearestOffer.value ) * nearestOffer.qty;
					regular_price = false;

					break;
				case 'no_discount':
					sale_price =
						parseFloat( offer.regular_price ) * nearestOffer.qty;
					regular_price = false;

					break;
				case 'free':
					sale_price = 0;

					break;

				default:
					break;
			}

			parent.attr( 'data-product-id', variation_id );

			parent
				.parent()
				.parent()
				.find( '.revx-campaign-add-to-cart-btn' )
				.attr( 'data-product-id', variation_id );

			parent.find( 'input[data-name=revx_quantity]' ).trigger( 'change' );

			parent
				.find( '.revx-campaign-item__regular-price' )
				.html( formatPrice( regular_price * quantity ) );
			parent
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( sale_price ) );
		} else {
			const parent = $( this ).closest( '.revx-campaign-item' );
			parent.find( '.revx-campaign-item__regular-price' ).html( '' );
			parent.find( '.revx-campaign-item__sale-price' ).html( '' );
		}
	} );

	function updatePriceDisplay( parent, quantity, salePrice, regularPrice ) {
		salePrice = parseFloat( salePrice );
		regularPrice = parseFloat( regularPrice );
		const salePriceElement = $( parent ).find(
			'.revx-campaign-item__sale-price'
		);
		const regularPriceElement = $( parent ).find(
			'.revx-campaign-item__regular-price'
		);
		const savingsTag = $( parent ).find( '.revx-builder-savings-tag' );

		if ( quantity == 0 ) {
			salePriceElement.html( formatPrice( 0 ) );
			savingsTag.hide();
			return;
		}

		if ( salePrice !== regularPrice ) {
			salePriceElement.html(
				quantity > 1
					? `${ quantity } x ` + formatPrice( salePrice )
					: formatPrice( salePrice )
			);
			regularPriceElement.html(
				quantity > 1
					? `${ quantity } x ` + formatPrice( regularPrice )
					: formatPrice( regularPrice )
			);
			savingsTag.show();
		} else {
			salePriceElement.html(
				quantity > 1
					? `${ quantity } x ` + formatPrice( regularPrice )
					: formatPrice( regularPrice )
			);
			regularPriceElement.empty();
			savingsTag.hide();
		}
	}

	$(
		'.revx-bundle-discount .revx-builder__quantity input[data-name=revx_quantity]'
	).on( 'change', function () {
		const parent = $( this ).closest( '.revx-campaign-container__wrapper' );
		const bundle_products = $( this )
			.closest( '.revx-campaign-container__wrapper' )
			.data( 'bundle_products' );

		if ( bundle_products.length == 0 ) {
			return;
		}

		const quantity = $( this ).val();

		parent
			.find( '.revenue-campaign-add-bundle-to-cart' )
			.attr( 'data-quantity', quantity );

		const campaignId = $( this ).data( 'campaign-id' ); // Get data-campaign-id attribute value

		let offerData = $( `input[name="revx-offer-data-${ campaignId }"]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		let totalRegularPrice = 0;
		let totalSalePrice = 0;

		bundle_products.forEach( ( product ) => {
			const productId = product.item_id;
			// let quantity = product.quantity;

			if ( jsonData[ productId ] ) {
				totalRegularPrice += parseFloat(
					(
						jsonData[ productId ].regular_price *
						quantity *
						product.quantity
					).toFixed( 2 )
				);
				totalSalePrice += parseFloat(
					calculateCurrentPrice(
						jsonData,
						productId,
						product.quantity * quantity
					).toFixed( 2 )
				);
			}
		} );

		if ( totalRegularPrice != totalSalePrice ) {
			$( parent )
				.find(
					'.revx-total-price__offer-price .revx-campaign-item__sale-price'
				)
				.html( formatPrice( totalSalePrice ) );
			$( parent )
				.find(
					'.revx-total-price__offer-price .revx-campaign-item__regular-price'
				)
				.html( formatPrice( totalRegularPrice ) );
		} else {
			$( parent )
				.find(
					'.revx-total-price__offer-price .revx-campaign-item__regular-price'
				)
				.html( formatPrice( totalRegularPrice ) );
		}
	} );

	$(
		'.revx-volume-discount .revx-builder__quantity input[data-name=revx_quantity]'
	).on( 'change', function () {
		const parent = $( this ).closest( '.revx-campaign-item' );
		const product_id = $( this )
			.closest( '.revx-campaign-item' )
			.data( 'product-id' );

		if ( ! product_id ) {
			return;
		}

		const quantity = $( this ).val();

		parent
			.parent()
			.parent()
			.find( '.revx-campaign-add-to-cart-btn' )
			.attr( 'data-quantity', quantity );

		const campaignId = $( this ).data( 'campaign-id' ); // Get data-campaign-id attribute value

		let offerData = $( `input[name="revx-offer-data-${ campaignId }"]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const regularPrice = (
			jsonData[ product_id ].regular_price * quantity
		).toFixed( 2 );
		const salePrice = calculateCurrentPrice(
			jsonData,
			product_id,
			quantity
		).toFixed( 2 );

		if ( salePrice != regularPrice ) {
			$( parent )
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( salePrice ) );
			$( parent )
				.find( '.revx-campaign-item__regular-price' )
				.html( formatPrice( regularPrice ) );
		} else {
			$( parent )
				.find( '.revx-campaign-item__regular-price' )
				.html( formatPrice( salePrice ) );
		}
	} );

	$(
		'.revx-normal-discount .revx-builder__quantity input[data-name=revx_quantity]'
	).on( 'change', function () {
		const parent = $( this ).closest( '.revx-campaign-item' );
		const product_id = $( this )
			.closest( '.revx-campaign-item' )
			.data( 'product-id' );

		if ( ! product_id ) {
			return;
		}

		const quantity = $( this ).val();

		parent
			.find( '.revx-campaign-add-to-cart-btn' )
			.attr( 'data-quantity', quantity );
		const campaignId = $( this ).data( 'campaign-id' ); // Get data-campaign-id attribute value

		let offerData = $( `input[name="revx-offer-data-${ campaignId }"]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		if ( ! jsonData[ product_id ] ) {
			return;
		}

		const inRP = jsonData[ product_id ].regular_price;

		const regularPrice = (
			jsonData[ product_id ].regular_price * quantity
		).toFixed( 2 );

		const salePrice = calculateCurrentPrice(
			jsonData,
			product_id,
			quantity
		).toFixed( 2 );

		const inSP = ( salePrice / quantity ).toFixed( 2 );

		updatePriceDisplay( parent, quantity, inSP, inRP );

		// if (salePrice != regularPrice) {
		//     $(parent).find('.revx-campaign-item__sale-price').html(`${quantity} x `+formatPrice(inSP));
		//     $(parent).find('.revx-campaign-item__regular-price').html(`${quantity} x `+formatPrice(inRP));
		// } else {
		//     $(parent).find('.revx-campaign-item__sale-price').html(`${quantity} x `+formatPrice(inRP));
		// }
	} );

	// Delegate click event for the 'minus' button
	$( document ).on( 'click', '.revx-quantity-minus', function ( e ) {
		e.preventDefault();
		e.stopPropagation(); // Stop event propagation to parent elements
		const $input = $( this ).siblings( 'input[type="number"]' );

		if ( $( this ).data( 'skip-global' ) ) {
			return;
		}

		if ( ! $input ) {
			return;
		}
		$input.focus(); // Focus on the input field after updating its value

		const currentValue = parseInt( $input.val(), 10 );
		const min = $input.attr( 'min' );

		if ( min && currentValue - 1 >= min ) {
			if ( ! isNaN( currentValue ) && currentValue > 0 ) {
				$input.val( currentValue - 1 );
			}
		}
		// else if ( ! isNaN( currentValue ) && currentValue - 1 > 0 ) {
		// 	$input.val( currentValue - 1 );
		// }

		$input.trigger( 'change' );
	} );

	// Delegate click event for the 'plus' button
	$( document ).on( 'click', '.revx-quantity-plus', function ( e ) {
		e.preventDefault();
		e.stopPropagation(); // Stop event propagation to parent elements

		// Skip if it has data-skip-global attribute
		if ( $( this ).data( 'skip-global' ) ) {
			return;
		}

		const $input = $( this ).siblings( 'input[type="number"]' );
		if ( ! $input.length ) {
			return;
		}
		$input.focus(); // Focus on the input field after updating its value

		const currentValue = parseInt( $input.val(), 10 );
		const maxValue = parseInt( $input.attr( 'max' ), 10 ); // Get the max attribute value

		if ( ! isNaN( currentValue ) ) {
			// Check if current value is less than the max value
			if ( ! isNaN( maxValue ) && currentValue < maxValue ) {
				$input.val( currentValue + 1 );
			} else if ( isNaN( maxValue ) ) {
				$input.val( currentValue + 1 ); // No max set, just increment
			}
		} else {
			$input.val( 1 ); // Set default value if current value is not a number
		}

		$input.trigger( 'change' );
	} );

	// Delegate input event for the quantity input field
	$( document ).on(
		'input',
		'input[data-name=revx_quantity]',
		function ( e ) {
			const minVal = parseInt( $( this ).attr( 'min' ) ) || 0; // Default to 0 if min is not set
			const maxVal = parseInt( $( this ).attr( 'max' ) ); // Parse max value from the attribute
			const val = parseInt( $( this ).val() ); // Get the current value of the input

			// If the current value is less than min, set it to min
			if ( val < minVal ) {
				$( this ).val( minVal );
			}

			// If the current value is greater than max, set it to max
			if ( ! isNaN( maxVal ) && val > maxVal ) {
				$( this ).val( maxVal );
			}
			// Trigger change event after updating the value
			$( this ).trigger( 'change' );
		}
	);

	function getCookie( cname ) {
		const name = 'revx_' + cname + '=';
		const decodedCookie = decodeURIComponent( document.cookie );
		const ca = decodedCookie.split( ';' );
		for ( let i = 0; i < ca.length; i++ ) {
			let c = ca[ i ];
			while ( c.charAt( 0 ) == ' ' ) {
				c = c.substring( 1 );
			}
			if ( c.indexOf( name ) == 0 ) {
				return c.substring( name.length, c.length );
			}
		}
		return '';
	}

	// Function to set the cookie
	function setCookie( name, value, days ) {
		const date = new Date();
		date.setTime( date.getTime() + days * 24 * 60 * 60 * 1000 );
		const expires = 'expires=' + date.toUTCString();
		document.cookie =
			'revx_' +
			name +
			'=' +
			encodeURIComponent( value ) +
			';' +
			expires +
			';path=/';
	}

	// Mix Match
	$( '.revx-mix-match button.revx-builder-add-btn' ).on(
		'click',
		function ( e ) {
			e.preventDefault();
			const campaign_id = $( this ).data( 'campaign-id' );
			const product_id = $( this ).data( 'product-id' );
			const item = $( this ).closest( '.revx-campaign-item' );

			const quantity =
				item.find( `input[data-name="revx_quantity"]` ).val() ?? 1;

			const offerData = $(
				`input[name="revx-offer-data-${ campaign_id }"]`
			).val();
			const jsonData = JSON.parse( offerData );

			const qtyData = $(
				`input[name="revx-qty-data-${ campaign_id }"]`
			).val();
			const jsonQtyData = JSON.parse( qtyData );

			const data = {
				id: product_id,
				productName: jsonData[ product_id ].item_name,
				regularPrice: jsonData[ product_id ].regular_price,
				thumbnail: jsonData[ product_id ].thumbnail,
				quantity,
			};

			const cookieName = `mix_match_${ campaign_id }`;
			let prevData = getCookie( cookieName );

			let prevSelectedItems = $(
				`input[name="revx-selected-items-${ campaign_id }"]`
			).val();

			prevSelectedItems = prevSelectedItems
				? JSON.parse( prevSelectedItems )
				: {};

			prevData = prevData ? JSON.parse( prevData ) : {};

			if ( Object.keys( prevData ).length == 0 ) {
				prevData = { ...prevSelectedItems };
			}

			if ( prevData[ product_id ] ) {
				prevData[ product_id ].quantity =
					parseInt( prevData[ product_id ].quantity ) +
					parseInt( quantity );

				$(
					`.revx-selected-item[data-campaign-id=${ campaign_id }][data-product-id=${ product_id }] .revx-selected-item__product-price`
				).html(
					`${ prevData[ product_id ].quantity } x ${ formatPrice(
						data.regularPrice
					) }`
				);
			} else {
				const container = $(
					`.revx-campaign-${ campaign_id } .revx-selected-product-container`
				);

				if ( container.hasClass( 'revx-d-none' ) ) {
					container.removeClass( 'revx-d-none' );
				}
				if ( container.hasClass( 'revx-empty-selected-items' ) ) {
					container.removeClass( 'revx-empty-selected-items' );
				}

				$(
					`.revx-campaign-${ campaign_id } .revx-empty-mix-match`
				).addClass( 'revx-d-none' );

				prevData[ product_id ] = data;

				const placeholderItem = $(
					`.revx-selected-item.revx-d-none[data-campaign-id=${ campaign_id }]`
				);
				const clonedItem = placeholderItem.clone();
				clonedItem
					.find( '.revx-selected-item__product-title' )
					.html( data.productName );
				clonedItem
					.find( '.revx-campaign-item__image img' )
					.attr( 'src', data.thumbnail );
				clonedItem
					.find( '.revx-campaign-item__image img' )
					.attr( 'alt', data.productName );
				clonedItem
					.find( '.revx-selected-item__product-price' )
					.html(
						`${ quantity } x ${ formatPrice( data.regularPrice ) }`
					);
				clonedItem.removeClass( 'revx-d-none' );
				clonedItem.attr( 'data-product-id', product_id );
				placeholderItem.before( clonedItem );
			}

			$( `input[name="revx-selected-items-${ campaign_id }"]` ).val(
				JSON.stringify( prevData )
			);

			setCookie( cookieName, JSON.stringify( prevData ), 7 );

			updateMixMatchHeaderAndPrices( campaign_id, prevData, jsonQtyData );

			$( this )
				.parent()
				.find( `input[data-name="revx_quantity"]` )
				.val( 1 );
		}
	);

	function removeMixMatchSelectedItem( e ) {
		const product_id = $( this )
			.closest( '[data-product-id]' )
			.data( 'product-id' );
		const campaign_id = $( this )
			.closest( '[data-campaign-id]' )
			.data( 'campaign-id' );
		const item = $(
			`.revx-selected-item[data-campaign-id=${ campaign_id }][data-product-id="${ product_id }"]`
		);

		item.remove();

		const cookieName = `mix_match_${ campaign_id }`;
		let prevData = getCookie( cookieName );

		let prevSelectedItems = $(
			`input[name=revx-selected-items-${ campaign_id }]`
		).val();
		prevSelectedItems = prevSelectedItems
			? JSON.parse( prevSelectedItems )
			: {};

		prevData = prevData ? JSON.parse( prevData ) : {};

		if ( Object.keys( prevData ).length == 0 ) {
			prevData = prevSelectedItems;
		}

		delete prevData[ product_id ];
		setCookie( cookieName, JSON.stringify( prevData ), 7 );
		$( `input[name=revx-selected-items-${ campaign_id }]` ).val(
			JSON.stringify( prevData )
		);

		const qtyData = $( `input[name=revx-qty-data-${ campaign_id }]` ).val();
		const jsonQtyData = JSON.parse( qtyData );

		if ( Object.keys( prevData ).length == 0 ) {
			$(
				`.revx-campaign-${ campaign_id } .revx-empty-selected-products`
			).removeClass( 'revx-d-none' );
			$(
				`.revx-campaign-${ campaign_id } .revx-selected-product-container`
			).addClass( 'revx-empty-selected-items' );
			$(
				`.revx-campaign-${ campaign_id } .revx-empty-mix-match`
			).removeClass( 'revx-d-none' );
		}

		updateMixMatchHeaderAndPrices( campaign_id, prevData, jsonQtyData );
	}

	$( '.revx-mix-match' ).on(
		'click',
		'.revx-remove-selected-item',
		removeMixMatchSelectedItem
	);

	function updateMixMatchHeaderAndPrices(
		campaign_id,
		prevData,
		jsonQtyData = {}
	) {
		const header = $(
			`.revx-campaign-${ campaign_id } .revx-selected-product-header`
		);
		const item_counts = Object.keys( prevData ).length;
		const qtyData = $( `input[name=revx-qty-data-${ campaign_id }]` ).val();
		jsonQtyData = qtyData ? JSON.parse( qtyData ) : [];

		if ( item_counts !== 0 && header.hasClass( 'revx-d-none' ) ) {
			header.removeClass( 'revx-d-none' );
		} else if ( item_counts == 0 ) {
			header.addClass( 'revx-d-none' );
		}
		// const addToCart = $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`);
		// if(item_counts==0 && !addToCart.hasClass('revx-d-none') ) {
		//     $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`).addClass('revx-d-none');
		// } else if(item_counts>0) {
		//     $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`).removeClass('revx-d-none');
		// }
		header.find( '.revx-selected-product-count' ).html( item_counts );

		let totalRegularPrice = 0;
		let totalSalePrice = 0;

		Object.values( prevData ).forEach( ( item ) => {
			totalRegularPrice +=
				parseFloat( item.regularPrice ) * parseInt( item.quantity );
		} );

		let selectedIndex = -1;
		jsonQtyData.forEach( ( item, idx ) => {
			if ( item_counts >= item.quantity ) {
				selectedIndex = idx;

				switch ( item.type ) {
					case 'percentage':
						totalSalePrice =
							totalRegularPrice * ( 1 - item.value / 100 );
						break;
					case 'fixed_discount':
						totalSalePrice = Math.max(
							0,
							parseFloat( totalRegularPrice ) -
								parseFloat( item.value * item?.quantity )
						);

						break;
					case 'no_discount':
						totalSalePrice = totalRegularPrice;

						break;

					default:
						break;
				}
			}
		} );

		$(
			`.revx-mix-match-${ campaign_id } .revx-mixmatch-quantity div[data-index=${ selectedIndex }]`
		).attr(
			'style',
			$(
				`.revx-mix-match-${ campaign_id } .revx-mixmatch-quantity div[data-index=${ selectedIndex }]`
			).data( 'selected-style' )
		);

		const that = $(
			`.revx-campaign-${ campaign_id } .revx-mixmatch-quantity`
		);
		that.each( function () {
			const item = $( this ).find( '.revx-mixmatch-regular-quantity' );
			const defaultStyle = item.data( 'default-style' );
			$( item ).attr( 'style', defaultStyle );

			$( item )
				.find( '.revx-builder-checkbox' )
				.addClass( 'revx-d-none' );
		} );

		const clickedItem = that.find( `div[data-index=${ selectedIndex }]` );
		const selectedStyle = clickedItem.data( 'selected-style' );
		clickedItem.attr( 'style', selectedStyle );
		$( clickedItem )
			.find( '.revx-builder-checkbox' )
			.removeClass( 'revx-d-none' );

		if ( totalSalePrice == 0 ) {
			header
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( totalRegularPrice ) );
		} else {
			if (
				totalSalePrice &&
				header
					.find( '.revx-campaign-item__sale-price' )
					.hasClass( 'revx-d-none' )
			) {
				header
					.find( '.revx-campaign-item__sale-price' )
					.removeClass( 'revx-d-none' );
			}
			if (
				totalRegularPrice &&
				header
					.find( '.revx-campaign-item__regular-price' )
					.hasClass( 'revx-d-none' )
			) {
				header
					.find( '.revx-campaign-item__regular-price' )
					.removeClass( 'revx-d-none' );
			}

			header
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( totalSalePrice ) );
			header
				.find( '.revx-campaign-item__regular-price' )
				.html( formatPrice( totalRegularPrice ) );
		}
	}

	// Frequently Bought Together

	// Function to update the styles based on selection
	function updateStyles( $checkbox, selected ) {
		const selectedStyles = $checkbox.data( 'selected-style' );
		const defaultStyles = $checkbox.data( 'default-style' );
		if ( selected ) {
			$checkbox.attr( 'style', selectedStyles );
		} else {
			$checkbox.attr( 'style', defaultStyles );
		}
	}
	$( '.revx-frequently-bought-together' ).on(
		'click',
		'.revx-item-options .revx-item-option',
		function ( e ) {
			e.preventDefault();

			const $this = $( this );
			if ( $this.hasClass( 'revx-item-required' ) ) {
				return;
			}
			const $checkbox = $this.find( '.revx-builder-checkbox' );

			const parent = $this.closest( '.revx-campaign-container__wrapper' );

			const campaign_id = parent.data( 'campaign-id' );
			const cookieName = `campaign_${ campaign_id }`;
			let selectedProducts = getCookie( cookieName );
			let prevSelectedItems = $(
				`input[name=revx-fbt-selected-items-${ campaign_id }]`
			).val();
			prevSelectedItems = prevSelectedItems
				? JSON.parse( prevSelectedItems )
				: {};
			selectedProducts = selectedProducts
				? JSON.parse( selectedProducts )
				: {};
			if ( Object.keys( selectedProducts ) == 0 ) {
				selectedProducts = { ...prevSelectedItems };
			}

			const productId = $this.data( 'product-id' );

			// Toggle the selected state
			if ( selectedProducts[ productId ] ) {
				// selectedProducts = selectedProducts.filter(id => id !== productId);
				delete selectedProducts[ productId ];
				updateStyles( $checkbox, false );
			} else {
				const quantityInput =
					$(
						`input[name="revx-quantity-${ campaign_id }-${ productId }"]`
					).val() ?? $this.data( 'min-quantity' );

				selectedProducts[ productId ] = quantityInput;
				updateStyles( $checkbox, true );
			}
			$( `input[name="revx-fbt-selected-items-${ campaign_id }"]` ).val(
				JSON.stringify( selectedProducts )
			);

			// Update the cookie
			setCookie( cookieName, JSON.stringify( selectedProducts ), 1 );

			fbtCalculation( parent, campaign_id );
		}
	);

	const fbtCalculation = ( parent, campaign_id ) => {
		const cookieName = `campaign_${ campaign_id }`;
		let selectedProducts = getCookie( cookieName );
		let prevSelectedItems = $(
			`input[name=revx-fbt-selected-items-${ campaign_id }]`
		).val();
		prevSelectedItems = prevSelectedItems
			? JSON.parse( prevSelectedItems )
			: {};
		selectedProducts = selectedProducts
			? JSON.parse( selectedProducts )
			: {};
		if ( Object.keys( selectedProducts ) == 0 ) {
			selectedProducts = { ...prevSelectedItems };
		}

		const calculateSalePrice = ( data, qty = 1 ) => {
			if ( ! data?.type ) {
				return data.regular_price * qty;
			}
			let total = 0;
			switch ( data.type ) {
				case 'percentage':
					total =
						parseFloat( data.regular_price ) *
						( 1 - data.value / 100 );

					break;
				case 'amount':
				case 'fixed_discount':
					total = Math.max(
						0,
						parseFloat( data.regular_price ) -
							parseFloat( data.value )
					);

					break;
				case 'fixed_price':
					total = parseFloat( data.value );

					break;
				case 'no_discount':
					total = parseFloat( data.regular_price );

					break;
				case 'free':
					total = 0;

					break;

				default:
					break;
			}

			return parseFloat( total ) * parseInt( qty );
		};
		let offerData = $(
			`input[name=revx-offer-data-${ campaign_id }]`
		).val();

		offerData = JSON.parse( offerData );

		let totalRegularPrice = 0;
		let totalSalePrice = 0;

		Object.keys( selectedProducts ).forEach( ( id ) => {
			totalRegularPrice +=
				parseFloat( offerData[ id ]?.regular_price ) *
				parseInt( selectedProducts[ id ] );
			totalSalePrice += parseFloat(
				calculateSalePrice(
					offerData[ id ],
					parseInt( selectedProducts[ id ] )
				)
			);
		} );

		if ( totalRegularPrice != totalSalePrice ) {
			parent
				.find(
					`.revx-triggerProduct .revx-campaign-item__regular-price`
				)
				.html( formatPrice( totalRegularPrice ) );
			parent
				.find( `.revx-triggerProduct .revx-campaign-item__sale-price` )
				.html( formatPrice( totalSalePrice ) );
		} else {
			parent
				.find( `.revx-triggerProduct .revx-campaign-item__sale-price` )
				.html( formatPrice( totalSalePrice ) );
			parent
				.find(
					`.revx-triggerProduct .revx-campaign-item__regular-price`
				)
				.html( '' );
		}
		parent
			.find( `.revx-triggerProduct .revx-selected-product-count` )
			.html( Object.keys( selectedProducts ).length );
	};

	$( '.revx-frequently-bought-together' ).on(
		'change',
		'input[data-name=revx_quantity]',
		function ( e ) {
			e.preventDefault();
			const parent = $( this ).closest(
				'.revx-campaign-container__wrapper'
			);

			const quantity = $( this ).val();

			const campaign_id = parent.data( 'campaign-id' );

			// addFbtRequiredProductsIfNotAdded(campaign_id,false);
			const product_id = $( this ).data( 'product-id' );
			const cookieName = `campaign_${ campaign_id }`;

			let selectedProducts = getCookie( cookieName );
			let prevSelectedItems = $(
				`input[name=revx-fbt-selected-items-${ campaign_id }]`
			).val();
			prevSelectedItems = prevSelectedItems
				? JSON.parse( prevSelectedItems )
				: {};
			selectedProducts = selectedProducts
				? JSON.parse( selectedProducts )
				: {};
			if ( Object.keys( selectedProducts ) == 0 ) {
				selectedProducts = { ...prevSelectedItems };
			}

			if ( selectedProducts[ product_id ] ) {
				selectedProducts[ product_id ] = quantity;

				setCookie( cookieName, JSON.stringify( selectedProducts ), 1 );
				fbtCalculation( parent, campaign_id );
			}

			$( `input[name=revx-fbt-selected-items-${ campaign_id }]` ).val(
				JSON.stringify( selectedProducts )
			);

			const calculateSalePrice = ( data, qty = 1 ) => {
				if ( ! data?.type ) {
					return data.regular_price * qty;
				}
				let total = 0;
				switch ( data.type ) {
					case 'percentage':
						total =
							parseFloat( data.regular_price ) *
							( 1 - data.value / 100 );

						break;
					case 'amount':
					case 'fixed_discount':
						total = Math.max(
							0,
							parseFloat( data.regular_price ) -
								parseFloat( data.value )
						);

						break;
					case 'fixed_price':
						total = parseFloat( data.value );

						break;
					case 'no_discount':
						total = parseFloat( data.regular_price );

						break;
					case 'free':
						total = 0;

						break;

					default:
						break;
				}

				return parseFloat( total ) * parseInt( qty );
			};
			let offerData = $( `input[name=revx-offer-data-${ campaign_id }]` );

			offerData = offerData[ 0 ].value;
			const jsonData = JSON.parse( offerData );

			const salePrice = calculateSalePrice(
				jsonData[ product_id ],
				quantity
			).toFixed( 2 );

			const inRP = jsonData[ product_id ].regular_price;

			const inSP = ( salePrice / quantity ).toFixed( 2 );

			const itemParent = $( this ).closest( '.revx-campaign-item' );

			updatePriceDisplay( itemParent, quantity, inSP, inRP );
		}
	);

	$( '.revx-buyx-gety' ).on(
		'change',
		'input[data-name=revx_quantity]',
		function ( e ) {
			e.preventDefault();
			const parent = $( this ).closest( '.revx-campaign-container' );

			const quantity = $( this ).val();

			const campaign_id = parent.data( 'campaign-id' );

			const product_id = $( this ).data( 'product-id' );

			let offerData = $( `input[name=revx-offer-data-${ campaign_id }]` );

			offerData = offerData[ 0 ].value;
			const jsonData = JSON.parse( offerData );

			const regularPrice = (
				jsonData[ product_id ].regular_price * quantity
			).toFixed( 2 );
			const salePrice = calculateCurrentPrice(
				jsonData,
				product_id,
				quantity
			).toFixed( 2 );

			const item = $( this ).closest( '.revx-campaign-item__content' );

			item.find( '.revx-campaign-item__regular-price' ).text(
				formatPrice( regularPrice )
			);
			item.find( '.revx-campaign-item__sale-price' ).text(
				formatPrice( salePrice )
			);

			if ( regularPrice == salePrice ) {
				if (
					! item
						.find( '.revx-campaign-item__regular-price' )
						.hasClass( 'revx-d-none' )
				) {
					item.find( '.revx-campaign-item__regular-price' ).addClass(
						'revx-d-none'
					);
				}
			} else if (
				item
					.find( '.revx-campaign-item__regular-price' )
					.hasClass( 'revx-d-none' )
			) {
				item.find( '.revx-campaign-item__regular-price' ).removeClass(
					'revx-d-none'
				);
			}

			let totalRegularPrice = 0;
			let totalSalePrice = 0;

			Object.keys( jsonData ).forEach( ( pid ) => {
				const qty = $(
					`input[name="revx-quantity-${ campaign_id }-${ pid }"]`
				).val();
				const rp = ( jsonData[ pid ].regular_price * qty ).toFixed( 2 );
				const sp = parseFloat(
					calculateCurrentPrice( jsonData, pid, qty ).toFixed( 2 )
				);
				totalRegularPrice += parseFloat( rp );
				totalSalePrice += parseFloat( sp );
			} );

			parent
				.find( '.revx-total-price .revx-campaign-item__regular-price' )
				.html( formatPrice( totalRegularPrice ) );
			parent
				.find( '.revx-total-price .revx-campaign-item__sale-price' )
				.html( formatPrice( totalSalePrice ) );
		}
	);

	const padWithZero = ( num ) => num.toString().padStart( 2, '0' );

	// Countdown Timer-----------------

	// Declaration

	const countdown = () => {
		try {
			if ( revenue_campaign ) {
				const countDownData = Object.keys( revenue_campaign.data );

				countDownData.forEach( ( campaignID ) => {
					const _data =
						revenue_campaign?.data?.[ campaignID ]?.countdown_data;

					const startTime = _data?.start_time
						? new Date( _data.start_time ).getTime()
						: null;
					const endTime = _data?.end_time
						? new Date( _data.end_time ).getTime()
						: null;
					let now = new Date().getTime();

					if ( startTime && startTime > now ) {
						return; // Skip if the campaign hasn't started yet
					}

					if ( endTime < now ) {
						return; // Skip if the campaign has already ended
					}

					// Function to update the countdown timer
					const updateCountdown = function () {
						now = new Date().getTime();
						const distance = endTime - now;

						if ( distance < 0 ) {
							clearInterval( interval );
							$(
								`#revx-countdown-timer-${ campaignID }`
							).addClass( 'revx-d-none' ); // Hide the element
							return;
						}

						// Calculate days, hours, minutes, and seconds
						const days = Math.floor(
							distance / ( 1000 * 60 * 60 * 24 )
						);
						const hours = Math.floor(
							( distance % ( 1000 * 60 * 60 * 24 ) ) /
								( 1000 * 60 * 60 )
						);
						const minutes = Math.floor(
							( distance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 )
						);
						const seconds = Math.floor(
							( distance % ( 1000 * 60 ) ) / 1000
						);

						// Update the HTML elements
						$(
							`#revx-countdown-timer-${ campaignID } .revx-days`
						).text( padWithZero( days ) );
						$(
							`#revx-countdown-timer-${ campaignID } .revx-hours`
						).text( padWithZero( hours ) );
						$(
							`#revx-countdown-timer-${ campaignID } .revx-minutes`
						).text( padWithZero( minutes ) );
						$(
							`#revx-countdown-timer-${ campaignID } .revx-seconds`
						).text( padWithZero( seconds ) );
					};

					// Call the updateCountdown function initially to set the first values
					updateCountdown();

					// Update the countdown every second
					const interval = setInterval( updateCountdown, 1000 );

					// Show the countdown timer only after the initial values are set
					$( `#revx-countdown-timer-${ campaignID }` ).removeClass(
						'revx-d-none'
					);
				} );
			}
		} catch ( error ) {}
	};

	// Call
	countdown();

	//--------------- Countdown Timer

	// Slider -----------------------

	function checkOverflow( container ) {
		$( container ).each( function () {
			const $this = $( this );

			const isOverflowing =
				$this[ 0 ].scrollWidth > $this[ 0 ].offsetWidth;

			if ( isOverflowing ) {
				$( this )
					.find( '.revx-builderSlider-icon' )
					.addClass( 'revx-has-overflow' );
			} else {
				$( this )
					.find( '.revx-builderSlider-icon' )
					.removeClass( 'revx-has-overflow' );
			}
		} );
	}

	function initializeSlider(
		$sliderContainer,
		$containerSelector = '.revx-inpage-container',
		$campaign_type = ''
	) {
		const $container = $sliderContainer.closest( $containerSelector );
		const containerElement = $container.get( 0 );
		const computedStyle = getComputedStyle( containerElement );
		const gridColumnValue = computedStyle
			.getPropertyValue( '--revx-grid-column' )
			.trim();
		let itemGap = parseInt(
			computedStyle.getPropertyValue( 'gap' ).trim()
		);

		if ( ! itemGap ) {
			itemGap = 16;
		}

		const $slides = $sliderContainer.find( '.revx-campaign-item' );
		const minSlideWidth = 100; // 12rem in pixels (assuming 1rem = 16px)

		let containerWidth = $sliderContainer.parent().width();

		if ( $campaign_type == 'mix_match' ) {
			containerWidth = $sliderContainer
				.closest( '.revx-slider-items-wrapper' )
				.innerWidth();
		}
		if ( $campaign_type == 'bundle_discount' ) {
			containerWidth = $sliderContainer
				.closest( '.revx-slider-items-wrapper' )
				.outerWidth();
			itemGap = 0;
		}
		if ( $campaign_type == 'fbt' ) {
			containerWidth = $container
				.find( '.revx-slider-items-wrapper' )
				.innerWidth();
		}
		if ( $campaign_type == 'normal_discount' ) {
			containerWidth = $container
				.closest( '.revx-slider-items-wrapper' )
				.innerWidth();
			itemGap = 0;
		}

		let slidesVisible = Math.min(
			gridColumnValue,
			Math.floor( containerWidth / minSlideWidth )
		); // Calculate initial slides visible

		let slideWidth = containerWidth / slidesVisible;
		slideWidth -= itemGap;

		if ( $campaign_type == 'bundle_discount' ) {
			slideWidth -= $container
				.find( '.revx-builder__middle_element' )
				.width();
		}

		const totalSlides = $slides.length;
		let slideIndex = 0;

		function updateSlideWidth() {
			containerWidth = $sliderContainer
				.closest( '.revx-slider-items-wrapper' )
				.innerWidth();

			slidesVisible = Math.min(
				gridColumnValue,
				Math.floor( containerWidth / minSlideWidth )
			); // Recalculate slides visible
			slideWidth = containerWidth / slidesVisible;
			slideWidth -= itemGap;

			if ( $campaign_type == 'bundle_discount' ) {
				slideWidth -= $sliderContainer
					.find( '.revx-builder__middle_element' )
					.width();
			}

			$slides.css( 'width', slideWidth + 'px' );

			moveToSlide( slideIndex );
		}

		setTimeout( () => {
			updateSlideWidth();
		} );

		function moveToSlide( index ) {
			let tempWidth = slideWidth;
			if ( $campaign_type == 'fbt' ) {
				tempWidth += $sliderContainer
					.find( '.revx-product-bundle' )
					.width();
			}
			if ( $campaign_type == 'bundle_discount' ) {
				tempWidth += $sliderContainer
					.find( '.revx-builder__middle_element' )
					.width();
			}
			if ( $campaign_type == 'mix_match' ) {
				tempWidth += itemGap;
			}
			const offset = -tempWidth * index;

			$sliderContainer.css( {
				transition: 'transform 0.5s ease-in-out',
				transform: `translateX(${ offset }px)`,
			} );
		}

		function moveToNextSlide() {
			slideIndex++;

			if ( slideIndex > totalSlides - slidesVisible ) {
				slideIndex = 0;
			}

			moveToSlide( slideIndex );
		}

		function moveToPrevSlide() {
			slideIndex--;

			if ( slideIndex < 0 ) {
				slideIndex = totalSlides - slidesVisible;
			}

			moveToSlide( slideIndex );
		}

		$sliderContainer
			.siblings( '.revx-builderSlider-right' )
			.click( function () {
				if ( ! $sliderContainer.is( ':animated' ) ) {
					moveToNextSlide();
				}
			} );

		$sliderContainer
			.siblings( '.revx-builderSlider-left' )
			.click( function () {
				if ( ! $sliderContainer.is( ':animated' ) ) {
					moveToPrevSlide();
				}
			} );

		setTimeout( () => {
			// // const initialWidth = $sliderContainer.width();
			// $sliderContainer.width(containerWidth + 1); // Increase width by 1px
			// $sliderContainer.width(containerWidth); // Reset to original width
			$sliderContainer.parent().width( containerWidth ); // Reset to original width
			$sliderContainer.parent().width( containerWidth + 1 ); // Reset to original width
			$( window ).trigger( 'resize' ); // Trigger window resize
		} );

		$( window ).resize( function () {
			updateSlideWidth();
		} );

		$sliderContainer
			.closest( '.revx-inpage-container' )
			.css( 'visibility', 'visible' );
	}

	function buxXGetYSlider() {
		$( '.revx-inpage-container.revx-buyx-gety-grid' ).each( function () {
			const $container = $( this ).find(
				'.revx-campaign-container__wrapper'
			);
			const containerElement = $container.get( 0 );
			const computedStyle = getComputedStyle( containerElement );

			let gridColumnValue = parseInt(
				computedStyle.getPropertyValue( '--revx-grid-column' ).trim()
			);
			const minSlideWidth = 132; // 12rem in pixels (assuming 1rem = 16px)

			const $triggerItemContainer = $container.find(
				'.revx-bxgy-trigger-items'
			);
			const $offerItemContainer = $container.find(
				'.revx-bxgy-offer-items'
			);

			let triggerItemColumn = parseInt(
				getComputedStyle( $triggerItemContainer.get( 0 ) )
					.getPropertyValue( '--revx-grid-column' )
					.trim()
			);
			let offerItemColumn = parseInt(
				getComputedStyle( $offerItemContainer.get( 0 ) )
					.getPropertyValue( '--revx-grid-column' )
					.trim()
			);

			let containerWidth = $container.width();

			const seperatorWidth = $container
				.find( '.revx-product-bundle' )
				.width();

			containerWidth -= seperatorWidth - 16;

			gridColumnValue = gridColumnValue ? gridColumnValue : 4;

			gridColumnValue = Math.min(
				gridColumnValue,
				Math.floor( containerWidth / minSlideWidth )
			);
			triggerItemColumn = Math.min(
				$triggerItemContainer.find( '.revx-campaign-item' ).length,
				triggerItemColumn
			);
			offerItemColumn = Math.min(
				$offerItemContainer.find( '.revx-campaign-item' ).length,
				offerItemColumn
			);

			gridColumnValue = Math.min(
				gridColumnValue,
				triggerItemColumn + offerItemColumn
			);

			// gridColumnValue = gridColumnValue ? gridColumnValue : 4;

			// Ensure the total columns for trigger and offer items do not exceed the available grid columns
			if ( triggerItemColumn + offerItemColumn > gridColumnValue ) {
				const excessColumns =
					triggerItemColumn + offerItemColumn - gridColumnValue;

				// Adjust columns proportionally to ensure total columns match gridColumnValue
				const triggerAdjustment = Math.floor(
					( triggerItemColumn /
						( triggerItemColumn + offerItemColumn ) ) *
						excessColumns
				);
				const offerAdjustment = excessColumns - triggerAdjustment;

				triggerItemColumn -= triggerAdjustment;
				offerItemColumn -= offerAdjustment;
			}

			const slideWidth = containerWidth / gridColumnValue;

			initializeSubSlider(
				$triggerItemContainer,
				triggerItemColumn,
				slideWidth,
				'trigger'
			);
			initializeSubSlider(
				$offerItemContainer,
				offerItemColumn,
				slideWidth,
				'offer'
			);

			$( this ).css( 'visibility', 'visible' );
		} );
	}

	function initializeSubSlider(
		$sliderContainer,
		itemColumn,
		slideWidth,
		type
	) {
		const $container = $sliderContainer.find( '.revx-slider-container' );
		const itemGap = parseInt(
			getComputedStyle( $container.get( 0 ) )
				.getPropertyValue( 'gap' )
				.trim()
		);

		// slideWidth -=itemGap;
		slideWidth -= itemGap;
		const containerWidth = itemColumn * slideWidth;
		$sliderContainer.width( containerWidth );
		slideWidth -= 16;

		if ( type == 'offer' ) {
			slideWidth += itemGap;
		}

		$sliderContainer = $container;

		const $slides = $sliderContainer.find( '.revx-campaign-item' );
		$slides.css( { width: slideWidth + 'px' } );

		const totalSlides = $slides.length;
		let slideIndex = 0; // Start at the first slide

		function moveToSlide( index ) {
			let tempWidth = slideWidth;
			tempWidth += itemGap + 16;
			tempWidth += index;

			if ( itemColumn == 1 ) {
				tempWidth += itemGap;
			}

			if ( type == 'offer' ) {
				tempWidth -= 16;
			}

			const offset = -tempWidth * index;

			$sliderContainer.css( {
				transition: 'transform 0.5s ease-in-out',
				transform: `translateX(${ offset }px)`,
			} );
		}

		function moveToNextSlide() {
			slideIndex++;
			if ( slideIndex > totalSlides - itemColumn ) {
				slideIndex = 0;
			}
			moveToSlide( slideIndex );
		}

		function moveToPrevSlide() {
			slideIndex--;
			if ( slideIndex < 0 ) {
				slideIndex = totalSlides - itemColumn;
			}
			moveToSlide( slideIndex );
		}

		$sliderContainer
			.siblings( '.revx-builderSlider-right' )
			.click( function () {
				if ( ! $sliderContainer.is( ':animated' ) ) {
					moveToNextSlide();
				}
			} );

		$sliderContainer
			.siblings( '.revx-builderSlider-left' )
			.click( function () {
				if ( ! $sliderContainer.is( ':animated' ) ) {
					moveToPrevSlide();
				}
			} );

		$( window ).resize( function () {
			moveToSlide( slideIndex );
		} );

		moveToSlide( slideIndex );
	}

	buxXGetYSlider();

	$( window ).resize( function () {
		buxXGetYSlider();
	} );

	$(
		'.revx-inpage-container.revx-normal-discount-grid .revx-slider-container'
	).each( function () {
		initializeSlider(
			$( this ),
			'.revx-campaign-view__items',
			'normal_discount'
		);
	} );
	$(
		'.revx-inpage-container.revx-mix-match-grid .revx-slider-container'
	).each( function () {
		initializeSlider(
			$( this ),
			'.revx-campaign-view__items',
			'mix_match'
		);
	} );
	$(
		'.revx-inpage-container.revx-bundle-discount-grid .revx-slider-container'
	).each( function () {
		initializeSlider(
			$( this ),
			'.revx-campaign-view__items',
			'bundle_discount'
		);
	} );
	$(
		'.revx-inpage-container.revx-frequently-bought-together-grid .revx-slider-container'
	).each( function () {
		initializeSlider( $( this ), '.revx-inpage-container', 'fbt' );
	} );

	$( window ).on( 'load resize', function () {
		checkOverflow( '.revx-slider' );
	} );

	// ---------------- Slider

	// $('.revx-ticket-type')

	$( '.revx-ticket-type' ).change( function () {
		const selectedSlug = $( this ).val();
		const campaignID = $( this ).data( 'campaign-id' );
		const offerQty = $( this ).data( 'quantity' );

		const prices = $( this ).data( 'prices' );

		$( this )
			.closest( '.revx-campaign-item' )
			.find( '.revx-total-ticket-price' )
			.text(
				`Total Price: ${ formatPrice(
					prices[ selectedSlug ] * offerQty
				) }`
			);

		const selectedItem = $( this )
			.closest( '.revx-volume-discount' )
			.find( '.revx-campaign-item[data-revx-selected=true]' );

		if ( selectedItem.length ) {
			// Check if the selectedItem exists
			// selectedItem.data('data-selected-ticket', selectedSlug);
			selectedItem.attr( 'data-selected-ticket', selectedSlug );
			selectedItem.attr(
				'data-selected-ticket-price',
				prices[ selectedSlug ] * offerQty
			);
		}
	} );

	$( '.revx-ticket-type' ).trigger( 'change' );

	// Double Order

	const double_order_countdown = () => {
		try {
			// Find all countdown timer containers
			$( '.revx-double-order-countdown-timer-container' ).each(
				function () {
					const timer = $( this );
					const duration = parseInt(
						timer.data( 'countdown-duration' ),
						10
					);

					if ( isNaN( duration ) || duration <= 0 ) {
						console.error( 'Invalid countdown duration' );
						return;
					}

					// Set the initial end time
					const endTime = Date.now() + duration * 1000;

					// Function to update the countdown timer
					const updateCountdown = function () {
						const now = Date.now();
						const distance = endTime - now;

						if ( distance <= 0 ) {
							clearInterval( interval );
							timer
								.closest(
									'.revx-double-order-countdown-timer-container'
								)
								.addClass( 'revx-d-none' );
							timer.parent().addClass( 'revx-d-none' );
							return;
						}

						// Calculate days, hours, minutes, and seconds
						const days = Math.floor(
							distance / ( 1000 * 60 * 60 * 24 )
						);
						const hours = Math.floor(
							( distance % ( 1000 * 60 * 60 * 24 ) ) /
								( 1000 * 60 * 60 )
						);
						const minutes = Math.floor(
							( distance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 )
						);
						const seconds = Math.floor(
							( distance % ( 1000 * 60 ) ) / 1000
						);

						// Update the HTML elements
						timer.find( '.revx-days' ).text( padWithZero( days ) );
						timer
							.find( '.revx-hours' )
							.text( padWithZero( hours ) );
						timer
							.find( '.revx-minutes' )
							.text( padWithZero( minutes ) );
						if ( minutes <= 0 ) {
							timer.find( '.revx-minutes' ).hide();
							timer.find( '.revx-minutes-label' ).hide();
						}
						timer
							.find( '.revx-seconds' )
							.text( padWithZero( seconds ) );
					};

					// Add leading zero if needed
					const padWithZero = function ( num ) {
						return num < 10 ? `0${ num }` : num;
					};

					// Update the countdown every second
					updateCountdown();
					const interval = setInterval( updateCountdown, 1000 );

					// Make the timer visible
					timer.removeClass( 'revx-d-none' );
				}
			);
		} catch ( error ) {
			console.error( 'Error initializing countdown timers:', error );
		}
	};

	// Call
	double_order_countdown();

	function runDoubleOrder() {
		let currentlyCheckedIndex = null;

		// Handle click on label
		$( document ).on(
			'click',
			'.revx-double-order-checkbox-label',
			function ( e ) {
				e.stopPropagation();
				if (
					e.target === this ||
					! $( e.target ).hasClass( 'revx-double-order-checkbox' )
				) {
					const $checkbox = $( this ).find(
						'.revx-double-order-checkbox'
					);

					doubleOrderToggleCheckbox( $checkbox );
				}
			}
		);

		function initializeDoubleOrderCheckboxes() {
			const $selectedCheckbox = $(
				'.revx-double-order-checkbox[data-is-checked="yes"]'
			);

			if ( $selectedCheckbox.length ) {
				const index = $selectedCheckbox.data( 'index' );
				currentlyCheckedIndex = index;

				// Apply selected style
				$selectedCheckbox.attr(
					'style',
					$selectedCheckbox.data( 'selected-style' )
				);

				// Set success message for selected item
				$selectedCheckbox
					.closest( '.revx-products-lists' )
					.find( '.revx-products-list-header > div' )
					.text(
						$selectedCheckbox
							.closest( '.revx-products-lists' )
							.find( '.revx-products-list-header > div' )
							.data( 'success-message' )
					);

				// Apply opacity effects to items before selected
				$( '.revx-products-lists' ).each( function () {
					const listIndex = $( this ).data( 'index' );
					if ( listIndex < index ) {
						$( this )
							.css( 'opacity', '0.5' )
							.hover(
								function () {
									$( this ).css( 'opacity', '1' );
								},
								function () {
									$( this ).css( 'opacity', '0.5' );
								}
							);
					}
				} );

				// Show/hide appropriate elements
				$( '.revx-products-lists' ).addClass( 'hidden' );

				// Show current index
				$(
					`.revx-products-lists[data-index="${ index }"]`
				).removeClass( 'hidden' );

				// Show all elements above current index
				$( `.revx-products-lists` )
					.filter( ( _, el ) => $( el ).data( 'index' ) < index )
					.removeClass( 'hidden' );

				// Show the immediate below index element
				const nextIndex = index + 1;
				$(
					`.revx-products-lists[data-index="${ nextIndex }"]`
				).removeClass( 'hidden' );
			} else {
				// If no checkbox is selected, show only the first element
				$( '.revx-products-lists' ).addClass( 'hidden' );
				$( `.revx-products-lists[data-index="0"]` ).removeClass(
					'hidden'
				);
			}
		}

		initializeDoubleOrderCheckboxes();

		// Main toggle function remains the same but without the initial currentlyCheckedIndex check
		function doubleOrderToggleCheckbox( $checkbox ) {
			const index = $checkbox.data( 'index' );
			const isChecked = $checkbox.data( 'is-checked' );
			const $allHeaders = $( '.revx-products-lists' ).find(
				'.revx-products-list-header > div'
			);
			const $allLists = $( '.revx-products-lists' );

			// Function to reset all opacities
			const resetOpacities = () => {
				$allLists.css( 'opacity', '' );
				$allLists.off( 'mouseenter mouseleave' );
			};

			// Function to apply opacity effects
			const applyOpacityEffects = ( selectedIndex ) => {
				$allLists.each( function () {
					const listIndex = $( this ).data( 'index' );
					if ( listIndex < selectedIndex ) {
						$( this )
							.css( 'opacity', '0.5' )
							.hover(
								function () {
									$( this ).css( 'opacity', '1' );
								},
								function () {
									$( this ).css( 'opacity', '0.5' );
								}
							);
					} else {
						$( this ).css( 'opacity', '1' );
						$( this ).off( 'mouseenter mouseleave' );
					}
				} );
			};

			if ( isChecked === 'yes' ) {
				// If already checked, uncheck it
				$checkbox.attr( 'style', $checkbox.data( 'default-style' ) );
				$checkbox
					.data( 'is-checked', 'no' )
					.attr( 'data-is-checked', 'no' );
				currentlyCheckedIndex = null;

				// Reset all headers to default message
				$allHeaders.each( function () {
					$( this ).text( $( this ).data( 'default-message' ) );
				} );

				// Reset all opacities
				resetOpacities();
			} else if ( currentlyCheckedIndex === index ) {
				// If clicking the same checkbox, uncheck it
				$checkbox.attr( 'style', $checkbox.data( 'default-style' ) );
				$checkbox
					.data( 'is-checked', 'no' )
					.attr( 'data-is-checked', 'no' );
				currentlyCheckedIndex = null;

				// Reset all headers to default message
				$allHeaders.each( function () {
					$( this ).text( $( this ).data( 'default-message' ) );
				} );

				// Reset all opacities
				resetOpacities();
			} else {
				// Uncheck previously checked checkbox if exists
				if ( currentlyCheckedIndex !== null ) {
					const $prevCheckbox = $(
						`.revx-double-order-checkbox[data-index="${ currentlyCheckedIndex }"]`
					);
					$prevCheckbox
						.attr( 'style', $checkbox.data( 'default-style' ) )
						.data( 'is-checked', 'no' )
						.attr( 'data-is-checked', 'no' );
				}

				// Check the clicked checkbox
				$checkbox
					.attr( 'style', $checkbox.data( 'selected-style' ) )
					.data( 'is-checked', 'yes' )
					.attr( 'data-is-checked', 'yes' );
				currentlyCheckedIndex = index;

				// Reset all headers to default message first
				$allHeaders.each( function () {
					$( this ).text( $( this ).data( 'default-message' ) );
				} );

				// Set success message only for the checked item's header
				$checkbox
					.closest( '.revx-products-lists' )
					.find( '.revx-products-list-header > div' )
					.text(
						$checkbox
							.closest( '.revx-products-lists' )
							.find( '.revx-products-list-header > div' )
							.data( 'success-message' )
					);

				// Apply opacity effects
				applyOpacityEffects( currentlyCheckedIndex );
			}

			// Hide all elements first
			$allLists.addClass( 'hidden' );

			if ( currentlyCheckedIndex !== null ) {
				// Show current index
				$(
					`.revx-products-lists[data-index="${ currentlyCheckedIndex }"]`
				).removeClass( 'hidden' );

				// Show all elements above current index
				$( `.revx-products-lists` )
					.filter(
						( _, el ) =>
							$( el ).data( 'index' ) < currentlyCheckedIndex
					)
					.removeClass( 'hidden' );

				// Show the immediate below index element
				const nextIndex = currentlyCheckedIndex + 1;
				$(
					`.revx-products-lists[data-index="${ nextIndex }"]`
				).removeClass( 'hidden' );
			} else {
				// If no checkbox is selected, show the first element
				$( `.revx-products-lists[data-index="0"]` ).removeClass(
					'hidden'
				);
			}

			const currentStateData = {
				index: currentlyCheckedIndex,
				multiplier:
					currentlyCheckedIndex !== null
						? $checkbox.data( 'multiplier' )
						: null,
				value:
					currentlyCheckedIndex !== null
						? $checkbox.data( 'value' )
						: null,
				campaign_id: $checkbox.data( 'campaign-id' ),
			};

			$( document ).trigger(
				'revenue_double_order_checkbox_state_change',
				currentStateData
			);
		}
	}

	function runGroupedOrder() {
		let currentlyCheckedGroup = null;
		const selectedProducts = new Set();

		// Initialize state based on any pre-checked items
		function initializeState() {
			const selectedCheckbox = $(
				'.revx-double-order-checkbox-specific[data-is-checked="yes"]'
			);

			if ( selectedCheckbox.length ) {
				currentlyCheckedGroup = selectedCheckbox
					.first()
					.closest( '.revx-products-lists-specific' )
					.data( 'index' );

				selectedCheckbox.each( function () {
					const $checkbox = $( this );
					selectedProducts.add( $checkbox.data( 'product-id' ) );
					$checkbox.attr(
						'style',
						$checkbox.data( 'selected-style' )
					);
				} );

				updateGroupVisibility();
				updateOpacityStyles();
				triggerStateChangeEvent( selectedCheckbox.first() );
			}
		}

		// Handle click on specific checkboxes
		$( document ).on(
			'click',
			'.revx-double-order-checkbox-specific',
			function ( e ) {
				e.stopPropagation();
				groupedOrderToggleCheckbox( $( this ) );
			}
		);

		// Add hover handlers for opacity
		$( document )
			.on( 'mouseenter', '.revx-products-lists-specific', function () {
				$( this ).css( 'opacity', '1' );
			} )
			.on( 'mouseleave', '.revx-products-lists-specific', function () {
				updateOpacityStyles();
			} );

		function groupedOrderToggleCheckbox( $checkbox ) {
			const groupIndex = $checkbox
				.closest( '.revx-products-lists-specific' )
				.data( 'index' );
			const productId = $checkbox.data( 'product-id' );

			if (
				currentlyCheckedGroup === null ||
				currentlyCheckedGroup === groupIndex
			) {
				if ( isCheckboxSelected( $checkbox ) ) {
					$checkbox.attr(
						'style',
						$checkbox.data( 'default-style' )
					);
					$checkbox.data( 'is-checked', 'no' );
					selectedProducts.delete( productId );

					if ( selectedProducts.size === 0 ) {
						currentlyCheckedGroup = null;
					}
				} else {
					$checkbox.attr(
						'style',
						$checkbox.data( 'selected-style' )
					);
					$checkbox.data( 'is-checked', 'yes' );
					selectedProducts.add( productId );
					currentlyCheckedGroup = groupIndex;
				}
			} else {
				$(
					`.revx-products-lists-specific[data-index="${ currentlyCheckedGroup }"] .revx-double-order-checkbox-specific`
				).each( function () {
					const $cb = $( this );
					$cb.attr( 'style', $cb.data( 'default-style' ) );
					$cb.data( 'is-checked', 'no' );
				} );

				selectedProducts.clear();
				currentlyCheckedGroup = groupIndex;

				$checkbox.attr( 'style', $checkbox.data( 'selected-style' ) );
				$checkbox.data( 'is-checked', 'yes' );
				selectedProducts.add( productId );
			}

			updateGroupVisibility();
			updateOpacityStyles();
			triggerStateChangeEvent( $checkbox );
		}

		function isCheckboxSelected( $checkbox ) {
			return $checkbox.data( 'is-checked' ) === 'yes';
		}

		function updateGroupVisibility() {
			$( '.revx-products-lists-specific' ).addClass( 'hidden' );

			if ( currentlyCheckedGroup !== null ) {
				$( '.revx-products-lists-specific' ).each( function () {
					const groupIndex = $( this ).data( 'index' );
					if ( groupIndex <= currentlyCheckedGroup + 1 ) {
						$( this ).removeClass( 'hidden' );
					}
				} );
			} else {
				$(
					'.revx-products-lists-specific[data-index="0"]'
				).removeClass( 'hidden' );
			}
		}

		function updateOpacityStyles() {
			if ( currentlyCheckedGroup !== null ) {
				$( '.revx-products-lists-specific' ).each( function () {
					const groupIndex = parseInt( $( this ).data( 'index' ) );
					if ( groupIndex < currentlyCheckedGroup ) {
						$( this ).css( 'opacity', '0.5' );
					} else {
						$( this ).css( 'opacity', '1' );
					}
				} );
			} else {
				$( '.revx-products-lists-specific' ).css( 'opacity', '1' );
			}
		}

		function triggerStateChangeEvent( $checkbox ) {
			const stateData = {
				groupIndex: currentlyCheckedGroup,
				selectedProducts: Array.from( selectedProducts ),
				multiplier:
					currentlyCheckedGroup !== null
						? $checkbox.data( 'multiplier' )
						: null,
				value:
					currentlyCheckedGroup !== null
						? $checkbox.data( 'value' )
						: null,
				campaign_id: $checkbox.data( 'campaign-id' ),
			};

			$( document ).trigger(
				'revenue_grouped_order_checkbox_state_change',
				stateData
			);
		}

		// Initialize the component
		initializeState();
	}
	runDoubleOrder();
	runGroupedOrder();

	function postRevenueOrderData( action, data, callback ) {
		$.post(
			revenue_campaign.ajax,
			{
				action: 'revenue_double_order_multiplier',
				...data,
				_wpnonce: revenue_campaign.nonce,
			},
			function () {
				if ( typeof callback === 'function' ) {
					callback();
				}
			}
		);
	}

	$( document ).on(
		'revenue_double_order_checkbox_state_change',
		function ( e, stateData ) {
			const data = {
				multiplier: stateData.multiplier,
				is_checked: stateData.index !== null ? 'yes' : 'no',
				campaign_id: stateData.campaign_id,
				index: stateData.index,
				product_id: null, // No specific product for double order
			};

			postRevenueOrderData(
				'revenue_double_order_multiplier',
				data,
				function () {
					$( 'body' ).trigger( 'update_checkout' );
				}
			);
		}
	);

	// Hook for grouped order checkbox state change
	$( document ).on(
		'revenue_grouped_order_checkbox_state_change',
		function ( e, stateData ) {
			const data = {
				multiplier: stateData.multiplier,
				is_checked: stateData.groupIndex !== null ? 'yes' : 'no',
				campaign_id: stateData.campaign_id,
				index: stateData.groupIndex,
				product_ids: stateData.selectedProducts,
			};

			postRevenueOrderData(
				'revenue_double_order_multiplier',
				data,
				function () {
					$( 'body' ).trigger( 'update_checkout' );
				}
			);
		}
	);

	$( '.revx-double-order-container' ).each( function () {
		const container = $( this );
		const delayBetween =
			parseFloat(
				getComputedStyle( this ).getPropertyValue(
					'--revx-double-order-animation-delay-between'
				)
			) * 1000;

		if ( ! delayBetween ) {
			return;
		}

		const activeTime =
			parseFloat(
				getComputedStyle( this ).getPropertyValue(
					'--animation-active-time'
				)
			) * 1000;

		container
			.find(
				'.revx-double-order-animation-shake,.revx-double-order-animation-pulse, .revx-double-order-animation-tada, .revx-double-order-animation-bounce, .revx-double-order-animation-swing'
			)
			.each( function () {
				const element = $( this );

				function triggerAnimation() {
					const animationName = element
						.attr( 'class' )
						.split( ' ' )
						.find( ( cls ) =>
							cls.startsWith( 'revx-double-order-animation-' )
						);
					element.css(
						'animation',
						`${ animationName } ${ activeTime }ms ease-in-out`
					);
					setTimeout( () => {
						element.css( 'animation', 'none' );
					}, activeTime );
				}

				setInterval( triggerAnimation, activeTime + delayBetween );
			} );
	} );

	function deduplicateCampaigns() {
		// Keep track of campaign IDs we've seen
		const seenCampaigns = new Set();

		// Find all campaign containers
		$( '.revx-campaign-container' ).each( function () {
			// Get campaign ID from class that matches revx-campaign-{number}
			const campaignClass = $( this )
				.attr( 'class' )
				.split( ' ' )
				.find( ( className ) => /revx-campaign-\d+/.test( className ) );

			if ( campaignClass ) {
				const campaignId = campaignClass.replace(
					'revx-campaign-',
					''
				);

				// If we've seen this campaign ID before, hide the container
				if ( seenCampaigns.has( campaignId ) ) {
					$( this ).hide();
				} else {
					// First time seeing this campaign ID
					seenCampaigns.add( campaignId );
					$( this ).show();
				}
			}
		} );
	}

	$( document ).ready( deduplicateCampaigns );

	// Tooltip - Spending Goal
	function updateTooltipPosition( $container ) {
		const $tooltip = $container.find( '.revx-gift-tooltip' );
		if ( ! $tooltip.length ) {
			return;
		}

		const containerRect = $container[ 0 ].getBoundingClientRect();
		const tooltipRect = $tooltip[ 0 ].getBoundingClientRect();
		const windowHeight = $( window ).height();
		const windowWidth = $( window ).width();

		// Calculate available space
		const spaceAbove = containerRect.top;
		const spaceBelow = windowHeight - containerRect.bottom;

		// Determine position (top or bottom)
		const position = spaceAbove > spaceBelow ? 'top' : 'bottom';
		$tooltip.attr( 'data-position', position );

		// Handle horizontal overflow
		const tooltipWidth = $tooltip.outerWidth();
		const containerCenterX = containerRect.left + containerRect.width / 2;
		const spaceLeft = containerCenterX;
		const spaceRight = windowWidth - containerCenterX;

		if ( tooltipWidth / 2 > spaceLeft ) {
			// Not enough space on the left
			$tooltip.css( {
				left: '0',
				transform: 'translateX(0)',
			} );
		} else if ( tooltipWidth / 2 > spaceRight ) {
			// Not enough space on the right
			$tooltip.css( {
				left: 'auto',
				right: '0',
				transform: 'translateX(0)',
			} );
		} else {
			// Center aligned
			$tooltip.css( {
				// left: '50%',
				right: 'auto',
				transform: 'translateX(-50%)',
			} );
		}
	}

	// Handle mouse enter
	$( '.revx-step-icon-container' ).on( 'mouseenter', function () {
		updateTooltipPosition( $( this ) );
	} );

	// Update positions on window resize
	let resizeTimer;
	$( window ).on( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( function () {
			$( '.revx-step-icon-container:hover' ).each( function () {
				updateTooltipPosition( $( this ) );
			} );
		}, 250 );
	} );

	function adjustContentMarginTop() {
		const helloBar = $( '.revx-spending-goal-top' ).length
			? $( '.revx-spending-goal-top' )
			: $( '.revx-campaign-fsb-top' );

		// Check if hello bar exists
		if ( helloBar.length === 0 ) {
			return;
		}

		const helloBarHeight = helloBar.outerHeight();

		// Only adjust if we have a valid height
		if ( helloBarHeight ) {
			$( 'body' ).css( 'margin-top', helloBarHeight );

			// Remove margin-bottom as it's causing double spacing
			helloBar.css( 'margin-top', -helloBarHeight );
		}
	}

	adjustContentMarginTop();
	function adjustContentMarginBottom() {
		const helloBar = $( '.revx-spending-goal-bottom' ).length
			? $( '.revx-spending-goal-bottom' )
			: $( '.revx-campaign-fsb-bottom' );

		// Check if hello bar exists
		if ( helloBar.length === 0 ) {
			return;
		}

		const helloBarHeight = helloBar.outerHeight();

		// Only adjust if we have a valid height
		if ( helloBarHeight ) {
			$( 'body' ).css( 'margin-bottom', helloBarHeight );

			// Remove margin-bottom as it's causing double spacing
			// helloBar.css('margin-bottom', -helloBarHeight);
		}
	}

	adjustContentMarginBottom();

	function showToast( message, type = 'success', duration = 3000 ) {
		// Check if toast container exists, otherwise create it
		let $toastContainer = $( '.revx-toaster-container' );
		if ( $toastContainer.length === 0 ) {
			$toastContainer = $( '<div class="revx-toaster-container"></div>' );
			$( 'body' ).append( $toastContainer );
		}

		// Determine toast class and icon based on type
		const toastClasses = {
			success: 'revx-toaster__success',
			error: 'revx-toaster__error',
		};

		const icons = {
			success: `
				<svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none" viewBox="0 0 16 16" class="revx-toaster__close-icon revx-toaster__icon">
					<path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m12 4-8 8M4 4l8 8"></path>
				</svg>
			`,
			error: `
				<svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none" viewBox="0 0 16 16" class="revx-toaster__close-icon revx-toaster__icon">
					<path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m12 4-8 8M4 4l8 8"></path>
				</svg>
			`,
		};

		// Create a new toast element as a jQuery object
		const $toast = $( `
			<div class="revx-toaster revx-justify-space revx-toaster-lg ${ toastClasses[ type ] }" style="display: flex;">
				<div class="revx-paragraph--xs revx-align-center-xs">
					${ message }
				</div>
				<div class="revx-paragraph--xs revx-align-center">
					${ icons[ type ] }
				</div>
			</div>
		` );

		// Add close button functionality
		$toast.find( '.revx-toaster__close-icon' ).on( 'click', function () {
			$toast.fadeOut( 400, function () {
				$( this ).remove(); // Remove the toast from DOM
			} );
		} );

		// Append the toast to the toast container
		$toastContainer.append( $toast );

		// Show the toast
		$toast.fadeIn( 400 );

		// Set timeout to hide the toast after the specified duration
		setTimeout( function () {
			if ( $toast.is( ':visible' ) ) {
				// Only remove if still visible
				$toast.fadeOut( 400, function () {
					$( this ).remove(); // Remove the toast from DOM
				} );
			}
		}, duration );
	}

	// Hide Campaign on Close Button Click

	$( document ).on( 'click', '.revx-campaign-close', function () {
		const campaignID = $( this ).data( 'campaign-id' );
		$( `.revx-campaign-${ campaignID }` ).hide();
		$( 'body' ).css( 'margin-bottom', 0 );
		$( 'body' ).css( 'margin-top', 0 );
	} );

	// Next Order Coupon Copy Clip-Borad
	$( '.revx-coupon-copy-btn' ).on( 'click', function () {
		const $btn = $( this );
		const $content = $btn.closest( '.revx-Coupon-button' );
		const text = $content
			.clone() // Clone to avoid modifying original
			.children() // Remove children (like .revx-coupon-copy-btn)
			.remove()
			.end() // Go back to cloned parent
			.text()
			.trim(); // Get just the raw text
		// Fallback for browsers that do not support navigator.clipboard
		const tempInput = $( '<input>' );
		$( 'body' ).append( tempInput );
		tempInput.val( text ).select();
		try {
			document.execCommand( 'copy' );
			// $btn.text( 'Copied!' );
			$( '.revx-Coupon-button' ).css( 'background-color', '#008000' );
			$( '.revx-Coupon-button' ).css( {
				transition: 'background-color 0.4s',
			} );

			setTimeout(
				() =>
					$( '.revx-Coupon-button' ).css(
						'background-color',
						'unset'
					),
				400
			);
		} catch ( err ) {
			console.error( 'Fallback: Failed to copy:', err );
		}
		tempInput.remove();
	} );

	window.Revenue = {
		getCookie,
		setCookie,
		calculateCurrentPrice,
		formatPrice,
		updatePriceDisplay,
		updateMixMatchHeaderAndPrices,
		fbtCalculation,
		updateStyles,
		showToast,
	};
	// eslint-disable-next-line no-undef
} )( jQuery );
