<?php
session_start();
include_once("include_functions.php");

/*
 * androapps.php
 * Verwaltung von Android-Software pro Gerät.
 * - Verwendet bestehende MySQLi-Verbindung über GetOpenAuditDbConnection()
 * - Legt Tabelle androidsoftware bei Bedarf automatisch an
 * - Gerät-Auswahl per Textfeld + Dropdown (Distinct-Geräte)
 * - Gerät löschen-Button im Formular
 * - Gesamtübersicht (standard)
 * - Detailansicht pro Gerät (?mode=device&geraet=...)
 * - Produktauswertung (?mode=products) sortiert nach produkt ASC, version DESC
 */

//
// 1. DB-Verbindung
//
$GLOBALS["db"] = GetOpenAuditDbConnection() or die('Could not connect: ' . mysqli_error($GLOBALS["db"]));
$db = $GLOBALS["db"];

$mysqli_database = "openaudit";  // ggf. anpassen
mysqli_select_db($db, $mysqli_database) or die(mysqli_error($db));

//
// 2. Tabelle androidsoftware prüfen & ggf. automatisch anlegen
//
$check = mysqli_query($db, "SHOW TABLES LIKE 'androidsoftware'");
if (mysqli_num_rows($check) === 0) {
    $create = "
        CREATE TABLE androidsoftware (
            id INT AUTO_INCREMENT PRIMARY KEY,
            geraet VARCHAR(255),
            hardwareid INT,
            hlink TEXT,
            version VARCHAR(50),
            produkt VARCHAR(255),
            loguser VARCHAR(255),
            datum DATETIME,
            jahr INT
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ";

    if (!mysqli_query($db, $create)) {
        die('Fehler beim Erstellen der Tabelle androidsoftware: ' . mysqli_error($db));
    }
}

//
// 3. Distinct-Geräteliste für Dropdown
//
$geraeteListe = [];
$resG = mysqli_query($db, "SELECT DISTINCT geraet FROM androidsoftware WHERE geraet <> '' ORDER BY geraet ASC");
if ($resG) {
    while ($row = mysqli_fetch_assoc($resG)) {
        $geraeteListe[] = $row['geraet'];
    }
}

//
// 4. App-Anzahl pro Gerät für Gesamtübersicht
//
$appCounts = [];
$resCnt = mysqli_query($db, "SELECT geraet, COUNT(*) AS cnt FROM androidsoftware WHERE geraet <> '' GROUP BY geraet");
if ($resCnt) {
    while ($row = mysqli_fetch_assoc($resCnt)) {
        $appCounts[$row['geraet']] = (int)$row['cnt'];
    }
}

//
// Hilfsfunktion für Eingaben
//
function req($arr, $key, $default = "")
{
    return isset($arr[$key]) ? trim($arr[$key]) : $default;
}

//
// Variablen aus GET/POST
//
$mode   = req($_GET, "mode");
$id     = intval($_GET["id"] ?? 0);
$gParam = req($_GET, "geraet");

//
// 5. MODE=add — neue Software per Textarea erfassen
//
if ($mode === "add" && $_SERVER["REQUEST_METHOD"] === "POST") {

    $geraet     = req($_POST, "geraet");
    $hardwareId = 0; // Hardware wird nicht mehr in der UI erfasst
    $datum      = str_replace("T", " ", req($_POST, "datum", date("Y-m-d H:i:s")));
    $jahr       = substr($datum, 0, 4);
    $produktRaw = req($_POST, "produkt");
    $loguser    = $_SESSION["username"] ?? "system";

    if ($geraet !== "" && $produktRaw !== "") {

        // Alte Einträge für dieses Gerät löschen
        mysqli_query(
            $db,
            "DELETE FROM androidsoftware WHERE geraet='" . mysqli_real_escape_string($db, $geraet) . "'"
        );

        // Textarea in Zeilen aufsplitten
        $lines = preg_split('/\r\n|\r|\n/', $produktRaw);

        for ($i = 0; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === "") {
                continue;
            }

            // Format: Produktname (Version)    [nächste Zeile: Link]
            if (preg_match('/^(.*)\((.*)\)\s*$/u', $line, $m)) {
                $produkt = mysqli_real_escape_string($db, trim($m[1]));
                $version = mysqli_real_escape_string($db, substr(trim($m[2]), 0, 30));
                $hlink   = mysqli_real_escape_string($db, trim($lines[$i + 1] ?? ""));
                $i++;

                $sql = "
                    INSERT INTO androidsoftware
                    (geraet, hardwareid, hlink, version, produkt, loguser, datum, jahr)
                    VALUES
                    (
                        '" . mysqli_real_escape_string($db, $geraet) . "',
                        $hardwareId,
                        '$hlink',
                        '$version',
                        '$produkt',
                        '" . mysqli_real_escape_string($db, $loguser) . "',
                        '$datum',
                        $jahr
                    )
                ";
                mysqli_query($db, $sql);
            }
        }
    }

    header("Location: androapps.php");
    exit;
}

//
// 6. MODE=update — einzelnen Datensatz speichern
//
if ($mode === "update" && $_SERVER["REQUEST_METHOD"] === "POST" && $id > 0) {

    $geraet  = mysqli_real_escape_string($db, req($_POST, "geraet"));
    $hlink   = mysqli_real_escape_string($db, req($_POST, "hlink"));
    $produkt = mysqli_real_escape_string($db, req($_POST, "produkt"));
    $version = mysqli_real_escape_string($db, substr(req($_POST, "version"), 0, 30));
    $datum   = str_replace("T", " ", req($_POST, "datum", date("Y-m-d H:i:s")));
    $jahr    = substr($datum, 0, 4);
    $loguser = mysqli_real_escape_string($db, $_SESSION["username"] ?? "system");

    // hardwareid wird nicht verändert
    $sql = "
        UPDATE androidsoftware SET
            jahr=$jahr,
            geraet='$geraet',
            hlink='$hlink',
            produkt='$produkt',
            version='$version',
            loguser='$loguser',
            datum='$datum'
        WHERE id=$id
    ";
    mysqli_query($db, $sql);

    header("Location: androapps.php");
    exit;
}

//
// 7. MODE=del — Gerät komplett löschen
//
if ($mode === "del") {

    $geraet = $gParam;
    if ($geraet !== "") {
        mysqli_query(
            $db,
            "DELETE FROM androidsoftware WHERE geraet='" . mysqli_real_escape_string($db, $geraet) . "'"
        );
        header("Location: androapps.php");
        exit;
    }
}

//
// 8. MODE=edit — Datensatz zum Bearbeiten laden
//
$editRow = null;
if ($mode === "edit" && $id > 0) {
    $resEdit = mysqli_query($db, "SELECT * FROM androidsoftware WHERE id=$id");
    if ($resEdit) {
        $editRow = mysqli_fetch_assoc($resEdit);
    }
}

//
// 9. MODE=device — Detailansicht je Gerät (Apps gruppiert nach Produkt)
//
$deviceRows = [];
if ($mode === "device" && $gParam !== "") {
    $gEsc = mysqli_real_escape_string($db, $gParam);
    $resDev = mysqli_query(
        $db,
        "SELECT id, produkt, version, datum, hlink, loguser
         FROM androidsoftware
         WHERE geraet='$gEsc'
         ORDER BY produkt ASC, version DESC"
    );
    if ($resDev) {
        while ($row = mysqli_fetch_assoc($resDev)) {
            $deviceRows[] = $row;
        }
    }
}

//
// 10. MODE=products — Auswertung nach Produkt (produkt ASC, version DESC, geraet ASC)
//
$productRows = [];
if ($mode === "products") {
    $resProd = mysqli_query(
        $db,
        "SELECT id, geraet, produkt, version, datum, hlink, loguser
         FROM androidsoftware
         WHERE produkt <> ''
         ORDER BY produkt ASC, version DESC, geraet ASC"
    );
    if ($resProd) {
        while ($row = mysqli_fetch_assoc($resProd)) {
            $productRows[] = $row;
        }
    }
}

//
// 11. Standardliste aller Einträge für Gesamtübersicht
//
$sqlList = "
    SELECT id, geraet, produkt, version, datum, hlink, loguser
    FROM androidsoftware
    ORDER BY geraet ASC, produkt ASC, version DESC
";
$resultList = mysqli_query($db, $sqlList);


include_once("include.php");
	echo "<td style=\"vertical-align:top;width:100%\">\n";
	echo "<div class=\"main_each\">";
	echo "<table><tr><td class=\"contenthead\">\n";
	echo 'Android-Software-Inventar</td></tr></table>';
	echo "<table ><tr><td style=\"padding:0 1em\">";
?>

    <script>
        function setGeraetFromSelect(selectEl, inputId) {
            var input = document.getElementById(inputId);
            if (input && selectEl.value !== '') {
                input.value = selectEl.value;
            }
        }
        function delGeraet(inputId) {
            var el = document.getElementById(inputId);
            if (!el) return;
            var val = el.value.trim();
            if (val === '') {
                alert('Kein Gerät eingetragen.');
                return;
            }
            if (confirm('Alle Einträge für das Gerät „' + val + '“ löschen?')) {
                window.location = 'androapps.php?mode=del&geraet=' + encodeURIComponent(val);
            }
        }
    </script>
</head>
<body>

<p>
    <a href="androapps.php">Gesamtübersicht</a> |
    <a href="androapps.php?mode=products">Auswertung nach Produkt</a> | 
    <a href="list.php?view=androapps"><i class="fa fa-lg fa-file-excel-o"></i> Openaudit exportierbare Liste</a>
</p>

<?php if ($editRow): ?>
    <!-- Bearbeiten-Formular -->
    <form method="post" action="androapps.php?mode=update&id=<?= (int)$editRow['id'] ?>">

        <label><b>Eintrag bearbeiten:</b></label>
        <div class="geraet-row">
            <input type="text" name="geraet" id="geraet_edit"
                   value="<?= htmlspecialchars($editRow['geraet']) ?>" required>

            <select onchange="setGeraetFromSelect(this, 'geraet_edit')">
                <option value="">– vorhandenes Gerät wählen –</option>
                <?php foreach ($geraeteListe as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>"
                        <?= ($g === $editRow['geraet']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="button" 
                    onclick="delGeraet('geraet_edit')"
                    style="margin-left:8px;width:200px">
                Gerät löschen
            </button>
        </div>

        <label>Datum:
            <input type="datetime-local" name="datum"
                   value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($editRow['datum']))) ?>"
                   required>
        </label>

        <label>Produkt:
            <input type="text" name="produkt"
                   value="<?= htmlspecialchars($editRow['produkt']) ?>" required>
        </label>

        <label>Version:
            <input type="text" name="version"
                   value="<?= htmlspecialchars($editRow['version']) ?>" required>
        </label>

        <label>Play-Store-Link:
            <input type="url" name="hlink"
                   value="<?= htmlspecialchars($editRow['hlink']) ?>">
        </label>

        <button type="submit">Änderungen speichern</button>
        <a href="androapps.php">Abbrechen</a>
    </form>

<?php endif; ?>

<?php if ($mode !== "edit") { ?>
	<!-- Formular: Neuer Software-Eintrag -->
	<form method="post" action="androapps.php?mode=add">

		<label><b>Datenimport für Gerät:</b></label>
		<div class="geraet-row">
			<input type="text" name="geraet" id="geraet_add" required>

			<select onchange="setGeraetFromSelect(this, 'geraet_add')">
				<option value="">– vorhandenes Gerät wählen –</option>
				<?php foreach ($geraeteListe as $g): ?>
					<option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
				<?php endforeach; ?>
			</select>

			<button type="button"
					onclick="delGeraet('geraet_add')"
					style="margin-left:8px;width:200px">
				Gerät löschen
			</button>
		</div>
		<label>Datum:     Produkte:</label><br>
			<input type="datetime-local" name="datum"
				   value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>" required>
			<textarea name="produkt" rows="3" cols="80" required
					  placeholder="Signal (6.30.3)
			https://play.google.com/store/apps/details?id=org.thoughtcrime.securesms
			WhatsApp (2.24.3)
			https://play.google.com/store/apps/details?id=com.whatsapp"></textarea>

		<button type="submit">Speichern</button>
	</form>
<?php } ?>

<?php if ($mode === "device" && $gParam !== ""): ?>
    <!-- Detailansicht für ein Gerät, gruppiert nach Produkt -->
    <h2>Detailansicht für Gerät: <?= htmlspecialchars($gParam) ?></h2>
    <p><a href="androapps.php">&laquo; Zur Gesamtübersicht</a></p>

    <table class="tftable">
        <thead>
        <tr>
            <th>Datum</th>
            <th>Produkt</th>
            <th>Version</th>
            <th>Play-Store-Link</th>
            <th>Aktion</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($deviceRows)): ?>
            <?php
            $currentProd = null;
            foreach ($deviceRows as $r):
                if ($currentProd !== $r['produkt']):
                    $currentProd = $r['produkt'];
            ?>
                <!-- Gruppenkopf pro Produkt -->
                <tr class="group-header">
                    <td colspan="5"><?= htmlspecialchars($currentProd) ?></td>
                </tr>
            <?php
                endif;
            ?>
                <tr>
                    <td><?= htmlspecialchars($r['datum']) ?></td>
                    <td><?= htmlspecialchars($r['produkt']) ?></td>
                    <td><?= htmlspecialchars($r['version']) ?></td>
                    <td>
                        <?php if (!empty($r['hlink'])): ?>
                            <a href="<?= htmlspecialchars($r['hlink']) ?>" target="_blank">Store</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="androapps.php?mode=edit&id=<?= (int)$r['id'] ?>">Bearbeiten</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">Keine Einträge für dieses Gerät.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <hr>
<?php endif; ?>

<?php if ($mode === "products"): ?>
    <!-- Produktauswertung -->
    <h2>Auswertung nach Produkt</h2>
    <p><a href="androapps.php">&laquo; Zur Gesamtübersicht</a></p>

    <table class="tftable">
        <thead>
        <tr>
            <th>Datum</th>
            <th>Produkt</th>
            <th>Version</th>
            <th>Gerät</th>
            <th>Play-Store-Link</th>
            <th>Aktion</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($productRows)): ?>
            <?php
            $currentProd = null;
            foreach ($productRows as $r):
                if ($currentProd !== $r['produkt']):
                    $currentProd = $r['produkt'];
            ?>
                <tr class="group-header">
                    <td colspan="6"><?= htmlspecialchars($currentProd) ?></td>
                </tr>
            <?php
                endif;
            ?>
                <tr>
                    <td><?= htmlspecialchars($r['datum']) ?></td>
                    <td><?= htmlspecialchars($r['produkt']) ?></td>
                    <td><?= htmlspecialchars($r['version']) ?></td>
                    <td>
                        <?php if ($r['geraet'] !== ''): ?>
                            <a href="androapps.php?mode=device&amp;geraet=<?= urlencode($r['geraet']) ?>">
                                <?= htmlspecialchars($r['geraet']) ?>
                            </a>
                        <?php else: ?>
                            (kein Gerät)
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($r['hlink'])): ?>
                            <a href="<?= htmlspecialchars($r['hlink']) ?>" target="_blank">Store</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="androapps.php?mode=edit&id=<?= (int)$r['id'] ?>">Bearbeiten</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">Keine Einträge vorhanden.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <hr>
<?php endif; ?>

<!-- Liste aller Einträge (Gesamtübersicht) -->
<h2>Erfasste Einträge (gesamt)</h2>

<table class="tftable">
    <thead>
    <tr>
        <th>Datum</th>
        <th>Gerät</th>
        <th>Produkt</th>
        <th>Version</th>
        <th>Play-Store-Link</th>
        <th>Aktion</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($resultList && mysqli_num_rows($resultList) > 0): ?>
        <?php while ($r = mysqli_fetch_assoc($resultList)): ?>
            <?php
            $geraet = $r['geraet'];
            $cnt    = $geraet !== '' && isset($appCounts[$geraet]) ? $appCounts[$geraet] : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($r['datum']) ?></td>
                <td>
                    <?php if ($geraet !== ''): ?>
                        <a href="androapps.php?mode=device&amp;geraet=<?= urlencode($geraet) ?>">
                            <?= htmlspecialchars($geraet) ?>
                            <?php if ($cnt > 0): ?>
                                (<?= $cnt ?> App<?= $cnt === 1 ? '' : 's' ?>)
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        (kein Gerät)
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['produkt']) ?></td>
                <td><?= htmlspecialchars($r['version']) ?></td>
                <td>
                    <?php if (!empty($r['hlink'])): ?>
                        <a href="<?= htmlspecialchars($r['hlink']) ?>" target="_blank">Store</a>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="androapps.php?mode=edit&id=<?= (int)$r['id'] ?>">Bearbeiten</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">Keine Einträge vorhanden.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
