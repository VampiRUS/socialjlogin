<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgUserSocjuser extends JPlugin {

	public function onUserAfterDelete($user, $succes, $msg)
	{
		$db = JFactory::getDBO();
		$db->setQuery('DELETE FROM #__socialjlogin where userid='.$user['id']);
		$db->query();
	}
	


	public function onUserLogout($user, $options = array())
	{
		$dispatcher	= JDispatcher::getInstance();
		JPluginHelper::importPlugin('socialjlogin');
		$result = $dispatcher->trigger('onLogout',$user,$options);
	}
	
	
}
