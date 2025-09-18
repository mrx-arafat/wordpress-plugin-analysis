(function () {
	'use strict';

	/**
	 * @TODO Checkout and Cart Block Integration Code
	 */
	const { registerCheckoutFilters } = window.wc.blocksCheckout;

	const modifyCartItemClass = (classList, extensions, args) => {
		return classList;
	};

	registerCheckoutFilters('revenue', {
		cartItemClass: modifyCartItemClass,
	});
})();
