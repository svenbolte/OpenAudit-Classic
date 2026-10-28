<?php
// Systems with the selected manufacturer, based on the system table.
$manufacturer_name = isset($_GET['name']) && is_scalar($_GET['name']) ? (string) $_GET['name'] : '';
$manufacturer_name_sql = mysqli_real_escape_string($db, $manufacturer_name);
$query_array = array(
    'headline' => __('List Systems for Manufacturer'),
    'sql' => "SELECT * FROM system WHERE system_vendor = '" . $manufacturer_name_sql . "'",
    'sort' => 'system_name',
    'dir' => 'ASC',
    'get' => array('file' => 'system.php', 'title' => __('Go to System'), 'var' => array('pc' => '%system_uuid', 'view' => 'summary')),
    'fields' => array(
        '10' => array('name' => 'system_uuid', 'head' => __('UUID'), 'show' => 'n'),
        '20' => array('name' => 'net_ip_address', 'head' => __('IP'), 'show' => 'y', 'link' => 'y'),
        '30' => array('name' => 'system_name', 'head' => __('Hostname'), 'show' => 'y', 'link' => 'y'),
        '35' => array('name' => 'net_user_name', 'head' => __('Network User'), 'show' => 'y'),
        '40' => array('name' => 'system_vendor', 'head' => __('OS'), 'show' => 'y'),
        '50' => array('name' => 'system_os_arch', 'head' => __('Architecture'), 'show' => 'y'),
        '60' => array('name' => 'system_version', 'head' => __('Version'), 'show' => 'y'),
        '70' => array('name' => 'system_timestamp', 'head' => __('Date Audited'), 'show' => $show_date_audited),
        '80' => array('name' => 'system_system_type', 'head' => __('System Type'), 'show' => $show_type),
        '90' => array('name' => 'system_description', 'head' => __('Description'), 'show' => $show_description),
    )
);
?>
