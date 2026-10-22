// panier : il vit dans le navigateur (localStorage) et n'est pas enregistre cote serveur
// les prix sont indicatifs, le serveur les recalcule a la commande

const CLE = 'nrv-panier';
const MAX_PAR_LIGNE = 10;

// une ligne modifiee a la main dans le navigateur est ignoree
const entier = (n, min, max) => Number.isInteger(n) && n >= min && n <= max;

function soireePassValide(soiree) {
    return soiree !== null && typeof soiree === 'object'
        && entier(soiree.id, 1, 999999999)
        && typeof soiree.nom === 'string'
        && typeof soiree.date === 'string';
}

function ligneValide(ligne) {
    if (ligne === null || typeof ligne !== 'object'
        || !['NORMAL', 'REDUIT'].includes(ligne.categorie)
        || !entier(ligne.quantite, 1, MAX_PAR_LIGNE)
        || !entier(ligne.prixUnitaire, 0, 1000000)
        || typeof ligne.nom !== 'string') {
        return false;
    }
    if (ligne.type === 'SOIREE') {
        return entier(ligne.soireeId, 1, 999999999);
    }
    // pass 3 jours : 3 soirees distinctes
    return ligne.type === 'PASS'
        && entier(ligne.passId, 1, 999999999)
        && Array.isArray(ligne.soirees)
        && ligne.soirees.length === 3
        && ligne.soirees.every(soireePassValide)
        && new Set(ligne.soirees.map((s) => s.id)).size === 3;
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

// places deja prises dans le panier pour une soiree (billets + pass qui la contiennent)
export function billetsPourSoiree(soireeId) {
    return lire()
        .filter((ligne) => (ligne.type === 'SOIREE' && ligne.soireeId === soireeId)
            || (ligne.type === 'PASS' && ligne.soirees.some((s) => s.id === soireeId)))
        .reduce((total, ligne) => total + ligne.quantite, 0);
}

// billets deja dans la ligne d'une soiree et d'une categorie (10 au maximum par ligne)
export function billetsPourLigne(soireeId, categorie) {
    return lire()
        .filter((ligne) => ligne.type === 'SOIREE' && ligne.soireeId === soireeId && ligne.categorie === categorie)
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

// ajoute un pass sur 3 soirees ; meme pass, memes soirees, meme tarif = meme ligne
export function ajouterPass(pass, soirees, categorie) {
    const choisies = [...soirees]
        .sort((a, b) => a.date.localeCompare(b.date))
        .map(({ id, nom, date }) => ({ id, nom, date }));
    const cle = choisies.map((s) => s.id).join('-');
    const lignes = lire();
    const existante = lignes.find((ligne) => ligne.type === 'PASS' && ligne.passId === pass.id
        && ligne.categorie === categorie && ligne.soirees.map((s) => s.id).join('-') === cle);

    if (existante) {
        existante.quantite = Math.min(MAX_PAR_LIGNE, existante.quantite + 1);
    } else {
        lignes.push({
            type: 'PASS',
            passId: pass.id,
            nom: pass.libelle,
            soirees: choisies,
            categorie,
            quantite: 1,
            prixUnitaire: categorie === 'REDUIT' ? pass.tarifs.reduit : pass.tarifs.normal,
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
