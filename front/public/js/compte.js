// page mon compte : GET /compte avec le jeton ; sans session valide, on renvoie vers la connexion

import { api } from './api.js';
import { majBadge } from './panier.js';
import * as session from './session.js';
import { afficher } from './templates.js';
import { afficherErreur } from './ui.js';

const contenu = document.getElementById('contenu-compte');
const VERS_CONNEXION = 'connexion.html?retour=compte.html';

async function charger() {
    const jeton = session.jeton();
    if (!jeton) {
        window.location.replace(VERS_CONNEXION);
        return;
    }

    try {
        const { utilisateur } = await api.compte(jeton);
        await afficher(contenu, 'compte', { ...utilisateur, admin: utilisateur.role === 'ADMIN' });
    } catch (erreur) {
        // jeton refuse par l'api (expire, compte supprime) : on se reconnecte
        if (erreur.statut === 401 || erreur.statut === 404) {
            session.fermer();
            window.location.replace(VERS_CONNEXION);
            return;
        }
        await afficherErreur(contenu, erreur, charger);
    }
    contenu.setAttribute('aria-busy', 'false');
}

contenu.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="deconnexion"]')) {
        session.fermer();
        window.location.href = 'index.html';
    }
});

majBadge();
charger();
