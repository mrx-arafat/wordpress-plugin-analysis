(function ($) {
	'use strict';

	// Utility functions grouped into a single object
	const Utils = {
		parsePx: (value) => parseFloat(value.replace(/px/, '')),
		getRandomInRange: (min, max, precision = 0) => {
			const multiplier = Math.pow(10, precision);
			const randomValue = Math.random() * (max - min) + min;
			return Math.floor(randomValue * multiplier) / multiplier;
		},
		getRandomItem: (array) =>
			array[Math.floor(Math.random() * array.length)],
		getScaleFactor: () => Math.log(window.innerWidth) / Math.log(1920),
		debounce: (func, delay) => {
			let timeout;
			return (...args) => {
				clearTimeout(timeout);
				timeout = setTimeout(() => func(...args), delay);
			};
		},
	};

	// Precomputed constants
	const DEG_TO_RAD = Math.PI / 180;

	// Centralized configuration for default values
	const defaultConfettiConfig = {
		confettiesNumber: 250,
		confettiRadius: 6,
		confettiColors: [
			'#fcf403',
			'#62fc03',
			'#f4fc03',
			'#03e7fc',
			'#03fca5',
			'#a503fc',
			'#fc03ad',
			'#fc03c2',
		],
		emojies: [],
		svgIcon: null,
	};

	// Confetti Classes
	class Confetti {
		constructor({
			initialPosition,
			direction,
			radius,
			colors,
			emojis,
			svgIcon,
		}) {
			const speedFactor =
				Utils.getRandomInRange(0.9, 1.7, 3) * Utils.getScaleFactor();
			this.speed = { x: speedFactor, y: speedFactor };
			this.finalSpeedX = Utils.getRandomInRange(0.2, 0.6, 3);
			this.rotationSpeed =
				emojis.length || svgIcon
					? 0.01
					: Utils.getRandomInRange(0.03, 0.07, 3) *
						Utils.getScaleFactor();
			this.dragCoefficient = Utils.getRandomInRange(0.0005, 0.0009, 6);
			this.radius = { x: radius, y: radius };
			this.initialRadius = radius;
			this.rotationAngle =
				direction === 'left'
					? Utils.getRandomInRange(0, 0.2, 3)
					: Utils.getRandomInRange(-0.2, 0, 3);
			this.emojiRotationAngle = Utils.getRandomInRange(0, 2 * Math.PI);
			this.radiusYDirection = 'down';

			const angle =
				direction === 'left'
					? Utils.getRandomInRange(82, 15) * DEG_TO_RAD
					: Utils.getRandomInRange(-15, -82) * DEG_TO_RAD;
			this.absCos = Math.abs(Math.cos(angle));
			this.absSin = Math.abs(Math.sin(angle));

			const offset = Utils.getRandomInRange(-150, 0);
			const position = {
				x:
					initialPosition.x +
					(direction === 'left' ? -offset : offset) * this.absCos,
				y: initialPosition.y - offset * this.absSin,
			};

			this.position = { ...position };
			this.initialPosition = { ...position };
			this.color =
				emojis.length || svgIcon ? null : Utils.getRandomItem(colors);
			this.emoji = emojis.length ? Utils.getRandomItem(emojis) : null;
			this.svgIcon = null;
			this.createdAt = Date.now();
			this.direction = direction;

			if (svgIcon) {
				this.svgImage = new Image();
				this.svgImage.src = svgIcon;
				this.svgImage.onload = () => {
					this.svgIcon = this.svgImage;
				};
			}
		}

		draw(context) {
			const { x, y } = this.position;
			const { x: radiusX, y: radiusY } = this.radius;
			const scale = window.devicePixelRatio;

			if (this.svgIcon) {
				context.save();
				context.translate(scale * x, scale * y);
				context.rotate(this.emojiRotationAngle);
				context.drawImage(
					this.svgIcon,
					-radiusX,
					-radiusY,
					radiusX * 2,
					radiusY * 2
				);
				context.restore();
			} else if (this.color) {
				context.fillStyle = this.color;
				context.beginPath();
				context.ellipse(
					x * scale,
					y * scale,
					radiusX * scale,
					radiusY * scale,
					this.rotationAngle,
					0,
					2 * Math.PI
				);
				context.fill();
			} else if (this.emoji) {
				context.font = `${radiusX * scale}px serif`;
				context.save();
				context.translate(scale * x, scale * y);
				context.rotate(this.emojiRotationAngle);
				context.textAlign = 'center';
				context.fillText(this.emoji, 0, radiusY / 2);
				context.restore();
			}
		}

		updatePosition(deltaTime, currentTime) {
			const elapsed = currentTime - this.createdAt;

			if (this.speed.x > this.finalSpeedX) {
				this.speed.x -= this.dragCoefficient * deltaTime;
			}

			this.position.x +=
				this.speed.x *
				(this.direction === 'left' ? -this.absCos : this.absCos) *
				deltaTime;
			this.position.y =
				this.initialPosition.y -
				this.speed.y * this.absSin * elapsed +
				(0.00125 * Math.pow(elapsed, 2)) / 2;

			if (!this.emoji && !this.svgIcon) {
				this.rotationSpeed -= 1e-5 * deltaTime;
				this.rotationSpeed = Math.max(this.rotationSpeed, 0);

				if (this.radiusYDirection === 'down') {
					this.radius.y -= deltaTime * this.rotationSpeed;
					if (this.radius.y <= 0) {
						this.radius.y = 0;
						this.radiusYDirection = 'up';
					}
				} else {
					this.radius.y += deltaTime * this.rotationSpeed;
					if (this.radius.y >= this.initialRadius) {
						this.radius.y = this.initialRadius;
						this.radiusYDirection = 'down';
					}
				}
			}
		}

		isVisible(canvasHeight) {
			return this.position.y < canvasHeight + 100;
		}
	}

	class ConfettiManager {
		constructor() {
			this.canvas = document.createElement('canvas');
			this.canvas.style =
				'position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; pointer-events: none;';
			document.body.appendChild(this.canvas);
			this.context = this.canvas.getContext('2d');
			this.confetti = [];
			this.lastUpdated = Date.now();
			window.addEventListener(
				'resize',
				Utils.debounce(() => this.resizeCanvas(), 200)
			);
			this.resizeCanvas();
			requestAnimationFrame(() => this.loop());
		}

		resizeCanvas() {
			this.canvas.width = window.innerWidth * window.devicePixelRatio;
			this.canvas.height = window.innerHeight * window.devicePixelRatio;
		}

		addConfetti(config = {}) {
			const {
				confettiesNumber,
				confettiRadius,
				confettiColors,
				emojies,
				svgIcon,
			} = {
				...defaultConfettiConfig,
				...config,
			};

			const baseY = (5 * window.innerHeight) / 7;
			for (let i = 0; i < confettiesNumber / 2; i++) {
				this.confetti.push(
					new Confetti({
						initialPosition: { x: 0, y: baseY },
						direction: 'right',
						radius: confettiRadius,
						colors: confettiColors,
						emojis: emojies,
						svgIcon,
					})
				);
				this.confetti.push(
					new Confetti({
						initialPosition: { x: window.innerWidth, y: baseY },
						direction: 'left',
						radius: confettiRadius,
						colors: confettiColors,
						emojis: emojies,
						svgIcon,
					})
				);
			}
		}

		resetAndStart(config = {}) {
			this.confetti = [];
			this.addConfetti(config);
		}

		loop() {
			const currentTime = Date.now();
			const deltaTime = currentTime - this.lastUpdated;
			this.lastUpdated = currentTime;

			this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);

			this.confetti = this.confetti.filter((item) => {
				item.updatePosition(deltaTime, currentTime);
				item.draw(this.context);
				return item.isVisible(this.canvas.height);
			});

			requestAnimationFrame(() => this.loop());
		}
	}

	// Main Progress Class
	class RevenueProgress {
		constructor(options) {
			this.defaults = {
				containerId: '#revx-progress-drawer',
				offersFieldName: 'revenue_spending_goal_offer',
				ajaxUrl: '',
				debugMode: false,
				refreshInterval: 10000,
				type: 'drawer', // 'drawer' or 'inpage'
			};

			this.settings = $.extend({}, this.defaults, options);
			this.container = $(this.settings.containerId);
			this.state = {
				isOpen: false,
				currentProgress: 0,
				totalGoal: 0,
				activeOffers: [],
				unlockedRewards: [],
				cartTotal: 0,
				isUpdating: false,
				radius: this.container.data('radius'),
				showConfetti: this.container.data('show-confetti'),
			};

			this.confetti = new ConfettiManager();
			this.initializeOffers();
		}

		initializeOffers() {
			try {
				const offersField = $(
					`input[name="${this.settings.offersFieldName}"]`
				);
				this.state.cartTotal = this.container.data('cart-total');

				if (offersField.length) {
					this.settings.offers = JSON.parse(
						offersField.val() || '[]'
					);
					this.init();
				} else {
					throw new Error('Offers hidden field not found');
				}
			} catch (error) {
				console.error('Revenue X: Error parsing offers data:', error);
			}
		}

		init() {
			if (!this.validateSetup()) {
				return;
			}

			this.cacheDOM();
			this.calculateTotalGoal();
			this.bindEvents();
			this.initSliderIfNeeded();
		}

		validateSetup() {
			if (
				!this.container.length ||
				!this.settings.offers?.length ||
				!this.settings.ajaxUrl
			) {
				console.error('Revenue X: Invalid setup');
				return false;
			}
			return true;
		}

		cacheDOM() {
			this.elements = {
				content: this.container.find(
					this.settings.type === 'drawer'
						? '.revx-drawer-content'
						: '.revx-progress-content'
				),
				progressBar: this.container.find('.revx-progress-fill'),
				closeBtn: this.container.find('.revx-close-btn'),
				circularProgress: this.container.find('.revx-progress-active'),
				circularText: this.container.find('.revx-circular-text'),
				message: this.container.find('.revx-message'),
				steps: this.container.find('.revx-progress-step'),
				rewardMessage: this.container.find(
					'.revx-spending-goal-reward-message'
				),
				finalMessage: this.container.data('final-message'),
			};
		}

		calculateTotalGoal() {
			this.state.totalGoal = this.settings.offers.reduce(
				(sum, offer) => sum + parseFloat(offer.spending_goal || 0),
				0
			);
		}

		bindEvents() {
			// Common events
			$(document.body).on(
				'updated_cart_totals added_to_cart removed_from_cart wc-blocks_added_to_cart wc-blocks_removed_from_cart',
				() => this.handleCartUpdate()
			);

			// Add to cart button events
			$(document).on('click', '.revx-spending-goal-add-cart', (e) => {
				e.preventDefault();
				this.handleAddToCart($(e.currentTarget));
			});

			// Drawer-specific events
			if (this.settings.type === 'drawer') {
				this.container
					.find('.revx-circular-progress')
					.on('click', () => this.toggleDrawer());
				this.elements.closeBtn.on('click', (e) => {
					e.stopPropagation();
					this.closeDrawer();
				});
				$(document).on('click', (e) => {
					if (
						!$(e.target).closest(this.settings.containerId).length
					) {
						this.closeDrawer();
					}
				});
			}

			$(document).on(
				'click',
				'.revx-spending-goal-gift__action.add_to_cart',
				(e) => {
					this.addFreeGiftToCart($(e.currentTarget));
				}
			);
			$(document).on(
				'click',
				'.revx-spending-goal-gift__action.minus.on-cart',
				(e) => {
					this.removeGiftFromCart($(e.currentTarget));
				}
			);
		}

		handleCartUpdate() {
			if ($(this.container).hasClass('hide')) {
				$(this.container).removeClass('hide');
			}

			// window.location.reload();
			

			if (this._cartUpdateTimeout) {
				clearTimeout(this._cartUpdateTimeout);
			}
			this._cartUpdateTimeout = setTimeout(
				() => this.getCartTotal(),
				300
			);
		}

		getCartTotal() {
			if (this.state.isUpdating) {
				return;
			}

			this.state.isUpdating = true;
			$.ajax({
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: { action: 'revenue_get_cart_total' },
				success: (response) => {
					if (response.success) {
						const newCartTotal = parseFloat(
							response.data.cart_total
						);
						if (newCartTotal !== this.state.cartTotal) {
							this.state.cartTotal = newCartTotal;
							this.updateProgress(newCartTotal);
						}

						this.updateGiftHeadingMessage(newCartTotal);
					}
				},
				error: (xhr, status, error) => {
					console.error('Revenue X: AJAX error:', error);
				},
				complete: () => {
					this.state.isUpdating = false;
				},
			});
		}

		updateProgress(cartTotal) {
			const stepWidth = 100 / this.settings.offers.length;
			let progress = 0;
			let remainingTotal = cartTotal;

			this.settings.offers.forEach((offer) => {
				if (!offer.spending_goal) {
					return;
				}

				const spendingGoal = parseFloat(offer.spending_goal);
				const contributionToProgress = Math.min(
					(stepWidth / spendingGoal) *
						Math.min(spendingGoal, remainingTotal),
					stepWidth
				);

				progress += contributionToProgress;
				remainingTotal -= Math.min(spendingGoal, remainingTotal);
			});

			this.state.currentProgress = progress;
			this.elements.progressBar.css('width', `${progress}%`);

			// Update circular progress if in drawer mode
			if (this.settings.type === 'drawer' && this.state.radius) {
				const circumference = 2 * Math.PI * this.state.radius;
				const offset = circumference - (progress / 100) * circumference;
				this.elements.circularProgress
					.css('stroke-dasharray', circumference)
					.css('stroke-dashoffset', offset);
				this.elements.circularText.text(`${Math.round(progress)}%`);
			}

			this.updateStepStates(cartTotal);
			this.updateMessages(cartTotal);
		}

		updateStepStates(cartTotal) {
			let accumulatedGoal = 0;

			this.settings.offers.forEach((offer, index) => {
				accumulatedGoal += parseFloat(offer.spending_goal);
				const stepElement = this.elements.steps.eq(index);
				const iconElements = stepElement.find(
					'.revx-step-icon-container, .revx-step-icon'
				);
				const isCompleted = cartTotal >= accumulatedGoal;

				if (iconElements.hasClass('completed')) {
					return;
				}

				iconElements.toggleClass('completed', isCompleted);

				if (
					isCompleted &&
					!this.state.unlockedRewards.includes(offer.key)
				) {
					this.state.unlockedRewards.push(offer.key);
					this.triggerReward(offer);
				}
			});
		}

		updateMessages(cartTotal) {
			let accumulatedGoal = 0;
			let message = '';
			let rewardMessage = '';

			for (const offer of this.settings.offers) {
				if (!offer.spending_goal) {
					continue;
				}

				accumulatedGoal += parseFloat(offer.spending_goal);
				const remainingAmount = Math.abs(accumulatedGoal - cartTotal);

				const rewardType = offer?.reward_type;
				let afterMessage = offer?.after_message??'';
				const discountValue = offer?.discount_value;

				switch (rewardType) {
					case 'discount':
						const discountType = offer?.discount_type;
						if ('percentage' === discountType) {
							afterMessage = afterMessage.replace(
								'{discount_value}',
								(offer?.discount_value ?? 0) + '%'
							);
						} else {
							afterMessage = afterMessage.replace(
								'{discount_value}',
								Revenue.formatPrice(offer?.discount_value ?? 0)
							);
						}

						break;

					default:
						break;
				}

				const REWARD_TYPE_OPTIONS = {
					free_shipping: 'Free Shipping',
					discount: 'Discount',
					gift: 'Gift Items',
				};

				if (cartTotal < accumulatedGoal) {
					message =
						(offer.before_message || '')
							.replace(
								'{remaining_amount}',
								Revenue.formatPrice(remainingAmount.toFixed(2))
							)
							?.replace('{reward_type}', rewardType)
							?.replace('{discount_value}', discountValue) ??
						'Before Message';

					// rewardMessage = offer.after_message || '';
					break;
				} else {
					afterMessage = (afterMessage || '').replace(
						'{remaining_amount}',
						Revenue.formatPrice(remainingAmount.toFixed(2))
					);
					rewardMessage = afterMessage || '';
				}
			}

			if (cartTotal >= accumulatedGoal) {
				this.elements.message.text(this.elements.finalMessage);
			} else {
				this.elements.message.text(message);
			}

			if (this.elements.rewardMessage.length) {
				this.elements.rewardMessage.text(rewardMessage);
				if (!rewardMessage) {
					this.elements.rewardMessage.parent().addClass('no-message');
				} else {
					this.elements.rewardMessage
						.parent()
						.removeClass('no-message');
				}
			}
		}

		triggerReward(offer) {
			$(document.body).trigger('revx_reward_unlocked', [offer]);
			if (this.state.showConfetti === 'yes') {
				this.confetti.addConfetti();
			}

			switch (offer.reward_type) {
				case 'free_shipping':
					$(document.body).trigger('revx_free_shipping_unlocked', [
						offer,
					]);
					break;
				case 'discount':
					$(document.body).trigger('revx_discount_unlocked', [
						{
							type: offer.discount_type,
							value: offer.discount_value,
							offer,
						},
					]);
					break;
				case 'gift':
					$(document.body).trigger('revx_gift_unlocked', [
						{
							products: offer.gift_products,
							quantity: offer.gift_quantity,
							offer,
						},
					]);
					// this.addFreeGiftToCart(offer);
					break;
			}
		}

		updateGiftHeadingMessage(cartTotal) {
			let accumulatedGoal = 0;
			this.settings.offers.forEach((offer, index) => {
				accumulatedGoal += parseFloat(offer.spending_goal);
				const stepElement = this.elements.steps.eq(index);
				const iconElements = stepElement.find(
					'.revx-step-icon-container, .revx-step-icon'
				);

				if ('gift' === offer?.reward_type) {
					// Get offer details from data attributes or localized script
					const quantity = offer?.gift_quantity;

					const isAll = quantity === 'all';

					let message = '';
					const remainingAmount = cartTotal - accumulatedGoal;

					if (accumulatedGoal > cartTotal) {
						// Format remaining amount using WooCommerce currency format
						const formattedAmount = Revenue.formatPrice(
							Math.abs(remainingAmount)
						);

						if (isAll) {
							message = `Spend ${formattedAmount} more to claim your gift!`;
						} else if (quantity == 1) {
							message = `Spend ${formattedAmount} more to get any ${quantity} item as a gift!`;
						} else {
							message = `Spend ${formattedAmount} more to get any ${quantity} items as a gift!`;
						}
					} else if (isAll) {
						message = 'Congrats! Your gift is here!';
					} else if (quantity == 1) {
						message = `Congrats! Choose any ${quantity} item.`;
					} else {
						message = `Congrats! Choose any ${quantity} items.`;
					}

					// Update the heading message
					$('.revx-spending-goal-gift__heading').html(message);
				}
			});
		}

		handleAddToCart($button) {
			const productId = $button.data('product-id');
			const campaignId = $button.data('campaign-id');
			const quantity =
				$button
					.closest('.revx-spending-goal-actions')
					.find('input[data-name="revx_quantity"]')
					.val() || 1;

			$button.addClass('loading');

			$.ajax({
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'revenue_add_to_cart',
					productId,
					campaignId,
					_wpnonce: revenue_campaign.nonce,
					quantity,
				},
				success: (response) => {
					Revenue.showToast('Added to cart');

					$(document.body).trigger('added_to_cart', [
						response?.data?.fragments,
						response?.data?.cart_hash,
						false,
					]);
				},
				complete: () => {
					$button.removeClass('loading');
				},
			});
		}

		addFreeGiftToCart($button) {
			const productId = $button.data('product-id');

			$.ajax({
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'revenue_add_free_gift',
					campaign_id: this.container.data('campaign-id'),
					product_id: productId,
				},
				success: (response) => {
					if (response.success) {
						if (response.data.status) {
							Revenue.showToast('Free gift added to cart');
							$(document.body).trigger('wc_fragment_refresh');

							location.reload();
						} else {
							Revenue.showToast(response.data.message, 'error');
						}
					}
				},
			});
		}
		removeGiftFromCart($button) {
			const productId = $button.data('product-id');

			$.ajax({
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'revenue_remove_free_gift',
					campaign_id: this.container.data('campaign-id'),
					product_id: productId,
				},
				success: (response) => {
					if (response.success && response.data.status) {
						Revenue.showToast('Free gift removed');
						$(document.body).trigger('wc_fragment_refresh');

						location.reload();
					}
				},
			});
		}

		initSliderIfNeeded() {
			const upsellProducts = JSON.parse(
				$('input[name="revenue_upsell_products"]').val() || '[]'
			);

			if (upsellProducts.length > 0) {
				this.initSlider();
			}
		}

		initSlider() {
			const $slider = $('.revx-spending-goal-slider');
			const $track = $slider.find('.revx-spending-goal-slider-track');
			const $items = $track.find('.revx-spending-goal-product-card');
			let currentIndex = 0;
			const totalItems = $items.length;

			// Clone items for infinite scroll
			const $firstClone = $items.first().clone().addClass('clone');
			const $lastClone = $items.last().clone().addClass('clone');
			$track.append($firstClone);
			$track.prepend($lastClone);

			const updateSlider = (animate = false) => {
				const itemWidth = $items.first().outerWidth(true);

				const translateX = -(currentIndex + 1) * itemWidth;

				$track.css(
					'transition',
					animate ? 'transform 0.3s ease-in-out' : 'none'
				);
				$track.css('transform', `translateX(${translateX}px)`);

				if (currentIndex === -1) {
					setTimeout(() => {
						$track.css('transition', 'none');
						currentIndex = totalItems - 1;
						const finalTranslateX = -(currentIndex + 1) * itemWidth;
						$track.css(
							'transform',
							`translateX(${finalTranslateX}px)`
						);
					}, 300);
				} else if (currentIndex === totalItems) {
					setTimeout(() => {
						$track.css('transition', 'none');
						currentIndex = 0;
						const finalTranslateX = -itemWidth;
						$track.css(
							'transform',
							`translateX(${finalTranslateX}px)`
						);
					}, 300);
				}
			};

			// Navigation
			$('.revx-spending-goal-prev').on('click', () => {
				currentIndex = (currentIndex - 1 + totalItems) % totalItems;
				updateSlider(true);
			});

			$('.revx-spending-goal-next').on('click', () => {
				currentIndex = (currentIndex + 1) % totalItems;
				updateSlider(true);
			});

			// Touch events
			let touchStartX = 0;
			let touchEndX = 0;

			$slider.on('touchstart', (e) => {
				touchStartX = e.touches[0].clientX;
			});

			$slider.on('touchmove', (e) => {
				touchEndX = e.touches[0].clientX;
			});

			$slider.on('touchend', () => {
				const swipeThreshold = 50;
				const swipeDistance = touchEndX - touchStartX;

				if (Math.abs(swipeDistance) > swipeThreshold) {
					if (swipeDistance > 0) {
						$('.revx-spending-goal-prev').trigger('click');
					} else {
						$('.revx-spending-goal-next').trigger('click');
					}
				}
			});

			// Initial setup
			updateSlider();
			// if (this.state.isOpen) {
			// 	updateSlider();
			// }
			$(window).on('load resize', () => updateSlider());
		}

		// Drawer-specific methods
		toggleDrawer() {
			if (this.settings.type !== 'drawer') {
				return;
			}

			this.state.isOpen = !this.state.isOpen;
			this.elements.content.toggleClass('open', this.state.isOpen);

			if (this.state.isOpen) {
				this.updateProgress(this.state.cartTotal);
				this.initSlider();
			}
		}

		closeDrawer() {
			if (this.settings.type !== 'drawer') {
				return;
			}

			this.state.isOpen = false;
			this.elements.content.removeClass('open');
		}

		destroy() {
			if (this._cartUpdateTimeout) {
				clearTimeout(this._cartUpdateTimeout);
			}

			this.container.find('.revx-circular-progress').off();
			this.elements.closeBtn.off();
			$(document).off('click.revenueX');
			$(document.body).off(
				'added_to_cart.revenueX removed_from_cart.revenueX updated_cart_totals.revenueX'
			);
			$('.revx-spending-goal-add-cart').off();
		}
	}

	// Plugin registration
	$.fn.revenueProgress = function (options) {
		return this.each(function () {
			if (!$.data(this, 'revenueProgress')) {
				$.data(this, 'revenueProgress', new RevenueProgress(options));
			}
		});
	};

	// Initialize the plugin
	$(document).ready(function () {
		// Initialize drawer if present
		$('#revx-progress-drawer').revenueProgress({
			ajaxUrl: revenue_campaign.ajax,
			type: 'drawer',
		});

		// Initialize in-page if present
		$('#revx-progress-inpage').revenueProgress({
			ajaxUrl: revenue_campaign.ajax,
			containerId: '#revx-progress-inpage',
			type: 'inpage',
		});
	});
})(jQuery);
