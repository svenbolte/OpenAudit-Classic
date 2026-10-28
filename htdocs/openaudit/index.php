<?php
/** Fast cockpit shell: the browser can paint the spinner before statistics are queried. */
if (!is_file(__DIR__ . '/include_config.php')) { header('Location: setup.php'); exit; }
include __DIR__ . '/include.php';
?>
<style>
.oa-cockpit{padding:22px 24px 42px;font-family:inherit;color:#243249;max-width:1650px;margin:0 auto}.oa-cockpit *{box-sizing:border-box}
.oa-hero{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:20px;flex-wrap:wrap}.oa-hero h1{font-size:28px;line-height:1.2;margin:0 0 7px;font-weight:750;color:#18263d}.oa-muted,.oa-hero p{color:#69788c;margin:0;font-size:13px}.oa-hero .oa-date{font-size:12px;color:#69788c}
.oa-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.oa-tile{display:flex;flex-direction:column;min-height:155px;padding:19px 20px;border:1px solid #e0e6ee;border-radius:15px;background:#fff;color:inherit;text-decoration:none!important;box-shadow:0 2px 12px rgba(20,35,65,.045);transition:transform .15s,border-color .15s}.oa-tile:hover,.oa-bar-row:hover{border-color:#8bb1eb;transform:translateY(-2px)}.oa-tile-icon{font-size:21px;line-height:1}.oa-tile-label{font-size:13px;color:#607087;margin:10px 0 6px}.oa-tile-value{font-size:30px;line-height:1.1;color:#1e3659}.oa-tile-detail{font-size:12px;color:#416fb2;margin-top:auto;padding-top:10px}
.oa-section-title{font-size:17px;font-weight:700;margin:28px 0 13px;color:#263b58}.oa-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.oa-panel{border:1px solid #e0e6ee;border-radius:15px;padding:20px;background:#fff;min-width:0;box-shadow:0 2px 12px rgba(20,35,65,.035)}.oa-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:17px}.oa-panel-head h2{font-size:16px;margin:0;color:#253b59}.oa-panel-head a{font-size:12px;color:#416fb2;text-decoration:none}.oa-bars{display:grid;gap:12px}.oa-bar-row{display:grid;grid-template-columns:minmax(115px,1fr) minmax(100px,2fr) 55px;gap:10px;align-items:center;text-decoration:none!important;color:#34445c;padding:5px;border:1px solid transparent;border-radius:7px;transition:transform .15s}.oa-bar-name{font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.oa-bar-svg{width:100%;height:18px}.oa-bar-row strong{font-size:12px;text-align:right}.oa-insights{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.oa-insight{border:1px solid #e0e6ee;border-radius:12px;background:#fff;overflow:hidden}.oa-insight summary{padding:15px 17px;cursor:pointer;font-weight:650;font-size:13px;list-style:none;display:flex;justify-content:space-between}.oa-insight summary::after{content:'＋';color:#416fb2}.oa-insight[open] summary::after{content:'−'}.oa-insight-content{padding:0 14px 15px;overflow:auto;max-height:360px;font-size:12px}.oa-insight-content table{width:100%}.oa-insight-content a{color:#416fb2}.oa-loading{color:#75859b;padding:12px;display:flex;align-items:center;gap:10px}.oa-spinner{display:inline-block;width:17px;height:17px;border:2px solid #dce6f4;border-top-color:#416fb2;border-radius:50%;animation:oa-spin .7s linear infinite;flex-shrink:0}@keyframes oa-spin{to{transform:rotate(360deg)}}.oa-page-loading{position:fixed;right:22px;bottom:22px;z-index:9999;background:#fff;border:1px solid #dce6f4;box-shadow:0 5px 22px #223a6022;border-radius:12px;padding:12px 17px;display:flex;align-items:center;gap:11px;color:#345174;font-size:13px;transition:opacity .2s}.oa-page-loading[hidden]{display:none}.oa-insight-content .npb_content_data{display:block!important}.oa-insight-content .tftable{font-size:12px}.oa-unicode-icon{display:inline-block;font-family:"Segoe UI Symbol","Arial Unicode MS",sans-serif;font-size:1.1em;line-height:1;vertical-align:middle;color:#416fb2;margin:0 3px}
@media(max-width:1050px){.oa-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.oa-cockpit{padding:14px}.oa-kpis,.oa-charts,.oa-insights{grid-template-columns:1fr}.oa-bar-row{grid-template-columns:minmax(80px,1fr) minmax(70px,1fr) 40px}}
</style>
<style>
.oa-start-overlay{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(244,247,251,.95);font-family:Arial,sans-serif;color:#27405f}
.oa-start-box{display:flex;align-items:center;gap:16px;background:white;border:1px solid #dce5f0;border-radius:16px;padding:24px 30px;box-shadow:0 12px 40px #172d4920}
.oa-start-box strong{display:block;font-size:17px}.oa-start-box small{display:block;margin-top:6px;color:#718096;font-size:12px}
.oa-start-box .oa-spinner{width:32px;height:32px;border-width:3px}
</style>
<td id="CenterColumn" class="main_each"><main class="oa-cockpit" id="oa-cockpit-content" aria-busy="true"></main></td></table>
<div class="oa-start-overlay" id="oa-start-overlay" role="status" aria-live="polite"><div class="oa-start-box"><span class="oa-spinner" aria-hidden="true"></span><div><strong>OpenAudit-Cockpit wird geladen …</strong><small>Inventar und Statistiken werden abgefragt</small></div></div></div>
<script>
(function(){
 var host=document.getElementById('oa-cockpit-content'), overlay=document.getElementById('oa-start-overlay');
 function showError(){host.innerHTML='<section class="oa-panel"><h2>Cockpit konnte nicht geladen werden</h2><p>Bitte die Serverprotokolle prüfen und die Seite erneut laden.</p><button type="button" onclick="location.reload()">Erneut versuchen</button></section>';}
 fetch('index_cockpit_data.php',{credentials:'same-origin',cache:'no-store'})
 .then(function(r){if(!r.ok)throw Error('HTTP '+r.status);return r.text()})
 .then(function(html){
  var doc=new DOMParser().parseFromString(html,'text/html');
  var scripts=Array.from(doc.querySelectorAll('script'));
  scripts.forEach(function(e){e.remove()});
  host.innerHTML=doc.body.innerHTML;
  scripts.forEach(function(s){var n=document.createElement('script');n.textContent=s.textContent;document.body.appendChild(n);n.remove();});
  host.setAttribute('aria-busy','false');
 }).catch(function(e){console.error('OpenAudit cockpit:',e);showError();})
 .finally(function(){overlay.remove();});
})();
</script>
</body></html>
