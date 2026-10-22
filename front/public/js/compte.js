import { getToken, getUtilisateur, deconnecter } from './auth.js';
import { majBadge } from './panier.js';

document.addEventListener('DOMContentLoaded', () => {
    majBadge();

    const messageEl        = document.getElementById('message-compte');
    const infosEl          = document.getElementById('compte-infos');
    const lienConnexionEl  = document.getElementById('compte-lien-connexion');
    const btnDeconnexion   = document.getElementById('compte-deconnexion');
    const heroNomEl        = document.getElementById('compte-hero-nom');
    const heroEmailEl      = document.getElementById('compte-hero-email');
    const initialesEl      = document.getElementById('compte-initiales');
    const actionsEl        = document.querySelector('.compte-actions');

    const afficherMessage = (texte, type = '') => {
        messageEl.textContent = texte;
        messageEl.className   = 'auth-message compte-status';
        if (type) messageEl.classList.add(`auth-message--${type}`);
    };

    const token = getToken();

    if (!token) {
        afficherMessage('Vous devez vous connecter pour accéder à votre compte.', 'erreur');
        if (infosEl)          { infosEl.hidden = true; }
        if (lienConnexionEl)  { lienConnexionEl.hidden = false; }
        if (btnDeconnexion)   { btnDeconnexion.hidden = true; }
        if (actionsEl)        { actionsEl.hidden = true; }
        if (heroNomEl)        { heroNomEl.textContent = 'Espace compte'; }
        return;
    }

    // Connecté
    if (lienConnexionEl)  lienConnexionEl.hidden = true;
    if (infosEl)          { infosEl.hidden = false; }
    if (btnDeconnexion)   { btnDeconnexion.hidden = false; }
    if (actionsEl)        { actionsEl.hidden = false; }

    const utilisateur = getUtilisateur();

    if (!utilisateur) {
        afficherMessage('Connecté, mais les informations du compte sont indisponibles.', 'erreur');
        if (heroNomEl) heroNomEl.textContent = 'Mon compte';
    } else {
        const prenom = utilisateur.prenom ?? '';
        const nom    = utilisateur.nom    ?? '';
        const email  = utilisateur.email  ?? '';

        // Hero
        if (heroNomEl)    heroNomEl.textContent  = prenom || nom ? `${prenom} ${nom}`.trim() : 'Mon compte';
        if (heroEmailEl)  heroEmailEl.textContent = email;

        // Avatar initiales
        if (initialesEl) {
            const init = [prenom[0], nom[0]].filter(Boolean).join('').toUpperCase();
            initialesEl.textContent = init || '?';
        }

        // Infos
        document.getElementById('compte-prenom').textContent = prenom || '—';
        document.getElementById('compte-nom').textContent    = nom    || '—';
        document.getElementById('compte-email').textContent  = email  || '—';

        afficherMessage('Vous êtes connecté à votre compte.', 'succes');
    }

    btnDeconnexion?.addEventListener('click', () => {
        deconnecter();
        window.location.href = 'index.html';
    });
});
