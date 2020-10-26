<?php

require_once "includes/Mailer.php";

$dbHandler = new PDO('mysql:host=localhost;'.'dbname=pmb;charset=utf8', 'pmb', 'Bd5d4LT.2020');

$sqlQuery = "SELECT date_format(pret_date, '%d/%m/%Y') as aff_pret_date, ";
$sqlQuery.= " date_format(pret_retour, '%d/%m/%Y') as aff_pret_retour, ";
$sqlQuery.= " DATEDIFF(curdate(), pret_retour) as retard, ";
$sqlQuery.= " id_empr, empr_nom, empr_prenom, empr_mail, empr_cb, expl_cote,";
$sqlQuery.= " notices_m.notice_id as m_id, notices_s.notice_id as s_id, expl_cb, expl_notice,tdoc_libelle, section_libelle, location_libelle, ";
$sqlQuery.= " expl_bulletin, notices_m.notice_id as idnot, trim(concat(ifnull(notices_m.tit1,''),";
$sqlQuery.= " ifnull(notices_s.tit1,''),' ',ifnull(bulletin_numero,''), if (mention_date, concat(' (',mention_date,')') ,''))) as tit ";
$sqlQuery.= "FROM (((exemplaires LEFT JOIN notices AS notices_m ON expl_notice = notices_m.notice_id ) ";
$sqlQuery.= "        LEFT JOIN bulletins ON expl_bulletin = bulletins.bulletin_id) ";
$sqlQuery.= "        LEFT JOIN notices AS notices_s ON bulletin_notice = notices_s.notice_id), ";
$sqlQuery.= "        docs_type, docs_section, docs_location, pret,empr ";
$sqlQuery.= "WHERE ";
$sqlQuery.= " expl_typdoc = idtyp_doc and pret_idexpl = expl_id  and empr.id_empr = pret.pret_idempr  and expl_section = idsection and expl_location = idlocation";
$sqlQuery.= " and pret_retour < curdate() order by id_empr;";

$query = $dbHandler->prepare($sqlQuery);
$query->execute();
$mailingList = $query->fetchAll(PDO::FETCH_OBJ);
$titleList = null;
$idUser = null;

function Testing($receiver,$titleList,$idUser){
    $msg = "\n **************************** ";
    $msg.= "\n" . "Mensaje para " . $receiver . " UserID: " . $idUser . "\n";
    $msg.= "\n" . $titleList;
    $msg.= "\n **************************** ";
    $msg.= "\n";
    $fp = fopen("/home/pmb/envio-mails/test_log","a");
    fwrite($fp, $msg);
    fclose($fp);
}

if(isset($mailingList)){
    foreach ($mailingList as $user) {
        $title = "\n" . $user->tit;
        $title.= "\nFecha del préstamo: " . $user->aff_pret_date . " Devolución el: " . $user->aff_pret_retour; 
        $title.= "\n" . $user->location_libelle . " : " . $user->section_libelle . " (" . $user->expl_cb .  ") " . "\n";

        if ($idUser == null){
            $idUser = $user->id_empr;
        }
        // Chequeo si el usuario en esta iteración es distinto al anterior
        if ($idUser <> $user->id_empr) {
            if (next($mailingList) == false){
                $receiver = $user->empr_mail;
                $titleList = $title;
                $idUser = $user->id_empr;
                Testing($receiver,$titleList,$idUser);
            } else {
                Testing($receiver,$titleList,$idUser);
                // Guardo el título del usuario actual
                $titleList = $title;
            }
        } else {
            // Anexo el título del actual usuario a la lista a enviar
            $titleList.= $title;
            if (next($mailingList) == false){
                Testing($receiver,$titleList,$idUser);
            }
        }
        $idUser = $user->id_empr;
        $receiver = $user->empr_mail;
    }
}
?>