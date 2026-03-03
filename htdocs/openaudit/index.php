<?php
/**********************************************************************************************************
Module:	index.php
Description:	Home page for Open Audit application.
**********************************************************************************************************/

$page = "";
$extra = "";
$software = "";
$count = 0;
$total_rows = 0;
$latest_version = "10.09.01";

// Check for config, otherwise run setup
if (!file_exists("include_config.php")) {
	header("Location: setup.php");
	exit;
}

include "include.php";

$software = GetGETOrDefaultValue("software", "");
$sort     = GetGETOrDefaultValue("sort", "system_name");

//******* Display Graph *****************************************************
function DisplayAuditGraph(): void
{
	global $systems_audited_days;
	echo "<div class='npb_section_shadow'>";
	echo "	<div class='npb_section_content'>";
	echo "		<div class='npb_section_heading'>";
	echo "			<a>Systems Audited in the last " . $systems_audited_days . " Days</a>";
	echo "		</div>";
	echo "		<div class='npb_section_data' id='AuditedSystems'>";
	echo "			<img class='npb_auditedsystems_hourglass' alt=' Retrieving...' src='images/hourglass-busy.gif'/>";
	echo "		</div>";
	echo "	</div>";
	echo "</div>";
}

/******* Generic display section function *****************************************************
	$SwitchID			- String	-	Unique element ID to be used by switchUl() function
	$Display			-	String	-	Section description (heading) string to be displayed
	$DivID				-	String	-  Unique element ID used by the HttpRequestor object
	$TotalString		- String	- String used in "total" description
	$RssUrl				- String	- RSS URL string
**********************************************************************************************/
function DisplaySection(string $SwitchID, string $Display, string $DivID, string $TotalString, string $RssUrl = ''): void
{
	echo "<div class='npb_section_shadow'>";
	echo "	<div class='npb_section_content'>";
	echo "		<div class='npb_section_heading'>";

	// Only for sections with RSS feed
	if ($RssUrl !== '') {
		$rssUrl = htmlspecialchars($RssUrl, ENT_QUOTES, 'UTF-8');
		echo "<a href='{$rssUrl}'><img class='npb_rss' src=\"images/feed-icon.png\" alt=\"RSS Feed\" /></a>";
	}

	// Note: $Display is typically composed from translated strings + numbers; keep behavior as-is.
	$switchEsc = htmlspecialchars($SwitchID, ENT_QUOTES, 'UTF-8');
	$divEsc    = htmlspecialchars($DivID, ENT_QUOTES, 'UTF-8');

	echo "			<a href=\"javascript://\" onclick=\"switchUl('{$switchEsc}');\">{$Display}</a>";
	echo "			<i class='fa fa-arrow-down' style='float:right;margin-top:6px;cursor:pointer' onclick=\"switchUl('{$switchEsc}');\"></i>";
	echo "		</div>";
	echo "		<div class='npb_section_data' id='{$divEsc}'>";
	echo "			<p class='npb_section_summary'>" . __($TotalString) . ": <img class='npb_hourglass' alt='Retrieving...' src='images/hourglass-busy.gif'/></p>";
	echo "		</div>";
	echo "	</div>";
	echo "</div>";
}

/**
 * Build a safe JS variable name from a DOM id.
 * Example: RecentlyDiscoveredSystems -> RecentlyDiscoveredSystemsXml
 */
function JsVarName(string $divId): string
{
	$base = preg_replace('/\W+/', '', $divId) ?: 'Section';
	return $base . 'Xml';
}

?>

<script>
function switchUl(param) {
  // Select element that matches ID + class
  const el = document.querySelector(`#${param}.npb_content_data`);

  if (el) {
    if (window.getComputedStyle(el).display === 'none') {
      el.style.display = 'block';
    } else {
      el.style.display = 'none';
    }
  } else {
    console.warn(`Element mit ID #${param} und Klasse .npb_content_data nicht gefunden.`);
  }
}
</script>

<?php
// ---------- Section configuration (single source of truth) ----------
$sections = [
	// key => [enabled, type, div, title, total, rss]
	'f1'  => ['enabled' => ($show_system_discovered === 'y'),     'type' => 'section', 'div' => 'RecentlyDiscoveredSystems', 'title' => __("Systems Discovered in the last ") . $system_detected . __(" Days"), 'total' => 'Systems',       'rss' => 'rss_new_systems.php'],
	'f2'  => ['enabled' => ($show_other_discovered === 'y'),      'type' => 'section', 'div' => 'OtherDiscovered',          'title' => __("Other Items Discovered in the last ") . $other_detected . __(" Days"),  'total' => 'Other Items',   'rss' => 'rss_new_other.php'],
	'f3'  => ['enabled' => ($show_systems_not_audited === 'y'),   'type' => 'section', 'div' => 'SystemsNotAudited',        'title' => __("Systems Not Audited in the last ") . $days_systems_not_audited . __(" Days"), 'total' => 'Systems'],
	'f4'  => ['enabled' => ($show_partition_usage === 'y'),       'type' => 'section', 'div' => 'PartitionUsage',          'title' => __("Partition free space less than ") . $partition_free_space . __(" MB"), 'total' => 'Partitions'],
	'f5'  => ['enabled' => ($show_software_detected === 'y'),     'type' => 'section', 'div' => 'DetectedSoftware',         'title' => __("Software detected in the last ") . $days_software_detected . __(" Days"), 'total' => 'Packages',      'rss' => 'rss_new_software.php'],

	// Detected servers group
	'f6'  => ['enabled' => ($show_detected_servers === 'y'),      'type' => 'section', 'div' => 'WebServers',               'title' => __("Web Servers"),                     'total' => 'Systems'],
	'f7'  => ['enabled' => ($show_detected_servers === 'y'),      'type' => 'section', 'div' => 'FtpServers',               'title' => __("FTP Servers"),                     'total' => 'Systems'],
	'f8'  => ['enabled' => ($show_detected_servers === 'y'),      'type' => 'section', 'div' => 'TelnetServers',            'title' => __("Telnet Servers"),                  'total' => 'Systems'],
	'f9'  => ['enabled' => ($show_detected_servers === 'y'),      'type' => 'section', 'div' => 'EmailServers',             'title' => __("Email Servers"),                   'total' => 'Systems'],
	'f10' => ['enabled' => ($show_detected_servers === 'y'),      'type' => 'section', 'div' => 'VncServers',               'title' => __("VNC Servers"),                     'total' => 'Systems'],
	'f12' => ['enabled' => ($show_detected_servers === 'y' && $show_detected_rdp === 'y'), 'type' => 'section', 'div' => 'RDPServers',  'title' => __('RDP and Terminal Servers'), 'total' => 'Systems'],
	'f13' => ['enabled' => ($show_detected_servers === 'y'),      'type' => 'section', 'div' => 'DbServers',                'title' => __('Database Servers'),                'total' => 'Systems'],

	'f11' => ['enabled' => ($show_detected_xp_av === 'y'),        'type' => 'section', 'div' => 'DetectedXpAv',             'title' => __("windows systems without up to date AntiVirus"), 'total' => 'Systems'],
	'f15' => ['enabled' => ($show_ldap_changes === 'y'),          'type' => 'section', 'div' => 'AdInfo',                   'title' => __("LDAP Directory changes in the last " . $ldap_changes_days . " days"), 'total' => 'Accounts', 'rss' => 'rss_ldap_directory_changes.php'],

	// Graph (has its own renderer but same data loader)
	'f14' => ['enabled' => ($show_systems_audited_graph === 'y'),  'type' => 'graph',   'div' => 'AuditedSystems'],

	'f16' => ['enabled' => ($show_hard_disk_alerts === 'y'),      'type' => 'section', 'div' => 'HardDisksAlerts',          'title' => __("Hard Disks Alerts detected in the last ") . $hard_disk_alerts_days . __(" Days"), 'total' => 'Systems', 'rss' => 'rss_hard_disk_alerts.php'],
];
?>

<!-- Create HttpRequestors -->
<script>//<![CDATA[
<?php
foreach ($sections as $key => $cfg) {
	if (empty($cfg['enabled'])) continue;
	$div = $cfg['div'];
	$var = JsVarName($div);
	echo "var {$var}=new HttpRequestor('{$div}');\n";
}
?>
//]]></script>

<?php
$title = "";
$show_all = "1";
if (isset($_GET["show_all"])) {
	$count_system = '10000';
}
if (isset($_GET["page_count"])) {
	$page_count = (int)$_GET["page_count"];
} else {
	$page_count = 0;
}
$page_prev = $page_count - 1;
if ($page_prev < 0) {
	$page_prev = 0;
}
$page_next = $page_count + 1;
$page_current = $page_count;
$page_count = $page_count * $count_system;

echo '<style>.mehrspaltig {display: grid;grid-template-columns: repeat(auto-fit, minmax(580px, 1fr));gap: 5px}</style>';
echo '<td id="CenterColumn" class="main_each" ><div class="mehrspaltig">';

// Render sections (data-driven)
foreach ($sections as $key => $cfg) {
	if (empty($cfg['enabled'])) continue;

	if (($cfg['type'] ?? 'section') === 'graph') {
		DisplayAuditGraph();
		continue;
	}

	DisplaySection(
		$key,
		$cfg['title'],
		$cfg['div'],
		$cfg['total'],
		$cfg['rss'] ?? ''
	);
}

echo '</div>';
echo "</td></table>\n";
?>

<script type='text/javascript'>//<![CDATA[
<?php
// Initiate retrieval of data for each enabled section
foreach ($sections as $key => $cfg) {
	if (empty($cfg['enabled'])) continue;
	$div = $cfg['div'];
	$var = JsVarName($div);
	echo "{$var}.send('index_data.php?sub={$key}');\n";
}
?>
//]]></script>

</body>
</html>
