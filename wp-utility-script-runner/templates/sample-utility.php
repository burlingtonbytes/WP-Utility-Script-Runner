<?php if(!defined('ABSPATH')) { die(); } // This line ensures that the script is not run directly
/**
 * Utility Name: SAMPLE Utility
 * Description: SAMPLE UTILITY to explain the basic structure of a Utility Script
 * Author: Burlington Bytes, LLC
 * Author URI: https://www.burlingtonbytes.com
 * Supports: input
 * Version: 1.0.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 **/

 // The File Header above allows the Script Runner to identify and autoload your utility

// This filter is only necessary if your script supports user input.
// Otherwise, you can remove this entire section
// ---
// Simply include properly named and formatted form fields and their values
// will end up in the $atts array of of the script, when run
function example_utility_input_html( $html ) {
	ob_start();
	// START YOUR FORM CODE HERE
	?>
	<label>
		<span>Sample text input: </span>
		<input type="text" name="sample_input" value="Sample Input Value" required>
	</label>
	<label>
		<span>Sample textarea: </span>
		<textarea name="sample_textarea" style="height:200px;" required>Sample Textarea Value</textarea>
	</label>
	<?php
	// END YOUR FORM CODE HERE
	return ob_get_clean();
}
add_filter('wp_util_input_html', 'example_utility_input_html');

// this filter contains the actual meat and potatoes of your script
// ---
// $legacy will always be an empty string, but it needed to support a
// legacy version of the utility script format
// ---
// $state is an aritrary value you can return from the previous run of the script,
// and which will be passed through to the next run. One common use is to
// store an offset for paginated database queries. State will be falsy for the
// initial run. It is recommended to store data in state as keys in an array, to
// ensure no overlap with the reserved values of 'complete' and 'error' which
// trigger exiting the script
// ---
// $atts is an array, containing your input form fields, by name, EXCEPT file inputs
// ---
// $files contains an array of any file inputs that were included in the input form
// ---
function example_utility_script( $legacy, $state, $atts, $files ) {
	// scripts must return a state and a message, in an array
	// ---
	// if state is not equal to 'complete' or 'error', the script will be
	// triggered again, with state passed to the $state variable.
	// this allows you to create scripts that will take longer than
	// PHP_MAX_EXECUTION_TIME to fully complete
	// ---
	// The contents of message will be output to the user on each run
	return array(
		'state'   => 'complete',
		'message' => "HELLO WORLD!\nAtts Were:\n" . json_encode( $atts ),
		/*
		// the optional payload parameter allows your script to create a file
		// that will be downloaded by the user on script completion
		'payload' => array(
			'type' => 'text/plain;charset=utf-8',
			'name' => 'hello-world.txt',
			'data' => 'HELLO WORLD!'
		)
		*/
	);
}
add_filter('wp_util_script', 'example_utility_script', 10, 4);
