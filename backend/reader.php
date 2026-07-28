<?php

require __DIR__.'/../global/bootstrap.php';

/* ---------------------------------------------------------------
 * Locations list
 * -------------------------------------------------------------- */
if (isset($_GET['show']) && $_GET['show'] === 'locations_list') {

    $locations = $openings_db->select('locations', '*', ['ORDER' => ['sort_order' => 'ASC', 'id' => 'ASC']]);

    echo '<div class="card p-3">';
    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<div></div>';
    echo '<button type="button" class="btn btn-primary btn-sm"
        hx-post="/admin-xhr/addons/plugin/openings/write/"
        hx-vals=\'{"create_location":"1","csrf_token":"'.$_SESSION['token'].'"}\'>'.$addon_lang['btn_new_location'].'</button>';
    echo '</div>';

    if (!$locations) {
        echo '<p class="text-muted">'.$addon_lang['msg_no_locations'].'</p>';
    } else {
        echo '<table class="table table-sm table-striped table-hover align-middle">';
        echo '<thead><tr>';
        echo '<th>'.$addon_lang['th_name'].'</th>';
        echo '<th>'.$addon_lang['th_shortcode'].'</th>';
        echo '<th class="text-end">'.$addon_lang['th_actions'].'</th>';
        echo '</tr></thead><tbody>';

        foreach ($locations as $location) {
            $shortcode = '[plugin=openings]location_id='.$location['id'].'[/plugin]';

            echo '<tr>';
            echo '<td>'.htmlspecialchars($location['name']).($location['status'] ? '' : ' <span class="badge text-bg-secondary">inaktiv</span>').'</td>';
            echo '<td><input type="text" class="form-control form-control-sm" readonly value="'.htmlspecialchars($shortcode).'" onclick="this.select()" style="width:300px"></td>';
            echo '<td class="text-end">';
            echo '<a class="btn btn-sm btn-default" href="/admin/addons/plugin/openings/location-editor/?location_id='.$location['id'].'">'.$addon_lang['btn_edit'].'</a> ';
            echo '<button type="button" class="btn btn-sm btn-default text-danger"
                hx-post="/admin-xhr/addons/plugin/openings/write/"
                hx-vals=\'{"delete_location":"'.$location['id'].'","csrf_token":"'.$_SESSION['token'].'"}\'
                hx-confirm="'.htmlspecialchars($addon_lang['msg_confirm_delete_location']).'"
                hx-target="closest tr" hx-swap="outerHTML swap:0s">'.$addon_lang['btn_delete'].'</button>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
    exit;
}

/* ---------------------------------------------------------------
 * Location settings sub-form (name, status, intro/note text, highlight)
 * -------------------------------------------------------------- */
if (isset($_GET['show']) && $_GET['show'] === 'location_settings') {

    $location_id = (int) ($_GET['location_id'] ?? 0);
    $location = $openings_db->get('locations', '*', ['id' => $location_id]);
    if (!$location) { exit; }

    echo '<div id="response"></div>';
    echo '<form hx-post="/admin-xhr/addons/plugin/openings/write/" hx-target="#response" hx-swap="innerHTML">';

    echo '<input type="hidden" name="save_location_settings" value="'.$location_id.'">';

    echo '<div class="mb-3"><label class="form-label">'.$addon_lang['label_location_name'].'</label>';
    echo '<input type="text" class="form-control" name="name" value="'.htmlspecialchars($location['name']).'"></div>';

    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="status" value="1" id="opn-status" '.($location['status'] ? 'checked' : '').'>';
    echo '<label class="form-check-label" for="opn-status">'.$addon_lang['label_status'].'</label></div>';

    echo '<hr>';

    echo '<div class="mb-3"><label class="form-label">'.$addon_lang['label_intro_text'].'</label>';
    echo '<textarea class="form-control" name="intro_text" rows="2">'.htmlspecialchars($location['intro_text'] ?? '').'</textarea></div>';

    echo '<div class="mb-3"><label class="form-label">'.$addon_lang['label_note_text'].'</label>';
    echo '<textarea class="form-control" name="note_text" rows="2">'.htmlspecialchars($location['note_text'] ?? '').'</textarea>';
    echo '<div class="form-text">'.$addon_lang['label_note_text_help'].'</div></div>';

    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="highlight_today" value="1" id="opn-highlight" '.($location['highlight_today'] ? 'checked' : '').'>';
    echo '<label class="form-check-label" for="opn-highlight">'.$addon_lang['label_highlight_today'].'</label></div>';

    echo '<input type="hidden" name="csrf_token" value="'.$_SESSION['token'].'">';
    echo '<button type="submit" class="btn btn-primary">'.$addon_lang['btn_save'].'</button>';
    echo '</form>';
    exit;
}

/* ---------------------------------------------------------------
 * Weekly hours editor for one location - one row per weekday
 * (1 = Monday ... 7 = Sunday), closed checkbox + a free-text "one range
 * per line" textarea. No per-row DB records to add/delete - all 7 days
 * always exist per location (seeded on location creation), so this is a
 * single save covering all of them.
 * -------------------------------------------------------------- */
if (isset($_GET['show']) && $_GET['show'] === 'hours_editor') {

    $location_id = (int) ($_GET['location_id'] ?? 0);
    if (!$openings_db->has('locations', ['id' => $location_id])) { exit; }

    $hours = $openings_db->select('hours', '*', ['location_id' => $location_id, 'ORDER' => ['day_of_week' => 'ASC']]);
    $hours_by_day = array_column($hours, null, 'day_of_week');

    echo '<div id="response"></div>';
    echo '<form hx-post="/admin-xhr/addons/plugin/openings/write/" hx-target="#response" hx-swap="innerHTML">';
    echo '<input type="hidden" name="save_hours" value="'.$location_id.'">';

    for ($day = 1; $day <= 7; $day++) {
        $row = $hours_by_day[$day] ?? null;
        $is_closed = $row ? (int) $row['is_closed'] === 1 : false;
        $ranges = $row ? (json_decode($row['ranges'] ?? '[]', true) ?: []) : [];

        echo '<div class="row g-2 align-items-start mb-3 pb-3 border-bottom">';
        echo '<div class="col-md-3 pt-1"><strong>'.htmlspecialchars(opn_weekday_label($day)).'</strong></div>';
        echo '<div class="col-md-3 form-check">';
        echo '<input type="checkbox" class="form-check-input" name="closed['.$day.']" value="1" id="opn-closed-'.$day.'" '.($is_closed ? 'checked' : '').'>';
        echo '<label class="form-check-label" for="opn-closed-'.$day.'">'.$addon_lang['label_closed'].'</label>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<textarea class="form-control form-control-sm" rows="2" name="ranges['.$day.']" placeholder="'.htmlspecialchars($addon_lang['label_ranges_placeholder']).'">'.htmlspecialchars(implode("\n", $ranges)).'</textarea>';
        echo '<div class="form-text">'.$addon_lang['label_ranges_help'].'</div>';
        echo '</div>';
        echo '</div>';
    }

    echo '<input type="hidden" name="csrf_token" value="'.$_SESSION['token'].'">';
    echo '<button type="submit" class="btn btn-primary">'.$addon_lang['btn_save'].'</button>';
    echo '</form>';
    exit;
}

exit;
