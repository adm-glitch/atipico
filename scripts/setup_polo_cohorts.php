<?php
/**
 * CODES TRE-PA 2026 — Polo Cohort & Auto-Enrolment Setup
 * 1. Makes polo profile field required
 * 2. Creates cohorts for each polo
 * 3. Sets up cohort-sync enrolment in each course
 * Run from Moodle root: php setup_polo_cohorts.php
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/enrol/cohort/lib.php');

echo "=== CODES TRE-PA 2026 — Polo Cohort Setup ===\n\n";

// ---------------------------------------------------------------------------
// 1. Make polo field required + visible everywhere
// ---------------------------------------------------------------------------
$polo_field = $DB->get_record('user_info_field', ['shortname' => 'polo'], '*', MUST_EXIST);
$polo_field->required  = 1;
$polo_field->visible   = 2;  // visible to all
$polo_field->signup    = 1;  // show on signup form
$DB->update_record('user_info_field', $polo_field);
echo "✓ Campo 'polo' marcado como obrigatório no cadastro\n";

// ---------------------------------------------------------------------------
// 2. Create cohorts
// ---------------------------------------------------------------------------
$context_system = \context_system::instance();
$cohort_defs = [
    'polo_maraba'   => ['name' => 'Polo Marabá',   'description' => 'Participantes do Polo Marabá — TRE/PA 2026'],
    'polo_santarem' => ['name' => 'Polo Santarém',  'description' => 'Participantes do Polo Santarém — TRE/PA 2026'],
    'polo_belem'    => ['name' => 'Polo Belém',     'description' => 'Participantes do Polo Belém — TRE/PA 2026'],
];

$cohort_ids = [];
foreach ($cohort_defs as $idnumber => $def) {
    $existing = $DB->get_record('cohort', ['idnumber' => $idnumber]);
    if ($existing) {
        $cohort_ids[$idnumber] = $existing->id;
        echo "  ✓ Coorte já existe: {$def['name']} (id:{$existing->id})\n";
    } else {
        $cohort = new stdClass();
        $cohort->contextid   = $context_system->id;
        $cohort->name        = $def['name'];
        $cohort->idnumber    = $idnumber;
        $cohort->description = $def['description'];
        $cohort->descriptionformat = FORMAT_HTML;
        $cohort->visible     = 1;
        $cohort->component   = '';
        $cohort->timecreated = $cohort->timemodified = time();
        $id = $DB->insert_record('cohort', $cohort);
        $cohort_ids[$idnumber] = $id;
        echo "  ✓ Coorte criada: {$def['name']} (id:{$id})\n";
    }
}

// ---------------------------------------------------------------------------
// 3. Cohort enrolment in each course
// ---------------------------------------------------------------------------

// course => list of cohort idnumbers to enrol
$course_cohorts = [
    'CODES-TRE-2026'   => ['polo_maraba', 'polo_santarem', 'polo_belem'],
    'CODES-EIXO1-2026' => ['polo_maraba', 'polo_santarem', 'polo_belem'],
    'CODES-EIXO2A-2026'=> ['polo_maraba', 'polo_santarem', 'polo_belem'],
    'CODES-EIXO2B-2026'=> ['polo_santarem'],        // Santarém only
    'CODES-EIXO2C-2026'=> ['polo_belem'],           // Belém only
    'CODES-EIXO3-2026' => ['polo_maraba', 'polo_santarem', 'polo_belem'],
];

$enrol_plugin = enrol_get_plugin('cohort');
$student_roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);

echo "\n--- Configurando enrolments por coorte ---\n";
foreach ($course_cohorts as $shortname => $idnumbers) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $label = "{$shortname} ← " . implode(', ', $idnumbers);

    foreach ($idnumbers as $idn) {
        $cohort_id = $cohort_ids[$idn];

        // Check if this cohort enrol instance already exists
        $existing = $DB->get_record('enrol', [
            'courseid'   => $course->id,
            'enrol'      => 'cohort',
            'customint1' => $cohort_id,
        ]);

        if ($existing) {
            // Make sure it's active
            if ($existing->status != 0) {
                $DB->set_field('enrol', 'status', 0, ['id' => $existing->id]);
                echo "  ✓ Reativado: {$shortname} ← {$idn}\n";
            } else {
                echo "  ✓ Já existe: {$shortname} ← {$idn}\n";
            }
        } else {
            $fields = [
                'status'        => 0,
                'roleid'        => $student_roleid,
                'customint1'    => $cohort_id,  // cohort id
                'customint2'    => 0,            // group id (0 = none)
                'customchar1'   => null,
                'enrolperiod'   => 0,
                'enrolstartdate'=> 0,
                'enrolenddate'  => 0,
            ];
            $enrol_plugin->add_instance($course, $fields);
            echo "  ✓ Criado: {$shortname} ← {$idn}\n";
        }
    }
}

// ---------------------------------------------------------------------------
// 4. Disable self-enrolment on 2B and 2C (access controlled by cohort)
// ---------------------------------------------------------------------------
echo "\n--- Desativando auto-inscrição manual em 2B e 2C ---\n";
foreach (['CODES-EIXO2B-2026', 'CODES-EIXO2C-2026'] as $sn) {
    $cid = $DB->get_field('course', 'id', ['shortname' => $sn], MUST_EXIST);
    $self_instances = $DB->get_records('enrol', ['courseid' => $cid, 'enrol' => 'self', 'status' => 0]);
    if ($self_instances) {
        foreach ($self_instances as $inst) {
            $DB->set_field('enrol', 'status', 1, ['id' => $inst->id]); // 1 = disabled
        }
        echo "  ✓ Auto-inscrição manual desativada em: {$sn} (acesso via polo)\n";
    } else {
        echo "  ✓ Auto-inscrição já estava inativa em: {$sn}\n";
    }
}

// ---------------------------------------------------------------------------
// 5. Sync existing users that already have polo set
// ---------------------------------------------------------------------------
echo "\n--- Sincronizando usuários existentes ---\n";
$polo_field_id = $polo_field->id;
$polo_data = $DB->get_records('user_info_data', ['fieldid' => $polo_field_id]);
$synced = 0;
require_once($CFG->dirroot . '/local/polo_enrol/classes/observer.php');
foreach ($polo_data as $d) {
    if (empty(trim($d->data))) continue;
    \local_polo_enrol\observer::sync_polo((int)$d->userid);
    $synced++;
}
echo "  ✓ {$synced} usuário(s) sincronizado(s) com coorte de polo\n";

echo "\n=== Concluído! ===\n";
echo "  3 coortes criadas/verificadas (Marabá, Santarém, Belém)\n";
echo "  Enrolments por coorte configurados em todos os cursos\n";
echo "  Novos cadastros serão automaticamente matriculados pelo polo\n";
echo "  Eixos 2B (Santarém) e 2C (Belém): auto-inscrição manual desativada\n";
