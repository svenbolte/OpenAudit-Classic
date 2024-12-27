<?php
include_once("include.php");

	echo "<td style=\"vertical-align:top;width:100%\">\n";
	echo "<div class=\"main_each\">";
	echo "<table ><tr><td class=\"contenthead\">\n";
	echo 'Batch: HTML Browser Bookmarks nach CSV (Excel) exportieren</td></tr></table>';
	echo "<table ><tr><td style=\"padding:3em\">";

?>

  <head>
	<title>HTML Browser Bookmarks to CSV (Excel)</title>
    <script type="module">
      import { readFile, removeExtension } from './javascript/bkmindex.js';

      document.addEventListener('DOMContentLoaded', (event) => {
        const changeFile = () => {
          const files = input.files;

          for (let i = 0; i < files.length; i++) {
            const file = files.item(i);

            if (file.type === 'text/html') {
              const reader = new FileReader();
              reader.fileName = removeExtension(file.name);
              reader.addEventListener('load', readFile);
              reader.readAsText(file);
            } else {
              alert("You don't enter a html file");
            }
          }
        };

        const input = document.querySelector('input[type=file]');
        input.addEventListener('change', changeFile);
      });
    </script>
  </head>
  <body>
    <p>
      You only need to drag and drop the HTML bookmarks in the input or select it
      and automatically will be download the bookmarks with data-icons in CSV for excel.
    </p>
    <input type="file" accept=".html" multiple />
  </body>
</html>
