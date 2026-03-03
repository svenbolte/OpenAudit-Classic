<?php 
  $page      = "admin";

  require "include.php";
  require "include_audit_functions.php";

  // Set tooltip values for some configuration options that need an explanation
  $tooltips = array(
    "unc_path" =>  "The network path to the audit.vbs file from the scripts directory.<br />"
                  ." This should be put on a network share that the account specified in<br /> "
                  ."'Audit Credentials' has access to",
    "uuid"     =>  "What to use as the Unique Identifier for the audited computers.<br />"
                  ."In most cases, the default of UUID will work fine",
  );

  $cfg = GetAuditConfigurationsFromDb();

  /* The tables that can be shown for MySQL queries */
  $tables = array(
    'network_card' , 'scheduled_task' , 'usb',
    'software'     , 'system'         , 'motherboard',
    'processor'    , 'service'        , 'sound',
    'video'
  );

  $id = ( isset($_GET['config_id']) ) ? $_GET['config_id'] : '0';
  $ip = explode(".", $cfg[$id]['ip_start'] );

  $opt_logging = ( !is_null($cfg[$id]) ) ? $cfg[$id]['enable_logging']     : '1';
  $opt_tcpsyn  = ( !is_null($cfg[$id]) ) ? $cfg[$id]['nmap_tcp_scan']      : '1';
  $opt_udp     = ( !is_null($cfg[$id]) ) ? $cfg[$id]['nmap_udp_scan']      : '1';
  $opt_service = ( !is_null($cfg[$id]) ) ? $cfg[$id]['nmap_detect_service']: '1';
  $nmap_int    = ( !is_null($cfg[$id]) ) ? $cfg[$id]['nmap_intensity']      : '7';
  $opt_winsoft = ( !is_null($cfg[$id]) ) ? $cfg[$id]['windows_software']   : '1';
  $opt_wait    = ( !is_null($cfg[$id]) ) ? $cfg[$id]['wait_time'] / 60     : '10';
  $opt_max     = ( !is_null($cfg[$id]) ) ? $cfg[$id]['max_audits']         : '10';
  $ldap_srv    = ( !is_null($cfg[$id]) ) ? $cfg[$id]['ldap_server']        : 'hostname';
  $ldap_path   = ( !is_null($cfg[$id]) ) ? $cfg[$id]['ldap_path']          : 'DC=mydomain,DC=com';
  $ldap_page   = ( !is_null($cfg[$id]) ) ? $cfg[$id]['ldap_page']          : '1000';
  $vbs_path    = ( !is_null($cfg[$id]) ) ? $cfg[$id]['vbs_path']           : '//server/share/audit.vbs';
  $cmd_int     = ( !is_null($cfg[$id]) ) ? $cfg[$id]['command_interact']   : '0';
  $ldap_user   = ( !is_null($cfg[$id]) ) ? $cfg[$id]['ldap_user']          : 'user@domain.com';
  $ldap_pass   = ( !is_null($cfg[$id]) ) ? $cfg[$id]['ldap_pass']          : 'password';
  $audit_user  = ( !is_null($cfg[$id]) ) ? $cfg[$id]['audit_user']         : 'user@domain.com';
  $audit_pass  = ( !is_null($cfg[$id]) ) ? $cfg[$id]['audit_pass']         : 'password';
  $audit_local = ( !is_null($cfg[$id]) ) ? $cfg[$id]['local_user']         : '0';
  $f_case      = ( !is_null($cfg[$id]) ) ? $cfg[$id]['filter_case']        : '1';
  $f_inverse   = ( !is_null($cfg[$id]) ) ? $cfg[$id]['filter_inverse']     : '0';

  $l_software_list    = ( !is_null($cfg[$id]) ) ? $cfg[$id]['linux_software_list'] : '0';
  $software_list_only = ( !is_null($cfg[$id]) ) ? $cfg[$id]['software_list_only']  : '0';
  $form_action        = ( !is_null($cfg[$id]) ) ? 'edit' : 'add' ; 

  $head = 
    ( isset($_GET['config_id']) ) ?
    "Editing Configuration: {$cfg[$id]['name']}" :
    'Add a Configuration';

  $ldap_connections = (is_array(GetLdapConnectionsFromDb())) ? '1' : '0';
?>
<link media="screen" rel="stylesheet" type="text/css" href="audit_config.css"/>
<script type='text/javascript' src="javascript/audit_config.js"></script>
<script type='text/javascript' src="javascript/mysqli_query.js"></script>
<script type='text/javascript' src="javascript/async_alerts.js"></script>
<td valign="top">
<div class="main_each">
  <div class="form-result"><span id="form_result_success"></span></div>
  <?php
    /* Check if the config exists. Do not show the form if none exists */
    if ((isset($_GET['config_id']) && !is_null($cfg[$id])) || !isset($_GET['config_id'])) {
  ?>
  <div class="submit-push">&nbsp;</div>
  <div class="header"><?php echo $head ?></div>
  <br /><br />
  <form action="javascript:SubmitForm('config','<?php echo $form_action ?>','<?php echo $_GET['config_id']; ?>');" method="post" id="form_config">
    <fieldset><legend>General Settings</legend>
      <label>Name</label>
        <input type="txt" size="20" id="input_name" name="input_name" value="<?php echo $cfg[$id]['name']; ?>"/>
      <br />
      <label>Audit Action</label>
        <select size="1" onChange="SwitchAction(this)" id="select_action" name="select_action">
          <option value="nothing">Select Audit Action</option>
          <option value="nothing">-------</option>
          <option value="pc" <?php if($cfg[$id]['action']=="pc"){ echo "SELECTED"; } ?> >Computer Audit</option>
          <option value="nmap" <?php if($cfg[$id]['action']=="nmap"){ echo "SELECTED"; } ?> >Port Scan (NMAP)</option>
          <option value="pc_nmap" <?php if($cfg[$id]['action']=="pc_nmap"){ echo "SELECTED"; } ?> >Computer Audit and Port Scan</option>
          <option value="command"<?php if($cfg[$id]['action']=="command"){ echo "SELECTED"; } ?> >Remote Command</option>
        </select>
      <br />
      <label>Audit Type</label>
        <select size="1" onChange="SwitchConfig(this)" id="select_audit" name="select_audit">
          <option value="nothing">Select Audit Type</option>
          <option value="nothing">-------</option>
          <option value="list" <?php if($cfg[$id]['type']=="list"){ echo "SELECTED"; } ?> >Computer List</option>
          <option value="domain" <?php if($cfg[$id]['type']=="domain"){ echo "SELECTED"; } ?> >Domain</option>
          <option value="iprange" <?php if($cfg[$id]['type']=="iprange"){ echo "SELECTED"; } ?> >IP Range</option>
          <option value="mysql" <?php if($cfg[$id]['type']=="mysql"){ echo "SELECTED"; } ?> >MySQL Query</option>
        </select>
      <br />
      <label>OS Type</label>
        <select size="1" onChange="SwitchOS(this)" id="select_os" name="select_os" class="pc command">
          <option value="nothing">Select OS Type</option>
          <option value="nothing">-------</option>
          <option value="windows" <?php if ( $cfg[$id]['os'] == "windows" ) { echo "SELECTED"; } ?> >Windows</option>
        </select>
      <br />
      <label>Simultaneous Audits</label>
        <input type="text" size="1" value="<?php echo $opt_max ?>" id="input_max_audits" name="input_max_audits"/><br />
      <label>Kill scripts running longer than</label>
        <input type="text" size="1" value="<?php echo $opt_wait ?>" id="input_wait_time" name="input_wait_time"/>&nbsp;&nbsp;<strong>minutes</strong>
      <br />
      <label>Enable Logging</label>
       <input type="checkbox" size="20" id="check_log_enable" name="check_log_enable" onclick="ToggleLogging(this)" <?php if($opt_logging){ echo "CHECKED"; } ?>/>
     <br />
     <input type="hidden" value="<?php echo $_GET['config_id']; ?>" id="config_id"/>
    </fieldset>
    <fieldset id="fs_auth" class="pc command pc_nmap audit-action"><legend>Audit Credentials</legend>
      <?php 
        if ( $ldap_connections ) { echo "<label>Use LDAP Connection</label>"; }
          else { echo "<label>No LDAP Connections Found</label>"; }

        $select = "select_audit_cred";
        $db=GetOpenAuditDbConnection();;
        Get_LDAP_Connections($select,$cfg[$id]['audit_conn']); ?>
      <?php 
        if ( $ldap_connections ) {
          echo "<br /><br />";
          echo "<label>Or Manually Enter Credentials...</label>";
        }
        else {
          echo "<br />";
          echo "<label>Manually Enter Credentials</label>";
        }
      ?>
      <br /><br />
      <br />
      <label>Username</label>
        <input type="text" id="input_cred_user" name="input_cred_user" size="20" value="<?php echo $audit_user ?>"/>
      <br />
      <label>Password</label>
        <input type="password" id="input_cred_pass" name="input_cred_pass" size="20" value="<?php echo $audit_pass ?>"/>
      <br />
      <label>This is a local account</label>
        <input type="checkbox" size="20" id="check_cred_local" name="check_cred_local" <?php if($audit_local){ echo "CHECKED"; } ?>/>
      <br /><br />
    </fieldset>
    <fieldset id="fs_nmap" class="nmap pc_nmap audit-action"><legend>NMAP Settings</legend>
      <label>TCP SYN Scan</label>
        <input type="checkbox" size="20" id="check_nmap_tcp_syn" name="check_nmap_tcp_syn" <?php if($opt_tcpsyn){ echo "CHECKED"; } ?>/>
      <br /><br />
      <label>UDP Scan</label>
        <input type="checkbox" size="20" id="check_nmap_udp" name="check_nmap_udp" <?php if($opt_udp){ echo "CHECKED"; } ?>/>
      <br /><br />
      <label>Service Version Detection</label>
        <input type="checkbox" size="20" id="check_nmap_srv" name="check_nmap_srv" <?php if ($opt_service) { echo "CHECKED"; } ?>/>
      <br /><br />
      <label>Intensity</label>
      <select size="1" id="select_nmap_intensity" name="select_nmap_intensity">
          <option value="0" <?php if ( $nmap_int == 0 ) { echo "SELECTED"; } ?> >0 - Less Accurate, Shorter</option>
          <option value="1" <?php if ( $nmap_int == 1 ) { echo "SELECTED"; } ?> >1</option>
          <option value="2" <?php if ( $nmap_int == 2 ) { echo "SELECTED"; } ?> >2</option>
          <option value="3" <?php if ( $nmap_int == 3 ) { echo "SELECTED"; } ?> >3</option>
          <option value="4" <?php if ( $nmap_int == 4 ) { echo "SELECTED"; } ?> >4</option>
          <option value="5" <?php if ( $nmap_int == 5 ) { echo "SELECTED"; } ?> >5</option>
          <option value="6" <?php if ( $nmap_int == 6 ) { echo "SELECTED"; } ?> >6</option>
          <option value="7" <?php if ( $nmap_int == 7 ) { echo "SELECTED"; } ?> >7 - Recommended</option>
          <option value="8" <?php if ( $nmap_int == 8 ) { echo "SELECTED"; } ?> >8</option>
          <option value="9" <?php if ( $nmap_int == 9 ) { echo "SELECTED"; } ?> >9 - Most Accurate, Very Long</option>
        </select>
      <br />
      <label>NMAP Path (Optional)</label>
        <input type="text" size="20" name="input_nmap_path" value="<?php echo $cfg[$id]['nmap_path']; ?>" id="input_nmap_path"/>
      <br />
      <label>Submit Results To (Optional)</label>
        <input type="text" size="30" name="input_nmap_url" value="<?php echo $cfg[$id]['nmap_url']; ?>" id="input_nmap_url"/>
      <br />
      <?php
        /* Only show tests if editing the page */ 
        if ( isset($_GET['config_id']) ) { ?>
        <label><input value="Test NMAP" id="test_nmap" type="button" onclick="TestResult(this,'nmap')" /></label>
        <div id="nmap_result"><br /><br />Save First!</div>
      <?php } ?>
    </fieldset>
    <fieldset id="fs_command" class="command audit-action"><legend>Remote Command</legend>
      <label>Desktop Interaction (Windows only)</label>
        <input type="checkbox" size="20" name="check_command_interact" id="check_command_interact" <?php if($cmd_int){ echo "CHECKED"; } ?>/>
      <br /><br />
      <br />
      <center><strong>List of commands to run. One command per line.</strong></center>
      <br /> <br />
      <center><textarea cols="50" rows="10" name="text_commands" id="text_commands"><?php echo $cfg[$id]['command_list']; ?></textarea></center><br /><br />
      <br /> 
      <center><strong>Run these commands. In order from top to bottom.</strong></center>
      <br />
      <label></label>
    <?php 
      $sql    = "SELECT * FROM audit_commands";
      $result = @mysqli_query($db,$sql);
      if ( @mysqli_num_rows($result) != 0 ) {
        echo "<div id=\"commandContainer\">
                <div id=\"sortButtons\">
                  <div><img src=\"images/up.png\" onclick=\"boxUp()\" class=\"sort\"/></div>
                  <div><img src=\"images/down.png\" onclick=\"boxDown()\" class=\"sort\"/></div>
                </div>";
        echo "<div id=\"DragContainer\">";
                @Get_Commands($db,$cfg[$id]['command_ids']);
        echo "</div>";
      }
      else {
        echo "<b>No commands found in DB. Add some <a href=\"audit_commands.php\">here</a></b>.";
      }
    ?>
      <br />
    </fieldset>
    <fieldset id="fs_ldap" class="domain audit-type"><legend>LDAP Settings</legend>
      <?php 
        $ldap_label = ( $ldap_connections ) ? "Use LDAP Connection" : "No LDAP Connections Found";
        echo "<label>$ldap_label</label>";

        $select = "select_ldap_cred";
        Get_LDAP_Connections($select,$cfg[$id]['ldap_conn']); ?>
      <?php 
        if ( $ldap_connections ) {
          echo "<br /><br />";
          echo "<label>Or Manually Enter Credentials...</label>";
        }
        else {
          echo "<br />";
          echo "<label>Manually Enter Credentials</label>";
        }
      ?>
      <br /><br /><br />
      <label>Username</label>
        <input type="text" size="20" value="<?php echo $ldap_user ?>" id="input_ldap_user" name="input_ldap_user"/>
      <br />
      <label>Password</label>
        <input type="password" size="20" id="input_ldap_pass" name="input_ldap_pass" value="<?php echo $ldap_pass ?>"/>
      <br />
      <label>LDAP Server</label>
        <input type="text" size="20" value="<?php echo $ldap_srv ?>" id="input_ldap_server" name="input_ldap_server"/>
      <br />
      <label>LDAP Path</label>
        <input type="text" size="20" value="<?php echo $ldap_path ?>" id="input_ldap_path" name="input_ldap_path"/>
      <br />
      <label>LDAP Page Size</label>
        <input type="text" size="20" value="<?php echo $ldap_page ?>" id="input_ldap_page" name="input_ldap_page"/>
      <br /><br />
      <label><i>Query Filter...</i></label><br />
      <br /><br />
      <label>Perl Regex Filter</label>
        <input type="text" size="20" value="<?php echo $cfg[$id]['filter'] ?>" id="input_filter" name="input_filter"/>
      <br />
      <label>Case Insensitive</label>
        <input type="checkbox" size="20" id="check_filter_case" name="check_filter_case" <?php if($f_case){ echo "CHECKED"; } ?> />
      <br /><br />
      <label>Non-Matching Results Only</label>
        <input type="checkbox" size="20" id="check_filter_inverse" name="check_filter_inverse" <?php if($f_inverse) { echo "CHECKED"; } ?> />
      <br /><br />
      <?php
        /* Only show tests if editing the page */ 
        if ( isset($_GET['config_id']) ) { ?>
        <label><input type="button" value="Test LDAP" id="test_ldap" onclick="TestResult(this,'ldap')" /></label>
        <div id="ldap_result"><br /><br />Save first!</div>
      <?php } ?>
    </fieldset>
    <fieldset id="fs_list" class="list audit-type"><legend>Computer List</legend>
     <center><strong>A list of computers, one computer per line</strong></center>
       <br />
     <center><textarea cols="50" rows="10" name="text_pc_list" id="text_pc_list"><?php echo $cfg[$id]['pc_list']; ?></textarea></center>
    </fieldset>
    <fieldset id="fs_range" class="iprange audit-type"><legend>IP Range</legend>
      <label>IP Start</label>
        <input type="text" name="start_ip_1" id="start_ip_1" size="2" maxlength="3" value="<?php echo $ip[0]; ?>" onChange="IpCopy(this,'1')"/>&nbsp;.&nbsp;<input type="text" name="start_ip_2" id="start_ip_2" size="2" value="<?php echo $ip[1]; ?>" onChange="IpCopy(this,2)"/>&nbsp;.&nbsp;<input type="text" name="start_ip_3" id="start_ip_3" size="2" value="<?php echo $ip[2]; ?>" onChange="IpCopy(this,3)"/>&nbsp;.&nbsp;<input type="text" name="start_ip_4" id="start_ip_4" size="2" value="<?php echo $ip[3]; ?>" />
      <br />
      <label>IP End</label>
        <input type="text" name="end_ip_1" id="end_ip_1" value="<?php echo $ip[0]; ?>" size="2"/>&nbsp;.&nbsp;<input type="text" name="end_ip_2" id="end_ip_2" size="2" value="<?php echo $ip[1]; ?>" />&nbsp;.&nbsp;<input type="text" name="end_ip_3" id="end_ip_3" size="2" value="<?php echo $ip[2]; ?>" />&nbsp;.&nbsp;<input type="text" name="end_ip_4" id="end_ip_4" size="2" value="<?php if ( $ip_end != 0 ) { echo $ip_end; } ?>" />
      <br />
    </fieldset>
    <fieldset id="fs_mysql" class="mysql audit-type"><legend>MySQL Query</legend>
      <label>Table</label>
      <select class="mysql" id="mysqli_tables" onChange="setMysqlFields(this,'select_fields')">
                <option value="nothing" SELECTED>Select MySQL Table</option>
                <option value="nothing">-------</option>
      <?php foreach ( $tables as $table ) { echo "<option value=\"$table\">$table</option>"; } ?>
      </select>
      <br />
      <label>Field</label>
      <div id="select_fields">
        <select class="mysql" id="fields_nothing">
          <option value="nothing" SELECTED>Select Field</option>
          <option value="nothing">-------</option>
        </select>
      </div>
      <label>Search Method</label>
      <select class="mysql" id="fields_sort">
        <option value="contains" SELECTED>Contains</option>
        <option value="begins">Begins With</option>
        <option value="ends">Ends With</option>
        <option value="equals">Equals</option>
        <option value="notequal">Does Not Equal</option>
        <option value="notcontain">Does Not Contain</option>
      </select>
      <br />
      <label>Search for Data</label>
      <input class="mysql" size="15" type="text" id="input_field_value">
      <img src="images/add.png" class="addbutton" onclick="addToQuery()">
      <br />
      <br />
      <center>
      <table class=\"tftable\"  id="mysqli_query_options">
        <?php Get_mysqli_Queries($db,$id); ?>
      </table>
      </center>
      <br /><br />
      <label><i>Query Filter...</i></label><br />
      <br /><br />
      <label>Perl Regex Filter</label>
        <input type="text" size="20" value="<?php echo $cfg[$id]['filter']; ?>" id="input_filter" name="input_filter"/>
      <br />
      <label>Case Insensitive</label>
        <input type="checkbox" size="20" id="check_filter_case" name="check_filter_case" <?php if($f_case){ echo "CHECKED"; } ?> />
      <br /><br />
      <label>Non-Matching Results Only</label>
        <input type="checkbox" size="20" id="check_filter_inverse" name="check_filter_inverse" <?php if($f_inverse ) { echo "CHECKED"; } ?> />
      <br /><br />
      <?php
        /* Only show tests if editing the page */ 
        if ( isset($_GET['config_id']) ) { ?>
        <label><input type="button" value="Test MySQL" id="test_mysql" onclick="TestResult(this,'mysql')" /></label>
        <div id="mysqli_result"><br /><br />Save first!</div>
      <?php } ?>

    </fieldset>
    <fieldset id="fs_windows" class="windows os"><legend>Windows Audit Settings</legend>
      <label>
	    <a href="#" title="<?php echo $tooltips["unc_path"] ?>" class="tooltip">[?]</a>Audit.vbs UNC Path
      </label>
        <input type="text" size="30" value="<?php echo $vbs_path ?>" name="input_vbs" id="input_vbs"/>
      <br />
      <label>Submit Results To (Optional)</label>
        <input type="text" size="30" value="<?php echo $cfg[$id]['windows_url']; ?>" name="input_windows_url" id="input_windows_url"/>
      <br />
      <label>Winexe/RemCom.exe Path (Optional)</label>
        <input type="text" size="30" value="<?php echo $cfg[$id]["remote_command_path"]; ?>" name="input_com_path" id="input_com_path"/>
      <br />
      <label>
	    <a href="#" title="<?php echo $tooltips["uuid"] ?>" class="tooltip">[?]</a>UUID Type
      </label>
        <select id="select_windows_uuid" name="select_windows_uuid">
          <option value="uuid" <?php if($cfg[$id]["windows_uuid"]=="uuid"){ echo "SELECTED"; } ?> >UUID</option>
          <option value="mac"  <?php if($cfg[$id]["windows_uuid"]=="mac"){ echo "SELECTED"; } ?> >MAC Address</option>
          <option value="name" <?php if($cfg[$id]["windows_uuid"]=="name"){ echo "SELECTED"; } ?> >System Name</option>
        </select>
      <br />
      <label>Audit Software</label>
        <input type="checkbox" size="20" id="check_windows_software" name="check_windows_software" <?php if($opt_winsoft) { echo "CHECKED"; } ?>/>
      <br />
    </fieldset>
    <fieldset id="fs_linux" class="linux os"><legend>Linux Audit Settings</legend>
      <label>Submit Results To (Optional)</label>
        <input type="text" size="30" value="<?php echo $cfg[$id]['linux_url'] ?>" name="input_linux_url" id="input_linux_url"/>
      <br />
      <label>Audit Software</label>
        <input type="checkbox" size="20" name="check_linux_software" id="check_linux_software" <?php if($linux_software){ echo "CHECKED"; } ?>/>
      <br /><br />
      <label>Only check these packages</label>
        <input type="checkbox" size="20" name="check_linux_software_list" id="check_linux_software_list" <?php if($software_list_only){ echo "CHECKED"; } ?> /><br />
      <br /><br />
       <center><textarea cols="50" rows="10" name="text_linux_software" id="text_linux_software"><?php echo $l_software_list; ?></textarea></center>
    </fieldset>
      <div class="submit-push"></div>
      <input value="Submit" type="submit"/>
      <br />
      <span id="form_result_fail"></span>
  </form>
      <?php
        }
        else {
          echo "<p>No such configuration found.</p>";
        }
    ?>
</div>
</td>
<?php  ?>
<div id="dialog-test-results">
  <div id="dialog-test-text"></div>
</div>
</body>
</html>
