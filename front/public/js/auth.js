// Module d'authentification et de gestion de session pour le festival NRV.net

const CLE_TOKEN = 'nrv_token';
const CLE_USER = 'nrv_utilisateur';

export function getToken() {
    return localStorage.getItem(CLE_TOKEN) || sessionStorage.getItem(CLE_TOKEN);
}

export function getUtilisateur() {
    const brut = localStorage.getItem(CLE_USER) || sessionStorage.getItem(CLE_USER);
    if (!brut) return null;
    try {
        return JSON.parse(brut);
    } catch {
        return null;
    }
}

export function estConnecte() {
    return Boolean(getToken());
}

export function enregistrerSession(token, utilisateur) {
    if (token) {
        localStorage.setItem(CLE_TOKEN, token);
        sessionStorage.setItem(CLE_TOKEN, token);
    }
    if (utilisateur) {
        const chaine = typeof utilisateur === 'string' ? utilisateur : JSON.stringify(utilisateur);
        localStorage.setItem(CLE_USER, chaine);
        sessionStorage.setItem(CLE_USER, chaine);
    }
    majNavigation();
}

export function deconnecter() {
    localStorage.removeItem(CLE_TOKEN);
    localStorage.removeItem(CLE_USER);
    sessionStorage.removeItem(CLE_TOKEN);
    sessionStorage.removeItem(CLE_USER);
    majNavigation();
}

export function majNavigation() {
    const connecte = estConnecte();
    const utilisateur = getUtilisateur();

    document.querySelectorAll('[data-auth="anonyme"]').forEach((element) => {
        element.hidden = connecte;
        element.style.display = connecte ? 'none' : '';
    });

    document.querySelectorAll('[data-auth="connecte"]').forEach((element) => {
        element.hidden = !connecte;
        element.style.display = !connecte ? 'none' : '';
    });

    // Mise à jour de l'intitulé du compte avec le prénom si connecté
    const liensCompte = document.querySelectorAll('a[href="compte.html"]');
    liensCompte.forEach((lien) => {
        if (connecte && utilisateur?.prenom) {
            lien.textContent = `Mon compte (${utilisateur.prenom})`;
        } else {
            lien.textContent = 'Mon compte';
        }
    });

    // Attacher la déconnexion sur tous les boutons de déconnexion de la page
    const btnsDeconnexion = document.querySelectorAll('#nav-deconnexion, .btn-deconnexion-nav');
    btnsDeconnexion.forEach((btn) => {
        if (!btn.dataset.listener) {
            btn.dataset.listener = 'true';
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                deconnecter();
                window.location.href = 'index.html';
            });
        }
    });
}

// Synchronisation multi-onglets
if (typeof window !== 'undefined') {
    window.addEventListener('storage', (e) => {
        if (e.key === CLE_TOKEN || e.key === CLE_USER) {
            majNavigation();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', majNavigation);
    } else {
        majNavigation();
    }

    // Expose sur window pour accès direct depuis d'autres scripts
    window.NRV_AUTH = {
        getToken,
        getUtilisateur,
        estConnecte,
        enregistrerSession,
        deconnecter,
        majNavigation,
    };
}
