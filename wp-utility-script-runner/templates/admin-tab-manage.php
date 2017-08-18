<?php if(!defined('ABSPATH')) { die(); } // Include in all php files, to prevent direct execution
?>
<h3>All Scripts:</h3>
<p>
	Utility Scripts can be added to your theme, by placing them in a subfolder
	named 'utilities'. <a href="#" class="download-sample-util">Download a sample utility script</a>
	and get started making your own.
</p>
<script type="text/template" class="sample-util-source"><?php echo $this->get_sample_script(); ?></script>
<hr>
<div class="wp-utilities-all">
<?php
if( count( $all_utils ) ) {
	?>
	<table class="wp-list-table widefat utilities">
		<thead>
			<tr>
				<th scope="col" id="name" class="manage-column column-name column-primary">Utility</th>
				<th scope="col" id="description" class="manage-column column-name column-description">Description</th>
			</tr>
		</thead>
		<tbody id="the-list">
		<?php
		foreach( $all_utils as $slug => $header ) {
			$active = false;
			$active_class = "inactive";
			$disabled = ' disabled';
			if( isset( $this->utils['active'][$slug] ) ) {
				$active = true;
				$active_class = "active";
			}
			$enable_state_change = false;
			$url = "#";
			if( $header['Enabled'] == 'auto' ) {
				$url = $this->make_activation_link($slug, !$active);
				$disabled = '';
			}
			?>
			<tr class="wp-utility-<?php echo $slug; ?> <?php echo $active_class; ?>">
				<td>
					<strong><?php echo $header['Utility Name']; ?></strong>
					<div class="row-actions visible">
						<?php echo $url; ?>
					</div>
				</td>
				<td>
					<p><?php if( isset( $header['Description'] ) ) { echo $header['Description']; } ?></p>
					<div class="version-author-uri">
						<?php echo $this->list_header_info( $header ); ?>
					</div>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
		<tfoot>
			<tr>
				<th scope="col" id="name" class="manage-column column-name column-primary">Utility</th>
				<th scope="col" id="description" class="manage-column column-name column-description">Description</th>
			</tr>
		</tfoot>
	</table>
	<?php
}
echo '</div>';
