<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: " . BASE_URL . "connexion/connexion.php");
    exit;
}

// On cherche le dernier personnage actif de l'utilisateur
$stmt = $pdo->prepare("
    SELECT char_id FROM char_progress 
    JOIN characters ON char_progress.char_id = characters.id 
    WHERE characters.user_id = ? 
    ORDER BY char_progress.updated_at DESC LIMIT 1
");
$stmt->execute([$userId]);
$progress = $stmt->fetch();

if ($progress) {
    // On remet l'ID du perso en session !
    $_SESSION['char_id'] = $progress['char_id'];
    header("Location: " . BASE_URL . "game/game.php");
} else {
    // Si aucun personnage n'existe, on va en créer un
    header("Location: " . BASE_URL . "game/choose_class.php");
}
exit;