// /* eslint-disable no-undef */
jQuery( document ).ready( function ( $ ) {
	$( '.rvex-stock-scarcity-wrapper' ).each( function () {
		const $this = $( this );
		const campaignId = $( this ).attr( 'data-campaign-id' );
		const productId = $( this ).attr( 'data-product-id' );
		let stockScarcityData = $(
			`input[name="revx-stock-scarcity-data-${ campaignId }"]`
		);
		stockScarcityData = stockScarcityData[ 0 ].value;
		const config = JSON.parse( stockScarcityData );
		// Ensure the script runs only on the single product page
		// if ( typeof single_product_data === 'undefined' ) {
		// 	return;
		// }
		$.ajax( {
			url: single_product_data.ajax_url,
			type: 'POST',
			data: {
				action: 'update_product_views',
				product_id: productId,
				campaign_id: campaignId,
			},
			success: function ( response ) {
				console.log( 'View count updated', response );
			},
			error: function ( xhr, status, error ) {
				console.log( 'Error:', error );
			},
		} );

		//stock scarcity stock bar
		$( document ).on( 'submit', 'form.cart', function () {
			const currentPage = $( '#wsx_current_page' ).val();
			$( '<input>' )
				.attr( {
					type: 'hidden',
					name: 'wsx_current_page',
					value: currentPage,
				} )
				.appendTo( this );
		} );
	} );
} );
