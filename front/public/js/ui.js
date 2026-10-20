// petits elements d'interface partages par les pages

import { afficher } from './templates.js';

let minuteurToast;

// notification en bas d'ecran (zone aria-live deja presente dans la page)
export function toast(message, { erreur = false } = {}) {
    const element = document.querySelector('.toast');
    if (!element) {
        return;
    }
    element.textContent = message;
    element.classList.toggle('toast--erreur', erreur);
    element.hidden = false;
    clearTimeout(minuteurToast);
    minuteurToast = setTimeout(() => {
        element.hidden = true;
    }, 4000);
}

// affiche une erreur de l'api avec un bouton d'action ("reessayer" par defaut)
export async function afficherErreur(element, erreur, reessayer, libelle = 'Réessayer') {
    const details = erreur.details && typeof erreur.details === 'object'
        ? Object.values(erreur.details).join(' · ')
        : erreur.details;

    await afficher(element, 'erreur', {
        titre: erreur.statut === 404 ? 'Introuvable' : 'Impossible de charger les données',
        message: erreur.message,
        details,
        reessayer: Boolean(reessayer),
        libelle,
    });

    element.querySelector('[data-action="reessayer"]')?.addEventListener('click', reessayer);
}
