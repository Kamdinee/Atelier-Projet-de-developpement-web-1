const API_URL = window.NRV_CONFIG?.apiUrl
    ?? `${window.location.protocol}//${window.location.hostname}:8080`;

const formulaireConnexion = document.querySelector('#form-connexion');
const formulaireInscription = document.querySelector('#form-inscription');

function afficherMessage(elementId, message, type = 'erreur') {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.className = `auth-message auth-message--${type}`;
}

async function envoyerRequete(chemin, donnees) {
    const reponse = await fetch(`${API_URL}${chemin}`, {
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
            resultat.detail
            ?? resultat.message
            ?? resultat.title
            ?? `Une erreur est survenue (${reponse.status}).`
        );
    }

    return resultat;
}

// Connexion
formulaireConnexion.addEventListener('submit', async (event) => {
    event.preventDefault();

    const bouton = formulaireConnexion.querySelector('button[type="submit"]');
    const donnees = Object.fromEntries(new FormData(formulaireConnexion));

    bouton.disabled = true;
    bouton.textContent = 'Connexion...';

    try {
        const resultat = await envoyerRequete('/auth', donnees);

        if (resultat.token) {
            sessionStorage.setItem('nrv_token', resultat.token);
        }

        if (resultat.utilisateur) {
            sessionStorage.setItem(
                'nrv_utilisateur',
                JSON.stringify(resultat.utilisateur)
            );
        }

        afficherMessage(
            'message-connexion',
            'Connexion réussie ! Redirection...',
            'succes'
        );

        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
    } catch (erreur) {
        afficherMessage(
            'message-connexion',
            erreur.message || "Impossible de se connecter."
        );
    } finally {
        bouton.disabled = false;
        bouton.textContent = 'Se connecter';
    }
});

// Inscription
formulaireInscription.addEventListener('submit', async (event) => {
    event.preventDefault();

    const bouton = formulaireInscription.querySelector('button[type="submit"]');
    const donnees = Object.fromEntries(new FormData(formulaireInscription));

    bouton.disabled = true;
    bouton.textContent = 'Création du compte...';

    try {
        await envoyerRequete('/inscription', donnees);

        afficherMessage(
            'message-inscription',
            'Compte créé avec succès ! Tu peux maintenant te connecter.',
            'succes'
        );

        formulaireInscription.reset();

        // Préremplit l'email du formulaire de connexion.
        formulaireConnexion.elements.email.value = donnees.email;
        formulaireConnexion.elements.mot_de_passe.focus();
    } catch (erreur) {
        afficherMessage(
            'message-inscription',
            erreur.message || "Impossible de créer le compte."
        );
    } finally {
        bouton.disabled = false;
        bouton.textContent = 'Créer mon compte';
    }
});
