<?php
session_start();
if (!isset($_SESSION['profil']) || $_SESSION['profil'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Accès réservé aux administrateurs.</h2>");
}
require_once 'config.php';

// Récupérer le filtre de statut (optionnel)
$statut_filtre = $_GET['statut'] ?? 'tous';
$search_query = $_GET['q'] ?? '';

// Construire la requête avec jointure
$sql = "
    SELECT d.*, 
           a.nom, 
           a.prenom, 
           a.matemp,
           af.lib_affect AS departement_libelle,
           ay.libelle as type_demande
    FROM demande d
    JOIN adherent a ON d.matemp = a.matemp
    LEFT JOIN affectation af ON a.code_affect = af.code_affect
    LEFT JOIN aide ay ON d.codetype = ay.codetype
";

$params = [];
$conditions = [];

// Filtre par statut
if ($statut_filtre !== 'tous') {
    $conditions[] = "d.statut = ?";
    $params[] = $statut_filtre;
}

// Recherche par nom, prénom, matricule ou référence
if (!empty($search_query)) {
    $search_term = "%$search_query%";
    $conditions[] = "(a.nom LIKE ? OR a.prenom LIKE ? OR a.matemp LIKE ? OR d.reference_dmde LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Ajouter les conditions à la requête
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY d.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$demandes = $stmt->fetchAll();

// Compter les statistiques
$stats = [
    'total' => 0,
    'en_attente' => 0,
    'approuvee' => 0,
    'refusee' => 0,
    'brouillon' => 0
];

$stmt = $pdo->query("
    SELECT statut, COUNT(*) as count 
    FROM demande 
    GROUP BY statut
");
$stat_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['en_attente'] = $stat_counts['en attente'] ?? 0;
$stats['approuvee'] = $stat_counts['approuvée'] ?? 0;
$stats['refusee'] = $stat_counts['refusée'] ?? 0;
$stats['brouillon'] = $stat_counts['brouillon'] ?? 0;
$stats['total'] = array_sum($stats) - $stats['total']; // total est 0, donc on recalcule

// Calculer le nombre de résultats filtrés
$filtered_count = count($demandes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toutes les Demandes - Administration</title>
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
        
        /* Search & Filters */
        .search-section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .search-box {
            position: relative;
            max-width: 600px;
            margin: 0 auto 1.5rem;
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
        
        .filters-container {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-xs);
            background: white;
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-btn:hover {
            border-color: var(--primary);
            background: rgba(0, 42, 92, 0.05);
            transform: translateY(-2px);
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            border-color: var(--primary);
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
            transform: translateX(4px);
        }
        
        .data-table td {
            padding: 1.25rem 1.5rem;
            color: var(--text);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-en-attente {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .status-approuvee {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .status-refusee {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .status-brouillon {
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-light);
            border: 1px solid rgba(100, 116, 139, 0.3);
        }
        
        /* Actions */
        .action-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-xs);
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 2px solid transparent;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 42, 92, 0.2);
            background: linear-gradient(135deg, #001a3a 0%, #002A5C 100%);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
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
        
        /* Table Footer */
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
                min-width: 120px;
            }
            
            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-btn {
                justify-content: center;
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
        
        
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <img src="bhbank.png" alt="BH Bank" class="logo">
                <div class="brand-text">Gestion des Demandes</div>
            </div>
            <a href="logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
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
                <a href="liste_demandes.php" class="nav-link active">
                    <i class="fas fa-list-check"></i> Toutes les demandes
                </a>
                <a href="gestion_aide.php" class="nav-link">
                    <i class="fas fa-cogs"></i> Gestion des aides
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Gestion des Demandes</h1>
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-file-alt"></i> Total
                    </div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-clock"></i> En attente
                    </div>
                    <div class="stat-value"><?= $stats['en_attente'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i> Approuvées
                    </div>
                    <div class="stat-value"><?= $stats['approuvee'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">
                        <i class="fas fa-times-circle"></i> Refusées
                    </div>
                    <div class="stat-value"><?= $stats['refusee'] ?></div>
                </div>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="search-section">
            <h3 style="color: var(--primary); margin-bottom: 1.5rem; text-align: center;">
                <i class="fas fa-search"></i> Recherche & Filtres
            </h3>
            
            <!-- Barre de recherche -->
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <form method="GET" action="">
                    <input type="text" 
                           name="q" 
                           class="search-input" 
                           placeholder="Rechercher par nom, prénom, matricule ou référence..." 
                           value="<?= htmlspecialchars($search_query) ?>"
                           autocomplete="off">
                    <?php if ($statut_filtre !== 'tous'): ?>
                        <input type="hidden" name="statut" value="<?= htmlspecialchars($statut_filtre) ?>">
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Filtres par statut -->
            <div class="filters-container">
                <a href="?statut=tous&q=<?= urlencode($search_query) ?>" 
                   class="filter-btn <?= $statut_filtre === 'tous' ? 'active' : '' ?>">
                    <i class="fas fa-layer-group"></i> Toutes (<?= $stats['total'] ?>)
                </a>
                <a href="?statut=en attente&q=<?= urlencode($search_query) ?>" 
                   class="filter-btn <?= $statut_filtre === 'en attente' ? 'active' : '' ?>">
                    <i class="fas fa-clock"></i> En attente (<?= $stats['en_attente'] ?>)
                </a>
                <a href="?statut=approuvée&q=<?= urlencode($search_query) ?>" 
                   class="filter-btn <?= $statut_filtre === 'approuvée' ? 'active' : '' ?>">
                    <i class="fas fa-check-circle"></i> Approuvées (<?= $stats['approuvee'] ?>)
                </a>
                <a href="?statut=refusée&q=<?= urlencode($search_query) ?>" 
                   class="filter-btn <?= $statut_filtre === 'refusée' ? 'active' : '' ?>">
                    <i class="fas fa-times-circle"></i> Refusées (<?= $stats['refusee'] ?>)
                </a>
                <a href="?statut=brouillon&q=<?= urlencode($search_query) ?>" 
                   class="filter-btn <?= $statut_filtre === 'brouillon' ? 'active' : '' ?>">
                    <i class="fas fa-edit"></i> Brouillons (<?= $stats['brouillon'] ?>)
                </a>
            </div>
        </div>

        <!-- Tableau des demandes -->
        <?php if (empty($demandes)): ?>
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>Aucune demande trouvée</h3>
                    <p>
                        <?php if ($search_query || $statut_filtre !== 'tous'): ?>
                            Aucune demande ne correspond à votre recherche.
                        <?php else: ?>
                            Aucune demande n'a été soumise pour le moment.
                        <?php endif; ?>
                    </p>
                    <?php if ($search_query || $statut_filtre !== 'tous'): ?>
                        <a href="liste_demandes.php" class="action-btn">
                            <i class="fas fa-times"></i> Effacer les filtres
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Adhérent</th>
                            <th>Type</th>
                            <th>Département</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $d): ?>
                        <?php 
                        $status_class = str_replace([' ', 'é', 'è'], ['-', 'e', 'e'], $d['statut']);
                        $status_class = "status-" . $status_class;
                        ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary);">#<?= htmlspecialchars($d['reference_dmde'] ?? $d['id']) ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.8rem;">
                                        <?= strtoupper(substr($d['prenom'], 0, 1) . substr($d['nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-light);"><?= htmlspecialchars($d['matemp']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="background: rgba(0, 42, 92, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 500;">
                                    <?= htmlspecialchars($d['type_demande'] ?? $d['codetype']) ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($d['departement_libelle'] ?? '—') ?>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($d['date'])) ?>
                                <div style="font-size: 0.85rem; color: var(--text-light);">
                                    <?= date('H:i', strtotime($d['date'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($d['mnt_demande'] > 0): ?>
                                    <span style="font-weight: 600; color: var(--primary);">
                                        <?= number_format($d['mnt_demande'], 2, ',', ' ') ?> DT
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $status_class ?>">
                                    <i class="fas 
                                        <?= $d['statut'] === 'en attente' ? 'fa-clock' : '' ?>
                                        <?= $d['statut'] === 'approuvée' ? 'fa-check-circle' : '' ?>
                                        <?= $d['statut'] === 'refusée' ? 'fa-times-circle' : '' ?>
                                        <?= $d['statut'] === 'brouillon' ? 'fa-edit' : '' ?>
                                    "></i>
                                    <?= ucfirst($d['statut']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($d['statut'] === 'en attente'): ?>
                                    <a href="traiter_demande.php?id=<?= $d['id'] ?>" class="action-btn">
                                        <i class="fas fa-gavel"></i> Traiter
                                    </a>
                                <?php elseif ($d['statut'] !== 'brouillon'): ?>
                                    <a href="traiter_demande.php?id=<?= $d['id'] ?>" class="action-btn">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light); font-style: italic;">En rédaction</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pied de tableau -->
                <div class="table-footer">
                    <div class="results-info">
                        <?php if ($search_query || $statut_filtre !== 'tous'): ?>
                            <span class="highlight"><?= $filtered_count ?></span> demande(s) trouvée(s)
                            <?php if ($search_query): ?> pour "<em><?= htmlspecialchars($search_query) ?></em>"<?php endif; ?>
                            <?php if ($statut_filtre !== 'tous'): ?> avec le statut "<?= htmlspecialchars($statut_filtre) ?>"<?php endif; ?>
                        <?php else: ?>
                            Affichage de <span class="highlight"><?= $filtered_count ?></span> demande(s) sur <?= $stats['total'] ?>
                        <?php endif; ?>
                    </div>
                    <div class="results-info">
                        <i class="fas fa-info-circle"></i> 
                        Cliquez sur "Traiter" pour examiner une demande en attente
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bouton de retour -->
        <a href="dashboard.php" class="nav-btn" style="margin-top: 2rem; display: inline-flex;">
            <i class="fas fa-arrow-left"></i> Accueil
        </a>
    </div>

    <script>
    // Auto-submit du formulaire de recherche lors de la saisie
    document.querySelector('.search-input').addEventListener('input', function(e) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.form.submit();
        }, 500);
    });
    
    // Animation des lignes du tableau
    document.querySelectorAll('.data-table tbody tr').forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
        row.style.animation = 'fadeIn 0.3s ease-out forwards';
        row.style.opacity = '0';
    });
    
    // Confirmation pour le traitement
    document.querySelectorAll('.action-btn[href*="traiter_demande"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous prêt à traiter cette demande ?')) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>