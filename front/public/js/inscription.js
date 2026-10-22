import { estConnecte } from './auth.js';

// Si déjà connecté, rediriger vers la page Mon compte
if (estConnecte()) {
    window.location.replace('compte.html');
}

const API_URL = window.NRV_CONFIG.apiUrl;
const formulaire = document.querySelector('#form-inscription');
const message = document.querySelector('#message-inscription');

formulaire.addEventListener('submit', async (event) => {
    event.preventDefault();

    const bouton = formulaire.querySelector('button[type="submit"]');
    const donnees = Object.fromEntries(new FormData(formulaire));

    bouton.disabled = true;
    bouton.textContent = 'Création en cours...';
    message.textContent = '';

    try {
        const reponse = await fetch(`${API_URL}/inscription`, {
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

        message.textContent =
            'Compte créé avec succès ! Tu peux maintenant te connecter.';
        message.className = 'auth-message auth-message--succes';

        formulaire.reset();

        window.setTimeout(() => {
            window.location.href = 'connexion.html';
        }, 1200);
    } catch (erreur) {
        message.textContent = erreur.message || 'Inscription impossible.';
        message.className = 'auth-message auth-message--erreur';
    } finally {
        bouton.disabled = false;
        bouton.textContent = 'Créer mon compte';
    }
});
