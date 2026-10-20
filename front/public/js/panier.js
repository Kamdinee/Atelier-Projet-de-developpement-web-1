// panier : il vit dans le navigateur (localStorage) et n'est pas enregistre cote serveur
// les prix sont indicatifs, le serveur les recalcule a la commande

const CLE = 'nrv-panier';

export function lire() {
    try {
        const lignes = JSON.parse(localStorage.getItem(CLE));
        return Array.isArray(lignes) ? lignes : [];
    } catch {
        return [];
    }
}

function ecrire(lignes) {
    try {
        localStorage.setItem(CLE, JSON.stringify(lignes));
    } catch {
        // stockage plein ou bloque : le panier ne sera pas garde
    }
}

export function nombreBillets() {
    return lire().reduce((total, ligne) => total + ligne.quantite, 0);
}

// billets deja dans le panier pour une soiree (pour ne pas depasser les places restantes)
export function billetsPourSoiree(soireeId) {
    return lire()
        .filter((ligne) => ligne.type === 'SOIREE' && ligne.soireeId === soireeId)
        .reduce((total, ligne) => total + ligne.quantite, 0);
}

// ajoute n billets d'une categorie (NORMAL ou REDUIT) ; une ligne par soiree et categorie
export function ajouterBillets(soiree, categorie, quantite, prixUnitaire) {
    if (quantite <= 0) {
        return;
    }
    const lignes = lire();
    const existante = lignes.find((ligne) =>
        ligne.type === 'SOIREE' && ligne.soireeId === soiree.id && ligne.categorie === categorie);

    if (existante) {
        existante.quantite += quantite;
    } else {
        lignes.push({
            type: 'SOIREE',
            soireeId: soiree.id,
            nom: soiree.nom,
            date: soiree.date,
            heure: soiree.heure_debut,
            categorie,
            quantite,
            prixUnitaire,
        });
    }
    ecrire(lignes);
}

// pastille du nombre de billets dans l'en-tete
export function majBadge() {
    const nombre = nombreBillets();
    const pastille = document.getElementById('panier-nombre');
    if (pastille) {
        pastille.textContent = String(nombre);
    }
    document.querySelector('.badge-panier')
        ?.setAttribute('aria-label', `Panier, ${nombre} billet${nombre > 1 ? 's' : ''}`);
}

// le panier peut changer dans un autre onglet
window.addEventListener('storage', (e) => {
    if (e.key === CLE) {
        majBadge();
    }
});
