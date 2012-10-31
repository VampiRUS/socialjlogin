<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

class SocialjloginController extends JControllerLegacy
{
	public function display($cachable = false, $urlparams = false)
	{
		//TODO страница с выводом кнопок авторизации
		$cachable	= true;	// Huh? Why not just put that in the constructor?
		$user		= JFactory::getUser();
		if ($user->id){
			JRequest::setVar('view', 'merge');
		} else {
			JRequest::setVar('view', 'login');
		}
		return parent::display($cachable);
	}
	
	public function login(){
		$dispatcher	= JDispatcher::getInstance();
		$referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
		if (JURI::isInternal($referer)){
			$_SESSION['return'] = $referer;
		}
		JPluginHelper::importPlugin('socialjlogin',JRequest::getCmd('type'));
		if (JFactory::getUser()->id){
			$results = $dispatcher->trigger('onMerge');
		} else {
			$results = $dispatcher->trigger('onLogin');
		}
	}
}
