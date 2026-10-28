<?php
set_time_limit(60);
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );

require "include_config.php";
require "include_audit_functions.php";
error_reporting(0);

$action = $_POST['action'];
$db     = GetOpenAuditDbConnection();

if ( $action == 'update' ) { Update_Commands($db); }

function Update_Commands($db) {
  $cmd_ids = array();
  $new_ids = array();

  /* Delete any commands that were removed */
  if ( isset($_POST['del_cmd']) ) {
    foreach ( $_POST['del_cmd'] as $del_id ) {
      mysqli_query($db,"DELETE FROM `audit_commands` WHERE audit_cmd_id='$del_id'",$db) or 
        die("Cannot delete from DB: " . mysqli_error($db) . "<br>");
    }
  }
  /* Add new commands */
  if ( isset($_POST['cmd_add_name']) ) {
    $count = 0;
    foreach ( $_POST['cmd_add_name'] as $name ) {
      $sql = "INSERT INTO `audit_commands` (audit_cmd_name, audit_cmd_command) 
                                    VALUES ('$name','{$_POST['cmd_add_cmd'][$count]}')";
      mysqli_query($db,$sql) or die("Cannot add command $name: " . mysqli_error($db) . "<br>");
      array_push($cmd_ids,mysqli_insert_id());
      array_push($new_ids,mysqli_insert_id());
      $count++;
    }
  }
  /* Update any commands */
  if ( isset($_POST['cmd_mod_id']) ) {
    $count = 0;
    foreach ( $_POST['cmd_mod_id'] as $id ) {
      $sql = "UPDATE `audit_commands` SET
                audit_cmd_name='{$_POST['cmd_mod_name'][$count]}',
                audit_cmd_command='{$_POST['cmd_mod_cmd'][$count]}'
              WHERE audit_cmd_id='$id'";
      mysqli_query($db,$sql) or
        die("Cannot update command {$_POST['cmd_mod_name'][$count]}: " . mysqli_error($db) . "<br>");
      array_push($cmd_ids,$id);
      $count++;
    }
  }

  echo "<p><i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i>
    <strong>Commands updated (".date('g:i:s A T', time()) .")</strong></p>";
}


?>
