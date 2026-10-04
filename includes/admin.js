// Postbox collapse + live preview wiring. Vanilla JS, no dependencies.
document.addEventListener( 'DOMContentLoaded', function() {
	document.querySelectorAll( '.zest-card' ).forEach( function( card ) {
		var btn = card.querySelector( '.handlediv' );
		if ( ! btn ) { return; }
		btn.addEventListener( 'click', function() {
			var open = 'true' === btn.getAttribute( 'aria-expanded' );
			btn.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
			card.classList.toggle( 'closed', open );
		} );
	} );
	var iframe = document.getElementById( 'zest-preview' );
	var form   = iframe ? iframe.closest( '.wrap' ).querySelector( 'form' ) : null;
	if ( ! iframe || ! form ) { return; }
	var timer  = null;
	var fields = form.querySelectorAll( 'input, select, textarea' );

	// Mirror the live form values onto the preview config.
	function collect() {
		var cfg = {};
		fields.forEach( function( el ) {
			// Multi-checkbox groups (e.g. hide_categories[]) accumulate.
			var g = el.name.match( /^zest_cmp_settings\[([a-z_]+)\]\[\]$/ );
			if ( g ) {
				if ( el.checked ) {
					cfg[ g[1] ] = cfg[ g[1] ] ? cfg[ g[1] ] + ',' + el.value : el.value;
				}
				return;
			}
			var m = el.name.match( /^zest_cmp_settings\[([a-z_]+)\]$/ );
			if ( ! m ) { return; }
			var key = m[1];
			if ( 'checkbox' === el.type ) {
				cfg[key] = el.checked ? '1' : '';
			} else {
				cfg[key] = el.value;
			}
		} );
		return cfg;
	}

	function reload() {
		var q = new URLSearchParams();
		var cfg = collect();
		Object.keys( cfg ).forEach( function( k ) {
			if ( '' !== cfg[k] ) {
				q.set( 'zest_' + k, cfg[k] );
			}
		} );
		q.set( '_wpnonce', zestAdmin.nonce );
		iframe.src = zestAdmin.previewUrl + '&' + q.toString();
	}

	function debounce() {
		clearTimeout( timer );
		timer = setTimeout( reload, 600 );
	}

	fields.forEach( function( el ) {
		el.addEventListener( 'change', debounce );
		el.addEventListener( 'input', debounce );
	} );

	document.getElementById( 'zest-replay' ).addEventListener( 'click', function() {
		iframe.contentWindow.postMessage( { type: 'zest-reset' }, window.location.origin );
	} );

	// Custom-theme color cells only apply when theme = custom.
	var themeSel = document.getElementById( 'zest_theme' );
	function syncColorRows() {
		var on = 'custom' === themeSel.value;
		form.querySelectorAll( '.zest-color-cell' ).forEach( function( tr ) {
			tr.style.display = on ? '' : 'none';
		} );
	}
	themeSel.addEventListener( 'change', function() { syncColorRows(); debounce(); } );
	syncColorRows();

	// Mirror native color input value into the readonly hex field.
	form.addEventListener( 'input', function( e ) {
		if ( 'color' === e.target.type && e.target.name ) {
			var hex = form.querySelector( '.zest-color-hex[data-for="' + e.target.id + '"]' );
			if ( hex ) { hex.value = e.target.value; }
		}
	} );

	reload();
} );