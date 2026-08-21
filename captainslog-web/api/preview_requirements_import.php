<?php
// api/preview_requirements_import.php

ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/requirements_import_common.php';
imp_user();

function parseBulletList(string $text): array
{
    $lines = preg_split('/\R/u', str_replace("\r", '', $text)) ?: [];
    $rows = [];
    $current = '';

    foreach ($lines as $line) {
        $line = rtrim($line);

        if (preg_match('/^\s*(?:[-*•–—]|\d+[.)])\s+(.+)$/u', $line, $match)) {
            if (trim($current) !== '') {
                $rows[] = [trim(preg_replace('/\s+/u', ' ', $current))];
            }
            $current = trim($match[1]);
        } elseif (trim($line) !== '' && trim($current) !== '') {
            $current .= ' ' . trim($line);
        }
    }

    if (trim($current) !== '') {
        $rows[] = [trim(preg_replace('/\s+/u', ' ', $current))];
    }

    return array_slice($rows, 0, 3000);
}

function parseParagraphs(string $text): array
{
    $blocks = preg_split('/\n\s*\n/u', trim(str_replace("\r", '', $text))) ?: [];
    $rows = [];

    foreach ($blocks as $block) {
        $block = trim(preg_replace('/\s+/u', ' ', $block));
        if ($block !== '') $rows[] = [$block];
    }

    return array_slice($rows, 0, 3000);
}

try {
    $config = json_decode($_POST['config'] ?? '{}', true) ?: [];
    $format = $config['format'] ?? 'text';
    $mode = $config['mode'] ?? 'bullet_list';
    $filename = 'Eingefügter Text';
    $rows = [];

    if ($format === 'text') {
        $text = (string)($_POST['pasted_text'] ?? '');
        if ($mode === 'bullet_list') $rows = parseBulletList($text);
        elseif ($mode === 'paragraphs') $rows = parseParagraphs($text);
        else $rows = imp_extract_text_records($text, $config);
    } else {
        if (empty($_FILES['file']['tmp_name'])) throw new Exception('Datei fehlt.');
        $file = $_FILES['file'];
        $filename = basename((string)$file['name']);
        if (($file['size'] ?? 0) > 30 * 1024 * 1024) throw new Exception('Datei ist größer als 30 MB.');

        if ($format === 'xlsx') {
            $rows = imp_xlsx_rows($file['tmp_name'], (int)($config['sheet_index'] ?? 0));
        } elseif ($format === 'csv') {
            $rows = imp_csv_rows($file['tmp_name'], $config['delimiter'] ?? 'auto');
        } elseif ($format === 'pdf') {
            $text = imp_pdf_text(
                $file['tmp_name'],
                (int)($config['page_from'] ?? 1),
                (int)($config['page_to'] ?? 9999)
            );
            if ($mode === 'bullet_list') $rows = parseBulletList($text);
            elseif ($mode === 'paragraphs') $rows = parseParagraphs($text);
            else $rows = imp_extract_text_records($text, $config);
        } else {
            throw new Exception('Unbekanntes Dateiformat.');
        }
    }

    if (!$rows) throw new Exception('Keine importierbaren Einträge erkannt.');

    $headerRow = max(0, (int)($config['header_row'] ?? 0));
    if (in_array($format, ['xlsx', 'csv'], true)) {
        $headers = isset($rows[$headerRow])
            ? array_map(static fn($value) => trim((string)$value), $rows[$headerRow])
            : [];
        $rows = array_slice($rows, $headerRow + 1);
    } elseif (in_array($mode, ['bullet_list', 'paragraphs'], true)) {
        $headers = ['Erkannter Inhalt'];
    } else {
        $width = max(array_map('count', $rows) ?: [0]);
        $headers = [];
        for ($index = 0; $index < $width; $index++) $headers[] = 'Spalte ' . ($index + 1);
    }

    imp_json([
        'success' => true,
        'filename' => $filename,
        'headers' => $headers,
        'rows' => array_slice($rows, 0, 500),
        'row_count' => count($rows),
        'single_content_column' => count($headers) === 1,
        'recommended_target' => in_array($mode, ['bullet_list', 'paragraphs'], true) ? 'title' : ''
    ]);
} catch (Throwable $error) {
    imp_json(['success' => false, 'error' => $error->getMessage()], 400);
}
