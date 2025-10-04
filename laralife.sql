-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Set 21, 2025 alle 12:09
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laralife`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `alimenti`
--

CREATE TABLE `alimenti` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(255) NOT NULL,
  `marca` varchar(255) DEFAULT NULL,
  `base_nutrizionale` enum('100g','100ml','unit') NOT NULL DEFAULT '100g',
  `kcal_ref` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `carbo_ref_g` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `prot_ref_g` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `grassi_ref_g` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `unita_preferita` enum('g','ml','u') NOT NULL DEFAULT 'g',
  `distributore` varchar(255) DEFAULT NULL,
  `fibre_ref_g` smallint(5) DEFAULT 0,
  `caffeina` smallint(5) DEFAULT 0,
  `zucchero_ref_g` smallint(5) DEFAULT 0,
  `sale_ref_g` smallint(5) DEFAULT 0,
  `prezzo_medio` decimal(5,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `alimenti_dispensa`
--

CREATE TABLE `alimenti_dispensa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `alimento_id` bigint(20) UNSIGNED NOT NULL,
  `quantita_disponibile` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unita` enum('g','ml','u') NOT NULL DEFAULT 'g',
  `scadenza` date DEFAULT NULL,
  `posizione` varchar(100) DEFAULT NULL,
  `n_pezzi` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `alimenti_pasti`
--

CREATE TABLE `alimenti_pasti` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `planning_id` bigint(20) UNSIGNED NOT NULL,
  `alimento_id` bigint(20) UNSIGNED NOT NULL,
  `quantita` int(10) UNSIGNED NOT NULL,
  `unita` enum('g','ml','u') NOT NULL DEFAULT 'g',
  `scarica_dispensa` tinyint(1) NOT NULL DEFAULT 1,
  `kcal` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `carbo_g` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `prot_g` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `grassi_g` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `esecuzioni`
--

CREATE TABLE `esecuzioni` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `modulo` varchar(255) NOT NULL,
  `riferimento_id` bigint(20) UNSIGNED NOT NULL,
  `minuti_effettivi` int(11) DEFAULT NULL,
  `stato` enum('completata','saltata','in_corso') NOT NULL DEFAULT 'in_corso',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `lezioni`
--

CREATE TABLE `lezioni` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titolo` varchar(255) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `durata` int(11) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `piattaforma` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `planning`
--

CREATE TABLE `planning` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `riferibile_type` varchar(255) DEFAULT NULL,
  `riferibile_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo_pasto` enum('colazione','spuntino','pranzo','merenda','cena','libero') DEFAULT NULL,
  `quantita` int(10) UNSIGNED DEFAULT NULL,
  `ordine` int(11) NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `alimenti`
--
ALTER TABLE `alimenti`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `alimenti_dispensa`
--
ALTER TABLE `alimenti_dispensa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dispensa_alimento` (`alimento_id`);

--
-- Indici per le tabelle `alimenti_pasti`
--
ALTER TABLE `alimenti_pasti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pasti_planning` (`planning_id`),
  ADD KEY `idx_pasti_alimento` (`alimento_id`);

--
-- Indici per le tabelle `esecuzioni`
--
ALTER TABLE `esecuzioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_esecuzioni_modulo_data` (`modulo`,`data`);

--
-- Indici per le tabelle `lezioni`
--
ALTER TABLE `lezioni`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_planning_data` (`data`),
  ADD KEY `idx_planning_morph` (`riferibile_type`,`riferibile_id`),
  ADD KEY `idx_planning_tipo_pasto` (`tipo_pasto`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `alimenti`
--
ALTER TABLE `alimenti`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `alimenti_dispensa`
--
ALTER TABLE `alimenti_dispensa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `alimenti_pasti`
--
ALTER TABLE `alimenti_pasti`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `esecuzioni`
--
ALTER TABLE `esecuzioni`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `lezioni`
--
ALTER TABLE `lezioni`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `planning`
--
ALTER TABLE `planning`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `alimenti_dispensa`
--
ALTER TABLE `alimenti_dispensa`
  ADD CONSTRAINT `fk_dispensa_alimento` FOREIGN KEY (`alimento_id`) REFERENCES `alimenti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limiti per la tabella `alimenti_pasti`
--
ALTER TABLE `alimenti_pasti`
  ADD CONSTRAINT `fk_pasti_alimento` FOREIGN KEY (`alimento_id`) REFERENCES `alimenti` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pasti_planning` FOREIGN KEY (`planning_id`) REFERENCES `planning` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
