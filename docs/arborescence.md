# arborescence

```text
.
├── docker-compose.yml          services db, php, api, front, adminer (+ sass en profil dev)
├── .env.example                variables a copier dans .env
├── docker/
│   ├── php/                    image php 8.3 fpm + composer
│   └── nginx/                  conf api (slim) et front (statique)
├── sql/
│   └── 01_schema.sql           tables + vue des jauges, joue au 1er demarrage
├── docs/                       notes du projet
├── api/                        backend slim, architecture hexagonale
│   ├── public/index.php        point d'entree
│   ├── config/                 bootstrap, settings, injection (php-di), middlewares, routes
│   ├── bin/seed.php            jeu de donnees faker
│   ├── src/
│   │   ├── Domain/             coeur metier, aucun framework
│   │   │   ├── Entity/         soiree, spectacle, commande, billet...
│   │   │   ├── ValueObject/    montant, categorie tarif, token billet, carte...
│   │   │   ├── Exception/      exceptions metier
│   │   │   └── Service/        calculateur panier (pass 3 jours)
│   │   ├── Application/        cas d'usage
│   │   │   ├── Port/In/        ports d'entree : interfaces des use cases (appelees par les actions)
│   │   │   ├── Port/Out/       ports de sortie : interfaces des depots et services externes
│   │   │   ├── Dto/            objets d'entree / sortie
│   │   │   └── UseCase/        implementations des use cases
│   │   └── Infrastructure/     adaptateurs
│   │       ├── Http/Action/        actions slim (adaptateur entrant)
│   │       ├── Http/Middleware/    cors, auth, erreurs, validation
│   │       ├── Http/Validation/    regles de validation par route
│   │       ├── Persistence/Pdo/    repositories pdo (adaptateur sortant)
│   │       ├── Security/           jwt, hash des mots de passe
│   │       ├── Payment/            passerelle de paiement simulee
│   │       ├── Token/              generateur d'uuid
│   │       └── Clock/              horloge systeme
│   └── tests/Domain/           tests phpunit du domaine
└── front/
    ├── package.json            sass + handlebars
    ├── src/scss/               abstracts (variables, mixins), base, layout, components, pages, print
    └── public/                 servi par nginx : html, css compile, js, templates handlebars, img
```

noyau metier = Domain + Application (entites, use cases, ports). adaptateurs = Infrastructure.

regle des dependances : `Infrastructure` -> `Application` -> `Domain`. le domaine ne depend de rien.
