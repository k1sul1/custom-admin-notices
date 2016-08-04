<?php
/**
 * Plugin Name: Custom admin notices
 * Version: 0.2
 * Description: This plugin allows you to create your own notices that show up in the WordPress admin.
 * Author: Christian Nikkanen
 * Author URI: https://github.com/k1sul1/
 * Text Domain: custom-admin-notices
 * @package Custom admin notices
 */

require_once("classes/class.custom-admin-notices.php");

add_action("init", function(){
  global $custom_admin_notices;
  $custom_admin_notices =  new customAdminNotices();
});
