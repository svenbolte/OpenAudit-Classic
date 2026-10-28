<?php
require "include_config.php";
require "include_functions.php";
require "include_email_functions.php";

if ( isset($_POST['timestamp']) && isset($_POST['schedule_id']) && is_numeric($_POST['timestamp']) ) {
  $schedules = GetAuditSchedulesFromDb();

  if ( is_null($schedules[$_POST["schedule_id"]]) ) {
    LogEvent("admin_email_trigger.php","SendAuditLogEmail","Non-Existent Schedule ID Used");
    exit;
  }

  $smtp_conn = GetSmtpConnectionFromDb();

  if ( is_null($smtp_conn) ) {
    LogEvent("admin_email_trigger.php","SendAuditLogEmail","No SMTP Connection. Cannot Send Email");
    exit;
  }

  $db       = GetOpenAuditDbConnection();
  $configs  = GetAuditConfigurationsFromDb();
  $settings = GetAuditSettingsFromDb();

  $id = $_POST["schedule_id"];
  $ts = $_POST["timestamp"];

  $elapsed     = null;
  $hosts       = Array();
  $failed      = Array();
  $killed      = Array();
  $aborted     = Array();
  $cmd_error   = Array();
  $cmd_success = Array();
  $success     = Array();

  $sql = "SELECT * FROM audit_log 
             WHERE audit_log_schedule_id = '$id'
               AND audit_log_config_id = '{$schedules[$id]['config_id']}'
               AND audit_log_timestamp = '$ts'";

  /* Sort the results of the audit first */
  $result = mysqli_query($db,$sql);
  if ($myrow = mysqli_fetch_array($result)) {
    do {
      $msg  = $myrow['audit_log_message'];
      $host = $myrow['audit_log_host'];
      $success_regex = "^(Finished|Audit Completed|Port Scan).*";
      $failed_regex = "^(Cannot Connect|Unable to Scan).*";
      $elapsed_regex = "^Script Execution Time: (.*)$";
      if ( preg_match("/$failed_regex/i",$msg)   ) { array_push($failed,$host);     }
      if ( preg_match("/^Killed Hanging/i",$msg) ) { array_push($killed,$host);     }
      if ( preg_match("/^Audit Stopped/i",$msg ) ) { array_push($aborted,$host);    }
      if ( preg_match("/^Error /i",$msg)         ) { array_push($cmd_error,$host);  }
      if ( preg_match("/^Success /i",$msg)       ) { array_push($cmd_success,$host);}
      if ( preg_match("/$success_regex/i",$msg)  ) { array_push($success,$host);    }
      if ( preg_match("/$elapsed_regex/i",$msg)  ) { $elapsed = $msg;               }
      if ( !empty($host) ) { array_push($hosts,$host); }
    }
    while ($myrow = mysqli_fetch_array($result));
  }
  else {
    exit;
  }

  $action = array( 
    'pc'      => 'PC Audits',
    'nmap'    => 'NMAP Scan', 
    'command' => 'Run Commands',
    'pc_nmap' => 'PC Audit and Port Scan'
  ); 
  $type = array(
    'domain'  => 'LDAP Query',
    'mysql'   => 'MySQL Query',
    'iprange' => 'IP Range',
    'list'    => 'PC/IP List'
  );

  $url_log = "{$settings['base_url']}list.php?view=audit_log_for_timestamp&schedule_id=$id&timestamp=$ts";

  $variables = array(
    '{schedule_name}' => $schedules[$id]['name'],
    '{audit_type}'    => $type[$configs[$schedules[$id]['config_id']]['type']],
    '{audit_action}'  => $action[$configs[$schedules[$id]['config_id']]['action']],
    '{schedule_type}' => ucwords($schedules[$id]['type']),
    '{time_start}'    => return_unix_date_time($ts),
    '{time_elapsed}'  => $elapsed,
    '{num_failed}'    => count($failed),
    '{num_success}'   => count($success),
    '{num_killed}'    => count($killed),
    '{num_aborted}'   => count($aborted),
    '{num_hosts}'     => count($hosts),
    '{url_log}'       => $url_log,
  );


  $email = GetEmailObject();
  preg_match("/^(.*?)@/",$schedules[$id]["email_replyto"],$name);
  $email->SetEncodedEmailHeader("Reply-To",$schedules[$id]["email_replyto"],$name[1]);

  $subject  = $schedules[$id]["email_subject"];
  $to       = explode(";",$schedules[$id]["email_list"]);
  $template = ( !empty($schedules[$id]["email_template"]) ) ?
    $schedules['id']['email_template'] :
    'removed-email-templates/audit_log.html';
  $logo     = '';
  $image    = '';
  $html     = ParseEmailTemplate($variables,$template);

  $result = SendHtmlEmail($subject,$html,$to,$email,null,$image);
}
?>
