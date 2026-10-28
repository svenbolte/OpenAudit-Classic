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

$log_disable         = ( $_POST['check_log_disable']   == "on" ) ? '1' : '0';
$email_log           = ( $_POST['check_email_log']     == "on" ) ? '1' : '0';
$check_hours_between = ( $_POST['check_hours_between'] == "on" ) ? '1' : '0';

$schedules = GetAuditSchedulesFromDb();
$smtp_conn = GetSmtpConnectionFromDb();

/* Check for general errors */
if ( empty($_POST['input_name']) ) {
  $error_list .= "<li>You must name the schedule<br>";
}
else if ( !is_null($schedules) ) {
  foreach ( $schedules as $key => $cfg ) {
    if ( $cfg['name'] == $_POST['input_name'] && $_POST['sched_id'] != $key ) {
      $error_list .= "<li>That schedule name already exists. Please choose another one.</li>";
    }
  }
}
  
if ( $_POST['select_config'] == 'nothing' ) { $error_list .= "<li>You must pick a configuration</li>"; }
if ( $_POST['select_sched_type'] == 'nothing' ) { $error_list .= "<li>You must pick a schedule type</li>"; }

/* Check for misconfigured daily audits */
if ( $_POST['select_sched_type'] == 'daily' ) {
  if ( !preg_match("/^[0-9]{1,3}$/",$_POST['input_days_freq']) ) {
      $error_list .= "<li>The days frequency must be a number and less than 1000</li>";
  }
}

/* Check for misconfigured weekly audits */
$week_choices = @implode(",",$_POST['check_weekly']);
if ( $_POST['select_sched_type'] == 'weekly' && count($_POST['check_weekly']) < 1 ) {
  $error_list .= "<li>You must select at least one day of the week</li>";
}

/* Check for misconfigured monthly audits */
$month_choices = @implode(",",$_POST['check_monthly']);
if ( $_POST['select_sched_type'] == 'monthly' && count($_POST['check_monthly']) < 1 ) {
  $error_list .= "<li>You must select at least one month</li>";
}

/* If they entered a cron line, attempt to parse it first */
if ( $_POST['select_sched_type'] == 'crontab' && is_null(Verify_Cron_Line($_POST['input_cron_line'])) ) {
  $error_list .= "<li>Failed to parse the cron entry. Make sure it's valid...</li>";
}

$emails = explode(";",$_POST["input_email_list"]);

if ( $email_log ) {
  if ( is_null($smtp_conn) ) {
    $error_list .= "<li>You need to configure a SMTP connection to email logs</li>";
  }
  if ( empty($emails) ) {
    $error_list .= "<li>You need to specify at least one address to email to</li>";
  }
  else {
    /* Verify that the email addresses are somewhat correct */
    while ( $email = array_pop($emails) ) {
      if ( !isEmailAddress($email) ) {
        $error_list .= "<li>Invalid email address format: $email</li>";
      }
    }
  }
  if ( !isEmailAddress($_POST['input_email_replyto']) ) {
    $error_list .= "<li>The Reply-To email address is in bad format: {$_POST['input_email_replyto']}</li>";
  }
}

/* At least verify they're trying to use an image */
if ( ! empty($_POST['select_email_logo']) ) {
  $img = false;
  if ( empty($img) ) {
    $error_list .= "<li>The logo doesn't seem to be an image file.</li>";
  }
}

/* Display any errors that occured, or submit the data */
if ( isset($error_list) ) {
  echo "<div id=\"form-result\"><p class=\"result-text\"><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i>
    <strong>Please correct the following form errors</strong><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></p>
    <ul>$error_list</ul></div>";
} else {
  /* Add the schedule to the table now */
  if ( $_POST['form_action'] == "edit" ) {
    $sql =
      "UPDATE audit_schedules SET 
        audit_schd_name='{$_POST['input_name']}',
        audit_schd_type='{$_POST['select_sched_type']}',
        audit_schd_strt_hr='{$_POST['select_gen_hour']}',
        audit_schd_strt_min='{$_POST['select_gen_min']}',
        audit_schd_hr_frq_hr='{$_POST['select_hourly_freq']}',
        audit_schd_hr_frq_min='{$_POST['select_hourly_start']}',
        audit_schd_hr_between='$check_hours_between',
        audit_schd_hr_strt_hr='{$_POST['select_hstrt_hour']}',
        audit_schd_hr_strt_min='{$_POST['select_hstrt_min']}',
        audit_schd_hr_end_hr='{$_POST['select_hend_hour']}',
        audit_schd_hr_end_min='{$_POST['select_hend_min']}',
        audit_schd_dly_frq='{$_POST['input_days_freq']}',
        audit_schd_wk_days='$week_choices',
        audit_schd_mth_day='{$_POST['select_monthly_day']}',
        audit_schd_mth_months='$month_choices',
        audit_schd_cfg_id='{$_POST['select_config']}',
        audit_schd_email_log='$email_log',
        audit_schd_email_list='{$_POST['input_email_list']}',
        audit_schd_email_subject='{$_POST['input_email_subject']}',
        audit_schd_email_replyto='{$_POST['input_email_replyto']}',
        audit_schd_email_logo='{$_POST['select_email_logo']}',
        audit_schd_email_template='{$_POST['select_email_template']}',
        audit_schd_cron_line='{$_POST['input_cron_line']}',
        audit_schd_updated='1',
        audit_schd_log_disable='$log_disable'
      WHERE audit_schd_id='{$_POST['sched_id']}'";
  }
  else {
    $sql = 
        "INSERT INTO audit_schedules (
           audit_schd_name, audit_schd_cfg_id, audit_schd_type,
           audit_schd_strt_hr, audit_schd_strt_min, audit_schd_hr_frq_hr,
           audit_schd_hr_frq_min, audit_schd_hr_between, audit_schd_hr_strt_hr,
           audit_schd_hr_strt_min, audit_schd_hr_end_hr, audit_schd_hr_end_min,
           audit_schd_dly_frq, audit_schd_wk_days, audit_schd_mth_day,
           audit_schd_mth_months, audit_schd_log_disable, audit_schd_email_log,
           audit_schd_email_list, audit_schd_email_subject, audit_schd_email_replyto,
           audit_schd_email_logo, audit_schd_email_template, audit_schd_cron_line
         ) 
         VALUES (
           '{$_POST['input_name']}','{$_POST['select_config']}','{$_POST['select_sched_type']}',
           '{$_POST['select_gen_hour']}', '{$_POST['select_gen_min']}','{$_POST['select_hourly_freq']}',
           '{$_POST['select_hourly_start']}','$check_hours_between', '{$_POST['select_hstrt_hour']}',
           '{$_POST['select_hstrt_min']}','{$_POST['select_hend_hour']}','{$_POST['select_hend_min']}',
           '{$_POST['input_days_freq']}','$week_choices','{$_POST['select_monthly_day']}',
           '$month_choices', '$log_disable','$email_log',
           '{$_POST['input_email_list']}','{$_POST['input_email_subject']}','{$_POST['input_email_replyto']}',
           '{$_POST['select_email_logo']}','{$_POST['select_email_template']}','{$_POST['input_cron_line']}'
         )";
  }
  $db = GetOpenAuditDbConnection();
  mysqli_query($db,$sql) or die("Could not add/update schedule: " . mysqli_error($db) . "<br>");
  $form_action = ( $_POST['form_action'] == "edit" ) ? 'updated' : 'added';
  echo "<div id=\"form-result\"><p class=\"result-text\"><i class=\"fa fa-check-circle\" aria-hidden=\"true\"></i>
    <strong>Schedule $form_action (".date('g:i:s A T', time()) .")</strong></p></div>";
}
?>
