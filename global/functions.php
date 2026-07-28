<?php
use Medoo\Medoo;
//error_reporting(E_ALL);

/* -------------------------------------------------------------------
 * Settings (key/value store)
 * ---------------------------------------------------------------- */

function opn_get_settings() {
    global $openings_db;

    if (!isset($openings_db)) {
        return [];
    } else {
        $result = $openings_db->select("settings", ["key", "value"]);
        return array_column($result, 'value', 'key');
    }
}

function opn_save_setting(string $key, $value): bool {
    global $openings_db;

    $exists = $openings_db->has('settings', ['key' => $key]);

    if ($exists) {
        $result = $openings_db->update('settings', [
            'value' => (string)$value
        ], ['key' => $key]);
        return $result->rowCount() > 0;
    } else {
        return $openings_db->insert('settings', [
            'key' => $key,
            'value' => (string)$value
        ]);
    }
}

/**
 * Ensure table exists and matches current schema.
 */
function opn_updateOrCreateTable(string $table_name, array $expected_columns): void
{
    global $openings_db;

    $tables = $openings_db->query("
    SELECT name FROM sqlite_master
    WHERE type='table' AND name = '$table_name' ")->fetchAll();

    if (empty($tables)) {
        $col_definitions = [];
        foreach ($expected_columns as $col_name => $col_type) {
            $col_definitions[] = "$col_name $col_type";
        }
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (" . implode(', ', $col_definitions) . ")";
        $openings_db->query($sql);
        echo "Created table $table_name<br>";
        return;
    }

    $tableInfo = $openings_db->query("PRAGMA table_info($table_name)")->fetchAll();
    $existing_columns = array_column($tableInfo, 'name');

    foreach ($expected_columns as $col_name => $col_type) {
        if (!in_array($col_name, $existing_columns, true)) {
            $sql = "ALTER TABLE $table_name ADD COLUMN $col_name $col_type";
            $result = $openings_db->query($sql);
            if ($result !== false) {
                echo "Added column $col_name to $table_name<br>";
            }
        }
    }
}

/* -------------------------------------------------------------------
 * Weekday helpers. day_of_week follows PHP date('N'): 1 = Monday ... 7 = Sunday,
 * so "highlight today" only needs a plain int comparison against date('N').
 * ---------------------------------------------------------------- */

function opn_weekday_label(int $day_of_week): string {
    global $addon_lang;

    $keys = [1 => 'day_1', 2 => 'day_2', 3 => 'day_3', 4 => 'day_4', 5 => 'day_5', 6 => 'day_6', 7 => 'day_7'];
    $fallback = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

    return $addon_lang[$keys[$day_of_week] ?? ''] ?? ($fallback[$day_of_week] ?? '');
}

/**
 * A time range line as typed by the admin, one per line, e.g. "09:00-12:00".
 * Lines that don't match are silently dropped - this is an admin-only
 * backend field, not visitor-facing input, so a quiet filter is enough
 * (no error banner needed).
 */
function opn_parse_ranges(string $raw): array {
    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    $ranges = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)\s*-\s*([01]\d|2[0-3]):([0-5]\d)$/', $line, $m)) {
            $ranges[] = $m[1].':'.$m[2].'-'.$m[3].':'.$m[4];
        }
    }

    return $ranges;
}

/* -------------------------------------------------------------------
 * Frontend rendering - used by the shortcode entry point
 * (plugins/openings/index.php), one location per shortcode instance
 * (mirrors plugins/former's form_id-per-shortcode model).
 * ---------------------------------------------------------------- */

function opn_render_hours(int $location_id): string {
    global $openings_db, $addon_lang;

    $location = $openings_db->get('locations', '*', ['id' => $location_id]);
    if (!$location || (int) $location['status'] !== 1) {
        return '<div class="alert alert-warning">Öffnungszeiten nicht verfügbar.</div>';
    }

    $hours = $openings_db->select('hours', '*', [
        'location_id' => $location_id,
        'ORDER' => ['day_of_week' => 'ASC'],
    ]);
    $hours_by_day = array_column($hours, null, 'day_of_week');
    $today = (int) date('N');

    $rows_html = '';
    for ($day = 1; $day <= 7; $day++) {
        $row = $hours_by_day[$day] ?? null;
        $is_closed = $row ? (int) $row['is_closed'] === 1 : true;
        $ranges = $row ? (json_decode($row['ranges'] ?? '[]', true) ?: []) : [];

        $is_today = !empty($location['highlight_today']) && $day === $today;
        $row_class = 'opn-row'.($is_today ? ' opn-row-today' : '');

        if ($is_closed || !$ranges) {
            $times_html = '<span class="opn-closed">'.htmlspecialchars($addon_lang['label_closed_frontend'] ?? 'geschlossen').'</span>';
        } else {
            $times_html = implode('<br>', array_map(
                fn($r) => htmlspecialchars(str_replace('-', ' – ', $r)),
                $ranges
            ));
        }

        $rows_html .= '<tr class="'.$row_class.'">';
        $rows_html .= '<th scope="row">'.htmlspecialchars(opn_weekday_label($day)).'</th>';
        $rows_html .= '<td>'.$times_html.'</td>';
        $rows_html .= '</tr>';
    }

    $intro_html = trim((string) ($location['intro_text'] ?? '')) !== ''
        ? '<p class="opn-intro">'.nl2br(htmlspecialchars($location['intro_text'])).'</p>'
        : '';
    $note_html = trim((string) ($location['note_text'] ?? '')) !== ''
        ? '<p class="opn-note text-muted small">'.nl2br(htmlspecialchars($location['note_text'])).'</p>'
        : '';

    $tpl = file_get_contents(__DIR__.'/../templates/hours-table.tpl');
    $tpl = str_replace('{intro_html}', $intro_html, $tpl);
    $tpl = str_replace('{rows_html}', $rows_html, $tpl);
    $tpl = str_replace('{note_html}', $note_html, $tpl);

    return $tpl;
}
