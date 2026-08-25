<?php
$query_array = array(
    "headline" => __("List all Android Software"),
    "sql" => "SELECT
                id,
                geraet,
                produkt,
                package_name,
                version,
                apk_size,
                archived,
                enabled,
                exists_in_app_store,
                first_installed,
                granted_permissions,
                last_updated,
                min_sdk,
                package_manager,
                requested_permissions,
                hlink,
                target_sdk,
                datum
              FROM androidsoftware",
    "sort" => "produkt",
    "dir" => "ASC",
    "fields" => array(
        "20" => array("name" => "produkt", "head" => __("App Name"), "show" => "y", "link" => "n"),
        "21" => array("name" => "package_name", "head" => __("Package Name"), "show" => "y", "link" => "n"),
        "22" => array("name" => "version", "head" => __("Version"), "show" => "y", "link" => "n"),
        "23" => array("name" => "geraet", "head" => __("Gerätebezeichnung"), "show" => "y", "link" => "n"),
        "24" => array("name" => "apk_size", "head" => __("APK Size (Bytes)"), "show" => "y", "link" => "n"),
        "25" => array("name" => "archived", "head" => __("Archived"), "show" => "y", "link" => "n"),
        "26" => array("name" => "enabled", "head" => __("Enabled"), "show" => "y", "link" => "n"),
        "27" => array("name" => "exists_in_app_store", "head" => __("Exists in App Store"), "show" => "y", "link" => "n"),
        "28" => array("name" => "first_installed", "head" => __("First Installed"), "show" => "y", "link" => "n"),
        "29" => array("name" => "last_updated", "head" => __("Last Updated"), "show" => "y", "link" => "n"),
        "30" => array("name" => "granted_permissions", "head" => __("Granted Permissions"), "show" => "y", "link" => "n"),
        "31" => array("name" => "requested_permissions", "head" => __("Requested Permissions"), "show" => "y", "link" => "n"),
        "32" => array("name" => "min_sdk", "head" => __("Min SDK"), "show" => "y", "link" => "n"),
        "33" => array("name" => "target_sdk", "head" => __("Target SDK"), "show" => "y", "link" => "n"),
        "34" => array("name" => "package_manager", "head" => __("Package Manager"), "show" => "y", "link" => "n"),
        "35" => array("name" => "hlink", "head" => __("Store URL"), "show" => "y", "link" => "y", "sort" => "y"),
        "36" => array("name" => "datum", "head" => __("Import Date"), "show" => "y", "link" => "n", "search" => "n"),
        "37" => array("name" => "id", "head" => __("ListID"), "show" => "y", "link" => "n", "search" => "n")
    )
);
?>
