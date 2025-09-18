/* global revenue_campaign */
jQuery( function ( $ ) {
	if ( typeof revenue_campaign === 'undefined' ) {
		console.error( 'Revenue campaign script not loaded.' );
		return;
	}
	let prevButtonText = '';

	// Initialize event handlers

	const requestsQueue = [];

	const addRequest = ( request ) => {
		requestsQueue.push( request );
		if ( requestsQueue.length === 1 ) {
			processRequests();
		}
	};

	const processRequests = () => {
		const request = requestsQueue[ 0 ];
		const originalComplete = request.complete;

		request.complete = () => {
			if ( typeof originalComplete === 'function' ) {
				originalComplete();
			}
			requestsQueue.shift();
			if ( requestsQueue.length > 0 ) {
				processRequests();
			}
		};

		$.ajax( request );
	};

	const handleAddToCart = ( e, index ) => {
		e.preventDefault();

		const $button = $( e.currentTarget );
		prevButtonText = $button.text();

		const data = prepareData( $button, index );

		const preventAddToCart = $( document.body ).triggerHandler(
			'revenue_prevent_proceeding_add_to_cart'
		);

		if ( ! data || preventAddToCart ) {
			return;
		}

		toggleLoading( $button, true );

		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => handleError( $button ),
			dataType: 'json',
		} );
	};

	const prepareData = ( $button, index ) => {
		const campaignId = $button.data( 'campaignId' );
		const productId = $button.data( 'productId' );
		const campaignType = $button.data( 'campaign-type' );
		const qty =
			$button.data( 'quantity' ) ||
			$(
				`input[name="revx-quantity-${ campaignId }-${ productId }-${ index }"]`
			).val() ||
			1;

		const data = {
			action: 'revenue_add_to_cart',
			productId,
			campaignId,
			_wpnonce: revenue_campaign.nonce,
			quantity: qty,
			campaignSourcePage: $button.data( 'campaign_source_page' ),
			campaignType,
			index,
		};

		const typeHandlers = {
			buy_x_get_y: () => {
				data.productId = $( '.single_add_to_cart_button' ).val();
				data.bxgy_data = getBxgyData( campaignId );
				data.bxgy_trigger_data = getBxgyTriggerData( campaignId );
				data.bxgy_offer_data = getBxgyOfferData( campaignId );
			},
			volume_discount: () => {
				data.quantity = $button
					.closest( '.revx-volume-discount' )
					.find( '.revx-campaign-item[data-revx-selected=true]' )
					.data( 'quantity' );
			},
			frequently_bought_together: () => {
				data.requiredProduct = productId;
				data.fbt_data = getFbtData( campaignId );
			},
			mix_match: () => {
				data.mix_match_data = getMixMatchData( campaignId );
			},
		};

		if ( typeHandlers[ campaignType ] ) {
			typeHandlers[ campaignType ]();
		}

		if ( 'mix_match' === campaignType ) {
			const requiredProducts = JSON.parse(
				$( `input[name=revx-required-products-${ campaignId }` ).val()
			);

			// Check if each required product exists in mixMatchData
			const missingProducts = requiredProducts.filter(
				( pid ) => ! data.mix_match_data.hasOwnProperty( pid )
			);

			if ( missingProducts.length > 0 ) {
				showToast(
					'Error adding to cart, Some required product is missing!',
					'error'
				);
				return;
			}
		} else if ( 'frequently_bought_together' === campaignType ) {
			if ( Object.keys( data.fbt_data ).length === 0 ) {
				showToast( 'Please select the item(s) first', 'error' );
				return;
			}
		}
		if ( ! validateData( data ) ) {
			return null;
		}

		return data;
	};

	const validateData = ( data ) => {
		return data.productId && data.campaignId && data._wpnonce;
	};

	const handleAddToCartSuccess = ( response, $button, data ) => {
		const campaignId = $button.data( 'campaignId' );
		$( `.revx-campaign-${ campaignId }` ).trigger(
			'revx_added_to_cart',
			data
		);

		$( document ).trigger( 'revenue:add_to_cart', {
			campaignId,
			response,
		} );

		if ( response?.data?.on_cart_action === 'hide_products' ) {
			hideProduct( $button.data( 'productId' ), campaignId );
		}

		toggleLoading( $button, false, 'Added to Cart' );
		showToast( revenue_campaign.added_to_cart );

		$( document.body ).trigger( 'added_to_cart', [
			response?.data?.fragments,
			response?.data?.cart_hash,
			$button,
		] );

		if ( $button.hasClass( 'revx-builder-atc-skip' ) ) {
			// Remove revx-loading class and update button text to 'Added to Cart'
			window.location.assign( revenue_campaign.checkout_page_url );
		}
		if ( response?.data?.is_reload ) {
			location.reload();
		}
	};

	const hideProduct = ( productId, campaignId ) => {
		const target = $(
			`#revenue-campaign-item-${ productId }-${ campaignId }`
		);
		target.hide( 'slow', () => {
			target.remove();
		} );
	};

	const handleError = ( $button ) => {
		toggleLoading( $button, false );
		console.error( 'Error adding to cart' );
		showToast( 'Error adding to cart', 'error' );
	};

	const toggleLoading = (
		$button,
		isLoading,
		text = revenue_campaign.adding
	) => {
		$button
			.toggleClass( 'revx-loading', isLoading )
			.text( isLoading ? text : prevButtonText );
	};

	const getFbtData = ( campaignId ) => {
		let selectedProducts = getCookieData( `campaign_${ campaignId }` );
		let prevSelectedItems = $(
			`input[name=revx-fbt-selected-items-${ campaignId }]`
		).val();
		prevSelectedItems = prevSelectedItems
			? JSON.parse( prevSelectedItems )
			: {};
		if ( Object.keys( selectedProducts ).length == 0 ) {
			selectedProducts = { ...prevSelectedItems };
		}
		return selectedProducts;
	};

	const getMixMatchData = ( campaignId ) => {
		const cookieName = `mix_match_${ campaignId }`;
		let prevData = getCookieData( cookieName );

		let prevSelectedItems = $(
			`input[name=revx-selected-items-${ campaignId }]`
		).val();

		prevSelectedItems = prevSelectedItems
			? JSON.parse( prevSelectedItems )
			: {};

		if ( Object.keys( prevData ).length === 0 ) {
			prevData = prevSelectedItems;
		}

		const mixMatchData = prevData;
		const mixMatchProducts = {};
		Object.values( mixMatchData ).forEach( ( item ) => {
			mixMatchProducts[ item.id ] = item.quantity;
		} );

		return mixMatchProducts;
	};

	const getBxgyData = ( campaignId ) => {
		let offerData = $( `input[name=revx-offer-data-${ campaignId }]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const bxgy_data = {};

		Object.keys( jsonData ).forEach( ( pid ) => {
			const qty = $(
				`input[name=revx-quantity-${ campaignId }-${ pid }]`
			).val();
			bxgy_data[ pid ] = qty;
		} );

		return bxgy_data;
	};
	const getBxgyTriggerData = ( campaignId ) => {
		let offerData = $( `input[name=revx-trigger-data-${ campaignId }]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const bxgy_data = {};

		Object.keys( jsonData ).forEach( ( pid ) => {
			const qty = $(
				`input[name=revx-quantity-${ campaignId }-${ pid }-trigger]`
			).val();
			bxgy_data[ pid ] = qty;
		} );

		return bxgy_data;
	};
	const getBxgyOfferData = ( campaignId ) => {
		let offerData = $( `input[name=revx-offer-data-${ campaignId }]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const bxgy_data = {};

		Object.keys( jsonData ).forEach( ( pid ) => {
			const qty = $(
				`input[name=revx-quantity-${ campaignId }-${ pid }-offer]`
			).val();
			bxgy_data[ pid ] = qty;
		} );

		return bxgy_data;
	};

	const getCookieData = ( cookieName ) => {
		try {
			return JSON.parse( Revenue.getCookie( cookieName ) || '{}' );
		} catch ( e ) {
			console.error(
				`Failed to parse cookie data for ${ cookieName }:`,
				e
			);
			return {};
		}
	};

	const handleAddBundleToCart = ( e ) => {
		e.preventDefault();
		const $button = $( e.currentTarget );
		const campaignId = $button.data( 'campaignId' );
		const qty = $button.data( 'quantity' ) || 1;
		const triggerProductId = getTriggerProductId( campaignId );

		const data = {
			action: 'revenue_add_bundle_to_cart',
			campaignId,
			_wpnonce: revenue_campaign.nonce,
			trigger_product_id: triggerProductId,
			quantity: qty,
			campaignType: 'bundle_discount',
		};
		toggleLoading( $button, true );
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			success: ( response ) =>
				handleBundleSuccess( response, $button, data ),
			error: () => handleError( $button ),
			dataType: 'json',
		} );
	};

	const getTriggerProductId = ( campaignId ) => {
		return (
			$( '.single_add_to_cart_button' ).val() ||
			$( `input[name="revx-trigger-product-id-${ campaignId }"]` ).val()
		);
	};

	const handleBundleSuccess = ( response, $button, data ) => {
		const campaignId = $button.data( 'campaignId' );
		$( `.revx-campaign-${ campaignId }` ).trigger(
			'revx_added_to_cart',
			data
		);

		$( document ).trigger( 'revenue:add_bundle_to_cart', {
			campaignId,
			response,
		} );

		$( document.body ).trigger( 'added_to_cart', [
			response?.data?.fragments,
			response?.data?.cart_hash,
			$button,
		] );

		toggleLoading( $button, false, 'Added to Cart' );
		showToast( revenue_campaign.added_to_cart );

		if ( $button.hasClass( 'revx-builder-atc-skip' ) ) {
			// Remove revx-loading class and update button text to 'Added to Cart'
			window.location.assign( revenue_campaign.checkout_page_url );
		}

		if ( response?.data?.is_reload ) {
			location.reload();
		}
	};

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

	// function showToast(message, duration = 3000) {
	// 	// Check if toast container exists, otherwise create it
	// 	let $toastContainer = $('.toast-container');
	// 	if ($toastContainer.length === 0) {
	// 		$toastContainer = $('<div class="revx-toaster-container"></div>');
	// 		$('body').append($toastContainer);
	// 	}

	// 	// Create a new toast element as a jQuery object
	// 	const $toast = $(`
	//         <div class="revx-toaster revx-justify-space revx-toaster-lg revx-toaster__success">
	//             <div class="revx-paragraph--xs revx-align-center-xs">
	//                 ${message}
	//             </div>
	//             <div class="revx-paragraph--xs revx-align-center">
	//                 <svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none" viewBox="0 0 16 16" class="revx-toaster__close-icon revx-toaster__icon">
	//                 <path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m12 4-8 8M4 4l8 8"></path>
	//                 </svg>
	//             </div>
	//         </div>
	//     `);

	// 	// Add close button functionality
	// 	$toast.find('.revx-toaster__close-icon').on('click', function () {
	// 		$toast.fadeOut(400, function () {
	// 			$(this).remove(); // Remove the toast from DOM
	// 		});
	// 	});

	// 	// Append the toast to the toast container
	// 	$toastContainer.append($toast);

	// 	// Show the toast
	// 	$toast.fadeIn(400);

	// 	// Set timeout to hide the toast after the specified duration
	// 	setTimeout(function () {
	// 		$toast.fadeOut(400, function () {
	// 			$(this).remove(); // Remove the toast from DOM
	// 		});
	// 	}, duration);
	// }

	function clearData( e, data ) {
		const campaignId = data.campaignId;
		const campaignType = data.campaignType;

		switch ( campaignType ) {
			case 'mix_match':
				Revenue.setCookie( `mix_match_${ campaignId }`, '', -1 );
				$( `input[name=revx-selected-items-${ campaignId }]` ).val(
					''
				);
				Revenue.updateMixMatchHeaderAndPrices( campaignId, '' );
				$( `.revx-campaign-${ campaignId }` )
					.find( '.revx-selected-item' )
					.each( function () {
						if ( ! $( this ).hasClass( 'revx-d-none' ) ) {
							$( this ).remove();
						}
					} );

				$(
					`.revx-campaign-${ campaignId } .revx-empty-selected-products`
				).removeClass( 'revx-d-none' );
				$(
					`.revx-campaign-${ campaignId } .revx-selected-product-container`
				).addClass( 'revx-empty-selected-items' );
				$(
					`.revx-campaign-${ campaignId } .revx-empty-mix-match`
				).removeClass( 'revx-d-none' );
				break;
			case 'frequently_bought_together':
				let hasRequired = false;

				const parent = $( this ).find( '.revx-campaign-container' );

				const productId = $(
					`button.revx-campaign-add-to-cart-btn[data-campaign-id="${ campaignId }"]`
				).data( 'product-id' );

				$( `.revx-campaign-${ campaignId }` )
					.find( '.revx-builder-checkbox' )
					.each( function () {
						if (
							! $( this )
								.parent()
								.hasClass( 'revx-item-required' )
						) {
							Revenue.updateStyles( $( this ), false );
						} else {
							hasRequired = true;
						}
					} );

				if ( hasRequired ) {
					$(
						`input[name=revx-fbt-selected-items-${ campaignId }]`
					).val( JSON.stringify( { [ productId ]: 1 } ) );
					Revenue.setCookie(
						`campaign_${ campaignId }`,
						JSON.stringify( { [ productId ]: 1 } ),
						1
					);
				} else {
					$(
						`input[name=revx-fbt-selected-items-${ campaignId }]`
					).val( JSON.stringify( {} ) );
					Revenue.setCookie(
						`campaign_${ campaignId }`,
						JSON.stringify( {} ),
						1
					);
				}

				Revenue.fbtCalculation( parent, campaignId );

				break;
			default:
				break;
		}

		$( `.revx-campaign-view-${ campaignId }.revx-floating-main` ).hide();
		$( `.revx-campaign-view-${ campaignId }.revx-popup` ).hide();
		$( `.revx-campaign-${ campaignId }.revx-volume-discount` ).hide();
		$( `.revx-campaign-${ campaignId }.revx-bundle-discount` ).hide();
		$( `.revx-campaign-${ campaignId }.revx-mix-match` ).hide();
		$(
			`.revx-campaign-${ campaignId }.revx-frequently-bought-together`
		).hide();
		$( `.revx-campaign-${ campaignId }.revx-buyx-gety` ).hide();
	}

	const initEventHandlers = () => {
		$( document.body )
			.find( '.revx-campaign-add-to-cart-btn:not(.revx-prevent-event)' )
			.each( function ( index ) {
				$( this ).on( 'click', function ( e ) {
					handleAddToCart( e, index );
				} );
			} )
			.end()
			.on(
				'click',
				'.revenue-campaign-add-bundle-to-cart:not(.revx-prevent-event)',
				handleAddBundleToCart
			)
			.on( 'revx_added_to_cart', clearData );
	};

	initEventHandlers();
} );
