<?php
// api/requirements_import_common.php

declare(strict_types=1);

function imp_json(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function imp_user(): int {
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) imp_json(['success' => false, 'error' => 'Nicht angemeldet.'], 401);
    return $id;
}

function imp_dir(): string {
    $dir = __DIR__ . '/../storage/requirement_imports';
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) throw new RuntimeException('Importordner konnte nicht erstellt werden.');
    return $dir;
}

function imp_clean(mixed $value): string { return trim((string)$value); }

function imp_find_pdftotext(): string {
    $configured = getenv('PDFTOTEXT_PATH');
    if ($configured && is_file($configured)) return $configured;
    $candidates = [
        'C:\\Program Files\\poppler\\Library\\bin\\pdftotext.exe',
        'C:\\Program Files\\poppler\\bin\\pdftotext.exe',
        'C:\\poppler\\Library\\bin\\pdftotext.exe',
        'C:\\poppler\\bin\\pdftotext.exe',
        '/usr/bin/pdftotext', '/usr/local/bin/pdftotext'
    ];
    foreach ($candidates as $candidate) if (is_file($candidate)) return $candidate;
    $cmd = PHP_OS_FAMILY === 'Windows' ? 'where.exe pdftotext.exe 2>NUL' : 'command -v pdftotext 2>/dev/null';
    exec($cmd, $lines, $code);
    if ($code === 0 && !empty($lines[0]) && is_file(trim($lines[0]))) return trim($lines[0]);
    throw new RuntimeException('pdftotext wurde von Apache nicht gefunden. Setze PDFTOTEXT_PATH auf pdftotext.exe.');
}

function imp_pdf_text(string $file, int $firstPage = 1, int $lastPage = 9999): string {
    $binary = imp_find_pdftotext();
    $output = tempnam(sys_get_temp_dir(), 'cl_pdf_');
    $cmd = escapeshellarg($binary) . ' -layout -enc UTF-8 -f ' . max(1, $firstPage) . ' -l ' . max($firstPage, $lastPage) . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($output) . ' 2>&1';
    exec($cmd, $lines, $code);
    if ($code !== 0 || !is_file($output)) throw new RuntimeException('PDF konnte nicht gelesen werden: ' . implode(' ', $lines));
    $text = (string)file_get_contents($output); @unlink($output);
    return str_replace("\r", '', $text);
}

function imp_csv_rows(string $file, string $delimiter = 'auto', int $max = 3000): array {
    $handle = fopen($file, 'rb'); if (!$handle) throw new RuntimeException('CSV konnte nicht geöffnet werden.');
    $sample = (string)fgets($handle); rewind($handle);
    if ($delimiter === 'auto') {
        $stats = [',' => substr_count($sample, ','), ';' => substr_count($sample, ';'), "\t" => substr_count($sample, "\t"), '|' => substr_count($sample, '|')];
        arsort($stats); $delimiter = (string)array_key_first($stats);
    } elseif ($delimiter === '\\t') $delimiter = "\t";
    $rows = [];
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false && count($rows) < $max) $rows[] = array_map('trim', $row);
    fclose($handle); return $rows;
}

function imp_xlsx_rows(string $file, int $sheetIndex = 0, int $max = 3000): array {
    if (!class_exists('ZipArchive')) throw new RuntimeException('PHP-Erweiterung ZipArchive fehlt.');
    $zip = new ZipArchive(); if ($zip->open($file) !== true) throw new RuntimeException('XLSX konnte nicht geöffnet werden.');
    $shared = [];
    if (($raw = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        $xml = simplexml_load_string($raw);
        foreach ($xml->si as $si) { $parts=[]; foreach ($si->xpath('.//t') as $t) $parts[]=(string)$t; $shared[]=implode('', $parts); }
    }
    $workbook = simplexml_load_string((string)$zip->getFromName('xl/workbook.xml'));
    $rels = simplexml_load_string((string)$zip->getFromName('xl/_rels/workbook.xml.rels'));
    $relMap=[]; foreach($rels->Relationship as $rel)$relMap[(string)$rel['Id']]=(string)$rel['Target'];
    $workbook->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $sheets=$workbook->xpath('//m:sheets/m:sheet'); $sheet=$sheets[$sheetIndex]??$sheets[0]??null; if(!$sheet)throw new RuntimeException('Kein Tabellenblatt gefunden.');
    $attrs=$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships'); $path='xl/'.ltrim($relMap[(string)$attrs['id']]??'','/');
    $xml=simplexml_load_string((string)$zip->getFromName($path)); $zip->close();
    $xml->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $rows=[];
    foreach($xml->xpath('//m:sheetData/m:row') as $row){if(count($rows)>=$max)break;$values=[];foreach($row->xpath('./m:c') as $c){$ref=(string)$c['r'];preg_match('/^[A-Z]+/',$ref,$m);$idx=0;foreach(str_split($m[0]??'A') as $ch)$idx=$idx*26+ord($ch)-64;$idx--;$v=$c->xpath('./m:v');$value=isset($v[0])?(string)$v[0]:'';$type=(string)$c['t'];if($type==='s')$value=$shared[(int)$value]??'';if($type==='inlineStr'){$tt=$c->xpath('.//m:t');$value=implode('',array_map(fn($x)=>(string)$x,$tt));}$values[$idx]=$value;}if($values){$last=max(array_keys($values));$rows[]=array_map(fn($i)=>$values[$i]??'',range(0,$last));}}
    return $rows;
}

function imp_extract_text_records(string $text, array $config): array {
    $mode = $config['mode'] ?? 'paragraphs'; $rows=[];
    if ($mode === 'paragraphs') {
        foreach (preg_split('/\n\s*\n/u', trim($text)) ?: [] as $block) { $block=trim(preg_replace('/\s+/u',' ',$block)); if($block!=='')$rows[]=['',$block]; }
    } elseif ($mode === 'numbered_list') {
        $pattern = $config['start_pattern'] ?? '/(?m)^\s*(\d+(?:\.\d+)*[.)]?)\s+(.+)$/u';
        preg_match_all($pattern,$text,$matches,PREG_OFFSET_CAPTURE); $count=count($matches[0]);
        for($i=0;$i<$count;$i++){$start=$matches[0][$i][1];$end=$i+1<$count?$matches[0][$i+1][1]:strlen($text);$block=trim(substr($text,$start,$end-$start));$key=rtrim(trim($matches[1][$i][0]??''),'.)');$title=trim($matches[2][$i][0]??'');$description=trim(preg_replace('/^.*\R?/u','',$block,1));$rows[]=[$key,$title,$description];}
    } elseif ($mode === 'regex') {
        $pattern=$config['regex']??''; if($pattern===''||@preg_match_all($pattern,$text,$matches,PREG_SET_ORDER)===false)throw new RuntimeException('Regulärer Ausdruck ist ungültig.');
        foreach($matches as $match)$rows[]=array_values(array_slice($match,1));
    } elseif ($mode === 'delimited') {
        $delimiter=(string)($config['delimiter']??'-'); foreach(preg_split('/\R/u',$text)?:[] as $line){$line=trim($line);if($line!=='')$rows[]=array_map('trim',explode($delimiter,$line));}
    } else {
        // Fixed-width table. boundaries are character offsets, e.g. 0,12,45,70.
        $boundaries=array_map('intval',$config['boundaries']??[0,12,45,70]);sort($boundaries);
        foreach(preg_split('/\R/u',$text)?:[] as $line){if(trim($line)==='')continue;$row=[];for($i=0;$i<count($boundaries);$i++){$start=$boundaries[$i];$length=isset($boundaries[$i+1])?$boundaries[$i+1]-$start:null;$row[]=trim($length===null?substr($line,$start):substr($line,$start,$length));}$rows[]=$row;}
    }
    return array_slice($rows,0,3000);
}
