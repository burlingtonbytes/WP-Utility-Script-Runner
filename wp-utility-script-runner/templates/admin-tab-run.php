<?php if(!defined('ABSPATH')) { die(); } // Include in all php files, to prevent direct execution

echo '<h3>Run Scripts:</h3>';
echo '<hr>';
if( count( $this->utils['active'] ) ) {
	?>
	<div class="wp-utilities">
	<?php
	foreach( $this->utils['active'] as $slug => $header ) {
		?>
		<div class="wp-utility utility wp-utility-<?php echo esc_attr( sanitize_title( str_replace( '.php', '', basename( $header['file'] ) ) ) ); ?>">
			<header>
				<h3><?php echo $header['Utility Name']; ?></h3>
				<p><?php if( isset( $header['Description'] ) ) { echo $header['Description']; } ?></p>
			</header>
			<div>
				<div class="wp-utility-input-form">
					<input type="hidden" class="wp_util_nonce" value="<?php echo esc_attr( wp_create_nonce( "wp_utility_script_" . $slug ) ); ?>">
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
					<button class="wp_util_button_run button button-primary button-large" data-util-slug="<?php echo $slug; ?>">Run Utility</button>
					<div class="response"></div>
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
	<p><em>	&laquo;No Active Utilities Found&raquo;</em></p>
	<?php
}
