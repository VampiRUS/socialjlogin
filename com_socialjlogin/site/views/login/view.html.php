<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

class SocialjloginViewLogin extends JViewLegacy
{
	function display($tpl = null)
	{
		$dispatcher	= JDispatcher::getInstance();
		JPluginHelper::importPlugin('socialjlogin');
		$icons = $dispatcher->trigger('getIcon');
		$this->assignRef('icons',		$icons);

		parent::display($tpl);
	}

}
