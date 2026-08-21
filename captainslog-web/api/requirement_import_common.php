<?php
// api/requirement_import_common.php

declare(strict_types=1);

function ri_json(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function ri_require_login(): int {
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) ri_json(['success' => false, 'error' => 'Nicht angemeldet.'], 401);
    return $id;
}

function ri_storage_dir(): string {
    $dir = __DIR__ . '/../storage/requirement_imports';
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        throw new RuntimeException('Import-Verzeichnis konnte nicht erstellt werden.');
    }
    return $dir;
}

function ri_clean(mixed $value): string { return trim((string)$value); }
function ri_token(): string { return bin2hex(random_bytes(20)); }

function ri_find_pdftotext(): string {
    $configured = getenv('PDFTOTEXT_PATH');
    if ($configured && is_file($configured)) return $configured;

    $candidates = [
        'C:\\Program Files\\poppler\\Library\\bin\\pdftotext.exe',
        'C:\\Program Files\\poppler\\bin\\pdftotext.exe',
        'C:\\poppler\\Library\\bin\\pdftotext.exe',
        'C:\\poppler\\bin\\pdftotext.exe',
        '/usr/bin/pdftotext',
        '/usr/local/bin/pdftotext'
    ];
    foreach ($candidates as $candidate) if (is_file($candidate)) return $candidate;

    $command = PHP_OS_FAMILY === 'Windows' ? 'where.exe pdftotext.exe 2>NUL' : 'command -v pdftotext 2>/dev/null';
    $lines = []; $code = 1;
    exec($command, $lines, $code);
    if ($code === 0 && !empty($lines[0]) && is_file(trim($lines[0]))) return trim($lines[0]);

    if (PHP_OS_FAMILY === 'Windows') {
        $local = getenv('LOCALAPPDATA');
        if ($local) {
            $matches = glob($local . '/Microsoft/WinGet/Packages/oschwartz10612.Poppler*/**/pdftotext.exe', GLOB_BRACE);
            if ($matches && is_file($matches[0])) return $matches[0];
        }
    }
    throw new RuntimeException('pdftotext.exe wurde vom Apache-Prozess nicht gefunden. Setze PDFTOTEXT_PATH auf den vollständigen Dateipfad.');
}

function ri_pdf_pages(string $file): array {
    $binary = ri_find_pdftotext();
    $output = tempnam(sys_get_temp_dir(), 'captains_pdf_');
    $cmd = escapeshellarg($binary) . ' -layout -enc UTF-8 ' . escapeshellarg($file) . ' ' . escapeshellarg($output) . ' 2>&1';
    $lines = []; $code = 1; exec($cmd, $lines, $code);
    if ($code !== 0 || !is_file($output)) {
        @unlink($output);
        throw new RuntimeException('PDF konnte nicht gelesen werden: ' . implode(' ', $lines));
    }
    $text = (string)file_get_contents($output); @unlink($output);
    $pages = preg_split('/\f/u', $text) ?: [];
    return array_values(array_map(static fn($p) => str_replace("\r", '', $p), $pages));
}

function ri_nrt_type(string $key): string {
    if ($key === '13.090') return 'SYS';
    $major = (int)explode('.', $key, 2)[0];
    if (in_array($major, [11, 12, 14], true)) return 'HRS';
    if ($major === 13) return 'SRS';
    if ($major >= 41) return 'SYS';
    return 'SYS';
}

function ri_nrt_noise_line(string $line): bool {
    $line = trim($line);
    if ($line === '') return false;
    return (bool)preg_match('/^(?:EPSa\s*-|Aufgabenstellung\s*\[NRT-NG\]|Seite\s+\d+\s+von\s+\d+|ID\s+Spezifikation|Nachweis\s+Verweis\s+Bemerkung|Erweiterte\s+Spezifikation)$/iu', $line);
}

function ri_nrt_clean_text(string $text): string {
    $lines = preg_split('/\R/u', $text) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $line = trim(preg_replace('/\s+/u', ' ', $line));
        if ($line === '' || ri_nrt_noise_line($line)) continue;
        if (preg_match('/^(?:Remschützer Straße|Telefon:|Telefax:|E-Mail:|Internet:)/iu', $line)) continue;
        $clean[] = $line;
    }
    $text = implode(' ', $clean);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function ri_nrt_title(string $description): string {
    $description = trim($description);
    if ($description === '') return 'Im Quelldokument ist keine Spezifikation angegeben.';
    $parts = preg_split('/(?<=[.!?;:])\s+/u', $description, 2);
    $title = trim($parts[0] ?? $description);
    if (mb_strlen($title) > 240) $title = rtrim(mb_substr($title, 0, 237)) . '...';
    return $title;
}

function ri_parse_nrt_ng(array $pages, int $startPage = 12, int $endPage = 47): array {
    $startPage = max(1, $startPage);
    $endPage = max($startPage, $endPage);
    $records = [];
    $seen = [];

    for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++) {
        $page = $pages[$pageNumber - 1] ?? '';
        if (trim($page) === '') continue;

        $lines = preg_split('/\R/u', $page) ?: [];
        $currentKey = null;
        $buffer = [];

        $flush = static function () use (&$currentKey, &$buffer, &$records, &$seen, $pageNumber) {
            if ($currentKey === null) return;
            $description = ri_nrt_clean_text(implode("\n", $buffer));
            if ($description === '') $description = 'Im Quelldokument ist keine Spezifikation angegeben.';
            if (!isset($seen[$currentKey])) {
                $records[] = [
                    $currentKey,
                    ri_nrt_type($currentKey),
                    ri_nrt_title($description),
                    $description,
                    $pageNumber,
                    'Projekt NRT-NG_Aufgabenstellung_1.0.pdf, Version 1.0'
                ];
                $seen[$currentKey] = count($records) - 1;
            } else {
                $index = $seen[$currentKey];
                if (mb_strlen($description) > mb_strlen($records[$index][3])) {
                    $records[$index][2] = ri_nrt_title($description);
                    $records[$index][3] = $description;
                    $records[$index][4] = $pageNumber;
                }
            }
            $currentKey = null; $buffer = [];
        };

        foreach ($lines as $line) {
            if (ri_nrt_noise_line($line)) continue;
            if (preg_match('/^\s*((?:1[1-4]|(?:4[1-2]|5[1-3]|6[1-2]|7[1-4]|8[1-2]|9[1-2]|10[1-2]|11[1-2]|12[1-2]|13[1-2]))\.\d{3})\b\s*(.*)$/u', $line, $match)) {
                $flush();
                $currentKey = $match[1];
                $remainder = trim($match[2]);
                $buffer = $remainder !== '' ? [$remainder] : [];
            } elseif ($currentKey !== null) {
                $buffer[] = $line;
            }
        }
        $flush();
    }

    usort($records, static function ($a, $b) {
        $ap = array_map('intval', explode('.', $a[0]));
        $bp = array_map('intval', explode('.', $b[0]));
        return $ap <=> $bp;
    });
    return $records;
}
