<?php
/**
 * setup_polo_courses.php
 * Run from Moodle root on server (via Termius):
 *   cd /home/user/htdocs/srv1526987.hstgr.cloud
 *   php -d max_input_vars=5000 scripts/setup_polo_courses.php
 *
 * What this does:
 *   1. Creates 3 polo courses (Marabá, Santarém, Belém) — skips if already exist
 *   2. Locks "polo" profile field (admin-only editing)
 *   3. Creates CPF profile field (required, regex-validated, shown on signup)
 *   4. Wires cohort-sync enrollment on each course:
 *        Polo course ← its own polo cohort + Equipe Interna cohort
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');
require_once($CFG->libdir  . '/clilib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

cli_writeln("=== DanKa Polo Course Setup ===\n");

// ─────────────────────────────────────────────────────────────────────────────
// 1. CREATE THE THREE POLO COURSES
// ─────────────────────────────────────────────────────────────────────────────
$polo_courses = [
    [
        'idnumber'  => 'curso_polo_maraba',
        'shortname' => 'polo-maraba-2026',
        'fullname'  => 'Polo Marabá — TRE-PA 2026',
        'summary'   => '<p>Formação Polo Marabá — Competências Comportamentais e Direitos Fundamentais na Atuação da Justiça Eleitoral.</p>',
    ],
    [
        'idnumber'  => 'curso_polo_santarem',
        'shortname' => 'polo-santarem-2026',
        'fullname'  => 'Polo Santarém — TRE-PA 2026',
        'summary'   => '<p>Formação Polo Santarém — Competências Comportamentais e Direitos Fundamentais na Atuação da Justiça Eleitoral.</p>',
    ],
    [
        'idnumber'  => 'curso_polo_belem',
        'shortname' => 'polo-belem-2026',
        'fullname'  => 'Polo Belém — TRE-PA 2026',
        'summary'   => '<p>Formação Polo Belém — Competências Comportamentais e Direitos Fundamentais na Atuação da Justiça Eleitoral.</p>',
    ],
];

// Target category: same as existing courses (id=2)
$catid = 2;

$created_course_ids = [];

foreach ($polo_courses as $def) {
    $existing = $DB->get_record('course', ['idnumber' => $def['idnumber']]);
    if ($existing) {
        cli_writeln("[SKIP] Course '{$def['fullname']}' already exists (id={$existing->id}).");
        $created_course_ids[$def['idnumber']] = $existing->id;
        continue;
    }

    $course = new stdClass();
    $course->category          = $catid;
    $course->fullname          = $def['fullname'];
    $course->shortname         = $def['shortname'];
    $course->idnumber          = $def['idnumber'];
    $course->summary           = $def['summary'];
    $course->summaryformat     = FORMAT_HTML;
    $course->format            = 'topics';
    $course->numsections       = 1;
    $course->visible           = 0;   // hidden until ready
    $course->lang              = 'pt_br';
    $course->enablecompletion  = 1;
    $course->showgrades        = 1;
    $course->startdate         = mktime(0, 0, 0, 1, 1, 2026);
    $course->enddate           = mktime(23, 59, 59, 12, 31, 2026);

    $new = create_course($course);
    $created_course_ids[$def['idnumber']] = $new->id;
    cli_writeln("[OK] Created course '{$def['fullname']}' (id={$new->id}).");
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. LOCK "polo" PROFILE FIELD
// ─────────────────────────────────────────────────────────────────────────────
$polo_fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'polo']);
if ($polo_fieldid) {
    $DB->set_field('user_info_field', 'locked', 1, ['id' => $polo_fieldid]);
    cli_writeln("[OK] 'polo' profile field locked — only admins can now edit it.");
} else {
    cli_writeln("[WARN] 'polo' profile field not found — skipping lock.");
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. CREATE CPF PROFILE FIELD
// ─────────────────────────────────────────────────────────────────────────────
$cpf_fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'cpf']);
if (!$cpf_fieldid) {
    // Get or create profile category
    $cat_name = 'Dados Pessoais';
    $catid_pf  = $DB->get_field('user_info_category', 'id', ['name' => $cat_name]);
    if (!$catid_pf) {
        $cat            = new stdClass();
        $cat->name      = $cat_name;
        $cat->sortorder = 1;
        $catid_pf = $DB->insert_record('user_info_category', $cat);
        cli_writeln("[OK] Created profile category '{$cat_name}' (id={$catid_pf}).");
    }

    $sortorder = (int)$DB->get_field_sql(
        'SELECT COALESCE(MAX(sortorder), 0) + 1 FROM {user_info_field} WHERE categoryid = ?',
        [$catid_pf]
    );

    $field                  = new stdClass();
    $field->shortname       = 'cpf';
    $field->name            = 'CPF';
    $field->datatype        = 'text';
    $field->description     = '<p>Cadastro de Pessoa Física. Formato: <strong>000.000.000-00</strong></p>';
    $field->descriptionformat = FORMAT_HTML;
    $field->categoryid      = $catid_pf;
    $field->sortorder       = $sortorder;
    $field->required        = 1;   // mandatory
    $field->locked          = 0;   // user fills it themselves
    $field->visible         = 2;   // everyone can see
    $field->forceunique     = 1;   // one CPF per account
    $field->signup          = 1;   // shown on signup page
    $field->defaultdata     = '';
    $field->defaultdataformat = 0;
    // text field params: 1=maxlength, 2=size, 3=regex, 4=link, 5=linktarget
    $field->param1          = '14';  // 000.000.000-00 = 14 chars
    $field->param2          = '14';
    $field->param3          = '';    // regex validated client-side via JS in theme
    $field->param4          = '';
    $field->param5          = '';

    $cpf_fieldid = $DB->insert_record('user_info_field', $field);
    cli_writeln("[OK] CPF profile field created (id={$cpf_fieldid}, required, shown on signup).");
} else {
    // Make sure it's required and shown on signup
    $DB->set_field('user_info_field', 'required', 1, ['id' => $cpf_fieldid]);
    $DB->set_field('user_info_field', 'signup',   1, ['id' => $cpf_fieldid]);
    cli_writeln("[OK] CPF field already exists (id={$cpf_fieldid}) — set required+signup.");
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. COHORT-SYNC ENROLLMENT ON EACH POLO COURSE
// ─────────────────────────────────────────────────────────────────────────────
$enrol_plugin = enrol_get_plugin('cohort');
if (!$enrol_plugin) {
    cli_writeln("[ERROR] Cohort enrol plugin not available — cannot wire enrollments.");
} else {
    require_once($CFG->dirroot . '/enrol/cohort/locallib.php');

    // course idnumber => [cohort idnumbers that should enroll into it]
    $map = [
        'curso_polo_maraba'   => ['polo_maraba',   'polo_interno'],
        'curso_polo_santarem' => ['polo_santarem', 'polo_interno'],
        'curso_polo_belem'    => ['polo_belem',    'polo_interno'],
    ];

    foreach ($map as $course_idnum => $cohort_idnums) {
        $course = $DB->get_record('course', ['idnumber' => $course_idnum]);
        if (!$course) {
            cli_writeln("[WARN] Course idnumber='{$course_idnum}' not found — skipping enrol.");
            continue;
        }

        foreach ($cohort_idnums as $cohort_idnum) {
            $cohort = $DB->get_record('cohort', ['idnumber' => $cohort_idnum]);
            if (!$cohort) {
                cli_writeln("[WARN] Cohort idnumber='{$cohort_idnum}' not found — skipping.");
                continue;
            }

            $existing = $DB->get_record('enrol', [
                'enrol'      => 'cohort',
                'courseid'   => $course->id,
                'customint1' => $cohort->id,
            ]);
            if ($existing) {
                cli_writeln("[SKIP] '{$course->shortname}' ← '{$cohort->name}': already set.");
                continue;
            }

            $instanceid = $enrol_plugin->add_instance($course, [
                'customint1' => $cohort->id,
                'roleid'     => 5,  // student
                'name'       => '',
            ]);

            // Sync immediately — existing cohort members get enrolled right now
            enrol_cohort_sync(new null_progress_trace(), $course->id);

            cli_writeln("[OK] '{$course->shortname}' ← '{$cohort->name}' cohort-sync added + synced (enrol id={$instanceid}).");
        }
    }
}

cli_writeln("\n=== All done. Run: php admin/cli/purge_caches.php ===");
