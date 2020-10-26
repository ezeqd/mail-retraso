<?php
/**
 * Esta clase se encarga de configurar la librería PHPMailer para el uso de nuestra aplicación.
 * Esto tiene en cuenta nuestro caso de uso, en el cuál se envía un mail de aviso al usuario
 * de la biblioteca
 */

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');

require_once "PHPMailer/class.phpmailer.php";
require_once "PHPMailer/class.smtp.php";

class Mailer {
    private $mail; 
    
    public function __construct($receiver, $subject, $body){
        $this->mail = new PHPMailer();
        // Server settings
        $this->mail->isSMTP();
        $this->mail->SMTPDebug = 2;
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $this->mail->Debugoutput = "html";
        //Ask for HTML-friendly debug output
        $this->mail->Host = "smtp.gmail.com";
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = "tls";
        $this->mail->Port = 587;
        $this->mail->Username = 'user@gmail.com';
        $this->mail->Password = 'password';
        $this->mail->CharSet    = "UTF-8";
        // Recipients
        $this->mail->setFrom('ditec@latu.org.uy', 'Centro de Información Técnica');
        $this->mail->addAddress($receiver);
        // Content
        $this->setBody($body);
        $this->setSubject($subject);
    }

    public function sendMail(){
        $error = null;
        if (!$this->mail->send()) {
            $error = $this->mail->ErrorInfo;
        }
        return $error;
    }

    public function setBody($body){
        $this->mail->Body = $body; 
    }

    public function setSubject($subject){
        $this->mail->Subject = $subject; 
    }
}
?>
