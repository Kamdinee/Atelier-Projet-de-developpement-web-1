// backoffice (F11) : jauge, places vendues et recette de chaque soiree (GET /admin/jauges, role admin)

import { api } from './api.js';
import { afficher } from './templates.js';
import { afficherErreur } from './ui.js';

const zone = document.getElementById('jauges');
const majTexte = document.getElementById('admin-maj');
const actualiser = document.getElementById('actualiser');

const LIBELLES = {
    date: 'date',
    places_vendues: 'places vendues',
    taux_remplissage: 'taux de remplissage',
    recette: 'recette',
};

let donnees = null;
// tri courant : par date croissante au depart, les autres colonnes en decroissant
let tri = { colonne: 'date', croissant: true };

function trier(soirees) {
    const { colonne, croissant } = tri;
    return [...soirees].sort((a, b) => {
        const ecart = a[colonne] < b[colonne] ? -1 : a[colonne] > b[colonne] ? 1 : 0;
        return croissant ? ecart : -ecart;
    });
}

async function rendre() {
    const sens = tri.croissant ? 'ascending' : 'descending';
    await afficher(zone, 'jauges', {
        synthese: donnees.synthese,
        soirees: trier(donnees.soirees.map(({ soiree }) => soiree)),
        tri: Object.fromEntries(Object.keys(LIBELLES).map((c) => [c, c === tri.colonne ? sens : 'none'])),
        triLibelle: `${LIBELLES[tri.colonne]} (${tri.croissant ? 'croissant' : 'décroissant'})`,
    });
}

async function charger() {
    zone.setAttribute('aria-busy', 'true');
    actualiser.disabled = true;
    try {
        donnees = await api.jauges();
        await rendre();
        const heure = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        majTexte.textContent = `Données à jour à ${heure}.`;
    } catch (erreur) {
        if (erreur.statut === 401) {
            await afficherErreur(zone, { ...erreur, message: 'Connectez-vous avec un compte administrateur.', details: null },
                () => { window.location.href = 'compte.html'; }, 'Se connecter');
        } else if (erreur.statut === 403) {
            await afficherErreur(zone, { ...erreur, message: 'Cette page est réservée aux administrateurs du festival.', details: null },
                () => { window.location.href = 'index.html'; }, 'Retour au programme');
        } else {
            await afficherErreur(zone, erreur, charger);
        }
    } finally {
        zone.setAttribute('aria-busy', 'false');
        actualiser.disabled = false;
    }
}

zone.addEventListener('click', async (e) => {
    const bouton = e.target.closest('[data-tri]');
    if (!bouton || !donnees) {
        return;
    }
    const colonne = bouton.dataset.tri;
    tri = colonne === tri.colonne
        ? { colonne, croissant: !tri.croissant }
        : { colonne, croissant: colonne === 'date' };
    await rendre();
    zone.querySelector(`[data-tri="${colonne}"]`)?.focus();
});

actualiser.addEventListener('click', charger);

charger();
