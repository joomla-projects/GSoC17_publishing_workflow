<?php
/**
 * @package     Joomla.Installation
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Installation\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Installation\Helper\DatabaseHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\Database\UTF8MB4SupportInterface;
use Joomla\Utilities\ArrayHelper;

/**
 * Setup model for the Joomla Core Installer.
 *
 * @since  3.1
 */
class SetupModel extends BaseInstallationModel
{
	/**
	 * The generated user ID.
	 *
	 * @var    integer
	 * @since  3.1
	 */
	protected static $userId = 0;

	/**
	 * Get the current setup options from the session.
	 *
	 * @return  array  An array of options from the session.
	 *
	 * @since   3.1
	 */
	public function getOptions()
	{
		if (!empty(Factory::getSession()->get('setup.options', array())))
		{
			return Factory::getSession()->get('setup.options', array());
		}
	}

	/**
	 * Store the current setup options in the session.
	 *
	 * @param   array  $options  The installation options.
	 *
	 * @return  array  An array of options from the session.
	 *
	 * @since   3.1
	 */
	public function storeOptions($options)
	{
		// Get the current setup options from the session.
		$old = (array) $this->getOptions();

		// Ensure that we have language
		if (!isset($options['language']) || empty($options['language']))
		{
			$options['language'] = Factory::getLanguage()->getTag();
		}

		// Get the session
		$session = Factory::getSession();
		$options['helpurl'] = $session->get('setup.helpurl', null);

		// Merge the new setup options into the current ones and store in the session.
		$options = array_merge($old, (array) $options);
		$session->set('setup.options', $options);

		return $options;
	}

	/**
	 * Method to get the form.
	 *
	 * @param   string  $view  The view being processed.
	 *
	 * @return  Form|boolean  JForm object on success, false on failure.
	 *
	 * @since   3.1
	 */
	public function getForm($view = null)
	{
		if (!$view)
		{
			$view = Factory::getApplication()->input->getWord('view', 'setup');
		}

		// Get the form.
		Form::addFormPath(JPATH_COMPONENT . '/forms');

		try
		{
			$form = Form::getInstance('jform', $view, array('control' => 'jform'));
		}
		catch (\Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		// Check the session for previously entered form data.
		$data = (array) $this->getOptions();

		// Bind the form data if present.
		if (!empty($data))
		{
			$form->bind($data);
		}

		return $form;
	}

	/**
	 * Method to check the form data.
	 *
	 * @param   string  $page  The view being checked.
	 *
	 * @return  array|boolean  Array with the validated form data or boolean false on a validation failure.
	 *
	 * @since   3.1
	 */
	public function checkForm($page = 'setup')
	{
		// Get the posted values from the request and validate them.
		$data   = Factory::getApplication()->input->post->get('jform', array(), 'array');
		$return = $this->validate($data, $page);

		// Attempt to save the data before validation.
		$form = $this->getForm();
		$data = $form->filter($data);

		$this->storeOptions($data);

		// Check for validation errors.
		if ($return === false)
		{
			return false;
		}

		// Store the options in the session.
		return $this->storeOptions($return);
	}

	/**
	 * Generate a panel of language choices for the user to select their language.
	 *
	 * @return  boolean True if successful.
	 *
	 * @since   3.1
	 */
	public function getLanguages()
	{
		// Detect the native language.
		$native = LanguageHelper::detectLanguage();

		if (empty($native))
		{
			$native = 'en-GB';
		}

		// Get a forced language if it exists.
		$forced = Factory::getApplication()->getLocalise();

		if (!empty($forced['language']))
		{
			$native = $forced['language'];
		}

		// Get the list of available languages.
		$list = LanguageHelper::createLanguageList($native);

		if (!$list || $list instanceof \Exception)
		{
			$list = array();
		}

		return $list;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   array   $data  The form data.
	 * @param   string  $view  The view.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   3.1
	 */
	public function validate($data, $view = null)
	{
		// Get the form.
		$form = $this->getForm($view);

		// Check for an error.
		if ($form === false)
		{
			return false;
		}

		// Filter and validate the form data.
		$data   = $form->filter($data);
		$return = $form->validate($data);

		// Check for an error.
		if ($return instanceof \Exception)
		{
			Factory::getApplication()->enqueueMessage($return->getMessage(), 'warning');

			return false;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				if ($message instanceof \Exception)
				{
					Factory::getApplication()->enqueueMessage($message->getMessage(), 'warning');
				}
				else
				{
					Factory::getApplication()->enqueueMessage($message, 'warning');
				}
			}

			return false;
		}

		return $data;
	}

	/**
	 * Generates the user ID.
	 *
	 * @return  integer  The user ID.
	 *
	 * @since   3.1
	 */
	protected static function generateRandUserId()
	{
		$session    = Factory::getSession();
		$randUserId = $session->get('randUserId');

		if (empty($randUserId))
		{
			// Create the ID for the root user only once and store in session.
			$randUserId = mt_rand(1, 1000);
			$session->set('randUserId', $randUserId);
		}

		return $randUserId;
	}

	/**
	 * Resets the user ID.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public static function resetRandUserId()
	{
		self::$userId = 0;

		Factory::getSession()->set('randUserId', self::$userId);
	}

	/**
	 * Retrieves the default user ID and sets it if necessary.
	 *
	 * @return  integer  The user ID.
	 *
	 * @since   3.1
	 */
	public static function getUserId()
	{
		if (!self::$userId)
		{
			self::$userId = self::generateRandUserId();
		}

		return self::$userId;
	}

	/**
	 * Method to initialise the database.
	 *
	 * @param   array  $option  The options to use for configuration.
	 *
	 * @return  \JDatabaseDriver|boolean  Database object on success, boolean false on failure
	 *
	 * @since   3.1
	 */
	public function initialise($option)
	{
		$options = $this->getOptions();

		// Get the options as an object for easier handling.
		$options = ArrayHelper::toObject($options);

		// Load the backend language files so that the DB error messages work.
		$lang = Factory::getLanguage();
		$currentLang = $lang->getTag();

		// Load the selected language
		if (LanguageHelper::exists($currentLang, JPATH_ADMINISTRATOR))
		{
			$lang->load('joomla', JPATH_ADMINISTRATOR, $currentLang, true);
		}
		// Pre-load en-GB in case the chosen language files do not exist.
		else
		{
			$lang->load('joomla', JPATH_ADMINISTRATOR, 'en-GB', true);
		}

		// Ensure a database type was selected.
		if (empty($options->db_type))
		{
			Factory::getApplication()->enqueueMessage(\JText::_('INSTL_DATABASE_INVALID_TYPE'), 'warning');

			return false;
		}

		// Ensure that a hostname and user name were input.
		if (empty($options->db_host) || empty($options->db_user))
		{
			Factory::getApplication()->enqueueMessage(\JText::_('INSTL_DATABASE_INVALID_DB_DETAILS'), 'warning');

			return false;
		}

		// Ensure that a database name was input.
		if (empty($options->db_name))
		{
			Factory::getApplication()->enqueueMessage(\JText::_('INSTL_DATABASE_EMPTY_NAME'), 'warning');

			return false;
		}

		// Validate database table prefix.
		if (!preg_match('#^[a-zA-Z]+[a-zA-Z0-9_]*$#', $options->db_prefix))
		{
			Factory::getApplication()->enqueueMessage(\JText::_('INSTL_DATABASE_PREFIX_MSG'), 'warning');

			return false;
		}

		// Validate length of database table prefix.
		if (strlen($options->db_prefix) > 15)
		{
			Factory::getApplication()->enqueueMessage(\JText::_('INSTL_DATABASE_FIX_TOO_LONG'), 'warning');

			return false;
		}

		// Validate length of database name.
		if (strlen($options->db_name) > 64)
		{
			Factory::getApplication()->enqueueMessage(\JText::_('INSTL_DATABASE_NAME_TOO_LONG'), 'warning');

			return false;
		}

		// Workaround for UPPERCASE table prefix for postgresql
		if (in_array($options->db_type, ['pgsql', 'postgresql']))
		{
			if (strtolower($options->db_prefix) != $options->db_prefix)
			{
				Factory::getApplication()->enqueueMessage(\JText::_('INSTL_DATABASE_FIX_LOWERCASE'), 'warning');

				return false;
			}
		}

		// Get a database object.
		try
		{
			if (!property_exists($options, 'db_select'))
			{
				return DatabaseHelper::getDbo(
					$options->db_type,
					$options->db_host,
					$options->db_user,
					$options->db_pass,
					$options->db_name,
					$options->db_prefix,
					isset($options->db_select) ? $options->db_select : false
				);
			}
		}
		catch (\RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage(\JText::sprintf('INSTL_DATABASE_COULD_NOT_CONNECT', $e->getMessage()), 'error');

			return false;
		}
	}

	/**
	 * Method to create a new database.
	 *
	 * @param   array  $options  The configuration options
	 *
	 * @return  boolean
	 *
	 * @since   3.1
	 * @throws  \RuntimeException
	 */
	public function createDatabase($options)
	{
		// Disable autoselect database before it's created.
		$tmpSelect = true;

		if (isset($options['db_select']))
		{
			$tmpSelect = $options['db_select'];
		}

		$options['db_select'] = false;

		$db = $this->initialise($options);

		if ($db === false)
		{
			// Error messages are enqueued by the initialise function, we just need to tell the controller how to redirect
			return false;
		}

		// Get the options as an object for easier handling.
		$options = ArrayHelper::toObject($options);

		// Check database version.
		$type = $options->db_type;

		try
		{
			$db_version = $db->getVersion();
		}
		catch (\RuntimeException $e)
		{
			/*
			 * We may get here if the database doesn't exist, if so then explain that to users instead of showing the database connector's error
			 * This only supports PostgreSQL and the PDO MySQL drivers presently
			 *
			 * Error Messages:
			 * PDO MySQL: [1049] Unknown database 'database_name'
			 * PostgreSQL: Error connecting to PGSQL database
			 */
			if ($type == 'mysql' && strpos($e->getMessage(), '[1049] Unknown database') === 42)
			{
				/*
				 * Now we're really getting insane here; we're going to try building a new JDatabaseDriver instance without the database name
				 * in order to trick the connection into creating the database
				 */
				$altDBoptions = array(
					'driver'   => $options->db_type,
					'host'     => $options->db_host,
					'user'     => $options->db_user,
					'password' => $options->db_pass,
					'prefix'   => $options->db_prefix,
					'select'   => $options->db_select,
				);

				$altDB = \JDatabaseDriver::getInstance($altDBoptions);

				// Try to create the database now using the alternate driver
				try
				{
					$this->createDb($altDB, $options, $altDB->hasUTFSupport());
				}
				catch (\RuntimeException $e)
				{
					// We did everything we could
					throw new \RuntimeException(\JText::_('INSTL_DATABASE_COULD_NOT_CREATE_DATABASE'), 500, $e);
				}

				// If we got here, the database should have been successfully created, now try one more time to get the version
				try
				{
					$db_version = $db->getVersion();
				}
				catch (\RuntimeException $e)
				{
					// We did everything we could
					throw new \RuntimeException(\JText::sprintf('INSTL_DATABASE_COULD_NOT_CONNECT', $e->getMessage()), 500, $e);
				}
			}
			elseif ($type == 'postgresql' && strpos($e->getMessage(), 'Error connecting to PGSQL database') === 42)
			{
				throw new \RuntimeException(\JText::_('INSTL_DATABASE_COULD_NOT_CREATE_DATABASE'), 500, $e);
			}
			// Anything getting into this part of the conditional either doesn't support manually creating the database or isn't that type of error
			else
			{
				throw new \RuntimeException(\JText::sprintf('INSTL_DATABASE_COULD_NOT_CONNECT', $e->getMessage()), 500, $e);
			}
		}

		if (!$db->isMinimumVersion())
		{
			throw new \RuntimeException(\JText::sprintf('INSTL_DATABASE_INVALID_' . strtoupper($type) . '_VERSION', $db_version));
		}

		// @internal Check for spaces in beginning or end of name.
		if (strlen(trim($options->db_name)) <> strlen($options->db_name))
		{
			throw new \RuntimeException(\JText::_('INSTL_DATABASE_NAME_INVALID_SPACES'));
		}

		// @internal Check for asc(00) Null in name.
		if (strpos($options->db_name, chr(00)) !== false)
		{
			throw new \RuntimeException(\JText::_('INSTL_DATABASE_NAME_INVALID_CHAR'));
		}

		// Get database's UTF support.
		$utfSupport = $db->hasUTFSupport();

		// Try to select the database.
		try
		{
			$db->select($options->db_name);
		}
		catch (\RuntimeException $e)
		{
			// If the database could not be selected, attempt to create it and then select it.
			if (!$this->createDb($db, $options, $utfSupport))
			{
				throw new \RuntimeException(\JText::sprintf('INSTL_DATABASE_ERROR_CREATE', $options->db_name), 500, $e);
			}

			$db->select($options->db_name);
		}

		$options = (array) $options;

		// Remove *_errors value.
		foreach ($options as $i => $option)
		{
			if (isset($i['1']) && $i['1'] == '*')
			{
				unset($options[$i]);

				break;
			}
		}

		$options = array_merge(['db_created' => 1], $options);

		// Restore autoselect value after database creation.
		$options['db_select'] = $tmpSelect;

		Factory::getSession()->set('setup.options', $options);

		return true;
	}

	/**
	 * Method to process the old database.
	 *
	 * @param   array  $options  The options array.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function handleOldDatabase($options)
	{
		if (!isset($options['db_created']) || !$options['db_created'])
		{
			return $this->createDatabase($options);
		}

		if (!$db = $this->initialise($options))
		{
			return false;
		}

		// Get the options as an object for easier handling.
		$options = ArrayHelper::toObject($options);

		// Set the character set to UTF-8 for pre-existing databases.
		try
		{
			$db->alterDbCharacterSet($options->db_name);
		}
		catch (\RuntimeException $e)
		{
			// Continue Anyhow
		}

		// Should any old database tables be removed or backed up?
		if ($options->db_old == 'remove')
		{
			// Attempt to delete the old database tables.
			if (!$this->deleteDatabase($db, $options->db_prefix))
			{
				// Message queued by method, simply return
				return false;
			}
		}
		else
		{
			// If the database isn't being deleted, back it up.
			if (!$this->backupDatabase($db, $options->db_prefix))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to create the database tables.
	 *
	 * @param   array  $options  The options array.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function createTables($options)
	{
		if (!isset($options['db_created']) || !$options['db_created'])
		{
			return $this->createDatabase($options);
		}

		if (!$db = $this->initialise($options))
		{
			return false;
		}

		// Get the options as an object for easier handling.
		$options = ArrayHelper::toObject($options);

		// Check database type.
		$type = $options->db_type;

		// Set the character set to UTF-8 for pre-existing databases.
		try
		{
			$db->alterDbCharacterSet($options->db_name);
		}
		catch (\RuntimeException $e)
		{
			// Continue Anyhow
		}

		$serverType = $db->getServerType();

		// Set the appropriate schema script based on UTF-8 support.
		$schema = 'sql/' . $serverType . '/joomla.sql';

		// Check if the schema is a valid file
		if (!is_file($schema))
		{
			Factory::getApplication()->enqueueMessage(\JText::sprintf('INSTL_ERROR_DB', \JText::_('INSTL_DATABASE_NO_SCHEMA')), 'error');

			return false;
		}

		// Attempt to import the database schema.
		if (!$this->populateDatabase($db, $schema))
		{
			return false;
		}

		// Get query object for later database access
		$query = $db->getQuery(true);

		// MySQL only: Attempt to update the table #__utf8_conversion.
		if ($serverType === 'mysql')
		{
			$query->clear()
				->update($db->quoteName('#__utf8_conversion'))
				->set($db->quoteName('converted') . ' = ' . ($db->hasUTF8mb4Support() ? 2 : 1));
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

				return false;
			}
		}

		// Attempt to update the table #__schema.
		$pathPart = JPATH_ADMINISTRATOR . '/components/com_admin/sql/updates/' . $serverType . '/';

		$files = \JFolder::files($pathPart, '\.sql$');

		if (empty($files))
		{
			Factory::getApplication()->enqueueMessage(\JText::_('INSTL_ERROR_INITIALISE_SCHEMA'), 'error');

			return false;
		}

		$version = '';

		foreach ($files as $file)
		{
			if (version_compare($version, \JFile::stripExt($file)) < 0)
			{
				$version = \JFile::stripExt($file);
			}
		}

		$query->clear()
			->insert($db->quoteName('#__schemas'))
			->columns(
				array(
					$db->quoteName('extension_id'),
					$db->quoteName('version_id')
				)
			)
			->values('700, ' . $db->quote($version));
		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (\RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		// Attempt to refresh manifest caches.
		$query->clear()
			->select('*')
			->from('#__extensions');
		$db->setQuery($query);

		$return = true;

		try
		{
			$extensions = $db->loadObjectList();
		}
		catch (\RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			$return = false;
		}

		Factory::$database = $db;
		$installer = Installer::getInstance();

		foreach ($extensions as $extension)
		{
			if (!$installer->refreshManifestCache($extension->extension_id))
			{
				Factory::getApplication()->enqueueMessage(\JText::sprintf('INSTL_DATABASE_COULD_NOT_REFRESH_MANIFEST_CACHE', $extension->name), 'error');

				return false;
			}
		}

		// Load the localise.sql for translating the data in joomla.sql.
		$dblocalise = 'sql/' . $serverType . '/localise.sql';

		if (is_file($dblocalise))
		{
			if (!$this->populateDatabase($db, $dblocalise))
			{
				return false;
			}
		}

		// Handle default backend language setting. This feature is available for localized versions of Joomla.
		$languages = Factory::getApplication()->getLocaliseAdmin($db);

		if (in_array($options->language, $languages['admin']) || in_array($options->language, $languages['setup']))
		{
			// Build the language parameters for the language manager.
			$params = array();

			// Set default administrator/site language to sample data values.
			$params['administrator'] = 'en-GB';
			$params['site']          = 'en-GB';

			if (in_array($options->language, $languages['admin']))
			{
				$params['administrator'] = $options->language;
			}

			if (in_array($options->language, $languages['site']))
			{
				$params['site'] = $options->language;
			}

			$params = json_encode($params);

			// Update the language settings in the language manager.
			$query->clear()
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('params') . ' = ' . $db->quote($params))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_languages'));
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

				$return = false;
			}
		}

		return $return;
	}

	/**
	 * Method to install the sample data.
	 *
	 * @param   array  $options  The options array.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function installSampleData($options)
	{
		if (!isset($options['db_created']) || !$options['db_created'])
		{
			return $this->createDatabase($options);
		}

		if (!$db = $this->initialise($options))
		{
			return false;
		}

		// Get the options as an object for easier handling.
		$options = ArrayHelper::toObject($options);

		// Build the path to the sample data file.
		$type = $db->getServerType();

		$data = JPATH_INSTALLATION . '/sql/' . $type . '/' . $options->sample_file;

		// Attempt to import the database schema if one is chosen.
		if ($options->sample_file != '')
		{
			if (!file_exists($data))
			{
				Factory::getApplication()->enqueueMessage(\JText::sprintf('INSTL_DATABASE_FILE_DOES_NOT_EXIST', $data), 'error');

				return false;
			}
			elseif (!$this->populateDatabase($db, $data))
			{
				return false;
			}

			$this->postInstallSampleData($db, $options->sample_file);
		}

		return true;
	}

	/**
	 * Sample data tables and data post install process.
	 *
	 * @param   \JDatabaseDriver  $db              Database connector object $db*.
	 * @param   string            $sampleFileName  The sample dats filename.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function postInstallSampleData($db, $sampleFileName = '')
	{
		// Update the sample data user ids.
		$this->updateUserIds($db);

		// If not joomla sample data for testing, update the sample data dates.
		if ($sampleFileName !== 'sample_testing.sql')
		{
			$this->updateDates($db);
		}
	}

	/**
	 * Method to install the cms data.
	 *
	 * @param   array  $options  The options array.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.6.1
	 */
	public function installCmsData($options)
	{
		// Attempt to create the database tables.
		if (!$this->createTables($options))
		{
			return false;
		}

		if (!$db = $this->initialise($options))
		{
			return false;
		}

		// Run Cms data post install to update user ids.
		return $this->postInstallCmsData($db);
	}

	/**
	 * Cms tables and data post install process.
	 *
	 * @param   \JDatabaseDriver  $db  Database connector object $db*.
	 *
	 * @return  void
	 *
	 * @since   3.6.1
	 */
	protected function postInstallCmsData($db)
	{
		// Update the cms data user ids.
		$this->updateUserIds($db);

		// Update the cms data dates.
		$this->updateDates($db);
	}

	/**
	 * Method to update the user id of sql data content to the new rand user id.
	 *
	 * @param   \JDatabaseDriver  $db  Database connector object $db*.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.6.1
	 */
	protected function updateUserIds($db)
	{
		// Create the ID for the root user.
		$userId = self::getUserId();

		// Update all core tables created_by fields of the tables with the random user id.
		$updatesArray = array(
			'#__banners'         => array('created_by', 'modified_by'),
			'#__categories'      => array('created_user_id', 'modified_user_id'),
			'#__contact_details' => array('created_by', 'modified_by'),
			'#__content'         => array('created_by', 'modified_by'),
			'#__fields'          => array('created_user_id', 'modified_by'),
			'#__finder_filters'  => array('created_by', 'modified_by'),
			'#__newsfeeds'       => array('created_by', 'modified_by'),
			'#__tags'            => array('created_user_id', 'modified_user_id'),
			'#__ucm_content'     => array('core_created_user_id', 'core_modified_user_id'),
			'#__ucm_history'     => array('editor_user_id'),
			'#__user_notes'      => array('created_user_id', 'modified_user_id'),
		);

		foreach ($updatesArray as $table => $fields)
		{
			foreach ($fields as $field)
			{
				$query = $db->getQuery(true)
					->update($db->quoteName($table))
					->set($db->quoteName($field) . ' = ' . $db->quote($userId))
					->where($db->quoteName($field) . ' != 0')
					->where($db->quoteName($field) . ' IS NOT NULL');

				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (\RuntimeException $e)
				{
					Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}
	}

	/**
	 * Method to update the dates of sql data content to the current date.
	 *
	 * @param   \JDatabaseDriver  $db  Database connector object $db*.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.7.0
	 */
	protected function updateDates($db)
	{
		// Get the current date.
		$currentDate = Factory::getDate()->toSql();
		$nullDate    = $db->getNullDate();

		// Update all core tables date fields of the tables with the current date.
		$updatesArray = array(
			'#__banners'             => array('publish_up', 'publish_down', 'reset', 'created', 'modified'),
			'#__banner_tracks'       => array('track_date'),
			'#__categories'          => array('created_time', 'modified_time'),
			'#__contact_details'     => array('publish_up', 'publish_down', 'created', 'modified'),
			'#__content'             => array('publish_up', 'publish_down', 'created', 'modified'),
			'#__contentitem_tag_map' => array('tag_date'),
			'#__fields'              => array('created_time', 'modified_time'),
			'#__finder_filters'      => array('created', 'modified'),
			'#__finder_links'        => array('indexdate', 'publish_start_date', 'publish_end_date', 'start_date', 'end_date'),
			'#__messages'            => array('date_time'),
			'#__modules'             => array('publish_up', 'publish_down'),
			'#__newsfeeds'           => array('publish_up', 'publish_down', 'created', 'modified'),
			'#__redirect_links'      => array('created_date', 'modified_date'),
			'#__tags'                => array('publish_up', 'publish_down', 'created_time', 'modified_time'),
			'#__ucm_content'         => array('core_created_time', 'core_modified_time', 'core_publish_up', 'core_publish_down'),
			'#__ucm_history'         => array('save_date'),
			'#__users'               => array('registerDate', 'lastvisitDate', 'lastResetTime'),
			'#__user_notes'          => array('publish_up', 'publish_down', 'created_time', 'modified_time'),
		);

		foreach ($updatesArray as $table => $fields)
		{
			foreach ($fields as $field)
			{
				$query = $db->getQuery(true)
					->update($db->quoteName($table))
					->set($db->quoteName($field) . ' = ' . $db->quote($currentDate))
					->where($db->quoteName($field) . ' != ' . $db->quote($nullDate));

				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (\RuntimeException $e)
				{
					Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}
	}

	/**
	 * Method to backup all tables in a database with a given prefix.
	 *
	 * @param   \JDatabaseDriver  $db      JDatabaseDriver object.
	 * @param   string            $prefix  Database table prefix.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since    3.1
	 */
	public function backupDatabase($db, $prefix)
	{
		$return = true;
		$backup = 'bak_' . $prefix;

		// Get the tables in the database.
		$tables = $db->getTableList();

		if ($tables)
		{
			foreach ($tables as $table)
			{
				// If the table uses the given prefix, back it up.
				if (strpos($table, $prefix) === 0)
				{
					// Backup table name.
					$backupTable = str_replace($prefix, $backup, $table);

					// Drop the backup table.
					try
					{
						$db->dropTable($backupTable, true);
					}
					catch (\RuntimeException $e)
					{
						Factory::getApplication()->enqueueMessage(\JText::sprintf('INSTL_DATABASE_ERROR_BACKINGUP', $e->getMessage()), 'error');

						$return = false;
					}

					// Rename the current table to the backup table.
					try
					{
						$db->renameTable($table, $backupTable, $backup, $prefix);
					}
					catch (\RuntimeException $e)
					{
						Factory::getApplication()->enqueueMessage(\JText::sprintf('INSTL_DATABASE_ERROR_BACKINGUP', $e->getMessage()), 'error');

						$return = false;
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Method to create a new database.
	 *
	 * @param   \JDatabaseDriver  $db       \JDatabase object.
	 * @param   CMSObject         $options  CMSObject coming from "initialise" function to pass user
	 *                                      and database name to database driver.
	 * @param   boolean           $utf      True if the database supports the UTF-8 character set.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function createDb($db, $options, $utf)
	{
		// Build the create database query.
		try
		{
			// Run the create database query.
			$db->createDatabase($options, $utf);
		}
		catch (\RuntimeException $e)
		{
			// If an error occurred return false.
			return false;
		}

		return true;
	}

	/**
	 * Method to delete all tables in a database with a given prefix.
	 *
	 * @param   \JDatabaseDriver  $db      JDatabaseDriver object.
	 * @param   string            $prefix  Database table prefix.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function deleteDatabase($db, $prefix)
	{
		$return = true;

		// Get the tables in the database.
		$tables = $db->getTableList();

		if ($tables)
		{
			foreach ($tables as $table)
			{
				// If the table uses the given prefix, drop it.
				if (strpos($table, $prefix) === 0)
				{
					// Drop the table.
					try
					{
						$db->dropTable($table);
					}
					catch (\RuntimeException $e)
					{
						Factory::getApplication()->enqueueMessage(\JText::sprintf('INSTL_DATABASE_ERROR_DELETE', $e->getMessage()), 'error');

						$return = false;
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Method to import a database schema from a file.
	 *
	 * @param   \JDatabaseDriver  $db      JDatabase object.
	 * @param   string            $schema  Path to the schema file.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function populateDatabase($db, $schema)
	{
		$return = true;

		// Get the contents of the schema file.
		if (!($buffer = file_get_contents($schema)))
		{
			Factory::getApplication()->enqueueMessage($db->getErrorMsg(), 'error');

			return false;
		}

		// Get an array of queries from the schema and process them.
		$queries = $this->splitQueries($buffer);

		foreach ($queries as $query)
		{
			// Trim any whitespace.
			$query = trim($query);

			// If the query isn't empty and is not a MySQL or PostgreSQL comment, execute it.
			if (!empty($query) && ($query{0} != '#') && ($query{0} != '-'))
			{
				/**
				 * If we don't have UTF-8 Multibyte support we'll have to convert queries to plain UTF-8
				 *
				 * Note: the JDatabaseDriver::convertUtf8mb4QueryToUtf8 performs the conversion ONLY when
				 * necessary, so there's no need to check the conditions in JInstaller.
				 */
				if ($db instanceof UTF8MB4SupportInterface)
				{
					$query = $db->convertUtf8mb4QueryToUtf8($query);

					/**
					 * This is a query which was supposed to convert tables to utf8mb4 charset but the server doesn't
					 * support utf8mb4. Therefore we don't have to run it, it has no effect and it's a mere waste of time.
					 */
					if (!$db->hasUTF8mb4Support() && stristr($query, 'CONVERT TO CHARACTER SET utf8 '))
					{
						continue;
					}
				}

				// Execute the query.
				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (\RuntimeException $e)
				{
					Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

					$return = false;
				}
			}
		}

		return $return;
	}

	/**
	 * Method to split up queries from a schema file into an array.
	 *
	 * @param   string  $query  SQL schema.
	 *
	 * @return  array  Queries to perform.
	 *
	 * @since   3.1
	 */
	protected function splitQueries($query)
	{
		$buffer    = array();
		$queries   = array();
		$in_string = false;

		// Trim any whitespace.
		$query = trim($query);

		// Remove comment lines.
		$query = preg_replace("/\n\#[^\n]*/", '', "\n" . $query);

		// Remove PostgreSQL comment lines.
		$query = preg_replace("/\n\--[^\n]*/", '', "\n" . $query);

		// Find function.
		$funct = explode('CREATE OR REPLACE FUNCTION', $query);

		// Save sql before function and parse it.
		$query = $funct[0];

		// Parse the schema file to break up queries.
		for ($i = 0; $i < strlen($query) - 1; $i++)
		{
			if ($query[$i] == ';' && !$in_string)
			{
				$queries[] = substr($query, 0, $i);
				$query     = substr($query, $i + 1);
				$i         = 0;
			}

			if ($in_string && ($query[$i] == $in_string) && $buffer[1] != "\\")
			{
				$in_string = false;
			}
			elseif (!$in_string && ($query[$i] == '"' || $query[$i] == "'") && (!isset ($buffer[0]) || $buffer[0] != "\\"))
			{
				$in_string = $query[$i];
			}

			if (isset ($buffer[1]))
			{
				$buffer[0] = $buffer[1];
			}

			$buffer[1] = $query[$i];
		}

		// If the is anything left over, add it to the queries.
		if (!empty($query))
		{
			$queries[] = $query;
		}

		// Add function part as is.
		for ($f = 1, $fMax = count($funct); $f < $fMax; $f++)
		{
			$queries[] = 'CREATE OR REPLACE FUNCTION ' . $funct[$f];
		}

		return $queries;
	}
}