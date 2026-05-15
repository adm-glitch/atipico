<?php
/**
 * CODES TRE-PA 2026 — Course Completion Setup
 * Creates assessment placeholders + wires completion criteria for each Eixo.
 * Run from Moodle root: php setup_completion.php
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir  . '/completionlib.php');
require_once($CFG->libdir  . '/gradelib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');

echo "=== CODES TRE-PA 2026 — Completion Setup ===\n\n";

// Resolve module IDs once
$MOD_ID = [
    'quiz'   => $DB->get_field('modules', 'id', ['name' => 'quiz'],   MUST_EXIST),
    'forum'  => $DB->get_field('modules', 'id', ['name' => 'forum'],  MUST_EXIST),
    'assign' => $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST),
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mk_quiz($courseid, $section, $name, $gradepass = 6.0) {
    global $CFG, $MOD_ID;
    $m = new stdClass();
    $m->modulename       = 'quiz';
    $m->module           = $MOD_ID['quiz'];
    $m->course           = $courseid;
    $m->section          = $section;
    $m->visible          = 1;
    $m->name             = $name;
    $m->intro            = '<p><em>As questões serão adicionadas pelo professor antes da publicação.</em></p>';
    $m->introformat      = FORMAT_HTML;
    $m->quizpassword     = '';
    $m->subnet           = '';
    $m->browsersecurity  = '-';
    $m->delay1 = $m->delay2 = 0;
    $m->timeopen = $m->timeclose = $m->timelimit = 0;
    $m->overduehandling  = 'autosubmit';
    $m->graceperiod      = 0;
    $m->attempts         = 0; // unlimited
    $m->grademethod      = 1; // highest grade
    $m->grade            = 10;
    $m->gradepass        = $gradepass;
    $m->decimalpoints    = 2;
    $m->questiondecimalpoints = -1;
    $m->questionsperpage = 1;
    $m->navmethod        = 'free';
    $m->shuffleanswers   = 1;
    $m->preferredbehaviour = 'deferredfeedback';
    $m->showuserpicture  = 0;
    $m->showblocks       = 0;
    $m->canredoquestions = 0;
    // Review flags
    $m->reviewattempt           = 69904;
    $m->reviewcorrectness       = 4368;
    $m->reviewmarks             = 4368;
    $m->reviewspecificfeedback  = 4368;
    $m->reviewgeneralfeedback   = 4368;
    $m->reviewrightanswer       = 4368;
    $m->reviewoverallfeedback   = 4368;
    // Completion
    $m->completion                = 2; // automatic
    $m->completionview            = 0;
    $m->completionexpected        = 0;
    $m->completionusegrade        = 1;
    $m->completionpassgrade       = 1;
    $m->completiongradeitemnumber = 0;
    $m->completionattemptsexhausted = 0;
    $m->completionminattempts     = 0;
    // Other required defaults
    $m->groupmode    = 0;
    $m->groupingid   = 0;
    $m->showdescription = 0;
    $m->visibleoncoursepage = 1;

    $result = add_moduleinfo($m, get_course($courseid), null);
    return $result->coursemodule;
}

function mk_forum($courseid, $section, $name, $intro = '') {
    global $MOD_ID;
    $m = new stdClass();
    $m->modulename       = 'forum';
    $m->module           = $MOD_ID['forum'];
    $m->course           = $courseid;
    $m->section          = $section;
    $m->visible          = 1;
    $m->name             = $name;
    $m->intro            = $intro ?: '<p><em>Compartilhe suas reflexões sobre o conteúdo do módulo.</em></p>';
    $m->introformat      = FORMAT_HTML;
    $m->type             = 'general';
    $m->assessed         = 0;
    $m->scale            = 0;
    $m->maxbytes         = 512000;
    $m->maxattachments   = 5;
    $m->forcesubscribe   = 0;
    $m->trackingtype     = 1;
    $m->rsstype = $m->rssarticles = 0;
    $m->timeopen = $m->timeclose = 0;
    $m->blockafter = $m->blockperiod = $m->warnafter = 0;
    $m->completiondiscussions = 0;
    $m->completionreplies     = 0;
    $m->completionposts       = 1;
    $m->completion            = 2;
    $m->completionview        = 0;
    $m->completionexpected    = 0;
    $m->completiongradeitemnumber = null;
    $m->groupmode    = 0;
    $m->groupingid   = 0;
    $m->showdescription = 0;
    $m->visibleoncoursepage = 1;

    $result = add_moduleinfo($m, get_course($courseid), null);
    return $result->coursemodule;
}

function mk_assign($courseid, $section, $name, $intro = '') {
    global $CFG, $MOD_ID;
    $m = new stdClass();
    $m->modulename       = 'assign';
    $m->module           = $MOD_ID['assign'];
    $m->course           = $courseid;
    $m->section          = $section;
    $m->visible          = 1;
    $m->name             = $name;
    $m->intro            = $intro ?: '<p><em>As instruções desta atividade serão adicionadas pelo professor.</em></p>';
    $m->introformat      = FORMAT_HTML;
    $m->alwaysshowdescription  = 1;
    $m->submissiondrafts       = 0;
    $m->sendnotifications      = 0;
    $m->sendlatenotifications  = 0;
    $m->sendstudentnotifications = 1;
    $m->duedate                = 0;
    $m->allowsubmissionsfromdate = 0;
    $m->cutoffdate             = 0;
    $m->gradingduedate         = 0;
    $m->grade                  = 100;
    $m->gradepass              = 0;
    $m->teamsubmission         = 0;
    $m->requireallteammemberssubmit = 0;
    $m->teamsubmissiongroupingid = 0;
    $m->blindmarking           = 0;
    $m->hidegrader             = 0;
    $m->attemptreopenmethod    = 'none';
    $m->maxattempts            = -1;
    $m->markingworkflow        = 0;
    $m->markingallocation      = 0;
    $m->requiresubmissionstatement = 0;
    $m->preventsubmissionnotingroup = 0;
    $m->timelimit              = 0;
    $m->submissionattachments  = 0;
    $m->nosubmissions          = 0;
    $m->completionsubmit       = 1;
    $m->completion             = 2;
    $m->completionview         = 0;
    $m->completionexpected     = 0;
    $m->completiongradeitemnumber = null;
    $m->assignsubmission_onlinetext_enabled   = 1;
    $m->assignsubmission_onlinetext_wordlimit = 0;
    $m->assignsubmission_file_enabled         = 1;
    $m->assignsubmission_file_maxfiles        = 3;
    $m->assignsubmission_file_maxsizebytes    = 10485760;
    $m->assignfeedback_comments_enabled       = 1;
    $m->assignfeedback_editpdf_enabled        = 0;
    $m->assignfeedback_file_enabled           = 0;
    $m->groupmode    = 0;
    $m->groupingid   = 0;
    $m->showdescription = 0;
    $m->visibleoncoursepage = 1;

    $result = add_moduleinfo($m, get_course($courseid), null);
    return $result->coursemodule;
}

/**
 * Set course completion to require ALL listed cmids (activity type).
 */
function set_course_completion($courseid, array $cmids) {
    global $DB;

    $DB->set_field('course', 'enablecompletion', 1, ['id' => $courseid]);

    // Aggregation for activity criteria: ALL
    if (!$DB->record_exists('course_completion_aggr_methd', ['course' => $courseid, 'criteriatype' => 1])) {
        $DB->insert_record('course_completion_aggr_methd', (object)[
            'course'       => $courseid,
            'criteriatype' => 1,
            'method'       => 1, // ALL
        ]);
    } else {
        $DB->set_field('course_completion_aggr_methd', 'method', 1,
            ['course' => $courseid, 'criteriatype' => 1]);
    }

    $done = $DB->get_fieldset_select(
        'course_completion_criteria', 'moduleinstance',
        'course = ? AND criteriatype = 1', [$courseid]
    );

    foreach ($cmids as $cmid) {
        if (in_array($cmid, $done)) continue;
        $cm = get_coursemodule_from_id('', $cmid, $courseid, false, MUST_EXIST);
        $DB->insert_record('course_completion_criteria', (object)[
            'course'         => $courseid,
            'criteriatype'   => 1,
            'module'         => $cm->modname,
            'moduleinstance' => $cmid,
            'courseinstance' => 0,
            'enrolperiod'    => 0,
            'timeend'        => 0,
            'gradepass'      => 0,
            'role'           => 0,
        ]);
    }

    echo "  ✓ Critérios de conclusão configurados (course id:{$courseid})\n";
}

function mod_exists($courseid, $modname, $actname) {
    global $DB;
    $modid = $DB->get_field('modules', 'id', ['name' => $modname]);
    return $DB->record_exists_sql(
        "SELECT cm.id FROM {course_modules} cm
         JOIN {{$modname}} m ON m.id = cm.instance
         WHERE cm.course = ? AND cm.module = ? AND m.name = ?",
        [$courseid, $modid, $actname]
    );
}

function get_cmid($courseid, $modname, $actname) {
    global $DB;
    $modid = $DB->get_field('modules', 'id', ['name' => $modname]);
    return $DB->get_field_sql(
        "SELECT cm.id FROM {course_modules} cm
         JOIN {{$modname}} m ON m.id = cm.instance
         WHERE cm.course = ? AND cm.module = ? AND m.name = ?",
        [$courseid, $modid, $actname]
    );
}

// ---------------------------------------------------------------------------
// Course IDs
// ---------------------------------------------------------------------------
$cids = [];
foreach (['CODES-EIXO1-2026','CODES-EIXO2A-2026','CODES-EIXO2B-2026','CODES-EIXO2C-2026','CODES-EIXO3-2026'] as $sn) {
    $cids[$sn] = $DB->get_field('course', 'id', ['shortname' => $sn], MUST_EXIST);
}
$chub = $DB->get_field('course', 'id', ['shortname' => 'CODES-TRE-2026'], MUST_EXIST);

// ---------------------------------------------------------------------------
// EIXO 1 — Presencial: self-completion (student marks themselves done)
// ---------------------------------------------------------------------------
echo "--- Eixo 1 ---\n";
$c1 = $cids['CODES-EIXO1-2026'];
$DB->set_field('course', 'enablecompletion', 1, ['id' => $c1]);
if (!$DB->record_exists('course_completion_aggr_methd', ['course' => $c1, 'criteriatype' => 3])) {
    $DB->insert_record('course_completion_aggr_methd', (object)[
        'course' => $c1, 'criteriatype' => 3, 'method' => 0,
    ]);
}
if (!$DB->record_exists('course_completion_criteria', ['course' => $c1, 'criteriatype' => 3])) {
    $DB->insert_record('course_completion_criteria', (object)[
        'course' => $c1, 'criteriatype' => 3,
        'module' => '', 'moduleinstance' => 0, 'courseinstance' => 0,
        'enrolperiod' => 0, 'timeend' => 0, 'gradepass' => 0, 'role' => 0,
    ]);
    echo "  ✓ Eixo 1: conclusão por auto-marcação (presencial — sem quiz AVA)\n";
} else {
    echo "  ✓ Eixo 1: critério já existe\n";
}

// ---------------------------------------------------------------------------
// EIXO 2A — Quiz (60%) + Fórum (1 post)  — section num 5
// ---------------------------------------------------------------------------
echo "\n--- Eixo 2A ---\n";
$c2a = $cids['CODES-EIXO2A-2026'];
$cmids_2a = [];

$n = 'Avaliação Final — Eixo 2A (10 questões)';
if (mod_exists($c2a, 'quiz', $n)) { $cmids_2a[] = get_cmid($c2a, 'quiz', $n); echo "  ✓ Quiz já existe\n"; }
else { $cmids_2a[] = mk_quiz($c2a, 5, $n, 6.0); echo "  ✓ Quiz criado\n"; }

$n = 'Fórum de Discussão — Eixo 2A';
if (mod_exists($c2a, 'forum', $n)) { $cmids_2a[] = get_cmid($c2a, 'forum', $n); echo "  ✓ Fórum já existe\n"; }
else { $cmids_2a[] = mk_forum($c2a, 5, $n); echo "  ✓ Fórum criado\n"; }

set_course_completion($c2a, $cmids_2a);

// ---------------------------------------------------------------------------
// EIXO 2B — Quiz (60%) + Tarefa reflexiva  — section num 4
// ---------------------------------------------------------------------------
echo "\n--- Eixo 2B ---\n";
$c2b = $cids['CODES-EIXO2B-2026'];
$cmids_2b = [];

$n = 'Avaliação Final — Eixo 2B (10 questões)';
if (mod_exists($c2b, 'quiz', $n)) { $cmids_2b[] = get_cmid($c2b, 'quiz', $n); echo "  ✓ Quiz já existe\n"; }
else { $cmids_2b[] = mk_quiz($c2b, 4, $n, 6.0); echo "  ✓ Quiz criado\n"; }

$n = 'Atividade Reflexiva — Eixo 2B';
$intro_2b = '<p>Descreva uma situação hipotética ou real de assédio ou discriminação no ambiente de trabalho e explique como você aplicaria o protocolo de acolhimento estudado neste eixo.</p>';
if (mod_exists($c2b, 'assign', $n)) { $cmids_2b[] = get_cmid($c2b, 'assign', $n); echo "  ✓ Tarefa já existe\n"; }
else { $cmids_2b[] = mk_assign($c2b, 4, $n, $intro_2b); echo "  ✓ Tarefa criada\n"; }

set_course_completion($c2b, $cmids_2b);

// ---------------------------------------------------------------------------
// EIXO 2C — Quiz (60%) + Checklist  — section num 4
// ---------------------------------------------------------------------------
echo "\n--- Eixo 2C ---\n";
$c2c = $cids['CODES-EIXO2C-2026'];
$cmids_2c = [];

$n = 'Avaliação Final — Eixo 2C (10 questões)';
if (mod_exists($c2c, 'quiz', $n)) { $cmids_2c[] = get_cmid($c2c, 'quiz', $n); echo "  ✓ Quiz já existe\n"; }
else { $cmids_2c[] = mk_quiz($c2c, 4, $n, 6.0); echo "  ✓ Quiz criado\n"; }

$n = 'Checklist de Acessibilidade da Zona Eleitoral';
$intro_2c = '<p>Preencha o checklist de acessibilidade da sua Zona Eleitoral e envie o documento preenchido. Identifique pelo menos 3 barreiras existentes e proponha soluções concretas para cada uma.</p>';
if (mod_exists($c2c, 'assign', $n)) { $cmids_2c[] = get_cmid($c2c, 'assign', $n); echo "  ✓ Checklist já existe\n"; }
else { $cmids_2c[] = mk_assign($c2c, 4, $n, $intro_2c); echo "  ✓ Checklist criado\n"; }

set_course_completion($c2c, $cmids_2c);

// ---------------------------------------------------------------------------
// EIXO 3 — Quiz (60%) + Fórum + 2 Tarefas  — section num 5
// ---------------------------------------------------------------------------
echo "\n--- Eixo 3 ---\n";
$c3 = $cids['CODES-EIXO3-2026'];
$cmids_3 = [];

$n = 'Avaliação Final — Eixo 3 (15 questões)';
if (mod_exists($c3, 'quiz', $n)) { $cmids_3[] = get_cmid($c3, 'quiz', $n); echo "  ✓ Quiz já existe\n"; }
else { $cmids_3[] = mk_quiz($c3, 5, $n, 6.0); echo "  ✓ Quiz criado\n"; }

$n = 'Fórum — Plano de Ação na Zona Eleitoral';
$intro_f3 = '<p>Compartilhe <strong>uma ação concreta de equidade racial</strong> que você pretende implementar na sua Zona Eleitoral nos próximos 3 meses. Seja específico: qual ação, quando, como.</p>';
if (mod_exists($c3, 'forum', $n)) { $cmids_3[] = get_cmid($c3, 'forum', $n); echo "  ✓ Fórum já existe\n"; }
else { $cmids_3[] = mk_forum($c3, 5, $n, $intro_f3); echo "  ✓ Fórum criado\n"; }

$n = 'Atividade Reflexiva — Protocolo de Perspectiva Racial em Caso Concreto';
$intro_a3a = '<p>Analise o caso fornecido pelo professor aplicando as 5 etapas do Protocolo de Julgamento com Perspectiva Racial do CNJ. Justifique cada etapa com base nos conceitos estudados no Eixo 3.</p>';
if (mod_exists($c3, 'assign', $n)) { $cmids_3[] = get_cmid($c3, 'assign', $n); echo "  ✓ At. reflexiva já existe\n"; }
else { $cmids_3[] = mk_assign($c3, 5, $n, $intro_a3a); echo "  ✓ At. reflexiva criada\n"; }

$n = 'Plano de Ação — Equidade Racial na Minha Zona Eleitoral';
$intro_a3b = '<p>Elabore um plano de ação de equidade racial para a sua Zona Eleitoral com pelo menos 3 ações concretas para os próximos 3 meses, alinhadas aos indicadores do IPER.</p>';
if (mod_exists($c3, 'assign', $n)) { $cmids_3[] = get_cmid($c3, 'assign', $n); echo "  ✓ Plano de ação já existe\n"; }
else { $cmids_3[] = mk_assign($c3, 5, $n, $intro_a3b); echo "  ✓ Plano de ação criado\n"; }

set_course_completion($c3, $cmids_3);

// ---------------------------------------------------------------------------
// HUB — depende de Eixo 1 + 2A + 3 (todos os polos)
// ---------------------------------------------------------------------------
echo "\n--- Hub (CODES-TRE-2026) ---\n";
$DB->set_field('course', 'enablecompletion', 1, ['id' => $chub]);
if (!$DB->record_exists('course_completion_aggr_methd', ['course' => $chub, 'criteriatype' => 8])) {
    $DB->insert_record('course_completion_aggr_methd', (object)[
        'course' => $chub, 'criteriatype' => 8, 'method' => 1,
    ]);
}
$done_deps = $DB->get_fieldset_select(
    'course_completion_criteria', 'courseinstance',
    'course = ? AND criteriatype = 8', [$chub]
);
foreach ([$cids['CODES-EIXO1-2026'], $cids['CODES-EIXO2A-2026'], $cids['CODES-EIXO3-2026']] as $eixoid) {
    if (in_array($eixoid, $done_deps)) {
        echo "  ✓ Dependência já existe: id:{$eixoid}\n";
        continue;
    }
    $sn = $DB->get_field('course', 'shortname', ['id' => $eixoid]);
    $DB->insert_record('course_completion_criteria', (object)[
        'course' => $chub, 'criteriatype' => 8,
        'module' => '', 'moduleinstance' => 0,
        'courseinstance' => $eixoid,
        'enrolperiod' => 0, 'timeend' => 0, 'gradepass' => 0, 'role' => 0,
    ]);
    echo "  ✓ Hub depende de: {$sn}\n";
}
echo "  ℹ  Eixos 2B e 2C: adicionar no hub manualmente por polo quando necessário\n";

// ---------------------------------------------------------------------------
// Purge completion cache
// ---------------------------------------------------------------------------
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
foreach (array_merge(array_values($cids), [$chub]) as $cid) {
    $info = new completion_info(get_course($cid));
    $info->clear_criteria();
    // Re-cache
    $info = new completion_info(get_course($cid));
}
echo "\n=== Concluído! ===\n";
echo "  Eixo 1  → auto-marcação (presencial)\n";
echo "  Eixo 2A → quiz 60% + fórum (1 post)\n";
echo "  Eixo 2B → quiz 60% + tarefa reflexiva\n";
echo "  Eixo 2C → quiz 60% + checklist ZE\n";
echo "  Eixo 3  → quiz 60% + fórum + at. reflexiva + plano de ação\n";
echo "  Hub     → Eixo 1 + 2A + 3 concluídos\n";
