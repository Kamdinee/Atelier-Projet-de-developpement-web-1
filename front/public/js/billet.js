// page billet (F10) : billet.html?token=<jeton> (un billet, page publique)
// ou billet.html?commande=<id> (tous les billets d'une de mes commandes, connexion requise)

import { api } from './api.js';
import { svgQr } from './qr.js';
import { afficher } from './templates.js';
import { afficherErreur } from './ui.js';

const contenu = document.getElementById('contenu');
const parametres = new URLSearchParams(window.location.search);

// adresse publique d'un billet : c'est elle que le qr code encode
function lienBillet(jeton) {
    const url = new URL('billet.html', window.location.href);
    url.search = new URLSearchParams({ token: jeton }).toString();
    return url.href;
}

function versModele({ billet }) {
    const lien = lienBillet(billet.token);
    const categorie = billet.categorie === 'REDUIT' ? 'réduit' : 'normal';
    const pass = billet.type === 'PASS';
    return {
        ...billet,
        titre: pass ? 'Pass 3 jours' : billet.soirees[0]?.nom,
        categorieLibelle: pass ? `Pass · ${categorie}` : categorie.charAt(0).toUpperCase() + categorie.slice(1),
        lien,
        lienCourt: lien.replace(/^https?:\/\//, ''),
        qr: svgQr(lien, `QR code du billet ${billet.reference}`),
    };
}

async function charger() {
    contenu.setAttribute('aria-busy', 'true');
    const jeton = parametres.get('token');
    const commande = parametres.get('commande');

    try {
        let billets;
        if (jeton) {
            billets = [await api.billet(jeton)];
        } else if (commande) {
            billets = (await api.billetsCommande(commande)).billets;
        } else {
            throw Object.assign(new Error('Aucun billet demandé : ouvrez le lien reçu après le paiement.'), { statut: 404 });
        }

        const modeles = billets.map(versModele);
        document.title = `${modeles.length > 1 ? 'Vos billets' : modeles[0].reference} - Nancy Rock Vibration 2027`;
        await afficher(contenu, 'billets', {
            titre: modeles.length > 1 ? 'Vos billets' : 'Votre billet',
            billets: modeles,
            plusieurs: modeles.length > 1,
        });
    } catch (erreur) {
        document.title = 'Billet introuvable - Nancy Rock Vibration 2027';
        if (erreur.statut === 401) {
            await afficherErreur(contenu, { ...erreur, message: 'Connectez-vous pour voir les billets de cette commande.' },
                () => { window.location.href = 'connexion.html'; }, 'Se connecter');
        } else if (erreur.statut === 404 || erreur.statut === 400) {
            await afficherErreur(contenu, { ...erreur, statut: 404, message: 'Ce billet est introuvable : vérifiez le lien reçu après le paiement.', details: null },
                () => { window.location.href = 'mes-billets.html'; }, 'Mes billets');
        } else {
            await afficherErreur(contenu, erreur, charger);
        }
    } finally {
        contenu.setAttribute('aria-busy', 'false');
    }
}

contenu.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="imprimer"]')) {
        window.print();
    }
});

charger();
