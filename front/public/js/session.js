// session de l'utilisateur connecte : jeton jwt et compte, gardes dans le navigateur
// le jeton est envoye a l'api dans l'en-tete Authorization ; l'api verifie sa signature

const CLE = 'nrv-session';

// pages ou l'on peut revenir apres la connexion (evite une redirection vers un autre site)
const RETOURS = /^(index|panier|compte)\.html$|^soiree\.html\?id=\d{1,9}$/;

// date d'expiration lue dans le jeton (partie centrale, en base64url)
function expiration(jeton) {
    try {
        const contenu = JSON.parse(atob(jeton.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
        return Number(contenu.exp) * 1000;
    } catch {
        return 0;
    }
}

// session en cours, ou null si absente, illisible ou expiree
export function lire() {
    try {
        const session = JSON.parse(localStorage.getItem(CLE));
        if (typeof session?.token !== 'string' || typeof session?.utilisateur?.prenom !== 'string') {
            return null;
        }
        if (expiration(session.token) <= Date.now()) {
            fermer();
            return null;
        }
        return session;
    } catch {
        return null;
    }
}

export function jeton() {
    return lire()?.token ?? null;
}

// reponse de /auth/signin ou /auth/register
export function ouvrir({ token, utilisateur }) {
    try {
        localStorage.setItem(CLE, JSON.stringify({ token, utilisateur }));
    } catch {
        // stockage bloque : la connexion ne durera que le temps de la page
    }
    majNavigation();
}

export function fermer() {
    try {
        localStorage.removeItem(CLE);
    } catch {
        // rien a faire
    }
}

// page ou revenir apres la connexion : ?retour=... si elle est autorisee
export function pageDeRetour(defaut = 'compte.html') {
    const retour = new URLSearchParams(window.location.search).get('retour') ?? '';
    return RETOURS.test(retour) ? retour : defaut;
}

// lien de connexion vers la page actuelle (pour y revenir ensuite)
export function lienConnexion() {
    const ici = window.location.pathname.split('/').pop() + window.location.search;
    return RETOURS.test(ici) ? `connexion.html?retour=${encodeURIComponent(ici)}` : 'connexion.html';
}

// en-tete : "Connexion" ou "Mon compte" selon l'etat de la session
export function majNavigation() {
    const lien = document.getElementById('lien-compte');
    if (!lien) {
        return;
    }
    const session = lire();
    if (session) {
        lien.textContent = 'Mon compte';
        lien.href = 'compte.html';
    } else if (!lien.hasAttribute('aria-current')) {
        lien.textContent = 'Connexion';
        lien.href = lienConnexion();
    }
}

// connexion ou deconnexion dans un autre onglet
window.addEventListener('storage', (e) => {
    if (e.key === CLE) {
        majNavigation();
    }
});
