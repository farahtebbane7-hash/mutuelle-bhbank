<?php
session_start();
if (!isset($_SESSION['profil']) || $_SESSION['profil'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once 'config.php';

// Vérifier que l'ID de la demande est fourni
if (!isset($_GET['id'])) {
    header('Location: liste_demandes_admin.php');
    exit;
}

$demande_id = (int)$_GET['id'];

// Récupérer la demande avec les infos de l'adhérent
$stmt = $pdo->prepare("
    SELECT d.*, a.nom, a.prenom, a.matemp, a.email, a.tel,
           af.lib_affect AS departement, ay.libelle as type_demande
    FROM demande d
    JOIN adherent a ON d.matemp = a.matemp
    LEFT JOIN affectation af ON a.code_affect = af.code_affect
    LEFT JOIN aide ay ON d.codetype = ay.codetype
    WHERE d.id = ?
");
$stmt->execute([$demande_id]);
$demande = $stmt->fetch();

if (!$demande) {
    header('Location: liste_demandes_admin.php');
    exit;
}

// Récupérer les informations spécifiques selon le type de demande
$details_specifiques = [];
switch($demande['codetype']) {
    case 'PH': case 'SM': case 'HO': case 'LO':
        $details_specifiques['Montant demandé'] = number_format($demande['mnt_demande'], 2, ',', ' ') . ' DT';
        break;
    case 'DP': case 'DC': case 'DE': case 'DA':
        $details_specifiques['Défunt'] = $demande['nom_defunt'] ?? 'Non spécifié';
        $details_specifiques['Lien de parenté'] = $demande['lien_parente'] ?? 'Non spécifié';
        break;
    case 'NA':
        $details_specifiques['Nom nouveau-né'] = $demande['nom_nouveau_ne'] ?? 'Non spécifié';
        if ($demande['date_naissance']) {
            $details_specifiques['Date de naissance'] = date('d/m/Y', strtotime($demande['date_naissance']));
        }
        break;
    case 'MA':
        $details_specifiques['Date mariage'] = $demande['date_mariage'] ? date('d/m/Y', strtotime($demande['date_mariage'])) : 'Non spécifié';
        break;
}

// Traitement du formulaire de traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statut_final = trim($_POST['statut_final'] ?? '');
    $montant_accorde = (float)($_POST['montant_accorde'] ?? 0);
    $observation = trim($_POST['observation'] ?? '');
    $date_pec = trim($_POST['date_pec'] ?? date('Y-m-d'));
    
    // Validation
    $errors = [];
    
    if (empty($statut_final)) {
        $errors[] = "Veuillez sélectionner une décision.";
    }
    
    if ($statut_final === 'favorable' && $montant_accorde <= 0) {
        $errors[] = "Le montant accordé doit être supérieur à 0 pour une décision favorable.";
    }
    
    if (empty($date_pec)) {
        $errors[] = "La date de prise en charge est obligatoire.";
    } elseif (strtotime($date_pec) > time()) {
        $errors[] = "La date de prise en charge ne peut pas être dans le futur.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Déterminer le nouveau statut
            $new_statut = match($statut_final) {
                'favorable' => 'approuvée',
                'rejet' => 'refusée',
                default => 'en attente'
            };
            
            // Mettre à jour la demande
            $stmt = $pdo->prepare("
                UPDATE demande 
                SET statut = ?, 
                    montant_accorde = ?, 
                    observation = ?, 
                    date_pec = ?,
                    date_traitement = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$new_statut, $montant_accorde, $observation, $date_pec, $demande_id]);
            
            // Si c'est une naissance approuvée, ajouter l'enfant aux personnes à charge
            if ($statut_final === 'favorable' && $demande['codetype'] === 'NA' && !empty($demande['nom_nouveau_ne']) && !empty($demande['date_naissance'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO personnes_a_charge (matricule, prenom, nom, typepers, date_naissance, etat)
                    VALUES (?, ?, ?, 'enfant', ?, 'ACTIF')
                ");
                
                // Extraire le prénom (premier mot)
                $prenom = explode(' ', $demande['nom_nouveau_ne'])[0];
                $nom = substr($demande['nom_nouveau_ne'], strlen($prenom) + 1);
                
                $stmt->execute([
                    $demande['matemp'],
                    $prenom,
                    $nom ?: $prenom,
                    $demande['date_naissance']
                ]);
            }
            
            $pdo->commit();
            
            // Journaliser l'action
            error_log("Demande traitée: ID=$demande_id, Statut=$new_statut, Montant=$montant_accorde");
            
            $_SESSION['success'] = "La demande #{$demande['reference_dmde']} a été traitée avec succès.";
            header('Location: liste_demandes_admin.php?statut=' . 
                ($new_statut === 'approuvée' ? 'approuvee' : ($new_statut === 'refusée' ? 'refusee' : 'en_instance')));
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur traitement demande: " . $e->getMessage());
            $errors[] = "Erreur lors du traitement de la demande.";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitement de la demande #<?= $demande['reference_dmde'] ?? $demande_id ?> - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        :root {
            --primary: #002A5C;
            --secondary: #D81B2D;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 16px;
            --radius-sm: 12px;
            --radius-xs: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: var(--radius);
            margin-bottom: 2rem;
            position: sticky;
            top: 20px;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            height: 60px;
            transition: var(--transition);
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .brand-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.5px;
        }
        
        .nav-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-xs);
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 2px solid transparent;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 42, 92, 0.2);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-title {
            color: var(--primary);
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 2px;
        }
        
        .page-subtitle {
            color: var(--text-light);
            text-align: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius-xs);
            margin-bottom: 2rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background-color: #fef2f2;
            border-left-color: var(--error);
            color: #991b1b;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            border-left-color: var(--success);
            color: #166534;
        }
        
        /* Demand Info Card */
        .demand-info-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .demand-info-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .demand-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .demand-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .demand-ref {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .demand-type {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
        }
        
        .demand-status {
            padding: 0.5rem 1.25rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-section {
            background: var(--light-bg);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            border-left: 4px solid var(--primary);
        }
        
        .section-title {
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        
        .info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .info-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .info-value {
            color: var(--text);
            font-weight: 600;
            text-align: right;
            max-width: 200px;
            word-break: break-word;
        }
        
        /* Specific Details */
        .specific-details {
            background: rgba(0, 42, 92, 0.05);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            background: white;
            padding: 1rem;
            border-radius: var(--radius-xs);
            border: 1px solid var(--border);
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Decision Form */
        .decision-form {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        
        .form-title {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .form-subtitle {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        /* Decision Options */
        .decision-options {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .decision-option {
            flex: 1;
            min-width: 200px;
        }
        
        .option-input {
            display: none;
        }
        
        .option-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            background: white;
        }
        
        .option-label:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }
        
        .option-input:checked + .option-label {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(0, 42, 92, 0.1) 0%, rgba(216, 27, 45, 0.05) 100%);
            box-shadow: var(--shadow-lg);
        }
        
        .option-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            height: 80px;
            width: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        
        .option-icon.pending {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .option-icon.approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .option-icon.rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }
        
        .option-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text);
        }
        
        .option-description {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        /* Form Fields */
        .form-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .required::after {
            content: " *";
            color: var(--error);
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-xs);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light-bg);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 42, 92, 0.1);
            background: white;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        
        /* Justificatif Section */
        .justificatif-section {
            background: rgba(0, 42, 92, 0.05);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .file-preview {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: var(--radius-xs);
            border: 1px solid var(--border);
            margin-top: 1rem;
        }
        
        .file-icon {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            color: var(--text);
        }
        
        .file-size {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 2px solid var(--border);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .action-btn {
            padding: 1rem 2rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            border: 2px solid transparent;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 42, 92, 0.15);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #001a3a 0%, #002A5C 100%);
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.5rem;
            }
            
            .decision-options {
                flex-direction: column;
            }
            
            .decision-option {
                min-width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-btn {
                justify-content: center;
                width: 100%;
            }
            
            .demand-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .demand-info-card, .decision-form {
                padding: 1.5rem;
            }
        }
        
        /* Montant Field Animation */
        .montant-field {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .montant-field.show {
            max-height: 200px;
            opacity: 1;
            margin-top: 1rem;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #001a3a;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="bhbank.png" alt="BH Bank" class="logo">
                <div class="brand-text">Traitement de demande</div>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="liste_demandes.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Retour aux demandes
                </a>
                <a href="dashboard.php" class="nav-btn">
                    <i class="fas fa-home"></i> Accueil
                </a>
            </div>
        </div>

        <div class="page-header">
            <h1 class="page-title">Traitement de demande</h1> <br><br>
            <p class="page-subtitle">Décision du comité - Référence : <?= htmlspecialchars($demande['reference_dmde'] ?? 'N/A') ?></p>
        </div>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                    <div><?= $_SESSION['error'] ?></div>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                    <div><?= $_SESSION['success'] ?></div>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Demand Information Card -->
        <div class="demand-info-card">
            <div class="demand-header">
                <div class="demand-meta">
                    <div class="demand-ref">Demande #<?= htmlspecialchars($demande['reference_dmde'] ?? $demande_id) ?></div>
                    <div class="demand-type"><?= htmlspecialchars($demande['type_demande']) ?></div>
                    <div class="demand-date">
                        <i class="far fa-calendar-alt"></i> 
                        <?= date('d/m/Y H:i', strtotime($demande['date'])) ?>
                    </div>
                </div>
                <div class="demand-status status-pending">
                    <?= ucfirst($demande['statut']) ?>
                </div>
            </div>

            <div class="info-grid">
                <!-- Informations adhérent -->
                <div class="info-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i> Informations adhérent
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Nom & Prénom</span>
                        <span class="info-value"><?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Matricule</span>
                        <span class="info-value"><?= htmlspecialchars($demande['matemp']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Département</span>
                        <span class="info-value"><?= htmlspecialchars($demande['departement'] ?? 'Non spécifié') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($demande['email'] ?? '—') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Téléphone</span>
                        <span class="info-value"><?= htmlspecialchars($demande['tel'] ?? '—') ?></span>
                    </div>
                </div>

                <!-- Informations demande -->
                <div class="info-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-alt"></i> Informations demande
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Type d'aide</span>
                        <span class="info-value"><?= htmlspecialchars($demande['type_demande']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date demande</span>
                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($demande['date'])) ?></span>
                    </div>
                    <?php if ($demande['mnt_demande'] > 0): ?>
                    <div class="info-row">
                        <span class="info-label">Montant demandé</span>
                        <span class="info-value"><?= number_format($demande['mnt_demande'], 2, ',', ' ') ?> DT</span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Statut actuel</span>
                        <span class="info-value">
                            <span class="demand-status status-pending" style="display: inline-block; padding: 0.25rem 0.75rem;">
                                <?= ucfirst($demande['statut']) ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Détails spécifiques -->
            <?php if (!empty($details_specifiques)): ?>
            <div class="specific-details">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i> Détails spécifiques
                </h3>
                <div class="details-grid">
                    <?php foreach ($details_specifiques as $label => $value): ?>
                    <div class="detail-item">
                        <div class="detail-label"><?= htmlspecialchars($label) ?></div>
                        <div class="detail-value"><?= htmlspecialchars($value) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Justificatif -->
            <?php if (!empty($demande['justificatif'])): ?>
            <div class="justificatif-section">
                <h3 class="section-title">
                    <i class="fas fa-paperclip"></i> Justificatif
                </h3>
                <div class="file-preview">
                    <i class="fas fa-file-pdf file-icon"></i>
                    <div class="file-info">
                        <div class="file-name">Document justificatif</div>
                        <div class="file-size">Téléchargé le <?= date('d/m/Y H:i', strtotime($demande['date'])) ?></div>
                    </div>
                    <a href="uploads/<?= htmlspecialchars($demande['justificatif']) ?>" 
                       target="_blank" 
                       class="action-btn btn-primary" 
                       style="padding: 0.75rem 1.5rem;">
                        <i class="fas fa-external-link-alt"></i> Ouvrir
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Decision Form -->
        <div class="decision-form">
            <h2 class="form-title">
                <i class="fas fa-gavel"></i> Décision du comité
            </h2>
            <p class="form-subtitle">Sélectionnez la décision appropriée et remplissez les informations de traitement</p>

            <form method="POST" id="decisionForm">
                <!-- Options de décision -->
                <div class="decision-options">
                    <!-- Instance -->
                    <div class="decision-option">
                        <input type="radio" name="statut_final" value="instance" id="decision_instance" class="option-input" required>
                        <label for="decision_instance" class="option-label">
                            <div class="option-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="option-title">Instance</div>
                            <div class="option-description">
                                La demande nécessite un examen complémentaire
                            </div>
                        </label>
                    </div>

                    <!-- Favorable -->
                    <div class="decision-option">
                        <input type="radio" name="statut_final" value="favorable" id="decision_favorable" class="option-input" required>
                        <label for="decision_favorable" class="option-label">
                            <div class="option-icon approved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="option-title">Favorable</div>
                            <div class="option-description">
                                La demande est approuvée
                            </div>
                        </label>
                    </div>

                    <!-- Rejet -->
                    <div class="decision-option">
                        <input type="radio" name="statut_final" value="rejet" id="decision_rejet" class="option-input" required>
                        <label for="decision_rejet" class="option-label">
                            <div class="option-icon rejected">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="option-title">Rejet</div>
                            <div class="option-description">
                                La demande est refusée
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="form-fields">
                    <!-- Montant accordé (conditionnel) -->
                    <div class="form-group">
                        <div class="montant-field" id="montantField">
                            <label for="montant_accorde" class="form-label required">
                                <i class="fas fa-money-bill-wave"></i> Montant accordé (DT)
                            </label>
                            <input type="number" step="0.01" id="montant_accorde" name="montant_accorde" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($demande['montant_accorde'] ?? $demande['mnt_demande']) ?>"
                                   min="0" max="10000">
                            
                        </div>
                    </div>

                    <!-- Date PEC -->
                    <div class="form-group">
                        <label for="date_pec" class="form-label required">
                            <i class="fas fa-calendar-check"></i> Date de prise en charge
                        </label>
                        <input type="date" id="date_pec" name="date_pec" 
                               class="form-control" 
                               value="<?= date('Y-m-d') ?>" 
                               max="<?= date('Y-m-d') ?>" 
                               required>
                    </div>
                </div>

                <!-- Observation -->
                <div class="form-group">
                    <label for="observation" class="form-label">
                        <i class="fas fa-comment-dots"></i> Observations
                    </label>
                    <textarea id="observation" name="observation" 
                              class="form-control" 
                              placeholder="Ajoutez vos commentaires, justifications ou recommandations..."
                              rows="4"></textarea>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="liste_demandes.php" class="action-btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="action-btn btn-primary" id="submitBtn">
                        <i class="fas fa-check"></i> Valider la décision
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Gestion de l'affichage du champ montant
    const decisionInputs = document.querySelectorAll('input[name="statut_final"]');
    const montantField = document.getElementById('montantField');
    const montantInput = document.getElementById('montant_accorde');
    
    decisionInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'favorable') {
                montantField.classList.add('show');
                montantInput.required = true;
            } else {
                montantField.classList.remove('show');
                montantInput.required = false;
            }
        });
    });
    
    // Validation du formulaire
    document.getElementById('decisionForm').addEventListener('submit', function(e) {
        const selectedDecision = document.querySelector('input[name="statut_final"]:checked');
        const datePec = document.getElementById('date_pec');
        const submitBtn = document.getElementById('submitBtn');
        
        // Validation de base
        if (!selectedDecision) {
            e.preventDefault();
            showError('Veuillez sélectionner une décision.');
            return false;
        }
        
        if (selectedDecision.value === 'favorable') {
            const montant = parseFloat(montantInput.value);
            if (!montant || montant <= 0) {
                e.preventDefault();
                showError('Veuillez saisir un montant valide pour une décision favorable.');
                montantInput.focus();
                return false;
            }
            
        }
        
        if (new Date(datePec.value) > new Date()) {
            e.preventDefault();
            showError('La date de prise en charge ne peut pas être dans le futur.');
            datePec.focus();
            return false;
        }
        
        // Confirmation avant soumission
        if (!confirm('Êtes-vous sûr de vouloir valider cette décision ? Cette action est irréversible.')) {
            e.preventDefault();
            return false;
        }
        
        // Désactiver le bouton pendant l'envoi
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
        
        return true;
    });
    
    function showError(message) {
        // Créer un élément d'erreur temporaire
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem;">
                <i class="fas fa-exclamation-circle"></i>
                <div>${message}</div>
            </div>
        `;
        
        // Insérer après le header
        const container = document.querySelector('.container');
        const firstChild = container.firstChild;
        container.insertBefore(errorDiv, firstChild.nextSibling);
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
    
    // Initialiser le formulaire
    document.addEventListener('DOMContentLoaded', function() {
        // Définir la date max pour les inputs date
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.max = today;
        });
        
        // Sélectionner la première option par défaut
        decisionInputs[0].checked = true;
        montantField.classList.remove('show');
        
        // Animation des cartes
        const cards = document.querySelectorAll('.demand-info-card, .decision-form');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.2}s`;
            card.style.animation = 'slideIn 0.5s ease-out forwards';
            card.style.opacity = '0';
        });
    });
    </script>
</body>
</html>