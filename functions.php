/**
 * Output Buffering
 *
 * Capturing the final html output for manipulation with php buffer.
 */

ob_start();

// Runs just before PHP shuts down execution.
add_action('shutdown', function() {
	// Final html output
    $final_output = '';

	// Get output buffer levels
    $levels = ob_get_level();

    for ($i = 0; $i < $levels; $i++) {
		// Composing buffer levels into final output
        $final_output .= ob_get_clean();
    }

    // Apply filter to the final output
    echo apply_filters('final_output', $final_output);
}, 0);

/**
 * Filter and block content from buffered output for services which need cookie consent.
 */
add_filter('final_output', function($output) {

	// Name of the cookie whichs holds the consent information
	$CONSENT_COOKIE = "cconsent";

    if( !isset($_COOKIE['cconsent']) ){
		// No consent cookie set yet. Block content and show cookie settings notice.
		$output = filterContent($output);
		
	} else {
		// Consent cookie is set, get cookie and stripe slashes to prepare for valid json decoding.
		$cookie = stripslashes($_COOKIE['cconsent']);
		$cookie_data = json_decode($cookie);

		// Extract social consent state from cookie.
		// Example is working cookieconsent from brainsum https://github.com/brainsum/cookieconsent
		$social_consent_state = $cookie_data->categories->socialmedia->wanted;

		if ( !$social_consent_state ) {
			// Consent not given. Block content and display cookie notice.
			$output = filterContent($output);
		}
	}

	// Return buffered output.
	return $output;
});

/***
 * Filter and replace content in given HTML markup.
 * 
 * @param string $output - The HTML output from the page.
 * @return string - Changed HTML output.
 */
function filterContent($output) {
	// Cookie notice which will be displayed instead of blocked content.
	$notice = 
			'<div class="accept-cookies">Für diesen Inhalt müssen Cookies für soziale Medien zugelassen werden.' . 
				'<button type="button" class="ccb__edit">Cookie-Einstellungen ändern</button>' .
			'</div>';

	// General example: Replace all selected iframes regardless of class, id, other attributes, with cookie notice
	$output = preg_replace( '/<iframe.*?>.*?<\/iframe>/i', $notice, $output );

	// Specific example: Replace embedded facebook content with cookie notice
	$output = preg_replace( '/<div.*?data-href=\"https?:\/\/(?:www.)?facebook(?:\.com).*?\".*?>.*?<\/div>/i', $notice, $output );
	
	return $output;
}
