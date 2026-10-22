// session de l'utilisateur connecte : jeton jwt et compte, gardes dans le navigateur
// le jeton est envoye a l'api dans l'en-tete Authorization ; l'api verifie sa signature

const CLE = 'nrv-session';

// pages ou l'on peut revenir apres la connexion (evite une redirection vers un autre site)
const RETOURS = /^(index|panier|compte|mes-billets|admin|admin-programme)\.html$|^soiree\.html\?id=\d{1,9}$|^paiement\.html\?commande=[0-9a-f-]{36}$/;

// contenu du jeton (partie centrale, en base64url) ; sert a l'affichage, l'api verifie la signature
function contenu(jeton) {
    try {
        const charge = jeton.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
        return JSON.parse(atob(charge.padEnd(charge.length + ((4 - (charge.length % 4)) % 4), '=')));
    } catch {
        return null;
    }
}

// session en cours, ou null si absente, illisible ou expiree
export function lire() {
    try {
        const session = JSON.parse(localStorage.getItem(CLE));
        if (typeof session?.token !== 'string' || typeof session?.utilisateur?.prenom !== 'string') {
            return null;
        }
        if (Number(contenu(session.token)?.exp ?? 0) * 1000 <= Date.now()) {
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

// compte connecte : { id, email, nom, prenom, role } ou null
export function utilisateur() {
    return lire()?.utilisateur ?? null;
}

export function estAdmin() {
    const session = lire();
    // le role affiche suit celui du jeton (celui que l'api controle)
    return session !== null && contenu(session.token)?.role === 'ADMIN';
}

// reponse de /auth/signin ou /auth/register
export function ouvrir({ token, utilisateur: compte }) {
    try {
        localStorage.setItem(CLE, JSON.stringify({ token, utilisateur: compte }));
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

// nom utilise par les pages de l'etape 7 (jeton refuse par l'api)
export const oublier = fermer;

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

// ajoute un lien a la navigation s'il n'y est pas deja
function lienNavigation(liste, href, texte) {
    if (liste.querySelector(`a[href="${href}"]`)) {
        return;
    }
    const element = document.createElement('li');
    const lien = document.createElement('a');
    lien.className = 'nav__lien';
    lien.href = href;
    lien.textContent = texte;
    // le backoffice regroupe admin.html et admin-programme.html
    const page = window.location.pathname.split('/').pop();
    if (page === href || (href === 'admin.html' && page.startsWith('admin'))) {
        lien.setAttribute('aria-current', 'page');
    }
    element.append(lien);
    liste.querySelector('#lien-compte')?.closest('li').before(element);
}

// en-tete : "Connexion" ou "Mon compte", et "Mes billets" / "Backoffice" selon la session
export function majNavigation() {
    const lien = document.getElementById('lien-compte');
    if (!lien) {
        return;
    }
    const session = lire();
    if (session) {
        lien.textContent = 'Mon compte';
        lien.href = 'compte.html';
        lienNavigation(lien.closest('.nav__liste'), 'mes-billets.html', 'Mes billets');
        if (estAdmin()) {
            lienNavigation(lien.closest('.nav__liste'), 'admin.html', 'Backoffice');
        }
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
