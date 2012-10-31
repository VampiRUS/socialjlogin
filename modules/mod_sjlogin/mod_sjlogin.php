<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once dirname(__FILE__).'/helper.php';

$params->def('greeting', 1);

$type	= modSJLoginHelper::getType();
$return	= modSJLoginHelper::getReturnURL($params, $type);
$user	= JFactory::getUser();
$dispatcher	= JDispatcher::getInstance();
JPluginHelper::importPlugin('socialjlogin');
$icons = $dispatcher->trigger('getIcon');
$avatar = modSJLoginHelper::getAvatar();

require JModuleHelper::getLayoutPath('mod_sjlogin', $params->get('layout', 'default'));
