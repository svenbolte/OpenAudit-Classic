<?php
// 
// Set up the Right hand search box and menu.


//echo "<td style=\"width:170px;\" valign=\"top\" align=\"center\" id=\"rightnav\">\n";

// echo "<div id='right_col' class=\"main_each\">\n";


//
// Show the Right hand menu, if we have a PC selected.    zurzeit disabled
//


    if(isset($pc) AND $pc!=""){
        $i=0;
        echo "<br />";
        echo "<a href=\"system.php?pc=$pc&amp;view=summary\">\n<b>" . $name . "</b></a>\n";
        echo "<table class=\"tftable\"  cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
        require_once("include_menu_array.php");
        reset ($menue_array["machine"]);
		foreach($menue_array["machine"] as $key_1 => $topic_item) {
            $i++;
            echo "<tr>\n";
            echo "<td align=\"left\" style=\"width:20px;\">\n";
            echo (strpos($topic_item["image"], "fa-") === 0) ? '<i class="fa '.htmlspecialchars($topic_item["image"], ENT_QUOTES, 'UTF-8').'" aria-hidden="true"></i>' : '';
            echo "</td>\n";

            echo "<td>\n";
            echo "<a href=\"".$topic_item["link"]."\"";
            if (isset($topic_item["css-class"])) {
              echo " class=\"".$topic_item["css-class"]."\"";
            }
            echo "/>\n";
            echo __($topic_item["name"]);
            echo "</a>\n";
            echo "</td>\n";

            if(isset($topic_item["childs"]) AND is_array($topic_item["childs"])){
                echo "<td>\n";
                 echo "<a href=\"javascript://\" onclick=\"switchUl('m".$i."');\">+</a>\n";
                echo "</td>\n";

                echo "</tr>\n";
                echo "<tr>\n";
                echo "<td colspan=\"3\">\n";

                echo "<div style=\"display:none; margin:7px;\" id=\"m".$i."\">\n";
                @reset ($topic_item["childs"]);

				foreach ($topic_item["childs"] as $key_2=>$child_item_2) {	
                    echo "<a href=\"".$child_item_2["link"]."\"";
                    if (isset($child_item_2["title"])) {
                      echo " title=\"".$child_item_2["title"]."\"";
                    }
                    echo ">";
                    //echo "<img src=\"".$child_item_2["image"]."\"  width=\"16\" height=\"16\" border=\"0\" alt=\"\" />&nbsp;";
                    echo __($child_item_2["name"]);
                    echo "</a><br />\n";
                }
                echo "</div>\n";
                echo "</td>\n";
            }
            echo "</tr>\n";

        }

        echo "</table>\n";
    }
	
//echo "</div>\n";
//echo "</td></tr>\n";
//echo "</table>\n";


?>
