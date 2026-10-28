/**********************************************************************************************************
Function Name:
	SwitchSchedType
Description:
	Show only the fieldset of the schedule type picked for the dropdown
Arguments:
	selected	[IN] [object]	select object for the schedule type
Returns:	None
**********************************************************************************************************/
function SwitchSchedType(selected) {
  var class_name = selected.options[selected.selectedIndex].value;

  $('fieldset.schedule-type').hide();

  if ( class_name == "hourly" || class_name == "crontab" ) {
    document.getElementById('select_gen_hour').disabled = true;
    document.getElementById('select_gen_min').disabled = true;
  }
  else {
    document.getElementById('select_gen_hour').disabled = false;
    document.getElementById('select_gen_min').disabled = false;
  }

  $('fieldset.' + class_name).show();
}

/**********************************************************************************************************
Function Name:
	BetweenHours
Description:
  Disable/enable boxes if this is a schedule that only goes between a certain time
Arguments:
	obj	[IN] [object]	checkbox object to select only between a certain time
Returns:	None
**********************************************************************************************************/
function BetweenHours(obj) {
  var action = ( obj.checked ) ? false : true;

  document.getElementById('select_hstrt_hour').disabled = action;
  document.getElementById('select_hstrt_min').disabled = action;
  document.getElementById('select_hend_hour').disabled = action;

  document.getElementById('select_hourly_start').disabled = ( obj.checked ) ? true : false ;
}

/**********************************************************************************************************
Function Name:
	ToggleLogging
Description:
	If logging is disabled at the schedule level, disable the ability to email
Arguments:
	obj	[IN] [object]	checkbox object for toggle the logging ability
Returns:	None
**********************************************************************************************************/
function toggleLogging(obj) {
  if ( obj.checked ) {
    document.getElementById('check_email_log').disabled = true;
    document.getElementById('check_email_log').checked = false;
    document.getElementById('fs_email').style.display = 'none';
  }
  else {
    document.getElementById('check_email_log').disabled = false;
    document.getElementById('check_log_disable').disabled = false;
    if ( document.getElementById('check_email_log').checked ) {
      document.getElementById('fs_email').style.display = 'block';
    }
  }
}

/**********************************************************************************************************
Function Name:
	toggleEmail
Description:
	Show/hide the email options
Arguments:
	obj	[IN] [object]	checkbox object for toggling emails
Returns:	None
**********************************************************************************************************/
function toggleEmail(obj) { ( obj.checked ) ? $('#fs_email').show() : $('#fs_email').hide(); }

/**********************************************************************************************************
Function Name:
	SchedType
Description:
	Called when the page is loaded to determine what to show
Arguments: None
Returns:	 None
**********************************************************************************************************/
function SchedType() {
  SwitchSchedType( document.getElementById('select_sched_type') );
  BetweenHours( document.getElementById('check_hours_between') );
  toggleEmail( document.getElementById('check_email_log') );
  toggleLogging( document.getElementById('check_log_disable') );

  document.getElementById('select_hend_min').disabled = true;
  document.getElementById('input_name').focus();
}

/**********************************************************************************************************
Function Name:
	cronTest
Description:
	Make an AJAX request to test if the user entered cron line is valid. If it is, show the next run time
Arguments: None
Returns:	 None
**********************************************************************************************************/
function cronTest() {
  document.getElementById('cron_result').innerHTML = 
   "<i class=\"fa fa-spinner fa-spin\"></i>&nbsp;&nbsp;<i>Checking cron line...</i>";
  if ( ajaxRequest.readyState == 4 ) {
    var invalid = /^<b>Invalid /;
    result = ajaxRequest.responseText;
    document.getElementById('cron_button').disabled = false;
    document.getElementById('cron_result').innerHTML = 
      ( invalid.exec(result) ) ?
      "<font color=\"red\">"   + result + "</font>" :
      "<font color=\"green\">" + result             ;            
  }
}

/* Get the next execution time of the cron line */
function testCron(selected) {
  selected.disabled = true;
  var postStr = "cron_line=" + encodeURI( document.getElementById('input_cron_line').value ) +
                "&type=cron";
  var phpPage = "audit_test_ajax.php";
  ajaxFunction(phpPage, postStr, cronTest);
} 
