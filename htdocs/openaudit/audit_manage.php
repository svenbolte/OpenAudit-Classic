<?php

$page = "admin";
require "include.php";
require "include_audit_functions.php";

?>
<link media="screen" rel="stylesheet" type="text/css" href="audit_manage.css"/>
<script type='text/javascript' src="javascript/async_alerts.js"></script>
<script type='text/javascript' src="javascript/audit_manage.js"></script>
<td valign="top">
<div class="main_each">
  <p class="header">Manage Schedules and Configurations</p>
  <div id="config-tables">
  <?php
    /* Check if audit settings exist */

    $db  = GetOpenAuditDbConnection();
    $cfg = GetAuditSettingsFromDb();

    if (!is_null($cfg)) {
      ?>
      <p id="log-link-holder"><a href="#" id="log-dialog-open"><img src="images/notes.png"/>View the Web-Schedule Log...</a></p>
      <label>
        Toggle Web-Schedule Status
        <img id="ws-status-img" onClick="toggleWsService(this,'normal')"/>
      </label>
      <span id="toggle-service">
        <img class="busy" src="images/hourglass-busy.gif"/>
        Changing Web-Schedule Status...
      </span>
      <br /><br />
  <?php
      }
      else {
        echo "<p>You need to update your database before using this page</p>";
      }

    Get_Manage_Configs();
    Get_Manage_Schedules();
  ?>
  <br />
  <br />
  </div>
</td>
<?php  ?>
<div id="log-dialog"><div id="log-box"></div></div>
<div id="confirm-dialog">
  <p><span class="ui-icon ui-icon-alert confirm-icon"></span><span id="confirm-text"></span></p>
</div>
</body>
</html>
