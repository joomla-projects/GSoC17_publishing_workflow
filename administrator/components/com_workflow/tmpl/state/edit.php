<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', '.advancedSelect', null, array('disable_search_threshold' => 0));

$app   = JFactory::getApplication();
$input = $app->input;

// In case of modal
$isModal = $input->get('layout') == 'modal' ? true : false;
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<form action="<?php echo JRoute::_('index.php?option=com_workflow&view=state&workflow_id=' . $input->getCmd('workflow_id') . '&extension=' . $input->getCmd('extension') . '&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="workflow-form" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', \JText::_('COM_WORKFLOW_DESCRIPTION')); ?>
	<div class="row">
		<div class="col-md-9">
			<?php echo $this->form->renderField('condition'); ?>
			<?php echo $this->form->getInput('description'); ?>
		</div>
		<div class="col-md-3">
			<div class="card card-block card-light">
				<div class="card-body">
					<fieldset class="form-vertical form-no-margin">
						<?php echo $this->form->renderField('published'); ?>
						<?php echo $this->form->renderField('default'); ?>
					</fieldset>
				</div>
			</div>
		</div>
	</div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>

	<?php echo JHtml::_('bootstrap.endTabSet'); ?>

	<?php echo $this->form->getInput('workflow_id'); ?>
	<input type="hidden" name="task" value="state.edit" />
	<?php echo JHtml::_('form.token'); ?>
</form>
