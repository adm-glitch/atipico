<?php
/**
 * create_test_video_activity.php
 *
 * Creates one mod_url test activity in course Polo Marabá (id=10)
 * pointing to theme/atipico/video.php (the Bunny Stream player page).
 *
 * Run from Moodle root via Termius:
 *   cd /home/user/htdocs/srv1526987.hstgr.cloud
 *   php -d max_input_vars=5000 theme/atipico/scripts/create_test_video_activity.php
 *
 * Output: prints the activity cmid and the full test URL.
 */

define('CLI_SCRIPT', true);

// Resolve Moodle root from this script's location: scripts/ → theme/atipico/ → theme/ → moodle_root
$moodle_root = dirname(dirname(dirname(__DIR__)));
require($moodle_root . '/config.php');
require_once($CFG->libdir  . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

cli_writeln("=== DanKa — Create Test Video Activity ===\n");

// ── Config ────────────────────────────────────────────────────────────────────
const COURSE_ID  = 10;      // Polo Marabá
const SECTION_NO = 1;       // Topics section 1 (first real section)
const GUID       = '01ae5ade-e109-445f-b86d-5dc0f6a4de9d';  // EP 2-005 (21 min)
const DURATION   = 1260;    // seconds  (21 × 60)
const ACTIVITY_NAME = 'EP 2-005 — Fundamentos dos Direitos Humanos (Parte 2)';

// ── Sanity checks ─────────────────────────────────────────────────────────────
$course = $DB->get_record('course', ['id' => COURSE_ID], '*', MUST_EXIST);
cli_writeln("[OK] Course found: {$course->fullname} (id={$course->id})");

$url_modid = $DB->get_field('modules', 'id', ['name' => 'url'], MUST_EXIST);
cli_writeln("[OK] mod_url id: {$url_modid}");

// Find the section
$section = $DB->get_record('course_sections', [
    'course'  => COURSE_ID,
    'section' => SECTION_NO,
]);
if (!$section) {
    // Create section if missing
    $section = new stdClass();
    $section->course     = COURSE_ID;
    $section->section    = SECTION_NO;
    $section->name       = '';
    $section->summary    = '';
    $section->summaryformat = FORMAT_HTML;
    $section->sequence   = '';
    $section->visible    = 1;
    $section->availability = null;
    $section->timemodified = time();
    $section->id = $DB->insert_record('course_sections', $section);
    cli_writeln("[OK] Created section {$section->section} (id={$section->id})");
} else {
    cli_writeln("[OK] Section found: section={$section->section} id={$section->id}");
}

// ── Check if activity already exists (avoid duplicates) ────────────────────────
$existing_url = $DB->get_record_select(
    'url',
    "course = ? AND " . $DB->sql_like('externalurl', '?'),
    [COURSE_ID, '%guid=' . GUID . '%']
);
if ($existing_url) {
    $existing_cm = $DB->get_record('course_modules', [
        'module'   => $url_modid,
        'instance' => $existing_url->id,
        'course'   => COURSE_ID,
    ]);
    if ($existing_cm) {
        $final_url = $CFG->wwwroot . '/theme/atipico/video.php'
                   . '?cmid=' . $existing_cm->id
                   . '&guid=' . GUID
                   . '&dur='  . DURATION;
        cli_writeln("[SKIP] Activity already exists:");
        cli_writeln("  cmid : {$existing_cm->id}");
        cli_writeln("  URL  : {$final_url}");
        exit(0);
    }
}

// ── Step 1: Insert mod_url record (placeholder cmid=0 in URL) ─────────────────
$placeholder_url = $CFG->wwwroot . '/theme/atipico/video.php'
                 . '?cmid=0'
                 . '&guid=' . GUID
                 . '&dur='  . DURATION;

$url_record                  = new stdClass();
$url_record->course          = COURSE_ID;
$url_record->name            = ACTIVITY_NAME;
$url_record->intro           = '';
$url_record->introformat     = FORMAT_HTML;
$url_record->externalurl     = $placeholder_url;
$url_record->display         = 6;   // RESOURCELIB_DISPLAY_OPEN — opens in same window
$url_record->displayoptions  = serialize(['printintro' => 0]);
$url_record->parameters      = '';
$url_record->timemodified    = time();
$urlid = $DB->insert_record('url', $url_record);
cli_writeln("[OK] mod_url record inserted (id={$urlid})");

// ── Step 2: Insert course_modules record ──────────────────────────────────────
$cm                      = new stdClass();
$cm->course              = COURSE_ID;
$cm->module              = $url_modid;
$cm->instance            = $urlid;
$cm->section             = $section->id;
$cm->added               = time();
$cm->visible             = 1;
$cm->visibleold          = 1;
$cm->groupmode           = 0;
$cm->groupingid          = 0;
$cm->completion          = COMPLETION_TRACKING_MANUAL; // 1 — JS will mark done
$cm->completionview      = 0;
$cm->completionexpected  = 0;
$cm->score               = 0;
$cm->indent              = 0;
$cm->showdescription     = 0;
$cm->deletioninprogress  = 0;
$cm->downloadcontent     = 1;
$cm->availability        = null;
$cmid = $DB->insert_record('course_modules', $cm);
cli_writeln("[OK] course_modules record inserted (cmid={$cmid})");

// ── Step 3: Create module context ─────────────────────────────────────────────
context_module::instance($cmid);
cli_writeln("[OK] Context created for cmid={$cmid}");

// ── Step 4: Patch URL to include real cmid ────────────────────────────────────
$final_url = $CFG->wwwroot . '/theme/atipico/video.php'
           . '?cmid=' . $cmid
           . '&guid=' . GUID
           . '&dur='  . DURATION;

$DB->set_field('url', 'externalurl', $final_url, ['id' => $urlid]);
cli_writeln("[OK] URL updated with real cmid");

// ── Step 5: Add cmid to section sequence ─────────────────────────────────────
$seq = array_values(array_filter(explode(',', $section->sequence ?? '')));
$seq[] = (string)$cmid;
$DB->set_field('course_sections', 'sequence', implode(',', $seq), ['id' => $section->id]);
cli_writeln("[OK] Added to section sequence");

// ── Step 6: Rebuild course cache ──────────────────────────────────────────────
rebuild_course_cache(COURSE_ID, true);
cli_writeln("[OK] Course cache rebuilt");

// ── Summary ───────────────────────────────────────────────────────────────────
cli_writeln("\n=== Done! ===");
cli_writeln("  Activity : " . ACTIVITY_NAME);
cli_writeln("  cmid     : {$cmid}");
cli_writeln("  URL      : {$final_url}");
cli_writeln("\nTest on desktop:");
cli_writeln("  {$final_url}");
cli_writeln("\nTest on mobile app:");
cli_writeln("  Open Polo Marabá course → section 1 → tap the activity");
cli_writeln("  (Course is still hidden — admin/enrolled users can access directly)");
cli_writeln("\nNext: php -d max_input_vars=5000 admin/cli/purge_caches.php");
