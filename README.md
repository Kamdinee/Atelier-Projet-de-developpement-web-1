# NRV.net - billetterie du festival Nancy Rock Vibration

SAE « Atelier-projet de développement web 1 » - BUT 3 informatique, IUT Nancy-Charlemagne.

Programme du festival, achat de billets (soirée ou Pass 3 jours) et suivi des jauges.
Backend PHP 8 / Slim 4 en architecture hexagonale, front HTML / Sass / Handlebars, le tout sous Docker.

Le détail de la conception et du travail réalisé est dans le dossier de rendu : [docs/NRV-dossier-iteration-1.pdf](docs/NRV-dossier-iteration-1.pdf).

## Installation

Prérequis : Git et Docker (avec `docker compose`).

```bash
git clone https://github.com/Kamdinee/Atelier-Projet-de-developpement-web-1.git
cd Atelier-Projet-de-developpement-web-1
git checkout production-kam
cp .env.example .env

docker compose up -d --build
docker compose exec php composer install
docker compose exec php composer seed
```

| Service | Adresse |
| --- | --- |
| API | http://localhost:8080 |
| Front | http://localhost:8081 |
| Adminer (base de données) | http://localhost:8082 - serveur `db`, utilisateur `nrv`, mot de passe `nrv` |

## Données de test

`composer seed` remplit la base : 14 soirées du 4 au 17 juin 2027, 37 spectacles, des milliers de billets et de pass déjà vendus.

| Rôle | Email | Mot de passe |
| --- | --- | --- |
| admin | admin@nrv.net | admin12345 |
| client | client@nrv.net | client12345 |

## Tests

```bash
docker compose exec php composer test
```

## Déploiement sur docketu

Mêmes commandes que l'installation, après avoir adapté le `.env` :

```ini
APP_ENV=prod
JWT_SECRET=<chaine aleatoire de 32 caracteres minimum>
CORS_ORIGINS=http://docketu.iutnc.univ-lorraine.fr:8081
```

Si un port est déjà pris, le changer dans le `.env` (`API_PORT`, `FRONT_PORT`...). Si `API_PORT` change, reporter la valeur dans `front/public/js/config.js`.

## Commandes utiles

| Pour... | Commande |
| --- | --- |
| arrêter | `docker compose down` |
| repartir d'une base neuve | `docker compose down -v && docker compose up -d && docker compose exec php composer seed` |
| voir les logs | `docker compose logs -f api` |
