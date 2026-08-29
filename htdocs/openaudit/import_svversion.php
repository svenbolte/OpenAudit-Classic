<?php
// Importiert die Softwareversions-CSV in die MySQL-Tabelle softwareversionen.
include_once("include.php");

$svStatus = svversionenimport(1, true);
$statusClass = !empty($svStatus['ok']) ? 'is-success' : 'is-error';
$downloadText = 'Nicht erforderlich';
if (!empty($svStatus['download_attempted'])) {
    $downloadText = !empty($svStatus['download_updated']) ? 'Neu heruntergeladen' : 'Versucht, lokaler Stand verwendet';
}
$localTime = !empty($svStatus['local_timestamp'])
    ? date('d.m.Y H:i:s', (int)$svStatus['local_timestamp'])
    : 'Nicht vorhanden';
?>

<div class="oa-admin-import">
    <div class="oa-admin-import-head">
        <div>
            <span class="oa-admin-eyebrow">Administrator</span>
            <h1>SV Versionen einlesen</h1>
            <p>Der zentrale Softwareversions- und Lizenzkatalog wird geprueft und in die Tabelle <code>softwareversionen</code> eingelesen.</p>
        </div>
        <div class="oa-admin-import-state <?= $statusClass ?>">
            <strong><?= !empty($svStatus['ok']) ? 'Import abgeschlossen' : 'Import mit Fehlern' ?></strong>
            <span><?= number_format((float)$svStatus['duration'], 2, ',', '.') ?> s</span>
        </div>
    </div>

    <div class="oa-admin-import-grid">
        <section class="oa-admin-stat">
            <span>Importierte Eintraege</span>
            <strong><?= (int)$svStatus['imported'] ?></strong>
        </section>
        <section class="oa-admin-stat">
            <span>Download</span>
            <strong><?= htmlspecialchars($downloadText, ENT_QUOTES, 'UTF-8') ?></strong>
        </section>
        <section class="oa-admin-stat">
            <span>Lokaler Katalog</span>
            <strong><?= htmlspecialchars($localTime, ENT_QUOTES, 'UTF-8') ?></strong>
        </section>
    </div>

    <section class="oa-admin-import-details">
        <h2>Details</h2>
        <dl>
            <div><dt>Quelle</dt><dd><a href="<?= htmlspecialchars($svStatus['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($svStatus['source_url'], ENT_QUOTES, 'UTF-8') ?></a></dd></div>
            <div><dt>Lokale Datei</dt><dd><?= htmlspecialchars($svStatus['local_file'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Datenbankziel</dt><dd><code>softwareversionen</code></dd></div>
        </dl>
    </section>

    <section class="oa-admin-import-log">
        <h2>Ablauf</h2>
        <ol>
            <?php foreach ((array)$svStatus['messages'] as $message): ?>
                <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
            <?php if (!empty($svStatus['download_error'])): ?>
                <li class="is-error"><?= htmlspecialchars($svStatus['download_error'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
        </ol>
    </section>

    <div class="oa-admin-import-actions">
        <a class="oa-button" href="import_svversion.php">Erneut prüfen und einlesen</a>
        <a class="oa-button oa-button-secondary" href="list.php?view=all_softwareversionen">Softwareversionen anzeigen</a>
    </div>
</div>

</td></tr></table></body></html>
