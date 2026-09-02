<?php
require '../config/db.php';
$data=json_decode(file_get_contents('php://input'),true);
$tc=(int)$data['test_case_id'];
$pdo->prepare('DELETE FROM tc_criterion_mapping WHERE test_case_id=?')->execute([$tc]);
$ins=$pdo->prepare('INSERT INTO tc_criterion_mapping(test_case_id,requirement_id,criterion_index) VALUES(?,?,?)');
foreach(($data['criteria']??[]) as $c){$ins->execute([$tc,$c['requirement_id'],$c['criterion_index']]);}
echo json_encode(['success'=>true]);
