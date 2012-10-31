<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
if (!class_exists('SocialjloginPlugin'))
    require(JPATH_ROOT .'/components/com_socialjlogin/plugin.php');

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
            $nonce=md5(time());
            
            $time = time();
            
            $request_string="POST&".
                    rawurlencode("https://api.twitter.com/oauth/access_token")."&".
                    rawurlencode(
                            "oauth_consumer_key=".$this->params->get('key')."&".
                            "oauth_nonce=$nonce&".
                            "oauth_signature_method=HMAC-SHA1&".
                            "oauth_timestamp=$time&".
                            "oauth_token=$token&".
                            "oauth_verifier=$oauth_verifier&".
                            "oauth_version=1.0");
            
            $sign = rawurlencode( base64_encode( hash_hmac(
                    "sha1", $request_string, $this->params->get('secret')."&", true) ) );
            
            $authorisationHeader = "OAuth ".
                    "oauth_consumer_key=\"".$this->params->get('key')."\", ".
                    "oauth_nonce=\"$nonce\", ".
                    "oauth_signature_method=\"HMAC-SHA1\", ".
                    "oauth_timestamp=\"$time\", ".
                    "oauth_signature=\"$sign\", ".
                    "oauth_token=\"$token\", ".
                    "oauth_version=\"1.0\"";
			$headers = array('Authorization'=>$authorisationHeader);
			$http = $this->getHttp();
			$response = $http->post("https://api.twitter.com/oauth/access_token",array(),$headers);
            $responseTokens = array();
            parse_str($response->body, $responseTokens);

			if(!isset($responseTokens["oauth_token"])){
				JError::raiseError(500,$response->body);
			}
            $twitter_url = "https://api.twitter.com/1/users/lookup.json";
            
            $nonce=md5(time());
            
            $time = time();
            
            $request_string="GET&".
                    rawurlencode($twitter_url)."&".
                    rawurlencode(
                            "oauth_consumer_key=".$this->params->get('key')."&".
                            "oauth_nonce=$nonce&".
                            "oauth_signature_method=HMAC-SHA1&".
                            "oauth_timestamp=$time&".
                            "oauth_token={$responseTokens["oauth_token"]}&".
                            "oauth_verifier=$oauth_verifier&".
                            "oauth_version=1.0&".
                            "user_id=".$responseTokens["user_id"]);
            
            $sign = rawurlencode(
               base64_encode(
                 hash_hmac("sha1", $request_string,
                   $this->params->get('secret')."&".$responseTokens["oauth_token_secret"], true) ) );
            
            
            $authorisationHeader = "OAuth ".
                    "oauth_consumer_key=\"".$this->params->get('key')."\", ".
                    "oauth_nonce=\"$nonce\", ".
                    "oauth_signature_method=\"HMAC-SHA1\", ".
                    "oauth_timestamp=\"$time\", ".
                    "oauth_signature=\"$sign\", ".
                    "oauth_token=\"{$responseTokens["oauth_token"]}\", ".
                    "oauth_version=\"1.0\"";
			$headers = array('Authorization'=>$authorisationHeader);
			$response = $http->get($twitter_url."?user_id=".$responseTokens["user_id"],$headers);

			$result = json_decode($response->body);
			if (isset($result->errors)||is_null($result)){
				JError::raiseError(500,$result->errors[0]->message);
			}
			$this->user_profile = $result[0];
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
            $callback=rawurlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name));
            $nonce=md5(time());
            
            $time = time();
            $request_string="POST&".
                    rawurlencode('https://api.twitter.com/oauth/request_token')."&".
					rawurlencode("oauth_callback=$callback&oauth_consumer_key=".$this->params->get('key')
					."&oauth_nonce=$nonce&oauth_signature_method=HMAC-SHA1&oauth_timestamp=$time&oauth_version=1.0");
            
            $sign = rawurlencode( base64_encode( hash_hmac("sha1", $request_string, $this->params->get('secret')."&", true) ) );
            
            $authorisationHeader = "OAuth oauth_callback=\"$callback\", ".
                    "oauth_consumer_key=\"".$this->params->get('key')."\", oauth_nonce=\"$nonce\", ".
                    "oauth_signature_method=\"HMAC-SHA1\", oauth_timestamp=\"$time\", ".
                    "oauth_signature=\"$sign\", oauth_version=\"1.0\"";
			$http = $this->getHttp();
			$headers = array('Authorization'=>$authorisationHeader);
			$result = $http->post('https://api.twitter.com/oauth/request_token',array(),$headers);
            $responseTokens = array();
			parse_str($result->body, $responseTokens);
            if(isset($responseTokens["oauth_token"])){
                header("Location: https://api.twitter.com/oauth/authenticate?oauth_token=".$responseTokens["oauth_token"]);
				JFactory::getApplication()->redirect("https://api.twitter.com/oauth/authenticate?oauth_token=".$responseTokens["oauth_token"]);
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
            $nonce=md5(time());
            
            $time = time();
            
            $request_string="POST&".
                    rawurlencode("https://api.twitter.com/oauth/access_token")."&".
                    rawurlencode(
                            "oauth_consumer_key=".$this->params->get('key')."&".
                            "oauth_nonce=$nonce&".
                            "oauth_signature_method=HMAC-SHA1&".
                            "oauth_timestamp=$time&".
                            "oauth_token=$token&".
                            "oauth_verifier=$oauth_verifier&".
                            "oauth_version=1.0");
            
            $sign = rawurlencode( base64_encode( hash_hmac(
                    "sha1", $request_string, $this->params->get('secret')."&", true) ) );
            
            $authorisationHeader = "OAuth ".
                    "oauth_consumer_key=\"".$this->params->get('key')."\", ".
                    "oauth_nonce=\"$nonce\", ".
                    "oauth_signature_method=\"HMAC-SHA1\", ".
                    "oauth_timestamp=\"$time\", ".
                    "oauth_signature=\"$sign\", ".
                    "oauth_token=\"$token\", ".
                    "oauth_version=\"1.0\"";
			$headers = array('Authorization'=>$authorisationHeader);
			$http = $this->getHttp();
			$response = $http->post("https://api.twitter.com/oauth/access_token",array(),$headers);
            $responseTokens = array();
            parse_str($response->body, $responseTokens);

			if(!isset($responseTokens["oauth_token"])){
				JError::raiseError(500,$response->body);
			}
            $twitter_url = "https://api.twitter.com/1/users/lookup.json";
            
            $nonce=md5(time());
            
            $time = time();
            
            $request_string="GET&".
                    rawurlencode($twitter_url)."&".
                    rawurlencode(
                            "oauth_consumer_key=".$this->params->get('key')."&".
                            "oauth_nonce=$nonce&".
                            "oauth_signature_method=HMAC-SHA1&".
                            "oauth_timestamp=$time&".
                            "oauth_token={$responseTokens["oauth_token"]}&".
                            "oauth_verifier=$oauth_verifier&".
                            "oauth_version=1.0&".
                            "user_id=".$responseTokens["user_id"]);
            
            $sign = rawurlencode(
               base64_encode(
                 hash_hmac("sha1", $request_string,
                   $this->params->get('secret')."&".$responseTokens["oauth_token_secret"], true) ) );
            
            
            $authorisationHeader = "OAuth ".
                    "oauth_consumer_key=\"".$this->params->get('key')."\", ".
                    "oauth_nonce=\"$nonce\", ".
                    "oauth_signature_method=\"HMAC-SHA1\", ".
                    "oauth_timestamp=\"$time\", ".
                    "oauth_signature=\"$sign\", ".
                    "oauth_token=\"{$responseTokens["oauth_token"]}\", ".
                    "oauth_version=\"1.0\"";
			$headers = array('Authorization'=>$authorisationHeader);
			$response = $http->get($twitter_url."?user_id=".$responseTokens["user_id"],$headers);

			$result = json_decode($response->body);
			if (isset($result->errors)||is_null($result)){
				JError::raiseError(500,$result->errors[0]->message);
			}
			$this->user_profile = $result[0];
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
            $callback=rawurlencode('http://'.JURI::getInstance()->getHost().JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name));
            $nonce=md5(time());
            
            $time = time();
            $request_string="POST&".
                    rawurlencode('https://api.twitter.com/oauth/request_token')."&".
					rawurlencode("oauth_callback=$callback&oauth_consumer_key=".$this->params->get('key')
					."&oauth_nonce=$nonce&oauth_signature_method=HMAC-SHA1&oauth_timestamp=$time&oauth_version=1.0");
            
            $sign = rawurlencode( base64_encode( hash_hmac("sha1", $request_string, $this->params->get('secret')."&", true) ) );
            
            $authorisationHeader = "OAuth oauth_callback=\"$callback\", ".
                    "oauth_consumer_key=\"".$this->params->get('key')."\", oauth_nonce=\"$nonce\", ".
                    "oauth_signature_method=\"HMAC-SHA1\", oauth_timestamp=\"$time\", ".
                    "oauth_signature=\"$sign\", oauth_version=\"1.0\"";
			$http = $this->getHttp();
			$headers = array('Authorization'=>$authorisationHeader);
			$result = $http->post('https://api.twitter.com/oauth/request_token',array(),$headers);
            $responseTokens = array();
			parse_str($result->body, $responseTokens);
            if(isset($responseTokens["oauth_token"])){
                header("Location: https://api.twitter.com/oauth/authenticate?oauth_token=".$responseTokens["oauth_token"]);
				JFactory::getApplication()->redirect("https://api.twitter.com/oauth/authenticate?oauth_token=".$responseTokens["oauth_token"]);
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

