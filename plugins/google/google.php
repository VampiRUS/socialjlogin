<?php
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT . '/components/com_socialjlogin/plugin.php');

class plgSocialjloginGoogle extends SocialjloginPlugin
{

	protected $name = 'google';
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
			$url = 'https://accounts.google.com/o/oauth2/token?client_id='.$this->params->get('clientid');
				//.'&client_secret='.$this->params->get('secret')
				//.'&code='.$code
				//.'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name));
				//.'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name);
			$data = array(
				'client_id'=>$this->params->get('clientid'),
				'client_secret'=>$this->params->get('secret'),
				'code'=>$code,
				//'redirect_uri'=>'http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name),
				'redirect_uri'=>JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name,
				'grant_type'=>'authorization_code'
				);
			$http = $this->getHttp();
			$response = $http->post($url,$data);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->token = $result->access_token;
			$response = $http->get('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$this->token);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->user_profile = $result;
			$this->realname = $this->user_profile->name;
			$this->username = $this->name.$this->user_profile->id;
			$this->email = $this->user_profile->email;
			$this->id = $this->user_profile->id;
			$this->user_id = $this->getUserId();
			if ($this->user_id){
				$this->login();
			} else {
				$this->registration();
			}
		} else {
			$loginUrl = 'https://accounts.google.com/o/oauth2/auth?client_id='.$this->params->get('clientid').
				'&scope='.urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email')
				//.'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name))
				.'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name)
				.'&response_type=code' ;
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

			if(isset($this->user_profile->given_name))$data['first_name'] = $this->user_profile->given_name;
			if(isset($this->user_profile->family_name))$data['last_name'] = $this->user_profile->family_name;
			if(isset($this->user_profile->timezone))$data['timezone'] = $this->user_profile->timezone;
			if(isset($this->user_profile->picture))$data['photo'] = $this->user_profile->picture;
			if(isset($this->user_profile->birthday))$data['bdate'] = $this->user_profile->birthday;
			if(isset($this->user_profile->link))$data['link'] = $this->user_profile->link;
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
			$url = 'https://accounts.google.com/o/oauth2/token?client_id='.$this->params->get('clientid');
				//.'&client_secret='.$this->params->get('secret')
				//.'&code='.$code
				//.'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name));
				//.'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name);
			$data = array(
				'client_id'=>$this->params->get('clientid'),
				'client_secret'=>$this->params->get('secret'),
				'code'=>$code,
				//'redirect_uri'=>'http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name),
				'redirect_uri'=>JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name,
				'grant_type'=>'authorization_code'
				);
			$http = $this->getHttp();
			$response = $http->post($url,$data);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->token = $result->access_token;
			$response = $http->get('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$this->token);
			$result = json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error);
			}
			$this->user_profile = $result;
			$this->id = $this->user_profile->id;
			switch ($this->user_profile->gender){
				case 'female':$gender = 1;break;
				case 'male':$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;

			if(isset($this->user_profile->given_name))$data['first_name'] = $this->user_profile->given_name;
			if(isset($this->user_profile->family_name))$data['last_name'] = $this->user_profile->family_name;
			if(isset($this->user_profile->timezone))$data['timezone'] = $this->user_profile->timezone;
			if(isset($this->user_profile->picture))$data['photo'] = $this->user_profile->picture;
			if(isset($this->user_profile->birthday))$data['bdate'] = $this->user_profile->birthday;
			if(isset($this->user_profile->link))$data['link'] = $this->user_profile->link;
			$this->mergeUserInfo($data);
		} else {
			$loginUrl = 'https://accounts.google.com/o/oauth2/auth?client_id='.$this->params->get('clientid').
				'&scope='.urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email')
				//.'&redirect_uri='.urlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name))
				.'&redirect_uri='.urlencode(JURI::base().'index.php?option=com_socialjlogin&task=login&type='.$this->name)
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

