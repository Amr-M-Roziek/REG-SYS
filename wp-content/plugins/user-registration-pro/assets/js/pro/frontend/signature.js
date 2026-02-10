/* global SignaturePad */

/**
 * Everest Forms signatures.
 *
 * @since 1.4.2
 */
var URSignatures = window.URSignatures || ( function( document, window, $ ) {
	'use strict';

	var app = {

		/**
		 * Configurations.
		 *
		 * @since 1.4.2
		 *
		 * @type object
		 */
		config: {
			width: false,
			changes: false,
			pixelRatio : Math.max( window.devicePixelRatio || 1, 1 ),
		},

		/**
		 * Resides all active signature instances.
		 *
		 * @since 1.4.2
		 *
		 * @type object
		 */
		signatures: {},

		/**
		 * Resides IDs of all signatures disabled.
		 *
		 * @since 1.4.2
		 *
		 * @type array
		 */
		signaturesDisabled: [],

		/**
		 * Initialize.
		 *
		 * @since 1.4.2
		 */
		init: function() {
			app.ready();
			$(window).on(
				"user_registration_frontend_after_ajax_complete",
				function (e, ajax_response, type, $this) {
					$(".user-registration-signature-reset").each( function() {
						$(this).trigger("click");
					});
				}
			);
		},

		/**
		 * Initialize once the DOM is fully loaded.
		 *
		 * @since 1.4.2
		 */
		ready: function() {
			// Set window width for resize events.
			app.config.width = $( window ).width();

			// Initialize instances.
			app.loadSignatures();

			// Bind button to reset signature.
			$( document ).on( 'click', '.user-registration-signature-reset', function( event ) {
				event.preventDefault();
				app.resetSignature( $( this ).parent().find( '.user-registration-signature-canvas' ) );
			} );

			// Bind window resize to reset signatures.
			$( window ).resize( app.resetSignatures );

			// If found hidden signatures, enable the visibility check.
			if ( app.signaturesDisabled.length > 0 ) {
				app.config.changes = setInterval( app.signatureChanges, 300 );
			}

			$( document ).on( 'ur-forms-signature-init', function( event, el ) {
				app.loadSignature( $( el ) );
			} );
		},

		/**
		 * Finds, creates and loads each signature instance.
		 *
		 * @since 1.4.2
		 */
		loadSignatures: function() {
			$( '.user-registration-signature-canvas' ).each( function() {

				app.loadSignature( $( this ) );
			});
		},

		/**
		 * Reset signatures when the viewport size is changed.
		 *
		 * @since 1.4.2
		 */
		resetSignatures: function() {
			// Return if the viewport width has not changed.
			if ( app.config.width === $( window ).width() )  {
				return;
			}

			$( '.user-registration-signature-canvas' ).each( function() {
				app.resetSignature( $( this ) );
			});
		},

		/**
		 * Returns the canvas element from jQuery signature object.
		 *
		 * Also adapt necessary adjustments for high-res displays.
		 *
		 * @since 1.4.2
		 *
		 * @param $signature Signature object.
		 *
		 * @return object
		 */
		getCanvas: function( $signature ) {
			var canvas = $signature.get( 0 );

			// This fixes issues with high res/retina displays.
			canvas.width  = canvas.offsetWidth * app.config.pixelRatio;
			canvas.height = canvas.offsetHeight * app.config.pixelRatio;
			canvas.getContext( '2d' ).scale( app.config.pixelRatio, app.config.pixelRatio );

			return canvas;
		},

		/**
		 * Creates and loads a single signature instance.
		 *
		 * @since 1.4.2
		 *
		 * @param $signature jQuery signature object.
		 */
		loadSignature: function( $signature ) {
			var $wrap  = $signature.closest( '.user-registration-field-signature' ),
				$input = $wrap.find( '.user-registration-signature-input' ),
				id = $signature.attr( 'id' ),
				canvas = app.getCanvas( $signature );

			if (  $signature.is( ':hidden' ) ) {
				// Canvas is currently hidden, so don't initialize yet.
				app.signaturesDisabled.push( id );
			} else {
				// Creates/recreates the signature instance.
				app.signatures[ id ] = new SignaturePad( canvas, {
					// penColor: evf_signature_params.pen_color,
					// backgroundColor: evf_signature_params.background_color,
					onEnd: function() {
						var imgFormat = canvas.parentNode.getAttribute( 'data-image-format' );
						$input.val( this.toDataURL( imgFormat ) ).trigger( 'input change' ).valid();
					}
				} );
			}
		},

		/**
		 * Reset the canvas for a signature.
		 *
		 * @since 1.4.2
		 *
		 * @param $signature jQuery signature object.
		 */
		resetSignature: function( $signature ) {
			var $wrap  = $signature.closest( '.user-registration-field-signature' ),
				$input = $wrap.find( '.user-registration-signature-input' ),
				id     = $signature.attr( 'id' );

			// Properly scale canvas.
			app.getCanvas( $signature );
			// Reset/clear signature.
			if ( app.signatures[ id ] ) {
				app.signatures[ id ].clear();
			}

			$input.val( '' ).trigger( 'input change' );

			// Check if signature is hidden.
			if ( $signature.is( ':hidden' ) ) {
				// Check if signature was not previously hidden.
				if ( $.inArray( id, app.signaturesDisabled ) === -1 ) {
					app.signaturesDisabled.push( id );
				}

				// Remove from active storage.
				if ( app.signatures[ id ] ) {
					delete app.signatures[ id ];
				}

				// Enable visibility check.
				if ( ! app.config.changes ) {
					app.config.changes = setInterval( app.signatureChanges, 300 );
				}
			}
		},

		/**
		 * Detects signature visibility when they become visible.
		 *
		 * @since 1.4.2
		 */
		signatureChanges: function() {
			// Update changes if signature is visible.
			if ( app.signaturesDisabled.length < 1 ) {
				clearInterval( app.config.changes );
				app.config.changes = false;
				return;
			}

			// Loop through all the hidden signatures.
			for ( var key in app.signaturesDisabled ) {
				var $signature = $( '#' + app.signaturesDisabled[ key ] );

				// Signature is now visible.
				if ( ! $signature.is( ':hidden' ) ) {
					// Remove the disabled signatures.
					app.signaturesDisabled.splice( key, 1 );

					// Since it is visible, lets load it!
					app.loadSignature( $signature );
				}
			}
		}
	};

	return app;

})( document, window, jQuery );

// Initialize.
URSignatures.init();
