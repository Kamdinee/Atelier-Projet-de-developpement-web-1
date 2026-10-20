// page programme : grille des spectacles (F1), filtres date et style (F4, F5), pagination (E1)
// les donnees viennent de l'api par fetch et sont affichees avec les templates handlebars

import { api, idDepuisLien } from './api.js';
import { dateCourte, nombre, pluriel } from './format.js';
import { majBadge } from './panier.js';
import { afficher, partials } from './templates.js';
import { afficherErreur } from './ui.js';

const TAILLE_PAGE = 12;
const CLE_RETOUR = 'nrv-retour-programme';

const formulaire = document.getElementById('filtres');
const selectDate = document.getElementById('filtre-date');
const selectStyle = document.getElementById('filtre-style');
const compteur = document.getElementById('compteur');
const resultats = document.getElementById('resultats');
const grille = document.getElementById('grille');
const pagination = document.getElementById('pagination');

// etat de la page = parametres de l'url : ?date=2027-06-11&style=metal&page=2
let etat = lireUrl();
let controleur = null;

function lireUrl() {
    const parametres = new URLSearchParams(window.location.search);
    return {
        date: parametres.get('date') ?? '',
        style: parametres.get('style') ?? '',
        page: Math.max(1, Number.parseInt(parametres.get('page'), 10) || 1),
    };
}

function queryString({ date, style, page }) {
    const parametres = new URLSearchParams();
    if (date) parametres.set('date', date);
    if (style) parametres.set('style', style);
    if (page > 1) parametres.set('page', page);
    const qs = parametres.toString();
    return qs ? '?' + qs : '';
}

// met l'url a jour sans recharger : le lien est partageable et le bouton retour marche
function ecrireUrl({ remplacer = false } = {}) {
    const url = 'index.html' + queryString(etat);
    if (remplacer) {
        history.replaceState(etat, '', url);
    } else {
        history.pushState(etat, '', url);
    }
    memoriserRetour();
}

// la page soiree s'en sert pour revenir au programme avec les memes filtres
function memoriserRetour() {
    try {
        sessionStorage.setItem(CLE_RETOUR, 'index.html' + queryString(etat));
    } catch {
        // stockage indisponible (navigation privee) : pas grave
    }
}

function synchroniserFiltres() {
    selectDate.value = etat.date;
    selectStyle.value = etat.style;
}

// format de l'api -> donnees du template carte-spectacle
function versCarte({ spectacle, links }) {
    return { ...spectacle, soireeId: idDepuisLien(links.soiree) };
}

// numeros affiches : 1 … 4 5 6 … 10
function modelePagination(page) {
    const nbPages = Math.max(1, Math.ceil(page.total / page.size));
    const lien = (numero) => ({ numero, href: 'index.html' + queryString({ ...etat, page: numero }) });

    const numeros = new Set([1, nbPages, page.page - 1, page.page, page.page + 1]);
    const pages = [];
    let precedentNumero = 0;
    [...numeros]
        .filter((n) => n >= 1 && n <= nbPages)
        .sort((a, b) => a - b)
        .forEach((numero) => {
            if (numero - precedentNumero > 1) {
                pages.push({ ellipse: true });
            }
            pages.push({ ...lien(numero), courante: numero === page.page });
            precedentNumero = numero;
        });

    return {
        plusieursPages: nbPages > 1,
        pages,
        // liens hateoas prev / next de l'api
        precedent: page.links.prev ? lien(page.page - 1) : null,
        suivant: page.links.next ? lien(page.page + 1) : null,
    };
}

async function chargerSpectacles({ focus = false } = {}) {
    // une seule requete a la fois : on annule la precedente si l'utilisateur change vite de filtre
    controleur?.abort();
    controleur = new AbortController();
    const { signal } = controleur;

    resultats.setAttribute('aria-busy', 'true');
    grille.setAttribute('aria-busy', 'true');

    try {
        const page = await api.spectacles(
            { date: etat.date, style: etat.style, page: etat.page, size: TAILLE_PAGE },
            { signal },
        );

        // page trop grande (lien ancien, filtre change) : on va a la derniere page
        if (page.count === 0 && page.total > 0 && etat.page > 1) {
            etat.page = Math.ceil(page.total / page.size);
            ecrireUrl({ remplacer: true });
            return chargerSpectacles({ focus });
        }

        await afficher(grille, 'spectacles', { spectacles: page.spectacles.map(versCarte) });
        await afficher(pagination, 'pagination', modelePagination(page));
        compteur.innerHTML = `<strong>${nombre(page.total)}</strong> ${pluriel(page.total, 'spectacle')}`;

        if (focus) {
            resultats.focus({ preventScroll: true });
            resultats.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (erreur) {
        if (erreur.name === 'AbortError') {
            return;
        }
        pagination.innerHTML = '';
        compteur.textContent = '';
        // 400 = filtre invalide dans l'url : reessayer ne servirait a rien
        if (erreur.statut === 400) {
            await afficherErreur(grille, erreur, reinitialiser, 'Voir tout le programme');
        } else {
            await afficherErreur(grille, erreur, () => chargerSpectacles());
        }
    } finally {
        if (!signal.aborted) {
            resultats.setAttribute('aria-busy', 'false');
            grille.setAttribute('aria-busy', 'false');
        }
    }
}

// remplit les listes deroulantes a partir de GET /dates et GET /styles
async function chargerFiltres() {
    try {
        const [dates, styles] = await Promise.all([api.dates(), api.styles()]);
        await afficher(selectDate, 'options', {
            libelleTous: 'Toutes les dates',
            options: dates.dates.map((date) => ({ valeur: date, libelle: dateCourte(date) })),
        });
        await afficher(selectStyle, 'options', {
            libelleTous: 'Tous les styles',
            options: styles.styles.map((style) => ({ valeur: style.slug, libelle: style.libelle })),
        });
    } catch {
        // sans les filtres la grille reste utilisable
        selectDate.disabled = true;
        selectStyle.disabled = true;
    }
    synchroniserFiltres();
}

function changerFiltres(modification) {
    etat = { ...etat, ...modification, page: 1 };
    ecrireUrl();
    chargerSpectacles();
}

function reinitialiser() {
    changerFiltres({ date: '', style: '' });
    synchroniserFiltres();
}

// ---------- evenements ----------

selectDate.addEventListener('change', () => changerFiltres({ date: selectDate.value }));
selectStyle.addEventListener('change', () => changerFiltres({ style: selectStyle.value }));

formulaire.addEventListener('submit', (e) => e.preventDefault());
formulaire.addEventListener('reset', (e) => {
    e.preventDefault();
    reinitialiser();
});

// bouton "voir tout le programme" quand il n'y a aucun resultat
grille.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="reinitialiser"]')) {
        reinitialiser();
    }
});

// clic sur un numero de page : pas de rechargement
pagination.addEventListener('click', (e) => {
    const lien = e.target.closest('a[data-page]');
    if (!lien || e.ctrlKey || e.metaKey || e.shiftKey) {
        return;
    }
    e.preventDefault();
    etat = { ...etat, page: Number(lien.dataset.page) };
    ecrireUrl();
    chargerSpectacles({ focus: true });
});

// boutons precedent / suivant du navigateur
window.addEventListener('popstate', () => {
    etat = lireUrl();
    synchroniserFiltres();
    memoriserRetour();
    chargerSpectacles();
});

// ---------- demarrage ----------

async function demarrer() {
    majBadge();
    memoriserRetour();
    await partials('carte-spectacle');
    await afficher(grille, 'squelette', { cartes: Array.from({ length: 8 }) });
    await Promise.all([chargerFiltres(), chargerSpectacles()]);
}

demarrer();
