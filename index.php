<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Chemin absolu pour tous les liens
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

    <?php include __DIR__ . '/components/header.php'; ?>

    <main class="banner">
        <img src="<?= BASE_URL ?>img/banner.png" alt="Bannière de représentation IA du donjon du magicien fou">
        <div class="banner-content">
            <br>
            <p>Plongez dans un donjon mystérieux où vos choix façonnent l’histoire à la manière des livres dont vous êtes le héros. Gérez votre inventaire, affrontez des créatures, et découvrez les secrets du Donjon du Magicien Fou !</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>connexion/inscription.php" class="btn-gold">S'inscrire !</a>
            <?php endif; ?>
            <br>
        </div>
    </main>

    <?php include __DIR__ . '/components/footer.php'; ?>

</body>
</html>