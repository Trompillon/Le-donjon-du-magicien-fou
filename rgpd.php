<?php

define('BASE_URL', '/projetDWWM/');

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
    <title>Le Donjon du Magicien Fou</title>
</head>
<body>

<div class="rgpd">
<header>
    <h1>Politique de confidentialité</h1>
</header>

<main>
    <h2>1. Collecte des données</h2>
    <p>Lors de la création d’un compte sur le site, certaines données personnelles sont collectées, notamment :</p>
    <ul>
        <li>Pseudonyme</li>
        <li>Adresse email</li>
        <li>Mot de passe (stocké de manière sécurisée)</li>
    </ul>
    <p>Ces informations sont nécessaires pour permettre l’accès aux fonctionnalités réservées aux utilisateurs.</p>

    <h2>2. Utilisation des données</h2>
    <p>Les données sont utilisées uniquement pour :</p>
    <ul>
        <li>Gérer la création et l’authentification des comptes</li>
        <li>Conserver la progression dans le jeu</li>
        <li>Assurer le bon fonctionnement général du site</li>
    </ul>
    <p>Aucune donnée n’est vendue, louée ou partagée avec des tiers.</p>

    <h2>3. Conservation des données</h2>
    <p>Les informations personnelles sont conservées uniquement tant que le compte est actif. L’utilisateur peut demander à tout moment la suppression de ses données et de son compte.</p>

    <h2>4. Sécurité</h2>
    <p>Le site prend des mesures pour protéger vos données, notamment le stockage sécurisé des mots de passe et des informations sensibles.</p>

    <h2>5. Vos droits</h2>
    <p>Conformément au RGPD, vous disposez d’un droit d’accès, de rectification et de suppression de vos données personnelles.</p>
    <p>Pour exercer ces droits, vous pouvez contacter l’administrateur du site à l’adresse suivante :</p>
    <p><strong>contact@exemple.com</strong></p>
</main>

<footer>
    © 2026 Le Donjon du Magicien Fou | <a href="<?= BASE_URL ?>/index.php">Accueil</a>
</footer>
</div>

</body>
</html>