<?php

define('BASE_URL', '/projetDWWM/');

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions légales - Le Donjon du Magicien Fou</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
</head>
<body>

<div class="rgpd">
<header>
    <h1>Mentions légales</h1>
</header>

<main>
    <h2>1. Éditeur du site</h2>
    <p>Le présent site <strong>Le Donjon du Magicien Fou</strong> est édité par :</p>
    <p>
        Nom : Trompillon<br>
        Statut : Projet personnel dans le cadre d’une formation<br>
        Email : <strong>contact@exemple.com</strong>
    </p>

    <h2>2. Hébergement</h2>
    <p>Le site est hébergé par :</p>
    <p>
        Nom de l’hébergeur : <br>
        Adresse : <br>
        Site web : 
    </p>

    <h2>3. Propriété intellectuelle</h2>
    <p>L’ensemble du contenu du site (textes, images, éléments graphiques, code, etc.) est la propriété exclusive de l’éditeur, sauf mention contraire.</p>
    <p>Toute reproduction, distribution, modification ou utilisation sans autorisation préalable est interdite.</p>

    <h2>4. Responsabilité</h2>
    <p>L’éditeur du site ne saurait être tenu responsable des éventuels bugs, erreurs ou interruptions de service.</p>
    <p>Le site est proposé à titre de projet pédagogique et peut évoluer à tout moment.</p>

    <h2>5. Données personnelles</h2>
    <p>Les informations concernant la collecte et le traitement des données personnelles sont détaillées dans la page :</p>
    <p>
        <a href="<?= BASE_URL ?>rgpd.php">Politique de confidentialité</a>
    </p>

    <h2>6. Contact</h2>
    <p>Pour toute question concernant le site, vous pouvez contacter l’éditeur à l’adresse suivante :</p>
    <p><strong>contact@exemple.com</strong></p>
</main>

<footer>
    © 2026 Le Donjon du Magicien Fou | 
    <a href="<?= BASE_URL ?>index.php">Accueil</a>
</footer>
</div>

</body>
</html>