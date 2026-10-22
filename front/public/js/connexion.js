import { estConnecte, enregistrerSession } from './auth.js';

// Si déjà connecté, rediriger directement vers la page Mon compte
if (estConnecte()) {
    window.location.replace('compte.html');
}

const API_URL = window.NRV_CONFIG.apiUrl;
const formulaire = document.querySelector('#form-connexion');
const message = document.querySelector('#message-connexion');

formulaire.addEventListener('submit', async (event) => {
    event.preventDefault();

    const bouton = formulaire.querySelector('button[type="submit"]');
    bouton.disabled = true;
    bouton.textContent = 'Connexion en cours...';
    message.textContent = '';

    try {
        const donnees = Object.fromEntries(new FormData(formulaire));

        const reponse = await fetch(`${API_URL}/auth`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(donnees),
        });

        const resultat = await reponse.json().catch(() => ({}));

        if (!reponse.ok) {
            throw new Error(
                resultat.detail ??
                resultat.message ??
                resultat.title ??
                `Erreur ${reponse.status}.`
            );
        }

        if (resultat.token || resultat.utilisateur) {
            enregistrerSession(resultat.token, resultat.utilisateur);
        }

        message.textContent = 'Connexion réussie ! Redirection...';
        message.className = 'auth-message auth-message--succes';

        window.setTimeout(() => {
            window.location.href = 'compte.html';
        }, 500);
    } catch (erreur) {
        message.textContent = erreur.message || 'Connexion impossible.';
        message.className = 'auth-message auth-message--erreur';
    } finally {
        bouton.disabled = false;
        bouton.textContent = 'Se connecter';
    }
});
