<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

// --- VÉRIFICATION CSRF ---
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : Jeton invalide ou expiré.");
    }
    // Vérifier si tous les champs sont remplis
    if (!empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['confirmPassword'])) {
        
        $email = trim($_POST['email']);
        // On valide l'email avant d'aller plus loin
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "L'adresse email n'est pas valide.";
        } else {
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirmPassword'];

            if ($password !== $confirmPassword) {
                $erreur = "Les mots de passe ne correspondent pas.";
            } else {
                
                // 3. Hacher le mot de passe (essentiel pour la sécurité)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $date_creation = date('Y-m-d H:i:s'); // Date/heure actuelle

                try {
                    // 4. Préparer et exécuter la requête d'insertion (CORRIGÉE)
                    $stmt = $pdo->prepare("
                    INSERT INTO user (email, password, created_at) 
                    VALUES (:email, :password, :created_at)
                    ");
        
                    $stmt->execute([
                        'email' => $email,
                        'password' => $hashed_password,
                        'created_at' => $date_creation
                    ]);

                    header('Location: ' . BASE_URL . 'connexion/connexion.php?action=added');
                    exit;

                } catch (PDOException $e) {
                    die($e->getMessage());
                }
            }
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
    <title>Inscription</title>
</head>
<body>

    <?php if (!empty($erreur)) : ?>
    <p class="error"><?= $erreur ?></p>
    <?php endif; ?>

    
    <div class="form-wrapper">
        <form class="formInscription" action="<?= BASE_URL ?>connexion/inscription.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <h1>Inscription</h1>

            <label for="email">Email</label>
            <input class="formInput" type="email" name="email" id="email" required>
            <br>

            <label for="password">Mot de passe</label>
            <input class="formInput" type="password" name="password" id="password" required>
            <br>

            <label for="confirmPassword">Confirmez le Mot de passe</label>
            <input class="formInput" type="password" name="confirmPassword" id="confirmPassword" required>
            <br>

            <br>
            <button type="submit" id="btn">Inscription</button>
            <br>
            <br>

            <p>
                <a href="<?= BASE_URL ?>/rgpd.php">En créant un compte, vous acceptez notre Politique de confidentialité</a>
            </p>
        </form>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>