<?php
// api/export_task_protocol_docx.php
// Erzeugt ein bearbeitbares EPSa-Aufgaben-Protokoll als DOCX.

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

function tp_value(array $row, string $key, string $fallback = ''): string
{
    return trim((string)($row[$key] ?? $fallback));
}

function tp_safe_name(string $value): string
{
    return trim((string)preg_replace('/[^A-Za-z0-9._-]+/u', '_', $value), '_') ?: 'Projekt';
}

function tp_selected(array $input, string $key): bool
{
    return in_array($key, $input['content'] ?? [], true);
}

function tp_status_label(string $status): string
{
    return match ($status) {
        'open' => 'Offen',
        'in_progress' => 'In Bearbeitung',
        'waiting_response' => 'Rückmeldung ausstehend',
        'ready_for_test' => 'Bereit zur Prüfung',
        'approved' => 'Freigegeben',
        'closed' => 'Abgeschlossen',
        'rejected' => 'Abgelehnt',
        default => $status !== '' ? $status : '-'
    };
}

function tp_review_label(string $status): string
{
    return $status !== '' ? $status : 'Neu';
}

function tp_clean_description(string $description): string
{
    $lines = [];
    foreach (preg_split('/\R/u', $description) ?: [] as $line) {
        // Diese Zeilen werden bereits als Unteraufgaben ausgegeben.
        if (preg_match('/^\s*--\s*\S/u', $line)) continue;
        $lines[] = rtrim($line);
    }
    return trim(implode("\n", $lines));
}

function tp_add_text($container, string $text, bool $bold = false, int $size = 9, string $color = '000000', array $paragraph = []): void
{
    $container->addText(
        $text !== '' ? $text : '-',
        ['name' => 'Arial', 'size' => $size, 'bold' => $bold, 'color' => $color],
        array_merge(['spaceAfter' => 0, 'lineHeight' => 1.08], $paragraph)
    );
}

function tp_add_header_row($table, array $headings, array $widths = []): void
{
    $row = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
    foreach ($headings as $index => $heading) {
        $cell = $row->addCell($widths[$index] ?? null, [
            'bgColor' => 'D9E2F3',
            'valign' => VerticalJc::CENTER
        ]);
        tp_add_text($cell, $heading, true, 8, '17365D');
    }
}

function tp_add_detail_row($table, string $label, string $value): void
{
    $row = $table->addRow(null, ['cantSplit' => true]);
    $labelCell = $row->addCell(2200, ['bgColor' => 'F2F2F2', 'valign' => VerticalJc::CENTER]);
    $valueCell = $row->addCell(6800, ['valign' => VerticalJc::CENTER]);
    tp_add_text($labelCell, $label, true, 8, '333333');
    tp_add_text($valueCell, $value, false, 8);
}

function tp_add_section_text($section, string $heading, string $text): void
{
    if (trim($text) === '') return;
    $section->addTitle($heading, 3);
    foreach (preg_split('/\R/u', trim($text)) ?: [] as $line) {
        if (trim($line) === '') continue;
        tp_add_text($section, trim($line), false, 9, '000000', ['spaceAfter' => 80, 'lineHeight' => 1.15]);
    }
}

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) throw new RuntimeException('Nicht angemeldet.');

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $projectId = trim((string)($input['project_id'] ?? ''));
    $taskIds = array_values(array_unique(array_filter(array_map('intval', $input['selected_ids'] ?? []))));

    if ($projectId === '') throw new RuntimeException('Projekt-ID fehlt.');
    if (!$taskIds) throw new RuntimeException('Bitte mindestens eine Aufgabe auswählen.');

    if (($_SESSION['role'] ?? '') !== 'admin') {
        $access = $pdo->prepare(
            'SELECT 1 FROM project_members
             WHERE project_id = ? AND user_id = ? AND is_active = 1'
        );
        $access->execute([$projectId, $userId]);
        if (!$access->fetchColumn()) throw new RuntimeException('Kein Zugriff auf dieses Projekt.');
    }

    $query = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $query->execute([$projectId]);
    $project = $query->fetch(PDO::FETCH_ASSOC);
    if (!$project) throw new RuntimeException('Projekt wurde nicht gefunden.');

    $marks = implode(',', array_fill(0, count($taskIds), '?'));
    $query = $pdo->prepare(
        "SELECT * FROM project_tasks
         WHERE project_id = ?
           AND parent_id IS NULL
           AND id IN ({$marks})
         ORDER BY wbs_code, id"
    );
    $query->execute(array_merge([$projectId], $taskIds));
    $tasks = $query->fetchAll(PDO::FETCH_ASSOC);

    $settings = [];
    try {
        $query = $pdo->prepare('SELECT * FROM project_report_settings WHERE project_id = ?');
        $query->execute([$projectId]);
        $settings = $query->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $ignored) {
        $settings = [];
    }

    $company = tp_value($settings, 'company_name', 'EPSa - Elektronik & Präzisionsbau Saalfeld GmbH');
    $classification = tp_value($settings, 'classification', 'Vertraulich');
    $version = trim((string)($input['version'] ?? '1.0.0')) ?: '1.0.0';
    $author = trim((string)($input['author'] ?? ($_SESSION['username'] ?? '')));
    $manager = trim((string)($input['manager'] ?? ''));
    $customer = trim((string)($input['customer'] ?? ''));
    $projectName = tp_value($project, 'name', 'Projekt');

    $word = new PhpWord();
    $word->setDefaultFontName('Arial');
    $word->setDefaultFontSize(9);
    $word->getSettings()->setUpdateFields(true);

    $word->addTitleStyle(1,
        ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => '17365D'],
        ['spaceBefore' => 180, 'spaceAfter' => 100, 'keepNext' => true]
    );
    $word->addTitleStyle(2,
        ['name' => 'Arial', 'size' => 12, 'bold' => true, 'color' => '17365D'],
        ['spaceBefore' => 160, 'spaceAfter' => 80, 'keepNext' => true]
    );
    $word->addTitleStyle(3,
        ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => '1F4E79'],
        ['spaceBefore' => 120, 'spaceAfter' => 60, 'keepNext' => true]
    );

    $section = $word->addSection([
        'marginTop' => 1250,
        'marginBottom' => 1200,
        'marginLeft' => 1050,
        'marginRight' => 1050
    ]);

    // Einheitliche EPSa-Kopfzeile.
    $header = $section->addHeader();
    $headerTable = $header->addTable([
        'width' => 100 * 50,
        'unit' => 'pct',
        'cellMargin' => 100,
        'borderBottomSize' => 6,
        'borderBottomColor' => '1F4E79'
    ]);
    $headerTable->addRow(620);

    $logoPath = '';
    $logoRelative = tp_value($settings, 'logo_path');
    if ($logoRelative !== '') {
        $candidate = realpath(__DIR__ . '/../' . $logoRelative);
        if ($candidate && is_file($candidate)) $logoPath = $candidate;
    }
    if ($logoPath === '') {
        $candidate = realpath(__DIR__ . '/../dashboard/css/logo.png');
        if ($candidate && is_file($candidate)) $logoPath = $candidate;
    }

    $logoCell = $headerTable->addCell(1900, [
        'valign' => VerticalJc::CENTER,
        'marginLeft' => 80,
        'marginRight' => 180,
        'marginTop' => 70,
        'marginBottom' => 70
    ]);
    if ($logoPath !== '') {
        $logoCell->addImage($logoPath, ['height' => 30, 'alignment' => Jc::START]);
    } else {
        tp_add_text($logoCell, 'EPSa', true, 9, '17365D');
    }

    $headerTextCell = $headerTable->addCell(7100, ['valign' => VerticalJc::CENTER]);
    tp_add_text(
        $headerTextCell,
        $projectName . ' | Meilenstein- und Aufgaben-Protokoll',
        true,
        8,
        '17365D',
        ['alignment' => Jc::END]
    );

    // Einheitliche EPSa-Fußzeile.
    $footer = $section->addFooter();
    tp_add_text($footer, $company, false, 7, '666666', ['alignment' => Jc::CENTER]);
    $footerLine = $footer->addTextRun(['alignment' => Jc::CENTER]);
    $footerLine->addText($classification . ' | Stand ' . date('d.m.Y') . ' | Seite ', ['name' => 'Arial', 'size' => 7, 'color' => '666666']);
    $footerLine->addField('PAGE');
    $footerLine->addText(' von ', ['name' => 'Arial', 'size' => 7, 'color' => '666666']);
    $footerLine->addField('NUMPAGES');

    // Deckblatt.
    $section->addTextBreak(5);
    $section->addText(
        'M e i l e n s t e i n -  u n d  A u f g a b e n - P r o t o k o l l',
        ['name' => 'Arial', 'size' => 19, 'bold' => true, 'color' => '17365D'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 260]
    );
    tp_add_text($section, 'für das Projekt', false, 12, '000000', ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
    tp_add_text($section, $projectName, true, 20, '1F4E79', ['alignment' => Jc::CENTER, 'spaceAfter' => 500]);

    $coverTable = $section->addTable([
        'borderSize' => 5,
        'borderColor' => '9EADBA',
        'cellMargin' => 100,
        'width' => 100 * 50,
        'unit' => 'pct'
    ]);
    tp_add_header_row($coverTable, ['Dokumentdaten', 'Inhalt'], [2500, 6500]);
    foreach ([
        ['Version', $version],
        ['Stand', date('d.m.Y')],
        ['Bearbeiter', $author],
        ['Projektleiter', $manager],
        ['Auftraggeber', $customer],
        ['Auftragnehmer', $company],
        ['Klassifizierung', $classification]
    ] as [$label, $value]) {
        tp_add_detail_row($coverTable, $label, $value);
    }

    $section->addPageBreak();

    // Versionsübersicht.
    $section->addTitle('Versionsübersicht', 1);
    $versionTable = $section->addTable([
        'borderSize' => 5,
        'borderColor' => '8FAADC',
        'cellMargin' => 80,
        'width' => 100 * 50,
        'unit' => 'pct'
    ]);
    tp_add_header_row($versionTable, ['Version', 'Datum', 'Kapitel', 'Bemerkung', 'Bearbeiter'], [1100, 1300, 1300, 3500, 1800]);
    $row = $versionTable->addRow(null, ['cantSplit' => true]);
    foreach ([$version, date('d.m.Y'), 'alle', 'Export aus Captain\'s Log', $author] as $value) {
        $cell = $row->addCell();
        tp_add_text($cell, $value, false, 8);
    }

    $section->addTitle('Inhaltsverzeichnis', 1);
    $section->addTOC(['name' => 'Arial', 'size' => 9], ['tabLeader' => 'dot']);
    $section->addPageBreak();

    // 1. Projektübersicht.
    $section->addTitle('1. Projektübersicht', 1);
    if (tp_value($project, 'description') !== '') {
        tp_add_text($section, tp_value($project, 'description'), false, 9, '000000', ['spaceAfter' => 120, 'lineHeight' => 1.15]);
    }

    $total = count($tasks);
    $completed = count(array_filter($tasks, static fn(array $task): bool => (int)$task['progress_pct'] >= 100));
    $inProgress = count(array_filter($tasks, static fn(array $task): bool => (int)$task['progress_pct'] > 0 && (int)$task['progress_pct'] < 100));
    $notStarted = max(0, $total - $completed - $inProgress);
    $average = $total ? (int)round(array_sum(array_map(static fn(array $task): int => (int)$task['progress_pct'], $tasks)) / $total) : 0;

    $summaryTable = $section->addTable([
        'borderSize' => 5,
        'borderColor' => 'AAB7C4',
        'cellMargin' => 90,
        'width' => 100 * 50,
        'unit' => 'pct'
    ]);
    tp_add_header_row($summaryTable, ['Kennzahl', 'Wert'], [6500, 2500]);
    foreach ([
        ['Ausgewählte Hauptaufgaben', (string)$total],
        ['Abgeschlossen', (string)$completed],
        ['In Bearbeitung', (string)$inProgress],
        ['Noch nicht begonnen', (string)$notStarted],
        ['Durchschnittlicher Fortschritt', $average . ' %']
    ] as [$label, $value]) {
        tp_add_detail_row($summaryTable, $label, $value);
    }

    // 2. Aufgabenübersicht.
    $section->addTitle('2. Aufgabenübersicht', 1);
    $overview = $section->addTable([
        'borderSize' => 5,
        'borderColor' => '8FAADC',
        'cellMargin' => 75,
        'width' => 100 * 50,
        'unit' => 'pct',
        'layout' => 'fixed'
    ]);
    tp_add_header_row($overview, ['WBS', 'Aufgabe', 'Zuständig', 'Zeitraum', 'Fortschritt'], [900, 3900, 1600, 1800, 900]);
    foreach ($tasks as $task) {
        $row = $overview->addRow(null, ['cantSplit' => true]);
        $values = [
            tp_value($task, 'wbs_code', '-'),
            tp_value($task, 'title'),
            tp_value($task, 'assignee', '-'),
            tp_value($task, 'start_date', '-') . ' bis ' . tp_value($task, 'end_date', '-'),
            (int)$task['progress_pct'] . ' %'
        ];
        foreach ($values as $index => $value) {
            $cell = $row->addCell(null, ['valign' => VerticalJc::CENTER]);
            tp_add_text($cell, $value, $index === 0, 8, '000000', [
                'alignment' => in_array($index, [0, 4], true) ? Jc::CENTER : Jc::START
            ]);
        }
    }

    // 3. Einzelaufgaben als echte Word-Kapitel für das Inhaltsverzeichnis.
    $section->addTitle('3. Aufgaben im Detail', 1);
    $taskNumber = 1;

    foreach ($tasks as $task) {
        $taskId = (int)$task['id'];
        $section->addTitle(
            '3.' . $taskNumber . ' ' . tp_value($task, 'wbs_code', '-') . ' ' . tp_value($task, 'title'),
            2
        );

        $details = $section->addTable([
            'borderSize' => 5,
            'borderColor' => 'AAB7C4',
            'cellMargin' => 85,
            'width' => 100 * 50,
            'unit' => 'pct'
        ]);
        tp_add_header_row($details, ['Aufgabendaten', 'Inhalt'], [2200, 6800]);
        tp_add_detail_row($details, 'WBS', tp_value($task, 'wbs_code', '-'));
        tp_add_detail_row($details, 'Kategorie', tp_value($task, 'category', '-'));
        if (tp_selected($input, 'assignee')) tp_add_detail_row($details, 'Zuständig', tp_value($task, 'assignee', '-'));
        if (tp_selected($input, 'dates')) tp_add_detail_row($details, 'Zeitraum', tp_value($task, 'start_date', '-') . ' bis ' . tp_value($task, 'end_date', '-'));
        tp_add_detail_row($details, 'Fortschritt', (int)$task['progress_pct'] . ' %');
        if (tp_selected($input, 'effort')) tp_add_detail_row($details, 'Interner Aufwand', tp_value($task, 'effort_mt', '0') . ' MT');

        if (tp_selected($input, 'description')) {
            tp_add_section_text($section, 'Aufgabenbeschreibung', tp_clean_description(tp_value($task, 'description')));
        }

        $subQuery = $pdo->prepare(
            'SELECT * FROM project_tasks WHERE parent_id = ? ORDER BY id'
        );
        $subQuery->execute([$taskId]);
        $subtasks = $subQuery->fetchAll(PDO::FETCH_ASSOC);

        if (tp_selected($input, 'checklist') && $subtasks) {
            $section->addTitle('Unteraufgaben und Checkliste', 3);
            $checkTable = $section->addTable([
                'borderSize' => 5,
                'borderColor' => 'AAB7C4',
                'cellMargin' => 80,
                'width' => 100 * 50,
                'unit' => 'pct',
                'layout' => 'fixed'
            ]);
            tp_add_header_row($checkTable, ['Erledigt', 'Unteraufgabe', 'Fortschritt'], [1000, 7000, 1000]);
            foreach ($subtasks as $subtask) {
                $done = (int)$subtask['progress_pct'] >= 100;
                $row = $checkTable->addRow(null, ['cantSplit' => true]);
                foreach ([$done ? '☒' : '☐', tp_value($subtask, 'title'), (int)$subtask['progress_pct'] . ' %'] as $index => $value) {
                    $cell = $row->addCell(null, ['valign' => VerticalJc::CENTER]);
                    tp_add_text($cell, $value, false, $index === 0 ? 11 : 8, '000000', [
                        'alignment' => in_array($index, [0, 2], true) ? Jc::CENTER : Jc::START
                    ]);
                }
            }
        }

        $requirementKeys = json_decode(tp_value($task, 'linked_reqs', '[]'), true) ?: [];
        $requirements = [];
        if ($requirementKeys) {
            $requirementMarks = implode(',', array_fill(0, count($requirementKeys), '?'));
            $requirementQuery = $pdo->prepare(
                "SELECT req_key,title,review_status,source_reference
                 FROM requirements
                 WHERE project_id = ? AND req_key IN ({$requirementMarks})
                 ORDER BY req_key"
            );
            $requirementQuery->execute(array_merge([$projectId], $requirementKeys));
            $requirements = $requirementQuery->fetchAll(PDO::FETCH_ASSOC);
        }

        if (tp_selected($input, 'requirements') && $requirements) {
            $section->addTitle('Verknüpfte Anforderungen', 3);
            $requirementTable = $section->addTable([
                'borderSize' => 5,
                'borderColor' => 'AAB7C4',
                'cellMargin' => 80,
                'width' => 100 * 50,
                'unit' => 'pct',
                'layout' => 'fixed'
            ]);
            tp_add_header_row($requirementTable, ['ID', 'Anforderung', 'Prüfstatus', 'Quellen-ID'], [1300, 4600, 1900, 1200]);
            foreach ($requirements as $requirement) {
                $row = $requirementTable->addRow(null, ['cantSplit' => true]);
                foreach ([
                    tp_value($requirement, 'req_key'),
                    tp_value($requirement, 'title'),
                    tp_review_label(tp_value($requirement, 'review_status')),
                    tp_value($requirement, 'source_reference', '-')
                ] as $index => $value) {
                    $cell = $row->addCell(null, ['valign' => VerticalJc::CENTER]);
                    tp_add_text($cell, $value, $index === 0, 8);
                }
            }
        }

        $issueQuery = $pdo->prepare(
            'SELECT i.issue_key,i.title,i.status,i.priority,u.username
             FROM issue_tasks relation_row
             JOIN issues i ON i.id = relation_row.issue_id
             LEFT JOIN users u ON u.id = i.assignee_user_id
             WHERE relation_row.task_id = ?
             ORDER BY i.issue_key'
        );
        $issueQuery->execute([$taskId]);
        $issues = $issueQuery->fetchAll(PDO::FETCH_ASSOC);

        if (tp_selected($input, 'issues') && $issues) {
            $section->addTitle('Verknüpfte Issues', 3);
            $issueTable = $section->addTable([
                'borderSize' => 5,
                'borderColor' => 'AAB7C4',
                'cellMargin' => 80,
                'width' => 100 * 50,
                'unit' => 'pct',
                'layout' => 'fixed'
            ]);
            tp_add_header_row($issueTable, ['ID', 'Issue', 'Status', 'Priorität', 'Zuständig'], [1300, 3900, 1500, 1100, 1200]);
            foreach ($issues as $issue) {
                $row = $issueTable->addRow(null, ['cantSplit' => true]);
                foreach ([
                    tp_value($issue, 'issue_key'),
                    tp_value($issue, 'title'),
                    tp_status_label(tp_value($issue, 'status')),
                    tp_value($issue, 'priority', '-'),
                    tp_value($issue, 'username', '-')
                ] as $index => $value) {
                    $cell = $row->addCell(null, ['valign' => VerticalJc::CENTER]);
                    tp_add_text($cell, $value, $index === 0, 8);
                }
            }
        }

        if (tp_selected($input, 'internal_details')) {
            $section->addTitle('Interne Angaben', 3);
            $internal = $section->addTable([
                'borderSize' => 5,
                'borderColor' => 'AAB7C4',
                'cellMargin' => 80,
                'width' => 100 * 50,
                'unit' => 'pct'
            ]);
            tp_add_detail_row($internal, 'Automatischer Fortschritt', !empty($task['is_auto_progress']) ? 'Ja' : 'Nein');
            tp_add_detail_row($internal, 'Leistungsfaktor', tp_value($task, 'performance_pct', '100') . ' %');
        }

        $taskNumber++;
    }

    $temporary = tempnam(sys_get_temp_dir(), 'cl_task_protocol_');
    if ($temporary === false) throw new RuntimeException('Temporäre Datei konnte nicht erstellt werden.');
    @unlink($temporary);
    $temporary .= '.docx';

    IOFactory::createWriter($word, 'Word2007')->save($temporary);

    if (!is_file($temporary) || filesize($temporary) < 2000) {
        throw new RuntimeException('Die erzeugte Word-Datei ist unvollständig.');
    }

    $archive = new ZipArchive();
    $result = $archive->open($temporary, ZipArchive::CHECKCONS);
    if ($result !== true) throw new RuntimeException('DOCX-Prüfung fehlgeschlagen. ZIP-Code: ' . $result);
    foreach (['[Content_Types].xml', '_rels/.rels', 'word/document.xml'] as $entry) {
        if ($archive->locateName($entry) === false) {
            $archive->close();
            throw new RuntimeException('DOCX-Bestandteil fehlt: ' . $entry);
        }
    }
    $archive->close();

    while (ob_get_level() > 0) ob_end_clean();
    $filename = tp_safe_name($projectName) . '_Aufgaben_Protokoll_' . tp_safe_name($version) . '.docx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temporary));
    header('Cache-Control: private, max-age=0, must-revalidate');
    readfile($temporary);
    @unlink($temporary);
} catch (Throwable $error) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Aufgaben-Protokoll-Exportfehler: ' . $error->getMessage();
}
