<?php
//error_reporting(E_ALL);
global $addon_lang, $hidden_csrf_token;
use Medoo\Medoo;

$mod_root = SE_ROOT.'plugins/openings/';
$openings_db_file = $mod_root.'data/openings.sqlite3';

// On the frontend, this file is reached via buffer_script() (a function),
// so a plain `$openings_db = ...` below would only become a local variable
// of that function's call frame - invisible to opn_*() helpers that do
// `global $openings_db;`. Declaring it global here forces it into the real
// global scope regardless of which function-local include chain runs it.
global $openings_db;

// $addon_info is only pre-set when this bootstrap is reached via the admin
// nav-tab router (acp/core/addons/edit-plugin.php). The admin-xhr reader/writer
// router and the frontend shortcode entry point do not set it, so it is
// loaded here directly to make bootstrap.php safe in all contexts.
if(!isset($addon_info)) {
    $addon_info = json_decode(file_get_contents($mod_root.'info.json'), true);
}

if(is_file($openings_db_file)) {
    $openings_db = new Medoo([
        'type' => 'sqlite',
        'database' => $openings_db_file
    ]);
} else {

    if(SE_SECTION === 'backend') {
        include __DIR__ . '/../install/installer.php';
    }

}

require_once __DIR__.'/functions.php';

if(isset($openings_db)) {
    $opn_settings = opn_get_settings();

    if(SE_SECTION === 'backend') {
        if (version_compare($opn_settings['version'] ?? '0', $addon_info['addon']['version'], '<')) {
            include __DIR__ . '/../install/updater.php';
        }
    }
}

// se_return_addon_translations() lives in acp/core/functions_addons.php,
// which is only autoloaded on admin pages - it's undefined on the frontend
// (shortcode), where $addon_lang isn't needed anyway.
if(!is_array($addon_lang) && function_exists('se_return_addon_translations')) {
    $addon_lang = se_return_addon_translations('openings');
}
