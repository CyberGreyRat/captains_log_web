<?php
require_once __DIR__.'/verification_common.php';require_once __DIR__.'/coverage_lib.php';vuser();try{$id=trim((string)($_GET['document_id']??''));vout(['success'=>true,'coverage'=>coverageCalculate($pdo,$id)]);}catch(Throwable$e){vout(['success'=>false,'error'=>$e->getMessage()],500);}
