// page programme : grille des spectacles chargee par fetch et affichee avec handlebars

import { api, idDepuisLien } from './api.js';
import { afficher, partials } from './templates.js';
import { afficherErreur } from './ui.js';

const TAILLE_PAGE = 12;
const grille = document.getElementById('grille');

// format de l'api -> donnees du template carte-spectacle
function versCarte({ spectacle, links }) {
    return { ...spectacle, soireeId: idDepuisLien(links.soiree) };
}

async function chargerSpectacles() {
    grille.setAttribute('aria-busy', 'true');
    try {
        const page = await api.spectacles({ page: 1, size: TAILLE_PAGE });
        await afficher(grille, 'spectacles', { spectacles: page.spectacles.map(versCarte) });
    } catch (erreur) {
        await afficherErreur(grille, erreur, chargerSpectacles);
    } finally {
        grille.setAttribute('aria-busy', 'false');
    }
}

async function demarrer() {
    await partials('carte-spectacle');
    await afficher(grille, 'squelette', { cartes: Array.from({ length: 8 }) });
    await chargerSpectacles();
}

demarrer();
