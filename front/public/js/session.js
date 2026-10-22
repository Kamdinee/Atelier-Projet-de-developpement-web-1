// session de l'utilisateur : s'appuie sur auth.js (jeton enregistre a la connexion, page connexion.html)

import { deconnecter, getToken, getUtilisateur } from './auth.js';

export function jeton() {
    return getToken();
}

// jeton refuse par l'api (expire, invalide) : on ferme la session locale
export function oublier() {
    deconnecter();
}

// role et id lus dans le jeton (non verifie : sert seulement a l'affichage, l'api verifie la signature)
export function utilisateur() {
    const valeur = jeton();
    if (!valeur) {
        return null;
    }
    try {
        const charge = valeur.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
        const donnees = JSON.parse(atob(charge.padEnd(charge.length + ((4 - (charge.length % 4)) % 4), '=')));
        if (typeof donnees.exp === 'number' && donnees.exp * 1000 < Date.now()) {
            return null;
        }
        return { id: donnees.sub, role: String(donnees.role ?? getUtilisateur()?.role ?? '').toUpperCase() };
    } catch {
        return null;
    }
}

export function estAdmin() {
    return utilisateur()?.role === 'ADMIN';
}
