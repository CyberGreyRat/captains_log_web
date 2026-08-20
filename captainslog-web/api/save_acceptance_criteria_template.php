<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) throw new Exception('Nicht angemeldet.');
    $data=json_decode(file_get_contents('php://input'),true)?:[];
    $type=strtoupper(trim($data['type']??''));
    $category=trim($data['category']??'Benutzerdefiniert');
    $criteria=$data['criteria']??[];
    if (is_string($criteria)) $criteria=preg_split('/\R/u',$criteria,-1,PREG_SPLIT_NO_EMPTY);
    if (!$type || !is_array($criteria)) throw new Exception('Typ oder Kriterien fehlen.');

    $stmt=$pdo->prepare("INSERT INTO acceptance_criteria_templates
        (requirement_type,category,criterion_text,keywords,source_type,usage_count,created_by,criterion_hash)
        VALUES(?,?,?,?, 'learned',1,?,?)
        ON DUPLICATE KEY UPDATE usage_count=usage_count+1,is_active=1,updated_at=CURRENT_TIMESTAMP");
    $saved=0;
    foreach($criteria as $criterion){
        $criterion=trim(preg_replace('/^[-•*\s]+/u','',(string)$criterion));
        if(mb_strlen($criterion)<12) continue;
        $hash=hash('sha256',$type.'|'.mb_strtolower($criterion));
        $keywords=trim($data['keywords']??'');
        $stmt->execute([$type,mb_substr($category,0,100),$criterion,mb_substr($keywords,0,500),(int)$_SESSION['user_id'],$hash]);
        $saved++;
    }
    echo json_encode(['success'=>true,'saved'=>$saved],JSON_UNESCAPED_UNICODE);
} catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
}
