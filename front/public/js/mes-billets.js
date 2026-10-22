// espace client : mes commandes (GET /commandes) et leurs billets (GET /commandes/{id}/billets)
// connexion requise : le jeton est envoye par api.js

import { api } from './api.js';
import { dateCourte } from './format.js';
import { afficher } from './templates.js';
import { afficherErreur } from './ui.js';

const zone = document.getElementById('mes-commandes');
const chargees = new Set();

function versCommande({ commande }) {
    return {
        ...commande,
        // reference courte, la meme que celle imprimee sur les billets
        reference: `NRV-${commande.id.slice(0, 8).toUpperCase()}`,
        payee: commande.statut === 'PAYEE',
        annulee: commande.statut === 'ANNULEE',
    };
}

function versBillet({ billet }) {
    const pass = billet.type === 'PASS';
    return {
        token: billet.token,
        reference: billet.reference,
        titre: pass ? 'Pass 3 jours' : billet.soirees[0]?.nom,
        categorie: billet.categorie === 'REDUIT' ? 'réduit' : 'normal',
        dates: billet.soirees.map((s) => dateCourte(s.date)).join(', '),
    };
}

// billets d'une commande, charges a la premiere ouverture
async function chargerBillets(details) {
    const id = details.dataset.commande;
    const corps = details.querySelector('.commande__corps');
    if (chargees.has(id) || !details.querySelector('.etiquette--succes')) {
        return;
    }
    chargees.add(id);
    try {
        const { billets } = await api.billetsCommande(id);
        await afficher(corps, 'billets-commande', { commande: id, billets: billets.map(versBillet) });
    } catch (erreur) {
        chargees.delete(id);
        await afficherErreur(corps, erreur, () => chargerBillets(details));
    }
}

async function charger() {
    zone.setAttribute('aria-busy', 'true');
    try {
        const { commandes } = await api.commandes();
        const modeles = commandes.map(versCommande);
        const nombreBillets = modeles.reduce((n, c) => n + c.nb_billets, 0);
        await afficher(zone, 'commandes', { commandes: modeles, nombreBillets });
        zone.querySelectorAll('details[open]').forEach(chargerBillets);
    } catch (erreur) {
        if (erreur.statut === 401) {
            await afficherErreur(zone, { ...erreur, message: 'Connectez-vous pour retrouver vos commandes et vos billets.', details: null },
                () => { window.location.href = 'compte.html'; }, 'Se connecter');
        } else {
            await afficherErreur(zone, erreur, charger);
        }
    } finally {
        zone.setAttribute('aria-busy', 'false');
    }
}

// l'evenement toggle ne remonte pas : on l'ecoute en phase de capture
zone.addEventListener('toggle', (e) => {
    if (e.target.matches('details.commande') && e.target.open) {
        chargerBillets(e.target);
    }
}, true);

charger();
