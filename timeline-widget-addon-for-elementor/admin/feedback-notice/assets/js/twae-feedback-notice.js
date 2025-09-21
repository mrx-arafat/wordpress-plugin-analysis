jQuery(document).ready(function($) {
	$('.twae_dismiss_notice').on('click', function(event) {
		var $this = $(this);
		var wrapper = $this.parents('.cool-feedback-notice-wrapper');
		var ajaxURL = wrapper.data('ajax-url');
		var ajaxCallback = wrapper.data('ajax-callback');
		var nonce = wrapper.data('wp-nonce');

		$.post(ajaxURL, {
			'action': ajaxCallback,
			'nonce': nonce
			}, function(data) {
			    wrapper.slideUp('fast');
			}, "json"
        );
	});
});