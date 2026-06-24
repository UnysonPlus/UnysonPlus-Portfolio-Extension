/**
 * Portfolio grid filtering — dependency-free (no jQuery, no Isotope).
 *
 * Each [data-fw-portfolio-grid] wrapper has a filter bar of buttons carrying
 * `data-filter` (either "*" for All or "cat-<termId>") and a grid of
 * `.fw-portfolio-card` items whose class list contains the matching
 * `cat-<termId>` tokens. Clicking a filter toggles `.is-hidden` on the cards.
 * Delegated + idempotent, so builder-preview re-renders work.
 */
( function () {
	'use strict';

	function applyFilter( wrap, filter ) {
		var cards = wrap.querySelectorAll( '.fw-portfolio-card' );
		for ( var i = 0; i < cards.length; i++ ) {
			var card = cards[ i ];
			var match = filter === '*' || card.classList.contains( filter );
			if ( match ) {
				card.classList.remove( 'is-hidden' );
				// Re-trigger the entrance transition.
				card.classList.add( 'is-entering' );
				/* eslint-disable no-unused-expressions */
				card.offsetWidth; // force reflow
				/* eslint-enable no-unused-expressions */
				card.classList.remove( 'is-entering' );
			} else {
				card.classList.add( 'is-hidden' );
			}
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '.fw-portfolio-filter' ) : null;
		if ( ! btn ) { return; }

		var wrap = btn.closest( '[data-fw-portfolio-grid]' );
		if ( ! wrap ) { return; }

		e.preventDefault();

		var buttons = wrap.querySelectorAll( '.fw-portfolio-filter' );
		for ( var i = 0; i < buttons.length; i++ ) {
			buttons[ i ].classList.toggle( 'is-active', buttons[ i ] === btn );
		}

		applyFilter( wrap, btn.getAttribute( 'data-filter' ) || '*' );
	} );
} )();
