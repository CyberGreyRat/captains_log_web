<?php
require '../config/db.php';
$id=(int)($_GET['test_case_id']??0);
$q=$pdo->prepare('SELECT * FROM tc_criterion_mapping WHERE test_case_id=?');
$q->execute([$id]);
echo json_encode(['success'=>true,'items'=>$q->fetchAll(PDO::FETCH_ASSOC)]);
