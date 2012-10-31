<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;


$controller	= JControllerLegacy::getInstance('Socialjlogin');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();
