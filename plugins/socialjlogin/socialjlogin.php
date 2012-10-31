<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

class plgAuthenticationSocialjlogin extends JPlugin
{
	function onUserAuthenticate($credentials, $options, &$response)
	{
		$response->type = 'SocialjLogin';
		if ($credentials['username']!='SOCJ_LOGIN'||$credentials['password']!='SOCJ_PASSWORD') {
			return false;
		}

		$dispatcher	= JDispatcher::getInstance();
		JPluginHelper::importPlugin('socialjlogin',$options['type']);
		$result = $dispatcher->trigger('onAuth',$options);

		if ($result[0]) {
				$user = JUser::getInstance($result[0]->id); // Bring this in line with the rest of the system
				$response->email = $user->email;
				$response->fullname = $user->name;
				$response->username = $user->username;
				$response->password = $result[0]->password;
				$response->language = $user->getParam('language');
				$response->status = JAuthentication::STATUS_SUCCESS;
				$response->error_message = '';
				return true;
		} else {
			$response->status = JAuthentication::STATUS_FAILURE;
			$response->error_message = JText::_('JGLOBAL_AUTH_NO_USER');
		}
	}
}
