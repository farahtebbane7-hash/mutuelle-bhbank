<?php
session_start();
require_once 'config.php';
define('AUTH_MODE', 'local'); 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?error=invalid');
    exit;
}
$matemp = trim($_POST['matemp'] ?? '');
$mdp = $_POST['mdp'] ?? '';

if ($matemp === '' || $mdp === '') {
    header('Location: index.php?error=empty');
    exit;
}
$user = null;
if (AUTH_MODE === 'local') {
    $stmt = $pdo->prepare("SELECT matemp, mdp, profil FROM login WHERE matemp = ? AND mdp = ?");
    $stmt->execute([$matemp, $mdp]);
    $user = $stmt->fetch();
} elseif (AUTH_MODE === 'ldap') {
    // LDAP : intégration future
    /*
    if (authenticate_with_ldap($matemp, $mdp)) {
        // Récupérer le profil depuis la BDD (car AD ne gère pas 'profil')
        $stmt = $pdo->prepare("SELECT matemp, profil FROM login WHERE matemp = ?");
        $stmt->execute([$matemp]);
        $user = $stmt->fetch();
        if ($user) {
            $user['mdp'] = ''; // on n'a pas besoin du mot de passe en session
        }
    }
    */
}

if ($user) {
    $_SESSION['matemp'] = $user['matemp'];
    $_SESSION['profil'] = $user['profil'] ?? 'user';
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: index.php?error=invalid');
    exit;
}

// FONCTION LDAP 
/*
function authenticate_with_ldap($username, $password) {
    $ldap_host = "ldap://votre-serveur-ad.bhbank.local";
    $ldap_port = 389;
    $ldap_dn = "BHBank\\$username"; // ou "user@bhbank.local"

    $ldap_conn = ldap_connect($ldap_host, $ldap_port);
    if (!$ldap_conn) return false;

    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    $bind = @ldap_bind($ldap_conn, $ldap_dn, $password);
    ldap_close($ldap_conn);

    return $bind;
}
*/
?>