<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT .'/components/com_socialjlogin/plugin.php');
require_once(JPATH_ROOT .'/plugins/socialjlogin/twitter/twitteroauth/twitteroauth.php');

class plgSocialjloginTwitter extends SocialjloginPlugin
{

	protected $name = 'twitter';
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
		$token = JRequest::getVar('oauth_token','');
		if ($token){
			$oauth_verifier = JRequest::getVar("oauth_verifier");
			$connection = new TwitterOAuth($this->params->get('key'), $this->params->get('secret'), $token, "123");
			$access_token = $connection->getAccessToken($oauth_verifier);
			$connection = new TwitterOAuth($this->params->get('key'), $this->params->get('secret'), $access_token['oauth_token'], $access_token['oauth_token_secret']);
			$result = $connection->get('account/verify_credentials');

			if (isset($result->errors)||is_null($result)){
				JError::raiseError(500,$result->errors[0]->message);
			}
			$this->user_profile = $result;
			$this->realname = $this->user_profile->name;
			$this->username = $this->user_profile->screen_name.'_'.$this->name.$this->user_profile->id;
			$this->email = $this->user_profile->screen_name.'@twitter.com';
			$this->id = $this->user_profile->id;
			$this->user_id = $this->getUserId();
			if ($this->user_id){
				$this->login();
			} else {
				$this->registration();
			}
		} else {
			$callback='http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name);
			$connection = new TwitterOAuth($this->params->get('key'), $this->params->get('secret'));
			$request_token = $connection->getRequestToken($callback);
			if(isset($request_token["oauth_token"])){
				JFactory::getApplication()->redirect("https://api.twitter.com/oauth/authenticate?oauth_token=".$request_token["oauth_token"]);
			}else{
				JError::raiseError(500,$result->body);
			}
		}
		return true;
	}

	public function onAuth($options){
		$data = array();
		if ($this->user_profile) {
			if(isset($this->user_profile->screen_name))$data['screen_name'] = $this->user_profile->screen_name;
			if(isset($this->user_profile->profile_image_url))$data['photo'] = $this->user_profile->profile_image_url;
			if(isset($this->user_profile->utc_offset))$data['timezone'] = $this->user_profile->utc_offset/(60*60);
			$this->updateUserInfo($data);
			return $this->getResponse();
		} else {
			return false;
		}
	}

	public function onMerge(){
		$token = JRequest::getVar('oauth_token','');
		if ($token){
            $oauth_verifier = JRequest::getVar("oauth_verifier");
			$connection = new TwitterOAuth($this->params->get('key'), $this->params->get('secret'), $token, "123");
			$access_token = $connection->getAccessToken($oauth_verifier);
			$connection = new TwitterOAuth($this->params->get('key'), $this->params->get('secret'), $access_token['oauth_token'], $access_token['oauth_token_secret']);
			$result = $connection->get('account/verify_credentials');
			if (isset($result->errors)||is_null($result)){
				JError::raiseError(500,$result->errors[0]->message);
			}
			$this->user_profile = $result;
			$this->realname = $this->user_profile->name;
			$this->username = $this->user_profile->screen_name.'_'.$this->name.$this->user_profile->id;
			$this->email = $this->user_profile->screen_name.'@twitter.com';
			$this->id = $this->user_profile->id;
			$this->user_id = $this->getUserId();
			if(isset($this->user_profile->screen_name))$data['screen_name'] = $this->user_profile->screen_name;
			if(isset($this->user_profile->profile_image_url))$data['photo'] = $this->user_profile->profile_image_url;
			if(isset($this->user_profile->utc_offset))$data['timezone'] = $this->user_profile->utc_offset/(60*60);
			$this->mergeUserInfo($data);
		} else {
            $callback='http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name);
            $connection = new TwitterOAuth($this->params->get('key'), $this->params->get('secret'));
			$request_token = $connection->getRequestToken($callback);
			if(isset($request_token["oauth_token"])){
				JFactory::getApplication()->redirect("https://api.twitter.com/oauth/authenticate?oauth_token=".$request_token["oauth_token"]);
            }else{
				JError::raiseError(500,$result->body);
            }
		}
		return true;
	}


	protected function getEmail(){return $this->email;}
	protected function getUsername(){return $this->username;}
	protected function getName(){return $this->realname;}
	protected function getSocId(){return $this->id;}
}

