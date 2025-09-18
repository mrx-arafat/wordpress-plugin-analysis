jQuery( document ).ready( function ( $ ) {
	$( '.revx-builder-atc-btn.revx-btn-animation' ).each( function () {
		$( this ).css(
			'animation-duration',
			`${
				$( this ).data( 'animation-delay' ) == 0
					? 0.8
					: $( this ).data( 'animation-delay' )
			}s`
		);
	} );
	$( document ).on(
		{
			mouseenter: function () {
				$( this ).addClass( $( this ).data( 'animation-class' ) );
			},
			mouseleave: function () {
				$( this ).removeClass( $( this ).data( 'animation-class' ) );
			},
		},
		'.revx-btn-animation[data-animation-trigger-type=on_hover]'
	);
} );
