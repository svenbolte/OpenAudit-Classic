<?php
include_once("include.php");


// If you would like to have a new View, you have to modify 3 parts:
// -> include_menu_array.php: $menue_array
// -> system_viewdef_X.php: "Table and fields to select and show"
// -> option: include_functions.php: special_field_converting()

//Include the view-definition
if(isset($_REQUEST["view"]) AND $_REQUEST["view"]!=""){
    $include_filename = "system_viewdef_".$_REQUEST["view"].".php";
}else{
    $include_filename = "system_viewdef_summary.php";
}
if(is_file($include_filename)){
    include_once($include_filename);
    $viewdef_array=$query_array;
}else{
    die(__("FATAL: Could not find view")." ".$include_filename);
}

if(isset($_GET["pc"]) AND $_GET["pc"]!=""){
    $pc=$_GET["pc"];
}

//Convert GET[category] to an array
if(isset($_REQUEST["category"]) AND $_REQUEST["category"]!=""){
    $array_category=explode(",",$_REQUEST["category"]);
}

//If someone wants to edit Systems Manual-Data, one entry has to created IF there is none
//This is because the fields are only shown, if the sql-guery gets an result
if(isset($_REQUEST["pc"]) AND
   isset($_REQUEST["view"]) AND $_REQUEST["view"]=="summary" AND
   isset($_REQUEST["category"]) AND $_REQUEST["category"]=="manual" AND
   isset($_REQUEST["edit"]) AND $_REQUEST["edit"]=="1" )
   {
    $sql_man="SELECT system_man_id FROM `system_man` WHERE `system_man_uuid` = '".$_REQUEST["pc"]."'; ";
    $result_man=mysqli_query($db,$sql_man);
    $man_count = mysqli_num_rows($result_man);
    if($man_count<1){
        $sql_man="INSERT INTO `system_man` ( `system_man_uuid` ) VALUES ( '".$_REQUEST["pc"]."' ); ";
        $result_man=mysqli_query($db,$sql_man);
        if(!$result_man) { echo "<br>".__("Fatal Error").":<br><br>".$sql_man."<br><br>".mysqli_error($db)."<br><br>";
                       echo "<pre>";
                       print_r($_REQUEST);
                       die();
                     };
    }
}

echo "<td style=\"vertical-align:top;width:100%\">\n";
  echo "<div class=\"main_each\">";

  if(isset($query_array["name"]) AND $query_array["name"]!=""){

      echo "<table  style=\"height: 30px\"><tr><td class=\"contenthead\">\n";
         //Is the headline a sql-query?
         if(isset($query_array["name"]) AND is_array($query_array["name"])){
             echo htmlspecialchars($query_array["name"]["name"]);
             echo " - ";
             $result_headline=mysqli_query($db,$query_array["name"]["sql"]);
             if ($myrow = mysqli_fetch_array($result_headline)){
                 echo $myrow[0];
                 echo "</td></tr>";
             }
         }else{
              echo htmlspecialchars($query_array["name"]);
         }
          if(isset($_REQUEST["headline_addition"])) {echo htmlspecialchars($_REQUEST["headline_addition"]);}
      echo "</td></tr></table>\n";
 }
 
//Delete undisplayed categories from $query_array, if a certain category is given
if(isset($array_category) AND is_array($array_category) AND $_REQUEST["category"]!=""){
    reset($query_array["views"]);

//		 while ( list ($key, $value) = each ($clone) ) {
//		 foreach ($clone as $key => $value) {


	foreach ($query_array["views"] as $viewname=>$viewdef_array) {		
        if(!in_array($viewname, $array_category)){
            unset($query_array["views"][$viewname]);
        }
    }
}

//Show each Category
reset($query_array["views"]);
foreach ($query_array["views"] as $viewname=>$viewdef_array) {

    //Executing Query
    $sql=$viewdef_array["sql"];
    $result=mysqli_query($db,$sql);
    if(!$result) { echo "<br>".__("Fatal Error").":<br><br>".$sql."<br><br>".mysqli_error($db)."<br><br>";
                   echo "<pre>";
                   echo "REQUEST:<br>";
                   print_r($_REQUEST);
                   echo "VIEWDEF:<br>";
                   print_r($viewdef_array);
                   die();
                 };
    $this_page_count = mysqli_num_rows($result);


        echo '<table ><tr>';
          //Image
          if(isset($viewdef_array["image"]) AND $viewdef_array["image"]!=""){
              echo "<td class=\"contentsubtitle\" style=\"width: 60px;\">\n";
              if(isset($viewdef_array["image_width"]) AND $viewdef_array["image_width"]!=""){
              $this_width = $viewdef_array["image_width"];
              } else {
              $this_width = "48";
              }
             if(isset($viewdef_array["image_height"]) AND $viewdef_array["image_height"]!=""){
              $this_height = $viewdef_array["image_height"];
              } else {
              $this_height = "48";
              }
               echo "<img src=\"" .$viewdef_array["image"]. "\" alt=\"\" style=\"border:0px;\" width=\"".$this_width."\" height=\"".$this_height."\"  />\n";
              echo "</td>\n";
          }
          //Headline
          if(isset($viewdef_array["headline"]) AND $viewdef_array["headline"]!=""){
              echo "<td class=\"contentsubtitle\">\n";
              if(isset($_REQUEST["category"]) AND $_REQUEST["category"]==""){
                  echo "<a href=\"".$_SERVER["PHP_SELF"]."?pc=".$_REQUEST["pc"]."&amp;view=".$_REQUEST["view"]."&amp;category=".$viewname."\">";
              }
               echo $viewdef_array["headline"]."\n";
              if(isset($_REQUEST["category"]) AND $_REQUEST["category"]==""){
                  echo "</a>";
              }
              echo "</td>\n";
          }
          echo "</tr>\n";
        echo "</table>\n";

    echo "<form id=\"v".$viewname."\" method=\"post\" action=\"system_post.php\">\n";
    echo "<p>\n";
    if(isset($_REQUEST["pc"])){
        echo "  <input type=\"hidden\" name=\"pc\" value=\"".$_REQUEST["pc"]."\" />";
    }
    if(isset($_REQUEST["category"])){
        echo "  <input type=\"hidden\" name=\"category\" value=\"".$_REQUEST["category"]."\" />";
    }
    if(isset($_REQUEST["view"])){
        echo "  <input type=\"hidden\" name=\"view\" value=\"".$_REQUEST["view"]."\" />";
    }
    if(isset($_REQUEST["other"])){
        echo "  <input type=\"hidden\" name=\"other\" value=\"".$_REQUEST["other"]."\" />";
    }
    if(isset($_REQUEST["monitor"])){
        echo "  <input type=\"hidden\" name=\"monitor\" value=\"".$_REQUEST["monitor"]."\" />";
    }
    if(isset($_REQUEST["user"])){
        echo "  <input type=\"hidden\" name=\"user\" value=\"".$_REQUEST["user"]."\" />";
    }
    echo "</p>\n";
    echo "<table class=\"tftable\"    >\n";

    //IF Horizontal Table-Layout
    if(isset($viewdef_array["table_layout"]) AND $viewdef_array["table_layout"]=="horizontal"){
        echo "<tr>\n";
        foreach($viewdef_array["fields"] as $field){
            echo "<td class=\"system_tablehead\">\n";
             echo $field["head"];
            echo "</td>\n";
        }
        echo "</tr>\n";
    }

    //Reset Background
    if ($myrow = mysqli_fetch_array($result)){
        do{
            //Convert the array-values to local variables
			foreach($myrow as $key => $val) {
                $$key=$val;
            }

            //IF Horizontal Table-Layout
            if(isset($viewdef_array["table_layout"]) AND $viewdef_array["table_layout"]=="horizontal"){
                // tabellierung über tftable css
                echo "<tr>\n";
            }

            $edit_category="0";
            foreach($viewdef_array["fields"] as $field){
                if(!isset($field["show"]) OR $field["show"]!="n"){

                    //Generating the link, if its configured
                    if(isset($field["get"]["var"]) AND is_array($field["get"]["var"])){
                        unset($link_query);
                        $link_query = "";
                        @reset ($field["get"]["var"]);
                        foreach ($field["get"]["var"] as $varname=>$value) {

							if(substr($value,0,1)=="%"){
                                $value=substr($value,1);
                                $value2=$$value;
                            }else{
                                $value2=$value;
                            }
                            $link_query.= $varname."=".urlencode($value2)."&amp;";
                            //Don't show the link if a GET-variable is empty
                            if($value2==""){
                                unset($field["get"]);
                            }
                        }
                    }

                    $show_value = ConvertSpecialField($myrow, $field, $db, "system");

                    //IF Horizontal Table-Layout
                    if(isset($viewdef_array["table_layout"]) AND $viewdef_array["table_layout"]=="horizontal"){
                        if(!isset($field["align"])) $field["align"]="left";
                        echo "<td align=\"".$field["align"]."\" class=\"system_tablebody_left\" >\n";
                         echo $show_value;
                        echo "</td>\n";
                    }else{
                        // tabellierung über tftable css
                        echo "<tr>\n";
                         if (!isset($field["align"])) { $field["align"] = "left"; }
                         echo "<td align=\"".$field["align"]."\" class=\"system_tablebody_left\" >";
                           echo $field["head"];
                           if($field["head"]!=""){
                               echo ":";
                           }else{
                               echo "&nbsp;";
                           }

                          echo "</td>\n";
                         echo "<td align=\"".$field["align"]."\" class=\"system_tablebody_right\">";

// 					if (isset ($system_os_name)) { if (strpos($system_os_name," ")!=0) {$logobild = substr($system_os_name,0,strpos($system_os_name," "));}else{$logobild=$system_os_name;} 
//						// if (strlen($logobild)>4) {$show_value = $logobild;}
//					 }
//					if (isset ($system_os_name)) { if (($field["name"]=="system_os_name") and is_file("softwarelogos/".$logobild.".png")){
//					   $vendorlogo = "<img src=\"softwarelogos/".$logobild.".png\" style=\"border:0px;\" alt=\"\" /> ";
//					} else {$vendorlogo="";} }
//					echo ($vendorlogo);

					// nur bei view = summary	
					if ($_GET['view']=="summary" and isset($system_vendor) )  {
						if (isset($field["name"]) and ($field["name"]=="Bilder")) {echo ("<div id=\"qrfeld\">"); }
						if (isset($field["name"])) {
							if (strpos($system_vendor," ")!=0) {$logobild = substr($system_vendor,0,strpos($system_vendor," "));}else{$logobild=$system_vendor;}
							if ($field["name"]=="Bilder" and is_file("softwarelogos/".$logobild.".jpg")){
								$vendorlogo = "<img src=\"softwarelogos/".$logobild.".jpg\" style=\"border:0px;\" alt=\"\" /> ";
							} else {$vendorlogo="";}
						echo ($vendorlogo);
						}
					}	

 					//if (strpos($system_system_type," ")!=0) {$logobild = substr($system_system_type,0,strpos($system_system_type," "));}else{$logobild=$system_system_type;}
					 // if (strlen($logobild)>4) {$show_value = $logobild;}
//					if (($field["name"]=="Bilder") and is_file("images/".$logobild.".png")){
//					   $vendorlogo = "<img src=\"images/".$logobild.".png\" style=\"border:0px;\" alt=\"\" /> ";
//					} else {$vendorlogo="";}
//					echo ($vendorlogo);

if (isset($field["name"]) and ($field["name"]=="Bilder")) {
					// Jscript QR-Code einbauen ************************************************
?>
<!-- 	<div id="qrfeld">    -->
		 &nbsp; &nbsp; &nbsp; &nbsp;
		<script src="jsqr-0.2-min.js" type="text/javascript"></script>
		<script type="text/javascript">
		// Text
		var qr = new JSQR();
		var code = new qr.Code();
		code.encodeMode = code.ENCODE_MODE.UTF8_SIGNATURE;
		code.version = code.DEFAULT;
		code.errorCorrection = code.ERROR_CORRECTION.M;
		var input = new qr.Input();
		input.dataType = input.DATA_TYPE.TEXT;
		input.data = {
			 "text": "<?php echo $system_vendor . " ; ". $system_system_type . " ; " . $system_model . " ; " . $system_id_number . " ; " . $system_name . " ; " . $system_description  . " ; " . $system_uuid . " ; "   ; ?>"
		};
		var matrix = new qr.Matrix(input, code);
		var canvas = document.createElement('canvas');
		matrix.scale = 2;
		canvas.setAttribute('width', matrix.pixelWidth);
		canvas.setAttribute('height', matrix.pixelWidth);
		canvas.getContext('2d').fillStyle = 'rgb(0,0,0)';
		matrix.draw(canvas, 0, 0);
		document.getElementById("qrfeld").appendChild(canvas);
	</script>
	</div>
<?php
// Jscript QR-Code einbauen Ende ************************************************
}
					
					
						 
						 if(isset($field["get"]) AND is_array($field["get"]) AND isset($field["get"]["file"])){
                               echo "<a href=\"".$field["get"]["file"]."?".$link_query."\" title=\"".$field["get"]["title"]."\"";
                               if(isset($field["get"]["target"])) {
                                 echo " onclick=\"this.target='" . $field["get"]["target"] . "';\"";
                               }
                               echo ">\n";
                               if(isset($field["get"]["image"]) AND $field["get"]["image"]!="") {
                                   echo "<img src=\"".$field["get"]["image"]."\" alt=\"\" style=\"border:0px;\"";
                                   if(isset($field["get"]["image_width"]) AND $field["get"]["image_width"]!="") {
                                       echo " width=\"".$field["get"]["image_width"]."\" ";
                                   }
                                   if(isset($field["get"]["image_height"]) AND $field["get"]["image_height"]!="") {
                                       echo " height=\"".$field["get"]["image_height"]."\" ";
                                   }
                                   echo "/>";
                                   echo "&nbsp;";
                               }
                               if(isset($field["get"]["head"]) AND $field["get"]["head"]!=""){
                                   echo $field["get"]["head"];
                               }
                               echo "</a>\n";
                           }else{
                               //Form-Fields
                               if(isset($field["edit"]) AND $field["edit"]=="y" AND isset($_REQUEST["edit"])){
                                   if(!isset($field["edit_type"])) $field["edit_type"]="text";
                                   SWITCH($field["edit_type"]){
                                       case "textarea":
                                           echo "<textarea name=\"".$field["name"]."\" style=\"width:300px\" rows=\"6\" cols=\"30\" class=\"for_forms\">".$show_value."</textarea>\n";
                                       break;
                                       case "select":
                                           echo "<select name=\"".$field["name"]."\" style=\"width:300px\" class=\"for_forms\">\n";
                                            echo "<option value=\"\">".__("None")."</option>\n";
                                            $result2 = mysqli_query($db,$field["edit_sql"]);
                                            if ($myrow2 = mysqli_fetch_array($result2)){
                                                do {
                                                    if($myrow2[0]==$myrow[$field["name"]]) $selected="selected"; else $selected=" ";
                                                    echo "<option value=\"".$myrow2[0]."\" $selected>".$myrow2[1]."</option>\n";
                                                } while ($myrow2 = mysqli_fetch_array($result2));
                                            }
                                           echo "</select>\n";
                                       break;
                                       case "select_static":
                                           echo "<select name=\"".$field["name"]."\" style=\"width:300px\" class=\"for_forms\">\n";
											foreach ($field["select_static_values"] as $varname=>$value) {
                                               if($value==$myrow[$field["name"]]) $selected="selected"; else $selected=" ";
                                               echo "<option value=\"".$value."\" $selected>".$varname."</option>\n";
                                           }
                                           echo "</select>\n";
                                       break;
                                       case "password":
                                           echo "<input type=\"password\" style=\"width:300px\" name=\"".$field["name"]."\" value=\"".$show_value."\" class=\"for_forms\" />";
                                           echo "<input type=\"hidden\" style=\"width:300px\" name=\"".$field["name"]."_old\" value=\"".$show_value."\" class=\"for_forms\" />";
                                       break;
                                       case "text":
                                           echo "<input type=\"text\" style=\"width:300px\" name=\"".$field["name"]."\" value=\"".$show_value."\" class=\"for_forms\" />";
                                       break;
                                   }
                               }else{
                                   echo $show_value;
                               }
                           }
                         echo "</td>\n";
                        echo "</tr>\n";
                    }
                }
            }
            //IF Horizontal Table-Layout
            if(isset($viewdef_array["table_layout"]) AND $viewdef_array["table_layout"]=="horizontal"){
                echo "</tr>\n";
            }

				
            //Links to Manufacturer
            if(isset($myrow["system_vendor"]) AND $myrow["system_vendor"]!="" AND ($viewname=="summary" OR $viewname=="chassis")){
                // tabellierung über tftable css
                echo "<tr>\n";
                 echo "<td>\n";
                  echo __("Links to Manufacturer");
                  echo ": &nbsp;";
                  echo "<a href=\"#\" onclick=\"alert('".__("Regarding to Modell # and Serial #")."');\">?</a>";
                 echo "</td>\n";
                 echo "<td>\n";

                     if ($myrow["system_vendor"] == "LENOVO" || $myrow["system_vendor"] == "Lenovo" ) {
                       if(isset($myrow["system_id_number"]) AND $myrow["system_id_number"]!=""){
                           echo " <a href='https://pcsupport.lenovo.com/de/de/products/" . $myrow["system_id_number"] . "/warranty' onclick=\"this.target='_blank';\">".__("Warranty Information")."</a>";
                           echo " | ";
                           echo " <a href='https://pcsupport.lenovo.com/de/de/products/" . $myrow["system_id_number"] . "/downloads/driver-list' onclick=\"this.target='_blank';\">".__("Drivers &amp; Software")."</a>";
                           echo " | ";
                           echo " <a href='https://pcsupport.lenovo.com/de/de/products/" . $myrow["system_id_number"] . "' onclick=\"this.target='_blank';\">".__("system overview")."</a>";
                           $links_to_manu=1;
                       }
                       // Added Hewlett-Packard (AJH 14th April 2009)
                     } elseif ($myrow["system_vendor"] == "Compaq" || $myrow["system_vendor"] == "HP" || $myrow["system_vendor"] == "Hewlett-Packard"){
                       if(isset($myrow["system_id_number"]) AND $myrow["system_id_number"]!=""){
                           echo " <a href='http://www4.itrc.hp.com/service/ewarranty/warrantyResults.do?BODServiceID=NA&amp;RegisteredPurchaseDate=&amp;country=GB&amp;productNumber=&amp;serialNumber1=" . $myrow["system_id_number"] . "' onclick=\"this.target='_blank';\">".__("Warranty Information")."</a>";
                           echo " / ";
                           echo " <a href='http://h20180.www2.hp.com/apps/Lookup?h_lang=en&amp;h_cc=uk&amp;cc=uk&amp;h_page=hpcom&amp;lang=en&amp;h_client=S-A-R135-1&amp;h_pagetype=s-002&amp;h_query=" . $myrow["system_model"] . "' onclick=\"this.target='_blank';\">".__("Drivers &amp; Software")."</a>";
                           $links_to_manu=1;
                       }
                      } elseif ($myrow["system_vendor"] == "IBM" || $myrow["system_vendor"] == "LENOVO") {
                       if(isset($myrow["system_id_number"]) AND $myrow["system_id_number"]!=""){
                           echo " <a href='http://www-307.ibm.com/pc/support/site.wss/quickPath.do?quickPathEntry=" . $myrow["system_model"] . "' onclick=\"this.target='_blank';\">".__("Product Page")."</a>";
                           echo " / ";
                           $links_to_manu=1;
                       }
                       if(isset($myrow["system_model"]) AND $myrow["system_model"]!= "" AND isset($myrow["system_id_number"]) AND $myrow["system_id_number"]!=""){
                           echo " <a href='http://www-307.ibm.com/pc/support/site.wss/warrantyLookup.do?type=".substr($myrow["system_model"],0,4)."&amp;serial=".$myrow["system_id_number"]."&amp;country=897&amp;iws=off&amp;sitestyle=lenovo' onclick=\"this.target='_blank';\">".__("Warranty Information 1")."</a>";
                           echo " / ";
                           echo " <a href='http://www-307.ibm.com/pc/support/site.wss/warrantyLookup.do?type=".substr($myrow["system_model"],-9,-5)."&amp;serial=".$myrow["system_id_number"]."&amp;country=897&amp;iws=off&amp;sitestyle=lenovo' onclick=\"this.target='_blank';\">".__("Warranty Information 2")."</a>";   
                           $links_to_manu=1;
                       }                  } elseif ($myrow["system_vendor"] == "Gateway") {
                       if(isset($myrow["system_id_number"]) AND $myrow["system_id_number"]!=""){
                           echo " <a href='http://support.gateway.com/support/allsysteminfo.asp?sn=" . $myrow["system_id_number"] . "' onclick=\"this.target='_blank';\">".__("Support Page")."</a>";
                           $links_to_manu=1;
                       }
                     }
                     if(!isset($links_to_manu)){
                       echo __("No Links configured for this Manufacturer");
                     }
					 
                 echo "</td>\n";
                        //
                // tabellierung über tftable css
                echo "<tr>\n";
                 echo "<td>\n";
                  echo __("What does it look like");
                  echo ": &nbsp;";
                  //echo "<a href=\"#\" onclick=\"alert('".__("Google image search for ").$myrow["system_model"]."');\">?</a>";
                 echo "</td>\n";
                 echo "<td>\n";
                //
                 echo " <a href='http://images.google.com/images?q=" . $myrow["system_vendor"]." ".$myrow["system_model"] . "&safe=active&imgtype=photo&as_st=y' onclick=\"this.target='_blank';\">".__("Show me some pictures of the "). $myrow["system_model"] ."</a>";
                 echo "<td>\n";
                //
                echo "</tr>\n";
            }
            //IF Horizontal Table-Layout
            if(isset($viewdef_array["table_layout"]) AND $viewdef_array["table_layout"]=="horizontal"){}else{
                // tabellierung über tftable css
                echo "<tr><td class=\"system_tablebody_right\" colspan=\"2\">&nbsp;</td></tr>\n";
            }
        }while ($myrow = mysqli_fetch_array($result));
    } else {
        echo "<tr>\n";
         echo "<td style=\"padding-right:10px;\" colspan=\"20\">";
          echo __("No Results");
         echo "</td>\n";
        echo "</tr>\n";

        echo "<tr><td style=\"padding-right:10px;\" colspan=\"2\">&nbsp;</td></tr>\n";
    }

     //Edit- and Submit-Button
     if(isset($viewdef_array["edit"]) AND $viewdef_array["edit"]=="y"){
         echo "<tr>\n";
          echo "<td colspan=2>\n";
           if(isset($_REQUEST["edit"]) AND $_REQUEST["edit"]==1){
        echo "<input type=\"submit\" name=\"save\" value=\"".__("Save")."\" />";
           } else {
        echo "<input type=\"button\" name=\"edit\" value=\"Edit\"";
        echo "onclick=\"window.location.href='".$_SERVER["PHP_SELF"]."?";
        if(isset($_REQUEST["pc"])){
            echo "pc=".$_REQUEST["pc"]."&amp;";
        }elseif(isset($_REQUEST["other"])){
            echo "other=".$_REQUEST["other"]."&amp;";
        }elseif(isset($_REQUEST["monitor"])){
            echo "monitor=".$_REQUEST["monitor"]."&amp;";
        }elseif(isset($_REQUEST["user"])){
            echo "user=".$_REQUEST["user"]."&amp;";
        }else{
            die(__("FATAL: There's no ID-variable to identify the item. I.e pc or other"));
        }
        echo "view=".$_REQUEST["view"]."&amp;category=".$viewname."&amp;edit=1';\" />";
           }
          echo "</td>\n";
         echo "</tr>\n";

         echo "<tr><td class=\"system_tablebody_right\" colspan=\"2\">&nbsp;</td></tr>\n";
    }

    //IF Horizontal Table-Layout
    if(isset($viewdef_array["table_layout"]) AND $viewdef_array["table_layout"]=="horizontal"){
        // tabellierung über tftable css
        echo "<tr><td class=\"system_tablebody_right\" colspan=\"10\">&nbsp;</td></tr>\n";
    }

    echo "</table>";
    echo "</form>\n";
}


  echo "</div><br>\n";
  

 echo "</td>\n";
// 
include "include_export_modal.php"; 
echo "</body>\n";
echo "</html>\n";



?>
