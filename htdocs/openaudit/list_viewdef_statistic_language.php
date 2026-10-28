<?php
// Statistik ueber die aktuell gespeicherten Systeme.
$query_array = array(
    "headline" => __("Betriebssystem-Sprachen"),
    "sql" => "SELECT system_language AS statistic_value, COUNT(*) AS count_item,
        ROUND(100.0 * COUNT(*) / NULLIF((SELECT COUNT(*) FROM system WHERE system_language IS NOT NULL AND TRIM(system_language) <> ''),0), $round_to_decimal_places) AS percentage
        FROM system WHERE system_language IS NOT NULL AND TRIM(system_language) <> ''
        GROUP BY system_language",
    "sort" => "count_item", "dir" => "DESC",
    "get" => array("file" => "list.php", "title" => __("Systeme anzeigen"),
        "var" => array("view" => "systems_for_language", "name" => "%statistic_value", "headline_addition" => "%statistic_value")),
    "fields" => array(
        "10" => array("name" => "statistic_value", "head" => __("Language"), "show" => "y", "link" => "y"),
        "20" => array("name" => "count_item", "head" => __("Count"), "show" => "y", "link" => "n", "search" => "n"),
        "30" => array("name" => "percentage", "head" => __("Percentage"), "show" => "y", "link" => "n", "search" => "n")
    )
);
?>
