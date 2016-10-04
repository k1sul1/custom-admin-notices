<?php
namespace k1sul1\custom_admin_notices\Settings;

use k1sul1\custom_admin_notices as Plugin;
use k1sul1\custom_admin_notices\AdminActions as AdminActions;

const SECTION_DEFAULT = "default";

function getSections()
{
    // Add Setting sections here
    return array(
        SECTION_DEFAULT => array(
            PROP_TITLE => "",
            PROP_DESCRIPTION => ""
        )
    );
}

function getFields()
{
    // Add fields here
    $fields = array(
        array(
          PROP_SECTION => SECTION_DEFAULT,
          PROP_TYPE => FIELD_SELECT,
          PROP_NAME => "allow-environments",
          PROP_TITLE => "Allow environments? (Bedrock)",
          PROP_DEFAULT => false,
          PROP_DESCRIPTION => "Enabling this will allow you to select environments
          in which the notice will be shown.<br>Exceptionally useful if you want to show a notice
          for yourself only.",
          PROP_OPTIONS => array("true" => "Yes", "false" => "No")
        ),
        array(
          PROP_SECTION => SECTION_DEFAULT,
          PROP_TYPE => FIELD_RADIO,
          PROP_NAME => "determine-environment",
          PROP_TITLE => "Determine environment from WP_ENV?",
          PROP_DEFAULT => false,
          PROP_DESCRIPTION => "Enable to determine environment from WP_ENV environment variable. Default is match-by-URL.<br>Only effective if allow-environments is set to true.",
          PROP_OPTIONS => array("true" => "Yes", "false" => "No")
        ),
        array(
          PROP_SECTION => SECTION_DEFAULT,
          PROP_TYPE => FIELD_TEXT_MULTILINE,
          PROP_NAME => "environments",
          PROP_TITLE => "Environments",
          PROP_DEFAULT => "development\nstaging\nproduction",
          PROP_DESCRIPTION => "List environments in use. One environment per line. Case insensitive. <br>Defaults: development, staging, production."
        )
    );
    return $fields;
}


// If version migrations need work done, here's the place to do it
function migrateVersion($values, $fromVersion, $toVersion)
{
    return $values;
}

/* There should be very little need to edit anything below this line */

/* Field types */
const FIELD_TEXT = "text";
const FIELD_TEXT_MULTILINE = "textarea";
const FIELD_URL = "url";
const FIELD_EMAIL = "email";
const FIELD_DATE = "date";
const FIELD_TIME = "time";
const FIELD_DATETIME = "datetime";
const FIELD_NUMBER = "number";
const FIELD_SELECT = "select";
const FIELD_RADIO = "radio";
const FIELD_CHECKBOX = "checkbox";
const FIELD_TOGGLE = "toggle";

/* Field/Section properties */
const PROP_DEFAULT = "default";
const PROP_DESCRIPTION = "description";
const PROP_NAME = "name";
const PROP_OPTIONS = "options";
const PROP_PLACEHOLDER = "placeholder";
const PROP_SANITIZE = "sanitize";
const PROP_SECTION = "section";
const PROP_TITLE = "title";
const PROP_TYPE = "type";
const PROP_VALIDATE = "validate";

/* Generated properties */
const PROP_ID = "id";
const PROP_VALUE = "value";
const PROP_FIELD_NAME = "field_name";
const PROP_LABEL_FOR = "label_for";
const PROP_CLASSNAME = "className";

/* Setting names */
const S_SETTING_NAME = "setting_name";
const S_PAGE_NAME = "page_name";
const S_PAGE_TITLE = "page_title";
const S_MENU_TITLE = "menu_title";
const S_DESCRIPTION = "description";
const S_REQUIRE_CAPS = "require_caps";
const S_SECTIONS = "sections";
const S_FIELDS = "fields";
const S_PLUGIN_VERSION = "plugin_version";

/* Default unselected value for radio/checkbox/select */
const V_UNSELECTED_VALUE = "____no_selection____";
const V_LITERAL_FALSE = "____false____";
const V_LITERAL_TRUE = "____true____";

add_action("admin_init", __NAMESPACE__ . "\\registerSettings");

function getSettings()
{
    return array(
        S_SETTING_NAME => Plugin\SETTING_NAME,
        S_PAGE_NAME => Plugin\PAGE_NAME,
        S_PAGE_TITLE => Plugin\PAGE_TITLE,
        S_MENU_TITLE => Plugin\MENU_TITLE,
        S_DESCRIPTION => Plugin\PAGE_DESCRIPTION,
        S_REQUIRE_CAPS => Plugin\REQUIRE_CAPS,
        S_SECTIONS => getSections(),
        S_FIELDS => array_filter(getFields(), __NAMESPACE__ . "\\isValidField")
    );
}

function getFieldValues($setDefault = false, $section = false)
{
    $settings = getSettings();
    $option = get_option($settings[S_SETTING_NAME]);
    $values = array(
        S_PLUGIN_VERSION => is_array($option) && array_key_exists(S_PLUGIN_VERSION, $option)
            ? $option[S_PLUGIN_VERSION]
            : getPluginVersion()
    );

    foreach ($settings[S_FIELDS] as $attribs) {
        if ($section && $section != $attribs[PROP_SECTION]) {
            continue;
        }

        $fieldName = $attribs[PROP_SECTION] . ":" . $attribs[PROP_NAME];

        // Prefix the export key with the section name when the section argument is unset
        $exportKey = false === $section
            ? $fieldName
            : $attribs[PROP_NAME];

        if ($attribs[PROP_TYPE] === FIELD_TOGGLE && !is_null($option[$fieldName])) {
            $values[$exportKey] = !!$option[$fieldName];
        } elseif (is_array($option) && array_key_exists($fieldName, $option) && !empty($option[$fieldName])) {
            $values[$exportKey] = $option[$fieldName];
        } elseif ($setDefault) {
            $values[$exportKey] = $attribs[PROP_DEFAULT];
        } else {
            $values[$exportKey] = null;
        }
    }

    return $values;
}

function isValidField($field)
{
    if (!is_array($field)) {
        error_log("A field definition must be an array");
        return false;
    }

    $hasRequiredProps = isset($field[PROP_NAME]) && isset($field[PROP_TITLE]) && isset($field[PROP_SECTION]);

    if (!$hasRequiredProps) {
            error_log(sprintf(
                "A field is missing one or more required property. Required properties are: %s, %s, %s.",
                PROP_NAME,
                PROP_TITLE,
                PROP_SECTION
            ));
            return false;
    } elseif ($hasRequiredProps && !array_key_exists($field[PROP_SECTION], getSections())) {
        error_log(sprintf(
            "Field `%s` is assigned to an undefined section `%s`",
            $field[PROP_NAME],
            $field[PROP_SECTION]
        ));
        return false;
    }

    return true;
}

function getPluginVersion()
{
    $pluginVersion = "";
    $pluginData = null;

    if (function_exists("get_plugin_data")) {
        $pluginData = \get_plugin_data(Plugin\BASE_NAME);
    } else {
        if (!function_exists("get_plugins")) {
            require_once(ABSPATH . "wp-admin/includes/plugin.php");
        }

        $pluginData = \get_plugins(DIRECTORY_SEPARATOR . \plugin_basename(Plugin\HOME_DIR));

        if (array_key_exists(basename(Plugin\BASE_NAME), $pluginData)) {
            $pluginData = $pluginData[basename(Plugin\BASE_NAME)];
        }
    }

    if (null != $pluginData && array_key_exists("Version", $pluginData)) {
        $pluginVersion = $pluginData["Version"];
    }

    return $pluginVersion;
}

function identity($value)
{
    return $value;
}

function sanitize($input)
{
    $settings = getSettings();
    $values = getFieldValues();
    $output = array(
        S_PLUGIN_VERSION => getPluginVersion()
    );

    // Filter and validate incoming data
    foreach ($settings[S_FIELDS] as $attribs) {
        $fieldName = $attribs[PROP_SECTION] . ":" . $attribs[PROP_NAME];

        // Skip any fields that don't exists
        if (!array_key_exists($fieldName, $input)) {
            continue;
        }

        $transientValue = $input[$fieldName];

        // Map symbolic values into their actual value
        switch ($transientValue) {
            case V_UNSELECTED_VALUE:
                $transientValue = null;
                break;

            case V_LITERAL_TRUE:
                $transientValue = 1;
                break;

            case V_LITERAL_FALSE:
                $transientValue = 0;
                break;
        }

        $validator = array_key_exists(PROP_VALIDATE, $attribs) && is_callable($attribs[PROP_VALIDATE])
            ? $attribs[PROP_VALIDATE]
            : __NAMESPACE__ . "\\identity";
        $sanitizer = array_key_exists(PROP_SANITIZE, $attribs) && is_callable($attribs[PROP_SANITIZE])
            ? $attribs[PROP_SANITIZE]
            : __NAMESPACE__ . "\\identity";

        $transientValue = call_user_func(
            $validator,
            call_user_func($sanitizer, $transientValue),
            $attribs,
            AdminActions\getErrorCallback()
        );

        $output[$fieldName] = $transientValue;
    }

    // When version numbers don't match, do a migration
    if ($values[S_PLUGIN_VERSION] !== $output[S_PLUGIN_VERSION]) {
        $output = migrateVersion($output, $values[S_PLUGIN_VERSION], $output[S_PLUGIN_VERSION]);
    }

    return $output;
}

function normaliseAttribNames($attribs)
{
    if (array_key_exists("sanitise", $attribs)) {
        error_log(sprintf(
            "Use spelling `sanitize` instead of `sanitise` for setting `%s` in section `%s`",
            $attribs[PROP_NAME],
            $attribs[PROP_SECTION]
        ));

        $attribs[PROP_SANITIZE] = $attribs["sanitise"];
        unset($attribs["sanitise"]);
    }

    return $attribs;
}

function registerSettings()
{
    $settings = getSettings();
    $values = getFieldValues();

    register_setting(
        $settings[S_SETTING_NAME],
        $settings[S_SETTING_NAME],
        __NAMESPACE__ . "\\sanitize"
    );

    foreach ($settings[S_SECTIONS] as $section => $attribs) {
        add_settings_section(
            $section,
            $attribs[PROP_TITLE],
            AdminActions\getSectionRenderer(),
            $settings[S_PAGE_NAME]
        );
    }

    $renderDefaults = array(
        PROP_TYPE => FIELD_TEXT,
        PROP_CLASSNAME => "",
        PROP_PLACEHOLDER => "",
        PROP_OPTIONS => null,
        PROP_DEFAULT => null,
        PROP_DESCRIPTION => null
    );

    foreach ($settings[S_FIELDS] as $attribs) {
        $fieldName = $attribs[PROP_SECTION] . ":" . $attribs[PROP_NAME];
        $renderingArgs = array_merge(
            $renderDefaults,
            normaliseAttribNames($attribs),
            array(
                PROP_VALUE => $values[$fieldName],
                PROP_LABEL_FOR => $fieldName,
                PROP_FIELD_NAME => $fieldName,
                S_SETTING_NAME => $settings[S_SETTING_NAME]
            )
        );

        add_settings_field(
            $fieldName,
            $attribs[PROP_TITLE],
            AdminActions\getFieldRenderer(),
            $settings[S_PAGE_NAME],
            $attribs[PROP_SECTION],
            $renderingArgs
        );
    }
}
