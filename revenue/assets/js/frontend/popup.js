jQuery(document).ready(function ($) {
	const $popupOverlay = $('#revx-popup-overlay');
	const $popup = $('#revx-popup');
	const $popupWrapper = $('.revx-popup');

	const animationType = $popup.data('animation-name'); // Default animation type
	const animationDelay = $popup.data('animation-delay');

	const $campaignID = $popup.data('campaign-id');

	// Function to show the popup with specified animation
	function showPopup(animationType) {
		$popupOverlay.show();
		$popupWrapper.css('visibility', 'visible');
		switch (animationType) {
			case 'fade':
				$popup.fadeToggle(1000);
				break;
			case 'slide':
				$popup.slideDown();
				break;
			case 'zoom':
				$popup.css({ display: 'block' }).addClass('zoom-in');
				break;
			case 'bounce':
				$popup.css({ display: 'block' }).addClass('bounce-in');
				break;
			case 'flip':
				$popup.css({ display: 'block' }).addClass('flip-in');
				break;
			case 'shake':
				$popup.css({ display: 'block' }).addClass('shake');
				break;
			case 'swing':
				$popup.css({ display: 'block' }).addClass('swing');
				break;
			case 'wobble':
				$popup.css({ display: 'block' }).addClass('wobble');
				break;
			case 'vibrate':
				$popup.css({ display: 'block' }).addClass('vibrate');
				break;
			case 'flash':
				$popup
					.css({ display: 'block', animationDuration: '1s' })
					.addClass('flash');
				break;
			default:
				$popup.show();
				break;
		}
	}

	// Function to hide the popup with specified animation
	function hidePopup(animationType) {
		switch (animationType) {
			case 'fade':
				$popupOverlay.hide();
				// $popup.fadeToggle(1000, "",function() {
				// });
				break;
			case 'zoom':
				$popup
					.removeClass('zoom-in')
					.addClass('zoom-out')
					.one('animationend', function () {
						$(this).removeClass('zoom-out').hide();
						$popupOverlay.hide();
					});
				break;
			case 'slide':
				$popup.slideUp(function () {
					$popupOverlay.hide();
				});
				break;
			case 'bounce':
				$popup
					.removeClass('bounce-in')
					.addClass('bounce-out')
					.one('animationend', function () {
						$(this).removeClass('bounce-out').hide();
						$popupOverlay.hide();
					});
				break;
			case 'flash':
				$popupOverlay.animate(
					{
						opacity: '0',
					},
					3000
				);
				$popup.css({
					animationDuration: '1000',
				});
				$popup
					.removeClass('flash')
					.addClass('flash-out')
					.one('animationend', function () {
						$(this).removeClass('flash-out').fadeOut(100);
					});
				break;
			default:
				break;
		}

		$popup.hide();
		$popupOverlay.hide();
		$('.revx-popup').remove();
		sendRevenueClosePopupRequest($campaignID);
	}

	function sendRevenueClosePopupRequest(campaignId) {
		$.ajax({
			url: revenue_campaign.ajax, // The AJAX URL for your WordPress action
			type: 'POST', // The HTTP request method (POST in this case)
			data: {
				_wpnonce: revenue_campaign.nonce,
				action: 'revenue_close_popup', // The WordPress action hook to call
				campaignId, // Spread the data object to include any additional data you pass
			},
			success(response) {
				// Handle the success case
				console.log('Success:', response);
				// You can add additional actions based on the response here
			},
			error(jqXHR, textStatus, errorThrown) {
				// Handle error case
				console.error('Error:', textStatus, errorThrown);
			},
		});
	}

	function checkOverflow(container) {
		$(container).each(function () {
			const $this = $(this);

			// var isOverflowing = $this[0].scrollWidth > $this[0].offerWidth;

			const isOverflowing = $this[0].scrollWidth > $this[0].offsetWidth;

			if (isOverflowing) {
				$(this)
					.find('.revx-builderSlider-icon')
					.addClass('revx-has-overflow');
			} else {
				$(this)
					.find('.revx-builderSlider-icon')
					.removeClass('revx-has-overflow');
			}
		});
	}

	function initializeSlider($sliderContainer, type) {
		const $container = $sliderContainer.closest('.revx-popup__content');

		const containerElement = $container.get(0);
		const computedStyle = getComputedStyle(containerElement);
		let gridColumnValue = computedStyle
			.getPropertyValue('--revx-grid-column')
			.trim();

		if (!gridColumnValue) {
			gridColumnValue = 3;
		}

		const $slides = $sliderContainer.find('.revx-campaign-item');
		const minSlideWidth = 10 * 16; // 12rem in pixels (assuming 1rem = 16px)
		let containerWidth = $sliderContainer.parent().width();

		if (type == 'normal_discount') {
		} else if (type == 'bundle_discount') {
			containerWidth =
				$sliderContainer
					.closest('.revx-campaign-container__wrapper')
					.innerWidth() - 16;
		} else if (type == 'fbt') {
			containerWidth = $container.find('.revx-regular-product').width();
		}
		let slidesVisible = Math.min(
			gridColumnValue,
			Math.floor(containerWidth / minSlideWidth)
		); // Calculate initial slides visible
		let slideWidth = containerWidth / slidesVisible;
		if (type == 'fbt') {
			slideWidth -= $container.find('.revx-product-bundle').width();
		}
		if (type == 'bundle_discount') {
			slideWidth -= $container
				.find('.revx-builder__middle_element')
				.width();
		}
		const totalSlides = $slides.length;
		let slideIndex = 0; // Start at the first slide

		// Function to update the slide width based on the container width
		function updateSlideWidth() {
			containerWidth = $sliderContainer.parent().width();

			if (type == 'normal_discount') {
			} else if (type == 'bundle_discount') {
				containerWidth =
					$sliderContainer
						.closest('.revx-campaign-container__wrapper')
						.innerWidth() - 16;
			}
			// else if(type=='fbt') {
			//     containerWidth = $sliderContainer.find(".revx-regular-product").width();
			// }
			slidesVisible = Math.min(
				gridColumnValue,
				Math.floor(containerWidth / minSlideWidth)
			); // Recalculate slides visible
			slideWidth = containerWidth / slidesVisible;
			if (type == 'fbt') {
				slideWidth -= $container.find('.revx-product-bundle').width();
			}
			if (type == 'bundle_discount') {
				slideWidth -= $container
					.find('.revx-builder__middle_element')
					.width();
			}
			$slides.css('width', slideWidth + 'px');
			moveToSlide(slideIndex); // Adjust the current slide position based on the new width
		}

		// Set the initial width of each slide
		updateSlideWidth();

		function moveToSlide(index) {
			let tempWidth = slideWidth;
			if (type == 'fbt') {
				tempWidth += $sliderContainer
					.find('.revx-product-bundle')
					.width();
			}
			if (type == 'bundle_discount') {
				tempWidth +=
					$sliderContainer
						.find('.revx-builder__middle_element')
						.width() + 8;
			}
			const offset = -tempWidth * index;
			$sliderContainer.css({
				transition: 'transform 0.5s ease-in-out',
				transform: `translateX(${offset}px)`,
			});
		}

		function moveToNextSlide() {
			slideIndex++;

			if (slideIndex > totalSlides - slidesVisible) {
				slideIndex = 0;
			}

			moveToSlide(slideIndex);
		}

		function moveToPrevSlide() {
			slideIndex--;

			if (slideIndex < 0) {
				slideIndex = totalSlides - slidesVisible;
			}

			moveToSlide(slideIndex);
		}

		// Event listeners for the navigation buttons
		$sliderContainer
			.siblings('.revx-builderSlider-right')
			.click(function () {
				if (!$sliderContainer.is(':animated')) {
					moveToNextSlide();
				}
			});

		$sliderContainer
			.siblings('.revx-builderSlider-left')
			.click(function () {
				if (!$sliderContainer.is(':animated')) {
					moveToPrevSlide();
				}
			});

		// Event listener for window resize to update slide width
		$(window).resize(function () {
			updateSlideWidth();
		});
	}
	// Automatically open the popup after 5 seconds
	setTimeout(function () {
		showPopup(animationType); // Change animationType to the desired animation type

		$(
			'.revx-popup__content.revx-normal-discount-grid .revx-slider-container'
		).each(function () {
			initializeSlider($(this), 'normal_discount');
		});
		$(
			'.revx-popup__content.revx-bundle-discount-grid .revx-slider-container'
		).each(function () {
			initializeSlider($(this), 'bundle_discount');
		});
		$(
			'.revx-popup__content.revx-frequently-bought-together-grid .revx-slider-container'
		).each(function () {
			initializeSlider($(this), 'fbt');
		});
		$(
			'.revx-popup__content.revx-mix-match-grid .revx-slider-container'
		).each(function () {
			initializeSlider($(this), 'mix-match');
		});

		buxXGetYSlider();

		$(window).resize(function () {
			buxXGetYSlider();
		});
		$(window).on('load resize', function () {
			// checkOverflow('.revx-popup__container.revx-slider');
			checkOverflow('.revx-popup__container.revx-slider');
		});
	}, animationDelay * 1000); // 5000 milliseconds = 5 seconds

	// Close the popup when the overlay or close button is clicked
	$popupOverlay.on('click', function () {
		hidePopup(animationType); // Change animationType to the desired animation type
	});

	$(document).on(
		'revx_added_to_cart',
		`.revx-campaign-${$campaignID}`,
		function () {
			hidePopup(animationType); // Change animationType to the desired animation type
		}
	);

	$('.revx-campaign-close').on('click', function () {
		hidePopup(animationType); // Change animationType to the desired animation type
	});
	$('.revx-builder-noThanks-btn').on('click', function () {
		hidePopup(animationType); // Change animationType to the desired animation type
	});

	function buxXGetYSlider() {
		$('.revx-popup__content.revx-buyx-gety-grid').each(function () {
			const $container = $(this).find(
				'.revx-campaign-container__wrapper'
			);
			const containerElement = $container.get(0);
			const computedStyle = getComputedStyle(containerElement);

			let gridColumnValue = parseInt(
				computedStyle.getPropertyValue('--revx-grid-column').trim()
			);
			const minSlideWidth = 132; // 12rem in pixels (assuming 1rem = 16px)

			const $triggerItemContainer = $container.find(
				'.revx-bxgy-trigger-items'
			);
			const $offerItemContainer = $container.find(
				'.revx-bxgy-offer-items'
			);

			let triggerItemColumn = parseInt(
				getComputedStyle($triggerItemContainer.get(0))
					.getPropertyValue('--revx-grid-column')
					.trim()
			);
			let offerItemColumn = parseInt(
				getComputedStyle($offerItemContainer.get(0))
					.getPropertyValue('--revx-grid-column')
					.trim()
			);

			let containerWidth = $container.width();

			const seperatorWidth = $container
				.find('.revx-product-bundle')
				.width();

			containerWidth -= seperatorWidth;

			gridColumnValue = gridColumnValue ? gridColumnValue : 4;

			gridColumnValue = Math.min(
				gridColumnValue,
				Math.floor(containerWidth / minSlideWidth)
			);
			triggerItemColumn = Math.min(
				$triggerItemContainer.find('.revx-campaign-item').length,
				triggerItemColumn
			);
			offerItemColumn = Math.min(
				$offerItemContainer.find('.revx-campaign-item').length,
				offerItemColumn
			);

			gridColumnValue = Math.min(
				gridColumnValue,
				triggerItemColumn + offerItemColumn
			);

			// Ensure the total columns for trigger and offer items do not exceed the available grid columns
			if (triggerItemColumn + offerItemColumn > gridColumnValue) {
				const excessColumns =
					triggerItemColumn + offerItemColumn - gridColumnValue;

				// Adjust columns proportionally to ensure total columns match gridColumnValue
				const triggerAdjustment = Math.floor(
					(triggerItemColumn /
						(triggerItemColumn + offerItemColumn)) *
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

			$container.css('visibility', 'visible');
		});
	}

	function initializeSubSlider(
		$sliderContainer,
		itemColumn,
		slideWidth,
		type
	) {
		const $container = $sliderContainer.find('.revx-slider-container');
		const itemGap = parseInt(
			getComputedStyle($container.get(0)).getPropertyValue('gap').trim()
		);

		// slideWidth -=itemGap;
		slideWidth -= itemGap;
		const containerWidth = itemColumn * slideWidth;
		$sliderContainer.width(containerWidth);

		$sliderContainer = $container;

		const $slides = $sliderContainer.find('.revx-campaign-item');
		$slides.css({ width: slideWidth + 'px' });

		const totalSlides = $slides.length;
		let slideIndex = 0; // Start at the first slide

		function moveToSlide(index) {
			let tempWidth = slideWidth;
			tempWidth += itemGap;
			tempWidth += index;

			if (itemColumn == 1) {
				tempWidth += itemGap;
			}

			const offset = -tempWidth * index;

			$sliderContainer.css({
				transition: 'transform 0.5s ease-in-out',
				transform: `translateX(${offset}px)`,
			});
		}

		function moveToNextSlide() {
			slideIndex++;
			if (slideIndex > totalSlides - itemColumn) {
				slideIndex = 0;
			}
			moveToSlide(slideIndex);
		}

		function moveToPrevSlide() {
			slideIndex--;
			if (slideIndex < 0) {
				slideIndex = totalSlides - itemColumn;
			}
			moveToSlide(slideIndex);
		}

		$sliderContainer
			.siblings('.revx-builderSlider-right')
			.click(function () {
				if (!$sliderContainer.is(':animated')) {
					moveToNextSlide();
				}
			});

		$sliderContainer
			.siblings('.revx-builderSlider-left')
			.click(function () {
				if (!$sliderContainer.is(':animated')) {
					moveToPrevSlide();
				}
			});

		$(window).resize(function () {
			moveToSlide(slideIndex);
		});

		moveToSlide(slideIndex);
	}
});
