<?php if(!defined('ABSPATH')) { die(); }

/**
 * Utility Name: Invalidate Passwords
 * Description: Invalidate all user passwords (except current user) and send them a notification email.
 * Author: Burlington Bytes, LLC
 * Author URI: https://www.burlingtonbytes.com
 * Supports: input
 * Version: 1.0.1
 **/

add_filter('wp_util_input_html', function( $html ) {
	$post_types = get_post_types(array(
		'public' => true
	));
	ob_start();
?>
Dear [[NAME]],<br>
<p>
For security reasons we have reset your account password for <?php bloginfo( 'name' ); ?>.
Please visit the url below to reset your account password.
</p>
<br>
<a href="<?php echo wp_lostpassword_url(); ?>"><?php echo wp_lostpassword_url(); ?></a>
<?php
	$message = ob_get_clean();
	ob_start()
	?>
	<label>
		<span>User Role: </span>
		<select name="user_role" required>
			<option value="" disabled selected>Select a Role</option>
			<?php wp_dropdown_roles( $selected ); ?>
			<option value="invalidate all users">All Users</option>
		</select>
	</label>
	<p>
		<small>
			The following tags can be inserted into the email body:<br>
			[[NAME]], [[USERNAME]], [[ID]], [[FIRSTNAME]], [[LASTNAME]],
			[[DISPLAYNAME]], [[NICENAME]], [[EMAIL]], [[URL]], [[NICKNAME]].
		</small>
	</p>
	<label>
		<span>Email Subject: </span>
		<input type="text" name="email_subject" value="[[NAME]], Password Change Required" required>
	</label>
	<label>
		<span>Email Body: </span>
		<textarea name="email_body" style="height:200px;" required><?php echo $message; ?></textarea>
	</label>
	<?php
	return ob_get_clean();
});
add_filter('wp_util_script', function( $output, $state, $atts ) {
	global $wpdb;
	$per_page = 50;

	$retval = array(
		'state'   => 'error',
		'message' => 'an unknown error has occurred'
	);
	$offset    = @$state['offset'];
	$offset    = (int)$offset;
	$user_role = @$atts['user_role'];
	$subject   = @$atts['email_subject'];
	$body      = @$atts['email_body'];
	if( !$user_role || !$subject || !$body ) {
		$retval['message'] = "Required inputs must be populated";
		return $retval;
	}

	$valid_roles = get_editable_roles();
	if( $user_role != 'invalidate all users' && !isset( $valid_roles[$user_role] ) ) {
		$retval['message'] = "User Role Invalid";
		return $retval;
	}

	$args = array(
		'exclude' => array( get_current_user_id() ),
		'offset'  => $offset,
		'number'  => $per_page,
	);
	if( $user_role != 'invalidate all users' ) {
		$args['role'] = $user_role;
	}

	$users = get_users( $args );
	if( !count( $users ) ) {
		return array(
			'state'=>'complete',
			'message'=> "Reset a total of " . $offset . " User Passwords"
		);
	}
	$bad_pass = "__!!_THIS_USER_MUST_RESET_THEIR_PASSWORD_!!__";
	$query = 'UPDATE `' . $wpdb->prefix . 'users` SET `user_pass`="' . $bad_pass . '" WHERE `ID`=%d';
	foreach( $users as $user ) {
		$user_id = $user->ID;
		$wpdb->query( $wpdb->prepare( $query, $user_id ) );
		wp_utility_invalidate_passwords_send_email( $user, $subject, $body );
	}
	$offset += count( $users );
	return array(
		'state' => array(
			'offset' => $offset
		),
		'message' => $offset . " Users Processed"
	);
}, 10, 3);

function wp_utility_invalidate_passwords_send_email( $user, $subject, $body ) {
	$email = $user->user_email;
	if( !$email ) {
		return;
	}
	$name = $user->display_name;
	if( !$name ) {
		$name = $user->user_login;
	}
	if( $user->first_name || $user->last_name ) {
		$name = implode( ' ', array( $user->first_name, $user->last_name ) );
	}
	$replace = array(
		'[[NAME]]'        => $name,
		'[[USERNAME]]'    => $user->user_login,
		'[[ID]]'          => $user->ID,
		'[[FIRSTNAME]]'   => $user->first_name,
		'[[LASTNAME]]'    => $user->last_name,
		'[[DISPLAYNAME]]' => $user->display_name,
		'[[NICENAME]]'    => $user->user_nicename,
		'[[EMAIL]]'       => $user->user_email,
		'[[URL]]'         => $user->user_url,
		'[[NICKNAME]]'    => $user->nickname
	);
	$subject = str_replace( array_keys( $replace ), array_values( $replace ), $subject );
	$body    = str_replace( array_keys( $replace ), array_values( $replace ), $body );
	wp_mail( $email, $subject, $body );
}
