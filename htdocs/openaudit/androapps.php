<?php
session_start();
include_once("include_functions.php");

/*
 * androapps.php
 * Verwaltung und CSV-Import von Android-Software pro Gerät über die kostenlose app list app: 
 * https://play.google.com/store/apps/details?id=com.github.keeganwitt.applist&hl=gsw
 * Erwartetes CSV-Format:
 * App Name, Package Name, APK_SIZE, ARCHIVED, ENABLED, EXISTS_IN_APP_STORE,
 * FIRST_INSTALLED, GRANTED_PERMISSIONS, LAST_UPDATED, MIN_SDK, PACKAGE_MANAGER,
 * REQUESTED_PERMISSIONS, STORE_URL, TARGET_SDK, VERSION
 */

$GLOBALS["db"] = GetOpenAuditDbConnection() or die('Could not connect: ' . mysqli_error($GLOBALS["db"]));
$db = $GLOBALS["db"];
$mysqli_database = "openaudit";
mysqli_select_db($db, $mysqli_database) or die(mysqli_error($db));
mysqli_set_charset($db, 'utf8mb4');

function req($arr, $key, $default = "")
{
    return isset($arr[$key]) ? trim((string)$arr[$key]) : $default;
}

function dbEscape($db, $value)
{
    return mysqli_real_escape_string($db, (string)$value);
}

function csvBool($value)
{
    $value = strtolower(trim((string)$value));
    return in_array($value, array('1', 'true', 'yes', 'ja', 'y'), true) ? 1 : 0;
}

function csvInt($value)
{
    $value = trim((string)$value);
    return preg_match('/^-?\d+$/', $value) ? (int)$value : 0;
}

function csvDateTime($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $formats = array('d.m.Y H:i:s', 'd.m.Y H:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i');
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        if ($date instanceof DateTime) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : null;
}

function htmlDateTime($value)
{
    if (empty($value) || strtotime($value) === false) {
        return '';
    }
    return date('Y-m-d\TH:i', strtotime($value));
}

function displayBool($value)
{
    return ((int)$value === 1) ? 'Ja' : 'Nein';
}

function displayBytes($bytes)
{
    $bytes = (int)$bytes;
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = array('B', 'KB', 'MB', 'GB');
    $power = min((int)floor(log($bytes, 1024)), count($units) - 1);
    return number_format($bytes / pow(1024, $power), $power === 0 ? 0 : 2, ',', '.') . ' ' . $units[$power];
}

function importHeaderKey($value)
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value));
    return strtoupper(str_replace(array(' ', '-'), '_', $value));
}

// Tabelle anlegen bzw. bestehende Tabelle automatisch erweitern.
$check = mysqli_query($db, "SHOW TABLES LIKE 'androidsoftware'");
if (!$check || mysqli_num_rows($check) === 0) {
    $create = "
        CREATE TABLE androidsoftware (
            id INT AUTO_INCREMENT PRIMARY KEY,
            geraet VARCHAR(255) NOT NULL DEFAULT '',
            hardwareid INT NOT NULL DEFAULT 0,
            produkt VARCHAR(255) NOT NULL DEFAULT '',
            package_name VARCHAR(255) NOT NULL DEFAULT '',
            version VARCHAR(100) NOT NULL DEFAULT '',
            apk_size BIGINT NOT NULL DEFAULT 0,
            archived TINYINT(1) NOT NULL DEFAULT 0,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            exists_in_app_store TINYINT(1) NOT NULL DEFAULT 0,
            first_installed DATETIME NULL,
            granted_permissions INT NOT NULL DEFAULT 0,
            last_updated DATETIME NULL,
            min_sdk INT NOT NULL DEFAULT 0,
            package_manager VARCHAR(255) NOT NULL DEFAULT '',
            requested_permissions INT NOT NULL DEFAULT 0,
            hlink TEXT,
            target_sdk INT NOT NULL DEFAULT 0,
            loguser VARCHAR(255) NOT NULL DEFAULT '',
            datum DATETIME NULL,
            jahr INT NOT NULL DEFAULT 0,
            INDEX idx_androidsoftware_geraet (geraet),
            INDEX idx_androidsoftware_package (package_name)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ";
    if (!mysqli_query($db, $create)) {
        die('Fehler beim Erstellen der Tabelle androidsoftware: ' . mysqli_error($db));
    }
}

$requiredColumns = array(
    'package_name' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER produkt",
    'apk_size' => "BIGINT NOT NULL DEFAULT 0 AFTER version",
    'archived' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER apk_size",
    'enabled' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER archived",
    'exists_in_app_store' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER enabled",
    'first_installed' => "DATETIME NULL AFTER exists_in_app_store",
    'granted_permissions' => "INT NOT NULL DEFAULT 0 AFTER first_installed",
    'last_updated' => "DATETIME NULL AFTER granted_permissions",
    'min_sdk' => "INT NOT NULL DEFAULT 0 AFTER last_updated",
    'package_manager' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER min_sdk",
    'requested_permissions' => "INT NOT NULL DEFAULT 0 AFTER package_manager",
    'target_sdk' => "INT NOT NULL DEFAULT 0 AFTER hlink"
);

$existingColumns = array();
$columnResult = mysqli_query($db, "SHOW COLUMNS FROM androidsoftware");
if ($columnResult) {
    while ($column = mysqli_fetch_assoc($columnResult)) {
        $existingColumns[$column['Field']] = true;
    }
}
foreach ($requiredColumns as $columnName => $definition) {
    if (!isset($existingColumns[$columnName])) {
        $alter = "ALTER TABLE androidsoftware ADD COLUMN `$columnName` $definition";
        if (!mysqli_query($db, $alter)) {
            die('Fehler beim Erweitern der Tabelle um ' . htmlspecialchars($columnName) . ': ' . mysqli_error($db));
        }
    }
}

$mode = req($_GET, 'mode');
$id = intval($_GET['id'] ?? 0);
$gParam = req($_GET, 'geraet');
$importMessage = '';
$importError = '';

// CSV-Import. Vorhandene Einträge des Geräts werden vollständig ersetzt.
if ($mode === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $geraet = req($_POST, 'geraet');
    $datum = str_replace('T', ' ', req($_POST, 'datum', date('Y-m-d H:i:s')));
    if (strlen($datum) === 16) {
        $datum .= ':00';
    }
    $jahr = (int)substr($datum, 0, 4);
    $loguser = $_SESSION['username'] ?? 'system';

    if ($geraet === '') {
        $importError = 'Bitte ein Gerät angeben.';
    } elseif (!isset($_FILES['app_csv']) || $_FILES['app_csv']['error'] !== UPLOAD_ERR_OK) {
        $importError = 'Bitte eine gültige CSV-Datei auswählen.';
    } else {
        $handle = fopen($_FILES['app_csv']['tmp_name'], 'rb');
        if ($handle === false) {
            $importError = 'Die CSV-Datei konnte nicht geöffnet werden.';
        } else {
            $header = fgetcsv($handle, 0, ',');
            if ($header === false || count($header) < 2) {
                $importError = 'Die CSV-Datei enthält keine gültige Kopfzeile.';
            } else {
                $headerMap = array();
                foreach ($header as $position => $name) {
                    $headerMap[importHeaderKey($name)] = $position;
                }

                $requiredHeaders = array(
                    'APP_NAME', 'PACKAGE_NAME', 'APK_SIZE', 'ARCHIVED', 'ENABLED',
                    'EXISTS_IN_APP_STORE', 'FIRST_INSTALLED', 'GRANTED_PERMISSIONS',
                    'LAST_UPDATED', 'MIN_SDK', 'PACKAGE_MANAGER', 'REQUESTED_PERMISSIONS',
                    'STORE_URL', 'TARGET_SDK', 'VERSION'
                );
                $missingHeaders = array_diff($requiredHeaders, array_keys($headerMap));

                if (!empty($missingHeaders)) {
                    $importError = 'Fehlende CSV-Spalten: ' . implode(', ', $missingHeaders);
                } else {
                    mysqli_begin_transaction($db);
                    $ok = mysqli_query($db, "DELETE FROM androidsoftware WHERE geraet='" . dbEscape($db, $geraet) . "'");
                    $count = 0;

                    while ($ok && ($row = fgetcsv($handle, 0, ',')) !== false) {
                        if (count($row) === 1 && trim($row[0]) === '') {
                            continue;
                        }

                        $get = function ($name) use ($row, $headerMap) {
                            $index = $headerMap[$name];
                            return isset($row[$index]) ? trim((string)$row[$index]) : '';
                        };

                        $produkt = $get('APP_NAME');
                        $packageName = $get('PACKAGE_NAME');
                        if ($produkt === '' && $packageName === '') {
                            continue;
                        }

                        $firstInstalled = csvDateTime($get('FIRST_INSTALLED'));
                        $lastUpdated = csvDateTime($get('LAST_UPDATED'));
                        $firstInstalledSql = $firstInstalled === null ? 'NULL' : "'" . dbEscape($db, $firstInstalled) . "'";
                        $lastUpdatedSql = $lastUpdated === null ? 'NULL' : "'" . dbEscape($db, $lastUpdated) . "'";

                        $sql = "INSERT INTO androidsoftware
                            (geraet, hardwareid, produkt, package_name, version, apk_size, archived,
                             enabled, exists_in_app_store, first_installed, granted_permissions,
                             last_updated, min_sdk, package_manager, requested_permissions, hlink,
                             target_sdk, loguser, datum, jahr)
                            VALUES (
                             '" . dbEscape($db, $geraet) . "', 0,
                             '" . dbEscape($db, $produkt) . "',
                             '" . dbEscape($db, $packageName) . "',
                             '" . dbEscape($db, $get('VERSION')) . "',
                             " . csvInt($get('APK_SIZE')) . ",
                             " . csvBool($get('ARCHIVED')) . ",
                             " . csvBool($get('ENABLED')) . ",
                             " . csvBool($get('EXISTS_IN_APP_STORE')) . ",
                             $firstInstalledSql,
                             " . csvInt($get('GRANTED_PERMISSIONS')) . ",
                             $lastUpdatedSql,
                             " . csvInt($get('MIN_SDK')) . ",
                             '" . dbEscape($db, $get('PACKAGE_MANAGER')) . "',
                             " . csvInt($get('REQUESTED_PERMISSIONS')) . ",
                             '" . dbEscape($db, $get('STORE_URL')) . "',
                             " . csvInt($get('TARGET_SDK')) . ",
                             '" . dbEscape($db, $loguser) . "',
                             '" . dbEscape($db, $datum) . "',
                             $jahr)";

                        $ok = mysqli_query($db, $sql);
                        if ($ok) {
                            $count++;
                        }
                    }

                    if ($ok) {
                        mysqli_commit($db);
                        fclose($handle);
                        header('Location: androapps.php?imported=' . $count);
                        exit;
                    }

                    mysqli_rollback($db);
                    $importError = 'Import abgebrochen: ' . mysqli_error($db);
                }
            }
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }
}

if (isset($_GET['imported'])) {
    $importMessage = (int)$_GET['imported'] . ' Apps wurden importiert.';
}

// Einzelnen Datensatz aktualisieren.
if ($mode === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $geraet = dbEscape($db, req($_POST, 'geraet'));
    $produkt = dbEscape($db, req($_POST, 'produkt'));
    $packageName = dbEscape($db, req($_POST, 'package_name'));
    $version = dbEscape($db, req($_POST, 'version'));
    $hlink = dbEscape($db, req($_POST, 'hlink'));
    $packageManager = dbEscape($db, req($_POST, 'package_manager'));
    $datum = str_replace('T', ' ', req($_POST, 'datum', date('Y-m-d H:i:s')));
    if (strlen($datum) === 16) {
        $datum .= ':00';
    }
    $firstInstalled = csvDateTime(req($_POST, 'first_installed'));
    $lastUpdated = csvDateTime(req($_POST, 'last_updated'));
    $firstInstalledSql = $firstInstalled === null ? 'NULL' : "'" . dbEscape($db, $firstInstalled) . "'";
    $lastUpdatedSql = $lastUpdated === null ? 'NULL' : "'" . dbEscape($db, $lastUpdated) . "'";
    $loguser = dbEscape($db, $_SESSION['username'] ?? 'system');

    $sql = "UPDATE androidsoftware SET
        geraet='$geraet', produkt='$produkt', package_name='$packageName', version='$version',
        apk_size=" . csvInt(req($_POST, 'apk_size')) . ",
        archived=" . (isset($_POST['archived']) ? 1 : 0) . ",
        enabled=" . (isset($_POST['enabled']) ? 1 : 0) . ",
        exists_in_app_store=" . (isset($_POST['exists_in_app_store']) ? 1 : 0) . ",
        first_installed=$firstInstalledSql,
        granted_permissions=" . csvInt(req($_POST, 'granted_permissions')) . ",
        last_updated=$lastUpdatedSql,
        min_sdk=" . csvInt(req($_POST, 'min_sdk')) . ",
        package_manager='$packageManager',
        requested_permissions=" . csvInt(req($_POST, 'requested_permissions')) . ",
        hlink='$hlink', target_sdk=" . csvInt(req($_POST, 'target_sdk')) . ",
        loguser='$loguser', datum='" . dbEscape($db, $datum) . "', jahr=" . (int)substr($datum, 0, 4) . "
        WHERE id=$id";
    mysqli_query($db, $sql);
    header('Location: androapps.php');
    exit;
}

if ($mode === 'del' && $gParam !== '') {
    mysqli_query($db, "DELETE FROM androidsoftware WHERE geraet='" . dbEscape($db, $gParam) . "'");
    header('Location: androapps.php');
    exit;
}

$editRow = null;
if ($mode === 'edit' && $id > 0) {
    $resEdit = mysqli_query($db, "SELECT * FROM androidsoftware WHERE id=$id");
    $editRow = $resEdit ? mysqli_fetch_assoc($resEdit) : null;
}

$geraeteListe = array();
$resG = mysqli_query($db, "SELECT DISTINCT geraet FROM androidsoftware WHERE geraet <> '' ORDER BY geraet ASC");
if ($resG) {
    while ($row = mysqli_fetch_assoc($resG)) {
        $geraeteListe[] = $row['geraet'];
    }
}

$appCounts = array();
$resCnt = mysqli_query($db, "SELECT geraet, COUNT(*) AS cnt FROM androidsoftware WHERE geraet <> '' GROUP BY geraet");
if ($resCnt) {
    while ($row = mysqli_fetch_assoc($resCnt)) {
        $appCounts[$row['geraet']] = (int)$row['cnt'];
    }
}

$selectFields = "id, geraet, produkt, package_name, version, apk_size, archived, enabled,
    exists_in_app_store, first_installed, granted_permissions, last_updated, min_sdk,
    package_manager, requested_permissions, hlink, target_sdk, datum, loguser";

$where = '';
$order = 'geraet ASC, produkt ASC, version DESC';
if ($mode === 'device' && $gParam !== '') {
    $where = "WHERE geraet='" . dbEscape($db, $gParam) . "'";
    $order = 'produkt ASC, version DESC';
} elseif ($mode === 'products') {
    $where = "WHERE produkt <> ''";
    $order = 'produkt ASC, version DESC, geraet ASC';
}
$resultList = mysqli_query($db, "SELECT $selectFields FROM androidsoftware $where ORDER BY $order");

include_once("include.php");
echo "<td style=\"vertical-align:top;width:100%\">\n";
echo "<div class=\"main_each\">";
echo "<table><tr><td class=\"contenthead\">\n";
echo 'Android-Software-Inventar</td></tr></table>';
echo "<table><tr><td style=\"padding:0 1em\">";
?>
<script>
function setGeraetFromSelect(selectEl, inputId) {
    var input = document.getElementById(inputId);
    if (input && selectEl.value !== '') input.value = selectEl.value;
}
function delGeraet(inputId) {
    var el = document.getElementById(inputId);
    if (!el || el.value.trim() === '') {
        alert('Kein Gerät eingetragen.');
        return;
    }
    if (confirm('Alle Einträge für das Gerät „' + el.value.trim() + '“ löschen?')) {
        window.location = 'androapps.php?mode=del&geraet=' + encodeURIComponent(el.value.trim());
    }
}
</script>

<p>
    <a href="androapps.php">Gesamtübersicht</a> |
    <a href="androapps.php?mode=products">Auswertung nach Produkt</a> |
    <a href="list.php?view=androapps"><i class="fa fa-lg fa-file-excel-o"></i> OpenAudit exportierbare Liste</a>
</p>

<?php if ($importMessage !== ''): ?>
    <p style="color:green"><b><?= htmlspecialchars($importMessage) ?></b></p>
<?php endif; ?>
<?php if ($importError !== ''): ?>
    <p style="color:red"><b><?= htmlspecialchars($importError) ?></b></p>
<?php endif; ?>

<?php if ($editRow): ?>
<form method="post" action="androapps.php?mode=update&id=<?= (int)$editRow['id'] ?>">
    <h3>Eintrag bearbeiten</h3>
    <label>Gerät:<br><input type="text" name="geraet" value="<?= htmlspecialchars($editRow['geraet']) ?>" required></label><br>
    <label>Importdatum:<br><input type="datetime-local" name="datum" value="<?= htmlspecialchars(htmlDateTime($editRow['datum'])) ?>" required></label><br>
    <label>App-Name:<br><input type="text" name="produkt" value="<?= htmlspecialchars($editRow['produkt']) ?>" required></label><br>
    <label>Paketname:<br><input type="text" name="package_name" value="<?= htmlspecialchars($editRow['package_name']) ?>"></label><br>
    <label>Version:<br><input type="text" name="version" value="<?= htmlspecialchars($editRow['version']) ?>"></label><br>
    <label>APK-Größe in Bytes:<br><input type="number" min="0" name="apk_size" value="<?= (int)$editRow['apk_size'] ?>"></label><br>
    <label><input type="checkbox" name="archived" value="1" <?= $editRow['archived'] ? 'checked' : '' ?>> Archiviert</label>
    <label><input type="checkbox" name="enabled" value="1" <?= $editRow['enabled'] ? 'checked' : '' ?>> Aktiviert</label>
    <label><input type="checkbox" name="exists_in_app_store" value="1" <?= $editRow['exists_in_app_store'] ? 'checked' : '' ?>> Im App Store vorhanden</label><br>
    <label>Erstinstallation:<br><input type="datetime-local" name="first_installed" value="<?= htmlspecialchars(htmlDateTime($editRow['first_installed'])) ?>"></label><br>
    <label>Letztes Update:<br><input type="datetime-local" name="last_updated" value="<?= htmlspecialchars(htmlDateTime($editRow['last_updated'])) ?>"></label><br>
    <label>Erteilte Berechtigungen:<br><input type="number" min="0" name="granted_permissions" value="<?= (int)$editRow['granted_permissions'] ?>"></label><br>
    <label>Angeforderte Berechtigungen:<br><input type="number" min="0" name="requested_permissions" value="<?= (int)$editRow['requested_permissions'] ?>"></label><br>
    <label>Min SDK:<br><input type="number" min="0" name="min_sdk" value="<?= (int)$editRow['min_sdk'] ?>"></label><br>
    <label>Target SDK:<br><input type="number" min="0" name="target_sdk" value="<?= (int)$editRow['target_sdk'] ?>"></label><br>
    <label>Paketmanager:<br><input type="text" name="package_manager" value="<?= htmlspecialchars($editRow['package_manager']) ?>"></label><br>
    <label>Store-Link:<br><input type="url" name="hlink" value="<?= htmlspecialchars($editRow['hlink']) ?>"></label><br>
    <button type="submit">Änderungen speichern</button> <a href="androapps.php">Abbrechen</a>
</form>
<hr>
<?php else: ?>
<form method="post" action="androapps.php?mode=add" enctype="multipart/form-data">
    <h3>CSV-Datenimport für ein Gerät</h3>
    <div class="geraet-row">
        <input type="text" name="geraet" id="geraet_add" placeholder="Gerätename" required>
        <select onchange="setGeraetFromSelect(this, 'geraet_add')">
            <option value="">– vorhandenes Gerät wählen –</option>
            <?php foreach ($geraeteListe as $g): ?>
                <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" onclick="delGeraet('geraet_add')" style="margin-left:8px;width:200px">Gerät löschen</button>
    </div>
    <label>Importdatum: <input type="datetime-local" name="datum" value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>" required></label><br>
    <label>App-Liste (CSV): <input type="file" name="app_csv" accept=".csv,text/csv" required></label><br>
    <small>Beim Import werden alle bisherigen App-Einträge des gewählten Geräts ersetzt.</small><br>
    <button type="submit">CSV importieren</button>
</form>
<hr>
<?php endif; ?>

<?php if ($mode === 'device' && $gParam !== ''): ?>
    <h2>Detailansicht für Gerät: <?= htmlspecialchars($gParam) ?></h2>
    <p><a href="androapps.php">&laquo; Zur Gesamtübersicht</a></p>
<?php elseif ($mode === 'products'): ?>
    <h2>Auswertung nach Produkt</h2>
    <p><a href="androapps.php">&laquo; Zur Gesamtübersicht</a></p>
<?php else: ?>
    <h2>Erfasste Einträge (gesamt)</h2>
<?php endif; ?>

<div style="overflow-x:auto">
<table class="tftable">
<thead><tr>
    <th>Importdatum</th>
    <th>Gerätebezecihnung</th>
    <th>App-Name</th>
    <th>Paketname</th>
    <th>Version</th>
    <th>APK-Größe</th>
    <th>Archiviert</th>
    <th>Aktiviert</th>
    <th>Im Store</th>
    <th>Erstinstallation</th>
    <th>Letztes Update</th>
    <th>Berechtigungen erteilt</th>
    <th>Berechtigungen angefordert</th>
    <th>Min SDK</th>
    <th>Target SDK</th>
    <th>Paketmanager</th>
    <th>Store-Link</th>
    <th>Aktion</th>
</tr></thead>
<tbody>
<?php if ($resultList && mysqli_num_rows($resultList) > 0): ?>
    <?php while ($r = mysqli_fetch_assoc($resultList)): ?>
    <tr>
        <td><?= htmlspecialchars($r['datum']) ?></td>
        <td>
            <?php if ($r['geraet'] !== ''): ?>
                <a href="androapps.php?mode=device&amp;geraet=<?= urlencode($r['geraet']) ?>">
                    <?= htmlspecialchars($r['geraet']) ?>
                    <?php if ($mode !== 'device' && isset($appCounts[$r['geraet']])): ?>(<?= $appCounts[$r['geraet']] ?> Apps)<?php endif; ?>
                </a>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($r['produkt']) ?></td>
        <td><?= htmlspecialchars($r['package_name']) ?></td>
        <td><?= htmlspecialchars($r['version']) ?></td>
        <td title="<?= (int)$r['apk_size'] ?> Bytes"><?= htmlspecialchars(displayBytes($r['apk_size'])) ?></td>
        <td><?= displayBool($r['archived']) ?></td>
        <td><?= displayBool($r['enabled']) ?></td>
        <td><?= displayBool($r['exists_in_app_store']) ?></td>
        <td><?= htmlspecialchars($r['first_installed'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['last_updated'] ?? '') ?></td>
        <td><?= (int)$r['granted_permissions'] ?></td>
        <td><?= (int)$r['requested_permissions'] ?></td>
        <td><?= (int)$r['min_sdk'] ?></td>
        <td><?= (int)$r['target_sdk'] ?></td>
        <td><?= htmlspecialchars($r['package_manager']) ?></td>
        <td><?php if (!empty($r['hlink'])): ?><a href="<?= htmlspecialchars($r['hlink']) ?>" target="_blank" rel="noopener">Store</a><?php endif; ?></td>
        <td><a href="androapps.php?mode=edit&id=<?= (int)$r['id'] ?>">Bearbeiten</a></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="18">Keine Einträge vorhanden.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</body>
</html>
