<?php

require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../db.php';

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

// --- VÉRIFICATION CSRF ---
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : Jeton invalide ou expiré.");
    }
    
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // 2. Préparer la requête pour récupérer l'utilisateur 
        // On sélectionne toutes les colonnes, mais on va surtout utiliser 'password_hash'
        $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // 3. Vérifier si l'utilisateur existe
        if ($user) {
            // 4. Vérifier si le mot de passe correspond au hash stocké 
            if (password_verify($password, $user['password'])) { // Changement ici
        
                // Connexion réussie !
                $_SESSION['user_id'] = $user['id'];
               
                // Rediriger vers une page sécurisée (ex: profil.php)
                header('Location: ' . BASE_URL . 'index.php');
                exit;

            } else {
                $erreur = "Email ou mot de passe incorrect.";
            }
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
// Fin du code de traitement PHP
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
    <title>Connexion</title>
</head>
<body>

    <?php
    if (!empty($_GET['action']) && $_GET['action'] === 'added') {
        $message_succes = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
    }
    ?>

    <?php if (!empty($erreur)) : ?>
    <p class="error"><?= $erreur ?></p>
    <?php endif; ?>

    <?php if (!empty($message_succes)) : ?>
    <p class="success"><?= $message_succes ?></p>
    <?php endif; ?>


    <div class="form-wrapper">
        <form class="formConnexion" action="<?= BASE_URL ?>connexion/connexion.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <h1>Connexion</h1>

            <label for="email">Email</label>
            <input class="formInput" type="text" name="email" id="email" required>
            <br>

            <label for="password">Mot de passe</label>
            <input class="formInput" type="password" name="password" id="password" required>
            <br>

            <br>
            <button type="submit" id="btn">Connexion</button>
            <br>
            <br>
            
            <p>
                <a href="<?= BASE_URL ?>connexion/inscription.php">Vous n'êtes pas inscrit?</a>
            </p>
        </form>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    
</body>
</html>