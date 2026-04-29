/**
 * Gère l'utilisation d'un objet (soin ou dégât) via une requête Fetch.
 * @param {number|string} itemId - L'identifiant de l'objet à utiliser.
 */
function useItem(itemId) {
    const csrfInput = document.getElementById('csrf_inventory');

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('csrf_token', csrfInput ? csrfInput.value : '');

    fetch('use_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // --- LOGIQUE DE MISE À JOUR VISUELLE ---
            if (data.type === 'damage') {
                // CAS DÉGÂTS : On met à jour le monstre
                const monsterText = document.getElementById('monster-hp-text');
                const monsterFill = document.getElementById('monster-hp-fill');
                
                if (monsterText) {
                    const parts = monsterText.textContent.split('/');
                    const maxHp = parts[1] ? parts[1].trim() : "??"; 
                    monsterText.textContent = `${data.newMonsterHp} / ${maxHp}`;
                    
                    if (monsterFill) {
                        const percent = (data.newMonsterHp / parseInt(maxHp)) * 100;
                        monsterFill.style.width = percent + '%';
                    }
                }
                console.log("Dégâts infligés au monstre !");
            } else {
                // CAS SOIN : On met à jour le HUD du joueur
                updateHUD(data);
            }
            // Dans tous les cas, on met à jour l'inventaire
            updateInventoryUI(itemId, data.remaining);
        } else {
            // Si le PHP renvoie success: false (ex: Jeton invalide)
            alert("Erreur : " + data.message);
        }
    })
    .catch(error => console.error('Erreur Fetch:', error));
}

/**
 * Met à jour l'affichage des barres de vie (HP) et de mana (PM) du joueur.
 * @param {Object} data - L'objet JSON contenant les nouvelles valeurs (newHp, newMana).
 */
function updateHUD(data) {
    // 1. Mise à jour PV
    const hpText = document.getElementById('hp-text');
    const hpFill = document.getElementById('hp-fill');
    
    if (hpText) {
        // On récupère le MAX qui est déjà écrit dans le texte (ex: "10 / 30 PV")
        const parts = hpText.textContent.split('/');
        const hpMax = parts[1] ? parts[1].trim() : "30 PV"; // On garde la partie droite
        
        // On réécrit le tout proprement
        hpText.textContent = `${data.newHp} / ${hpMax}`;
        
        if (hpFill) {
            const maxVal = parseInt(hpMax);
            hpFill.style.width = (data.newHp / maxVal * 100) + '%';
        }
    }

    // 2. Mise à jour Mana
    const manaText = document.getElementById('mana-text');
    const manaFill = document.getElementById('mana-fill');
    
    if (manaText && data.newMana !== undefined) {
        const parts = manaText.textContent.split('/');
        const manaMax = parts[1] ? parts[1].trim() : "50 PM";
        
        manaText.textContent = `${data.newMana} / ${manaMax}`;
        
        if (manaFill) {
            const maxVal = parseInt(manaMax);
            manaFill.style.width = (data.newMana / maxVal * 100) + '%';
        }
    }
}

/**
 * Met à jour la quantité d'un objet dans l'inventaire ou supprime la ligne.
 * @param {number|string} itemId - L'identifiant de l'objet.
 * @param {number} remaining - La quantité restante renvoyée par le serveur.
 */
function updateInventoryUI(itemId, remaining) {
    const itemRow = document.getElementById(`item-row-${itemId}`);
    if (itemRow) {
        const qtyVal = itemRow.querySelector('.qty-val');
        if (qtyVal) {
            const currentQty = parseInt(remaining);
            if (currentQty > 0) {
                qtyVal.innerText = currentQty;
            } else {
                itemRow.remove();
            }
        }
    }
}