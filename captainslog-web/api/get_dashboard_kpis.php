<?php // api/get_dashboard_kpis.php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    // Wir laden jetzt auch die 'children' Spalte mit!
    $stmt = $pdo->prepare("SELECT id, req_key, title, review_status, type, children FROM requirements WHERE project_id = ? ORDER BY req_key ASC");
    $stmt->execute([$project_id]);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 1. Schnelles Lookup-Array für alle Status bauen
    $status_map = [];
    foreach ($all as $r) {
        $status_map[$r['req_key']] = $r['review_status'];
    }

    // 2. Risk-Treatment Logik berechnen
    foreach ($all as &$r) {
        if ($r['type'] === 'RISK') {
            $children = json_decode($r['children'] ?: '[]', true);
            $child_details = []; // NEU: Array für Details der Kinder

            if (empty($children)) {
                $r['mitigation_status'] = 'Unbehandelt';
                $r['mitigation_color'] = 'bg-red-100 text-red-800 border-red-200';
            } else {
                $all_approved = true;
                foreach ($children as $child_key) {
                    $child_status = $status_map[$child_key] ?? 'Neu';

                    // Details für das Frontend speichern
                    foreach ($all as $child_req) {
                        if ($child_req['req_key'] === $child_key) {
                            $child_details[] = [
                                'key' => $child_req['req_key'],
                                'title' => $child_req['title'],
                                'status' => $child_status
                            ];
                            break;
                        }
                    }

                    if ($child_status !== 'Geprüft & Freigegeben') {
                        $all_approved = false;
                    }
                }

                if ($all_approved) {
                    $r['mitigation_status'] = 'Behandelt & Verifiziert';
                    $r['mitigation_color'] = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                } else {
                    $r['mitigation_status'] = 'In Umsetzung';
                    $r['mitigation_color'] = 'bg-amber-100 text-amber-800 border-amber-200';
                }
            }
            $r['child_details'] = $child_details; // Ans Frontend übergeben
        }
    }

    $waiting = array_values(array_filter($all, function ($r) {
        return $r['review_status'] === 'Wartet auf Überprüfung'; }));
    $approved = array_values(array_filter($all, function ($r) {
        return $r['review_status'] === 'Geprüft & Freigegeben'; }));
    $risks = array_values(array_filter($all, function ($r) {
        return $r['type'] === 'RISK'; }));
    $sec = array_values(array_filter($all, function ($r) {
        return $r['type'] === 'SEC'; }));

    echo json_encode([
        'success' => true,
        'kpis' => [
            'total' => ['count' => count($all), 'items' => $all],
            'waiting' => ['count' => count($waiting), 'items' => $waiting],
            'approved' => ['count' => count($approved), 'items' => $approved],
            'risks' => ['count' => count($risks), 'items' => $risks],
            'sec' => ['count' => count($sec), 'items' => $sec]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>