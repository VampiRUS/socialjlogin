<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

class plgSocialjloginintegrationJomsocial extends JPlugin{

	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onSocialLogin($id,$name){
		$db = JFactory::getDBO();
		$db->setQuery('SELECT * FROM #__socialjlogin WHERE socid='.$db->Quote($id).' and `type`='.$db->Quote($name).' LIMIT 1');
		$data = $db->loadObject();

		$db->setQuery('SELECT count(userid) from #__community_users where userid='.$data->userid);
		$exists = $db->loadResult();
		if (!$exists){
			$avatar = $data->photo;
			$thumb = ($data->photo_rec)?$data->photo_rec:$data->photo;
			if (strpos($thumb,'http://')===0){
				$img = file_get_contents($thumb);
				if ($img && file_put_contents(JPATH_ROOT.'/images/avatar/thumb_'.md5($thumb).'.jpg',$img)){
					$thumb = 'images/avatar/thumb_'.md5($thumb).'.jpg';
				} else {
					$thumb = '';
				}
			}
			if (strpos($avatar,'http://')===0){
				$img = file_get_contents($avatar);
				if ($img && file_put_contents(JPATH_ROOT.'/images/avatar/'.md5($avatar).'.jpg',$img)){
					$avatar = 'images/avatar/'.md5($avatar).'.jpg';
				} else {
					$avatar = '';
				}
			}
			$db->setQuery('INSERT INTO #__community_users (userid,avatar,thumb) VALUES ('.$data->userid.','.$db->Quote($avatar).','.$db->Quote($thumb).')');
			$db->query();
			$sql = 'INSERT INTO #__community_fields_values (user_id, 	field_id, 	value) VALUES ';
			$sqldata = array();
			if (isset($data->sex)&&$data->sex) {
				switch($data->sex){
					case 1:$sex='Female';break;
					case 2:
					default:$sex='Male';
				}
				$sqldata[] = '('.$data->userid.',2,'.$db->Quote($sex).')';
			}
			if (isset($data->bdate)&&$data->bdate) {
				$time = strtotime($data->bdate);
				$sqldata[] = '('.$data->userid.',3,'.$db->Quote(@date("Y-m-d",$time)).')';
			}
			if (isset($data->city)&&$data->city) {
				$sqldata[] = '('.$data->userid.',10,'.$db->Quote($data->city).')';
			}
			if (isset($data->link)&&$data->link) {
				$sqldata[] = '('.$data->userid.',12,'.$db->Quote($data->link).')';
			}
			if (isset($data->home_phone)&&$data->home_phone) {
				$sqldata[] = '('.$data->userid.',7,'.$db->Quote($data->home_phone).')';
			}
			if (isset($data->mobile_phone)&&$data->mobile_phone) {
				$sqldata[] = '('.$data->userid.',6,'.$db->Quote($data->mobile_phone).')';
			}
			if ((isset($data->university_name)&&$data->university_name)||(isset($data->faculty_name)&&$data->faculty_name)) {
				$sqldata[] = '('.$data->userid.',14,'.$db->Quote($data->university_name.' '.$data->faculty_name).')';
			}
			if (isset($data->graduation)&&$data->graduation) {
				$sqldata[] = '('.$data->userid.',15,'.$db->Quote($data->graduation).')';
			}
			if (!empty($sqldata)){
				$sql .= implode(',',$sqldata);
				$db->setQuery($sql);
				$db->query();
			}
		}
		return null;
	}
}
?>
