// page panier : lignes du panier, modification des quantites (E2), total (F7), composition d'un Pass 3 jours
// le panier est dans le navigateur ; les places restantes et le tarif du pass sont relus sur l'api

import { api } from './api.js';
import { nombre, pluriel, prix } from './format.js';
import * as panier from './panier.js';
import { afficher } from './templates.js';
import { toast } from './ui.js';

const MAX_PAR_LIGNE = 10;
const contenu = document.getElementById('contenu-panier');
const composer = document.getElementById('composer-pass');

// soirees du festival (GET /soirees) et formule pass (GET /pass), lues une fois au chargement
const soirees = new Map();
let formule = null;

async function chargerDonnees() {
    const [liste, pass] = await Promise.allSettled([api.soirees(), api.pass()]);
    if (liste.status === 'fulfilled') {
        liste.value.soirees.forEach(({ soiree }) => soirees.set(soiree.id, soiree));
    }
    if (pass.status === 'fulfilled') {
        formule = pass.value.pass[0] ?? null;
    }
}

// places encore libres pour une soiree, en comptant ce qui est deja dans le panier
function placesLibres(soireeId) {
    const soiree = soirees.get(soireeId);
    return soiree === undefined ? Infinity : soiree.places_restantes - panier.billetsPourSoiree(soireeId);
}

// ---------- lignes du panier ----------

function modele() {
    const lignes = panier.lire().map((ligne) => {
        const ids = ligne.type === 'PASS' ? ligne.soirees.map((s) => s.id) : [ligne.soireeId];
        const libres = Math.min(...ids.map(placesLibres));
        const depassee = ids.find((id) => placesLibres(id) < 0);
        let limite = '';
        if (depassee !== undefined) {
            const restantes = soirees.get(depassee).places_restantes;
            limite = `Il ne reste que ${nombre(restantes)} ${pluriel(restantes, 'place')} pour « ${soirees.get(depassee).nom} ».`;
        }
        return {
            ...ligne,
            pass: ligne.type === 'PASS',
            montant: ligne.quantite * ligne.prixUnitaire,
            complet: ligne.quantite >= MAX_PAR_LIGNE || libres <= 0,
            limite,
        };
    });
    return {
        lignes,
        nombreBillets: lignes.reduce((n, l) => n + l.quantite, 0),
        total: lignes.reduce((t, l) => t + l.montant, 0),
    };
}

// ---------- composition d'un pass ----------

const choix = new Set();

function modelePass() {
    return {
        ...formule,
        // prix de 3 billets normaux separes (toutes les soirees ont le meme tarif)
        separes: formule.nb_soirees * [...soirees.values()][0].tarifs.normal,
        soirees: [...soirees.values()].map((s) => ({
            ...s,
            disponible: !s.complete && placesLibres(s.id) > 0,
            choisie: choix.has(s.id),
        })),
    };
}

// compteur, soirees encore cochables, prix et bouton selon le choix en cours
function majPass() {
    const form = document.getElementById('form-pass');
    if (!form) {
        return;
    }
    const reste = formule.nb_soirees - choix.size;
    form.querySelectorAll('input[name="soiree"]').forEach((caseACocher) => {
        const soiree = soirees.get(Number(caseACocher.value));
        const disponible = !soiree.complete && placesLibres(soiree.id) > 0;
        caseACocher.disabled = !disponible || (reste === 0 && !caseACocher.checked);
    });
    const categorie = form.elements.categorie.value;
    form.querySelector('#pass-compteur').textContent = `${choix.size} / ${formule.nb_soirees}`;
    form.querySelector('#pass-total').textContent = prix(categorie === 'REDUIT' ? formule.tarifs.reduit : formule.tarifs.normal);
    form.querySelector('#pass-ajouter').disabled = reste !== 0;
    form.querySelector('#pass-aide').textContent = reste === 0
        ? 'Vos 3 soirées sont choisies.'
        : `Choisissez encore ${reste} ${pluriel(reste, 'soirée')}.`;
}

async function afficherComposer() {
    if (!formule || soirees.size === 0) {
        composer.hidden = true;
        return;
    }
    // une soiree devenue indisponible (panier modifie) est retiree du choix
    choix.forEach((id) => {
        if (placesLibres(id) <= 0) {
            choix.delete(id);
        }
    });
    await afficher(composer, 'pass', modelePass());
    majPass();
}

composer.addEventListener('change', (e) => {
    if (e.target.name === 'soiree') {
        const id = Number(e.target.value);
        if (e.target.checked) {
            choix.add(id);
        } else {
            choix.delete(id);
        }
    }
    majPass();
});

composer.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (choix.size !== formule.nb_soirees) {
        return;
    }
    const categorie = e.target.elements.categorie.value;
    panier.ajouterPass(formule, [...choix].map((id) => soirees.get(id)), categorie);
    choix.clear();
    toast(`${formule.libelle} ajouté au panier.`);
    await rafraichir();
    document.getElementById('titre-panier').focus();
});

// ---------- affichage ----------

async function rafraichir() {
    await afficher(contenu, 'panier', modele());
    await afficherComposer();
    panier.majBadge();
}

contenu.addEventListener('click', async (e) => {
    const bouton = e.target.closest('[data-action]');
    if (!bouton) {
        return;
    }
    const index = Number(bouton.dataset.index);
    const ligne = panier.lire()[index];

    switch (bouton.dataset.action) {
        case 'plus':
            panier.changerQuantite(index, ligne.quantite + 1);
            break;
        case 'moins':
            panier.changerQuantite(index, ligne.quantite - 1);
            break;
        case 'supprimer':
            panier.changerQuantite(index, 0);
            toast(`« ${ligne.nom} » retiré du panier.`);
            break;
        case 'vider':
            panier.vider();
            break;
        default:
            return;
    }
    await rafraichir();
});

// le panier peut changer dans un autre onglet
window.addEventListener('storage', (e) => {
    if (e.key === 'nrv-panier') {
        rafraichir();
    }
});

async function demarrer() {
    panier.majBadge();
    await chargerDonnees();
    await rafraichir();
    contenu.setAttribute('aria-busy', 'false');
    // lien "Composer un pass" de la page soiree : le bloc est affiche apres le chargement
    if (window.location.hash === '#titre-pass') {
        document.getElementById('titre-pass')?.scrollIntoView();
    }
}

demarrer();
