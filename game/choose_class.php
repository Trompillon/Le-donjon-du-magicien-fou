<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/* =========================================
   1. RÉCUPÉRATION DES DONNÉES
========================================= */

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: " . BASE_URL . "connexion/connexion.php");
    exit;
}

// --- Récupérer le passage d'introduction ---
$stmtPassage = $pdo->prepare("SELECT * FROM story WHERE id = 1");
$stmtPassage->execute();
$passage = $stmtPassage->fetch();

if (!$passage) {
    die("Passage d'introduction introuvable !");
}

// --- Récupérer l'image associée au passage ---
$stmtImage = $pdo->prepare("SELECT * FROM images WHERE story_id = ?");
$stmtImage->execute([$passage['id']]);
$image = $stmtImage->fetch();

// --- Vérifier si un perso existe déjà et le supprimer pour reset ---
$stmtCheck = $pdo->prepare("SELECT * FROM characters WHERE user_id = ?");
$stmtCheck->execute([$userId]);
$character = $stmtCheck->fetch();

if ($character) {
    $stmtDel = $pdo->prepare("DELETE FROM characters WHERE user_id = ?");
    $stmtDel->execute([$userId]);
}

// --- Traitement du formulaire de choix de classe ---
if (isset($_POST['class'])) {

// Vérification du badge AVANT de commencer à créer le personnage
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : Jeton CSRF invalide.");
    }

    $class = $_POST['class'];

    switch($class) {
        case 'Guerrier':
            $hp_max = 50;
            $mana_max = 0;
            $attack_base = 3;
            $defense_base = 2;
            $startPassageId = 2;
            break;
        case 'Mage':
            $hp_max = 30;
            $mana_max = 50;
            $attack_base = 2;
            $defense_base = 1;
            $startPassageId = 3;
            break;
            // ajout d'autres classes par la suite
    }

    $hp_current = $hp_max;
    $mana_current = $mana_max;
    $gold_pieces = 50;
    $name = $class;

/* =========================================
   2. CREATION DU PERSONNAGE
========================================= */

    // 1. Création du perso
    $stmtInsert = $pdo->prepare("
        INSERT INTO characters
        (user_id, name, class, hp_max, hp_current, mana_max, mana_current, attack_base, defense_base, gold_pieces, is_deleted, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
    ");
    $stmtInsert->execute([$userId, $name, $class, $hp_max, $hp_current, $mana_max, $mana_current, $attack_base, $defense_base, $gold_pieces]);
    
    // 2. Récupération de l'ID du personnage créé et stockage en session
    $charId = $pdo->lastInsertId();
    $_SESSION['char_id'] = $charId; 

    // 3. Gestion des sorts pour le Mage
    if ($class === 'Mage') {
        $defaultSpells = [1, 2]; 
        $stmtSpell = $pdo->prepare("INSERT INTO character_spells (char_id, spell_id) VALUES (?, ?)");
        foreach ($defaultSpells as $spellId) {
            $stmtSpell->execute([$charId, $spellId]);
        }
    }
    
    // 4. INITIALISER LA SAUVEGARDE EN BDD
    $stmtProgress = $pdo->prepare("INSERT INTO char_progress (char_id, current_story_id, updated_at) VALUES (?, ?, NOW())");
    $stmtProgress->execute([$charId, $startPassageId]);

    // 5. DONNER LES OBJETS DE DÉPART
    // On regarde quels objets sont liés au passage de départ (ID 2 ou 3 selon la classe)
    $stmtItems = $pdo->prepare("SELECT item_id, quantity FROM story_items WHERE story_id = ?");
    $stmtItems->execute([$startPassageId]);
    $startItems = $stmtItems->fetchAll();


    $stmtitem = $pdo->prepare("INSERT INTO inventory (char_id, item_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
    foreach ($startItems as $item) {
            $stmtitem->execute([$charId, $item['item_id'], $item['quantity']]);
    }
    // --- FIN AJOUT ---

    header("Location: " . BASE_URL . "game/game.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choix de Classe</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
</head>
<body>

    <?php include __DIR__ . '/../components/header.php'; ?>

    <div id="story">
        <?php if (!empty($image)): ?>
            <div class="story-img-wrapper">
                <img src="<?= BASE_URL ?>img/<?= htmlspecialchars($image['img_url']) ?>" alt="Image du passage">
            </div>
        <?php endif; ?>

        <p><?= htmlspecialchars($passage['content']) ?></p>
    </div>

    <div id="choices">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" name="class" value="Guerrier">Je suis un(e) puissant(e) Guerrier(e) !</button>
            <button type="submit" name="class" value="Mage">Je suis un(e) Mage doté(e) de pouvoirs magiques...</button>
        </form>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>
