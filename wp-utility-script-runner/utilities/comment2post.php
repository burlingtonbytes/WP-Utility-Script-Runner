<?php if(!defined('ABSPATH')) { die(); }

/**
 * Utility Name: Comments to Posts
 * Description: convert top-level comments on a single post into their own posts in a specific Post Type
 * Author: Burlington Bytes, LLC
 * Author URI: https://www.burlingtonbytes.com
 * Supports: input
 * Version: 1.0.0
 **/

add_filter('wp_util_input_html', function( $html ) {
	$post_types = get_post_types(array(
		'public' => true
	));
	ob_start()
	?>
	<label>
		<span>Convert to Post Type: </span>
		<select name="post_type" required>
			<option default disabled>Select Post Type</option>
			<?php
			foreach( $post_types as $post_type ) {
				?>
				<option value="<?php echo esc_attr( $post_type ); ?>"><?php echo esc_attr( $post_type ); ?></option>
				<?php
			}
			?>
		</select>
	</label>
	<label>
		<span>Donor Post ID: </span>
		<input type="number" name="parent_id" min="0" required>
		<em>The ID of the post we are pulling comments from</em>
	</label>
	<?php
	return ob_get_clean();
});
add_filter('wp_util_script', function( $output, $state, $atts ) {
	$per_page = 50;

	$retval = array(
		'state'   => 'error',
		'message' => 'an unknown error has occurred'
	);
	$offset    = @$state['offset'];
	$offset    = (int)$offset;
	$post_type = @$atts['post_type'];
	$parent_id = @$atts['parent_id'];
	if( !$post_type || !$parent_id ) {
		$retval['message'] = "Required inputs must be populated";
		return $retval;
	}
	global $wpdb;
	$queryvars = array();
	$query = "SELECT * FROM `" . $wpdb->prefix . "comments` WHERE `comment_parent`=0 AND `comment_post_id`=%d LIMIT %d";
	$queryvars[] = $parent_id;
	$queryvars[] = $per_page;
	if( $offset ) {
		$query .= " OFFSET %d";
		$queryvars[] = $offset;
	}
	$comments = $wpdb->get_results( $wpdb->prepare( $query, $queryvars ) );
	if( !count( $comments ) ) {
		$queryvars = array();
		$query = "DELETE FROM `" . $wpdb->prefix . "comments` WHERE `comment_parent`=0 AND `comment_post_id`=%d";
		$queryvars[] = $parent_id;
		$wpdb->query( $wpdb->prepare( $query, $queryvars ) );
		return array(
			'state'=>'complete',
			'message'=> "Found a total of " . $offset . " Comments"
		);
	}
	foreach( $comments as $comment ) {
		$comment_id = $comment->comment_ID;
		$meta       = get_comment_meta( $id );
		$user_id    = $comment->user_id;
		$content    = $comment ->comment_content;
		$approved   = $comment->comment_approved;
		$date       = $comment->comment_date;
		$status = 'draft';
		if( $approved ) {
			$status = 'publish';
		}
		array_walk( $meta, function( $val ) {
			$val = maybe_unserialize( $val );
		});
		$post_id    = wp_insert_post( array(
			'post_author'  => $user_id,
			'post_date'    => $date,
			'post_content' => $content,
			'post_status'  => $status,
			'meta_input'   => $meta
		) );
		if( $post_id ) {
			$query = "UPDATE `" . $wpdb->prefix . "comments` SET `comment_parent`=0, `comment_post_id`=%d WHERE `comment_parent`=%d";
			$queryvars = array( $post_id, $comment_id );
			$wpdb->query( $wpdb->prepare( $query, $queryvars ) );
		}
	}
	$offset += count( $comments );
	return array(
		'state' => array(
			'offset' => $offset
		),
		'message' => $offset . " Comments Processed"
	);
}, 10, 3);
