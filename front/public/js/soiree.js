// page soiree : detail d'une soiree (F2, F3) et ajout de billets au panier (F6, E4)
// adresse de la page : soiree.html?id=8

import { api, ErreurApi } from './api.js';
import { nombre, pluriel, prix } from './format.js';
import * as panier from './panier.js';
import { afficher, partials } from './templates.js';
import { afficherErreur, toast } from './ui.js';

const MAX_PAR_LIGNE = 10;
const CLE_RETOUR = 'nrv-retour-programme';

const contenu = document.getElementById('contenu');
let soiree = null;

// lien de retour vers le programme avec les filtres utilises avant
function lienRetour() {
    try {
        const url = sessionStorage.getItem(CLE_RETOUR);
        if (url && url.startsWith('index.html')) {
            return url;
        }
    } catch {
        // stockage indisponible
    }
    return 'index.html';
}

function idDepuisUrl() {
    const id = new URLSearchParams(window.location.search).get('id') ?? '';
    return /^[1-9]\d*$/.test(id) ? Number(id) : null;
}

// ---------- images et video ----------

// clic sur une miniature : elle remplace l'image principale du spectacle
function changerImage(bouton) {
    const spectacle = bouton.closest('.spectacle-detail');
    const image = spectacle.querySelector('.spectacle-detail__image');
    image.srcset = bouton.dataset.srcset ?? '';
    image.src = bouton.dataset.url;
    image.alt = bouton.dataset.alt;
    spectacle.querySelectorAll('.galerie__bouton').forEach((b) => {
        b.setAttribute('aria-pressed', String(b === bouton));
    });
}

// le lecteur youtube n'est charge qu'au clic (page plus legere, pas de cookies avant)
function lancerVideo(bouton) {
    const url = bouton.dataset.video;
    if (!/^https:\/\/(www\.)?(youtube\.com|youtube-nocookie\.com)\/embed\/[\w-]+/.test(url)) {
        return;
    }
    const iframe = document.createElement('iframe');
    iframe.src = url + (url.includes('?') ? '&' : '?') + 'autoplay=1';
    iframe.title = `Vidéo de ${bouton.dataset.titre}`;
    iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
    iframe.allowFullscreen = true;
    iframe.loading = 'lazy';
    bouton.replaceWith(iframe);
    iframe.focus();
}

// ---------- encart d'achat ----------

function initialiserAchat() {
    const form = document.getElementById('form-achat');
    if (!form) {
        return; // soiree complete
    }
    const champs = {
        NORMAL: form.querySelector('#qte-normal'),
        REDUIT: form.querySelector('#qte-reduit'),
    };
    const prixUnitaires = { NORMAL: soiree.tarifs.normal, REDUIT: soiree.tarifs.reduit };
    const total = form.querySelector('#achat-total');
    const limite = form.querySelector('#achat-limite');
    const ajouter = form.querySelector('#achat-ajouter');

    const valeur = (champ) => Math.max(0, Number.parseInt(champ.value, 10) || 0);

    // places encore possibles = places restantes - billets deja dans le panier
    const disponibles = () =>
        Math.max(0, soiree.places_restantes - panier.billetsPourSoiree(soiree.id));

    // 10 billets au maximum par tarif, en comptant ceux deja dans le panier
    const plafond = (categorie) =>
        Math.max(0, MAX_PAR_LIGNE - panier.billetsPourLigne(soiree.id, categorie));

    // recalcule le sous-total et bloque ce qui depasserait les places disponibles
    function maj(champModifie = null) {
        const max = disponibles();

        // on corrige d'abord les valeurs saisies au clavier
        for (const [categorie, champ] of Object.entries(champs)) {
            champ.value = Math.min(valeur(champ), plafond(categorie));
        }
        let quantite = valeur(champs.NORMAL) + valeur(champs.REDUIT);
        if (quantite > max) {
            const champ = champModifie ?? champs.NORMAL;
            champ.value = Math.max(0, valeur(champ) - (quantite - max));
            quantite = valeur(champs.NORMAL) + valeur(champs.REDUIT);
        }

        const montant = valeur(champs.NORMAL) * prixUnitaires.NORMAL + valeur(champs.REDUIT) * prixUnitaires.REDUIT;
        total.textContent = prix(montant);
        ajouter.disabled = quantite === 0;

        form.querySelectorAll('[data-pas]').forEach((bouton) => {
            const champ = form.querySelector('#' + bouton.dataset.cible);
            const categorie = champ === champs.NORMAL ? 'NORMAL' : 'REDUIT';
            bouton.disabled = Number(bouton.dataset.pas) < 0
                ? valeur(champ) === 0
                : valeur(champ) >= plafond(categorie) || quantite >= max;
        });

        // message quand on atteint la limite des places
        const dejaAuPanier = panier.billetsPourSoiree(soiree.id);
        if (max === 0) {
            limite.textContent = `Les ${nombre(soiree.places_restantes)} ${pluriel(soiree.places_restantes, 'place restante', 'places restantes')} sont déjà dans votre panier.`;
        } else if (quantite >= max && max < MAX_PAR_LIGNE * 2) {
            limite.textContent = dejaAuPanier > 0
                ? `Vous avez déjà ${dejaAuPanier} ${pluriel(dejaAuPanier, 'billet')} pour cette soirée : ${max} de plus au maximum.`
                : `Il ne reste que ${max} ${pluriel(max, 'place')} pour cette soirée.`;
        } else if (Object.keys(champs).some((c) => plafond(c) < MAX_PAR_LIGNE && valeur(champs[c]) >= plafond(c))) {
            limite.textContent = `${MAX_PAR_LIGNE} billets au maximum par tarif, en comptant ceux déjà dans votre panier.`;
        } else {
            limite.textContent = '';
        }
        limite.hidden = limite.textContent === '';
    }

    form.addEventListener('click', (e) => {
        const bouton = e.target.closest('[data-pas]');
        if (!bouton) {
            return;
        }
        const champ = form.querySelector('#' + bouton.dataset.cible);
        champ.value = valeur(champ) + Number(bouton.dataset.pas);
        maj(champ);
    });

    form.addEventListener('input', (e) => {
        if (e.target.matches('input[type="number"]')) {
            maj(e.target);
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        maj();
        const quantite = valeur(champs.NORMAL) + valeur(champs.REDUIT);
        if (quantite === 0) {
            return;
        }
        for (const [categorie, champ] of Object.entries(champs)) {
            panier.ajouterBillets(soiree, categorie, valeur(champ), prixUnitaires[categorie]);
            champ.value = 0;
        }
        panier.majBadge();
        maj();
        toast(`${quantite} ${pluriel(quantite, 'billet ajouté', 'billets ajoutés')} au panier pour « ${soiree.nom} ».`);
    });

    maj();
}

// ---------- chargement ----------

async function charger() {
    const id = idDepuisUrl();
    contenu.setAttribute('aria-busy', 'true');

    try {
        if (id === null) {
            throw new ErreurApi(404, "Cette adresse ne correspond à aucune soirée.");
        }
        const reponse = await api.soiree(id);
        soiree = reponse.soiree;

        document.title = `${soiree.nom} - Nancy Rock Vibration 2027`;
        await afficher(contenu, 'soiree', {
            ...soiree,
            retour: lienRetour(),
            maxParLigne: MAX_PAR_LIGNE,
        });
        initialiserAchat();
    } catch (erreur) {
        document.title = 'Soirée introuvable - Nancy Rock Vibration 2027';
        if (erreur.statut === 404) {
            await afficherErreur(contenu, erreur, () => { window.location.href = lienRetour(); }, 'Retour au programme');
        } else {
            await afficherErreur(contenu, erreur, charger);
        }
    } finally {
        contenu.setAttribute('aria-busy', 'false');
    }
}

contenu.addEventListener('click', (e) => {
    const miniature = e.target.closest('.galerie__bouton');
    if (miniature) {
        changerImage(miniature);
        return;
    }
    const video = e.target.closest('.video-vignette[data-video]');
    if (video) {
        lancerVideo(video);
    }
});

async function demarrer() {
    panier.majBadge();
    await partials('spectacle-detail', 'achat');
    await charger();
}

demarrer();
