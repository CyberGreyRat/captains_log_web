<?php
require '../config/db.php';
$data=json_decode(file_get_contents('php://input'),true);
$testCaseId=(int)$data['test_case_id'];
$criteria=$data['criteria'] ?? [];
$pdo->beginTransaction();
$pdo->prepare('DELETE FROM criterion_test_links WHERE test_case_id=?')->execute([$testCaseId]);
$q=$pdo->prepare('INSERT INTO criterion_test_links(requirement_id,criterion_index,test_case_id) VALUES(?,?,?)');
foreach($criteria as $c){$q->execute([(int)$c['requirement_id'],(int)$c['criterion_index'],$testCaseId]);}
$pdo->commit();
echo json_encode(['success'=>true]);
