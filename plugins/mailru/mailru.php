<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT .'/components/com_socialjlogin/plugin.php');

class plgSocialjloginMailru extends SocialjloginPlugin
{

	protected $name = 'mailru';
	private $email = '';
	private $username = '';
	private $realname = '';
	private $id = 0;
	private $user_profile = null;

	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function getIcon(){
		$doc = JFactory::getDocument();
		$doc->addScript('http://cdn.connect.mail.ru/js/loader.js');
		$doc->addScriptDeclaration('
			mailru.loader.require("api", function() {
			mailru.connect.init("'.$this->params->get('appid').'",
                               "'.$this->params->get('private').'");
mailru.connect.getLoginStatus(function(result) {
	});
mailru.events.listen(mailru.connect.events.login, function(session) {
	location.href = "'.JUri::base(true).'/index.php?option=com_socialjlogin&task=login&type='.$this->name.'";
});
	});
	');
		return '<a href="javascript:;" onclick="mailru.connect.login([\'widget\']);" title="'.JText::_('PLG_SOCIALJLOGIN_'.$this->name.'_LOGIN').'"><img alt="'.JText::_('PLG_SOCIALJLOGIN_LOGIN_W_'.$this->name).'"  src="'.JURI::base().'plugins/socialjlogin/'.$this->name.'/'.$this->name.'.png"></a>';
	}
	public function onLogin()
	{
		if (!isset($_COOKIE['mrc'])) return false;
		parse_str(urldecode($_COOKIE['mrc']),$data);
		if ($data['oid']){
			$http = $this->getHttp();
			$query = array(
				'appid'=>$this->params->get('appid'),
				'session_key'=>$data['session_key'],
				'method'=>'users.getInfo',
				'uids'=>$data['oid'],
				'secure'=>1
				);
			$sig = $this->signServerServer($query,$this->params->get('secret'));
			$url = 'http://www.appsmail.ru/platform/api?';
			foreach ($query as $key=>$param){
				$url .= "$key=$param&";
			}
			$url .= "sig=$sig";
			$response = $http->get($url);
			$result= json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error->error_msg);
			}
			$this->clearCookie();
			$this->user_profile = $result[0];
			$this->realname = $this->user_profile->first_name.' '.$this->user_profile->last_name;
			$this->username = $this->user_profile->nick.'_'.$this->name.$this->user_profile->uid;
			$this->email = $this->user_profile->email;
			$this->id = $this->user_profile->uid;
			$user_id = $this->getUserId();
			if ($user_id){
				$this->login();
			} else {
				$this->registration();
			}
		}
		return false;

	}

	public function onAuth($options){
		if(!$this->user_profile) return false;
		$data = array();
		switch ($this->user_profile->sex){
			case 1:$gender = 1;break;
			case 0:$gender = 2;break;
			default:$gender = 0;
		}
		$data['sex'] = $gender;
		if(isset($this->user_profile->first_name))$data['first_name'] = $this->user_profile->first_name;
		if(isset($this->user_profile->last_name))$data['last_name'] = $this->user_profile->last_name;
		if(isset($this->user_profile->pic))$data['photo'] = $this->user_profile->pic;
		if(isset($this->user_profile->nick))$data['nickname'] = $this->user_profile->nick;
		if(isset($this->user_profile->link))$data['link'] = $this->user_profile->link;
		if(isset($this->user_profile->birthday))$data['bdate'] = $this->user_profile->birthday;
		if(isset($this->user_profile->pic_big))$data['photo_big'] = $this->user_profile->pic_big;
		if(isset($this->user_profile->pic_small))$data['photo_rec'] = $this->user_profile->pic_small;
		if (isset($this->user_profile->location)){
			if(isset($this->user_profile->location->city))$data['city'] = $this->user_profile->location->city->name;
			if(isset($this->user_profile->location->country))$data['country'] = $this->user_profile->location->country->name;
		}
		$this->updateUserInfo($data);
		return $this->getResponse();
	}

	public function onMerge(){
		if (!isset($_COOKIE['mrc'])) return false;
		parse_str(urldecode($_COOKIE['mrc']),$data);
		if ($data['oid']){
			$http = $this->getHttp();
			$query = array(
				'appid'=>$this->params->get('appid'),
				'session_key'=>$data['session_key'],
				'method'=>'users.getInfo',
				'uids'=>$data['oid'],
				'secure'=>1
				);
			$sig = $this->signServerServer($query,$this->params->get('secret'));
			$url = 'http://www.appsmail.ru/platform/api?';
			foreach ($query as $key=>$param){
				$url .= "$key=$param&";
			}
			$url .= "sig=$sig";
			$response = $http->get($url);
			$result= json_decode($response->body);
			if (isset($result->error)){
				JError::raiseError('500',$result->error->error_msg);
			}
			$this->clearCookie();
			$this->user_profile = $result[0];
			$data = array();
			switch ($this->user_profile->sex){
				case 1:$gender = 1;break;
				case 0:$gender = 2;break;
				default:$gender = 0;
			}
			$data['sex'] = $gender;
			$this->id = $this->user_profile->uid;
			$this->email = $this->user_profile->email;
			if(isset($this->user_profile->first_name))$data['first_name'] = $this->user_profile->first_name;
			if(isset($this->user_profile->last_name))$data['last_name'] = $this->user_profile->last_name;
			if(isset($this->user_profile->pic))$data['photo'] = $this->user_profile->pic;
			if(isset($this->user_profile->nick))$data['nickname'] = $this->user_profile->nick;
			if(isset($this->user_profile->link))$data['link'] = $this->user_profile->link;
			if(isset($this->user_profile->birthday))$data['bdate'] = $this->user_profile->birthday;
			if(isset($this->user_profile->pic_big))$data['photo_big'] = $this->user_profile->pic_big;
			if(isset($this->user_profile->pic_small))$data['photo_rec'] = $this->user_profile->pic_small;
			if (isset($this->user_profile->location)){
				if(isset($this->user_profile->location->city))$data['city'] = $this->user_profile->location->city->name;
				if(isset($this->user_profile->location->country))$data['country'] = $this->user_profile->location->country->name;
			}
			$this->mergeUserInfo($data);
		} 
		return false;
	}

	public function onLogout($user, $options){
		$this->clearCookie();
		return true;
	}

	protected function getEmail(){return $this->email;}
	protected function getUsername(){return $this->username;}
	protected function getName(){return $this->realname;}
	protected function getSocId(){return $this->id;}

    private function signClientServer($request_params, $uid, $private_key) {
      ksort($request_params);
      $params = '';
      foreach ($request_params as $key => $value) {
        $params .= "$key=$value";
      }
      return md5($uid . $params . $private_key);
    }

	 private function signServerServer($request_params, $secret_key) {
      ksort($request_params);
      $params = '';
      foreach ($request_params as $key => $value) {
        $params .= "$key=$value";
      }
      return md5($params . $secret_key);
    }

	private function clearCookie(){
		$path = JURI::getInstance()->getPath();
		setcookie ('mrc', "", time() - 3600,'/');
		setcookie ('mrc', "", time() - 3600,$path);
		unset($_COOKIE['mrc']); 
	}
}

