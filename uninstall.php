<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

foreach((get_posts(array("post_type" => "custom_notice", "posts_per_page" => -1))) as $post){
  wp_delete_post($post->ID, true);
}
