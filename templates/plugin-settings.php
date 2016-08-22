<form action="options.php" method="post">
    <?php
    if (array_key_exists("page_title", $settings)) {
        printf("<h2>%s</h2>", $settings["page_title"]);
    }

    if (array_key_exists("description", $settings)) {
        printf("<p>%s</p>", $settings["description"]);
    }

    settings_fields($settings["setting_name"]);
    do_settings_sections($settings["page_name"]);
    submit_button();
    ?>
</form>

<?php if (WP_DEBUG): ?>
    <hr>
    <pre><?php echo var_export($settingsValues) ?></pre>
<?php endif; ?>
