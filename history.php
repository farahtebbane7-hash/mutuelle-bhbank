<?php
session_start();
if (!isset($_SESSION['matemp'])) {
    header('Location: index.php');
    exit;
}
require_once 'config.php';

$matemp = $_SESSION['matemp'];
$profil = $_SESSION['profil'] ?? 'user';

// Récupérer les demandes avec jointure sur aide
$stmt = $pdo->prepare("
    SELECT d.*, 
           a.libelle AS type_libelle,
           af.lib_affect AS departement
    FROM demande d
    LEFT JOIN aide a ON d.codetype = a.codetype
    LEFT JOIN adherent ad ON d.matemp = ad.matemp
    LEFT JOIN affectation af ON ad.code_affect = af.code_affect
    WHERE d.matemp = ?
    ORDER BY d.date DESC
");
$stmt->execute([$matemp]);
$demandes = $stmt->fetchAll();

// Compter les demandes par statut
$stats = [
    'total' => 0,
    'en_attente' => 0,
    'approuvee' => 0,
    'refusee' => 0,
    'brouillon' => 0
];

foreach ($demandes as $d) {
    $stats['total']++;
    $statut_key = str_replace([' ', 'é', 'è'], ['_', 'e', 'e'], $d['statut']);
    if (isset($stats[$statut_key])) {
        $stats[$statut_key]++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Demandes - Mutuelle BH Bank</title>
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 2px 2px 0 0;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .stat-total .stat-icon {
            background: rgba(0, 42, 92, 0.1);
            color: var(--primary);
        }
        
        .stat-pending .stat-icon {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .stat-approved .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .stat-rejected .stat-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }
        
        .stat-draft .stat-icon {
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-light);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .main-content:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .content-title {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .new-demand-btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-xs);
            font-weight: 700;
            font-size: 1rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 2px solid transparent;
        }
        
        .new-demand-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 42, 92, 0.25);
            background: linear-gradient(135deg, #001a3a 0%, #002A5C 100%);
            border-color: rgba(255, 255, 255, 0.2);
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
        
        .alert-success {
            background-color: #f0fdf4;
            border-left-color: var(--success);
            color: #166534;
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
        
        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
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
        
        /* Action Buttons */
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
        
        /* Montant Cell */
        .montant-cell {
            font-weight: 600;
            color: var(--primary);
        }
        
        .montant-accorde {
            color: var(--success);
            font-weight: 700;
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
            
            .content-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .new-demand-btn {
                justify-content: center;
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .data-table {
                min-width: auto;
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 100px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
        
        /* Animations */
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
                <div class="brand-text">Historique des Demandes</div>
            </div>
            <a href="dashboard.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Accueil
            </a>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Vos Demandes</h1> <br><br>
            <p class="page-subtitle">Suivez l'état de vos demandes d'aide</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total des demandes</div>
            </div>
            
            <div class="stat-card stat-pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?= $stats['en_attente'] ?></div>
                <div class="stat-label">En attente</div>
            </div>
            
            <div class="stat-card stat-approved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $stats['approuvee'] ?></div>
                <div class="stat-label">Approuvées</div>
            </div>
            
            <div class="stat-card stat-rejected">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value"><?= $stats['refusee'] ?></div>
                <div class="stat-label">Refusées</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                        <div><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="content-header">
                <h2 class="content-title">Historique complet</h2>
                <a href="nouvdemande.php" class="new-demand-btn">
                    <i class="fas fa-plus-circle"></i> Nouvelle demande
                </a>
            </div>

            <?php if (empty($demandes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>Aucune demande</h3>
                    <p>Vous n'avez pas encore soumis de demande d'aide. Cliquez sur "Nouvelle demande" pour commencer.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Type d'aide</th>
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
                                    <?php if ($profil === 'admin'): ?>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            <?= htmlspecialchars($d['departement'] ?? '—') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($d['type_libelle'] ?? $d['codetype']) ?></div>
                                    <?php if (!empty($d['observation'])): ?>
                                        <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 0.25rem;">
                                            <i class="fas fa-comment"></i> <?= substr(htmlspecialchars($d['observation']), 0, 30) ?>...
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($d['date'])) ?>
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        <?= date('H:i', strtotime($d['date'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($d['mnt_demande'] > 0): ?>
                                        <div class="montant-cell">
                                            <?= number_format($d['mnt_demande'], 2, ',', ' ') ?> DT
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($d['montant_accorde'] > 0): ?>
                                        <div class="montant-accorde" style="margin-top: 0.25rem;">
                                            <?= number_format($d['montant_accorde'], 2, ',', ' ') ?> DT
                                            <div style="font-size: 0.75rem; color: var(--success);">
                                                Montant accordé
                                            </div>
                                        </div>
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
                                    
                                    <?php if ($d['date_pec']): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 0.25rem;">
                                            <i class="far fa-calendar"></i> 
                                            <?= date('d/m/Y', strtotime($d['date_pec'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <?php if (!empty($d['justificatif'])): ?>
                                            <a href="uploads/<?= htmlspecialchars($d['justificatif']) ?>" 
                                               target="_blank" 
                                               class="action-btn">
                                                <i class="fas fa-eye"></i> Justificatif
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Table Footer -->
                <div style="padding: 1.5rem; background: var(--light-bg); border-top: 1px solid var(--border); border-radius: 0 0 var(--radius) var(--radius);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div style="color: var(--text-light); font-size: 0.95rem;">
                            <i class="fas fa-info-circle"></i> 
                            Affichage de <span style="color: var(--primary); font-weight: 600;"><?= count($demandes) ?></span> demande(s)
                        </div>
                        <div style="color: var(--text-light); font-size: 0.95rem;">
                            Cliquez sur "Justificatif" pour voir les documents joints
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Animation des lignes du tableau
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.data-table tbody tr');
        rows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Confirmation pour ouvrir le justificatif
        document.querySelectorAll('.action-btn[href*="uploads"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Ouvrir le justificatif dans un nouvel onglet ?')) {
                    e.preventDefault();
                }
            });
        });
    });
    
    // Tooltip pour les observations longues
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: var(--primary);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: var(--radius-xs);
                font-size: 0.875rem;
                z-index: 1000;
                max-width: 300px;
                white-space: normal;
                box-shadow: var(--shadow);
            `;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
    </script>
</body>
</html>