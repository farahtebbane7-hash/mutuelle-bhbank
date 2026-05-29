<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config.php';

if (!isset($_SESSION['matemp'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nouvdemande.php');
    exit;
}

$matemp = $_SESSION['matemp'];
$errors = [];
$codetype = trim($_POST['type'] ?? '');
$date_demande = trim($_POST['date'] ?? '');
if (!$codetype || !$date_demande) {
    $errors[] = "Veuillez remplir tous les champs obligatoires.";
}
$montant = 0.00;
switch ($codetype) {
    case 'PH': 
    case 'SM': 
        $montant = (float)($_POST['montant'] ?? 0);
        if ($montant <= 0) {
            $errors[] = "Le montant est obligatoire.";
        }
        break;
    case 'HO': 
        $montant = (float)($_POST['montant'] ?? 0);
        if ($montant <= 0) {
            $errors[] = "Le montant est obligatoire.";
        }
        break;
    case 'DP': case 'DC': case 'DE': case 'DA': 
    case 'NA':  
    case 'MA': 
    case 'LO': 
    case 'CI': 
        
        break;
        
    default:
        $errors[] = "Type de demande invalide.";
}

// justificatif (obligatoire)
$justificatif_filename = null;
if (isset($_FILES['justificatif']) && $_FILES['justificatif']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['justificatif'];
    $allowed_types = ['pdf', 'jpeg', 'jpg', 'png'];
    $max_size = 5 * 1024 * 1024; // 5 Mo

    if ($file['size'] > $max_size) {
        $errors[] = "Le fichier est trop volumineux (max 5 Mo).";
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        $errors[] = "Format non autorisé. Utilisez PDF, JPG ou PNG.";
    }

    if (empty($errors)) {
        $justificatif_filename = uniqid('justif_') . '.' . $file_ext;
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $justificatif_filename)) {
            $errors[] = "Erreur lors de l'upload du fichier.";
        }
    }
} else {
    $errors[] = "Veuillez joindre un justificatif.";
}
$reference_dmde = $matemp . '_' . date('YmdHis');
if (empty($errors)) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO demande (
                matemp, codetype, date, statut,
                reference_dmde, mnt_demande, justificatif
            ) VALUES (
                ?, ?, NOW(), 'en attente',
                ?, ?, ?
            )
        ");

        $stmt->execute([
            $matemp, $codetype,
            $reference_dmde, $montant, $justificatif_filename
        ]);

        $_SESSION['success'] = "Votre demande a été soumise avec succès.";
        header('Location: history.php');
        exit;

    } catch (Exception $e) {
        error_log("Erreur BDD : " . $e->getMessage());
        echo "<h2 style='color:red;'>Erreur de base de données :</h2>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit;
    }
}

$_SESSION['errors'] = $errors;
header('Location: nouvdemande.php');
exit;
?>