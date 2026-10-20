<?php

declare(strict_types=1);

// remplit la base avec un jeu de donnees realiste
// usage : docker compose exec php composer seed

require __DIR__ . '/../vendor/autoload.php';

$faker = Faker\Factory::create('fr_FR');
// toujours le meme jeu de donnees
$faker->seed(2027);
mt_srand(2027);

$env = static fn (string $nom, string $defaut): string => getenv($nom) !== false ? (string) getenv($nom) : $defaut;

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env('DB_HOST', 'db'), $env('DB_PORT', '3306'), $env('DB_NAME', 'nrv')),
    $env('DB_USER', 'nrv'),
    $env('DB_PASSWORD', 'nrv'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);

// uuid v4 aleatoire
function uuid4(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

const TARIF_NORMAL = 3000;
const TARIF_REDUIT = 2000;
const REMISE_PASS = 3;

// ---------- donnees de reference ----------

$lieux = [
    ['L\'Autre Canal', '45 boulevard d\'Austrasie, 54000 Nancy', 'Nancy', 1200],
    ['Zénith de Nancy', 'rue du Zénith, 54320 Maxéville', 'Maxéville', 5000],
    ['Salle Poirel', '3 rue Victor Poirel, 54000 Nancy', 'Nancy', 750],
    ['Parc de la Pépinière - grande scène', 'rue Sainte-Catherine, 54000 Nancy', 'Nancy', 3000],
];

$styles = [
    'classic-rock' => 'Classic rock',
    'blues-rock' => 'Blues rock',
    'metal' => 'Métal',
    'chanson' => 'Chanson',
    'reggae' => 'Reggae',
    'punk' => 'Punk',
    'folk' => 'Folk',
    'electro-rock' => 'Électro rock',
];

// groupes fictifs par style
$groupes = [
    'classic-rock' => ['The Stanislas Riders', 'Velvet Moselle', 'Les Chardons Électriques', 'Old Lorraine Road', 'Highway 54', 'The Grand Duke', 'Marble Heads'],
    'blues-rock' => ['Muddy Meurthe', 'Johnny Bluesville', 'The Rusty Strings', 'Sister Magnolia', 'Slow Train Quartet'],
    'metal' => ['Iron Lorraine', 'Fer Rouge', 'Abyssal Forge', 'Thrash Gargoyle', 'Nordic Anvil', 'Black Mirabelle'],
    'chanson' => ['Lou Marchal', 'Céleste Arnaud', 'Les Voix du Quai', 'Jeanne Vasseur', 'Antoine Lebrun'],
    'reggae' => ['Roots Mirabelle', 'Dub Station 54', 'Kaya Lion', 'Sound System Austrasie'],
    'punk' => ['Les Rats de Ville-Vieille', 'No Futur Stan', 'Pogo Sabotage', 'Crash Kiosque'],
    'folk' => ['La Ronde des Vosges', 'Clara & the Pines', 'Les Sabots Volants', 'Harvest Moon Trio'],
    'electro-rock' => ['Neon Duc', 'Circuit Basilique', 'Pulse Lorraine', 'Laser Rococo'],
];

// textes de description par style
$phrases = [
    'classic-rock' => ['Guitares saturées et refrains à reprendre en choeur : %s fait revivre l\'âge d\'or du rock.', 'Formé à %s en %d, %s enchaîne les tournées avec un son taillé pour la scène.'],
    'blues-rock' => ['Un blues brûlant, des solos interminables : %s joue comme si chaque soir était le dernier.', 'Originaire de %s, actif depuis %d, %s mêle blues du Delta et riffs rock.'],
    'metal' => ['Riffs massifs et double pédale : %s vient défendre son dernier album sur scène.', 'Né à %s en %d, %s est devenu une référence de la scène métal du Grand Est.'],
    'chanson' => ['Des textes ciselés et une voix qui porte : %s revisite la grande chanson française.', 'Venue de %s, sur scène depuis %d, %s raconte la ville et ses gens.'],
    'reggae' => ['Basses profondes et messages positifs : %s promet une soirée sous le soleil.', 'Collectif fondé à %s en %d, %s fait vibrer les sound systems de la région.'],
    'punk' => ['Trois accords, beaucoup d\'énergie : %s ne laisse personne immobile.', 'Groupe de %s formé en %d, %s joue vite, fort et sans filet.'],
    'folk' => ['Banjo, violon et harmonies vocales : %s invite au voyage.', 'Depuis %s et depuis %d, %s fait danser les festivals avec ses ballades.'],
    'electro-rock' => ['Synthés et guitares se répondent : %s transforme la salle en piste de danse.', 'Duo né à %s en %d, %s mélange machines et rock indé.'],
];

// 14 soirees du 4 au 17 juin 2027 : nom, thematique, index lieu, heure, styles des spectacles
$programme = [
    ['Ouverture électrique', 'Soirée classic rock', 1, '20:00', ['classic-rock', 'classic-rock', 'blues-rock']],
    ['Blues de minuit', 'Soirée blues', 0, '20:30', ['blues-rock', 'blues-rock']],
    ['Dimanche chanson', 'Soirée chanson', 2, '18:30', ['chanson', 'chanson', 'folk']],
    ['Heavy night', 'Soirée métal', 0, '20:00', ['metal', 'metal', 'metal']],
    ['Reggae sous les arbres', 'Soirée reggae', 3, '19:00', ['reggae', 'reggae']],
    ['Punk à Stan', 'Soirée punk', 0, '20:30', ['punk', 'punk', 'metal']],
    ['Folk et feux de camp', 'Soirée folk', 2, '19:30', ['folk', 'chanson']],
    ['Nuit métal', 'Soirée métal', 1, '19:30', ['metal', 'metal', 'classic-rock']],
    ['Rock en Lorraine', 'Soirée classic rock', 3, '19:00', ['classic-rock', 'blues-rock', 'classic-rock']],
    ['Électro rock party', 'Soirée électro rock', 0, '21:00', ['electro-rock', 'electro-rock', 'punk']],
    ['Blues et soul', 'Soirée blues', 2, '20:00', ['blues-rock', 'chanson']],
    ['Hommage aux légendes', 'Soirée classic rock', 1, '20:00', ['classic-rock', 'blues-rock', 'classic-rock']],
    ['Reggae roots', 'Soirée reggae', 3, '18:30', ['reggae', 'folk']],
    ['Grand final', 'Soirée de clôture', 1, '19:30', ['classic-rock', 'metal', 'electro-rock']],
];

// taux de remplissage vise par soiree : 1.0 = complete, null = 2 places restantes
$remplissage = [0.62, 0.48, 1.0, 0.71, 0.35, 0.55, 0.40, 0.73, 0.50, 0.66, null, 0.58, 0.30, 0.45];

// ---------- remise a zero ----------

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['billets', 'lignes_commande', 'commandes', 'utilisateurs', 'spectacle_images', 'spectacle_artiste', 'spectacles', 'soirees', 'artistes', 'styles', 'lieux'] as $table) {
    $pdo->exec("TRUNCATE TABLE {$table}");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$pdo->beginTransaction();

// ---------- lieux, styles ----------

$insLieu = $pdo->prepare('INSERT INTO lieux (nom, adresse, ville, capacite) VALUES (?, ?, ?, ?)');
$lieuxIds = [];
foreach ($lieux as $l) {
    $insLieu->execute($l);
    $lieuxIds[] = ['id' => (int) $pdo->lastInsertId(), 'capacite' => $l[3]];
}

$insStyle = $pdo->prepare('INSERT INTO styles (libelle, slug) VALUES (?, ?)');
$stylesIds = [];
foreach ($styles as $slug => $libelle) {
    $insStyle->execute([$libelle, $slug]);
    $stylesIds[$slug] = (int) $pdo->lastInsertId();
}

// ---------- soirees, spectacles, artistes ----------

$insSoiree = $pdo->prepare('INSERT INTO soirees (nom, thematique, date_soiree, heure_debut, lieu_id, jauge, places_vendues, tarif_normal, tarif_reduit) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)');
$insSpectacle = $pdo->prepare('INSERT INTO spectacles (soiree_id, style_id, titre, description, video_url, horaire_previsionnel) VALUES (?, ?, ?, ?, ?, ?)');
$insArtiste = $pdo->prepare('INSERT INTO artistes (nom) VALUES (?)');
$insLien = $pdo->prepare('INSERT INTO spectacle_artiste (spectacle_id, artiste_id, ordre) VALUES (?, ?, ?)');
$insImage = $pdo->prepare('INSERT INTO spectacle_images (spectacle_id, url, alt, ordre) VALUES (?, ?, ?, ?)');

$artistesIds = [];
$artisteId = static function (string $nom) use (&$artistesIds, $insArtiste, $pdo): int {
    if (!isset($artistesIds[$nom])) {
        $insArtiste->execute([$nom]);
        $artistesIds[$nom] = (int) $pdo->lastInsertId();
    }

    return $artistesIds[$nom];
};

// on pioche les groupes sans remise pour eviter les doublons
$reserve = array_map(static fn (array $g) => $faker->shuffleArray($g), $groupes);
$soirees = [];
$nbSpectacles = 0;
$date = new DateTimeImmutable('2027-06-04');

foreach ($programme as $i => [$nom, $thematique, $lieuIndex, $heure, $stylesSoiree]) {
    $lieu = $lieuxIds[$lieuIndex];
    $insSoiree->execute([$nom, $thematique, $date->format('Y-m-d'), $heure, $lieu['id'], $lieu['capacite'], TARIF_NORMAL, TARIF_REDUIT]);
    $soireeId = (int) $pdo->lastInsertId();
    $soirees[] = ['id' => $soireeId, 'jauge' => $lieu['capacite'], 'vendues' => 0];

    $horaire = new DateTimeImmutable($date->format('Y-m-d') . ' ' . $heure);
    foreach ($stylesSoiree as $ordre => $slug) {
        // le dernier spectacle est la tete d'affiche
        $horaire = $horaire->modify($ordre === 0 ? '+30 minutes' : '+1 hour 20 minutes');
        $groupe = array_shift($reserve[$slug]) ?? $faker->unique()->lastName() . ' Band';

        $modele = $faker->randomElement($phrases[$slug]);
        $description = str_contains($modele, '%d')
            ? sprintf($modele, $faker->city(), $faker->numberBetween(1995, 2022), $groupe)
            : sprintf($modele, $groupe);
        $description .= ' ' . $faker->randomElement([
            'Un concert à ne pas manquer.',
            'Leur passage au festival est très attendu.',
            'Première venue à Nancy.',
            'Le groupe présentera aussi des titres inédits.',
        ]);

        $video = 'https://www.youtube.com/embed/' . $faker->regexify('[A-Za-z0-9_-]{11}');
        $insSpectacle->execute([$soireeId, $stylesIds[$slug], $groupe, $description, $video, $horaire->format('H:i')]);
        $spectacleId = (int) $pdo->lastInsertId();
        $nbSpectacles++;

        $insLien->execute([$spectacleId, $artisteId($groupe), 1]);
        // parfois un invite
        if ($faker->boolean(25)) {
            $invite = $faker->firstName() . ' ' . $faker->lastName();
            $insLien->execute([$spectacleId, $artisteId($invite), 2]);
        }

        $nbImages = $faker->numberBetween(1, 3);
        for ($n = 1; $n <= $nbImages; $n++) {
            $insImage->execute([$spectacleId, "https://picsum.photos/seed/nrv-{$spectacleId}-{$n}/800/500", "{$groupe} sur scène", $n]);
        }
    }

    $date = $date->modify('+1 day');
}

// ---------- utilisateurs ----------

$insUser = $pdo->prepare('INSERT INTO utilisateurs (email, nom, prenom, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)');
$insUser->execute(['admin@nrv.net', 'Billetterie', 'Sophie', password_hash('admin12345', PASSWORD_BCRYPT), 'ADMIN']);
$insUser->execute(['client@nrv.net', 'Durand', 'Marc', password_hash('client12345', PASSWORD_BCRYPT), 'CLIENT']);

// clients fictifs, meme mot de passe pour tous
$hash = password_hash('motdepasse1', PASSWORD_BCRYPT);
$clients = [];
for ($i = 0; $i < 40; $i++) {
    $prenom = $faker->firstName();
    $nom = $faker->lastName();
    $insUser->execute([$faker->unique()->safeEmail(), $nom, $prenom, $hash, 'CLIENT']);
    $clients[] = ['id' => (int) $pdo->lastInsertId(), 'nom' => "{$prenom} {$nom}"];
}

// ---------- ventes simulees ----------

$insCommande = $pdo->prepare('INSERT INTO commandes (id, utilisateur_id, statut, sous_total, remise, total, carte_fin, cree_le, payee_le) VALUES (?, ?, \'PAYEE\', ?, ?, ?, ?, ?, ?)');
$insLigne = $pdo->prepare('INSERT INTO lignes_commande (commande_id, soiree_id, categorie, quantite, prix_unitaire, quantite_en_pass, remise_unitaire) VALUES (?, ?, ?, ?, ?, ?, ?)');
$insBillet = $pdo->prepare('INSERT INTO billets (token, reference, commande_id, soiree_id, categorie, prix_paye, nom_acheteur, emis_le) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$majVendues = $pdo->prepare('UPDATE soirees SET places_vendues = ? WHERE id = ?');

$prix = ['NORMAL' => TARIF_NORMAL, 'REDUIT' => TARIF_REDUIT];
$remiseUnitaire = static fn (int $p): int => (int) round($p * REMISE_PASS / 100);

// cree une commande payee : $lignes = [[index soiree, categorie, quantite, quantite en pass], ...]
$vendre = static function (array $lignes) use (&$soirees, $faker, $clients, $prix, $remiseUnitaire, $insCommande, $insLigne, $insBillet): void {
    $client = $faker->randomElement($clients);
    $commandeId = uuid4();
    $date = $faker->dateTimeBetween('2026-09-01', '2026-10-19')->format('Y-m-d H:i:s');

    $sousTotal = 0;
    $remise = 0;
    foreach ($lignes as [, $categorie, $quantite, $enPass]) {
        $sousTotal += $quantite * $prix[$categorie];
        $remise += $enPass * $remiseUnitaire($prix[$categorie]);
    }
    $insCommande->execute([$commandeId, $client['id'], $sousTotal, $remise, $sousTotal - $remise, $faker->numerify('####'), $date, $date]);

    $numero = 0;
    foreach ($lignes as [$index, $categorie, $quantite, $enPass]) {
        $p = $prix[$categorie];
        $r = $enPass > 0 ? $remiseUnitaire($p) : 0;
        $insLigne->execute([$commandeId, $soirees[$index]['id'], $categorie, $quantite, $p, $enPass, $r]);

        for ($b = 0; $b < $quantite; $b++) {
            $numero++;
            $prixPaye = $b < $enPass ? $p - $r : $p;
            $reference = sprintf('NRV-%s-%02d', strtoupper(substr($commandeId, 0, 8)), $numero);
            $insBillet->execute([uuid4(), $reference, $commandeId, $soirees[$index]['id'], $categorie, $prixPaye, $client['nom'], $date]);
        }
        $soirees[$index]['vendues'] += $quantite;
    }
};

// objectif de places vendues par soiree
$objectifs = [];
foreach ($soirees as $i => $s) {
    $objectifs[$i] = $remplissage[$i] === null ? $s['jauge'] - 2 : (int) floor($s['jauge'] * $remplissage[$i]);
}
$reste = static function (int $i) use (&$objectifs, &$soirees): int {
    return $objectifs[$i] - $soirees[$i]['vendues'];
};

// quelques pass 3 jours (1 billet par soiree, meme categorie)
for ($n = 0; $n < 60; $n++) {
    $choix = $faker->randomElements(array_keys($soirees), 3);
    $possibles = array_filter($choix, static fn (int $i) => $reste($i) >= 1);
    if (count($possibles) < 3) {
        continue;
    }
    $categorie = $faker->boolean(70) ? 'NORMAL' : 'REDUIT';
    $vendre(array_map(static fn (int $i) => [$i, $categorie, 1, 1], $choix));
}

// puis des commandes simples jusqu'a l'objectif
foreach (array_keys($soirees) as $i) {
    while ($reste($i) > 0) {
        $quantite = min($reste($i), $faker->numberBetween(1, 6));
        $reduits = $faker->numberBetween(0, $quantite);
        $lignes = [];
        if ($quantite - $reduits > 0) {
            $lignes[] = [$i, 'NORMAL', $quantite - $reduits, 0];
        }
        if ($reduits > 0) {
            $lignes[] = [$i, 'REDUIT', $reduits, 0];
        }
        $vendre($lignes);
    }
}

foreach ($soirees as $s) {
    $majVendues->execute([$s['vendues'], $s['id']]);
}

$pdo->commit();

// ---------- resume ----------

$nbBillets = (int) $pdo->query('SELECT COUNT(*) FROM billets')->fetchColumn();
$nbCommandes = (int) $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn();
$recette = (int) $pdo->query('SELECT COALESCE(SUM(prix_paye), 0) FROM billets')->fetchColumn();

printf(
    "seed ok : %d lieux, %d styles, %d soirees, %d spectacles, %d artistes, %d utilisateurs, %d commandes, %d billets, recette %.2f eur\n",
    count($lieux),
    count($styles),
    count($soirees),
    $nbSpectacles,
    count($artistesIds),
    count($clients) + 2,
    $nbCommandes,
    $nbBillets,
    $recette / 100
);
echo "comptes de test : admin@nrv.net / admin12345 - client@nrv.net / client12345\n";
