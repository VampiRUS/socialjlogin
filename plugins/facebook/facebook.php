<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT .'/components/com_socialjlogin/plugin.php');

class plgSocialjloginFacebook extends SocialjloginPlugin
{

	protected $name = 'facebook';
	private $email = '';
	private $username = '';
	private $realname = '';
	private $id = 0;
	private $facebook = null;
	private $user_profile;

	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		require_once("lib/facebook.php");

		$config = array();
		$config['appId'] = $this->params->get('appid', '');
		$config['secret'] =  $this->params->get('secret','');
		$config['fileUpload'] = false; // optional

		$this->facebook = new Facebook($config);
		$this->loadLanguage();
	}

	public function onLogin()
	{
		$uid = $this->facebook->getUser();
		if ($uid){
			$this->user_profile = $this->facebook->api('/me?fields=id,name,email,username,gender,first_name,last_name,middle_name,timezone,picture,link');
			$this->realname = $this->user_profile['name'];
			$this->username = $this->user_profile['username'].'_'.$this->name.$this->user_profile['id'];
			$this->email = $this->user_profile['email'];
			$this->id = $this->user_profile['id'];
			$this->user_id = $this->getUserId();
			if ($this->user_id){
				$this->login();
			} else {
				$this->registration();
			}
		} else {
			//TODO http://api.mail.ru/docs/guides/oauth/sites/ - make uniq urls
			$params = array(
			  'scope' => 'email',
			  'redirect_uri' => JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name
			);

			$loginUrl = $this->facebook->getLoginUrl($params);
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}

	public function onAuth($options){
		$this->id = $this->facebook->getUser();
		$data = array();
		if ($this->id) {
			//like on vk.com
			switch ($this->user_profile['gender']){
				case 'female':$gender = 1;break;
				case 'male':$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;
			if(isset($this->user_profile['first_name']))$data['first_name'] = $this->user_profile['first_name'];
			if(isset($this->user_profile['last_name']))$data['last_name'] = $this->user_profile['last_name'];
			if(isset($this->user_profile['middle_name']))$data['middle_name'] = $this->user_profile['middle_name'];
			if(isset($this->user_profile['timezone']))$data['timezone'] = $this->user_profile['timezone'];
			if(isset($this->user_profile['picture']))$data['photo'] = $this->user_profile['picture']['data']['url'];
			if(isset($this->user_profile['username']))$data['nickname'] = $this->user_profile['username'];
			if(isset($this->user_profile['link']))$data['link'] = $this->user_profile['link'];
			//TODO params for request birthday, bio , etc
			$this->updateUserInfo($data);
			return $this->getResponse();
		} else {
			return false;
		}
	}

	public function onMerge(){
		$this->id = $uid = $this->facebook->getUser();
		if ($uid){
			$this->user_profile = $this->facebook->api('/me?fields=id,name,email,username,gender,first_name,last_name,middle_name,timezone,picture,username,link');
			//like on vk.com
			switch ($this->user_profile['gender']){
				case 'female':$gender = 1;break;
				case 'male':$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;
			$this->email = $this->user_profile['email'];
			if(isset($this->user_profile['first_name']))$data['first_name'] = $this->user_profile['first_name'];
			if(isset($this->user_profile['last_name']))$data['last_name'] = $this->user_profile['last_name'];
			if(isset($this->user_profile['middle_name']))$data['middle_name'] = $this->user_profile['middle_name'];
			if(isset($this->user_profile['timezone']))$data['timezone'] = $this->user_profile['timezone'];
			if(isset($this->user_profile['picture']))$data['photo'] = $this->user_profile['picture']['data']['url'];
			if(isset($this->user_profile['username']))$data['nickname'] = $this->user_profile['username'];
			if(isset($this->user_profile['link']))$data['link'] = $this->user_profile['link'];
			$this->mergeUserInfo($data);
		} else {
			$params = array(
			  'scope' => 'email',
			  'redirect_uri' => JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name
			);

			$loginUrl = $this->facebook->getLoginUrl($params);
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}
	protected function getEmail(){return $this->email;}
	protected function getUsername(){return $this->username;}
	protected function getName(){return $this->realname;}
	protected function getSocId(){return $this->id;}
}

