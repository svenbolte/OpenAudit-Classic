<?php
/**********************************************************************************************************
Module:	include_config_defaults.php

Description:
	This module is included by "include_config.php".  Provides a default set of values for the system. These default values 
	are overridden by the values in "include_config.php".

**********************************************************************************************************/

// ****************  General Settings *******************************************
$accent_color = '#004477';
$language = 'de_de';
$mysqli_server = 'localhost';
$mysqli_user = 'root';
$mysqli_password = 'password';
$mysqli_database = 'openaudit';

// ****************  Security Settings *******************************************
$use_https = 'n';
$use_pass = 'n';
// An array of allowed users and their passwords (set use_pass = "n" if you do not wish to use passwords)
$users = array(
  'admin' => 'Open-AudIT'
);
$use_ldap_integration= 'n';
$ldap_base_dn = 'dc=mydomain,dc=local';
$ldap_server = 'domain.local';
$ldap_user = 'unknown@domain.local';
$ldap_secret = 'password';
$use_ldap_login = 'n';
$full_details = 'y';
$human_readable_ldap_fields = 'y';
$image_link_ldap_attribute = "name";

// ****************  Homepage Settings *******************************************
$show_other_discovered = 'y';
$other_detected = '7';
$show_system_discovered = 'y';
$system_detected = '7';
$show_systems_not_audited = 'y';
$days_systems_not_audited = '7';
$show_partition_usage = 'y';
$partition_free_space = '1000';
$show_software_detected = 'y';
$days_software_detected = '7';
$show_patches_not_detected = 'y';
$number_patches_not_detected = '5';
$show_detected_servers = 'y';
$show_detected_xp_av = 'y';
$show_detected_rdp = 'y';
$show_os = 'y';
$show_date_audited = 'y';
$show_type = 'y';
$show_description = 'y';
$show_domain = 'y';
$show_service_pack = 'n';
$count_system = '30';
$vnc_type = 'ultra';
$round_to_decimal_places = '2';
$management_domain_suffix = 'domain.local';
$show_systems_audited_graph = 'y';
$systems_audited_days='90';
$show_ldap_changes = 'y';
$ldap_changes_days = 7;
$show_hard_disk_alerts = 'y';
$hard_disk_alerts_days = '14';

// ****************  Settings that have no associated GUI *******************************************
$enable_remote_management = 'y';
$max_log_entries = 1000;
$utf8 = 'y';
$show_lenovo_warranty = 'y';
$show_tips = 'y';
$admin_list = Array('Domain Admins');
$user_list = Array('Domain Admins');
$user_ldap_attributes = Array("company","department","description","displayname",
	"mail","manager","msexchhomeservername","name","physicaldeliveryofficename","samaccountname","telephonenumber");
$computer_ldap_attributes = Array("description","name","operatingsystem","operatingsystemversion","operatingsystemservicepack");
$timezone = 'Europe/London';
$show_summary_barcode = FALSE ;
/* 
 [AJH] 12th Feb 2010 Included the GPLed liberation-fonts from RedHat, you can still pick a local font, but these are now the defaults
  These are included in './lib/fonts-ttf/' 
  Valid font names are.. 
    LiberationSerif-Bold.ttf
	LiberationSans-Regular.ttf
	LiberationMono-Bold.ttf
	LiberationSerif-BoldItalic.ttf
	LiberationSans-BoldItalic.ttf
	LiberationSans-Bold.ttf
	LiberationSans-Italic.ttf
	LiberationMono-Regular.ttf
	LiberationSerif-Italic.ttf
	LiberationMono-BoldItalic.ttf
	LiberationSerif-Regular.ttf
	LiberationMono-Italic.ttf
	*/
	
$summary_barcode_font = './lib/fonts-ttf/LiberationSerif-Bold.ttf';
//
$summary_barcode = "name";
?>
