// session de l'utilisateur : le jeton jwt recu a la connexion est garde dans le navigateur
// (la page de connexion de l'etape 5 l'ecrit avec enregistrer() ; meme cle a garder a la fusion)

const CLE = 'nrv-jeton';

export function jeton() {
    try {
        return localStorage.getItem(CLE);
    } catch {
        return null;
    }
}

export function enregistrer(valeur) {
    try {
        localStorage.setItem(CLE, valeur);
    } catch {
        // stockage bloque : la session ne durera que le temps de la page
    }
}

export function oublier() {
    try {
        localStorage.removeItem(CLE);
    } catch {
        // rien a faire
    }
}

// contenu du jeton (non verifie : sert seulement a l'affichage, l'api verifie la signature)
export function utilisateur() {
    const valeur = jeton();
    if (!valeur) {
        return null;
    }
    try {
        const charge = valeur.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
        const donnees = JSON.parse(atob(charge.padEnd(charge.length + ((4 - (charge.length % 4)) % 4), '=')));
        if (typeof donnees.exp === 'number' && donnees.exp * 1000 < Date.now()) {
            oublier();
            return null;
        }
        return { id: donnees.sub, role: donnees.role, email: donnees.email ?? null };
    } catch {
        return null;
    }
}

export function estAdmin() {
    return utilisateur()?.role === 'ADMIN';
}
