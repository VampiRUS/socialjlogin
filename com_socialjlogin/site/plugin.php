<?php
/**
 * @copyright	Copyright (C) 2012 vampirus.ru. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

class SocialjloginPlugin extends JPlugin{

	protected $name = '';

	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function getPluginName()
	{
		return $this->name;
	}

	public function getIcon(){
		return '<a href="'.JRoute::_('index.php?option=com_socialjlogin&task=login&type='.$this->name).'" title="'.JText::_('PLG_SOCIALJLOGIN_'.$this->name.'_LOGIN').'"><img alt="'.JText::_('PLG_SOCIALJLOGIN_'.$this->name.'_LOGIN').'"  src="'.JURI::base().'plugins/socialjlogin/'.$this->name.'/'.$this->name.'.png"></a>';
	}

	/**
	 * Realisation must be in plugin
	 **/
	public function onLogin(){return null;}
	public function onLogout($user, $options){return null;}
	public function onMerge(){return null;}
	public function onAuth($options){return null;}
	protected function getEmail(){return null;}
	protected function getUsername(){return null;}
	protected function getName(){return null;}
	protected function getSocId(){return null;}

	protected function getResponse(){
		$db = JFactory::getDBO();
		//TODO переделать на конструктор запроса
		$db->setQuery('SELECT userid as id,password FROM #__socialjlogin as s left join #__users as u on u.id=s.userid where s.socid='.$db->Quote($this->getSocId()).' and s.type='.$db->Quote($this->name));
		$result = $db->loadObject();
		return $result;
	}

	protected function getUserId(){
		$db = JFactory::getDBO();
		//TODO переделать на конструктор запроса
		$db->setQuery('SELECT userid FROM #__socialjlogin where socid='.$db->Quote($this->getSocId()).' and type='.$db->Quote($this->name));
		$user_id = $db->loadResult();
		return $user_id;
	}

	protected function updateUserInfo($data){
		$db = JFactory::getDBO();
		$user_id = JFactory::getUser()->get('id');
		if ($user_id){
			$data['userid'] = $user_id;
			$data['type'] = $this->name;
			$data['socid'] = $this->getSocId();
		}
		array_walk($data,array($this,'quoteArray'),$db);
		if ($user_id){
			$db->setQuery('INSERT INTO #__socialjlogin SET '.implode(',',$data));
		} else {
			$db->setQuery("UPDATE #__socialjlogin SET ".implode(',',$data).' where socid='.$db->Quote($this->getSocId()).' and type='.$db->Quote($this->name));
		}
		$db->query();
	}

	protected function mergeUserInfo($data){
		$db = JFactory::getDBO();
		$user_id = JFactory::getUser()->get('id');
		if(!isset($data['userid']))$data['userid'] = $user_id;
		$data['type'] = $this->name;
		$data['socid'] = $this->getSocId();
		$data['email_hash'] = md5($this->getEmail());
		array_walk($data,array($this,'quoteArray'),$db);
		$db->setQuery('INSERT INTO #__socialjlogin SET '.implode(',',$data));
		$db->query();
		JFactory::getApplication()->redirect(JURI::base(),JText::_('COM_SOCIALJLOGIN_ACCOUNTS_HAVE_BEEN_MERGED'));
	}

	private function quoteArray(&$item,$key,$db){
		$item = "`$key`=".$db->Quote($item);
	}

	protected function registration($options=array()){
		$usersConfig = &JComponentHelper::getParams( 'com_users' );
		$username = str_replace(array('[','<','>','"','\'','%',';','(',')','&',']'),'',$this->getUsername());
		$name = $this->getName();
		$email = $this->getEmail();
		JUser::getTable('User', 'JTable');
		$user = new JUser();

		$authorize	=& JFactory::getACL();
		$data = array(
			'username'	=> $username,
			'name'		=> $name,
			'email'		=> $email
		);
		$newUsertype = $usersConfig->get( 'new_usertype', 2);
		$data['groups'][] = $newUsertype;

		if (!$user->bind( $data)) {
			JError::raiseError( 500, $user->getError());
		}
		// If there was an error with registration, set the message and display form
		if ( !$user->save() )
		{
			JError::raiseWarning('', JText::_( $user->getError()));
			JFactory::getApplication()->redirect(JUri::base());
		} else {
			$db = JFactory::getDBO();
			$db->setQuery('INSERT INTO #__socialjlogin (userid,socid,email_hash,`type`) VALUES ('.$user->id.','
				.$db->Quote($this->getSocId()).','.$db->Quote(md5($user->email)).','.$db->Quote($this->name).')');
			$db->query();
			$this->login($options);
		}
	}

	protected function login($options=array()){
		$mainframe	=& JFactory::getApplication();

		if ($return = $_SESSION['return']) {
			if (!JURI::isInternal($return)) {
				$return = '';
			}
		}

		$options['remember'] = false;
		$options['return'] = $return;
		$options['type'] = $this->name;

		$credentials = array();
		$credentials['username'] = 'SOCJ_LOGIN';
		$credentials['password'] = 'SOCJ_PASSWORD';

		//preform the login action
		$error = $mainframe->login($credentials, $options);
		if(!JError::isError($error))
		{
			// Redirect if the return url is not registration or login
			if ( ! $return ) {
					$return	= JRoute::_('index.php');
			}
			JPluginHelper::importPlugin('socialjloginintegration');
			$dispatcher	= JDispatcher::getInstance();
			$results = $dispatcher->trigger('onSocialLogin',array($this->getSocId(),$this->name));
			foreach($results as $url){
				if ($url) $return = $url;
			}
			$mainframe->redirect( $return );
		}
		else
		{
			if ( ! $return ) {
				$return	= JRoute::_('index.php?option=com_socialjlogin', false);
			}

			$mainframe->redirect( $return );
		}
	
	}
	
	protected function getHttp(){
		jimport( 'joomla.client.http' );
		$opt = new JRegistry;
		if (function_exists('curl_version') && curl_version()){
			$trans = new JHttpTransportCurl($opt);
		} elseif (function_exists('fopen') && is_callable('fopen') && ini_get('allow_url_fopen')){
			$trans = new JHttpTransportStream($opt);
		} elseif(function_exists('fsockopen') && is_callable('fsockopen')){
			$trans = new JHttpTransportSocket($opt);
		} else {
			JError::raiseError(500, "Can't initialise http transport ");
		}
		$http = new JHttp($opt,$trans);
		return $http;
	}
}
?>
