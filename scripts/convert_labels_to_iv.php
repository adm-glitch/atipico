<?php
/**
 * Convert video-marker labels to mod_interactivevideo (Bunny Stream)
 * Run from Moodle root: php convert_labels_to_iv.php
 *
 * video-m2a.1 label  → IV: EP1-006 (Parte 1) + reuse existing EP2-005 cm (Parte 2)
 * video-m2a.2 label  → IV: EP3-011
 */
define('CLI_SCRIPT', true);
require('/home/user/htdocs/srv1526987.hstgr.cloud/config.php');
require_once($CFG->dirroot . '/course/lib.php');

$CID     = 5;
$BUNNY   = '661783';
$IV_MOD  = $DB->get_field('modules', 'id', ['name' => 'interactivevideo'], MUST_EXIST);
$LBL_MOD = $DB->get_field('modules', 'id', ['name' => 'label'],            MUST_EXIST);

echo "IV module id={$IV_MOD}, label module id={$LBL_MOD}\n\n";

// Use the manually-created record (id=1) as a field template so defaults match
$tmpl = $DB->get_record('interactivevideo', ['id' => 1]);
if (!$tmpl) {
    die("Template record (id=1) not found — run from Moodle root after adding one IV manually.\n");
}

// -----------------------------------------------------------------------
// Helper: insert one interactivevideo record + course_module + context
// -----------------------------------------------------------------------
function create_iv_cm($DB, $CID, $IV_MOD, $section_id, $name, $videourl, $tmpl) {
    $ivid = $DB->insert_record('interactivevideo', (object)[
        'course'               => $CID,
        'name'                 => $name,
        'timecreated'          => time(),
        'timemodified'         => time(),
        'intro'                => $tmpl->intro       ?? '',
        'introformat'          => $tmpl->introformat ?? 1,
        'source'               => 'url',
        'videourl'             => $videourl,
        'type'                 => 'bunnystream',
        'video'                => $tmpl->video       ?? '',
        'endscreentext'        => $tmpl->endscreentext        ?? '',
        'displayasstartscreen' => $tmpl->displayasstartscreen ?? 0,
        'starttime'            => 0,
        'endtime'              => 0,
        'completionpercentage' => 0,
        'grade'                => $tmpl->grade          ?? 0,
        'displayoptions'       => $tmpl->displayoptions ?? '',
        'posterimage'          => '',
        'extendedcompletion'   => $tmpl->extendedcompletion ?? 0,
    ]);

    $cmid = $DB->insert_record('course_modules', (object)[
        'course'             => $CID,
        'module'             => $IV_MOD,
        'instance'           => $ivid,
        'section'            => $section_id,
        'added'              => time(),
        'visible'            => 1,
        'visibleold'         => 1,
        'groupmode'          => 0,
        'groupingid'         => 0,
        'completion'         => 0,
        'completionview'     => 0,
        'completionexpected' => 0,
        'score'              => 0,
        'indent'             => 0,
        'showdescription'    => 0,
        'deletioninprogress' => 0,
        'downloadcontent'    => 1,
    ]);

    context_module::instance($cmid); // creates context record
    echo "  + Created IV '{$name}' → ivid={$ivid}, cmid={$cmid}\n";
    return $cmid;
}

// -----------------------------------------------------------------------
// Helper: find label cm by intro marker
// -----------------------------------------------------------------------
function find_label($DB, $CID, $LBL_MOD, $marker) {
    return $DB->get_record_sql(
        "SELECT cm.id AS cmid, cm.section, l.id AS labelid
         FROM {course_modules} cm
         JOIN {label} l ON l.id = cm.instance AND cm.module = :mid
         WHERE cm.course = :cid AND l.intro LIKE :m",
        ['mid' => $LBL_MOD, 'cid' => $CID, 'm' => '%' . $marker . '%']
    );
}

// -----------------------------------------------------------------------
// Replace a label with one or more IV cms in the section sequence
// -----------------------------------------------------------------------
function splice_sequence($DB, $section_id, $old_cmid, $new_cmids) {
    $section = $DB->get_record('course_sections', ['id' => $section_id], '*', MUST_EXIST);
    $seq     = array_values(array_filter(explode(',', $section->sequence ?? '')));
    $pos     = array_search((string)$old_cmid, $seq);
    if ($pos !== false) {
        array_splice($seq, $pos, 1, array_map('strval', $new_cmids));
    } else {
        $seq = array_merge($seq, array_map('strval', $new_cmids));
    }
    $DB->set_field('course_sections', 'sequence', implode(',', $seq), ['id' => $section_id]);
}

// -----------------------------------------------------------------------
// 1.  video-m2a.1  →  EP1-006 (Parte 1)  +  EP2-005 (Parte 2, already exists)
// -----------------------------------------------------------------------
echo "--- video-m2a.1 ---\n";
$lbl1 = find_label($DB, $CID, $LBL_MOD, 'video-m2a.1');
if ($lbl1) {
    $ep2005_cm = $DB->get_record('course_modules', ['module' => $IV_MOD, 'instance' => 1]);

    $ep1006_cmid = create_iv_cm(
        $DB, $CID, $IV_MOD, $lbl1->section,
        'M2A.1 — Fundamentos dos Direitos Humanos (Parte 1)',
        "https://player.mediadelivery.net/play/{$BUNNY}/7d5f2f26-9423-447a-bd4f-30ba6269823e",
        $tmpl
    );

    $new_cmids = [$ep1006_cmid];

    if ($ep2005_cm) {
        // Ensure it lives in this section
        $DB->set_field('course_modules', 'section', $lbl1->section, ['id' => $ep2005_cm->id]);
        $new_cmids[] = (int) $ep2005_cm->id;
        echo "  ~ Moved EP2-005 cm (id={$ep2005_cm->id}) to section {$lbl1->section}\n";
        // Remove from any other section sequence
        foreach ($DB->get_records('course_sections', ['course' => $CID]) as $sec) {
            if ($sec->id == $lbl1->section) continue;
            $seq = array_values(array_filter(explode(',', $sec->sequence ?? '')));
            $i   = array_search((string)$ep2005_cm->id, $seq);
            if ($i !== false) {
                array_splice($seq, $i, 1);
                $DB->set_field('course_sections', 'sequence', implode(',', $seq), ['id' => $sec->id]);
            }
        }
    } else {
        $ep2005_cmid = create_iv_cm(
            $DB, $CID, $IV_MOD, $lbl1->section,
            'M2A.1 — Fundamentos dos Direitos Humanos (Parte 2)',
            "https://player.mediadelivery.net/play/{$BUNNY}/01ae5ade-e109-445f-b86d-5dc0f6a4de9d",
            $tmpl
        );
        $new_cmids[] = $ep2005_cmid;
    }

    splice_sequence($DB, $lbl1->section, $lbl1->cmid, $new_cmids);
    $DB->delete_records('course_modules', ['id' => $lbl1->cmid]);
    $DB->delete_records('label',          ['id' => $lbl1->labelid]);
    echo "  ✓ Label replaced, sequence updated\n\n";
} else {
    echo "  ⚠ Label not found — skipping\n\n";
}

// -----------------------------------------------------------------------
// 2.  video-m2a.2  →  EP3-011
// -----------------------------------------------------------------------
echo "--- video-m2a.2 ---\n";
$lbl2 = find_label($DB, $CID, $LBL_MOD, 'video-m2a.2');
if ($lbl2) {
    $ep3011_cmid = create_iv_cm(
        $DB, $CID, $IV_MOD, $lbl2->section,
        'M2A.2 — Perspectiva de Gênero no Poder Judiciário',
        "https://player.mediadelivery.net/play/{$BUNNY}/f2716a84-2de8-41e6-83c4-e90e409b75c1",
        $tmpl
    );
    splice_sequence($DB, $lbl2->section, $lbl2->cmid, [$ep3011_cmid]);
    $DB->delete_records('course_modules', ['id' => $lbl2->cmid]);
    $DB->delete_records('label',          ['id' => $lbl2->labelid]);
    echo "  ✓ Label replaced, sequence updated\n\n";
} else {
    echo "  ⚠ Label not found — skipping\n\n";
}

// -----------------------------------------------------------------------
// Rebuild cache
// -----------------------------------------------------------------------
rebuild_course_cache($CID, true);
echo "=== Done! Check: https://cursos.dankarh.com.br/course/view.php?id=5 ===\n";
