<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Workflow\Administrator\View\Graph;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Workflows view class for the Workflow package.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * An array of states
	 *
	 * @var     array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $states;

	/**
	 * An array of transitions
	 *
	 * @var     array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $transitions;

	/**
	 * The model state
	 *
	 * @var     object
	 * @since  __DEPLOY_VERSION__
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$this->state         	= $this->get('State');
		$this->states			= $this->get('States');
		$this->transitions    	= $this->get('Transitions');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \JViewGenericdataexception(implode("\n", $errors), 500);
		}

		HTMLHelper::_('script', 'com_workflow/antuane-chart/antuane-chart.min.js', ['relative' => true]);

		$chart = [
			'diagrams' => [],
			'links' => []
		];

		$chart['config'] = new \stdClass;

		$chart['config']->element = 'workflow-graph';
		$chart['config']->autoSize = true;
		$chart['config']->margin = 30;
		$chart['config']->padding = 10;
		$chart['config']->width = 100;
		$chart['config']->height = 50;
		$chart['config']->radius = 3;
		$chart['config']->lineColor = '#cccccc';
		$chart['config']->arrowWidth = 8;
		$chart['config']->lineWidth = 2;
		$chart['config']->hiddenBg = false;
		$chart['config']->mouseEvents = true;
		$chart['config']->lineDiff = true;
		$chart['config']->fontSize = 12;

		foreach ($this->states as $state)
		{
			switch ($state->condition)
			{
				case 1:
					$state->color = '#3c763d';
					$state->bgColor = '#dff0d8';
					break;

				case 0:
					$state->color = '#a94442';
					$state->bgColor = '#f2dede';
					break;

				default:
					$state->color = '#31708f';
					$state->bgColor = '#d9edf7';
			}

			$state->text = HTMLHelper::_('string.abridge', $state->title, 30, 20);

			$chart['diagrams'][] = $state;
		}

		foreach ($this->transitions as $transition)
		{
			$chart['links'][] = $transition;
		}

		$js = "
			;(function($)
			{
				$(function()
				{
					$('html,body').height('100%');

					var chart = new AntuaneChart(" . json_encode($chart) . ");
				});
			})(jQuery);
		";

		//echo $js;

		Factory::getDocument()->addScriptDeclaration($js);

		Factory::getApplication()->input->set('tmpl', 'component');

		return parent::display($tpl);
	}
}
