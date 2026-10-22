// formulaires de compte : envoi, messages d'erreur sous chaque champ, bouton occupe

// messages lisibles pour les erreurs renvoyees par l'api (champ => message)
const MESSAGES = {
    email: 'Saisissez une adresse email valide.',
    nom: 'Saisissez votre nom (80 caractères au maximum).',
    prenom: 'Saisissez votre prénom (80 caractères au maximum).',
    mot_de_passe: 'Le mot de passe doit faire de 8 à 72 caractères.',
};

function erreurChamp(formulaire, nom, message) {
    const champ = formulaire.elements[nom];
    const zone = document.getElementById(`${nom}-erreur`);
    if (!champ || !zone) {
        return false;
    }
    champ.setAttribute('aria-invalid', 'true');
    zone.textContent = message;
    zone.hidden = false;
    return true;
}

function effacer(formulaire) {
    formulaire.querySelectorAll('[aria-invalid]').forEach((champ) => champ.removeAttribute('aria-invalid'));
    formulaire.querySelectorAll('.champ-erreur').forEach((zone) => {
        zone.hidden = true;
        zone.textContent = '';
    });
    message(formulaire, '');
}

function message(formulaire, texte) {
    const zone = formulaire.querySelector('.formulaire__message');
    zone.textContent = texte;
    zone.hidden = texte === '';
}

// branche l'envoi du formulaire : envoyer(donnees) appelle l'api et renvoie la page suivante
// erreurs : statut http => message general (401 identifiants, 409 email deja pris...)
export function brancher(formulaire, envoyer, erreurs = {}) {
    const bouton = formulaire.querySelector('button[type="submit"]');
    const libelle = bouton.textContent;

    formulaire.addEventListener('submit', async (e) => {
        e.preventDefault();
        effacer(formulaire);
        bouton.disabled = true;
        bouton.textContent = 'Un instant…';

        try {
            window.location.href = await envoyer(Object.fromEntries(new FormData(formulaire)));
        } catch (erreur) {
            const champs = erreur.statut === 400 && erreur.details && typeof erreur.details === 'object'
                ? Object.keys(erreur.details).filter((nom) => erreurChamp(formulaire, nom, MESSAGES[nom] ?? erreur.details[nom]))
                : [];
            if (champs.length > 0) {
                // premier champ en erreur dans l'ordre de la page
                formulaire.querySelector('[aria-invalid="true"]').focus();
            } else {
                message(formulaire, erreurs[erreur.statut] ?? erreur.message);
            }
            bouton.disabled = false;
            bouton.textContent = libelle;
        }
    });
}
