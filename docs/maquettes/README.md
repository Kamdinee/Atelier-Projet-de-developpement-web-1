# maquettes des ecrans

maquettes statiques en html, avec les vrais styles du front (`front/public/css/main.css`) et les donnees du seed.
elles servent de modele pour le front : il reste a remplacer le contenu en dur par des appels `fetch` + des templates handlebars.

pour les ouvrir avec les styles, lancer un petit serveur depuis la racine du depot :

```bash
python3 -m http.server 8000
# puis http://localhost:8000/docs/maquettes/index.html
```

| ecran | fichier | captures |
| --- | --- | --- |
| programme (grille + filtres + pagination) | `index.html` | `captures/index-bureau.png`, `captures/index-mobile.png` |
| programme filtre par style | `programme-filtre.html` | `captures/programme-filtre-bureau.png` |
| detail d'une soiree + ajout au panier | `soiree.html` | `captures/soiree-bureau.png`, `captures/soiree-mobile.png` |
| soiree complete | `soiree-complete.html` | `captures/soiree-complete-bureau.png` |
| panier + composer un pass 3 jours | `panier.html` | `captures/panier-bureau.png`, `captures/panier-mobile.png` |
| connexion / inscription | `compte.html` | `captures/compte-bureau.png` |
| paiement (formulaire carte) | `paiement.html` | `captures/paiement-bureau.png` |
| confirmation et liste des billets | `confirmation.html` | `captures/confirmation-bureau.png` |
| billet imprimable (soiree + pass) | `billet.html` | `captures/billet-bureau.png`, `captures/billet-mobile.png`, `captures/billet-impression.png` |
| backoffice : suivi des jauges | `admin.html` | `captures/admin-bureau.png`, `captures/admin-mobile.png` |

les affiches des spectacles (`img/spectacle-*.svg`) sont generees, a remplacer par de vraies photos si besoin.
