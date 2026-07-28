<?php

include __DIR__.'/schema.php';

    // update
    echo '<p class="alert alert-info">We try to update version: '.$addon_info['addon']['version'].'</p>';

    $tables = OpeningsSchema::getTables();

    // 1. Update/Create all tables with current schema
    foreach ($tables as $table_name => $columns) {
        opn_updateOrCreateTable($table_name, $columns);
    }

    // 2. Migrate pre-1.1 installs (single shared weekly schedule) to the
    // multi-location model. Before 1.1 there was no `locations` table, and
    // `hours` had no `location_id` column; opn_updateOrCreateTable() above
    // just added it with its schema default (0). Any row still at 0 is a
    // leftover from that old single-schedule install, so fold it into one
    // newly created default location instead of discarding it - along with
    // the display settings that used to live in the global `settings` table
    // before they became per-location fields.
    $orphan_hours = $openings_db->count('hours', ['location_id' => 0]);
    if ($orphan_hours > 0) {
        // Pre-1.1's `hours` table was created with `day_of_week INTEGER NOT
        // NULL UNIQUE` (one row per weekday, no location scoping yet). That
        // UNIQUE constraint's autoindex survives the ADD COLUMN above and
        // SQLite has no ALTER TABLE / DROP INDEX to lift a UNIQUE or PRIMARY
        // KEY constraint directly ("index associated with UNIQUE or PRIMARY
        // KEY constraint cannot be dropped") - it can only be removed by
        // rebuilding the table. Left in place, creating a second location
        // would fail the instant its second weekday row (day_of_week
        // already used by location 1) was inserted. Detected via sql IS
        // NULL, which is true only for autoindexes (never set for an
        // explicit CREATE INDEX), so this never misfires on an index this
        // plugin might add deliberately later.
        $legacy_unique_index = $openings_db->query("
            SELECT name FROM sqlite_master
            WHERE type='index' AND tbl_name='hours' AND sql IS NULL
        ")->fetchAll();

        if ($legacy_unique_index) {
            $hours_columns = OpeningsSchema::getTableColumns('hours');
            $col_definitions = [];
            foreach ($hours_columns as $col_name => $col_type) {
                $col_definitions[] = "$col_name $col_type";
            }
            $openings_db->query('CREATE TABLE hours_rebuild ('.implode(', ', $col_definitions).')');
            $openings_db->query('INSERT INTO hours_rebuild (id, location_id, day_of_week, is_closed, ranges, updated_at)
                SELECT id, location_id, day_of_week, is_closed, ranges, updated_at FROM hours');
            $openings_db->query('DROP TABLE hours');
            $openings_db->query('ALTER TABLE hours_rebuild RENAME TO hours');
        }

        $legacy_settings = opn_get_settings();

        $openings_db->insert('locations', [
            'name' => 'Standort 1',
            'status' => 1,
            'intro_text' => $legacy_settings['intro_text'] ?? '',
            'note_text' => $legacy_settings['note_text'] ?? '',
            'highlight_today' => (int) ($legacy_settings['highlight_today'] ?? 1),
            'sort_order' => 0,
        ]);
        $default_location_id = $openings_db->id();

        $openings_db->update('hours', ['location_id' => $default_location_id], ['location_id' => 0]);

        $openings_db->delete('settings', ['key' => ['intro_text', 'note_text', 'highlight_today']]);
    }

    // 3. Update version
    $openings_db->update('settings', [
        'value' => $addon_info['addon']['version']
    ], [
        'key' => 'version'
    ]);

    echo '<p class="alert alert-info">Updated to version: '.$addon_info['addon']['version'].'</p>';
