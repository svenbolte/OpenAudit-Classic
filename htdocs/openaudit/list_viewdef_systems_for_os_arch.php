<?php
$value = isset($_GET['name']) && is_scalar($_GET['name']) ? (string) $_GET['name'] : '';
$valueSql = mysqli_real_escape_string($db, $value);
$query_array = array(
    "headline" => __("Systeme nach Architecture"),
    "sql" => "SELECT * FROM system WHERE system_os_arch = '" . $valueSql . "'",
    "sort" => "system_name", "dir" => "ASC",
    "get" => array("file" => "system.php", "title" => __("Go to System"), "var" => array("pc" => "%system_uuid", "view" => "summary")),
    "fields" => array(
        "10" => array("name" => "system_uuid", "head" => __("UUID"), "show" => "n"),
        "20" => array("name" => "system_name", "head" => __("Hostname"), "show" => "y", "link" => "y"),
        "30" => array("name" => "net_ip_address", "head" => __("IP"), "show" => "y", "link" => "y"),
        "40" => array("name" => "system_os_name", "head" => __("OS"), "show" => "y"),
        "50" => array("name" => "system_os_arch", "head" => __("Architecture"), "show" => "y"),
        "60" => array("name" => "system_timestamp", "head" => __("Date Audited"), "show" => $show_date_audited)
    )
);
?>
