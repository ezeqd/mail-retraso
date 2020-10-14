<?php
/**
 * Modulo de envío de mails. Se encarga de enviar mails de manera automática todos los usuarios
 * que registren atrasos en la devolución de libros o documentos.
 * Este modulo también se encarga de registrar los posibles errores en 
 * un archivo ubicado en '/tmp/mailer_log'.
 */
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

$body = null;
$titleList = null;
$idUser = null;

$header = "Estimado usuario/a," . "\n"; 
$header.= "\nSegún nuestros registros usted posee en préstamo los siguientes documentos cuyo plazo de devolución ha vencido: " . "\n";

$signLatu = "\nLe agradeceríamos que se pusiera en contacto con nosotros para ver la posibilidad de renovar o devolver estos documentos.";
$signLatu.= "\nSi desea puede renovarlos respondiendo a este correo electrónico con la solicitud pertinente.";
$signLatu.= "\nPor el sólo hecho de hacerlo se asume que se poseen todos los materiales listados y la responsabilidad por los mismos." . "\n";
$signLatu.= "\nCentro de Información Técnica ";
$signLatu.= "\nTel. 2601 37 24 int. 1314, 1350";
$signLatu.= "\nDir. Av. Italia 6201";
$signLatu.= "\nMontevideo-Uruguay";
$signLatu.= "\nditec@latu.org.uy" . "\n";
$signLatu.= "\nCITEIN";
$signLatu.= "\nCorreo electrónico ditec@latu.org.uy";
$signLatu.= "\nPágina web https://catalogo.latu.org.uy/opac_css";

function Send($receiver, $subject, $body, $idUser){
    $mail = new Mailer($receiver, $subject, $body);
    
    $error = $mail->sendMail();
    if (empty($error)) {
        $msg = "Mensaje a " . $receiver . " ha sido enviado.\n";
    } else {
        $msg = "Mailer Error: " . $error . "\n";
        $msg.= "Mensaje a " . $receiver . " NO ha sido enviado." . " UserID: " . $idUser . "\n";
    }
    $fp = fopen("/home/pmb/envio-mails/mailer_log","a");
    fwrite($fp, $msg);
    fclose($fp);
}

if(isset($mailingList)){
    foreach ($mailingList as $user) {    
        $title = "\n" . $user->tit;
        $title.= "\nFecha del préstamo: " . $user->aff_pret_date . " Devolución el: " . $user->aff_pret_retour; 
        $title.= "\n" . $user->location_libelle . " : " . $user->section_libelle . " (" . $user->expl_cb .  ") " . "\n";

        wordwrap($body, 70, "\r\n", TRUE);

        if ($idUser == null){
            $idUser = $user->id_empr;
        }
        // Chequeo si el usuario en esta iteración es distinto al anterior
        if ($idUser <> $user->id_empr){
            $body = $header . $titleList . $signLatu;
            Send($receiver, $subject, $body, $idUser);
            // Guardo el título del usuario actual
            $titleList = $title;
        } else {
            // Anexo el título del actual usuario a la lista a enviar
            $titleList.= $title;
        }
        $idUser = $user->id_empr;
        $receiver = $user->empr_mail;
        $subject = "CITEIN : préstamos atrasados: " . $user->empr_prenom . "  " . $user->empr_nom . " (" . $user->id_empr . ") ";
        // Chequeo que haya otro usuario
        if (next($mailingList) == false){
            $body = $header . $titleList . $signLatu;
            Send($receiver, $subject, $body, $idUser);
        }
    }
}
?>