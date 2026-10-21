// panier : il vit dans le navigateur (localStorage) et n'est pas enregistre cote serveur
// les prix sont indicatifs, le serveur les recalcule a la commande

const CLE = 'nrv-panier';
const MAX_PAR_LIGNE = 10;

// une ligne modifiee a la main dans le navigateur est ignoree
function ligneValide(ligne) {
    const entier = (n, min, max) => Number.isInteger(n) && n >= min && n <= max;
    return ligne !== null
        && typeof ligne === 'object'
        && ligne.type === 'SOIREE'
        && ['NORMAL', 'REDUIT'].includes(ligne.categorie)
        && entier(ligne.soireeId, 1, 999999999)
        && entier(ligne.quantite, 1, MAX_PAR_LIGNE)
        && entier(ligne.prixUnitaire, 0, 1000000)
        && typeof ligne.nom === 'string';
}

export function lire() {
    try {
        const lignes = JSON.parse(localStorage.getItem(CLE));
        return Array.isArray(lignes) ? lignes.filter(ligneValide) : [];
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

// billets deja dans la ligne d'une soiree et d'une categorie (10 au maximum par ligne)
export function billetsPourLigne(soireeId, categorie) {
    return lire()
        .filter((ligne) => ligne.soireeId === soireeId && ligne.categorie === categorie)
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
        existante.quantite = Math.min(MAX_PAR_LIGNE, existante.quantite + quantite);
    } else {
        lignes.push({
            type: 'SOIREE',
            soireeId: soiree.id,
            nom: soiree.nom,
            date: soiree.date,
            heure: soiree.heure_debut,
            categorie,
            quantite: Math.min(MAX_PAR_LIGNE, quantite),
            prixUnitaire,
        });
    }
    ecrire(lignes);
}

// change la quantite d'une ligne ; 0 supprime la ligne
export function changerQuantite(index, quantite) {
    const lignes = lire();
    if (!lignes[index]) {
        return;
    }
    if (quantite <= 0) {
        lignes.splice(index, 1);
    } else {
        lignes[index].quantite = Math.min(MAX_PAR_LIGNE, quantite);
    }
    ecrire(lignes);
}

export function vider() {
    ecrire([]);
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
