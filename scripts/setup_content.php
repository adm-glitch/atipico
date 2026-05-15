<?php
/**
 * CODES TRE-PA 2026 — Section Content + Video Placeholders
 * Populates section summaries and creates video-placeholder label activities
 * from the official pedagogical document (codes.pdf).
 * Run from Moodle root: php setup_content.php
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');

echo "=== CODES TRE-PA 2026 — Content Setup ===\n\n";

$LABEL_MOD_ID = $DB->get_field('modules', 'id', ['name' => 'label'], MUST_EXIST);

// ---------------------------------------------------------------------------
// Helper: create a label activity in a section (idempotent by marker)
// ---------------------------------------------------------------------------
function mk_label_activity($courseid, $sectionnum, $html, $marker) {
    global $DB, $CFG, $LABEL_MOD_ID;

    // course_modules.section stores the course_sections.id (PK), not the section number
    $sec = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
    if (!$sec) return null;

    // Idempotency: skip if a label with this marker already exists in this section
    $exists = $DB->record_exists_sql(
        "SELECT 1 FROM {course_modules} cm
         JOIN {label} l ON l.id = cm.instance AND cm.module = :mid
         WHERE cm.course = :cid AND cm.section = :sid AND l.intro LIKE :marker",
        ['mid' => $LABEL_MOD_ID, 'cid' => $courseid, 'sid' => $sec->id,
         'marker' => '%' . $marker . '%']
    );
    if ($exists) return null;

    // Insert label record directly
    $label              = new stdClass();
    $label->course      = $courseid;
    $label->name        = '';
    $label->intro       = $html;
    $label->introformat = FORMAT_HTML;
    $label->timemodified = time();
    $label_id = $DB->insert_record('label', $label);

    // Insert course_module record
    $cm = new stdClass();
    $cm->course             = $courseid;
    $cm->module             = $LABEL_MOD_ID;
    $cm->instance           = $label_id;
    $cm->section            = $sec->id;
    $cm->added              = time();
    $cm->visible            = 1;
    $cm->visibleoncoursepage = 1;
    $cm->visibleold         = 1;
    $cm->groupmode          = 0;
    $cm->groupingid         = 0;
    $cm->completion         = 0;
    $cm->completionview     = 0;
    $cm->completionexpected = 0;
    $cm->completionpassgrade = 0;
    $cm->showdescription    = 0;
    $cm->downloadcontent    = 1;
    $cm->deletioninprogress = 0;
    $cm->indent             = 0;
    $cm->score              = 0;
    $cm->idnumber           = '';
    $cm_id = $DB->insert_record('course_modules', $cm);

    // Append to section sequence
    $seq = empty($sec->sequence) ? [] : explode(',', $sec->sequence);
    $seq[] = $cm_id;
    $DB->set_field('course_sections', 'sequence', implode(',', $seq), ['id' => $sec->id]);

    return $cm_id;
}

// ---------------------------------------------------------------------------
// Helper: update section summary (only if currently empty)
// ---------------------------------------------------------------------------
function set_section_summary($courseid, $sectionnum, $html) {
    global $DB;
    $sec = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnum]);
    if (!$sec) return;
    if (!empty(trim(strip_tags($sec->summary)))) return; // don't overwrite existing content
    $DB->update_record('course_sections', (object)[
        'id'            => $sec->id,
        'summary'       => $html,
        'summaryformat' => FORMAT_HTML,
    ]);
}

// ---------------------------------------------------------------------------
// Helper: build video placeholder HTML
// ---------------------------------------------------------------------------
function video_placeholder($title, $intro_text, $marker) {
    $safe_intro = nl2br(htmlspecialchars($intro_text, ENT_QUOTES, 'UTF-8'));
    return '<div data-video-marker="' . htmlspecialchars($marker, ENT_QUOTES) . '" '
        . 'style="background:rgba(218,170,0,0.07);border-left:4px solid #DAAA00;'
        . 'padding:16px 20px;margin:12px 0;border-radius:4px">'
        . '<p style="margin:0 0 6px;font-weight:600;font-size:1.05em">🎬 ' . htmlspecialchars($title, ENT_QUOTES) . '</p>'
        . '<p style="margin:0 0 10px;font-style:italic;opacity:.9">' . $safe_intro . '</p>'
        . '<p style="margin:0;font-size:.8em;opacity:.55">📹 Vídeo a ser integrado via Bunny Stream · '
        . '<em>aguardando upload</em></p></div>';
}

// ---------------------------------------------------------------------------
// Helper: build section summary HTML
// ---------------------------------------------------------------------------
function section_summary($description, $duration, $modality, $submods = []) {
    $html  = '<p>' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($submods) {
        $html .= '<ul>';
        foreach ($submods as $sm) {
            $html .= '<li>' . htmlspecialchars($sm, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul>';
    }
    $html .= '<p><small>⏱ ' . htmlspecialchars($duration, ENT_QUOTES)
           . ' &nbsp;|&nbsp; 📡 ' . htmlspecialchars($modality, ENT_QUOTES) . '</small></p>';
    return $html;
}

// Get course IDs
$CID = [];
foreach (['CODES-EIXO1-2026','CODES-EIXO2A-2026','CODES-EIXO2B-2026','CODES-EIXO2C-2026','CODES-EIXO3-2026'] as $sn) {
    $CID[$sn] = $DB->get_field('course', 'id', ['shortname' => $sn], MUST_EXIST);
}

// ============================================================
// EIXO 1 — Competências Comportamentais e Cultura Institucional
// ============================================================
echo "--- EIXO 1 ---\n";
$c = $CID['CODES-EIXO1-2026'];

set_section_summary($c, 0,
    '<h4>Ementa</h4>'
    . '<p>O Eixo 1 promove o desenvolvimento humano e institucional de magistrados e servidores '
    . 'das Zonas Eleitorais do Pará, com foco em competências comportamentais fundamentais para uma '
    . 'atuação judicial ética, empática e comprometida com a qualidade do serviço público. Aborda a '
    . 'Justiça centrada nas pessoas, o papel institucional da Zona Eleitoral no contexto eleitoral de '
    . '2026, a comunicação e a escuta qualificada, e o desenvolvimento emocional para situações de '
    . 'pressão institucional.</p>'
    . '<p><small>⏱ 4 horas &nbsp;|&nbsp; 📡 100% Presencial &nbsp;|&nbsp; 🗺 Todos os polos</small></p>'
    . '<p><small>Normativos: Prêmio CNJ de Qualidade · Portaria CNJ nº 471/2025 · Resolução CNJ nº 351/2020</small></p>'
);

// M1.1
set_section_summary($c, 1, section_summary(
    'O que significa colocar o cidadão no centro da atuação judicial. Princípios de humanização '
    . 'do serviço público. O papel da Zona Eleitoral como ponto de contato entre o Estado e o eleitor.',
    '1 hora', 'Presencial'
));
mk_label_activity($c, 1, video_placeholder(
    'Módulo M1.1 — Abertura',
    'Bem-vindos ao curso Competências Comportamentais e Direitos Fundamentais na Atuação da Justiça Eleitoral. '
    . 'Neste primeiro módulo, vamos refletir sobre uma questão simples, mas profunda: para quem trabalhamos? '
    . 'A Zona Eleitoral não é apenas um cartório de registros eleitorais. É o ponto onde a democracia se '
    . 'torna tangível para o cidadão. Neste módulo, vamos discutir o que significa, na prática, colocar '
    . 'o eleitor no centro de tudo o que fazemos.',
    'video-m1.1'
), 'video-m1.1');
echo "  ✓ M1.1 — conteúdo + vídeo placeholder\n";

// M1.2
set_section_summary($c, 2, section_summary(
    'O que são competências comportamentais e por que são tão importantes quanto as técnico-jurídicas. '
    . 'Autoconhecimento, responsabilidade, proatividade e comprometimento institucional.',
    '1 hora', 'Presencial'
));
mk_label_activity($c, 2, video_placeholder(
    'Módulo M1.2 — Competências Comportamentais no Serviço Público',
    'Competências técnico-jurídicas resolvem processos. Competências comportamentais resolvem pessoas. '
    . 'Neste módulo vamos entender por que o autoconhecimento e o comprometimento institucional são '
    . 'tão decisivos quanto o domínio das normas eleitorais.',
    'video-m1.2'
), 'video-m1.2');
echo "  ✓ M1.2 — conteúdo + vídeo placeholder\n";

// M1.3
set_section_summary($c, 3, section_summary(
    'A diferença entre ouvir e escutar. Comunicação não-violenta no atendimento ao público. '
    . 'Empatia como ferramenta de trabalho — não fraqueza, mas competência. Barreiras comunicacionais '
    . 'e como superá-las.',
    '1 hora', 'Presencial'
));
mk_label_activity($c, 3, video_placeholder(
    'Módulo M1.3 — Abertura',
    'Você já atendeu alguém que saiu satisfeito tecnicamente, mas insatisfeito como pessoa? A maioria '
    . 'das reclamações de serviços públicos não é sobre o resultado — é sobre a forma. Este módulo é '
    . 'sobre a diferença entre processar uma demanda e acolher uma pessoa. Escuta qualificada e empatia '
    . 'não são atributos pessoais: são competências que se aprendem e se praticam.',
    'video-m1.3'
), 'video-m1.3');
echo "  ✓ M1.3 — conteúdo + vídeo placeholder\n";

// M1.4
set_section_summary($c, 4, section_summary(
    'O impacto do ano eleitoral na saúde emocional dos servidores. Técnicas de autorregulação. '
    . 'Gestão de conflitos interpessoais. Prevenção do adoecimento no trabalho.',
    '1 hora', 'Presencial'
));
mk_label_activity($c, 4, video_placeholder(
    'Módulo M1.4 — Gestão Emocional em Contextos de Pressão Institucional',
    'O período eleitoral é, comprovadamente, um dos momentos de maior carga emocional para servidores '
    . 'do Judiciário. Neste módulo vamos explorar técnicas concretas de autorregulação emocional e '
    . 'prevenção do adoecimento — não como luxo, mas como condição para um serviço público de qualidade.',
    'video-m1.4'
), 'video-m1.4');
echo "  ✓ M1.4 — conteúdo + vídeo placeholder\n";

// ============================================================
// EIXO 2A — Direitos Humanos, Gênero, Raça e Etnia
// ============================================================
echo "\n--- EIXO 2A ---\n";
$c = $CID['CODES-EIXO2A-2026'];

set_section_summary($c, 0,
    '<h4>Ementa</h4>'
    . '<p>O Eixo 2A desenvolve nos participantes a compreensão dos direitos humanos como eixo transversal '
    . 'da atuação judicial e administrativa. Aborda os fundamentos conceituais e normativos dos direitos '
    . 'humanos, a perspectiva de gênero no Poder Judiciário, os marcadores de raça e etnia como fatores '
    . 'de desigualdade estrutural, a interseccionalidade como ferramenta de análise, e a aplicação desses '
    . 'conceitos na prática das Zonas Eleitorais. Prepara o terreno para o aprofundamento no Eixo 3.</p>'
    . '<p><small>⏱ 12 horas (4h presencial + 4h EAD síncrono + 4h assíncrono) &nbsp;|&nbsp; 📡 Híbrida &nbsp;|&nbsp; 🗺 Todos os polos</small></p>'
    . '<p><small>Normativos: Res. CNJ nº 255/2018 · Res. CNJ nº 492/2023 · ODS 5 e 10 (Agenda 2030)</small></p>'
);

// M2A.1
set_section_summary($c, 1, section_summary(
    'O sistema internacional de proteção dos direitos humanos. A Constituição Federal de 1988 como carta '
    . 'de direitos. Direitos humanos na prática do Poder Judiciário: da norma ao comportamento.',
    '1h30', 'Presencial',
    [
        'SM2A.1.1 — O Sistema ONU de Direitos Humanos: tratados, protocolos e mecanismos de monitoramento ratificados pelo Brasil',
        'SM2A.1.2 — A Constituição Federal de 1988 e o Catálogo de Direitos Fundamentais: artigos essenciais para o trabalho na ZE',
        'SM2A.1.3 — Direitos Humanos vs. Direitos do Cidadão: por que a distinção importa para a Justiça Eleitoral',
    ]
));
mk_label_activity($c, 1, video_placeholder(
    'Módulo M2A.1 — Abertura',
    'Os direitos humanos não são uma abstração filosófica. Eles estão presentes em cada atendimento que '
    . 'você faz na Zona Eleitoral: no momento em que você garante o voto de uma pessoa idosa, quando '
    . 'orienta um eleitor sem documentos, quando trata com respeito alguém que veio de uma comunidade '
    . 'quilombola a horas de distância. Neste módulo, vamos situar os direitos humanos exatamente onde '
    . 'eles pertencem: no centro da prática judicial cotidiana.',
    'video-m2a.1'
), 'video-m2a.1');
echo "  ✓ M2A.1 — conteúdo + vídeo\n";

// M2A.2
set_section_summary($c, 2, section_summary(
    'Desigualdades de gênero no sistema de justiça. A Resolução CNJ nº 255/2018 e o incentivo à '
    . 'participação feminina. Violência política de gênero no contexto eleitoral. O Protocolo CNJ para '
    . 'Julgamento com Perspectiva de Gênero.',
    '1h', 'Presencial',
    [
        'SM2A.2.1 — Dados sobre desigualdade de gênero no Judiciário e no campo político',
        'SM2A.2.2 — Violência política de gênero: definição, formas e como a ZE deve responder',
        'SM2A.2.3 — Resolução CNJ nº 255/2018: o que ela determina e como implementar na ZE',
    ]
));
mk_label_activity($c, 2, video_placeholder(
    'Módulo M2A.2 — Perspectiva de Gênero no Poder Judiciário',
    'Os dados não deixam dúvidas: mulheres são maioria do eleitorado brasileiro, mas minoria nas decisões '
    . 'que organizam as eleições. Este módulo examina as desigualdades de gênero no sistema de justiça '
    . 'e apresenta ferramentas concretas para que a Zona Eleitoral seja um espaço mais equitativo.',
    'video-m2a.2'
), 'video-m2a.2');
echo "  ✓ M2A.2 — conteúdo + vídeo\n";

// M2A.3
set_section_summary($c, 3, section_summary(
    'Introdução aos conceitos de raça, etnia, racismo individual, institucional e estrutural. '
    . 'Dados sobre desigualdades raciais no Brasil e no Pará. Preparação para o aprofundamento no Eixo 3.',
    '1h30', 'Presencial',
    [
        'SM2A.3.1 — Raça como construção social: por que o conceito importa',
        'SM2A.3.2 — Racismo estrutural e institucional: como operam nas instituições',
        'SM2A.3.3 — Panorama das desigualdades raciais no Brasil e no Pará (dados IBGE 2022)',
    ]
));
mk_label_activity($c, 3, video_placeholder(
    'Módulo M2A.3 — Abertura',
    'Quando falamos de raça neste curso, não estamos falando de biologia. Estamos falando de história, '
    . 'de poder e de consequências muito concretas na vida das pessoas. O Pará é um estado onde cerca '
    . 'de 80% da população é preta, parda ou indígena — e isso significa que a maioria das pessoas que '
    . 'batem na porta da sua Zona Eleitoral carrega, em graus diferentes, o peso de uma história de '
    . 'exclusão. Entender essa história não é um exercício de culpa. É um exercício de justiça.',
    'video-m2a.3'
), 'video-m2a.3');
echo "  ✓ M2A.3 — conteúdo + vídeo\n";

// M2A.4
set_section_summary($c, 4, section_summary(
    'O conceito de interseccionalidade de Kimberlé Crenshaw. Como raça, gênero e classe se combinam '
    . 'para criar experiências únicas de vulnerabilidade. Aplicação prática no atendimento da ZE.',
    '1h', 'EAD síncrono',
    [
        'SM2A.4.1 — A teoria da interseccionalidade e seu desenvolvimento',
        'SM2A.4.2 — Casos de interseccionalidade na Justiça Eleitoral: mulheres negras candidatas, quilombolas, LGBTQIA+',
        'SM2A.4.3 — Como aplicar o olhar interseccional no atendimento cotidiano',
    ]
));
mk_label_activity($c, 4, video_placeholder(
    'Módulo M2A.4 — Interseccionalidade: Quando as Identidades se Cruzam',
    'Uma mulher negra quilombola que tenta registrar sua candidatura não enfrenta apenas o racismo, '
    . 'nem apenas o machismo, nem apenas a distância geográfica — ela enfrenta tudo isso ao mesmo tempo, '
    . 'de forma amplificada. Isso é interseccionalidade. E reconhecer isso é o primeiro passo para '
    . 'fazer justiça de verdade.',
    'video-m2a.4'
), 'video-m2a.4');
echo "  ✓ M2A.4 — conteúdo + vídeo\n";

// M2A.5 (EAD assíncrono — já tem quiz + fórum)
set_section_summary($c, 5, section_summary(
    'Leituras complementares, videoaula gravada de síntese, fórum de discussão e avaliação final do Eixo 2A.',
    '4h assíncrono', 'EAD assíncrono (AVA)'
));
echo "  ✓ M2A.5 — sumário atualizado\n";

// ============================================================
// EIXO 2B — Enfrentamento ao Assédio e à Discriminação
// ============================================================
echo "\n--- EIXO 2B ---\n";
$c = $CID['CODES-EIXO2B-2026'];

set_section_summary($c, 0,
    '<h4>Ementa</h4>'
    . '<p>O Eixo 2B capacita magistrados e servidores para identificar, prevenir e enfrentar situações '
    . 'de assédio moral e sexual e de discriminação no ambiente de trabalho e no atendimento ao público, '
    . 'com ênfase nas formas de discriminação racial, de gênero e por orientação sexual. Apresenta a '
    . 'política institucional do CNJ, os canais de denúncia disponíveis, o protocolo de acolhimento a '
    . 'vítimas e as responsabilidades administrativas e penais envolvidas.</p>'
    . '<p><small>⏱ 8 horas (4h presencial + 2h EAD síncrono + 2h assíncrono) &nbsp;|&nbsp; 📡 Híbrida &nbsp;|&nbsp; 🗺 Polo Santarém</small></p>'
    . '<p><small>Normativos: Res. CNJ nº 351/2020 · Lei nº 9.029/1995 · Lei nº 14.457/2022</small></p>'
);

// M2B.1
set_section_summary($c, 1, section_summary(
    'Definições legais e doutrinários. Assédio moral individual e organizacional. Assédio sexual no '
    . 'trabalho. Diferenciação de condutas legítimas e ilegítimas. O impacto psicológico nas vítimas.',
    '1h30', 'Presencial',
    [
        'SM2B.1.1 — Assédio moral: conceito, tipos e exemplos práticos no ambiente judicial',
        'SM2B.1.2 — Assédio sexual: do comportamento tolerado ao crime — onde está a linha?',
        'SM2B.1.3 — Assédio com motivação racial: a especificidade do racismo no ambiente de trabalho',
    ]
));
mk_label_activity($c, 1, video_placeholder(
    'Módulo M2B.1 — Abertura',
    'O assédio não vive apenas em grandes gestos. Muitas vezes, ele está nos comentários que ninguém '
    . 'leva a sério, nas piadas que todo mundo ri, no silêncio que se impõe a quem tem menos poder. '
    . 'Este módulo não é sobre o monstro distante. É sobre o ambiente que construímos juntos — e sobre '
    . 'o que cada um de nós faz ou deixa de fazer quando isso acontece ao nosso lado.',
    'video-m2b.1'
), 'video-m2b.1');
echo "  ✓ M2B.1 — conteúdo + vídeo\n";

// M2B.2
set_section_summary($c, 2, section_summary(
    'Resolução CNJ nº 351/2020 em detalhe. Responsabilidades do gestor, do colega e da instituição. '
    . 'Consequências administrativas, civis e penais. A Política do TRE/PA sobre assédio e discriminação.',
    '1h', 'Presencial',
    [
        'SM2B.2.1 — Resolução CNJ nº 351/2020: obrigações dos tribunais e das chefias',
        'SM2B.2.2 — Responsabilidade do servidor que testemunha: o dever de comunicar',
        'SM2B.2.3 — Crimes e infrações disciplinares: o que cada conduta pode gerar',
    ]
));
mk_label_activity($c, 2, video_placeholder(
    'Módulo M2B.2 — Marco Normativo e Responsabilidades Institucionais',
    'A Resolução CNJ nº 351/2020 não deixa dúvidas: assédio e discriminação no Judiciário não são '
    . 'questões pessoais — são questões institucionais. Neste módulo vamos percorrer o que a norma '
    . 'determina e o que cada magistrado, chefe e servidor é obrigado a fazer quando toma conhecimento '
    . 'de uma situação de assédio.',
    'video-m2b.2'
), 'video-m2b.2');
echo "  ✓ M2B.2 — conteúdo + vídeo\n";

// M2B.3
set_section_summary($c, 3, section_summary(
    'Como funciona o canal de denúncia do TRE/PA. O protocolo de acolhimento a vítimas (espaço seguro, '
    . 'suporte psicossocial e orientação jurídica). Como conduzir o primeiro contato com uma vítima.',
    '1h30', 'Presencial',
    [
        'SM2B.3.1 — O canal de denúncia: como acessar, o que esperar e como é investigado',
        'SM2B.3.2 — O sistema de acolhimento à vítima: três elementos essenciais',
        'SM2B.3.3 — Simulação de atendimento: como conduzir o primeiro contato com quem denuncia',
    ]
));
mk_label_activity($c, 3, video_placeholder(
    'Módulo M2B.3 — Canal de Denúncia e Protocolo de Acolhimento',
    'Saber que existe um canal de denúncia não é suficiente. O que importa é saber como usá-lo — '
    . 'e, acima de tudo, saber o que dizer quando alguém chega até você com uma denúncia. '
    . 'Neste módulo vamos praticar o acolhimento: como criar um espaço seguro, '
    . 'como ouvir sem julgamento e como orientar a vítima nos próximos passos.',
    'video-m2b.3'
), 'video-m2b.3');
echo "  ✓ M2B.3 — conteúdo + vídeo\n";

// M2B.4 (EAD — já tem quiz + tarefa)
set_section_summary($c, 4, section_summary(
    'Como identificar e responder a situações de discriminação no atendimento eleitoral. '
    . 'Avaliação final do Eixo 2B.',
    '2h síncrono + 2h assíncrono', 'EAD'
));
echo "  ✓ M2B.4 — sumário atualizado\n";

// ============================================================
// EIXO 2C — Acessibilidade e Inclusão
// ============================================================
echo "\n--- EIXO 2C ---\n";
$c = $CID['CODES-EIXO2C-2026'];

set_section_summary($c, 0,
    '<h4>Ementa</h4>'
    . '<p>O Eixo 2C desenvolve nos participantes a compreensão dos marcos normativos da acessibilidade e '
    . 'inclusão no Poder Judiciário, a identificação de barreiras físicas, comunicacionais, atitudinais e '
    . 'tecnológicas que impedem o pleno acesso de pessoas com deficiência e grupos vulneráveis à Justiça '
    . 'Eleitoral, e a adoção de práticas inclusivas no atendimento, na comunicação institucional e no '
    . 'planejamento das eleições de 2026. Aborda com especial atenção a intersecção entre deficiência, '
    . 'raça e vulnerabilidade social.</p>'
    . '<p><small>⏱ 12 horas (4h presencial + 4h EAD síncrono + 4h assíncrono) &nbsp;|&nbsp; 📡 Híbrida &nbsp;|&nbsp; 🗺 Polo Belém</small></p>'
    . '<p><small>Normativos: Res. CNJ nº 401/2021 · Res. CNJ nº 343/2020 · Lei nº 13.146/2015 (LBI)</small></p>'
);

// M2C.1
set_section_summary($c, 1, section_summary(
    'Conceitos centrais: acessibilidade, inclusão, deficiência e barreiras. A Lei Brasileira de Inclusão '
    . '(Lei nº 13.146/2015). As Resoluções CNJ nº 401/2021 e 343/2020. O modelo social da deficiência.',
    '1h30', 'Presencial',
    [
        'SM2C.1.1 — Do modelo médico ao modelo social da deficiência: uma mudança de paradigma',
        'SM2C.1.2 — A Lei Brasileira de Inclusão e suas obrigações para o Judiciário',
        'SM2C.1.3 — Resoluções CNJ nº 401/2021 e 343/2020: o que determinam para as Zonas Eleitorais',
    ]
));
mk_label_activity($c, 1, video_placeholder(
    'Módulo M2C.1 — Abertura',
    'Uma cadeira de rodas não impede uma pessoa de votar. Mas uma seção eleitoral sem rampa sim. '
    . 'Uma deficiência visual não impede alguém de exercer a cidadania. Mas uma urna sem audiodescrição '
    . 'adequada pode. Acessibilidade não é sobre a pessoa — é sobre o ambiente que construímos. '
    . 'E esse ambiente, nas eleições de 2026, é responsabilidade de cada um de nós.',
    'video-m2c.1'
), 'video-m2c.1');
echo "  ✓ M2C.1 — conteúdo + vídeo\n";

// M2C.2
set_section_summary($c, 2, section_summary(
    'Barreiras físicas, comunicacionais, tecnológicas, atitudinais e programáticas. '
    . 'Diagnóstico de acessibilidade nas Zonas Eleitorais. Estratégias de remoção de cada tipo de barreira.',
    '1h30', 'Presencial',
    [
        'SM2C.2.1 — Barreiras físicas e arquitetônicas: diagnóstico e soluções para os locais de votação',
        'SM2C.2.2 — Barreiras comunicacionais: linguagem simples, LIBRAS, Braille e formatos acessíveis',
        'SM2C.2.3 — Barreiras atitudinais: o papel do servidor no acolhimento de eleitores com deficiência',
    ]
));
mk_label_activity($c, 2, video_placeholder(
    'Módulo M2C.2 — Tipos de Barreiras e Como Removê-las',
    'Existem cinco tipos de barreiras que impedem pessoas com deficiência de exercer plenamente '
    . 'a cidadania eleitoral. Algumas são físicas e visíveis. Outras são comunicacionais, tecnológicas '
    . 'ou atitudinais — e essas últimas são, frequentemente, as mais difíceis de remover, '
    . 'porque começam dentro de nós.',
    'video-m2c.2'
), 'video-m2c.2');
echo "  ✓ M2C.2 — conteúdo + vídeo\n";

// M2C.3
set_section_summary($c, 3, section_summary(
    'Protocolo de atendimento a eleitores com diferentes tipos de deficiência. Tecnologias assistivas '
    . 'disponíveis. Atendimento prioritário e diferenciado. A intersecção entre deficiência, raça e pobreza.',
    '1h', 'Presencial',
    [
        'SM2C.3.1 — Atendimento a pessoas com deficiência visual, auditiva, física e intelectual: orientações práticas',
        'SM2C.3.2 — Pessoas com deficiência negras: dupla vulnerabilidade e como responder',
        'SM2C.3.3 — Tecnologias assistivas disponíveis na ZE e como usá-las',
    ]
));
mk_label_activity($c, 3, video_placeholder(
    'Módulo M2C.3 — Atendimento Inclusivo na Zona Eleitoral',
    'Cada tipo de deficiência requer um protocolo diferente. Mas há um princípio comum: '
    . 'pergunte antes de ajudar. Nunca presuma o que a pessoa precisa. Neste módulo vamos praticar '
    . 'os protocolos de atendimento para diferentes perfis de eleitores com deficiência '
    . 'e explorar as tecnologias assistivas já disponíveis na ZE.',
    'video-m2c.3'
), 'video-m2c.3');
echo "  ✓ M2C.3 — conteúdo + vídeo\n";

// M2C.4 (EAD — já tem quiz + checklist)
set_section_summary($c, 4, section_summary(
    'Como produzir materiais eleitorais acessíveis. WCAG 2.1 aplicado à comunicação da ZE. '
    . 'Planejamento de seções eleitorais acessíveis. Avaliação e checklist final.',
    '4h EAD síncrono + 4h assíncrono', 'EAD'
));
echo "  ✓ M2C.4 — sumário atualizado\n";

// ============================================================
// EIXO 3 — Equidade Racial (disciplina principal — 20h)
// ============================================================
echo "\n--- EIXO 3 ---\n";
$c = $CID['CODES-EIXO3-2026'];

set_section_summary($c, 0,
    '<h4>Ementa</h4>'
    . '<p>O Eixo 3 promove o desenvolvimento de competências conceituais, normativas, práticas e reflexivas '
    . 'de magistradas, magistrados, servidoras e servidores das Zonas Eleitorais para uma atuação judicial '
    . 'e administrativa equitativa, inclusiva e comprometida com os direitos fundamentais da população negra '
    . 'e de grupos étnico-raciais vulneráveis.</p>'
    . '<p>A disciplina está organizada em quatro grandes módulos temáticos: (1) fundamentos históricos e '
    . 'conceituais sobre a identidade e a resistência do povo quilombola; (2) as dificuldades, violações e '
    . 'lutas das comunidades quilombolas contemporâneas; (3) o marco normativo das ações afirmativas e do '
    . 'IPER; e (4) a aplicação prática do Protocolo de Julgamento com Perspectiva Racial do CNJ.</p>'
    . '<p><small>⏱ 20 horas (8h presencial + 8h EAD síncrono + 4h assíncrono) &nbsp;|&nbsp; 📡 Híbrida &nbsp;|&nbsp; 🗺 Todos os polos</small></p>'
    . '<p><small>Normativos: Res. CNJ nº 492/2023 · Res. CNJ nº 599/2024 · Lei nº 12.288/2010 · Lei nº 12.990/2014</small></p>'
    . '<p><small>Indicadores CNJ: IPER — Dimensão de Pessoas · Prêmio CNJ de Qualidade — Eixo Equidade Racial</small></p>'
);

// M3.1
set_section_summary($c, 1, section_summary(
    'Origens africanas e formação dos quilombos no Brasil colonial. Identidade quilombola contemporânea: '
    . 'autodeterminação, território e cultura. Quilombo dos Palmares e a história da resistência. '
    . 'Comunidades quilombolas no Brasil e no Pará: dados, distribuição e contextos regionais. '
    . 'Manifestações culturais quilombolas: religiosidade, música, culinária e saberes tradicionais.',
    '2h', 'Presencial (Dia 1 — tarde)',
    [
        'SM3.1.1 — A África que Veio ao Brasil: diversidade de povos, culturas e línguas',
        'SM3.1.2 — O Que é um Quilombo? Origem da palavra, função histórica e diversidade dos quilombos brasileiros',
        'SM3.1.3 — Quilombo dos Palmares e Zumbi: a resistência que se tornou símbolo',
        'SM3.1.4 — Identidade Quilombola Hoje: autodeterminação, território, comunidade e cultura',
        'SM3.1.5 — Quilombolas no Pará: regiões, comunidades e contextos específicos (Marajó, Trombetas, Tocantins)',
        'SM3.1.6 — Manifestações Culturais: Carimbó, Tambor de Mina, culinária e medicina tradicional',
    ]
));
mk_label_activity($c, 1, video_placeholder(
    'Módulo 3.1 — Abertura (Presencial)',
    'Quando falamos de povo quilombola, estamos falando de uma das histórias mais extraordinárias de '
    . 'resistência da América Latina. São comunidades que, há séculos, recusaram a escravidão, '
    . 'construíram mundos livres no meio da floresta e transmitiram sua identidade de geração em geração '
    . '— apesar de tudo. Hoje, essas comunidades estão presentes no Marajó, no Baixo Amazonas, no '
    . 'Nordeste Paraense. Muitas delas estão na sua jurisdição. Conhecer essa história não é um exercício '
    . 'acadêmico. É um pré-requisito para fazer justiça.',
    'video-m3.1'
), 'video-m3.1');
echo "  ✓ M3.1 — conteúdo + vídeo\n";

// M3.2
set_section_summary($c, 2, section_summary(
    'A abolição inacabada e o legado do abandono pós-1888. A questão fundiária: titulação, grilagem '
    . 'e conflitos. Acesso a saúde, educação e justiça. Racismo institucional e barreiras ao voto. '
    . 'Violência política e sub-representação. O ciclo de mobilização do Movimento Negro.',
    '2h', 'Presencial (Dia 1 — tarde)',
    [
        'SM3.2.1 — A Abolição Inacabada: o que não aconteceu em 1888 e suas consequências até hoje',
        'SM3.2.2 — Questão Fundiária: o que são os processos do INCRA e por que menos de 5% estão concluídos',
        'SM3.2.3 — Saúde, Educação e Acesso à Justiça: dados e barreiras específicas das comunidades quilombolas',
        'SM3.2.4 — Racismo Institucional e Barreiras ao Voto: como a ZE pode identificar e remover obstáculos',
        'SM3.2.5 — Violência Fundiária no Pará: o estado que lidera os conflitos agrários',
        'SM3.2.6 — Movimento Negro e a Conquista dos Direitos: quem lutou por cada norma que estudamos',
    ]
));
mk_label_activity($c, 2, video_placeholder(
    'Módulo 3.2 — Abertura (Presencial)',
    'A Lei Áurea foi assinada em 1888. Mas nenhuma lei de terras, nenhuma política de educação, nenhum '
    . 'programa de integração acompanhou a assinatura. As pessoas libertas foram, literalmente, deixadas '
    . 'à própria sorte — enquanto o governo financiava a imigração europeia. Esse abandono não é história '
    . 'passada. Seus efeitos estão presentes nos dados que vamos ver agora. E parte desses efeitos está '
    . 'dentro das atribuições da Justiça Eleitoral resolver — ou pelo menos não piorar.',
    'video-m3.2'
), 'video-m3.2');
echo "  ✓ M3.2 — conteúdo + vídeo\n";

// M3.3
set_section_summary($c, 3, section_summary(
    'O que são ações afirmativas e sua base constitucional. Estatuto da Igualdade Racial, Lei de Cotas '
    . 'e marcos legais. Resoluções CNJ nº 492/2023 e nº 599/2024 em detalhe. O IPER: conceito, as três '
    . 'dimensões e os indicadores. Como cada servidor contribui para o IPER. O IPER e o Prêmio CNJ de Qualidade.',
    '2h presencial + 2h EAD síncrono', 'Híbrida (Dia 1 tarde + webinar)',
    [
        'SM3.3.1 — O Que São Ações Afirmativas: fundamento constitucional e diferença de "privilégio"',
        'SM3.3.2 — Marco Legal Nacional: Estatuto da Igualdade Racial, Lei de Cotas e Convenções Internacionais',
        'SM3.3.3 — Legislação do Estado do Pará: Estatuto da Equidade Racial Estadual (Lei nº 9.341/2021)',
        'SM3.3.4 — Resolução CNJ nº 492/2023: os 7 dispositivos e o que significam na prática',
        'SM3.3.5 — Resolução CNJ nº 599/2024: obrigações específicas para quilombolas',
        'SM3.3.6 — O IPER: as três dimensões (Institucional, Pessoas, Serviços) e seus indicadores',
        'SM3.3.7 — O Papel de Cada Servidor no IPER: como a ZE contribui concretamente',
        'SM3.3.8 — IPER e o Prêmio CNJ de Qualidade: eixos avaliados e metas do TRE/PA',
    ]
));
mk_label_activity($c, 3, video_placeholder(
    'Módulo 3.3 — Abertura da Parte Presencial',
    'Ações afirmativas não são favores. São instrumentos de equidade que reconhecem que, em uma corrida '
    . 'onde alguns partiram séculos atrás, não é possível chegar ao mesmo ponto partindo do mesmo lugar '
    . 'ao mesmo tempo. Neste módulo vamos entender por que o Brasil levou mais de 100 anos para criar as '
    . 'primeiras políticas de reparação — e o que o Conselho Nacional de Justiça está fazendo agora para '
    . 'garantir que o Judiciário faça a sua parte.',
    'video-m3.3a'
), 'video-m3.3a');
mk_label_activity($c, 3, video_placeholder(
    'Módulo 3.3 — Abertura do Webinar EAD Síncrono (Webinar 1)',
    'Bem-vindos ao nosso primeiro webinar do Eixo 3! Agora que você já passou pela parte presencial e '
    . 'conheceu o marco normativo básico, vamos aprofundar juntos o IPER — Índice de Promoção da '
    . 'Equidade Racial. Este é o indicador que vai medir, de forma concreta e pública, o quanto o '
    . 'TRE/PA está cumprindo seus compromissos com a equidade racial. E cada um de vocês, na sua '
    . 'Zona Eleitoral, é parte fundamental desse índice.',
    'video-m3.3b'
), 'video-m3.3b');
echo "  ✓ M3.3 — conteúdo + 2 vídeos (presencial + webinar)\n";

// M3.4
set_section_summary($c, 4, section_summary(
    'Por que o sistema de justiça reproduz desigualdades raciais. O conceito de viés racial implícito. '
    . 'O paradoxo da neutralidade. As 5 etapas do Protocolo CNJ. Aplicação prática em casos concretos '
    . 'da Justiça Eleitoral. Fundamentação e transparência nas decisões.',
    '2h presencial + 2h EAD síncrono + 1h gravada', 'Híbrida + aula 100% gravada (EAD assíncrono)',
    [
        'SM3.4.1 — Por Que o Protocolo Existe: dados sobre desigualdade racial no sistema de justiça',
        'SM3.4.2 — Igualdade Formal vs. Igualdade Material: por que "tratar todos igual" pode ser injusto',
        'SM3.4.3 — Viés Racial Implícito: o que é, como opera e por que todos nós o temos',
        'SM3.4.4 — Como o Viés Se Manifesta em Decisões Judiciais: 5 formas concretas',
        'SM3.4.5 — O Paradoxo da Neutralidade: por que "não ver raça" não é imparcialidade',
        'SM3.4.6 — O Protocolo CNJ: visão geral das 5 etapas e sua lógica',
        'SM3.4.7 — Etapa 1: Identificação — A raça é relevante neste caso?',
        'SM3.4.8 — Etapa 2: Contextualização — Qual é o contexto racial desta situação?',
        'SM3.4.9 — Etapa 3: Análise dos Vieses — O teste da inversão racial',
        'SM3.4.10 — Etapa 4: Aplicação com Equidade — Qual decisão promove mais igualdade material?',
        'SM3.4.11 — Etapa 5: Fundamentação e Transparência — Documentando a análise racial',
        'SM3.4.12 — Casos Práticos: a candidata quilombola e o eleitor em situação de rua',
    ]
));
mk_label_activity($c, 4, video_placeholder(
    'Módulo 3.4 — Abertura da Parte Presencial',
    'Há uma frase que repito sempre quando falo sobre este tema: a Justiça que não examina seus próprios '
    . 'vieses pode estar sendo injusta sem perceber. Viés racial implícito não é sobre ser racista. É '
    . 'sobre ser humano em uma sociedade racista. E o protocolo que vamos estudar hoje existe exatamente '
    . 'para dar a magistrados e servidores uma ferramenta concreta para interromper esse ciclo.',
    'video-m3.4a'
), 'video-m3.4a');
mk_label_activity($c, 4, video_placeholder(
    'Módulo 3.4 — Videoaula EAD 100% Gravada',
    'Bom dia, boa tarde ou boa noite — seja quando for que você esteja assistindo a esta aula! '
    . 'Esta é a videoaula sobre o Protocolo de Julgamento com Perspectiva Racial do CNJ. '
    . 'Ao longo de aproximadamente 70 minutos, vamos percorrer as 5 etapas do protocolo, entender '
    . 'o que são vieses raciais implícitos, e analisar casos concretos do contexto eleitoral paraense. '
    . 'Ao final, você terá acesso ao Cartão de Síntese para download e à avaliação no AVA.',
    'video-m3.4b'
), 'video-m3.4b');
echo "  ✓ M3.4 — conteúdo + 2 vídeos (presencial + EAD gravada)\n";

// M3.5 (EAD assíncrono — já tem quiz + fórum + 2 tarefas)
set_section_summary($c, 5,
    '<p>Leituras complementares. Videoaula gravada: <em>O IPER e Você</em>. Fórum de discussão: '
    . 'compartilhando o Plano de Ação. Atividade reflexiva: aplicação do protocolo em caso real. '
    . 'Avaliação final de 15 questões.</p>'
    . '<ul>'
    . '<li>LEITURA 1 — Resolução CNJ nº 492/2023 (texto integral) — 30 min</li>'
    . '<li>LEITURA 2 — Apostila: Povo Quilombola, Equidade Racial e IPER (Unidade 5) — 40 min</li>'
    . '<li>VIDEOAULA — "O IPER e Você: como cada servidor contribui para o índice" — 30 min</li>'
    . '<li>FÓRUM — Compartilhe UMA ação concreta de equidade racial para sua ZE — 45 min</li>'
    . '<li>ATIVIDADE REFLEXIVA — Análise de caso com Protocolo de Perspectiva Racial — 45 min</li>'
    . '<li>AVALIAÇÃO FINAL — 15 questões objetivas, aprovação mínima 60% — 45 min</li>'
    . '</ul>'
    . '<p><small>⏱ 4h assíncrono &nbsp;|&nbsp; 📡 EAD assíncrono (AVA)</small></p>'
);
mk_label_activity($c, 5, video_placeholder(
    'Módulo 3.5 — Videoaula: O IPER e Você',
    'Esta videoaula faz parte das atividades assíncronas do Eixo 3. Ela conecta tudo o que você '
    . 'aprendeu nos módulos anteriores ao indicador que o TRE/PA vai usar para medir seu compromisso '
    . 'institucional com a equidade racial: o IPER. Assista antes de começar as atividades deste módulo.',
    'video-m3.5'
), 'video-m3.5');
echo "  ✓ M3.5 — sumário detalhado + vídeo placeholder\n";

// ---------------------------------------------------------------------------
// Purge course caches
// ---------------------------------------------------------------------------
foreach ($CID as $sn => $cid) {
    rebuild_course_cache($cid, true);
}
echo "\n✓ Caches dos cursos purgados\n";

echo "\n=== Concluído! ===\n";
echo "Sumários de seções e placeholders de vídeo adicionados em todos os 5 Eixos.\n";
echo "Total de vídeos placeholder: 17 (M1.1–M1.4, M2A.1–M2A.4, M2B.1–M2B.3, M2C.1–M2C.3, M3.1–M3.5)\n";
