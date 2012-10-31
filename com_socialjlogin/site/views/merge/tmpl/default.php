<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
?>
<h3><?php echo JText::_('COM_SOCIALJLOGIN_MERGE_ACCOUNTS')?></h3>
<?php foreach($this->icons as $icon):
 echo $icon.'&nbsp;';
endforeach;?>
