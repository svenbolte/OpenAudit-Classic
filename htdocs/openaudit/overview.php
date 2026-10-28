<?php
include_once("include.php");



require("overview_viewdef.php");

echo "<td style=\"vertical-align:top;width:100%\">\n";
  echo "<div class=\"main_each\">";

//Table header
$view_count=0;
foreach($query_array as $view_master) {

    $view_count++;

    $count_items_category=0;;
    foreach($view_master["views"] as $view) {

        if(isset($view["show"]) AND $view["show"]=="y"){

            //Executing the Qeuery
            $sql=$view["sql"]."ORDER BY ".$view["sort"];
            $result = mysqli_query($db,$sql);
            if(!$result) {die( "<br>".__("Fatal Error").":<br><br>".$sql."<br><br>".mysqli_error($db)."<br><br>" );};
            $this_page_count = mysqli_num_rows($result);

            //Table body
            $body=" ";
            $i=0;
            if ($myrow = mysqli_fetch_array($result)){

                //Table header
                unset($td_width);
                if(isset($view["headline"])){
                    $body .= $view["headline"];
                }
                $body .= "<table class=\"tftable\"    >\n";
                $body .= " <tr>\n";

                $colgroup = "<colgroup>\n";
                foreach($view["fields"] as $field) {
                    if(!isset($field["show"]) OR $field["show"]=="y"){
                        $body .= "<td nowrap class=\"views_tablehead\">";
                         $body .= $field["head"];
                        $body .= " </th>\n";;
                        if(is_array($view_master["td_width"]) AND isset($view_master["td_width"][$i]) AND $view_master["td_width"][$i]!="") {
                            $colgroup .= "<col width=\"".$view_master["td_width"][$i]."\">\n";
                            $i++;
                        }
                    }
                }
                $colgroup .= "</colgroup>\n";

                $body .= "<td nowrap class=\"views_tablehead\">&nbsp;</th>\n";;
                $body .= " </tr>\n";
                $body .= $colgroup;

                do{
                    $count_items_category++;

                    //Convert the array-values to local variables
					foreach($myrow as $key => $val) {
                        $$key=$val;
                    }

                    // tabellierung über tftable css
                    $body .= " <tr>\n";
                    foreach($view["fields"] as $field) {

                        if(!isset($field["show"]) OR $field["show"]=="y"){

                            //Generating the link
                            //Does the field has an own link? Otherwise take the standard-link of the view
                            if(isset($field["get"]) AND $field["get"]["file"]!=""){
                                $get_array=$field["get"];
                            }else{
                                $get_array=$view["get"];
                            }

                            if(substr($get_array["file"],0,1)=="%"){
                                $value=substr($get_array["file"],1);
                                $link_file=$$value;
                            }else{
                                $link_file=$get_array["file"];
                            }
                            //Don't show the link if it's empty
                            if($link_file==""){
                                $field["link"]="n";
                            }
                            if(isset($field["link"]) AND $field["link"]=="y"){
                                unset($link_query);
                                @reset ($get_array["var"]);
                               	foreach ($get_array["var"] as $varname=>$value) {
                                    if(substr($value,0,1)=="%"){
                                        $value=substr($value,1);
                                        $value2=$$value;
                                    }else{
                                        $value2=$value;
                                    }
                                    if(!isset($link_query)) {
                                        $link_query = $varname."=".urlencode($value2)."&amp;";
                                    }else{
                                        $link_query.= $varname."=".urlencode($value2)."&amp;";
                                    }
                                    //Don't show the link if a GET-variable is empty
                                    if($value2==""){
                                        $field["link"]="n";
                                    }
                                }
                            }
                            if($link_query!=""){
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

                            //Special field-converting
                            unset($show_value);
                            if($field["name"]=="system_os_name"){
                                $show_value=determine_os($myrow[$field["name"]]);
                            }elseif($field["name"]=="system_timestamp"){
                                $show_value=return_date($myrow[$field["name"]]);
                            }elseif($field["name"]=="software_first_timestamp" OR
                                $field["name"]=="software_timestamp" OR
                                $field["name"]=="system_first_timestamp" OR
                                $field["name"]=="system_audits_timestamp"){
                                $show_value=return_date($myrow[$field["name"]]);
                            }elseif($field["name"]=="system_system_type"){
                                $show_value=determine_img($myrow["system_os_name"],$myrow[$field["name"]]);
                            }elseif($field["name"]=="other_ip_address"){
                                if($myrow[$field["name"]]==""){
                                    $show_value="Not-Networked";
                                }else{
                                    $show_value=$myrow[$field["name"]];
                                }
                            }elseif($field["name"]=="partition_free_space" OR
                                    $field["name"]=="partition_size"){
                                $show_value=$myrow[$field["name"]]." ".__("MB");
                            }else{
                                $show_value=$myrow[$field["name"]];
                            }

                            if(!isset($field["align"])) $field["align"]=" ";
                            $body .= " <td style=\"padding-right:10px;\" align=\"".$field["align"]."\">\n";
                             if(isset($field["link"]) AND $field["link"]=="y"){
                                 if(!isset($get_array["target"])) $get_array["target"]="_TOP";
                                 if(!isset($get_array["title"])) $get_array["title"]=" ";
                                 if(!isset($get_array["onClick"])) $get_array["onClick"]=" ";
                                 $body .= "<a href=\"".$link_uri."\"target=\"".$get_array["target"]."\" title=\"".$get_array["title"]."\" onClick=\"".$get_array["onClick"]."\">\n";
                             }
                             $body .= $show_value;
                             if(isset($field["link"]) AND $field["link"]=="y"){
                                 $body .= " </a>\n";
                             }
                            $body .= " </td>\n";
                        }
                    }
                    $body .= " <td  >&nbsp;</td>\n";
                    $body .= " </tr>\n";

                }while ($myrow = mysqli_fetch_array($result));

                // tabellierung über tftable css
                if(!isset($field_align)) $field_align=" ";
                $body .= "<tr>\n";
                 $body .= "<td $field_align style=\"padding-right:10px;\" colspan=\"10\">\n";
                  $body .= "&nbsp;";
                 $body .= "</td>\n";
                $body .= "</tr>\n";
                $body .= "</table>\n";
            }
        }

    }

    //Headline
    $buffer = "<div class=\"main_each\">";

    $buffer .= "<table class=\"tftable\"     >\n";
     $buffer .= "<tr\n>";
      $buffer .= "<td class=\"contenthead\" width=\"450\">\n";
       $buffer .= "<a href=\"javascript://\" onclick=\"switchUl('f".$view_count."');\">\n";
        $buffer .= $view_master["headline"]."\n";
       $buffer .= "</a>\n";
      $buffer .= "</td\n>";
      $buffer .= "<td class=\"contenthead\" width=\"30\" align=\"right\">\n";
       $buffer .= $count_items_category;
      $buffer .= "</td\n>";
      $buffer .= "<td align=\"right\"><a href=\"javascript://\" onclick=\"switchUl('f".$view_count."');\"><i class=\"fa fa-arrow-down\" aria-hidden=\"true\"></i></a></td\n>";
     $buffer .= "</tr\n>";
    $buffer .= "</table\n>";

    $buffer .= "<div style=\"display:none;\" id=\"f".$view_count."\">\n";

    $buffer .= $body;

    $buffer .= "</div>";
    $buffer .= "</div>";

    echo $buffer;

    unset($body);
    unset($buffer);

}

  echo "</div>\n";

  

 echo "</td>\n";

echo "</body>\n";
echo "</html>\n";
?>
