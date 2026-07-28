<?php
/**
 * example: [plugin=openings]location_id=1[/plugin]
 */
if (SE_SECTION !== 'backend') {

    require_once __DIR__.'/global/bootstrap.php';

    $opn_location_id = (int) ($location_id ?? 0);

    if ($opn_location_id > 0 && isset($openings_db)) {
        echo opn_render_hours($opn_location_id);
    }
}
