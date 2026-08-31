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
    return ex_type_title(report_v($requirement, 'type', 'SYS'));
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


/** Feste, fachliche Reihenfolge für das Pflichtenheft. */
function ex_type_order(string $type): int
{
    $order = ['USR'=>10,'SYS'=>20,'HRS'=>30,'SRS'=>40,'SWC'=>50,'SEC'=>60,'TC'=>70,'TR'=>80,'ENV'=>90,'AST'=>100,'GOAL'=>110,'RISK'=>900];
    return $order[strtoupper($type)] ?? 500;
}
function ex_type_title(string $type): string
{
    return match (strtoupper($type)) {
        'USR'=>'Benutzeranforderungen (USR)', 'SYS'=>'Systemanforderungen (SYS)',
        'HRS'=>'Hardwareanforderungen (HRS)', 'SRS'=>'Softwareanforderungen (SRS)',
        'SWC'=>'Softwarekomponenten (SWC)', 'SEC'=>'Security-Anforderungen (SEC)',
        'TC'=>'Testfälle (TC)', 'TR'=>'Testergebnisse (TR)', 'ENV'=>'Umweltanforderungen (ENV)',
        'AST'=>'Assets (AST)', 'GOAL'=>'Projektziele (GOAL)', default=>'Weitere Anforderungen'
    };
}
function ex_attrs(array $row): array
{
    $raw=$row['attributes'] ?? [];
    if (is_array($raw)) return $raw;
    $decoded=json_decode((string)$raw,true);
    return is_array($decoded)?$decoded:[];
}
/** Zerlegt Kriterien und verbindet sie mit criteria_states. */
function ex_criteria(array $requirement): array
{
    $lines=array_values(array_filter(array_map(static fn($v)=>trim((string)preg_replace('/^-\\s*/u','',$v)),preg_split('/\\R/u',report_v($requirement,'acceptance_criteria'))?:[]),static fn($v)=>$v!==''));
    $states=ex_attrs($requirement)['criteria_states'] ?? [];
    if (is_string($states)) $states=json_decode($states,true)?:[];
    $items=[];$done=0;
    foreach($lines as $index=>$line){$state=$states[$index]??$states[(string)$index]??[];$checked=!empty($state['checked']);if($checked)$done++;$items[]=['text'=>$line,'checked'=>$checked,'by'=>$state['by']??'','date'=>$state['date']??'','note'=>$state['note']??''];}
    return ['items'=>$items,'done'=>$done,'total'=>count($items),'percent'=>count($items)?(int)round($done/count($items)*100):0];
}
/** Rendert Kriterien im Pflichtenheft nur als Soll-Vorgaben. */
function ex_criteria_definition_html(array $requirement): string
{
    $criteria = ex_criteria($requirement);
    if (!$criteria['total']) return '<div class="criteria-empty">Keine Akzeptanzkriterien definiert.</div>';
    $html = '<ul class="criteria-definition">';
    foreach ($criteria['items'] as $item) $html .= '<li>' . report_h($item['text']) . '</li>';
    return $html . '</ul>';
}

function ex_criteria_html(array $requirement): string
{
    $c=ex_criteria($requirement); if(!$c['total'])return '<div class="criteria-empty">Keine Akzeptanzkriterien definiert.</div>';
    $html='<div class="criteria-summary"><strong>'.$c['done'].' von '.$c['total'].' Kriterien erfüllt</strong><span>'.$c['percent'].' %</span></div><div class="criteria-track"><div style="width:'.$c['percent'].'%"></div></div><table class="criteria-table">';
    foreach($c['items'] as $item){$meta=$item['checked']&&($item['by']||$item['date']||$item['note'])?'<small>'.report_h(trim(($item['by']?'Geprüft von '.$item['by'].' ':'').($item['date']?'am '.$item['date'].' ':'').$item['note'])).'</small>':'';$html.='<tr><td class="criteria-mark '.($item['checked']?'done':'open').'">'.($item['checked']?'✓':'○').'</td><td>'.report_h($item['text']).$meta.'</td></tr>';}
    return $html.'</table>';
}
function ex_risk_data(PDO $pdo,string $projectId): array
{
    $q=$pdo->prepare("SELECT * FROM requirements WHERE project_id=? AND type='RISK' AND review_status<>'Archiviert' ORDER BY COALESCE(serial_number,id),id");$q->execute([$projectId]);$risks=$q->fetchAll(PDO::FETCH_ASSOC);
    $linkReq=$pdo->prepare("SELECT l.risk_id,l.link_group,r.req_key,r.title FROM risk_requirement_links l JOIN requirements r ON r.id=l.requirement_id JOIN requirements risk ON risk.id=l.risk_id WHERE risk.project_id=? ORDER BY l.risk_id,l.link_group,r.req_key");$linkReq->execute([$projectId]);$links=[];foreach($linkReq->fetchAll(PDO::FETCH_ASSOC) as $x)$links[(int)$x['risk_id']][$x['link_group']][]=$x;
    foreach($risks as &$risk)$risk['risk_links']=$links[(int)$risk['id']]??[];unset($risk);return $risks;
}
function ex_tree_html(array $requirements): string
{
    // Fachliche Übersicht statt eines irreführenden technischen Dateibaums:
    // Jede USR bildet einen Themenblock. Erreichbare Nachfolger werden nach
    // Anforderungsebene gruppiert. Dadurch sehen SRS nicht wie direkte Kinder
    // der USR aus, wenn zwischen den Ebenen fachlich SYS/HRS liegen.
    $byKey=[];$children=[];
    foreach($requirements as $row)$byKey[report_v($row,'req_key')]=$row;
    foreach($requirements as $row){
        foreach(json_decode(report_v($row,'parents','[]'),true)?:[] as $parentKey){
            if(isset($byKey[$parentKey]))$children[$parentKey][]=report_v($row,'req_key');
        }
    }
    $usr=array_values(array_filter($requirements,static fn($r)=>strtoupper(report_v($r,'type'))==='USR'));
    usort($usr,static fn($a,$b)=>strcmp(report_v($a,'req_key'),report_v($b,'req_key')));
    $globallyShown=[];$html='';
    foreach($usr as $root){
        $rootKey=report_v($root,'req_key');$reachable=[];$queue=$children[$rootKey]??[];$seen=[];
        while($queue){$key=array_shift($queue);if(isset($seen[$key])||!isset($byKey[$key]))continue;$seen[$key]=true;$reachable[]=$byKey[$key];foreach($children[$key]??[] as $next)$queue[]=$next;}
        $html.='<section class="tree-topic"><div class="tree-root"><strong>'.report_h($rootKey).'</strong><span>'.report_h(report_v($root,'title')).'</span><small>'.report_h(report_v($root,'review_status','Neu')).'</small></div>';
        $levels=['SYS'=>'Systemanforderungen','HRS'=>'Hardwareanforderungen','SRS'=>'Softwareanforderungen','SWC'=>'Softwarekomponenten','SEC'=>'Security-Anforderungen','TC'=>'Testfälle','TR'=>'Testergebnisse'];
        foreach($levels as $type=>$label){
            $items=array_values(array_filter($reachable,static fn($r)=>strtoupper(report_v($r,'type'))===$type));
            if(!$items)continue;
            $html.='<div class="tree-level"><div class="tree-level-title">├─ '.$label.'</div>';
            foreach($items as $index=>$item){$key=report_v($item,'req_key');$globallyShown[$key]=true;$last=$index===array_key_last($items);$html.='<div class="tree-item"><span class="tree-lines">│&nbsp;&nbsp;'.($last?'└─':'├─').'</span><strong>'.report_h($key).'</strong><span>'.report_h(report_v($item,'title')).'</span><small>'.report_h(report_v($item,'review_status','Neu')).'</small></div>';}
            $html.='</div>';
        }
        $html.='</section>';$globallyShown[$rootKey]=true;
    }
    $unassigned=array_values(array_filter($requirements,static fn($r)=>!isset($globallyShown[report_v($r,'req_key')])));
    if($unassigned){$html.='<section class="tree-topic"><div class="tree-root"><strong>Weitere Anforderungen</strong><span>Keine eindeutige Zuordnung zu einer Benutzeranforderung</span></div>';$groups=[];foreach($unassigned as $r)$groups[strtoupper(report_v($r,'type','SYS'))][]=$r;uksort($groups,static fn($a,$b)=>ex_type_order($a)<=>ex_type_order($b));foreach($groups as $type=>$items){$html.='<div class="tree-level"><div class="tree-level-title">├─ '.report_h(ex_type_title($type)).'</div>';foreach($items as $i=>$item)$html.='<div class="tree-item"><span class="tree-lines">│&nbsp;&nbsp;'.($i===array_key_last($items)?'└─':'├─').'</span><strong>'.report_h(report_v($item,'req_key')).'</strong><span>'.report_h(report_v($item,'title')).'</span><small>'.report_h(report_v($item,'review_status','Neu')).'</small></div>';$html.='</div>';}$html.='</section>';}
    return $html?:'<p>Keine Anforderungen vorhanden.</p>';
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
.criteria-definition { margin: 0; padding-left: 5mm; } .criteria-definition li { margin: 0 0 1.5mm; padding-left: 1mm; line-height: 1.35; }
.context-card { margin: 0 0 5mm; padding: 3mm; border: .6pt solid #aab4c0; page-break-inside: avoid; } .context-block { margin-top: 3mm; line-height: 1.35; } .context-block > strong { display: block; margin-bottom: 1.2mm; color: #17365d; } .story-sentence { padding: 3mm; border-left: 2pt solid #17365d; background: #eef3f8; line-height: 1.45; } .context-empty { color: #777; font-style: italic; } 
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
    $ids=array_values(array_unique(array_filter(array_map('intval',$data['selected_ids']??[]))));if(!$ids)throw new RuntimeException('Keine Aufgaben ausgewählt.');
    $q=$pdo->prepare('SELECT * FROM project_tasks WHERE project_id=? AND parent_id IS NULL AND id IN ('.ex_marks($ids).') ORDER BY wbs_code,id');$q->execute(array_merge([report_v($project,'id')],$ids));$tasks=$q->fetchAll(PDO::FETCH_ASSOC);
    [$accent,$company,$header,$footer,$logo]=ex_layout($project,$settings,'Meilenstein- und Aufgaben-Protokoll');
    $body='<div class="cover"><h1>Meilenstein- und Aufgaben-Protokoll</h1><div class="project">'.report_h(report_v($project,'name')).'</div><table class="document-data"><tr><th>Stand</th><td>'.date('d.m.Y').'</td></tr><tr><th>Berichtsart</th><td>Separates Aufgaben-Protokoll</td></tr></table></div><pagebreak/><h1>Aufgaben-Protokoll</h1>';
    foreach($tasks as $task){$id=(int)$task['id'];$q=$pdo->prepare('SELECT * FROM project_tasks WHERE parent_id=? ORDER BY id');$q->execute([$id]);$subs=$q->fetchAll(PDO::FETCH_ASSOC);$keys=json_decode(report_v($task,'linked_reqs','[]'),true)?:[];$reqs=[];if($keys){$q=$pdo->prepare('SELECT * FROM requirements WHERE project_id=? AND req_key IN ('.ex_marks($keys).') ORDER BY req_key');$q->execute(array_merge([report_v($project,'id')],$keys));$reqs=$q->fetchAll(PDO::FETCH_ASSOC);} $q=$pdo->prepare('SELECT i.*,u.username FROM issue_tasks x JOIN issues i ON i.id=x.issue_id LEFT JOIN users u ON u.id=i.assignee_user_id WHERE x.task_id=? ORDER BY i.issue_key');$q->execute([$id]);$issues=$q->fetchAll(PDO::FETCH_ASSOC);
        $body.='<section class="card task-card"><div class="category">'.report_h(report_v($task,'category','Aufgabe')).'</div><h2>'.report_h(report_v($task,'wbs_code','-')).' · '.report_h(report_v($task,'title')).'</h2><table class="facts"><tr><th>Zuständig</th><td>'.report_h(report_v($task,'assignee','Nicht zugewiesen')).'</td><th>Fortschritt</th><td>'.(int)$task['progress_pct'].' %</td></tr><tr><th>Zeitraum</th><td colspan="3">'.report_h(report_v($task,'start_date','-').' bis '.report_v($task,'end_date','-')).'</td></tr></table>';
        if(ex_selected($data,'description')&&ex_clean_description(report_v($task,'description'))!=='')$body.='<div class="box"><strong>Beschreibung</strong>'.ex_rich_text(ex_clean_description(report_v($task,'description'))).'</div>';
        if(ex_selected($data,'progress'))$body.='<div class="track"><div style="width:'.max(0,min(100,(int)$task['progress_pct'])).'%"></div></div>';
        if(ex_selected($data,'checklist')&&$subs){$body.='<h3>Unteraufgaben / Checkliste</h3><table class="check-table">';foreach($subs as $sub)$body.='<tr><td class="check-cell">'.(ex_done($sub)?'✓':'○').'</td><td class="title-cell">'.report_h(report_v($sub,'title')).'</td><td class="percent-cell">'.(int)$sub['progress_pct'].' %</td></tr>';$body.='</table>';}
        if(ex_selected($data,'requirements')){$body.='<h3 class="requirement-heading">Verknüpfte Anforderungen</h3>';if(!$reqs)$body.='<p class="criteria-empty">Keine Anforderungen verknüpft.</p>';foreach($reqs as $r){$c=ex_criteria($r);$body.='<article class="requirement-card"><h3><span class="key">'.report_h(report_v($r,'req_key')).'</span> '.report_h(report_v($r,'title')).'</h3><div class="requirement-meta">Typ: '.report_h(report_v($r,'type')).' · Status: '.report_h(report_v($r,'review_status')).' · Kriterien: '.$c['done'].'/'.$c['total'].'</div>'.ex_criteria_html($r).'</article>';}}
        if(ex_selected($data,'issues')&&$issues){$body.='<h3>Verknüpfte Issues</h3>';foreach($issues as $i)$body.='<div class="trace"><strong>'.report_h(report_v($i,'issue_key')).'</strong> '.report_h(report_v($i,'title')).'<small>'.report_h(report_v($i,'status')).' · '.report_h(report_v($i,'username','Nicht zugewiesen')).'</small></div>';}
        $body.='</section>';
    }
    return [ex_html_frame($body,$header,$footer,$logo,$accent),'status_report'];
}
function ex_context_data(PDO $pdo, string $projectId): array
{
    $q=$pdo->prepare("SELECT * FROM requirements WHERE project_id=? AND type='GOAL' AND review_status<>'Archiviert' ORDER BY COALESCE(serial_number,id),id");$q->execute([$projectId]);$goals=$q->fetchAll(PDO::FETCH_ASSOC);
    $q=$pdo->prepare('SELECT * FROM user_stories WHERE project_id=? ORDER BY us_key,id');$q->execute([$projectId]);$stories=$q->fetchAll(PDO::FETCH_ASSOC);
    $q=$pdo->prepare('SELECT * FROM use_cases WHERE project_id=? ORDER BY uc_key,id');$q->execute([$projectId]);$cases=$q->fetchAll(PDO::FETCH_ASSOC);
    return ['goals'=>$goals,'stories'=>$stories,'cases'=>$cases];
}
function ex_goal_html(array $g): string
{
    $a=ex_attrs($g);$h='<article class="context-card"><h3><span class="key">'.report_h(report_v($g,'req_key')).'</span> '.report_h(report_v($g,'title')).'</h3><div class="context-block"><strong>Zielbeschreibung</strong>'.ex_rich_text(report_v($g,'description',report_v($g,'title'))).'</div>';
    $benefit=(string)($a['benefit']??report_v($g,'rationale'));if(trim($benefit)!=='')$h.='<div class="context-block"><strong>Begründung / Nutzen</strong>'.ex_rich_text($benefit).'</div>';
    $criteria=(string)($a['success_criteria']??report_v($g,'acceptance_criteria'));if(trim($criteria)!=='')$h.='<div class="context-block"><strong>Erfolgskriterien</strong>'.ex_rich_text($criteria).'</div>';return $h.'</article>';
}
function ex_story_html(array $x): string
{
    $h='<article class="context-card"><h3><span class="key">'.report_h(report_v($x,'us_key')).'</span> '.report_h(report_v($x,'title')).'</h3><div class="story-sentence"><strong>Als</strong> '.report_h(report_v($x,'us_role','-')).' <strong>möchte ich</strong> '.report_h(report_v($x,'us_action','-')).', <strong>so dass</strong> '.report_h(report_v($x,'us_benefit','-')).'.</div>';$c=trim(report_v($x,'acceptance_criteria'));if($c!==''){$h.='<div class="context-block"><strong>Akzeptanzkriterien als Soll-Vorgaben</strong><ul class="criteria-definition">';foreach(preg_split('/\\R/u',$c)?:[] as $line){$line=trim((string)preg_replace('/^-\\s*/u','',$line));if($line!=='')$h.='<li>'.report_h($line).'</li>';}$h.='</ul></div>';}return $h.'</article>';
}
function ex_case_html(array $x): string
{
    $h='<article class="context-card"><h3><span class="key">'.report_h(report_v($x,'uc_key')).'</span> '.report_h(report_v($x,'title')).'</h3><table class="facts"><tr><th>Primärer Akteur</th><td>'.report_h(report_v($x,'primary_actor','-')).'</td></tr></table>';foreach([['Vorbedingungen','preconditions'],['Erfolgreicher Standardablauf','main_scenario'],['Alternative Abläufe und Fehlerfälle','alt_scenario']] as $part){$text=report_v($x,$part[1]);if(trim($text)!=='')$h.='<div class="context-block"><strong>'.report_h($part[0]).'</strong>'.ex_rich_text($text).'</div>';}return $h.'</article>';
}

function ex_spec_html(PDO $pdo, array $project, array $settings): array
{
    $q=$pdo->prepare("SELECT * FROM requirements WHERE project_id=? AND type<>'RISK' ORDER BY COALESCE(serial_number,id),id");$q->execute([report_v($project,'id')]);$reqs=$q->fetchAll(PDO::FETCH_ASSOC);usort($reqs,static fn($a,$b)=>ex_type_order(report_v($a,'type'))<=>ex_type_order(report_v($b,'type')) ?: ((int)($a['serial_number']??$a['id'])<=>(int)($b['serial_number']??$b['id'])));
    $risks=ex_risk_data($pdo,report_v($project,'id'));$contextData=ex_context_data($pdo,report_v($project,'id'));[$accent,$company,$header,$footer,$logo]=ex_layout($project,$settings,'Pflichtenheft');
    $projectDescription=trim(report_v($project,'description'));
    $body='<div class="cover"><h1>P f l i c h t e n h e f t</h1><div class="project">'.report_h(report_v($project,'name')).'</div></div><pagebreak/><tocpagebreak links="on" toc-preHTML="&lt;div class=\'toc-title\'&gt;Inhaltsverzeichnis&lt;/div&gt;"/>';
    $body.='<h1>1. Einführung</h1><h2>1.1 Zweck des Dokuments</h2><p>Dieses Pflichtenheft beschreibt die Benutzer-, System-, Hardware- und Softwareanforderungen des Projekts sowie deren Verifikation und risikobezogene Rückverfolgbarkeit.</p><h2>1.2 Projektbeschreibung</h2><p>'.($projectDescription!==''?nl2br(report_h($projectDescription)):'Für das Projekt wurde noch keine Projektbeschreibung hinterlegt.').'</p><h2>1.3 Dokumentstruktur</h2><p>Die Anforderungen sind nach Anforderungstyp gegliedert. Die vorgelagerte Übersicht fasst die gepflegten Beziehungen fachlich nach Ebenen zusammen. Risiken und Verifikation werden in eigenen Abschnitten dargestellt.</p><h2>1.4 Geltungsbereich</h2><p>Der Geltungsbereich ergibt sich aus der Projektbeschreibung und den im Projekt enthaltenen Anforderungen.</p>';
    $body.='<h1>2. Fachlicher Kontext</h1><h2>2.1 Projektziele</h2>';if($contextData['goals'])foreach($contextData['goals'] as $x)$body.=ex_goal_html($x);else $body.='<p class="context-empty">Keine Projektziele hinterlegt.</p>';$body.='<h2>2.2 User Stories</h2>';if($contextData['stories'])foreach($contextData['stories'] as $x)$body.=ex_story_html($x);else $body.='<p class="context-empty">Keine User Stories hinterlegt.</p>';$body.='<h2>2.3 Use Cases</h2>';if($contextData['cases'])foreach($contextData['cases'] as $x)$body.=ex_case_html($x);else $body.='<p class="context-empty">Keine Use Cases hinterlegt.</p>';$body.='<h1>3. Anforderungsübersicht und Traceability</h1><p>Die Übersicht gruppiert die erreichbaren Anforderungen je Benutzeranforderung nach fachlicher Ebene.</p><div class="requirement-tree">'.ex_tree_html($reqs).'</div>';$groups=[];foreach($reqs as $r)$groups[report_v($r,'type','SYS')][]=$r;$chapter=4;
    foreach($groups as $type=>$items){$body.='<h1>'.$chapter++.'. '.report_h(ex_type_title($type)).'</h1>';foreach($items as $r){$parents=json_decode(report_v($r,'parents','[]'),true)?:[];$children=json_decode(report_v($r,'children','[]'),true)?:[];$description=report_v($r,'description',report_v($r,'title'));$body.='<article class="requirement-card"><h2><span class="key">'.report_h(report_v($r,'req_key')).'</span> '.report_h(report_v($r,'title')).'</h2><table class="facts"><tr><th>Typ</th><td>'.report_h(report_v($r,'type')).'</td><th>Status</th><td>'.report_h(report_v($r,'review_status')).'</td></tr><tr><th>Parents</th><td>'.report_h($parents?implode(', ',$parents):'-').'</td><th>Children</th><td>'.report_h($children?implode(', ',$children):'-').'</td></tr></table><div class="requirement-section"><div class="requirement-section-title">Spezifikation</div><div class="requirement-section-content">'.ex_rich_text($description).'</div></div><div class="requirement-section"><div class="requirement-section-title">Akzeptanzkriterien</div><div class="requirement-section-content">'.ex_criteria_definition_html($r).'</div></div></article>';}}
    $body.='<h1>'.$chapter++.'. Risikomanagement</h1><p>Die folgenden Abschnitte dokumentieren die aktiven Risiken, ihre Bewertung, Ursachen, möglichen Systemfehlverhalten, Auswirkungen, Maßnahmen und Verknüpfungen.</p>';
    $body.='<h2>Risikoübersicht</h2><table class="spec"><tr><th>ID</th><th>Risiko</th><th>Initial</th><th>Restrisiko</th><th>Status</th></tr>';foreach($risks as $risk){$a=ex_attrs($risk);$body.='<tr><td class="key">'.report_h(report_v($risk,'req_key')).'</td><td>'.report_h(report_v($risk,'title')).'</td><td>W '.(int)($a['w']??1).' · A '.(int)($a['e']??1).' · R '.(int)($a['risk_score']??((int)($a['w']??1)*(int)($a['e']??1))).'</td><td>W '.(int)($a['residual_w']??$a['w']??1).' · A '.(int)($a['residual_e']??$a['e']??1).' · R '.(int)($a['residual_score']??1).'</td><td>'.report_h($a['workflow_status']??'open').'</td></tr>';}$body.='</table>';
    foreach($risks as $risk){$a=ex_attrs($risk);$control=$risk['risk_links']['control']??[];$verification=$risk['risk_links']['verification']??[];$body.='<article class="requirement-card"><h2><span class="key">'.report_h(report_v($risk,'req_key')).'</span> '.report_h(report_v($risk,'title')).'</h2><div class="requirement-section"><div class="requirement-section-title">Ursache / Softwarefehler</div><div class="requirement-section-content">'.nl2br(report_h($a['cause']??'-')).'</div></div><div class="requirement-section"><div class="requirement-section-title">Systemfehlverhalten</div><div class="requirement-section-content">'.nl2br(report_h($a['malfunction']??'-')).'</div></div><div class="requirement-section"><div class="requirement-section-title">Auswirkung</div><div class="requirement-section-content">'.nl2br(report_h($a['effect']??'-')).'</div></div><div class="requirement-section"><div class="requirement-section-title">Maßnahme</div><div class="requirement-section-content">'.nl2br(report_h($a['mitigation_plan']??'-')).'</div></div><div class="trace"><strong>Kontrollierende Anforderungen:</strong> '.report_h($control?implode(', ',array_column($control,'req_key')):'-').'</div><div class="trace"><strong>Verifikation:</strong> '.report_h($verification?implode(', ',array_column($verification,'req_key')):'-').'</div></article>';}
    return [ex_html_frame($body,$header,$footer,$logo,$accent),'specification'];
}
function ex_spec_docx(PDO $pdo, array $project, array $settings, array $data, string $path): void
{
    [$accent,$company,$header,$footer,$logo]=ex_layout($project,$settings,'Pflichtenheft');$accent=ltrim($accent,'#');
    $q=$pdo->prepare("SELECT * FROM requirements WHERE project_id=? AND type<>'RISK' ORDER BY COALESCE(serial_number,id),id");$q->execute([report_v($project,'id')]);$reqs=$q->fetchAll(PDO::FETCH_ASSOC);usort($reqs,static fn($a,$b)=>ex_type_order(report_v($a,'type'))<=>ex_type_order(report_v($b,'type')) ?: ((int)($a['serial_number']??$a['id'])<=>(int)($b['serial_number']??$b['id'])));$risks=ex_risk_data($pdo,report_v($project,'id'));$contextData=ex_context_data($pdo,report_v($project,'id'));
    $word=new PhpWord();$word->setDefaultFontName('Arial');$word->setDefaultFontSize(9);$section=$word->addSection(['marginTop'=>1350,'marginBottom'=>1200,'marginLeft'=>1000,'marginRight'=>1000]);$section->addTextBreak(4);$section->addText('P f l i c h t e n h e f t',['size'=>24,'bold'=>true,'color'=>$accent],['alignment'=>Jc::CENTER]);$section->addText(report_v($project,'name'),['size'=>20,'bold'=>true,'color'=>$accent],['alignment'=>Jc::CENTER]);$section->addPageBreak();$section->addText('Inhaltsverzeichnis',['size'=>18,'bold'=>true,'color'=>$accent]);$section->addTOC(['name'=>'Arial','size'=>9],['tabLeader'=>'dot']);$section->addPageBreak();$section->addTitle('1. Einführung',1);$section->addText(report_v($project,'description','-'));$section->addTitle('2. Fachlicher Kontext',1);$section->addTitle('2.1 Projektziele',2);if(!$contextData['goals'])$section->addText('Keine Projektziele hinterlegt.',['italic'=>true]);foreach($contextData['goals'] as $x){$section->addTitle(report_v($x,'req_key').' · '.report_v($x,'title'),3);$section->addText(report_v($x,'description','-'));}$section->addTitle('2.2 User Stories',2);if(!$contextData['stories'])$section->addText('Keine User Stories hinterlegt.',['italic'=>true]);foreach($contextData['stories'] as $x){$section->addTitle(report_v($x,'us_key').' · '.report_v($x,'title'),3);$section->addText('Als '.report_v($x,'us_role','-').' möchte ich '.report_v($x,'us_action','-').', so dass '.report_v($x,'us_benefit','-').'.');}$section->addTitle('2.3 Use Cases',2);if(!$contextData['cases'])$section->addText('Keine Use Cases hinterlegt.',['italic'=>true]);foreach($contextData['cases'] as $x){$section->addTitle(report_v($x,'uc_key').' · '.report_v($x,'title'),3);$section->addText('Primärer Akteur: '.report_v($x,'primary_actor','-'),['bold'=>true]);foreach([['Vorbedingungen','preconditions'],['Erfolgreicher Standardablauf','main_scenario'],['Alternative Abläufe und Fehlerfälle','alt_scenario']] as $part){if(trim(report_v($x,$part[1]))!==''){$section->addText($part[0],['bold'=>true]);$section->addText(report_v($x,$part[1]));}}}$section->addTitle('3. Anforderungsübersicht',1);$byKey=[];$child=[];$hasParent=[];foreach($reqs as $r)$byKey[report_v($r,'req_key')]=$r;foreach($reqs as $r)foreach(json_decode(report_v($r,'parents','[]'),true)?:[] as $p)if(isset($byKey[$p])){$child[$p][]=$r;$hasParent[report_v($r,'req_key')]=true;}$rendered=[];$render=function($r,$level)use(&$render,&$rendered,$child,$section){$key=report_v($r,'req_key');if(isset($rendered[$key]))return;$rendered[$key]=true;$section->addText(str_repeat('   ',$level).($level?'└─ ':'').$key.' · '.report_v($r,'title').' ['.report_v($r,'type').']',['size'=>8]);foreach($child[$key]??[] as $c)$render($c,$level+1);};foreach($reqs as $r)if(empty($hasParent[report_v($r,'req_key')]))$render($r,0);
    $groups=[];foreach($reqs as $r)$groups[report_v($r,'type','SYS')][]=$r;$chapter=4;foreach($groups as $type=>$items){$section->addTitle($chapter++.'. '.ex_type_title($type),1);foreach($items as $r){$section->addTitle(report_v($r,'req_key').' · '.report_v($r,'title'),2);$section->addText('Status: '.report_v($r,'review_status').' | Typ: '.report_v($r,'type'),['size'=>8,'color'=>'666666']);$section->addText(report_v($r,'description',report_v($r,'title')));$c=ex_criteria($r);$section->addText('Akzeptanzkriterien',['bold'=>true,'size'=>9]);if(!$c['total']){$section->addText('Keine Akzeptanzkriterien definiert.',['italic'=>true,'size'=>8,'color'=>'666666']);}else{foreach($c['items'] as $item)$section->addListItem($item['text'],0,['size'=>8]);}}}
    $section->addTitle($chapter++.'. Risikomanagement',1);$section->addTitle('Risikoübersicht',2);$t=$section->addTable(['borderSize'=>5,'borderColor'=>'8FAADC','cellMargin'=>70]);$row=$t->addRow();foreach(['ID','Risiko','Initial','Restrisiko','Status'] as $h)$row->addCell()->addText($h,['bold'=>true]);foreach($risks as $risk){$a=ex_attrs($risk);$row=$t->addRow();foreach([report_v($risk,'req_key'),report_v($risk,'title'),'W '.($a['w']??1).' / A '.($a['e']??1).' / R '.($a['risk_score']??1),'W '.($a['residual_w']??1).' / A '.($a['residual_e']??1).' / R '.($a['residual_score']??1),$a['workflow_status']??'open'] as $v)$row->addCell()->addText((string)$v,['size'=>8]);}
    IOFactory::createWriter($word,'Word2007')->save($path);
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
