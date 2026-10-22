// appels a l'api nrv.net avec fetch
// les erreurs de l'api sont au format application/problem+json (rfc 7807)

const API_URL = window.NRV_CONFIG.apiUrl;

export class ErreurApi extends Error {
    constructor(statut, titre, details = null) {
        super(titre);
        this.name = 'ErreurApi';
        this.statut = statut;
        this.details = details;
    }
}

// appel de l'api : accepte un chemin (/soirees/8) ou un lien hateoas complet
// corps : objet envoye en json (POST) ; jeton : jwt de l'utilisateur connecte
async function requete(chemin, { methode = 'GET', corps, jeton, signal } = {}) {
    const url = chemin.startsWith('http') ? chemin : API_URL + chemin;
    const headers = { Accept: 'application/json' };
    if (corps !== undefined) {
        headers['Content-Type'] = 'application/json';
    }
    if (jeton) {
        headers.Authorization = `Bearer ${jeton}`;
    }

    let reponse;
    try {
        reponse = await fetch(url, {
            method: methode,
            headers,
            body: corps === undefined ? undefined : JSON.stringify(corps),
            signal,
        });
    } catch (e) {
        // requete annulee volontairement : on laisse remonter tel quel
        if (e.name === 'AbortError') {
            throw e;
        }
        throw new ErreurApi(0, "L'API ne répond pas. Vérifiez votre connexion puis réessayez.");
    }

    const data = await reponse.json().catch(() => null);

    if (!reponse.ok) {
        throw new ErreurApi(
            reponse.status,
            data?.title ?? `Erreur ${reponse.status}`,
            data?.errors ?? data?.detail ?? null,
        );
    }

    return data;
}

export function getJson(chemin, options = {}) {
    return requete(chemin, options);
}

export function postJson(chemin, corps, options = {}) {
    return requete(chemin, { ...options, methode: 'POST', corps });
}

// id a la fin d'un lien hateoas : { href: "/soirees/4" } -> 4
export function idDepuisLien(lien) {
    const morceaux = (lien?.href ?? '').split('?')[0].split('/');
    return Number.parseInt(morceaux[morceaux.length - 1], 10) || null;
}

// raccourcis des routes utilisees par le front
export const api = {
    spectacles: (parametres, options) => {
        const query = new URLSearchParams();
        for (const [cle, valeur] of Object.entries(parametres)) {
            if (valeur !== null && valeur !== undefined && valeur !== '') {
                query.set(cle, valeur);
            }
        }
        const qs = query.toString();
        return getJson('/spectacles' + (qs ? '?' + qs : ''), options);
    },
    soiree: (id, options) => getJson(`/soirees/${encodeURIComponent(id)}`, options),
    soirees: (options) => getJson('/soirees', options),
    styles: (options) => getJson('/styles', options),
    dates: (options) => getJson('/dates', options),
    pass: (options) => getJson('/pass', options),

    // comptes
    inscription: (donnees) => postJson('/auth/register', donnees),
    connexion: (donnees) => postJson('/auth/signin', donnees),
    compte: (jeton) => getJson('/compte', { jeton }),
};
