/**********************************************************************************************************
Function Name:
	addToCommands
Description:
  Add a new command row on the audit_commands.php page when the add button is clicked
Arguments: None
Returns:	None
**********************************************************************************************************/
function addToCommands() {
  var c_name = document.getElementById("input-cmdname").value;
  var c_cmd  = document.getElementById("input-cmd").value;


  if ( c_name == "" || c_cmd == "" ) { return; }

  var tbl  = document.getElementById('cmd_table');
  var s_tr = document.getElementById('cmd_table').getElementsByTagName('tr');

  if ( tbl.rows.length == '0' || rowsShowing(tbl) == '0' ) { addHeader(tbl); }

  var row = tbl.insertRow(tbl.rows.length);

  var input_name = document.createElement("input");
  input_name.setAttribute("id", "cmdname" + s_tr.length);
  input_name.value = c_name;

  var input_cmd = document.createElement("input");
  input_cmd.setAttribute("id", "cmd" + s_tr.length);
  input_cmd.setAttribute('class','command-value');
  input_cmd.setAttribute("className","command-value");
  input_cmd.value = c_cmd;

  var del_img = document.createElement('img');
  del_img.style.display = 'none';
  del_img.id  = s_tr.length;
  del_img.setAttribute('class','deletebutton');
  del_img.setAttribute("className","deletebutton");
  del_img.onclick = function() { removeCommand(del_img) };

  var cellLeft = row.insertCell(0);
  var cellMiddle = row.insertCell(1);
  var cellRight = row.insertCell(2);

  cellLeft.appendChild(input_name);
  cellMiddle.appendChild(input_cmd);
  cellRight.appendChild(del_img);

  // Get a select for the table/field in HTML format to insert for this row
  row.setAttribute('class','cmdbox');
  row.setAttribute('id','newcmd');
}

function removeCommand(obj) {
  var tbl  = document.getElementById("cmd_table");
  var row = obj.parentNode;
  while(row.nodeName.toLowerCase()!='tr') { row = row.parentNode; }
  ( row.id == 'newcmd' ) ? ( tbl.deleteRow(row.rowIndex) ) : row.style.display = 'none' ;
  if ( tbl.rows.length == '1' || rowsShowing(tbl) == '0' ) { tbl.deleteRow(0); }
}

/**********************************************************************************************************
Function Name:
	stateChange
Description:
	Handle the state change from the AJAX POST request
Arguments: None
Returns:	None
**********************************************************************************************************/
function stateChange() {
  if ( ajaxRequest.readyState == 4 ) {
    if ( ajaxRequest.status == 200 ) {
      result = ajaxRequest.responseText;
        document.getElementById('form_result').innerHTML = result;            
    } else {
      alert('There was a problem with the request.');
    }
  }
}

/**********************************************************************************************************
Function Name:
	submitCommands
Description:
	Update the commands via an AJAX POST to audit_command_ajax.php when the update button is pushed.
Arguments: None
Returns:	None
**********************************************************************************************************/
function submitCommands() {
  var postStr = "action=update";
  var change = false;

  var s_tr = document.getElementById('cmd_table').getElementsByTagName('tr');
  for( var i = 0 ; i < s_tr.length ; i++ ) {
		if ( s_tr[i].id == "table-cmd-head" ) { continue; }
    // The display will only be none if they checked to remove an existing entry
    if ( s_tr[i].style.display == 'none' ) {
      postStr = postStr + "&del_cmd[]=" + s_tr[i].id;
      change = true;
      continue;
    }

    var c_img = s_tr[i].getElementsByTagName('img');
    var c_id  = c_img[0].id;

    var o_name  = document.getElementById('cmdname' + c_id).value;
    var o_cmd   = document.getElementById('cmd' + c_id).value;

    if ( s_tr[i].id == "newcmd" ) {
      postStr = postStr + "&cmd_add_name[]=" + o_name;
      postStr = postStr + "&cmd_add_cmd[]=" + o_cmd;
      change = true;
    }
    else {
      postStr = postStr + "&cmd_mod_id[]=" + s_tr[i].id;
      postStr = postStr + "&cmd_mod_name[]=" + o_name;
      postStr = postStr + "&cmd_mod_cmd[]=" + o_cmd;
      change = true;
    }
  }

  if ( change == true ) {
    ajaxRequest = GetXmlHttpObject();
    ajaxRequest.onreadystatechange = stateChange;
    ajaxRequest.open('POST', 'audit_command_ajax.php' , true);
    ajaxRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajaxRequest.setRequestHeader("Content-length", postStr.length);
    ajaxRequest.setRequestHeader("Connection", "close");
    ajaxRequest.send(postStr);
  }
}

/**********************************************************************************************************
Function Name:
	addHeader
Description:
	Add a header to a table
Arguments:
  tbl	[IN] [OBJECT]  The table object to add the header to
Returns:	None
**********************************************************************************************************/
function addHeader(tbl) {
	if ( document.getElementById('table-cmd-head') ) { return; }
  var row = tbl.insertRow(0);
  row.setAttribute("id", "table-cmd-head");

  var cellLeft   = row.insertCell(0);
  var cellMiddle = row.insertCell(1);
  var cellRight  = row.insertCell(2);

  cellLeft.appendChild(document.createTextNode('Name'));
  cellMiddle.appendChild(document.createTextNode('Command'));
  cellRight.appendChild(document.createTextNode('Delete'));
}

/**********************************************************************************************************
Function Name:
	rowsShowing
Description:
	Get the ammount of rows that are actually visible
Arguments:
  tbl [IN] [OBJECT] The table object to check
Returns:	None
  count [INTEGER] The number of rows visible
**********************************************************************************************************/
function rowsShowing(tbl) {
  var tr = document.getElementById('cmd_table').getElementsByTagName('tr');
	var count = 0;
  for( var i = 1 ; i < tr.length ; i++ ) {
    if ( tr[i].style.display != 'none' ) { count++; }
	}

	return count;
}
