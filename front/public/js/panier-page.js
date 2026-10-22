import { api } from './api.js';
import { getToken, majNavigation } from './auth.js';
import { dateCourte, nombre, pluriel, prix } from './format.js';
import * as panier from './panier.js';
import { afficher } from './templates.js';
import { toast } from './ui.js';

const MAX_PAR_LIGNE = 10;
const contenu = document.getElementById('contenu-panier');
const placesRestantes = new Map();

const formulairePass = document.getElementById('form-pass-3-jours');
const listeDatesPass = document.getElementById('pass-dates');
const compteurPass = document.getElementById('pass-compteur');
const tarifPass = document.getElementById('pass-tarif');
const prixPass = document.getElementById('pass-prix');
const messagePass = document.getElementById('pass-message');
const boutonPass = document.getElementById('pass-ajouter');

let definitionPass = null;

function afficherMessagePass(texte, type = '') {
    messagePass.textContent = texte;
    messagePass.className = 'pass-3-jours__message';

    if (type) {
        messagePass.classList.add(`pass-3-jours__message--${type}`);
    }
}

async function chargerPlaces() {
    const ids = [
        ...new Set(
            panier.lire()
                .filter((ligne) => ligne.type === 'SOIREE')
                .map((ligne) => ligne.soireeId)
        ),
    ];

    await Promise.all(ids.map(async (id) => {
        try {
            const { soiree } = await api.soiree(id);
            placesRestantes.set(id, soiree.places_restantes);
        } catch {
            // L'affichage du panier reste possible si l'API est indisponible.
        }
    }));
}

function modele() {
    const lignes = panier.lire().map((ligne) => {
        const estPass = ligne.type === 'PASS';
        const restantes = estPass ? undefined : placesRestantes.get(ligne.soireeId);
        const dansLePanier = estPass ? 0 : panier.billetsPourSoiree(ligne.soireeId);

        const complet = ligne.quantite >= MAX_PAR_LIGNE
            || (!estPass && restantes !== undefined && dansLePanier >= restantes);

        let limite = '';
        if (!estPass && restantes !== undefined && dansLePanier > restantes) {
            limite = `Il ne reste que ${nombre(restantes)} ${pluriel(restantes, 'place')} pour cette soirée.`;
        }

        return {
            ...ligne,
            estPass,
            montant: ligne.quantite * ligne.prixUnitaire,
            complet,
            limite,
        };
    });

    return {
        lignes,
        nombreBillets: lignes.reduce((total, ligne) => total + ligne.quantite, 0),
        total: lignes.reduce((total, ligne) => total + ligne.montant, 0),
    };
}

async function rafraichir() {
    await afficher(contenu, 'panier', modele());
    panier.majBadge();
}

contenu.addEventListener('click', async (e) => {
    const bouton = e.target.closest('[data-action]');

    if (!bouton) {
        return;
    }

    const index = Number(bouton.dataset.index);
    const ligne = panier.lire()[index];

    if (!['vider', 'valider'].includes(bouton.dataset.action) && !ligne) {
        return;
    }

    switch (bouton.dataset.action) {
        case 'plus':
            panier.changerQuantite(index, ligne.quantite + 1);
            break;
        case 'moins':
            panier.changerQuantite(index, ligne.quantite - 1);
            break;
        case 'supprimer':
            panier.changerQuantite(index, 0);
            toast(`« ${ligne.nom} » retiré du panier.`);
            break;
        case 'vider':
            panier.vider();
            break;
        case 'valider': {
            console.log('[Panier] Clic sur Valider la commande');
            const lignes = panier.lire();
            if (!lignes.length) {
                toast('Votre panier est vide.');
                return;
            }

            const token = getToken();
            if (!token) {
                sessionStorage.setItem('nrv_redirect', 'panier.html');
                toast('Veuillez vous connecter pour valider votre commande.');
                window.location.href = 'connexion.html?redirect=panier.html';
                return;
            }

            bouton.disabled = true;
            bouton.textContent = 'Création de la commande…';

            try {
                const payloadLignes = lignes.map((l) => ({
                    type: l.type,
                    soiree_id: l.soireeId ?? null,
                    pass_id: l.passId ?? null,
                    categorie: l.categorie,
                    quantite: l.quantite,
                    dates: l.dates ?? [],
                }));

                const res = await api.creerCommande(payloadLignes);
                const commandeId = res.commande.id;

                toast('Commande enregistrée ! Accès au paiement…');
                window.location.href = `commande.html?id=${encodeURIComponent(commandeId)}`;
            } catch (err) {
                console.error('Erreur création commande:', err);
                const conteneurErreur = document.getElementById('panier-erreur');
                let messageErreur = err.message || 'Impossible de créer la commande.';
                
                // Explication claire et bien formulée
                if (messageErreur.toLowerCase().includes('places') || messageErreur.toLowerCase().includes('complète')) {
                    messageErreur = `${messageErreur} Veuillez modifier ou supprimer cette date de votre panier pour continuer.`;
                }

                if (conteneurErreur) {
                    conteneurErreur.textContent = messageErreur;
                    conteneurErreur.style.display = 'block';
                    conteneurErreur.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    toast(messageErreur, 'erreur');
                }

                bouton.disabled = false;
                bouton.textContent = 'Valider la commande';
            }
            return;
        }
        default:
            return;
    }

    await rafraichir();
});

window.addEventListener('storage', (e) => {
    if (e.key === 'nrv-panier') {
        rafraichir();
    }
});

function actualiserEtatFormulaire() {
    if (!formulairePass) {
        return;
    }

    const selection = [
        ...formulairePass.querySelectorAll('input[name="dates-pass"]:checked'),
    ];

    const nombreRequis = Number(definitionPass?.nb_soirees) || 3;

    compteurPass.textContent =
        `${selection.length} / ${nombreRequis} dates sélectionnées`;

    // Une fois trois dates choisies, les autres cases sont désactivées.
    for (const caseDate of formulairePass.querySelectorAll('input[name="dates-pass"]')) {
        caseDate.disabled = selection.length >= nombreRequis && !caseDate.checked;
    }

    const tarif = tarifPass.value;
    const montant = tarif === 'REDUIT'
        ? definitionPass?.tarif_reduit
        : definitionPass?.tarif_normal;

    prixPass.textContent = Number.isInteger(montant) ? prix(montant) : '—';
    boutonPass.disabled = !definitionPass || selection.length !== nombreRequis;
}

async function initialiserComposerPass() {
    if (!formulairePass) {
        return;
    }

    try {
        const [reponseDates, reponsePass] = await Promise.all([
            api.dates(),
            api.pass(),
        ]);

        const dates = Array.isArray(reponseDates.dates)
            ? [...new Set(reponseDates.dates)].sort()
            : [];

        definitionPass = Array.isArray(reponsePass.pass)
            ? reponsePass.pass.find((pass) => Number(pass.nb_soirees) === 3)
            : null;

        if (!definitionPass || dates.length < 3) {
            throw new Error('Les données du Pass 3 jours sont indisponibles.');
        }

        listeDatesPass.replaceChildren();

        for (const date of dates) {
            const label = document.createElement('label');
            label.className = 'pass-date';

            const caseDate = document.createElement('input');
            caseDate.type = 'checkbox';
            caseDate.name = 'dates-pass';
            caseDate.value = date;
            caseDate.addEventListener('change', () => {
                afficherMessagePass('');
                actualiserEtatFormulaire();
            });

            const texte = document.createElement('span');
            texte.textContent = dateCourte(date);

            label.append(caseDate, texte);
            listeDatesPass.append(label);
        }

        tarifPass.addEventListener('change', actualiserEtatFormulaire);
        actualiserEtatFormulaire();
    } catch (erreur) {
        definitionPass = null;
        boutonPass.disabled = true;
        listeDatesPass.textContent = 'Impossible de charger les dates disponibles.';
        afficherMessagePass(
            erreur.message || 'Impossible de charger les Pass depuis le serveur.',
            'erreur'
        );
    }
}

formulairePass?.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!definitionPass) {
        afficherMessagePass('Les données du Pass ne sont pas disponibles.', 'erreur');
        return;
    }

    const dates = [
        ...formulairePass.querySelectorAll('input[name="dates-pass"]:checked'),
    ].map((caseDate) => caseDate.value);

    const nombreRequis = Number(definitionPass.nb_soirees);

    if (dates.length !== nombreRequis || new Set(dates).size !== nombreRequis) {
        afficherMessagePass(`Sélectionnez exactement ${nombreRequis} dates différentes.`, 'erreur');
        return;
    }

    const categorie = tarifPass.value;
    const montant = categorie === 'REDUIT'
        ? definitionPass.tarif_reduit
        : definitionPass.tarif_normal;

    const ajoute = panier.ajouterPass(
        definitionPass,
        dates,
        categorie,
        montant
    );

    if (!ajoute) {
        afficherMessagePass('Impossible d’ajouter ce Pass. Vérifiez les dates et la quantité.', 'erreur');
        return;
    }

    afficherMessagePass('Pass 3 jours ajouté à votre panier.', 'succes');
    toast('Pass 3 jours ajouté au panier.');
    await rafraichir();
});

async function demarrer() {
    majNavigation();
    panier.majBadge();
    await chargerPlaces();
    await rafraichir();
    await initialiserComposerPass();
    contenu.setAttribute('aria-busy', 'false');
}

demarrer();
