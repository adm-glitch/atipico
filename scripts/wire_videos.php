<?php
/**
 * CODES TRE-PA 2026 — Wire Bunny Stream videos into placeholder labels
 * Replaces video-placeholder labels (by data-video-marker) with real iframes.
 * Run from Moodle root: php wire_videos.php
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');

echo "=== CODES TRE-PA 2026 — Wire Bunny Stream Videos ===\n\n";

$LABEL_MOD_ID = $DB->get_field('modules', 'id', ['name' => 'label'], MUST_EXIST);
$BUNNY_LIB_ID = '661783';

// ---------------------------------------------------------------------------
// Map: marker => list of Bunny video GUIDs to embed (in order)
// ---------------------------------------------------------------------------
$VIDEO_MAP = [
    // M2A.1 — two test videos (EP 1-006 + EP 2-005, both ready/processing)
    'video-m2a.1' => [
        '7d5f2f26-9423-447a-bd4f-30ba6269823e',  // EP 1-006  37m32s
        '01ae5ade-e109-445f-b86d-5dc0f6a4de9d',  // EP 2-005  (processing)
    ],
    // M2A.2 — EP 3-011
    'video-m2a.2' => [
        'f2716a84-2de8-41e6-83c4-e90e409b75c1',  // EP 3-011  45m29s
    ],
];

// ---------------------------------------------------------------------------
// Helper: build embed HTML for one or more GUIDs
// ---------------------------------------------------------------------------
function bunny_embed_html($lib_id, $guids, $marker, $title) {
    $html = '<div data-video-marker="' . htmlspecialchars($marker, ENT_QUOTES) . '">';

    foreach ($guids as $i => $guid) {
        $embed_url = "https://iframe.mediadelivery.net/embed/{$lib_id}/{$guid}"
                   . "?autoplay=false&loop=false&muted=false&preload=true&responsive=true";

        $label = count($guids) > 1 ? ' (Parte ' . ($i + 1) . ')' : '';

        $html .= '<div style="margin:12px 0;">'
               . '<p style="margin:0 0 6px;font-weight:600">🎬 ' . htmlspecialchars($title . $label, ENT_QUOTES) . '</p>'
               . '<div style="position:relative;padding-top:56.25%;">'
               . '<iframe src="' . $embed_url . '" '
               . 'loading="lazy" '
               . 'style="border:none;position:absolute;top:0;left:0;width:100%;height:100%;" '
               . 'allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;" '
               . 'allowfullscreen></iframe>'
               . '</div>'
               . '</div>';
    }

    $html .= '</div>';
    return $html;
}

// ---------------------------------------------------------------------------
// Helper: find label cm by marker in a given course
// ---------------------------------------------------------------------------
function find_label_by_marker($courseid, $marker, $label_mod_id) {
    global $DB;
    return $DB->get_record_sql(
        "SELECT cm.id as cmid, l.id as labelid, l.intro
         FROM {course_modules} cm
         JOIN {label} l ON l.id = cm.instance AND cm.module = :mid
         WHERE cm.course = :cid AND l.intro LIKE :marker",
        ['mid' => $label_mod_id, 'cid' => $courseid,
         'marker' => '%' . $marker . '%']
    );
}

// ---------------------------------------------------------------------------
// Titles for each marker (from the pedagogical doc)
// ---------------------------------------------------------------------------
$TITLES = [
    'video-m2a.1' => 'Módulo M2A.1 — Fundamentos dos Direitos Humanos',
    'video-m2a.2' => 'Módulo M2A.2 — Perspectiva de Gênero no Poder Judiciário',
];

// ---------------------------------------------------------------------------
// Wire each mapping
// ---------------------------------------------------------------------------
$cid_2a = $DB->get_field('course', 'id', ['shortname' => 'CODES-EIXO2A-2026'], MUST_EXIST);

foreach ($VIDEO_MAP as $marker => $guids) {
    $title = $TITLES[$marker] ?? $marker;
    $record = find_label_by_marker($cid_2a, $marker, $LABEL_MOD_ID);

    if (!$record) {
        echo "  ⚠ Placeholder not found for marker: {$marker} — skipping\n";
        continue;
    }

    $new_intro = bunny_embed_html($BUNNY_LIB_ID, $guids, $marker, $title);

    $DB->update_record('label', (object)[
        'id'           => $record->labelid,
        'intro'        => $new_intro,
        'introformat'  => FORMAT_HTML,
        'timemodified' => time(),
    ]);

    echo "  ✓ {$marker}: " . count($guids) . " vídeo(s) embeddado(s)\n";
    foreach ($guids as $guid) {
        echo "      → https://iframe.mediadelivery.net/embed/{$BUNNY_LIB_ID}/{$guid}\n";
    }
}

rebuild_course_cache($cid_2a, true);
echo "\n✓ Cache do curso EIXO 2A purgado\n";
echo "\n=== Concluído! Verifique em: https://cursos.dankarh.com.br/course/view.php?id=5 ===\n";
