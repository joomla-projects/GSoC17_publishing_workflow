<?php
/**
 * Item Model for a Prove Component.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_prove
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       4.0
 */
namespace Joomla\Component\Workflow\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Component\Workflow\Administrator\Helper\WorkflowHelper;

/**
 * The first example class, this is in the same
 * package as declared at the start of file but
 * this example has a defined subpackage
 *
 * @since  4.0
 */
class State extends AdminModel
{

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return   boolean  True on success.
	 *
	 * @since 4.0
	 */
	public function save($data)
	{
		$context    = $this->option . '.' . $this->name;
		$app = \JFactory::getApplication();
		$workflowID = $app->getUserStateFromRequest($context . '.filter.workflow_id', 'workflow_id', 0, 'int');

		if (empty($data['workflow_id']))
		{
			$data['workflow_id'] = $workflowID;
		}

		return parent::save($data);
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 *
	 * @since   4.0
	 * @throws  \UnexpectedValueException
	 */
	protected function canDelete($record)
	{
		if (!\JFactory::getUser()->authorise('core.delete', 'com_workflows'))
		{
			throw new \Exception(\JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
		}

		$app = \JFactory::getApplication();
		$extension = $app->getUserStateFromRequest('com_workflow.state.filter.extension', 'extension', 'com_content', 'cmd');

		$isAssigned = WorkflowHelper::callMethodFromHelper($extension, 'canDeleteState', $record->id);

		if ($isAssigned && !$record->default)
		{
			return true;
		}
		elseif ($isAssigned === null && !$record->default)
		{
			return true;
		}
		else
		{
			$this->setError(\JText::_('COM_WORKFLOW_MSG_DELETE_IS_ASSIGNED'));

			return false;
		}
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return \JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since 4.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_workflow.state',
			'state',
			array(
				'control' => 'jform',
				'load_data' => $loadData
			)
		);

		$item = $this->getItem($form->getValue('id'));

		// Deactivate switcher if default
		// Use $item, otherwise we'll be locked when we get the data from the request
		if (!empty($item->default))
		{
			$form->setValue('default', null, 1);
			$form->setFieldAttribute('default', 'readonly', 'true');
		}

		return $form;
	}


	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed  The data for the form.
	 *
	 * @since 4.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = \JFactory::getApplication()->getUserState(
			'com_workflow.edit.state.data',
			array()
		);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to change the home state of one or more items.
	 *
	 * @param   array    $pk     A list of the primary keys to change.
	 * @param   integer  $value  The value of the home state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   4.0
	 */
	public function setHome($pk, $value = 1)
	{
		$table = $this->getTable();

		if ($table->load(array('id' => $pk)))
		{
			if ($table->published !== 1)
			{
				$this->setError(\JText::_("COM_WORKFLOW_ITEM_MUST_PUBLISHED"));

				return false;
			}
		}

		if ($value)
		{
			// Verify that the home page for this language is unique per client id
			if ($table->load(array('default' => '1', 'workflow_id' => $table->workflow_id)))
			{
				$table->default = 0;
				$table->store();
			}
		}

		if ($table->load(array('id' => $pk)))
		{
			$table->default = $value;
			$table->store();
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
		$table = $this->getTable();
		$pks   = (array) $pks;
		$app = Factory::getApplication();
		$extension = $app->getUserStateFromRequest('com_workflow.state.filter.extension', 'extension', 'com_content', 'cmd');

		// Default item existence checks.
		if ($value != 1)
		{
			foreach ($pks as $i => $pk)
			{
				if ($table->load(array('id' => $pk)) && $table->default)
				{
					// Prune items that you can't change.
					$app->enqueueMessage(\JText::_('COM_WORKFLOW_ITEM_MUST_PUBLISHED'), 'error');
					unset($pks[$i]);
				}

				if (!WorkflowHelper::callMethodFromHelper($extension, 'canDeleteState', $pks[$i]))
				{
					$app->enqueueMessage(\JText::_('COM_WORKFLOW_MSG_DELETE_IS_ASSIGNED'), 'error');
					unset($pks[$i]);
				}
			}
		}

		return parent::publish($pks, $value);
	}
}
