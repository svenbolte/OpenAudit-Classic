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

function safeHttpUrl($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $parts = parse_url($value);
    if ($parts === false || empty($parts['scheme'])) {
        return '';
    }
    return in_array(strtolower($parts['scheme']), array('http', 'https'), true) ? $value : '';
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
            $header = fgetcsv($handle, null, ',', '"', '');
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

                    while ($ok && ($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
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
<style>
.andro-shell{max-width:100%;color:#20262d}.andro-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:10px 0 16px}.andro-toolbar a,.andro-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border:1px solid #cfd7df;border-radius:7px;background:#fff;color:#263746;text-decoration:none;cursor:pointer;line-height:1.2}.andro-toolbar a:hover,.andro-btn:hover{background:#f4f7f9;border-color:#aebac5}.andro-btn-primary{background:#2f6f9f;border-color:#2f6f9f;color:#fff}.andro-btn-danger{color:#a12a2a;border-color:#e1b8b8}.andro-card{background:#fff;border:1px solid #dbe2e8;border-radius:10px;padding:16px;margin:12px 0 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)}.andro-card h3{margin:0 0 14px}.andro-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px 16px}.andro-field label{display:block;font-weight:600;margin-bottom:5px}.andro-field input,.andro-field select,.andro-search{width:100%;box-sizing:border-box;padding:8px 9px;border:1px solid #cbd4dc;border-radius:6px;background:#fff}.andro-field-wide{grid-column:1/-1}.andro-notice{padding:10px 12px;border-radius:7px;margin:10px 0;border:1px solid}.andro-success{background:#f1f8f3;border-color:#bcdac4;color:#245d32}.andro-error{background:#fff2f2;border-color:#e6bcbc;color:#8b2424}.andro-list-tools{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;align-items:center;margin:12px 0 8px}.andro-list-tools .andro-search{max-width:360px}.andro-table-wrap{overflow:auto;border:1px solid #d9e0e6;border-radius:9px;background:#fff;max-height:70vh}.andro-table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;white-space:nowrap}.andro-table th{position:sticky;top:0;z-index:2;background:#eef3f6;color:#273746;text-align:left;font-weight:700;padding:9px 10px;border-bottom:1px solid #ccd5dd}.andro-table td{padding:8px 10px;border-bottom:1px solid #edf0f2;vertical-align:middle}.andro-table tbody tr:nth-child(even){background:#fafbfc}.andro-table tbody tr:hover{background:#f1f6fa}.andro-table a{text-decoration:none}.andro-table .cell-app{font-weight:600;max-width:260px;overflow:hidden;text-overflow:ellipsis}.andro-table .cell-package{max-width:300px;overflow:hidden;text-overflow:ellipsis;color:#53616d}.andro-badge{display:inline-block;min-width:38px;text-align:center;padding:3px 7px;border-radius:999px;font-size:11px;font-weight:700}.badge-yes{background:#e4f4e8;color:#276438}.badge-no{background:#f0f2f4;color:#59636c}.andro-optional{display:none}.andro-show-advanced .andro-optional{display:table-cell}.andro-empty{text-align:center;padding:22px!important;color:#6b7680}@media(max-width:760px){.andro-card{padding:12px}.andro-table{font-size:12px}}
</style>
<div class="andro-shell">
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

function filterApps(value) {
    var query=(value||'').toLowerCase().trim();
    document.querySelectorAll('#androAppTable tbody tr[data-search]').forEach(function(row){
        row.style.display=row.getAttribute('data-search').indexOf(query)!==-1?'':'none';
    });
}
function toggleAdvancedColumns(button) {
    var wrap=document.getElementById('androTableWrap');
    if(!wrap)return;
    var active=wrap.classList.toggle('andro-show-advanced');
    button.textContent=active?'Technische Spalten ausblenden':'Technische Spalten anzeigen';
}
</script>

<div class="andro-toolbar">
    <a href="androapps.php"><i class="fa fa-th-list"></i> Gesamtübersicht</a>
    <a href="androapps.php?mode=products"><i class="fa fa-cubes"></i> Nach Produkt</a>
    <a href="list.php?view=androapps"><i class="fa fa-file-excel-o"></i> Exportierbare Liste</a>
</div>

<?php if ($importMessage !== ''): ?>
    <div class="andro-notice andro-success"><b><?= htmlspecialchars($importMessage) ?></b></div>
<?php endif; ?>
<?php if ($importError !== ''): ?>
    <div class="andro-notice andro-error"><b><?= htmlspecialchars($importError) ?></b></div>
<?php endif; ?>

<?php if ($editRow): ?>
<form class="andro-card" method="post" action="androapps.php?mode=update&id=<?= (int)$editRow['id'] ?>">
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
<form class="andro-card" method="post" action="androapps.php?mode=add" enctype="multipart/form-data">
    <h3>CSV-Datenimport für ein Gerät</h3>
    <div class="andro-grid">
        <div class="andro-field"><label for="geraet_add">Gerätename</label><input type="text" name="geraet" id="geraet_add" placeholder="Gerätename" required></div>
        <div class="andro-field"><label for="geraet_existing">Vorhandenes Gerät</label><select id="geraet_existing" onchange="setGeraetFromSelect(this, 'geraet_add')"><option value="">– auswählen –</option><?php foreach ($geraeteListe as $g): ?><option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option><?php endforeach; ?></select></div>
        <div class="andro-field"><label for="import_datum">Importdatum</label><input id="import_datum" type="datetime-local" name="datum" value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>" required></div>
        <div class="andro-field"><label for="app_csv">App-Liste (CSV)</label><input id="app_csv" type="file" name="app_csv" accept=".csv,text/csv" required></div>
        <div class="andro-field-wide"><small>Beim Import werden alle bisherigen App-Einträge des gewählten Geräts ersetzt.</small></div>
    </div>
    <div class="andro-toolbar"><button class="andro-btn andro-btn-primary" type="submit"><i class="fa fa-upload"></i> CSV importieren</button><button class="andro-btn andro-btn-danger" type="button" onclick="delGeraet('geraet_add')"><i class="fa fa-trash"></i> Gerät löschen</button></div>
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

<div class="andro-list-tools">
    <input class="andro-search" type="search" placeholder="Apps, Gerät, Paket oder Version filtern …" oninput="filterApps(this.value)">
    <button type="button" class="andro-btn" onclick="toggleAdvancedColumns(this)">Technische Spalten anzeigen</button>
</div>
<div class="andro-table-wrap" id="androTableWrap">
<table class="andro-table" id="androAppTable">
<thead><tr>
    <th>Importdatum</th>
    <th>Gerätebezeichnung</th>
    <th>App-Name</th>
    <th>Paketname</th>
    <th>Version</th>
    <th>APK-Größe</th>
    <th class="andro-optional">Archiviert</th>
    <th>Aktiviert</th>
    <th>Im Store</th>
    <th class="andro-optional">Erstinstallation</th>
    <th>Letztes Update</th>
    <th class="andro-optional">Berechtigungen erteilt</th>
    <th class="andro-optional">Berechtigungen angefordert</th>
    <th class="andro-optional">Min SDK</th>
    <th class="andro-optional">Target SDK</th>
    <th class="andro-optional">Paketmanager</th>
    <th>Store-Link</th>
    <th>Aktion</th>
</tr></thead>
<tbody>
<?php if ($resultList && mysqli_num_rows($resultList) > 0): ?>
    <?php while ($r = mysqli_fetch_assoc($resultList)): ?>
    <?php $searchText = strtolower(implode(' ', array($r['geraet'], $r['produkt'], $r['package_name'], $r['version'], $r['package_manager']))); ?>
    <tr data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
        <td><?= htmlspecialchars($r['datum']) ?></td>
        <td>
            <?php if ($r['geraet'] !== ''): ?>
                <a href="androapps.php?mode=device&amp;geraet=<?= urlencode($r['geraet']) ?>">
                    <?= htmlspecialchars($r['geraet']) ?>
                    <?php if ($mode !== 'device' && isset($appCounts[$r['geraet']])): ?>(<?= $appCounts[$r['geraet']] ?> Apps)<?php endif; ?>
                </a>
            <?php endif; ?>
        </td>
        <td class="cell-app" title="<?= htmlspecialchars($r['produkt']) ?>"><?= htmlspecialchars($r['produkt']) ?></td>
        <td class="cell-package" title="<?= htmlspecialchars($r['package_name']) ?>"><?= htmlspecialchars($r['package_name']) ?></td>
        <td><?= htmlspecialchars($r['version']) ?></td>
        <td title="<?= (int)$r['apk_size'] ?> Bytes"><?= htmlspecialchars(displayBytes($r['apk_size'])) ?></td>
        <td class="andro-optional"><span class="andro-badge <?= $r['archived'] ? 'badge-yes' : 'badge-no' ?>"><?= displayBool($r['archived']) ?></span></td>
        <td><span class="andro-badge <?= $r['enabled'] ? 'badge-yes' : 'badge-no' ?>"><?= displayBool($r['enabled']) ?></span></td>
        <td><span class="andro-badge <?= $r['exists_in_app_store'] ? 'badge-yes' : 'badge-no' ?>"><?= displayBool($r['exists_in_app_store']) ?></span></td>
        <td class="andro-optional"><?= htmlspecialchars($r['first_installed'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['last_updated'] ?? '') ?></td>
        <td class="andro-optional"><?= (int)$r['granted_permissions'] ?></td>
        <td class="andro-optional"><?= (int)$r['requested_permissions'] ?></td>
        <td class="andro-optional"><?= (int)$r['min_sdk'] ?></td>
        <td class="andro-optional"><?= (int)$r['target_sdk'] ?></td>
        <td class="andro-optional"><?= htmlspecialchars($r['package_manager']) ?></td>
        <td><?php $storeUrl = safeHttpUrl($r['hlink']); if ($storeUrl !== ''): ?><a href="<?= htmlspecialchars($storeUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-external-link"></i> Store</a><?php endif; ?></td>
        <td><a class="andro-btn" href="androapps.php?mode=edit&id=<?= (int)$r['id'] ?>"><i class="fa fa-pencil"></i> Bearbeiten</a></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td class="andro-empty" colspan="18">Keine Einträge vorhanden.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</body>
</html>
