function lianaAutomationCookie() {
	const tries      = 10;
	let tried        = 0;
	let cookieIsSet  = false;

	function setLianaTCookie() {
		const liana_t = window.Automation.getTrackingCode();

		let secure, d;
		let sameSite = 'SameSite=Lax';

		if ( window.location.protocol === 'https:' ) {
			secure   = '; Secure';
			sameSite = 'SameSite=None';
		}

		d = new Date();
		d.setDate( d.getDate() + 365 );

		document.cookie = 'liana_t=' + liana_t + '; Expires=' + d.toUTCString() + '; ' + sameSite  + '; Path=/' + secure;
		cookieIsSet     = true;
	}

	// Loop until the tracking code is available
	function waitForTrackingCode() {
		if ( window.Automation && ! cookieIsSet ) {
			setLianaTCookie();
		} else if ( ! cookieIsSet && tried < tries ) {
			tried++;
			setTimeout( waitForTrackingCode, 500 );
		}
	}

	waitForTrackingCode();
}

lianaAutomationCookie();
