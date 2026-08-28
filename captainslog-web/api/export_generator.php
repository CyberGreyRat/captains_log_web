<?php
// api/export_generator.php - zentraler Exporter für alle Berichte

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/report_common.php';

use Mpdf\Mpdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

function ex_selected(array $data, string $key): bool
{
    return in_array($key, $data['content'] ?? [], true);
}
function ex_marks(array $values): string
{
    return implode(',', array_fill(0, count($values), '?'));
}
function ex_safe(string $value): string
{
    return trim((string) preg_replace('/[^A-Za-z0-9._-]+/u', '_', $value), '_') ?: 'Projekt';
}
function ex_done(array $row): bool
{
    return (int) ($row['progress_pct'] ?? 0) >= 100 || in_array(report_v($row, 'status'), ['closed', 'approved', 'rejected'], true);
}
function ex_clean_description(string $description): string
{
    $lines = [];
    foreach (preg_split('/\R/u', $description) ?: [] as $line) {
        if (preg_match('/^\s*--\s*\S/u', $line))
            continue;
        $lines[] = rtrim($line);
    }
    return trim(implode("\n", $lines));
}
function ex_tokens(string $text, array $tokens): string
{
    foreach ($tokens as $key => $value)
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    return $text;
}
function ex_group(array $requirement): string
{
    return match (strtoupper(report_v($requirement, 'type', 'SYS'))) {
        'HRS' => 'Elektronik / Hardware', 'SRS', 'SWC' => 'Software', 'USR' => 'Nutzeranforderungen',
        'SEC' => 'Security', 'TC', 'TR' => 'Verifikation und Test', 'RISK' => 'Risiken', 'ENV' => 'Umwelt',
        default => 'Systemanforderungen'
    };
}
function ex_rich_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    if (trim($text) === '')
        return '';
    $parts = preg_split('/```(?:[A-Za-z0-9_+.#-]+)?\s*\n?(.*?)```/su', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false)
        return nl2br(report_h($text));
    $html = '';
    foreach ($parts as $index => $part) {
        if ($index % 2 === 1) {
            $html .= '<div class="code-block"><pre>' . report_h(trim($part, "\n")) . '</pre></div>';
        } else {
            $escaped = report_h($part);
            $escaped = preg_replace_callback('/`([^`\n]+)`/u', static fn(array $m): string => '<code class="inline-code">' . $m[1] . '</code>', $escaped) ?? $escaped;
            $html .= nl2br($escaped);
        }
    }
    return $html;
}

/**
 * Shortens titles only inside the PDF table of contents.
 * The complete title remains unchanged in the report body.
 */
function ex_toc_title(string $title, int $maximumLength = 105): string
{
    $title = preg_replace('/\s+/u', ' ', trim($title)) ?? trim($title);

    if (mb_strlen($title) <= $maximumLength) {
        return $title;
    }

    return rtrim(mb_substr($title, 0, $maximumLength - 3)) . '...';
}

/**
 * mPDF reads the encoded strong tag inside the TOC entry.
 * This keeps only the issue ID bold while the title stays normal.
 */
function ex_issue_toc_label(string $issueKey, string $title): string
{
    return '&lt;strong class=&quot;toc-issue-key&quot;&gt;'
        . report_h($issueKey)
        . '&lt;/strong&gt; '
        . report_h(ex_toc_title($title));
}

function ex_settings(PDO $pdo, string $projectId): array
{
    try {
        $q = $pdo->prepare('SELECT * FROM project_report_settings WHERE project_id=?');
        $q->execute([$projectId]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}
function ex_logo_data(array $settings): string
{
    $relative = report_v($settings, 'logo_path');
    $path = __DIR__ . '/../' . $relative;
    if ($relative === '' || !is_file($path))
        return '';
    return 'data:' . (mime_content_type($path) ?: 'image/png') . ';base64,' . base64_encode((string) file_get_contents($path));
}
function ex_layout(array $project, array $settings, string $reportName): array
{
    $accent = preg_match('/^#[0-9a-f]{6}$/i', report_v($settings, 'accent_color')) ? report_v($settings, 'accent_color') : '#1f4e79';
    $company = report_v($settings, 'company_name', 'EPSa - Elektronik & Präzisionsbau Saalfeld GmbH');
    $tokens = ['company' => $company, 'project' => report_v($project, 'name'), 'report' => $reportName, 'date' => date('d.m.Y'), 'classification' => report_v($settings, 'classification')];
    return [$accent, $company, ex_tokens(report_v($settings, 'header_text', '{company} | {project} | {report}'), $tokens), ex_tokens(report_v($settings, 'footer_text', '{classification} | {date} | Seite {page} von {pages}'), $tokens), ex_logo_data($settings)];
}
function ex_css(string $accent): string
{
    return <<<'CSS'
@page {
    margin: 32mm 16mm 20mm 16mm;
    header: reportHeader;
    footer: reportFooter;
}

body {
    font-family: Arial, sans-serif;
    font-size: 9pt;
    line-height: 1.28;
    color: #111;
}

h1 {
    margin: 0 0 7mm;
    padding-bottom: 4mm;
    border-bottom: 1.2pt solid #17365d;
    color: #17365d;
    font-size: 18pt;
}

h2 {
    margin: 7mm 0 3mm;
    color: #17365d;
    font-size: 13pt;
    page-break-after: avoid;
}

h3 {
    margin: 5mm 0 2mm;
    color: #17365d;
    font-size: 10pt;
    page-break-after: avoid;
}

.report-header {
    height: 15mm;
    padding: 2.8mm 0 4mm;
    border-bottom: .6pt solid #777;
    color: #444;
    font-size: 7.5pt;
    line-height: 1.2;
    vertical-align: middle;
}

.report-header img {
    width: auto;
    height: 8.5mm;
    margin: 1.2mm 8mm 1.2mm 0;
    vertical-align: middle;
}

.report-footer {
    height: 8mm;
    padding-top: 2.4mm;
    border-top: .4pt solid #aaa;
    color: #666;
    font-size: 7pt;
    line-height: 1.15;
    text-align: center;
}

.cover {
    padding-top: 48mm;
    text-align: center;
}

.cover h1 {
    margin-bottom: 8mm;
    border: 0;
    font-size: 22pt;
    letter-spacing: 1.5pt;
}

.cover .project {
    margin: 5mm 0 20mm;
    color: #17365d;
    font-size: 17pt;
    font-weight: bold;
}

.document-data {
    width: 78%;
    margin: 0 auto;
    border-collapse: collapse;
}

.document-data th,
.document-data td {
    padding: 2.2mm 3mm;
    border: .5pt solid #999;
    text-align: left;
}

.document-data th {
    width: 34%;
    background: #eee;
}

.meta {
    margin: 0 0 6mm;
    padding: 0;
}

.card {
    margin: 0 0 7mm;
    padding: 4mm 0 0;
    border: 0;
    border-top: .6pt solid #999;
    page-break-inside: auto;
}

.task-card {
    margin-top: 0;
    padding-top: 0;
    border-top: 0;
}

.task-card + .task-card {
    margin-top: 2mm;
    padding-top: 6mm;
    border-top: .6pt solid #999;
}

.category {
    color: #555;
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
}

.key {
    color: #17365d;
    font-family: monospace;
    font-weight: bold;
}

.identity {
    width: 100%;
    border-collapse: collapse;
}

.identity td {
    vertical-align: middle;
}

.identity .key-cell {
    width: 28mm;
    padding-right: 8mm;
}

.facts {
    width: 100%;
    margin: 2mm 0 4mm;
    border-collapse: collapse;
}

.facts th,
.facts td {
    padding: 1.8mm 2.5mm;
    border: .5pt solid #aaa;
    text-align: left;
}

.facts th {
    width: 32mm;
    background: #eee;
    font-size: 8pt;
}

.box {
    margin: 4mm 0;
    padding: 0;
}

.box strong {
    display: block;
    margin-bottom: 1.5mm;
    color: #17365d;
}

.customer,
.internal,
.solution {
    background: none;
}

.code-block {
    margin: 2.5mm 0 3mm;
    padding: 2.5mm 3mm;
    border: .5pt solid #999;
    border-left: 2pt solid #555;
    background: #f2f2f2;
    page-break-inside: avoid;
}

.code-block pre {
    margin: 0;
    color: #111;
    font-family: DejaVu Sans Mono, Courier New, monospace;
    font-size: 7.6pt;
    line-height: 1.28;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.inline-code {
    padding: .2mm .8mm;
    background: #eee;
    font-family: DejaVu Sans Mono, Courier New, monospace;
    font-size: 8pt;
}

.summary {
    margin: 3mm 0;
}

.track {
    height: 3mm;
    margin-bottom: 4mm;
    background: #ddd;
}

.track div {
    height: 3mm;
    background: #555;
}

.check-table,
.spec {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.check-table td,
.spec th,
.spec td {
    padding: 1.8mm 2mm;
    border: .5pt solid #999;
    vertical-align: top;
}

.spec th {
    background: #e8e8e8;
    color: #111;
    text-align: left;
}

.check-table .check-cell {
    width: 12mm;
    padding-right: 4mm;
    text-align: center;
}

.check-table .title-cell {
    padding-left: 4mm;
}

.check-table .percent-cell {
    width: 18mm;
    text-align: right;
}

.trace {
    padding: 2mm 0;
    border-bottom: .4pt solid #bbb;
}

.trace small {
    display: block;
    margin-top: 1mm;
    color: #555;
}

.spec {
    font-size: 7.5pt;
}

.spec .id { width: 9%; }
.spec .description { width: 39%; }
.spec .evidence { width: 22%; }
.spec .reference { width: 13%; }
.spec .notes { width: 17%; }

thead {
    display: table-header-group;
}

tr {
    page-break-inside: avoid;
}

/* Table of contents */
.toc-title {
    margin: 0 0 7mm;
    padding-bottom: 3mm;
    border-bottom: 1pt solid #17365d;
    color: #17365d;
    font-size: 16pt;
    font-weight: normal;
    font-style: normal;
}

.mpdf_toc,
.mpdf_toc a,
.mpdf_toc_level_1,
.mpdf_toc_t_level_1,
.mpdf_toc_p_level_1 {
    color: #111 !important;
    font-family: Arial, sans-serif !important;
    font-size: 8.2pt !important;
    font-weight: normal !important;
    font-style: normal !important;
    line-height: 1.22 !important;
    text-decoration: none !important;
}

.mpdf_toc {
    width: 100%;
    margin: 0;
    padding: 0;
}

.mpdf_toc_level_1 {
    margin-right: 0 !important;
    margin-left: 0 !important;
    padding-right: 0 !important;
    padding-left: 0 !important;
}

.mpdf_toc_t_level_1 {
    padding-right: 3mm !important;
}

.mpdf_toc_p_level_1 {
    width: 8mm !important;
    min-width: 8mm !important;
    text-align: right !important;
    white-space: nowrap !important;
}

.toc-issue-key,
.mpdf_toc .toc-issue-key {
    font-weight: bold !important;
    font-style: normal !important;
}
CSS;
}

function ex_html_frame(string $body, string $header, string $footer, string $logo, string $accent): string
{
    $headerHtml = '<htmlpageheader name="reportHeader"><div class="report-header">' . ($logo !== '' ? '<img src="' . $logo . '" alt="Logo">' : '') . report_h($header) . '</div></htmlpageheader>';
    $footerHtml = '<htmlpagefooter name="reportFooter"><div class="report-footer">' . report_h(str_replace(['{page}', '{pages}'], ['{PAGENO}', '{nbpg}'], $footer)) . '</div></htmlpagefooter>';
    return '<html><head><meta charset="UTF-8"><style>' . ex_css($accent) . '</style></head><body>' . $headerHtml . $footerHtml . $body . '</body></html>';
}
function ex_issue_html(PDO $pdo, array $project, array $settings, array $data): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $data['selected_ids'] ?? []))));
    if (!$ids)
        throw new RuntimeException('Keine Issues ausgewählt.');
    $q = $pdo->prepare('SELECT i.*,u.username FROM issues i LEFT JOIN users u ON u.id=i.assignee_user_id WHERE i.project_id=? AND i.id IN (' . ex_marks($ids) . ') ORDER BY i.issue_key');
    $q->execute(array_merge([report_v($project, 'id')], $ids));
    $issues = $q->fetchAll(PDO::FETCH_ASSOC);
    [$accent, $company, $header, $footer, $logo] = ex_layout($project, $settings, 'Issue- und Fehler-Report');
    $body = '<div class="cover"><h1>Issue- und Fehler-Report</h1><div class="project">' . report_h(report_v($project, 'name')) . '</div><table class="document-data"><tr><th>Stand</th><td>' . date('d.m.Y') . '</td></tr><tr><th>Berichtsart</th><td>Issue-Report</td></tr><tr><th>Klassifizierung</th><td>' . report_h(report_v($settings, 'classification', 'Vertraulich')) . '</td></tr></table></div><tocpagebreak links="on" toc-preHTML="&lt;div class=&quot;toc-title&quot;&gt;Inhaltsverzeichnis&lt;/div&gt;" /><h1>Issue- und Fehler-Report</h1><div class="meta"><strong>Projekt:</strong> ' . report_h(report_v($project, 'name')) . ' &nbsp; <strong>Stand:</strong> ' . date('d.m.Y H:i') . '</div>';
    foreach ($issues as $issue) {
        $body .= '<section class="card"><table class="identity"><tr><td class="key-cell"><span class="key">' . report_h(report_v($issue, 'issue_key')) . '</span></td><td class="state-cell">' . (ex_selected($data, 'status') ? report_h(report_v($issue, 'status')) : '') . '</td></tr></table><tocentry content="' . ex_issue_toc_label(report_v($issue, 'issue_key'), report_v($issue, 'title')) . '" level="1" /><h2>' . report_h(report_v($issue, 'title')) . '</h2>';
        $facts = [];
        if (ex_selected($data, 'priority'))
            $facts[] = ['Priorität', report_v($issue, 'priority', '-')];
        if (ex_selected($data, 'category'))
            $facts[] = ['Kategorie', report_v($issue, 'category', '-')];
        if (ex_selected($data, 'assignee'))
            $facts[] = ['Zuständig', report_v($issue, 'username', 'Nicht zugewiesen')];
        if (ex_selected($data, 'dates'))
            $facts[] = ['Termin', report_v($issue, 'reported_at', '-') . ' bis ' . report_v($issue, 'due_date', '-')];
        if ($facts) {
            $body .= '<table class="facts">';
            foreach ($facts as $fact)
                $body .= '<tr><th>' . report_h($fact[0]) . '</th><td>' . report_h($fact[1]) . '</td></tr>';
            $body .= '</table>';
        }
        if (ex_selected($data, 'description'))
            $body .= '<div class="box"><strong>Problembeschreibung</strong><br>' . ex_rich_text(report_v($issue, 'description', '-')) . '</div>';
        if (ex_selected($data, 'customer_communication'))
            $body .= '<div class="box customer"><strong>Kundenkommunikation</strong><br>' . ex_rich_text(report_v($issue, 'external_response', '-')) . '</div>';
        if (ex_selected($data, 'internal_response'))
            $body .= '<div class="box internal"><strong>Interne Bearbeitung</strong><br>' . ex_rich_text(report_v($issue, 'internal_response', '-')) . '</div>';
        if (ex_selected($data, 'resolution'))
            $body .= '<div class="box solution"><strong>Lösung / Abschluss</strong><br>' . ex_rich_text(report_v($issue, 'resolution', '-')) . '</div>';
        if (ex_selected($data, 'requirements')) {
            $q = $pdo->prepare('SELECT r.req_key,r.title,r.review_status FROM issue_requirements x JOIN requirements r ON r.id=x.requirement_id WHERE x.issue_id=?');
            $q->execute([$issue['id']]);
            $linkedRequirements = $q->fetchAll(PDO::FETCH_ASSOC);
            if ($linkedRequirements)
                $body .= '<h3>Verknüpfte Anforderungen</h3>';
            foreach ($linkedRequirements as $r)
                $body .= '<div class="trace"><span class="key">' . report_h(report_v($r, 'req_key')) . '</span>&nbsp;&nbsp;&nbsp;&nbsp;' . report_h(report_v($r, 'title')) . '<small>' . report_h(report_v($r, 'review_status')) . '</small></div>';
        }
        if (ex_selected($data, 'tasks')) {
            $q = $pdo->prepare('SELECT t.wbs_code,t.title,t.progress_pct FROM issue_tasks x JOIN project_tasks t ON t.id=x.task_id WHERE x.issue_id=?');
            $q->execute([$issue['id']]);
            $linkedTasks = $q->fetchAll(PDO::FETCH_ASSOC);
            if ($linkedTasks)
                $body .= '<h3>Verknüpfte Aufgaben im Projektplan</h3>';
            foreach ($linkedTasks as $t)
                $body .= '<div class="trace"><span class="key">' . report_h(report_v($t, 'wbs_code')) . '</span>&nbsp;&nbsp;&nbsp;&nbsp;' . report_h(report_v($t, 'title')) . '<small>' . (int) $t['progress_pct'] . '%</small></div>';
        }
        if (ex_selected($data, 'comments')) {
            $q = $pdo->prepare('SELECT c.*,u.username FROM issue_comments c LEFT JOIN users u ON u.id=c.created_by WHERE c.issue_id=? ORDER BY c.created_at');
            $q->execute([$issue['id']]);
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $c)
                $body .= '<div class="box internal"><strong>' . report_h(report_v($c, 'comment_type', 'Kommentar')) . ' · ' . report_h(report_v($c, 'username', 'Intern')) . '</strong><br>' . ex_rich_text(report_v($c, 'comment_text')) . '</div>';
        }
        if (ex_selected($data, 'source'))
            $body .= '<p><small>Quelle: ' . report_h(report_v($issue, 'source_document', '-')) . ' · Zeile ' . report_h(report_v($issue, 'source_row', '-')) . '</small></p>';
        $body .= '</section>';
    }
    return [ex_html_frame($body, $header, $footer, $logo, $accent), 'issue_report'];
}
function ex_status_html(PDO $pdo, array $project, array $settings, array $data): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $data['selected_ids'] ?? []))));
    if (!$ids)
        throw new RuntimeException('Keine Aufgaben ausgewählt.');
    $q = $pdo->prepare('SELECT * FROM project_tasks WHERE project_id=? AND parent_id IS NULL AND id IN (' . ex_marks($ids) . ') ORDER BY wbs_code,id');
    $q->execute(array_merge([report_v($project, 'id')], $ids));
    $tasks = $q->fetchAll(PDO::FETCH_ASSOC);
    [$accent, $company, $header, $footer, $logo] = ex_layout($project, $settings, 'Meilenstein- und Aufgaben-Protokoll');
    $body = '<div class="cover"><h1>Meilenstein- und Aufgaben-Protokoll</h1><div class="project">' . report_h(report_v($project, 'name')) . '</div><table class="document-data"><tr><th>Stand</th><td>' . date('d.m.Y') . '</td></tr><tr><th>Berichtsart</th><td>Aufgaben-Protokoll</td></tr><tr><th>Klassifizierung</th><td>' . report_h(report_v($settings, 'classification', 'Vertraulich')) . '</td></tr></table></div><tocpagebreak links="on" toc-preHTML="&lt;div class=&quot;toc-title&quot;&gt;Inhaltsverzeichnis&lt;/div&gt;" /><h1>Meilenstein- und Aufgaben-Protokoll</h1><div class="meta"><strong>Projekt:</strong> ' . report_h(report_v($project, 'name')) . ' &nbsp; <strong>Stand:</strong> ' . date('d.m.Y H:i') . '</div>';
    foreach ($tasks as $task) {
        $id = (int) $task['id'];
        $q = $pdo->prepare('SELECT * FROM project_tasks WHERE parent_id=? ORDER BY id');
        $q->execute([$id]);
        $subs = $q->fetchAll(PDO::FETCH_ASSOC);
        $keys = json_decode(report_v($task, 'linked_reqs', '[]'), true) ?: [];
        $reqs = [];
        if ($keys) {
            $q = $pdo->prepare('SELECT * FROM requirements WHERE project_id=? AND req_key IN (' . ex_marks($keys) . ') ORDER BY req_key');
            $q->execute(array_merge([report_v($project, 'id')], $keys));
            $reqs = $q->fetchAll(PDO::FETCH_ASSOC);
        }
        $q = $pdo->prepare('SELECT i.*,u.username FROM issue_tasks x JOIN issues i ON i.id=x.issue_id LEFT JOIN users u ON u.id=i.assignee_user_id WHERE x.task_id=? ORDER BY i.issue_key');
        $q->execute([$id]);
        $issues = $q->fetchAll(PDO::FETCH_ASSOC);
        $doneSubs = count(array_filter($subs, 'ex_done'));
        $doneReq = count(array_filter($reqs, fn($r) => report_v($r, 'review_status') === 'Geprüft & Freigegeben'));
        $doneIssues = count(array_filter($issues, 'ex_done'));
        $progress = (int) $task['progress_pct'];
        $body .= '<section class="card task-card"><div class="category">' . report_h(report_v($task, 'category', 'Aufgabe')) . '</div><tocentry content="' . report_h(report_v($task, 'wbs_code', '-') . ' ' . ex_toc_title(report_v($task, 'title'))) . '" level="1" /><h2><span class="key">' . report_h(report_v($task, 'wbs_code', '-')) . '</span>&nbsp;&nbsp;&nbsp;&nbsp;' . report_h(report_v($task, 'title')) . '</h2>';
        $taskFacts = [['Kategorie', report_v($task, 'category', '-')]];
        if (ex_selected($data, 'assignee'))
            $taskFacts[] = ['Zuständig', report_v($task, 'assignee', 'Nicht zugewiesen')];
        if (ex_selected($data, 'dates'))
            $taskFacts[] = ['Zeitraum', report_v($task, 'start_date', '-') . ' bis ' . report_v($task, 'end_date', '-')];
        $taskFacts[] = ['Fortschritt', $progress . ' %'];
        $body .= '<table class="facts">';
        foreach ($taskFacts as $fact)
            $body .= '<tr><th>' . report_h($fact[0]) . '</th><td>' . report_h($fact[1]) . '</td></tr>';
        $body .= '</table>';
        if (ex_selected($data, 'description')) {
            $description = ex_clean_description(report_v($task, 'description'));
            if ($description !== '')
                $body .= '<p>' . ex_rich_text($description) . '</p>';
        }
        if (ex_selected($data, 'summary'))
            $body .= '<div class="summary"><strong>' . $progress . '%</strong> · ' . $doneSubs . '/' . count($subs) . ' Check &amp; ' . $doneReq . '/' . count($reqs) . ' Reqs &amp; ' . $doneIssues . '/' . count($issues) . ' Issues erledigt</div>';
        if (ex_selected($data, 'progress'))
            $body .= '<div class="track"><div style="width:' . max(0, min(100, $progress)) . '%"></div></div>';
        if (ex_selected($data, 'checklist')) {
            $body .= '<h3>Unteraufgaben / Checkliste</h3><table class="check-table">';
            foreach ($subs as $sub) {
                $done = ex_done($sub);
                $body .= '<tr><td class="check-cell"><span class="checkbox ' . ($done ? 'done' : '') . '">' . ($done ? '✓' : '') . '</span></td><td class="title-cell">' . report_h(report_v($sub, 'title')) . '</td><td class="percent-cell">' . (int) $sub['progress_pct'] . '%</td></tr>';
            }
            $body .= '</table>';
        }
        if (ex_selected($data, 'requirements')) {
            $body .= '<h3>Verknüpfte Anforderungen</h3>';
            foreach ($reqs as $r)
                $body .= '<div class="trace"><span class="key">' . report_h(report_v($r, 'req_key')) . '</span>&nbsp;&nbsp;&nbsp;&nbsp;' . report_h(report_v($r, 'title')) . '<small>' . report_h(report_v($r, 'review_status')) . (report_v($r, 'source_reference') !== '' ? ' · Quelle ' . report_h(report_v($r, 'source_reference')) : '') . '</small></div>';
        }
        if (ex_selected($data, 'issues')) {
            $body .= '<h3>Verknüpfte Issues</h3>';
            foreach ($issues as $i)
                $body .= '<div class="trace"><span class="key">' . report_h(report_v($i, 'issue_key')) . '</span>&nbsp;&nbsp;&nbsp;&nbsp;' . report_h(report_v($i, 'title')) . '<small>' . report_h(report_v($i, 'status')) . ' · ' . report_h(report_v($i, 'username', 'Nicht zugewiesen')) . '</small></div>';
        }
        if (ex_selected($data, 'effort'))
            $body .= '<div class="box internal"><strong>Interner Aufwand:</strong> ' . report_h(report_v($task, 'effort_mt', '0')) . ' MT</div>';
        if (ex_selected($data, 'internal_details'))
            $body .= '<div class="box internal"><strong>Interne Details:</strong> Leistung ' . report_h(report_v($task, 'performance_pct', '100')) . '% · Auto-Fortschritt ' . (!empty($task['is_auto_progress']) ? 'Ja' : 'Nein') . '</div>';
        $body .= '</section>';
    }
    return [ex_html_frame($body, $header, $footer, $logo, $accent), 'status_report'];
}
function ex_spec_html(PDO $pdo, array $project, array $settings): array
{
    $q = $pdo->prepare('SELECT * FROM requirements WHERE project_id=? ORDER BY type,COALESCE(serial_number,id),id');
    $q->execute([report_v($project, 'id')]);
    $reqs = $q->fetchAll(PDO::FETCH_ASSOC);
    [$accent, $company, $header, $footer, $logo] = ex_layout($project, $settings, 'Pflichtenheft');
    $groups = [];
    foreach ($reqs as $r)
        $groups[ex_group($r)][] = $r;
    $body = '<div style="text-align:center;padding-top:45mm"><h1 style="border:0;letter-spacing:5px">P f l i c h t e n h e f t</h1><p>für das Projekt</p><h1 style="border:0">' . report_h(report_v($project, 'name')) . '</h1></div><pagebreak><h1>1. Einführung</h1><p>' . nl2br(report_h(report_v($project, 'description'))) . '</p><h1>2. Pflichten und Anforderungen</h1>';
    $n = 1;
    foreach ($groups as $name => $items) {
        $body .= '<h2>2.' . $n++ . ' ' . report_h($name) . '</h2><table class="spec"><thead><tr><th class="id">ID</th><th class="description">Spezifikation</th><th class="evidence">Nachweis</th><th class="reference">Verweis</th><th class="notes">Bemerkung</th></tr></thead><tbody>';
        foreach ($items as $r) {
            $spec = report_v($r, 'title');
            if (report_v($r, 'description') !== '' && report_v($r, 'description') !== $spec)
                $spec .= "\n" . report_v($r, 'description');
            $parents = json_decode(report_v($r, 'parents', '[]'), true) ?: [];
            $children = json_decode(report_v($r, 'children', '[]'), true) ?: [];
            $refs = [];
            if ($parents)
                $refs[] = 'Parents: ' . implode(', ', $parents);
            if ($children)
                $refs[] = 'Children: ' . implode(', ', $children);
            $notes = ['Typ: ' . report_v($r, 'type'), 'Status: ' . report_v($r, 'review_status')];
            if (report_v($r, 'source_reference') !== '')
                $notes[] = 'Quelle-ID: ' . report_v($r, 'source_reference');
            if (report_v($r, 'source_document') !== '')
                $notes[] = 'Dokument: ' . report_v($r, 'source_document');
            if (report_v($r, 'source_page') !== '')
                $notes[] = 'Seite: ' . report_v($r, 'source_page');
            $body .= '<tr><td>' . report_h(report_v($r, 'req_key')) . '</td><td>' . nl2br(report_h($spec)) . '</td><td>' . nl2br(report_h(report_v($r, 'acceptance_criteria', '-'))) . '</td><td>' . nl2br(report_h(implode("\n", $refs))) . '</td><td>' . nl2br(report_h(implode("\n", $notes))) . '</td></tr>';
        }
        $body .= '</tbody></table>';
    }
    return [ex_html_frame($body, $header, $footer, $logo, $accent), 'specification'];
}
function ex_spec_docx(PDO $pdo, array $project, array $settings, array $data, string $path): void
{
    [$accent, $company, $header, $footer, $logo] = ex_layout($project, $settings, 'Pflichtenheft');
    $accent = ltrim($accent, '#');
    $q = $pdo->prepare('SELECT * FROM requirements WHERE project_id=? ORDER BY type,COALESCE(serial_number,id),id');
    $q->execute([report_v($project, 'id')]);
    $reqs = $q->fetchAll(PDO::FETCH_ASSOC);
    $word = new PhpWord();
    $word->setDefaultFontName('Arial');
    $word->setDefaultFontSize(9);
    $section = $word->addSection(['marginTop' => 1350, 'marginBottom' => 1200, 'marginLeft' => 1000, 'marginRight' => 1000]);
    $head = $section->addHeader();
    $table = $head->addTable(['width' => 100 * 50, 'unit' => 'pct', 'cellMargin' => 120, 'borderBottomSize' => 6, 'borderBottomColor' => $accent]);
    $table->addRow(650);
    $logoCell = $table->addCell(1800);
    $logoPath = report_v($settings, 'logo_path') !== '' ? realpath(__DIR__ . '/../' . report_v($settings, 'logo_path')) : false;
    if ($logoPath && is_file($logoPath))
        $logoCell->addImage($logoPath, ['height' => 32]);
    else
        $logoCell->addText($company, ['bold' => true, 'size' => 7]);
    $textCell = $table->addCell(7200);
    $textCell->addText($header, ['size' => 8, 'bold' => true, 'color' => $accent], ['alignment' => Jc::END]);
    $foot = $section->addFooter();
    $run = $foot->addTextRun(['alignment' => Jc::CENTER]);
    $parts = preg_split('/(\{page\}|\{pages\})/', $footer, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $part) {
        if ($part === '{page}')
            $run->addField('PAGE');
        elseif ($part === '{pages}')
            $run->addField('NUMPAGES');
        else
            $run->addText($part, ['size' => 7, 'color' => '666666']);
    }
    $section->addTextBreak(4);
    $section->addText('P f l i c h t e n h e f t', ['size' => 24, 'bold' => true, 'color' => $accent], ['alignment' => Jc::CENTER]);
    $section->addText('für das Projekt', ['size' => 13], ['alignment' => Jc::CENTER]);
    $section->addText(report_v($project, 'name'), ['size' => 20, 'bold' => true, 'color' => $accent], ['alignment' => Jc::CENTER]);
    $section->addPageBreak();
    $section->addText('Pflichten und Anforderungen', ['size' => 18, 'bold' => true, 'color' => $accent]);
    $groups = [];
    foreach ($reqs as $r)
        $groups[ex_group($r)][] = $r;
    foreach ($groups as $name => $items) {
        $section->addText($name, ['size' => 14, 'bold' => true, 'color' => $accent]);
        $t = $section->addTable(['borderSize' => 5, 'borderColor' => '8FAADC', 'cellMargin' => 70, 'layout' => 'fixed']);
        $row = $t->addRow(null, ['tblHeader' => true]);
        foreach (['ID', 'Spezifikation', 'Nachweis', 'Verweis', 'Bemerkung'] as $caption)
            $row->addCell()->addText($caption, ['bold' => true, 'size' => 8]);
        foreach ($items as $r) {
            $row = $t->addRow(null, ['cantSplit' => true]);
            $spec = report_v($r, 'title') . (report_v($r, 'description') !== '' && report_v($r, 'description') !== report_v($r, 'title') ? "\n" . report_v($r, 'description') : '');
            $parents = json_decode(report_v($r, 'parents', '[]'), true) ?: [];
            $children = json_decode(report_v($r, 'children', '[]'), true) ?: [];
            $refs = implode("\n", array_filter([$parents ? 'Parents: ' . implode(', ', $parents) : '', $children ? 'Children: ' . implode(', ', $children) : '']));
            $notes = implode("\n", array_filter(['Typ: ' . report_v($r, 'type'), 'Status: ' . report_v($r, 'review_status'), report_v($r, 'source_reference') !== '' ? 'Quelle-ID: ' . report_v($r, 'source_reference') : '', report_v($r, 'source_document') !== '' ? 'Dokument: ' . report_v($r, 'source_document') : '']));
            foreach ([[report_v($r, 'req_key'), 7], [$spec, 8], [report_v($r, 'acceptance_criteria', '-'), 7], [$refs, 7], [$notes, 7]] as [$text, $size])
                $row->addCell()->addText($text !== '' ? $text : '-', ['size' => $size]);
        }
    }
    IOFactory::createWriter($word, 'Word2007')->save($path);
}

try {
    $uid = report_user();
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $projectId = report_v($data, 'project_id');
    report_access($pdo, $projectId, $uid);
    $q = $pdo->prepare('SELECT * FROM projects WHERE id=?');
    $q->execute([$projectId]);
    $project = $q->fetch(PDO::FETCH_ASSOC);
    if (!$project)
        throw new RuntimeException('Projekt nicht gefunden.');
    $settings = ex_settings($pdo, $projectId);
    $type = report_v($data, 'type', 'specification');
    $format = report_v($data, 'format', 'pdf');
    if ($format === 'docx') {
        if ($type !== 'specification')
            throw new RuntimeException('Word ist derzeit nur für das Pflichtenheft verfügbar.');
        $tmp = tempnam(sys_get_temp_dir(), 'cl_docx_');
        if ($tmp === false)
            throw new RuntimeException('Temporäre Datei konnte nicht angelegt werden.');
        @unlink($tmp);
        $tmp .= '.docx';
        ex_spec_docx($pdo, $project, $settings, $data, $tmp);
        if (!is_file($tmp) || filesize($tmp) < 2000)
            throw new RuntimeException('DOCX-Datei ist unvollständig.');
        $zip = new ZipArchive();
        $result = $zip->open($tmp, ZipArchive::CHECKCONS);
        if ($result !== true)
            throw new RuntimeException('DOCX-Prüfung fehlgeschlagen. ZIP-Code ' . $result);
        foreach (['[Content_Types].xml', 'word/document.xml'] as $entry)
            if ($zip->locateName($entry) === false)
                throw new RuntimeException('DOCX-Bestandteil fehlt: ' . $entry);
        $zip->close();
        while (ob_get_level() > 0)
            ob_end_clean();
        $name = ex_safe(report_v($project, 'name')) . '_Pflichtenheft_' . date('Ymd_His') . '.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }
    [$html, $suffix] = match ($type) { 'issue_report' => ex_issue_html($pdo, $project, $settings, $data), 'status_report' => ex_status_html($pdo, $project, $settings, $data), default => ex_spec_html($pdo, $project, $settings)};
    $pdf = new Mpdf(['format' => 'A4', 'margin_top' => 32, 'margin_bottom' => 20]);
    $pdf->WriteHTML($html);
    $content = $pdf->Output('', 'S');
    $name = ex_safe(report_v($project, 'name')) . '_' . $suffix . '_' . date('Ymd_His') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
} catch (Throwable $error) {
    while (ob_get_level() > 0)
        ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Exportfehler: ' . $error->getMessage();
}
