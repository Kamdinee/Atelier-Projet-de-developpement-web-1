// page panier : lignes du panier, modification des quantites (E2), total (F7)
// le panier est dans le navigateur ; les places restantes sont relues sur l'api

import { api } from './api.js';
import { nombre, pluriel } from './format.js';
import * as panier from './panier.js';
import { afficher } from './templates.js';
import { toast } from './ui.js';

const MAX_PAR_LIGNE = 10;
const contenu = document.getElementById('contenu-panier');

// places restantes par soiree, lues une fois au chargement
const placesRestantes = new Map();

async function chargerPlaces() {
    const ids = [...new Set(panier.lire().map((ligne) => ligne.soireeId))];
    await Promise.all(ids.map(async (id) => {
        try {
            const { soiree } = await api.soiree(id);
            placesRestantes.set(id, soiree.places_restantes);
        } catch {
            // api indisponible : on ne bloque pas l'affichage du panier
        }
    }));
}

function modele() {
    const lignes = panier.lire().map((ligne) => {
        const restantes = placesRestantes.get(ligne.soireeId);
        const dansLePanier = panier.billetsPourSoiree(ligne.soireeId);
        const complet = ligne.quantite >= MAX_PAR_LIGNE
            || (restantes !== undefined && dansLePanier >= restantes);
        let limite = '';
        if (restantes !== undefined && dansLePanier > restantes) {
            limite = `Il ne reste que ${nombre(restantes)} ${pluriel(restantes, 'place')} pour cette soirée.`;
        }
        return { ...ligne, montant: ligne.quantite * ligne.prixUnitaire, complet, limite };
    });
    return {
        lignes,
        nombreBillets: lignes.reduce((n, l) => n + l.quantite, 0),
        total: lignes.reduce((t, l) => t + l.montant, 0),
    };
}

async function rafraichir() {
    await afficher(contenu, 'panier', modele());
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
    await chargerPlaces();
    await rafraichir();
    contenu.setAttribute('aria-busy', 'false');
}

demarrer();
