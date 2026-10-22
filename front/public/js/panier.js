// Panier local : billets de soirée et Pass 3 jours.
// Les prix sont indicatifs ; le serveur devra les recalculer à la commande.

const CLE = 'nrv-panier';
const MAX_PAR_LIGNE = 10;

function entier(n, min, max) {
    return Number.isInteger(n) && n >= min && n <= max;
}

function datesPassValides(dates) {
    return Array.isArray(dates)
        && dates.length === 3
        && new Set(dates).size === 3
        && dates.every((date) =>
            typeof date === 'string'
            && /^\d{4}-\d{2}-\d{2}$/.test(date)
        );
}

// Rejette les lignes invalides ou modifiées manuellement.
function ligneValide(ligne) {
    if (
        ligne === null
        || typeof ligne !== 'object'
        || !['NORMAL', 'REDUIT'].includes(ligne.categorie)
        || !entier(ligne.quantite, 1, MAX_PAR_LIGNE)
        || !entier(ligne.prixUnitaire, 0, 1000000)
        || typeof ligne.nom !== 'string'
    ) {
        return false;
    }

    if (ligne.type === 'SOIREE') {
        return entier(ligne.soireeId, 1, 999999999);
    }

    if (ligne.type === 'PASS') {
        return entier(ligne.passId, 1, 999999999)
            && datesPassValides(ligne.dates);
    }

    return false;
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
        // Le stockage peut être indisponible ou plein.
    }
}

export function nombreBillets() {
    return lire().reduce((total, ligne) => total + ligne.quantite, 0);
}

export function billetsPourSoiree(soireeId) {
    return lire()
        .filter((ligne) =>
            ligne.type === 'SOIREE' && ligne.soireeId === soireeId
        )
        .reduce((total, ligne) => total + ligne.quantite, 0);
}

export function billetsPourLigne(soireeId, categorie) {
    return lire()
        .filter((ligne) =>
            ligne.type === 'SOIREE'
            && ligne.soireeId === soireeId
            && ligne.categorie === categorie
        )
        .reduce((total, ligne) => total + ligne.quantite, 0);
}

// Ajoute des billets de soirée.
export function ajouterBillets(soiree, categorie, quantite, prixUnitaire) {
    if (
        quantite <= 0
        || !['NORMAL', 'REDUIT'].includes(categorie)
        || !Number.isInteger(prixUnitaire)
    ) {
        return;
    }

    const lignes = lire();
    const existante = lignes.find((ligne) =>
        ligne.type === 'SOIREE'
        && ligne.soireeId === soiree.id
        && ligne.categorie === categorie
    );

    if (existante) {
        existante.quantite = Math.min(
            MAX_PAR_LIGNE,
            existante.quantite + quantite
        );
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

// Ajoute un Pass correspondant à exactement trois dates distinctes.
export function ajouterPass(pass, dates, categorie, prixUnitaire) {
    if (
        !pass
        || !entier(pass.id, 1, 999999999)
        || Number(pass.nb_soirees) !== 3
        || !datesPassValides(dates)
        || !['NORMAL', 'REDUIT'].includes(categorie)
        || !entier(prixUnitaire, 0, 1000000)
    ) {
        return false;
    }

    const datesTriees = [...dates].sort();
    const lignes = lire();

    const existante = lignes.find((ligne) =>
        ligne.type === 'PASS'
        && ligne.passId === pass.id
        && ligne.categorie === categorie
        && ligne.dates.join('|') === datesTriees.join('|')
    );

    if (existante) {
        if (existante.quantite >= MAX_PAR_LIGNE) {
            return false;
        }
        existante.quantite += 1;
    } else {
        lignes.push({
            type: 'PASS',
            passId: pass.id,
            nom: pass.libelle,
            dates: datesTriees,
            categorie,
            quantite: 1,
            prixUnitaire,
        });
    }

    ecrire(lignes);
    return true;
}

// Une quantité à zéro supprime la ligne.
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

export function majBadge() {
    const nombre = nombreBillets();
    const pastille = document.getElementById('panier-nombre');

    if (pastille) {
        pastille.textContent = String(nombre);
    }

    document.querySelector('.badge-panier')
        ?.setAttribute(
            'aria-label',
            `Panier, ${nombre} billet${nombre > 1 ? 's' : ''}`
        );
}

window.addEventListener('storage', (e) => {
    if (e.key === CLE) {
        majBadge();
    }
});
