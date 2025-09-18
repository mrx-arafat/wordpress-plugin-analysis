( function ( $ ) {
	'use strict';

	class UpsellSlider {
		constructor() {
			this.currentIndex = 0;
			this.initSliderIfNeeded();

			$( document ).on(
				'click',
				'.revx-upsell-slider-add-cart',
				( e ) => {
					e.preventDefault();
					this.handleAddToCart( $( e.currentTarget ) );
				}
			);
		}

		handleAddToCart( $button ) {
			const productId = $button.data( 'product-id' );
			const campaignId = $button.data( 'campaign-id' );
			const quantity =
				$button
					.closest( '.revx-upsell-slider-actions' )
					.find( 'input[data-name="revx_quantity"]' )
					.val() || 1;

			const beforeText = $button.text();
			const newText = 'Adding to cart';
			$button.addClass( 'loading' );
			$button.text( newText );

			$.ajax( {
				url: '/?wc-ajax=revenue_add_to_cart',
				type: 'POST',
				data: {
					// action: 'revenue_add_to_cart',
					productId,
					campaignId,
					_wpnonce: revenue_campaign.nonce,
					quantity,
				},
				success: ( response ) => {
					Revenue.showToast( 'Added to cart' );

					$( document.body ).trigger( 'added_to_cart', [
						response?.data?.fragments,
						response?.data?.cart_hash,
						false,
					] );
				},
				complete: () => {
					$button.removeClass( 'loading' );
					$button.text( beforeText );
				},
			} );
		}

		initSliderIfNeeded() {
			const $slider = $( '.revx-upsell-slider' );
			if ( $slider.length > 0 ) {
				this.initSlider( $slider );
			}
		}

		initSlider( $slider ) {
			const $track = $slider.find( '.revx-upsell-slider-track' );
			const $items = $track.find( '.revx-upsell-slider-product-card' );
			this.totalItems = $items.length;

			if ( this.totalItems === 0 ) {
				return;
			}

			// Clone items for infinite scroll
			const $firstClone = $items.first().clone().addClass( 'clone' );
			const $lastClone = $items.last().clone().addClass( 'clone' );
			$track.append( $firstClone );
			$track.prepend( $lastClone );

			// Bind events
			this.bindEvents( $slider, $track );

			// Initial setup
			this.updateSlider( $track, false );
			$( window ).on( 'load resize', () =>
				this.updateSlider( $track, false )
			);
		}

		updateSlider( $track, animate = false ) {
			const $items = $track.find( '.revx-upsell-slider-product-card' );
			const itemWidth = $items.first().outerWidth( true );
			const translateX = -( this.currentIndex + 1 ) * itemWidth;

			$track.css(
				'transition',
				animate ? 'transform 0.3s ease-in-out' : 'none'
			);
			$track.css( 'transform', `translateX(${ translateX }px)` );

			if ( this.currentIndex === -1 ) {
				setTimeout( () => {
					$track.css( 'transition', 'none' );
					this.currentIndex = this.totalItems - 1;
					const finalTranslateX =
						-( this.currentIndex + 1 ) * itemWidth;
					$track.css(
						'transform',
						`translateX(${ finalTranslateX }px)`
					);
				}, 300 );
			} else if ( this.currentIndex === this.totalItems ) {
				setTimeout( () => {
					$track.css( 'transition', 'none' );
					this.currentIndex = 0;
					const finalTranslateX = -itemWidth;
					$track.css(
						'transform',
						`translateX(${ finalTranslateX }px)`
					);
				}, 300 );
			}
		}

		bindEvents( $slider, $track ) {
			// Navigation buttons
			$slider.find( '.revx-upsell-slider-prev' ).on( 'click', () => {
				this.currentIndex =
					( this.currentIndex - 1 + this.totalItems ) %
					this.totalItems;
				this.updateSlider( $track, true );
			} );

			$slider.find( '.revx-upsell-slider-next' ).on( 'click', () => {
				this.currentIndex = ( this.currentIndex + 1 ) % this.totalItems;
				this.updateSlider( $track, true );
			} );

			// Touch events
			let touchStartX = 0;
			let touchEndX = 0;

			$slider.on( 'touchstart', ( e ) => {
				touchStartX = e.touches[ 0 ].clientX;
			} );

			$slider.on( 'touchmove', ( e ) => {
				touchEndX = e.touches[ 0 ].clientX;
			} );

			$slider.on( 'touchend', () => {
				const swipeThreshold = 50;
				const swipeDistance = touchEndX - touchStartX;

				if ( Math.abs( swipeDistance ) > swipeThreshold ) {
					if ( swipeDistance > 0 ) {
						$slider
							.find( '.revx-upsell-slider-prev' )
							.trigger( 'click' );
					} else {
						$slider
							.find( '.revx-upsell-slider-next' )
							.trigger( 'click' );
					}
				}
			} );

			// Quantity controls
			$slider.find( '.revx-quantity-minus' ).attr('data-skip-global', true).on( 'click', function () {
				const $input = $( this ).siblings( 'input[type="number"]' );
				const currentValue = parseInt( $input.val(), 10 );
				if ( currentValue > 1 ) {
					$input.val( currentValue - 1 ).trigger( 'change' );
				}
			} );

			$slider.find('.revx-quantity-plus').attr('data-skip-global', true).on('click', function (e) {

				const $input = $(this).siblings('input[type="number"]');
				const currentValue = parseInt($input.val(), 10);
				$input.val(currentValue + 1).trigger('change');
			});
		}
	}

	// Initialize on document ready
	$( document ).ready( () => {
		new UpsellSlider();
	} );
} )( jQuery );
