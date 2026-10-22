// page inscription : POST /auth/register (l'api connecte directement le nouveau compte)

import { api } from './api.js';
import { brancher } from './formulaire.js';
import { majBadge } from './panier.js';
import * as session from './session.js';

const retour = session.pageDeRetour();

if (session.lire()) {
    window.location.replace(retour);
}

majBadge();
session.majNavigation();
document.getElementById('lien-autre').href = `connexion.html${window.location.search}`;

brancher(document.getElementById('form-compte'), async (donnees) => {
    session.ouvrir(await api.inscription(donnees));
    return retour;
}, {
    409: 'Un compte existe déjà avec cette adresse email. Connectez-vous.',
});
