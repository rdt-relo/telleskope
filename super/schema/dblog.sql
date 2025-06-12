-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 15, 2021 at 03:30 AM
-- Server version: 5.7.28
-- PHP Version: 7.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dblog`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `email_log_id` int(11) NOT NULL,
  `domain` varchar(64) NOT NULL,
  `section_type` tinyint(4) NOT NULL,
  `section_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `label` varchar(32) DEFAULT NULL,
  `createdby` int(11) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `email_log_rcpts`
--

CREATE TABLE `email_log_rcpts` (
  `email_rcpt_id` bigint(20) NOT NULL,
  `email_log_id` int(11) NOT NULL,
  `rcpt_userid` int(11) NOT NULL,
  `rcpt_email` varchar(64) DEFAULT NULL COMMENT 'Set this only if rcpt_userid=0',
  `sent_timestamp` timestamp NULL DEFAULT NULL,
  `first_open_timestamp` timestamp NULL DEFAULT NULL,
  `last_open_timestamp` timestamp NULL DEFAULT NULL,
  `total_opens` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `email_log_rcpt_url_clicks`
--

CREATE TABLE `email_log_rcpt_url_clicks` (
  `email_rcpt_id` bigint(20) NOT NULL,
  `email_url_id` int(11) NOT NULL,
  `first_click_timestamp` timestamp NULL DEFAULT NULL,
  `last_click_timestamp` timestamp NULL DEFAULT NULL,
  `total_clicks` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `email_log_urls`
--

CREATE TABLE `email_log_urls` (
  `email_url_id` int(11) NOT NULL,
  `email_log_id` int(11) NOT NULL,
  `url` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`email_log_id`);

--
-- Indexes for table `email_log_rcpts`
--
ALTER TABLE `email_log_rcpts`
  ADD PRIMARY KEY (`email_rcpt_id`),
  ADD KEY `email_log_rcpts_email_log_id_index` (`email_log_id`);

--
-- Indexes for table `email_log_rcpt_url_clicks`
--
ALTER TABLE `email_log_rcpt_url_clicks`
  ADD UNIQUE KEY `email_rcpt_url_clicks_email_rcpt_id_email_url_id_uindex` (`email_rcpt_id`,`email_url_id`);

--
-- Indexes for table `email_log_urls`
--
ALTER TABLE `email_log_urls`
  ADD PRIMARY KEY (`email_url_id`),
  ADD KEY `email_log_urls_email_log_id_index` (`email_log_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `email_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_log_rcpts`
--
ALTER TABLE `email_log_rcpts`
  MODIFY `email_rcpt_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_log_urls`
--
ALTER TABLE `email_log_urls`
  MODIFY `email_url_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
