<?php
declare(strict_types=1);
ini_set('display_errors','0');error_reporting(E_ALL);session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/docx_template_service.php';

function pv(array $r,string $k,string $f=''):string{return trim((string)($r[$k]??$f));}
function safe(string $v):string{return trim((string)preg_replace('/[^A-Za-z0-9._-]+/u','_',$v),'_')?:'Projekt';}
function rowFor(array $r):array{
    $spec=pv($r,'title');$desc=pv($r,'description');if($desc!==''&&$desc!==$spec)$spec.="\n".$desc;
    $parents=json_decode(pv($r,'parents','[]'),true)?:[];$children=json_decode(pv($r,'children','[]'),true)?:[];$refs=[];
    if($parents)$refs[]='Parents: '.implode(', ',$parents);if($children)$refs[]='Children: '.implode(', ',$children);
    $notes=['Typ: '.pv($r,'type','REQ'),'Status: '.pv($r,'review_status','Neu')];
    foreach(['source_reference'=>'Quelle-ID: ','source_document'=>'Dokument: ','source_page'=>'Seite: ','rationale'=>'Begründung: '] as $key=>$prefix)if(pv($r,$key)!=='')$notes[]=$prefix.pv($r,$key);
    return [pv($r,'req_key'),$spec,pv($r,'acceptance_criteria','-'),implode("\n",$refs),implode("\n",$notes)];
}
function chapterFor(array $r):string{
    $source=pv($r,'source_reference');
    if(preg_match('/^(\d{2,3})\./',$source,$m)){
        $major=(int)$m[1];
        return match($major){11=>'11',12=>'12',13=>'13',14=>'14',41=>'41',42=>'42',51=>'51',52=>'52',53=>'53',61=>'61',62=>'62',71=>'71',72=>'72',73=>'73',74=>'74',81=>'81',82=>'82',91=>'91',92=>'92',101=>'101',102=>'102',111=>'111',112=>'112',121=>'121',122=>'122',131=>'131',132=>'132',default=>'41'};
    }
    return match(strtoupper(pv($r,'type','SYS'))){'HRS'=>'11','SRS','SWC'=>'13','ENV'=>'62',default=>'41'};
}
try{
    if(empty($_SESSION['user_id']))throw new RuntimeException('Nicht angemeldet.');
    $in=json_decode(file_get_contents('php://input'),true)?:[];$projectId=trim((string)($in['project_id']??''));if($projectId==='')throw new RuntimeException('Projekt-ID fehlt.');
    if(($_SESSION['role']??'')!=='admin'){$q=$pdo->prepare('SELECT 1 FROM project_members WHERE project_id=? AND user_id=? AND is_active=1');$q->execute([$projectId,(int)$_SESSION['user_id']]);if(!$q->fetchColumn())throw new RuntimeException('Kein Projektzugriff.');}
    $q=$pdo->prepare('SELECT * FROM projects WHERE id=?');$q->execute([$projectId]);$project=$q->fetch(PDO::FETCH_ASSOC);if(!$project)throw new RuntimeException('Projekt nicht gefunden.');
    $q=$pdo->prepare('SELECT * FROM requirements WHERE project_id=? ORDER BY COALESCE(serial_number,id),id');$q->execute([$projectId]);$requirements=$q->fetchAll(PDO::FETCH_ASSOC);
    $q=$pdo->prepare('SELECT * FROM stakeholders WHERE project_id=? ORDER BY name');$q->execute([$projectId]);$stakeholders=$q->fetchAll(PDO::FETCH_ASSOC);

    $template=__DIR__.'/../storage/templates/Projekt_PH _1.0.docx';if(!is_file($template)){$c=glob(__DIR__.'/../storage/templates/*Projekt*PH*1.0*.docx')?:[];$template=$c[0]??'';}if(!$template||!is_file($template))throw new RuntimeException('Vorlage unter storage/templates nicht gefunden.');
    $tmp=tempnam(sys_get_temp_dir(),'cl_ph_');if($tmp===false)throw new RuntimeException('Temporäre Datei konnte nicht erstellt werden.');@unlink($tmp);$tmp.='.docx';if(!copy($template,$tmp))throw new RuntimeException('Vorlage konnte nicht kopiert werden.');

    $service=new DocxTemplateService($tmp);$projectName=pv($project,'name','Projektname');$service->replaceText('Projektname',$projectName);$service->cleanProjectBrackets();
    $customerText=trim((string)($in['customer']??''));$customerStakeholder=null;foreach($stakeholders as $s){if($customerText!==''&&(stripos(pv($s,'name'),$customerText)!==false||stripos(pv($s,'role'),$customerText)!==false)){$customerStakeholder=$s;break;}}if(!$customerStakeholder){foreach($stakeholders as $s){if(stripos(pv($s,'role'),'auftraggeber')!==false){$customerStakeholder=$s;break;}}}
    $customer=['company'=>$customerText,'name'=>$customerStakeholder?pv($customerStakeholder,'name'):$customerText,'street'=>'','city'=>'','phone'=>$customerStakeholder?pv($customerStakeholder,'phone'):'','email'=>$customerStakeholder?pv($customerStakeholder,'email'):''];
    $contractor=['company'=>'EPSa - Elektronik & Präzisionsbau Saalfeld GmbH','name'=>trim((string)($in['manager']??'')),'street'=>'Remschützer Straße 1','city'=>'07318 Saalfeld','phone'=>'03671/595-0','email'=>'saalfeld@epsa.de'];
    $service->fillCover($customer,$contractor,['author'=>trim((string)($in['author']??($_SESSION['username']??''))),'manager'=>trim((string)($in['manager']??''))]);
    $service->insertProjectDescription(pv($project,'description'));

    $documents=[];foreach($requirements as $r){$d=pv($r,'source_document');if($d!=='')$documents[$d]=true;}$docRows=[];$n=1;foreach(array_keys($documents) as $d)$docRows[]=['('.$n++.')',$d];$service->fillTableAfterExactHeading('Mitgeltende Dokumente',$docRows,2);
    $service->fillTableAfterExactHeading('Versionsübersicht',[[trim((string)($in['version']??'1.0.0')),date('d.m.Y'),'alle','Export aus Captain\'s Log',trim((string)($in['author']??($_SESSION['username']??'')))]],5);

    $groups=[];foreach(['11','12','13','14','41','42','51','52','53','61','62','71','72','73','74','81','82','91','92','101','102','111','112','121','122','131','132'] as $key)$groups[$key]=[];
    foreach($requirements as $r)$groups[chapterFor($r)][]=rowFor($r);
    $anchors=['11'=>'11.001','12'=>'12.001','13'=>'13.001','14'=>'14.001','41'=>'41.010','51'=>'51.010','52'=>'52.010','53'=>'53.010','61'=>'61.010','62'=>'62.010','71'=>'71.010','72'=>'72.010','73'=>'73.010','74'=>'74.010','81'=>'81.010','82'=>'82.010','91'=>'91.010','92'=>'92.010','101'=>'101.010','102'=>'102.010','111'=>'111.010','112'=>'112.010','121'=>'121.010','122'=>'122.010','131'=>'131.010','132'=>'132.010'];
    foreach($anchors as $chapter=>$anchor)$service->fillTableByAnchor($anchor,$groups[$chapter],5);
    // Projektqualität hat in der Vorlage keine eigene Tabelle. Anforderungen 42.xxx werden kompakt bei Produktqualität ergänzt.
    if($groups['42'])$service->fillTableByAnchor('41.010',array_merge($groups['41'],$groups['42']),5);

    $service->save();$zip=new ZipArchive();$res=$zip->open($tmp,ZipArchive::CHECKCONS);if($res!==true)throw new RuntimeException('Erzeugtes DOCX ist ungültig. ZIP-Code '.$res);foreach(['[Content_Types].xml','word/document.xml'] as $entry)if($zip->locateName($entry)===false)throw new RuntimeException('DOCX-Bestandteil fehlt: '.$entry);$zip->close();
    while(ob_get_level()>0)ob_end_clean();$name=safe($projectName).'_Pflichtenheft_'.safe(trim((string)($in['version']??'1.0.0'))).'.docx';header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($tmp));readfile($tmp);@unlink($tmp);
}catch(Throwable $e){while(ob_get_level()>0)ob_end_clean();http_response_code(500);header('Content-Type: text/plain; charset=utf-8');echo 'Pflichtenheft-Exportfehler: '.$e->getMessage();}
