-- nrv.net - schema de la base (mariadb 10.11 / mysql 8)
-- joue automatiquement au premier demarrage du conteneur db
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW  IF EXISTS v_jauges_soirees;
DROP TABLE IF EXISTS billet_pass_soirees, billets, ligne_pass_soirees, lignes_commande,
                     commandes, utilisateurs, pass, spectacle_images, spectacle_artiste,
                     spectacles, soirees, artistes, styles, lieux;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE lieux (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom       VARCHAR(120) NOT NULL,
    adresse   VARCHAR(255) NOT NULL,
    ville     VARCHAR(80)  NOT NULL DEFAULT 'Nancy',
    capacite  INT UNSIGNED NOT NULL,
    CONSTRAINT chk_lieux_capacite CHECK (capacite > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE styles (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle  VARCHAR(60) NOT NULL,
    slug     VARCHAR(60) NOT NULL,
    CONSTRAINT uq_styles_slug UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE artistes (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom  VARCHAR(120) NOT NULL,
    CONSTRAINT uq_artistes_nom UNIQUE (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE soirees (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(120) NOT NULL,
    thematique      VARCHAR(80)  NOT NULL,
    date_soiree     DATE         NOT NULL,
    heure_debut     TIME         NOT NULL,
    lieu_id         INT UNSIGNED NOT NULL,
    jauge           INT UNSIGNED NOT NULL,
    places_vendues  INT UNSIGNED NOT NULL DEFAULT 0,  -- billets soiree + pass
    tarif_normal    INT UNSIGNED NOT NULL,            -- centimes
    tarif_reduit    INT UNSIGNED NOT NULL,            -- centimes
    CONSTRAINT uq_soirees_date UNIQUE (date_soiree),
    CONSTRAINT fk_soirees_lieu FOREIGN KEY (lieu_id)
        REFERENCES lieux (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_soirees_jauge  CHECK (jauge > 0),
    CONSTRAINT chk_soirees_ventes CHECK (places_vendues <= jauge),
    CONSTRAINT chk_soirees_tarifs CHECK (tarif_reduit <= tarif_normal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- un spectacle a un seul style (cle etrangere directe)
CREATE TABLE spectacles (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    soiree_id             INT UNSIGNED NOT NULL,
    style_id              INT UNSIGNED NOT NULL,
    titre                 VARCHAR(150) NOT NULL,
    description           TEXT         NOT NULL,
    video_url             VARCHAR(255) NULL,
    horaire_previsionnel  TIME         NOT NULL,
    CONSTRAINT fk_spectacles_soiree FOREIGN KEY (soiree_id)
        REFERENCES soirees (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_spectacles_style FOREIGN KEY (style_id)
        REFERENCES styles (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_spectacles_style (style_id),
    INDEX idx_spectacles_soiree_horaire (soiree_id, horaire_previsionnel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE spectacle_artiste (
    spectacle_id  INT UNSIGNED NOT NULL,
    artiste_id    INT UNSIGNED NOT NULL,
    ordre         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (spectacle_id, artiste_id),
    CONSTRAINT fk_sa_spectacle FOREIGN KEY (spectacle_id)
        REFERENCES spectacles (id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_artiste FOREIGN KEY (artiste_id)
        REFERENCES artistes (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE spectacle_images (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spectacle_id  INT UNSIGNED NOT NULL,
    url           VARCHAR(255) NOT NULL,
    alt           VARCHAR(150) NOT NULL DEFAULT '',
    ordre         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_images_spectacle FOREIGN KEY (spectacle_id)
        REFERENCES spectacles (id) ON DELETE CASCADE,
    INDEX idx_images_spectacle (spectacle_id, ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- formule pass 3 jours : produit a part avec ses deux tarifs (remise de 3 % deja appliquee)
CREATE TABLE pass (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle       VARCHAR(80)      NOT NULL,
    nb_soirees    TINYINT UNSIGNED NOT NULL DEFAULT 3,
    tarif_normal  INT UNSIGNED     NOT NULL,          -- centimes
    tarif_reduit  INT UNSIGNED     NOT NULL,          -- centimes
    CONSTRAINT chk_pass_nb CHECK (nb_soirees >= 2),
    CONSTRAINT chk_pass_tarifs CHECK (tarif_reduit <= tarif_normal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utilisateurs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(180) NOT NULL,
    nom           VARCHAR(80)  NOT NULL,
    prenom        VARCHAR(80)  NOT NULL,
    mot_de_passe  VARCHAR(255) NOT NULL,              -- hash bcrypt
    role          ENUM('CLIENT','ADMIN') NOT NULL DEFAULT 'CLIENT',
    cree_le       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_utilisateurs_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commandes (
    id              CHAR(36)     NOT NULL PRIMARY KEY,  -- uuid v4
    utilisateur_id  INT UNSIGNED NOT NULL,
    statut          ENUM('EN_ATTENTE','PAYEE','ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE',
    total           INT UNSIGNED NOT NULL,              -- centimes
    carte_fin       CHAR(4)      NULL,
    cree_le         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payee_le        DATETIME     NULL,
    CONSTRAINT fk_commandes_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE RESTRICT,
    INDEX idx_commandes_utilisateur (utilisateur_id, cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- une ligne = n billets identiques : soit pour une soiree, soit un pass sur 3 soirees
CREATE TABLE lignes_commande (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commande_id    CHAR(36)     NOT NULL,
    type           ENUM('SOIREE','PASS') NOT NULL,
    soiree_id      INT UNSIGNED NULL,                   -- rempli si type = SOIREE
    pass_id        INT UNSIGNED NULL,                   -- rempli si type = PASS
    categorie      ENUM('NORMAL','REDUIT') NOT NULL,
    quantite       SMALLINT UNSIGNED NOT NULL,
    prix_unitaire  INT UNSIGNED NOT NULL,
    CONSTRAINT fk_lignes_commande FOREIGN KEY (commande_id)
        REFERENCES commandes (id) ON DELETE CASCADE,
    CONSTRAINT fk_lignes_soiree FOREIGN KEY (soiree_id)
        REFERENCES soirees (id) ON DELETE RESTRICT,
    CONSTRAINT fk_lignes_pass FOREIGN KEY (pass_id)
        REFERENCES pass (id) ON DELETE RESTRICT,
    CONSTRAINT chk_lignes_quantite CHECK (quantite BETWEEN 1 AND 10),
    CONSTRAINT chk_lignes_type CHECK (
        (type = 'SOIREE' AND soiree_id IS NOT NULL AND pass_id IS NULL)
        OR (type = 'PASS' AND soiree_id IS NULL AND pass_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- les 3 soirees choisies pour une ligne de pass
CREATE TABLE ligne_pass_soirees (
    ligne_id   INT UNSIGNED NOT NULL,
    soiree_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (ligne_id, soiree_id),
    CONSTRAINT fk_lps_ligne FOREIGN KEY (ligne_id)
        REFERENCES lignes_commande (id) ON DELETE CASCADE,
    CONSTRAINT fk_lps_soiree FOREIGN KEY (soiree_id)
        REFERENCES soirees (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- billet soiree (1 soiree) ou billet pass 3 jours (3 soirees dans billet_pass_soirees)
CREATE TABLE billets (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token         CHAR(36)     NOT NULL,                -- uuid v4 aleatoire
    reference     VARCHAR(40)  NOT NULL,
    commande_id   CHAR(36)     NOT NULL,
    type          ENUM('SOIREE','PASS') NOT NULL,
    soiree_id     INT UNSIGNED NULL,                    -- rempli si type = SOIREE
    pass_id       INT UNSIGNED NULL,                    -- rempli si type = PASS
    categorie     ENUM('NORMAL','REDUIT') NOT NULL,
    prix_paye     INT UNSIGNED NOT NULL,                -- centimes
    nom_acheteur  VARCHAR(170) NOT NULL,                -- fige a l'emission
    emis_le       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_billets_token UNIQUE (token),
    CONSTRAINT uq_billets_reference UNIQUE (reference),
    CONSTRAINT fk_billets_commande FOREIGN KEY (commande_id)
        REFERENCES commandes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_billets_soiree FOREIGN KEY (soiree_id)
        REFERENCES soirees (id) ON DELETE RESTRICT,
    CONSTRAINT fk_billets_pass FOREIGN KEY (pass_id)
        REFERENCES pass (id) ON DELETE RESTRICT,
    CONSTRAINT chk_billets_type CHECK (
        (type = 'SOIREE' AND soiree_id IS NOT NULL AND pass_id IS NULL)
        OR (type = 'PASS' AND soiree_id IS NULL AND pass_id IS NOT NULL)
    ),
    INDEX idx_billets_soiree (soiree_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- les 3 soirees d'un billet pass (cle primaire = soirees distinctes)
CREATE TABLE billet_pass_soirees (
    billet_id  INT UNSIGNED NOT NULL,
    soiree_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (billet_id, soiree_id),
    CONSTRAINT fk_bps_billet FOREIGN KEY (billet_id)
        REFERENCES billets (id) ON DELETE CASCADE,
    CONSTRAINT fk_bps_soiree FOREIGN KEY (soiree_id)
        REFERENCES soirees (id) ON DELETE RESTRICT,
    INDEX idx_bps_soiree (soiree_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vue pour le backoffice : jauge et recette par soiree
-- la recette d'un pass est repartie a parts egales sur ses soirees
CREATE VIEW v_jauges_soirees AS
SELECT  s.id,
        s.nom,
        s.date_soiree,
        s.heure_debut,
        l.nom                                      AS lieu,
        s.jauge,
        s.places_vendues,
        s.jauge - s.places_vendues                 AS places_restantes,
        ROUND(100 * s.places_vendues / s.jauge, 1) AS taux_remplissage,
        (SELECT COUNT(*) FROM billets b WHERE b.soiree_id = s.id)            AS billets_soiree,
        (SELECT COUNT(*) FROM billet_pass_soirees bp WHERE bp.soiree_id = s.id) AS billets_pass,
        ROUND(
            COALESCE((SELECT SUM(b.prix_paye) FROM billets b WHERE b.soiree_id = s.id), 0)
          + COALESCE((SELECT SUM(b.prix_paye / p.nb_soirees)
                      FROM billet_pass_soirees bp
                      JOIN billets b ON b.id = bp.billet_id
                      JOIN pass p    ON p.id = b.pass_id
                      WHERE bp.soiree_id = s.id), 0)
        )                                          AS recette
FROM    soirees s
JOIN    lieux   l ON l.id = s.lieu_id;
