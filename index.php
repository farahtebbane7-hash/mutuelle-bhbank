<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - BH Bank Mutuelle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #002A5C;
            --secondary: #D81B2D;
            --accent: #FFD700;
            --light: #f8fafc;
            --dark: #1a1a1a;
            --success: #28a745;
            --error: #dc3545;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            --shadow-light: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --radius: 16px;
            --radius-sm: 8px;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, #001a3a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        /* Animated background elements */
        .bg-element {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float 15s infinite linear;
        }

        .bg-element:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        .bg-element:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -100px;
            right: -100px;
            animation-delay: 5s;
        }

        .bg-element:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            right: 10%;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(30px, 30px) rotate(90deg);
            }
            50% {
                transform: translate(0, 60px) rotate(180deg);
            }
            75% {
                transform: translate(-30px, 30px) rotate(270deg);
            }
        }

        .container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 2;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: var(--transition);
            animation: slideUp 0.6s ease-out;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 180px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            transition: var(--transition);
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .title {
            color: var(--primary);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: var(--secondary);
            font-size: 16px;
            font-weight: 500;
            opacity: 0.9;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            opacity: 0.7;
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-sm);
            font-size: 16px;
            background: white;
            transition: var(--transition);
            color: var(--dark);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 42, 92, 0.1);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            opacity: 0.6;
            transition: var(--transition);
            z-index: 2;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
        }

        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        .forgot-link {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-link:hover {
            color: #b01520;
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--secondary) 0%, #b01520 100%);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .submit-btn:hover:before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(216, 27, 45, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: var(--error);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            border-left: 4px solid var(--error);
            animation: shake 0.5s ease-in-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .success-message {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            border-left: 4px solid var(--success);
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .help-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .help-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .language-selector {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 3;
        }

        .language-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .language-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .version {
            text-align: center;
            color: rgb(255, 255, 255);
            font-size: 15px;
            margin-top: 20px;
            position: relative;
            z-index: 2;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 25px;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .language-selector {
                top: 10px;
                right: 10px;
            }
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

        /* Particle animation */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: particle-float linear infinite;
        }

        @keyframes particle-float {
            to {
                transform: translateY(-100vh);
            }
        }
    </style>
</head>
<body>
    <!-- Background elements -->
    <div class="bg-element"></div>
    <div class="bg-element"></div>
    <div class="bg-element"></div>
    
    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <!-- Language selector -->
    <div class="language-selector">
        <button class="language-btn">
            <i class="fas fa-globe"></i>
            <span>Français</span>
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <div class="container">
        <div class="login-card">
            <div class="logo-section">
                <img src="bhbank.png" alt="BH Bank" class="logo">
                <h1 class="title">Mutuelle BH Bank</h1>
                <p class="subtitle">Accès sécurisé</p>
            </div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    $errorMessages = [
                        'invalid' => 'Matricule ou mot de passe incorrect.',
                        'empty' => 'Veuillez remplir tous les champs.',
                        'expired' => 'Votre session a expiré. Veuillez vous reconnecter.',
                        'inactive' => 'Compte inactif. Contactez l\'administrateur.'
                    ];
                    echo $errorMessages[$_GET['error']] ?? 'Une erreur est survenue.';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    $successMessages = [
                        'logout' => 'Déconnexion réussie.',
                        'registered' => 'Inscription réussie. Veuillez vous connecter.'
                    ];
                    echo $successMessages[$_GET['success']] ?? 'Opération réussie.';
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="auth.php" id="loginForm">
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               name="matemp" 
                               class="form-input" 
                               placeholder="Matricule"
                               required
                               autocomplete="username"
                               autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               name="mdp" 
                               id="password" 
                               class="form-input" 
                               placeholder="Mot de passe"
                               required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Se connecter</span>
                </button>
            </form>

            <div class="footer-links">
                <p class="footer-text">Première connexion ?</p>
                <a href="#" class="help-link" id="helpLink">
                    <i class="fas fa-question-circle"></i>
                    Guide d'utilisation
                </a>
            </div>
        </div>

        <div class="version">
            © 2026 BH Bank Mutuelle développée par Farah Tebbane
        </div>
    </div>

    <script>
        // Password toggle visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });

        // Form submission loading state
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnText = submitBtn.querySelector('span');

        loginForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtnText.innerHTML = '';
            submitBtn.innerHTML = '<div class="loading"></div>';
        });

        // Forgot password modal
        

        // Help link
        const helpLink = document.getElementById('helpLink');
        helpLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Guide d\'utilisation disponible sur l\'intranet de BH Bank.');
        });

        // Auto-hide error/success messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.error-message, .success-message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);

        // Particles animation
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size
                const size = Math.random() * 4 + 1;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}vw`;
                particle.style.top = `${Math.random() * 100}vh`;
                
                // Random animation
                const duration = Math.random() * 10 + 10;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                // Random opacity
                particle.style.opacity = Math.random() * 0.3 + 0.1;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                loginForm.submit();
            }
            
            // Escape to clear form
            if (e.key === 'Escape') {
                loginForm.reset();
            }
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Auto-focus on matricule field
            const matriculeField = document.querySelector('input[name="matemp"]');
            if (matriculeField.value === '') {
                matriculeField.focus();
            }
        });
    </script>
</body>
</html>