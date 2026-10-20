# NRV.net - billetterie du festival Nancy Rock Vibration

SAE « Atelier-projet de développement web 1 » - BUT 3 informatique, IUT Nancy-Charlemagne, octobre 2026.

Application de billetterie d'un festival de musique de 2 semaines : consultation du programme, filtres par date et par style, détail des soirées, achat de billets (soirée ou Pass 3 jours) et suivi des jauges pour l'organisation.

- **backend** : PHP 8.3 / Slim 4, API RESTful JSON avec liens HATEOAS, architecture hexagonale, PHP-DI, PDO (MariaDB 10.11)
- **frontend** : HTML5, Sass/SCSS, JavaScript `fetch` + templates Handlebars
- **environnement** : Docker Compose

## Sommaire

1. [Installation en local](#installation-en-local)
2. [Données de test](#données-de-test)
3. [API](#api)
4. [Tests unitaires](#tests-unitaires)
5. [Architecture](#architecture)
6. [Déploiement sur docketu](#déploiement-sur-docketu)
7. [Commandes utiles et dépannage](#commandes-utiles-et-dépannage)

## Installation en local

Prérequis : Git et Docker avec le plugin `docker compose` (Docker Desktop sous Windows/macOS).

```bash
git clone https://github.com/Kamdinee/aterlier-s5-web1.git
cd aterlier-s5-web1
git checkout production
cp .env.example .env                       # windows powershell : copy .env.example .env

docker compose up -d --build               # la base est creee au premier demarrage (sql/01_schema.sql)
docker compose exec php composer install   # dependances php
docker compose exec php composer seed      # jeu de donnees de test
```

| Service | Adresse | Rôle |
| --- | --- | --- |
| api | http://localhost:8080 | API Slim (point d'entrée `GET /`) |
| front | http://localhost:8081 | site de la billetterie |
| adminer | http://localhost:8082 | interface de la base (serveur `db`, utilisateur `nrv`, mot de passe `nrv`, base `nrv`) |
| db | 127.0.0.1:3307 | MariaDB, accessible seulement depuis la machine |

Vérification rapide :

```bash
curl http://localhost:8080/
curl "http://localhost:8080/spectacles?style=metal&date=2027-06-11"
```

Le CSS compilé est déjà dans `front/public/css`. Pour modifier le Sass : `docker compose --profile dev up -d sass` (recompile à chaque sauvegarde).

## Données de test

`composer seed` vide la base puis la remplit avec un jeu réaliste et toujours identique (FakerPHP, graine fixe) :

- 4 lieux à Nancy (L'Autre Canal, Zénith, Salle Poirel, Parc de la Pépinière), 8 styles de musique
- 14 soirées du 4 au 17 juin 2027, 37 spectacles (2 ou 3 par soirée), artistes, images, vidéos
- tarifs : 30,00 € normal / 20,00 € réduit par soirée ; Pass 3 jours : 87,30 € normal / 58,20 € réduit (3 soirées moins 3 %)
- environ 5 600 commandes payées, 18 000 billets soirée et 580 Pass 3 jours

Cas utiles pour la démo :

| Soirée | Id | Situation |
| --- | --- | --- |
| Dimanche chanson (6 juin) | 3 | **complète** : tout achat doit être refusé |
| Blues et soul (14 juin) | 11 | **2 places restantes** : 2 billets passent, 3 sont refusés |
| Nuit métal (11 juin) | 8 | 3 spectacles, grande jauge (Zénith) |

Comptes :

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| admin (backoffice) | admin@nrv.net | admin12345 |
| client | client@nrv.net | client12345 |
| 40 clients fictifs | (voir table `utilisateurs`) | motdepasse1 |

La vue SQL `v_jauges_soirees` donne pour chaque soirée la jauge, les places vendues et restantes, et la recette (le prix d'un pass est réparti sur ses 3 soirées).

## API

Toutes les réponses sont en JSON. Les montants sont en centimes, les dates au format ISO (`2027-06-11`, `20:00`).

| Méthode | Route | Description |
| --- | --- | --- |
| GET | `/` | point d'entrée, liens vers les ressources |
| GET | `/spectacles` | liste paginée du programme |
| GET | `/spectacles/{id}` | un spectacle |
| GET | `/soirees/{id}` | détail d'une soirée avec ses 2 à 3 spectacles |
| GET | `/styles` | styles présents au programme (filtre) |
| GET | `/dates` | dates des soirées (filtre) |

Paramètres de `GET /spectacles` (tous optionnels, cumulables) :

| Paramètre | Format | Défaut |
| --- | --- | --- |
| `date` | `AAAA-MM-JJ` | aucun filtre |
| `style` | slug (`metal`, `blues-rock`...) | aucun filtre |
| `page` | entier ≥ 1 | 1 |
| `size` | entier de 1 à 50 | 12 |

Exemple de réponse (extrait) :

```json
{
  "type": "collection",
  "count": 2, "total": 7, "page": 1, "size": 2,
  "spectacles": [
    {
      "spectacle": { "id": 9, "titre": "Fer Rouge", "date": "2027-06-07", "horaire": "20:30",
                     "image": "https://picsum.photos/seed/nrv-9-1/800/500",
                     "style": { "slug": "metal", "libelle": "Métal" } },
      "links": { "self": { "href": "/spectacles/9" }, "soiree": { "href": "/soirees/4" } }
    }
  ],
  "links": { "self": {...}, "first": {...}, "next": {...}, "last": {...} }
}
```

Erreurs : format RFC 7807 (`application/problem+json`).

| Code | Cas |
| --- | --- |
| 400 | paramètre invalide (date impossible, style mal formé, id non numérique) ; le champ `errors` détaille chaque paramètre |
| 404 | ressource ou route inconnue |
| 405 | méthode non autorisée (en-tête `Allow`) |
| 409 | places insuffisantes |
| 422 | règle métier non respectée |
| 500 | erreur interne (sans détail quand `APP_ENV=prod`) |

CORS : seules les origines listées dans `CORS_ORIGINS` (`.env`) reçoivent les en-têtes `Access-Control-*`. Les requêtes `OPTIONS` reçoivent une réponse 204.

## Tests unitaires

```bash
docker compose exec php composer test
# ou, pour le detail de chaque test :
docker compose exec php vendor/bin/phpunit --testdox
```

PHPUnit 11 teste le noyau métier sans base de données :

- `tests/Domain` : entités et value objects (soirée de 2 à 3 spectacles, jauge, réservation, soirée complète, tarifs, style, critères de recherche)
- `tests/Application` : cas d'usage, branchés sur des dépôts en mémoire (`tests/Application/Mock`) à la place des adaptateurs PDO

## Architecture

Architecture hexagonale : le noyau métier (`Domain` + `Application`) ne dépend d'aucun framework. Les adaptateurs (`Infrastructure`) dépendent du noyau, jamais l'inverse.

```text
api/src/
├── Domain/               entites, value objects, exceptions metier (aucune dependance)
├── Application/
│   ├── Port/In/          ports d'entree : interfaces des cas d'usage
│   ├── Port/Out/         ports de sortie : interfaces des depots
│   ├── UseCase/          services qui implementent les cas d'usage
│   └── Dto/              donnees echangees avec les adaptateurs
└── Infrastructure/
    ├── Http/             adaptateur d'entree : actions slim, validation, erreurs, cors
    └── Persistence/Pdo/  adaptateur de sortie : depots pdo (requetes preparees)
```

- les branchements port → adaptateur sont dans `api/config/dependencies.php` (PHP-DI, autowiring)
- les routes sont dans `api/config/routes.php`, les middlewares dans `api/config/middlewares.php`
- détail de l'arborescence : [docs/arborescence.md](docs/arborescence.md)
- schéma de la base : [sql/01_schema.sql](sql/01_schema.sql)

Sécurité : requêtes préparées PDO systématiques (`ATTR_EMULATE_PREPARES` désactivé), validation des paramètres avant les cas d'usage, détail des erreurs masqué en production, CORS restreint, base non exposée hors de la machine.

## Déploiement sur docketu

Sur `docketu.iutnc.univ-lorraine.fr` :

```bash
git clone https://github.com/Kamdinee/aterlier-s5-web1.git
cd aterlier-s5-web1
git checkout production
cp .env.example .env
```

Dans `.env`, adapter :

```ini
APP_ENV=prod
JWT_SECRET=<une longue chaine aleatoire, 32 caracteres minimum>
DB_PASSWORD=<mot de passe a changer>
DB_ROOT_PASSWORD=<mot de passe a changer>
# changer les ports s'ils sont deja utilises sur le serveur
API_PORT=8080
FRONT_PORT=8081
ADMINER_PORT=8082
# adresse exacte du front telle que tapee dans le navigateur
CORS_ORIGINS=http://docketu.iutnc.univ-lorraine.fr:8081
```

Si `API_PORT` n'est pas 8080, mettre le même port dans `front/public/js/config.js` (`const API_PORT = ...`).

Puis :

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php composer seed
docker compose ps        # tous les services doivent etre "running", db "healthy"
```

Le site est alors sur `http://docketu.iutnc.univ-lorraine.fr:8081` et l'API sur le port 8080.

## Commandes utiles et dépannage

| Pour... | Commande |
| --- | --- |
| arrêter | `docker compose down` |
| relancer | `docker compose up -d` |
| récupérer les derniers commits | `git pull` puis `docker compose exec php composer install` |
| repartir d'une base neuve | `docker compose down -v && docker compose up -d && docker compose exec php composer seed` |
| voir les logs | `docker compose logs -f api` (ou `php`, `db`) |

- **`port is already allocated`** : un port est déjà pris, le changer dans `.env` (`API_PORT`, `FRONT_PORT`...).
- **Fedora / SELinux, `Permission denied`** : les volumes ont déjà l'option `:z` dans `docker-compose.yml` ; vérifier que le dépôt est à jour.
- **`composer seed` : `Connection refused`** : la base démarre encore, attendre 30 secondes.
- **Le front affiche « L'API ne répond pas »** : vérifier `docker compose ps`, l'origine du front dans `CORS_ORIGINS` et le port dans `front/public/js/config.js`.

## Organisation

- tableau Trello par étape (conception, environnement, production...)
- branche de travail `production`, un commit par carte Trello
- document de conception : scénarios, modèle du domaine, schéma SQL, contrat d'API, wireframes
