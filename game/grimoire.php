<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    exit("Accès refusé");
}

$userId = $_SESSION['user_id'];

// Récupérer le personnage du user
$stmtChar = $pdo->prepare("SELECT id FROM characters WHERE user_id = ?");
$stmtChar->execute([$userId]);
$char = $stmtChar->fetch();

if (!$char) {
    exit("Aucun personnage trouvé.");
}

$charId = $char['id'];

// var_dump($charId);
// die();

// Récupérer les sorts appris par le personnage
$sql = "
    SELECT spells.name, spells.description
    FROM character_spells
    JOIN spells ON character_spells.spell_id = spells.id
    WHERE character_spells.char_id = ?
    ORDER BY spells.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$charId]);
$spells = $stmt->fetchAll();
?>

<h3 class="grimoire-title">Grimoire</h3>

<?php if (!empty($spells)): ?>
    <?php foreach ($spells as $spell): ?>
        <div class="grimoire-item">
            <div class="grimoire-item-name"><?= htmlspecialchars($spell['name']) ?></div>
            <div class="grimoire-item-description"> <?= htmlspecialchars($spell['description']) ?></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Votre grimoire est vide.</p>
<?php endif; ?>