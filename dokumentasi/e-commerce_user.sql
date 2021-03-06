-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2022 at 06:37 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-commerce_user`
--

-- --------------------------------------------------------

--
-- Table structure for table `paroki`
--

CREATE TABLE `paroki` (
  `id` int(11) NOT NULL,
  `nama_paroki` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `paroki`
--

INSERT INTO `paroki` (`id`, `nama_paroki`) VALUES
(1, 'Santa Maria Ratu Bayat'),
(2, 'Santa Maria Assumpta Cawas'),
(3, 'Santa Perawan Maria Diangkat Ke Surga Dalem'),
(4, 'Santo Yohanes Rasul Delanggu'),
(5, 'Santo Yusuf Pekerja Gondangwinangun'),
(6, 'Santa Theresia Jombor'),
(7, 'Roh Kudus Kebonarum'),
(8, 'Santo Ignatius Ketandan'),
(9, 'Santa Maria Assumpta Klaten'),
(10, 'Santa Maria Bunda Kristus Wedi');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `client_id` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `nama`, `email`, `password`, `client_id`) VALUES
(20888, 'coba', 'coba@gmail.com', '$2y$10$/PKWw0J003D0kAjyekWfSOT5weyUxd2oL0an8SoLYS8nGdUAiCyBm', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `paroki`
--
ALTER TABLE `paroki`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `paroki`
--
ALTER TABLE `paroki`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20890;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
