<?php
require '../config/db.php';
$data=json_decode(file_get_contents('php://input'),true);
$testCaseId=(int)$data['test_case_id'];
$criteria=$data['criteria'];
foreach($criteria as $item){
 if($item['passed']){
   $u=$pdo->prepare('UPDATE criterion_test_links SET verification_status="passed" WHERE requirement_id=? AND criterion_index=? AND test_case_id=?');
   $u->execute([$item['requirement_id'],$item['criterion_index'],$testCaseId]);
 }
}
// Requirement automatisch freigeben
foreach($criteria as $item){
 $req=(int)$item['requirement_id'];
 $c=$pdo->prepare('SELECT COUNT(*) total,SUM(verification_status="passed") donecnt FROM criterion_test_links WHERE requirement_id=?');
 $c->execute([$req]);
 $r=$c->fetch(PDO::FETCH_ASSOC);
 if($r['total']>0 && $r['total']==$r['donecnt']){
   $pdo->prepare("UPDATE requirements SET review_status='Geprüft & Freigegeben' WHERE id=?")->execute([$req]);
 }
}
echo json_encode(['success'=>true]);
