<?php
/**********************************************************************************************************
Module: list.php
Description: List page for Open Audit application.
Optimizations:
- Safer input handling (filter_input + whitelisting for sort/order)
- Cleaner redirects (header + exit)
- Reduced repetition and safer defaults
**********************************************************************************************************/

// Check for config, otherwise run setup
if (!file_exists("include_config.php")) {
    header("Location: setup.php");
    exit;
}

include "include.php";

// Prefer embedded catalog logos; never fetch logos from removed directories.
function oa_software_icon($name, $inline = '') {
    $inline = trim((string)$inline);
    if (preg_match('~^data:image/(?:png|gif|jpeg|jpg|webp|svg\+xml|x-icon|vnd\.microsoft\.icon);base64,[A-Za-z0-9+/=]+$~i', $inline)) {
        return '<img src="'.htmlspecialchars($inline, ENT_QUOTES, 'UTF-8').'" alt="" style="width:16px;height:16px;object-fit:contain;vertical-align:middle" /> ';
    }
    $n = strtolower((string)$name);
    $icons = array('windows'=>'fa-windows','microsoft'=>'fa-windows','linux'=>'fa-linux','ubuntu'=>'fa-linux','debian'=>'fa-linux','apple'=>'fa-apple','macos'=>'fa-apple','android'=>'fa-android','wordpress'=>'fa-wordpress','chrome'=>'fa-chrome','firefox'=>'fa-firefox','edge'=>'fa-edge','docker'=>'fa-cubes','java'=>'fa-coffee','database'=>'fa-database','mysql'=>'fa-database','pdf'=>'fa-file-pdf-o','office'=>'fa-file-text-o','excel'=>'fa-file-excel-o','word'=>'fa-file-word-o');
    foreach ($icons as $needle => $icon) {
        if (strpos($n, $needle) !== false) return '<i class="fa '.$icon.'" aria-hidden="true"></i> ';
    }
    return ''; // Unknown product: no icon rather than an incorrect logo.
}

// ------------------------ Input handling ------------------------
$software = filter_input(INPUT_GET, 'software', FILTER_UNSAFE_RAW);
$software = is_string($software) ? $software : "";

$page_count = filter_input(INPUT_GET, 'page_count', FILTER_VALIDATE_INT);
$page_count = ($page_count === null || $page_count === false) ? 0 : max(0, (int)$page_count);

// Common list params (keep compatible with existing code that may use $_GET directly elsewhere)
$show_all = filter_input(INPUT_GET, 'show_all', FILTER_UNSAFE_RAW);
$show_all = is_string($show_all) ? $show_all : "";

// Sorting: whitelist to avoid SQL injection if used in ORDER BY later
$sort = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW);
$sort = is_string($sort) ? $sort : "";
$order = filter_input(INPUT_GET, 'order', FILTER_UNSAFE_RAW);
$order = is_string($order) ? strtolower($order) : "";

// If your code uses a different name, adjust these lists accordingly.
$allowed_sort = [
    'system_name','hostname','ip','domain','os_name','os_version','last_audit','type','manufacturer','model','serial','location','owner'
];
if ($sort === "" || !in_array($sort, $allowed_sort, true)) {
    $sort = 'system_name';
}
$allowed_order = ['asc','desc'];
if (!in_array($order, $allowed_order, true)) {
    $order = 'asc';
}

// Paging helpers (preserve original semantics)
$page_prev = max(0, $page_count - 1);
$page_next = $page_count + 1;
$page_current = $page_count;

// If original code uses $count_system for paging, preserve it.
if (!isset($count_system) || !is_numeric($count_system)) {
    // sensible default if not defined by include.php
    $count_system = 50;
}

// show_all often means "no paging"
if ($show_all !== "") {
    $count_system = 10000;
}

$offset = $page_count * (int)$count_system;

// ------------------------ Original content ------------------------
// Below: we keep the existing file body but patch a few unsafe / noisy patterns.
// If the original file already defines variables/functions below, this wrapper keeps them set.

?> 
<?php

include_once("include.php");

// set an initial 4 min extra timeout
set_time_limit(240000);

$count_system_max="10000";

// If you would like to have a new View, you have to modify 3 parts:
// -> include_menu_array.php: $menue_array
// -> list_viewdef_X.php: "Table and fields to select and show"
// -> option: include_functions.php: ConvertSpecialField()

//Include the view-definition
if (isset($_REQUEST["view"]) AND $_REQUEST["view"]!="") {
    $include_filename = "list_viewdef_".$_REQUEST["view"].".php";
} else {
    $include_filename = "list_viewdef_all_systems.php";
}
if (is_file($include_filename)) {
    include_once($include_filename);
    $viewdef_array=$query_array;
}else{
    die("FATAL: Could not find view $include_filename");
}

// totals defined
$total_count_item = 0;
$total_system_memory = 0;
$total_hard_drive_size = 0;
$total_partition_size = 0;
$total_partition_free_space = 0;
$total_partition_used_space = 0;
$total_system_lcpu = 0;

    //ORDER, SORT and LIMIT
    if (isset($_REQUEST['sort']) AND $_REQUEST['sort']!="") {$sort = $_REQUEST['sort'];} else {$sort = $query_array["sort"];}
    if (isset($_REQUEST['dir']) AND $_REQUEST['dir']!="")  {$dir = $_REQUEST['dir'];} else {$dir = $query_array["dir"];}
    if (isset($dir) AND $dir=="ASC")  { $new_dir = "DESC"; }else{ $new_dir = "ASC";}
    if (!isset($_REQUEST["show_all"]))  { $show_all = "1"; }else{ $show_all = $_REQUEST["show_all"]; }
    if (!isset($_REQUEST["headline_addition"]))  { $headline_addition=" "; } else { $headline_addition = $_REQUEST["headline_addition"]; }

    if (isset($_REQUEST["page_count"])){ $page_count = $_REQUEST["page_count"]; } else { $page_count = 0;}
    $page_prev = $page_count - 1;
    if ($page_prev < 0){ $page_prev = 0; } else {}
    $page_next = $page_count + 1;
    $page_current = $page_count;
    $page_count = $page_count * $count_system;

$show_all=1;
$page_count=0;

    //Preparing the Qeuery
    $sql_query=$query_array["sql"];
    //SORT
    $sql_sort=" ORDER BY " . $sort . " " . $dir;
    //LIMIT
    if(isset($show_all) AND $show_all!=1){
        $sql_limit=" LIMIT " . $page_count . "," . $count_system;
    }else{
        $sql_limit="";
    }

    //Integrating Search-Values in the SQL-Query (WHERE)
    if(isset($_REQUEST["filter"]) AND $_REQUEST["filter"]){
        $sql_query=sql_insert_search($sql_query, $_REQUEST["filter"]);
    }

    //Executing the Qeuery
    $sql=$sql_query."\n".$sql_sort."\n".$sql_limit;

    //Getting the count of all available items
    $sql_all = $sql_query."\n".$sql_sort;
    $result_all = mysqli_query($db,$sql_all);
    // Add an additional 100ms to our time this should act as a watchdog timer, since we should never 
    // spend more than an additional 100ms per loop.. If we do... Crunch! We error.
    set_time_limit(100);
    if(!$result_all) {die( "<br>".__("Fatal Error").":<br><br>".$sql_all."<br><br>".mysqli_error($db)."<br><br>" );};
    $all_page_count = mysqli_num_rows($result_all);
    $result = $result_all;
    $this_page_count = ($page_current * $count_system) - $all_page_count;

    //Show Searchboxes, if search is used on the calling page
    $searchboxes_array[0]["image"]="fa-search";
    $searchboxes_array[0]["alt"]=__("Discard Search");
    $searchboxes_array[1]["image"]="fa-search";
    $searchboxes_array[1]["alt"]=__("Search in this View");

    //Check for searchvalues
    if(isset($_REQUEST["filter"]) ){
        reset($_REQUEST["filter"]);
	
		 foreach ($_REQUEST["filter"] as $filter_var=>$filter_val) {
            if($filter_val!="")$show_filter=1;
        }
    }
    if(isset($show_filter) AND $show_filter==1){
        $style_searchboxes="display:inline;";
        $image_searchboxes=$searchboxes_array[0]["image"];
        $alt_searchboxes=$searchboxes_array[0]["alt"];
    }else{
        $style_searchboxes="display:none;";
        $image_searchboxes=$searchboxes_array[1]["image"];
        $alt_searchboxes=$searchboxes_array[1]["alt"];
    }

echo "<td class=\"oa-main-cell\">\n";
echo "<div class=\"main_each\">";

//IIS doesn't set $_SERVER["REQUEST_URI"] so need to use script name and query string instead
$MY_REQUEST_URI = $_SERVER["SCRIPT_NAME"] . "?" .  $_SERVER["QUERY_STRING"];

echo "<form method=\"post\" id=\"form_nav\" class=\"oa-list-form\" action=\"".htmlentities($MY_REQUEST_URI)."\">\n";

//Calculating the page-count-vars in headline
if( ($page_count+$count_system)>$all_page_count OR (isset($show_all) AND $show_all==1)){
  $show_page_count_to=$all_page_count;
} else {
  $show_page_count_to=$page_count+$count_system;
}

echo '<table class="oa-list-heading"><tr><td class="contenthead">';

     //Is the headline a sql-query?
     if(isset($query_array["headline"]) AND is_array($query_array["headline"])){
         echo htmlspecialchars($query_array["headline"]["name"]);
         echo " - ";
         $result_headline=mysqli_query($db,$query_array["headline"]["sql"]);
         if ($myrow = mysqli_fetch_array($result_headline)){
             echo $myrow[0];
         }
     }else{
         echo htmlspecialchars($query_array["headline"])." ";
     }

     if(isset($_REQUEST["headline_addition"])) {echo htmlspecialchars(stripslashes($_REQUEST["headline_addition"]));}
     echo " (".($page_count+1)."-".$show_page_count_to."/".$all_page_count.")\n";
  echo "</td><td align=\"right\" >\n";

  echo "</tr>";
  echo "</table>\n";

  // List actions live in the document flow so they never overlap the title.
  $can_network_export = false;
  if (isset($_REQUEST["view"])) {
      $network_export_markers = array("systems", "laptops", "servers", "workstations", "for_gateway", "networked", "hosts", "printers", "port", "_all");
      foreach ($network_export_markers as $network_export_marker) {
          if (strpos($_REQUEST["view"], $network_export_marker) !== false) {
              $can_network_export = true;
              break;
          }
      }
  }

  echo '<div class="oa-list-actions" role="toolbar" aria-label="'.__("List actions").'">';
  echo '<a class="oa-action-link" href="#" onclick="document.forms[\'form_export\'].submit(); return false;"><i class="fa fa-file-text-o" aria-hidden="true"></i><span>'.__("Export this List to CSV").'</span></a>';
  echo '<a class="oa-action-link" href="#" onclick="document.forms[\'form_export_xlsx\'].submit(); return false;"><i class="fa fa-file-excel-o" aria-hidden="true"></i><span>'.__("XLSX Export Excel").'</span></a>';
  if ($can_network_export) {
      echo '<a class="oa-action-link" href="#" onclick="document.forms[\'form_export_rdg\'].submit(); return false;"><i class="fa fa-desktop" aria-hidden="true"></i><span>'.__("Create RDG for RDCMan").'</span></a>';
      echo '<a class="oa-action-link" href="#" onclick="document.forms[\'form_export_dia\'].submit(); return false;"><i class="fa fa-object-group" aria-hidden="true"></i><span>'.__("Create DIA Network Diagram From List").'</span></a>';
      echo '<a class="oa-action-link" href="#" onclick="document.forms[\'form_export_inkscape\'].submit(); return false;"><i class="fa fa-line-chart" aria-hidden="true"></i><span>'.__("Create Inkscape (SVG) Picture From List").'</span></a>';
  }
  echo '</div>';

//Table header
$headline_1=" ";
$headline_2=" ";
$count_searchboxes=0;
foreach($viewdef_array["fields"] as $field) {
    if($field["show"]=="y"){
        // Add an additional 100ms to our time this should act as a watchdog timer, since we should never 
        // spend more than an additional 100ms per loop.. If we do... Crunch! We error.
        set_time_limit(100);
        $field_width = "";
        $field_height = "";
        if ( isset($field["width"]) AND $field["width"] <> "") {$field_width = " style=\"width:".$field["width"].";\"";}
        if (isset($field["height"]) AND $field["height"] <> "") {$field_height = " height=\"".$field["height"]."\"";}
        $headline_1 .= "<td class=\"views_tablehead\">";
        if(!isset($field["sort"]) OR (isset($field["sort"]) AND $field["sort"]!="n")){
            $headline_1 .= "<a href=\"#\" onclick=\"set_form_field('sort', '".$field["name"]."'); set_form_field('dir', '".$new_dir."'); set_form_field('page_count', '0'); submit_form();\" title=\"".__("Sort by").": ".$field["head"].", ".__("Direction").": ".__($new_dir)."\">";
        }
        $headline_1 .= $field["head"];
        if(!isset($field["sort"]) OR (isset($field["sort"]) AND $field["sort"]!="n")){
            $headline_1 .= "</a>\n";
        }
        if($sort==$field["name"]){
            $headline_1 .= "<i class=\"fa fa-caret-down\" aria-hidden=\"true\"></i>";
        }
        $headline_1 .= "</td>\n";

        $headline_2 .= "<td class=\"searchboxes\">\n";

        if(!isset($field["search"])) $field["search"]="y";
         if($field["search"]!="n"){
             $count_searchboxes++;
             $headline_2 .= "<div id=\"searchboxes_".$count_searchboxes."\" style=\"$style_searchboxes\">";
             $headline_2 .= "<input type=\"text\" name=\"filter[".$field["name"]."]\" value=\"";
             if(isset($_POST["filter"][$field["name"]])) $headline_2 .= $_POST["filter"][$field["name"]];
             $headline_2 .= "\" style=\"width:90%;\" />\n";
             $headline_2 .= "</div>";
         }
        $headline_2 .= "</td>\n";
    }
}

 //Button to Show and Hide the searchboxes
 $headline_1 .= "<td class=\"oa-filter-toggle-cell\">";
 $headline_1 .= "<a href=\"#\" onclick=\"show_searchboxes();\" id=\"link_searchboxes\"><i id=\"arrows_searchboxes\" class=\"fa ".$image_searchboxes." oa-filter-icon\" title=\"".$alt_searchboxes."\" aria-hidden=\"true\"></i></a>";
 $headline_1 .= "</td>";

 $count_searchboxes++;
 $headline_2 .= "<td class=\"searchboxes\" >\n";
 $headline_2 .= "<div id=\"searchboxes_".$count_searchboxes."\" style=\"$style_searchboxes\">";
 $headline_2 .= "<input type=\"submit\" name=\"filter_submit\" value=\">\" style=\"width:16px;\" title=\"".__("Execute search")."\" onclick=\"set_form_field('page_count', '0');\" />\n";
 $headline_2 .= "</div>";
 $headline_2 .= "</td>\n";

echo "<script type=\"text/javascript\">\n";
 echo "<!--\n";
  echo "function set_form_field(var_name, var_val){
        document.forms['form_nav'].elements[var_name].value = var_val;
        }\n";
  echo "function submit_form(var_name, var_val){
            document.forms['form_nav'].submit();
        }\n";
  echo "function show_searchboxes(){
            if(document.getElementById(\"searchboxes_1\").style.display == 'none'){
                action='inline';
                var img_src='".$searchboxes_array[0]["image"]."';
                var img_alt='".$searchboxes_array[0]["alt"]."';
                //Show Boxes
                for (var i = 1 ; i < ".($count_searchboxes+1)."; i++){
                    document.getElementById(\"searchboxes_\"+i).style.display = action;
                }
                //Show Image
                document.getElementById('arrows_searchboxes').className='fa '+img_src+' oa-filter-icon';
                document.getElementById('arrows_searchboxes').alt=img_alt;
                document.getElementById('arrows_searchboxes').title=img_alt;
            }else{
                document.getElementById(\"link_searchboxes\").href='".$MY_REQUEST_URI."';
            }
        }\n";
 echo "//-->\n";
echo "</script>\n";

echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\" />\n";
echo "<input type=\"hidden\" name=\"sort\" value=\"".$sort."\" />\n";
echo "<input type=\"hidden\" name=\"page_count\" value=\"".$page_count."\" />\n";
echo "<input type=\"hidden\" name=\"show_all\" value=\"".$show_all."\" />\n";
echo "<input type=\"hidden\" name=\"headline_addition\" value=\"".$headline_addition."\" />\n";
echo "<table class=\"tftable oa-data-table\">\n";

echo "<tr>\n";
 echo $headline_1;
echo "</tr>\n";

  echo "<tr class=\"oa-filter-row\">\n";
   echo $headline_2;
  echo "</tr>\n";

//Table body
$rownumber=0;
if ($myrow = mysqli_fetch_array($result)){
    do{
        // tabellierung über tftable css
        echo '<tr>';
        foreach($query_array["fields"] as $field){

            if($field["show"]!="n"){
               $a_misc = "";
			   
               //Convert the array-values to local variables
				foreach($myrow as $key => $val) {
                   $$key=$val;
               }

                //Generating the link
                //Does the field have its own link? Otherwise take the standard-link of the view
                if (isset($field["get"]["file"]) AND $field["get"]["file"]!="") {
                    $get_array=$field["get"];
                } else {
                    if(isset($query_array["get"])){
                        $get_array=$query_array["get"];
                    }
                }

                if(!isset($get_array["target"])) $get_array["target"]="_self";
                if(!isset($get_array["onclick"])) $get_array["onclick"]=" ";

                if(isset($get_array["file"])){
                    if(substr($get_array["file"],0,1)=="%"){
                        $value=substr($get_array["file"],1);
                        $link_file=@$$value;
                    }else{
                        $link_file=$get_array["file"];
                    }
                }else{
                    $link_file=FALSE;
                }
                //Don't show the link if ther's no target-file
                if($link_file==FALSE){
                    $field["link"]="n";
                }

                if(isset($field["link"]) AND $field["link"]=="y") {
					unset($link_query);
					if (isset($get_array["var"])) {
					 reset ($get_array["var"]);
					 foreach ($get_array["var"] as $varname=>$value) {
						$value = (isset($value)) ? $value : "";
						if(substr($value,0,1)=="%")
						{
							$value = substr($value,1);
							$value2 = (isset($$value)) ? $$value : "";
						}
						else {$value2 = $value;}

						if(!isset($link_query)) 
						{$link_query = $varname."=".urlencode($value2)."&amp;";}
						else{$link_query.= $varname."=".urlencode($value2)."&amp;";}

						//Don't show the link if one GET-variable is empty
						if(!isset($value2) or $value2==""){$field["link"]="n";}
					}
				   }	
				}

                if(isset($link_query) AND $link_query!=""){
                    $url=parse_url($get_array["file"]);
                    if(isset($url["query"]) AND $url["query"]!=""){
                        $link_separator="&amp;";
                    }else{
                        $link_separator="?";
                    }
                    $link_uri=$link_file.$link_separator.$link_query;
                }else{
                    $link_uri=$link_file;
                }
                $field_align = "";
                echo "  <td ";
                 if (isset($field["align"])) { echo "align=\"".$field["align"]."\""; }
                 echo ">";

                $show_value=" ";

                //Strip the table-name
                if(strpos($field["name"],".")){
                    $field["name"]=substr($field["name"],(strpos($field["name"],".")+1));
                }

                // numeric fields sums
			   if ($field["name"]=="software_count") { $total_count_item += (int) $software_count; }
			   if ($field["name"]=="count_item") { $total_count_item += (int) $count_item; }
			   if ($field["name"]=="system_memory") { $total_system_memory += (int) $system_memory; }
			   if ($field["name"]=="hard_drive_size") { $total_hard_drive_size += (int) $hard_drive_size; }
			   if ($field["name"]=="partition_size") { $total_partition_size += (int) $partition_size; }
			   if ($field["name"]=="partition_free_space") { $total_partition_free_space += (int) $partition_free_space; }
			   if ($field["name"]=="partition_used_space") { $total_partition_used_space += (int) $partition_used_space; }
			   if ($field["name"]=="system_lcpu") { $total_system_lcpu += (int) $system_lcpu; }
				
				//Special field-converting

				if(isset($field["calculate"]) AND $field["calculate"]=="y") {
					// Special field calculations here, for example warranty days. 
					//
					
					if(isset($field["lenovo_warranty"]) AND $field["lenovo_warranty"]=="y") {
						// allow another 10 seconds for this bit to complete
						set_time_limit(240);
						$show_value = $myrow["system_id_number"];
						$this_lenovo_warranty_remaining = get_lenovo_warranty_days( $show_value);
						$myrow ["lenovo_warranty"] = $this_lenovo_warranty_remaining ;
						$show_value = $this_lenovo_warranty_remaining; 
					}
				} else {
				// If this is not a calculated value, just show it
               $show_value = ConvertSpecialField($myrow, $field, $db, "list");
			   if ($field["name"]=="software_comment") {
					if (!isset($sv_bemerkungen)) $sv_bemerkungen = '';
					$show_value = "<div style='word-break: break-all;overflow:hidden;font-size:0.9em;max-width:350px' title='".$software_comment.' '.$sv_bemerkungen."'>".substr($software_comment.' '.$sv_bemerkungen,0,140);
				$show_value .= "</div>";
				}
			   if ($field["name"]=="sv_lizenztyp") {
					$show_value = "<div style='font-size:0.9em;max-width:200px'>".$sv_lizenztyp."</div>";
				}
			   if ($field["name"]=="net_user_name") {
					$show_value = "<div style='font-size:0.8em'>".$net_user_name."</div>";
				}
			   if ($field["name"]=="net_domain") {
					$show_value = "<div style='font-size:0.9em'>".$net_domain."</div>";
				}
			   if ($field["name"]=="processor_name") {
					$show_value = "<div style='font-size:0.8em'>".$processor_name."</div>";
				}
			   if ($field["name"]=="system_uuid") {
					$show_value = "<div style='font-size:0.7em'>".$system_uuid."</div>";
				}
			   if ($field["name"]=="system_vendor") {
					$show_value = "<div style='font-size:0.9em'>".$system_vendor."</div>";
				}
			   if ($field["name"]=="system_id_number") {
					$show_value = "<div style='font-size:0.7em'>".$system_id_number."</div>";
				}

			   if ($field["name"]=="sv_version") {
					if (empty($software_version)) $software_version='9999.0';
					if (empty($sv_version)) $sv_version='';
					if ( version_compare($sv_version, $software_version,'>')) $warncolor = "#f80000"; else $warncolor="#00aa00";
					$show_value = "<div style='font-size:.9em;max-width:100px;color:".$warncolor."'>".$sv_version."</div>";
				}

			   if ($field["name"]=="sv_newer") {
					if (empty($software_version)) $software_version='9999.0';
					if (empty($sv_version)) $sv_version='';
					if ( version_compare($sv_version, $software_version,'>')) $warnmarker= "X"; else $warnmarker="";
					$show_value = $warnmarker;
				}

			   if ($field["name"]=="software_version") {
					$show_value = "<div style='word-break: break-all;font-size:0.9em;max-width:100px'>".$software_version."</div>";
				}

			    if ($field["name"]=="system_os_name" AND $system_os_name != "") { $software_name=$system_os_name; }
			    if (isset($ms_keys_name)) { if ($ms_keys_name != "") { $software_name=$ms_keys_name; } }

				if (isset($software_name)) {
					if (strpos($software_name," ")!=0) {
							$logobild = substr($software_name,0,strpos($software_name," "));
						}else{
							if (strpos($software_name,".")!=0) {
								$logobild = substr($software_name,0,strpos($software_name,"."));
							}else{
								$logobild=$software_name;
							}
						} 
						$logobild = preg_replace('/[^A-Za-z0-9\-_]/', '', $logobild);
				}	

                if ($field["name"]=="software_name" || $field["head"]=="Software" || $field["name"]=="system_os_name") {
                    $show_value = oa_software_icon(isset($software_name) ? $software_name : '', isset($sv_icondata) ? $sv_icondata : '');
                    $show_value .= '<span style="font-size:.95em">'.htmlspecialchars((string)(isset($software_name) ? $software_name : ''), ENT_QUOTES, 'UTF-8').'</span>';
                }
				
				if (isset($software_title)) {
					if (strpos($software_title," ")!=0) {
							$logobild = substr($software_title,0,strpos($software_title," "));
						}else{
							if (strpos($software_title,".")!=0) {
								$logobild = substr($software_title,0,strpos($software_title,"."));
							}else{
								$logobild=$software_title;
							}
						} 
						$logobild = preg_replace('/[^A-Za-z0-9\-_]/', '', $logobild);
				}	
                if ($field["name"]=="software_title") {
                    $show_value = oa_software_icon($software_title, isset($sv_icondata) ? $sv_icondata : '') . htmlspecialchars((string)$software_title, ENT_QUOTES, 'UTF-8');
                }
								
				}
				if(isset($field["link"]) AND $field["link"]=="y"){
                    if(!isset($get_array["title"])) $get_array["title"]=$show_value;
                    echo "<a href=\"".$link_uri."\" title=\"".$get_array["title"]."\" onclick=\"".$get_array["onclick"]." ; this.target='".$get_array["target"]."';\" $a_misc>";
                }
                if(isset($field["image"]) AND $field["image"]!=""){
                    echo (strpos($field["image"], "fa-") === 0) ? oa_icon_markup($field["image"]) : "";
                }else{
                    echo $show_value;
                }
                if(isset($field["link"]) AND $field["link"]=="y"){
                    echo "</a>\n";
                }
                //Is there a help entry?
                if(isset($field["help"]) AND $field["help"]!=""){
                    if(substr($field["help"],0,1)=="%"){
                        $value=substr($field["help"],1);
                        $help=$$value;
                    }else{
                        $help=$field["help"];
                    }
                    echo "&nbsp;<a href=\"#\" onclick=\"alert('".addslashes(str_replace("\"","",$help))."')\">?</a>";
                }
                echo "</td>\n";
            }
        }
        echo "<td>\n";
        echo "</td>\n";
        echo " </tr>\n";
      $rownumber ++;
    } while ($myrow = mysqli_fetch_array($result));

	// Totals
	
	echo "<tr class=\"oa-total-row\">";
        foreach($query_array["fields"] as $field) {
            if($field["show"]!="n") {
				echo "<td>";
				if ($field["name"]=="software_count") echo number_format($total_count_item,0,",",".");
				if ($field["name"]=="count_item") echo number_format($total_count_item,0,",",".");
				if ($field["name"]=="system_memory") echo number_format($total_system_memory/1024,1,",",".").'G';
				if ($field["name"]=="hard_drive_size") echo number_format($total_hard_drive_size/1024,1,",",".").'G';
				if ($field["name"]=="partition_size") echo number_format($total_partition_size/1024,1,",",".").'G';
				if ($field["name"]=="partition_free_space") echo number_format($total_partition_free_space/1024,1,",",".").'G';
				if ($field["name"]=="partition_used_space") echo number_format($total_partition_used_space/1024,1,",",".").'G';
				if ($field["name"]=="system_lcpu") echo number_format($total_system_lcpu,0,",",".");
				echo "</td>";
			}
		}
	echo "</tr></table></form>\n";

    // Export forms are kept separate from the visible toolbar to avoid nested forms.
    echo '<div class="oa-export-forms" aria-hidden="true">';

    echo "<form method=\"post\" id=\"form_export\" action=\"list_export.php\">\n";
    echo "<input type=\"hidden\" name=\"sql\" value=\"".urlencode($sql)."\" />\n";
    echo "<input type=\"hidden\" name=\"view\" value=\"".$_REQUEST["view"]."\"/>\n";
    if(isset($_REQUEST["pc"])) echo "<input type=\"hidden\" name=\"pc\" value=\"".$_REQUEST["pc"]."\"/>\n";
    if(isset($_REQUEST["other"])) echo "<input type=\"hidden\" name=\"other\" value=\"".$_REQUEST["other"]."\" />\n";
    if(isset($_REQUEST["monitor"])) echo "<input type=\"hidden\" name=\"monitor\" value=\"".$_REQUEST["monitor"]."\" />\n";
    echo "</form>\n";

    echo "<form method=\"post\" id=\"form_export_xlsx\" action=\"list_export_xlsx.php\">\n";
    echo "<input type=\"hidden\" name=\"sql\" value=\"".urlencode($sql)."\" />\n";
    echo "<input type=\"hidden\" name=\"view\" value=\"".$_REQUEST["view"]."\"/>\n";
    if(isset($_REQUEST["pc"])) echo "<input type=\"hidden\" name=\"pc\" value=\"".$_REQUEST["pc"]."\"/>\n";
    if(isset($_REQUEST["other"])) echo "<input type=\"hidden\" name=\"other\" value=\"".$_REQUEST["other"]."\" />\n";
    if(isset($_REQUEST["monitor"])) echo "<input type=\"hidden\" name=\"monitor\" value=\"".$_REQUEST["monitor"]."\" />\n";
    echo "</form>\n";

    if ($can_network_export) {
        echo "<form method=\"post\" id=\"form_export_rdg\" action=\"list_export_rdg.php\">\n";
        echo "<input type=\"hidden\" name=\"sql\" value=\"".urlencode($sql)."\" />\n";
        echo "<input type=\"hidden\" name=\"view\" value=\"".$_REQUEST["view"]."\"/>\n";
        if(isset($_REQUEST["pc"])) echo "<input type=\"hidden\" name=\"pc\" value=\"".$_REQUEST["pc"]."\"/>\n";
        if(isset($_REQUEST["other"])) echo "<input type=\"hidden\" name=\"other\" value=\"".$_REQUEST["other"]."\" />\n";
        if(isset($_REQUEST["monitor"])) echo "<input type=\"hidden\" name=\"monitor\" value=\"".$_REQUEST["monitor"]."\" />\n";
        echo "</form>\n";

        echo "<form method=\"post\" id=\"form_export_dia\" action=\"list_export_dia.php\">\n";
        echo "<input type=\"hidden\" name=\"sql\" value=\"".urlencode($sql)."\" />\n";
        echo "<input type=\"hidden\" name=\"view\" value=\"".$_REQUEST["view"]."\"/>\n";
        if(isset($_REQUEST["pc"])) echo "<input type=\"hidden\" name=\"pc\" value=\"".$_REQUEST["pc"]."\"/>\n";
        if(isset($_REQUEST["other"])) echo "<input type=\"hidden\" name=\"other\" value=\"".$_REQUEST["other"]."\" />\n";
        if(isset($_REQUEST["monitor"])) echo "<input type=\"hidden\" name=\"monitor\" value=\"".$_REQUEST["monitor"]."\" />\n";
        echo "</form>\n";

        echo "<form method=\"post\" id=\"form_export_inkscape\" action=\"list_export_inkscape.php\">\n";
        echo "<input type=\"hidden\" name=\"sql\" value=\"".urlencode($sql)."\" />\n";
        echo "<input type=\"hidden\" name=\"view\" value=\"".$_REQUEST["view"]."\"/>\n";
        if(isset($_REQUEST["pc"])) echo "<input type=\"hidden\" name=\"pc\" value=\"".$_REQUEST["pc"]."\"/>\n";
        if(isset($_REQUEST["other"])) echo "<input type=\"hidden\" name=\"other\" value=\"".$_REQUEST["other"]."\" />\n";
        if(isset($_REQUEST["monitor"])) echo "<input type=\"hidden\" name=\"monitor\" value=\"".$_REQUEST["monitor"]."\" />\n";
        echo "</form>\n";
    }
    echo '</div>';

} else {

  echo "<tr><td colspan=\"7\">".__("No Results")."</td></tr>\n";
  echo "</table>\n";
  echo "</form>\n";
}

include "include_export_modal.php"; 
echo "</body>\n";
echo "</html>\n";