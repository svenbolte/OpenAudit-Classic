/**********************************************************************************************************
Function Name:
	ajaxFunction
Description:
	Make the POST request to the request url, set the callback
Arguments:
  url	       [IN] [STRING]   The page to make the request to
  parameters [IN] [STRING]   The POST variables to send
  callBack   [IN] [FUNCTION] The function to call on the state change
Returns:	None
**********************************************************************************************************/
function ajaxFunction(url, parameters, callBack) {
  ajaxRequest = GetXmlHttpObject();
  ajaxRequest.onreadystatechange = callBack;
  ajaxRequest.open('POST', url, true);
  ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  ajaxRequest.setRequestHeader("Content-length", parameters.length);
  ajaxRequest.setRequestHeader("Connection", "close");
  ajaxRequest.send(parameters);
}

/**********************************************************************************************************
Function Name:
	resetFormData
Description:
  Clears the audit configuration form after a successful edit/add
Arguments: None
Returns: None
**********************************************************************************************************/
function resetFormData() {
    document.getElementById('select_os').disabled = true;
    document.getElementById('select_audit').disabled = true;
    document.getElementById('select_os').disabled = 'true';
    document.getElementById('select_audit').disabled = 'true';
    $('fieldset.os').hide();
    $('fieldset.audit-action').hide();
    $('fieldset.audit-type').hide();
    document.getElementById('input_ldap_user').disabled = false;
    document.getElementById('input_ldap_pass').disabled = false;
    document.getElementById('input_ldap_server').disabled = false;
    document.getElementById('input_ldap_path').disabled = false;
    document.getElementById('input_cred_user').disabled = false;
    document.getElementById('input_cred_pass').disabled = false;
    document.getElementById('input_name').focus();
}

/**********************************************************************************************************
Function Name:
	resetSchedFormData
Description:
  Clears the audit schedules form after a successful edit/add
Arguments: None
Returns: None
**********************************************************************************************************/
function resetSchedFormData() {
  $('fieldset.schedule-type').hide();
  document.getElementById("fs_email").style.display = 'none';
  document.getElementById('input_name').focus();
}

/**********************************************************************************************************
Function Name:
	stateChange
Description:
	Called when the AJAX request for a schedule/configuration add/edit returns with a status. This decides
  what to do based on it
Arguments: None
Returns: None
**********************************************************************************************************/
function stateChange() {
  if ( ajaxRequest.readyState == 4 ) {
    if ( ajaxRequest.status == 200 ) {
      result = ajaxRequest.responseText;
      var regConfig = /.*Configuration added.*/i;
      var regSched = /.*Schedule added.*/i;
      if ( regConfig.exec(result) ) {
        document.getElementById('form_config').reset();
        resetFormData();
        document.getElementById('form_result_success').innerHTML = result;            
        document.getElementById('form_result_fail').innerHTML = '';            
      } else if ( regSched.exec(result) ) {
        document.getElementById('form_sched').reset();
        resetSchedFormData();
        document.getElementById('form_result_success').innerHTML = result;            
        document.getElementById('form_result_fail').innerHTML = '';            
      } else {
        document.getElementById('form_result_fail').innerHTML = result;            
        document.getElementById('form_result_success').innerHTML = '';            
      }
    } else {
      alert('There was a problem with the request.');
    }
  }
}

/**********************************************************************************************************
Function Name:
	SubmitForm
Description:
	Put together the POST string and send the request to the correct PHP page
Arguments:
	type   [IN] [string] config or schedule
	action [IN] [string] add or edit
	editID [IN] [string] ID of the configuration/schedule to edit
Returns:	None
**********************************************************************************************************/
function SubmitForm(type,action,editID) {
  if ( type == "config" ) {
    var postStr = $('#form_config').serialize();

    if ( document.getElementById('DragContainer') != undefined ) {
      var cmdCheck = document.getElementById('DragContainer').getElementsByTagName('input');
      for( var i = 0 ; i < cmdCheck.length ; i++ ) { 
        if ( cmdCheck[i].checked ) {
          postStr = postStr + "&check_cmd[]=" + cmdCheck[i].value;
        }
      }
    }

    // The filter can be defined in two places, but they use the same DB entries
    // So just write new values based on what it will be used for, if at all
    var audit_type = $("#select_audit").val();

    if ( audit_type == "domain" || audit_type == "mysql" ) {
      if ( audit_type == "domain" ) { audit_type = 'ldap'; }
      filter = $('#fs_' + audit_type + ' input');
      for( var i = 0 ; i < filter.length ; i++ ) { 
        switch (filter[i].id) {
          case  "check_filter_case":
            postStr = postStr + "&check_filter_case=" + filter[i].checked;
            break;
          case  "check_filter_inverse":
            postStr = postStr + "&check_filter_inverse=" + filter[i].checked;
            break;
          case  "input_filter":
            postStr = postStr + "&input_filter=" + encodeURI( filter[i].value );
            break;
        }
      }
    }

    if ( audit_type == "mysql" ) {
      var s_tr = $('#mysqli_query_options tr');
      for( var i = 0 ; i < s_tr.length ; i++ ) {
        // The display will only be none if they checked to remove an existing entry
        if ( s_tr[i].style.display == 'none' ) {
          postStr = postStr + "&del_query[]=" + s_tr[i].id;
          continue;
        }
        var q_img = s_tr[i].getElementsByTagName('img');
        var q_id  = q_img[0].id;

        var o_tbl  = document.getElementById('qtbl' + q_id);
        var o_fld  = document.getElementById('qfld' + q_id);
        var o_srt  = document.getElementById('qsrt' + q_id);

        var tbl  = o_tbl.options[o_tbl.selectedIndex].value;
        var fld  = o_fld.options[o_fld.selectedIndex].value;
        var srt  = o_srt.options[o_srt.selectedIndex].value;
        var data = document.getElementById('qdata' + q_id).value;

        if ( s_tr[i].id == "qnewrow" ) {
          postStr = postStr + "&query_fields_add[]=" + tbl + "," + fld + "," + srt;
          postStr = postStr + "&query_data_add[]=" + data;
        }
        else {
          postStr = postStr + "&query_fields_mod[]=" + s_tr[i].id + "," + tbl + "," + fld + "," + srt;
          postStr = postStr + "&query_data_mod[]=" + data;
        }
      }
    }

    if ( action == "edit" ) {
      postStr = postStr + "&form_action=edit";
      postStr = postStr + "&config_id=" + editID;
    }
    var phpPage = "audit_config_add_ajax.php";
  } else if ( type == "sched" ) {
    var postStr = $('#form_sched').serialize();

    /* Add all the weekdays selected to a post array */
    var weekCheck=document.getElementsByName("check_weekly");
    for(var i=0;i<weekCheck.length;i++){ 
      if ( weekCheck[i].checked ) {
        postStr = postStr + "&check_weekly[]=" + weekCheck[i].value;
      }
    }
    /* Add all the months selected to a post array */
    var monthCheck=document.getElementsByName("check_monthly");
    for(var i=0;i<monthCheck.length;i++){
      if ( monthCheck[i].checked ) {
        postStr = postStr + "&check_monthly[]=" + monthCheck[i].value;
      }
    }
    /* Add the email list to a post array */
    var emails=document.getElementsByName("email_to");
    for(var i=0;i<emails.length;i++){
      postStr = postStr + "&email_list[]=" + emails[i].value;
    }

    if ( action == "edit" ) {
      postStr = postStr + "&form_action=edit";
      postStr = postStr + "&sched_id=" + editID;
    }
    var phpPage = "audit_sched_add_ajax.php";
  }
  ajaxFunction(phpPage, postStr, stateChange);
}

/**********************************************************************************************************
Function Name:
	SwitchConfig
Description:
	This function hides the other FS's based on what type of config was selected
Arguments:
	selected [IN] [object] select object for the type of config
Returns:	None
**********************************************************************************************************/
function SwitchConfig(selected) {
  var name = selected.options[selected.selectedIndex].value;
  $('fieldset.audit-type').hide();
  $('fieldset.' + name).toggle();
}

/**********************************************************************************************************
Function Name:
	SwitchOS
Description:
	This function hides/shows the correct FS for the OS based on what was selected
Arguments:
	selected [IN] [object] select object for the type of OS
Returns:	None
**********************************************************************************************************/
function SwitchOS(selected) {
  var name   = $('#' + selected.id + ' :selected').val();
  var action = $('#select_action :selected').val();
  $('fieldset.os').hide();
  if ( action == 'nmap' || action == 'command' ) { return; }
  if ( name != 'nothing' ) { $('fieldset.' + name ).toggle(); }
}

/**********************************************************************************************************
Function Name:
	SwitchAction
Description:
	This function hides/shows the correct FS for the config action based on what was selected
Arguments:
	selected [IN] [object] select object for the type of audit action
Returns:	None
**********************************************************************************************************/
function SwitchAction(selected) {
  var name = selected.options[selected.selectedIndex].value;
  $('fieldset.audit-action').hide();
  $('fieldset.' + name).show();
  if ( name == "pc" ) {
    /* If the previous action was "command", the OS might remain hidden */
    if ( document.getElementById('select_os').value != 'nothing' ) {
      var os_choice = document.getElementById('select_os').value;
      document.getElementById("fs_" + os_choice ).style.display = 'block';
    }
    document.getElementById('select_os').disabled = false;
    document.getElementById('select_audit').disabled = false;
  } else if ( name == "nmap" ) {
    $('fieldset.os').hide();
    document.getElementById('select_audit').disabled = false;
    document.getElementById('select_os').selectedIndex = 0;
    document.getElementById('select_os').disabled = 'true';
  } else if ( name == "pc_nmap" ) {
    document.getElementById('select_audit').disabled = false;
    document.getElementById('select_os').disabled = false;
  } else if ( name == "command" ) {
    $('fieldset.os').hide();
    document.getElementById('select_os').disabled = false;
    document.getElementById('select_audit').disabled = false;
  } else if ( name == "nothing" ) {
    $('fieldset.os').hide();
    $('fieldset.audit-type').hide();
    document.getElementById('select_audit').selectedIndex = 0;
    document.getElementById('select_os').selectedIndex = 0;
    document.getElementById('select_os').disabled = 'true';
    document.getElementById('select_audit').disabled = 'true';
  }
}

/**********************************************************************************************************
Function Name:
	ToggleAuth
Description:
	This function disables/enables manual user/pass fields if the user selects an ldap connection
Arguments:
	selected [IN] [object] select object for the LDAP connection
Returns:	None
**********************************************************************************************************/
function ToggleAuth(selected) {
  var action = ( selected.options[selected.selectedIndex].value != 'nothing' ) ? true : false;
  if ( selected.id == 'select_audit_cred' ) {
    document.getElementById('input_cred_user').disabled = action;
    document.getElementById('input_cred_pass').disabled = action;
  }
  else {
    document.getElementById('input_ldap_user').disabled = action;
    document.getElementById('input_ldap_pass').disabled = action;
    document.getElementById('input_ldap_server').disabled = action;
    document.getElementById('input_ldap_path').disabled = action;
  } 
}

/**********************************************************************************************************
Function Name:
	DisableOnLoad
Description:
	This function disables certain elements on the page load for the configs/schedules 
Arguments:
	type [IN] [string] Is this for a configuration (config) or schedule (sched)?
Returns:	None
**********************************************************************************************************/
function DisableOnLoad(type) {
  if ( type == "config" ) {
    document.getElementById('select_os').disabled = true;
    document.getElementById('select_audit').disabled = true;
    document.getElementById('end_ip_1').disabled = true;
    document.getElementById('end_ip_2').disabled = true;
    document.getElementById('end_ip_3').disabled = true;
    document.getElementById("fields_nothing").disabled = true;
  }
  else if ( type == "sched" ) {
    document.getElementById('select_hstrt_hour').disabled = true;
    document.getElementById('select_hstrt_min').disabled = true;
    document.getElementById('select_hend_hour').disabled = true;
    document.getElementById('select_hend_min').disabled = true;
  }
  document.getElementById('input_name').focus();
}

/**********************************************************************************************************
Function Name:
	IpCopy
Description:
	Copy the typed IP octet to the corresponding one below
Arguments:
	selected [IN] [object]  INPUT object whose value needs to be copied
	octet    [IN] [INTEGER] The octet of the corresponding IP that needs to have the value copied into
Returns:	None
**********************************************************************************************************/
function IpCopy(selected, octet) { document.getElementById('end_ip_' + octet).value = selected.value; }

/**********************************************************************************************************
Function Name:
	MinCopy
Description:
  This function copies the starting minutes for hourly schedules between a certain time
  since it makes no sense to have a difference between the start and end minute
Arguments:
	selected [IN] [object] INPUT object whose value we should copy
Returns:	None
**********************************************************************************************************/
function MinCopy(selected) { document.getElementById('select_hend_min').value = selected.value; }

/**********************************************************************************************************
Function Name:
	ConfigType
Description:
  Determine how/what to display on the audit_configuration.php page
Arguments: None
Returns:	None
**********************************************************************************************************/
function ConfigType() {

  SwitchConfig(document.getElementById("select_audit"));
  SwitchAction(document.getElementById("select_action"));
  SwitchOS(document.getElementById("select_os"));
  ToggleAuth(document.getElementById("select_audit_cred"));
  ToggleAuth(document.getElementById("select_ldap_cred"));

  document.getElementById('end_ip_1').disabled = true;
  document.getElementById('end_ip_2').disabled = true;
  document.getElementById('end_ip_3').disabled = true;
}

/**********************************************************************************************************
Function Name:
	MakeMoveable
Description:
  Make it so when a user clicks a command box div it can be moved up/down
Arguments:
	obj	[IN] [object]	div object that fired the event
Returns:	None
**********************************************************************************************************/
// Change the class on the box so we know which one should move
function MakeMovable(obj) {
  if ( obj.className == "Box" ) {
    var ctr  = document.getElementById('DragContainer');
    var divs = ctr.getElementsByTagName('div');
    for(var i = 0 ; i < divs.length ; i++ ) { 
      if ( divs[i].id != obj.id && divs[i].className == 'MoveBox' ) {
        divs[i].setAttribute("class","Box");
        divs[i].setAttribute("className","Box");
      }
    }
    // This seems to set the class for both IE and FireFox
    obj.setAttribute("class","MoveBox");
    obj.setAttribute("className","MoveBox");
  }
  else {
    obj.setAttribute("class","Box");
    obj.setAttribute("className","Box");
  }
}

/**********************************************************************************************************
Function Name:
	swapNodes
Description:
  Swap the conents of two nodes
Arguments:
	item1	[IN] [object]	DOM object to swap
	item2	[IN] [object]	DOM object to swap
Returns:	None
**********************************************************************************************************/
function swapNodes(item1,item2) {
  var itemtmp = item1.cloneNode(1);
  var parent = item1.parentNode;

  item2 = parent.replaceChild(itemtmp,item2);

  parent.replaceChild(item2,item1);
  parent.replaceChild(item1,itemtmp);

  itemtmp = null;
}

/**********************************************************************************************************
Function Name:
	boxUp
Description:
  Move a command box up in order
Arguments: None
Returns:	None
**********************************************************************************************************/
function boxUp() {
  var boxes = document.getElementById('DragContainer').getElementsByTagName('div');
  for ( var i = 0 ; i < boxes.length ; i++ ) { 
    if ( boxes[i].className == 'MoveBox' ) {
      if ( i == 0 ) { return; }
      swapNodes(boxes[i].previousSibling,boxes[i]); return;
    }
  }
}

/**********************************************************************************************************
Function Name:
	boxDown
Description:
  Moves a command box down in order
Arguments: None
Returns:	None
**********************************************************************************************************/
function boxDown() {
  var boxes = document.getElementById('DragContainer').getElementsByTagName('div');
  var end   = boxes.length - 1;
  for ( var i = 0 ; i < boxes.length ; i++ ) { 
    if ( boxes[i].className == 'MoveBox' ) {
      if ( i == end ) { return; }
      swapNodes(boxes[i].nextSibling,boxes[i]); return;
    }
  }
}

function TestResult(selected,test_type) {
  var id = $('#config_id').val();
  var title;
  switch (test_type) {
    case "ldap":  title = 'LDAP Query Results';  break;
    case "mysql": title = 'MySQL Query Results'; break;
    case "nmap":  title = 'NMAP Command Output'; break;
  }
  $('#dialog-test-results').dialog('option','title',title);
  selected.disabled = true;
  $.ajax({
    'url': 'audit_test_ajax.php',
    'type': 'POST',
    'data': {
      'config_id': id,
      'type': test_type
    },
    'beforeSend': function(){
      var html = "<br><br><i class=\"fa fa-spinner fa-spin\"></i><i>Fetching Results...</i>";
      $('#' + test_type + '_result').html(html);
    },
    'success': function(msg){
      selected.disabled = false;
      $('#' + test_type + '_result').html('');
      $('#dialog-test-text').html(msg);
      $('#dialog-test-results').dialog('open');
    },
    'error': function(){ $('#' + test_type + '_result').html(''); selected.disabled = false; }
  });
}

/**********************************************************************************************************

	Jquery 'ready' function - things to execute when the DOM is ready

**********************************************************************************************************/

$(document).ready(function() {
	$('a.tooltip').tooltip({
		'showURL' : false,
		'extraClass' : 'cfg-tooltip',
		'delay' : 0,
		'fade' : 250
	}).click( function() { return false; });

  // Stuff for the audit_configuration.php page only...
  if(($('#select_audit').length)){ 
    ConfigType();
    $("#dialog-test-results").dialog({
      bgiframe: true,
      resizable: false,
      draggable: false,
      autoOpen: false,
      modal: true,
      width: 400,
      height: 300,
      overlay: {
        backgroundColor: '#000',
        opacity: 0.5
      }
    });
  }
  // Stuff for the audit_schedule.php page only...
  else if(($('#select_sched_type').length)){ 
    SchedType();
  }
});
