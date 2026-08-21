<?php
ini_set('display_errors','0');session_start();require_once __DIR__.'/../config/db.php';require_once __DIR__.'/requirements_import_common.php';$uid=imp_user();
try{$d=json_decode(file_get_contents('php://input'),true)?:[];$q=$pdo->prepare('DELETE FROM requirement_import_profiles WHERE id=? AND created_by=?');$q->execute([(int)($d['id']??0),$uid]);imp_json(['success'=>true]);}catch(Throwable $e){imp_json(['success'=>false,'error'=>$e->getMessage()],400);}
