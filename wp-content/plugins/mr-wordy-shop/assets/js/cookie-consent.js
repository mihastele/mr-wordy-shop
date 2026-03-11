/**
 * MR Wordy Shop – Cookie Consent Banner
 *
 * Shows a GDPR-style consent banner on first visit.
 * Consent choice is stored in localStorage so the banner
 * is not shown again until the visitor clears storage.
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'mrws_cookie_consent';

	/**
	 * Check whether the user has already responded.
	 *
	 * @return {string|null} "accepted", "declined", or null.
	 */
	function getConsent() {
		try {
			return localStorage.getItem( STORAGE_KEY );
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Persist the user's choice.
	 *
	 * @param {string} value "accepted" or "declined".
	 */
	function setConsent( value ) {
		try {
			localStorage.setItem( STORAGE_KEY, value );
		} catch ( e ) {
			// Storage unavailable – silently ignore.
		}
	}

	/**
	 * Hide both the banner and the optional overlay.
	 */
	function hideBanner() {
		var banner  = document.querySelector( '.mrws-cookie-banner' );
		var overlay = document.querySelector( '.mrws-cookie-overlay' );

		if ( banner ) {
			banner.classList.remove( 'mrws-cookie-banner--visible' );
			banner.addEventListener( 'transitionend', function handler() {
				banner.classList.add( 'mrws-cookie-banner--hidden' );
				banner.removeEventListener( 'transitionend', handler );
			} );
		}

		if ( overlay ) {
			overlay.classList.remove( 'mrws-cookie-overlay--visible' );
			overlay.addEventListener( 'transitionend', function handler() {
				overlay.classList.add( 'mrws-cookie-overlay--hidden' );
				overlay.removeEventListener( 'transitionend', handler );
			} );
		}
	}

	/**
	 * Initialise the banner once the DOM is ready.
	 */
	function init() {
		if ( getConsent() ) {
			/* User already responded – remove banner markup entirely. */
			var existing = document.querySelector( '.mrws-cookie-banner' );
			if ( existing ) {
				existing.remove();
			}
			var existingOverlay = document.querySelector( '.mrws-cookie-overlay' );
			if ( existingOverlay ) {
				existingOverlay.remove();
			}
			return;
		}

		/* Show the banner with a small delay so the CSS transition fires. */
		var banner  = document.querySelector( '.mrws-cookie-banner' );
		var overlay = document.querySelector( '.mrws-cookie-overlay' );

		if ( ! banner ) {
			return;
		}

		requestAnimationFrame( function () {
			banner.classList.add( 'mrws-cookie-banner--visible' );
			if ( overlay ) {
				overlay.classList.add( 'mrws-cookie-overlay--visible' );
			}
		} );

		/* Accept button */
		var acceptBtn = banner.querySelector( '.mrws-cookie-banner__btn--accept' );
		if ( acceptBtn ) {
			acceptBtn.addEventListener( 'click', function () {
				setConsent( 'accepted' );
				hideBanner();
			} );
		}

		/* Decline button */
		var declineBtn = banner.querySelector( '.mrws-cookie-banner__btn--decline' );
		if ( declineBtn ) {
			declineBtn.addEventListener( 'click', function () {
				setConsent( 'declined' );
				hideBanner();
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
