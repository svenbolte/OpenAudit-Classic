<?php
  $page = "admin";
  require_once "include.php";
  require_once "include_audit_functions.php";

  // Set tooltip values for some configuration options that need an explanation
  $tooltips = array(
    "email_list" =>  "Separate multiple email addresses with a semi-colon",
    "cron_entry" =>  "A Vixie compatible cron entry for a schedule.<br />"
                    ."Supports steps (*/1), ranges (1-5), and three letter<br />"
                    ."abbreviations for weekdays/months. Example<br /> Format: 10 5 * * *",
  );

// Get values for items that are stored only in the DB, not in include_config.php
  $cfg = GetAuditSchedulesFromDb();
  $id  = ( isset($_GET['sched_id']) ) ? $_GET['sched_id'] : '0'; 

  $dly_frq = ( empty($cfg[$id]['daily_frequency']) ) ? '1' : $cfg[$id]['daily_frequency'] ;

  $form_action = ( isset($_GET['sched_id']) ) ? 'edit' : 'add' ; 
  $months    = @explode(",",$cfg[$id]['months']); 
  $wk_days   = @explode(",",$cfg[$id]['week_days']); 
  $daily_frq = (!is_null($cfg[$id])) ? $cfg[$id]['daily_frequency'] : 1;

  $head = 
    ( isset($_GET['sched_id']) ) ?
    "Editing Schedule: {$cfg[$id]['name']}" :
    'Add a Schedule';

  function CheckWeekMonth($list,$item){ if(in_array($item,$list)){echo "CHECKED";} }
?>
<link media="screen" rel="stylesheet" type="text/css" href="audit_sched.css" />
<script type='text/javascript' src="javascript/audit_config.js"></script>
<script type='text/javascript' src="javascript/audit_sched.js"></script>
<td valign="top">
<div class="main_each">
  <div class="form-result"><span id="form_result_success"></span></div>
  <?php
    /* Check if the schedule exists. Do not show the form if none exists */
    if ((isset($_GET['sched_id']) && !is_null($cfg[$id])) || !isset($_GET['sched_id'])) {
  ?>
  <div class="submit-push">&nbsp;</div>
  <div class="header"><?php echo $head ?></div>
  <br /><br />
  <form action="javascript:SubmitForm('sched','<?php echo $form_action ?>','<?php echo $id; ?>');" method="post" id="form_sched">
    <fieldset><legend>General Settings</legend>
        <label for="input_name">Name</label>
        <input type="text" size="20" id="input_name" name="input_name" value="<?php echo $cfg[$id]['name'] ?>"/>
        <br />
        <label for="select_config">Configuration</label>
        <?php Get_Audit_Configs($cfg[$id]['config_id']); ?>
        <br />
        <label for="select_sched_type">Schedule Type</label>
        <select size="1" onChange="SwitchSchedType(this)" id="select_sched_type" name="select_sched_type">
          <option value="nothing">Schedule Type</option>
          <option value="nothing">-------</option>
          <option value="hourly" <?php if($cfg[$id]['type']=="hourly"){echo "SELECTED";} ?> >Hourly</option>
          <option value="daily" <?php if($cfg[$id]['type']=="daily"){echo "SELECTED";} ?> >Daily</option>
          <option value="weekly" <?php if($cfg[$id]['type']=="weekly"){echo "SELECTED";} ?> >Weekly</option>
          <option value="monthly" <?php if($cfg[$id]['type']=="monthly"){echo "SELECTED";} ?> >Monthly</option>
          <option value="crontab"<?php if($cfg[$id]['type']=="crontab"){echo "SELECTED";} ?> >Cron Entry</option>
        </select>
        <br />
        <label for="select_gen_hour">Starting Time</label>
        <select size="1" id="select_gen_hour" name="select_gen_hour">
          <?php Get_Select_Options("0","23",$cfg[$id]['hour']); ?>
        </select>:
        <select size="1" id="select_gen_min" name="select_gen_min">
          <?php Get_Select_Options("0","59",$cfg[$id]['minute']); ?>
        </select>
        <br />
        <label for="check_log_disable">Disable Logging</label>
        <input type="checkbox" onclick="toggleLogging(this)" size="20" id="check_log_disable" name="check_log_disable" <?php if ( $cfg[$id]['log_disabled'] ) { echo "CHECKED"; } ?>/>
        <br /><br />
        <label for="check_email_log">Email Audit Results</label>
        <input type="checkbox" onclick="toggleEmail(this)" size="20" id="check_email_log" name="check_email_log" <?php if ( $cfg[$id]['email_log'] ) { echo "CHECKED"; } ?>/>
    </fieldset>
    <fieldset id="fs_hourly" class="schedule-type hourly"><legend>Hourly Settings</legend>
      <label for="select_hourly_freq">Every</label>
      <select size="1" id="select_hourly_freq" name="select_hourly_freq"> <?php Get_Select_Options("1","12",$cfg[$id]['hour_frequency']); ?> </select>&nbsp;&nbsp;
      <b>hours</b>
      <br />
      <label for="select_hourly_start">Start the task</label>
      <select size="1" id="select_hourly_start" name="select_hourly_start"><?php Get_Select_Options("0","59",$cfg[$id]['minute_frequency']); ?></select>&nbsp;&nbsp;
      <b>minutes past the hour</b>
      <br />
      <label for="select_hourly_start">Between a certain time</label>
      <input type="checkbox" size="20" id="check_hours_between" name="check_hours_between" <?php if ( $cfg[$id]['between_hours'] ) { echo "CHECKED"; } ?> onClick="BetweenHours(this)"/>
      <br /><br />
      <label for="select_hstrt_hour">Starting Time</label>
      <select size="1" id="select_hstrt_hour" name="select_hstrt_hour">
        <?php Get_Select_Options("0","23",$cfg[$id]['hour_start']); ?>
      </select>:
      <select size="1" id="select_hstrt_min" name="select_hstrt_min" onChange="MinCopy(this)">
        <?php Get_Select_Options("0","59",$cfg[$id]['minute_start']); ?>
      </select>
      <br />
      <label for="select_hend_hour">Ending Time</label>
      <select size="1" id="select_hend_hour" name="select_hend_hour">
        <?php Get_Select_Options("0","23",$cfg[$id]['hour_end']); ?>
      </select>:
      <select size="1" id="select_hend_min" name="select_hend_min">
        <?php Get_Select_Options("0","59",$cfg[$id]['minute_start']); ?>
      </select>
    </fieldset>
    <fieldset id="fs_daily" class="schedule-type daily"><legend>Daily Settings</legend>
      <label for="input_days_freq">Every</label>
      <input type="text" size="3" id="input_days_freq" name="input_days_freq" value="<?php echo $daily_frq ?>"/>
      &nbsp;&nbsp;<b>day(s)</b>
    </fieldset>
    <fieldset id="fs_crontab" class="schedule-type crontab"><legend>Cron Entry</legend>
      <label for="input_cron_line">
	    <a href="#" title="<?php echo $tooltips["cron_entry"] ?>" class="tooltip">[?]</a>Cron Line
      </label>
      <input type="text" size="25" id="input_cron_line" name="input_cron_line" value="<?php echo $cfg[$id]['cron_line'] ?>"/>
      <br />
      <label>Check Next Execution Time</label>
      <input type="button" id="cron_button" value="Cron Test" onclick="testCron(this)">
      <br /><br />
      <label></label>
      <span id="cron_result"></span>
    </fieldset>
    <fieldset id="fs_weekly" class="schedule-type weekly"><legend>Weekly Settings</legend>
      <label>On these days...</label>
      <br />
      <br />
      <table class=\"tftable\"  border="0" id="table_weekly" class="form-table">
        <tr><td><input type="checkbox" name="check_weekly" value="mon" <?php CheckWeekMonth($wk_days,"mon"); ?> />Monday</td>
            <td><input type="checkbox" name="check_weekly" value="tue" <?php CheckWeekMonth($wk_days,"tue"); ?> />Tuesday</td>
            <td><input type="checkbox" name="check_weekly" value="wed" <?php CheckWeekMonth($wk_days,"wed"); ?> />Wednesday</td>
            <td><input type="checkbox" name="check_weekly" value="thu" <?php CheckWeekMonth($wk_days,"thu"); ?> />Thursday</td>
        </tr><tr>
            <td><input type="checkbox" name="check_weekly" value="fri" <?php CheckWeekMonth($wk_days,"fri"); ?> />Friday</td>
            <td><input type="checkbox" name="check_weekly" value="sat" <?php CheckWeekMonth($wk_days,"sat"); ?> />Saturday</td>
            <td><input type="checkbox" name="check_weekly" value="sun" <?php CheckWeekMonth($wk_days,"sun"); ?> />Sunday</td>
        </tr>
      </table>
    </fieldset>
    <fieldset id="fs_monthly" class="schedule-type monthly"><legend>Monthly Settings</legend>
      <label for="select_monthly_day">This day of the month</label>
      <select size="1" id="select_monthly_day" name="select_monthly_day">
        <?php Get_Select_Options("1","31",$cfg[$id]['month_day']); ?>
      </select><br /><br />
      <label>On these months...</label><br />
      <br />
      <table class=\"tftable\"  border="0" class="form-table" id="table_monthly">
        <tr><td><input type="checkbox" name="check_monthly" value="jan" <?php CheckWeekMonth($months,"jan"); ?> />January</td>
            <td><input type="checkbox" name="check_monthly" value="feb" <?php CheckWeekMonth($months,"feb"); ?> />February</td>
            <td><input type="checkbox" name="check_monthly" value="mar" <?php CheckWeekMonth($months,"mar"); ?> />March</td>
            <td><input type="checkbox" name="check_monthly" value="apr" <?php CheckWeekMonth($months,"apr"); ?> />April</td>
        </tr><tr>
            <td><input type="checkbox" name="check_monthly" value="may" <?php CheckWeekMonth($months,"may"); ?> />May</td>
            <td><input type="checkbox" name="check_monthly" value="jun" <?php CheckWeekMonth($months,"jun"); ?> />June</td>
            <td><input type="checkbox" name="check_monthly" value="jul" <?php CheckWeekMonth($months,"jul"); ?> />July</td>
            <td><input type="checkbox" name="check_monthly" value="aug" <?php CheckWeekMonth($months,"aug"); ?> />August</td>
        </tr><tr>
            <td><input type="checkbox" name="check_monthly" value="sep" <?php CheckWeekMonth($months,"sep"); ?> />September</td>
            <td><input type="checkbox" name="check_monthly" value="oct" <?php CheckWeekMonth($months,"oct"); ?> />October</td>
            <td><input type="checkbox" name="check_monthly" value="nov" <?php CheckWeekMonth($months,"nov"); ?> />November</td>
            <td><input type="checkbox" name="check_monthly" value="dec" <?php CheckWeekMonth($months,"dec"); ?> />December</td>
        </tr>
      </table>
    </fieldset>
    <fieldset id="fs_email" class="schedule-email"><legend>Email Settings</legend>
      <label for="input_email_subject">Email Subject Line</label>
      <input type="text" size="20" value="<?php echo $cfg[$id]['email_subject'] ?>" id="input_email_subject" name="input_email_subject"/>
      <br />
      <label for="input_email_replyto">Reply-To Email Address</label>
      <input type="text" size="20" value="<?php echo $cfg[$id]['email_replyto'] ?>"  id="input_email_replyto" name="input_email_replyto"/>
      <br />
      <label for="input_email_to">
	    <a href="#" title="<?php echo $tooltips["email_list"] ?>" class="tooltip">[?]</a>Email To
      </label>
      <input type="text" size="20" value="<?php echo $cfg[$id]['email_list'] ?>"  id="input_email_list" name="input_email_list"/>
      <br />
      <label for="select_email_logo">Email Header Logo</label>
        <?php get_file_list('./emails/images','select_email_logo', $cfg[$id]['email_logo']); ?>
      <br />
      <label for="select_tt_html">Email Template File</label>
        <?php get_file_list('./emails','select_email_template', $cfg[$id]['email_template']); ?>
    </fieldset>
    <br />
      <div class="submit-push"></div>
      <input value="Submit" type="submit"/>
      <br />
      <span id="form_result_fail"></span>
  </form>
  <?php
    } else {
      echo "No such schedule found.";
    }
  ?>
  <span id="form_result_fail"></span>
</div>
</td>
<?php  ?>
</body>
</html>
