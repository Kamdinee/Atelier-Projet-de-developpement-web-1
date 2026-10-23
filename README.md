# NRV.net - billetterie du festival Nancy Rock Vibration

SAE « Atelier-projet de développement web 1 » - BUT 3 informatique, IUT Nancy-Charlemagne.

Programme du festival, achat de billets (soirée ou Pass 3 jours) et suivi des jauges.  
Backend PHP 8 / Slim 4 en architecture hexagonale, front HTML / Sass / Handlebars, le tout sous Docker.

Le détail de la conception et du travail réalisé est dans le dossier de rendu final : [docs/NRV-dossier-final.pdf](docs/NRV-dossier-final.pdf) (le dossier de l'itération 1 reste dans [docs/NRV-dossier-iteration-1.pdf](docs/NRV-dossier-iteration-1.pdf)).

## Version en ligne

| Service | Adresse |
| --- | --- |
| Site du festival | http://docketu.iutnc.univ-lorraine.fr:25001/ |
| API (JSON) | http://docketu.iutnc.univ-lorraine.fr:25000/ |
| Adminer (base de données) | http://docketu.iutnc.univ-lorraine.fr:25002/ - serveur `db`, utilisateur `nrv`, mot de passe `nrv`, base `nrv` |

Le serveur docketu est sur le réseau privé de l'IUT : les liens marchent directement à l'IUT (Wi-Fi Eduroam).
Depuis l'extérieur, activer d'abord le VPN de l'Université de Lorraine :

1. aller sur https://vpn.univ-lorraine.fr/ et se connecter avec ses identifiants ENT (`login@etu`)
2. installer le logiciel proposé (Cisco Secure Client)
3. dans Cisco Secure Client, saisir `vpn.univ-lorraine.fr`, cliquer sur *Connect*, puis entrer son identifiant et son mot de passe ENT
4. une fois le cadenas vert affiché, les liens ci-dessus fonctionnent

## Installation en local

Prérequis : Git et Docker (avec `docker compose`).

```bash
git clone https://github.com/Kamdinee/Atelier-Projet-de-developpement-web-1.git
cd Atelier-Projet-de-developpement-web-1
git checkout main
cp .env.example .env

docker compose up -d --build
docker compose exec php composer install
docker compose exec php composer seed
```

| Service | Adresse par défaut |
| --- | --- |
| API | http://localhost:8080 |
| Front | http://localhost:8081 |
| Adminer (base de données) | http://localhost:8082 - serveur `db`, utilisateur `nrv`, mot de passe `nrv` |

## Données de test

La commande `docker compose exec php composer seed` remplit la base : 14 soirées du 4 au 17 juin 2027, 37 spectacles, des milliers de billets et de pass déjà vendus.

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| Admin | `admin@nrv.net` | `admin12345` |
| Client | `client@nrv.net` | `client12345` |

Le compte `client@nrv.net` a une commande payée (2 billets « Nuit métal » + 1 Pass 3 jours) pour tester « Mes billets » et l'impression des billets.

## Commande et paiement (étape 6)

| Route | Accès | Rôle |
| --- | --- | --- |
| `POST /auth/register`, `POST /auth/signin` | public | inscription, connexion (jeton JWT) |
| `POST /commandes` | connecté | le panier devient une commande « en attente », prix recalculés par le serveur |
| `GET /commandes/{id}` | connecté (propriétaire) | récapitulatif avant paiement |
| `POST /commandes/{id}/paiement` | connecté (propriétaire) | paiement simulé : places vérifiées et réservées dans une transaction, billets émis |

Paiement simulé : la carte `4242 4242 4242 4242` est acceptée, `4000 0000 0000 0002` est refusée par la banque (402). Seuls les 4 derniers chiffres sont enregistrés.

## Billets et backoffice (étape 7)

| Route | Accès | Rôle |
| --- | --- | --- |
| `GET /billets/{token}` | public (le jeton sert de clé) | un billet imprimable |
| `GET /commandes`, `GET /commandes/{id}/billets`, `GET /billets` | connecté | mes commandes et mes billets |
| `GET /admin/jauges` | admin | places vendues et recette par soirée |
| `GET /admin/referentiels` | admin | lieux et styles des formulaires |
| `POST /admin/soirees`, `POST /admin/soirees/{id}/spectacles` | admin | ajout d'une soirée (2 à 3 spectacles) ou d'un spectacle |

Pages du site : `billet.html?token=…`, `mes-billets.html`, `admin.html` (jauges), `admin-programme.html` (ajout).

Les routes protégées attendent l'en-tête `Authorization: Bearer <jwt>`, renvoyé par `POST /auth/signin`. Sur le site, il suffit de se connecter : avec `admin@nrv.net`, le lien « Backoffice » apparaît dans l'en-tête.

## Tests automatisés

Pour lancer la suite de tests unitaires (PHPUnit) :

```bash
docker compose exec php composer test
```

## Déploiement sur docketu

Mêmes commandes que pour l'installation, en adaptant le fichier `.env` :

```ini
APP_ENV=prod
JWT_SECRET=<chaine_aleatoire_de_32_caracteres_minimum>

# Adapter les ports si ceux par défaut sont déjà utilisés sur la machine :
API_PORT=25000
FRONT_PORT=25001
ADMINER_PORT=25002
DB_PORT_HOTE=25003

# L'adresse autorisée pour le front doit correspondre au FRONT_PORT choisi :
CORS_ORIGINS=http://docketu.iutnc.univ-lorraine.fr:25001
```

Si `API_PORT` change, reporter la même valeur dans `front/public/js/config.js` (`const API_PORT = 25000;`).

## Commandes utiles

| Pour... | Commande |
| --- | --- |
| Arrêter les conteneurs | `docker compose down` |
| Voir les logs de l'API | `docker compose logs -f api` |
| Réinitialiser complètement la base | `docker compose down -v && docker compose up -d && docker compose exec php composer seed` |
| Reconstruire après modification Docker | `docker compose up -d --build` |
