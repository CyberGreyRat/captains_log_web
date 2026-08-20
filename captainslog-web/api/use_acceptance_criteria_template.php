<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    if (empty($_SESSION['user_id'])) throw new Exception('Nicht angemeldet.');
    $data=json_decode(file_get_contents('php://input'),true)?:[];
    $ids=array_values(array_filter(array_map('intval',$data['ids']??[])));
    if($ids){
        $marks=implode(',',array_fill(0,count($ids),'?'));
        $stmt=$pdo->prepare("UPDATE acceptance_criteria_templates SET usage_count=usage_count+1 WHERE id IN ($marks)");
        $stmt->execute($ids);
    }
    echo json_encode(['success'=>true]);
} catch(Throwable $e){http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()]);}
