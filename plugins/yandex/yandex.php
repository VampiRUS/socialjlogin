<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT .'/components/com_socialjlogin/plugin.php');

class plgSocialjloginYandex extends SocialjloginPlugin
{

	protected $name = 'yandex';
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
			JError::raiseError(500,$error);
		}
		$code = JRequest::getVar('code','');
		if ($code){
			$url = 'https://oauth.yandex.ru/token';
			$data = array(
				'code'=>$code,
				'grant_type'=>'authorization_code',
				'client_id'=>$this->params->get('clientid'),
				'client_secret'=>$this->params->get('secret')
				);
			$http = $this->getHttp();
			$response = $http->post($url,$data);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->token = $result->access_token;
			$response = $http->get('https://login.yandex.ru/info?format=json&oauth_token='.$this->token);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->user_profile = $result;
			$this->realname = $this->user_profile->real_name;
			$this->username = $this->user_profile->display_name.'_'.$this->name.$this->user_profile->id;
			$this->email = $this->user_profile->default_email;
			$this->id = $this->user_profile->id;
			$this->user_id = $this->getUserId();
			if ($this->user_id){
				$this->login();
			} else {
				$this->registration();
			}
		} else {
			$loginUrl = 'https://oauth.yandex.ru/authorize?client_id='.$this->params->get('clientid').
				//'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name))
				'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name)
				.'&response_type=code' ;
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}

	public function onAuth($options){
		$data = array();
		if ($this->user_profile) {
			switch ($this->user_profile->sex){
				case 'female':$gender = 1;break;
				case 'male':$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;

			if(isset($this->user_profile->birthday))$data['bdate'] = $this->user_profile->birthday;
			$this->updateUserInfo($data);
			return $this->getResponse();
		} else {
			return false;
		}
	}

	public function onMerge(){
		$error = JRequest::getVar('error','');
		if ($error){
			JError::raiseError(500,JRequest::getVar('error'));
		}
		$code = JRequest::getVar('code','');
		if ($code){
			$url = 'https://oauth.yandex.ru/token';
			$data = array(
				'code'=>$code,
				'grant_type'=>'authorization_code',
				'client_id'=>$this->params->get('clientid'),
				'client_secret'=>$this->params->get('secret')
				);
			$http = $this->getHttp();
			$response = $http->post($url,$data);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->token = $result->access_token;
			$response = $http->get('https://login.yandex.ru/info?format=json&oauth_token='.$this->token);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->user_profile = $result;
			$this->id = $this->user_profile->id;

			switch ($this->user_profile->sex){
				case 'female':$gender = 1;break;
				case 'male':$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;

			if(isset($this->user_profile->birthday))$data['bdate'] = $this->user_profile->birthday;
			$this->mergeUserInfo($data);
		} else {
			$loginUrl = 'https://oauth.yandex.ru/authorize?client_id='.$this->params->get('clientid').
				//'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name))
				'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name)
				.'&response_type=code' ;
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}


	protected function getEmail(){return $this->email;}
	protected function getUsername(){return $this->username;}
	protected function getName(){return $this->realname;}
	protected function getSocId(){return $this->id;}
}

