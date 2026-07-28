<?php

require __DIR__.'/../global/bootstrap.php';

/* ---------------------------------------------------------------
 * Locations
 * -------------------------------------------------------------- */

if (isset($_POST['create_location'])) {
    $next_sort = (int) $openings_db->max('locations', 'sort_order') + 1;

    $openings_db->insert('locations', [
        'name' => 'Neuer Standort',
        'status' => 1,
        'highlight_today' => 1,
        'sort_order' => $next_sort,
    ]);
    $new_id = $openings_db->id();

    for ($day = 1; $day <= 7; $day++) {
        $is_weekend = $day >= 6;
        $openings_db->insert('hours', [
            'location_id' => $new_id,
            'day_of_week' => $day,
            'is_closed' => $is_weekend ? 1 : 0,
            'ranges' => json_encode($is_weekend ? [] : ['09:00-18:00']),
        ]);
    }

    header('HX-Redirect: /admin/addons/plugin/openings/location-editor/?location_id='.$new_id);
    exit;
}

if (isset($_POST['delete_location'])) {
    $location_id = (int) $_POST['delete_location'];

    $openings_db->delete('hours', ['location_id' => $location_id]);
    $openings_db->delete('locations', ['id' => $location_id]);

    header('HX-Trigger: update_openings_locations');
    exit;
}

if (isset($_POST['save_location_settings'])) {
    $location_id = (int) $_POST['save_location_settings'];

    $openings_db->update('locations', [
        'name' => sanitizeUserInputs($_POST['name'] ?? ''),
        'status' => isset($_POST['status']) ? 1 : 0,
        'intro_text' => sanitizeUserInputs($_POST['intro_text'] ?? ''),
        'note_text' => sanitizeUserInputs($_POST['note_text'] ?? ''),
        'highlight_today' => isset($_POST['highlight_today']) ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['id' => $location_id]);

    echo '<div class="alert alert-success">'.$addon_lang['msg_saved'].'</div>';
    header('HX-Trigger: update_openings_locations');
    exit;
}

/* ---------------------------------------------------------------
 * Weekly hours (per location)
 * -------------------------------------------------------------- */

if (isset($_POST['save_hours'])) {
    $location_id = (int) $_POST['save_hours'];
    if (!$openings_db->has('locations', ['id' => $location_id])) { exit; }

    $closed = $_POST['closed'] ?? [];
    $ranges_raw = $_POST['ranges'] ?? [];

    for ($day = 1; $day <= 7; $day++) {
        $is_closed = isset($closed[$day]) ? 1 : 0;
        $ranges = opn_parse_ranges((string) ($ranges_raw[$day] ?? ''));

        $exists = $openings_db->has('hours', ['location_id' => $location_id, 'day_of_week' => $day]);
        $data = [
            'is_closed' => $is_closed,
            'ranges' => json_encode($ranges),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            $openings_db->update('hours', $data, ['location_id' => $location_id, 'day_of_week' => $day]);
        } else {
            $openings_db->insert('hours', $data + ['location_id' => $location_id, 'day_of_week' => $day]);
        }
    }

    echo '<div class="alert alert-success">'.$addon_lang['msg_saved'].'</div>';
    exit;
}

exit;
