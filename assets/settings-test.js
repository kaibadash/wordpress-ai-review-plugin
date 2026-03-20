( function () {
	var btn = document.getElementById( 'ai-review-test-btn' );
	var spinner = document.getElementById( 'ai-review-test-spinner' );
	var result = document.getElementById( 'ai-review-test-result' );

	if ( ! btn ) {
		return;
	}

	btn.addEventListener( 'click', function () {
		btn.disabled = true;
		spinner.classList.add( 'is-active' );
		result.textContent = '';
		result.className = '';

		fetch( aiReviewTest.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': aiReviewTest.nonce,
			},
			body: '{}',
		} )
			.then( function ( res ) {
				return res.text().then( function ( text ) {
					return { status: res.status, text: text };
				} );
			} )
			.then( function ( res ) {
				result.style.whiteSpace = 'pre-wrap';
				if ( res.status === 200 ) {
					var data = JSON.parse( res.text );
					if ( data.success && data.reply ) {
						result.style.color = '#00a32a';
						var msg = '✅ ' + aiReviewTest.successLabel + ' ' + data.reply;
						if ( data.reasoning ) {
							msg += '\n\n--- Reasoning ---\n' + data.reasoning;
						}
						result.textContent = msg;
						return;
					}
				}
				result.style.color = '#d63638';
				result.textContent = '❌ ' + aiReviewTest.errorLabel + ' HTTP ' + res.status + '\n\n' + res.text;
			} )
			.catch( function ( err ) {
				result.style.color = '#d63638';
				result.style.whiteSpace = 'pre-wrap';
				result.textContent = '❌ ' + aiReviewTest.errorLabel + ' ' + err.message;
			} )
			.finally( function () {
				btn.disabled = false;
				spinner.classList.remove( 'is-active' );
			} );
	} );
} )();
