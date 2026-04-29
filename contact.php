<?php

define('BASE_URL', '/projetDWWM/');

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Le Donjon du Magicien Fou</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
</head>
<body>

<div class="rgpd">
<header>
    <h1>Contact</h1>
</header>

<main>
    <h2>Nous contacter</h2>
    <p>Une question, un bug, ou simplement l’envie de laisser un message ? Utilisez le formulaire ci-dessous.</p>

    <form method="post" action="">
        <label for="email">Votre email :</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="message">Votre message :</label><br>
        <textarea id="message" name="message" rows="6" required></textarea><br><br>

        <button type="submit">Envoyer</button>
    </form>

    <h2>Contact direct</h2>
    <p>Vous pouvez également contacter l’administrateur à l’adresse suivante :</p>
    <p><strong>contact@exemple.com</strong></p>
</main>

<footer>
    © 2026 Le Donjon du Magicien Fou | 
    <a href="<?= BASE_URL ?>index.php">Accueil</a>
</footer>
</div>

</body>
</html>