<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

?>

<nav class="navbar">

    <button class="burger-menu" id="burgerMenu">
        <span>☰</span> </button>

    <ul class="nav-left" id="navLinks">
        <li class="close-menu" id="closeMenu">
            <span>&times;</span>
        </li>
        <li><a href="<?= BASE_URL ?>index.php">Accueil</a></li>

        <?php if (isset($_SESSION['user_id'])) : ?>
            <li><a href="<?= BASE_URL ?>game/resume_game.php">Continuer l'aventure</a></li>
            <li><a href="<?= BASE_URL ?>game/choose_class.php">Nouvelle aventure</a></li>
            <li><a href="<?= BASE_URL ?>connexion/deconnexion.php">Déconnexion</a></li>
        <?php else : ?>

            <li><a href="<?= BASE_URL ?>connexion/connexion.php">Se connecter</a></li>
            <li><a href="<?= BASE_URL ?>connexion/inscription.php">S'inscrire</a></li>
        <?php endif; ?>

    </ul>

    <?php if (isset($_SESSION['user_id'])) : ?>

    <div class="nav-right">
        <?php if ($character['class'] === 'Mage'): ?>
            <a href="#" class="grimoire-btn" id="btnGrimoire"  <?= strtolower($character['class']) ?>">
                <img src="<?= BASE_URL ?>img/grimoire.png" alt="Icône de Grimoire">
            </a>
        <?php endif; ?>
        <a href="#" class="inventory-btn" id="btnInventory" >
            <img src="<?= BASE_URL ?>img/backpack.png" alt="Icône sac à dos">
        </a>
    </div>

    <?php endif; ?>

</nav>

<!-- Modal inventaire -->
<div class="inventory-modal" id="inventoryModal" >
    <div class="inventory-content">
        <div id="inventoryContent">
            <!-- contenu injecté ici -->
        </div>
        <button id="closeInventory">Fermer</button>
    </div>
</div>

<!-- Modal grimoire -->
<div class="inventory-modal grimoire-modal" id="grimoireModal" >
    <div class="inventory-content">
        <div id="grimoireContent">
            <!-- contenu du grimoire injecté ici -->
        </div>
        <button id="closeGrimoire">Fermer</button>
    </div>
</div>

<script>

    // --- INVENTAIRE ---
    const btnInventory = document.getElementById('btnInventory');
    const inventoryModal = document.getElementById('inventoryModal');
    const closeInventory = document.getElementById('closeInventory');
    const inventoryContent = document.getElementById('inventoryContent');

    if (btnInventory) {
        btnInventory.addEventListener('click', () => {
            fetch('<?= BASE_URL ?>game/inventory.php')
            .then(res => res.text())
            .then(data => {
                inventoryContent.innerHTML = data;
                inventoryModal.style.display = 'flex';
        });
        });
    }

    if (closeInventory) {
        closeInventory.addEventListener('click', function() {
            inventoryModal.style.display = 'none';
        });
    }

    inventoryModal.addEventListener('click', function(e) {
        if (e.target === inventoryModal) inventoryModal.style.display = 'none';
    });

    // --- GRIMOIRE ---
    const btnGrimoire = document.getElementById('btnGrimoire');
    const grimoireModal = document.getElementById('grimoireModal');
    const closeGrimoire = document.getElementById('closeGrimoire');
    const grimoireContent = document.getElementById('grimoireContent');

    if (btnGrimoire) {
        btnGrimoire.addEventListener('click', () => {
            fetch('<?= BASE_URL ?>game/grimoire.php')
                .then(res => res.text())
                .then(data => {
                    grimoireContent.innerHTML = data;
                    grimoireModal.style.display = 'flex';
                });
        });
    }

    if (closeGrimoire) {
        closeGrimoire.addEventListener('click', function() {
            grimoireModal.style.display = 'none';
        });
    }

    grimoireModal.addEventListener('click', function(e) {
        if (e.target === grimoireModal) grimoireModal.style.display = 'none';
    });

    // --- MENU BURGER ---
    const burger = document.getElementById('burgerMenu');
    const navLinks = document.getElementById('navLinks');
    const closeBtn = document.getElementById('closeMenu');

    // OUVRIR
    if (burger) {
        burger.onclick = () => {
            navLinks.classList.add('active');
        };
    }

    // FERMER
    if (closeBtn) {
        closeBtn.onclick = () => {
            navLinks.classList.remove('active');
        };
    };

</script>