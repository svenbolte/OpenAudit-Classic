/**********************************************************************************************************
Function Name:
  deleteConfigRow
Description:
  Delete an audit configuration. 
Arguments:
  selected    [IN] [OBJECT]  The image object that was clicked
  config_id   [IN] [INTEGER] The configuration ID number to delete
Returns:  None
**********************************************************************************************************/
function deleteConfigRow(selected,config_id) {
  var DeleteConfiguration = new XmlRequestor('audit_manage_ajax.php?action=delete_config&config_id=' + config_id);

  if ( DeleteConfiguration.GetValue("result") == 'true' ) {
    var row_to_remove = selected.parentNode.parentNode.rowIndex;
    document.getElementById('config-table').deleteRow(row_to_remove);
    if ( document.getElementById('config-table').rows.length == '2' ) { 
      document.getElementById('cfg-holder').innerHTML = 
        "<p class=\"no-table\">No audit configurations found." +
        "  <a href=\"audit_configuration.php\">Add one</a></p>";
    }
  }

  alert(DeleteConfiguration.GetValue("message"));
}

/**********************************************************************************************************
Function Name:
  confirmDeleteConfig
Description:
  Confirm the deletion of an audit configuration
Arguments:
  selected    [IN] [OBJECT]  The image object that was clicked
  config_id   [IN] [INTEGER] The config ID number to delete
  config_name [IN] [STRING]  The config name to delete
Returns:  None
**********************************************************************************************************/
function confirmDeleteConfig(selected,config_id,config_name) {
  $('#confirm-dialog').dialog('option', 'title', config_name);
  $('#confirm-text').html('Delete this configuration?');
  $('#confirm-dialog').dialog('option', 'buttons', {
    "Delete": function() { deleteConfigRow(selected,config_id); $(this).dialog("close"); },
    "Cancel": function() { $(this).dialog("close"); }
  }); 
  $('#confirm-dialog').dialog('open');
}

/**********************************************************************************************************
Function Name:
  deleteSchedRow
Description:
  Delete an audit schedule. 
Arguments:
  selected   [IN] [OBJECT]  The image object that was clicked
  sched_id   [IN] [INTEGER] The schedule ID number to delete
Returns:  None
**********************************************************************************************************/
function deleteSchedRow(selected,sched_id) {
  var DeleteSchedule = new XmlRequestor('audit_manage_ajax.php?action=delete_schedule&schedule_id=' + sched_id);
  if ( DeleteSchedule.GetValue("result") == 'true' ) {
    var row_to_remove = selected.parentNode.parentNode.rowIndex;
    document.getElementById('sched-table').deleteRow(row_to_remove);
    if ( document.getElementById('sched-table').rows.length == '2' ) { 
      document.getElementById('sched-holder').innerHTML = 
        "<p class=\"no-table\">No audit schedules found." +
        "  <a href=\"audit_schedule.php\">Add one</a></p>";
    }
  }

  alert(DeleteSchedule.GetValue("message"));
}

/**********************************************************************************************************
Function Name:
  confirmDeleteSchedule
Description:
  Confirm the deletion of an audit schedule
Arguments:
  selected   [IN] [OBJECT]  The image object that was clicked
  sched_id   [IN] [INTEGER] The schedule ID number to delete
  sched_name [IN] [STRING] The schedule name to delete
Returns:  None
**********************************************************************************************************/
function confirmDeleteSchedule(selected,sched_id,sched_name) {
  $('#confirm-dialog').dialog('option', 'title', sched_name);
  $('#confirm-text').html('Delete this schedule?');
  $('#confirm-dialog').dialog('option', 'buttons', {
    "Delete": function() { deleteSchedRow(selected,sched_id); $(this).dialog("close"); },
    "Cancel": function() { $(this).dialog("close"); }
  }); 
  $('#confirm-dialog').dialog('open');
}

/**********************************************************************************************************
Function Name:
  auditConfigNow
Description:
  Run the specified audit configuration from the audit_manage.php page
Arguments:
  config_id   [IN] [INTEGER] The configuration ID number to run
Returns:  None
**********************************************************************************************************/
function auditConfigNow(config_id) {
  var RunAudit = new XmlRequestor('audit_manage_ajax.php?action=run_config&config_id=' + config_id);
  alert(RunAudit.GetValue("message"));
}

/**********************************************************************************************************
Function Name:
  confirmRunConfig
Description:
  Confirm if they really want to run the configuration
Arguments:
  config_id   [IN] [INTEGER] The config ID number to run
  config_name [IN] [STRING] The config name to run
Returns:  None
**********************************************************************************************************/
function confirmRunConfig(config_id,config_name) {
  $('#confirm-dialog').dialog('option', 'title', config_name);
  $('#confirm-text').html('Run this configuration now?');
  $('#confirm-dialog').dialog('option', 'buttons', {
    "Run": function() { auditConfigNow(config_id); $(this).dialog("close"); },
    "Cancel": function() { $(this).dialog("close"); }
  }); 
  $('#confirm-dialog').dialog('open');
}

/**********************************************************************************************************
Function Name:
  toggleSchedule
Description:
  Turn off/on the selected schedule
Arguments:
  selected  [IN] [OBJECT]  The configuration ID number to run
  sched_id  [IN] [INTEGER] The name of the configuration
Returns:  None
**********************************************************************************************************/
function toggleSchedule(selected,sched_id) {
  var ToggleSchedule = new XmlRequestor('audit_manage_ajax.php?action=toggle_schedule&schedule_id=' + sched_id);
  if(ToggleSchedule.GetValue("action") == 'Activated') {
    selected.className = (ToggleSchedule.GetValue("result") == 'true') ? "fa fa-check-circle" : "fa fa-times-circle";
  }
  else {
    selected.className = (ToggleSchedule.GetValue("result") == 'true') ? "fa fa-check-circle" : "fa fa-times-circle";
  }
  alert(ToggleSchedule.GetValue("message"));
}

/**********************************************************************************************************
Function Name:
  getCronLog
Description:
  Fetch the most recent entries for the web schedule log
Arguments: None
Returns:  None
**********************************************************************************************************/
function getWsLog() {
  var WsLog=new HttpRequestor('log-box');
  WsLog.send('audit_manage_ajax.php?action=ws_log&row_num=10');
}

/**********************************************************************************************************
Function Name:
  toggleCron
Description:
  Toggle the status of the web-schedule service, show an alert with the result of the action
Arguments: None
Returns:  None
**********************************************************************************************************/
function toggleWsService() {
  $.ajax({
    'url': 'audit_manage_ajax.php', 'type': 'GET', 'data': { 'action' : 'toggle_ws' },
    'beforeSend': function(){ $('#toggle-service').show(); },
    'error': function(){ alert('An Unexpected Error Occured'); },
    'success': function(msg){
      var message, img;
      var xmlResult = $(msg).find('result').text() 
      $('#toggle-service').hide(); 
      if($(msg).find('action').text() == 'start'){
        img     = ( xmlResult == 'true' ) ? "fa-check-circle" : "fa-times-circle"; 
        message = ( xmlResult == 'true' ) ?
          "Started the Web-Schedule service" : "Unable to start the Web-Schedule service";
      }
      else {
        img = ( xmlResult == 'true' || xmlResult == 'pending' ) ? "fa-check-circle" : "fa-times-circle"; 
        if ( xmlResult == 'true'    ) { message = "Stopped the Web-Schedule service";        }
        if ( xmlResult == 'pending' ) { message = "Service will stop on next DB poll";       }
        if ( xmlResult == 'false'   ) { message = "Unable to stop the Web-Schedule service"; }
      }
      $("#ws-status-img").removeClass("fa-check-circle fa-times-circle fa-circle-o").addClass("fa " + img); 
      alert(message);
    }
  });
}

/**********************************************************************************************************
Function Name:
  getWsStatus
Description:
  Called on a set interval to update the image that shows if the web schedule service is running or not
Arguments: None
Returns:  None
**********************************************************************************************************/
function getWsStatus() {
  var WsStatus = new XmlRequestor('audit_manage_ajax.php?action=ws_status');
  var img = ( WsStatus.GetValue("result") == 'running' ) ?  "fa-check-circle" : "fa-times-circle";
  $("#ws-status-img").removeClass("fa-check-circle fa-times-circle fa-circle-o").addClass("fa " + img); 
}

/**********************************************************************************************************
Function Name:
  loadWsUpdate
Description:
  This is ran when the page loads, it sets an interval for the page to be updated
Arguments: None
Returns:  None
**********************************************************************************************************/
function loadWsUpdate() { updatePage(); setInterval(updatePage,5000); }

/**********************************************************************************************************
Function Name:
  updatePage
Description:
  Runs at an interval specified by loadCron() to call some functions that update the page
Arguments: None
Returns:  None
**********************************************************************************************************/
function updatePage() {
  getWsStatus();
  if($("#log-dialog").dialog('isOpen')) { getWsLog(); }
}

/**********************************************************************************************************

  Jquery 'ready' function - things to execute when the DOM is ready

**********************************************************************************************************/

$(document).ready(function() {

  loadWsUpdate();

  $("#log-dialog").dialog({
    width: 410,
    bgiframe: true,
    draggable: true,
    resizable: false,
    autoOpen: false,
    title: "Web-Schedule Service Log",
    position: ['center','middle']
  });

  $("#log-dialog-open").click(function() {
    getWsLog();
    $("#log-dialog").dialog('open');
    return false;
  });

  $("#confirm-dialog").dialog({
    bgiframe: true,
    resizable: false,
    draggable: false,
    autoOpen: false,
    modal: true,
    overlay: {
      backgroundColor: '#000',
      opacity: 0.5
    }
  });
});
