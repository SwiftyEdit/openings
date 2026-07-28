<?php
require __DIR__.'/../global/bootstrap.php';

$location_id = (int) ($_GET['location_id'] ?? 0);
$location = $openings_db->get('locations', '*', ['id' => $location_id]);

if (!$location) {
    echo '<div class="alert alert-danger">'.$addon_lang['msg_location_not_found'].'</div>';
    return;
}

echo '<h1>'.$addon_lang['title_location_editor'].' – '.htmlspecialchars($location['name']).'</h1>';

echo '<div class="row">';

echo '<div class="col-md-5">';
echo '<div class="card">';
echo '<div class="card-header">'.$addon_lang['title_location_settings'].'</div>';
echo '<div class="card-body" hx-get="/admin-xhr/addons/plugin/openings/read/?show=location_settings&location_id='.$location_id.'" hx-trigger="load, update_openings_location_settings from:body">LOADING ...</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-7">';
echo '<div class="card">';
echo '<div class="card-header">'.$addon_lang['title_hours_editor'].'</div>';
echo '<div class="card-body" hx-get="/admin-xhr/addons/plugin/openings/read/?show=hours_editor&location_id='.$location_id.'" hx-trigger="load, update_openings_hours from:body">LOADING ...</div>';
echo '</div>';
echo '</div>';

echo '</div>';
