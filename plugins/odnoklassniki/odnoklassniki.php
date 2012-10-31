<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT . '/components/com_socialjlogin/plugin.php');

class plgSocialjloginOdnoklassniki extends SocialjloginPlugin
{

	protected $name = 'odnoklassniki';
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
			$url = 'http://api.odnoklassniki.ru/oauth/token.do';
			$data = array(
				'code'=>$code,
				'redirect_uri'=>'http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name),
				'grant_type'=>'authorization_code',
				'client_id'=>$this->params->get('appid'),
				'client_secret'=>$this->params->get('secret')
				);
			$http = $this->getHttp();
			$response = $http->post($url,$data);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error_description);
			}
			$this->token = $result->access_token;
			$params = array(
				'application_key='.$this->params->get('public'),
				'method=users.getCurrentUser');
			$sig = md5(implode('',$params).md5($this->token.$this->params->get('secret')));

			$response = $http->get('http://api.odnoklassniki.ru/fb.do?'.implode('&',$params).'&access_token='.$this->token.'&sig='.$sig);
			$result = json_decode($response->body);
			if (isset($result->error_code)||is_null($result)){
				JError::raiseError('500',$result->error_msg);
			}

			$this->user_profile = $result;
			$this->id = $result->response->uid;
			$this->realname = $this->user_profile->name;
			$this->username = $this->user_profile->uid.'_'.$this->name.$this->user_profile->uid;
			$this->email = $this->user_profile->uid.'@odnoklassniki.ru';
			$this->id = $this->user_profile->uid;
			$this->user_id = $this->getUserId();
			if ($this->user_id){
				$this->login();
			} else {
				$this->registration();
			}
		} else {
			//TODO http://api.mail.ru/docs/guides/oauth/sites/ - make uniq urls
			$loginUrl = 'http://www.odnoklassniki.ru/oauth/authorize?client_id='.$this->params->get('appid').
				'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name)).'&response_type=code' ;
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}

	public function onAuth($options){
		$data = array();
		if ($this->user_profile) {
			switch ($this->user_profile->gender){
				case 'female':$gender = 1;break;
				case 'male':$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;
			if(isset($this->user_profile->first_name))$data['first_name'] = $this->user_profile->first_name;
			if(isset($this->user_profile->last_name))$data['last_name'] = $this->user_profile->last_name;
			if(isset($this->user_profile->pic_2)){
				$http = $this->getHttp();
				$response = $http->get($this->user_profile->pic_2);
				$img = $response->body;
				$imgpath = dirname(__FILE__).'/images/'.$this->user_profile->uid.'_pic2.jpg';
				$imgurl = JURI::base().'/plugins/socialjlogin/odnoklassniki/images/'.$this->user_profile->uid.'_pic2.jpg';
				if ($img && file_put_contents($imgpath,$img)){
					$data['photo'] = $imgurl;
				} else {
					$data['photo'] = $this->user_profile->pic_2;
				}
			}
			if(isset($this->user_profile->pic_1)){
				$http = $this->getHttp();
				$response = $http->get($this->user_profile->pic_1);
				$img = $response->body;
				$imgpath = dirname(__FILE__).'/images/'.$this->user_profile->uid.'_pic1.jpg';
				$imgurl = JURI::base().'/plugins/socialjlogin/odnoklassniki/images/'.$this->user_profile->uid.'_pic1.jpg';
				if ($img && file_put_contents($imgpath,$img)){
					$data['photo_rec'] = $imgurl;
				} else {
					$data['photo_rec'] = $this->user_profile->pic_1;
				}
			}
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
			JError::raiseError(500,$error);
		}
		$code = JRequest::getVar('code','');
		if ($code){
			$url = 'http://api.odnoklassniki.ru/oauth/token.do';
			$data = array(
				'code'=>$code,
				'redirect_uri'=>urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name)),
				'grant_type'=>'authorization_code',
				'client_id'=>$this->params->get('appid'),
				'client_secret'=>$this->params->get('secret')
				);
			$http = $this->getHttp();
			$response = $http->post($url,$data);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error_description);
			}
			$this->id = $result->user_id;
			$this->token = $result->access_token;
			$params = array(
				'application_key='.$this->params->get('public'),
				'method=users.getCurrentUser');
			$sig = md5(implode('',$params).md5($this->token.$this->params->get('secret')));

			$response = $http->get('http://api.odnoklassniki.ru/fb.do?'.implode('&',$params).'&access_token='.$this->token.'&sig='.$sig);
			$result = json_decode($response->body);
			if (isset($result->error_code)||is_null($result)){
				JError::raiseError('500',$result->error_msg);
			}
			$this->user_profile = $result;
			$this->email = $this->user_profile->uid.'@odnoklassniki.ru';
			switch ($this->user_profile->gender){
				case 'female':$gender = 1;break;
				case 'male':$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;
			if(isset($this->user_profile->first_name))$data['first_name'] = $this->user_profile->first_name;
			if(isset($this->user_profile->last_name))$data['last_name'] = $this->user_profile->last_name;
			if(isset($this->user_profile->pic_2)){
				$http = $this->getHttp();
				$response = $http->get($this->user_profile->pic_2);
				$img = $response->body;
				$imgpath = dirname(__FILE__).'/images/'.$this->user_profile->uid.'_pic2.jpg';
				$imgurl = JURI::base().'/plugins/socialjlogin/odnoklassniki/images/'.$this->user_profile->uid.'_pic2.jpg';
				if ($img && file_put_contents($imgpath,$img)){
					$data['photo'] = $imgurl;
				} else {
					$data['photo'] = $this->user_profile->pic_2;
				}
			}
			if(isset($this->user_profile->pic_1)){
				$http = $this->getHttp();
				$response = $http->get($this->user_profile->pic_1);
				$img = $response->body;
				$imgpath = dirname(__FILE__).'/images/'.$this->user_profile->uid.'_pic1.jpg';
				$imgurl = JURI::base().'/plugins/socialjlogin/odnoklassniki/images/'.$this->user_profile->uid.'_pic1.jpg';
				if ($img && file_put_contents($imgpath,$img)){
					$data['photo_rec'] = $imgurl;
				} else {
					$data['photo_rec'] = $this->user_profile->pic_1;
				}
			}
			if(isset($this->user_profile->birthday))$data['bdate'] = $this->user_profile->birthday;
			$this->mergeUserInfo($data);
		} else {
			$loginUrl = 'http://www.odnoklassniki.ru/oauth/authorize?client_id='.$this->params->get('appid').
				'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name)).'&response_type=code' ;
			JFactory::getApplication()->redirect($loginUrl);
		}
		return true;
	}


	protected function getEmail(){return $this->email;}
	protected function getUsername(){return $this->username;}
	protected function getName(){return $this->realname;}
	protected function getSocId(){return $this->id;}
}

