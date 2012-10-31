<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

class SocialjloginViewMerge extends JViewLegacy
{
	function display($tpl = null)
	{
		$db = JFactory::getDBO();
		$user = JFactory::getUser();
		$db->setQuery('SELECT `type` FROM #__socialjlogin where userid='.$user->id);
		$connected = $db->loadResultArray();
		JPluginHelper::importPlugin('socialjlogin');
		$plugins = JPluginHelper::getPlugin('socialjlogin');
		$icons = array();
		$dispatcher = JDispatcher::getInstance();
		foreach ($plugins as $plugin)
		{
			if (in_array($plugin->name,$connected))continue;
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className))
			{
				$plugin = new $className($dispatcher, (array) $plugin);
			}
			else
			{
				// Bail here if the plugin can't be created
				JError::raiseWarning(50, JText::sprintf('COM_SOCIALJLOGIN_FAILED_LOAD_PLUGIN', $className));
				continue;
			}
			$icons[] = $plugin->getIcon();
		}
		$this->assignRef('icons',		$icons);

		parent::display($tpl);
	}

}
