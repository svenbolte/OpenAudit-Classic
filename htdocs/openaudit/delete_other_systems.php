<?php
    $page = "";
    include "include.php";
    

    if (!empty($_POST['Perform'])) {
      foreach($_POST as $id) {

           $query = "DELETE FROM other WHERE other_id = '" . $id . "'";
           $result = mysqli_query($db,$query)  or die("Query failed at delete stage. groups");

           $query = "DELETE FROM nmap_ports WHERE nmap_other_id = '" . $id . "'";
           $result = mysqli_query($db,$query)  or die("Query failed at delete stage. nmap_ports");

      }
    }
	// SQL Query sortiert nach ältesten zuerst
    $sql = "SELECT other_id, other_ip_address, other_network_name, other_description, other_type, other_timestamp FROM other ORDER BY other_timestamp ";
    $result = mysqli_query($db,$sql);
     
    echo "<td style=\"vertical-align:top;width:100%\">
          <div class=\"main_each\">";

    if ($myrow = mysqli_fetch_array($result)){
      echo "<form name=\"DeleteList\" id=\"DeleteList\" method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\" >
           
            <script language=\"JavaScript\" TYPE=\"text/javascript\">
             function CheckUncheckAll(form){
               if(!form)
                 return;
               var objElements = form.elements;
               if(!objElements)
                 return;
               var countElements = objElements.length;
               if(!countElements)
                 return;
               else
                 for (var i = 1; i < countElements; i++){   
                   eval(\"objElements[\" + i + \"].checked = objElements[1].checked\"); 
                 }
              }
            </script>
     
              <table>
                <tr>
                   <td class=\"contenthead\">".__("Delete other devices")."</td></tr>
                </tr><td>
				   <a href=\"./list.php?view=other_all\"><i class=\"fa fa-sort\"></i>
  				    Für das Sortieren der Liste, Exportieren oder bitte hier klicken</a></td>
                </td></tr>
              </table>
             
              <table>
                <tr>
                   <td width=\"30%\"><input type=\"submit\" name=\"Perform\" id=\"Perform\" value=\"".__("Delete other devices")."\" onclick=\"return confirm('Do you really want to DELETE all selected other equipments?')\"></td>
                   <td width=\"70%\"><input type=\"checkbox\" name=\"SetUnset\" id=\"SetUnset\" onClick=\"CheckUncheckAll(this.form);\" />Check/Uncheck all<br /></td>   
                </tr>
              </table>

              <table class=\"tftable\"  class=\"tftable\" >
                <tr>&nbsp;</tr>
                <tr>
                   <td></td>
                   <td class=\"contentsubtitle\">".__("IP Address")."</td>
                   <td class=\"contentsubtitle\">".__("Hostname")."</td>
                   <td class=\"contentsubtitle\">".__("Description")."</td>
                   <td class=\"contentsubtitle\">".__("Type")."</td>
                   <td class=\"contentsubtitle\">".__("Date Audited")."</td>
                   <td class=\"contentsubtitle\"><i class=\"fa fa-sort-numeric-asc\"></i> ".__("age")."</td>
                </tr>\n";
	$xanzahl = 0;
      do {
          // tabellierung über tftable css
			$datetime1 = return_timestamp( $myrow["other_timestamp"] );
          if (strlen($myrow["other_type"]) > 20) {$typebild="router";} else {$typebild = str_replace(" ","_",$myrow["other_type"]);}
		  echo "<tr  >
                   <td width=\"5%\"><input type=\"checkbox\" name=" . $myrow["other_id"] . " id=" . $myrow["other_id"] . " value=" . $myrow["other_id"] . "></td>
                   <td><a href=\"system.php?other=".$myrow["other_id"]."&amp;view=other_system\">" . ip_trans($myrow["other_ip_address"]) . "</a></td>
                   <td>" . $myrow["other_network_name"] . "</td>
                   <td>" . $myrow["other_description"] . "</td>
                   <td><i class=\"fa fa-desktop\" aria-hidden=\"true\"></i></td>
                   <td>" . return_date_time($myrow["other_timestamp"]) . "</td>
                   <td>" . human_timing($datetime1) ."</td>
                </tr>\n";
				$xanzahl += 1;
        } while ($myrow = mysqli_fetch_array($result));
		// Summenzeile
	  echo "<tr><td><b>".$xanzahl."</b></td><td><b>Anzahl</b></td><td colspan=6></td></tr>";
      echo "  </table>
           </form>";
       
    } else {
        echo "<p class=\"content\">No Other Systems in database.</p>\n";
      }

    echo "</div>\n";
    
    echo "</td>\n";
    
?>