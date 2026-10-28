<?php
/**********************************************************************************************************

Description:
    These functions are used on the audit_*.php pages for various reasons, mostly to separate HTML from
    code a bit. Others are used for things specific to the web scheduling and audits
    
**********************************************************************************************************/

include_once "application_class.php";
include_once "include_functions.php";

/**********************************************************************************************************
Function Name:
  Get_Commands
Description:
  Construct the HTML DIV elements for each command 
Arguments:
  $db       [IN] [RESOURCE] The MySQL connection resource to use
  $cmd_list [IN] [ARRAY]    The commands that should be marked as selected
Returns:    
  Nothing to return
**********************************************************************************************************/
function Get_Commands($db,$cmd_list) {
  $sql    = "SELECT * FROM audit_commands";
  $result = mysqli_query($db,$sql);

  // Needed to stop onlick from parent div
  $js = "if (typeof event.stopPropagation != 'undefined') {
           event.stopPropagation();
         }
         if (typeof event.cancelBubble != 'undefined') {
           event.cancelBubble = true;
         }";

  // No commands in list, just show them all in any order
  if ( empty($cmd_list) ) {
    while ( $myrow = mysqli_fetch_array($result) ) {
      $id      = $myrow['audit_cmd_id'];
      $name    = $myrow['audit_cmd_name'];
      $command = $myrow['audit_cmd_command'];
      $checked = ( !empty($cmd_id) AND $cmd_id == $id ) ? 'SELECTED' : '' ;
      if ( empty($name) ) { next; }
      echo "<div class=\"Box\" onclick=\"MakeMovable(this)\" id=\"$id\">
            <input type=\"checkbox\" value=\"$id\" name=\"$name\" onclick=\"$js\">$name</div>";
    }
  }
  else {  // Order the commands from the list so they appear in order
    $cmds_chk = explode(',',$cmd_list);
    foreach  ( $cmds_chk as $id ) {
      $result = mysqli_query($db,$sql);
      while ( $myrow = mysqli_fetch_array($result) ) {
        if ( $myrow['audit_cmd_id'] == $id ) {
          echo "<div class=\"Box\" onclick=\"MakeMovable(this)\" id=\"".$id."\">
                <input type=\"checkbox\" value=\"$id\" name=\"{$myrow['audit_cmd_name']}\" onclick=\"$js\" CHECKED>{$myrow['audit_cmd_name']}</div>";
          break;
        }
      }
    }

    $result = mysqli_query($db,$sql);
    while ( $myrow = mysqli_fetch_array($result) ) {
      $id = $myrow['audit_cmd_id'];
      if ( ! preg_grep("/.*$id.*/",$cmds_chk) ) {
        $name    = $myrow['audit_cmd_name'];
        $command = $myrow['audit_cmd_command'];
        if ( empty($name) ) { next; }
        echo "<div class=\"Box\" onclick=\"MakeMovable(this)\" id=\"$id\">
              <input type=\"checkbox\" value=\"$id\" name=\"$name\" onclick=\"$js\">$name</div>";
      }
    }
  }
}

/**********************************************************************************************************
Function Name:
  get_command_info
Description:
  Construct the table rows for the table that holds the commands on the audit_commands.php page
Arguments:
  $db    [IN] [RESOURCE] The MySQL connection to use
Returns:    
  Nothing to return
**********************************************************************************************************/
function get_command_info($db){
  $sql    = "SELECT * FROM audit_commands";
  $result = mysqli_query($db,$sql);
  if ( mysqli_num_rows($result) != 0 ) {
    echo "<tr id=\"table-cmd-head\">
            <td><center><b>Name</b></center></td>
            <td><center><b>Command</b></center></td>
            <td><center><b>Delete</b></center></td>
          </tr>";
    $count = 0;
    while ( $myrow = mysqli_fetch_array($result) ) {
      $id      = $myrow['audit_cmd_id'];
      $name    = $myrow['audit_cmd_name'];
      $command = $myrow['audit_cmd_command'];
      echo "
        <tr id=\"$id\">
          <td><input type=\"text\" value=\"".$name."\" size=\"20\" id=\"cmdname$count\"/></td>
          <td><input type=\"text\" class=\"command-value\" value=\"".$command."\" id=\"cmd$count\"/></td>
          <td><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></td>
        </tr>
      ";
      $count++;
    }
  }
}

/**********************************************************************************************************
Function Name:
  get_dir_files
Description:
  Given a directory, return a list of files inside it (but not recursively)
Arguments:
  $dir   [IN] [String]  The directory to look at
Returns:    
  [Array] An array of the filenames in the directory, sorted
**********************************************************************************************************/
function get_dir_files($dir) {
  $files = array();
  $fh    = opendir($dir);

  while ( $file = readdir($fh) ) {
    if ( is_file($dir . '/' . $file) ) array_push($files, $file);
  }

  closedir($fh);
  rsort($files);

  return $files;
}

/**********************************************************************************************************
Function Name:
  get_file_list
Description:
  Construct an HTML SELECT element with a list of files from a directory 
Arguments:
  $dir      [IN] [String] The directory to get the file list from
  $id       [IN] [String] The ID/name to give the select element
  $selected [IN] [String] The option that should be selected
Returns:    
  Nothing to return
**********************************************************************************************************/
function get_file_list($dir,$id,$selected) {
  $files = get_dir_files($dir);

  echo "<select id=\"$id\" name=\"$id\">\n
        <option value=\"\" SELECTED>Default</option>\n
        <option value=\"\">---------</option>\n";
  while ( $file = array_pop($files) ) {
    $default = ( !empty($selected) && $file == $selected ) ? 'SELECTED' : '' ;
    echo "<option value=\"$file\" $default>$file</option>\n";
  }
  echo "</select>\n";
}

/**********************************************************************************************************
Function Name:
  Get_mysqli_Queries
Description:
  Contstruct the table rows for the mysql queries table.
Arguments:
  $db     [IN] [RESOURCE] The MySQL resource connection to use
  $cfg_id [IN] [INTEGER]  The configuration ID to get the query rows for
Returns:    
  Nothing to return
**********************************************************************************************************/
function Get_mysqli_Queries($db,$cfg_id) {
  $sql      = "SELECT * FROM mysqli_queries WHERE mysqli_queries_cfg_id = '$cfg_id'";
  $result   = mysqli_query($db,$sql);
  $tables = array(
    'network_card' , 'scheduled_task' , 'usb'        ,
    'software'     , 'system'         , 'motherboard',
    'processor'    , 'service'        , 'sound'      ,
    'video'
  );
  $srt_flds = array(
    'contains'   => 'Contains',
    'begins'     => 'Begins With',
    'ends'       => 'Ends With',
    'equals'     => 'Equals',
    'notequal'   => 'Does Not Equal',
    'notcontain' => 'Does Not Contain'
  );

  if ( $result ) {
    $count = 0;
    while ( $myrow = mysqli_fetch_array($result) ) {
      $id    = $myrow['mysqli_queries_id'];
      $field = $myrow['mysqli_queries_field'];
      $table = $myrow['mysqli_queries_table'];
      $data  = $myrow['mysqli_queries_data'];
      $sort  = $myrow['mysqli_queries_sort'];

      echo "<tr id=\"{$id}\">\n
              <td>\n
                <i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></td>\n
              <td>\n
                <select class=\"mysql\" id=\"qtbl$count\" onChange=\"setFieldSelect(this,'cellfield$count','qfld$count')\">\n";
                foreach ( $tables as $line ) {
                  $selected = ( $line == $table ) ? 'SELECTED' : '';
                  echo "<option value=\"$line\" $selected>$line</option>\n";
                }
      echo "    </select>\n
              </td>\n
              <td id=\"cellfield{$count}\">\n
               <select class=\"mysql\" id=\"qfld{$count}\">\n";
               $fields = Get_mysqli_Fields($db,$table);
                foreach ( $fields as $line ) {
                  $selected = ( $line == $field ) ? 'SELECTED' : '';
                  echo "<option value=\"$line\" $selected>$line</option>\n";
                }
      echo "    </select>\n
              </td>\n
              <td>\n
                <select class=\"mysql\" id=\"qsrt{$count}\">\n";
                foreach ( $srt_flds as $key => $value ) {
                  $selected = ( $key == $sort ) ? 'SELECTED' : '';
                  echo "<option value=\"$key\" $selected>$value</option>\n";
                }
      echo "    </select>\n
              <td><input size=\"15\" class=\"mysql\" id=\"qdata$count\" value=\"$data\"></td>\n
            </tr>\n";
      $count++;
    }
  }
}

/**********************************************************************************************************
Function Name:
  Get_LDAP_Connections
Description:
  Construct an HTML SELECT element that has the names of the available LDAP connections
Arguments:
  $select_name [IN] [String]   The ID for the SELECT element
  $conn_id     [IN] [INTEGER]  The connection that should be selected
Returns:    
  Nothing to return
**********************************************************************************************************/
function Get_LDAP_Connections($select_name,$conn_id) {
  $l = GetLdapConnectionsFromDb();
  if ( is_array($l) ) {
    echo "<select size=\"1\" id=\"$select_name\" name=\"$select_name\" onChange=\"ToggleAuth(this)\">\n";
    echo "<option value=\"nothing\" selected=\"selected\">Select Connection</option>\n";
    echo "<option value=\"nothing\">-------</option>\n";
    foreach($l as $key => $conn) {
      $select = ( !empty($conn_id) AND $conn_id == $key ) ? 'SELECTED' : '' ;
      echo "<option value=\"$key\" $select>{$conn['name']}</option>\n";
    }
    echo "</select>\n";
  } else {
    echo "<select size=\"1\" id=\"$select_name\" name=\"$select_name\" STYLE=\"visibility:hidden\">\n";
    echo "<option value=\"nothing\" SELECTED>None Found</option>\n";
    echo "</select><br /><br />\n";
  }
}

/**********************************************************************************************************
Function Name:
  Get_Audit_Configs
Description:
  Consturct an HTML SELECT element to choose an audit configuration on the audit_schedule.php form
Arguments:
  $config_id [IN] [INTEGER]   The configuration that should be selected
Returns:    
  Nothing to return
**********************************************************************************************************/
function Get_Audit_Configs($config_id) {
  $configs = GetAuditConfigurationsFromDb();
  echo "<select size=\"1\" id=\"select_config\" name=\"select_config\">\n";
  echo "<option value=\"nothing\" selected=\"selected\">Select Audit Config</option>\n";
  echo "<option value=\"nothing\">-------</option>\n";
  if ( !is_null($configs) ) {
    foreach ( $configs as $key => $cfg ) {
      $name = $cfg['name'];
      $select = ( !empty($config_id) AND $config_id == $key ) ? 'SELECTED' : '' ;
      echo "<option value=\"$key\" $select>{$cfg['name']}</option>\n";
    }
  }
  echo "</select>\n";
}

/**********************************************************************************************************
Function Name:
  Get_Select_Options
Description:
  Construct an HTML SELECT element for the time dropdowns on the audit_schedule.php form
Arguments:
  $start    [IN] [INTEGER] The number to begin with
  $end      [IN] [INTEGER] The number to end with
  $selected [IN] [INTEGER] The number that should be selected
Returns:    
  Nothing to return
**********************************************************************************************************/
function Get_Select_Options($start,$end,$selected) {
  while ( $start <= $end ) {
    $value = ( preg_match("/^[0-9]$/", $start) ) ? "0".$start : $start;
    $select = ( !empty($selected) AND $selected == $start ) ? 'SELECTED' : '' ;
    echo "<option value=\"$start\" $select>$value</option>";
    $start++;
  }
}

/**********************************************************************************************************
Function Name:
  Get_Config_Name
Description:
  Given a configuration ID, get the name
Arguments:
  $id   [IN] [INTEGER]  The ID of the configuration
Returns:    
  [String] The audit configuration name
**********************************************************************************************************/
function Get_Config_Name($id) {
  $cfg = GetAuditConfigurationsFromDb();
  return $cfg[$id]['name'];
}

/**********************************************************************************************************
Function Name:
  Get_Manage_Configs
Description:
  Construct the table of audit configurations for the audit_manage.php page
Arguments: None
Returns:    
  Nothing to return
**********************************************************************************************************/
function Get_Manage_Configs() {
  $configs = GetAuditConfigurationsFromDb();
  echo "<div id=\"cfg-holder\">";
  if (!is_null($configs)) {
    echo "<table class=\"tftable\"  id=\"config-table\" summary=\"Audit Configurations\">
      <thead>
    	  <tr>
            <th scope=\"row\" colspan=\"5\"><center>Audit Configurations</center></th>
          </tr>
    	  <tr>
            <th scope=\"col\">Name</th>
            <th scope=\"col\">Action</th>
            <th scope=\"col\">Type</th>
            <th scope=\"col\">Run</th>
            <th scope=\"col\">Delete</th>
          </tr>
      </thead>
      <tbody>";
    foreach ( $configs as $key => $cfg ) {
      $cfg_action = array(
        'pc'      => "PC Audit",
         'nmap'    => "Port Scan",
         'pc_nmap' => "Audit/Port Scan",
         'command' => "Commands"
      );
      $cfg_type = array(
        'iprange' => "IP Range",
        'domain'  => "LDAP",
        'list'    => "PC List",
        'mysql'   => "MySQL"
      );
      $audit_action = $cfg_action[$cfg['action']];
      $audit_type   = $cfg_type[$cfg['type']];
      echo "<tr>
              <td><a href=\"audit_configuration.php?config_id=$key\">{$cfg['name']}</a></td>
              <td>$audit_action</td>
              <td>$audit_type</td>
              <td>&nbsp;&nbsp;&nbsp;<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></td>
              <td>&nbsp;&nbsp;&nbsp;<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></td>
            </tr>";
    }
    echo "</tbody></table>";
  }
  else {
    echo "<p class=\"no-table\">No configurations found.
          <a href=\"audit_configuration.php\">Add one</a></p>";
  }
  echo "</div>";
}

/**********************************************************************************************************
Function Name:
  Get_Manage_Schedules
Description:
  Construct the table of audit schedules for the audit_manage.php page
Arguments:
  $db   [IN] [RESOURCE]  The MySQL resource connection to use
Returns:    
  Nothing to return
**********************************************************************************************************/
function Get_Manage_Schedules() {
  $schedules = GetAuditSchedulesFromDb();
  $type_map = array(
    'hourly'  => "Hourly",
    'weekly'  => "Weekly",
    'monthly' => "Monthly",
    'daily'   => "Daily",
    'crontab' => "Cron Entry"
  );

  echo "<div id=\"sched-holder\">";
  if ( !is_null($schedules) ) {
    echo "<table class=\"tftable\"  id=\"sched-table\" summary=\"Audit Schedules\">
      <thead>
    	  <tr>
            <th scope=\"row\" colspan=\"7\"><center>Audit Schedules</center></th>
          </tr>
    	  <tr>
            <th scope=\"col\">Name</th>
            <th scope=\"col\">Config</th>
            <th scope=\"col\">Type</th>
            <th scope=\"col\">Last Run</th>
            <th scope=\"col\">Next Run</th>
            <th scope=\"col\">Stop/Start</th>
            <th scope=\"col\">Delete</th>
          </tr>
      </thead>
      <tbody>";
    foreach ( $schedules as $key => $cfg ) {
      $config_name  = Get_Config_Name($cfg['config_id']);
      $status_image = ( $cfg['active'] ) ? 'start' :  'stop';
      $run_time = ($cfg['last_run'] == 0) ? 'Never'   : date('D M jS Y h:i:s A',$cfg['last_run']);
      $next_run = ($cfg['next_run'] == 0) ? 'Unknown' : date('D M jS Y h:i:s A',$cfg['next_run']);
      echo "<tr>
              <td><a href=\"audit_schedule.php?sched_id=$key\">{$cfg['name']}</a></td>
              <td>$config_name</td>
              <td>{$type_map[$cfg['type']]}</td>
              <td>$run_time</td>
              <td>$next_run</td>
              <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></td>
              <td>&nbsp;&nbsp;&nbsp;<i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></td>
            </tr>";
    }
    echo "</tbody></table></div>";
  }
  else {
    echo "<p class=\"no-table\">No schedules found. <a href=\"audit_schedule.php\">Add one</a></p>";
  }
  echo "</div>";
}

/**********************************************************************************************************
Function Name:
  Get_mysqli_Fields
Description:
  Given the template varibles, replacements, and filename, return the HTML with the vars in place.
Arguments:
  $db    [IN] [RESOURCE]  MySQL connection resource
  $table [IN] [String]    The table to get fields for
Returns:    
  [Array] The fields from the table, sorted
**********************************************************************************************************/
function Get_mysqli_Fields($db,$table) {
  $result = mysqli_query($db,"SHOW COLUMNS FROM $table",$db);
  $fields = array();
  if (!$result) {
    echo 'Could not run query: ' . mysqli_error($db);
    exit;
  }

  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) { array_push($fields,$row['Field']); };
  }

  sort($fields);

  return $fields;
}

/**********************************************************************************************************
Function Name:
  Get_Audit_Bin
Description:
  Make a best guess about what command/path to execute from the web interface
Arguments:
  None
Returns:    
  [String] The path to the file to use
**********************************************************************************************************/
function Get_Audit_Bin() {
  global $TheApp;
  $bin  = null;
  $wdir = getcwd();
  $cfg  = GetAuditSettingsFromDb();

  if ( $cfg["script_only"] && file_exists('./scripts/audit.pl') ) {
    $bin = ( $TheApp->OS == 'Windows' ) ? "perl \"$wdir\\scripts\\audit.pl\"" : "\"$wdir/scripts/audit.pl\"";
  } 
  elseif ( $TheApp->OS == 'Windows' && file_exists('./scripts/audit.exe') ) {
    $bin = "$wdir\\scripts\\audit.exe";
  }
  elseif ( $TheApp->OS != 'Windows' && file_exists('./scripts/audit') ) {
    $bin = "\"$wdir/scripts/audit\"";
  }
  elseif ( file_exists('./scripts/audit.pl') ) {
    $bin = ( $TheApp->OS == 'Windows' ) ? "perl \"$wdir\\scripts\\audit.pl\"" : "\"$wdir/scripts/audit.pl\"";
  } 

  return $bin;
}

/**********************************************************************************************************
Function Name:
  Verify_Cron_Line
Description:
  Check that a cron entry has correct syntax.
Arguments:
  [String] The cron line to check
Returns:    
  [Integer] The unix timestamp of the next run time. Or null on failure to verify the cron entry
**********************************************************************************************************/
function Verify_Cron_Line($cron_entry) {
    $cron_entry = trim($cron_entry);
		$cron_entry = preg_replace('/[\s]{2,}/', ' ', $cron_entry);

		if ( preg_match('/[^-,* \\d\/]/', $cron_entry) !== 0 || count(explode(' ',$cron_entry)) == 5 ) {
      $audit_bin = Get_Audit_Bin();
      $entry     = escapeshellarg($cron_entry);
      $output    = `$audit_bin --test-cron $entry`;
      return ( !empty($output) ) ? $output : null;
		}
    else {
      return null;
    }
}
?>
