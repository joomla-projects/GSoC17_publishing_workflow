<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Workflow\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Joomla\CMS\Mvc\Factory\MvcFactoryInterface;
use Joomla\CMS\Controller\Controller as BaseController;

/**
 * Workflow base controller package.
 *
 * @since  1.6
 */
class Controller extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $default_view = 'workflows';

	/**
	 * The extension for which the categories apply.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $extension = "";

	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 * @param   MvcFactoryInterface  $factory  The factory.
	 * @param   CMSApplication       $app      The JApplication for the dispatcher
	 * @param   \JInput              $input    Input
	 *
	 * @since   3.0
	 */
	public function __construct($config = array(), MvcFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		// Guess the \JText message prefix. Defaults to the option.
		if (empty($this->extension))
		{
			$this->extension = $this->input->get('extension', 'com_content');
		}
	}
}