<?php
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendMutuelleEmail($to, $subject, $body, $name = '') {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = '192.168.250.221';    // SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mutuelle@bhbank.tn'; // SMTP username
        $mail->Password   = 'bh1234$';          // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;              // Common port
        $mail->setFrom('mutuelle@bhbank.tn', 'Mutuelle BH Bank');
        $mail->addAddress($to, $name);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

function notifyAdherentDemandeTraitee($matemp, $statut, $montant = 0) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT email, prenom, nom FROM adherent WHERE matemp = ?");
    $stmt->execute([$matemp]);
    $adherent = $stmt->fetch();
    
    if (!$adherent || empty($adherent['email'])) {
        return false;
    }
    
    $email = $adherent['email'];
    $name = $adherent['prenom'] . ' ' . $adherent['nom'];
    
    if ($statut === 'approuvée') {
        $subject = " Votre demande d'aide a été approuvée";
        $body = "
        <h2>Bonjour {$name},</h2>
        <p>Votre demande d'aide sociale a été <strong>approuvée</strong>.</p>
        <p><strong>Montant accordé :</strong> {$montant} DT</p>
        <p>Vous pouvez consulter le détail dans votre espace personnel.</p>
        <p>Cordialement,<br>L'équipe de la Mutuelle BH Bank</p>
        ";
    } else {
        $subject = " Votre demande d'aide a été refusée";
        $body = "
        <h2>Bonjour {$name},</h2>
        <p>Votre demande d'aide sociale a été <strong>refusée</strong>.</p>
        <p>Pour plus d'informations, contactez l'administrateur.</p>
        <p>Cordialement,<br>L'équipe de la Mutuelle BH Bank</p>
        ";
    }
    
    return sendMutuelleEmail($email, $subject, $body, $name);
}
?>