<?php
// api/get_dashboard_kpis.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) { echo json_encode(['success' => false]); exit; }

try {
    // 1. Anforderungen & Risiken
    $stmt = $pdo->prepare("SELECT id, req_key, title, review_status, type, children, parents, attributes FROM requirements WHERE project_id = ? ORDER BY req_key ASC");
    $stmt->execute([$project_id]);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Projektplan Fortschritt berechnen
    $tStmt = $pdo->prepare("SELECT progress_pct FROM project_tasks WHERE project_id = ? AND parent_id IS NULL");
    $tStmt->execute([$project_id]);
    $tasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);
    $total_task_progress = 0;
    if (count($tasks) > 0) {
        $sum = 0;
        foreach($tasks as $t) $sum += (int)$t['progress_pct'];
        $total_task_progress = round($sum / count($tasks));
    }

    // 3. SBOM Warnungen (Fehlende Lizenzen)
    $sbomStmt = $pdo->prepare("SELECT sbom_data FROM project_sboms WHERE project_id = ? ORDER BY id DESC LIMIT 1");
    $sbomStmt->execute([$project_id]);
    $sbomRow = $sbomStmt->fetch();
    $sbom_warnings = 0;
    if ($sbomRow && !empty($sbomRow['sbom_data'])) {
        $sbom = json_decode($sbomRow['sbom_data'], true);
        if (isset($sbom['packages'])) {
            foreach ($sbom['packages'] as $pkg) {
                $lic = $pkg['licenseDeclared'] ?? $pkg['licenseConcluded'] ?? 'NONE';
                if ($lic === 'NONE' || $lic === 'NOASSERTION') $sbom_warnings++;
            }
        }
    }

    // 4. Stakeholder laden (Jetzt inkl. Rolle und Position für die Sortierung)
    $sStmt = $pdo->prepare("SELECT id, name, role, position, influence, interest FROM stakeholders WHERE project_id = ?");
    $sStmt->execute([$project_id]);
    $stakeholders = $sStmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter für die restlichen KPIs
    $waiting = array_values(array_filter($all, fn($r) => $r['review_status'] === 'Wartet auf Überprüfung'));
    $approved = array_values(array_filter($all, fn($r) => $r['review_status'] === 'Geprüft & Freigegeben'));
    $risks = array_values(array_filter($all, fn($r) => $r['type'] === 'RISK'));
    $sec = array_values(array_filter($all, fn($r) => $r['type'] === 'SEC'));

    echo json_encode([
        'success' => true,
        'kpis' => [
            'total' => ['count' => count($all), 'items' => $all],
            'waiting' => ['count' => count($waiting), 'items' => $waiting],
            'approved' => ['count' => count($approved), 'items' => $approved],
            'risks' => ['count' => count($risks), 'items' => $risks],
            'sec' => ['count' => count($sec), 'items' => $sec]
        ],
        'project_progress' => $total_task_progress,
        'sbom_warnings' => $sbom_warnings,
        'stakeholders' => $stakeholders
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}