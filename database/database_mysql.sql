CREATE TABLE IF NOT EXISTS `config` (
  `setting` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`setting`),
  UNIQUE KEY `setting` (`setting`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `config` (`setting`, `value`) VALUES
('table_adi_progress', 'adi_progress'),
('table_adi_test_progress', 'adi_test_progress'),
('table_adi_questions', 'adi_questions'),
('table_adi_dvsa_sections', 'adi_dsa_sections');

CREATE TABLE IF NOT EXISTS `adi_dsa_sections` (
  `section` varchar(2) DEFAULT NULL,
  `group_id` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(50) DEFAULT NULL,
  `free` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  UNIQUE KEY `section` (`section`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `adi_modules` (
  `section` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `lesson` tinyint(3) UNSIGNED DEFAULT '0',
  `free` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `adi_progress` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `progress` longtext NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `adi_publications` (
  `section` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `free` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `adi_questions` (
  `prim` int(11) UNSIGNED NOT NULL,
  `dsaband` varchar(2) DEFAULT NULL,
  `dsabandno` smallint(6) UNSIGNED DEFAULT NULL,
  `cat` tinyint(3) UNSIGNED DEFAULT NULL,
  `dsaqposition` smallint(6) UNSIGNED DEFAULT NULL,
  `ldclessonno` tinyint(3) UNSIGNED DEFAULT NULL,
  `ldcqno` smallint(6) UNSIGNED DEFAULT NULL,
  `hcsection` tinyint(3) UNSIGNED DEFAULT NULL,
  `hcqno` smallint(6) UNSIGNED DEFAULT NULL,
  `hcrule1` int(10) UNSIGNED DEFAULT NULL,
  `hcrule2` int(10) UNSIGNED DEFAULT NULL,
  `hcrule3` int(10) UNSIGNED DEFAULT NULL,
  `hcpage1` smallint(6) UNSIGNED DEFAULT NULL,
  `hcpage2` smallint(6) UNSIGNED DEFAULT NULL,
  `hcpage3` smallint(6) UNSIGNED DEFAULT NULL,
  `question` varchar(255) DEFAULT NULL,
  `mark` tinyint(3) UNSIGNED DEFAULT NULL,
  `tickamount` tinyint(3) UNSIGNED DEFAULT NULL,
  `option1` varchar(255) DEFAULT NULL,
  `option2` varchar(255) DEFAULT NULL,
  `option3` varchar(255) DEFAULT NULL,
  `option4` varchar(255) DEFAULT NULL,
  `option5` varchar(255) DEFAULT NULL,
  `option6` varchar(255) DEFAULT NULL,
  `answer1` varchar(1) DEFAULT NULL,
  `answer2` varchar(1) DEFAULT NULL,
  `answer3` varchar(1) DEFAULT NULL,
  `answer4` varchar(1) DEFAULT NULL,
  `answer5` varchar(1) DEFAULT NULL,
  `answer6` varchar(1) DEFAULT NULL,
  `answerletters` varchar(1) DEFAULT NULL,
  `dsaexplanation` longtext,
  `type` varchar(5) DEFAULT NULL,
  `dsaimageid` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `excludesni` varchar(5) DEFAULT NULL,
  `includedintest` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `signstheory` double DEFAULT NULL,
  `mt1` tinyint(3) UNSIGNED DEFAULT NULL,
  `mt2` tinyint(3) UNSIGNED DEFAULT NULL,
  `mt3` tinyint(3) UNSIGNED DEFAULT NULL,
  `mt4` tinyint(3) UNSIGNED DEFAULT NULL,
  `mt5` tinyint(3) UNSIGNED DEFAULT NULL,
  `mt6` tinyint(3) UNSIGNED DEFAULT NULL,
  `format` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`prim`),
  UNIQUE KEY `mt1` (`mt1`) USING BTREE,
  UNIQUE KEY `mt2` (`mt2`) USING BTREE,
  UNIQUE KEY `mt3` (`mt3`) USING BTREE,
  UNIQUE KEY `mt4` (`mt4`) USING BTREE,
  UNIQUE KEY `mt5` (`mt5`) USING BTREE,
  UNIQUE KEY `mt6` (`mt6`) USING BTREE,
  KEY `hcrule1` (`hcrule1`),
  KEY `hcrule2` (`hcrule2`),
  KEY `hcrule3` (`hcrule3`),
  KEY `dsaband` (`dsaband`),
  KEY `ldclessonno` (`ldclessonno`),
  KEY `hcsection` (`hcsection`),
  KEY `dsabandno` (`dsabandno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `adi_test_progress` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `questions` text NOT NULL,
  `answers` text NOT NULL,
  `results` text,
  `test_id` int(11) UNSIGNED NOT NULL,
  `question_no` int(11) UNSIGNED NOT NULL DEFAULT '1',
  `started` datetime NOT NULL,
  `complete` datetime DEFAULT NULL,
  `time_remaining` varchar(10) DEFAULT NULL,
  `time_taken` varchar(10) DEFAULT NULL,
  `totalscore` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `type` varchar(10) DEFAULT 'adi',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `users`
ADD `first_name` VARCHAR(50) NOT NULL AFTER `id`,
ADD `last_name` VARCHAR(50) NOT NULL AFTER `first_name`,
ADD `settings` TEXT NULL DEFAULT NULL AFTER `isactive`;

--
-- Constraints
--

ALTER TABLE `adi_progress`
  ADD CONSTRAINT `adi_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `adi_questions`
  ADD CONSTRAINT `adi_questions_ibfk_1` FOREIGN KEY (`hcrule1`) REFERENCES `highway_code` (`hcno`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `adi_questions_ibfk_2` FOREIGN KEY (`hcrule2`) REFERENCES `highway_code` (`hcno`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `adi_questions_ibfk_3` FOREIGN KEY (`hcrule3`) REFERENCES `highway_code` (`hcno`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `adi_questions_ibfk_4` FOREIGN KEY (`dsaband`) REFERENCES `adi_dsa_sections` (`section`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `adi_questions_ibfk_5` FOREIGN KEY (`ldclessonno`) REFERENCES `adi_modules` (`section`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `adi_questions_ibfk_6` FOREIGN KEY (`hcsection`) REFERENCES `adi_publications` (`section`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `adi_test_progress`
  ADD CONSTRAINT `adi_test_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
