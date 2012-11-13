<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT .'/components/com_socialjlogin/plugin.php');

class plgSocialjloginVKontakte extends SocialjloginPlugin
{

	protected $name = 'vkontakte';
	private $email = '';
	private $username = '';
	private $realname = '';
	private $id = 0;
	private $user_profile;
	private $token;

	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onLogin()
	{

		$error = JRequest::getVar('error','');
		if ($error){
			JError::raiseError(500,JRequest::getVar('error_description'));
		}
		$code = JRequest::getVar('code','');
		if ($code){
			$url = 'https://oauth.vk.com/access_token?client_id='.$this->params->get('appid')
				.'&client_secret='.$this->params->get('secret')
				.'&code='.$code
				.'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name));
			$http = $this->getHttp();
			$response = $http->get($url);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error->error_description);
			}
			$this->id = $result->user_id;
			$this->token = $result->access_token;
			$response = $http->get('https://api.vk.com/method/users.get?uid='.$this->id
				.'&fields=uid,first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo_100,photo_rec,photo_medium_rec,photo_medium,photo_big,has_mobile,contacts,education,relation&access_token='.$this->token);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error->error_msg);
			}
			$this->user_profile = $result->response[0];
			$this->realname = $this->user_profile->first_name.' '.$this->user_profile->last_name;
			$this->username = $this->user_profile->screen_name.'_'.$this->name.$this->user_profile->uid;
			$this->email = $this->user_profile->screen_name.'@vk.com';
			$this->id = $this->user_profile->uid;
			$this->user_id = $this->getUserId();
			if ($this->user_id){
				$this->login();
			} else {
				$this->registration();
			}
		} else {
			//TODO http://api.mail.ru/docs/guides/oauth/sites/ - make uniq urls
			$loginUrl = 'http://oauth.vk.com/authorize?client_id='.$this->params->get('appid').
				'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name)).'&response_type=code' ;
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}

	public function onAuth($options){
		$data = array();
		if ($this->user_profile) {
			if(isset($this->user_profile->sex))$data['sex'] = $this->user_profile->sex;
			if(isset($this->user_profile->first_name))$data['first_name'] = $this->user_profile->first_name;
			if(isset($this->user_profile->last_name))$data['last_name'] = $this->user_profile->last_name;
			if(isset($this->user_profile->screen_name)){
				$data['screen_name'] = $this->user_profile->screen_name;
				$data['link'] = 'http://vk.com/'.$this->user_profile->screen_name;
			}
			if(isset($this->user_profile->timezone))$data['timezone'] = $this->user_profile->timezone;
			if(isset($this->user_profile->photo_100))$data['photo'] = $this->user_profile->photo_100;
			if(isset($this->user_profile->photo_medium))$data['photo_medium'] = $this->user_profile->photo_medium;
			if(isset($this->user_profile->photo_medium_rec))$data['photo_medium_rec'] = $this->user_profile->photo_medium_rec;
			if(isset($this->user_profile->photo_big))$data['photo_big'] = $this->user_profile->photo_big;
			if(isset($this->user_profile->photo_rec))$data['photo_rec'] = $this->user_profile->photo_rec;
			if(isset($this->user_profile->nickname))$data['nickname'] = $this->user_profile->nickname;
			if(isset($this->user_profile->mobile_phone))$data['mobile_phone'] = $this->user_profile->mobile_phone;
			if(isset($this->user_profile->home_phone))$data['home_phone'] = $this->user_profile->home_phone;
			if(isset($this->user_profile->relation))$data['relation'] = $this->user_profile->relation;
			if(isset($this->user_profile->bdate))$data['bdate'] = $this->user_profile->bdate;
			if(isset($this->user_profile->university_name))$data['university_name'] = $this->user_profile->university_name;
			if(isset($this->user_profile->faculty_name))$data['faculty_name'] = $this->user_profile->faculty_name;
			if(isset($this->user_profile->graduation))$data['graduation'] = $this->user_profile->graduation;
			$this->updateUserInfo($data);
			return $this->getResponse();
		} else {
			return false;
		}
	}

	public function onMerge(){
		$error = JRequest::getVar('error','');
		if ($error){
			JError::raiseError(500,JRequest::getVar('error_description'));
		}
		$code = JRequest::getVar('code','');
		if ($code){
			$url = 'https://oauth.vk.com/access_token?client_id='.$this->params->get('appid')
				.'&client_secret='.$this->params->get('secret')
				.'&code='.$code
				.'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name);
			$http = $this->getHttp();
			$response = $http->get($url);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error->error_description);
			}
			$this->id = $result->user_id;
			$this->token = $result->access_token;
			$response = $http->get('https://api.vk.com/method/users.get?uid='.$this->id
				.'&fields=uid,first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo_100,photo_rec,photo_medium_rec,photo_medium,photo_big,has_mobile,contacts,education,relation&access_token='.$this->token);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error->error_msg);
			}
			$this->user_profile = $result->response[0];
			$this->email = $this->user_profile->screen_name.'@vk.com';
			if(isset($this->user_profile->sex))$data['sex'] = $this->user_profile->sex;
			if(isset($this->user_profile->first_name))$data['first_name'] = $this->user_profile->first_name;
			if(isset($this->user_profile->last_name))$data['last_name'] = $this->user_profile->last_name;
			if(isset($this->user_profile->screen_name))$data['screen_name'] = $this->user_profile->screen_name;
			if(isset($this->user_profile->timezone))$data['timezone'] = $this->user_profile->timezone;
			if(isset($this->user_profile->photo))$data['photo'] = $this->user_profile->photo;
			if(isset($this->user_profile->photo_medium))$data['photo_medium'] = $this->user_profile->photo_medium;
			if(isset($this->user_profile->photo_medium_rec))$data['photo_medium_rec'] = $this->user_profile->photo_medium_rec;
			if(isset($this->user_profile->photo_big))$data['photo_big'] = $this->user_profile->photo_big;
			if(isset($this->user_profile->photo_rec))$data['photo_rec'] = $this->user_profile->photo_rec;
			if(isset($this->user_profile->nickname))$data['nickname'] = $this->user_profile->nickname;
			if(isset($this->user_profile->mobile_phone))$data['mobile_phone'] = $this->user_profile->mobile_phone;
			if(isset($this->user_profile->home_phone))$data['home_phone'] = $this->user_profile->home_phone;
			if(isset($this->user_profile->relation))$data['relation'] = $this->user_profile->relation;
			if(isset($this->user_profile->bdate))$data['bdate'] = $this->user_profile->bdate;
			if(isset($this->user_profile->university_name))$data['university_name'] = $this->user_profile->university_name;
			if(isset($this->user_profile->faculty_name))$data['faculty_name'] = $this->user_profile->faculty_name;
			if(isset($this->user_profile->graduation))$data['graduation'] = $this->user_profile->graduation;
			$this->mergeUserInfo($data);
		} else {
			$loginUrl = 'http://oauth.vk.com/authorize?client_id='.$this->params->get('appid').
				'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name).'&response_type=code' ;
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}


	protected function getEmail(){return $this->email;}
	protected function getUsername(){return $this->username;}
	protected function getName(){return $this->realname;}
	protected function getSocId(){return $this->id;}
}

