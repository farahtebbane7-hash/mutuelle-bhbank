<?php
session_start();
// Rediriger si non connecté
if (!isset($_SESSION['matemp'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$matemp = $_SESSION['matemp'];
$profil = $_SESSION['profil'] ?? 'user';

$stmt = $pdo->prepare("
    SELECT 
        a.nom, 
        a.prenom, 
        a.email, 
        a.code_affect,
        af.lib_affect AS departement_libelle
    FROM adherent a
    LEFT JOIN affectation af ON a.code_affect = af.code_affect
    WHERE a.matemp = ?
");
$stmt->execute([$matemp]);
$adherent = $stmt->fetch();

if (!$adherent) {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Erreur : vos données n'ont pas été trouvées. Contactez le service RH.</h2>");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Mutuelle BH Bank</title>
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
            display: flex;
            flex-direction: column;
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
            height: 80px;
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
        .content {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, #003d7a 100%);
            color: white;
            padding: 2.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        
        .welcome-card:before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.1;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: rotate(0deg) translate(0, 0); }
            100% { transform: rotate(360deg) translate(10px, 10px); }
        }
        
        .welcome-card h1 {
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 1;
        }
        
        .welcome-card p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* Profile Card */
        .profile-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }
        
        .avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .profile-info h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .profile-info p {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.25rem;
        }
        
        .info-item {
            background: var(--light-bg);
            padding: 1.25rem;
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }
        
        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            color: var(--text);
            font-weight: 600;
        }
        
        /* Admin Badge */
        .admin-badge {
            background: linear-gradient(135deg, #c62828 0%, #d81b2d 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 4px 15px rgba(216, 27, 45, 0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 4px 15px rgba(216, 27, 45, 0.2); }
            50% { box-shadow: 0 4px 25px rgba(216, 27, 45, 0.4); }
            100% { box-shadow: 0 4px 15px rgba(216, 27, 45, 0.2); }
        }
        
        /* Action Buttons */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2.5rem;
        }
        
        .action-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .action-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .action-card h3 {
            color: var(--primary);
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }
        
        .action-card p {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        
        .action-btn {
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            display: inline-block;
            border: 2px solid transparent;
        }
        
        .action-btn:hover {
            background: linear-gradient(135deg, #001a3a 0%, #002A5C 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 42, 92, 0.25);
        }
        
        /* FOOTER */
        .footer {
            background: var(--primary);
            color: white;
            padding: 3rem 1.5rem 1.5rem;
            margin-top: auto;
            position: relative;
            overflow: hidden;
        }
        
        .footer:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary) 0%, #ff6b6b 100%);
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }
        
        .footer-section h3 {
            color: white;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .footer-section h3:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--secondary);
            border-radius: 2px;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .footer-contact p {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .footer-contact i {
            color: var(--secondary);
            width: 20px;
        }
        
        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .social-icon:hover {
            background: var(--secondary);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 3rem auto 0;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .footer-bottom p {
            margin-bottom: 0.5rem;
        }
        
        .copyright {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }
        
        /* Responsive */
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
            
            .welcome-card h1 {
                font-size: 1.75rem;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .footer-section h3:after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .footer-links a {
                justify-content: center;
            }
            
            .footer-contact p {
                justify-content: center;
            }
            
            .footer-social {
                justify-content: center;
            }
        }
        
        /* Loading animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .content > * {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .content > *:nth-child(2) { animation-delay: 0.2s; }
        .content > *:nth-child(3) { animation-delay: 0.4s; }
        .content > *:nth-child(4) { animation-delay: 0.6s; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- En-tête avec logo -->
    <div class="header">
        <div class="logo-container">
            <img src="bhbank.png" alt="BH Bank" class="logo">
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>

    <!-- Menu de navigation -->
    <div class="nav">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-home"></i> Accueil
            </a>
            <a href="nouvdemande.php" class="nav-link">
                <i class="fas fa-plus-circle"></i> Nouvelle demande
            </a>
            <a href="history.php" class="nav-link">
                <i class="fas fa-history"></i> Historique
            </a>
            <a href="profils.php" class="nav-link">
                <i class="fas fa-user"></i> Mon Profil
            </a>
            <?php if ($profil === 'admin'): ?>
                <a href="adherent.php" class="nav-link">
                    <i class="fas fa-users"></i> Liste des adhérents
                </a>
                <a href="liste_demandes.php" class="nav-link">
                    <i class="fas fa-list-check"></i> Toutes les demandes
                </a>
                <a href="gestion_aide.php" class="nav-link">
                    <i class="fas fa-cogs"></i> Gestion des aides
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="content">
        <!-- Carte de bienvenue -->
        <div class="welcome-card">
            <h1>Bienvenue sur votre espace <?= htmlspecialchars($adherent['prenom']) ?></h1>
            <p>Gérez vos demandes d'aide simplement et efficacement</p>
        </div>

        <!-- Carte de profil -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar">
                    <?= strtoupper(substr($adherent['prenom'], 0, 1) . substr($adherent['nom'], 0, 1)) ?>
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($adherent['prenom'] . ' ' . $adherent['nom']) ?></h2>
                    <p>Matricule : <?= htmlspecialchars($matemp) ?></p>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($adherent['email'] ?? 'Non renseigné') ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Département</div>
                    <div class="info-value">
                        <?= htmlspecialchars($adherent['departement_libelle'] ?? $adherent['code_affect'] ?? 'Non spécifié') ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Statut</div>
                    <div class="info-value">
                        <?= $profil === 'admin' ? 'Administrateur' : 'Adhérent' ?>
                    </div>
                </div>
                
            </div>
        </div>

        <?php if ($profil === 'admin'): ?>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i> Mode Administrateur
            </div>
        <?php endif; ?>

        <!-- Actions principales -->
        <div class="action-grid">
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h3>Nouvelle Demande</h3>
                <p>Soumettez une nouvelle demande d'aide</p><br>
                <a href="nouvdemande.php" class="action-btn">
                    <i class="fas fa-rocket"></i> Démarrer
                </a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>Suivi des Demandes</h3>
                <p>Consultez l'historique et le statut de vos demandes</p>
                <a href="history.php" class="action-btn">
                    <i class="fas fa-eye"></i> Voir l'historique
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Mutuelle BH Bank</h3>
                <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                    Votre partenaire de confiance pour la solidarité et l'entraide entre collaborateurs.
                </p>
                <div class="footer-social">
                    <a href="https://www.linkedin.com/company/bh-bank" class="social-icon" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="https://www.facebook.com/BHBank/" class="social-icon" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    
                    <a href="https://www.instagram.com/bh_bank/?hl=en" class="social-icon" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Liens Rapides</h3>
                <ul class="footer-links">
                    <li><a href="dashboard.php"><i class="fas fa-chevron-right"></i> Accueil</a></li>
                    <li><a href="nouvdemande.php"><i class="fas fa-chevron-right"></i> Nouvelle demande</a></li>
                    <li><a href="history.php"><i class="fas fa-chevron-right"></i> Historique</a></li>
                    <li><a href="profils.php"><i class="fas fa-chevron-right"></i> Mon profil</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact</h3>
                <div class="footer-contact">
                    <p><i class="fas fa-phone"></i> +216 71 126 000</p>
                    <p><i class="fas fa-envelope"></i> contact@bhbank.tn</p>
                    <p><i class="fas fa-map-marker-alt"></i> 18 Avenue Mohamed V Tunis 1023</p>
                    <p><i class="fas fa-clock"></i> Lun - Ven: 8h00 - 17h00</p>
                </div>
            </div>
        </div>
        
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Mutuelle BH Bank. Tous droits réservés.</p>
            <p class="copyright">Développé avec <i class="fas fa-heart" style="color: var(--secondary);"></i> par Farah Tebbane</p>
        </div>
    </footer>

</body>
</html>