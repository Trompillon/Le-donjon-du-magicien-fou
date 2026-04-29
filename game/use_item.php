<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// --- SÉCURITÉ CSRF POUR AJAX ---
$token = $_POST['csrf_token'] ?? null;

if (!$token || $token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Erreur de sécurité (CSRF)']);
    exit;
}

// Vérification de la session et de l'item
if (!isset($_SESSION['user_id']) || !isset($_POST['item_id'])) {
    echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
    exit;
}

$userId = $_SESSION['user_id'];
$itemId = intval($_POST['item_id']);

// 1. Récupérer le perso et l'item (AJOUT de damage_on_use ici)
$stmt = $pdo->prepare("
    SELECT 
        characters.id, 
        characters.hp_current, 
        characters.hp_max, 
        characters.mana_current, 
        characters.mana_max, 
        items.heal_hp, 
        items.heal_mana, 
        items.damage_on_use,
        inventory.quantity 
    FROM characters
    JOIN inventory ON characters.id = inventory.char_id
    JOIN items ON inventory.item_id = items.id
    WHERE characters.user_id = ? AND items.id = ?
");

$stmt->execute([$userId, $itemId]);
$data = $stmt->fetch();

if (!$data || $data['quantity'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Objet introuvable']);
    exit;
}

$charId = $data['id'];

// --- LOGIQUE DE L'OBJET ---

$pdo->beginTransaction();
try {
    // CAS A : C'est un objet de DÉGÂTS
    if ($data['damage_on_use'] > 0) {
        // On cherche le combat en cours
        $stmtFight = $pdo->prepare("SELECT id, monster_current_hp FROM fights WHERE char_id = ? AND monster_current_hp > 0 LIMIT 1");
        $stmtFight->execute([$charId]);
        $fight = $stmtFight->fetch();

        if (!$fight) {
            throw new Exception("Aucun monstre à attaquer !");
        }

        $newMonsterHp = max(0, $fight['monster_current_hp'] - $data['damage_on_use']);
        
        // Update du monstre
        $updateMonster = $pdo->prepare("UPDATE fights SET monster_current_hp = ? WHERE id = ?");
        $updateMonster->execute([$newMonsterHp, $fight['id']]);

        $response = [
            'success' => true,
            'type' => 'damage',
            'newMonsterHp' => $newMonsterHp,
            'remaining' => $data['quantity'] - 1
        ];

    } 
    // CAS B : C'est un objet de SOIN
    else {
        $newHp = min($data['hp_max'], $data['hp_current'] + $data['heal_hp']);
        $newMana = min($data['mana_max'], $data['mana_current'] + $data['heal_mana']);

        $updateChar = $pdo->prepare("UPDATE characters SET hp_current = ?, mana_current = ? WHERE id = ?");
        $updateChar->execute([$newHp, $newMana, $charId]);

        $response = [
            'success' => true,
            'type' => 'heal',
            'newHp' => $newHp,
            'newMana' => $newMana,
            'remaining' => $data['quantity'] - 1
        ];
    }

    // MISE À JOUR COMMUNE : L'inventaire
    if ($data['quantity'] > 1) {
        $updateInv = $pdo->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE char_id = ? AND item_id = ?");
        $updateInv->execute([$charId, $itemId]);
    } else {
        $deleteInv = $pdo->prepare("DELETE FROM inventory WHERE char_id = ? AND item_id = ?");
        $deleteInv->execute([$charId, $itemId]);
    }

    $pdo->commit();
    echo json_encode($response);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}