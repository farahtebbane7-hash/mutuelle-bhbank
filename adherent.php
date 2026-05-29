<?php
session_start();
if (!isset($_SESSION['profil']) || $_SESSION['profil'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Accès réservé aux administrateurs.</h2>");
}
require_once 'config.php';

// Recherche (si paramètre présent)
$search = trim($_GET['q'] ?? '');
$adherents = [];

// Configuration de la pagination
$results_per_page = 15; // Nombre d'adhérents par page

// Récupérer le numéro de page (par défaut page 1)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // S'assurer que la page est au moins 1
$offset = ($page - 1) * $results_per_page;

// Compter le nombre total d'adhérents (pour la pagination)
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM adherent a
        LEFT JOIN affectation af ON a.code_affect = af.code_affect
        WHERE 
            a.matemp LIKE ? OR
            a.nom LIKE ? OR
            a.prenom LIKE ? OR
            a.email LIKE ? OR
            af.lib_affect LIKE ?
    ");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like, $like, $like]);
    $total_result = $stmt->fetch()['total'];
    
    // Requête avec pagination pour la recherche
    $stmt = $pdo->prepare("
        SELECT 
            a.matemp,
            a.nom,
            a.prenom,
            a.email,
            a.code_affect,
            af.lib_affect AS departement_libelle
        FROM adherent a
        LEFT JOIN affectation af ON a.code_affect = af.code_affect
        WHERE 
            a.matemp LIKE ? OR
            a.nom LIKE ? OR
            a.prenom LIKE ? OR
            a.email LIKE ? OR
            af.lib_affect LIKE ?
        ORDER BY a.nom, a.prenom
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $like);
    $stmt->bindValue(2, $like);
    $stmt->bindValue(3, $like);
    $stmt->bindValue(4, $like);
    $stmt->bindValue(5, $like);
    $stmt->bindValue(6, $results_per_page, PDO::PARAM_INT);
    $stmt->bindValue(7, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    // Compter tous les adhérents
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM adherent");
    $total_result = $stmt->fetch()['total'];
    
    // Requête avec pagination (sans recherche)
    $stmt = $pdo->prepare("
        SELECT 
            a.matemp,
            a.nom,
            a.prenom,
            a.email,
            a.code_affect,
            af.lib_affect AS departement_libelle
        FROM adherent a
        LEFT JOIN affectation af ON a.code_affect = af.code_affect
        ORDER BY a.nom, a.prenom
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $results_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$adherents = $stmt->fetchAll();

// Calculer le nombre total de pages
$total_pages = ceil($total_result / $results_per_page);

// Ajuster la page si elle dépasse le nombre total
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    // Rediriger vers la dernière page valide
    $query_params = $_GET;
    $query_params['page'] = $page;
    header('Location: adherent.php?' . http_build_query($query_params));
    exit;
}

// Calculer les résultats affichés
$start_result = ($page - 1) * $results_per_page + 1;
$end_result = min($page * $results_per_page, $total_result);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Adhérents - Admin</title>
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
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
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
        
        .logout-btn {
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 2px solid transparent;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 42, 92, 0.2);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Navigation */
        .nav {
            background: var(--primary);
            padding: 1rem 0;
            position: sticky;
            top: 84px;
            z-index: 90;
            box-shadow: 0 2px 8px rgba(0, 42, 92, 0.2);
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
            border-radius: var(--radius-sm);
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
        
        /* Main Content */
        .container {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        
        /* Header Content */
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
            min-width: 180px;
            box-shadow: var(--shadow);
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
        
        /* Search Box */
        .search-section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .search-title {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .search-box {
            position: relative;
            max-width: 600px;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem 1.25rem 1rem 3rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--light-bg);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 42, 92, 0.1);
            background: white;
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }
        
        /* Table Container */
        .table-container {
            background: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        
        /* Table Styles */
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
            user-select: none;
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
            cursor: pointer;
            transform: translateX(4px);
        }
        
        .data-table td {
            padding: 1.25rem 1.5rem;
            color: var(--text);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border);
        }
        
        .data-table td:first-child {
            font-weight: 600;
            color: var(--primary);
        }
        
        .matricule-cell {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.9rem;
            background: rgba(0, 42, 92, 0.05);
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            display: inline-block;
        }
        
        .user-name {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .email-cell {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .department-badge {
            background: rgba(0, 42, 92, 0.1);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
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
        
        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            transition: var(--transition);
            margin-top: 2rem;
            border: 2px solid transparent;
        }
        
        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 42, 92, 0.25);
            background: linear-gradient(135deg, #001a3a 0%, #002A5C 100%);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Pagination & Results Info */
        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: var(--light-bg);
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .results-info {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .highlight {
            color: var(--primary);
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .container {
                padding: 0 1.5rem;
            }
            
            .data-table {
                min-width: auto;
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .logo-container {
                flex-direction: column;
                text-align: center;
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
                min-width: 140px;
            }
            
            .table-footer {
                flex-direction: column;
                text-align: center;
            }
            
            .container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }
            
            .search-section {
                padding: 1.5rem;
            }
        }
        
        /* Loading animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .container > * {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .container > *:nth-child(2) { animation-delay: 0.2s; }
        .container > *:nth-child(3) { animation-delay: 0.4s; }
        .container > *:nth-child(4) { animation-delay: 0.6s; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- En-tête avec logo -->
    <div class="header">
        <div class="logo-container">
            <img src="bhbank.png" alt="BH Bank" class="logo">
            <div class="brand-text">Administration</div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>

    <!-- Menu de navigation -->
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
            <a href="adherent.php" class="nav-link active">
                <i class="fas fa-users"></i> Liste des adhérents
            </a>
            <a href="liste_demandes.php" class="nav-link">
                <i class="fas fa-list-check"></i> Toutes les demandes
            </a>
            <a href="gestion_aide.php" class="nav-link">
                <i class="fas fa-cogs"></i> Gestion des aides
            </a>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="container">
        <!-- En-tête de page avec statistiques -->
        <div class="page-header">
            <h1 class="page-title">Gestion des Adhérents</h1>
                <div class="admin-stats">
        <div class="stat-card">
            <div class="stat-label">
                <i class="fas fa-users"></i> Total des adhérents
            </div>
            <div class="stat-value"><?= $total_result ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">
                <i class="fas fa-search"></i> Affichage
            </div>
            <div class="stat-value"><?= $search ? count($adherents) . '/' . $total_result : count($adherents) . '/' . $total_result ?></div>
        </div>
    </div>
        </div>

        <!-- Section de recherche -->
        <div class="search-section">
            <h2 class="search-title">
                <i class="fas fa-search"></i> Recherche d'adhérents
            </h2>
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="q" 
                        class="search-input" 
                        placeholder="Rechercher par matricule, nom, prénom, email ou département..." 
                        value="<?= htmlspecialchars($search) ?>" 
                        autofocus
                        autocomplete="off"
                    >
                </form>
            </div>
        </div>

        <!-- Tableau des adhérents -->
        <?php if (empty($adherents)): ?>
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3>Aucun adhérent trouvé</h3>
                    <p>
                        <?= $search ? 
                            "Aucun résultat pour \"" . htmlspecialchars($search) . "\"" : 
                            "La liste des adhérents est vide." 
                        ?>
                    </p>
                    <?php if ($search): ?>
                        <a href="adherent.php" class="back-link">
                            <i class="fas fa-times"></i> Effacer la recherche
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th>Email</th>
                            <th>Département</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adherents as $a): ?>
                        <tr onclick="window.location='fiche_adherent.php?matemp=<?= urlencode($a['matemp']) ?>'">
                            <td>
                                <span class="matricule-cell">
                                    <?= htmlspecialchars($a['matemp']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="user-name">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($a['prenom'], 0, 1) . substr($a['nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td class="email-cell">
                                <?= htmlspecialchars($a['email'] ?? '—') ?>
                            </td>
                            <td>
                                <span class="department-badge">
                                    <?= htmlspecialchars($a['departement_libelle'] ?? $a['code_affect'] ?? 'Non spécifié') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pied de tableau avec informations de pagination -->
                <div class="table-footer">
                    <div class="results-info">
                        <?php if ($search): ?>
                            <span class="highlight"><?= $start_result ?>-<?= $end_result ?></span> sur <span class="highlight"><?= $total_result ?></span> résultat(s) pour "<em><?= htmlspecialchars($search) ?></em>"
                        <?php else: ?>
                            Affichage des adhérents <span class="highlight"><?= $start_result ?>-<?= $end_result ?></span> sur <span class="highlight"><?= $total_result ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($total_pages > 1): ?>
                    <div class="results-info">
                        Page <span class="highlight"><?= $page ?></span> sur <span class="highlight"><?= $total_pages ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination" style="padding: 1.5rem; background: var(--light-bg); border-top: 1px solid var(--border); display: flex; justify-content: center; align-items: center; gap: 0.75rem;">
                    <!-- Bouton Précédent -->
                    <?php if ($page > 1): ?>
                        <a href="adherent.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                           class="pagination-btn">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php endif; ?>
                    
                    <!-- Numéros de page -->
                    <div class="pagination-numbers" style="display: flex; gap: 0.5rem;">
                        <?php 
                        // Afficher seulement quelques pages autour de la page actuelle
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        // Première page
                        if ($start_page > 1): ?>
                            <a href="adherent.php?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" 
                               class="pagination-number">
                                1
                            </a>
                            <?php if ($start_page > 2): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif;
                        endif;
                        
                        // Pages autour de la page actuelle
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="pagination-number active">
                                    <?= $i ?>
                                </span>
                            <?php else: ?>
                                <a href="adherent.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="pagination-number">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php // Dernière page
                        if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif; ?>
                            <a href="adherent.php?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" 
                               class="pagination-number">
                                <?= $total_pages ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Bouton Suivant -->
                    <?php if ($page < $total_pages): ?>
                        <a href="adherent.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                           class="pagination-btn">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <style>
                    .pagination-btn {
                        padding: 0.75rem 1.25rem;
                        background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
                        color: white;
                        text-decoration: none;
                        border-radius: var(--radius-sm);
                        font-weight: 600;
                        font-size: 0.9rem;
                        transition: var(--transition);
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        border: 2px solid transparent;
                    }
                    
                    .pagination-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(0, 42, 92, 0.2);
                        border-color: rgba(255, 255, 255, 0.2);
                    }
                    
                    .pagination-number {
                        padding: 0.75rem 1rem;
                        background: white;
                        color: var(--text);
                        text-decoration: none;
                        border-radius: var(--radius-sm);
                        font-weight: 600;
                        font-size: 0.9rem;
                        transition: var(--transition);
                        border: 2px solid var(--border);
                        min-width: 44px;
                        text-align: center;
                    }
                    
                    .pagination-number:hover {
                        border-color: var(--primary);
                        background: rgba(0, 42, 92, 0.05);
                        transform: translateY(-1px);
                    }
                    
                    .pagination-number.active {
                        background: linear-gradient(135deg, var(--secondary) 0%, #e53935 100%);
                        color: white;
                        border-color: var(--secondary);
                    }
                    
                    .pagination-ellipsis {
                        padding: 0.75rem 0.5rem;
                        color: var(--text-light);
                        font-weight: 600;
                    }
                </style>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Bouton de retour -->
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Accueil
        </a>
    </div>

    <script>
    // Auto-submit du formulaire de recherche lors de la saisie
    document.querySelector('.search-input').addEventListener('input', function(e) {
        // Soumettre le formulaire après un délai
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 500); // 500ms de délai
    });
    
    // Ajouter des animations aux lignes du tableau
    document.querySelectorAll('.data-table tbody tr').forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
        row.style.animation = 'fadeIn 0.3s ease-out forwards';
        row.style.opacity = '0';
    });
    </script>

</body>
</html>