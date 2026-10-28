<?php
/** OpenAudit cockpit. Drop-in replacement for index.php; existing navigation and authentication stay in include.php. */
if (!is_file(__DIR__ . '/include_config.php')) { header('Location: setup.php'); exit; }
ob_start();
include __DIR__ . '/include.php';
ob_end_clean();

function oa_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function oa_query($sql) {
    global $db;
    if (!isset($db) || !($db instanceof mysqli)) return false;
    try {
        return mysqli_query($db, $sql);
    } catch (mysqli_sql_exception $e) {
        error_log('OpenAudit Cockpit SQL: ' . $e->getMessage());
        return false;
    }
}
function oa_number($sql) {
    $r = oa_query($sql);
    if (!$r) return null;
    $row = mysqli_fetch_row($r);
    mysqli_free_result($r);
    return $row ? (int)$row[0] : null;
}
function oa_groups($sql) {
    $r = oa_query($sql);
    if (!$r) return [];
    $rows = [];
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    mysqli_free_result($r);
    return $rows;
}
function oa_card($title, $value, $detail, $url, $icon) {
    echo '<a class="oa-tile" href="'.oa_h($url).'" aria-label="'.oa_h($title).' – Ansicht öffnen">';
    echo '<span class="oa-tile-icon">'.oa_h($icon).'</span><span class="oa-tile-label">'.oa_h($title).'</span>';
    echo '<strong class="oa-tile-value">'.($value === null ? '–' : number_format((int)$value, 0, ',', '.')).'</strong>';
    echo '<span class="oa-tile-detail">'.oa_h($detail).' <span aria-hidden="true">↗</span></span></a>';
}
function oa_bars($title, $rows, $url, $color) {
    echo '<section class="oa-panel"><div class="oa-panel-head"><h2>'.oa_h($title).'</h2><a href="'.oa_h($url).'">Gesamtansicht ↗</a></div>';
    if (!$rows) { echo '<p class="oa-muted">Keine Daten verfügbar.</p></section>'; return; }
    $max = max(1, max(array_map(function($r){return (int)$r['n'];}, $rows)));
    echo '<div class="oa-bars">';
    foreach ($rows as $row) {
        $name = trim((string)$row['label']) ?: 'Unbekannt';
        $n = (int)$row['n'];
        $width = max(0, min(100, $n / $max * 100));
        echo '<a class="oa-bar-row" href="'.oa_h($url).'" title="'.oa_h($name.' – '.$n).'">';
        echo '<span class="oa-bar-name">'.oa_h($name).'</span>';
        echo '<svg class="oa-bar-svg" viewBox="0 0 100 12" preserveAspectRatio="none" role="img" aria-label="'.oa_h($name.': '.$n).'">';
        echo '<rect x="0" y="1" width="100" height="10" rx="3" fill="#e9eef6"/>';
        echo '<rect x="0" y="1" width="'.number_format($width, 2, '.', '').'" height="10" rx="3" fill="'.oa_h($color).'"/></svg>';
        echo '<strong>'.number_format($n, 0, ',', '.').'</strong></a>';
    }
    echo '</div></section>';
}

$systems = oa_number('SELECT COUNT(*) FROM `system`');
$software = oa_number('SELECT COUNT(*) FROM `software`');
$printers = oa_number("SELECT COUNT(*) FROM `other` WHERE other_type='printer' AND COALESCE(other_p_port_name,'') NOT LIKE '\\\\%'");
$other = oa_number("SELECT COUNT(*) FROM `other` WHERE other_type <> 'printer' OR other_type IS NULL");
$audited30 = oa_number("SELECT COUNT(*) FROM `system` WHERE system_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 DAY), '%Y%m%d000000')");
$stale90 = oa_number("SELECT COUNT(*) FROM `system` WHERE system_timestamp < DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 90 DAY), '%Y%m%d000000') OR system_timestamp IS NULL OR system_timestamp = ''");
$recent = oa_number("SELECT COUNT(*) FROM `system` WHERE system_first_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 DAY), '%Y%m%d000000')");
$shared = oa_number("SELECT COUNT(*) FROM `other` WHERE other_type='printer' AND other_p_shared IN ('True','true','1','Yes','yes','y') AND COALESCE(other_p_port_name,'') NOT LIKE '\\\\%'");
$os = oa_groups("SELECT COALESCE(NULLIF(TRIM(system_os_name),''),'Unbekannt') AS label, COUNT(*) AS n FROM `system` GROUP BY label ORDER BY n DESC LIMIT 8");
$makers = oa_groups("SELECT COALESCE(NULLIF(TRIM(system_vendor),''),'Unbekannt') AS label, COUNT(*) AS n FROM `system` GROUP BY label ORDER BY n DESC LIMIT 8");
$models = oa_groups("SELECT COALESCE(NULLIF(TRIM(system_model),''),'Unbekannt') AS label, COUNT(*) AS n FROM `system` GROUP BY label ORDER BY n DESC LIMIT 8");
$types = oa_groups("SELECT COALESCE(NULLIF(TRIM(system_system_type),''),'Unbekannt') AS label, COUNT(*) AS n FROM `system` GROUP BY label ORDER BY n DESC LIMIT 8");
// Reuse the exact definition of "Old Software w/ Hosts" instead of
// inventing a second, potentially inconsistent definition of "outdated".
$oldSoftwareCount = null;
$oldSoftwareGroups = [];
$oldSoftwareDefinition = __DIR__ . '/list_viewdef_all_software_hosts_old.php';
if (is_file($oldSoftwareDefinition)) {
    $query_array = null;
    include $oldSoftwareDefinition;
    if (isset($query_array['sql']) && is_string($query_array['sql'])) {
        $oldSql = trim($query_array['sql']);
        $oldSql = rtrim($oldSql, "; \t\r\n");
        // This existing query returns one row per outdated software/version.
        $oldSoftwareCount = oa_number('SELECT COUNT(*) FROM (' . $oldSql . ') AS oa_outdated');
        $oldSoftwareGroups = oa_groups('SELECT software_name AS label, COUNT(*) AS n FROM (' . $oldSql . ') AS oa_outdated GROUP BY software_name ORDER BY n DESC, label ASC LIMIT 8');
    }
    unset($query_array);
}
$legacy = [
 ['f1','Neu erkannte Systeme','show_system_discovered'],
 ['f2','Neu erkannte sonstige Geräte','show_other_discovered'],
 ['f3','Nicht kürzlich auditierte Systeme','show_systems_not_audited'],
 ['f4','Partitionen mit wenig freiem Speicher','show_partition_usage'],
 ['f5','Neu erkannte Software','show_software_detected'],
 ['f6','Webserver','show_detected_servers'],
 ['f7','FTP-Server','show_detected_servers'],
 ['f8','Telnet-Server','show_detected_servers'],
 ['f9','Mailserver','show_detected_servers'],
 ['f10','VNC-Server','show_detected_servers'],
 ['f12','RDP-/Terminalserver','show_detected_rdp'],
 ['f13','Datenbankserver','show_detected_servers'],
 ['f11','Veralteter Virenschutz','show_detected_xp_av'],
 ['f15','LDAP-Verzeichnisänderungen','show_ldap_changes'],
 ['f16','Festplattenwarnungen','show_hard_disk_alerts'],
];
?>
<div class="oa-hero"><div><h1>IT-Audit Cockpit</h1><p>Inventar, Audit-Aktualität und Geräteverteilung auf einen Blick. Alle Kennzahlen führen zur passenden Ansicht.</p></div><span class="oa-date">Stand: <?=oa_h(date('d.m.Y H:i'))?></span></div>
<div class="oa-kpis">
<?php
 oa_card('Inventarisierte Systeme',$systems,'Alle Systeme','list.php?view=all_systems_more','▣');
 oa_card('Software-Einträge',$software,'Softwareinventar','list.php?view=all_software','▤');
 oa_card('Installierte Drucker',$printers,'Ohne UNC-Anschlüsse','list.php?view=all_printers_installed','▧');
 oa_card('Sonstige Geräte',$other,'Geräteübersicht','list.php?view=all_other','◈');
 oa_card('Audit in letzten 30 Tagen',$audited30,'Aktuelle Systemdaten','list.php?view=all_systems_more','✓');
 oa_card('Audit älter als 90 Tage',$stale90,'Prüfbedarf · Systemliste','list.php?view=all_systems_more','◷');
 oa_card('Neue Systeme (30 Tage)',$recent,'Neu im Inventar','list.php?view=all_systems_more','＋');
 oa_card('Freigegebene Drucker',$shared,'Druckerübersicht','list.php?view=all_printers_installed','⇄');
 oa_card('Veraltete Software',$oldSoftwareCount,'Veraltete Programm-/Versionskombinationen · mit Rechnern','list.php?view=all_software_hosts_old','⚠');
?>
</div>
<h2 class="oa-section-title">Verteilung und Struktur</h2>
<div class="oa-charts">
<?php
 oa_bars('Betriebssysteme',$os,'list.php?view=statistic_os','#3874cc');
 oa_bars('Hardwarehersteller',$makers,'list.php?view=statistic_manufacturer','#159b92');
 oa_bars('Gerätemodelle',$models,'list.php?view=statistic_model','#8b6ad7');
 oa_bars('Systemtypen',$types,'list.php?view=all_systems_more','#db8b3c');
 oa_bars('Veraltete Software nach Produkt',$oldSoftwareGroups,'list.php?view=all_software_hosts_old','#d97745');
?>
</div>
<h2 class="oa-section-title">Bestehende Audit-Meldungen und Detailwerte</h2>
<p class="oa-muted" style="margin-bottom:13px">Die bisherigen Startseiten-Auswertungen bleiben erhalten. Bereiche öffnen, um die Daten bei Bedarf nachzuladen.</p>
<div class="oa-insights">
<?php foreach ($legacy as $item):
 $key=$item[0]; $flag=$item[2];
 if (isset($GLOBALS[$flag]) && $GLOBALS[$flag] !== 'y') continue;
?><details class="oa-insight" data-sub="<?=oa_h($key)?>"><summary><?=oa_h($item[1])?></summary><div class="oa-insight-content"><p class="oa-loading">Zum Laden öffnen …</p></div></details><?php endforeach; ?>
</div>
<script>
(function(){
 var pageLoader=document.getElementById('oa-page-loading');
 function hidePageLoader(){if(pageLoader) pageLoader.hidden=true;}
 if(document.readyState==='complete') hidePageLoader();
 else window.addEventListener('load',hidePageLoader,{once:true});
 document.querySelectorAll('.oa-insight').forEach(function(panel){
  panel.addEventListener('toggle',function(){
   if(!panel.open || panel.dataset.loaded) return;
   panel.dataset.loaded='1';
   var box=panel.querySelector('.oa-insight-content');
   box.innerHTML='<p class="oa-loading"><span class="oa-spinner" aria-hidden="true"></span>Daten werden geladen …</p>';
   fetch('index_data.php?sub='+encodeURIComponent(panel.dataset.sub),{credentials:'same-origin'})
    .then(function(r){if(!r.ok) throw Error('HTTP '+r.status);return r.text()})
    .then(function(html){
      // Existing index_data.php supplies server-rendered HTML; scripts are not executed.
      var doc=new DOMParser().parseFromString(html,'text/html');
      doc.querySelectorAll('script,iframe,object,embed').forEach(function(e){e.remove()});
      // Replace legacy bitmap icons with Unicode symbols, avoiding broken-image placeholders.
      doc.querySelectorAll('img').forEach(function(img){
        var src=(img.getAttribute('src')||'').toLowerCase();
        var alt=(img.getAttribute('alt')||'').toLowerCase();
        var symbol='▣';
        if(/hourglass|busy|loading|retriev/.test(src+' '+alt)) symbol='◷';
        else if(/ldap|directory|account|user/.test(src+' '+alt)) symbol='♙';
        else if(/warning|alert|error|critical/.test(src+' '+alt)) symbol='⚠';
        else if(/ok|success|check|tick/.test(src+' '+alt)) symbol='✓';
        else if(/feed|rss/.test(src+' '+alt)) symbol='◉';
        else if(/disk|drive|storage/.test(src+' '+alt)) symbol='▤';
        else if(/printer|print/.test(src+' '+alt)) symbol='▧';
        else if(/server|computer|system/.test(src+' '+alt)) symbol='▣';
        var icon=doc.createElement('span');
        icon.className='oa-unicode-icon';
        icon.setAttribute('aria-label',img.getAttribute('alt')||'Symbol');
        icon.textContent=symbol;
        img.replaceWith(icon);
      });
      box.innerHTML=doc.body.innerHTML || '<p class="oa-muted">Keine Einträge.</p>';
      box.querySelectorAll('[style]').forEach(function(e){if(e.classList.contains('npb_content_data')) e.style.display='block'});
    }).catch(function(){box.textContent='Diese Auswertung konnte nicht geladen werden.'});
  });
 });
})();
</script>
