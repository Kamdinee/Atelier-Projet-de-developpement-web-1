document.addEventListener('DOMContentLoaded', () => {
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
    const token = sessionStorage.getItem('nrv_token');

    if (!token) {
        afficherMessage(
            'Vous devez vous connecter pour accéder à votre compte.',
            'erreur'
        );

        informations.hidden = true;
        lienConnexion.hidden = false;
        boutonDeconnexion.hidden = true;

        return;
    }

    // Un token existe : afficher les informations enregistrées à la connexion.
    lienConnexion.hidden = true;
    boutonDeconnexion.hidden = false;
    informations.hidden = false;

    const utilisateurBrut = sessionStorage.getItem('nrv_utilisateur');

    if (!utilisateurBrut) {
        afficherMessage(
            'Vous êtes connecté, mais les informations du compte sont indisponibles.',
            'erreur'
        );
    } else {
        try {
            const utilisateur = JSON.parse(utilisateurBrut);

            document.querySelector('#compte-prenom').textContent =
                utilisateur.prenom ?? 'Non renseigné';

            document.querySelector('#compte-nom').textContent =
                utilisateur.nom ?? 'Non renseigné';

            document.querySelector('#compte-email').textContent =
                utilisateur.email ?? 'Non renseigné';

            afficherMessage('Vous êtes connecté à votre compte.', 'succes');
        } catch (erreur) {
            afficherMessage(
                'Les informations du compte sont illisibles. Déconnectez-vous puis reconnectez-vous.',
                'erreur'
            );
        }
    }

    // Supprimer les données de session lors de la déconnexion.
    boutonDeconnexion.addEventListener('click', () => {
        sessionStorage.removeItem('nrv_token');
        sessionStorage.removeItem('nrv_utilisateur');

        window.location.href = 'index.html';
    });
});
