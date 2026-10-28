<?php
// OpenAudit: Wiederherstellung ausschließlich in die ausdrücklich gewählte Datenbank.
set_time_limit(600);
include 'include.php';
function oa_restore_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function oa_restore_fail($message) { throw new RuntimeException($message); }
function oa_restore_validate($database, $file) {
    if (!in_array($database, array('openaudit', 'wordpress'), true)) oa_restore_fail('Ungültige Zieldatenbank.');
    if (!preg_match('/^' . preg_quote($database, '/') . '_Backup_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.sql$/D', $file)) oa_restore_fail('Sicherung passt nicht zur Zieldatenbank.');
    $directory = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'backup');
    if (!$directory) oa_restore_fail('Backup-Ordner fehlt.');
    $path = $directory . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path) || is_link($path) || !is_readable($path)) oa_restore_fail('Sicherungsdatei nicht gefunden.');
    return $path;
}
echo '<td style="vertical-align:top;width:100%"><div class="main_each"><table class="tftable">';
echo '<tr><td class="contenthead">Datenbank wiederherstellen</td></tr>';
try {
    $isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    $input = $isPost ? $_POST : $_GET;
    $database = isset($input['database']) && is_string($input['database']) ? $input['database'] : '';
    $file = isset($input['backup_file']) && is_string($input['backup_file']) ? $input['backup_file'] : '';
    $path = oa_restore_validate($database, $file);
    $fp = fopen($path, 'rb');
    if (!$fp) oa_restore_fail('Sicherungsdatei konnte nicht geöffnet werden.');
    $header = fread($fp, 4096);
    fclose($fp);
    if ($header === false || !preg_match('/^-- OpenAudit SQL Backup\R-- Database: ' . preg_quote($database, '/') . '\R/', $header)) {
        oa_restore_fail('Der Datei-Header bestätigt die gewählte Datenbank nicht.');
    }
    if (!$isPost) {
        $token = bin2hex(random_bytes(16));
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['oa_restore_token'] = $token;
        echo '<tr><td><strong>Achtung:</strong> Die Datenbank <strong>' . oa_restore_h($database) . '</strong> wird aus <strong>' . oa_restore_h($file) . '</strong> wiederhergestellt. Vorhandene Tabellen und Daten können überschrieben werden.</td></tr>';
        echo '<tr><td><form method="post" action="database_restore_sql.php">';
        echo '<input type="hidden" name="database" value="' . oa_restore_h($database) . '">';
        echo '<input type="hidden" name="backup_file" value="' . oa_restore_h($file) . '">';
        echo '<input type="hidden" name="restore_token" value="' . oa_restore_h($token) . '">';
        echo '<button type="submit" name="submit" value="1">Ja, Datenbank wiederherstellen</button></form></td></tr>';
    } else {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $provided = isset($_POST['restore_token']) && is_string($_POST['restore_token']) ? $_POST['restore_token'] : '';
        if (!isset($_SESSION['oa_restore_token']) || !hash_equals($_SESSION['oa_restore_token'], $provided)) oa_restore_fail('Bestätigung ungültig oder abgelaufen.');
        unset($_SESSION['oa_restore_token']);
        $connection = mysqli_init();
        $user = $database === 'wordpress' ? 'root' : $mysqli_user;
        if (!$connection || !@mysqli_real_connect($connection, $mysqli_server, $user, $mysqli_password, $database)) oa_restore_fail('Verbindung zur Zieldatenbank fehlgeschlagen.');
        try {
            if (!mysqli_set_charset($connection, 'utf8mb4')) oa_restore_fail('Zeichensatz konnte nicht gesetzt werden.');
            $sql = file_get_contents($path);
            if ($sql === false || trim($sql) === '') oa_restore_fail('SQL-Datei ist leer oder nicht lesbar.');
            // Backups aus database_backup_sql.php enthalten kein DELIMITER und verwenden
            // normale MySQL-Statements; multi_query berücksichtigt Semikolons in Strings.
            if (!mysqli_multi_query($connection, $sql)) oa_restore_fail('SQL-Fehler: ' . mysqli_error($connection));
            do {
                $result = mysqli_store_result($connection);
                if ($result instanceof mysqli_result) mysqli_free_result($result);
                if (mysqli_errno($connection)) oa_restore_fail('SQL-Fehler: ' . mysqli_error($connection));
                if (!mysqli_more_results($connection)) break;
                if (!mysqli_next_result($connection)) oa_restore_fail('SQL-Fehler: ' . mysqli_error($connection));
            } while (true);
            echo '<tr><td><strong>Wiederherstellung abgeschlossen:</strong> ' . oa_restore_h($database) . ' aus ' . oa_restore_h($file) . '</td></tr>';
        } finally { mysqli_close($connection); }
    }
} catch (Throwable $e) {
    echo '<tr><td style="color:#a00"><strong>Wiederherstellung fehlgeschlagen:</strong> ' . oa_restore_h($e->getMessage()) . '</td></tr>';
}
echo '<tr><td><a href="database_restore_form.php">Zurück zur Auswahl</a></td></tr></table></div></td>';
