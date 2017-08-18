<?php if(!defined('ABSPATH')) { die(); } // Include in all php files, to prevent direct execution

echo '<h3>Schedule Scripts:</h3>';
echo '<small>to enable scheduling a script, modify the file header to include: <code>Supports: cron</code></small><br/>';
echo '<small>A scheduled script can also include input options, but file fields are strongly discouraged, as that data will be stored in the database.</small><br/>';
echo '<hr>';
if( count( $this->utils['cron'] ) ) {
	?>
	<div class="wp-utilities wp-utilities-cron">
	<?php
	$avg_now = ceil( time() / 900 ) * 900; // current time, rounded to 15 mins
	$now_date = get_date_from_gmt( '@' . $avg_now, 'Y-m-d' );
	$now_time = get_date_from_gmt( '@' . $avg_now, 'h:i A' );
	foreach( $this->utils['cron'] as $slug => $header ) {
		$task = $this->get_scheduled_task_info( $slug );
		$active = false;
		$status_class = 'inactive';
		$icon = 'dashicons-clock';
		if( !$task ) {
			$task = array(
				'frequency' => 'once',
				'next_run'  => $avg_now,
				'next_date' => $now_date,
				'next_time' => $now_time,
				'args'      => array()
			);
		} else {
			if( empty( $task['frequency'] ) ) {
				$task['frequency'] = 'once';
			} else {
				$icon = 'dashicons-backup';
			}
			$active = true;
			$status_class = 'active active-' . $task['frequency'];
		}
		?>
		<div class="wp-utility utility <?php echo $status_class; ?> wp-utility-<?php echo esc_attr( sanitize_title( str_replace( '.php', '', basename( $header['file'] ) ) ) ); ?>">
			<header>
				<span class="cron-icon dashicons <?php echo $icon; ?>">
					<span class="screen-reader-text">status: inactive</span>
				</span>
				<h3><?php echo $header['Utility Name']; ?></h3>
				<p><?php if( isset( $header['Description'] ) ) { echo $header['Description']; } ?></p>
			</header>
			<div>
				<div class="wp-utility-input-form">
					<input type="hidden" class="wp_util_nonce" value="<?php echo esc_attr( wp_create_nonce( "wp_utility_script_" . $slug ) ); ?>">
					<?php
					if( !$active ) {
						?>
						<form class="wp-util-input">
							<?php
							if( !empty( $header['Supports'] ) && in_array( 'input', $header['Supports'] ) ) {
								// throw away any output generated
								$this->safe_include( $slug );
								echo apply_filters( 'wp_util_input_html', "", $slug );
								remove_all_filters( 'wp_util_input_html' );
								remove_all_filters( 'wp_util_script'     );
							}
							?>
						</form>
						<?php
					}
					?>
					<form class="wp_util_schedule">
						<h4>Cron Settings</h4>
						<label>
							<span>Frequency</span>
							<select name="frequency" <?php disabled( $active ); ?>>
								<?php

								foreach($this->cron_fqs as $val => $label ) {
								?>
									<option value="<?php echo $val; ?>"  <?php selected( $val, $task['frequency'] ); ?>><?php echo $label; ?></option>
								<?php
								}
								?>
							</select>
						</label>
						<label>
							<span>Next Run</span>
							<input type="text" name="start_date" class="datepicker" value="<?php echo $task['next_date']; ?>" <?php disabled( $active ); ?>>
						</label>
						<label>
							<span class="screen-reader-text">Time</span>
							<select name="start_time" <?php disabled( $active ); ?>>
								<?php
								$aps = array('AM', 'PM');
								foreach( $aps as $ap ) {
									$hs = array( '12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11' );
									foreach( $hs as $h ) {
										$ms = array( '00', '15', '30', '45' );
										foreach( $ms as $m ) {
											$time_str = $h . ":" . $m . ' ' . $ap;
											if( $time_str == "12:00 AM" ) {
												$time_str = "Midnight";
											} elseif( $time_str == "12:00 PM" ) {
												$time_str = "Noon";
											}
											?><option value="<?php echo $time_str; ?>" <?php selected( $time_str, $task['next_time'] ); ?>><?php echo $time_str; ?></option><?php
										}
									}
								}
								?>
							</select>
						</label>
					</form>
					<?php
					if( !$active ) {
						?>
						<button class="wp_util_button_schedule button button-primary button-large" data-util-slug="<?php echo $slug; ?>">Schedule Utility</button>
						<?php
					} else {
						?>
						<button class="wp_util_button_cancel button button-primary button-large" data-util-slug="<?php echo $slug; ?>">Cancel Scheduled Utility</button>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	</div>
	<?php
} else {
	?>
	<p><em>	&laquo;No Scheduleable Utilities Found&raquo;</em></p>
	<?php
}
