<?php
if ($count_system <> "10000"){
  echo "  <td align=\"right\">\n";
  echo "    <a href=\"" . $_SERVER["PHP_SELF"] . "?page_count=" . $page_prev . "&amp;sort=" . $sort . "\"><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></a>&nbsp;&nbsp;&nbsp;\n"; 
  echo "    <a href=\"" . $_SERVER["PHP_SELF"] . "?page_count=0&amp;show_all=1&amp;sort=" . $sort . "\"><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></a>&nbsp;&nbsp;&nbsp;\n"; 
  echo "    <a href=\"" . $_SERVER["PHP_SELF"] . "?page_count=" . $page_next . "&amp;sort=" . $sort . "\"><i class=\"fa fa-circle-o\" aria-hidden=\"true\"></i></a>&nbsp;<br />&nbsp;\n  </td>\n"; 
} else {}
?>
