<?php

function deactivate_and_die($file) {
    $message = sprintf(__('Levelup has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate.'), $file);

    if (!function_exists('deactivate_plugins')):
        include ABSPATH . 'wp-admin/includes/plugin.php';
    endif;

    deactivate_plugins(__FILE__);
    wp_die($message);
}


function safeRequireFile($file) {
  global $LVLTES_PLUGIN_DIR;

    if (!@include $LVLTES_PLUGIN_DIR . $file):
        deactivate_and_die($LVLTES_PLUGIN_DIR . $file);
    endif;
}
