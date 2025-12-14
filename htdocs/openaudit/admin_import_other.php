<?php
declare(strict_types=1);

$page = "add_other";
include "include.php";

/**
 * CSV Import für Tabelle `other`
 * - mysqli
 * - DB-Connection via GetOpenAuditDbConnection()
 * - fgetcsv mit delimiter / enclosure / escape
 */

// ------------------------------------------------
// CONFIG
// ------------------------------------------------
$table = 'other';

// CSV-Parameter (explizit!)
$delimiter = ';';
$enclosure = '"';
$escape    = "\\";

// Falls CSV UTF-8 ist, DB aber latin1 → true setzen
$convertUtf8ToLatin1 = false;

// erlaubte Spalten
$allowedColumns = [
  'other_network_name',
  'other_ip_address',
  'other_mac_address',
  'other_description',
  'other_serial',
  'other_model',
  'other_type',
  'other_location',
  'other_value',
  'other_linked_pc',
  'other_manufacturer',
  'other_date_purchased',
  'other_purchase_order_number',
  'other_p_port_name',
  'other_p_shared',
  'other_p_share_name',
  'other_switch_id',
  'other_switch_port'
];

$requiredColumns = $allowedColumns;

// ------------------------------------------------
// HELPERS
// ------------------------------------------------
function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function toDbEncoding(string $s, bool $convert): string {
  $s = trim($s);
  if (!$convert) return $s;
  return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
}

function normalizeDate(string $v): string {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '0000-00-00';
}

function normalizeIp(string $v): string {
  return ($v !== '' && filter_var($v, FILTER_VALIDATE_IP)) ? $v : '';
}

function normalizeMac(string $v): string {
  return strtoupper(trim($v));
}

// ------------------------------------------------
// MAIN
// ------------------------------------------------
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'CSV-Datei fehlt oder Upload-Fehler.';
  } else {

    $fh = fopen($_FILES['csv']['tmp_name'], 'rb');
    if (!$fh) {
      $errors[] = 'CSV konnte nicht geöffnet werden.';
    } else {

      // Header lesen
      $header = fgetcsv($fh, 0, $delimiter, $enclosure, $escape);
      if ($header === false) {
        $errors[] = 'Keine Header-Zeile gefunden.';
      } else {

        $header = array_map('trim', $header);

        // Pflichtspalten prüfen
        $missing = array_diff($requiredColumns, $header);
        if ($missing) {
          $errors[] = 'Fehlende Spalten: ' . implode(', ', $missing);
        } else {

          $insertColumns = array_values(array_intersect($allowedColumns, $header));

          // DB-Verbindung
          $db = GetOpenAuditDbConnection();
          if (!$db instanceof mysqli) {
            $errors[] = 'Ungültige DB-Verbindung.';
          } else {

            // SQL vorbereiten
            $cols = array_merge($insertColumns, ['other_timestamp', 'other_first_timestamp']);
            $placeholders = implode(',', array_fill(0, count($cols), '?'));

            $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`)
                    VALUES ($placeholders)";

            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
              $errors[] = 'Prepare fehlgeschlagen: ' . mysqli_error($db);
            } else {

              // Typen: alles string, timestamps int
              $types = str_repeat('s', count($insertColumns)) . 'ii';

              $rowNum = 0;
              $ok = 0;
              $skip = 0;
              $now = time();

              while (($row = fgetcsv($fh, 0, $delimiter, $enclosure, $escape)) !== false) {
                $rowNum++;

                if (count($row) < count($header)) {
                  $skip++;
                  $errors[] = "Zeile $rowNum: Spaltenanzahl zu klein (CSV defekt?)";
                  continue;
                }

                $values = [];
                foreach ($insertColumns as $i => $col) {
                  $v = $row[array_search($col, $header)];
                  $v = toDbEncoding($v, $convertUtf8ToLatin1);

                  if ($col === 'other_date_purchased') $v = normalizeDate($v);
                  if ($col === 'other_ip_address')     $v = normalizeIp($v);
                  if ($col === 'other_mac_address')    $v = normalizeMac($v);

                  $values[] = $v;
                }

                $values[] = $now;
                $values[] = $now;

                mysqli_stmt_bind_param($stmt, $types, ...$values);

                if (mysqli_stmt_execute($stmt)) {
                  $ok++;
                } else {
                  $skip++;
                  $errors[] = "Zeile $rowNum: Insert fehlgeschlagen – " . mysqli_stmt_error($stmt);
                }
              }

              mysqli_stmt_close($stmt);
              $messages[] = "Import abgeschlossen: OK=$ok, Übersprungen=$skip";
            }
          }
        }
      }

      fclose($fh);
    }
  }
}

// Seite darstellen


echo "<td style=\"vertical-align:top;width:100%\">\n";
echo "<div class=\"main_each\">";
echo "<table ><tr><td class=\"contenthead\">\n";
echo __("add other devices by importing CSV content") . '</td></tr><tr><td>';
echo "</td></tr></table>\n";

?>
  <div style="max-width:80%"> 

    <?php if (!empty($errors)): ?>
      <div class="err">
        <strong>Fehler / Hinweise:</strong>
        <ul>
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($messages)): ?>
      <div class="ok">
        <ul>
          <?php foreach ($messages as $m): ?><li><?= h($m) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <p>
        <label>CSV-Datei (Semikolon-getrennt, mit Header):<br>
          <input type="file" name="csv" accept=".csv,text/csv" required>
        </label>
      </p>
      <button type="submit">Import starten</button>
    </form>

    <p><strong>Erwartete Spalten (Header) in der CSV:</strong></p>
    <code style="max-width:75%;word-break:break-all"><?php echo implode(';', $allowedColumns) ?></code>

    <p>
      Hinweis: <code>other_id</code> wird automatisch vergeben. Timestamps werden beim Import auf <code>time()</code> gesetzt.
      CSV-Parsing nutzt explizit <code>delimiter</code>, <code>enclosure</code> und <code>escape</code>.
    </p>
  </div>

<?php
echo "</body>\n";
echo "</html>\n";
?>