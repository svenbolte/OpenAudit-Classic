DROP TABLE IF EXISTS `odbc`;
CREATE TABLE `odbc` (
  `odbc_id` int(10) unsigned NOT NULL auto_increment,
  `odbc_uuid` varchar(255) NOT NULL default '',
  `odbc_dsn` varchar(200) NOT NULL default '',
  `odbc_config` varchar(3000) NOT NULL default '',
  `odbc_timestamp` bigint(20) unsigned NOT NULL default '0',
  `odbc_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`odbc_id`),
  KEY `id` (`odbc_uuid`),
  KEY `id2` (`odbc_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `audit_commands`;
CREATE TABLE `audit_commands` (
  `audit_cmd_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_cmd_name` VARCHAR(100) NOT NULL default '',
  `audit_cmd_command` VARCHAR(255) NOT NULL default '',
  PRIMARY KEY(`audit_cmd_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `audit_configurations`;
CREATE TABLE `audit_configurations` (
  `audit_cfg_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_cfg_name` VARCHAR(50) NOT NULL default '',
  `audit_cfg_action` VARCHAR(25) NOT NULL default '',
  `audit_cfg_type` VARCHAR(25) NOT NULL default '',
  `audit_cfg_os` VARCHAR(25) NOT NULL default '',
  `audit_cfg_max_audits` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_wait_time` INT(10) UNSIGNED NOT NULL default '1200',
  `audit_cfg_ldap_conn` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_ldap_use_conn` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_audit_conn` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_audit_use_conn` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_audit_local` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_ldap_user` VARBINARY(255) NOT NULL default '',
  `audit_cfg_ldap_pass` VARBINARY(255) NOT NULL default '',
  `audit_cfg_ldap_server` VARCHAR(200) NOT NULL default '',
  `audit_cfg_ldap_path` VARCHAR(200) NOT NULL default '',
  `audit_cfg_ldap_page` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_filter` VARCHAR(200) NOT NULL default '',
  `audit_cfg_filter_case` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_filter_inverse` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_audit_user` VARCHAR(200) NOT NULL default '',
  `audit_cfg_audit_pass` VARCHAR(200) NOT NULL default '',
  `audit_cfg_ip_start` VARCHAR(15) NOT NULL default '',
  `audit_cfg_ip_end` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_pc_list` MEDIUMTEXT NOT NULL,
  `audit_cfg_win_vbs` VARCHAR(200) NOT NULL default '',
  `audit_cfg_com_path` VARCHAR(200) NOT NULL default '',
  `audit_cfg_lin_sft` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_lin_sft_lst` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_sft_lst` MEDIUMTEXT NOT NULL,
  `audit_cfg_lin_identity` VARCHAR(200) NOT NULL default '',
  `audit_cfg_lin_url` VARCHAR(200) NOT NULL default '',
  `audit_cfg_win_sft` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_win_url` VARCHAR(200) NOT NULL default '',
  `audit_cfg_win_uuid` VARCHAR(255) NOT NULL default '',
  `audit_cfg_nmap_int` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_nmap_srv` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_nmap_udp` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_nmap_tcp_syn` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_nmap_url` VARCHAR(200) NOT NULL default '',
  `audit_cfg_nmap_path` VARCHAR(200) NOT NULL default '',
  `audit_cfg_command_list` MEDIUMTEXT NOT NULL,
  `audit_cfg_command_interact` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_log_enable` INT(10) UNSIGNED NOT NULL default '0',
  `audit_cfg_mysqli_ids` VARCHAR(200) NOT NULL default '',
  `audit_cfg_cmd_list` VARCHAR(200) NOT NULL default '',
  PRIMARY KEY(`audit_cfg_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `audit_settings`;
CREATE TABLE `audit_settings` (
  `audit_settings_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_settings_active` INT(10) UNSIGNED NOT NULL default '0',
  `audit_settings_interval` INT(10) UNSIGNED NOT NULL default '3',
  `audit_settings_pid` INT(10) UNSIGNED NOT NULL default '0',
  `audit_settings_runas_service` INT(10) UNSIGNED NOT NULL default '0',
  `audit_settings_script_only` INT(10) UNSIGNED NOT NULL default '0',
  `audit_settings_base_url` VARCHAR(200) NOT NULL default '',
  `audit_settings_service_name` VARCHAR(200) NOT NULL default 'openaudit',
  PRIMARY KEY(`audit_settings_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `audit_log_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_log_message` VARCHAR(255) NOT NULL default '',
  `audit_log_host` VARCHAR(255) NOT NULL default '',
  `audit_log_schedule_id` VARCHAR(255) NOT NULL default '',
  `audit_log_config_id` VARCHAR(255) NOT NULL default '',
  `audit_log_time` INT(10) UNSIGNED NOT NULL default '0',
  `audit_log_timestamp` INT(10) UNSIGNED NOT NULL default '0',
  `audit_log_pid` INT(10) UNSIGNED NOT NULL default '0',
  PRIMARY KEY(`audit_log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `mysqli_queries`;
CREATE TABLE `mysqli_queries` (
  `mysqli_queries_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, 
  `mysqli_queries_cfg_id` INT(10) UNSIGNED NOT NULL default '0',
  `mysqli_queries_table` VARCHAR(50) NOT NULL default '',
  `mysqli_queries_field` VARCHAR(50) NOT NULL default '',
  `mysqli_queries_sort` VARCHAR(10) NOT NULL default '',
  `mysqli_queries_data` VARCHAR(255) NOT NULL default '',
  PRIMARY KEY(`mysqli_queries_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `audit_schedules`;
CREATE TABLE `audit_schedules` (
  `audit_schd_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_schd_name` VARCHAR(100) NOT NULL default '',
  `audit_schd_cfg_id` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_active` INT(10) UNSIGNED NOT NULL default '1',
  `audit_schd_updated` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_type` VARCHAR(25) NOT NULL default '',
  `audit_schd_strt_hr` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_strt_min` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_hr_frq_hr` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_hr_frq_min` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_hr_between` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_hr_strt_hr` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_hr_strt_min` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_hr_end_hr` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_hr_end_min` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_dly_frq` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_wk_days` VARCHAR(75) NOT NULL default '',
  `audit_schd_mth_day` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_mth_months` VARCHAR(100) NOT NULL default '',
  `audit_schd_last_run` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_next_run` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_log_disable` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_cron_line` VARCHAR(250) NOT NULL default '',
  `audit_schd_email_log` INT(10) UNSIGNED NOT NULL default '0',
  `audit_schd_email_list` VARCHAR(255) NOT NULL default '',
  `audit_schd_email_subject` VARCHAR(100) NOT NULL default '',
  `audit_schd_email_replyto` VARCHAR(100) NOT NULL default '',
  `audit_schd_email_template` VARCHAR(100) NOT NULL default '',
  `audit_schd_email_logo` VARCHAR(100) NOT NULL default '',
  PRIMARY KEY(`audit_schd_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `auth`;
CREATE TABLE `auth` (
  `auth_id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `auth_username` VARCHAR( 25 ) NOT NULL ,
  `auth_hash` VARCHAR( 49 ) NOT NULL ,
  `auth_realname` VARCHAR( 255 ) NOT NULL ,
  `auth_enabled` BOOL NOT NULL DEFAULT '1' ,
  `auth_admin` BOOL NOT NULL DEFAULT '0' ,
  UNIQUE (
  `auth_username`
  )
) ENGINE = MYISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `auto_updating`;
CREATE TABLE `auto_updating` (
  `au_id` int(10) unsigned NOT NULL auto_increment,
  `au_uuid` varchar(255) NOT NULL default '',
  `au_gpo_configured` varchar(10) NOT NULL default '',
  `au_enabled` varchar(10) NOT NULL default '',
  `au_behaviour` varchar(100) NOT NULL default '',
  `au_sched_install_day` varchar(20) NOT NULL default '',
  `au_sched_install_time` varchar(10) NOT NULL default '',
  `au_use_wuserver` varchar(10) NOT NULL default '',
  `au_wuserver` varchar(100) NOT NULL default '',
  `au_wustatusserver` varchar(100) NOT NULL default '',
  `au_target_group` varchar(100) NOT NULL default '',
  `au_elevate_nonadmins` varchar(10) NOT NULL default '',
  `au_auto_install` varchar(10) NOT NULL default '',
  `au_detection_frequency` varchar(10) NOT NULL default '',
  `au_reboot_timeout` varchar(10) NOT NULL default '',
  `au_noautoreboot` varchar(10) NOT NULL default '',
  `au_timestamp` bigint(20) unsigned NOT NULL default '0',
  `au_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`au_id`),
  KEY `id` (`au_uuid`),
  KEY `id2` (`au_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `battery`;
CREATE TABLE `battery` (
  `battery_id` int(10) unsigned NOT NULL auto_increment,
  `battery_uuid` varchar(255) NOT NULL default '',
  `battery_description` varchar(100) NOT NULL default '',
  `battery_device_id` varchar(100) NOT NULL default '',
  `battery_timestamp` bigint(20) unsigned NOT NULL default '0',
  `battery_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`battery_id`),
  KEY `id` (`battery_uuid`),
  KEY `id2` (`battery_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `bios`;
CREATE TABLE `bios` (
  `bios_id` int(10) unsigned NOT NULL auto_increment,
  `bios_uuid` varchar(255) NOT NULL default '',
  `bios_description` varchar(200) NOT NULL default '',
  `bios_manufacturer` varchar(200) NOT NULL default '',
  `bios_serial_number` varchar(100) NOT NULL default '',
  `bios_sm_bios_version` varchar(100) NOT NULL default '',
  `bios_version` varchar(100) NOT NULL default '',
  `bios_asset_tag` varchar(100) NOT NULL default '',
  `bios_timestamp` bigint(20) unsigned NOT NULL default '0',
  `bios_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`bios_id`),
  KEY `id` (`bios_uuid`),
  KEY `id2` (`bios_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `browser_helper_objects`;
CREATE TABLE `browser_helper_objects` (
  `bho_id` int(10) unsigned NOT NULL auto_increment,
  `bho_uuid` varchar(255) NOT NULL default '',
  `bho_code_base` varchar(250) NOT NULL default '',
  `bho_status` varchar(45) NOT NULL default '',
  `bho_program_file` varchar(100) NOT NULL default '',
  `bho_timestamp` bigint(20) unsigned NOT NULL default '0',
  `bho_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`bho_id`),
  KEY `id` (`bho_uuid`),
  KEY `id2` (`bho_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `config_name` varchar(45) NOT NULL default '',
  `config_value` varchar(45) NOT NULL default '',
  PRIMARY KEY  (`config_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `environment_variable`;
CREATE TABLE `environment_variable` (
  `env_var_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `env_var_uuid` varchar(255) NOT NULL DEFAULT '',
  `env_var_name` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `env_var_value` VARCHAR( 250 ) NOT NULL DEFAULT '',
  `env_var_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `env_var_first_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`env_var_id`),
  KEY `id` (`env_var_uuid`),
  KEY `id2` (`env_var_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `event_log`;
CREATE TABLE `event_log` (
  `evt_log_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `evt_log_uuid` varchar(255) NOT NULL DEFAULT '',
  `evt_log_name` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `evt_log_file_name` VARCHAR( 100 ) NOT NULL DEFAULT '',
  `evt_log_file_size` INT( 11 ) NOT NULL DEFAULT '0',
  `evt_log_max_file_size` INT( 11 ) NOT NULL DEFAULT '0',
  `evt_log_overwrite` VARCHAR( 30 ) NOT NULL DEFAULT '',
  `evt_log_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `evt_log_first_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`evt_log_id`),
  KEY `id` (`evt_log_uuid`),
  KEY `id2` (`evt_log_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `firewall_auth_app`;
CREATE TABLE `firewall_auth_app` (
  `firewall_app_id` int(10) unsigned NOT NULL auto_increment,
  `firewall_app_uuid` varchar(255) NOT NULL default '',
  `firewall_app_name` varchar(100) NOT NULL default '',
  `firewall_app_executable` varchar(200) NOT NULL default '',
  `firewall_app_remote_address` varchar(45) NOT NULL default '',
  `firewall_app_enabled` varchar(45) NOT NULL default '',
  `firewall_app_profile` varchar(45) NOT NULL default '',
  `firewall_app_timestamp` bigint(20) unsigned NOT NULL default '0',
  `firewall_app_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`firewall_app_id`),
  KEY `id` (`firewall_app_uuid`),
  KEY `id2` (`firewall_app_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `firewall_ports`;
CREATE TABLE `firewall_ports` (
  `port_id` int(10) unsigned NOT NULL auto_increment,
  `port_uuid` varchar(255) NOT NULL default '',
  `port_number` int(10) unsigned NOT NULL default '0',
  `port_protocol` varchar(45) NOT NULL default '',
  `port_scope` varchar(45) NOT NULL default '',
  `port_enabled` varchar(45) NOT NULL default '',
  `port_profile` varchar(45) NOT NULL default '',
  `port_timestamp` bigint(20) unsigned NOT NULL default '0',
  `port_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`port_id`),
  KEY `id` (`port_uuid`),
  KEY `id2` (`port_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `firewire`;
CREATE TABLE `firewire` (
  `fw_id` int(10) unsigned NOT NULL auto_increment,
  `fw_uuid` varchar(255) NOT NULL default '',
  `fx_description` varchar(200) NOT NULL default '',
  `fw_manufacturer` varchar(100) NOT NULL default '',
  `fw_caption` varchar(200) NOT NULL default '',
  `fw_timestamp` bigint(20) unsigned NOT NULL default '0',
  `fw_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`fw_id`),
  KEY `id` (`fw_uuid`),
  KEY `id2` (`fw_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `floppy`;
CREATE TABLE `floppy` (
  `floppy_id` int(10) unsigned NOT NULL auto_increment,
  `floppy_uuid` varchar(255) NOT NULL default '',
  `floppy_description` varchar(100) NOT NULL default '',
  `floppy_device_id` varchar(100) NOT NULL default '',
  `floppy_manufacturer` varchar(100) NOT NULL default '',
  `floppy_caption` varchar(100) NOT NULL default '',
  `floppy_timestamp` bigint(20) unsigned NOT NULL default '0',
  `floppy_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`floppy_id`),
  KEY `id` (`floppy_uuid`),
  KEY `id2` (`floppy_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `graphs_disk`;
CREATE TABLE `graphs_disk` (
  `disk_id` int(10) unsigned NOT NULL auto_increment,
  `disk_uuid` varchar(255) NOT NULL default '',
  `disk_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `disk_letter` varchar(4) NOT NULL default '',
  `disk_percent` varchar(3) NOT NULL default '',
  PRIMARY KEY  (`disk_id`),
  KEY `id` (`disk_uuid`),
  KEY `id2` (`disk_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `group_members`;
CREATE TABLE `group_members` (
  `group_id` int(10) unsigned NOT NULL auto_increment,
  `group_uuid` varchar(255) NOT NULL default '',
  `group_uuid_type` varchar(10) NOT NULL default '',
  `group_names_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`group_id`),
  KEY `id` (`group_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `group_names`;
CREATE TABLE `group_names` (
  `group_id` int(10) unsigned NOT NULL auto_increment,
  `group_name` varchar(60) NOT NULL default '',
  `group_desc` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `groups_id` int(10) unsigned NOT NULL auto_increment,
  `groups_uuid` varchar(255) NOT NULL default '',
  `groups_description` varchar(200) NOT NULL default '',
  `groups_name` varchar(100) NOT NULL default '',
  `groups_members` varchar(255) NOT NULL default '',
  `groups_sid` varchar(100) NOT NULL default '',
  `groups_timestamp` bigint(20) unsigned NOT NULL default '0',
  `groups_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`groups_id`),
  KEY `id` (`groups_uuid`),
  KEY `id2` (`groups_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `groups_details`;
CREATE TABLE `groups_details` (
  `gd_name` varchar(100) NOT NULL default '',
  `gd_description` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`gd_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `hard_drive`;
CREATE TABLE `hard_drive` (
  `hard_drive_id` int(10) unsigned NOT NULL auto_increment,
  `hard_drive_uuid` varchar(255) NOT NULL default '',
  `hard_drive_caption` varchar(100) NOT NULL default '',
  `hard_drive_index` int(11) unsigned NOT NULL default '0',
  `hard_drive_interface_type` varchar(10) NOT NULL default '',
  `hard_drive_manufacturer` varchar(100) NOT NULL default '',
  `hard_drive_model` varchar(100) NOT NULL default '',
  `hard_drive_partitions` int(11) unsigned NOT NULL default '0',
  `hard_drive_scsi_bus` varchar(10) NOT NULL default '',
  `hard_drive_scsi_logical_unit` varchar(100) NOT NULL default '',
  `hard_drive_scsi_port` varchar(10) NOT NULL default '',
  `hard_drive_size` int(11) unsigned NOT NULL default '0',
  `hard_drive_pnpid` varchar(200) NOT NULL default '',
  `hard_drive_status` VARCHAR(10) NOT NULL DEFAULT '',
  `hard_drive_predicted_failure` VARCHAR(10) NOT NULL DEFAULT '',
  `hard_drive_timestamp` bigint(20) unsigned NOT NULL default '0',
  `hard_drive_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`hard_drive_id`),
  KEY `id` (`hard_drive_uuid`),
  KEY `id2` (`hard_drive_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `iis`;
CREATE TABLE `iis` (
  `iis_id` int(10) unsigned NOT NULL auto_increment,
  `iis_uuid` varchar(255) NOT NULL default '',
  `iis_site` int(10) unsigned NOT NULL default '0',
  `iis_description` varchar(100) NOT NULL default '',
  `iis_logging_enabled` varchar(100) NOT NULL default '',
  `iis_logging_dir` varchar(100) NOT NULL default '',
  `iis_logging_format` varchar(100) NOT NULL default '',
  `iis_logging_time_period` varchar(100) NOT NULL default '',
  `iis_home_directory` varchar(100) NOT NULL default '',
  `iis_directory_browsing` varchar(100) NOT NULL default '',
  `iis_default_documents` varchar(100) NOT NULL default '',
  `iis_secure_ip` varchar(100) NOT NULL default '',
  `iis_secure_port` varchar(100) NOT NULL default '',
  `iis_site_state` varchar(20) NOT NULL default '',
  `iis_site_app_pool` varchar(100) NOT NULL default '',
  `iis_site_anonymous_user` varchar(100) NOT NULL default '',
  `iis_site_anonymous_auth` varchar(10) NOT NULL default '',
  `iis_site_basic_auth` varchar(10) NOT NULL default '',
  `iis_site_ntlm_auth` varchar(10) NOT NULL default '',
  `iis_site_ssl_en` varchar(10) NOT NULL default '',
  `iis_site_ssl128_en` varchar(10) NOT NULL default '',
  `iis_timestamp` bigint(20) unsigned NOT NULL default '0',
  `iis_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`iis_id`),
  KEY `id` (`iis_uuid`),
  KEY `id2` (`iis_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `iis_ip`;
CREATE TABLE `iis_ip` (
  `iis_ip_id` int(10) unsigned NOT NULL auto_increment,
  `iis_ip_uuid` varchar(255) NOT NULL default '',
  `iis_ip_site` varchar(100) NOT NULL default '',
  `iis_ip_ip_address` varchar(100) NOT NULL default '',
  `iis_ip_port` varchar(100) NOT NULL default '',
  `iis_ip_host_header` varchar(100) NOT NULL default '',
  `iis_ip_timestamp` bigint(20) unsigned NOT NULL default '0',
  `iis_ip_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`iis_ip_id`),
  KEY `id` (`iis_ip_uuid`),
  KEY `id2` (`iis_ip_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `iis_vd`;
CREATE TABLE `iis_vd` (
  `iis_vd_id` int(10) unsigned NOT NULL auto_increment,
  `iis_vd_uuid` varchar(255) NOT NULL default '',
  `iis_vd_site` varchar(100) NOT NULL default '',
  `iis_vd_name` varchar(100) NOT NULL default '',
  `iis_vd_path` varchar(100) NOT NULL default '',
  `iis_vd_timestamp` bigint(20) unsigned NOT NULL default '0',
  `iis_vd_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`iis_vd_id`),
  KEY `id` (`iis_vd_uuid`),
  KEY `id2` (`iis_vd_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `iis_web_ext`;
CREATE TABLE `iis_web_ext` (
  `iis_web_ext_id` int(10) unsigned NOT NULL auto_increment,
  `iis_web_ext_uuid` varchar(255) NOT NULL default '',
  `iis_web_ext_path` varchar(100) NOT NULL default '',
  `iis_web_ext_desc` varchar(100) NOT NULL default '',
  `iis_web_ext_access` varchar(20) NOT NULL default '',
  `iis_web_ext_timestamp` bigint(20) unsigned NOT NULL default '0',
  `iis_web_ext_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`iis_web_ext_id`),
  KEY `id` (`iis_web_ext_uuid`),
  KEY `id2` (`iis_web_ext_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `invoice`;
CREATE TABLE `invoice` (
  `invoice_id` int(10) unsigned NOT NULL auto_increment,
  `invoice_uuid` varchar(255) NOT NULL default '',
  `invoice_filename` varchar(100) NOT NULL default '',
  `invoice_image` blob,
  PRIMARY KEY  (`invoice_id`),
  KEY `id` (`invoice_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ip_route`;
CREATE TABLE `ip_route` (
  `ip_route_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `ip_route_uuid` varchar(255) NOT NULL DEFAULT '',
  `ip_route_destination` VARCHAR( 20 ) NOT NULL DEFAULT '',
  `ip_route_mask` VARCHAR( 20 ) NOT NULL DEFAULT '',
  `ip_route_metric` VARCHAR( 10 ) NOT NULL DEFAULT '',
  `ip_route_next_hop` VARCHAR( 20 ) NOT NULL DEFAULT '',
  `ip_route_protocol` VARCHAR( 10 ) NOT NULL DEFAULT '',
  `ip_route_type` VARCHAR( 10 ) NOT NULL DEFAULT '',
  `ip_route_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `ip_route_first_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`ip_route_id`),
  KEY `id` (`ip_route_uuid`),
  KEY `id2` (`ip_route_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `keyboard`;
CREATE TABLE `keyboard` (
  `keyboard_id` int(10) unsigned NOT NULL auto_increment,
  `keyboard_uuid` varchar(255) NOT NULL default '',
  `keyboard_description` varchar(100) NOT NULL default '',
  `keyboard_caption` varchar(100) NOT NULL default '',
  `keyboard_connection` varchar(45) NOT NULL default '',
  `keyboard_timestamp` bigint(20) unsigned NOT NULL default '0',
  `keyboard_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  `keyboard_device_id` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`keyboard_id`),
  KEY `id` (`keyboard_uuid`),
  KEY `id2` (`keyboard_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ldap_computers`;
CREATE TABLE  `ldap_computers` (
  `ldap_computers_guid` varchar(45) NOT NULL,
  `ldap_computers_cn` varchar(255) NOT NULL,
  `ldap_computers_timestamp` varchar(45) NOT NULL,
  `ldap_computers_first_timestamp` varchar(45) NOT NULL,
  `ldap_computers_path_id` varchar(45) NOT NULL,
  `ldap_computers_description` varchar(255) default NULL,
  `ldap_computers_os` varchar(255) default NULL,
  `ldap_computers_service_pack` varchar(255) default NULL,
  `ldap_computers_dn` varchar(255) NOT NULL,

  `ldap_computers_distinguishedname` varchar(255) NOT NULL,
  `ldap_computers_instancetype` varchar(255) NOT NULL,
  `ldap_computers_whencreated` varchar(255) NOT NULL,
  `ldap_computers_whenchanged` varchar(255) NOT NULL,
  `ldap_computers_displayname` varchar(255) NOT NULL,
  `ldap_computers_usncreated` varchar(255) NOT NULL,
  `ldap_computers_usnchanged` varchar(255) NOT NULL,
  `ldap_computers_name` varchar(255) NOT NULL,
  `ldap_computers_objectguid` varchar(255) NOT NULL,
  `ldap_computers_useraccountcontrol` varchar(255) NOT NULL,
  `ldap_computers_badpwdcount` varchar(255) NOT NULL,
  `ldap_computers_codepage` varchar(255) NOT NULL,
  `ldap_computers_countrycode` varchar(255) NOT NULL,
  `ldap_computers_badpasswordtime` varchar(255) NOT NULL,
  `ldap_computers_lastlogoff` varchar(255) NOT NULL,
  `ldap_computers_lastlogon` varchar(255) NOT NULL,
  `ldap_computers_localpolicyflags` varchar(255) NOT NULL,
  `ldap_computers_pwdlastset` varchar(255) NOT NULL,
  `ldap_computers_primarygroupid` varchar(255) NOT NULL,
  `ldap_computers_objectsid` varchar(255) NOT NULL,
  `ldap_computers_accountexpires` varchar(255) NOT NULL,
  `ldap_computers_logoncount` varchar(255) NOT NULL,
  `ldap_computers_samaccountname` varchar(255) NOT NULL,
  `ldap_computers_samaccounttype` varchar(255) NOT NULL,
  `ldap_computers_operatingsystem` varchar(255) NOT NULL,
  `ldap_computers_operatingsystemversion` varchar(255) NOT NULL,
  `ldap_computers_operatingsystemservicepack` varchar(255) NOT NULL,
  `ldap_computers_dnshostname` varchar(255) NOT NULL,
  `ldap_computers_serviceprincipalname` varchar(255) NOT NULL,
  `ldap_computers_objectcategory` varchar(255) NOT NULL,
  `ldap_computers_iscriticalsystemobject` varchar(255) NOT NULL,
  `ldap_computers_lastlogontimestamp` varchar(255) NOT NULL,

  PRIMARY KEY  (`ldap_computers_guid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ldap_connections`;
CREATE TABLE  `ldap_connections` (
  `ldap_connections_id` int(10) unsigned NOT NULL auto_increment,
  `ldap_connections_nc` varchar(255) NOT NULL,
  `ldap_connections_fqdn` varchar(255) NOT NULL,
  `ldap_connections_server` varchar(255) NOT NULL,
  `ldap_connections_user` varbinary(255) NOT NULL,
  `ldap_connections_password` varbinary(255) NOT NULL,
  `ldap_connections_name` varchar(45) NOT NULL,
  `ldap_connections_schema` varchar(45) NOT NULL,
  `ldap_connections_use_ssl` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`ldap_connections_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ldap_paths`;
CREATE TABLE  `ldap_paths` (
  `ldap_paths_id` int(10) unsigned NOT NULL auto_increment,
  `ldap_paths_dn` varchar(255) default NULL,
  `ldap_paths_connection_id` varchar(45) default NULL,
  `ldap_paths_timestamp` varchar(45) default NULL,
  `ldap_paths_audit` tinyint(1) default NULL,
  PRIMARY KEY  (`ldap_paths_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ldap_users`;
CREATE TABLE  `ldap_users` (
  `ldap_users_guid` varchar(45) NOT NULL,
  `ldap_users_cn` varchar(255) NOT NULL,
  `ldap_users_timestamp` varchar(45) NOT NULL,
  `ldap_users_first_timestamp` varchar(45) NOT NULL,
  `ldap_users_path_id` varchar(45) NOT NULL,
  `ldap_users_description` varchar(255) default NULL,
  `ldap_users_department` varchar(255) default NULL,
  `ldap_users_dn` varchar(255) NOT NULL,

  `ldap_users_sn` varchar(255) NOT NULL,
  `ldap_users_c` varchar(255) NOT NULL,
  `ldap_users_l` varchar(255) NOT NULL,
  `ldap_users_st` varchar(255) NOT NULL,
  `ldap_users_title` varchar(255) NOT NULL,
  `ldap_users_postalcode` varchar(255) NOT NULL,
  `ldap_users_physicaldeliveryofficename` varchar(255) NOT NULL,
  `ldap_users_telephonenumber` varchar(255) NOT NULL,
  `ldap_users_givenname` varchar(255) NOT NULL,
  `ldap_users_distinguishedname` varchar(255) NOT NULL,
  `ldap_users_instancetype` varchar(255) NOT NULL,
  `ldap_users_whencreated` varchar(255) NOT NULL,
  `ldap_users_whenchanged` varchar(255) NOT NULL,
  `ldap_users_displayname` varchar(255) NOT NULL,
  `ldap_users_usncreated` varchar(255) NOT NULL,
  `ldap_users_usnchanged` varchar(255) NOT NULL,
  `ldap_users_co` varchar(255) NOT NULL,
  `ldap_users_company` varchar(255) NOT NULL,
  `ldap_users_streetaddress` varchar(255) NOT NULL,
  `ldap_users_name` varchar(255) NOT NULL,
  `ldap_users_objectguid` varchar(255) NOT NULL,
  `ldap_users_useraccountcontrol` varchar(255) NOT NULL,
  `ldap_users_badpwdcount` varchar(255) NOT NULL,
  `ldap_users_codepage` varchar(255) NOT NULL,
  `ldap_users_countrycode` varchar(255) NOT NULL,
  `ldap_users_badpasswordtime` varchar(255) NOT NULL,
  `ldap_users_lastlogoff` varchar(255) NOT NULL,
  `ldap_users_lastlogon` varchar(255) NOT NULL,
  `ldap_users_scriptpath` varchar(255) NOT NULL,
  `ldap_users_pwdlastset` varchar(255) NOT NULL,
  `ldap_users_primarygroupid` varchar(255) NOT NULL,
  `ldap_users_objectsid` varchar(255) NOT NULL,
  `ldap_users_accountexpires` varchar(255) NOT NULL,
  `ldap_users_logoncount` varchar(255) NOT NULL,
  `ldap_users_samaccountname` varchar(255) NOT NULL,
  `ldap_users_samaccounttype` varchar(255) NOT NULL,
  `ldap_users_userprincipalname` varchar(255) NOT NULL,
  `ldap_users_lockouttime` varchar(255) NOT NULL,
  `ldap_users_objectcategory` varchar(255) NOT NULL,
  `ldap_users_dscorepropagationdata` varchar(255) NOT NULL,
  `ldap_users_lastlogontimestamp` varchar(255) NOT NULL,
  `ldap_users_mail` varchar(255) NOT NULL,
  `ldap_users_manager` varchar(255) NOT NULL,
  
  PRIMARY KEY  (`ldap_users_guid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `log_id` int(10) unsigned NOT NULL auto_increment,
  `log_timestamp` varchar(45) NOT NULL,
  `log_message` varchar(1024) NOT NULL,
  `log_severity` int(10) unsigned NOT NULL,
  `log_module` varchar(128) NOT NULL,
  `log_function` varchar(128) NOT NULL,
  PRIMARY KEY  (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `manual_software`;
CREATE TABLE `manual_software` (
  `man_soft_id` int(10) unsigned NOT NULL auto_increment,
  `man_soft_det_id` int(10) unsigned NOT NULL default '0',
  `man_soft_version` varchar(45) NOT NULL default '',
  `man_soft_uuid` varchar(255) NOT NULL default '',
  `man_soft_filesize` int(10) unsigned NOT NULL default '0',
  `man_soft_date_detected` date NOT NULL default '0000-00-00',
  `man_soft_date_first_detected` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`man_soft_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `manual_software_detection`;
CREATE TABLE `manual_software_detection` (
  `man_soft_det_id` int(10) unsigned NOT NULL auto_increment,
  `man_soft_det_dir` varchar(45) NOT NULL default '',
  `man_soft_det_file` varchar(45) NOT NULL default '',
  `man_soft_det_name` varchar(45) NOT NULL default '',
  `man_soft_det_comments` varchar(45) NOT NULL default '',
  PRIMARY KEY  (`man_soft_det_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `mapped`;
CREATE TABLE `mapped` (
  `mapped_id` int(10) unsigned NOT NULL auto_increment,
  `mapped_uuid` varchar(255) NOT NULL default '',
  `mapped_device_id` varchar(100) NOT NULL default '',
  `mapped_file_system` varchar(100) NOT NULL default '',
  `mapped_provider_name` varchar(100) NOT NULL default '',
  `mapped_free_space` int(10) unsigned NOT NULL default '0',
  `mapped_size` int(11) unsigned NOT NULL default '0',
  `mapped_username` varchar(100) NOT NULL default '',
  `mapped_connect_as` varchar(100) NOT NULL default '',
  `mapped_timestamp` bigint(20) unsigned NOT NULL default '0',
  `mapped_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`mapped_id`),
  KEY `id` (`mapped_uuid`),
  KEY `id2` (`mapped_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `media_id` int(10) unsigned NOT NULL auto_increment,
  `media_uuid` varchar(255) NOT NULL default '',
  `media_type` varchar(45) NOT NULL default '',
  `media_file` varchar(250) NOT NULL default '',
  `media_size` int(10) unsigned NOT NULL default '0',
  `media_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`media_id`),
  KEY `id` (`media_uuid`),
  KEY `id2` (`media_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `memory`;
CREATE TABLE `memory` (
  `memory_id` int(10) unsigned NOT NULL auto_increment,
  `memory_uuid` varchar(255) NOT NULL default '',
  `memory_bank` varchar(45) NOT NULL default '',
  `memory_type` varchar(45) NOT NULL default '',
  `memory_form_factor` varchar(45) NOT NULL default '',
  `memory_detail` varchar(45) NOT NULL default '',
  `memory_capacity` int(11) NOT NULL,
  `memory_speed` varchar(45) NOT NULL default '',
  `memory_tag` varchar(255) NOT NULL default '',
  `memory_timestamp` bigint(20) unsigned NOT NULL default '0',
  `memory_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`memory_id`),
  KEY `id` (`memory_uuid`),
  KEY `id2` (`memory_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `modem`;
CREATE TABLE `modem` (
  `modem_id` int(10) unsigned NOT NULL auto_increment,
  `modem_uuid` varchar(255) NOT NULL default '',
  `modem_attached_to` varchar(100) NOT NULL default '',
  `modem_country_selected` varchar(100) NOT NULL default '',
  `modem_description` varchar(100) NOT NULL default '',
  `modem_device_id` varchar(100) NOT NULL default '',
  `modem_device_type` varchar(100) NOT NULL default '',
  `modem_timestamp` bigint(20) unsigned NOT NULL default '0',
  `modem_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`modem_id`),
  KEY `id` (`modem_uuid`),
  KEY `id2` (`modem_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `monitor`;
CREATE TABLE `monitor` (
  `monitor_id` int(10) unsigned NOT NULL auto_increment,
  `monitor_uuid` varchar(255) NOT NULL default '',
  `monitor_manufacturer` varchar(45) NOT NULL default '',
  `monitor_deviceid` varchar(45) NOT NULL default '',
  `monitor_manufacture_date` varchar(45) NOT NULL default '',
  `monitor_model` varchar(45) NOT NULL default '',
  `monitor_serial` varchar(45) NOT NULL default '',
  `monitor_edid` varchar(45) NOT NULL default '',
  `monitor_description` varchar(100) NOT NULL default '',
  `monitor_value` varchar(45) NOT NULL default '',
  `monitor_purchase_order_number` varchar(45) NOT NULL default '',
  `monitor_date_purchased` date NOT NULL default '0000-00-00',
  `monitor_timestamp` bigint(20) unsigned NOT NULL default '0',
  `monitor_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`monitor_id`),
  KEY `id` (`monitor_uuid`),
  KEY `id2` (`monitor_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `motherboard`;
CREATE TABLE `motherboard` (
  `motherboard_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `motherboard_uuid` varchar(255) NOT NULL DEFAULT '',
  `motherboard_manufacturer` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `motherboard_product` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `motherboard_cpu_sockets` INT( 10 ) NOT NULL DEFAULT '0',
  `motherboard_memory_slots` INT( 10 ) NOT NULL DEFAULT '0',
  `motherboard_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `motherboard_first_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`motherboard_id`),
  KEY `id` (`motherboard_uuid`),
  KEY `id2` (`motherboard_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `mouse`;
CREATE TABLE `mouse` (
  `mouse_id` int(10) unsigned NOT NULL auto_increment,
  `mouse_uuid` varchar(255) NOT NULL default '',
  `mouse_description` varchar(100) NOT NULL default '',
  `mouse_number_of_buttons` varchar(45) NOT NULL default '',
  `mouse_device_id` varchar(100) NOT NULL default '',
  `mouse_type` varchar(45) NOT NULL default '',
  `mouse_port` varchar(45) NOT NULL default '',
  `mouse_timestamp` bigint(20) unsigned NOT NULL default '0',
  `mouse_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`mouse_id`),
  KEY `id` (`mouse_uuid`),
  KEY `id2` (`mouse_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ms_keys`;
CREATE TABLE `ms_keys` (
  `ms_keys_id` int(10) unsigned NOT NULL auto_increment,
  `ms_keys_uuid` varchar(255) NOT NULL default '',
  `ms_keys_name` varchar(80) NOT NULL default '',
  `ms_keys_cd_key` varchar(45) NOT NULL default '',
  `ms_keys_release` varchar(45) NOT NULL default '',
  `ms_keys_edition` varchar(45) NOT NULL default '',
  `ms_keys_key_type` varchar(45) NOT NULL default '',
  `ms_keys_timestamp` bigint(20) unsigned NOT NULL default '0',
  `ms_keys_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ms_keys_id`),
  KEY `id` (`ms_keys_uuid`),
  KEY `id2` (`ms_keys_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `network_card`;
CREATE TABLE `network_card` (
      `net_id` int(10) unsigned NOT NULL auto_increment,
      `net_mac_address` varchar(17) NOT NULL default '',
      `net_uuid` varchar(255) NOT NULL default '',
      `net_ip_enabled` varchar(10) NOT NULL default '',
      `net_index` varchar(10) NOT NULL default '',
      `net_service_name` varchar(30) NOT NULL default '',
      `net_description` varchar(255) NOT NULL default '',
      `net_dhcp_enabled` varchar(100) NOT NULL default '',
      `net_dhcp_server` varchar(30) NOT NULL default '',
      `net_dhcp_lease_obtained` varchar(14) NOT NULL default '',
      `net_dhcp_lease_expires` varchar(14) NOT NULL default '',
      `net_dns_host_name` varchar(100) NOT NULL default '',
      `net_dns_server` varchar(30) NOT NULL default '',
      `net_dns_server_2` varchar(30) NOT NULL default '',
      `net_dns_server_3` varchar(30) NOT NULL default '',
      `net_dns_domain` varchar(100) NOT NULL default '',
      `net_dns_domain_suffix` varchar(100) NOT NULL default '',
      `net_dns_domain_suffix_2` varchar(100) NOT NULL default '',
      `net_dns_domain_suffix_3` varchar(100) NOT NULL default '',
      `net_dns_domain_reg_enabled` varchar(10) NOT NULL default '',
      `net_dns_domain_full_reg_enabled` varchar(10) NOT NULL default '',
      `net_ip_address` varchar(30) NOT NULL default '',
      `net_ip_subnet` varchar(30) NOT NULL default '',
      `net_ip_address_2` varchar(30) NOT NULL default '',
      `net_ip_subnet_2` varchar(30) NOT NULL default '',
      `net_ip_address_3` varchar(30) NOT NULL default '',
      `net_ip_subnet_3` varchar(30) NOT NULL default '',
      `net_wins_primary` varchar(30) NOT NULL default '',
      `net_wins_secondary` varchar(30) NOT NULL default '',
      `net_wins_lmhosts_enabled` varchar(10) NOT NULL default '',
      `net_netbios_options` varchar(10) NOT NULL default '',
      `net_adapter_type` varchar(100) NOT NULL default '',
      `net_manufacturer` varchar(100) NOT NULL default '',
      `net_connection_id` varchar(255) NOT NULL default '',
      `net_connection_status` varchar(30) NOT NULL default '',
      `net_speed` varchar(10) NOT NULL default '',
      `net_gateway` varchar(100) NOT NULL default '',
      `net_gateway_metric` varchar(10) NOT NULL default '',
      `net_gateway_2` varchar(100) NOT NULL default '',
      `net_gateway_metric_2` varchar(10) NOT NULL default '',
      `net_gateway_3` varchar(100) NOT NULL default '',
      `net_gateway_metric_3` varchar(10) NOT NULL default '',
      `net_ip_metric` varchar(10) NOT NULL default '',
      `net_driver_provider` varchar(100) NOT NULL default '',
      `net_driver_version` varchar(20) NOT NULL default '',
      `net_driver_date` varchar(10) NOT NULL default '',
      `net_timestamp` bigint(20) unsigned NOT NULL default '0',
      `net_first_timestamp` bigint(20) unsigned NOT NULL default '0',
      PRIMARY KEY  (`net_id`),
      KEY `id` (`net_mac_address`),
      KEY `id2` (`net_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `nmap_ports`;
CREATE TABLE `nmap_ports` (
  `nmap_id` int(10) unsigned NOT NULL auto_increment,
  `nmap_port_number` int(10) unsigned NOT NULL default '0',
  `nmap_port_proto` varchar(10) NOT NULL default '',
  `nmap_other_id` varchar(100) NOT NULL default '',
  `nmap_port_name` varchar(45) NOT NULL default '',
  `nmap_port_version` varchar(100) NOT NULL default '',
  `nmap_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`nmap_id`),
  KEY `id` (`nmap_other_id`),
  KEY `id2` (`nmap_port_number`),
  KEY `id3` (`nmap_port_proto`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `notes_id` int(10) unsigned NOT NULL auto_increment,
  `notes_uuid` varchar(255) NOT NULL default '',
  `notes_notes` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`notes_id`),
  KEY `id` (`notes_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `onboard_device`;
CREATE TABLE `onboard_device` (
  `onboard_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `onboard_uuid` varchar(255) NOT NULL DEFAULT '',
  `onboard_description` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `onboard_type` VARCHAR( 20 ) NOT NULL DEFAULT '',
  `onboard_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `onboard_first_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`onboard_id`),
  KEY `id` (`onboard_uuid`),
  KEY `id2` (`onboard_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `optical_drive`;
CREATE TABLE `optical_drive` (
  `optical_drive_id` int(10) unsigned NOT NULL auto_increment,
  `optical_drive_uuid` varchar(255) NOT NULL default '',
  `optical_drive_caption` varchar(100) NOT NULL default '',
  `optical_drive_device_id` varchar(100) NOT NULL default '',
  `optical_drive_drive` varchar(10) NOT NULL default '',
  `optical_drive_timestamp` bigint(20) unsigned NOT NULL default '0',
  `optical_drive_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`optical_drive_id`),
  KEY `id` (`optical_drive_uuid`),
  KEY `id2` (`optical_drive_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `other`;
CREATE TABLE `other` (
  `other_id` int(10) unsigned NOT NULL auto_increment,
  `other_network_name` varchar(200) NOT NULL default '',
  `other_ip_address` varchar(30) NOT NULL default '',
  `other_mac_address` varchar(17) NOT NULL default '',
  `other_description` varchar(255) NOT NULL default '',
  `other_serial` varchar(50) NOT NULL default '',
  `other_model` varchar(50) NOT NULL default '',
  `other_type` varchar(50) NOT NULL default '',
  `other_location` varchar(1000) NOT NULL default '',
  `other_value` varchar(30) NOT NULL default '',
  `other_linked_pc` varchar(100) NOT NULL default '',
  `other_manufacturer` varchar(50) NOT NULL default '',
  `other_date_purchased` date NOT NULL default '0000-00-00',
  `other_purchase_order_number` varchar(255) NOT NULL default '',
  `other_p_port_name` varchar(100) NOT NULL default '',
  `other_p_shared` varchar(10) NOT NULL default '',
  `other_p_share_name` varchar(50) NOT NULL default '',
  `other_switch_id` varchar(10) NOT NULL default '',
  `other_switch_port` varchar(10) NOT NULL default '',
  `other_timestamp` bigint(20) unsigned NOT NULL default '0',
  `other_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`other_id`),
  KEY `id` (`other_network_name`),
  KEY `id2` (`other_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `pagefile`;
CREATE TABLE `pagefile` (
  `pagefile_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `pagefile_uuid` varchar(255) NOT NULL DEFAULT '',
  `pagefile_name` VARCHAR( 100 ) NOT NULL DEFAULT '',
  `pagefile_initial_size` INT( 11 ) NOT NULL DEFAULT '0',
  `pagefile_max_size` INT( 11 ) NOT NULL DEFAULT '0',
  `pagefile_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `pagefile_first_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`pagefile_id`),
  KEY `id` (`pagefile_uuid`),
  KEY `id2` (`pagefile_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `partition`;
CREATE TABLE `partition` (
  `partition_id` int(10) unsigned NOT NULL auto_increment,
  `partition_uuid` varchar(255) NOT NULL default '',
  `partition_bootable` varchar(10) NOT NULL default '',
  `partition_boot_partition` varchar(10) NOT NULL default '',
  `partition_device_id` varchar(100) NOT NULL default '',
  `partition_disk_index` varchar(50) NOT NULL default '',
  `partition_index` varchar(100) NOT NULL default '',
  `partition_primary_partition` varchar(10) NOT NULL default '',
  `partition_caption` varchar(100) NOT NULL default '',
  `partition_file_system` varchar(20) NOT NULL default '',
  `partition_volume_name` varchar(100) NOT NULL default '',
  `partition_free_space` int(11) unsigned NOT NULL default '1',
  `partition_used_space` int(11) unsigned NOT NULL default '1',
  `partition_type` varchar(30) NOT NULL default '',
  `partition_bitlocker` varchar(10) NOT NULL default '',
  `partition_size` int(11) unsigned NOT NULL default '1',
  `partition_timestamp` bigint(20) unsigned NOT NULL default '0',
  `partition_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`partition_id`),
  KEY `id` (`partition_uuid`),
  KEY `id2` (`partition_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `passwords`;
CREATE TABLE `passwords` (
  `passwords_id` int(10) unsigned NOT NULL auto_increment,
  `passwords_uuid` varchar(255) NOT NULL default '',
  `passwords_application` varchar(100) NOT NULL default '',
  `passwords_password` varchar(100) NOT NULL default '',
  `passwords_user` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`passwords_id`),
  KEY `id` (`passwords_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `processor`;
CREATE TABLE `processor` (
  `processor_id` int(10) unsigned NOT NULL auto_increment,
  `processor_uuid` varchar(255) NOT NULL default '',
  `processor_caption` varchar(100) NOT NULL default '',
  `processor_device_id` varchar(100) NOT NULL default '',
  `processor_manufacturer` varchar(100) NOT NULL default '',
  `processor_name` varchar(100) NOT NULL default '',
  `processor_power_management_supported` varchar(20) NOT NULL default '',
  `processor_socket_designation` varchar(50) NOT NULL default '',
  `processor_current_clock_speed` int(11) unsigned NOT NULL default '0',
  `processor_current_voltage` int(11) unsigned NOT NULL default '0',
  `processor_ext_clock` int(11) unsigned NOT NULL default '0',
  `processor_max_clock_speed` int(11) unsigned NOT NULL default '0',
  `processor_timestamp` bigint(20) unsigned NOT NULL default '0',
  `processor_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`processor_id`),
  KEY `id` (`processor_uuid`),
  KEY `id2` (`processor_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `scan_type`;
CREATE TABLE `scan_type` (
      `scan_type_id` int  NOT NULL AUTO_INCREMENT,
      `scan_type_uuid` varchar(255)  NOT NULL,
      `scan_type_ip_address` varchar(16)  NOT NULL,
      `scan_type` varchar(10)  NOT NULL,
      `scan_type_detail` VARCHAR(100)  NOT NULL,
      `scan_type_frequency` TINYINT  NOT NULL,
      PRIMARY KEY(`scan_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `scan_log`;
CREATE TABLE `scan_log` (
      `scan_log_id` int  NOT NULL AUTO_INCREMENT,
      `scan_log_uuid` varchar(255)  NOT NULL,
      `scan_log_ip_address` varchar(16)  NOT NULL,
      `scan_log_type` varchar(10)  NOT NULL,
      `scan_log_detail` VARCHAR(100)  NOT NULL,
      `scan_log_frequency` TINYINT  NOT NULL,
      `scan_log_date_time` datetime  NOT NULL,
      `scan_log_result` varchar(20)  NOT NULL,
      `scan_log_success` varchar(2)  NOT NULL,
      PRIMARY KEY(`scan_log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `scan_latest`;
CREATE TABLE `scan_latest` (
      `scan_latest_id` int  NOT NULL AUTO_INCREMENT,
      `scan_latest_uuid` varchar(255)  NOT NULL,
      `scan_latest_ip_address` varchar(16)  NOT NULL,
      `scan_latest_type` varchar(10)  NOT NULL,
      `scan_latest_detail` VARCHAR(100)  NOT NULL,
      `scan_latest_frequency` TINYINT  NOT NULL,
      `scan_latest_date_time` datetime  NOT NULL,
      `scan_latest_result` varchar(20)  NOT NULL,
      `scan_latest_success` varchar(2)  NOT NULL,
      PRIMARY KEY(`scan_latest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `scheduled_task`;
CREATE TABLE `scheduled_task` (
  `sched_task_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `sched_task_uuid` varchar(255) NOT NULL DEFAULT '',
  `sched_task_name` VARCHAR( 100 ) NOT NULL DEFAULT '',
  `sched_task_next_run` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `sched_task_status` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `sched_task_last_run` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `sched_task_last_result` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `sched_task_creator` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `sched_task_schedule` VARCHAR( 100 ) NOT NULL DEFAULT '',
  `sched_task_task` VARCHAR( 100 ) NOT NULL DEFAULT '',
  `sched_task_state` VARCHAR( 10 ) NOT NULL DEFAULT '',
  `sched_task_runas` VARCHAR( 50 ) NOT NULL DEFAULT '',
  `sched_task_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `sched_task_first_timestamp` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY  (`sched_task_id`),
  KEY `id` (`sched_task_uuid`),
  KEY `id2` (`sched_task_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `scsi_controller`;
CREATE TABLE `scsi_controller` (
  `scsi_controller_id` int(10) unsigned NOT NULL auto_increment,
  `scsi_controller_uuid` varchar(200) NOT NULL default '',
  `scsi_controller_caption` varchar(200) NOT NULL default '',
  `scsi_controller_device_id` varchar(200) NOT NULL default '',
  `scsi_controller_manufacturer` varchar(100) NOT NULL default '',
  `scsi_controller_timestamp` bigint(20) unsigned NOT NULL default '0',
  `scsi_controller_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`scsi_controller_id`),
  KEY `id` (`scsi_controller_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `scsi_device`;
CREATE TABLE `scsi_device` (
  `scsi_device_id` int(10) unsigned NOT NULL auto_increment,
  `scsi_device_uuid` varchar(255) NOT NULL default '',
  `scsi_device_controller` varchar(200) NOT NULL default '',
  `scsi_device_device` varchar(200) NOT NULL default '',
  `scsi_device_timestamp` bigint(20) unsigned NOT NULL default '0',
  `scsi_device_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`scsi_device_id`),
  KEY `id2` (`scsi_device_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `service`;
CREATE TABLE `service` (
  `service_id` int(10) unsigned NOT NULL auto_increment,
  `service_uuid` varchar(255) NOT NULL default '',
  `service_display_name` varchar(100) NOT NULL default '',
  `service_name` varchar(100) NOT NULL default '',
  `service_path_name` varchar(200) NOT NULL default '',
  `service_started` varchar(10) NOT NULL default '',
  `service_start_mode` varchar(10) NOT NULL default '',
  `service_state` varchar(10) NOT NULL default '',
  `service_count` varchar(5) NOT NULL default '',
  `service_start_name` varchar(100) NOT NULL default '',
  `service_timestamp` bigint(20) unsigned NOT NULL default '0',
  `service_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`service_id`),
  KEY `id` (`service_uuid`),
  KEY `id2` (`service_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `service_details`;
CREATE TABLE `service_details` (
  `sd_display_name` varchar(100) NOT NULL default '',
  `sd_description` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`sd_display_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `shares`;
CREATE TABLE `shares` (
  `shares_id` int(10) unsigned NOT NULL auto_increment,
  `shares_uuid` varchar(255) NOT NULL default '',
  `shares_caption` varchar(100) NOT NULL default '',
  `shares_name` varchar(100) NOT NULL default '',
  `shares_path` varchar(100) NOT NULL default '',
  `shares_timestamp` bigint(20) unsigned NOT NULL default '0',
  `shares_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`shares_id`),
  KEY `id` (`shares_uuid`),
  KEY `id2` (`shares_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `smtp_connection`;
CREATE TABLE `smtp_connection` (
  `smtp_connection_id` int(10) unsigned NOT NULL auto_increment,
  `smtp_connection_from` varchar(45) NOT NULL default '',
  `smtp_connection_server` varchar(255) NOT NULL default '',
  `smtp_connection_port` int(10) unsigned NOT NULL,
  `smtp_connection_auth` int(10) unsigned NOT NULL,
  `smtp_connection_use_ssl` int(10) unsigned NOT NULL,
  `smtp_connection_start_tls` int(10) unsigned NOT NULL,
  `smtp_connection_security` varchar(45) NOT NULL default '',
  `smtp_connection_user` varbinary(255) NOT NULL default '',
  `smtp_connection_password` varbinary(255) NOT NULL default '',
  `smtp_connection_realm` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`smtp_connection_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `software`;
CREATE TABLE `software` (
  `software_id` int(10) unsigned NOT NULL auto_increment,
  `software_uuid` varchar(255) NOT NULL default '',
  `software_name` varchar(255) NOT NULL default '',
  `software_version` varchar(50) NOT NULL default '',
  `software_location` varchar(200) NOT NULL default '',
  `software_uninstall` MEDIUMTEXT NOT NULL default '',
  `software_install_date` varchar(100) NOT NULL default '',
  `software_publisher` varchar(100) NOT NULL default '',
  `software_install_source` varchar(200) NOT NULL default '',
  `software_system_component` varchar(2) NOT NULL default '',
  `software_url` varchar(100) NOT NULL default '',
  `software_comment` varchar(200) NOT NULL default '',
  `software_count` varchar(5) NOT NULL default '',
  `software_timestamp` bigint(20) unsigned NOT NULL default '0',
  `software_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`software_id`),
  KEY `id` (`software_uuid`),
  KEY `id2` (`software_timestamp`),
	KEY `Index3` (`software_first_timestamp`),
	KEY `Index4` (`software_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `softwapps`;
CREATE TABLE `softwapps` (
  `software_id` int(10) unsigned NOT NULL auto_increment,
  `software_uuid` varchar(255) NOT NULL default '',
  `software_name` varchar(255) NOT NULL default '',
  `software_version` varchar(50) NOT NULL default '',
  `software_location` varchar(200) NOT NULL default '',
  `software_uninstall` MEDIUMTEXT NOT NULL default '',
  `software_install_date` varchar(100) NOT NULL default '',
  `software_publisher` varchar(100) NOT NULL default '',
  `software_install_source` varchar(200) NOT NULL default '',
  `software_system_component` varchar(2) NOT NULL default '',
  `software_url` varchar(100) NOT NULL default '',
  `software_comment` varchar(200) NOT NULL default '',
  `software_count` varchar(5) NOT NULL default '',
  `software_timestamp` bigint(20) unsigned NOT NULL default '0',
  `software_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`software_id`),
  KEY `id` (`software_uuid`),
  KEY `id2` (`software_timestamp`),
	KEY `Index3` (`software_first_timestamp`),
	KEY `Index4` (`software_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `softwareversionen`;
CREATE TABLE `softwareversionen` (
  `sv_datum` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `sv_rating` varchar(45) NOT NULL DEFAULT '',
  `sv_id` int(10) UNSIGNED NOT NULL,
  `sv_product` varchar(255) NOT NULL DEFAULT '',
  `sv_version` varchar(50) NOT NULL DEFAULT '1.0',
  `sv_bemerkungen` varchar(2000) NOT NULL DEFAULT '',
  `sv_vorinstall` varchar(100) NOT NULL DEFAULT '',
  `sv_quelle` varchar(100) NOT NULL DEFAULT '',
  `sv_lizenztyp` varchar(100) NOT NULL DEFAULT '',
  `sv_lizenzgeber` varchar(255) NOT NULL DEFAULT '',
  `sv_lizenzbestimmungen` varchar(255) NOT NULL DEFAULT '',
  `sv_instlocation` varchar(20) NOT NULL DEFAULT '',
  `sv_herstellerwebsite` varchar(255) NOT NULL DEFAULT '',
  `sv_linkempf` varchar(10) NOT NULL DEFAULT '',
  `sv_icondata` varchar(20000) NOT NULL DEFAULT '',
  `sv_supportmail` varchar(60) NOT NULL,
  `sv_supporttel` varchar(30) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `software_group_members`;
CREATE TABLE `software_group_members` (
  `group_id` int(10) unsigned NOT NULL default '0',
  `group_software_title` varchar(250) NOT NULL default '',
  UNIQUE KEY `group_id` (`group_id`,`group_software_title`),
  KEY `group_software_title` (`group_software_title`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `software_group_names`;
CREATE TABLE `software_group_names` (
  `group_id` int(10) unsigned NOT NULL auto_increment,
  `group_name` varchar(200) NOT NULL default '',
  `group_desc` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `software_licenses`;
CREATE TABLE `software_licenses` (
  `license_id` int(10) unsigned NOT NULL auto_increment,
  `license_software_id` int(10) unsigned NOT NULL default '0',
  `license_purchase_cost_each` int(10) NOT NULL default '0',
  `license_purchase_number` int(10) NOT NULL default '0',
  `license_purchase_date` date NOT NULL default '0000-00-00',
  `license_purchase_vendor` varchar(150) NOT NULL default '',
  `license_comments` varchar(200) NOT NULL default '',
  `license_purchase_type` varchar(50) NOT NULL default '',
  `license_order_number` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`license_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `software_register`;
CREATE TABLE `software_register` (
  `software_reg_id` int(10) unsigned NOT NULL auto_increment,
  `group_id` int(10) unsigned NOT NULL default '0',
  `software_title` varchar(100) NOT NULL default '',
  `software_comments` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`software_reg_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `sound`;
CREATE TABLE `sound` (
  `sound_id` int(10) unsigned NOT NULL auto_increment,
  `sound_uuid` varchar(255) NOT NULL default '',
  `sound_manufacturer` varchar(100) NOT NULL default '',
  `sound_device_id` varchar(100) NOT NULL default '',
  `sound_name` varchar(100) NOT NULL default '',
  `sound_timestamp` bigint(20) unsigned NOT NULL default '0',
  `sound_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`sound_id`),
  KEY `id` (`sound_uuid`),
  KEY `id2` (`sound_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `spare`;
CREATE TABLE `spare` (
  `spare_id` int(10) unsigned NOT NULL auto_increment,
  `spare_uuid` varchar(255) NOT NULL default '',
  `spare_field_1` varchar(45) NOT NULL default '',
  `spare_field_2` varchar(100) NOT NULL default '',
  `spare_field_3` varchar(200) NOT NULL default '',
  `spare_field_4` varchar(200) NOT NULL default '',
  `spare_field_5` varchar(200) NOT NULL default '',
  `spare_field_6` varchar(200) NOT NULL default '',
  `spare_field_7` varchar(200) NOT NULL default '',
  `spare_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`spare_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `startup`;
CREATE TABLE `startup` (
  `startup_id` int(10) unsigned NOT NULL auto_increment,
  `startup_uuid` varchar(255) NOT NULL default '',
  `startup_caption` varchar(200) NOT NULL default '',
  `startup_name` varchar(100) NOT NULL default '',
  `startup_command` varchar(200) NOT NULL default '',
  `startup_description` varchar(200) NOT NULL default '',
  `startup_location` varchar(200) NOT NULL default '',
  `startup_user` varchar(100) NOT NULL default '',
  `startup_timestamp` bigint(20) unsigned NOT NULL default '0',
  `startup_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`startup_id`),
  KEY `id` (`startup_uuid`),
  KEY `id2` (`startup_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `switch_ports`;
CREATE TABLE `switch_ports` (
  `switch_id` int(10) unsigned NOT NULL auto_increment,
  `switch_switch_id` varchar(100) NOT NULL default '',
  `switch_other_id` varchar(45) NOT NULL default '',
  `switch_system_id` varchar(45) NOT NULL default '',
  `switch_port` varchar(100) NOT NULL default '',
  `switch_timestamp` varchar(200) NOT NULL default '',
  `switch_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`switch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `system`;
CREATE TABLE `system` (
  `system_num_processors` int(11) unsigned NOT NULL default '0',
  `system_memory` int(11) unsigned NOT NULL default '0',
  `system_build_number` varchar(20) NOT NULL default '',
  `net_ip_address` varchar(20) NOT NULL default '',
  `system_uuid` varchar(255) NOT NULL default '',
  `net_domain` varchar(100) NOT NULL default '',
  `net_user_name` varchar(100) NOT NULL default '',
  `net_client_site_name` varchar(100) NOT NULL default '',
  `net_domain_controller_address` varchar(100) NOT NULL default '',
  `net_domain_controller_name` varchar(100) NOT NULL default '',
  `system_model` varchar(100) NOT NULL default '',
  `system_name` varchar(100) NOT NULL default '',
  `system_part_of_domain` varchar(10) NOT NULL default '',
  `system_primary_owner_name` varchar(100) NOT NULL default '',
  `system_system_type` varchar(100) NOT NULL default '',
  `system_id_number` varchar(100) NOT NULL default '',
  `system_vendor` varchar(100) NOT NULL default '',
  `time_caption` varchar(100) NOT NULL default '',
  `time_daylight` varchar(100) NOT NULL default '',
  `system_vcpu` int(11) unsigned NOT NULL default '0',
  `system_lcpu` int(11) unsigned NOT NULL default '0',
  `system_tpmver` varchar(80) NOT NULL default '',
  `tpm_init` varchar(10) NOT NULL default '',
  `tpm_password` varchar(10) NOT NULL default '',
  `system_boot_device` varchar(100) NOT NULL default '',
  `system_os_type` varchar(50) NOT NULL default '',
  `system_os_name` varchar(100) NOT NULL default '',
  `system_os_arch` varchar(7) NOT NULL default '',
  `system_country_code` varchar(50) NOT NULL default '',
  `system_description` varchar(50) NOT NULL default '',
  `system_organisation` varchar(80) NOT NULL default '',
  `system_language` varchar(50) NOT NULL default '',
  `system_registered_user` varchar(50) NOT NULL default '',
  `system_serial_number` varchar(50) NOT NULL default '',
  `system_service_pack` varchar(20) NOT NULL default '',
  `system_version` varchar(20) NOT NULL default '',
  `system_windows_directory` varchar(20) NOT NULL default '',
  `audit_type` varchar(20) NOT NULL default '',
  `firewall_enabled_domain` varchar(45) NOT NULL default '',
  `firewall_disablenotifications_domain` varchar(45) NOT NULL default '',
  `firewall_donotallowexceptions_domain` varchar(45) NOT NULL default '',
  `net_domain_role` varchar(40) NOT NULL default '',
  `firewall_enabled_standard` varchar(45) NOT NULL default '',
  `firewall_disablenotifications_standard` varchar(45) NOT NULL default '',
  `firewall_donotallowexceptions_standard` varchar(45) NOT NULL default '',
  `virus_manufacturer` varchar(150) NOT NULL default '',
  `virus_version` varchar(45) NOT NULL default '',
  `virus_name` varchar(100) NOT NULL default '',
  `virus_uptodate` varchar(45) NOT NULL default '',
  `date_virus_def` date NOT NULL default '0000-00-00',
  `system_last_boot` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0',
  `date_system_install` date NOT NULL default '0000-00-00',
  `iis_version` varchar(10) NOT NULL default '',
  `system_timestamp` bigint(20) unsigned NOT NULL default '0',
  `system_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`system_uuid`),
  KEY `id2` (`system_timestamp`),
	KEY `Index3` (`system_first_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `system_audits`;
CREATE TABLE `system_audits` (
  `system_audits_id` int(10) unsigned NOT NULL auto_increment,
  `system_audits_uuid` varchar(255) NOT NULL default '',
  `system_audits_username` varchar(45) NOT NULL default '',
  `system_audits_time` varchar(45) NOT NULL default '',
  `system_audits_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`system_audits_id`),
  KEY `Index_1` (`system_audits_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `system_change`;
CREATE TABLE `system_change` (
  `system_change_id` int(10) unsigned NOT NULL auto_increment,
  `system_change_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `system_change_uuid` varchar(255) NOT NULL default '',
  `system_change_short_desc` varchar(200) NOT NULL default '',
  `system_change_detailed_desc` varchar(200) NOT NULL default '',
  `system_change_authorising_person` varchar(45) NOT NULL default '',
  `system_change_reason` varchar(200) NOT NULL default '',
  `system_change_potential_issues` varchar(200) NOT NULL default '',
  `system_change_backout_plan` varchar(200) NOT NULL default '',
  `system_change_callid` int(10) unsigned NOT NULL default '0',
  `system_change_call_techid` int(10) unsigned NOT NULL default '0',
  `system_change_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`system_change_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `system_change_log`;
CREATE TABLE `system_change_log` (
  `system_change_log_id` int(10) unsigned NOT NULL auto_increment,
  `system_change_log_changeid` int(10) unsigned NOT NULL default '0',
  `system_change_log_attachmentid` int(10) unsigned NOT NULL default '0',
  `system_change_log_call_techid` int(10) unsigned NOT NULL default '0',
  `system_change_log_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `system_change_log_comments` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`system_change_log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `system_man`;
CREATE TABLE `system_man` (
  `system_man_id` int(10) unsigned NOT NULL auto_increment,
  `system_man_uuid` varchar(255) NOT NULL default '',
  `system_man_value` varchar(50) NOT NULL default '',
  `system_man_description` varchar(100) NOT NULL default '',
  `system_man_location` varchar(100) NOT NULL default '',
  `system_man_serial_number` varchar(50) NOT NULL default '',
  `system_man_vendor` varchar(150) NOT NULL default '',
  `system_man_purchase_order_number` varchar(50) NOT NULL default '',
  `system_man_invoice` varchar(100) NOT NULL default '',
  `system_man_ethernet_socket` varchar(45) NOT NULL default '',
  `system_man_phone_number` varchar(45) NOT NULL default '',
  `system_man_date_of_purchase` date NOT NULL default '0000-00-00',
  `system_man_terminal_number` int(10) unsigned NOT NULL default '0',
  `system_man_picture` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`system_man_id`),
  KEY `id` (`system_man_uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `system_security`;
CREATE TABLE `system_security` (
  `ss_id` int(10) unsigned NOT NULL auto_increment,
  `ss_uuid` varchar(45) NOT NULL default '',
  `ss_qno` varchar(45) NOT NULL default '',
  `ss_status` varchar(45) NOT NULL default '',
  `ss_reason` varchar(200) NOT NULL default '',
  `ss_product` varchar(45) NOT NULL default '',
  `ss_timestamp` bigint(20) unsigned NOT NULL default '0',
  `ss_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ss_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `system_security_bulletins`;
CREATE TABLE `system_security_bulletins` (
  `ssb_title` varchar(200) NOT NULL default '',
  `ssb_description` text NOT NULL,
  `ssb_bulletin` varchar(45) NOT NULL default '',
  `ssb_qno` varchar(45) NOT NULL default '',
  `ssb_url` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`ssb_qno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `tape_drive`;
CREATE TABLE `tape_drive` (
  `tape_drive_id` int(10) unsigned NOT NULL auto_increment,
  `tape_drive_uuid` varchar(255) NOT NULL default '',
  `tape_drive_caption` varchar(100) NOT NULL default '',
  `tape_drive_description` varchar(100) NOT NULL default '',
  `tape_drive_device_id` varchar(100) NOT NULL default '',
  `tape_drive_manufacturer` varchar(100) NOT NULL default '',
  `tape_drive_name` varchar(100) NOT NULL default '',
  `tape_drive_timestamp` bigint(20) unsigned NOT NULL default '0',
  `tape_drive_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`tape_drive_id`),
  KEY `id` (`tape_drive_uuid`),
  KEY `id2` (`tape_drive_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `usb`;
CREATE TABLE `usb` (
  `usb_id` int(10) unsigned NOT NULL auto_increment,
  `usb_uuid` varchar(255) NOT NULL default '',
  `usb_caption` varchar(100) NOT NULL default '',
  `usb_description` varchar(100) NOT NULL default '',
  `usb_manufacturer` varchar(100) NOT NULL default '',
  `usb_device_id` varchar(120) NOT NULL default '',
  `usb_timestamp` bigint(20) unsigned NOT NULL default '0',
  `usb_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`usb_id`),
  KEY `id` (`usb_uuid`),
  KEY `id2` (`usb_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `users_id` int(10) unsigned NOT NULL auto_increment,
  `users_uuid` varchar(255) NOT NULL default '',
  `users_disabled` varchar(20) NOT NULL default '',
  `users_full_name` varchar(100) NOT NULL default '',
  `users_name` varchar(100) NOT NULL default '',
  `users_password_changeable` varchar(20) NOT NULL default '',
  `users_password_expires` varchar(20) NOT NULL default '',
  `users_password_required` varchar(20) NOT NULL default '',
  `users_sid` varchar(100) NOT NULL default '',
  `users_lockout` varchar(10) NOT NULL default '',
  `users_timestamp` bigint(20) unsigned NOT NULL default '0',
  `users_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`users_id`),
  KEY `id` (`users_uuid`),
  KEY `id2` (`users_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `users_detail`;
CREATE TABLE `users_detail` (
  `ud_name` varchar(100) NOT NULL default '',
  `ud_description` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`ud_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `optionalfeatures`;
CREATE TABLE `optionalfeatures` (
  `opt_id` int(10) unsigned NOT NULL auto_increment,
  `opt_uuid` varchar(255) NOT NULL default '',
  `caption` varchar(100) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`opt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `video`;
CREATE TABLE `video` (
  `video_id` int(10) unsigned NOT NULL auto_increment,
  `video_uuid` varchar(255) NOT NULL default '',
  `video_adapter_ram` varchar(100) NOT NULL default '',
  `video_caption` varchar(100) NOT NULL default '',
  `video_current_horizontal_res` varchar(20) NOT NULL default '',
  `video_current_number_colours` varchar(20) NOT NULL default '',
  `video_current_refresh_rate` varchar(20) NOT NULL default '',
  `video_current_vertical_res` varchar(20) NOT NULL default '',
  `video_description` varchar(100) NOT NULL default '',
  `video_device_id` varchar(100) NOT NULL default '',
  `video_driver_date` varchar(20) NOT NULL default '',
  `video_driver_version` varchar(20) NOT NULL default '',
  `video_max_refresh_rate` varchar(20) NOT NULL default '',
  `video_min_refresh_rate` varchar(20) NOT NULL default '',
  `video_timestamp` bigint(20) unsigned NOT NULL default '0',
  `video_first_timestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`video_id`),
  KEY `id` (`video_uuid`),
  KEY `id2` (`video_timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ws_log`;
CREATE TABLE `ws_log` (
  `ws_log_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ws_log_pid` INT(10) UNSIGNED NOT NULL default '0',
  `ws_log_message` VARCHAR(255) NOT NULL default '',
  `ws_log_timestamp` INT(10) UNSIGNED NOT NULL default '0',
  PRIMARY KEY(`ws_log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO config (config_name, config_value) VALUES ('version','10.09.01');
INSERT INTO audit_settings () VALUES ();