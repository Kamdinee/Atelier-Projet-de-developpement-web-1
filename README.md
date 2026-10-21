# NRV.net - billetterie du festival Nancy Rock Vibration

SAE « Atelier-projet de développement web 1 » - BUT 3 informatique, IUT Nancy-Charlemagne.

Programme du festival, achat de billets (soirée ou Pass 3 jours) et suivi des jauges.  
Backend PHP 8 / Slim 4 en architecture hexagonale, front HTML / Sass / Handlebars, le tout sous Docker.

Le détail de la conception et du travail réalisé est dans le dossier de rendu : [docs/NRV-dossier-iteration-1.pdf](docs/NRV-dossier-iteration-1.pdf).

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
