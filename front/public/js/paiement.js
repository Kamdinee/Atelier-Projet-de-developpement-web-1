// page paiement (F9) : paiement.html?commande=<id>
// recapitulatif de la commande, carte bancaire (paiement simule), puis confirmation avec les billets

import { api } from './api.js';
import { dateCourte } from './format.js';
import { brancher } from './formulaire.js';
import * as panier from './panier.js';
import * as session from './session.js';
import { afficher } from './templates.js';
import { afficherErreur } from './ui.js';

const contenu = document.getElementById('contenu-paiement');
const etapes = document.getElementById('etapes');
const commandeId = new URLSearchParams(window.location.search).get('commande') ?? '';

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

// etape "Billets" cochee une fois la commande payee
function etapeBillets() {
    etapes.querySelectorAll('li').forEach((li) => {
        li.className = 'etapes__faite';
        li.removeAttribute('aria-current');
    });
    etapes.lastElementChild.className = '';
    etapes.lastElementChild.setAttribute('aria-current', 'step');
}

async function confirmer(commande, billets) {
    etapeBillets();
    await afficher(contenu, 'confirmation', {
        ...commande,
        prenom: session.utilisateur()?.prenom ?? '',
        billets: billets.map(versBillet),
    });
    document.title = 'Commande confirmée - Nancy Rock Vibration 2027';
    document.getElementById('titre-confirmation').focus();
}

// message clair pour chaque refus du paiement
function messageErreur(erreur, commande) {
    if (erreur.statut === 402) {
        return 'Paiement refusé par la banque. Aucune somme n\'a été débitée : essayez avec une autre carte.';
    }
    if (erreur.statut === 422) {
        return 'Carte refusée : vérifiez le numéro et la date d\'expiration.';
    }
    if (erreur.statut === 409 && erreur.details?.soirees) {
        const id = erreur.details.soirees[0].soiree_id;
        const nom = commande.lignes.flatMap((l) => l.soirees).find((s) => s.id === id)?.nom ?? 'une soirée';
        return `Il n'y a plus assez de places pour « ${nom} ». Rien n'a été débité : modifiez votre panier puis recommencez.`;
    }
    return null;
}

async function charger() {
    if (!session.lire()) {
        window.location.replace(session.lienConnexion());
        return;
    }
    if (!/^[0-9a-f-]{36}$/i.test(commandeId)) {
        window.location.replace('panier.html');
        return;
    }

    try {
        const { commande } = await api.commande(commandeId);
        if (commande.statut === 'PAYEE') {
            // page rechargee apres le paiement : on reaffiche la confirmation
            const { billets } = await api.billetsCommande(commandeId);
            await confirmer(commande, billets);
            return;
        }

        await afficher(contenu, 'paiement', {
            ...commande,
            lignes: commande.lignes.map((l) => ({ ...l, pass: l.type === 'PASS' })),
        });
        brancher(document.getElementById('form-paiement'), async (carte) => {
            const resultat = await api.payer(commandeId, carte);
            panier.vider();
            panier.majBadge();
            await confirmer(resultat.commande, resultat.billets);
        }, (erreur) => messageErreur(erreur, commande));
    } catch (erreur) {
        if (erreur.statut === 401) {
            window.location.replace(session.lienConnexion());
            return;
        }
        await afficherErreur(contenu, erreur, erreur.statut === 404 ? null : charger);
    } finally {
        contenu.setAttribute('aria-busy', 'false');
    }
}

charger();
