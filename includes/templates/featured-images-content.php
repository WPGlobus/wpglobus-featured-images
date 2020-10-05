<?php
/**
 * File: featured-images-content.php
 *
 * @package WPGlobus Featured Images
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hidden_types = array();
if ( class_exists('WPGlobus_Post_Types') ) {
	$hidden_types = WPGlobus_Post_Types::hidden_types();
}

$_post_types = array_merge(
	array(
		'post' => 'post',
		'page' => 'page'
	),
	get_post_types(
		array(
			'_builtin' => false,
		)
	)
);

$post_types = array(
	'on' => array(),
	'off' => array(),
);
foreach ( $_post_types as $_post_type ) {
	if ( ! empty($hidden_types) && in_array( $_post_type, $hidden_types ) ) {
		continue;
	}	
	if ( post_type_supports( $_post_type, 'thumbnail' ) ) {
		$post_types['on'][] = $_post_type;
	} else {
		$post_types['off'][] = $_post_type;
	}
}
$support_keys = array( 'on', 'off' );
ob_start();
?>
<div class="wpglobus-featured-images-options-box">
	<h3>
		Before using WPGlobus Featured Images with existing post types,<br/>
		please, be sure they are supporting "thumbnail" feature.<br/>
	</h3>
	<!--<h4>List of post types:</h3>-->
	<ul>
		<?php // foreach ( $post_types as $support=>$post_types ) : ?>
		<?php foreach ( $support_keys as $key ) : ?>
			<?php if ( 'on' == $key ) {
				$message = 'supports thumbnail';
			} else {
				$message = 'doesn\'t support thumbnail';
			} ?>
			<?php foreach ( $post_types[$key] as $post_type ) { ?>
				<li><span>Post type <b><?php echo $post_type; ?></b>&nbsp;<?php echo $message; ?></span><hr /></li>
			<?php } ?>	
		<?php endforeach; ?>
	</ul>
</div><!-- .wpglobus-featured-images-options-box -->
<?php
$featured_images_content = ob_get_clean();
return $featured_images_content;

# --- EOF