<?php
/**
 * create_polo_iv_activity.php
 *
 * Creates one mod_interactivevideo activity (Bunny Stream) in Polo Marabá
 * (course 10, section 1) for EP 2-005.
 *
 * Replaces the mod_url approach — interactivevideo renders inside Moodle's
 * own page/WebView, so no external browser redirect and no login prompt.
 *
 * Run from Moodle root via Termius:
 *   cd /home/user/htdocs/srv1526987.hstgr.cloud
 *   php -d max_input_vars=5000 theme/atipico/scripts/create_polo_iv_activity.php
 *
 * Idempotent: skips creation if an IV activity for this GUID already exists
 * in course 10. Always cleans up the old mod_url test activity if found.
 */

define('CLI_SCRIPT', true);

$moodle_root = dirname(dirname(dirname(__DIR__)));
require($moodle_root . '/config.php');
require_once($CFG->libdir  . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

cli_writeln("=== DanKa — Create Polo Marabá IV Activity ===\n");

// ── Config ────────────────────────────────────────────────────────────────────
const COURSE_ID   = 10;
const SECTION_NO  = 1;
const GUID        = '01ae5ade-e109-445f-b86d-5dc0f6a4de9d';   // EP 2-005 (21 min)
const BUNNY_LIB   = '661783';
const VIDEO_NAME  = 'EP 2-005 — Fundamentos dos Direitos Humanos (Parte 2)';
const COMPLETION_PCT = 80;   // percent watched required for automatic completion

// ── Resolve module IDs ────────────────────────────────────────────────────────
$iv_mod  = $DB->get_field('modules', 'id', ['name' => 'interactivevideo'], MUST_EXIST);
$url_mod = $DB->get_field('modules', 'id', ['name' => 'url'],              MUST_EXIST);
cli_writeln("[OK] interactivevideo module id={$iv_mod}");

// ── Verify course ─────────────────────────────────────────────────────────────
$course = $DB->get_record('course', ['id' => COURSE_ID], '*', MUST_EXIST);
cli_writeln("[OK] Course: {$course->fullname}");

// ── Find / create section 1 ───────────────────────────────────────────────────
$section = $DB->get_record('course_sections', [
    'course'  => COURSE_ID,
    'section' => SECTION_NO,
]);
if (!$section) {
    $section = new stdClass();
    $section->course        = COURSE_ID;
    $section->section       = SECTION_NO;
    $section->name          = '';
    $section->summary       = '';
    $section->summaryformat = FORMAT_HTML;
    $section->sequence      = '';
    $section->visible       = 1;
    $section->availability  = null;
    $section->timemodified  = time();
    $section->id = $DB->insert_record('course_sections', $section);
    cli_writeln("[OK] Created section {$section->section} (id={$section->id})");
} else {
    cli_writeln("[OK] Section id={$section->id}");
}

// ── Remove old mod_url test activity if present ───────────────────────────────
$old_urls = $DB->get_records_select(
    'url',
    "course = ? AND " . $DB->sql_like('externalurl', '?'),
    [COURSE_ID, '%video.php%']
);
foreach ($old_urls as $old_url) {
    $old_cm = $DB->get_record('course_modules', [
        'module'   => $url_mod,
        'instance' => $old_url->id,
        'course'   => COURSE_ID,
    ]);
    if ($old_cm) {
        // Remove from section sequence
        $sec = $DB->get_record('course_sections', ['id' => $old_cm->section]);
        if ($sec) {
            $seq = array_values(array_filter(explode(',', $sec->sequence ?? '')));
            $seq = array_diff($seq, [(string)$old_cm->id]);
            $DB->set_field('course_sections', 'sequence', implode(',', $seq), ['id' => $sec->id]);
        }
        $DB->delete_records('course_modules', ['id' => $old_cm->id]);
        cli_writeln("[OK] Removed old mod_url cmid={$old_cm->id}");
    }
    $DB->delete_records('url', ['id' => $old_url->id]);
}

// ── Check for existing IV activity for this GUID (idempotency) ────────────────
$video_url = "https://player.mediadelivery.net/play/" . BUNNY_LIB . "/" . GUID;
$existing_iv = $DB->get_record_select(
    'interactivevideo',
    "course = ? AND videourl = ?",
    [COURSE_ID, $video_url]
);
if ($existing_iv) {
    $existing_cm = $DB->get_record('course_modules', [
        'module'   => $iv_mod,
        'instance' => $existing_iv->id,
        'course'   => COURSE_ID,
    ]);
    cli_writeln("[SKIP] IV activity already exists:");
    cli_writeln("  ivid : {$existing_iv->id}");
    cli_writeln("  cmid : " . ($existing_cm ? $existing_cm->id : 'NOT FOUND'));
    cli_writeln("  URL  : https://cursos.dankarh.com.br/course/view.php?id=" . COURSE_ID);
    rebuild_course_cache(COURSE_ID, true);
    exit(0);
}

// ── Get displayoptions from a working IV record (created via Moodle UI) ────────
// Records ivid=1,2,3 in course 5 were saved through the UI and have correct
// displayoptions with thumbnail hooks wired. Copy from whichever exists.
$tmpl_displayoptions = null;
$tmpl_endscreentext  = '';
foreach ([1, 2, 3] as $try_ivid) {
    $tmpl = $DB->get_record('interactivevideo', ['id' => $try_ivid]);
    if ($tmpl && !empty($tmpl->displayoptions)) {
        $tmpl_displayoptions = $tmpl->displayoptions;
        $tmpl_endscreentext  = $tmpl->endscreentext ?? '';
        cli_writeln("[OK] Using displayoptions template from ivid={$try_ivid}");
        break;
    }
}
if ($tmpl_displayoptions === null) {
    // Fallback: build minimal displayoptions JSON if no template found
    $tmpl_displayoptions = json_encode([
        'usecustomposterimage' => '0',
        'courseindex'          => '1',
        'distractionfreemode'  => '1',
    ]);
    cli_writeln("[WARN] No template IV found — using minimal displayoptions fallback");
}

// ── Insert interactivevideo record ────────────────────────────────────────────
// Critical fields from replit.md:
//   extendedcompletion MUST be JSON string '[]' (not int 0) — PHP8 TypeError otherwise
//   posterimage MUST be '' (CDN thumbnail URL is token-gated, returns 403)
//   completion=2 on course_modules for automatic (percentage-based) completion
$ivid = $DB->insert_record('interactivevideo', (object)[
    'course'               => COURSE_ID,
    'name'                 => VIDEO_NAME,
    'timecreated'          => time(),
    'timemodified'         => time(),
    'intro'                => '',
    'introformat'          => FORMAT_HTML,
    'source'               => 'url',
    'videourl'             => $video_url,
    'type'                 => 'bunnystream',
    'video'                => 0,
    'starttime'            => 0,
    'endtime'              => 0,
    'completionpercentage' => COMPLETION_PCT,
    'grade'                => 0,
    'displayoptions'       => $tmpl_displayoptions,
    'posterimage'          => '',
    'extendedcompletion'   => '[]',     // MUST be JSON string, not int
    'endscreentext'        => $tmpl_endscreentext,
    'displayasstartscreen' => 0,
]);
cli_writeln("[OK] interactivevideo record inserted (ivid={$ivid})");

// ── Insert course_modules record ──────────────────────────────────────────────
// completion=2 (COMPLETION_TRACKING_AUTOMATIC) so the 80% watch threshold
// triggers the green tick automatically without manual user action.
$cmid = $DB->insert_record('course_modules', (object)[
    'course'             => COURSE_ID,
    'module'             => $iv_mod,
    'instance'           => $ivid,
    'section'            => $section->id,
    'added'              => time(),
    'visible'            => 1,
    'visibleold'         => 1,
    'groupmode'          => 0,
    'groupingid'         => 0,
    'completion'         => 2,    // COMPLETION_TRACKING_AUTOMATIC
    'completionview'     => 0,
    'completionexpected' => 0,
    'score'              => 0,
    'indent'             => 0,
    'showdescription'    => 0,
    'deletioninprogress' => 0,
    'downloadcontent'    => 1,
    'availability'       => null,
]);
cli_writeln("[OK] course_modules record inserted (cmid={$cmid})");

// ── Create module context ─────────────────────────────────────────────────────
context_module::instance($cmid);
cli_writeln("[OK] Context created");

// ── Add to section sequence ───────────────────────────────────────────────────
$seq   = array_values(array_filter(explode(',', $section->sequence ?? '')));
$seq[] = (string)$cmid;
$DB->set_field('course_sections', 'sequence', implode(',', $seq), ['id' => $section->id]);
cli_writeln("[OK] Added cmid to section sequence");

// ── Rebuild course cache ──────────────────────────────────────────────────────
rebuild_course_cache(COURSE_ID, true);
cli_writeln("[OK] Course cache rebuilt");

// ── Summary ───────────────────────────────────────────────────────────────────
cli_writeln("\n=== Done! ===");
cli_writeln("  Activity : " . VIDEO_NAME);
cli_writeln("  ivid     : {$ivid}");
cli_writeln("  cmid     : {$cmid}");
cli_writeln("  Course   : https://cursos.dankarh.com.br/course/view.php?id=" . COURSE_ID);
cli_writeln("\nNext steps:");
cli_writeln("  1. php -d max_input_vars=5000 admin/cli/purge_caches.php");
cli_writeln("  2. Open course on desktop — confirm activity appears with video");
cli_writeln("  3. Test in Moodle app — video should open inside the app (no browser redirect)");
cli_writeln("  4. Watch past 80% — green tick should appear automatically");
cli_writeln("\nIf thumbnail shows generic placeholder:");
cli_writeln("  Open the activity in Moodle admin, save it once via UI — triggers thumbnail fetch");
