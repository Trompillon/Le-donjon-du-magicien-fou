<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/* =========================================
   1. SÉCURITÉ & RÉCUPÉRATION DU PERSO
========================================= */
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: " . BASE_URL . "connexion/connexion.php");
    exit;
}

$stmtChar = $pdo->prepare("SELECT * FROM characters WHERE user_id = ?");
$stmtChar->execute([$userId]);
$character = $stmtChar->fetch();

// 1. On vérifie d'abord si le perso existe
if (!$character) {
    header("Location: " . BASE_URL . "game/choose_class.php");
    exit;
}

// On ne vérifie le badge que si un formulaire a été envoyé (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité : Jeton CSRF invalide.");
    }
}

// 2. Maintenant qu'on est SÛR qu'il existe, on définit les IDs
$charId = $character['id'];
$_SESSION['char_id'] = $charId;

// 3. Et ENFIN on récupère ses sorts de type damage
$stmtSpells = $pdo->prepare("
    SELECT s.* FROM spells s
    JOIN character_spells cs ON s.id = cs.spell_id
    WHERE cs.char_id = :char_id 
    AND s.type = 'damage'
");
$stmtSpells->execute([':char_id' => $charId]);
$offenseSpells = $stmtSpells->fetchAll();

/* =========================================
   2. LOGIQUE DU COMBAT (MONSTRE)
========================================= */
// On récupère l'ID du monstre envoyé par le formulaire précédent
$monsterId = $_POST['monster_id'] ?? $_GET['monster_id'] ?? null;

if (!$monsterId) {
    // Si on arrive sur cette page sans monstre (ex: actualisation), 
    // on redirige vers la carte ou le jeu
    header("Location: " . BASE_URL . "game/game.php");
    exit;
}

// Récupération des infos du monstre
$stmtMonster = $pdo->prepare("SELECT * FROM monsters WHERE id = :id");
$stmtMonster->execute([':id' => $monsterId]);
$monster = $stmtMonster->fetch();

/* =========================================
   3. GESTION DU COMBAT (RÉCUPÉRATION OU CRÉATION)
========================================= */

// On cherche s'il y a un combat EN COURS (plus de 0 PV)
$stmtCheck = $pdo->prepare("
    SELECT id FROM fights 
    WHERE char_id = :char_id 
    AND monsters_id = :monsters_id 
    AND monster_current_hp > 0 
    LIMIT 1
");

$stmtCheck->execute([
    ':char_id' => $charId,
    ':monsters_id'    => $monsterId
]);
$existingFight = $stmtCheck->fetch();

if ($existingFight) {
    // Si on en trouve un, on récupère son ID
    $currentFightId = $existingFight['id'];
} else {
    // SINON, on en crée un nouveau avec les PV au max
    $stmtFight = $pdo->prepare("
        INSERT INTO fights (monsters_id, monster_current_hp, char_id, char_current_hp) 
        VALUES (:monsters_id, :monster_current_hp, :char_id, :char_current_hp)
    ");
    $stmtFight->execute([
        ':monsters_id'        => $monsterId,
        ':monster_current_hp' => $monster['hp_max'],
        ':char_id'            => $charId,
        ':char_current_hp'    => $character['hp_current']
    ]);
    $currentFightId = $pdo->lastInsertId();
}

// ON GARDE BIEN CE QUI SUIT : c'est ce qui permet d'afficher la vie
$stmtActiveFight = $pdo->prepare("SELECT * FROM fights WHERE id = ?");
$stmtActiveFight->execute([$currentFightId]);
$activeFight = $stmtActiveFight->fetch();

// On synchronise les PV du perso avec ceux du combat en cours pour le HUD
$character['hp_current'] = $activeFight['char_current_hp'];

/* =========================================
   4. RÉCUPÉRATION DES DONNÉES VISUELLES
========================================= */

// On récupère d'abord l'ID du passage actuel via char_progress
$stmtPos = $pdo->prepare("SELECT current_story_id FROM char_progress WHERE char_id = ?");
$stmtPos->execute([$charId]);
$progress = $stmtPos->fetch();
$currentPassageId = $progress ? $progress['current_story_id'] : 1;

// Maintenant on récupère le contenu
$stmtPassage = $pdo->prepare("SELECT * FROM story WHERE id = ?");
$stmtPassage->execute([$currentPassageId]);
$passage = $stmtPassage->fetch();

$stmtImages = $pdo->prepare("SELECT * FROM images WHERE story_id = ?");
$stmtImages->execute([$currentPassageId]);
$images = $stmtImages->fetch();

// On rafraîchit le perso mais on RE-SYNCHRONISE avec les PV du combat
$stmtChar = $pdo->prepare("SELECT * FROM characters WHERE id = ?");
$stmtChar->execute([$charId]);
$character = $stmtChar->fetch();

// On récupère le perso avec les infos de son arme équipée
$stmtChar = $pdo->prepare("
    SELECT 
        c.*, 
        i.name AS weapon_name, 
        i.dice_type AS weapon_dice
    FROM characters c
    LEFT JOIN inventory inv ON c.id = inv.char_id
    LEFT JOIN items i ON inv.item_id = i.id
    WHERE c.id = ?
    ORDER BY i.bonus_atk DESC -- On prend l'item avec le meilleur bonus (ton arme)
    LIMIT 1
");
$stmtChar->execute([$charId]);
$character = $stmtChar->fetch();

// On force les PV du HUD à être ceux de la table fights
$character['hp_current'] = $activeFight['char_current_hp'];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Combat !</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="shortcut icon" href="<?= BASE_URL ?>img/icon.png">
    <script src="../script.js" defer></script>
</head>
<body>

    <?php include __DIR__ . '/../components/header.php'; ?>

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

    <?php if (isset($_SESSION['combat_log'])): ?>
        <div class="combat-log">
            <?= $_SESSION['combat_log']; ?>
            <?php unset($_SESSION['combat_log']); ?>
        </div>
    <?php endif; ?>

    <main class="fight-container">
    <section class="story-img-wrapper">
        <img src="../img/<?= htmlspecialchars($images['img_url']) ?>" alt="Images du passage">
    </section>

    <section class="battle-arena">
        <div class="stat-box monster">
            <h3><?= htmlspecialchars($monster['name']) ?></h3>
            
            <div class="hp-bar">
                <div id="monster-hp-fill" class="hp-fill" style="width: <?= ($activeFight['monster_current_hp'] / $monster['hp_max']) * 100 ?>%;"></div>
                
                <span id="monster-hp-text"><?= $activeFight['monster_current_hp'] ?> / <?= $monster['hp_max'] ?> HP</span>
            </div>
        </div>

        <div class="fight-actions">
            <div class="main-buttons" style="display: flex; gap: 10px;">
                <button type="button" class="btn-action btn-attack" onclick="toggleAttack()" style="flex: 1;">⚔️ Attaquer</button>

                <?php if (strtolower($character['class']) === 'mage'): ?>
                    <button type="button" class="btn-action btn-spell" onclick="toggleSpells()" style="flex: 1;">✨ Magie</button>
                <?php endif; ?>
                
            </div>

            <div id="attack-list" class="spell-submenu" style="display:none;">
                <form action="process_attack.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="fight_id" value="<?= $currentFightId ?>">
                    <input type="hidden" name="action" value="attack">
                    
                    <button type="submit" class="btn-spell-choice">
                        <?= htmlspecialchars($character['weapon_name'] ?? 'Mains nues') ?> 
                        <span style="font-size: 0.8em; opacity: 0.8;">
                            (dmg: 1d<?= $character['weapon_dice'] ?? 4 ?> + <?= $character['attack_base'] ?>)
                        </span>
                    </button>
                </form>
            </div>

            <div id="spell-list" class="spell-submenu" style="display:none;">
                <?php if (!empty($offenseSpells)): ?>
                    <?php foreach ($offenseSpells as $spell): ?>
                        <form action="process_attack.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="fight_id" value="<?= $currentFightId ?>">
                            <input type="hidden" name="action" value="cast_spell">
                            <input type="hidden" name="spell_id" value="<?= $spell['id'] ?>">
                            
                            <button type="submit" class="btn-spell-choice">
                                <?= htmlspecialchars($spell['name']) ?> 
                                <span style="font-size: 0.8em; opacity: 0.8;">
                                    (cost: <?= $spell['mana_cost'] ?> PM | dmg: <?= $spell['damage_base'] ?>)
                                </span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; font-style: italic; text-align: center;">Aucun sort de combat connu...</p>
                <?php endif; ?>
            </div>
        </div>

    <script>
        function toggleAttack() {
            const attackList = document.getElementById('attack-list');
            const spellList = document.getElementById('spell-list');
                
            // On ferme le menu sort si il est ouvert
            if(spellList) spellList.style.display = 'none';
                
            attackList.style.display = (attackList.style.display === 'none') ? 'block' : 'none';
        }

        function toggleSpells() {
            const spellList = document.getElementById('spell-list');
            const attackList = document.getElementById('attack-list');
                
            // On ferme le menu attaque si il est ouvert
            if(attackList) attackList.style.display = 'none';
                
            spellList.style.display = (spellList.style.display === 'none') ? 'block' : 'none';
        }
    </script>

    </section>
    </main>

    <?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>
