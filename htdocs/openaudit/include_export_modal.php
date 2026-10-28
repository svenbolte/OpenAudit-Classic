<?php
  /*
   This page is included on system.php and list.php. The modal is activated when the PDF-Report link
   is clicked on the menu along the right hand side or if a listview is exported as a CSV/PDF
  */
?>

<style type="text/css" media="screen">
  .email-list-invalid { background: #FFBAD2; }
  .export-error { color: #CC0033; }
  #export-sending {display: block; height: 2em; width: 2em; }
</style>

<form id="export_modal_info">
<?php
  $username = (isset($_SESSION["username"])) ? $_SESSION["username"] : 'Unknown';
  $pcname   = (isset($name)) ? $name : 'Unknown';
  echo "<input type=\"hidden\" name=\"system-pdf\" value=\"n\" />\n";
  echo "<input type=\"hidden\" name=\"system-name\" value=\"".$pcname."\" />\n";
  echo "<input type=\"hidden\" name=\"username\" value=\"".$username."\" />\n";
  if (isset($pc) || isset($_GET["PC"])) {
    $pc_uuid = ( isset($pc) ) ? $pc : $_GET["pc"];
    echo "<input type=\"hidden\" name=\"pc\" value=\"".$pc_uuid."\" />\n";
  }
?>
</form>

<div style="display: none; " id="export-dialog">
    <label class="ui-dialog-content-label">File Name:</label>
    <input type="text" id="export-file-name" style="clear: none;width:50px;" class="text ui-widget-content ui-corner-all ui-dialog-content-button"/><?php echo '-'. $_GET["view"]; ?><strong><span id="export-file-ext"></span></strong>
	<label class="ui-dialog-content-label pdf-sidemenu-select">Report Type:</label>
    <select id="export-select-report" class="ui-widget ui-dialog-content-button pdf-sidemenu-select">
      <option value="report_full">Full</option>
      <option value="report">Partial</option>
    </select><br/>
    <label class="ui-dialog-content-label export-method">Export Method:</label>
    <select id="export-select-method" class="ui-widget ui-dialog-content-button">
      <option value="download">Download</option>
      <option value="email">Email</option>
    </select>
    <div style="display: none;" id="export-email">
      <label class="ui-dialog-content-label">Email To:</label>
      <input title="Separate email addresses with a semi-colon" type="text" id="export-email-list" class="text ui-widget ui-widget-content ui-corner-all ui-dialog-content-button"/>
      <center>
        <span id="export-result" class="export-error"></span>
        <i class="fa fa-circle-o" aria-hidden="true"></i>
      </center>
      <input type="hidden" value="n" id="export-page-form"/>
      <input type="hidden" value="" id="export-page"/>
    </div>
</div>
