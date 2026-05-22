-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 22 mai 2026 à 23:11
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `emploi_du_temps`
--

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `niveau` varchar(50) NOT NULL DEFAULT 'Troisième',
  `capacite` int(11) NOT NULL DEFAULT 30,
  `id_niveau` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`id`, `nom`, `niveau`, `capacite`, `id_niveau`) VALUES
(1, '3ème A', 'Troisième', 35, 4),
(2, '3ème B', 'Troisième', 35, 4),
(3, '4ème A', 'Quatrième', 33, 3),
(4, '4ème B', 'Quatrième', 33, 3),
(5, 'Terminale S', 'Terminale', 30, 7);

-- --------------------------------------------------------

--
-- Structure de la table `creneaux`
--

CREATE TABLE `creneaux` (
  `id` int(11) NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `creneaux`
--

INSERT INTO `creneaux` (`id`, `jour`, `heure_debut`, `heure_fin`) VALUES
(1, 'Lundi', '08:00:00', '09:00:00'),
(2, 'Lundi', '09:00:00', '10:00:00'),
(3, 'Lundi', '10:00:00', '11:00:00'),
(4, 'Lundi', '11:00:00', '12:00:00'),
(5, 'Lundi', '14:00:00', '15:00:00'),
(6, 'Lundi', '15:00:00', '16:00:00'),
(7, 'Lundi', '16:00:00', '17:00:00'),
(8, 'Lundi', '17:00:00', '18:00:00'),
(9, 'Mardi', '08:00:00', '09:00:00'),
(10, 'Mardi', '09:00:00', '10:00:00'),
(11, 'Mardi', '10:00:00', '11:00:00'),
(12, 'Mardi', '11:00:00', '12:00:00'),
(13, 'Mardi', '14:00:00', '15:00:00'),
(14, 'Mardi', '15:00:00', '16:00:00'),
(15, 'Mardi', '16:00:00', '17:00:00'),
(16, 'Mardi', '17:00:00', '18:00:00'),
(17, 'Mercredi', '08:00:00', '09:00:00'),
(18, 'Mercredi', '09:00:00', '10:00:00'),
(19, 'Mercredi', '10:00:00', '11:00:00'),
(20, 'Mercredi', '11:00:00', '12:00:00'),
(21, 'Mercredi', '14:00:00', '15:00:00'),
(22, 'Mercredi', '15:00:00', '16:00:00'),
(23, 'Mercredi', '16:00:00', '17:00:00'),
(24, 'Mercredi', '17:00:00', '18:00:00'),
(25, 'Jeudi', '08:00:00', '09:00:00'),
(26, 'Jeudi', '09:00:00', '10:00:00'),
(27, 'Jeudi', '10:00:00', '11:00:00'),
(28, 'Jeudi', '11:00:00', '12:00:00'),
(29, 'Jeudi', '14:00:00', '15:00:00'),
(30, 'Jeudi', '15:00:00', '16:00:00'),
(31, 'Jeudi', '16:00:00', '17:00:00'),
(32, 'Jeudi', '17:00:00', '18:00:00'),
(33, 'Vendredi', '08:00:00', '09:00:00'),
(34, 'Vendredi', '09:00:00', '10:00:00'),
(35, 'Vendredi', '10:00:00', '11:00:00'),
(36, 'Vendredi', '11:00:00', '12:00:00'),
(37, 'Vendredi', '14:00:00', '15:00:00'),
(38, 'Vendredi', '15:00:00', '16:00:00'),
(39, 'Vendredi', '16:00:00', '17:00:00'),
(40, 'Vendredi', '17:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `disponibilites`
--

CREATE TABLE `disponibilites` (
  `id` int(11) NOT NULL,
  `id_professeur` int(11) NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `disponibilites`
--

INSERT INTO `disponibilites` (`id`, `id_professeur`, `jour`, `heure_debut`, `heure_fin`, `disponible`) VALUES
(1, 14, 'Lundi', '08:00:00', '10:00:00', 1),
(2, 14, 'Lundi', '10:00:00', '12:00:00', 1),
(3, 14, 'Mardi', '08:00:00', '10:00:00', 1);

-- --------------------------------------------------------

--
-- Structure de la table `emplois_du_temps`
--

CREATE TABLE `emplois_du_temps` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `id_classe` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `id_professeur` int(11) NOT NULL,
  `id_salle` int(11) NOT NULL,
  `id_creneau` int(11) NOT NULL,
  `statut` enum('provisoire','valide','rejete','confirme') NOT NULL DEFAULT 'provisoire',
  `commentaire_validation` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `emplois_du_temps`
--

INSERT INTO `emplois_du_temps` (`id`, `version`, `id_classe`, `id_matiere`, `id_professeur`, `id_salle`, `id_creneau`, `statut`, `commentaire_validation`, `date_creation`) VALUES
(1, 1, 3, 1, 2, 1, 1, 'provisoire', NULL, '2026-05-21 21:53:28'),
(2, 1, 3, 1, 2, 1, 2, 'provisoire', NULL, '2026-05-21 21:53:28'),
(3, 1, 3, 1, 2, 1, 3, 'provisoire', NULL, '2026-05-21 21:53:28'),
(4, 1, 3, 1, 2, 1, 4, 'provisoire', NULL, '2026-05-21 21:53:28'),
(5, 1, 3, 5, 2, 1, 5, 'provisoire', NULL, '2026-05-21 21:53:28'),
(6, 1, 3, 5, 2, 1, 6, 'provisoire', NULL, '2026-05-21 21:53:28'),
(7, 1, 3, 5, 2, 1, 7, 'provisoire', NULL, '2026-05-21 21:53:28'),
(8, 1, 3, 2, 3, 1, 8, 'provisoire', NULL, '2026-05-21 21:53:28'),
(9, 1, 3, 2, 3, 1, 9, 'provisoire', NULL, '2026-05-21 21:53:28'),
(10, 1, 3, 2, 3, 1, 10, 'provisoire', NULL, '2026-05-21 21:53:28'),
(11, 1, 3, 2, 3, 1, 11, 'provisoire', NULL, '2026-05-21 21:53:28'),
(12, 1, 3, 3, 3, 1, 12, 'provisoire', NULL, '2026-05-21 21:53:28'),
(13, 1, 3, 3, 3, 1, 13, 'provisoire', NULL, '2026-05-21 21:53:28'),
(14, 1, 3, 3, 3, 1, 14, 'provisoire', NULL, '2026-05-21 21:53:28'),
(15, 1, 3, 4, 3, 1, 15, 'provisoire', NULL, '2026-05-21 21:53:28'),
(16, 1, 3, 4, 3, 1, 16, 'provisoire', NULL, '2026-05-21 21:53:28'),
(17, 1, 3, 6, 4, 1, 17, 'provisoire', NULL, '2026-05-21 21:53:28'),
(18, 1, 3, 6, 4, 1, 18, 'provisoire', NULL, '2026-05-21 21:53:28'),
(19, 1, 3, 7, 4, 1, 19, 'provisoire', NULL, '2026-05-21 21:53:28'),
(20, 1, 3, 7, 4, 1, 20, 'provisoire', NULL, '2026-05-21 21:53:28'),
(21, 1, 3, 8, 4, 1, 21, 'provisoire', NULL, '2026-05-21 21:53:28'),
(22, 1, 3, 8, 4, 1, 22, 'provisoire', NULL, '2026-05-21 21:53:28'),
(23, 1, 4, 1, 2, 2, 8, 'provisoire', NULL, '2026-05-21 21:53:28'),
(24, 1, 4, 1, 2, 2, 9, 'provisoire', NULL, '2026-05-21 21:53:28'),
(25, 1, 4, 1, 2, 2, 10, 'provisoire', NULL, '2026-05-21 21:53:28'),
(26, 1, 4, 1, 2, 2, 11, 'provisoire', NULL, '2026-05-21 21:53:28'),
(27, 1, 4, 5, 2, 2, 12, 'provisoire', NULL, '2026-05-21 21:53:28'),
(28, 1, 4, 5, 2, 2, 13, 'provisoire', NULL, '2026-05-21 21:53:28'),
(29, 1, 4, 5, 2, 2, 14, 'provisoire', NULL, '2026-05-21 21:53:28'),
(30, 1, 4, 2, 3, 2, 1, 'provisoire', NULL, '2026-05-21 21:53:28'),
(31, 1, 4, 2, 3, 2, 2, 'provisoire', NULL, '2026-05-21 21:53:28'),
(32, 1, 4, 2, 3, 2, 3, 'provisoire', NULL, '2026-05-21 21:53:28'),
(33, 1, 4, 2, 3, 2, 4, 'provisoire', NULL, '2026-05-21 21:53:28'),
(34, 1, 4, 3, 3, 2, 5, 'provisoire', NULL, '2026-05-21 21:53:28'),
(35, 1, 4, 3, 3, 2, 6, 'provisoire', NULL, '2026-05-21 21:53:28'),
(36, 1, 4, 3, 3, 2, 7, 'provisoire', NULL, '2026-05-21 21:53:28'),
(37, 1, 4, 4, 3, 2, 17, 'provisoire', NULL, '2026-05-21 21:53:28'),
(38, 1, 4, 4, 3, 2, 18, 'provisoire', NULL, '2026-05-21 21:53:28'),
(39, 1, 4, 6, 4, 2, 15, 'provisoire', NULL, '2026-05-21 21:53:28'),
(40, 1, 4, 6, 4, 2, 16, 'provisoire', NULL, '2026-05-21 21:53:28'),
(41, 1, 4, 7, 4, 1, 23, 'provisoire', NULL, '2026-05-21 21:53:28'),
(42, 1, 4, 7, 4, 1, 24, 'provisoire', NULL, '2026-05-21 21:53:28'),
(43, 1, 4, 8, 4, 1, 25, 'provisoire', NULL, '2026-05-21 21:53:28'),
(44, 1, 4, 8, 4, 1, 26, 'provisoire', NULL, '2026-05-21 21:53:28'),
(45, 1, 5, 1, 2, 3, 15, 'provisoire', NULL, '2026-05-21 21:53:28'),
(46, 1, 5, 1, 2, 3, 16, 'provisoire', NULL, '2026-05-21 21:53:28'),
(47, 1, 5, 1, 2, 3, 17, 'provisoire', NULL, '2026-05-21 21:53:28'),
(48, 1, 5, 1, 2, 3, 18, 'provisoire', NULL, '2026-05-21 21:53:28'),
(49, 1, 5, 5, 2, 2, 19, 'provisoire', NULL, '2026-05-21 21:53:28'),
(50, 1, 5, 5, 2, 2, 20, 'provisoire', NULL, '2026-05-21 21:53:28'),
(51, 1, 5, 5, 2, 2, 21, 'provisoire', NULL, '2026-05-21 21:53:28'),
(52, 1, 5, 2, 3, 2, 22, 'provisoire', NULL, '2026-05-21 21:53:28'),
(53, 1, 5, 2, 3, 2, 23, 'provisoire', NULL, '2026-05-21 21:53:28'),
(54, 1, 5, 2, 3, 2, 24, 'provisoire', NULL, '2026-05-21 21:53:28'),
(55, 1, 5, 2, 3, 2, 25, 'provisoire', NULL, '2026-05-21 21:53:28'),
(56, 1, 5, 3, 3, 2, 26, 'provisoire', NULL, '2026-05-21 21:53:28'),
(57, 1, 5, 3, 3, 1, 27, 'provisoire', NULL, '2026-05-21 21:53:28'),
(58, 1, 5, 3, 3, 1, 28, 'provisoire', NULL, '2026-05-21 21:53:28'),
(59, 1, 5, 4, 3, 1, 29, 'provisoire', NULL, '2026-05-21 21:53:28'),
(60, 1, 5, 4, 3, 1, 30, 'provisoire', NULL, '2026-05-21 21:53:28'),
(61, 1, 5, 6, 4, 3, 1, 'provisoire', NULL, '2026-05-21 21:53:28'),
(62, 1, 5, 6, 4, 3, 2, 'provisoire', NULL, '2026-05-21 21:53:28'),
(63, 1, 5, 7, 4, 3, 3, 'provisoire', NULL, '2026-05-21 21:53:28'),
(64, 1, 5, 7, 4, 3, 4, 'provisoire', NULL, '2026-05-21 21:53:28'),
(65, 1, 5, 8, 4, 3, 5, 'provisoire', NULL, '2026-05-21 21:53:28'),
(66, 1, 5, 8, 4, 3, 6, 'provisoire', NULL, '2026-05-21 21:53:28'),
(67, 1, 1, 1, 2, 3, 22, 'provisoire', NULL, '2026-05-21 21:53:28'),
(68, 1, 1, 1, 2, 3, 23, 'provisoire', NULL, '2026-05-21 21:53:29'),
(69, 1, 1, 1, 2, 3, 24, 'provisoire', NULL, '2026-05-21 21:53:29'),
(70, 1, 1, 1, 2, 3, 25, 'provisoire', NULL, '2026-05-21 21:53:29'),
(71, 1, 1, 5, 2, 3, 26, 'provisoire', NULL, '2026-05-21 21:53:29'),
(72, 1, 1, 5, 2, 2, 27, 'provisoire', NULL, '2026-05-21 21:53:29'),
(73, 1, 1, 5, 2, 2, 28, 'provisoire', NULL, '2026-05-21 21:53:29'),
(74, 1, 1, 2, 3, 3, 19, 'provisoire', NULL, '2026-05-21 21:53:29'),
(75, 1, 1, 2, 3, 3, 20, 'provisoire', NULL, '2026-05-21 21:53:29'),
(76, 1, 1, 2, 3, 3, 21, 'provisoire', NULL, '2026-05-21 21:53:29'),
(77, 1, 1, 2, 3, 1, 31, 'provisoire', NULL, '2026-05-21 21:53:29'),
(78, 1, 1, 3, 3, 1, 32, 'provisoire', NULL, '2026-05-21 21:53:29'),
(79, 1, 1, 3, 3, 1, 33, 'provisoire', NULL, '2026-05-21 21:53:29'),
(80, 1, 1, 3, 3, 1, 34, 'provisoire', NULL, '2026-05-21 21:53:29'),
(81, 1, 1, 4, 3, 1, 35, 'provisoire', NULL, '2026-05-21 21:53:29'),
(82, 1, 1, 4, 3, 1, 36, 'provisoire', NULL, '2026-05-21 21:53:29'),
(83, 1, 1, 6, 4, 3, 7, 'provisoire', NULL, '2026-05-21 21:53:29'),
(84, 1, 1, 6, 4, 3, 8, 'provisoire', NULL, '2026-05-21 21:53:29'),
(85, 1, 1, 7, 4, 3, 9, 'provisoire', NULL, '2026-05-21 21:53:29'),
(86, 1, 1, 7, 4, 3, 10, 'provisoire', NULL, '2026-05-21 21:53:29'),
(87, 1, 1, 8, 4, 3, 11, 'provisoire', NULL, '2026-05-21 21:53:29'),
(88, 1, 1, 8, 4, 3, 12, 'provisoire', NULL, '2026-05-21 21:53:29'),
(89, 1, 2, 1, 2, 2, 29, 'provisoire', NULL, '2026-05-21 21:53:29'),
(90, 1, 2, 1, 2, 2, 30, 'provisoire', NULL, '2026-05-21 21:53:29'),
(91, 1, 2, 1, 2, 2, 31, 'provisoire', NULL, '2026-05-21 21:53:29'),
(92, 1, 2, 1, 2, 2, 32, 'provisoire', NULL, '2026-05-21 21:53:29'),
(93, 1, 2, 5, 2, 2, 33, 'provisoire', NULL, '2026-05-21 21:53:29'),
(94, 1, 2, 5, 2, 2, 34, 'provisoire', NULL, '2026-05-21 21:53:29'),
(95, 1, 2, 5, 2, 2, 35, 'provisoire', NULL, '2026-05-21 21:53:29'),
(96, 1, 2, 2, 3, 1, 37, 'provisoire', NULL, '2026-05-21 21:53:29'),
(97, 1, 2, 2, 3, 1, 38, 'provisoire', NULL, '2026-05-21 21:53:29'),
(98, 1, 2, 2, 3, 1, 39, 'provisoire', NULL, '2026-05-21 21:53:29'),
(99, 1, 2, 2, 3, 1, 40, 'provisoire', NULL, '2026-05-21 21:53:29'),
(100, 1, 2, 6, 4, 3, 13, 'provisoire', NULL, '2026-05-21 21:53:29'),
(101, 1, 2, 6, 4, 3, 14, 'provisoire', NULL, '2026-05-21 21:53:29'),
(102, 1, 2, 7, 4, 3, 27, 'provisoire', NULL, '2026-05-21 21:53:29'),
(103, 1, 2, 7, 4, 3, 28, 'provisoire', NULL, '2026-05-21 21:53:29'),
(104, 1, 2, 8, 4, 2, 36, 'provisoire', NULL, '2026-05-21 21:53:29'),
(105, 2, 3, 1, 2, 5, 1, 'provisoire', NULL, '2026-05-21 21:58:19'),
(106, 2, 3, 1, 2, 5, 2, 'provisoire', NULL, '2026-05-21 21:58:19'),
(107, 2, 3, 1, 2, 5, 3, 'provisoire', NULL, '2026-05-21 21:58:19'),
(108, 2, 3, 1, 2, 5, 4, 'provisoire', NULL, '2026-05-21 21:58:19'),
(109, 2, 3, 5, 2, 5, 5, 'provisoire', NULL, '2026-05-21 21:58:19'),
(110, 2, 3, 5, 2, 5, 6, 'provisoire', NULL, '2026-05-21 21:58:19'),
(111, 2, 3, 5, 2, 5, 7, 'provisoire', NULL, '2026-05-21 21:58:19'),
(112, 2, 3, 2, 3, 5, 8, 'provisoire', NULL, '2026-05-21 21:58:19'),
(113, 2, 3, 2, 3, 5, 9, 'provisoire', NULL, '2026-05-21 21:58:19'),
(114, 2, 3, 2, 3, 5, 10, 'provisoire', NULL, '2026-05-21 21:58:19'),
(115, 2, 3, 2, 3, 5, 11, 'provisoire', NULL, '2026-05-21 21:58:19'),
(116, 2, 3, 3, 3, 5, 12, 'provisoire', NULL, '2026-05-21 21:58:19'),
(117, 2, 3, 3, 3, 5, 13, 'provisoire', NULL, '2026-05-21 21:58:19'),
(118, 2, 3, 3, 3, 5, 14, 'provisoire', NULL, '2026-05-21 21:58:19'),
(119, 2, 3, 4, 3, 5, 15, 'provisoire', NULL, '2026-05-21 21:58:19'),
(120, 2, 3, 4, 3, 5, 16, 'provisoire', NULL, '2026-05-21 21:58:19'),
(121, 2, 3, 6, 4, 5, 17, 'provisoire', NULL, '2026-05-21 21:58:19'),
(122, 2, 3, 6, 4, 5, 18, 'provisoire', NULL, '2026-05-21 21:58:19'),
(123, 2, 3, 7, 4, 5, 19, 'provisoire', NULL, '2026-05-21 21:58:19'),
(124, 2, 3, 7, 4, 5, 20, 'provisoire', NULL, '2026-05-21 21:58:19'),
(125, 2, 3, 8, 4, 5, 21, 'provisoire', NULL, '2026-05-21 21:58:19'),
(126, 2, 3, 8, 4, 5, 22, 'provisoire', NULL, '2026-05-21 21:58:19'),
(127, 2, 3, 1, 14, 5, 23, 'provisoire', NULL, '2026-05-21 21:58:19'),
(128, 2, 3, 1, 14, 5, 24, 'provisoire', NULL, '2026-05-21 21:58:19'),
(129, 2, 3, 1, 14, 5, 25, 'provisoire', NULL, '2026-05-21 21:58:19'),
(130, 2, 3, 1, 14, 5, 26, 'provisoire', NULL, '2026-05-21 21:58:19'),
(131, 2, 4, 1, 2, 1, 8, 'provisoire', NULL, '2026-05-21 21:58:19'),
(132, 2, 4, 1, 2, 1, 9, 'provisoire', NULL, '2026-05-21 21:58:19'),
(133, 2, 4, 1, 2, 1, 10, 'provisoire', NULL, '2026-05-21 21:58:19'),
(134, 2, 4, 1, 2, 1, 11, 'provisoire', NULL, '2026-05-21 21:58:19'),
(135, 2, 4, 5, 2, 1, 12, 'provisoire', NULL, '2026-05-21 21:58:19'),
(136, 2, 4, 5, 2, 1, 13, 'provisoire', NULL, '2026-05-21 21:58:19'),
(137, 2, 4, 5, 2, 1, 14, 'provisoire', NULL, '2026-05-21 21:58:19'),
(138, 2, 4, 2, 3, 1, 1, 'provisoire', NULL, '2026-05-21 21:58:19'),
(139, 2, 4, 2, 3, 1, 2, 'provisoire', NULL, '2026-05-21 21:58:19'),
(140, 2, 4, 2, 3, 1, 3, 'provisoire', NULL, '2026-05-21 21:58:19'),
(141, 2, 4, 2, 3, 1, 4, 'provisoire', NULL, '2026-05-21 21:58:19'),
(142, 2, 4, 3, 3, 1, 5, 'provisoire', NULL, '2026-05-21 21:58:19'),
(143, 2, 4, 3, 3, 1, 6, 'provisoire', NULL, '2026-05-21 21:58:19'),
(144, 2, 4, 3, 3, 1, 7, 'provisoire', NULL, '2026-05-21 21:58:19'),
(145, 2, 4, 4, 3, 1, 17, 'provisoire', NULL, '2026-05-21 21:58:19'),
(146, 2, 4, 4, 3, 1, 18, 'provisoire', NULL, '2026-05-21 21:58:19'),
(147, 2, 4, 6, 4, 1, 15, 'provisoire', NULL, '2026-05-21 21:58:19'),
(148, 2, 4, 6, 4, 1, 16, 'provisoire', NULL, '2026-05-21 21:58:19'),
(149, 2, 4, 7, 4, 1, 23, 'provisoire', NULL, '2026-05-21 21:58:19'),
(150, 2, 4, 7, 4, 1, 24, 'provisoire', NULL, '2026-05-21 21:58:19'),
(151, 2, 4, 8, 4, 1, 25, 'provisoire', NULL, '2026-05-21 21:58:19'),
(152, 2, 4, 8, 4, 1, 26, 'provisoire', NULL, '2026-05-21 21:58:19'),
(153, 2, 4, 1, 14, 1, 19, 'provisoire', NULL, '2026-05-21 21:58:19'),
(154, 2, 4, 1, 14, 1, 20, 'provisoire', NULL, '2026-05-21 21:58:19'),
(155, 2, 4, 1, 14, 1, 21, 'provisoire', NULL, '2026-05-21 21:58:19'),
(156, 2, 4, 1, 14, 1, 22, 'provisoire', NULL, '2026-05-21 21:58:19'),
(157, 2, 5, 1, 2, 2, 15, 'provisoire', NULL, '2026-05-21 21:58:19'),
(158, 2, 5, 1, 2, 2, 16, 'provisoire', NULL, '2026-05-21 21:58:19'),
(159, 2, 5, 1, 2, 2, 17, 'provisoire', NULL, '2026-05-21 21:58:19'),
(160, 2, 5, 1, 2, 2, 18, 'provisoire', NULL, '2026-05-21 21:58:19'),
(161, 2, 5, 5, 2, 2, 19, 'provisoire', NULL, '2026-05-21 21:58:19'),
(162, 2, 5, 5, 2, 2, 20, 'provisoire', NULL, '2026-05-21 21:58:19'),
(163, 2, 5, 5, 2, 2, 21, 'provisoire', NULL, '2026-05-21 21:58:19'),
(164, 2, 5, 2, 3, 2, 22, 'provisoire', NULL, '2026-05-21 21:58:19'),
(165, 2, 5, 2, 3, 2, 23, 'provisoire', NULL, '2026-05-21 21:58:19'),
(166, 2, 5, 2, 3, 2, 24, 'provisoire', NULL, '2026-05-21 21:58:19'),
(167, 2, 5, 2, 3, 2, 25, 'provisoire', NULL, '2026-05-21 21:58:19'),
(168, 2, 5, 3, 3, 2, 26, 'provisoire', NULL, '2026-05-21 21:58:19'),
(169, 2, 5, 3, 3, 5, 27, 'provisoire', NULL, '2026-05-21 21:58:19'),
(170, 2, 5, 3, 3, 5, 28, 'provisoire', NULL, '2026-05-21 21:58:19'),
(171, 2, 5, 4, 3, 5, 29, 'provisoire', NULL, '2026-05-21 21:58:19'),
(172, 2, 5, 4, 3, 5, 30, 'provisoire', NULL, '2026-05-21 21:58:19'),
(173, 2, 5, 6, 4, 2, 1, 'provisoire', NULL, '2026-05-21 21:58:20'),
(174, 2, 5, 6, 4, 2, 2, 'provisoire', NULL, '2026-05-21 21:58:20'),
(175, 2, 5, 7, 4, 2, 3, 'provisoire', NULL, '2026-05-21 21:58:20'),
(176, 2, 5, 7, 4, 2, 4, 'provisoire', NULL, '2026-05-21 21:58:20'),
(177, 2, 5, 8, 4, 2, 5, 'provisoire', NULL, '2026-05-21 21:58:20'),
(178, 2, 5, 8, 4, 2, 6, 'provisoire', NULL, '2026-05-21 21:58:20'),
(179, 2, 5, 1, 14, 2, 7, 'provisoire', NULL, '2026-05-21 21:58:20'),
(180, 2, 5, 1, 14, 2, 8, 'provisoire', NULL, '2026-05-21 21:58:20'),
(181, 2, 5, 1, 14, 2, 9, 'provisoire', NULL, '2026-05-21 21:58:20'),
(182, 2, 5, 1, 14, 2, 10, 'provisoire', NULL, '2026-05-21 21:58:20'),
(183, 2, 1, 1, 2, 3, 22, 'provisoire', NULL, '2026-05-21 21:58:20'),
(184, 2, 1, 1, 2, 3, 23, 'provisoire', NULL, '2026-05-21 21:58:20'),
(185, 2, 1, 1, 2, 3, 24, 'provisoire', NULL, '2026-05-21 21:58:20'),
(186, 2, 1, 1, 2, 3, 25, 'provisoire', NULL, '2026-05-21 21:58:20'),
(187, 2, 1, 5, 2, 3, 26, 'provisoire', NULL, '2026-05-21 21:58:20'),
(188, 2, 1, 5, 2, 1, 27, 'provisoire', NULL, '2026-05-21 21:58:20'),
(189, 2, 1, 5, 2, 1, 28, 'provisoire', NULL, '2026-05-21 21:58:20'),
(190, 2, 1, 2, 3, 3, 19, 'provisoire', NULL, '2026-05-21 21:58:20'),
(191, 2, 1, 2, 3, 3, 20, 'provisoire', NULL, '2026-05-21 21:58:20'),
(192, 2, 1, 2, 3, 3, 21, 'provisoire', NULL, '2026-05-21 21:58:20'),
(193, 2, 1, 2, 3, 5, 31, 'provisoire', NULL, '2026-05-21 21:58:20'),
(194, 2, 1, 3, 3, 5, 32, 'provisoire', NULL, '2026-05-21 21:58:20'),
(195, 2, 1, 3, 3, 5, 33, 'provisoire', NULL, '2026-05-21 21:58:20'),
(196, 2, 1, 3, 3, 5, 34, 'provisoire', NULL, '2026-05-21 21:58:20'),
(197, 2, 1, 4, 3, 5, 35, 'provisoire', NULL, '2026-05-21 21:58:20'),
(198, 2, 1, 4, 3, 5, 36, 'provisoire', NULL, '2026-05-21 21:58:20'),
(199, 2, 1, 6, 4, 3, 7, 'provisoire', NULL, '2026-05-21 21:58:20'),
(200, 2, 1, 6, 4, 3, 8, 'provisoire', NULL, '2026-05-21 21:58:20'),
(201, 2, 1, 7, 4, 3, 9, 'provisoire', NULL, '2026-05-21 21:58:20'),
(202, 2, 1, 7, 4, 3, 10, 'provisoire', NULL, '2026-05-21 21:58:20'),
(203, 2, 1, 8, 4, 2, 11, 'provisoire', NULL, '2026-05-21 21:58:20'),
(204, 2, 1, 8, 4, 2, 12, 'provisoire', NULL, '2026-05-21 21:58:20'),
(205, 2, 1, 1, 14, 3, 1, 'valide', '', '2026-05-21 21:58:20'),
(206, 2, 1, 1, 14, 3, 2, 'provisoire', NULL, '2026-05-21 21:58:20'),
(207, 2, 1, 1, 14, 3, 3, 'valide', '', '2026-05-21 21:58:20'),
(208, 2, 1, 1, 14, 3, 4, 'provisoire', NULL, '2026-05-21 21:58:20'),
(209, 2, 2, 1, 2, 1, 29, 'provisoire', NULL, '2026-05-21 21:58:20'),
(210, 2, 2, 1, 2, 1, 30, 'provisoire', NULL, '2026-05-21 21:58:20'),
(211, 2, 2, 1, 2, 1, 31, 'provisoire', NULL, '2026-05-21 21:58:20'),
(212, 2, 2, 1, 2, 1, 32, 'provisoire', NULL, '2026-05-21 21:58:20'),
(213, 2, 2, 5, 2, 1, 33, 'provisoire', NULL, '2026-05-21 21:58:20'),
(214, 2, 2, 5, 2, 1, 34, 'provisoire', NULL, '2026-05-21 21:58:20'),
(215, 2, 2, 5, 2, 1, 35, 'provisoire', NULL, '2026-05-21 21:58:20'),
(216, 2, 2, 2, 3, 5, 37, 'provisoire', NULL, '2026-05-21 21:58:20'),
(217, 2, 2, 2, 3, 5, 38, 'provisoire', NULL, '2026-05-21 21:58:20'),
(218, 2, 2, 2, 3, 5, 39, 'provisoire', NULL, '2026-05-21 21:58:20'),
(219, 2, 2, 2, 3, 5, 40, 'provisoire', NULL, '2026-05-21 21:58:20'),
(220, 2, 2, 6, 4, 2, 13, 'provisoire', NULL, '2026-05-21 21:58:20'),
(221, 2, 2, 6, 4, 2, 14, 'provisoire', NULL, '2026-05-21 21:58:20'),
(222, 2, 2, 7, 4, 2, 27, 'provisoire', NULL, '2026-05-21 21:58:20'),
(223, 2, 2, 7, 4, 2, 28, 'provisoire', NULL, '2026-05-21 21:58:20'),
(224, 2, 2, 8, 4, 1, 36, 'provisoire', NULL, '2026-05-21 21:58:20'),
(225, 2, 2, 1, 14, 3, 5, 'provisoire', NULL, '2026-05-21 21:58:20'),
(226, 2, 2, 1, 14, 3, 6, 'provisoire', NULL, '2026-05-21 21:58:20'),
(227, 2, 2, 1, 14, 3, 11, 'provisoire', NULL, '2026-05-21 21:58:20'),
(228, 2, 2, 1, 14, 3, 12, 'provisoire', NULL, '2026-05-21 21:58:20'),
(229, 3, 1, 1, 2, 2, 1, 'provisoire', NULL, '2026-05-22 13:32:44'),
(230, 3, 1, 1, 2, 2, 2, 'provisoire', NULL, '2026-05-22 13:32:44'),
(231, 3, 1, 1, 2, 2, 3, 'provisoire', NULL, '2026-05-22 13:32:44'),
(232, 3, 1, 1, 2, 2, 4, 'provisoire', NULL, '2026-05-22 13:32:44'),
(233, 3, 1, 5, 2, 2, 5, 'provisoire', NULL, '2026-05-22 13:32:44'),
(234, 3, 1, 5, 2, 2, 6, 'provisoire', NULL, '2026-05-22 13:32:45'),
(235, 3, 1, 5, 2, 2, 7, 'provisoire', NULL, '2026-05-22 13:32:45'),
(236, 3, 1, 2, 3, 2, 8, 'provisoire', NULL, '2026-05-22 13:32:45'),
(237, 3, 1, 2, 3, 2, 9, 'provisoire', NULL, '2026-05-22 13:32:45'),
(238, 3, 1, 2, 3, 2, 10, 'provisoire', NULL, '2026-05-22 13:32:45'),
(239, 3, 1, 2, 3, 2, 11, 'provisoire', NULL, '2026-05-22 13:32:45'),
(240, 3, 1, 3, 3, 2, 12, 'provisoire', NULL, '2026-05-22 13:32:45'),
(241, 3, 1, 3, 3, 2, 13, 'provisoire', NULL, '2026-05-22 13:32:45'),
(242, 3, 1, 3, 3, 2, 14, 'provisoire', NULL, '2026-05-22 13:32:45'),
(243, 3, 1, 4, 3, 2, 15, 'provisoire', NULL, '2026-05-22 13:32:45'),
(244, 3, 1, 4, 3, 2, 16, 'provisoire', NULL, '2026-05-22 13:32:45'),
(245, 3, 1, 6, 4, 2, 17, 'provisoire', NULL, '2026-05-22 13:32:45'),
(246, 3, 1, 6, 4, 2, 18, 'provisoire', NULL, '2026-05-22 13:32:45'),
(247, 3, 1, 7, 4, 2, 19, 'provisoire', NULL, '2026-05-22 13:32:45'),
(248, 3, 1, 7, 4, 2, 20, 'provisoire', NULL, '2026-05-22 13:32:45'),
(249, 3, 1, 8, 4, 2, 21, 'provisoire', NULL, '2026-05-22 13:32:45'),
(250, 3, 1, 8, 4, 2, 22, 'provisoire', NULL, '2026-05-22 13:32:45'),
(251, 4, 3, 1, 2, 1, 1, 'provisoire', NULL, '2026-05-22 13:33:49'),
(252, 4, 3, 1, 2, 1, 2, 'provisoire', NULL, '2026-05-22 13:33:49'),
(253, 4, 3, 1, 2, 1, 3, 'provisoire', NULL, '2026-05-22 13:33:49'),
(254, 4, 3, 1, 2, 1, 4, 'provisoire', NULL, '2026-05-22 13:33:49'),
(255, 4, 3, 5, 2, 1, 5, 'provisoire', NULL, '2026-05-22 13:33:49'),
(256, 4, 3, 5, 2, 1, 6, 'provisoire', NULL, '2026-05-22 13:33:49'),
(257, 4, 3, 5, 2, 1, 7, 'provisoire', NULL, '2026-05-22 13:33:49'),
(258, 4, 3, 2, 3, 1, 8, 'provisoire', NULL, '2026-05-22 13:33:49'),
(259, 4, 3, 2, 3, 1, 9, 'provisoire', NULL, '2026-05-22 13:33:49'),
(260, 4, 3, 2, 3, 1, 10, 'provisoire', NULL, '2026-05-22 13:33:49'),
(261, 4, 3, 2, 3, 1, 11, 'provisoire', NULL, '2026-05-22 13:33:49'),
(262, 4, 3, 3, 3, 1, 12, 'provisoire', NULL, '2026-05-22 13:33:49'),
(263, 4, 3, 3, 3, 1, 13, 'provisoire', NULL, '2026-05-22 13:33:49'),
(264, 4, 3, 3, 3, 1, 14, 'provisoire', NULL, '2026-05-22 13:33:49'),
(265, 4, 3, 4, 3, 1, 15, 'provisoire', NULL, '2026-05-22 13:33:49'),
(266, 4, 3, 4, 3, 1, 16, 'provisoire', NULL, '2026-05-22 13:33:49'),
(267, 4, 3, 6, 4, 1, 17, 'provisoire', NULL, '2026-05-22 13:33:49'),
(268, 4, 3, 6, 4, 1, 18, 'provisoire', NULL, '2026-05-22 13:33:49'),
(269, 4, 3, 7, 4, 1, 19, 'provisoire', NULL, '2026-05-22 13:33:49'),
(270, 4, 3, 7, 4, 1, 20, 'provisoire', NULL, '2026-05-22 13:33:49'),
(271, 4, 3, 8, 4, 1, 21, 'provisoire', NULL, '2026-05-22 13:33:50'),
(272, 4, 3, 8, 4, 1, 22, 'provisoire', NULL, '2026-05-22 13:33:50'),
(273, 4, 4, 1, 2, 2, 8, 'provisoire', NULL, '2026-05-22 13:33:50'),
(274, 4, 4, 1, 2, 2, 9, 'provisoire', NULL, '2026-05-22 13:33:50'),
(275, 4, 4, 1, 2, 2, 10, 'provisoire', NULL, '2026-05-22 13:33:50'),
(276, 4, 4, 1, 2, 2, 11, 'provisoire', NULL, '2026-05-22 13:33:50'),
(277, 4, 4, 5, 2, 2, 12, 'provisoire', NULL, '2026-05-22 13:33:50'),
(278, 4, 4, 5, 2, 2, 13, 'provisoire', NULL, '2026-05-22 13:33:50'),
(279, 4, 4, 5, 2, 2, 14, 'provisoire', NULL, '2026-05-22 13:33:50'),
(280, 4, 4, 2, 3, 2, 1, 'provisoire', NULL, '2026-05-22 13:33:50'),
(281, 4, 4, 2, 3, 2, 2, 'provisoire', NULL, '2026-05-22 13:33:50'),
(282, 4, 4, 2, 3, 2, 3, 'provisoire', NULL, '2026-05-22 13:33:50'),
(283, 4, 4, 2, 3, 2, 4, 'provisoire', NULL, '2026-05-22 13:33:50'),
(284, 4, 4, 3, 3, 2, 5, 'provisoire', NULL, '2026-05-22 13:33:50'),
(285, 4, 4, 3, 3, 2, 6, 'provisoire', NULL, '2026-05-22 13:33:50'),
(286, 4, 4, 3, 3, 2, 7, 'provisoire', NULL, '2026-05-22 13:33:50'),
(287, 4, 4, 4, 3, 2, 17, 'provisoire', NULL, '2026-05-22 13:33:50'),
(288, 4, 4, 4, 3, 2, 18, 'provisoire', NULL, '2026-05-22 13:33:50'),
(289, 4, 4, 6, 4, 2, 15, 'provisoire', NULL, '2026-05-22 13:33:50'),
(290, 4, 4, 6, 4, 2, 16, 'provisoire', NULL, '2026-05-22 13:33:50'),
(291, 4, 4, 7, 4, 1, 23, 'provisoire', NULL, '2026-05-22 13:33:50'),
(292, 4, 4, 7, 4, 1, 24, 'provisoire', NULL, '2026-05-22 13:33:50'),
(293, 4, 4, 8, 4, 1, 25, 'provisoire', NULL, '2026-05-22 13:33:50'),
(294, 4, 4, 8, 4, 1, 26, 'provisoire', NULL, '2026-05-22 13:33:50'),
(295, 4, 1, 1, 2, 3, 15, 'provisoire', NULL, '2026-05-22 13:33:50'),
(296, 4, 1, 1, 2, 3, 16, 'provisoire', NULL, '2026-05-22 13:33:50'),
(297, 4, 1, 1, 2, 3, 17, 'provisoire', NULL, '2026-05-22 13:33:50'),
(298, 4, 1, 1, 2, 3, 18, 'provisoire', NULL, '2026-05-22 13:33:50'),
(299, 4, 1, 5, 2, 2, 19, 'provisoire', NULL, '2026-05-22 13:33:50'),
(300, 4, 1, 5, 2, 2, 20, 'provisoire', NULL, '2026-05-22 13:33:50'),
(301, 4, 1, 5, 2, 2, 21, 'provisoire', NULL, '2026-05-22 13:33:50'),
(302, 4, 1, 2, 3, 2, 22, 'provisoire', NULL, '2026-05-22 13:33:50'),
(303, 4, 1, 2, 3, 2, 23, 'provisoire', NULL, '2026-05-22 13:33:50'),
(304, 4, 1, 2, 3, 2, 24, 'provisoire', NULL, '2026-05-22 13:33:50'),
(305, 4, 1, 2, 3, 2, 25, 'provisoire', NULL, '2026-05-22 13:33:50'),
(306, 4, 1, 3, 3, 2, 26, 'provisoire', NULL, '2026-05-22 13:33:50'),
(307, 4, 1, 3, 3, 1, 27, 'provisoire', NULL, '2026-05-22 13:33:50'),
(308, 4, 1, 3, 3, 1, 28, 'provisoire', NULL, '2026-05-22 13:33:50'),
(309, 4, 1, 4, 3, 1, 29, 'provisoire', NULL, '2026-05-22 13:33:50'),
(310, 4, 1, 4, 3, 1, 30, 'provisoire', NULL, '2026-05-22 13:33:50'),
(311, 4, 1, 6, 4, 3, 1, 'provisoire', NULL, '2026-05-22 13:33:50'),
(312, 4, 1, 6, 4, 3, 2, 'provisoire', NULL, '2026-05-22 13:33:50'),
(313, 4, 1, 7, 4, 3, 3, 'provisoire', NULL, '2026-05-22 13:33:50'),
(314, 4, 1, 7, 4, 3, 4, 'provisoire', NULL, '2026-05-22 13:33:50'),
(315, 4, 1, 8, 4, 3, 5, 'provisoire', NULL, '2026-05-22 13:33:50'),
(316, 4, 1, 8, 4, 3, 6, 'provisoire', NULL, '2026-05-22 13:33:50'),
(317, 4, 1, 1, 14, 3, 9, 'valide', '', '2026-05-22 13:33:50'),
(318, 4, 1, 1, 14, 3, 10, 'valide', '', '2026-05-22 13:33:50'),
(319, 4, 2, 1, 2, 3, 22, 'provisoire', NULL, '2026-05-22 13:33:50'),
(320, 4, 2, 1, 2, 3, 23, 'provisoire', NULL, '2026-05-22 13:33:50'),
(321, 4, 2, 1, 2, 3, 24, 'provisoire', NULL, '2026-05-22 13:33:50'),
(322, 4, 2, 1, 2, 3, 25, 'provisoire', NULL, '2026-05-22 13:33:50'),
(323, 4, 2, 5, 2, 3, 26, 'provisoire', NULL, '2026-05-22 13:33:51'),
(324, 4, 2, 5, 2, 2, 27, 'provisoire', NULL, '2026-05-22 13:33:51'),
(325, 4, 2, 5, 2, 2, 28, 'provisoire', NULL, '2026-05-22 13:33:51'),
(326, 4, 2, 2, 3, 3, 19, 'provisoire', NULL, '2026-05-22 13:33:51'),
(327, 4, 2, 2, 3, 3, 20, 'provisoire', NULL, '2026-05-22 13:33:51'),
(328, 4, 2, 2, 3, 3, 21, 'provisoire', NULL, '2026-05-22 13:33:51'),
(329, 4, 2, 2, 3, 1, 31, 'provisoire', NULL, '2026-05-22 13:33:51'),
(330, 4, 2, 3, 3, 1, 32, 'provisoire', NULL, '2026-05-22 13:33:51'),
(331, 4, 2, 3, 3, 1, 33, 'provisoire', NULL, '2026-05-22 13:33:51'),
(332, 4, 2, 3, 3, 1, 34, 'provisoire', NULL, '2026-05-22 13:33:51'),
(333, 4, 2, 4, 3, 1, 35, 'provisoire', NULL, '2026-05-22 13:33:51'),
(334, 4, 2, 4, 3, 1, 36, 'provisoire', NULL, '2026-05-22 13:33:51'),
(335, 4, 2, 6, 4, 3, 7, 'provisoire', NULL, '2026-05-22 13:33:51'),
(336, 4, 2, 6, 4, 3, 8, 'provisoire', NULL, '2026-05-22 13:33:51'),
(337, 4, 2, 7, 4, 3, 11, 'provisoire', NULL, '2026-05-22 13:33:51'),
(338, 4, 2, 7, 4, 3, 12, 'provisoire', NULL, '2026-05-22 13:33:51'),
(339, 4, 2, 8, 4, 3, 13, 'provisoire', NULL, '2026-05-22 13:33:51'),
(340, 4, 2, 8, 4, 3, 14, 'provisoire', NULL, '2026-05-22 13:33:51'),
(341, 4, 5, 1, 2, 2, 29, 'provisoire', NULL, '2026-05-22 13:33:51'),
(342, 4, 5, 1, 2, 2, 30, 'provisoire', NULL, '2026-05-22 13:33:51'),
(343, 4, 5, 1, 2, 2, 31, 'provisoire', NULL, '2026-05-22 13:33:51'),
(344, 4, 5, 1, 2, 2, 32, 'provisoire', NULL, '2026-05-22 13:33:51'),
(345, 4, 5, 5, 2, 2, 33, 'provisoire', NULL, '2026-05-22 13:33:51'),
(346, 4, 5, 5, 2, 2, 34, 'provisoire', NULL, '2026-05-22 13:33:51'),
(347, 4, 5, 5, 2, 2, 35, 'provisoire', NULL, '2026-05-22 13:33:51'),
(348, 4, 5, 2, 3, 1, 37, 'provisoire', NULL, '2026-05-22 13:33:51'),
(349, 4, 5, 2, 3, 1, 38, 'provisoire', NULL, '2026-05-22 13:33:51'),
(350, 4, 5, 2, 3, 1, 39, 'provisoire', NULL, '2026-05-22 13:33:51'),
(351, 4, 5, 2, 3, 1, 40, 'provisoire', NULL, '2026-05-22 13:33:51'),
(352, 4, 5, 6, 4, 3, 27, 'provisoire', NULL, '2026-05-22 13:33:51'),
(353, 4, 5, 6, 4, 3, 28, 'provisoire', NULL, '2026-05-22 13:33:51'),
(354, 4, 5, 7, 4, 2, 36, 'provisoire', NULL, '2026-05-22 13:33:51');

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

CREATE TABLE `matieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `couleur_hex` varchar(7) NOT NULL DEFAULT '#1A56DB',
  `nb_heures_semaine` int(11) NOT NULL DEFAULT 2,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matieres`
--

INSERT INTO `matieres` (`id`, `nom`, `couleur_hex`, `nb_heures_semaine`, `description`) VALUES
(1, 'Mathématiques', '#1A56DB', 4, NULL),
(2, 'Français', '#7C3AED', 4, NULL),
(3, 'Anglais', '#059669', 3, NULL),
(4, 'Histoire-Géo', '#D97706', 2, NULL),
(5, 'Physique-Chimie', '#DC2626', 3, NULL),
(6, 'SVT', '#0D9488', 2, NULL),
(7, 'Éducation Phys.', '#DB2777', 2, NULL),
(8, 'Informatique', '#6366F1', 2, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `id_expediteur` int(11) NOT NULL,
  `id_classe` int(11) NOT NULL,
  `sujet` varchar(191) NOT NULL,
  `contenu` text NOT NULL,
  `type` enum('info','absence','devoir','report','autre') NOT NULL DEFAULT 'info',
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages`
--

INSERT INTO `messages` (`id`, `id_expediteur`, `id_classe`, `sujet`, `contenu`, `type`, `date_envoi`) VALUES
(1, 14, 1, 'absence demain', 'demain je serais absent', 'absence', '2026-05-22 13:29:32');

-- --------------------------------------------------------

--
-- Structure de la table `message_lu`
--

CREATE TABLE `message_lu` (
  `id_message` int(11) NOT NULL,
  `id_eleve` int(11) NOT NULL,
  `lu_le` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `message_lu`
--

INSERT INTO `message_lu` (`id_message`, `id_eleve`, `lu_le`) VALUES
(1, 16, '2026-05-22 13:32:03');

-- --------------------------------------------------------

--
-- Structure de la table `niveaux`
--

CREATE TABLE `niveaux` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `niveaux`
--

INSERT INTO `niveaux` (`id`, `nom`, `ordre`, `description`) VALUES
(1, 'Sixième', 1, NULL),
(2, 'Cinquième', 2, NULL),
(3, 'Quatrième', 3, NULL),
(4, 'Troisième', 4, NULL),
(5, 'Seconde', 5, NULL),
(6, 'Première', 6, NULL),
(7, 'Terminale', 7, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `titre` varchar(191) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `est_lu` tinyint(1) NOT NULL DEFAULT 0,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `id_utilisateur`, `titre`, `message`, `type`, `est_lu`, `date_envoi`) VALUES
(1, 2, 'Nouveau planning disponible', 'L\'emploi du temps version 1 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-21 21:53:29'),
(2, 3, 'Nouveau planning disponible', 'L\'emploi du temps version 1 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-21 21:53:29'),
(3, 4, 'Nouveau planning disponible', 'L\'emploi du temps version 1 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-21 21:53:29'),
(4, 1, 'Génération terminée (v1)', '104 créneau(x) générés. 0 créneau(x) ignorés (conflits ou indisponibilités).', 'success', 0, '2026-05-21 21:53:29'),
(5, 14, 'Bienvenue !', 'Votre compte professeur a été créé par l\'administrateur.', 'info', 0, '2026-05-21 21:55:29'),
(6, 2, 'Nouveau planning disponible', 'L\'emploi du temps version 2 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-21 21:58:20'),
(7, 3, 'Nouveau planning disponible', 'L\'emploi du temps version 2 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-21 21:58:20'),
(8, 4, 'Nouveau planning disponible', 'L\'emploi du temps version 2 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-21 21:58:20'),
(9, 14, 'Nouveau planning disponible', 'L\'emploi du temps version 2 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-21 21:58:20'),
(10, 1, 'Génération terminée (v2)', '124 créneau(x) générés. 0 créneau(x) ignorés (conflits ou indisponibilités).', 'success', 0, '2026-05-21 21:58:20'),
(11, 15, 'Message de Bamba Diawara', 'absence demain', 'info', 0, '2026-05-22 13:29:32'),
(12, 2, 'Nouveau planning v3', 'L\'emploi du temps version 3 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-22 13:32:45'),
(13, 3, 'Nouveau planning v3', 'L\'emploi du temps version 3 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-22 13:32:45'),
(14, 4, 'Nouveau planning v3', 'L\'emploi du temps version 3 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-22 13:32:45'),
(15, 1, 'Génération terminée (v3)', '22 créneaux générés. 0 conflits. 18 ignorés (disponibilités). 0 ignorés (niveaux).', 'success', 0, '2026-05-22 13:32:45'),
(16, 2, 'Nouveau planning v4', 'L\'emploi du temps version 4 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-22 13:33:51'),
(17, 3, 'Nouveau planning v4', 'L\'emploi du temps version 4 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-22 13:33:51'),
(18, 4, 'Nouveau planning v4', 'L\'emploi du temps version 4 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-22 13:33:51'),
(19, 14, 'Nouveau planning v4', 'L\'emploi du temps version 4 a été généré. Veuillez valider vos créneaux.', 'info', 0, '2026-05-22 13:33:51'),
(20, 1, 'Génération terminée (v4)', '104 créneaux générés. 18 conflits. 84 ignorés (disponibilités). 0 ignorés (niveaux).', 'success', 0, '2026-05-22 13:33:51');

-- --------------------------------------------------------

--
-- Structure de la table `professeur_matiere`
--

CREATE TABLE `professeur_matiere` (
  `id_professeur` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `professeur_matiere`
--

INSERT INTO `professeur_matiere` (`id_professeur`, `id_matiere`) VALUES
(2, 1),
(2, 5),
(3, 2),
(3, 3),
(3, 4),
(4, 6),
(4, 7),
(4, 8),
(14, 1);

-- --------------------------------------------------------

--
-- Structure de la table `professeur_niveau`
--

CREATE TABLE `professeur_niveau` (
  `id_professeur` int(11) NOT NULL,
  `id_niveau` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `professeur_niveau`
--

INSERT INTO `professeur_niveau` (`id_professeur`, `id_niveau`) VALUES
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(3, 7),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6),
(4, 7),
(14, 1),
(14, 2),
(14, 3),
(14, 4),
(14, 5),
(14, 6),
(14, 7);

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

CREATE TABLE `salles` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `capacite` int(11) NOT NULL DEFAULT 30,
  `type` enum('normale','informatique','laboratoire') NOT NULL DEFAULT 'normale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `nom`, `capacite`, `type`) VALUES
(1, 'Salle 101', 35, 'normale'),
(2, 'Salle 102', 35, 'normale'),
(3, 'Salle 201', 33, 'normale'),
(4, 'Salle Informatique A', 25, 'informatique'),
(5, 'Laboratoire Sciences', 28, 'laboratoire');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('admin','professeur','eleve') NOT NULL DEFAULT 'eleve',
  `statut` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `id_classe` int(11) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `statut`, `id_classe`, `date_creation`) VALUES
(1, 'Admin', 'Super', 'admin@ecole.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif', NULL, '2026-05-21 21:39:42'),
(2, 'Thioye', 'Babacar', 'Babacar.Thioye@ecole.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professeur', 'actif', NULL, '2026-05-21 21:39:42'),
(3, 'Diallo', 'Aïssatou', 'aissatou.diallo@ecole.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professeur', 'actif', NULL, '2026-05-21 21:39:42'),
(4, 'Ndiaye', 'Ibrahima', 'ibrahima.ndiaye@ecole.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professeur', 'actif', NULL, '2026-05-21 21:39:42'),
(13, 'codeur', 'anonym', 'anonym@gmail.com', '$2y$10$ggpHAZRBN5gEbQQqjGjjReKvf3m/zjobUEY2KaxpCQlrlK0j0QoqS', 'eleve', 'actif', 3, '2026-05-21 21:47:38'),
(14, 'Diawara', 'Bamba', 'bamba.diawara@gmail.com', '$2y$10$O6qt5DLbraZrN8wu3cM/cuqi5YgSuMkl5BNxbGI.G9xM/dBa/wQ.e', 'professeur', 'actif', NULL, '2026-05-21 21:55:29'),
(15, 'fall', 'saliou', 'saliou.fall@gmail.com', '$2y$10$Jr4rJ8tWoaQr3d1XhKtan.FLTCRrLX4qz8sS5Kt3819Y59tb5C/w.', 'eleve', 'actif', 1, '2026-05-21 22:00:09'),
(16, 'gueye', 'fatou', 'fatou.gueye@gmail.com', '$2y$10$8HfELjm/fZMObO1UVEfHOeFgfgyKtAw59MOuDWoYct8LFnEHq8xJi', 'eleve', 'actif', 1, '2026-05-22 13:31:37');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_classe_nom` (`nom`),
  ADD KEY `fk_classe_niveau` (`id_niveau`);

--
-- Index pour la table `creneaux`
--
ALTER TABLE `creneaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_creneau` (`jour`,`heure_debut`,`heure_fin`);

--
-- Index pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_professeur` (`id_professeur`);

--
-- Index pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_classe` (`id_classe`),
  ADD KEY `id_matiere` (`id_matiere`),
  ADD KEY `id_professeur` (`id_professeur`),
  ADD KEY `id_salle` (`id_salle`),
  ADD KEY `id_creneau` (`id_creneau`);

--
-- Index pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_matiere_nom` (`nom`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_expediteur` (`id_expediteur`),
  ADD KEY `id_classe` (`id_classe`);

--
-- Index pour la table `message_lu`
--
ALTER TABLE `message_lu`
  ADD PRIMARY KEY (`id_message`,`id_eleve`),
  ADD KEY `id_eleve` (`id_eleve`);

--
-- Index pour la table `niveaux`
--
ALTER TABLE `niveaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_niveau_nom` (`nom`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `professeur_matiere`
--
ALTER TABLE `professeur_matiere`
  ADD PRIMARY KEY (`id_professeur`,`id_matiere`),
  ADD KEY `id_matiere` (`id_matiere`);

--
-- Index pour la table `professeur_niveau`
--
ALTER TABLE `professeur_niveau`
  ADD PRIMARY KEY (`id_professeur`,`id_niveau`),
  ADD KEY `id_niveau` (`id_niveau`);

--
-- Index pour la table `salles`
--
ALTER TABLE `salles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_salle_nom` (`nom`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `id_classe` (`id_classe`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `creneaux`
--
ALTER TABLE `creneaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=355;

--
-- AUTO_INCREMENT pour la table `matieres`
--
ALTER TABLE `matieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `niveaux`
--
ALTER TABLE `niveaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `salles`
--
ALTER TABLE `salles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_classe_niveau` FOREIGN KEY (`id_niveau`) REFERENCES `niveaux` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `disponibilites`
--
ALTER TABLE `disponibilites`
  ADD CONSTRAINT `disponibilites_ibfk_1` FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emplois_du_temps`
--
ALTER TABLE `emplois_du_temps`
  ADD CONSTRAINT `emplois_du_temps_ibfk_1` FOREIGN KEY (`id_classe`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emplois_du_temps_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emplois_du_temps_ibfk_3` FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emplois_du_temps_ibfk_4` FOREIGN KEY (`id_salle`) REFERENCES `salles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emplois_du_temps_ibfk_5` FOREIGN KEY (`id_creneau`) REFERENCES `creneaux` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_expediteur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`id_classe`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message_lu`
--
ALTER TABLE `message_lu`
  ADD CONSTRAINT `message_lu_ibfk_1` FOREIGN KEY (`id_message`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_lu_ibfk_2` FOREIGN KEY (`id_eleve`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `professeur_matiere`
--
ALTER TABLE `professeur_matiere`
  ADD CONSTRAINT `professeur_matiere_ibfk_1` FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professeur_matiere_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `professeur_niveau`
--
ALTER TABLE `professeur_niveau`
  ADD CONSTRAINT `professeur_niveau_ibfk_1` FOREIGN KEY (`id_professeur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `professeur_niveau_ibfk_2` FOREIGN KEY (`id_niveau`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_classe`) REFERENCES `classes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
