<?php
// api/verify_criterion.php
ini_set('display_errors', 0); error_reporting(E_ALL); session_start();
require '../config/db.php'; header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$req_id = $data['req_id'] ?? null;
$idx = $data['criterion_idx'] ?? null;
$note = $data['note'] ?? '';

$user_id = $_SESSION['user_id'] ?? 1;
$user = $_SESSION['username'] ?? 'admin';
$hostname = $_SESSION['hostname'] ?? 'LocalPC';

if (!$req_id || $idx === null) {
    echo json_encode(['success' => false, 'error' => 'Fehlende Daten']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM requirements WHERE id = ?");
    $stmt->execute([$req_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) throw new Exception("Element nicht gefunden");

    // Attribute absolut kugelsicher auslesen
    $attrs = $req['attributes'];
    if (is_string($attrs)) $attrs = json_decode($attrs, true) ?: [];
    if (!isset($attrs['criteria_states'])) $attrs['criteria_states'] = [];
    if (is_string($attrs['criteria_states'])) $attrs['criteria_states'] = json_decode($attrs['criteria_states'], true) ?: [];

    // Kriterium abhaken
    $attrs['criteria_states'][$idx] = [
        'checked' => true,
        'by' => $user,
        'date' => date('d.m.Y H:i'),
        'note' => $note
    ];

    // Status berechnen
    $raw_lines = explode("\n", $req['acceptance_criteria']);
    $valid_count = 0;
    $checked_count = 0;
    foreach ($raw_lines as $i => $line) {
        if (trim(preg_replace('/^-\s*/', '', $line)) !== '') {
            $valid_count++;
            if (isset($attrs['criteria_states'][$i]['checked']) && $attrs['criteria_states'][$i]['checked']) {
                $checked_count++;
            }
        }
    }

    $auto_approved = ($valid_count > 0 && $valid_count === $checked_count);
    $new_status = $auto_approved ? 'Geprüft & Freigegeben' : $req['review_status'];
    $new_attrs_json = json_encode($attrs, JSON_UNESCAPED_UNICODE);

    // Nur bei echter Änderung schreiben
    if ($req['attributes'] !== $new_attrs_json || $req['review_status'] !== $new_status) {
        
        // 1. Die Datenbank aktualisieren (Löst den automatischen "root@localhost"-Trigger aus)
        $update_stmt = $pdo->prepare("UPDATE requirements SET attributes = ?, review_status = ? WHERE id = ?");
        $update_stmt->execute([$new_attrs_json, $new_status, $req_id]);

        // 2. MAGIC TRICK: Wir überschreiben sofort den Namen im gerade erstellten Trigger-Log!
        $fixLogStmt = $pdo->prepare("
            UPDATE audit_log 
            SET actor_name = ?, actor_user_id = ?, source_type = 'web', source_name = 'verify_criterion.php', hostname = ? 
            WHERE entity_type = 'requirement' AND entity_id = ? AND actor_name = 'root@localhost' 
            ORDER BY created_at DESC LIMIT 1
        ");
        $fixLogStmt->execute([$user, $user_id, $hostname, $req_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'auto_approved' => $auto_approved]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>