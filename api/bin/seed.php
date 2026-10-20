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
// pass 3 jours : 3 soirees avec 3 % de remise
const PASS_NB_SOIREES = 3;
const PASS_NORMAL = 8730;  // round(3 x 3000 x 0.97)
const PASS_REDUIT = 5820;  // round(3 x 2000 x 0.97)

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
    'classic-rock' => ['The Stanislas Riders', 'Velvet Moselle', 'Les Chardons Électriques', 'Old Lorraine Road', 'Highway 54', 'The Grand Duke', 'Marble Heads', 'Moselle Thunder'],
    'blues-rock' => ['Muddy Meurthe', 'Johnny Bluesville', 'The Rusty Strings', 'Sister Magnolia', 'Slow Train Quartet', 'Bayou Lorrain'],
    'metal' => ['Iron Lorraine', 'Fer Rouge', 'Abyssal Forge', 'Thrash Gargoyle', 'Nordic Anvil', 'Black Mirabelle', 'Hurlement d\'Acier'],
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
foreach (['billet_pass_soirees', 'billets', 'ligne_pass_soirees', 'lignes_commande', 'commandes', 'utilisateurs', 'pass', 'spectacle_images', 'spectacle_artiste', 'spectacles', 'soirees', 'artistes', 'styles', 'lieux'] as $table) {
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

// ---------- formule pass 3 jours ----------

$pdo->prepare('INSERT INTO pass (libelle, nb_soirees, tarif_normal, tarif_reduit) VALUES (?, ?, ?, ?)')
    ->execute(['Pass 3 jours', PASS_NB_SOIREES, PASS_NORMAL, PASS_REDUIT]);
$passId = (int) $pdo->lastInsertId();

// ---------- ventes simulees ----------

$insCommande = $pdo->prepare('INSERT INTO commandes (id, utilisateur_id, statut, total, carte_fin, cree_le, payee_le) VALUES (?, ?, \'PAYEE\', ?, ?, ?, ?)');
$insLigne = $pdo->prepare('INSERT INTO lignes_commande (commande_id, type, soiree_id, pass_id, categorie, quantite, prix_unitaire) VALUES (?, ?, ?, ?, ?, ?, ?)');
$insLignePass = $pdo->prepare('INSERT INTO ligne_pass_soirees (ligne_id, soiree_id) VALUES (?, ?)');
$insBillet = $pdo->prepare('INSERT INTO billets (token, reference, commande_id, type, soiree_id, pass_id, categorie, prix_paye, nom_acheteur, emis_le) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$insBilletPass = $pdo->prepare('INSERT INTO billet_pass_soirees (billet_id, soiree_id) VALUES (?, ?)');
$majVendues = $pdo->prepare('UPDATE soirees SET places_vendues = ? WHERE id = ?');

$prix = [
    'SOIREE' => ['NORMAL' => TARIF_NORMAL, 'REDUIT' => TARIF_REDUIT],
    'PASS' => ['NORMAL' => PASS_NORMAL, 'REDUIT' => PASS_REDUIT],
];

// cree une commande payee
// $lignes = [[type, [index soirees], categorie, quantite], ...] : 1 soiree pour SOIREE, 3 pour PASS
$vendre = static function (array $lignes) use (&$soirees, $faker, $clients, $prix, $passId, $pdo, $insCommande, $insLigne, $insLignePass, $insBillet, $insBilletPass): void {
    $client = $faker->randomElement($clients);
    $commandeId = uuid4();
    $date = $faker->dateTimeBetween('2026-09-01', '2026-10-19')->format('Y-m-d H:i:s');

    $total = 0;
    foreach ($lignes as [$type, , $categorie, $quantite]) {
        $total += $quantite * $prix[$type][$categorie];
    }
    $insCommande->execute([$commandeId, $client['id'], $total, $faker->numerify('####'), $date, $date]);

    $numero = 0;
    foreach ($lignes as [$type, $indexes, $categorie, $quantite]) {
        $p = $prix[$type][$categorie];
        $soireeId = $type === 'SOIREE' ? $soirees[$indexes[0]]['id'] : null;
        $lignePass = $type === 'PASS' ? $passId : null;

        $insLigne->execute([$commandeId, $type, $soireeId, $lignePass, $categorie, $quantite, $p]);
        $ligneId = (int) $pdo->lastInsertId();
        if ($type === 'PASS') {
            foreach ($indexes as $i) {
                $insLignePass->execute([$ligneId, $soirees[$i]['id']]);
            }
        }

        for ($b = 0; $b < $quantite; $b++) {
            $numero++;
            $reference = sprintf('NRV-%s-%02d', strtoupper(substr($commandeId, 0, 8)), $numero);
            $insBillet->execute([uuid4(), $reference, $commandeId, $type, $soireeId, $lignePass, $categorie, $p, $client['nom'], $date]);
            $billetId = (int) $pdo->lastInsertId();
            if ($type === 'PASS') {
                foreach ($indexes as $i) {
                    $insBilletPass->execute([$billetId, $soirees[$i]['id']]);
                }
            }
        }

        // un billet pass prend une place dans chacune de ses soirees
        foreach ($indexes as $i) {
            $soirees[$i]['vendues'] += $quantite;
        }
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

// des pass 3 jours : 3 soirees distinctes, 1 ou 2 pass par commande
for ($n = 0; $n < 400; $n++) {
    $choix = $faker->randomElements(array_keys($soirees), PASS_NB_SOIREES);
    sort($choix);
    $quantite = $faker->numberBetween(1, 2);
    $ok = array_filter($choix, static fn (int $i) => $reste($i) >= $quantite);
    if (count($ok) < PASS_NB_SOIREES) {
        continue;
    }
    $categorie = $faker->boolean(70) ? 'NORMAL' : 'REDUIT';
    $vendre([['PASS', $choix, $categorie, $quantite]]);
}

// puis des billets soiree jusqu'a l'objectif
foreach (array_keys($soirees) as $i) {
    while ($reste($i) > 0) {
        $quantite = min($reste($i), $faker->numberBetween(1, 6));
        $reduits = $faker->numberBetween(0, $quantite);
        $lignes = [];
        if ($quantite - $reduits > 0) {
            $lignes[] = ['SOIREE', [$i], 'NORMAL', $quantite - $reduits];
        }
        if ($reduits > 0) {
            $lignes[] = ['SOIREE', [$i], 'REDUIT', $reduits];
        }
        $vendre($lignes);
    }
}

foreach ($soirees as $s) {
    $majVendues->execute([$s['vendues'], $s['id']]);
}

$pdo->commit();

// ---------- resume ----------

$nbBillets = (int) $pdo->query("SELECT COUNT(*) FROM billets WHERE type = 'SOIREE'")->fetchColumn();
$nbPass = (int) $pdo->query("SELECT COUNT(*) FROM billets WHERE type = 'PASS'")->fetchColumn();
$nbCommandes = (int) $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn();
$recette = (int) $pdo->query('SELECT COALESCE(SUM(prix_paye), 0) FROM billets')->fetchColumn();

printf(
    "seed ok : %d lieux, %d styles, %d soirees, %d spectacles, %d artistes, %d utilisateurs, %d commandes, %d billets soiree, %d pass 3 jours, recette %.2f eur\n",
    count($lieux),
    count($styles),
    count($soirees),
    $nbSpectacles,
    count($artistesIds),
    count($clients) + 2,
    $nbCommandes,
    $nbBillets,
    $nbPass,
    $recette / 100
);
echo "comptes de test : admin@nrv.net / admin12345 - client@nrv.net / client12345\n";
