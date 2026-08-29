<?php
$query_array = array(
    "headline" => __("Android-Software-Inventar"),
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
    "sort" => "geraet",
    "dir" => "ASC",
    "fields" => array(
        "20" => array("name" => "produkt", "head" => __("App"), "show" => "y", "link" => "n"),
        "21" => array("name" => "package_name", "head" => __("Paketname"), "show" => "y", "link" => "n"),
        "22" => array("name" => "version", "head" => __("Version"), "show" => "y", "link" => "n"),
        "23" => array("name" => "geraet", "head" => __("Gerätebezeichnung"), "show" => "y", "link" => "n"),
        "24" => array("name" => "apk_size", "head" => __("APK-Größe (Bytes)"), "show" => "y", "link" => "n"),
        "25" => array("name" => "archived", "head" => __("Archiviert"), "show" => "n", "link" => "n"),
        "26" => array("name" => "enabled", "head" => __("Aktiv"), "show" => "y", "link" => "n"),
        "27" => array("name" => "exists_in_app_store", "head" => __("Im Store"), "show" => "y", "link" => "n"),
        "28" => array("name" => "first_installed", "head" => __("Erstinstallation"), "show" => "n", "link" => "n"),
        "29" => array("name" => "last_updated", "head" => __("Letztes Update"), "show" => "y", "link" => "n"),
        "30" => array("name" => "granted_permissions", "head" => __("Berechtigungen erteilt"), "show" => "n", "link" => "n"),
        "31" => array("name" => "requested_permissions", "head" => __("Berechtigungen angefordert"), "show" => "n", "link" => "n"),
        "32" => array("name" => "min_sdk", "head" => __("Min SDK"), "show" => "n", "link" => "n"),
        "33" => array("name" => "target_sdk", "head" => __("Target SDK"), "show" => "n", "link" => "n"),
        "34" => array("name" => "package_manager", "head" => __("Paketmanager"), "show" => "n", "link" => "n"),
        "35" => array("name" => "hlink", "head" => __("Store"), "show" => "y", "link" => "y", "sort" => "y"),
        "36" => array("name" => "datum", "head" => __("Importdatum"), "show" => "y", "link" => "n", "search" => "n"),
        "37" => array("name" => "id", "head" => __("ListID"), "show" => "n", "link" => "n", "search" => "n")
    )
);
?>
