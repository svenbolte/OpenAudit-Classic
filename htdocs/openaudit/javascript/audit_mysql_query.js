function setMysqlFields(obj,id,o_tbl,o_fld) {
  var table; var field; var s_status;

  ( obj != null ) ?  table = obj.options[obj.selectedIndex].value : table = o_tbl ;

  if ( obj != null ) {
    var sel_obj = document.getElementById('select_fields').getElementsByTagName('select');
    s_status = sel_obj[0].id;
  }
    
  var http_request = GetXmlHttpObject();
  var postStr = "action=get_fields&table=" + table + "&field=" + field;

  http_request.onreadystatechange = function () {
    if ( http_request.readyState == 4 ) {
      if ( http_request.status == 200 ) {
        document.getElementById(id).innerHTML = http_request.responseText;
        if ( table == 'nothing' ) {
          document.getElementById("fields_nothing").disabled = true;
        }
        else {
          document.getElementById('fields_nothing').disabled = false;
        }
      }
    }
  }

  http_request.open('POST', 'audit_config_mysqli_ajax.php', true);
  http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  http_request.setRequestHeader("Content-length", postStr.length);
  http_request.setRequestHeader("Connection", "close");
  http_request.send(postStr);
}

function addToQuery() {
  var obj   = document.getElementById("select_fields").firstChild; // Div that holds original field select node
  var t_obj = document.getElementById("mysqli_tables");             // Original table select node
  var s_obj = document.getElementById("fields_sort");              // Original sort select node
  var data  = document.getElementById("input_field_value").value;  // Original data input node

  // The values the new row should have
  var field = obj.options[obj.selectedIndex].value;
  var table = t_obj.options[t_obj.selectedIndex].value;
  var sort  = s_obj.options[s_obj.selectedIndex].value;

  // Check that something was entered
  if ( table == "nothing" || data == "" ) { return; }

  var tbl  = document.getElementById("mysqli_query_options");
  var s_tr = document.getElementById('mysqli_query_options').getElementsByTagName('tr');
  var el  = new Array();

  var row = tbl.insertRow(tbl.rows.length);

  // Left most cell with image element
  var cellLeft = row.insertCell(0);
  el[0] = document.createElement('img');
  el[0].style.display = 'none';
  el[0].id  = s_tr.length;
  el[0].setAttribute('class','deletebutton');
  el[0].onclick = function() { removeQueryOpt(el[0]) };

  cellLeft.appendChild(el[0]);

  // Clone needed nodes and set the right id
  var c_field   = obj.cloneNode(true);
  var c_table   = t_obj.cloneNode(true);
  var c_sort    = s_obj.cloneNode(true);
  var c_data    = document.getElementById("input_field_value").cloneNode(true);

  c_field.setAttribute('id','qfld' + s_tr.length)
  c_table.setAttribute('id','qtbl' + s_tr.length)
  c_sort.setAttribute('id','qsrt'  + s_tr.length)

  c_data.setAttribute('id','qdata' + s_tr.length)
  c_data.setAttribute("class","mysql");
  c_data.setAttribute("className","mysql");

  c_table.onchange = function() { setFieldSelect(this,'cellfield' + s_tr.length,'qfld' + s_tr.length ) };

  // Remove blank options, set selected values
  for (var i = c_field.length - 1; i>=0; i--) {
    if ( c_field.options[i].value == "nothing" ) { c_field.remove(i) };
    if ( c_field.options[i].value == field ) { c_field.selectedIndex = i };
  }

  for (var i = c_table.length - 1; i>=0; i--) {
    if ( c_table.options[i].value == "nothing" ) { c_table.remove(i) };
    if ( c_table.options[i].value == table ) { c_table.selectedIndex = i };
  }

  for (var i = c_sort.length - 1; i>=0; i--) {
    if ( c_sort.options[i].value == sort ) { c_sort.selectedIndex = i };
  }

  var cellTable = row.insertCell(1);
  var cellField = row.insertCell(2);
  cellField.setAttribute('id','cellfield' + s_tr.length);
  var cellSort  = row.insertCell(3);
  var cellData  = row.insertCell(4);

  cellField.appendChild(c_field);
  cellTable.appendChild(c_table);
  cellSort.appendChild(c_sort);
  cellData.appendChild(c_data);

  // Get a select for the table/field in HTML format to insert for this row
  row.setAttribute('class','query_box');
  row.setAttribute('id','qnewrow');
}

function removeQueryOpt(obj) {
  var tbl  = document.getElementById("mysqli_query_options");
  var row = obj.parentNode;
  while(row.nodeName.toLowerCase()!='tr') { row = row.parentNode; }
  ( row.id == 'qnewrow' ) ? ( tbl.deleteRow(row.rowIndex) ) : row.style.display = 'none' ;
}

function setFieldSelect(obj,id,field_id) {
  var table = obj.options[obj.selectedIndex].value;
  var http_request = GetXmlHttpObject();
  var postStr = "action=get_fields&table=" + table + "&add_query_row=1&field_id=" + field_id;

  http_request.onreadystatechange = function () {
    if ( http_request.readyState == 4 ) {
      if ( http_request.status == 200 ) {
        document.getElementById(id).innerHTML = http_request.responseText;
      }
    }
  }

  http_request.open('POST', 'audit_config_mysqli_ajax.php', true);
  http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  http_request.setRequestHeader("Content-length", postStr.length);
  http_request.setRequestHeader("Connection", "close");
  http_request.send(postStr);
}
