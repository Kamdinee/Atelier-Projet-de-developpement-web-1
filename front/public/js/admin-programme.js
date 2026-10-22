// backoffice (E3) : ajout d'une soiree avec ses 2 a 3 spectacles, ou d'un spectacle a une soiree existante
// POST /admin/soirees et POST /admin/soirees/{id}/spectacles (role admin)

import { api } from './api.js';
import { lienConnexion } from './session.js';
import { dateCourte, nombre } from './format.js';
import { afficher, template } from './templates.js';
import { afficherErreur, toast } from './ui.js';

const etat = document.getElementById('admin-etat');
const formSoiree = document.getElementById('form-soiree');
const formSpectacle = document.getElementById('form-spectacle');
const zoneSpectacles = document.getElementById('soiree-spectacles');
const selectLieu = document.getElementById('soiree-lieu');
const champJauge = document.getElementById('soiree-jauge');
const selectSoiree = document.getElementById('spectacle-soiree');
const infoSoiree = document.getElementById('spectacle-soiree-info');
const boutonTroisieme = formSoiree.querySelector('[data-action="ajouter-spectacle"]');

let styles = [];
let lieux = [];

// ---------- champs des spectacles ----------

async function champsSpectacle(index, { prefixe = `spectacles.${index}.`, legende, supprimable = false } = {}) {
    const rendu = await template('spectacle-champs');
    return rendu({
        id: `sp-${prefixe.replace(/\W/g, '') || 'seul'}`,
        prefixe,
        legende: legende ?? `Spectacle ${index + 1}`,
        styles,
        supprimable,
    });
}

async function initialiserSpectaclesSoiree() {
    zoneSpectacles.innerHTML = (await champsSpectacle(0)) + (await champsSpectacle(1));
    boutonTroisieme.hidden = false;
}

boutonTroisieme.addEventListener('click', async () => {
    zoneSpectacles.insertAdjacentHTML('beforeend', await champsSpectacle(2, { supprimable: true }));
    boutonTroisieme.hidden = true;
    zoneSpectacles.querySelector('[name="spectacles.2.titre"]').focus();
});

zoneSpectacles.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="retirer-spectacle"]')) {
        e.target.closest('fieldset').remove();
        boutonTroisieme.hidden = false;
        boutonTroisieme.focus();
    }
});

// ---------- listes : lieux, styles, soirees ----------

function majJauge() {
    const lieu = lieux.find((l) => String(l.id) === selectLieu.value);
    champJauge.max = lieu ? lieu.capacite : '';
    champJauge.placeholder = lieu ? `${nombre(lieu.capacite)} (capacité)` : 'capacité';
}

selectLieu.addEventListener('change', majJauge);

async function chargerSoirees() {
    const { soirees } = await api.jauges();
    await afficher(selectSoiree, 'options', {
        libelleTous: 'Choisir une soirée…',
        options: soirees.map(({ soiree }) => ({
            valeur: soiree.id,
            libelle: `${dateCourte(soiree.date)} · ${soiree.nom}`,
        })),
    });
    infoSoiree.textContent = '';
}

// a la selection d'une soiree, on affiche ses spectacles : une soiree en a 3 au plus
selectSoiree.addEventListener('change', async () => {
    const bouton = formSpectacle.querySelector('[type="submit"]');
    bouton.disabled = false;
    infoSoiree.textContent = '';
    if (!selectSoiree.value) {
        return;
    }
    try {
        const { soiree } = await api.soiree(selectSoiree.value);
        const liste = soiree.spectacles.map((s) => `${s.titre} (${s.horaire})`).join(', ');
        if (soiree.spectacles.length >= 3) {
            infoSoiree.textContent = `Complet : 3 spectacles au programme (${liste}).`;
            bouton.disabled = true;
        } else {
            infoSoiree.textContent = `${soiree.spectacles.length} spectacles au programme : ${liste}. Ouverture à ${soiree.heure_debut}.`;
        }
    } catch {
        infoSoiree.textContent = '';
    }
});

// ---------- lecture des formulaires ----------

const centimes = (valeur) => Math.round(Number.parseFloat(String(valeur).replace(',', '.')) * 100);

function lireSpectacle(donnees, prefixe) {
    const champ = (nom) => (donnees.get(prefixe + nom) ?? '').toString().trim();
    return {
        titre: champ('titre'),
        style: champ('style'),
        horaire: champ('horaire'),
        artistes: champ('artistes').split(',').map((a) => a.trim()).filter(Boolean),
        description: champ('description'),
        video: champ('video') || null,
        image: champ('image') || null,
    };
}

function lireSoiree() {
    const donnees = new FormData(formSoiree);
    const jauge = donnees.get('jauge').toString().trim();
    return {
        nom: donnees.get('nom').toString().trim(),
        thematique: donnees.get('thematique').toString().trim(),
        date: donnees.get('date'),
        heure_debut: donnees.get('heure_debut'),
        lieu_id: Number(donnees.get('lieu_id')),
        jauge: jauge === '' ? null : Number(jauge),
        tarif_normal: centimes(donnees.get('tarif_normal')),
        tarif_reduit: centimes(donnees.get('tarif_reduit')),
        spectacles: [...zoneSpectacles.querySelectorAll('fieldset')]
            .map((fieldset) => lireSpectacle(donnees, fieldset.dataset.prefixe)),
    };
}

// ---------- erreurs renvoyees par l'api ----------

function effacerErreurs(form) {
    form.querySelectorAll('.champ-erreur').forEach((p) => p.remove());
    form.querySelectorAll('[aria-invalid]').forEach((champ) => {
        champ.removeAttribute('aria-invalid');
        champ.removeAttribute('aria-describedby');
    });
    const message = form.querySelector('.admin-form__message');
    message.hidden = true;
    message.textContent = '';
}

function afficherErreurs(form, erreur, prefixe = '') {
    const message = form.querySelector('.admin-form__message');
    const generales = [];

    // 400 : une erreur par champ, au nom du champ (ex. spectacles.0.titre)
    if (erreur.details && typeof erreur.details === 'object') {
        for (const [cle, texte] of Object.entries(erreur.details)) {
            const champ = form.querySelector(`[name="${CSS.escape(prefixe + cle)}"]`);
            if (!champ) {
                generales.push(`${cle} : ${texte}`);
                continue;
            }
            const aide = document.createElement('p');
            aide.className = 'champ-erreur';
            aide.id = `erreur-${champ.id}`;
            // les tarifs sont saisis en euros, l'api parle en centimes
            const libelle = cle.startsWith('tarif_') ? 'montant entre 0 et 1 000 € attendu' : texte;
            aide.textContent = libelle.charAt(0).toUpperCase() + libelle.slice(1) + '.';
            champ.setAttribute('aria-invalid', 'true');
            champ.setAttribute('aria-describedby', aide.id);
            champ.closest('.champ')?.append(aide);
        }
    } else if (erreur.details) {
        generales.push(String(erreur.details));
    }

    message.textContent = generales.length > 0
        ? `${erreur.message} : ${generales.join(' · ')}`
        : `${erreur.message}. Corrigez les champs signalés.`;
    message.hidden = false;

    // le focus va sur le premier champ a corriger, sinon sur le message
    const premier = form.querySelector('[aria-invalid="true"]');
    if (premier) {
        premier.focus();
    } else {
        message.setAttribute('tabindex', '-1');
        message.focus();
    }
}

async function envoyer(form, action, succes, prefixe = '') {
    effacerErreurs(form);
    const bouton = form.querySelector('[type="submit"]');
    bouton.disabled = true;
    try {
        const reponse = await action();
        await succes(reponse);
    } catch (erreur) {
        if (erreur.statut === 401 || erreur.statut === 403) {
            await bloquer(erreur);
            return;
        }
        afficherErreurs(form, erreur, prefixe);
    } finally {
        bouton.disabled = false;
    }
}

// message de reussite avec un lien vers la page publique de la soiree
function confirmer(texte, soireeId) {
    etat.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'admin-succes';
    p.textContent = `${texte} `;
    const lien = document.createElement('a');
    lien.href = `soiree.html?id=${encodeURIComponent(soireeId)}`;
    lien.textContent = 'Voir la soirée';
    p.append(lien);
    etat.append(p);
    toast(texte);
    etat.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

formSoiree.addEventListener('submit', (e) => {
    e.preventDefault();
    envoyer(formSoiree, () => api.ajouterSoiree(lireSoiree()), async (reponse) => {
        const nom = formSoiree.elements.nom.value;
        formSoiree.reset();
        majJauge();
        await initialiserSpectaclesSoiree();
        await chargerSoirees();
        confirmer(`La soirée « ${nom} » a été ajoutée au programme.`, reponse.soiree.id);
    });
});

formSpectacle.addEventListener('submit', (e) => {
    e.preventDefault();
    const soireeId = selectSoiree.value;
    envoyer(formSpectacle, () => api.ajouterSpectacle(soireeId, lireSpectacle(new FormData(formSpectacle), '')), async () => {
        const titre = formSpectacle.elements.titre.value;
        formSpectacle.reset();
        infoSoiree.textContent = '';
        confirmer(`Le spectacle « ${titre} » a été ajouté.`, soireeId);
    });
});

// ---------- demarrage ----------

// sans les droits d'administration, on remplace les formulaires par le message de l'api
async function bloquer(erreur) {
    formSoiree.hidden = true;
    formSpectacle.hidden = true;
    const message = erreur.statut === 401
        ? 'Connectez-vous avec un compte administrateur.'
        : 'Cette page est réservée aux administrateurs du festival.';
    await afficherErreur(etat, { ...erreur, message, details: null },
        () => { window.location.href = erreur.statut === 401 ? lienConnexion() : 'index.html'; },
        erreur.statut === 401 ? 'Se connecter' : 'Retour au programme');
}

async function demarrer() {
    try {
        const referentiels = await api.referentiels();
        styles = referentiels.styles;
        lieux = referentiels.lieux;
        await afficher(selectLieu, 'options', {
            libelleTous: 'Choisir un lieu…',
            options: lieux.map((l) => ({ valeur: l.id, libelle: `${l.nom} (${nombre(l.capacite)} places)` })),
        });
        await Promise.all([
            initialiserSpectaclesSoiree(),
            champsSpectacle(0, { prefixe: '', legende: 'Spectacle' }).then((html) => {
                document.getElementById('spectacle-champs').innerHTML = html;
            }),
            chargerSoirees(),
        ]);
    } catch (erreur) {
        if (erreur.statut === 401 || erreur.statut === 403) {
            await bloquer(erreur);
        } else {
            await afficherErreur(etat, erreur, () => window.location.reload());
        }
    }
}

demarrer();
