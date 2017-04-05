-- --------------------------------------------------------
-- Хост:                         127.0.0.1
-- Версия сервера:               5.7.13-log - MySQL Community Server (GPL)
-- ОС Сервера:                   Win64
-- HeidiSQL Версия:              9.3.0.4984
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Дамп структуры для таблица test.snapshots
CREATE TABLE IF NOT EXISTS `snapshots` (
  `type` varchar(255) NOT NULL,
  `refreshdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `needUpdate` tinyint(1) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `snapshotId` varchar(255) NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`type`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Экспортируемые данные не выделены.


-- Дамп структуры для таблица test.snapshot_adgroups
CREATE TABLE IF NOT EXISTS `snapshot_adgroups` (
  `adGroupId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `campaignId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `defaultBid` float unsigned NOT NULL,
  `state` varchar(255) NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`adGroupId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- Экспортируемые данные не выделены.


-- Дамп структуры для таблица test.snapshot_campaigns
CREATE TABLE IF NOT EXISTS `snapshot_campaigns` (
  `campaignId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `campaignType` varchar(255) NOT NULL,
  `targetingType` varchar(255) NOT NULL,
  `premiumBidAdjustment` varchar(255) NOT NULL,
  `dailyBudget` float unsigned NOT NULL,
  `startDate` date NOT NULL,
  `state` varchar(255) NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`campaignId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- Экспортируемые данные не выделены.


-- Дамп структуры для таблица test.snapshot_keywords
CREATE TABLE IF NOT EXISTS `snapshot_keywords` (
  `keywordId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `adGroupId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `campaignId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `keywordText` varchar(255) NOT NULL,
  `matchType` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `bid` float unsigned NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`keywordId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- Экспортируемые данные не выделены.


-- Дамп структуры для таблица test.snapshot_productads
CREATE TABLE IF NOT EXISTS `snapshot_productads` (
  `adId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `adGroupId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `campaignId` bigint(20) unsigned NOT NULL DEFAULT '0',
  `asin` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `userId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`adId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Экспортируемые данные не выделены.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
