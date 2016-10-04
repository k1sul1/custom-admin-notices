<?php
namespace k1sul1\custom_admin_notices\AdminActions;

use k1sul1\custom_admin_notices as Plugin;
use k1sul1\custom_admin_notices\Settings as Settings;

// Add admin actions and handlers here

add_action("init", __NAMESPACE__ . "\\initialize");

function initialize(){
  global $custom_admin_notices;
  $custom_admin_notices =  new \customAdminNotices(); // It's in global namespace.
}


/* There should be very little need to edit anything below this line */
add_action("admin_init", __NAMESPACE__ . "\\enqueuePubResources");
add_action("admin_notices", __NAMESPACE__ . "\\adminNotices");
add_action("admin_menu", __NAMESPACE__ . "\\registerSettingsPage");
add_filter(
    "plugin_action_links_" . plugin_basename(Plugin\BASE_NAME),
    __NAMESPACE__ . "\\renderPluginActionsLinks"
);

function enqueuePubResources()
{
    $pluginVersion = Settings\getPluginVersion();
    $urlStem = plugin_dir_url(Plugin\BASE_NAME);

    wp_enqueue_script(
        Plugin\SETTING_NAME . "-helpers",
        $urlStem . "/pub/admin-helpers.js",
        null,
        $pluginVersion,
        true
    );

    wp_enqueue_style(
        Plugin\SETTING_NAME . "-styles",
        $urlStem . "/pub/admin-styles.css",
        null,
        $pluginVersion,
        "screen"
    );
}

function registerSettingsPage()
{
    $settings = Settings\getSettings();

    add_plugins_page(
        $settings[Settings\S_PAGE_TITLE],
        $settings[Settings\S_MENU_TITLE],
        $settings[Settings\S_REQUIRE_CAPS],
        $settings[Settings\S_PAGE_NAME],
        getPageRenderer()
    );
}

function getErrorCallback()
{
    return __NAMESPACE__ . "\\addSettingsError";
}

function addSettingsError($message, $type = "error")
{
    $settings = Settings\getSettings();

    add_settings_error(
        $settings[Settings\S_SETTING_NAME],
        $settings[Settings\S_PAGE_NAME],
        $message,
        $type
    );
}

function adminNotices()
{
    $settings = Settings\getSettings();
    $errors = get_settings_errors();

    foreach ($errors as $error) {
        if ($error["type"] != "error" && $error["type"] != "updated") {
            continue;
        } elseif ($error["code"] === $settings[Settings\S_PAGE_NAME]) {
            ?>
            <div class="<?php echo $error["type"] ?>"><p><?php echo $error["message"] ?></p></div>
            <?php
        }
    }
}

function getPageRenderer()
{
    return __NAMESPACE__ . "\\renderSettingsPage";
}

function getSectionRenderer()
{
    return __NAMESPACE__ . "\\renderSection";
}

function getFieldRenderer()
{
    return __NAMESPACE__ . "\\renderField";
}

function renderPluginActionsLinks($links)
{
    $settings = Settings\getSettings();

    array_unshift(
        $links,
        sprintf(
            "<a href=\"plugins.php?page=%s\">%s</a>",
            $settings[Settings\S_PAGE_NAME],
            "Settings"
        )
    );

    return $links;
}

function renderSettingsPage()
{
    $settings = Settings\getSettings();
    $settingsValues = WP_DEBUG ? Settings\getFieldValues(true) : null;

    include Plugin\HOME_DIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "plugin-settings.php";
}

function renderSection($section)
{
    $settings = Settings\getSettings();

    if (array_key_exists($section[Settings\PROP_ID], $settings[Settings\S_SECTIONS])) {
        $attribs = $settings[Settings\S_SECTIONS][$section[Settings\PROP_ID]];
        ?>
        <p id="<?php echo $section[Settings\PROP_ID] ?>"><?php echo $attribs[Settings\PROP_DESCRIPTION] ?></p>
        <?php
    }
}

function renderField($args)
{
    switch ($args[Settings\PROP_TYPE])
    {
        case Settings\FIELD_NUMBER:
        case Settings\FIELD_EMAIL:
        case Settings\FIELD_TEXT:
        case Settings\FIELD_URL:
        case Settings\FIELD_DATE:
        case Settings\FIELD_DATETIME:
        case Settings\FIELD_TIME:
            renderTextField($args);
            break;

        case Settings\FIELD_TEXT_MULTILINE:
            renderTextField($args, true);
            break;

        case Settings\FIELD_SELECT:
            renderSelect($args);
            break;

        case Settings\FIELD_RADIO:
            renderRadioButtons($args);
            break;

        case Settings\FIELD_CHECKBOX:
            renderCheckbox($args);
            break;

        case Settings\FIELD_TOGGLE:
            renderToggle($args);
            break;

        default:
            error_log("Unknown field type: " . $args[Settings\PROP_TYPE]);
            break;
    }

    if (array_key_exists(Settings\PROP_DESCRIPTION, $args) && !empty($args[Settings\PROP_DESCRIPTION])) {
        printf("<p>%s</p>", $args[Settings\PROP_DESCRIPTION]);
    }
}

function getFieldName($args) {
    return sprintf("%s[%s]", $args[Settings\S_SETTING_NAME], $args[Settings\PROP_FIELD_NAME]);
}

function renderTextField($args, $multiline = false)
{
    if (!$multiline) {
        ?>
        <input
            type="<?php echo $args[Settings\PROP_TYPE] ?>"
            name="<?php echo getFieldName($args) ?>"
            value="<?php echo $args[Settings\PROP_VALUE] ?>"
            id="<?php echo $args[Settings\PROP_FIELD_NAME] ?>"
            placeholder="<?php echo $args[Settings\PROP_PLACEHOLDER] ?>"
            class="<?php echo $args[Settings\PROP_CLASSNAME] ?> textinput"
        />
        <?php
    } else {
        ?>
        <textarea
            name="<?php echo getFieldName($args) ?>"
            id="<?php echo $args[Settings\PROP_FIELD_NAME] ?>"
            placeholder="<?php echo $args[Settings\PROP_PLACEHOLDER] ?>"
            class="<?php echo $args[Settings\PROP_CLASSNAME] ?> textinput"
        ><?php echo $args[Settings\PROP_VALUE] ?></textarea>
        <?php
    }
}

function renderSelect($args)
{
    $useKeyForValue = isAssocArray($args[Settings\PROP_OPTIONS]);
    ?>
    <select
        name="<?php echo getFieldName($args) ?>"
        id="<?php echo $args[Settings\PROP_FIELD_NAME] ?>"
        class="<?php echo $args[Settings\PROP_CLASSNAME] ?>"
    >

    <?php if (!is_null($args[Settings\PROP_DEFAULT])): ?>
        <option value="<?php echo Settings\V_UNSELECTED_VALUE ?>"><?php echo $args[Settings\PROP_DEFAULT] ?></option>
    <?php endif; ?>

    <?php foreach ($args[Settings\PROP_OPTIONS] as $value => $label): ?>
        <?php
        $selected = $args[Settings\PROP_VALUE] === ($useKeyForValue ? $value : $label);
        ?>

        <?php if ($useKeyForValue): ?>
            <option
                value="<?php echo $value ?>"
                <?php if ($selected): ?>selected<?php endif; ?>
            ><?php echo $label ?></option>
        <?php else: ?>
            <option
                <?php if ($selected): ?>selected<?php endif; ?>
            ><?php echo $label ?></option>
        <?php endif; ?>
    <?php endforeach; ?>

    </select>
    <?php
}

function renderRadioButtons($args)
{
    $useKeyForValue = isAssocArray($args[Settings\PROP_OPTIONS]);

    foreach ($args[Settings\PROP_OPTIONS] as $value => $label):
        $selected = $args[Settings\PROP_VALUE] === ($useKeyForValue ? $value : $label);
        $fieldValue = $useKeyForValue ? $value : $label;
        $isDefault = $fieldValue === $args[Settings\PROP_DEFAULT];
        ?>

        <label>
        <input
            type="radio"
            name="<?php echo getFieldName($args) ?>"
            id="<?php echo $args[Settings\PROP_FIELD_NAME] ?>"
            class="<?php echo $args[Settings\PROP_CLASSNAME] ?>"
            value="<?php echo $fieldValue ?>"
            <?php if ($selected): ?>checked<?php endif; ?>
        >

        <?php echo $label ?></label> <?php if ($isDefault): ?>(default)<?php endif; ?><br>
    <?php
    endforeach;
    ?>
    <a href="#" class="cloak" data-action="clear" data-input="<?php echo $args[Settings\S_SETTING_NAME] ?>[<?php echo $args[Settings\PROP_FIELD_NAME] ?>]">Clear selection</a>
    <?php
}

function renderCheckbox($args)
{
    $useKeyForValue = isAssocArray($args[Settings\PROP_OPTIONS]);
    $valueFlipped = is_array($args[Settings\PROP_VALUE]) ? array_flip($args[Settings\PROP_VALUE]) : array();

    foreach ($args[Settings\PROP_OPTIONS] as $value => $label):
        $selected = array_key_exists($useKeyForValue ? $value : $label, $valueFlipped);
        $fieldValue = $useKeyForValue ? $value : $label;
        $isDefault = $fieldValue === $args[Settings\PROP_DEFAULT];
        ?>

        <label>
        <input
            type="checkbox"
            name="<?php echo getFieldName($args) ?>[]"
            id="<?php echo $args[Settings\PROP_FIELD_NAME] ?>"
            class="<?php echo $args[Settings\PROP_CLASSNAME] ?>"
            value="<?php echo $useKeyForValue ? $value : $label ?>"
            <?php if ($selected): ?>checked<?php endif; ?>
        >

        <?php echo $label ?></label> <?php if ($isDefault): ?>(default)<?php endif; ?><br>
    <?php
    endforeach;
}

function renderToggle($args) {
    $selectedValue = !is_null($args[Settings\PROP_VALUE])
        ? $args[Settings\PROP_VALUE]
        : $args[Settings\PROP_DEFAULT];

    $options = $args[Settings\PROP_OPTIONS] && 2 <= count($args[Settings\PROP_OPTIONS])
        ? array_slice(array_values($args[Settings\PROP_OPTIONS]), 0, 2)
        : array("Off", "On");

    foreach ($options as $value => $label):
        $selected = !!$value === $selectedValue;
        $isDefault = !!$value === $args[Settings\PROP_DEFAULT];
        $fieldValue = !!$value ? Settings\V_LITERAL_TRUE : Settings\V_LITERAL_FALSE;
        ?>

        <label><input
            type="radio"
            name="<?php echo getFieldName($args) ?>"
            value="<?php echo $fieldValue ?>"
            <?php if ($selected): ?>checked<?php endif; ?>
        > <?php echo $label ?></label> <?php if ($isDefault): ?>(default)<?php endif; ?><br>
    <?php
    endforeach;
}

function isAssocArray($arr)
{
    return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
}
