<?php
// OpenAudit: feste Sicherung der Datenbanken openaudit und wordpress.
include 'include.php';
set_time_limit(600);

function oa_backup_identifier($identifier) {
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function oa_backup_write($handle, $text) {
    $length = strlen($text);
    $written = 0;
    while ($written < $length) {
        $count = fwrite($handle, substr($text, $written));
        if ($count === false || $count === 0) {
            throw new RuntimeException('Schreiben der Sicherungsdatei fehlgeschlagen.');
        }
        $written += $count;
    }
}

function oa_backup_database($db, $database, $directory, $withDrop) {
    $dbSql = oa_backup_identifier($database);
    if (!mysqli_select_db($db, $database)) {
        throw new RuntimeException('Datenbank nicht erreichbar: ' . $database . ' (' . mysqli_error($db) . ')');
    }
    // UTF-8 auch für Umlaute und WordPress-Inhalte.
    if (!mysqli_set_charset($db, 'utf8mb4')) {
        throw new RuntimeException('UTF-8-Zeichensatz konnte nicht gesetzt werden: ' . mysqli_error($db));
    }
    $filename = $directory . DIRECTORY_SEPARATOR . $database . '_Backup_' . date('Y-m-d_H-i-s') . '.sql';
    $handle = fopen($filename, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Sicherungsdatei konnte nicht erstellt werden: ' . $filename);
    }
    $tableCount = 0;
    try {
        oa_backup_write($handle, "-- OpenAudit SQL Backup\n-- Database: " . $database . "\n-- Date: " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nCREATE DATABASE IF NOT EXISTS " . $dbSql . ";\nUSE " . $dbSql . ";\n\n");
        $result = mysqli_query($db, 'SHOW FULL TABLES FROM ' . $dbSql);
        if ($result === false) {
            throw new RuntimeException('Tabellen konnten nicht gelesen werden: ' . mysqli_error($db));
        }
        while ($table = mysqli_fetch_row($result)) {
            $tableName = $table[0];
            $tableSql = oa_backup_identifier($tableName);
            $isView = isset($table[1]) && strtoupper($table[1]) === 'VIEW';
            $createResult = mysqli_query($db, 'SHOW CREATE ' . ($isView ? 'VIEW ' : 'TABLE ') . $tableSql);
            if ($createResult === false) {
                throw new RuntimeException('Tabellenstruktur konnte nicht gelesen werden (' . $tableName . '): ' . mysqli_error($db));
            }
            $create = mysqli_fetch_assoc($createResult);
            mysqli_free_result($createResult);
            $definition = $isView ? ($create['Create View'] ?? '') : ($create['Create Table'] ?? '');
            if ($definition === '') {
                throw new RuntimeException('Leere Definition für ' . $tableName);
            }
            if ($withDrop) {
                oa_backup_write($handle, 'DROP ' . ($isView ? 'VIEW' : 'TABLE') . ' IF EXISTS ' . $tableSql . ";\n");
            }
            oa_backup_write($handle, $definition . ";\n");
            if (!$isView) {
                $data = mysqli_query($db, 'SELECT * FROM ' . $tableSql, MYSQLI_USE_RESULT);
                if ($data === false) {
                    throw new RuntimeException('Daten konnten nicht gelesen werden (' . $tableName . '): ' . mysqli_error($db));
                }
                while ($values = mysqli_fetch_row($data)) {
                    $encoded = array();
                    foreach ($values as $value) {
                        $encoded[] = $value === null ? 'NULL' : "'" . mysqli_real_escape_string($db, (string) $value) . "'";
                    }
                    oa_backup_write($handle, 'INSERT INTO ' . $tableSql . ' VALUES (' . implode(', ', $encoded) . ");\n");
                }
                mysqli_free_result($data);
            }
            oa_backup_write($handle, "\n");
            $tableCount++;
        }
        mysqli_free_result($result);
        oa_backup_write($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    } catch (Throwable $error) {
        fclose($handle);
        @unlink($filename);
        throw $error;
    }
    if (!fclose($handle)) {
        @unlink($filename);
        throw new RuntimeException('Sicherungsdatei konnte nicht abgeschlossen werden: ' . $filename);
    }
    return array('file' => $filename, 'tables' => $tableCount, 'bytes' => filesize($filename));
}

function oa_backup_html($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'backup';
$errors = array();
$success = array();
$withDrop = isset($_POST['add_drop_tables']) && $_POST['add_drop_tables'] === 'y';

echo '<td style="vertical-align:top;width:100%"><div class="main_each"><table class="tftable">';
echo '<tr><td class="contenthead" colspan="3">Sichern der Datenbanken openaudit und wordpress</td></tr>';
echo '<tr><th>Datenbank</th><th>Ergebnis</th><th>Datei</th></tr>';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_button'])) {
    echo '<tr><td colspan="3">Bitte das Backup über die Backup-Seite starten.</td></tr>';
} elseif ((!is_dir($backupDir) && !mkdir($backupDir, 0750, true)) || !is_writable($backupDir)) {
    echo '<tr><td colspan="3">Fehler: Der Backup-Ordner ist nicht beschreibbar.</td></tr>';
} else {
    foreach (array('openaudit', 'wordpress') as $database) {
        $backupConnection = null;
        try {
            if ($database === 'wordpress') {
                // Gleiches Passwort und gleicher Server wie OpenAudit, aber Benutzer root.
                // Zugangsdaten kommen aus include_config.php, nicht aus dieser Datei.
                $backupConnection = mysqli_init();
                if (!$backupConnection || !@mysqli_real_connect(
                    $backupConnection, $mysqli_server, 'root', $mysqli_password, 'wordpress'
                )) {
                    throw new RuntimeException('Verbindung zur Datenbank wordpress mit Benutzer root fehlgeschlagen.');
                }
            } else {
                // Bereits authentifizierte OpenAudit-Verbindung verwenden.
                $backupConnection = $db;
            }
            $result = oa_backup_database($backupConnection, $database, $backupDir, $withDrop);
            $success[] = $database;
            echo '<tr><td>' . oa_backup_html($database) . '</td><td>Erfolgreich: ' . (int) $result['tables'] . ' Tabellen/Views, ' . (int) $result['bytes'] . ' Bytes</td><td>' . oa_backup_html(basename($result['file'])) . '</td></tr>';
        } catch (Throwable $error) {
            $errors[] = $database;
            echo '<tr><td>' . oa_backup_html($database) . '</td><td>FEHLER: ' . oa_backup_html($error->getMessage()) . '</td><td>Keine vollständige Sicherung</td></tr>';
        } finally {
            if ($database === 'wordpress' && $backupConnection instanceof mysqli) {
                mysqli_close($backupConnection);
            }
        }
    }
    echo '<tr><td colspan="3"><strong>' . (count($errors) ? 'Backup nicht vollständig!' : 'Beide Datenbanken gesichert.') . '</strong></td></tr>';
}
echo '</table></div></td>';
?>
