<?php
// Pure table schema definitions - edit here to add/modify columns

return [
    'locations' => [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'name' => 'VARCHAR(255) NOT NULL',
        'status' => 'TINYINT(1) DEFAULT 1',
        'intro_text' => 'TEXT NULL',
        'note_text' => 'TEXT NULL',
        'highlight_today' => 'TINYINT(1) DEFAULT 1',
        'sort_order' => 'INTEGER DEFAULT 0',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    'hours' => [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        // DEFAULT 0 (not just NOT NULL) so `ALTER TABLE ADD COLUMN` on an
        // existing pre-1.1 install (single shared schedule, no locations
        // table yet) succeeds - sqlite requires a constant default for
        // that. install/updater.php's migration step then moves any rows
        // still at 0 into a newly created default location.
        'location_id' => 'INTEGER NOT NULL DEFAULT 0',
        'day_of_week' => 'INTEGER NOT NULL', // 1 = Monday ... 7 = Sunday (PHP date('N'))
        'is_closed' => 'TINYINT(1) DEFAULT 0',
        'ranges' => 'TEXT NULL', // JSON array of "HH:MM-HH:MM" strings
        'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
    ],

    'settings' => [
        'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        'key' => 'TEXT NOT NULL UNIQUE',
        'value' => 'TEXT',
    ],
];
