<?php
require __DIR__.'/../global/bootstrap.php';

echo '<h1>'.$addon_lang['title_locations_list'].'</h1>';

echo '<div class="row">';
echo '<div class="col-md-12">';
echo '<div hx-get="/admin-xhr/addons/plugin/openings/read/?show=locations_list" hx-trigger="load, update_openings_locations from:body">';
echo 'LOADING ...';
echo '</div>';
echo '</div>';
echo '</div>';
