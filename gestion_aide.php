<?php
session_start();
if (!isset($_SESSION['profil']) || $_SESSION['profil'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require_once 'config.php';

// Ajouter un nouveau type d'aide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $codetype = trim($_POST['codetype'] ?? '');
    $libelle = trim($_POST['libelle'] ?? '');
    $mntmax = trim($_POST['mntmax'] ?? '');
    
    $errors = [];
    
    if (strlen($codetype) !== 2) {
        $errors[] = "Le code doit avoir exactement 2 caractères.";
    }
    
    if (empty($libelle)) {
        $errors[] = "Le libellé est obligatoire.";
    }
    
    if (empty($errors)) {
        $mntmax_value = (!empty($mntmax)) ? (float)$mntmax : null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO aide (codetype, libelle, mntmax) VALUES (?, ?, ?)");
            $stmt->execute([$codetype, $libelle, $mntmax_value]);
            $_SESSION['success'] = "✅ Type d'aide ajouté avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Erreur : Ce code existe déjà ou une erreur est survenue.";
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    header('Location: gestion_aide.php');
    exit;
}

// Modifier un type d'aide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $codetype = trim($_POST['codetype'] ?? '');
    $libelle = trim($_POST['libelle'] ?? '');
    $mntmax = trim($_POST['mntmax'] ?? '');
    
    $errors = [];
    
    if (strlen($codetype) !== 2) {
        $errors[] = "Le code doit avoir exactement 2 caractères.";
    }
    
    if (empty($libelle)) {
        $errors[] = "Le libellé est obligatoire.";
    }
    
    if (empty($errors)) {
        $mntmax_value = (!empty($mntmax)) ? (float)$mntmax : null;
        
        try {
            // Mise à jour basée sur le codetype (qui doit être unique)
            $stmt = $pdo->prepare("UPDATE aide SET libelle = ?, mntmax = ? WHERE codetype = ?");
            $stmt->execute([$libelle, $mntmax_value, $codetype]);
            $_SESSION['success'] = "✅ Type d'aide modifié avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Erreur lors de la modification.";
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
    header('Location: gestion_aide.php');
    exit;
}

// Supprimer un type d'aide
if (isset($_GET['supprimer'])) {
    $codetype = $_GET['supprimer'];
    
    // Vérifier qu'aucune demande n'utilise ce type
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM demande WHERE codetype = ?");
    $stmt->execute([$codetype]);
    
    if ($stmt->fetchColumn() == 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM aide WHERE codetype = ?");
            $stmt->execute([$codetype]);
            $_SESSION['success'] = "✅ Type d'aide supprimé.";
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ Erreur lors de la suppression.";
        }
    } else {
        $_SESSION['error'] = "❌ Impossible de supprimer : des demandes existent avec ce type.";
    }
    header('Location: gestion_aide.php');
    exit;
}

// Récupérer tous les types d'aide avec statistiques
$stmt = $pdo->query("
    SELECT a.*, 
           COUNT(d.id) as nb_demandes,
           COALESCE(SUM(d.mnt_demande), 0) as total_demande,
           COALESCE(SUM(d.montant_accorde), 0) as total_accorde
    FROM aide a
    LEFT JOIN demande d ON a.codetype = d.codetype
    GROUP BY a.codetype, a.libelle, a.mntmax
    ORDER BY a.libelle
");
$aides = $stmt->fetchAll();

// Récupérer les statistiques générales
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_aides,
        COUNT(DISTINCT LEFT(codetype, 1)) as total_categories,
        SUM(CASE WHEN mntmax IS NOT NULL THEN 1 ELSE 0 END) as aides_avec_limite
    FROM aide
");
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Types d'Aide - Administration</title>
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
            max-width: 1400px;
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
            top: 104px;
            z-index: 90;
            box-shadow: 0 2px 8px rgba(0, 42, 92, 0.2);
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
        }
        
        .nav-container {
            max-width: 1400px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .page-title {
            color: var(--primary);
            font-size: 2.25rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--secondary);
            border-radius: 2px;
        }
        
        .admin-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, #003d7a 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-sm);
            min-width: 150px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
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
        
        /* Form Cards */
        .form-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .form-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .card-title {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        /* Form Elements */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
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
        
        .form-help {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: 0.5rem;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border);
        }
        
        .form-btn {
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
        
        .form-btn:hover {
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
        
        /* Table Styles */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .data-table thead {
            background: linear-gradient(135deg, var(--primary) 0%, #003d7a 100%);
        }
        
        .data-table th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid var(--secondary);
            position: relative;
        }
        
        .data-table th:after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 60%;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .data-table th:last-child:after {
            display: none;
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
            transform: translateX(4px);
        }
        
        .data-table td {
            padding: 1.25rem 1.5rem;
            color: var(--text);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border);
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 2px solid transparent;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, var(--warning) 0%, #f59e0b 100%);
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, var(--error) 0%, #dc3545 100%);
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--border);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--text-light);
            margin-bottom: 0.75rem;
            font-size: 1.5rem;
        }
        
        .empty-state p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 2rem;
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .admin-stats {
                width: 100%;
                justify-content: space-between;
            }
            
            .stat-card {
                flex: 1;
                min-width: 120px;
            }
            
            .form-card {
                padding: 1.5rem;
            }
            
            .data-table {
                min-width: auto;
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        /* Edit Form Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius);
            padding: 2.5rem;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
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
        
        /* Animation for table rows */
        .data-table tbody tr {
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
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
                <div class="brand-text">Gestion des Types d'Aide</div>
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
                <a href="adherent.php" class="nav-link">
                    <i class="fas fa-users"></i> Liste des adhérents
                </a>
                <a href="liste_demandes.php" class="nav-link">
                    <i class="fas fa-list-check"></i> Toutes les demandes
                </a>
                <a href="gestion_aide.php" class="nav-link active">
                    <i class="fas fa-cogs"></i> Gestion des aides
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Gestion des Types d'Aide</h1>
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-hand-holding-heart"></i> Types d'aide
                    </div>
                    <div class="stat-value"><?= $stats['total_aides'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-layer-group"></i> Groupes
                    </div>
                    <div class="stat-value"><?= $stats['total_categories'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-money-bill-wave"></i> Avec limite
                    </div>
                    <div class="stat-value"><?= $stats['aides_avec_limite'] ?></div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
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

        <!-- Add Form Card -->
        <div class="form-card">
            <h2 class="card-title">
                <i class="fas fa-plus-circle"></i> Ajouter un nouveau type d'aide
            </h2>
            
            <form method="POST" id="addForm">
                <input type="hidden" name="action" value="ajouter">
                
                <div class="form-grid">
                    <!-- Code -->
                    <div class="form-group">
                        <label for="codetype" class="form-label required">
                            <i class="fas fa-hashtag"></i> Code
                        </label>
                        <input type="text" id="codetype" name="codetype" 
                               class="form-control" maxlength="2" required
                               placeholder="PH, SM, NA...">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> 
                            Doit contenir exactement 2 caractères
                        </div>
                    </div>
                    
                    <!-- Libellé -->
                    <div class="form-group">
                        <label for="libelle" class="form-label required">
                            <i class="fas fa-font"></i> Libellé
                        </label>
                        <input type="text" id="libelle" name="libelle" 
                               class="form-control" required
                               placeholder="Plafond pharmacie, Soins médicaux...">
                    </div>
                    
                    <!-- Montant maximum -->
                    <div class="form-group">
                        <label for="mntmax" class="form-label">
                            <i class="fas fa-money-bill-wave"></i> Montant maximum (DT)
                        </label>
                        <input type="number" step="0.01" id="mntmax" name="mntmax" 
                               class="form-control" min="0"
                               placeholder="500.00">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> 
                            Laisser vide pour aucune limite
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="form-btn btn-primary">
                        <i class="fas fa-save"></i> Ajouter le type d'aide
                    </button>
                    <button type="reset" class="form-btn btn-secondary">
                        <i class="fas fa-undo"></i> Réinitialiser
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing Aides -->
        <div class="form-card">
            <h2 class="card-title">
                <i class="fas fa-list-check"></i> Types d'aide existants
                <span style="font-size: 1rem; color: var(--text-light); margin-left: 1rem;">
                    (<?= count($aides) ?> type<?= count($aides) > 1 ? 's' : '' ?>)
                </span>
            </h2>
            
            <?php if (empty($aides)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>Aucun type d'aide</h3>
                    <p>Commencez par ajouter votre premier type d'aide ci-dessus.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Libellé</th>
                                <th>Limite</th>
                                <th>Statistiques</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aides as $aide): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary); font-size: 1.1rem;">
                                        <?= htmlspecialchars($aide['codetype']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($aide['libelle']) ?></div>
                                </td>
                                <td>
                                    <?php if (!is_null($aide['mntmax'])): ?>
                                        <div style="font-weight: 600; color: var(--primary);">
                                            <?= number_format($aide['mntmax'], 2, ',', ' ') ?> DT
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-light); font-style: italic;">Aucune limite</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <div>
                                            <i class="fas fa-file-alt" style="color: var(--info);"></i>
                                            <span style="font-weight: 600;"><?= $aide['nb_demandes'] ?></span> demande(s)
                                        </div>
                                        <?php if ($aide['nb_demandes'] > 0): ?>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            <?= number_format($aide['total_demande'], 0, ',', ' ') ?> DT demandés
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--success); font-weight: 600;">
                                            <?= number_format($aide['total_accorde'], 0, ',', ' ') ?> DT accordés
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" 
                                                class="action-btn btn-edit"
                                                onclick="openEditModal(
                                                    '<?= addslashes($aide['codetype']) ?>',
                                                    '<?= addslashes($aide['libelle']) ?>',
                                                    '<?= addslashes($aide['mntmax'] ?? '') ?>'
                                                )">
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <a href="?supprimer=<?= $aide['codetype'] ?>" 
                                           class="action-btn btn-delete"
                                           onclick="return confirmDelete('<?= addslashes($aide['codetype']) ?> - <?= addslashes($aide['libelle']) ?>', <?= $aide['nb_demandes'] ?>)">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <h2 class="card-title" style="margin-top: 0;">
                <i class="fas fa-edit"></i> Modifier le type d'aide
            </h2>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="modifier">
                
                <div class="form-grid">
                    <!-- Code -->
                    <div class="form-group">
                        <label for="editCodetype" class="form-label required">
                            <i class="fas fa-hashtag"></i> Code
                        </label>
                        <input type="text" id="editCodetype" name="codetype" 
                               class="form-control" maxlength="2" required readonly
                               style="background-color: #f0f0f0;">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> 
                            Le code ne peut pas être modifié
                        </div>
                    </div>
                    
                    <!-- Libellé -->
                    <div class="form-group">
                        <label for="editLibelle" class="form-label required">
                            <i class="fas fa-font"></i> Libellé
                        </label>
                        <input type="text" id="editLibelle" name="libelle" 
                               class="form-control" required>
                    </div>
                    
                    <!-- Montant maximum -->
                    <div class="form-group">
                        <label for="editMntmax" class="form-label">
                            <i class="fas fa-money-bill-wave"></i> Montant maximum (DT)
                        </label>
                        <input type="number" step="0.01" id="editMntmax" name="mntmax" 
                               class="form-control" min="0">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> 
                            Laisser vide pour aucune limite
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="form-btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <button type="button" class="form-btn btn-secondary" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Edit modal functionality
    function openEditModal(codetype, libelle, mntmax) {
        document.getElementById('editCodetype').value = codetype;
        document.getElementById('editLibelle').value = libelle;
        document.getElementById('editMntmax').value = mntmax;
        
        document.getElementById('editModal').style.display = 'flex';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Confirm delete with custom message
    function confirmDelete(aideName, nbDemandes) {
        let message = `Êtes-vous sûr de vouloir supprimer le type d'aide "${aideName}" ?`;
        
        if (nbDemandes > 0) {
            message += `\n\n⚠️ Attention : ${nbDemandes} demande(s) utilisent ce type d'aide.`;
            message += '\nLa suppression ne sera pas possible.';
            return confirm(message);
        }
        
        return confirm(message);
    }
    
    // Form validation
    document.getElementById('addForm').addEventListener('submit', function(e) {
        const codetype = document.getElementById('codetype');
        const libelle = document.getElementById('libelle');
        
        if (codetype.value.length !== 2) {
            e.preventDefault();
            alert('Le code doit contenir exactement 2 caractères.');
            codetype.focus();
            return false;
        }
        
        if (!libelle.value.trim()) {
            e.preventDefault();
            alert('Le libellé est obligatoire.');
            libelle.focus();
            return false;
        }
        
        return true;
    });
    
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const libelle = document.getElementById('editLibelle');
        
        if (!libelle.value.trim()) {
            e.preventDefault();
            alert('Le libellé est obligatoire.');
            libelle.focus();
            return false;
        }
        
        return true;
    });
    
    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
    
    // Animation for table rows
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.1}s`;
        });
    });
    </script>
</body>
</html>