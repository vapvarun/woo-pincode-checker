/**
 * Pincode Checker admin: Service Areas list + form, and the Overview postcode tester.
 *
 * Vanilla JS over the wbpc/v1 REST API. Data is written with textContent only, never innerHTML.
 */
( function () {
	'use strict';

	var __ = wp.i18n.__;
	var _x = wp.i18n._x;
	var _n = wp.i18n._n;
	var sprintf = wp.i18n.sprintf;
	var apiFetch = wp.apiFetch;
	var addQueryArgs = wp.url.addQueryArgs;
	var cfg = window.wbpcAdmin || { currency: '', decimals: 2, decimalSep: '.', thousandSep: ',', currencyPos: 'left' };

	/* Numbers and prices follow the store's WooCommerce separators, not the browser's locale. */
	function num( value, decimals ) {
		var parts = Math.abs( Number( value ) ).toFixed( decimals || 0 ).split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, cfg.thousandSep );
		return ( Number( value ) < 0 ? '-' : '' ) + parts.join( cfg.decimalSep );
	}

	function money( value ) {
		if ( null === value || undefined === value ) {
			return '';
		}
		var amount = num( value, cfg.decimals );
		return {
			right: amount + cfg.currency,
			left_space: cfg.currency + '\u00a0' + amount,
			right_space: amount + '\u00a0' + cfg.currency
		}[ cfg.currencyPos ] || cfg.currency + amount;
	}

	/* Whole sentences side by side, each its own node: never glued into one string. */
	function sentences( node, list ) {
		node.textContent = '';
		list.filter( Boolean ).forEach( function ( text ) {
			node.appendChild( el( 'span', { class: 'wbpc-sentence' }, text ) );
		} );
	}

	var TYPE_LABELS = {
		exact: _x( 'Exact', 'postcode area type', 'woo-pincode-checker' ),
		prefix: _x( 'Prefix', 'postcode area type', 'woo-pincode-checker' ),
		range: _x( 'Range', 'postcode area type', 'woo-pincode-checker' )
	};
	var STATUS_LABELS = {
		serviceable: _x( 'Serviceable', 'area status', 'woo-pincode-checker' ),
		blocked: _x( 'Blocked', 'area status', 'woo-pincode-checker' )
	};

	function el( tag, attrs, text ) {
		var node = document.createElement( tag );
		Object.keys( attrs || {} ).forEach( function ( key ) {
			node.setAttribute( key, attrs[ key ] );
		} );
		if ( undefined !== text && null !== text ) {
			node.textContent = text;
		}
		return node;
	}

	function badge( text, tone ) {
		return el( 'span', { class: 'wbcom-badge wbcom-badge--' + tone }, text );
	}

	function icons() {
		if ( window.lucide ) {
			window.lucide.createIcons();
		}
	}

	function message( error ) {
		return ( error && error.message ) || __( 'Something went wrong. Please try again.', 'woo-pincode-checker' );
	}

	/* A table cell: the value, plus an optional quiet second line. */
	function cell( label, main, sub, cls ) {
		var td = el( 'td', { 'data-label': label, class: cls || '' } );
		var wrap = el( 'span', { class: 'wbpc-cell' } );
		wrap.appendChild( el( 'span', { class: 'wbpc-cell__main' }, main ) );
		if ( sub ) {
			wrap.appendChild( el( 'span', { class: 'wbpc-cell__sub' }, sub ) );
		}
		td.appendChild( wrap );
		return td;
	}

	function codeLabel( area ) {
		return 'range' === area.type ? area.code + ' - ' + area.code_to : area.code;
	}

	function daysLabel( area ) {
		if ( null === area.days_min ) {
			return _x( 'Default', 'delivery days: store default', 'woo-pincode-checker' );
		}
		return null === area.days_max || area.days_max === area.days_min ? String( area.days_min ) : area.days_min + '-' + area.days_max;
	}

	/* ------------------------------------------------------------------ Service Areas */

	var root = document.querySelector( '[data-wbpc-areas]' );

	if ( root ) {
		var q = function ( sel ) {
			return root.querySelector( sel );
		};
		var filtersForm = q( '[data-wbpc-filters]' );
		var form = q( '[data-wbpc-form]' );
		var rowsEl = q( '[data-wbpc-rows]' );
		var statusEl = q( '[data-wbpc-status]' );
		var pager = q( '[data-wbpc-pager]' );
		var empty = q( '[data-wbpc-empty]' );
		var bulkbar = q( '[data-wbpc-bulkbar]' );
		var confirmBox = q( '[data-wbpc-confirm]' );
		var checkPage = q( '[data-wbpc-check-page]' );

		var state = { page: 1, perPage: 25, total: 0, pages: 1, rows: [], selected: new Set(), allMatching: false, editing: 0, loaded: false };
		var request = 0;

		var filters = function () {
			var data = new FormData( filtersForm );
			var out = {};
			data.forEach( function ( value, key ) {
				if ( '' !== value ) {
					out[ key ] = value;
				}
			} );
			return out;
		};

		var setStatus = function ( text ) {
			statusEl.textContent = text;
		};

		// `note` reports the action that triggered this reload; it survives the re-render.
		var load = function ( note ) {
			var mine = ++request;
			var args = Object.assign( filters(), { page: state.page, per_page: state.perPage, order: 'newest' === filtersForm.orderby.value ? 'desc' : 'asc' } );

			root.setAttribute( 'aria-busy', 'true' );
			rowsEl.classList.add( 'is-loading' );

			apiFetch( { path: addQueryArgs( '/wbpc/v1/areas', args ), parse: false } )
				.then( function ( response ) {
					state.total = parseInt( response.headers.get( 'X-WP-Total' ), 10 ) || 0;
					state.pages = parseInt( response.headers.get( 'X-WP-TotalPages' ), 10 ) || 1;
					return response.json();
				} )
				.then( function ( rows ) {
					if ( mine !== request ) {
						return; // A newer request superseded this one.
					}
					state.rows = rows;
					render();
					if ( note ) {
						sentences( statusEl, [ note, statusEl.textContent ] );
					}
				} )
				.catch( function ( error ) {
					if ( mine !== request ) {
						return;
					}
					rowsEl.textContent = '';
					showEmpty( __( 'Could not load areas', 'woo-pincode-checker' ), message( error ) );
					setStatus( '' );
				} )
				.finally( function () {
					root.removeAttribute( 'aria-busy' );
					rowsEl.classList.remove( 'is-loading' );
				} );
		};

		var showEmpty = function ( title, desc ) {
			empty.hidden = false;
			q( '[data-wbpc-empty-title]' ).textContent = title;
			q( '[data-wbpc-empty-desc]' ).textContent = desc;
			q( '[data-wbpc-table]' ).hidden = true;
			pager.hidden = true;
		};

		var render = function () {
			rowsEl.textContent = '';
			state.selected.clear();
			state.allMatching = false;

			if ( ! state.rows.length ) {
				var filtered = Object.keys( filters() ).some( function ( key ) {
					return 'orderby' !== key;
				} );
				showEmpty(
					filtered ? __( 'No areas match these filters', 'woo-pincode-checker' ) : __( 'No delivery areas yet', 'woo-pincode-checker' ),
					filtered ? __( 'Clear the search or filters to see all areas.', 'woo-pincode-checker' ) : __( 'Add the postcodes you deliver to, or import them from a CSV file.', 'woo-pincode-checker' )
				);
				setStatus( '' );
				updateBulk();
				return;
			}

			empty.hidden = true;
			q( '[data-wbpc-table]' ).hidden = false;

			state.rows.forEach( function ( area ) {
				var tr = el( 'tr', { 'data-id': area.id } );
				/* translators: %s: area code or range, e.g. 110001. */
				var check = el( 'input', { type: 'checkbox', 'aria-label': sprintf( __( 'Select area %s', 'woo-pincode-checker' ), codeLabel( area ) ) } );
				check.dataset.wbpcCheck = area.id;

				var tdCheck = el( 'td', { class: 'wbpc-col-check' } );
				tdCheck.appendChild( check );
				tr.appendChild( tdCheck );

				tr.appendChild( cell( __( 'Code', 'woo-pincode-checker' ), codeLabel( area ), TYPE_LABELS[ area.type ], 'wbpc-col-code' ) );
				tr.appendChild( cell( __( 'Country', 'woo-pincode-checker' ), area.country || _x( 'Any', 'country: any country', 'woo-pincode-checker' ) ) );
				tr.appendChild( cell( __( 'City / State', 'woo-pincode-checker' ), area.city || area.state || '-', area.city ? area.state : '' ) );
				tr.appendChild( cell( __( 'Delivery days', 'woo-pincode-checker' ), daysLabel( area ) ) );
				tr.appendChild( cell( __( 'Shipping', 'woo-pincode-checker' ), null === area.shipping_fee ? '-' : money( area.shipping_fee ) ) );
				tr.appendChild( cell( __( 'COD', 'woo-pincode-checker' ), area.cod_allowed ? __( 'Yes', 'woo-pincode-checker' ) : __( 'No', 'woo-pincode-checker' ), area.cod_allowed && area.cod_fee > 0
					? sprintf( /* translators: %s: cash on delivery fee with currency, e.g. ₹25.00. */ __( '+%s fee', 'woo-pincode-checker' ), money( area.cod_fee ) )
					: '' ) );

				var status = el( 'td', { 'data-label': __( 'Status', 'woo-pincode-checker' ) } );
				status.appendChild( badge( STATUS_LABELS[ area.status ], 'serviceable' === area.status ? 'success' : 'danger' ) );
				tr.appendChild( status );

				var actions = el( 'td', { class: 'wbpc-col-actions' } );
				/* translators: %s: area code or range. */
				var edit = el( 'button', { type: 'button', class: 'button wbcom-btn wbpc-icon-btn', 'aria-label': sprintf( __( 'Edit %s', 'woo-pincode-checker' ), codeLabel( area ) ) } );
				edit.appendChild( el( 'i', { 'data-lucide': 'pencil' } ) );
				edit.dataset.wbpcEdit = area.id;
				/* translators: %s: area code or range. */
				var del = el( 'button', { type: 'button', class: 'button wbcom-btn wbpc-icon-btn wbpc-icon-btn--danger', 'aria-label': sprintf( __( 'Delete %s', 'woo-pincode-checker' ), codeLabel( area ) ) } );
				del.appendChild( el( 'i', { 'data-lucide': 'trash-2' } ) );
				del.dataset.wbpcDelete = area.id;
				actions.appendChild( edit );
				actions.appendChild( del );
				tr.appendChild( actions );

				rowsEl.appendChild( tr );
			} );

			var first = ( state.page - 1 ) * state.perPage + 1;
			/* translators: 1: first row, 2: last row, 3: total areas. */
			setStatus( sprintf( _n( 'Showing %1$s-%2$s of %3$s area', 'Showing %1$s-%2$s of %3$s areas', state.total, 'woo-pincode-checker' ), num( first ), num( first + state.rows.length - 1 ), num( state.total ) ) );

			pager.hidden = state.pages <= 1;
			/* translators: 1: current page number, 2: total pages. */
			q( '[data-wbpc-page-label]' ).textContent = sprintf( __( 'Page %1$s of %2$s', 'woo-pincode-checker' ), num( state.page ), num( state.pages ) );
			q( '[data-wbpc-prev]' ).disabled = state.page <= 1;
			q( '[data-wbpc-next]' ).disabled = state.page >= state.pages;

			checkPage.checked = false;
			updateBulk();
			icons();
		};

		/* Selection + bulk delete */

		var updateBulk = function () {
			var count = state.allMatching ? state.total : state.selected.size;
			var selectAll = q( '[data-wbpc-select-all]' );

			bulkbar.hidden = 0 === count;
			/* translators: %s: number of selected areas. */
			q( '[data-wbpc-selected]' ).textContent = sprintf( _n( '%s area selected', '%s areas selected', count, 'woo-pincode-checker' ), num( count ) );

			var pageFull = state.rows.length > 0 && state.selected.size === state.rows.length;
			selectAll.hidden = ! pageFull || state.allMatching || state.total <= state.rows.length;
			/* translators: %s: number of areas matching the filters. */
			selectAll.textContent = sprintf( _n( 'Select the %s matching area', 'Select all %s matching areas', state.total, 'woo-pincode-checker' ), num( state.total ) );
		};

		rowsEl.addEventListener( 'change', function ( e ) {
			var id = e.target.dataset.wbpcCheck;
			if ( ! id ) {
				return;
			}
			state.allMatching = false;
			if ( e.target.checked ) {
				state.selected.add( Number( id ) );
			} else {
				state.selected.delete( Number( id ) );
			}
			checkPage.checked = state.selected.size === state.rows.length;
			updateBulk();
		} );

		checkPage.addEventListener( 'change', function () {
			state.allMatching = false;
			rowsEl.querySelectorAll( '[data-wbpc-check]' ).forEach( function ( box ) {
				box.checked = checkPage.checked;
				if ( checkPage.checked ) {
					state.selected.add( Number( box.dataset.wbpcCheck ) );
				} else {
					state.selected.delete( Number( box.dataset.wbpcCheck ) );
				}
			} );
			updateBulk();
		} );

		q( '[data-wbpc-select-all]' ).addEventListener( 'click', function () {
			state.allMatching = true;
			updateBulk();
		} );

		/* In-page confirmation (never window.confirm). Typed DELETE above 100 areas. */

		var pending = null;

		var askConfirm = function ( text, count, run ) {
			pending = run;
			confirmBox.hidden = false;
			q( '[data-wbpc-confirm-text]' ).textContent = text;
			var typed = q( '[data-wbpc-confirm-typed]' );
			typed.hidden = count <= 100;
			typed.querySelector( 'input' ).value = '';
			( typed.hidden ? q( '[data-wbpc-confirm-yes]' ) : typed.querySelector( 'input' ) ).focus();
		};

		var closeConfirm = function () {
			pending = null;
			confirmBox.hidden = true;
		};

		q( '[data-wbpc-confirm-no]' ).addEventListener( 'click', closeConfirm );
		q( '[data-wbpc-confirm-yes]' ).addEventListener( 'click', function () {
			var typed = q( '[data-wbpc-confirm-typed]' );
			if ( ! typed.hidden && 'DELETE' !== typed.querySelector( 'input' ).value.trim() ) {
				typed.querySelector( 'input' ).focus();
				return;
			}
			var run = pending;
			closeConfirm();
			if ( run ) {
				run();
			}
		} );

		var removeAreas = function ( body ) {
			apiFetch( { path: '/wbpc/v1/areas/batch-delete', method: 'POST', data: body } )
				.then( function ( res ) {
					// Step back when the current page was emptied.
					if ( body.all ) {
						state.page = 1;
					} else if ( state.page > 1 && body.ids.length >= state.rows.length ) {
						state.page--;
					}
					/* translators: %s: number of areas deleted. */
					load( sprintf( _n( '%s area deleted.', '%s areas deleted.', res.deleted, 'woo-pincode-checker' ), num( res.deleted ) ) );
				} )
				.catch( function ( error ) {
					setStatus( message( error ) );
				} );
		};

		q( '[data-wbpc-bulk-delete]' ).addEventListener( 'click', function () {
			var count = state.allMatching ? state.total : state.selected.size;
			askConfirm(
				/* translators: %s: number of areas to delete. */
				sprintf( _n( 'Delete %s area? This cannot be undone.', 'Delete %s areas? This cannot be undone.', count, 'woo-pincode-checker' ), num( count ) ),
				count,
				function () {
					removeAreas( state.allMatching ? Object.assign( filters(), { all: true } ) : { ids: Array.from( state.selected ) } );
				}
			);
		} );

		/* Row actions */

		rowsEl.addEventListener( 'click', function ( e ) {
			var editBtn = e.target.closest( '[data-wbpc-edit]' );
			var delBtn = e.target.closest( '[data-wbpc-delete]' );
			var area;

			if ( editBtn ) {
				area = state.rows.find( function ( row ) {
					return row.id === Number( editBtn.dataset.wbpcEdit );
				} );
				openForm( area );
			} else if ( delBtn ) {
				area = state.rows.find( function ( row ) {
					return row.id === Number( delBtn.dataset.wbpcDelete );
				} );
				/* translators: %s: area code or range. */
				askConfirm( sprintf( __( 'Delete area %s? This cannot be undone.', 'woo-pincode-checker' ), codeLabel( area ) ), 1, function () {
					apiFetch( { path: '/wbpc/v1/areas/' + area.id, method: 'DELETE' } )
						.then( function () {
							/* translators: %s: area code or range. */
							load( sprintf( __( 'Area %s deleted.', 'woo-pincode-checker' ), codeLabel( area ) ) );
						} )
						.catch( function ( error ) {
							// 404 = someone else deleted it already; the reload shows the truth either way.
							load( message( error ) );
						} );
				} );
			}
		} );

		/* Add / edit form */

		var syncType = function () {
			var type = form.querySelector( 'input[name="type"]:checked' ).value;
			form.querySelector( '[data-wbpc-field="code_to"]' ).hidden = 'range' !== type;
			form.querySelector( 'label[for="wbpc-f-code"]' ).textContent =
				'range' === type ? __( 'Range start', 'woo-pincode-checker' ) : 'prefix' === type ? __( 'Postcode starts with', 'woo-pincode-checker' ) : __( 'Postcode', 'woo-pincode-checker' );
		};

		var clearErrors = function () {
			q( '[data-wbpc-form-error]' ).hidden = true;
			form.querySelectorAll( '.wbpc-field-error' ).forEach( function ( span ) {
				span.textContent = '';
			} );
			form.querySelectorAll( '[aria-invalid]' ).forEach( function ( input ) {
				input.removeAttribute( 'aria-invalid' );
			} );
		};

		var openForm = function ( area ) {
			clearErrors();
			form.reset();
			state.editing = area ? area.id : 0;
			q( '[data-wbpc-form-title]' ).textContent = area
				? sprintf( /* translators: %s: area code or range. */ __( 'Edit area %s', 'woo-pincode-checker' ), codeLabel( area ) )
				: __( 'Add a delivery area', 'woo-pincode-checker' );

			if ( area ) {
				form.querySelector( 'input[name="type"][value="' + area.type + '"]' ).checked = true;
				[ 'code', 'code_to', 'country', 'status', 'city', 'state', 'days_min', 'days_max', 'shipping_fee', 'cod_fee', 'note' ].forEach( function ( key ) {
					var value = area[ key ];
					form.elements[ key ].value = null === value || undefined === value ? '' : ( 'code' === key && 'prefix' === area.type ? String( value ).replace( /\*+$/, '' ) : value );
				} );
				form.elements.cod_allowed.checked = !! area.cod_allowed;
			}

			syncType();
			form.hidden = false;
			q( '[data-wbpc-form-title]' ).focus();
		};

		var closeForm = function () {
			form.hidden = true;
			state.editing = 0;
			q( '[data-wbpc-add]' ).focus();
		};

		form.addEventListener( 'change', function ( e ) {
			if ( 'type' === e.target.name ) {
				syncType();
			}
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			clearErrors();

			var type = form.querySelector( 'input[name="type"]:checked' ).value;
			var code = form.elements.code.value.trim();
			var data = {
				code: 'prefix' === type ? code.replace( /\*+$/, '' ) + '*' : code,
				code_to: 'range' === type ? form.elements.code_to.value.trim() : '',
				country: form.elements.country.value,
				status: form.elements.status.value,
				city: form.elements.city.value,
				state: form.elements.state.value,
				days_min: form.elements.days_min.value,
				days_max: form.elements.days_max.value,
				shipping_fee: form.elements.shipping_fee.value,
				cod_allowed: form.elements.cod_allowed.checked,
				cod_fee: form.elements.cod_fee.value,
				note: form.elements.note.value
			};
			var save = q( '[data-wbpc-save]' );
			save.disabled = true;

			apiFetch( state.editing ? { path: '/wbpc/v1/areas/' + state.editing, method: 'PATCH', data: data } : { path: '/wbpc/v1/areas', method: 'POST', data: data } )
				.then( function ( area ) {
					var note = state.editing
						? sprintf( /* translators: %s: area code or range. */ __( 'Area %s updated.', 'woo-pincode-checker' ), codeLabel( area ) )
						: sprintf( /* translators: %s: area code or range. */ __( 'Area %s added.', 'woo-pincode-checker' ), codeLabel( area ) );
					closeForm();
					load( note );
				} )
				.catch( function ( error ) {
					var fields = ( error && error.data && error.data.fields ) || {};
					var formError = q( '[data-wbpc-form-error]' );
					formError.textContent = message( error );
					formError.hidden = false;

					Object.keys( fields ).forEach( function ( key ) {
						var span = form.querySelector( '#wbpc-e-' + key );
						var input = form.elements[ key ];
						if ( span ) {
							span.textContent = fields[ key ];
						}
						if ( input && input.setAttribute ) {
							input.setAttribute( 'aria-invalid', 'true' );
						}
					} );

					if ( fields._duplicate_id ) {
						var link = el( 'button', { type: 'button', class: 'button-link' }, __( 'Open the existing area', 'woo-pincode-checker' ) );
						link.addEventListener( 'click', function () {
							apiFetch( { path: '/wbpc/v1/areas/' + fields._duplicate_id } ).then( openForm );
						} );
						form.querySelector( '#wbpc-e-code' ).appendChild( document.createTextNode( ' ' ) );
						form.querySelector( '#wbpc-e-code' ).appendChild( link );
					}

					formError.scrollIntoView( { block: 'nearest' } );
				} )
				.finally( function () {
					save.disabled = false;
				} );
		} );

		q( '[data-wbpc-add]' ).addEventListener( 'click', function () {
			openForm( null );
		} );
		q( '[data-wbpc-cancel]' ).addEventListener( 'click', closeForm );

		/* Filters + paging */

		var searchTimer;
		filtersForm.addEventListener( 'input', function ( e ) {
			if ( 'search' !== e.target.name ) {
				return;
			}
			clearTimeout( searchTimer );
			searchTimer = setTimeout( function () {
				state.page = 1;
				load();
			}, 300 );
		} );
		function refilter( e ) {
			if ( 'search' === e.target.name ) {
				return;
			}
			state.page = 1;
			load();
		}
		filtersForm.addEventListener( 'change', refilter );
		/* The sort select sits outside the form (form attribute), so its change does not bubble there. */
		filtersForm.orderby.addEventListener( 'change', refilter );
		filtersForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
		} );

		q( '[data-wbpc-prev]' ).addEventListener( 'click', function () {
			state.page--;
			load();
		} );
		q( '[data-wbpc-next]' ).addEventListener( 'click', function () {
			state.page++;
			load();
		} );
		q( '[data-wbpc-per-page]' ).addEventListener( 'change', function ( e ) {
			state.perPage = Number( e.target.value );
			state.page = 1;
			load();
		} );

		/*
		 * Load only when the tab is shown (the shell renders every tab on every page load). This
		 * script runs after the shell's, so a page opened directly on this tab has already fired
		 * the event: check the active section too.
		 */
		var loadOnce = function () {
			if ( ! state.loaded ) {
				state.loaded = true;
				load();
			}
		};
		document.addEventListener( 'wbcom:section-shown', function ( e ) {
			if ( 'wbpc-areas' === e.detail.id ) {
				loadOnce();
			}
		} );
		if ( root.closest( '.wbcom-settings-section.is-active' ) ) {
			loadOnce();
		}
	}

	/* ------------------------------------------------------------------ Overview tester */

	var tester = document.querySelector( '[data-wbpc-tester]' );

	if ( tester ) {
		var out = document.querySelector( '[data-wbpc-tester-result]' );

		tester.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			out.textContent = __( 'Checking...', 'woo-pincode-checker' );

			apiFetch( { path: '/wbpc/v1/tools/test', method: 'POST', data: { postcode: tester.postcode.value, country: tester.country.value } } )
				.then( function ( res ) {
					var r = res.result;
					var list = el( 'dl', { class: 'wbpc-tester__list' } );
					var add = function ( term, value ) {
						list.appendChild( el( 'dt', {}, term ) );
						var dd = el( 'dd' );
						if ( value instanceof Node ) {
							dd.appendChild( value );
						} else {
							dd.textContent = value;
						}
						list.appendChild( dd );
					};

					out.textContent = '';
					add(
						__( 'Shopper sees', 'woo-pincode-checker' ),
						{
							available: badge( __( 'Delivery available', 'woo-pincode-checker' ), 'success' ),
							unavailable: badge( __( 'Not available', 'woo-pincode-checker' ), 'danger' ),
							invalid: badge( __( 'Invalid postcode for this country', 'woo-pincode-checker' ), 'warn' )
						}[ r.status ]
					);

					if ( 'invalid' !== r.status ) {
						add(
							__( 'Decided by', 'woo-pincode-checker' ),
							res.area
								? sprintf( /* translators: 1: area code or range, 2: area type (Exact, Prefix or Range), 3: country code or "Any", 4: area status (Serviceable or Blocked). */ __( 'Area %1$s (%2$s, %3$s, %4$s)', 'woo-pincode-checker' ), codeLabel( res.area ), TYPE_LABELS[ res.area.type ], res.area.country || _x( 'Any', 'country: any country', 'woo-pincode-checker' ), STATUS_LABELS[ res.area.status ] )
								: __( 'No area matched, so the unknown-postcode setting applies.', 'woo-pincode-checker' )
						);
					}
					if ( r.city || r.state ) {
						add( __( 'Location', 'woo-pincode-checker' ), [ r.city, r.state ].filter( Boolean ).join( ', ' ) );
					}
					if ( 'available' === r.status ) {
						add( __( 'Delivery days', 'woo-pincode-checker' ), null === r.days_min ? __( 'Store default', 'woo-pincode-checker' ) : daysLabel( r ) );
						add( __( 'Shipping fee', 'woo-pincode-checker' ), null === r.shipping_fee ? __( 'None from this area', 'woo-pincode-checker' ) : money( r.shipping_fee ) );
						add( __( 'Cash on delivery', 'woo-pincode-checker' ), r.cod.allowed ? ( r.cod.fee > 0
							? sprintf( /* translators: %s: cash on delivery fee with currency. */ __( 'Allowed (+%s)', 'woo-pincode-checker' ), money( r.cod.fee ) )
							: __( 'Allowed', 'woo-pincode-checker' ) ) : __( 'Not allowed', 'woo-pincode-checker' ) );
					}
					if ( r.nearby && r.nearby.length ) {
						add( __( 'Suggested nearby', 'woo-pincode-checker' ), r.nearby.map( function ( n ) {
							return n.code;
						} ).join( ', ' ) );
					}
					out.appendChild( list );
				} )
				.catch( function ( error ) {
					out.textContent = message( error );
				} );
		} );
	}
	/* ------------------------------------------------------------------ Import */

	var imp = document.querySelector( '[data-wbpc-import]' );

	if ( imp ) {
		var iq = function ( sel ) {
			return imp.querySelector( sel );
		};
		var uploadForm = iq( '[data-wbpc-upload]' );
		var panels = { upload: uploadForm, preview: iq( '[data-wbpc-preview]' ), progress: iq( '[data-wbpc-progress]' ), result: iq( '[data-wbpc-result]' ) };
		var pollTimer = null;
		var stagedMode = 'skip';

		var show = function ( name ) {
			Object.keys( panels ).forEach( function ( key ) {
				panels[ key ].hidden = key !== name;
			} );
		};

		var counts = function ( job ) {
			/* translators: 1: added, 2: updated, 3: skipped, 4: failed. */
			return sprintf( __( '%1$s added, %2$s updated, %3$s skipped (already existed), %4$s failed.', 'woo-pincode-checker' ), num( job.added ), num( job.updated ), num( job.skipped ), num( job.failed ) );
		};

		var showResult = function ( job ) {
			var lead = {
				done: __( 'Import finished.', 'woo-pincode-checker' ),
				cancelled: __( 'Import cancelled. Rows already imported were kept.', 'woo-pincode-checker' ),
				/* translators: %s: reason the import stopped. */
				failed: sprintf( __( 'Import stopped: %s', 'woo-pincode-checker' ), job.message )
			}[ job.status ] || job.message;
			sentences( iq( '[data-wbpc-result-text]' ), [ lead, counts( job ) ] );
			var link = iq( '[data-wbpc-errors]' );
			link.hidden = ! job.has_errors;
			link.href = cfg.errorsUrl + '&job=' + encodeURIComponent( job.id );
			show( 'result' );
			icons();
		};

		var showProgress = function ( job ) {
			var pct = job.total ? Math.round( ( job.processed / job.total ) * 100 ) : 0;
			iq( 'progress' ).value = pct;
			iq( '[data-wbpc-progress-label]' ).textContent = 'queued' === job.status && ! job.processed
				? __( 'Waiting for the background worker to start...', 'woo-pincode-checker' )
				: sprintf( /* translators: 1: rows imported so far, 2: total rows, 3: percent complete. */ __( 'Imported %1$s of %2$s rows (%3$s%%)', 'woo-pincode-checker' ), num( job.processed ), num( job.total ), num( pct ) );
			show( 'progress' );
		};

		var poll = function () {
			clearTimeout( pollTimer );
			apiFetch( { path: '/wbpc/v1/import' } )
				.then( function ( res ) {
					var job = res.job;
					if ( job && ( 'queued' === job.status || 'running' === job.status ) ) {
						showProgress( job );
						pollTimer = setTimeout( poll, 2000 );
					} else if ( job ) {
						showResult( job );
					}
				} )
				.catch( function () {
					pollTimer = setTimeout( poll, 5000 ); // Keep trying through a brief network blip.
				} );
		};

		var importError = function ( text ) {
			var box = iq( '[data-wbpc-import-error]' );
			box.textContent = text;
			box.hidden = ! text;
		};

		uploadForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			importError( '' );
			stagedMode = uploadForm.mode.value;

			var button = uploadForm.querySelector( 'button[type="submit"]' );
			button.disabled = true;

			apiFetch( { path: '/wbpc/v1/import', method: 'POST', body: new FormData( uploadForm ) } )
				.then( function ( res ) {
					var rows = iq( '[data-wbpc-preview-rows]' );
					var bad = res.preview.filter( function ( row ) {
						return row.error;
					} ).length;
					rows.textContent = '';

					res.preview.forEach( function ( row ) {
						var tr = el( 'tr' );
						tr.appendChild( el( 'td', { 'data-label': __( 'Line', 'woo-pincode-checker' ) }, String( row.line ) ) );
						tr.appendChild( el( 'td', { 'data-label': __( 'Code', 'woo-pincode-checker' ) }, row.code || '-' ) );
						tr.appendChild( el( 'td', { 'data-label': __( 'City', 'woo-pincode-checker' ) }, row.city || '-' ) );
						var check = el( 'td', { 'data-label': _x( 'Check', 'import preview column: row validation result', 'woo-pincode-checker' ) } );
						check.appendChild( row.error ? badge( row.error, 'danger' ) : badge( __( 'OK', 'woo-pincode-checker' ), 'success' ) );
						tr.appendChild( check );
						rows.appendChild( tr );
					} );

					sentences( iq( '[data-wbpc-preview-summary]' ), [
						/* translators: 1: number of rows, 2: file name. */
						sprintf( _n( '%1$s row found in %2$s.', '%1$s rows found in %2$s.', res.job.total, 'woo-pincode-checker' ), num( res.job.total ), res.job.name ),
						bad
							? sprintf( /* translators: %s: number of preview rows with problems. */ _n( '%s of the first rows has a problem and will be skipped; the import reports every such row.', '%s of the first rows have problems and will be skipped; the import reports every such row.', bad, 'woo-pincode-checker' ), num( bad ) )
							: __( 'The first rows look good.', 'woo-pincode-checker' )
					] );

					iq( '[data-wbpc-replace-confirm]' ).hidden = 'replace' !== stagedMode;
					iq( '#wbpc-replace-input' ).value = '';
					show( 'preview' );
					iq( '[data-wbpc-start]' ).focus();
				} )
				.catch( function ( error ) {
					importError( message( error ) );
				} )
				.finally( function () {
					button.disabled = false;
				} );
		} );

		iq( '[data-wbpc-start]' ).addEventListener( 'click', function () {
			if ( 'replace' === stagedMode && 'REPLACE' !== iq( '#wbpc-replace-input' ).value.trim() ) {
				iq( '#wbpc-replace-input' ).focus();
				return;
			}
			apiFetch( { path: '/wbpc/v1/import/start', method: 'POST' } )
				.then( function ( job ) {
					showProgress( job );
					pollTimer = setTimeout( poll, 1500 );
				} )
				.catch( function ( error ) {
					show( 'upload' );
					importError( message( error ) );
				} );
		} );

		imp.querySelectorAll( '[data-wbpc-import-cancel]' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				clearTimeout( pollTimer );
				apiFetch( { path: '/wbpc/v1/import/cancel', method: 'POST' } )
					.then( function ( job ) {
						if ( panels.preview.hidden ) {
							showResult( job );
						} else {
							show( 'upload' );
						}
					} )
					.catch( function ( error ) {
						importError( message( error ) );
						show( 'upload' );
					} );
			} );
		} );

		iq( '[data-wbpc-import-again]' ).addEventListener( 'click', function () {
			uploadForm.reset();
			importError( '' );
			show( 'upload' );
			uploadForm.file.focus();
		} );

		/* Resume: a job may be running from an earlier visit. */
		var initial = JSON.parse( imp.dataset.job || 'null' );
		if ( initial && ( 'queued' === initial.status || 'running' === initial.status ) ) {
			showProgress( initial );
			poll();
		}
	}
}() );
