<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
class pkg_socialjloginInstallerScript{
	public function postflight( $type, $parent,$result ) {
		if ($type=='install'){
			$db = JFactory::getDBO();
			$db->setQuery('UPDATE #__extensions set enabled=1 where `type`="plugin" and (
				(element="socjuser" and folder="user")
				or (element="socialjlogin" and folder="authentication"))');
			$db->query();
		}
		return true;
	}
}
