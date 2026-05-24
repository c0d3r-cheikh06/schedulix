SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `emploi_du_temps`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `emploi_du_temps`;

CREATE TABLE IF NOT EXISTS `niveaux` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(50)   NOT NULL,
  `ordre`       INT(11)       NOT NULL DEFAULT 0,
  `description` VARCHAR(255)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_niveau_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `classes` (
  `id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `nom`       VARCHAR(100) NOT NULL,
  `niveau` VARCHAR(50) NOT NULL DEFAULT 'Troisieme',
  `capacite`  INT(11)      NOT NULL DEFAULT 30,
  `id_niveau` INT(11)      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_classe_nom` (`nom`),
  KEY `fk_classe_niveau` (`id_niveau`),
  CONSTRAINT `fk_classe_niveau`
    FOREIGN KEY (`id_niveau`) REFERENCES `niveaux` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `salles` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `nom`      VARCHAR(100) NOT NULL,
  `capacite` INT(11)      NOT NULL DEFAULT 30,
  `type`     ENUM('normale','informatique','laboratoire') NOT NULL DEFAULT 'normale',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_salle_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `matieres` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `nom`               VARCHAR(100) NOT NULL,
  `couleur_hex`       VARCHAR(7)   NOT NULL DEFAULT '#1A56DB',
  `nb_heures_semaine` INT(11)      NOT NULL DEFAULT 2,
  `description`       TEXT         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_matiere_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `nom`           VARCHAR(100) NOT NULL,
  `prenom`        VARCHAR(100) NOT NULL,
  `email`         VARCHAR(191) NOT NULL,
  `mot_de_passe`  VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','professeur','eleve') NOT NULL DEFAULT 'eleve',
  `statut`        ENUM('actif','inactif')             NOT NULL DEFAULT 'actif',
  `id_classe`     INT(11)      DEFAULT NULL,
  `date_creation` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `id_classe` (`id_classe`),
  CONSTRAINT `utilisateurs_ibfk_1`
    FOREIGN KEY (`id_classe`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `professeur_matiere` (
  `id_professeur` INT(11) NOT NULL,
  `id_matiere`    INT(11) NOT NULL,
  PRIMARY KEY (`id_professeur`,`id_matiere`),
  KEY `id_matiere` (`id_matiere`),
  CONSTRAINT `professeur_matiere_ibfk_1`
    FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professeur_matiere_ibfk_2`
    FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `professeur_niveau` (
  `id_professeur` INT(11) NOT NULL,
  `id_niveau`     INT(11) NOT NULL,
  PRIMARY KEY (`id_professeur`,`id_niveau`),
  KEY `id_niveau` (`id_niveau`),
  CONSTRAINT `professeur_niveau_ibfk_1`
    FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professeur_niveau_ibfk_2`
    FOREIGN KEY (`id_niveau`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `creneaux` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `jour`        ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut` TIME    NOT NULL,
  `heure_fin`   TIME    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_creneau` (`jour`,`heure_debut`,`heure_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `emplois_du_temps` (
  `id`                     INT(11)  NOT NULL AUTO_INCREMENT,
  `version`                INT(11)  NOT NULL DEFAULT 1,
  `id_classe`              INT(11)  NOT NULL,
  `id_matiere`             INT(11)  NOT NULL,
  `id_professeur`          INT(11)  NOT NULL,
  `id_salle`               INT(11)  NOT NULL,
  `id_creneau`             INT(11)  NOT NULL,
  `statut`                 ENUM('provisoire','valide','rejete','confirme') NOT NULL DEFAULT 'provisoire',
  `commentaire_validation` TEXT     DEFAULT NULL,
  `date_creation`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_classe`     (`id_classe`),
  KEY `id_matiere`    (`id_matiere`),
  KEY `id_professeur` (`id_professeur`),
  KEY `id_salle`      (`id_salle`),
  KEY `id_creneau`    (`id_creneau`),
  CONSTRAINT `emplois_du_temps_ibfk_1`
    FOREIGN KEY (`id_classe`)     REFERENCES `classes`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `emplois_du_temps_ibfk_2`
    FOREIGN KEY (`id_matiere`)    REFERENCES `matieres`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `emplois_du_temps_ibfk_3`
    FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emplois_du_temps_ibfk_4`
    FOREIGN KEY (`id_salle`)      REFERENCES `salles`       (`id`) ON DELETE CASCADE,
  CONSTRAINT `emplois_du_temps_ibfk_5`
    FOREIGN KEY (`id_creneau`)    REFERENCES `creneaux`     (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `disponibilites` (
  `id`            INT(11)  NOT NULL AUTO_INCREMENT,
  `id_professeur` INT(11)  NOT NULL,
  `jour`          ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut`   TIME     NOT NULL,
  `heure_fin`     TIME     NOT NULL,
  `disponible`    TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `id_professeur` (`id_professeur`),
  CONSTRAINT `disponibilites_ibfk_1`
    FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `messages` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `id_expediteur` INT(11)      NOT NULL,
  `id_classe`     INT(11)      NOT NULL,
  `sujet`         VARCHAR(191) NOT NULL,
  `contenu`       TEXT         NOT NULL,
  `type`          ENUM('info','absence','devoir','report','autre') NOT NULL DEFAULT 'info',
  `date_envoi`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_expediteur` (`id_expediteur`),
  KEY `id_classe`     (`id_classe`),
  CONSTRAINT `messages_ibfk_1`
    FOREIGN KEY (`id_expediteur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2`
    FOREIGN KEY (`id_classe`)     REFERENCES `classes`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `message_lu` (
  `id_message` INT(11)   NOT NULL,
  `id_eleve`   INT(11)   NOT NULL,
  `lu_le`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_message`,`id_eleve`),
  KEY `id_eleve` (`id_eleve`),
  CONSTRAINT `message_lu_ibfk_1`
    FOREIGN KEY (`id_message`) REFERENCES `messages`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_lu_ibfk_2`
    FOREIGN KEY (`id_eleve`)   REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `id_utilisateur`  INT(11)      NOT NULL,
  `titre`           VARCHAR(191) NOT NULL,
  `message`         TEXT         NOT NULL,
  `type`            ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `est_lu`          TINYINT(1)   NOT NULL DEFAULT 0,
  `date_envoi`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`),
  CONSTRAINT `notifications_ibfk_1`
    FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `niveaux` (`id`,`nom`,`ordre`,`description`) VALUES
(1,'Sixieme',  1, NULL),
(2,'Cinquieme',2, NULL),
(3,'Quatrieme',3, NULL),
(4,'Troisieme',4, NULL),
(5,'Seconde',  5, NULL),
(6,'Premiere', 6, NULL),
(7,'Terminale',7, NULL);

INSERT INTO `classes` (`id`,`nom`,`niveau`,`capacite`,`id_niveau`) VALUES
(1, '3eme A',      'Troisieme', 35, 4),
(2, '3eme B',      'Troisieme', 35, 4),
(3, '4eme A',      'Quatrieme', 33, 3),
(4, '4eme B',      'Quatrieme', 33, 3),
(5, 'Terminale S', 'Terminale', 30, 7);

INSERT INTO `salles` (`id`,`nom`,`capacite`,`type`) VALUES
(1, 'Salle 101',           35, 'normale'),
(2, 'Salle 102',           35, 'normale'),
(3, 'Salle 201',           33, 'normale'),
(4, 'Salle Informatique A',25, 'informatique'),
(5, 'Laboratoire Sciences', 28,'laboratoire');

INSERT INTO `matieres` (`id`,`nom`,`couleur_hex`,`nb_heures_semaine`,`description`) VALUES
(1, 'Mathematiques',  '#1A56DB', 4, NULL),
(2, 'Francais',       '#7C3AED', 4, NULL),
(3, 'Anglais',        '#059669', 3, NULL),
(4, 'Histoire-Geo',   '#D97706', 2, NULL),
(5, 'Physique-Chimie','#DC2626', 3, NULL),
(6, 'SVT',            '#0D9488', 2, NULL),
(7, 'Education Phys.','#DB2777', 2, NULL),
(8, 'Informatique',   '#6366F1', 2, NULL);

INSERT INTO `utilisateurs` (`id`,`nom`,`prenom`,`email`,`mot_de_passe`,`role`,`statut`,`id_classe`,`date_creation`) VALUES
(1,  'Admin',   'Super',    'admin@ecole.fr',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin','actif', NULL, '2026-05-21 21:39:42'),

(2,  'Thioye',  'Babacar',  'Babacar.Thioye@ecole.fr',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'professeur','actif', NULL, '2026-05-21 21:39:42'),

(3,  'Diallo',  'Aissatou', 'aissatou.diallo@ecole.fr',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'professeur','actif', NULL, '2026-05-21 21:39:42'),

(4,  'Ndiaye',  'Ibrahima', 'ibrahima.ndiaye@ecole.fr',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'professeur','actif', NULL, '2026-05-21 21:39:42'),

(13, 'codeur',  'anonym',   'anonym@gmail.com',
 '$2y$10$ggpHAZRBN5gEbQQqjGjjReKvf3m/zjobUEY2KaxpCQlrlK0j0QoqS',
 'eleve','actif', 3, '2026-05-21 21:47:38'),

(14, 'Diawara', 'Bamba',    'bamba.diawara@gmail.com',
 '$2y$10$O6qt5DLbraZrN8wu3cM/cuqi5YgSuMkl5BNxbGI.G9xM/dBa/wQ.e',
 'professeur','actif', NULL, '2026-05-21 21:55:29'),

(15, 'fall',    'saliou',   'saliou.fall@gmail.com',
 '$2y$10$Jr4rJ8tWoaQr3d1XhKtan.FLTCRrLX4qz8sS5Kt3819Y59tb5C/w.',
 'eleve','actif', 1, '2026-05-21 22:00:09'),

(16, 'gueye',   'fatou',    'fatou.gueye@gmail.com',
 '$2y$10$8HfELjm/fZMObO1UVEfHOeFgfgyKtAw59MOuDWoYct8LFnEHq8xJi',
 'eleve','actif', 1, '2026-05-22 13:31:37');

INSERT INTO `professeur_matiere` (`id_professeur`,`id_matiere`) VALUES
(2,1),(2,5),
(3,2),(3,3),(3,4),
(4,6),(4,7),(4,8),
(14,1);

INSERT INTO `professeur_niveau` (`id_professeur`,`id_niveau`) VALUES
(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),
(3,1),(3,2),(3,3),(3,4),(3,5),(3,6),(3,7),
(4,1),(4,2),(4,3),(4,4),(4,5),(4,6),(4,7),
(14,1),(14,2),(14,3),(14,4),(14,5),(14,6),(14,7);

INSERT INTO `creneaux` (`id`,`jour`,`heure_debut`,`heure_fin`) VALUES
(1, 'Lundi',   '08:00:00','09:00:00'),
(2, 'Lundi',   '09:00:00','10:00:00'),
(3, 'Lundi',   '10:00:00','11:00:00'),
(4, 'Lundi',   '11:00:00','12:00:00'),
(5, 'Lundi',   '14:00:00','15:00:00'),
(6, 'Lundi',   '15:00:00','16:00:00'),
(7, 'Lundi',   '16:00:00','17:00:00'),
(8, 'Lundi',   '17:00:00','18:00:00'),
(9, 'Mardi',   '08:00:00','09:00:00'),
(10,'Mardi',   '09:00:00','10:00:00'),
(11,'Mardi',   '10:00:00','11:00:00'),
(12,'Mardi',   '11:00:00','12:00:00'),
(13,'Mardi',   '14:00:00','15:00:00'),
(14,'Mardi',   '15:00:00','16:00:00'),
(15,'Mardi',   '16:00:00','17:00:00'),
(16,'Mardi',   '17:00:00','18:00:00'),
(17,'Mercredi','08:00:00','09:00:00'),
(18,'Mercredi','09:00:00','10:00:00'),
(19,'Mercredi','10:00:00','11:00:00'),
(20,'Mercredi','11:00:00','12:00:00'),
(21,'Mercredi','14:00:00','15:00:00'),
(22,'Mercredi','15:00:00','16:00:00'),
(23,'Mercredi','16:00:00','17:00:00'),
(24,'Mercredi','17:00:00','18:00:00'),
(25,'Jeudi',   '08:00:00','09:00:00'),
(26,'Jeudi',   '09:00:00','10:00:00'),
(27,'Jeudi',   '10:00:00','11:00:00'),
(28,'Jeudi',   '11:00:00','12:00:00'),
(29,'Jeudi',   '14:00:00','15:00:00'),
(30,'Jeudi',   '15:00:00','16:00:00'),
(31,'Jeudi',   '16:00:00','17:00:00'),
(32,'Jeudi',   '17:00:00','18:00:00'),
(33,'Vendredi','08:00:00','09:00:00'),
(34,'Vendredi','09:00:00','10:00:00'),
(35,'Vendredi','10:00:00','11:00:00'),
(36,'Vendredi','11:00:00','12:00:00'),
(37,'Vendredi','14:00:00','15:00:00'),
(38,'Vendredi','15:00:00','16:00:00'),
(39,'Vendredi','16:00:00','17:00:00'),
(40,'Vendredi','17:00:00','18:00:00');

INSERT INTO `disponibilites` (`id`,`id_professeur`,`jour`,`heure_debut`,`heure_fin`,`disponible`) VALUES
(1,14,'Lundi',  '08:00:00','10:00:00',1),
(2,14,'Lundi',  '10:00:00','12:00:00',1),
(3,14,'Mardi',  '08:00:00','10:00:00',1);

INSERT INTO `emplois_du_temps`
  (`id`,`version`,`id_classe`,`id_matiere`,`id_professeur`,`id_salle`,`id_creneau`,`statut`,`commentaire_validation`,`date_creation`)
VALUES
(251,4,3,1,2,1,1,'provisoire',NULL,'2026-05-22 13:33:49'),
(252,4,3,1,2,1,2,'provisoire',NULL,'2026-05-22 13:33:49'),
(253,4,3,1,2,1,3,'provisoire',NULL,'2026-05-22 13:33:49'),
(254,4,3,1,2,1,4,'provisoire',NULL,'2026-05-22 13:33:49'),
(255,4,3,5,2,1,5,'provisoire',NULL,'2026-05-22 13:33:49'),
(256,4,3,5,2,1,6,'provisoire',NULL,'2026-05-22 13:33:49'),
(257,4,3,5,2,1,7,'provisoire',NULL,'2026-05-22 13:33:49'),
(258,4,3,2,3,1,8,'provisoire',NULL,'2026-05-22 13:33:49'),
(259,4,3,2,3,1,9,'provisoire',NULL,'2026-05-22 13:33:49'),
(260,4,3,2,3,1,10,'provisoire',NULL,'2026-05-22 13:33:49'),
(261,4,3,2,3,1,11,'provisoire',NULL,'2026-05-22 13:33:49'),
(262,4,3,3,3,1,12,'provisoire',NULL,'2026-05-22 13:33:49'),
(263,4,3,3,3,1,13,'provisoire',NULL,'2026-05-22 13:33:49'),
(264,4,3,3,3,1,14,'provisoire',NULL,'2026-05-22 13:33:49'),
(265,4,3,4,3,1,15,'provisoire',NULL,'2026-05-22 13:33:49'),
(266,4,3,4,3,1,16,'provisoire',NULL,'2026-05-22 13:33:49'),
(267,4,3,6,4,1,17,'provisoire',NULL,'2026-05-22 13:33:49'),
(268,4,3,6,4,1,18,'provisoire',NULL,'2026-05-22 13:33:49'),
(269,4,3,7,4,1,19,'provisoire',NULL,'2026-05-22 13:33:49'),
(270,4,3,7,4,1,20,'provisoire',NULL,'2026-05-22 13:33:49'),
(271,4,3,8,4,1,21,'provisoire',NULL,'2026-05-22 13:33:50'),
(272,4,3,8,4,1,22,'provisoire',NULL,'2026-05-22 13:33:50'),
(273,4,4,1,2,2,8,'provisoire',NULL,'2026-05-22 13:33:50'),
(274,4,4,1,2,2,9,'provisoire',NULL,'2026-05-22 13:33:50'),
(275,4,4,1,2,2,10,'provisoire',NULL,'2026-05-22 13:33:50'),
(276,4,4,1,2,2,11,'provisoire',NULL,'2026-05-22 13:33:50'),
(277,4,4,5,2,2,12,'provisoire',NULL,'2026-05-22 13:33:50'),
(278,4,4,5,2,2,13,'provisoire',NULL,'2026-05-22 13:33:50'),
(279,4,4,5,2,2,14,'provisoire',NULL,'2026-05-22 13:33:50'),
(280,4,4,2,3,2,1,'provisoire',NULL,'2026-05-22 13:33:50'),
(281,4,4,2,3,2,2,'provisoire',NULL,'2026-05-22 13:33:50'),
(282,4,4,2,3,2,3,'provisoire',NULL,'2026-05-22 13:33:50'),
(283,4,4,2,3,2,4,'provisoire',NULL,'2026-05-22 13:33:50'),
(284,4,4,3,3,2,5,'provisoire',NULL,'2026-05-22 13:33:50'),
(285,4,4,3,3,2,6,'provisoire',NULL,'2026-05-22 13:33:50'),
(286,4,4,3,3,2,7,'provisoire',NULL,'2026-05-22 13:33:50'),
(287,4,4,4,3,2,17,'provisoire',NULL,'2026-05-22 13:33:50'),
(288,4,4,4,3,2,18,'provisoire',NULL,'2026-05-22 13:33:50'),
(289,4,4,6,4,2,15,'provisoire',NULL,'2026-05-22 13:33:50'),
(290,4,4,6,4,2,16,'provisoire',NULL,'2026-05-22 13:33:50'),
(291,4,4,7,4,1,23,'provisoire',NULL,'2026-05-22 13:33:50'),
(292,4,4,7,4,1,24,'provisoire',NULL,'2026-05-22 13:33:50'),
(293,4,4,8,4,1,25,'provisoire',NULL,'2026-05-22 13:33:50'),
(294,4,4,8,4,1,26,'provisoire',NULL,'2026-05-22 13:33:50'),
(295,4,1,1,2,3,15,'provisoire',NULL,'2026-05-22 13:33:50'),
(296,4,1,1,2,3,16,'provisoire',NULL,'2026-05-22 13:33:50'),
(297,4,1,1,2,3,17,'provisoire',NULL,'2026-05-22 13:33:50'),
(298,4,1,1,2,3,18,'provisoire',NULL,'2026-05-22 13:33:50'),
(299,4,1,5,2,2,19,'provisoire',NULL,'2026-05-22 13:33:50'),
(300,4,1,5,2,2,20,'provisoire',NULL,'2026-05-22 13:33:50'),
(301,4,1,5,2,2,21,'provisoire',NULL,'2026-05-22 13:33:50'),
(302,4,1,2,3,2,22,'provisoire',NULL,'2026-05-22 13:33:50'),
(303,4,1,2,3,2,23,'provisoire',NULL,'2026-05-22 13:33:50'),
(304,4,1,2,3,2,24,'provisoire',NULL,'2026-05-22 13:33:50'),
(305,4,1,2,3,2,25,'provisoire',NULL,'2026-05-22 13:33:50'),
(306,4,1,3,3,2,26,'provisoire',NULL,'2026-05-22 13:33:50'),
(307,4,1,3,3,1,27,'provisoire',NULL,'2026-05-22 13:33:50'),
(308,4,1,3,3,1,28,'provisoire',NULL,'2026-05-22 13:33:50'),
(309,4,1,4,3,1,29,'provisoire',NULL,'2026-05-22 13:33:50'),
(310,4,1,4,3,1,30,'provisoire',NULL,'2026-05-22 13:33:50'),
(311,4,1,6,4,3,1,'provisoire',NULL,'2026-05-22 13:33:50'),
(312,4,1,6,4,3,2,'provisoire',NULL,'2026-05-22 13:33:50'),
(313,4,1,7,4,3,3,'provisoire',NULL,'2026-05-22 13:33:50'),
(314,4,1,7,4,3,4,'provisoire',NULL,'2026-05-22 13:33:50'),
(315,4,1,8,4,3,5,'provisoire',NULL,'2026-05-22 13:33:50'),
(316,4,1,8,4,3,6,'provisoire',NULL,'2026-05-22 13:33:50'),
(317,4,1,1,14,3,9,'valide','','2026-05-22 13:33:50'),
(318,4,1,1,14,3,10,'valide','','2026-05-22 13:33:50'),
(319,4,2,1,2,3,22,'provisoire',NULL,'2026-05-22 13:33:50'),
(320,4,2,1,2,3,23,'provisoire',NULL,'2026-05-22 13:33:50'),
(321,4,2,1,2,3,24,'provisoire',NULL,'2026-05-22 13:33:50'),
(322,4,2,1,2,3,25,'provisoire',NULL,'2026-05-22 13:33:50'),
(323,4,2,5,2,3,26,'provisoire',NULL,'2026-05-22 13:33:51'),
(324,4,2,5,2,2,27,'provisoire',NULL,'2026-05-22 13:33:51'),
(325,4,2,5,2,2,28,'provisoire',NULL,'2026-05-22 13:33:51'),
(326,4,2,2,3,3,19,'provisoire',NULL,'2026-05-22 13:33:51'),
(327,4,2,2,3,3,20,'provisoire',NULL,'2026-05-22 13:33:51'),
(328,4,2,2,3,3,21,'provisoire',NULL,'2026-05-22 13:33:51'),
(329,4,2,2,3,1,31,'provisoire',NULL,'2026-05-22 13:33:51'),
(330,4,2,3,3,1,32,'provisoire',NULL,'2026-05-22 13:33:51'),
(331,4,2,3,3,1,33,'provisoire',NULL,'2026-05-22 13:33:51'),
(332,4,2,3,3,1,34,'provisoire',NULL,'2026-05-22 13:33:51'),
(333,4,2,4,3,1,35,'provisoire',NULL,'2026-05-22 13:33:51'),
(334,4,2,4,3,1,36,'provisoire',NULL,'2026-05-22 13:33:51'),
(335,4,2,6,4,3,7,'provisoire',NULL,'2026-05-22 13:33:51'),
(336,4,2,6,4,3,8,'provisoire',NULL,'2026-05-22 13:33:51'),
(337,4,2,7,4,3,11,'provisoire',NULL,'2026-05-22 13:33:51'),
(338,4,2,7,4,3,12,'provisoire',NULL,'2026-05-22 13:33:51'),
(339,4,2,8,4,3,13,'provisoire',NULL,'2026-05-22 13:33:51'),
(340,4,2,8,4,3,14,'provisoire',NULL,'2026-05-22 13:33:51'),
(341,4,5,1,2,2,29,'provisoire',NULL,'2026-05-22 13:33:51'),
(342,4,5,1,2,2,30,'provisoire',NULL,'2026-05-22 13:33:51'),
(343,4,5,1,2,2,31,'provisoire',NULL,'2026-05-22 13:33:51'),
(344,4,5,1,2,2,32,'provisoire',NULL,'2026-05-22 13:33:51'),
(345,4,5,5,2,2,33,'provisoire',NULL,'2026-05-22 13:33:51'),
(346,4,5,5,2,2,34,'provisoire',NULL,'2026-05-22 13:33:51'),
(347,4,5,5,2,2,35,'provisoire',NULL,'2026-05-22 13:33:51'),
(348,4,5,2,3,1,37,'provisoire',NULL,'2026-05-22 13:33:51'),
(349,4,5,2,3,1,38,'provisoire',NULL,'2026-05-22 13:33:51'),
(350,4,5,2,3,1,39,'provisoire',NULL,'2026-05-22 13:33:51'),
(351,4,5,2,3,1,40,'provisoire',NULL,'2026-05-22 13:33:51'),
(352,4,5,6,4,3,27,'provisoire',NULL,'2026-05-22 13:33:51'),
(353,4,5,6,4,3,28,'provisoire',NULL,'2026-05-22 13:33:51'),
(354,4,5,7,4,2,36,'provisoire',NULL,'2026-05-22 13:33:51');

INSERT INTO `messages` (`id`,`id_expediteur`,`id_classe`,`sujet`,`contenu`,`type`,`date_envoi`) VALUES
(1, 14, 1, 'absence demain', 'demain je serais absent', 'absence', '2026-05-22 13:29:32');

INSERT INTO `message_lu` (`id_message`,`id_eleve`,`lu_le`) VALUES
(1, 16, '2026-05-22 13:32:03');

INSERT INTO `notifications` (`id`,`id_utilisateur`,`titre`,`message`,`type`,`est_lu`,`date_envoi`) VALUES
(1, 2,  'Nouveau planning disponible','L\'emploi du temps version 1 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-21 21:53:29'),
(2, 3,  'Nouveau planning disponible','L\'emploi du temps version 1 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-21 21:53:29'),
(3, 4,  'Nouveau planning disponible','L\'emploi du temps version 1 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-21 21:53:29'),
(4, 1,  'Generation terminee (v1)','104 creneau(x) generes.','success',0,'2026-05-21 21:53:29'),
(5, 14, 'Bienvenue !','Votre compte professeur a ete cree par l\'administrateur.','info',0,'2026-05-21 21:55:29'),
(6, 2,  'Nouveau planning disponible','L\'emploi du temps version 2 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-21 21:58:20'),
(7, 3,  'Nouveau planning disponible','L\'emploi du temps version 2 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-21 21:58:20'),
(8, 4,  'Nouveau planning disponible','L\'emploi du temps version 2 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-21 21:58:20'),
(9, 14, 'Nouveau planning disponible','L\'emploi du temps version 2 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-21 21:58:20'),
(10,1,  'Generation terminee (v2)','124 creneau(x) generes.','success',0,'2026-05-21 21:58:20'),
(11,15, 'Message de Bamba Diawara','absence demain','info',0,'2026-05-22 13:29:32'),
(12,2,  'Nouveau planning v3','L\'emploi du temps version 3 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-22 13:32:45'),
(13,3,  'Nouveau planning v3','L\'emploi du temps version 3 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-22 13:32:45'),
(14,4,  'Nouveau planning v3','L\'emploi du temps version 3 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-22 13:32:45'),
(15,1,  'Generation terminee (v3)','22 creneaux generes. 18 ignores (disponibilites).','success',0,'2026-05-22 13:32:45'),
(16,2,  'Nouveau planning v4','L\'emploi du temps version 4 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-22 13:33:51'),
(17,3,  'Nouveau planning v4','L\'emploi du temps version 4 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-22 13:33:51'),
(18,4,  'Nouveau planning v4','L\'emploi du temps version 4 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-22 13:33:51'),
(19,14, 'Nouveau planning v4','L\'emploi du temps version 4 a ete genere. Veuillez valider vos creneaux.','info',0,'2026-05-22 13:33:51'),
(20,1,  'Generation terminee (v4)','104 creneaux generes. 18 conflits. 84 ignores (disponibilites).','success',0,'2026-05-22 13:33:51');

ALTER TABLE `niveaux`          AUTO_INCREMENT = 8;
ALTER TABLE `classes`          AUTO_INCREMENT = 12;
ALTER TABLE `salles`           AUTO_INCREMENT = 12;
ALTER TABLE `matieres`         AUTO_INCREMENT = 18;
ALTER TABLE `utilisateurs`     AUTO_INCREMENT = 17;
ALTER TABLE `creneaux`         AUTO_INCREMENT = 41;
ALTER TABLE `disponibilites`   AUTO_INCREMENT = 4;
ALTER TABLE `emplois_du_temps` AUTO_INCREMENT = 355;
ALTER TABLE `messages`         AUTO_INCREMENT = 2;
ALTER TABLE `notifications`    AUTO_INCREMENT = 21;

COMMIT;
