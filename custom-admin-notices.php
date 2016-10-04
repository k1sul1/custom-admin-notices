<?php
/**
 * Plugin Name: Custom admin notices
 * Version: 0.4-alpha
 * Description: This plugin allows you to create your own notices that show up in the WordPress admin.
 * Author: Christian Nikkanen
 * Author URI: https://github.com/k1sul1/
 * Text Domain: custom-admin-notices
 * @package Custom admin notices
 */

namespace k1sul1\custom_admin_notices;

// Value for get_option (database name)
const SETTING_NAME = "can_settings";

// Settings page slug
const PAGE_NAME = "can-settings";

// Settings page title
const PAGE_TITLE = "Custom admin notices settings";

// Settings page description
const PAGE_DESCRIPTION = "Settings for custom admin notices";

// Menu title (in plugins menu)
const MENU_TITLE = "Custom admin notices";

// Helper for absolute path references
const HOME_DIR = __DIR__;

// Helpers for use get_plugin_data
const BASE_NAME = __FILE__;

// Capability required for managing settings
const REQUIRE_CAPS = "manage_options";

// Include settings and actions
require HOME_DIR . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "class.custom-admin-notices.php";
require HOME_DIR . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "settings.php";
require HOME_DIR . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "admin-actions.php";
require HOME_DIR . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "actions.php";
