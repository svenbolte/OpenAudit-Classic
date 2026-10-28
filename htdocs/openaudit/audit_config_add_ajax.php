<?php

set_time_limit(60);
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );

require "include_config.php";
require "include_audit_functions.php";
require_once "include_functions.php";
error_reporting(0);

/* Turn checks that are true/false into ints for the db */
$linux_soft_audit   = ( $_POST['check_linux_software']      == "on"    ) ? '1' : '0';
$windows_soft_audit = ( $_POST['check_windows_software']    == "on"    ) ? '1' : '0';
$linux_soft_list    = ( $_POST['check_linux_software_list'] == "on"    ) ? '1' : '0';
$nmap_srv           = ( $_POST['check_nmap_srv']            == "on"    ) ? '1' : '0';
$nmap_udp           = ( $_POST['check_nmap_udp']            == "on"    ) ? '1' : '0';
$nmap_tcp_syn       = ( $_POST['check_nmap_tcp_syn']        == "on"    ) ? '1' : '0';
$filter_case        = ( $_POST['check_filter_case']         == "true"  ) ? '1' : '0';
$filter_inverse     = ( $_POST['check_filter_inverse']      == "true"  ) ? '1' : '0';
$ldap_use_conn      = ( $_POST['select_ldap_cred']          != "nothing" ) ? '1' : '0';
$audit_use_conn     = ( $_POST['select_audit_cred']         != "nothing" ) ? '1' : '0';
$log_enable         = ( $_POST['check_log_enable']          == "on"    ) ? '1' : '0';
$cmd_interact       = ( $_POST['check_command_interact']    == "on"    ) ? '1' : '0';
$audit_local        = ( $_POST['check_cred_local']          == "on"    ) ? '1' : '0';

$db      = GetOpenAuditDbConnection();
$ws_cfg  = GetAuditSettingsFromDb();
$configs = GetAuditConfigurationsFromDb();

/* If they havent set the default base URL, do it now */
if ( empty($ws_cfg['base_url']) ){
  $sql = "UPDATE `audit_settings` SET audit_settings_base_url='" . GetUrlPath() . "';";
  $result = mysqli_query($db,$sql);
}

/* The following options are a must */
if ( empty($_POST['input_name']) ){
  $error_list .= "<li>You must name the configuration</li>";
}
else if ( !is_null($configs) ) {
  foreach ( $configs as $key => $cfg ) {
    if ( $cfg['name'] == $_POST['input_name'] && $_POST['config_id'] != $key ) {
      $error_list .= "<li>That configuration name already exists. Please choose another one.</li>";
    }
  }
}
 
if ( $_POST['select_action'] == 'nothing' ) { $error_list .= "<li>You must pick an audit action</li>"; }
if ( $_POST['select_audit']  == 'nothing' ) { $error_list .= "<li>You must pick an audit type</li>";   }

if ( !preg_match("/^[1-9]([0-9]+)?$/",$_POST['input_max_audits']) ) {
  $error_list .= "<li>Simultaneous audits must not be blank and must be a number (No leading zeros)</li>";
}

if ( !preg_match("/^[1-9]([0-9]+)?$/",$_POST['input_wait_time']) ) {
  $error_list .= "<li>Audit script wait time must not be blank and must be a number (No leading zeros)</li>";
}
else {
  $wait_time = $_POST['input_wait_time'] * 60; /* wait time needs to be in seconds */
}

/* Check for misconfigured PC audits */
if ( $_POST['select_action'] == 'pc' or $_POST['select_action'] == 'command' ) {
  if ( $_POST['select_os'] == 'nothing' ) { $error_list .= "<li>You must choose an OS type for PC audits/Commands</li>"; }
  if ( $_POST['select_os'] == 'windows' && !preg_match("/^\/\/.*\.vbs$/i",$_POST['input_vbs']) ) {
    $error_list .= "<li>Audit.vbs path needs to be in the form of \"//server/share/audit.vbs\"</li>"; }
  if ( $_POST['select_audit_cred'] == "nothing" ) {
    if ( empty($_POST['input_cred_user']) ) { $error_list .= "<li>Audit username cannot be blank</li>"; }
    if ( empty($_POST['input_cred_pass']) ) { $error_list .= "<li>Audit password cannot be blank</li>"; }
  }
}

/* Ensure the IP address range is valid */
if ( $_POST['select_audit'] == 'iprange' ) {
  $ip_start = $_POST['start_ip_1'].".".$_POST['start_ip_2'].".".$_POST['start_ip_3'].".".$_POST['start_ip_4'];
  $ip_end = $_POST['end_ip_4'];
  if ( !preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $ip_start) ) {
    $error_list .= "<li>Starting IP Address not valid</li>";
  }
  elseif ( $_POST['start_ip_4'] > $ip_end ) {
    $error_list .= "<li>The last octet of the ending IP needs to be greater than the last octect for the starting IP</li>"; 
  }
}

/* Validate domain specific form issues */
if ( $_POST['select_audit'] == 'domain' && $_POST['select_ldap_cred'] == 'nothing' ) {
  if ( empty($_POST['input_ldap_user'])   ) { $error_list .= "<li>LDAP username cannot be blank<br />"; }
  if ( empty($_POST['input_ldap_pass'])   ) { $error_list .= "<li>LDAP password cannot be blank<br />"; }
  if ( empty($_POST['input_ldap_server']) ) { $error_list .= "<li>LDAP server cannot be blank<br />";   }
  if ( empty($_POST['input_ldap_path'])   ) { $error_list .= "<li>LDAP path cannot be blank<br />";     }
  if ( !preg_match("/^[0-9]+$/", $_POST['input_ldap_page']) ) {
    $error_list .= "<li>LDAP page size cannot be blank and must be a number</li>";
  }
}

/* Validate computer list form issues */
if ( $_POST['select_audit'] == 'list' && $_POST['text_pc_list'] == '' ) {
  $error_list .= "<li>The computer list cannot be left blank</li>";
}

/* Check that the nmap path exists on the filesystem */
if ( !empty($_POST['input_nmap_path']) && !file_exists($_POST['input_nmap_path']) ) {
  $error_list .= "<li>Cannot find Nmap at path: " . $_POST['input_nmap_path'] . "</li>";
}

/* Check that the winexe path exists on the filesystem */
if ( !empty($_POST['input_com_path']) && !file_exists($_POST['input_com_path']) ) {
  $error_list .= "<li>Cannot find Winexe/RemCom.exe at path: " . $_POST['input_com_path'] . "</li>";
}

/* Get the commands from the post array */
$cmd_choices = @implode(",",$_POST['check_cmd']);

/* Display any errors that occured, or submit the data */
if ( isset($error_list) ) {
  echo "<div class=\"formResult\"><p class=\"result-text\"><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i>
    <strong>Please correct the following form errors</strong><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></p>
    <ul>$error_list</ul></div>";
}
else {
  /* Add the audit config to the table now */
  $aes_key   = GetAesKey();
  $query_ids = array();
  $new_ids   = array();
  /* Delete any mysql query boxes that were removed */
  if ( isset($_POST['del_query']) ) {
    foreach ( $_POST['del_query'] as $del_id ) {
      mysqli_query($db,"DELETE FROM `mysqli_queries` WHERE mysqli_queries_id = '$del_id'",$db);
    }
  }
  /* Add new mysql query boxes */
  if ( isset($_POST['query_fields_add']) ) {
    $count = 0;
    foreach ( $_POST['query_fields_add'] as $query_row ) {
      $values = explode(',',$query_row);
      $sql_add = "INSERT INTO `mysqli_queries` ( mysqli_queries_table, mysqli_queries_field,
                                                mysqli_queries_sort, mysqli_queries_data    ) 
                                       VALUES ('{$values[0]}','{$values[1]}',
                                               '{$values[2]}','{$_POST['query_data_add'][$count]}')";
      mysqli_query($db,$sql_add);
      $id = mysqli_insert_id($db);
      array_push($query_ids,$id);
      array_push($new_ids,$id);
      $count++;
    }
  }
  /* Update any mysql query boxes */
  if ( isset($_POST['query_fields_mod']) ) {
    $count = 0;
    foreach ( $_POST['query_fields_mod'] as $query_row ) {
      $values = explode(',',$query_row);
      mysqli_query($db,"UPDATE `mysqli_queries` SET mysqli_queries_table='{$values[1]}', mysqli_queries_field='{$values[2]}',
                          mysqli_queries_sort='{$values[3]}', mysqli_queries_data='{$_POST['query_data_mod'][$count]}'
                   WHERE mysqli_queries_id='{$values[0]}'", $db);
      array_push($query_ids,$values[0]);
      $count++;
    }
  }
  if ( $_POST['form_action'] == "edit" ) {
    $sql = "UPDATE audit_configurations SET 
              audit_cfg_name='{$_POST['input_name']}',
              audit_cfg_action='{$_POST['select_action']}',
              audit_cfg_type='{$_POST['select_audit']}',
              audit_cfg_os='{$_POST['select_os']}',
              audit_cfg_max_audits='{$_POST['input_max_audits']}',
              audit_cfg_ldap_user=AES_ENCRYPT('{$_POST['input_ldap_user']}','".$aes_key."'),
              audit_cfg_ldap_pass=AES_ENCRYPT('{$_POST['input_ldap_pass']}','".$aes_key."'),
              audit_cfg_ldap_server='{$_POST['input_ldap_server']}',
              audit_cfg_ldap_page='{$_POST['input_ldap_page']}',
              audit_cfg_audit_user=AES_ENCRYPT('{$_POST['input_cred_user']}','".$aes_key."'),
              audit_cfg_audit_pass=AES_ENCRYPT('{$_POST['input_cred_pass']}','".$aes_key."'),
              audit_cfg_ip_start='$ip_start',
              audit_cfg_ip_end='$ip_end',
              audit_cfg_pc_list='{$_POST['text_pc_list']}',
              audit_cfg_win_vbs='{$_POST['input_vbs']}',
              audit_cfg_win_sft='$windows_soft_audit',
              audit_cfg_win_url='{$_POST['input_windows_url']}',
              audit_cfg_lin_sft='$linux_soft_audit',
              audit_cfg_lin_sft_lst='$linux_soft_list',
              audit_cfg_sft_lst='{$_POST['text_linux_software']}',
              audit_cfg_ldap_use_conn='$ldap_use_conn',
              audit_cfg_audit_use_conn='$audit_use_conn',
              audit_cfg_ldap_conn='{$_POST['select_ldap_cred']}',
              audit_cfg_audit_conn='{$_POST['select_audit_cred']}',
              audit_cfg_nmap_udp='$nmap_udp',
              audit_cfg_nmap_tcp_syn='$nmap_tcp_syn',
              audit_cfg_nmap_srv='$nmap_srv',
              audit_cfg_nmap_int='{$_POST['select_nmap_intensity']}',
              audit_cfg_nmap_url='{$_POST['input_nmap_url']}',
              audit_cfg_ldap_path='{$_POST['input_ldap_path']}',
              audit_cfg_wait_time='".$wait_time."',
              audit_cfg_lin_url='{$_POST['input_linux_url']}',
              audit_cfg_filter='{$_POST['input_filter']}',
              audit_cfg_filter_case='$filter_case',
              audit_cfg_filter_inverse='$filter_inverse',
              audit_cfg_nmap_path='{$_POST['input_nmap_path']}',
              audit_cfg_com_path='{$_POST['input_com_path']}',
              audit_cfg_command_list='{$_POST['text_commands']}',
              audit_cfg_log_enable='$log_enable',
              audit_cfg_mysqli_ids='".@implode(",",$query_ids)."',
              audit_cfg_command_interact='$cmd_interact',
              audit_cfg_cmd_list='$cmd_choices',
              audit_cfg_win_uuid='{$_POST['select_windows_uuid']}',
              audit_cfg_audit_local='$audit_local'
            WHERE audit_cfg_id='{$_POST['config_id']}'";
  }
  else {
    $sql = "INSERT INTO audit_configurations ( audit_cfg_name, audit_cfg_action,
              audit_cfg_type, audit_cfg_os,
              audit_cfg_max_audits, audit_cfg_ldap_conn,
              audit_cfg_audit_conn, audit_cfg_ldap_user,
              audit_cfg_ldap_pass, audit_cfg_ldap_server,
              audit_cfg_ldap_page, audit_cfg_audit_user,
              audit_cfg_audit_pass, audit_cfg_ip_start,
              audit_cfg_ip_end, audit_cfg_pc_list,
              audit_cfg_win_vbs, audit_cfg_lin_sft,
              audit_cfg_lin_sft_lst, audit_cfg_sft_lst,
              audit_cfg_ldap_use_conn, audit_cfg_audit_use_conn,
              audit_cfg_nmap_int, audit_cfg_nmap_srv,
              audit_cfg_nmap_udp, audit_cfg_nmap_tcp_syn,
              audit_cfg_nmap_url, audit_cfg_ldap_path,
              audit_cfg_wait_time, audit_cfg_lin_url,
              audit_cfg_filter, audit_cfg_filter_case,
              audit_cfg_nmap_path, audit_cfg_com_path,
              audit_cfg_command_list, audit_cfg_log_enable, 
              audit_cfg_filter_inverse, audit_cfg_cmd_list,
              audit_cfg_mysqli_ids, audit_cfg_command_interact,
              audit_cfg_audit_local, audit_cfg_win_url,
              audit_cfg_win_sft, audit_cfg_win_uuid)
           VALUES ( '{$_POST['input_name']}','{$_POST['select_action']}',
             '{$_POST['select_audit']}','{$_POST['select_os']}',
             '{$_POST['input_max_audits']}', '{$_POST['select_ldap_cred']}',
             '{$_POST['select_audit_cred']}', AES_ENCRYPT('{$_POST['input_ldap_user']}', '$aes_key'),
             AES_ENCRYPT('".$_POST['input_ldap_pass']."','$aes_key'), '{$_POST['input_ldap_server']}',
             '{$_POST['input_ldap_page']}', AES_ENCRYPT('{$_POST['input_cred_user']}','$aes_key'),
             AES_ENCRYPT('".$_POST['input_cred_pass']."','$aes_key'), '$ip_start',
             '$ip_end','{$_POST['text_pc_list']}',
             '{$_POST['input_vbs']}','$linux_soft_audit',
             '$linux_soft_list','{$_POST['text_linux_software']}',
             '$ldap_use_conn','$audit_use_conn',
             '{$_POST['select_nmap_intensity']}', '$nmap_srv',
             '$nmap_udp', '$nmap_tcp_syn',
             '{$_POST['input_nmap_url']}', '{$_POST['input_ldap_path']}',
             '$wait_time','{$_POST['input_linux_url']}',
             '{$_POST['input_filter']}','$filter_case',
             '{$_POST['input_nmap_path']}','{$_POST['input_com_path']}',
             '{$_POST['text_commands']}', '$log_enable',
             '$filter_inverse', '$cmd_choices',
             '".@implode(',',$query_ids)."','$cmd_interact',
             '$audit_local','{$_POST['input_windows_url']}',
             '$windows_soft_audit','{$_POST['select_windows_uuid']}')";
  }

  mysqli_query($db,$sql) or die("Could not add config: " . mysqli_error($db) . "<br />");
  /* Update the schedules only if this is an edit and it was updated successfully */
  if ( $_POST['form_action'] == 'edit' ) {
    mysqli_query($db,"UPDATE audit_schedules SET audit_schd_updated='1' WHERE audit_schd_cfg_id='{$_POST['config_id']}'",$db);
  }

  /* Config id needed to associate any mysql query options to this config */
  $config_id = ( ! isset($_POST['config_id']) ) ? mysqli_insert_id($db) : $_POST['config_id'];

  /* Associate the config to query to make it easy to get them in the future */
  if ( isset($_POST['query_fields_add']) ) {
    foreach ( $new_ids as $id ) {
      mysqli_query($db,"UPDATE `mysqli_queries` SET mysqli_queries_cfg_id='$config_id' WHERE mysqli_queries_id='$id'",$db);
    }
  }

  $form_action = ( $_POST['form_action'] == "edit" ) ? 'updated' : 'added';
  echo "<div class=\"formResult\"><p class=\"result-text\"><i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i>
    <strong>Configuration $form_action (".date('g:i:s A T', time()) .")</strong></p></div>";
}
?>
