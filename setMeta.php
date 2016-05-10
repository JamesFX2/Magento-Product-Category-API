<?php

require_once 'api.php';

$rowcount = 1;
if (($handle = fopen("csv/product_meta.csv", "r")) !== FALSE) {
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if ($rowcount++ > 1) { /* Skip first row, headers */

            $result = $client->catalogProductUpdate($session, $row[0], array(
                'meta_title' => $row[1]
            ));

            echo "Set product ".$row[0]." meta title to ".$row[1]."\n";

        }
    }
    fclose($handle);
}
