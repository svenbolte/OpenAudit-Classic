<?php
// OpenAudit: beide Anwendungsdatenbanken ohne Auswahl sichern.
include 'include.php';
?>
<td style="vertical-align:top;width:100%">
<div class="main_each">
<table>
<tr><td class="contenthead"><?php echo htmlspecialchars(__('Backup the Database'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
</table>
<form method="post" action="database_backup_sql.php" name="database_backup">
<table class="content">
<tr><td>Datenbanken:</td><td><strong>openaudit</strong> und <strong>wordpress</strong></td></tr>
<tr><td colspan="2"><label><input type="checkbox" name="add_drop_tables" value="y" checked> DROP TABLE IF EXISTS in die Sicherung aufnehmen</label></td></tr>
<tr><td colspan="2"><input type="submit" value="<?php echo htmlspecialchars(__('Backup'), ENT_QUOTES, 'UTF-8'); ?>" name="submit_button"></td></tr>
<tr><td colspan="2"><?php echo htmlspecialchars(__('This process can take several minutes'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
</table>
</form>
</div>
</td>
