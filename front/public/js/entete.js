// en-tete commun : badge du panier et lien vers le backoffice pour les administrateurs
import { majBadge } from './panier.js';
import { estAdmin } from './session.js';

majBadge();

if (estAdmin()) {
    const liste = document.querySelector('.nav__liste');
    if (liste && !liste.querySelector('a[href="admin.html"]')) {
        const page = window.location.pathname.split('/').pop();
        const element = document.createElement('li');
        const lien = document.createElement('a');
        lien.className = 'nav__lien';
        lien.href = 'admin.html';
        lien.textContent = 'Backoffice';
        if (page.startsWith('admin')) {
            lien.setAttribute('aria-current', 'page');
        }
        element.append(lien);
        // avant « Mon compte » s'il est la, sinon en fin de menu
        const compte = liste.querySelector('a[href="compte.html"]')?.closest('li');
        liste.insertBefore(element, compte ?? null);
    }
}
