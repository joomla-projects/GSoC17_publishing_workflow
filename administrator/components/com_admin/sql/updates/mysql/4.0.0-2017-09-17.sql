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
-- Dumping data for table `#__workflows`
--

-- INSERT INTO `#__workflows` (`id`, `asset_id`, `published`, `title`, `description`, `extension`, `default`, `created`, `created_by`, `modified`, `modified_by`) VALUES
-- (1, 56, 1, 'Joomla! Default', '', 'com_content', 1, NOW(), 0, '0000-00-00 00:00:00', 0);

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
-- Dumping data for table `#__workflow_states`
--

-- INSERT INTO `#__workflow_states` (`id`, `asset_id`, `workflow_id`, `published`, `title`, `description`, `condition`, `default`) VALUES
-- (1, 57, 1, 1, 'Unpublished', '', '0', 0),
-- (2, 58, 1, 1, 'Published', '', '1', 1),
-- (3, 59, 1, 1, 'Trashed', '', '-2', 0),
-- (4, 60, 1, 1, 'Archived', '', '1', 0);

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

--
-- Dumping data for table `#__workflow_transitions`
--

-- INSERT INTO `#__workflow_transitions` (`id`, `asset_id`, `published`, `title`, `description`, `from_state_id`, `to_state_id`, `workflow_id`) VALUES
-- (1, 61, 1, 'Unpublish', '', 2, 1, 1),
-- (2, 62, 1, 'Unpublish', '', 3, 1, 1),
-- (3, 63, 1, 'Unpublish', '', 4, 1, 1),
-- (4, 64, 1, 'Publish', '', 1, 2, 1),
-- (5, 65, 1, 'Publish', '', 3, 2, 1),
-- (6, 66, 1, 'Publish', '', 4, 2, 1),
-- (7, 67, 1, 'Trash', '', 1, 3, 1),
-- (8, 68, 1, 'Trash', '', 2, 3, 1),
-- (9, 69, 1, 'Trash', '', 4, 3, 1),
-- (10, 70, 1, 'Archive', '', 1, 4, 1),
-- (11, 71, 1, 'Archive', '', 2, 4, 1),
-- (12, 72, 1, 'Archive', '', 3, 4, 1);

-- Adds com_workflow entry into the #__extensions table

INSERT INTO `#__extensions` (`extension_id`, `package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `checked_out`, `checked_out_time`, `ordering`, `state`, `namespace`) VALUES
(35, 0, 'com_workflow', 'component', 'com_workflow', '', 1, 1, 0, 0, '', '{}', 0, '0000-00-00 00:00:00', 0, 0, 'Joomla\\Component\\Workflow');

-- UPDATE `#__categories` SET `params` = '{"category_layout":"","image":"","workflow_id":"1"}' WHERE `extension` = 'com_content';

-- INSERT INTO `#__assets` (`id`, `parent_id`, `lft`, `rgt`, `level`, `name`, `title`, `rules`) VALUES
-- (56, 8, 20, 53, 2, 'com_content.workflow.1', 'Joomla! Default', '{}'),
-- (57, 56, 21, 22, 3, 'com_content.state.1', 'Unpublished', '{}'),
-- (58, 56, 23, 24, 3, 'com_content.state.2', 'Published', '{}'),
-- (59, 56, 25, 26, 3, 'com_content.state.3', 'Trashed', '{}'),
-- (60, 56, 27, 28, 3, 'com_content.state.4', 'Archived', '{}'),
-- (61, 56, 29, 30, 3, 'com_content.transition.1', 'Unpublish', '{}'),
-- (62, 56, 31, 32, 3, 'com_content.transition.2', 'Unpublish', '{}'),
-- (63, 56, 33, 34, 3, 'com_content.transition.3', 'Unpublish', '{}'),
-- (64, 56, 35, 36, 3, 'com_content.transition.4', 'Publish', '{}'),
-- (65, 56, 37, 38, 3, 'com_content.transition.5', 'Publish', '{}'),
-- (66, 56, 39, 40, 3, 'com_content.transition.6', 'Publish', '{}'),
-- (67, 56, 41, 42, 3, 'com_content.transition.7', 'Trash', '{}'),
-- (68, 56, 43, 44, 3, 'com_content.transition.8', 'Trash', '{}'),
-- (69, 56, 45, 46, 3, 'com_content.transition.9', 'Trash', '{}'),
-- (70, 56, 47, 48, 3, 'com_content.transition.10', 'Archive', '{}'),
-- (71, 56, 49, 50, 3, 'com_content.transition.11', 'Archive', '{}'),
-- (72, 56, 51, 52, 3, 'com_content.transition.12', 'Archive', '{}');

UPDATE `#__content` SET `state` = 3 WHERE `state` = -2;
UPDATE `#__content` SET `state` = 4 WHERE `state` = 2;
UPDATE `#__content` SET `state` = 2 WHERE `state` = 1;
UPDATE `#__content` SET `state` = 1 WHERE `state` = 0;


