<?php

$lang = "es_ES";
$pmb_indexation_lang = $lang;
////      INICIO IMPORT DEL INDEX.php

$base_path = ".";

include_once ("./includes/error_report.inc.php");
include_once ("./includes/global_vars.inc.php");
include_once ("./includes/config.inc.php");

//require_once ("$base_path/includes/init.inc.php");  

if (file_exists("$include_path/db_param.inc.php"))
    require_once("$include_path/db_param.inc.php");
require_once("$include_path/mysql_connect.inc.php");
$dbh = connection_mysql();
require_once("$include_path/marc_tables/$pmb_indexation_lang/empty_words");
require_once("$include_path/misc.inc.php");
require_once("$javascript_path/misc.inc.php");
require_once ("$include_path/notice_authors.inc.php");
require_once($include_path . "/mail.inc.php");
require_once ($include_path . "/mailing.inc.php");
require_once ($include_path . "/fpdf.inc.php");

require_once ("$class_path/author.class.php");
require_once ($class_path . "/serie.class.php");
//INIT
//require_once ("$base_path/includes/init.inc.php"); 
include("$include_path/start.inc.php");
require_once("$include_path/sessions.inc.php");
require_once("$include_path/clean_pret_temp.inc.php");
require_once($class_path . "/parameters_subst.class.php");
require_once($base_path . "/includes/global_vars.inc.php");
require_once("$include_path/marc_tables/$pmb_indexation_lang/empty_words");
require_once("$class_path/marc_table.class.php");
require_once("$class_path/docs_location.class.php");

checkUser('PhpMyBibli');

$parameter_subst = new parameters_subst($include_path . "/parameters_subst/rfid_per_localisations.xml", $deflt2docs_location);
$parameter_subst->extract();

include_once("$class_path/XMLlist.class.php");
////
$messages = new XMLlist("$include_path/messages/$lang.xml", 0);
$messages->analyser();
$msg = $messages->table;

// Activation RFID selon les prefs user
if ($pmb_rfid_activate)
    $pmb_rfid_activate = $param_rfid_activate;
// Pr paration des js sripts pour la RFID
if ($pmb_rfid_activate) {
    require_once($include_path . "/rfid_config.inc.php");
    get_rfid_js_header();
}
$tipo = false;
if ($_GET["tipo"]) {
    $tipo = $_GET["tipo"];
}

if($tipo == false){
	$mandarMailsPreretraso = MandarMailsPreretraso("test");
}else{
	if($tipo == "retraso"){
		//solo mando si tipo=retraso
		$mand_retraso = mandarMailRetraso($tipo);
	}
}
mysql_close($dbh);

function MandarMailsPreretraso($param) {

    global $UMmailretard_cant_dias,
    $dbh,
    $msg,
    $parameter_subst,
    $UMmailretard_1objet,
    $UMmailretard_1fdp,
    $UMmailretard_1after_list,
    $UMmailretard_1before_list,
    $UMmailretard_mail_bcc,
    $UMmailretard_mail_resumen,
    $UMmailretard_1madame_monsieur;
    global $biblio_name,
    $biblio_email,
    $biblio_phone,
    $PMBuseremailbcc;
//  $biblio_name = "Biblioteca";
//  $biblio_email = "reservas-biblio@um.edu.uy";
//  $biblio_phone = "27074461";
//  $PMBuseremailbcc = "mvidal@um.edu.uy";
    if (!$relance)
        $relance = 1;
    if ($deflt2docs_location)
        $requete_param = "SELECT * FROM docs_location where idlocation='$deflt2docs_location'";
    else
        $requete_param = "SELECT * FROM docs_location limit 1";
    $res_param = mysql_query($requete_param, $dbh);
    $obj_location = mysql_fetch_object($res_param);
    $biblio_name = $obj_location->name;
    $biblio_adr1 = $obj_location->adr1;
    $biblio_adr2 = $obj_location->adr2;
    $biblio_cp = $obj_location->cp;
    $biblio_town = $obj_location->town;
    $biblio_state = $obj_location->state;
    $biblio_country = $obj_location->country;
    $biblio_phone = $obj_location->phone;
    $biblio_email = $obj_location->email;
    $biblio_website = $obj_location->website;
    $biblio_logo = $obj_location->logo;



    $cant_dias = $UMmailretard_cant_dias;

    $objet = $UMmailretard_1objet;
    $fdp = $UMmailretard_1fdp;
    $after_list = $UMmailretard_1after_list;
    $before_list = $UMmailretard_1before_list;
    $madame_monsieur = $UMmailretard_1madame_monsieur;

//REINITIALISATION DE LA REQUETE SQL
    $sqlp = "SELECT date_format(pret_date, '%d/%m/%Y') as aff_pret_date, ";
    $sqlp .= " date_format(pret_retour, '%d/%m/%Y') as aff_pret_retour, ";
    $sqlp .= " IF(pret_retour=CURDATE()+$cant_dias,1,0) as retard, ";
    $sqlp .= " id_empr, empr_nom, empr_prenom, empr_mail, id_empr, empr_cb, expl_cote,";
    $sqlp .= " notices_m.notice_id as m_id, notices_s.notice_id as s_id, expl_cb, expl_notice,tdoc_libelle, section_libelle, location_libelle, ";
    $sqlp .= " expl_bulletin, notices_m.notice_id as idnot, trim(concat(ifnull(notices_m.tit1,''),";
    $sqlp .= " ifnull(notices_s.tit1,''),' ',ifnull(bulletin_numero,''), if (mention_date, concat(' (',mention_date,')') ,''))) as tit ";
    $sqlp .= "FROM (((exemplaires LEFT JOIN notices AS notices_m ON expl_notice = notices_m.notice_id ) ";
    $sqlp .= "        LEFT JOIN bulletins ON expl_bulletin = bulletins.bulletin_id) ";
    $sqlp .= "        LEFT JOIN notices AS notices_s ON bulletin_notice = notices_s.notice_id), ";
    $sqlp .= "        docs_type, docs_section, docs_location, pret,empr ";
    $sqlp .= "WHERE ";
    $sqlp .= " expl_typdoc = idtyp_doc and pret_idexpl = expl_id  and empr.id_empr = pret.pret_idempr  and expl_section = idsection and expl_location = idlocation ";
    $sqlp .= $critere_requete;
//$sql = $sql . " LIMIT " . $limite_mysql . ", " . $limite_page;
    $sqlp.= "and pret_retour=CURDATE()+$cant_dias order by retard";

// on lance la requ te (mysql_query) et on impose un message d'erreur si la requ te ne se passe pas bien (or die) 
    $result = mysql_query($sqlp) or die("Erreur SQL !<br />" . $sqlp . "<br /><br/>" . mysql_error());
    //or die("Erreur SQL !<br />" . $sql . "<br /><br/>" . mysql_error())

    while ($expl = mysql_fetch_object($result)) {
        $texte_mail = "";
        if ($madame_monsieur)
            $texte_mail.=$madame_monsieur . "\r\n\r\n";
        if ($before_list)
            $texte_mail.=$before_list . "\r\n\r\n";
        $headers = "";
        $responsabilites = array();
        $header_aut = "";
        //$responsabilites = get_notice_authors(($expl->m_id + $expl->s_id));
        $responsabilites = get_notice_authors(($expl->m_id));
        $as = array_search("0", $responsabilites["responsabilites"]);
        if ($as !== FALSE && $as !== NULL) {
            $auteur_0 = $responsabilites["auteurs"][$as];
            $auteur = new auteur($auteur_0["id"]);
            $header_aut .= $auteur->isbd_entry;
        } else {
            print "paso";
            $aut1_libelle = array();
            $as = array_keys($responsabilites["responsabilites"], "1");
            for ($i = 0; $i < count($as); $i++) {
                $indice = $as[$i];
                $auteur_1 = $responsabilites["auteurs"][$indice];
                $auteur = new auteur($auteur_1["id"]);
                $aut1_libelle[] = $auteur->isbd_entry;
            }

            $header_aut .= implode(", ", $aut1_libelle);
        }
        $header_aut ? $auteur = " / " . $header_aut : $auteur = "";

        // r cup ration du titre de s rie
        $tit_serie = "";
        if ($expl->tparent_id && $expl->m_id) {
            $parent = new serie($expl->tparent_id);
            $tit_serie = $parent->name;
            if ($expl->tnvol)
                $tit_serie .= ', ' . $expl->tnvol;
        }
        if ($tit_serie) {
            $expl->tit = $tit_serie . '. ' . $expl->tit;
        }

        $texte_mail.=$expl->tit . $auteur . "\r\n";
        $texte_mail.="    -" . $msg[fpdf_date_pret] . " : " . $expl->aff_pret_date . " " . $msg[fpdf_retour_prevu] . " : " . $expl->aff_pret_retour . "\r\n";
        $texte_mail.="    -" . $expl->location_libelle . ": " . $expl->section_libelle . " (" . $expl->expl_cb . ")\r\n\r\n\r\n";
        $texte_mail.="\r\n";
        if ($after_list)
            $texte_mail.=$after_list . "\r\n\r\n";
        if ($fdp)
            $texte_mail.=$fdp . "\r\n\r\n";

        //$texte_mail.=mail_bloc_adresse();//Si está aparece lo de Ponce
//remplacement nom et prenom
        $texte_mail = str_replace("!!empr_name!!", $expl->empr_nom, $texte_mail);
        $texte_mail = str_replace("!!empr_first_name!!", $expl->empr_prenom, $texte_mail);
        $headers = "MIME-Version: 1.0\r\n" . " Content-Type: text/html; charset=utf-8\r\n";
        header('Content-Type: text/html; charset=ISO-8859-1');

        $res_envoi = mailpmb($expl->empr_prenom . " " . $expl->empr_nom, $expl->empr_mail, $objet, $texte_mail, $biblio_name, $biblio_email, $headers, "", $UMmailretard_mail_bcc, 1);


        if ($res_envoi)
            echo "<center><h3>" . sprintf($msg["mail_retard_succeed"], $expl->empr_mail) . "</h3><br /><a href=\"\" onClick=\"self.close(); return false;\">" . $msg["mail_retard_close"] . "</a></center><br /><br />" . nl2br($texte_mail);
        else
            echo "<center><h3>" . sprintf($msg["mail_retard_failed"], $expl->empr_mail) . "</h3><br /><a href=\"\" onClick=\"self.close(); return false;\">" . $msg["mail_retard_close"] . "</a></center>";
    }

    //Ahora envío mail de confirmación  a sistemas y a biblioteca
    $headers = "MIME-Version: 1.0\r\n" . " Content-Type: text/html; charset=utf-8\r\n";
    header('Content-Type: text/html; charset=ISO-8859-1');

    $objet = "Aviso de prestamos que vencen en $cant_dias dias";

    $texte_mail = "Corri&oacute; el sistema y se procesaron <b>" . mysql_num_rows($result) . "</b>";
    $res_envoi = mailpmb("Biblioteca", $UMmailretard_mail_resumen, $objet, $texte_mail, $biblio_name, $biblio_email, $headers, "", $UMmailretard_mail_resumen, 1);
    mysql_free_result($result);
}

function mandarMailRetraso($tipo = false) {
    global $include_path, $class_path, $charset, $msg, $parameter_subst, $dbh, $base_path;
    
	global $biblio_name,
    $biblio_email,
    $biblio_phone,
    $PMBuseremailbcc;
//  $biblio_name = "Biblioteca";
//  $biblio_email = "reservas-biblio@um.edu.uy";
//  $biblio_phone = "27074461";
//  $PMBuseremailbcc = "mvidal@um.edu.uy";
    if (!$relance)
        $relance = 1;
    if ($deflt2docs_location)
        $requete_param = "SELECT * FROM docs_location where idlocation='$deflt2docs_location'";
    else
        $requete_param = "SELECT * FROM docs_location limit 1";
    $res_param = mysql_query($requete_param, $dbh);
    $obj_location = mysql_fetch_object($res_param);
    $biblio_name = $obj_location->name;
    $biblio_adr1 = $obj_location->adr1;
    $biblio_adr2 = $obj_location->adr2;
    $biblio_cp = $obj_location->cp;
    $biblio_town = $obj_location->town;
    $biblio_state = $obj_location->state;
    $biblio_country = $obj_location->country;
    $biblio_phone = $obj_location->phone;
    $biblio_email = $obj_location->email;
    $biblio_website = $obj_location->website;
    $biblio_logo = $obj_location->logo;
    global $mailretard_priorite_email, $mailretard_priorite_email_3, $mailretard_1objet,
    $mailretard_1after_list,
    $UMmailretard_cant_dias,
    $mailretard_1fdp, $mailretard_1before_list, $mailretard_1madame_monsieur,$UMmailretard_mail_resumen;

    $reque_mv = "select distinct pret_idempr from pret where pret_retour < curdate()";

    print "<br>mysql: " . $reque_mv;    
    $req_mv = mysql_query($reque_mv);
    if($req_mv){
        $texto_mail = "";
        while ($data_mv = mysql_fetch_array($req_mv)) {
            $texte_mail = "";
            $id_empr = $data_mv["pret_idempr"];
            include("./edit/mail-retard.inc.php");
            $texto_mail .=$texte_mail;
        }
        print "<br>".$texto_mail;
        
        $headers = "MIME-Version: 1.0\r\n" . " Content-Type: text/html; charset=utf-8\r\n";
        header('Content-Type: text/html; charset=ISO-8859-1');
        $objet = "Aviso de prestamos vencidos";
        $res_envoi = mailpmb("Biblioteca", $UMmailretard_mail_resumen, $objet, $texto_mail, $biblio_name, $biblio_email, $headers, "", $UMmailretard_mail_resumen, 1);
        mysql_free_result($req_mv);
    }
}