/**
 * Pincode checker (storefront). Vanilla JS; jQuery is used only to hear WooCommerce's own
 * variation events where WooCommerce already loaded it.
 *
 * The page is static and cacheable: the saved pincode lives in a first-party cookie and every
 * answer comes from GET wbpc/v1/check. The server re-checks on add to cart and checkout, so this
 * script only improves the experience; it is not the gatekeeper.
 */
( function () {
	'use strict';

	var cfg = window.wbpcChecker;
	if ( ! cfg ) {
		return;
	}

	var COOKIE = 'wbpc_postcode';

	function readSaved() {
		var match = document.cookie.match( /(?:^|; )wbpc_postcode=([^;]*)/ );
		var parts = match ? decodeURIComponent( match[ 1 ] ).split( ':' ) : [];
		return 2 === parts.length ? { country: parts[ 0 ], postcode: parts[ 1 ] } : null;
	}

	function save( country, postcode ) {
		document.cookie = COOKIE + '=' + encodeURIComponent( country + ':' + postcode ) + '; path=/; max-age=' + ( cfg.cookieDays * 86400 ) + '; SameSite=Lax' + ( 'https:' === location.protocol ? '; Secure' : '' );
	}

	function line( className, text ) {
		var p = document.createElement( 'p' );
		p.className = className;
		p.textContent = text;
		return p;
	}

	function Checker( root ) {
		var self = this;

		this.root = root;
		this.entry = root.querySelector( '[data-wbpc-entry]' );
		this.input = root.querySelector( '[data-wbpc-input]' );
		this.result = root.querySelector( '[data-wbpc-result]' );
		this.change = root.querySelector( '[data-wbpc-change]' );
		this.product = parseInt( root.dataset.product, 10 ) || 0;
		this.form = this.product ? ( root.closest( 'form.cart' ) || document.querySelector( 'form.cart' ) ) : null;
		this.state = null;
		this.blocked = false;

		root.querySelector( '[data-wbpc-check]' ).addEventListener( 'click', function () {
			self.check( self.input.value );
		} );

		// Enter must check the pincode, never submit the surrounding add-to-cart form.
		this.input.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key ) {
				e.preventDefault();
				self.check( self.input.value );
			}
		} );

		this.change.addEventListener( 'click', function () {
			self.edit();
		} );

		this.result.addEventListener( 'click', function ( e ) {
			var pick = e.target.closest( '[data-wbpc-pick]' );
			if ( pick ) {
				self.input.value = pick.dataset.wbpcPick;
				self.check( pick.dataset.wbpcPick );
			} else if ( e.target.closest( '[data-wbpc-retry]' ) ) {
				self.check( self.input.value );
			}
		} );

		if ( this.form ) {
			// Last line on the client; the server validates add to cart regardless.
			this.form.addEventListener( 'submit', function ( e ) {
				if ( self.blocked ) {
					e.preventDefault();
					self.edit();
				}
			}, true );

			// WooCommerce toggles its own "disabled" class on variation changes; re-apply ours after it.
			if ( window.jQuery ) {
				window.jQuery( this.form ).on( 'found_variation reset_data hide_variation show_variation', function () {
					window.setTimeout( function () {
						self.apply();
					}, 0 );
				} );
			}
		}

		var saved = readSaved();
		if ( saved && saved.country === cfg.country ) {
			this.input.value = saved.postcode;
			this.check( saved.postcode );
		} else {
			this.apply();
		}
	}

	Checker.prototype.check = function ( value ) {
		var self = this;
		var postcode = String( value || '' ).trim();

		if ( ! postcode ) {
			this.input.focus();
			return;
		}

		this.root.setAttribute( 'aria-busy', 'true' );
		this.result.textContent = '';
		this.result.appendChild( line( 'wbpc-checker__muted', cfg.i18n.checking ) );

		var query = new URLSearchParams( { postcode: postcode, country: cfg.country, product_id: String( this.product ) } );

		window.fetch( cfg.endpoint + ( cfg.endpoint.indexOf( '?' ) > -1 ? '&' : '?' ) + query.toString(), { credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json().then( function ( body ) {
					if ( ! response.ok ) {
						throw new Error( body && body.message ? body.message : cfg.i18n.error );
					}
					return body;
				} );
			} )
			.then( function ( data ) {
				self.state = data;
				if ( 'invalid' !== data.status ) {
					save( data.country, data.postcode );
				}
				self.render( data );
				self.apply();
				self.root.dispatchEvent( new CustomEvent( 'wbpc:checked', { bubbles: true, detail: data } ) );
			} )
			.catch( function ( error ) {
				self.result.textContent = '';
				self.result.appendChild( line( 'wbpc-checker__error', error.message || cfg.i18n.error ) );
				var retry = document.createElement( 'button' );
				retry.type = 'button';
				retry.className = 'wbpc-checker__link';
				retry.dataset.wbpcRetry = '1';
				retry.textContent = cfg.i18n.retry;
				self.result.appendChild( retry );
			} )
			.finally( function () {
				self.root.removeAttribute( 'aria-busy' );
			} );
	};

	Checker.prototype.render = function ( data ) {
		var m = data.messages;

		this.root.classList.remove( 'is-available', 'is-unavailable', 'is-invalid' );
		this.root.classList.add( 'is-' + data.status );
		this.result.textContent = '';
		this.result.appendChild( line( 'wbpc-checker__headline', m.headline ) );

		[ m.estimate, m.shipping, m.cod ].forEach( function ( text ) {
			if ( text ) {
				this.result.appendChild( line( 'wbpc-checker__detail', text ) );
			}
		}, this );

		if ( data.nearby && data.nearby.length ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'wbpc-checker__nearby';
			wrap.appendChild( line( 'wbpc-checker__muted', cfg.i18n.nearby ) );
			data.nearby.forEach( function ( area ) {
				var pick = document.createElement( 'button' );
				pick.type = 'button';
				pick.className = 'wbpc-checker__chip';
				pick.dataset.wbpcPick = area.code;
				pick.textContent = area.city ? area.code + ' (' + area.city + ')' : area.code;
				wrap.appendChild( pick );
			} );
			this.result.appendChild( wrap );
		}

		var settled = 'invalid' !== data.status;
		this.entry.hidden = settled;
		this.change.hidden = ! settled;
		this.input.setAttribute( 'aria-invalid', settled ? 'false' : 'true' );
		if ( ! settled ) {
			this.input.focus();
		}
	};

	Checker.prototype.edit = function () {
		this.entry.hidden = false;
		this.change.hidden = true;
		this.input.focus();
		this.input.select();
	};

	/**
	 * Add to cart: blocked while a required check is missing or the answer is "no" (per settings).
	 * We own the `disabled` attribute and the wbpc-atc-hidden class; WooCommerce owns its `disabled` class.
	 */
	Checker.prototype.apply = function () {
		if ( ! this.form ) {
			return;
		}

		var data = this.state;
		this.blocked = data ? ! data.can_add : !! cfg.requireCheck;

		if ( ! data && cfg.requireCheck && ! this.result.textContent ) {
			this.result.appendChild( line( 'wbpc-checker__muted', cfg.i18n.required ) );
		}

		var blocked = this.blocked;
		var hide = 'hide' === cfg.action;

		this.form.querySelectorAll( '.single_add_to_cart_button' ).forEach( function ( button ) {
			button.classList.toggle( 'wbpc-atc-hidden', blocked && hide );
			if ( blocked && ! hide ) {
				button.setAttribute( 'disabled', 'disabled' );
				button.setAttribute( 'aria-disabled', 'true' );
				button.dataset.wbpcBlocked = '1';
			} else if ( button.dataset.wbpcBlocked ) {
				button.removeAttribute( 'disabled' );
				button.removeAttribute( 'aria-disabled' );
				delete button.dataset.wbpcBlocked;
			}
		} );
	};

	function boot() {
		document.querySelectorAll( '[data-wbpc-checker]:not([data-wbpc-ready])' ).forEach( function ( root ) {
			root.dataset.wbpcReady = '1';
			new Checker( root ); // eslint-disable-line no-new
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
