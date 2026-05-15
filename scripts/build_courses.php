<?php
/**
 * CODES TRE-PA 2026 — Course Builder Script
 * Run from Moodle root: php /path/to/scripts/build_courses.php
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');

echo "=== CODES TRE-PA 2026 — Course Builder ===\n\n";

// ---------------------------------------------------------------------------
// 1. CATEGORY
// ---------------------------------------------------------------------------
$catname = 'Formação TRE/PA 2026';
$existing_cat = $DB->get_record('course_categories', ['name' => $catname, 'parent' => 0]);
if ($existing_cat) {
    $catid = $existing_cat->id;
    echo "✓ Categoria já existe: {$catname} (id:{$catid})\n";
} else {
    $cat = new stdClass();
    $cat->name        = $catname;
    $cat->idnumber    = 'FORMAÇAO-TRE-2026';
    $cat->description = 'Competências Comportamentais e Direitos Fundamentais na Atuação da Justiça Eleitoral — TRE/PA — CODES 2026';
    $cat->parent      = 0;
    $cat->sortorder   = 999;
    $cat->visible     = 1;
    $DB->insert_record('course_categories', $cat);
    $catid = $DB->get_field('course_categories', 'id', ['name' => $catname, 'parent' => 0]);
    fix_course_sortorder();
    echo "✓ Categoria criada: {$catname} (id:{$catid})\n";
}

// ---------------------------------------------------------------------------
// 2. CUSTOM PROFILE FIELD — Polo
// ---------------------------------------------------------------------------
$fieldshort = 'polo';
$existing_field = $DB->get_record('user_info_field', ['shortname' => $fieldshort]);
if ($existing_field) {
    $fieldid = $existing_field->id;
    echo "✓ Campo de perfil já existe: polo (id:{$fieldid})\n";
} else {
    $fieldcat = $DB->get_record('user_info_category', ['name' => 'Dados do Curso']);
    if (!$fieldcat) {
        $fc = new stdClass();
        $fc->name      = 'Dados do Curso';
        $fc->sortorder = 1;
        $fieldcatid = $DB->insert_record('user_info_category', $fc);
    } else {
        $fieldcatid = $fieldcat->id;
    }
    $field = new stdClass();
    $field->shortname          = $fieldshort;
    $field->name               = 'Polo';
    $field->datatype           = 'menu';
    $field->description        = 'Polo de participação na Formação TRE/PA 2026';
    $field->descriptionformat  = FORMAT_HTML;
    $field->categoryid         = $fieldcatid;
    $field->sortorder          = 1;
    $field->required           = 0;
    $field->locked             = 0;
    $field->visible            = 2;
    $field->forceunique        = 0;
    $field->signup             = 1;
    $field->defaultdata        = '';
    $field->defaultdataformat  = 0;
    $field->param1             = "Marabá\nSantarém\nBelém";
    $field->param2 = $field->param3 = $field->param4 = $field->param5 = '';
    $fieldid = $DB->insert_record('user_info_field', $field);
    echo "✓ Campo de perfil criado: Polo (Marabá / Santarém / Belém) (id:{$fieldid})\n";
}

// ---------------------------------------------------------------------------
// 3. COURSES
// ---------------------------------------------------------------------------
$courses_def = [
    [
        'fullname'    => 'Formação: Competências Comportamentais e Direitos Fundamentais na Atuação da Justiça Eleitoral',
        'shortname'   => 'CODES-TRE-2026',
        'idnumber'    => 'CODES-TRE-2026',
        'summary'     => '<p>Portal de entrada da Formação TRE/PA 2026. Aqui você encontra o cronograma geral, as orientações de navegação e, ao concluir todos os Eixos obrigatórios do seu polo, o <strong>certificado da Formação</strong>.</p>',
        'numsections' => 3,
        'sections'    => [
            1 => 'Boas-vindas e Orientações',
            2 => 'Cronograma por Polo',
            3 => 'Programa IPER — Contexto Transversal',
        ],
    ],
    [
        'fullname'    => 'Eixo 1 — Competências Comportamentais e Cultura Institucional',
        'shortname'   => 'CODES-EIXO1-2026',
        'idnumber'    => 'CODES-EIXO1-2026',
        'summary'     => '<p>Desenvolvimento humano e institucional de magistrados e servidores das Zonas Eleitorais. <strong>4h | 100% Presencial | Todos os polos.</strong></p>',
        'numsections' => 4,
        'sections'    => [
            1 => 'M1.1 — Justiça Centrada nas Pessoas',
            2 => 'M1.2 — Competências Comportamentais no Serviço Público',
            3 => 'M1.3 — Comunicação, Escuta Qualificada e Empatia',
            4 => 'M1.4 — Gestão Emocional em Contextos de Pressão Institucional',
        ],
    ],
    [
        'fullname'    => 'Eixo 2A — Direitos Humanos, Gênero, Raça e Etnia',
        'shortname'   => 'CODES-EIXO2A-2026',
        'idnumber'    => 'CODES-EIXO2A-2026',
        'summary'     => '<p>Direitos humanos como eixo transversal da atuação judicial: fundamentos, gênero, raça, etnia e interseccionalidade. <strong>12h | Híbrida | Todos os polos.</strong></p>',
        'numsections' => 5,
        'sections'    => [
            1 => 'M2A.1 — Fundamentos dos Direitos Humanos',
            2 => 'M2A.2 — Perspectiva de Gênero no Poder Judiciário',
            3 => 'M2A.3 — Raça, Etnia e Desigualdades Estruturais',
            4 => 'M2A.4 — Interseccionalidade: Quando as Identidades se Cruzam',
            5 => 'M2A.5 — Aprofundamento, Avaliação e Recursos',
        ],
    ],
    [
        'fullname'    => 'Eixo 2B — Enfrentamento ao Assédio e à Discriminação',
        'shortname'   => 'CODES-EIXO2B-2026',
        'idnumber'    => 'CODES-EIXO2B-2026',
        'summary'     => '<p>Assédio moral e sexual, marco normativo, canal de denúncia e protocolo de acolhimento. <strong>8h | Híbrida | Polo Santarém.</strong></p>',
        'numsections' => 4,
        'sections'    => [
            1 => 'M2B.1 — Assédio Moral e Sexual: Conceitos, Formas e Impactos',
            2 => 'M2B.2 — Marco Normativo e Responsabilidades Institucionais',
            3 => 'M2B.3 — Canal de Denúncia e Protocolo de Acolhimento',
            4 => 'M2B.4 — Discriminação no Atendimento ao Público e Avaliação',
        ],
    ],
    [
        'fullname'    => 'Eixo 2C — Acessibilidade e Inclusão',
        'shortname'   => 'CODES-EIXO2C-2026',
        'idnumber'    => 'CODES-EIXO2C-2026',
        'summary'     => '<p>Acessibilidade no Poder Judiciário, remoção de barreiras, atendimento inclusivo e comunicação acessível. <strong>12h | Híbrida | Polo Belém.</strong></p>',
        'numsections' => 4,
        'sections'    => [
            1 => 'M2C.1 — Fundamentos da Acessibilidade no Poder Judiciário',
            2 => 'M2C.2 — Tipos de Barreiras e Como Removê-las',
            3 => 'M2C.3 — Atendimento Inclusivo na Zona Eleitoral',
            4 => 'M2C.4 — Comunicação Acessível e Eleições 2026',
        ],
    ],
    [
        'fullname'    => 'Eixo 3 — Equidade Racial: Fundamentos, Normativa e Protocolo de Julgamento',
        'shortname'   => 'CODES-EIXO3-2026',
        'idnumber'    => 'CODES-EIXO3-2026',
        'summary'     => '<p>Disciplina principal da Formação: povo quilombola, ações afirmativas, IPER e Protocolo de Julgamento com Perspectiva Racial. <strong>20h | Híbrida | Todos os polos.</strong></p>',
        'numsections' => 5,
        'sections'    => [
            1 => 'M3.1 — Povo Quilombola: História, Identidade e Resistência',
            2 => 'M3.2 — Dificuldades, Violações e Lutas das Comunidades Quilombolas',
            3 => 'M3.3 — Ações Afirmativas, Marco Normativo e o IPER',
            4 => 'M3.4 — Protocolo de Julgamento com Perspectiva Racial',
            5 => 'M3.5 — Práticas Institucionais de Equidade Racial e Avaliação Final',
        ],
    ],
];

$created_course_ids = [];

foreach ($courses_def as $def) {
    $existing = $DB->get_record('course', ['shortname' => $def['shortname']]);
    if ($existing) {
        echo "✓ Curso já existe: {$def['shortname']} (id:{$existing->id})\n";
        $created_course_ids[$def['shortname']] = $existing->id;
        $courseid = $existing->id;
    } else {
        $course = new stdClass();
        $course->category          = $catid;
        $course->fullname          = $def['fullname'];
        $course->shortname         = $def['shortname'];
        $course->idnumber          = $def['idnumber'];
        $course->summary           = $def['summary'];
        $course->summaryformat     = FORMAT_HTML;
        $course->format            = 'topics';
        $course->numsections       = $def['numsections'];
        $course->visible           = 1;
        $course->lang              = 'pt_br';
        $course->enablecompletion  = 1;
        $course->startdate         = mktime(0, 0, 0, 3, 12, 2026);
        $new_course = create_course($course);
        $courseid = $new_course->id;
        $created_course_ids[$def['shortname']] = $courseid;
        echo "✓ Curso criado: {$def['shortname']} (id:{$courseid})\n";
    }

    // Name the sections
    foreach ($def['sections'] as $num => $name) {
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $num]);
        if ($section) {
            $section->name    = $name;
            $section->visible = 1;
            $DB->update_record('course_sections', $section);
        }
    }
    echo "  ✓ Seções nomeadas em {$def['shortname']}\n";
}

// ---------------------------------------------------------------------------
// 4. GROUPS (Marabá / Santarém / Belém) in each Eixo course
// ---------------------------------------------------------------------------
$polos = ['Marabá', 'Santarém', 'Belém'];
$eixo_shorts = ['CODES-EIXO1-2026','CODES-EIXO2A-2026','CODES-EIXO2B-2026','CODES-EIXO2C-2026','CODES-EIXO3-2026'];

echo "\n--- Grupos ---\n";
foreach ($eixo_shorts as $shortname) {
    if (!isset($created_course_ids[$shortname])) continue;
    $courseid = $created_course_ids[$shortname];
    foreach ($polos as $polo) {
        if ($DB->record_exists('groups', ['courseid' => $courseid, 'name' => $polo])) {
            echo "  ✓ Grupo já existe: {$polo} em {$shortname}\n";
            continue;
        }
        $group = new stdClass();
        $group->courseid         = $courseid;
        $group->name             = $polo;
        $group->description      = "Participantes do Polo {$polo}";
        $group->descriptionformat = FORMAT_HTML;
        $group->enrolmentkey     = '';
        $group->timecreated      = time();
        $group->timemodified     = time();
        $gid = groups_create_group($group);
        echo "  ✓ Grupo criado: {$polo} em {$shortname} (id:{$gid})\n";
    }
}

// ---------------------------------------------------------------------------
// 5. SELF-ENROLMENT on each Eixo course
// ---------------------------------------------------------------------------
echo "\n--- Auto-inscrição ---\n";
foreach ($eixo_shorts as $shortname) {
    if (!isset($created_course_ids[$shortname])) continue;
    $courseid = $created_course_ids[$shortname];
    if ($DB->record_exists('enrol', ['courseid' => $courseid, 'enrol' => 'self'])) {
        echo "  ✓ Auto-inscrição já ativa em {$shortname}\n";
        continue;
    }
    $enrol_plugin = enrol_get_plugin('self');
    $enrol_plugin->add_instance(get_course($courseid), [
        'status'         => ENROL_INSTANCE_ENABLED,
        'name'           => 'Auto-inscrição',
        'enrolperiod'    => 0,
        'enrolstartdate' => 0,
        'enrolenddate'   => 0,
        'expirynotify'   => 0,
        'expirythreshold'=> 86400,
        'notifyall'      => 0,
        'password'       => '',
        'customint1'     => 0,
        'customint2'     => 0,
        'customint3'     => 0,
        'customint4'     => 1,
        'customint5'     => 0,
        'customint6'     => 1,
        'roleid'         => 5,
    ]);
    echo "  ✓ Auto-inscrição ativada em {$shortname}\n";
}

// ---------------------------------------------------------------------------
// Done
// ---------------------------------------------------------------------------
echo "\n=== Concluído! ===\n";
foreach ($created_course_ids as $short => $id) {
    echo "  {$short} → id:{$id}\n";
}
echo "\nPróximos passos:\n";
echo "  1. Instalar mod_customcert para certificados\n";
echo "  2. Adicionar atividades de vídeo, questionários e fóruns\n";
echo "  3. Configurar regras de conclusão por eixo\n";
