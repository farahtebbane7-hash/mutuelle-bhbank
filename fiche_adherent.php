<?php
session_start();
if (!isset($_SESSION['profil']) || $_SESSION['profil'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}
require_once 'config.php';

$matemp = $_GET['matemp'] ?? '';
if (!$matemp) {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Adhérent non spécifié.</h2>");
}

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
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Adhérent non trouvé.</h2>");
}

// Compter le nombre de demandes de cet adhérent
$stmt = $pdo->prepare("SELECT COUNT(*) as nb_demandes FROM demande WHERE matemp = ?");
$stmt->execute([$matemp]);
$nb_demandes = $stmt->fetch()['nb_demandes'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Adhérent - <?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?></title>
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
            padding: 1rem 2rem;
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
            height: 50px;
            transition: var(--transition);
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .brand-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -0.5px;
        }
        
        /* Navigation */
        .nav-links {
            display: flex;
            gap: 1rem;
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
        
        /* Main Content */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-title {
            color: var(--primary);
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
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
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
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
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--secondary) 0%, #e53935 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
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
            margin-bottom: 0.5rem;
        }
        
        .profile-role {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }
        
        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
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
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
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
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.25rem;
        }
        
        .info-item {
            background: var(--light-bg);
            padding: 1.25rem;
            border-radius: var(--radius-xs);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }
        
        .info-item:hover {
            transform: translateX(5px);
            background: white;
            box-shadow: var(--shadow);
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            color: var(--text);
            font-weight: 600;
            word-break: break-word;
        }
        
        .info-value.empty {
            color: var(--text-light);
            font-style: italic;
        }
        
        /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid var(--border);
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
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 1rem;
            vertical-align: middle;
        }
        
        .badge-primary {
            background: rgba(0, 42, 92, 0.1);
            color: var(--primary);
        }
        
        .badge-secondary {
            background: rgba(216, 27, 45, 0.1);
            color: var(--secondary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.5rem;
            }
            
            .nav-links {
                flex-direction: column;
                width: 100%;
            }
            
            .nav-btn {
                justify-content: center;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-bar {
                flex-direction: column;
            }
            
            .action-btn {
                justify-content: center;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card, .profile-hero {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.4s; }
        
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
        <!-- Header -->
        <div class="header">
            <div class="logo-container">
                <img src="bhbank.png" alt="BH Bank" class="logo">
                <div class="brand-text">Fiche Adhérent</div>
            </div>
            <div class="nav-links">
                <a href="adherent.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <a href="dashboard.php" class="nav-btn">
                    <i class="fas fa-home"></i> Accueil
                </a>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Profil Adhérent</h1>
            <p class="page-subtitle">Informations détaillées et historique</p>
        </div>

        <!-- Profile Hero -->
        <div class="profile-hero">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($adherent['prenom'], 0, 1) . substr($adherent['nom'], 0, 1)) ?>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name"><?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?></h2>
                    <p class="profile-role">
                        <?= htmlspecialchars($adherent['departement_libelle'] ?? '—') ?>
                        <span class="badge badge-primary"><?= htmlspecialchars($adherent['code_affect'] ?? '—') ?></span>
                    </p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?= $nb_demandes ?></span>
                            <span class="stat-label">Demandes</span>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="cards-grid">
            <!-- Informations Personnelles -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="card-title">Informations Personnelles</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-id-card"></i> Matricule
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($adherent['matemp']) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-envelope"></i> Email
                        </div>
                        <div class="info-value <?= empty($adherent['email']) ? 'empty' : '' ?>">
                            <?= !empty($adherent['email']) ? htmlspecialchars($adherent['email']) : 'Non renseigné' ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-phone"></i> Téléphone
                        </div>
                        <div class="info-value <?= empty($adherent['tel']) ? 'empty' : '' ?>">
                            <?= !empty($adherent['tel']) ? htmlspecialchars($adherent['tel']) : 'Non renseigné' ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-home"></i> Adresse
                        </div>
                        <div class="info-value <?= empty($adherent['adresse']) ? 'empty' : '' ?>">
                            <?= !empty($adherent['adresse']) ? htmlspecialchars($adherent['adresse']) : 'Non renseigné' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations Professionnelles -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3 class="card-title">Informations Professionnelles</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-building"></i> Département
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($adherent['departement_libelle'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-hashtag"></i> Code Affectation
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($adherent['code_affect'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-tag"></i> Type Affectation
                        </div>
                        <div class="info-value">
                            <?php
                            $type = $adherent['type_affect'] ?? '';
                            $type_label = match($type) {
                                'D' => 'Département',
                                'S' => 'Service',
                                default => $type ?: 'Non spécifié'
                            };
                            echo htmlspecialchars($type_label);
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar-check"></i> Date d'adhésion
                        </div>
                        <div class="info-value <?= empty($adherent['date_adh']) ? 'empty' : '' ?>">
                            <?= !empty($adherent['date_adh']) ? date('d/m/Y', strtotime($adherent['date_adh'])) : 'Non renseignée' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations Familiales -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="card-title">Informations Familiales</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-heart"></i> Situation familiale
                        </div>
                        <div class="info-value <?= empty($adherent['sit_fam']) ? 'empty' : '' ?>">
                            <?= !empty($adherent['sit_fam']) ? htmlspecialchars($adherent['sit_fam']) : 'Non renseignée' ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-baby"></i> Nombre d'enfants
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($adherent['nb_enf'] ?? '0') ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-file-alt"></i> Demandes totales
                        </div>
                        <div class="info-value">
                            <?= $nb_demandes ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="actions-bar">
            <a href="adherent.php" class="action-btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
            <a href="liste_demandes.php?matemp=<?= urlencode($adherent['matemp']) ?>" class="action-btn btn-primary">
                <i class="fas fa-history"></i> Voir l'historique des demandes
            </a>
            <a href="javascript:window.print()" class="action-btn btn-secondary">
                <i class="fas fa-print"></i> Imprimer la fiche
            </a>
        </div>
    </div>

    <script>
        // Animation des cartes au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.2}s`;
            });
        });
        
        // Confirmation avant impression
        document.querySelector('a[href*="print"]').addEventListener('click', function(e) {
            if (!confirm('Voulez-vous imprimer cette fiche adhérent ?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>