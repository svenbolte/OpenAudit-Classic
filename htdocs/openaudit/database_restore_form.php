<?php
// OpenAudit: gezielte Auswahl einer Datenbank und ihrer Sicherung.
include 'include.php';
$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'backup';
if (!is_dir($backupDir)) { @mkdir($backupDir, 0750, true); }
function oa_restore_escape($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$files = array('openaudit' => array(), 'wordpress' => array());
if (is_dir($backupDir)) {
    foreach (new DirectoryIterator($backupDir) as $entry) {
        if (!$entry->isFile() || $entry->isLink()) continue;
        foreach (array_keys($files) as $name) {
            if (preg_match('/^' . $name . '_Backup_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.sql$/', $entry->getFilename())) {
                $files[$name][] = $entry->getFilename();
            }
        }
    }
}
foreach ($files as &$names) { rsort($names, SORT_STRING); } unset($names);
echo '<td style="vertical-align:top;width:100%"><div class="main_each">';
echo '<table class="content"><tr><td class="contenthead">Datenbank wiederherstellen</td></tr></table>';
echo '<p>Wähle die Zieldatenbank und eine dazugehörige Sicherung. Die Wiederherstellung überschreibt vorhandene Daten.</p>';
echo '<form method="get" action="database_restore_sql.php"><table class="tftable">';
echo '<tr><td>Zieldatenbank</td><td><select name="database" id="restore_database" class="for_forms">';
foreach (array('openaudit', 'wordpress') as $name) echo '<option value="' . $name . '">' . $name . '</option>';
echo '</select></td></tr>';
echo '<tr><td>SQL-Sicherung</td><td><select name="backup_file" id="restore_file" class="for_forms" required></select></td></tr>';
echo '<tr><td colspan="2"><input type="submit" value="Wiederherstellung vorbereiten" /></td></tr></table></form>';
echo '<script>const restoreFiles=' . json_encode($files, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';';
echo 'const dbSel=document.getElementById("restore_database"), fileSel=document.getElementById("restore_file");';
echo 'function updateRestoreFiles(){fileSel.replaceChildren();(restoreFiles[dbSel.value]||[]).forEach(function(f){const o=document.createElement("option");o.value=f;o.textContent=f;fileSel.appendChild(o);});if(!fileSel.options.length){const o=document.createElement("option");o.textContent="Keine Sicherung vorhanden";o.value="";fileSel.appendChild(o);}}';
echo 'dbSel.addEventListener("change",updateRestoreFiles);updateRestoreFiles();</script>';
echo '</div></td>';
