( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		try {
			if ( revenue_countdown ) {
				const countDownData = Object.keys( revenue_countdown.data );

				countDownData.forEach( ( campaignID ) => {
					const startTime = revenue_countdown.data[ campaignID ]
						.start_time
						? new Date(
								revenue_countdown.data[ campaignID ].start_time
						  ).getTime()
						: null;
					const endTime = new Date(
						revenue_countdown.data[ campaignID ].end_time
					).getTime();
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
								`.revx-countdown-timer-container[data-campaign-id=${ campaignID }]`
							).addClass( 'revx-d-none' );
							// $(`.revx-countdown-timer-container`).addClass('revx-d-none'); // Hide the element
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
							`#revx-campaign-countdown-${ campaignID } .revx-day`
						).text( days );
						$(
							`#revx-campaign-countdown-${ campaignID } .revx-hour`
						).text( hours );
						$(
							`#revx-campaign-countdown-${ campaignID } .revx-minute`
						).text( minutes );
						$(
							`#revx-campaign-countdown-${ campaignID } .revx-second`
						).text( seconds );
					};

					// Call the updateCountdown function initially to set the first values
					updateCountdown();

					// Update the countdown every second
					const interval = setInterval( updateCountdown, 1000 );

					// Show the countdown timer only after the initial values are set
					//  $(`#revx-countdown-timer-${campaignID}`).removeClass('revx-d-none');

					$(
						`.revx-countdown-timer-container[data-campaign-id=${ campaignID }]`
					).removeClass( 'revx-d-none' );
				} );
			}
		} catch ( error ) {}
	} );
} )( jQuery );
