<?php

/* =========================================
   1. GESTION DE LA SESSION
========================================= */
// On lance la session en tout premier.
// Cela permet de stocker l'ID du joueur, ses stats temporaires et la sécurité.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================
   2. PARAMÈTRES GLOBAUX
========================================= */
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Le-donjon-du-magicien-fou/');
}

/* =========================================
   3. SÉCURITÉ : GÉNÉRATION DU JETON CSRF
========================================= */
// Si le joueur n'a pas encore de badge de sécurité (token), on lui en crée un unique.
// Ce badge sera valable pour toute sa durée de jeu.
if (empty($_SESSION['csrf_token'])) {
    // random_bytes(32) crée une clé aléatoire indéchiffrable.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>