<?php
session_start();
require '../config/db.php';

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    echo json_encode([]);
    exit;
}

// Fettes JOIN über alle Tabellen
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        s.email, s.phone, s.organization, s.role, s.influence,
        us.us_role, us.us_action, us.us_benefit, us.acceptance_criteria, us.story_points,
        uc.primary_actor, uc.preconditions, uc.main_scenario, uc.alt_scenario
    FROM requirements r
    LEFT JOIN req_stakeholders s ON r.id = s.requirement_id
    LEFT JOIN req_user_stories us ON r.id = us.requirement_id
    LEFT JOIN req_use_cases uc ON r.id = uc.requirement_id
    WHERE r.project_id = ?
");
$stmt->execute([$project_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($rows as $row) {
    // Standard-Felder decodieren
    $row['parents'] = json_decode($row['parents'] ?: '[]');
    $row['children'] = json_decode($row['children'] ?: '[]');
    
    // Leeres Array für Attribute vorbereiten
    $attrs = [];
    
    if ($row['type'] === 'STK') {
        $attrs = [
            'email' => $row['email'],
            'phone' => $row['phone'],
            'organization' => $row['organization'],
            'role' => $row['role'],
            'influence' => $row['influence']
        ];
    } elseif ($row['type'] === 'US') {
        $attrs = [
            'us_role' => $row['us_role'],
            'us_action' => $row['us_action'],
            'us_benefit' => $row['us_benefit'],
            'acceptance_criteria' => $row['acceptance_criteria'],
            'story_points' => $row['story_points']
        ];
    } elseif ($row['type'] === 'UC') {
        $attrs = [
            'primary_actor' => $row['primary_actor'],
            'preconditions' => $row['preconditions'],
            'main_scenario' => $row['main_scenario'],
            'alt_scenario' => $row['alt_scenario']
        ];
    }
    
    // Alte JSON-Attribute aus der requirements-Tabelle (falls noch Alt-Daten da sind)
    $oldAttrs = json_decode($row['attributes'] ?: '{}', true) ?: [];
    
    // Wir überschreiben das attributes-Feld im $row mit unserem sauberen Array!
    $row['attributes'] = array_merge($oldAttrs, $attrs);
    
    // Die JOIN-Spalten aufräumen, damit wir keinen Müll ans Frontend senden
    unset($row['email'], $row['phone'], $row['organization'], $row['role'], $row['influence']);
    unset($row['us_role'], $row['us_action'], $row['us_benefit'], $row['acceptance_criteria'], $row['story_points']);
    unset($row['primary_actor'], $row['preconditions'], $row['main_scenario'], $row['alt_scenario']);

    $result[] = $row;
}

echo json_encode($result);