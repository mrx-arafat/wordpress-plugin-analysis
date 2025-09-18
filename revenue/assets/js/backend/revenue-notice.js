jQuery(document).ready(function($) {
	$('#revx-activate-woocommerce').on('click', function(e) {
		e.preventDefault();
		let $button = $(this);
		$button.removeClass('installing').addClass('activating');
		$button.text('Activating...').append('<span class="spinner"></span>');
		$.ajax({
			url: revenue?.ajax,
			type: 'POST',
			data: {
				action: 'revx_activate_woocommerce',
				security: revenue?.nonce
			},
			success: function(response) {
				$button.removeClass('activating');
				$button.find('.spinner').hide();
				if (response.success) {
					location.reload();
				} else {
					console.log( response.data );
				}
			},
			error: function() {
				$button.removeClass('activating');
				$button.find('.spinner').hide();
				console.log('There was an error activating WooCommerce.');
			}
		});
	});

	$('#revx-install-woocommerce').on('click', function(e) {
		e.preventDefault();
		var $button = $(this);
		$button.addClass('installing');
		$button.find('.spinner').show();
		$button.text('Installing...').append('<span class="spinner"></span>');
		$.ajax({
			url: revenue?.ajax,
			type: 'POST',
			data: {
				action: 'revx_install_woocommerce',
				security: revenue?.nonce,
			},
			success: function(response) {
				if (response.success) {
					$button.removeClass('installing').addClass('activating');
					$button.text('Activating...').append('<span class="spinner"></span>');
					$.ajax({
						url: revenue?.ajax,
						type: 'POST',
						data: {
							action: 'revx_activate_woocommerce',
							security: revenue?.nonce
						},
						success: function(response) {
							$button.removeClass('activating');
							$button.find('.spinner').hide();
							if (response.success) {
								location.reload();
							} else {
								console.log(response.data);
							}
						},
						error: function() {
							$button.removeClass('activating');
							$button.find('.spinner').hide();
							console.log('There was an error activating WooCommerce.');
						}
					});
				} else {
					$button.removeClass('installing');
					$button.find('.spinner').hide();
					console.log( response.data );
				}
			},
			error: function() {
				$button.removeClass('installing');
				$button.find('.spinner').hide();
				console.log('There was an error installing WooCommerce.');
			}
		});
	});
});

