/**
 * Portfolio lightbox — dependency-free (no jQuery), accessible.
 *
 * Replaces the old NivoSlider. Auto-initialises against any container
 * marked `[data-fw-pg-lightbox]` whose direct gallery links carry the
 * `.fw-pg__item` class and an `href` pointing at the full-size image.
 * Clicks are delegated, so containers injected after load (AJAX, page
 * builder preview) work without re-init. Navigation cycles within the
 * clicked gallery only.
 *
 * Features: prev/next, keyboard (Esc / ← → / Home / End), focus trap +
 * restore, body scroll-lock, loading spinner, caption + counter, basic
 * touch swipe. One overlay element is built lazily and reused.
 */
( function () {
	'use strict';

	var overlay = null;
	var els = {};
	var group = [];        // [{ href, caption }]
	var index = 0;
	var lastFocused = null;
	var preloads = {};

	var SVG_NS = 'http://www.w3.org/2000/svg';

	function svg( paths ) {
		var s = '<svg viewBox="0 0 24 24" aria-hidden="true">' + paths + '</svg>';
		return s;
	}

	function buildOverlay() {
		if ( overlay ) { return; }

		overlay = document.createElement( 'div' );
		overlay.className = 'fw-pg-lb';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.setAttribute( 'aria-label', 'Image gallery' );
		overlay.setAttribute( 'aria-hidden', 'true' );

		overlay.innerHTML =
			'<button type="button" class="fw-pg-lb__btn fw-pg-lb__close" aria-label="Close">' +
				svg( '<path d="M6 6l12 12M18 6L6 18"/>' ) +
			'</button>' +
			'<button type="button" class="fw-pg-lb__btn fw-pg-lb__prev" aria-label="Previous image">' +
				svg( '<path d="M15 5l-7 7 7 7"/>' ) +
			'</button>' +
			'<button type="button" class="fw-pg-lb__btn fw-pg-lb__next" aria-label="Next image">' +
				svg( '<path d="M9 5l7 7-7 7"/>' ) +
			'</button>' +
			'<div class="fw-pg-lb__stage">' +
				'<div class="fw-pg-lb__spinner" aria-hidden="true"></div>' +
				'<img class="fw-pg-lb__img" alt="" />' +
			'</div>' +
			'<div class="fw-pg-lb__bar">' +
				'<span class="fw-pg-lb__caption"></span>' +
				'<span class="fw-pg-lb__count"></span>' +
			'</div>';

		document.body.appendChild( overlay );

		els.img     = overlay.querySelector( '.fw-pg-lb__img' );
		els.caption = overlay.querySelector( '.fw-pg-lb__caption' );
		els.count   = overlay.querySelector( '.fw-pg-lb__count' );
		els.prev    = overlay.querySelector( '.fw-pg-lb__prev' );
		els.next    = overlay.querySelector( '.fw-pg-lb__next' );
		els.close   = overlay.querySelector( '.fw-pg-lb__close' );

		els.close.addEventListener( 'click', closeLb );
		els.prev.addEventListener( 'click', function () { step( -1 ); } );
		els.next.addEventListener( 'click', function () { step( 1 ); } );

		// Click the backdrop (but not the image / controls) to close.
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay || e.target.classList.contains( 'fw-pg-lb__stage' ) ) {
				closeLb();
			}
		} );

		els.img.addEventListener( 'load', function () { overlay.classList.add( 'is-loaded' ); } );

		bindTouch();
	}

	function bindTouch() {
		var startX = 0, startY = 0, tracking = false;
		overlay.addEventListener( 'touchstart', function ( e ) {
			if ( e.touches.length !== 1 ) { return; }
			tracking = true;
			startX = e.touches[ 0 ].clientX;
			startY = e.touches[ 0 ].clientY;
		}, { passive: true } );
		overlay.addEventListener( 'touchend', function ( e ) {
			if ( ! tracking ) { return; }
			tracking = false;
			var dx = e.changedTouches[ 0 ].clientX - startX;
			var dy = e.changedTouches[ 0 ].clientY - startY;
			if ( Math.abs( dx ) > 45 && Math.abs( dx ) > Math.abs( dy ) ) {
				step( dx < 0 ? 1 : -1 );
			}
		}, { passive: true } );
	}

	function preload( src ) {
		if ( ! src || preloads[ src ] ) { return; }
		var im = new Image();
		im.src = src;
		preloads[ src ] = true;
	}

	function show( i ) {
		var len = group.length;
		index = ( ( i % len ) + len ) % len; // wrap
		var item = group[ index ];

		overlay.classList.remove( 'is-loaded' );
		els.img.alt = item.caption || '';
		els.img.src = item.href;

		els.caption.textContent = item.caption || '';
		els.count.textContent = len > 1 ? ( index + 1 ) + ' / ' + len : '';

		// Preload neighbours.
		if ( len > 1 ) {
			preload( group[ ( index + 1 ) % len ].href );
			preload( group[ ( index - 1 + len ) % len ].href );
		}
	}

	function step( dir ) {
		if ( group.length < 2 ) { return; }
		show( index + dir );
	}

	function openLb( items, start ) {
		buildOverlay();
		group = items;
		lastFocused = document.activeElement;

		overlay.classList.toggle( 'is-single', group.length < 2 );
		document.body.classList.add( 'fw-pg-lb-open' );
		overlay.setAttribute( 'aria-hidden', 'false' );
		overlay.classList.add( 'is-open' );

		show( start );

		document.addEventListener( 'keydown', onKey );
		// Move focus into the dialog for screen-reader + keyboard users.
		els.close.focus();
	}

	function closeLb() {
		if ( ! overlay ) { return; }
		overlay.classList.remove( 'is-open' );
		overlay.setAttribute( 'aria-hidden', 'true' );
		document.body.classList.remove( 'fw-pg-lb-open' );
		document.removeEventListener( 'keydown', onKey );
		// Free the decoded image.
		els.img.removeAttribute( 'src' );
		if ( lastFocused && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
		}
	}

	function onKey( e ) {
		switch ( e.key ) {
			case 'Escape':     closeLb(); break;
			case 'ArrowRight': step( 1 ); break;
			case 'ArrowLeft':  step( -1 ); break;
			case 'Home':       show( 0 ); break;
			case 'End':        show( group.length - 1 ); break;
			case 'Tab':        trapFocus( e ); break;
			default: return;
		}
	}

	// Keep Tab inside the dialog's visible controls.
	function trapFocus( e ) {
		var focusables = [ els.close ];
		if ( group.length > 1 ) { focusables.push( els.prev, els.next ); }
		var first = focusables[ 0 ];
		var last  = focusables[ focusables.length - 1 ];
		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	}

	// Read a gallery container's items into the lightbox model.
	function collect( container ) {
		var links = container.querySelectorAll( 'a.fw-pg__item' );
		var items = [];
		for ( var i = 0; i < links.length; i++ ) {
			items.push( {
				href: links[ i ].getAttribute( 'href' ),
				caption: links[ i ].getAttribute( 'data-caption' ) || '',
				node: links[ i ]
			} );
		}
		return items;
	}

	// Delegated click — works for galleries added after load.
	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest ? e.target.closest( 'a.fw-pg__item' ) : null;
		if ( ! link ) { return; }
		var container = link.closest( '[data-fw-pg-lightbox]' );
		if ( ! container ) { return; }

		e.preventDefault();

		var items = collect( container );
		var start = 0;
		for ( var i = 0; i < items.length; i++ ) {
			if ( items[ i ].node === link ) { start = i; break; }
		}
		openLb( items, start );
	} );
} )();
