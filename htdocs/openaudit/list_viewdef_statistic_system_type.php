<?php
// Statistik ueber die aktuell gespeicherten Systeme.
$query_array = array(
    "headline" => __("Systemtypen"),
    "sql" => "SELECT system_system_type AS statistic_value, COUNT(*) AS count_item,
        ROUND(100.0 * COUNT(*) / NULLIF((SELECT COUNT(*) FROM system WHERE system_system_type IS NOT NULL AND TRIM(system_system_type) <> ''),0), $round_to_decimal_places) AS percentage
        FROM system WHERE system_system_type IS NOT NULL AND TRIM(system_system_type) <> ''
        GROUP BY system_system_type",
    "sort" => "count_item", "dir" => "DESC",
    "get" => array("file" => "list.php", "title" => __("Systeme anzeigen"),
        "var" => array("view" => "systems_for_system_type", "name" => "%statistic_value", "headline_addition" => "%statistic_value")),
    "fields" => array(
        "10" => array("name" => "statistic_value", "head" => __("System Type"), "show" => "y", "link" => "y"),
        "20" => array("name" => "count_item", "head" => __("Count"), "show" => "y", "link" => "n", "search" => "n"),
        "30" => array("name" => "percentage", "head" => __("Percentage"), "show" => "y", "link" => "n", "search" => "n")
    )
);
?>
