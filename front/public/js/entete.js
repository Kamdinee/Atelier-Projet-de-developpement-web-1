// en-tete commun a toutes les pages : badge du panier et liens selon la session
// (Connexion ou Mon compte, Mes billets une fois connecte, Backoffice pour un administrateur)
import { majBadge } from './panier.js';
import { majNavigation } from './session.js';

majBadge();
majNavigation();
