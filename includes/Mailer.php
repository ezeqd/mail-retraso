<?php
/**
 * Esta clase se encarga de configurar la librería PHPMailer para el uso de nuestra aplicación.
 * Esto tiene en cuenta nuestro caso de uso, en el cuál se envía un mail de aviso al usuario
 * de la biblioteca
 */

require_once "PHPMailer/PHPMailer.php";
require_once "PHPMailer/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer {
    private $mail; 
    
    public function __construct($receiver, $subject, $body){
        $this->mail = new PHPMailer();
        // Server settings
        $this->mail->isSMTP();
        $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
                       // SMTP::DEBUG_OFF = off (for production use)
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->Username = '4dm1ntpeweb2@gmail.com';
        $this->mail->Password = 'securepass';
        $this->mail->CharSet    = "UTF-8";
        // Recipients
        $this->mail->setFrom('4dm1ntpeweb2@gmail.com', 'Administrador PMB');
        $this->mail->addAddress($receiver);
        // Content
        $this->setBody($body);
        $this-> setSubject($subject);
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