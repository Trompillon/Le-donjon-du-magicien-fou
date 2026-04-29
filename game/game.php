<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/* =========================================
   1. RÉCUPÉRATION DU PERSONNAGE (SÉCURITÉ)
========================================= */
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: " . BASE_URL . "connexion/connexion.php");
    exit;
}

// On récupère le perso lié à l'user
$stmtChar = $pdo->prepare("SELECT * FROM characters WHERE user_id = ?");
$stmtChar->execute([$userId]);
$character = $stmtChar->fetch();

if (!$character) {
    header("Location: " . BASE_URL . "game/choose_class.php");
    exit;
}

$charId = $character['id'];
$_SESSION['char_id'] = $charId; // On synchronise la session

/* =========================================
   2. TRAITEMENT DU CHOIX (POST)
========================================= */
$error='';

if (isset($_POST['choice_id'])) {

// Vérification du badge AVANT de permettre les choix
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : Jeton CSRF invalide.");
    }

    $choiceId = $_POST['choice_id'];

    $stmt = $pdo->prepare("SELECT * FROM choice WHERE id = ?");
    $stmt->execute([$choiceId]);
    $choice = $stmt->fetch();

    if ($choice) {
        // 1. Vérification des conditions
        $canTake = true;
        if ($choice['required_class'] && $choice['required_class'] !== $character['class']) $canTake = false;
        if ($character['gold_pieces'] < $choice['required_gold']) { $canTake = false; $error = "Pas assez d'or !"; }
        if ($character['mana_current'] < $choice['required_mana']) { $canTake = false; $error = "Pas assez de mana !"; }

        if ($canTake) {
            // A. Mise à jour des Stats
            $newGold = max(0, $character['gold_pieces'] + $choice['gold_change']);
            $newMana = max(0, $character['mana_current'] + $choice['mana_change']);
            $newHp = max(0, $character['hp_current'] + $choice['hp_change']);

            $stmtUpdate = $pdo->prepare("UPDATE characters SET gold_pieces = ?, mana_current = ?, hp_current = ? WHERE id = ?");
            $stmtUpdate->execute([$newGold, $newMana, $newHp, $charId]);

            // B. GESTION DES OBJETS (Achat ET Automatique)
            // On regarde si le passage de destination donne des objets
            $nextPassageId = $choice['to_story_id'];
            
            $stmtItems = $pdo->prepare("SELECT item_id, quantity FROM story_items WHERE story_id = ?");
            $stmtItems->execute([$nextPassageId]);
            $itemsToGive = $stmtItems->fetchAll();

            foreach ($itemsToGive as $item) {
                $itemId = $item['item_id'];
                $qtyToAdd = $item['quantity'];

                // On récupère le type pour savoir si on cumule (consommable) ou pas
                $stmtItemInfo = $pdo->prepare("SELECT item_type FROM items WHERE id = ?");
                $stmtItemInfo->execute([$itemId]);
                $itemInfo = $stmtItemInfo->fetch();

                if ($itemInfo) {
                    // On regarde si l'objet est déjà dans l'inventaire
                    $stmtCheck = $pdo->prepare("SELECT id, quantity FROM inventory WHERE char_id = ? AND item_id = ?");
                    $stmtCheck->execute([$charId, $itemId]);
                    $existing = $stmtCheck->fetch();

                    if (!$existing) {
                        // Pas encore dans l'inventaire -> Insertion
                        $pdo->prepare("INSERT INTO inventory (char_id, item_id, quantity, created_at) VALUES (?, ?, ?, NOW())")
                            ->execute([$charId, $itemId, $qtyToAdd]);
                    } else {
                        // Déjà présent : on cumule seulement si c'est un consommable (type 3)
                        if ((int)$itemInfo['item_type'] === 3) {
                            $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?")
                                ->execute([$qtyToAdd, $existing['id']]);
                        }
                    }
                }
            }

            // C. Mise à jour de la Position
            $stmtSave = $pdo->prepare("UPDATE char_progress SET current_story_id = ?, updated_at = NOW() WHERE char_id = ?");
            $stmtSave->execute([$nextPassageId, $charId]);

            // D. REDIRECTION (Crucial pour éviter le bug du refresh)
            header("Location: " . BASE_URL . "game/game.php");
            exit;
        }
    }
}

/* =========================================
   3. LOGIQUE D'AFFICHAGE ET OBJETS
========================================= */

// A. Récupérer la position actuelle
$stmtPos = $pdo->prepare("SELECT current_story_id FROM char_progress WHERE char_id = ?");
$stmtPos->execute([$charId]);
$progress = $stmtPos->fetch();
$currentPassageId = $progress ? $progress['current_story_id'] : 1;

// --- AJOUT : DÉTECTION DU COMBAT ---
$stmtFight = $pdo->prepare("SELECT monsters_id FROM story_fights WHERE story_id = ?");
$stmtFight->execute([$currentPassageId]);
$encounter = $stmtFight->fetch();

$isFighting = false;
if ($encounter) {
    $isFighting = true;
    // On garde l'ID du monstre pour le bouton plus bas
    $monsterId = $encounter['monsters_id'];
}
// --- FIN AJOUT ---

// B. Récupérer les données finales pour la page
$stmtPassage = $pdo->prepare("SELECT * FROM story WHERE id = ?");
$stmtPassage->execute([$currentPassageId]);
$passage = $stmtPassage->fetch();

$stmtChoices = $pdo->prepare("SELECT * FROM choice WHERE from_story_id = ?");
$stmtChoices->execute([$currentPassageId]);
$choices = $stmtChoices->fetchAll();

$stmtImages = $pdo->prepare("SELECT * FROM images WHERE story_id = ?");
$stmtImages->execute([$currentPassageId]);
$images = $stmtImages->fetch();

// On rafraîchit $character pour avoir les stats à jour dans le HUD
$stmtChar = $pdo->prepare("SELECT * FROM characters WHERE id = ?");
$stmtChar->execute([$charId]);
$character = $stmtChar->fetch();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
    <script src="../script.js" defer></script>
    <title>Game</title>
</head>
<body>

    <?php include __DIR__ . '/../components/header.php'; ?>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($character): ?>
        <div id="hud">
            <div class="bar health">
                <div id="hp-fill" class="fill" style="width: <?= ($character['hp_current'] / $character['hp_max']) * 100 ?>%;"></div>
                <span id="hp-text"><?= $character['hp_current'] ?> / <?= $character['hp_max'] ?> PV</span>
            </div>

            <?php if ($character['class'] === 'Mage'): ?>
                <div class="bar mana">
                    <div id="mana-fill" class="fill" style="width: <?= ($character['mana_current'] / $character['mana_max']) * 100 ?>%;"></div>
                    <span id="mana-text"><?= $character['mana_current'] ?> / <?= $character['mana_max'] ?> PM</span>
                </div>
            <?php endif; ?>

            <div class="gold">💰 <span id="gold-amount"><?= $character['gold_pieces'] ?></span></div>
        </div>
    <?php endif; ?>

    <div id="story">
        <?php if ($images): ?>
            <div class="story-img-wrapper">
                <img src="../img/<?= htmlspecialchars($images['img_url']) ?>" alt="Images du passage">
            </div>
        <?php endif; ?>

        <?php
            $text = $passage['content'];
            // Tags PNJ
            $text = preg_replace('/\[PNJF\](.*?)\[\/PNJF\]/s', '<span class="npc-friendly">$1</span>', $text);
            $text = preg_replace('/\[PNJE\](.*?)\[\/PNJE\]/s', '<span class="npc-enemy">$1</span>', $text);
            $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE);
            $text = str_replace(['&lt;span class=&quot;npc-friendly&quot;&gt;', '&lt;span class=&quot;npc-enemy&quot;&gt;', '&lt;/span&gt;'], 
                                ['<span class="npc-friendly">','<span class="npc-enemy">','</span>'], $text);
            echo nl2br($text);
        ?>
    </div>

    <div id="choices">
        <?php if ($isFighting): ?>
            <div class="combat-encounter">
                <form action="fight.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="monster_id" value="<?= $monsterId ?>">
                    <button type="submit" class="btn-fight">
                        ⚔️ Combattre
                    </button>
                </form>
            </div>

        <?php else: ?>
            <?php foreach ($choices as $choice): ?>
                <?php if ($choice['required_class'] && $choice['required_class'] !== $character['class']) continue; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="choice_id" value="<?= $choice['id'] ?>">
                    <button type="submit" class="choice <?= $choice['required_class'] ? strtolower($choice['required_class']) : '' ?>">
                        <?= htmlspecialchars($choice['choice']) ?>
                    </button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    
</body>
</html>