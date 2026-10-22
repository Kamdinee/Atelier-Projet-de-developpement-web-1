import { getToken, getUtilisateur, deconnecter } from './auth.js';
import { majBadge } from './panier.js';

document.addEventListener('DOMContentLoaded', () => {
    majBadge();

    const message = document.querySelector('#message-compte');
    const informations = document.querySelector('#compte-infos');
    const lienConnexion = document.querySelector('#compte-lien-connexion');
    const boutonDeconnexion = document.querySelector('#compte-deconnexion');

    const afficherMessage = (texte, type = '') => {
        message.textContent = texte;
        message.className = 'auth-message';

        if (type) {
            message.classList.add(`auth-message--${type}`);
        }
    };

    // Vérifier si un token JWT est enregistré.
    const token = getToken();

    if (!token) {
        afficherMessage(
            'Vous devez vous connecter pour accéder à votre compte.',
            'erreur'
        );

        informations.hidden = true;
        informations.style.display = 'none';

        lienConnexion.hidden = false;
        lienConnexion.style.display = '';

        boutonDeconnexion.hidden = true;
        boutonDeconnexion.style.display = 'none';

        return;
    }

    // Un token existe : afficher les informations enregistrées à la connexion.
    lienConnexion.hidden = true;
    lienConnexion.style.display = 'none';

    boutonDeconnexion.hidden = false;
    boutonDeconnexion.style.display = '';

    informations.hidden = false;
    informations.style.display = '';

    const utilisateur = getUtilisateur();

    if (!utilisateur) {
        afficherMessage(
            'Vous êtes connecté, mais les informations du compte sont indisponibles.',
            'erreur'
        );
    } else {
        document.querySelector('#compte-prenom').textContent =
            utilisateur.prenom ?? 'Non renseigné';

        document.querySelector('#compte-nom').textContent =
            utilisateur.nom ?? 'Non renseigné';

        document.querySelector('#compte-email').textContent =
            utilisateur.email ?? 'Non renseigné';

        afficherMessage('Vous êtes connecté à votre compte.', 'succes');
    }

    // Supprimer les données de session lors de la déconnexion.
    boutonDeconnexion.addEventListener('click', () => {
        deconnecter();
        window.location.href = 'index.html';
    });
});
