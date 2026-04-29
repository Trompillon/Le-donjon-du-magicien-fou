<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/* =========================================
   1. RÉCUPÉRATION DES DONNÉES
========================================= */
$fightId = $_POST['fight_id'] ?? null;
$action  = $_POST['action'] ?? null;
$spellId = $_POST['spell_id'] ?? null;
$token   = $_POST['csrf_token'] ?? null;

// --- LA BARRIÈRE DE SÉCURITÉ ---
if (!$token || $token !== $_SESSION['csrf_token']) {
    die("Erreur de sécurité : Action de combat non autorisée.");
}

if (!$fightId) { 
    header("Location: game.php"); 
    exit; 
}

// Récupération du combat
$stmtFight = $pdo->prepare("SELECT * FROM fights WHERE id = ?");
$stmtFight->execute([$fightId]);
$fight = $stmtFight->fetch();

if (!$fight) {
    header("Location: game.php");
    exit;
}

// Récupération du Personnage avec son arme
$stmtChar = $pdo->prepare("
    SELECT 
        characters.*, 
        items.bonus_atk, 
        items.dice_type AS weapon_dice, 
        items.bonus_def AS armor_bonus
    FROM characters
    LEFT JOIN inventory ON characters.id = inventory.char_id
    LEFT JOIN items ON inventory.item_id = items.id
    WHERE characters.id = ?
    ORDER BY items.bonus_atk DESC 
    LIMIT 1
");
$stmtChar->execute([$fight['char_id']]);
$player = $stmtChar->fetch();

// Récupération du Monstre
$stmtM = $pdo->prepare("SELECT * FROM monsters WHERE id = ?");
$stmtM->execute([$fight['monsters_id']]);
$monster = $stmtM->fetch();

/* =========================================
   2. INITIALISATION DU TOUR
========================================= */
$log = "";
$newMonsterHp = $fight['monster_current_hp']; 
$newPlayerHp  = $fight['char_current_hp'];

// Sécurité sur les bonus
$player['bonus_atk']   = $player['bonus_atk'] ?? 0;
$player['weapon_dice'] = $player['weapon_dice'] ?? 4; 
$player['armor_bonus'] = $player['armor_bonus'] ?? 0;

/* =========================================
   3. LOGIQUE DES ACTIONS
========================================= */

if ($action === 'cast_spell' && $spellId) {
    /* -----------------------------------------
       OPTION A : MAGIE (Succès Garanti, Pas de riposte)
    ----------------------------------------- */
    $stmtS = $pdo->prepare("SELECT * FROM spells WHERE id = ?");
    $stmtS->execute([$spellId]);
    $spell = $stmtS->fetch();

    if ($spell && $player['mana_current'] >= $spell['mana_cost']) {
        // Dégâts bruts sans dé aléatoire
        $degatsMagiques = $spell['damage_base'];
        $newMonsterHp = max(0, $newMonsterHp - $degatsMagiques);
        
        // Décompte immédiat du Mana
        $newMana = max(0, $player['mana_current'] - $spell['mana_cost']);
        $pdo->prepare("UPDATE characters SET mana_current = ? WHERE id = ?")
            ->execute([$newMana, $player['id']]);

        // Mise à jour PV Monstre dans le combat
        $pdo->prepare("UPDATE fights SET monster_current_hp = ? WHERE id = ?")
            ->execute([$newMonsterHp, $fightId]);

        $log = "✨ Vous incantez *" . htmlspecialchars($spell['name']) . "* ! Le monstre subit $degatsMagiques dégâts de feu.";
    } else {
        $_SESSION['combat_log'] = "❌ Pas assez de mana !";
        header("Location: fight.php?monster_id=" . $fight['monsters_id']);
        exit;
    }

} else {
    /* -----------------------------------------
       OPTION B : ATTAQUE PHYSIQUE (Système de CA / Riposte)
    ----------------------------------------- */
    $d10 = rand(1, 10);
    $scoreAttaque = $player['attack_base'] + $player['bonus_atk'] + $d10;

    if ($scoreAttaque >= $monster['armor_class']) {
        // SUCCÈS : Le joueur touche
        $d_arme = rand(1, $player['weapon_dice']);
        $degats = ($player['attack_base'] + $d_arme) - $monster['defense_base'];
        $degats = max(1, $degats);
        
        $newMonsterHp = max(0, $newMonsterHp - $degats);
        $pdo->prepare("UPDATE fights SET monster_current_hp = ? WHERE id = ?")
            ->execute([$newMonsterHp, $fightId]);
        
        $log = "⚔️ Vous touchez ! Le monstre subit $degats dégâts.";
    } else {
        // ÉCHEC : Le monstre riposte uniquement ici
        $d_monstre = rand(1, $monster['dice_type']);
        $degatsRecus = ($monster['attack_base'] + $d_monstre) - ($player['defense_base'] + $player['armor_bonus']);
        $degatsRecus = max(1, $degatsRecus);

        $newPlayerHp = max(0, $newPlayerHp - $degatsRecus);
        $pdo->prepare("UPDATE fights SET char_current_hp = ? WHERE id = ?")
            ->execute([$newPlayerHp, $fightId]);
        
        $log = "🛡️ Échec ! Votre coup est paré et le monstre riposte : -$degatsRecus PV.";
    }
}

/* =========================================
   4. GESTION DE LA VICTOIRE OU DÉFAITE
========================================= */

// --- CAS : VICTOIRE ---
if ($newMonsterHp <= 0) {

    $pdo->prepare("UPDATE characters SET hp_current = ? WHERE id = ?")
        ->execute([$newPlayerHp, $fight['char_id']]);

    $stmtWin = $pdo->prepare("
        SELECT win_story_id FROM story_fights 
        WHERE story_id = (SELECT current_story_id FROM char_progress WHERE char_id = ?)
    ");
    $stmtWin->execute([$fight['char_id']]);
    $win = $stmtWin->fetch();

    $nextStep = $win['win_story_id'] ?? 1;

    $pdo->prepare("UPDATE char_progress SET current_story_id = ? WHERE char_id = ?")
        ->execute([$nextStep, $fight['char_id']]);

    $_SESSION['combat_log'] = $log . " 🏆 Victoire !";
    header("Location: game.php");
    exit;
}

// --- CAS : DÉFAITE ---
if ($newPlayerHp <= 0) {
    $pdo->prepare("UPDATE characters SET hp_current = 0 WHERE id = ?")
        ->execute([$fight['char_id']]);
    $_SESSION['combat_log'] = "💀 Vous avez succombé...";
    header("Location: game_over.php"); 
    exit;
}

// --- CONTINUATION DU COMBAT ---
$_SESSION['combat_log'] = $log;
header("Location: fight.php?monster_id=" . $fight['monsters_id']);
exit;