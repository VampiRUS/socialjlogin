<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

jimport('joomla.application.categories');

function SocialJLoginBuildRoute(&$query)
{
	$segments = array();

	// get a menu item based on Itemid or currently active
	$app		= JFactory::getApplication();
	$menu		= $app->getMenu();
	// we need a menu item.  Either the one specified in the query, or the current active one if none specified
	if (empty($query['Itemid'])) {
		$menuItem = $menu->getActive();
	}
	else {
		$menuItem = $menu->getItem($query['Itemid']);
	}

	$mView	= (empty($menuItem->query['view'])) ? null : $menuItem->query['view'];
	$mType	= (empty($menuItem->query['type'])) ? null : $menuItem->query['type'];
	$mTask	= (empty($menuItem->query['task'])) ? null : $menuItem->query['task'];

	if (isset($query['view'])) {
		$view = $query['view'];

		if (empty($query['Itemid'])) {
			$segments[] = $query['view'];
		}

		unset($query['view']);
	}

	if (isset($query['task'])) {
		$segments[] = $query['task'];
		unset($query['task']);
	}
	if (isset($query['type'])) {
		$segments[] = $query['type'];
		unset($query['type']);
	}

	return $segments;
}
/**
 * Parse the segments of a URL.
 *
 * @param	array	The segments of the URL to parse.
 *
 * @return	array	The URL attributes to be used by the application.
 */
function SocialJLoginParseRoute($segments)
{
	$vars = array();

	//Get the active menu item.
	$app	= JFactory::getApplication();
	$menu	= $app->getMenu();
	$item	= $menu->getActive();

	// Count route segments
	$count = count($segments);

	// Standard routing for weblinks.
	if ($count == 1) {
		$vars['view']	= $segments[0];
	}
	if ($count == 2) {
		$vars['task'] = $segments[0];
		$vars['type'] = $segments[1];
	}

	// From the categories view, we can only jump to a category.

	return $vars;
}
