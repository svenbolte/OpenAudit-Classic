<?php
  echo "  <div class=\"navarrows\">\n";
  if ($_GET['show_all'] <> 1) { // AND (($page_next * $count_system) < $total_software)) { << need count on each page for this
    echo "    <a href=\"" . $_SERVER["PHP_SELF"] . "?page_count=" . $page_next . "&amp;sort=" . $sort . "\"><i class=\"fa fa-chevron-right\" aria-hidden=\"true\"></i></a>\n";
  } else {
    echo "    <i class=\"fa fa-chevron-right\" aria-hidden=\"true\"></i>\n";
  }
  echo "  </div>\n<div class=\"navarrows\">\n";
  if ($_GET['show_all'] <> 1) {
    echo "    <a href=\"" . $_SERVER["PHP_SELF"] . "?page_count=0&amp;show_all=1&amp;sort=" . $sort . "\"><i class=\"fa fa-angle-double-right\" aria-hidden=\"true\"></i></a>\n";
  } else {
    echo "    <a href=\"" . $_SERVER["PHP_SELF"] . "?page_count=0&amp;sort=" . $sort . "\"><i class=\"fa fa-angle-double-right\" aria-hidden=\"true\"></i></a>\n";
  }
  echo "  </div>\n<div class=\"navarrows\">\n";
  if (($page_current <> "0") AND ($_GET['show_all'] <> 1)){
    echo "    <a href=\"" . $_SERVER["PHP_SELF"] . "?page_count=" . $page_prev . "&amp;sort=" . $sort . "\"><i class=\"fa fa-chevron-left\" aria-hidden=\"true\"></i></a>\n";
  } else {
    echo "    <i class=\"fa fa-chevron-left\" aria-hidden=\"true\"></i>\n";
  }    
  echo "  </div>\n";
?>
