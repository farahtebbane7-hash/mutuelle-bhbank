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

// Récupérer les types d'aide depuis la base
$stmt = $pdo->query("SELECT codetype, libelle FROM aide ORDER BY libelle");
$aides = $stmt->fetchAll();

// Récupérer les personnes à charge de l'employé
$matricule = $_SESSION['matemp'];
$stmt = $pdo->prepare("
    SELECT id, prenom, nom, typepers 
    FROM perscharge 
    WHERE matemp = ? 
    ORDER BY typepers, prenom
");
$stmt->execute([$matricule]);
$perscharge = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Demande - Mutuelle BH Bank</title>
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
            max-width: 800px;
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
        
        /* Form Container */
        .form-container {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .form-container:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .form-title {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-align: center;
            position: relative;
            padding-bottom: 1rem;
        }
        
        .form-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--secondary);
            border-radius: 2px;
        }
        
        .form-subtitle {
            color: var(--text-light);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
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
        
        .form-control:hover {
            border-color: #cbd5e1;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }
        
        /* File Upload */
        .file-upload {
            border: 2px dashed var(--border);
            border-radius: var(--radius-xs);
            padding: 2.5rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--light-bg);
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(0, 42, 92, 0.02);
        }
        
        .file-upload input[type="file"] {
            display: none;
        }
        
        .file-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .file-icon {
            font-size: 3rem;
            color: var(--primary);
            opacity: 0.7;
        }
        
        .file-text {
            color: var(--text);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .file-hint {
            color: var(--text-light);
            font-size: 0.9rem;
            max-width: 400px;
            line-height: 1.4;
        }
        
        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(0, 42, 92, 0.05);
            border-radius: var(--radius-xs);
            display: none;
        }
        
        .file-preview.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Dynamic Fields */
        .dynamic-fields {
            margin-top: 2rem;
            padding: 2rem;
            background: var(--light-bg);
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
            display: none;
            animation: slideIn 0.3s ease-out;
        }
        
        .dynamic-fields.active {
            display: block;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Error Messages */
        .alert {
            padding: 1.25rem;
            border-radius: var(--radius-xs);
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease-out;
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
        
        /* Submit Button */
        .form-actions {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid var(--border);
        }
        
        .submit-btn {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, var(--primary) 0%, #004085 100%);
            color: white;
            border: none;
            border-radius: var(--radius-xs);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 42, 92, 0.25);
            background: linear-gradient(135deg, #001a3a 0%, #002A5C 100%);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .loader {
            display: none;
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
        
        .loading .loader {
            display: inline-block;
        }
        
        .loading .btn-text {
            display: none;
        }
        
        /* Form Help */
        .form-help {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: 0.5rem;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            
            .form-container {
                padding: 1.5rem;
            }
            
            .form-title {
                font-size: 1.75rem;
            }
            
            .file-upload {
                padding: 1.5rem;
            }
            
            .dynamic-fields {
                padding: 1.5rem;
            }
        }
        
        /* Animation pour les champs dynamiques */
        .animate-field {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Scrollbar personnalisée */
        .form-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .form-container::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 4px;
        }
        
        .form-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        .form-container::-webkit-scrollbar-thumb:hover {
            background: #001a3a;
        }
        
        /* Tooltip pour les champs obligatoires */
        [data-tooltip] {
            position: relative;
        }
        
        [data-tooltip]:hover:after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-xs);
            font-size: 0.875rem;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="bhbank.png" alt="BH Bank" class="logo">
                <div class="brand-text">Nouvelle Demande</div>
            </div>
            <a href="dashboard.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Accueil
            </a>
        </div>

        <div class="form-container">
            <h1 class="form-title">Nouvelle Demande d'Aide</h1>
            <p class="form-subtitle">Remplissez le formulaire ci-dessous pour soumettre votre demande</p>

            <?php if (!empty($_SESSION['errors'])): ?>
                <div class="alert alert-error">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                        <div>
                            <strong style="display: block; margin-bottom: 0.5rem;">Des erreurs sont survenues :</strong>
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <div>• <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
    
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                        <div><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form id="demandeForm" method="POST" action="demande.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="type" class="form-label required" data-tooltip="Sélectionnez le type d'aide demandée">
                        <i class="fas fa-hand-holding-heart"></i> Type de demande
                    </label>
                    <select id="type" name="type" class="form-control" required onchange="toggleDynamicFields()">
                        <option value="">-- Choisissez le type d'aide --</option>
                        <?php foreach ($aides as $a): ?>
                            <option value="<?= htmlspecialchars($a['codetype'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($a['libelle'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date" class="form-label required" data-tooltip="Date à laquelle l'événement s'est produit">
                        <i class="fas fa-calendar-alt"></i> Date de l'événement
                    </label>
                    <input type="date" id="date" name="date" class="form-control" 
                           value="<?= date('Y-m-d') ?>" 
                           max="<?= date('Y-m-d') ?>" 
                           required>
                    <div class="form-help">
                        <i class="fas fa-info-circle"></i> Ne peut pas être une date future
                    </div>
                </div>

                <div class="form-group">
                    <label for="montant" class="form-label" id="montantLabel" data-tooltip="Montant en dinars tunisiens">
                        <i class="fas fa-money-bill-wave"></i> Montant demandé (DT)
                    </label>
                    <input type="number" step="0.01" id="montant" name="montant" 
                           class="form-control" min="0" max="10000" 
                           placeholder="0.00">
                    <div class="form-help">
                        <i class="fas fa-info-circle"></i> Montant maximum autorisé : 10 000 DT
                    </div>
                </div>

                <div id="dynamicFields" class="dynamic-fields">
                </div>
                <div class="form-group">
                    <label class="form-label required" data-tooltip="Document justificatif obligatoire">
                        <i class="fas fa-file-upload"></i> Justificatif
                    </label>
                    <div class="file-upload" onclick="document.getElementById('justificatif').click()">
                        <div class="file-content">
                            <i class="fas fa-cloud-upload-alt file-icon"></i>
                            <div class="file-text">Cliquez pour télécharger un fichier</div>
                            <div class="file-hint">
                                Formats acceptés : PDF, JPG, JPEG, PNG<br>
                                Taille maximale : 5MB
                            </div>
                        </div>
                        <input type="file" id="justificatif" name="justificatif" 
                               accept=".pdf,.jpg,.jpeg,.png" required 
                               onchange="previewFile(this)">
                    </div>
                    <div id="filePreview" class="file-preview"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <div class="loader"></div>
                        <span class="btn-text">
                            <i class="fas fa-paper-plane"></i> Soumettre la demande
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const fieldConfigs = {
        'PH': { montant: true, label: "Montant pour plafond pharmacie" },
        'SM': { montant: true, label: "Montant pour soins médicaux" },
        'HO': { montant: true, label: "Montant pour hospitalisation" },
        'LO': { montant: true, label: "Montant pour logement" },
        'NA': {
            label: "Informations naissance",
            fields: [
                {
                    name: "nom_nouveau_ne",
                    type: "text",
                    label: "Nom du nouveau-né",
                    placeholder: "Entrez le nom complet",
                    required: true
                },
                {
                    name: "date_naissance",
                    type: "date",
                    label: "Date de naissance",
                    max: new Date().toISOString().split('T')[0],
                    required: true
                }
            ]
        },
        'MA': {
            label: "Informations mariage",
            fields: [
                {
                    name: "conjoint",
                    type: "select",
                    label: "Conjoint(e)",
                    options: <?= json_encode(array_filter($perscharge, function($p) {
                        return $p['typepers'] === 'conjoint';
                    })) ?>,
                    required: true
                },
                {
                    name: "date_mariage",
                    type: "date",
                    label: "Date du mariage",
                    max: new Date().toISOString().split('T')[0],
                    required: true
                }
            ]
        }
    };

    function toggleDynamicFields() {
        const typeSelect = document.getElementById('type');
        const type = typeSelect.value;
        const dynamicFields = document.getElementById('dynamicFields');
        const montantInput = document.getElementById('montant');
        const montantLabel = document.getElementById('montantLabel');
        
        // Réinitialiser les champs dynamiques
        dynamicFields.innerHTML = '';
        dynamicFields.classList.remove('active');
        
        // Masquer le montant par défaut
        montantInput.closest('.form-group').style.display = 'none';
        montantInput.required = false;
        
        if (type && fieldConfigs[type]) {
            const config = fieldConfigs[type];
            
            // Gérer le champ montant
            if (config.montant) {
                montantInput.closest('.form-group').style.display = 'block';
                montantInput.required = true;
                if (config.label) {
                    montantLabel.innerHTML = `<i class="fas fa-money-bill-wave"></i> ${config.label} (DT)`;
                }
            }
            
            // Ajouter les champs spécifiques
            if (config.fields && config.fields.length > 0) {
                dynamicFields.classList.add('active');
                dynamicFields.innerHTML = `<h3 style="color: var(--primary); margin-bottom: 1.5rem; font-size: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-info-circle"></i> ${config.label}
                </h3>`;
                
                config.fields.forEach((field, index) => {
                    const fieldId = `${type}_${field.name}`;
                    let fieldHtml = '';
                    
                    switch(field.type) {
                        case 'select':
                            fieldHtml = `
                                <div class="form-group animate-field" style="animation-delay: ${index * 0.1}s">
                                    <label for="${fieldId}" class="form-label ${field.required ? 'required' : ''}">
                                        <i class="fas fa-user"></i> ${field.label}
                                    </label>
                                    <select id="${fieldId}" name="${field.name}" class="form-control" ${field.required ? 'required' : ''}>
                                        <option value="">-- Sélectionnez --</option>
                                        ${field.options ? field.options.map(option => 
                                            `<option value="${option.id}">${option.prenom} ${option.nom} (${option.typepers})</option>`
                                        ).join('') : ''}
                                    </select>
                                </div>
                            `;
                            break;
                            
                        case 'text':
                            fieldHtml = `
                                <div class="form-group animate-field" style="animation-delay: ${index * 0.1}s">
                                    <label for="${fieldId}" class="form-label ${field.required ? 'required' : ''}">
                                        <i class="fas fa-font"></i> ${field.label}
                                    </label>
                                    <input type="text" id="${fieldId}" name="${field.name}" 
                                           class="form-control" placeholder="${field.placeholder || ''}"
                                           ${field.required ? 'required' : ''}>
                                </div>
                            `;
                            break;
                            
                        case 'date':
                            fieldHtml = `
                                <div class="form-group animate-field" style="animation-delay: ${index * 0.1}s">
                                    <label for="${fieldId}" class="form-label ${field.required ? 'required' : ''}">
                                        <i class="fas fa-calendar"></i> ${field.label}
                                    </label>
                                    <input type="date" id="${fieldId}" name="${field.name}" 
                                           class="form-control" max="${field.max || ''}"
                                           ${field.required ? 'required' : ''}>
                                </div>
                            `;
                            break;
                    }
                    
                    dynamicFields.innerHTML += fieldHtml;
                });
            }
        }
    }
    
    // Aperçu du fichier
    function previewFile(input) {
        const preview = document.getElementById('filePreview');
        const file = input.files[0];
        
        if (file) {
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            const fileName = file.name.length > 30 ? file.name.substring(0, 27) + '...' : file.name;
            const fileType = file.type.split('/')[1].toUpperCase();
            
            preview.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="fas fa-file-alt" style="font-size: 2rem; color: var(--primary);"></i>
                        <div>
                            <strong style="display: block;">${fileName}</strong>
                            <small style="color: var(--text-light);">${fileType} • ${fileSize} MB</small>
                        </div>
                    </div>
                    <button type="button" onclick="removeFile()" style="background: var(--error); color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition);">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            preview.classList.add('active');
        }
    }
    
    function removeFile() {
        const input = document.getElementById('justificatif');
        const preview = document.getElementById('filePreview');
        
        input.value = '';
        preview.classList.remove('active');
        preview.innerHTML = '';
    }
    
    // Validation du formulaire
    document.getElementById('demandeForm').addEventListener('submit', function(e) {
        const type = document.getElementById('type').value;
        const submitBtn = document.getElementById('submitBtn');
        
        // Validation de base
        if (!type) {
            e.preventDefault();
            showError('Veuillez sélectionner un type de demande.');
            return false;
        }
        
        // Validation du fichier
        const fileInput = document.getElementById('justificatif');
        if (fileInput.files.length === 0) {
            e.preventDefault();
            showError('Veuillez joindre un justificatif.');
            return false;
        }
        
        const file = fileInput.files[0];
        const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!validTypes.includes(file.type)) {
            e.preventDefault();
            showError('Format de fichier non accepté. Veuillez utiliser PDF, JPG ou PNG.');
            return false;
        }
        
        if (file.size > maxSize) {
            e.preventDefault();
            showError('Le fichier est trop volumineux. La taille maximale est de 5MB.');
            return false;
        }
        
        // Validation des dates
        const dateInput = document.getElementById('date');
        if (new Date(dateInput.value) > new Date()) {
            e.preventDefault();
            showError('La date de l\'événement ne peut pas être dans le futur.');
            return false;
        }
        
        // Validation du montant pour les types concernés
        if (fieldConfigs[type]?.montant) {
            const montant = parseFloat(document.getElementById('montant').value);
            if (!montant || montant <= 0) {
                e.preventDefault();
                showError('Le montant est obligatoire pour ce type de demande.');
                return false;
            }
            if (montant > 10000) {
                e.preventDefault();
                showError('Le montant maximum autorisé est de 10 000 DT.');
                return false;
            }
        }
        
        // Désactiver le bouton pendant l'envoi
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        
        // Timeout de sécurité
        setTimeout(() => {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }, 15000);
        
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
        
        // Insérer après le titre
        const formContainer = document.querySelector('.form-container');
        const title = document.querySelector('.form-title');
        formContainer.insertBefore(errorDiv, title.nextSibling.nextSibling);
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
    
    // Initialiser les champs dynamiques
    document.addEventListener('DOMContentLoaded', function() {
        toggleDynamicFields();
        
        // Définir la date max pour tous les inputs date
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.max = today;
        });
        
        // Empêcher le double clic
        let isSubmitting = false;
        document.getElementById('demandeForm').addEventListener('submit', function() {
            if (isSubmitting) return false;
            isSubmitting = true;
            return true;
        });
    });
    </script>
</body>
</html>