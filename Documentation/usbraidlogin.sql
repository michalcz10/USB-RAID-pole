-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost:3306
-- Vytvořeno: Pát 17. led 2025, 20:30
-- Verze serveru: 8.0.40-0ubuntu0.24.10.1
-- Verze PHP: 8.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `usbraidlogin`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `uname` varchar(255) NOT NULL,
  `pswd` varchar(60) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`uname`, `pswd`, `admin`) VALUES
('admin', '$2y$10$pBq6HloKwFo3b0yFEn7BJu3ZeRe/GDVJKQ5LW2C8rw6jgMvMqDD9G', 1),
('asd', '$2y$10$2T0fSipsMXhQ8pYoYxXDIeHr/33Zg1qw10MwIqk.xXW7svEeuZr52', 0),
('micalis', '$2y$10$.VyDlPCMkRfSqT4XygA8gex3j2vBfF8rWB0TzNe4NNco7hsfOGHPG', 0),
('asdf', '$2y$10$og7bvS9Q1ryCQk9gc/FtAeSpeZ9DVQtnkTeI21b/7YZFHZ1aZ1nPi', 0),
('asdfg', '$2y$10$DxtdXRZMbC.JiOV6YdOTYun2/Etu5P88UpZ.nKvoX.vxzHZeGPaSm', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
