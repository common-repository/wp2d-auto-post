<?php
/*
Plugin Name:	wp2d Auto Post
Plugin URI:		https://progr.interplanety.org/en/wordpress-plugin-wp2d/
Version:		1.0.0
Author:			Nikita Akimov
Author URI:		https://progr.interplanety.org/en/
License:		GPL-3.0-or-later
Description:	Autopost publishing posts to the Discord server
*/

//	not run directly
if(!defined('ABSPATH')) {
	exit;
}


// ---------- autoposting

function wp2d_post_to_discord($new_status, $old_status, $post) {
	// autopost
	if(in_array($post->post_type, ['post', 'page'])
			&& $new_status === 'publish'
			&& $old_status !== 'publish') {
		
		// if "do_autopost" is checked
		// first check in $_POST because transition_post_status event executes before saving post
		//	so this value can be changed on the form but this change stil is not saved
		$do_autopost = 'no';
		if(isset($_POST) && $_POST) {
			// published by user pressed a button
			if(isset($_POST['wp2d_do_autopost']) && $_POST['wp2d_do_autopost']) {
				$do_autopost = ($_POST['wp2d_do_autopost'] == 'on' ? 'yes' : 'no');
			}
		} else {
			// published by shedule - ?
			$do_autopost = get_post_meta(
				$post->ID,
				'wp2d_do_autopost',
				true
			);
		}

		if($do_autopost && $do_autopost == 'yes') {
			// check webhook_url existed
			$plugin_options = get_option('wp2d_plugin_options');
			$webhook_url = $plugin_options['wp2d_webhook_url'];

			if($webhook_url) {

				$embed = [];	// "embed" structure to sent to discord

				// author
				$author_name = get_the_author_meta('display_name', $post->post_author);
				if($author_name) {
					$embed['author'] = [
						'name' => $author_name
					];
					$author_url = get_author_posts_url($post->post_author);
					if($author_url) {
						$embed['author']['url'] = $author_url;
					}
					$author_avatar_url = get_avatar_url($post->post_author);
					if($author_avatar_url) {
						$embed['author']['icon_url'] = $author_avatar_url;
					}
				}

				// title
				$post_title = $post->post_title;
				if($post_title) {
					$embed['title'] = $post_title;
				}

				// permalink
				$permalink = get_permalink($post->ID);
				if (in_array($post->post_status, array('draft', 'pending', 'auto-draft', 'future'))) {
					$permalink_arr = get_sample_permalink($post->ID);
					$permalink = str_replace('%postname%', $permalink_arr[1], $permalink_arr[0]);
				}
				if($permalink) {
					$embed['url'] = $permalink;
				}

				// image
				$post_thumbnail = get_the_post_thumbnail_url($post->ID);
				if($post_thumbnail) {
					$embed['image'] = [
						'url' => $post_thumbnail
					];
				}

				// encode to JSON
				$content = json_encode(
					[
						'embeds' => [
							$embed
						]
					]
				);

				// if use "content" structure - problems with html tags, so it's better to use "embed"
				// $content = '**'.$post_title.'**' . PHP_EOL . 'by ' . $author_name . PHP_EOL . $permalink . PHP_EOL . '<img src="' . $post_thumbnail . '">';
				// $content = json_encode(
				// 	array(
				// 		'content' => $content
				// 	)
				// );
				// error_log($content);
				
				// send POST request to discord
				$opts = array(
					'http' => array(
						'method'  => 'POST',
						'header'  => 'Content-Type:application/json',
						'content' => $content
					)
				);

				// make POST request to Discord
				$response = wp_remote_post(
					$webhook_url,
					array(
						'method' => 'POST',
						'headers' => array('Content-Type' => 'application/json'),
						'body' => $content
						)
				);
				// error_log(print_r($response, true));
			}
		}
	}
}

add_action('transition_post_status',  'wp2d_post_to_discord', 10, 3);


// ---------- settings menu

function wp2d_add_options_page() {
	// function to render the options page
	add_options_page(
		'WP2D',						// page title text
		'WP2D',						// menu item text
		'manage_options',			// user rights
		'wp2d/wp2d-options.php'		// file to render the options view page
	);
}

add_action('admin_menu',  'wp2d_add_options_page');


// ---------- page

function wp2d_register_settings() {
	// function to register plugin options
    // whole wp2d settings
	register_setting(
		'wp2d_plugin_options',	// option group
		'wp2d_plugin_options'		// option name
	);
	// discord api section in settings
    add_settings_section(
		'discord_api',				// id
		'Discord API Settings',		// title
		'wp2d_section_text',		// function name for render section title
		'wp2d_plugin'				// page id for do_settings_section
	);
	// options fields
	// webhook
    add_settings_field(
		'wp2d_webhook_url',		// id
		'Webhook URL',				// field title
		'wp2d_webhook_url',		// function name for rendering this option
		'wp2d_plugin',			// menu page id
		'discord_api'				// section id
	);
}
// function for render section title
function wp2d_section_text() {
	echo 'Discord webhook settings:';
}
// functions for current option render
function wp2d_webhook_url() {
    $options = get_option('wp2d_plugin_options');
    echo '<input id="' . 'wp2d_webhook_url" name="'.'wp2d_plugin_options['.'wp2d_webhook_url]" type="text"
		value="' . esc_attr($options['wp2d_webhook_url']) . '" style="width: 90%;">';
}

add_action('admin_init',  'wp2d_register_settings');


// ---------- "settings" link for plugin on plugins page

function wp2d_settings_link($links) {
    return array_merge(
		array(
			'settings' => '<a href="options-general.php?page=wp2d/wp2d-options.php">' . __('Settings') . '</a>'
		),
		$links
	);
}

add_filter('plugin_action_links_' . plugin_basename( __FILE__ ),  'wp2d_settings_link');


// ---------- adding metabox with option to posts

function wp2d_meta_box() {
    // define meta_box
    add_meta_box(
		'wp2d_metabox',			// id
		'WP2D',						// title
		'wp2d_meta_box_render',	// function name for rendering this metabox
		array(
			'post',
			'page'
		),							// post types to use this metabox in
		'normal',					// show in bottom bar
		'default'					// priority: high - to show above other blocks, low - under other blocks
	);
}

// functions for current metabox render
function wp2d_meta_box_render($post) {
	// security hidden field
	wp_nonce_field(plugin_basename(__FILE__), 'wp2d_meta_nonce');
	// wp2d_do_autopost checkbox field
	$do_autopost = get_post_meta(
						$post->ID,
						'wp2d_do_autopost',
						true
					);
	$do_autopost = ($do_autopost ? $do_autopost : 'yes');	// by default = 'yes'
	?>
	
	<label for=" <?php echo 'wp2d_do_autopost'; ?> ">
		<input type="checkbox" name="<?php echo 'wp2d_do_autopost'; ?>" id="<?php echo 'wp2d_do_autopost'; ?>"
			<?php echo ($do_autopost == 'yes' ? ' checked="checked"' : ''); ?>
			>
		<?php echo __('Autopost to Discord'); ?>
	</label>

	<?php
}

// function for saving metabox data
function wp2d_meta_box_save($post_id) {
	// check and save/update
    if(!isset($_POST['wp2d_meta_nonce'])) {
		return $post_id;
	}
    if(!wp_verify_nonce($_POST['wp2d_meta_nonce'], plugin_basename(__FILE__))) {
		return $post_id;
	}
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}
    if(isset($_POST['wp2d_do_autopost'])) {
		update_post_meta($post_id, 'wp2d_do_autopost', 'yes');
    } else {
		update_post_meta($post_id, 'wp2d_do_autopost', 'no');
	}
}

add_action('add_meta_boxes', 'wp2d_meta_box');
add_action('save_post', 'wp2d_meta_box_save');

?>
