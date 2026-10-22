// appels a l'api nrv.net avec fetch
// les erreurs de l'api sont au format application/problem+json (rfc 7807)

import * as session from './session.js';

const API_URL = window.NRV_CONFIG.apiUrl;

export class ErreurApi extends Error {
    constructor(statut, titre, details = null) {
        super(titre);
        this.name = 'ErreurApi';
        this.statut = statut;
        this.details = details;
    }
}

// appel generique : GET par defaut, corps json pour POST, jeton de connexion si auth = true
async function requete(chemin, { methode = 'GET', corps, auth = false, signal, headers = {} } = {}) {
    const url = chemin.startsWith('http') ? chemin : API_URL + chemin;
    const entetes = { Accept: 'application/json', ...headers };
    if (corps !== undefined) {
        entetes['Content-Type'] = 'application/json';
    }
    if (auth) {
        const jeton = session.jeton() || localStorage.getItem('nrv_token') || sessionStorage.getItem('nrv_token');
        if (!jeton) {
            throw new ErreurApi(401, 'Connexion requise');
        }
        entetes.Authorization = `Bearer ${jeton}`;
    } else {
        const jeton = localStorage.getItem('nrv_token') || sessionStorage.getItem('nrv_token');
        if (jeton) {
            entetes.Authorization = `Bearer ${jeton}`;
        }
    }

    let reponse;
    try {
        reponse = await fetch(url, {
            method: methode,
            headers: entetes,
            body: corps !== undefined ? JSON.stringify(corps) : undefined,
            signal,
        });
    } catch (e) {
        if (e.name === 'AbortError') {
            throw e;
        }
        throw new ErreurApi(0, "L'API ne répond pas. Vérifiez votre connexion puis réessayez.");
    }

    const data = await reponse.json().catch(() => null);

    if (!reponse.ok) {
        // jeton refuse par l'api : la session locale n'est plus valable
        if (reponse.status === 401 && auth) {
            session.oublier();
        }
        throw new ErreurApi(
            reponse.status,
            data?.detail ?? data?.message ?? data?.title ?? `Erreur ${reponse.status}`,
            data?.errors ?? data?.detail ?? null,
        );
    }

    return data;
}

// GET <chemin> et renvoie le json ; accepte un chemin (/soirees/8) ou un lien hateoas complet
export function getJson(chemin, options = {}) {
    return requete(chemin, options);
}

// POST <chemin> avec un corps JSON et renvoie le json
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
    styles: (options) => getJson('/styles', options),
    dates: (options) => getJson('/dates', options),
    pass: (options) => getJson('/pass', options),

    // billets (le jeton du billet suffit, pas besoin d'etre connecte)
    billet: (jeton, options) => getJson(`/billets/${encodeURIComponent(jeton)}`, options),

    // espace client : connexion requise
    commandes: (options) => getJson('/commandes', { ...options, auth: true }),
    billetsCommande: (id, options) => getJson(`/commandes/${encodeURIComponent(id)}/billets`, { ...options, auth: true }),
    mesBillets: (options) => getJson('/billets', { ...options, auth: true }),

    // backoffice : connexion admin requise
    jauges: (options) => getJson('/admin/jauges', { ...options, auth: true }),
    referentiels: (options) => getJson('/admin/referentiels', { ...options, auth: true }),
    ajouterSoiree: (soiree) => postJson('/admin/soirees', soiree, { auth: true }),
    ajouterSpectacle: (soireeId, spectacle) =>
        postJson(`/admin/soirees/${encodeURIComponent(soireeId)}/spectacles`, spectacle, { auth: true }),

    // Commandes & Tunnel d'achat
    creerCommande: (lignes, options) => postJson('/commandes', { lignes }, { ...options, auth: true }),
    commande: (id, options) => getJson(`/commandes/${encodeURIComponent(id)}`, { ...options, auth: true }),
    payerCommande: (id, donneesPaiement, options) =>
        postJson(`/commandes/${encodeURIComponent(id)}/paiement`, donneesPaiement, { ...options, auth: true }),
};
