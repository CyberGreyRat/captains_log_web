param(
    [string]$ProjectPath = ".",
    [string]$OutputFile = "Code_Export.txt"
)

$ErrorActionPreference = "Stop"

$ProjectPath = (Resolve-Path $ProjectPath).Path
$OutputFile = [System.IO.Path]::GetFullPath(
    (Join-Path (Get-Location) $OutputFile)
)

# Diese Ordner werden nicht exportiert.
$ExcludedFolders = @(
    "node_modules",
    "vendor",
    ".git",
    ".idea",
    ".vscode",
    "dist",
    "build",
    "coverage",
    "cache",
    "tmp",
    "temp",
    "logs",
    "uploads"
)

# Diese Dateien werden nicht exportiert.
$ExcludedFiles = @(
    ".env",
    ".env.local",
    ".env.production",
    "secrets.php",
    "credentials.php"
)

$files = Get-ChildItem -Path $ProjectPath -Recurse -File |
    Where-Object {
        $_.Extension.ToLower() -in @(".js", ".php") -and
        $_.FullName -ne $OutputFile -and
        $_.Name -notin $ExcludedFiles
    } |
    Where-Object {
        $relativePath = $_.FullName.Substring($ProjectPath.Length)
        $pathParts = $relativePath -split '[\\/]'

        -not ($pathParts | Where-Object {
            $_ -in $ExcludedFolders
        })
    } |
    Sort-Object FullName

$builder = [System.Text.StringBuilder]::new()

[void]$builder.AppendLine("============================================================")
[void]$builder.AppendLine("CODE-EXPORT")
[void]$builder.AppendLine("============================================================")
[void]$builder.AppendLine("Projekt:       $ProjectPath")
[void]$builder.AppendLine("Erstellt:      $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
[void]$builder.AppendLine("Dateitypen:    JavaScript (*.js), PHP (*.php)")
[void]$builder.AppendLine("Dateien:       $($files.Count)")
[void]$builder.AppendLine("")

[void]$builder.AppendLine("============================================================")
[void]$builder.AppendLine("DATEIUEBERSICHT")
[void]$builder.AppendLine("============================================================")

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($ProjectPath.Length).
        TrimStart('\', '/')

    $lineCount = (Get-Content -LiteralPath $file.FullName).Count

    [void]$builder.AppendLine(
        "$relativePath | $lineCount Zeilen | $($file.Length) Bytes"
    )
}

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($ProjectPath.Length).
        TrimStart('\', '/')

    $content = Get-Content `
        -LiteralPath $file.FullName `
        -Raw `
        -Encoding UTF8 `
        -ErrorAction SilentlyContinue

    if ($null -eq $content) {
        $content = "[Datei konnte nicht als UTF-8 gelesen werden]"
    }

    [void]$builder.AppendLine("")
    [void]$builder.AppendLine("============================================================")
    [void]$builder.AppendLine("DATEI: $relativePath")
    [void]$builder.AppendLine("============================================================")
    [void]$builder.AppendLine($content)
    [void]$builder.AppendLine("")
    [void]$builder.AppendLine("ENDE DATEI: $relativePath")
}

[System.IO.File]::WriteAllText(
    $OutputFile,
    $builder.ToString(),
    [System.Text.UTF8Encoding]::new($false)
)

Write-Host ""
Write-Host "Export abgeschlossen."
Write-Host "Gefundene Dateien: $($files.Count)"
Write-Host "Ausgabedatei: $OutputFile"