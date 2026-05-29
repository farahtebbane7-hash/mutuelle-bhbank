<?php
session_start();

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if (!isset($_SESSION['matemp'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$matemp = $_SESSION['matemp'];
$profil = $_SESSION['profil'] ?? 'user';

// Récupérer les infos complètes de l'adhérent
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        af.lib_affect AS departement_libelle,
        af.type_affect
    FROM adherent a
    LEFT JOIN affectation af ON a.code_affect = af.code_affect
    WHERE a.matemp = ?
");
$stmt->execute([$matemp]);
$adherent = $stmt->fetch();

if (!$adherent) {
    header('Location: index.php');
    exit;
}

$stmt_pers = $pdo->prepare("
    SELECT * FROM perscharge 
    WHERE matemp = ? 
    ORDER BY 
        CASE typepers 
            WHEN 'conjoint' THEN 1
            WHEN 'enfant' THEN 2
            WHEN 'parent' THEN 3
            ELSE 4
        END, 
        prenom
");
$stmt_pers->execute([$matemp]);
$personnes_a_charge = $stmt_pers->fetchAll();

// Statistiques de l'adhérent
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_demandes,
        SUM(CASE WHEN statut = 'approuvée' THEN 1 ELSE 0 END) as demandes_approuvees,
        SUM(CASE WHEN statut = 'refusée' THEN 1 ELSE 0 END) as demandes_refusees,
        SUM(CASE WHEN statut = 'en attente' THEN 1 ELSE 0 END) as demandes_en_cours
    FROM demande 
    WHERE matemp = ?
");
$stmt_stats->execute([$matemp]);
$stats = $stmt_stats->fetch();

// Gérer les messages d'erreur/succès
$errors = $_SESSION['errors'] ?? [];
$success = $_SESSION['success'] ?? [];
unset($_SESSION['errors'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Mutuelle BH Bank</title>
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
        
        /* Navigation */
        .nav {
            background: var(--primary);
            padding: 1rem 0;
            position: sticky;
            top: 94px;
            z-index: 90;
            box-shadow: 0 2px 8px rgba(0, 42, 92, 0.2);
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-xs);
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--secondary);
            transition: var(--transition);
            transform: translateX(-50%);
        }
        
        .nav-link:hover:before,
        .nav-link.active:before {
            width: 80%;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            background: rgba(216, 27, 45, 0.15);
            color: white;
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
        
        /* Profile Hero */
        .profile-hero {
            background: linear-gradient(135deg, var(--primary) 0%, #003d7a 100%);
            border-radius: var(--radius);
            padding: 2.5rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .profile-hero:before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.1;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--secondary) 0%, #e53935 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .profile-role {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-sm);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }
        
        .stat-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: var(--radius-sm);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .card-title {
            color: var(--primary);
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
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
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }
        
        /* Buttons */
        .btn {
            padding: 0.875rem 1.75rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            border: 2px solid transparent;
            text-decoration: none;
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
        
        .btn-danger {
            background: linear-gradient(135deg, var(--error) 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
        }
        
        .btn:hover {
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
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        /* Table */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: linear-gradient(135deg, var(--primary) 0%, #003d7a 100%);
        }
        
        .data-table th {
            padding: 1rem 1.25rem;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid var(--border);
        }
        
        .data-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .data-table tbody tr:hover {
            background: rgba(0, 42, 92, 0.03);
        }
        
        .data-table td {
            padding: 1rem 1.25rem;
            color: var(--text);
            font-size: 0.95rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-conjoint {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .badge-enfant {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .badge-parent {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .badge-autre {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
            border: 1px solid rgba(107, 114, 128, 0.2);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
        }
        
        .empty-icon {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Modal Form */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: slideIn 0.3s ease-out;
        }
        
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .modal-close:hover {
            color: var(--error);
        }
        
        .modal-title {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
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
            
            .nav-container {
                padding: 0 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .nav-link {
                justify-content: center;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }
            
            .profile-role {
                justify-content: center;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .page-title {
                font-size: 2rem;
            }
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
        
        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <img src="bhbank.png" alt="BH Bank" class="logo">
                <div class="brand-text">Mon Profil</div>
            </div>
            <a href="dashboard.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Accueil
            </a>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <div class="nav-container">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="nouvdemande.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i> Nouvelle demande
                </a>
                <a href="history.php" class="nav-link">
                    <i class="fas fa-history"></i> Historique
                </a>
                <a href="profil.php" class="nav-link active">
                    <i class="fas fa-user"></i> Mon profil
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Mon Profil Personnel</h1>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                    <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 0.5rem;">Des erreurs sont survenues :</strong>
                        <?php foreach ($errors as $error): ?>
                            <div>• <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Profile Hero -->
        <div class="profile-hero">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($adherent['prenom'], 0, 1) . substr($adherent['nom'], 0, 1)) ?>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name"><?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="profile-role">
                        <span>
                            <i class="fas fa-id-card"></i> <?= htmlspecialchars($adherent['matemp'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span>
                            <i class="fas fa-building"></i> <?= htmlspecialchars($adherent['departement_libelle'] ?? 'Non spécifié', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span>
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($adherent['email'] ?? 'Non renseigné', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="profile-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['total_demandes'] ?? 0 ?></span>
                    <span class="stat-label">Demandes totales</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['demandes_approuvees'] ?? 0 ?></span>
                    <span class="stat-label">Demandes approuvées</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['demandes_en_cours'] ?? 0 ?></span>
                    <span class="stat-label">En cours</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= count($personnes_a_charge) ?></span>
                    <span class="stat-label">Personnes à charge</span>
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="cards-grid">
            <!-- Informations personnelles -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h3 class="card-title">Informations personnelles</h3>
                </div>
                <form method="POST" action="update_profil.php" id="profileForm">
                    <div class="form-group">
                        <label for="nom" class="form-label required">
                            <i class="fas fa-user"></i> Nom
                        </label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?= htmlspecialchars($adherent['nom'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom" class="form-label required">
                            <i class="fas fa-user"></i> Prénom
                        </label>
                        <input type="text" id="prenom" name="prenom" class="form-control" 
                               value="<?= htmlspecialchars($adherent['prenom'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label required">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($adherent['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tel" class="form-label">
                            <i class="fas fa-phone"></i> Téléphone
                        </label>
                        <input type="text" id="tel" name="tel" class="form-control" 
                               value="<?= htmlspecialchars($adherent['tel'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse" class="form-label">
                            <i class="fas fa-home"></i> Adresse
                        </label>
                        <input type="text" id="adresse" name="adresse" class="form-control" 
                               value="<?= htmlspecialchars($adherent['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Mettre à jour mes informations
                    </button>
                </form>
            </div>

            <!-- Personnes à charge -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="card-title">Personnes à charge</h3>
                    
                </div>
                
                <?php if (empty($personnes_a_charge)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-friends empty-icon"></i>
                        <p>Aucune personne à charge enregistrée</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Nom & Prénom</th>
                                    <th>Date naissance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personnes_a_charge as $p): ?>
                                <?php 
                                $badge_class = match($p['typepers']) {
                                    'conjoint' => 'badge-conjoint',
                                    'enfant' => 'badge-enfant',
                                    'parent' => 'badge-parent',
                                    default => 'badge-autre'
                                };
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= htmlspecialchars($p['typepers'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td>
                                        <?= $p['datenais'] ? date('d/m/Y', strtotime($p['datenais'])) : '—' ?>
                                    </td>
                                    <td>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter/modifier une personne -->
    <div class="modal" id="personModal">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="hidePersonModal()">×</button>
            <h2 class="modal-title" id="modalTitle">Ajouter une personne à charge</h2>
            
            <form method="POST" action="save_person.php" id="personForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="personId">
                
                <div class="form-group">
                    <label for="modal_typepers" class="form-label required">
                        <i class="fas fa-user-tag"></i> Type de personne
                    </label>
                    <select id="modal_typepers" name="typepers" class="form-control" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="conjoint">Conjoint</option>
                        <option value="enfant">Enfant</option>
                        <option value="parent">Parent</option>
                        <option value="autre">Autre membre de famille</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="modal_nom" class="form-label required">
                        <i class="fas fa-user"></i> Nom
                    </label>
                    <input type="text" id="modal_nom" name="nom" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="modal_prenom" class="form-label required">
                        <i class="fas fa-user"></i> Prénom
                    </label>
                    <input type="text" id="modal_prenom" name="prenom" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="modal_date_naissance" class="form-label">
                        <i class="fas fa-birthday-cake"></i> Date de naissance
                    </label>
                    <input type="date" id="modal_date_naissance" name="date_naissance" class="form-control" 
                           max="<?= date('Y-m-d') ?>">
                    <div style="font-size: 0.875rem; color: var(--text-light); margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> Optionnel, mais requis pour certaines demandes
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="hidePersonModal()" style="flex: 1;">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;" id="submitPersonBtn">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Gestion de la modal pour les personnes
    function showPersonModal(action, personData = null) {
        const modal = document.getElementById('personModal');
        const title = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const personId = document.getElementById('personId');
        
        if (action === 'add') {
            title.textContent = 'Ajouter une personne à charge';
            formAction.value = 'add';
            personId.value = '';
            document.getElementById('modal_typepers').value = '';
            document.getElementById('modal_nom').value = '';
            document.getElementById('modal_prenom').value = '';
            document.getElementById('modal_date_naissance').value = '';
        } else if (action === 'edit' && personData) {
            title.textContent = 'Modifier la personne à charge';
            formAction.value = 'edit';
            personId.value = personData.id;
            document.getElementById('modal_typepers').value = personData.typepers;
            document.getElementById('modal_nom').value = personData.nom;
            document.getElementById('modal_prenom').value = personData.prenom;
            document.getElementById('modal_date_naissance').value = personData.date_naissance || '';
        }
        
        modal.classList.add('active');
    }
    
    function hidePersonModal() {
        document.getElementById('personModal').classList.remove('active');
    }
    
    // Confirmation de suppression
    function confirmDelete(id, fullName) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer "${fullName}" ?\n\nCette action est irréversible et peut affecter vos demandes en cours.`)) {
            window.location.href = `delete_person.php?id=${id}`;
        }
    }
    
    // Validation du formulaire de profil
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Veuillez saisir une adresse email valide.');
            document.getElementById('email').focus();
            return false;
        }
        
        // Désactiver le bouton pendant l'envoi
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="loading"></div> Mise à jour en cours...';
        
        return true;
    });
    
    // Validation du formulaire de personne
    document.getElementById('personForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitPersonBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="loading"></div> Enregistrement...';
        
        return true;
    });
    
    // Fermer la modal avec ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hidePersonModal();
        }
    });
    
    // Fermer la modal en cliquant à l'extérieur
    document.getElementById('personModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hidePersonModal();
        }
    });
    
    // Initialiser les dates max
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.max = today;
        });
        
        // Animation des cartes
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.2}s`;
            card.style.animation = 'slideIn 0.5s ease-out forwards';
            card.style.opacity = '0';
        });
    });
    </script>
</body>
</html>