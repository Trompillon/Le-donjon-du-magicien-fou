<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$message = $_SESSION['combat_log'] ?? "Votre aventure s'arrête ici...";
unset($_SESSION['combat_log']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Game Over</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
</head>
<body class="body-game-over">

    <div class="death-container">
        <h1 class="death-title">YOU DIED</h1>
        <p class="death-log"><?= htmlspecialchars($message) ?></p>
        
        <a href="choose_class.php" class="btn-retry">Recommencer</a>
    </div>

</body>
</html>