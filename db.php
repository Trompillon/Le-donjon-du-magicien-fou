<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=LDVH;charset=utf8", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}
?>