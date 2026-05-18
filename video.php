<?php
/**
 * DanKa / TRE-PA — Bunny Stream Video Player
 *
 * Solves the Moodle mobile-app blank-screen problem: because this page is
 * served from cursos.dankarh.com.br (the Moodle domain), Bunny Stream's
 * referrer check passes and the video loads inside the app's WebView.
 *
 * URL: /theme/atipico/video.php?cmid=INT&guid=STRING[&dur=INT]
 *   cmid  — course-module id of the mod_url activity that wraps this player
 *   guid  — Bunny Stream video GUID
 *   dur   — (optional fallback) duration in seconds; ignored when the Bunny
 *            API is reachable and returns a valid length
 *
 * Duration source (priority order):
 *   1. Bunny Stream API — GET /library/{lib}/videos/{guid}, field "length"
 *      Key: get_config('theme_atipico','bunny_api_key') or BUNNY_STREAM_API_KEY env var
 *   2. dur URL param (fallback when API key absent or API unreachable)
 *
 * Completion: increments a 1-second timer while the page is visible.
 * When watched seconds >= duration * 0.8, calls Moodle's manual-completion AJAX
 * endpoint and marks the activity complete (green tick in Moodle).
 * Also listens for Bunny postMessage progress events as a secondary signal.
 *
 * @package   theme_atipico
 * @copyright DanKa Treinamentos
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// ── Parameters ────────────────────────────────────────────────────────────────
$cmid = required_param('cmid', PARAM_INT);
$guid = required_param('guid', PARAM_ALPHANUMEXT);

// ── Auth & course-module access ───────────────────────────────────────────────
// No fallback: if the cmid is invalid or the user lacks access, Moodle's own
// error handling redirects to login or shows an access-denied page.
[$course, $cm] = get_course_and_cm_from_cmid($cmid);
require_login($course, false, $cm);
$modcontext = context_module::instance($cmid);
$title      = format_string($cm->name, true, ['context' => $modcontext]);

// ── Video duration — server-side from Bunny API (primary) ────────────────────
$bunny_lib = '661783';
$duration  = 0;

$bunny_api_key = (string)get_config('theme_atipico', 'bunny_api_key');
if ($bunny_api_key === '') {
    $bunny_api_key = (string)getenv('BUNNY_STREAM_API_KEY');
}

if ($bunny_api_key !== '') {
    $ch = curl_init("https://video.bunnycdn.com/library/{$bunny_lib}/videos/{$guid}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["AccessKey: {$bunny_api_key}", 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp      = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $resp !== false) {
        $vdata = json_decode($resp, true);
        if (isset($vdata['length']) && (int)$vdata['length'] > 0) {
            $duration = (int)$vdata['length'];
        }
    }
}

// Fallback: accept dur from URL only when API key is absent or API unreachable
if ($duration <= 0) {
    $duration = optional_param('dur', 0, PARAM_INT);
}

$threshold = ($duration > 0) ? (int)round($duration * 0.8) : 0;

// Bunny embed: autoplay=false keeps it polite; preload=metadata is fast
$embed_url  = "https://iframe.mediadelivery.net/embed/{$bunny_lib}/{$guid}"
            . "?autoplay=false&loop=false&muted=false&preload=true"
            . "&responsive=true";

$wwwroot    = $CFG->wwwroot;
$sesskey    = sesskey();
$cmid_js    = (int)$cmid;
$dur_js     = (int)$duration;
$thr_js     = (int)$threshold;

// ── Output ────────────────────────────────────────────────────────────────────
// Bypass Moodle's output system for a clean standalone player page.
// Session is already started by require_login() above.
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($title, ENT_QUOTES); ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:    #DAAA00;
      --teal:    #298976;
      --bg:      #0d0b1a;
      --surface: #16122b;
      --text:    #f0edf8;
      --muted:   #9b94b8;
    }

    html, body {
      height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: 'Segoe UI', system-ui, sans-serif;
      font-size: 15px;
      overscroll-behavior: none;
    }

    /* ── Layout ── */
    .at-player-wrap {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      max-width: 960px;
      margin: 0 auto;
      padding: 0 0 32px;
    }

    /* ── Title bar ── */
    .at-player-title {
      padding: 14px 20px 10px;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--muted);
      letter-spacing: 0.02em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ── Video container: responsive 16:9 ── */
    .at-video-box {
      position: relative;
      width: 100%;
      padding-top: 56.25%; /* 16:9 */
      background: #000;
    }
    .at-video-box iframe {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }

    /* ── Progress bar ── */
    .at-progress-track {
      height: 4px;
      background: rgba(255,255,255,0.08);
      border-radius: 2px;
      margin: 0 20px;
      overflow: hidden;
    }
    .at-progress-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, var(--teal), var(--gold));
      border-radius: 2px;
      transition: width 0.8s linear;
    }

    /* ── Status row ── */
    .at-status-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 20px 0;
      font-size: 0.78rem;
      color: var(--muted);
    }
    .at-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 600;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.10);
      color: var(--muted);
      transition: all 0.4s ease;
    }
    .at-status-badge.done {
      background: rgba(41,137,118,0.15);
      border-color: rgba(41,137,118,0.4);
      color: #5de8c8;
    }
    .at-status-badge.done::before { content: '✓  '; }

    /* ── No-duration notice ── */
    .at-manual-btn {
      display: none;
      margin: 20px auto 0;
      padding: 10px 28px;
      background: transparent;
      border: 1.5px solid var(--gold);
      color: var(--gold);
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .at-manual-btn:hover { background: rgba(218,170,0,0.10); }

    /* ── Completion overlay (brief flash) ── */
    .at-done-toast {
      position: fixed;
      bottom: 28px;
      left: 50%;
      transform: translateX(-50%) translateY(20px);
      background: rgba(41,137,118,0.95);
      color: #fff;
      padding: 12px 28px;
      border-radius: 40px;
      font-size: 0.95rem;
      font-weight: 700;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.4s ease, transform 0.4s ease;
      z-index: 9999;
      white-space: nowrap;
    }
    .at-done-toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }
  </style>
</head>
<body>
<div class="at-player-wrap">
  <div class="at-player-title"><?php echo htmlspecialchars($title, ENT_QUOTES); ?></div>

  <div class="at-video-box">
    <iframe
      id="at-bunny-player"
      src="<?php echo htmlspecialchars($embed_url, ENT_QUOTES); ?>"
      allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
      allowfullscreen
      loading="lazy"
    ></iframe>
  </div>

  <div class="at-progress-track">
    <div class="at-progress-fill" id="at-pfill"></div>
  </div>

  <div class="at-status-row">
    <span id="at-watched-label">0:00 assistido</span>
    <span class="at-status-badge" id="at-status-badge">Em andamento</span>
  </div>

  <button class="at-manual-btn" id="at-manual-btn"
          onclick="markComplete(true)">
    Marcar como concluído
  </button>
</div>

<div class="at-done-toast" id="at-toast">✓ Aula concluída!</div>

<script>
(function () {
  'use strict';

  // ── Config (injected by PHP) ────────────────────────────────────────────────
  var MOODLE_WWW  = <?php echo json_encode($wwwroot); ?>;
  var SESSKEY     = <?php echo json_encode($sesskey); ?>;
  var CMID        = <?php echo $cmid_js; ?>;
  var DURATION    = <?php echo $dur_js; ?>;   // seconds; 0 = unknown
  var THRESHOLD   = <?php echo $thr_js; ?>;   // seconds to watch for 80%; 0 = manual only

  // ── State ───────────────────────────────────────────────────────────────────
  var watchedSec    = 0;
  var completionDone = false;

  // ── DOM refs ─────────────────────────────────────────────────────────────────
  var pfill    = document.getElementById('at-pfill');
  var badge    = document.getElementById('at-status-badge');
  var watched  = document.getElementById('at-watched-label');
  var toast    = document.getElementById('at-toast');
  var manualBtn= document.getElementById('at-manual-btn');

  // If no duration, show manual button as fallback
  if (THRESHOLD <= 0) {
    manualBtn.style.display = 'block';
  }

  // ── Helpers ──────────────────────────────────────────────────────────────────
  function fmtTime(sec) {
    var m = Math.floor(sec / 60);
    var s = sec % 60;
    return m + ':' + (s < 10 ? '0' : '') + s;
  }

  function updateUI() {
    if (THRESHOLD > 0) {
      var pct = Math.min(100, (watchedSec / THRESHOLD) * 100);
      pfill.style.width = pct + '%';
    }
    watched.textContent = fmtTime(watchedSec) + ' assistido';
  }

  // ── Timer: count seconds while page is visible ───────────────────────────────
  var timer = setInterval(function () {
    if (document.hidden)   { return; }
    if (completionDone)    { clearInterval(timer); return; }
    watchedSec++;
    updateUI();
    if (THRESHOLD > 0 && watchedSec >= THRESHOLD) {
      markComplete(false);
    }
  }, 1000);

  // ── Bunny postMessage listener (secondary signal) ───────────────────────────
  // Bunny may emit progress events — treat >= 80% as completion trigger.
  var BUNNY_ORIGINS = ['https://iframe.mediadelivery.net', 'https://player.mediadelivery.net'];

  window.addEventListener('message', function (e) {
    if (completionDone) { return; }
    if (BUNNY_ORIGINS.indexOf(e.origin) === -1) { return; }
    try {
      var msg = (typeof e.data === 'string') ? JSON.parse(e.data) : e.data;
      if (!msg) { return; }

      var pct = null;

      // Pattern A: { event: 'timeupdate', currentTime: X, duration: Y }
      if (msg.event === 'timeupdate' && msg.duration > 0) {
        pct = msg.currentTime / msg.duration;
      }
      // Pattern B: { type: 'progress', value: 0..1 }
      else if (msg.type === 'progress' && typeof msg.value === 'number') {
        pct = msg.value;
      }
      // Pattern C: { percentage: 0..100 }
      else if (typeof msg.percentage === 'number') {
        pct = msg.percentage / 100;
      }
      // Pattern D: { event: 'progress', data: { percent: 0..100 } }
      else if (msg.event === 'progress' && msg.data && typeof msg.data.percent === 'number') {
        pct = msg.data.percent / 100;
      }

      if (pct !== null && pct >= 0.8) {
        markComplete(false);
      }
    } catch (err) { /* ignore malformed messages */ }
  });

  // ── Completion ───────────────────────────────────────────────────────────────
  function markComplete(manual) {
    if (completionDone) { return; }
    completionDone = true;
    clearInterval(timer);

    // UI feedback
    pfill.style.width = '100%';
    badge.textContent = 'Concluído';
    badge.classList.add('done');
    manualBtn.style.display = 'none';
    toast.classList.add('show');
    setTimeout(function () { toast.classList.remove('show'); }, 3500);

    // AJAX → Moodle manual completion
    if (!CMID) { return; }
    var payload = JSON.stringify([{
      index: 0,
      methodname: 'core_completion_update_activity_completion_status_manually',
      args: { cmid: CMID, completed: 1 }
    }]);

    fetch(MOODLE_WWW + '/lib/ajax/service.php?sesskey=' + encodeURIComponent(SESSKEY), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: payload,
      credentials: 'include'
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      console.log('[DanKa] Completion recorded:', data);
    })
    .catch(function (err) {
      console.warn('[DanKa] Completion AJAX failed:', err);
      // Store for retry on next page load
      try {
        localStorage.setItem('danka_pending_completion_' + CMID, '1');
      } catch(e) {}
    });
  }

  // ── Retry any pending completions from previous sessions ─────────────────────
  (function retryPending() {
    try {
      var key = 'danka_pending_completion_' + CMID;
      if (localStorage.getItem(key) === '1') {
        var payload = JSON.stringify([{
          index: 0,
          methodname: 'core_completion_update_activity_completion_status_manually',
          args: { cmid: CMID, completed: 1 }
        }]);
        fetch(MOODLE_WWW + '/lib/ajax/service.php?sesskey=' + encodeURIComponent(SESSKEY), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: payload,
          credentials: 'include'
        }).then(function () { localStorage.removeItem(key); });
      }
    } catch(e) {}
  }());

})();
</script>
</body>
</html>
<?php
// No Moodle footer — standalone page intentionally.
