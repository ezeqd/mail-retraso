<?php
require_once "includes/Mailer.php";

$dbHandler = new PDO('mysql:host=localhost;'.'dbname=pmblatu2;charset=utf8', 'root', 'ezequiel');

$sqlQuery = "SELECT date_format(pret_date, '%d/%m/%Y') as aff_pret_date, ";
$sqlQuery.= " date_format(pret_retour, '%d/%m/%Y') as aff_pret_retour, ";
$sqlQuery.= " IF(pret_retour=CURDATE()+365,1,0) as retard, ";
$sqlQuery.= " id_empr, empr_nom, empr_prenom, empr_mail, id_empr, empr_cb, expl_cote,";
$sqlQuery.= " notices_m.notice_id as m_id, notices_s.notice_id as s_id, expl_cb, expl_notice,tdoc_libelle, section_libelle, location_libelle, ";
$sqlQuery.= " expl_bulletin, notices_m.notice_id as idnot, trim(concat(ifnull(notices_m.tit1,''),";
$sqlQuery.= " ifnull(notices_s.tit1,''),' ',ifnull(bulletin_numero,''), if (mention_date, concat(' (',mention_date,')') ,''))) as tit ";
$sqlQuery.= "FROM (((exemplaires LEFT JOIN notices AS notices_m ON expl_notice = notices_m.notice_id ) ";
$sqlQuery.= "        LEFT JOIN bulletins ON expl_bulletin = bulletins.bulletin_id) ";
$sqlQuery.= "        LEFT JOIN notices AS notices_s ON bulletin_notice = notices_s.notice_id), ";
$sqlQuery.= "        docs_type, docs_section, docs_location, pret,empr ";
$sqlQuery.= "WHERE ";
$sqlQuery.= " expl_typdoc = idtyp_doc and pret_idexpl = expl_id  and empr.id_empr = pret.pret_idempr  and expl_section = idsection and expl_location = idlocation";
$sqlQuery.= " and pret_retour < curdate() order by pret_retour, empr_nom, empr_prenom limit 2;";

$query = $dbHandler->prepare($sqlQuery);
$query->execute();
$mailingList = $query->fetchAll(PDO::FETCH_OBJ);

//$mailingList = array('ezequiel.domenech@gmail.com', 'cristian@braintly.com', 'cristianclasasadonte@gmail.com');
if(isset($mailingList)){
    foreach ($mailingList as $user) {
        $userMail = $user->empr_mail;
        if ($userMail){
            $receiver = $userMail;
            $subject = "Documentos atrasados: " . $user->tit;
            $body = "Estimado usuario/a,";
            $body.= "\nSegún nuestros registros usted posee en préstamo los siguientes documentos cuyo plazo de devolución ha vencido: \n";
            $body.= "\n" . $user->tit . "\n";
            $body.= "\nLe agradecemos que se ponga en contacto con nosotros para renovarlos o devolverlos." . "\n";
            $body.= "\nCentro de Información Técnica ";
            $body.= "\nTel. 2601 37 24 int. 1314, 1350";
            $body.= "\nDir. Av. Italia 6201";
            $body.= "\nMontevideo-Uruguay";
            $body.= "\nditec@latu.org.uy";

            $mail = new Mailer($receiver, $subject, $body);
            $error = $mail->sendMail();
            if (empty($error)){
                echo "se envió ";
            } else {
                echo "no se envió ";
            }
        } else {
            echo "ERROR user vacio";
        }
    }
}