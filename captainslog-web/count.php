<?php
// count_lines.php
$directory = __DIR__;
$extensions = ['php', 'js', 'html', 'css', 'sql'];

function countLinesRecursive($dir, $exts, &$stats) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        // Korrigiert: DIRECTORY_SEPARATOR statt DIRECTORY_INFO
        if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . '.git') !== false) {
            continue;
        }

        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $exts)) {
                $lines = count(file($file->getPathname()));
                $stats['total'] += $lines;
                $stats['breakdown'][$ext]['files']++;
                $stats['breakdown'][$ext]['lines'] += $lines;
            }
        }
    }
}

$stats = ['total' => 0, 'breakdown' => []];
foreach ($extensions as $ext) {
    $stats['breakdown'][$ext] = ['files' => 0, 'lines' => 0];
}

countLinesRecursive($directory, $extensions, $stats);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Projekt Zeilenzähler</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-10 font-sans">
    <div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-md border border-slate-200">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Projekt Code-Statistik</h1>
        <p class="text-sm text-slate-500 mb-6">Verzeichnis: <code><?php echo htmlspecialchars($directory); ?></code></p>
        
        <div class="bg-blue-50 border border-blue-200 p-4 rounded mb-6 flex justify-between items-center">
            <span class="font-bold text-blue-900">Gesamtanzahl Zeilen:</span>
            <span class="text-2xl font-extrabold text-blue-700"><?php echo number_format($stats['total'], 0, ',', '.'); ?></span>
        </div>

        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-3">Aufteilung nach Dateityp</h2>
        <table class="w-full text-left text-sm text-slate-600 border-collapse">
            <thead>
                <tr class="border-b bg-slate-50 text-slate-900">
                    <th class="p-2">Typ</th>
                    <th class="p-2 text-center">Dateien</th>
                    <th class="p-2 text-right">Zeilen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['breakdown'] as $ext => $data): ?>
                <tr class="border-b">
                    <td class="p-2 font-mono uppercase font-bold text-slate-800">.<?php echo $ext; ?></td>
                    <td class="p-2 text-center"><?php echo $data['files']; ?></td>
                    <td class="p-2 text-right font-semibold"><?php echo number_format($data['lines'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="mt-6 text-center">
            <a href="count_lines.php" class="text-xs text-blue-600 hover:underline">Aktualisieren</a>
        </div>
    </div>
</body>
</html>