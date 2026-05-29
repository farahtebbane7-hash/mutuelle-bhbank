<?php
echo "<h1 style='text-align:center; color:green;'>✅ Formulaire reçu avec succès !</h1>";
echo "<h3>Données POST :</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Fichiers uploadés :</h3>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

echo '<br><a href="nouvdemande.php" style="display:inline-block; padding:10px 20px; background:#002A5C; color:white; text-decoration:none; border-radius:5px;">← Retour</a>';
?>