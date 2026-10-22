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

// GET <chemin> et renvoie le json ; accepte un chemin (/soirees/8) ou un lien hateoas complet
export async function getJson(chemin, { signal } = {}) {
    const url = chemin.startsWith('http') ? chemin : API_URL + chemin;

    let reponse;
    try {
        reponse = await fetch(url, { headers: { Accept: 'application/json' }, signal });
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
    styles: (options) => getJson('/styles', options),
    dates: (options) => getJson('/dates', options),
    pass: (options) => getJson('/pass', options),
};
