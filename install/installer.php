<?php
/**
 * @var string $openings_db_file from bootstrap.php
 * @var string $mod_root
 */
use Medoo\Medoo;
include __DIR__.'/schema.php';


/* INSTALL */

if(!is_file("$openings_db_file")) {

    if(!is_dir(dirname($openings_db_file))) {
        mkdir(dirname($openings_db_file), 0755, true);
    }

    echo '<p class="alert alert-info">We try to generate SQLite File: '.$openings_db_file.'</p>';

    $openings_db = new Medoo([
        'type' => 'sqlite',
        'database' => $openings_db_file
    ]);

    $tables = OpeningsSchema::getTables();

    // Tabellen erstellen
    foreach ($tables as $table_name => $columns) {
        $col_definitions = [];
        foreach ($columns as $col_name => $col_type) {
            $col_definitions[] = "$col_name $col_type";
        }

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (" .
            implode(', ', $col_definitions) . ")";

        $openings_db->query($sql)->execute();
    }

    // Initiale Daten einfügen (sicher mit Prepared Statements)
    $openings_db->insert('settings', [
        'key' => 'version',
        'value' => $addon_info['addon']['version']
    ]);

    echo '<p class="alert alert-info">Generated SQLite File: '.$openings_db_file.'</p>';

    // Seed one default location with a generic Mon-Fri schedule, so the
    // admin sees a sensible starting point instead of an empty list.
    $openings_db->insert('locations', [
        'name' => 'Standort 1',
        'status' => 1,
        'intro_text' => '',
        'note_text' => '',
        'highlight_today' => 1,
        'sort_order' => 0,
    ]);
    $default_location_id = $openings_db->id();

    for ($day = 1; $day <= 7; $day++) {
        $is_weekend = $day >= 6;
        $openings_db->insert('hours', [
            'location_id' => $default_location_id,
            'day_of_week' => $day,
            'is_closed' => $is_weekend ? 1 : 0,
            'ranges' => json_encode($is_weekend ? [] : ['09:00-18:00']),
        ]);
    }

}
