/**
 * Portfolio grid — AJAX category filtering + load-more pagination.
 * Dependency-free (no jQuery, no Isotope).
 *
 * Each [data-fw-portfolio-grid] wrapper carries `data-pf-query` (the
 * server-exported, re-validated-on-request query JSON), `data-pf-page` and
 * `data-pf-max`. Filter buttons (`data-filter` = term id, "0" = All) re-query
 * page 1 and replace the cards; the load-more button fetches the next page
 * and appends. Filters deep-link via `#pf=<term-slug>`. Configuration
 * (ajaxUrl, nonce, strings) arrives via wp_localize_script as
 * window.fwPortfolioGrid. Delegated + idempotent, so builder-preview
 * re-renders work.
 */
( function () {
	'use strict';

	var cfg = window.fwPortfolioGrid || {};

	function grid( wrap ) {
		return wrap.querySelector( '.fw-portfolio-grid' );
	}

	function status( wrap, count ) {
		var el = wrap.querySelector( '.fw-portfolio-sr' );
		if ( el && cfg.shown ) {
			el.textContent = cfg.shown.replace( '%d', String( count ) );
		}
	}

	function setBusy( wrap, busy ) {
		var g = grid( wrap );
		if ( g ) {
			g.setAttribute( 'aria-busy', busy ? 'true' : 'false' );
			g.classList.toggle( 'is-loading', !! busy );
		}
		var btn = wrap.querySelector( '.fw-portfolio-loadmore__btn' );
		if ( btn ) {
			btn.disabled = !! busy;
			if ( busy ) {
				btn.dataset.label = btn.dataset.label || btn.textContent;
				btn.textContent = cfg.loading || 'Loading…';
			} else if ( btn.dataset.label ) {
				btn.textContent = btn.dataset.label;
			}
		}
	}

	// Re-trigger the entrance transition on newly inserted cards.
	function animateNew( nodes ) {
		for ( var i = 0; i < nodes.length; i++ ) {
			if ( nodes[ i ].classList && nodes[ i ].classList.contains( 'fw-portfolio-card' ) ) {
				nodes[ i ].classList.add( 'is-entering' );
			}
		}
		// Force reflow, then release.
		void document.body.offsetWidth;
		for ( var j = 0; j < nodes.length; j++ ) {
			if ( nodes[ j ].classList ) {
				nodes[ j ].classList.remove( 'is-entering' );
			}
		}
	}

	function updateLoadmore( wrap ) {
		var lm = wrap.querySelector( '.fw-portfolio-loadmore' );
		if ( ! lm ) { return; }
		var page = parseInt( wrap.getAttribute( 'data-pf-page' ) || '1', 10 );
		var max  = parseInt( wrap.getAttribute( 'data-pf-max' ) || '1', 10 );
		lm.hidden = ( page >= max );
	}

	function request( wrap, page, append ) {
		var query = wrap.getAttribute( 'data-pf-query' );
		if ( ! query || ! cfg.ajaxUrl || wrap.getAttribute( 'data-pf-busy' ) === '1' ) { return; }

		wrap.setAttribute( 'data-pf-busy', '1' );
		setBusy( wrap, true );

		var body = new FormData();
		body.append( 'action', 'fw_portfolio_load' );
		body.append( 'nonce', cfg.nonce || '' );
		body.append( 'query', query );
		body.append( 'page', String( page ) );
		body.append( 'filter', wrap.getAttribute( 'data-pf-filter' ) || '0' );

		window.fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( ! res || ! res.success || ! res.data ) { return; }

				var g = grid( wrap );
				if ( ! g ) { return; }

				if ( append ) {
					var marker = g.children.length;
					g.insertAdjacentHTML( 'beforeend', res.data.html );
					animateNew( Array.prototype.slice.call( g.children, marker ) );
				} else {
					g.innerHTML = res.data.html;
					animateNew( Array.prototype.slice.call( g.children ) );
				}

				wrap.setAttribute( 'data-pf-page', String( res.data.page ) );
				wrap.setAttribute( 'data-pf-max', String( res.data.max ) );
				updateLoadmore( wrap );
				status( wrap, g.children.length );
			} )
			.catch( function () { /* leave the current cards in place */ } )
			.then( function () {
				wrap.removeAttribute( 'data-pf-busy' );
				setBusy( wrap, false );
			} );
	}

	function activateFilter( wrap, btn, skipHash ) {
		var buttons = wrap.querySelectorAll( '.fw-portfolio-filter' );
		for ( var i = 0; i < buttons.length; i++ ) {
			var on = buttons[ i ] === btn;
			buttons[ i ].classList.toggle( 'is-active', on );
			buttons[ i ].setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		}

		wrap.setAttribute( 'data-pf-filter', btn.getAttribute( 'data-filter' ) || '0' );

		// Deep-link the active filter (slug-based so it survives re-imports).
		if ( ! skipHash && window.history && window.history.replaceState ) {
			var slug = btn.getAttribute( 'data-slug' ) || '';
			var base = window.location.pathname + window.location.search;
			window.history.replaceState( null, '', slug ? base + '#pf=' + slug : base );
		}

		request( wrap, 1, false );
	}

	document.addEventListener( 'click', function ( e ) {
		var target = e.target;
		if ( ! target.closest ) { return; }

		var btn = target.closest( '.fw-portfolio-filter' );
		if ( btn ) {
			var wrap = btn.closest( '[data-fw-portfolio-grid]' );
			if ( wrap && wrap.getAttribute( 'data-pf-query' ) ) {
				e.preventDefault();
				activateFilter( wrap, btn, false );
			}
			return;
		}

		var more = target.closest( '.fw-portfolio-loadmore__btn' );
		if ( more ) {
			var mWrap = more.closest( '[data-fw-portfolio-grid]' );
			if ( mWrap ) {
				e.preventDefault();
				var page = parseInt( mWrap.getAttribute( 'data-pf-page' ) || '1', 10 );
				request( mWrap, page + 1, true );
			}
		}
	} );

	// Apply a deep-linked filter (#pf=<slug>) on load.
	function applyHash() {
		var m = /(?:^|[#&])pf=([^&]+)/.exec( window.location.hash );
		if ( ! m ) { return; }
		var slug  = decodeURIComponent( m[ 1 ] );
		var wraps = document.querySelectorAll( '[data-fw-portfolio-grid]' );
		for ( var i = 0; i < wraps.length; i++ ) {
			var btn = wraps[ i ].querySelector( '.fw-portfolio-filter[data-slug="' + slug + '"]' );
			if ( btn ) {
				activateFilter( wraps[ i ], btn, true );
			}
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', applyHash );
	} else {
		applyHash();
	}
} )();
