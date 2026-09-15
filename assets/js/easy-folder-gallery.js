/* Easy Folder Gallery — minimal dependency-free lightbox. */
( function () {
	'use strict';

	function init() {
		var links = Array.prototype.slice.call( document.querySelectorAll( '.efg-photos a.efg-photo' ) );
		if ( ! links.length ) {
			return;
		}

		var overlay = document.createElement( 'div' );
		overlay.className = 'efg-lightbox';
		overlay.innerHTML =
			'<button type="button" class="efg-lb-close" aria-label="Close">&times;</button>' +
			'<button type="button" class="efg-lb-prev" aria-label="Previous">&#10094;</button>' +
			'<img alt="">' +
			'<button type="button" class="efg-lb-next" aria-label="Next">&#10095;</button>';
		document.body.appendChild( overlay );

		var img = overlay.querySelector( 'img' );
		var current = 0;

		function show( i ) {
			current = ( i + links.length ) % links.length;
			img.src = links[ current ].href;
			overlay.classList.add( 'is-open' );
		}

		function close() {
			overlay.classList.remove( 'is-open' );
			img.removeAttribute( 'src' );
		}

		links.forEach( function ( link, i ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				show( i );
			} );
		} );

		overlay.querySelector( '.efg-lb-close' ).addEventListener( 'click', close );
		overlay.querySelector( '.efg-lb-prev' ).addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			show( current - 1 );
		} );
		overlay.querySelector( '.efg-lb-next' ).addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			show( current + 1 );
		} );
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				close();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! overlay.classList.contains( 'is-open' ) ) {
				return;
			}
			if ( 'Escape' === e.key ) {
				close();
			} else if ( 'ArrowLeft' === e.key ) {
				show( current - 1 );
			} else if ( 'ArrowRight' === e.key ) {
				show( current + 1 );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
