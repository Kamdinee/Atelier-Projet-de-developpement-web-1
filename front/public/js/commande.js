import { api } from './api.js';
import { getToken, getUtilisateur, majNavigation } from './auth.js';
import { dateCourte, prix } from './format.js';
import * as panier from './panier.js';
import { toast } from './ui.js';

document.addEventListener('DOMContentLoaded', async () => {
    majNavigation();
    panier.majBadge();

    const token = getToken();
    const params = new URLSearchParams(window.location.search);
    const commandeId = params.get('id');

    const chargementEl = document.getElementById('commande-chargement');
    const erreurEl = document.getElementById('commande-erreur');
    const sectionPaiement = document.getElementById('section-paiement');
    const sectionConfirmation = document.getElementById('section-confirmation');

    const afficherErreur = (message) => {
        chargementEl.hidden = true;
        erreurEl.hidden = false;
        erreurEl.textContent = message;
    };

    if (!token) {
        sessionStorage.setItem('nrv_redirect', window.location.href);
        window.location.href = 'connexion.html';
        return;
    }

    if (!commandeId) {
        afficherErreur('Identifiant de commande manquant dans l’adresse.');
        return;
    }

    try {
        const reponse = await api.commande(commandeId);
        const commande = reponse.commande;

        chargementEl.hidden = true;

        if (commande.statut === 'PAYEE') {
            panier.vider();
            panier.majBadge();
            afficherConfirmation(commande);
        } else {
            afficherPaiement(commande);
        }
    } catch (err) {
        afficherErreur(err.message || 'Impossible de récupérer votre commande.');
    }

    // Affichage de l'écran de paiement
    function afficherPaiement(commande) {
        sectionPaiement.hidden = false;
        sectionConfirmation.hidden = true;

        document.getElementById('paiement-ref-commande').textContent = commande.id;
        document.getElementById('paiement-total').textContent = prix(commande.total);

        const btnPayer = document.getElementById('btn-payer');
        btnPayer.textContent = `Régler ${prix(commande.total)}`;

        // Pré-remplir le nom avec le profil si disponible
        const user = getUtilisateur();
        const inputNom = document.getElementById('cb-nom');
        if (user && (user.prenom || user.nom)) {
            inputNom.value = `${user.prenom ?? ''} ${user.nom ?? ''}`.trim();
        }

        // Remplir les lignes du récapitulatif
        const listeLignes = document.getElementById('paiement-liste-lignes');
        listeLignes.replaceChildren();

        for (const ligne of commande.lignes) {
            const li = document.createElement('li');
            li.className = 'commande-ligne-item';

            const info = document.createElement('div');
            info.className = 'commande-ligne-item__info';

            const titre = document.createElement('strong');
            const catLabel = ligne.categorie === 'REDUIT' ? 'Tarif réduit' : 'Tarif normal';
            const nomObjet = ligne.type === 'PASS' ? (ligne.pass_libelle ?? 'Pass 3 jours') : (ligne.soiree_nom ?? 'Soirée');
            titre.textContent = `${nomObjet} (${catLabel})`;

            const sub = document.createElement('span');
            sub.className = 'commande-ligne-item__sub';
            if (ligne.type === 'PASS' && Array.isArray(ligne.soirees)) {
                const datesFormat = ligne.soirees.map(s => dateCourte(s.date_soiree)).join(', ');
                sub.textContent = `3 dates : ${datesFormat} · ${prix(ligne.prix_unitaire)} × ${ligne.quantite}`;
            } else {
                sub.textContent = `${dateCourte(ligne.date_soiree)} · ${ligne.heure_debut?.slice(0, 5)} · ${prix(ligne.prix_unitaire)} × ${ligne.quantite}`;
            }

            info.append(titre, sub);

            const montant = document.createElement('span');
            montant.className = 'commande-ligne-item__montant';
            montant.textContent = prix(ligne.montant);

            li.append(info, montant);
            listeLignes.append(li);
        }

        // Formatage automatique carte
        const inputNumero = document.getElementById('cb-numero');
        inputNumero.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').slice(0, 16);
            val = val.replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = val;
        });

        const inputExp = document.getElementById('cb-expiration');
        inputExp.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').slice(0, 4);
            if (val.length >= 3) {
                val = `${val.slice(0, 2)}/${val.slice(2)}`;
            }
            e.target.value = val;
        });

        // Soumission du paiement
        const formPaiement = document.getElementById('form-paiement');
        const messagePaiement = document.getElementById('paiement-message');

        formPaiement.addEventListener('submit', async (e) => {
            e.preventDefault();
            messagePaiement.textContent = '';
            messagePaiement.className = 'auth-message';

            const nom = inputNom.value.trim();
            const numero = inputNumero.value.replace(/\D/g, '');
            const exp = inputExp.value.trim();
            const cvv = document.getElementById('cb-cvv').value.trim();

            if (!nom) {
                messagePaiement.textContent = 'Indiquez le nom sur la carte.';
                messagePaiement.className = 'auth-message auth-message--erreur';
                return;
            }

            if (numero.length < 12) {
                messagePaiement.textContent = 'Numéro de carte incomplet.';
                messagePaiement.className = 'auth-message auth-message--erreur';
                return;
            }

            if (!/^\d{2}\/\d{2}$/.test(exp)) {
                messagePaiement.textContent = 'Date d’expiration invalide (format MM/AA).';
                messagePaiement.className = 'auth-message auth-message--erreur';
                return;
            }

            if (!/^\d{3,4}$/.test(cvv)) {
                messagePaiement.textContent = 'Cryptogramme (CVV) invalide.';
                messagePaiement.className = 'auth-message auth-message--erreur';
                return;
            }

            btnPayer.disabled = true;
            btnPayer.textContent = 'Traitement bancaire en cours…';

            try {
                const res = await api.payerCommande(commande.id, {
                    nom_carte: nom,
                    numero_carte: numero,
                    date_expiration: exp,
                    cvv,
                });

                panier.vider();
                panier.majBadge();
                toast('Paiement accepté ! Billets disponibles.');
                afficherConfirmation(res.commande);
            } catch (err) {
                messagePaiement.textContent = err.message || 'Paiement refusé.';
                messagePaiement.className = 'auth-message auth-message--erreur';
                btnPayer.disabled = false;
                btnPayer.textContent = `Régler ${prix(commande.total)}`;
            }
        });
    }

    // Affichage de l'écran de confirmation
    function afficherConfirmation(commande) {
        sectionPaiement.hidden = true;
        sectionConfirmation.hidden = false;

        document.getElementById('confirm-id').textContent = commande.id;
        document.getElementById('confirm-date').textContent = commande.payee_le ?? new Date().toLocaleString('fr-FR');
        document.getElementById('confirm-total').textContent = prix(commande.total);
        document.getElementById('confirm-carte').textContent = `•••• •••• •••• ${commande.carte_fin ?? '••••'}`;

        const grilleBillets = document.getElementById('confirm-liste-billets');
        grilleBillets.replaceChildren();

        const billets = commande.billets || [];

        if (!billets.length) {
            grilleBillets.innerHTML = '<p class="message-vide">Aucun billet associé à cette commande.</p>';
            return;
        }

        for (const billet of billets) {
            const carte = document.createElement('div');
            carte.className = 'billet-carte';

            const header = document.createElement('div');
            header.className = 'billet-carte__header';

            const ref = document.createElement('strong');
            ref.className = 'billet-carte__ref';
            ref.textContent = billet.reference;

            const badgeType = document.createElement('span');
            badgeType.className = `billet-carte__badge billet-carte__badge--${billet.type.toLowerCase()}`;
            badgeType.textContent = billet.type === 'PASS' ? 'Pass 3 jours' : 'Billet Soirée';

            header.append(ref, badgeType);

            const corps = document.createElement('div');
            corps.className = 'billet-carte__corps';

            const titre = document.createElement('h3');
            titre.textContent = billet.type === 'PASS' ? (billet.pass_libelle ?? 'Pass 3 jours') : (billet.soiree_nom ?? 'Soirée NRV');

            const infos = document.createElement('p');
            infos.className = 'billet-carte__details';

            if (billet.type === 'PASS' && Array.isArray(billet.soirees)) {
                const datesStr = billet.soirees.map(s => dateCourte(s.date_soiree)).join(', ');
                infos.textContent = `Dates : ${datesStr}`;
            } else {
                infos.textContent = `${dateCourte(billet.date_soiree)} · ${billet.heure_debut?.slice(0, 5)}`;
            }

            const pied = document.createElement('div');
            pied.className = 'billet-carte__pied';

            const acheteur = document.createElement('span');
            acheteur.className = 'billet-carte__acheteur';
            acheteur.textContent = `Titulaire : ${billet.nom_acheteur}`;

            const prixBillet = document.createElement('span');
            prixBillet.className = 'billet-carte__prix';
            prixBillet.textContent = `${billet.categorie === 'REDUIT' ? 'Tarif réduit' : 'Plein tarif'} · ${prix(billet.prix_paye)}`;

            pied.append(acheteur, prixBillet);

            corps.append(titre, infos, pied);

            if (billet.token) {
                const lienBillet = document.createElement('a');
                lienBillet.className = 'btn btn--secondaire';
                lienBillet.style.marginTop = '0.75rem';
                lienBillet.style.fontSize = '0.9rem';
                lienBillet.href = `billet.html?token=${encodeURIComponent(billet.token)}`;
                lienBillet.textContent = 'Voir le billet & QR Code';
                corps.append(lienBillet);
            }

            carte.append(header, corps);
            grilleBillets.append(carte);
        }

        const btnImprimer = document.getElementById('btn-imprimer-billets-officiels');
        if (btnImprimer) {
            btnImprimer.href = `billet.html?commande=${encodeURIComponent(commande.id)}`;
        }
    }
});
