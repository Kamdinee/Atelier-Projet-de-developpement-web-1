// page connexion : POST /auth/signin, puis retour a la page d'origine (?retour=...)

import { api } from './api.js';
import { brancher } from './formulaire.js';
import { majBadge } from './panier.js';
import * as session from './session.js';

const retour = session.pageDeRetour();

// deja connecte : rien a faire ici
if (session.lire()) {
    window.location.replace(retour);
}

majBadge();
document.getElementById('lien-autre').href = `inscription.html${window.location.search}`;

brancher(document.getElementById('form-compte'), async (donnees) => {
    session.ouvrir(await api.connexion(donnees));
    return retour;
}, {
    401: 'Adresse email ou mot de passe incorrect.',
});
