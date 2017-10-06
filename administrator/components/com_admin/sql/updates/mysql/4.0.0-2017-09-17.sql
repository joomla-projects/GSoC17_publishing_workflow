--
-- Dumping data for table `#__workflows`
--

CREATE TABLE IF NOT EXISTS `#__workflows` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) DEFAULT 0, # TODO replace with real value
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `extension` varchar(255) NOT NULL,
  `default` tinyint(1) NOT NULL,
  `created` datetime NOT NULL DEFAULT NOW(),
  `created_by` int(10) NOT NULL,
  `modified` datetime NOT NULL DEFAULT NOW(),
  `modified_by` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `title` (`title`(191)),
  KEY `extension` (`extension`(191)),
  KEY `default` (`default`),
  KEY `created` (`created`),
  KEY `created_by` (`created_by`),
  KEY `modified` (`modified`),
  KEY `modified_by` (`modified_by`)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `#__workflow_associations`
--

CREATE TABLE IF NOT EXISTS `#__workflow_associations` (
  `item_id` int(10) NOT NULL DEFAULT 0 COMMENT 'Extension table id value',
  `state_id` int(10) NOT NULL COMMENT 'Foreign Key to #__workflow_states.id',
  `extension` varchar(100) NOT NULL,
  PRIMARY KEY (`item_id`, `state_id`, `extension`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_state_id` (`state_id`),
  KEY `idx_extension` (`extension`(100))
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#__workflow_states`
--

CREATE TABLE IF NOT EXISTS `#__workflow_states` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) DEFAULT 0,
  `workflow_id` int(10) NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `condition` enum('0','1','-2') NOT NULL,
  `default` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_id` (`workflow_id`),
  KEY `title` (`title`(191)),
  KEY `asset_id` (`asset_id`),
  KEY `default` (`default`)
) ENGINE=InnoDB DEFAULT COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#__workflow_transitions`
--

CREATE TABLE IF NOT EXISTS `#__workflow_transitions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `from_state_id` int(10) NOT NULL,
  `to_state_id` int(10) NOT NULL,
  `workflow_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `title` (`title`(191)),
  KEY `asset_id` (`asset_id`),
  KEY `from_state_id` (`from_state_id`),
  KEY `to_state_id` (`to_state_id`),
  KEY `workflow_id` (`workflow_id`)
) ENGINE=InnoDB DEFAULT COLLATE=utf8mb4_unicode_ci;

-- Adds com_workflow entry into the #__extensions table

INSERT INTO `#__extensions` (`extension_id`, `package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `checked_out`, `checked_out_time`, `ordering`, `state`, `namespace`) VALUES
(35, 0, 'com_workflow', 'component', 'com_workflow', '', 1, 1, 0, 0, '', '{}', 0, '0000-00-00 00:00:00', 0, 0, 'Joomla\\Component\\Workflow');
