<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/* WP port by singleview.co.kr */

namespace X2board\Includes\Classes;

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

if (!class_exists('\\X2board\\Includes\\Classes\\Context')) {
	/**
	 * Manages Context such as request arguments/environment variables
	 * It has dual method structure, easy-to use methods which can be called as self::methodname(),and methods called with static object.
	 *
	 * @author https://singleview.co.kr
	 */
	class Context {
		/**
		 * Allow rewrite
		 * @var bool TRUE: using rewrite mod, FALSE: otherwise
		 */
		// public $allow_rewrite = FALSE;

		/**
		 * Request method
		 * @var string GET|POST|XMLRPC|JSON
		 */
		// public $request_method = 'GET';

		/**
		 * js callback function name.
		 * @var string
		 */
		// public $js_callback_func = '';

		/**
		 * Response method.If it's not set, it follows request method.
		 * @var string HTML|XMLRPC|JSON|JS_CALLBACK
		 */
		// public $response_method = '';

		/**
		 * DB info
		 * @var object
		 */
		// public $db_info = NULL;

		/**
		 * FTP info
		 * @var object
		 */
		// public $ftp_info = NULL;

		/**
		 * ssl action cache file
		 * @var array
		 */
		// public $sslActionCacheFile = './files/cache/sslCacheFile.php';

		/**
		 * List of actions to be sent via ssl (it is used by javascript xml handler for ajax)
		 * @var array
		 */
		// public $ssl_actions = array();

		/**
		 * obejct oFrontEndFileHandler()
		 * @var object
		 */
		// public $oFrontEndFileHandler;

		/**
		 * script codes in <head>..</head>
		 * @var string
		 */
		// public $html_header = NULL;

		/**
		 * class names of <body>
		 * @var array
		 */
		// public $body_class = array();

		/**
		 * codes after <body>
		 * @var string
		 */
		// public $body_header = NULL;

		/**
		 * class names before </body>
		 * @var string
		 */
		// public $html_footer = NULL;

		/**
		 * path of Xpress Engine
		 * @var string
		 */
		// public $path = '';
		// language information - it is changed by HTTP_USER_AGENT or user's cookie
		/**
		 * language type
		 * @var string
		 */
		// public $lang_type = '';

		/**
		 * contains language-specific data
		 * @var object
		 */
		// public $lang = NULL;

		/**
		 * list of loaded languages (to avoid re-loading them)
		 * @var array
		 */
		// public $loaded_lang_files = array();

		/**
		 * site's browser title
		 * @var string
		 */
		// public $site_title = '';

		/**
		 * Conatins request parameters and environment variables
		 * @var object
		 */
		public $context = NULL;

		/**
		 * build an UTF8 decoded URL for custom router only
		 * @var object
		 */
		private $_s_page_permlink = null;

		/**
		 * variables from GET or form submit
		 * @var mixed
		 */
		public $get_vars = NULL;

		/**
		 * Checks uploaded
		 * @var bool TRUE if attached file exists
		 */
		// public $is_uploaded = FALSE;
		
		/**
		 * Pattern for request vars check
		 * @var array
		 */
		private $_a_patterns = array(
				'/<\?/iUsm',
				'/<\%/iUsm',
				'/<script\s*?language\s*?=\s*?("|\')?\s*?php\s*("|\')?/iUsm'
				);
		
		/**
		 * Pattern for request vars check
		 * @var array
		 */
		private $_a_ignore_request = array(
				'woocommerce-login-nonce', '_wpnonce',
				'woocommerce-reset-password-nonce',
				'woocommerce-edit-address-nonce',
				'save-account-details-nonce'
				);

		/**
		 * Check init
		 * @var bool FALSE if init fail
		 */
		public $isSuccessInit = TRUE;

		/**
		 * returns static context object (Singleton). It's to use Context without declaration of an object
		 *
		 * @return object Instance
		 */
		public static function &getInstance() {
			static $theInstance = null;
			if(!$theInstance) {
				$theInstance = new Context();
			}
			return $theInstance;
		}

		/**
		 * Cunstructor
		 *
		 * @return void
		 */
		public function __construct() {
			// $this->oFrontEndFileHandler = new FrontEndFileHandler();
			$this->get_vars = new \stdClass();

			// // include ssl action cache file
			// $this->sslActionCacheFile = FileHandler::getRealPath($this->sslActionCacheFile);
			// if(is_readable($this->sslActionCacheFile))
			// {
			// 	require($this->sslActionCacheFile);
			// 	if(isset($sslActions))
			// 	{
			// 		$this->ssl_actions = $sslActions;
			// 	}
			// }
			$this->context = new \stdClass();

			// WP stores small-letter URL like wp-%ed%8e%98%ec%9d%b4%ec%a7%80-%ec%a0%9c%eb%aa%a9-2
			// router needs capitalized URL like wp-%ED%8E%98%EC%9D%B4%EC%A7%80-%EC%A0%9C%EB%AA%A9-2
			if(get_post()){
				$this->_s_page_permlink = site_url().'/'.urlencode(urldecode(get_post()->post_name));
			}
		}

		/**
		 * Initialization, it sets DB information, request arguments and so on.
		 *
		 * @see This function should be called only once
		 * @return void
		 */
		function init($s_cmd_type)	{
			// fix missing HTTP_RAW_POST_DATA in PHP 5.6 and above
			// if(!isset($GLOBALS['HTTP_RAW_POST_DATA']) && version_compare(PHP_VERSION, '5.6.0', '>=') === TRUE)
			// {
			// 	$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents("php://input");

			// 	// If content is not XML JSON, unset
			// 	if(!preg_match('/^[\<\{\[]/', $GLOBALS['HTTP_RAW_POST_DATA']) && strpos($_SERVER['CONTENT_TYPE'], 'json') === FALSE && strpos($_SERVER['HTTP_CONTENT_TYPE'], 'json') === FALSE)
			// 	{
			// 		unset($GLOBALS['HTTP_RAW_POST_DATA']);
			// 	}
			// }

			// // set context variables in $GLOBALS (to use in display handler)
			// if(is_null($GLOBALS['__Context__']))
			// 	$GLOBALS['__Context__'] = new stdClass();
			// $this->context = &$GLOBALS['__Context__'];
			// $this->context->lang = &$GLOBALS['lang'];
			// $this->context->_COOKIE = $_COOKIE;

			// 20140429 editor/image_link
			// $this->_checkGlobalVars();

			$this->setRequestMethod('');

			// $this->_setXmlRpcArgument();
			// $this->_setJSONRequestArgument();

			$this->_setRequestArgument();

			$o_logged_info = wp_get_current_user();
			$o_logged_info->is_admin = current_user_can('manage_options') ? 'Y' : 'N';
			$this->set( 'is_logged', is_user_logged_in() );
			$this->set( 'logged_info', $o_logged_info );

			$o_grant = new \stdClass();
			$o_grant->is_site_admin = true;
			$o_grant->manager = true; 
			$o_grant->access = true;
			$o_grant->is_admin = true;
			$o_grant->list = true;
			$o_grant->view = true; 
			$o_grant->write_post = true;
			$o_grant->write_comment = true;
			// $o_grant->consultation_read = true;

			// $o_module_info = new \stdClass();
			// $o_module_info->module = 'board';
			// $o_module_info->skin = 'sketchb2ook5';
			// $o_module_info->admin_mail = '';
			// $o_module_info->use_category = 'Y';
			// $o_module_info->use_anonymous = 'Y';
			// $o_module_info->use_status = '';
			// $o_module_info->mobile_use_editor = '';
			// $o_module_info->use_comment_validation = '';
			// $o_module_info->list = true;
			// $o_module_info->skin_vars = new \stdClass();
			
			if( $s_cmd_type == 'proc' ) {  // load controller priority
				$s_cmd = isset( $_REQUEST['cmd'])?$_REQUEST['cmd'] : '';
				$s_cmd_prefix = substr( $s_cmd, 0, 4 );
// var_dump('detected proc cmd:'. $s_cmd);
				if( $s_cmd_prefix === 'proc' ) {  
					$o_controller = \X2board\Includes\getController('board');
					$n_board_id = sanitize_text_field(intval($_REQUEST['board_id']));
					$o_controller->setModuleInfo($n_board_id, $o_grant);
					$next_page_url = $o_controller->get('s_wp_redirect_url');
					if ( wp_redirect( $next_page_url ) ) {
						unset($o_controller);
						exit;  // required to execute wp_redirect()
					}
					wp_redirect(home_url());
					unset($o_controller);
					exit;
				}
				wp_redirect(home_url());
				exit;  // required to execute wp_redirect()
			}
			///////// end of proc mode ////////////////////// 
			///////// begin of view mode ////////////////////// 
			// set frequently used skin vars
			$this->set( 'board_id', get_the_ID() ); // x2board id is WP page ID  get_the_ID() work only view mode
			// global $pagename;
			// self::set( 'wp_page_name', $pagename ); // x2board URL is WP page name
			
			$this->_convert_pretty_command_uri(); // pretty url is for view only
			$s_cmd = self::get('cmd');
			$s_cmd_prefix = substr( $s_cmd, 0, 4 );
var_dump('detected view cmd:'. $s_cmd);
			if( $s_cmd_prefix === '' || $s_cmd_prefix === 'view' ) {  // load view
				$o_view = \X2board\Includes\getModule('board');
// var_dump(get_the_ID());
				$o_view->setModuleInfo(get_the_ID(), $o_grant);
				unset($o_view);
			}

			// $this->_setUploadedArgument();

			// $this->loadDBInfo();
			// if($this->db_info->use_sitelock == 'Y')
			// {
			// 	if(is_array($this->db_info->sitelock_whitelist)) $whitelist = $this->db_info->sitelock_whitelist;

			// 	if(!IpFilter::filter($whitelist))
			// 	{
			// 		$title = ($this->db_info->sitelock_title) ? $this->db_info->sitelock_title : 'Maintenance in progress...';
			// 		$message = $this->db_info->sitelock_message;

			// 		define('_XE_SITELOCK_', TRUE);
			// 		define('_XE_SITELOCK_TITLE_', $title);
			// 		define('_XE_SITELOCK_MESSAGE_', $message);

			// 		header("HTTP/1.1 403 Forbidden");
			// 		if(FileHandler::exists(_XE_PATH_ . 'common/tpl/sitelock.user.html'))
			// 		{
			// 			include _XE_PATH_ . 'common/tpl/sitelock.user.html';
			// 		}
			// 		else
			// 		{
			// 			include _XE_PATH_ . 'common/tpl/sitelock.html';
			// 		}
			// 		exit;
			// 	}
			// }

			// // If XE is installed, get virtual site information
			// if(self::isInstalled())
			// {
			// 	$oModuleModel = getModel('module');
			// 	$site_module_info = $oModuleModel->getDefaultMid();

			// 	if(!isset($site_module_info))
			// 	{
			// 		$site_module_info = new stdClass();
			// 	}

			// 	// if site_srl of site_module_info is 0 (default site), compare the domain to default_url of db_config
			// 	if($site_module_info->site_srl == 0 && $site_module_info->domain != $this->db_info->default_url)
			// 	{
			// 		$site_module_info->domain = $this->db_info->default_url;
			// 	}

			// 	$this->set('site_module_info', $site_module_info);
			// 	if($site_module_info->site_srl && isSiteID($site_module_info->domain))
			// 	{
			// 		$this->set('vid', $site_module_info->domain, TRUE);
			// 	}

			// 	if(!isset($this->db_info))
			// 	{
			// 		$this->db_info = new stdClass();
			// 	}

			// 	$this->db_info->lang_type = $site_module_info->default_language;
			// 	if(!$this->db_info->lang_type)
			// 	{
			// 		$this->db_info->lang_type = 'en';
			// 	}
			// 	if(!$this->db_info->use_db_session)
			// 	{
			// 		$this->db_info->use_db_session = 'N';
			// 	}
			// }

			// // Load Language File
			// $lang_supported = $this->loadLangSelected();

			// // Retrieve language type set in user's cookie
			// if($this->lang_type = $this->get('l'))
			// {
			// 	if($_COOKIE['lang_type'] != $this->lang_type)
			// 	{
			// 		setcookie('lang_type', $this->lang_type, $_SERVER['REQUEST_TIME'] + 3600 * 24 * 1000);
			// 	}
			// }
			// elseif($_COOKIE['lang_type'])
			// {
			// 	$this->lang_type = $_COOKIE['lang_type'];
			// }

			// // If it's not exists, follow default language type set in db_info
			// if(!$this->lang_type)
			// {
			// 	$this->lang_type = $this->db_info->lang_type;
			// }

			// // if still lang_type has not been set or has not-supported type , set as English.
			// if(!$this->lang_type)
			// {
			// 	$this->lang_type = 'en';
			// }
			// if(is_array($lang_supported) && !isset($lang_supported[$this->lang_type]))
			// {
			// 	$this->lang_type = 'en';
			// }

			// $this->set('lang_supported', $lang_supported);
			// $this->setLangType($this->lang_type);

			// // load module module's language file according to language setting
			// $this->loadLang(_XE_PATH_ . 'modules/module/lang');

			// // set session handler
			// if(self::isInstalled() && $this->db_info->use_db_session == 'Y')
			// {
			// 	$oSessionModel = getModel('session');
			// 	$oSessionController = getController('session');
			// 	ini_set('session.serialize_handler', 'php');
			// 	session_set_save_handler(
			// 			array(&$oSessionController, 'open'), array(&$oSessionController, 'close'), array(&$oSessionModel, 'read'), array(&$oSessionController, 'write'), array(&$oSessionController, 'destroy'), array(&$oSessionController, 'gc')
			// 	);
			// }

			// if($sess = $_POST[session_name()]) session_id($sess);
			// session_start();

			// // set authentication information in Context and session
			// if(self::isInstalled())
			// {
			// 	$oModuleModel = getModel('module');
			// 	$oModuleModel->loadModuleExtends();

			// 	$oMemberModel = getModel('member');
			// 	$oMemberController = getController('member');

			// 	if($oMemberController && $oMemberModel)
			// 	{
					// if signed in, validate it.
					// if($oMemberModel->isLogged())
					// {
					// 	$oMemberController->setSessionInfo();
					// }
					// check auto sign-in
					// elseif($_COOKIE['xeak'])
					// {
					// 	$oMemberController->doAutologin();
					// }
// $o_logged_info = wp_get_current_user();
// $o_logged_info->is_admin = current_user_can('manage_options') ? 'Y' : 'N';
// $this->set( 'is_logged', is_user_logged_in() );  // $oMemberModel->isLogged());
// $this->set( 'logged_info', $o_logged_info );  // $oMemberModel->getLoggedInfo());
			// 	}
			// }

			// // load common language file
			// $this->lang = &$GLOBALS['lang'];
			// $this->loadLang(_XE_PATH_ . 'common/lang/');

			// // check if using rewrite module
			// $this->allow_rewrite = ($this->db_info->use_rewrite == 'Y' ? TRUE : FALSE);

			// // set locations for javascript use
			// $url = array();
			// $current_url = self::getRequestUri();
			// if($_SERVER['REQUEST_METHOD'] == 'GET')
			// {
			// 	if($this->get_vars)
			// 	{
			// 		$url = array();
			// 		foreach($this->get_vars as $key => $val)
			// 		{
			// 			if(is_array($val) && count($val) > 0)
			// 			{
			// 				foreach($val as $k => $v)
			// 				{
			// 					$url[] = $key . '[' . $k . ']=' . urlencode($v);
			// 				}
			// 			}
			// 			elseif($val)
			// 			{
			// 				$url[] = $key . '=' . urlencode($val);
			// 			}
			// 		}

			// 		$current_url = self::getRequestUri();
			// 		if($url) $current_url .= '?' . join('&', $url);
			// 	}
			// 	else
			// 	{
			// 		$current_url = $this->getUrl();
			// 	}
			// }
			// else
			// {
			// 	$current_url = self::getRequestUri();
			// }

			// $this->set('current_url', $current_url);
			// $this->set('request_uri', self::getRequestUri());

			// if(strpos($current_url, 'xn--') !== FALSE)
			// {
			// 	$this->set('current_url', self::decodeIdna($current_url));
			// }

			// if(strpos(self::getRequestUri(), 'xn--') !== FALSE)
			// {
			// 	$this->set('request_uri', self::decodeIdna(self::getRequestUri()));
			// }
		}

		/**
		 * execute view class of a requested module
		 *
		 * @return void
		 */
		// private function _render_view($s_req_module) {
		// 	$o_view = \X2board\Includes\getModule($s_req_module);
		// 	$o_view->init(); 
		// 	unset($o_view);
		// }

		/**
		 * handle request arguments for GET/POST
		 *
		 * @return void
		 */
		private function _setRequestArgument() {
	// var_dump($_REQUEST);
			if(!count($_REQUEST)) {
				return;
			}
			$requestMethod = $this->getRequestMethod();
// var_dump($requestMethod);
			foreach($_REQUEST as $key => $val) {
				if($val === '' || self::get($key) || in_array($key, $this->_a_ignore_request) ) {
					continue;
				}

	// error_log(print_r($key, true));
	// error_log(print_r($val, true));			
				$key = htmlentities($key);
				$val = $this->_filterRequestVar($key, $val, false, ($requestMethod == 'GET'));

				if($requestMethod == 'GET' && isset($_GET[$key])) {
					$set_to_vars = TRUE;
				}
				elseif($requestMethod == 'POST' && isset($_POST[$key])) {
					$set_to_vars = TRUE;
				}
				// elseif($requestMethod == 'JS_CALLBACK' && (isset($_GET[$key]) || isset($_POST[$key])))
				// {
				// 	$set_to_vars = TRUE;
				// }
				else {
					$set_to_vars = FALSE;
				}

				if($set_to_vars) {
					$this->_recursiveCheckVar($val);
				}

				$this->set($key, $val, $set_to_vars);
			}
			// check rewrite conf for pretty post URL
			$a_board_rewrite_settings = get_option( X2B_REWRITE_OPTION_TITLE );
			if(isset( $a_board_rewrite_settings[get_the_ID()])) {
				$set_to_vars = TRUE;
				if( get_query_var( 'post_id' ) ) {  // post_id from custom route detected, find the code blocks by X2B_REWRITE_OPTION_TITLE
					$this->set( 'post_id', get_query_var( 'post_id' ), $set_to_vars);
				}
				$this->set( 'use_rewrite', 'Y', $set_to_vars);
			}
			unset($a_board_rewrite_settings);
		}

		/**
		 * pretty uri를  command query로 재설정함
		 * pretty URL ?post/3 represents ?mod=post&post_id=3
		 * http://127.0.0.1/wp-x2board?post/168
		 * $_SERVER['REQUEST_URI']['path'] = /wp-x2board
		 * $_SERVER['REQUEST_URI']['query'] = post/168
		 */
		private function _convert_pretty_command_uri() {
			$a_cascaded_search_cmd = array('p' => 'page', 'cat' => 'category', 'tag' => 'tag', 
										   'search' => 'search_target', 'q' => 'search_keyword', 
										   'sort' => 'sort_field', 't' => 'sort_type');
			$a_query_param = array( 'cmd'=>null, 'page'=>null,
									'post_id'=>null, 'comment_id'=>null, 
									'search_target'=>null, 'search_keyword'=>null, 
									'sort_field'=>null, 'sort_type'=>null, 
									'tag'=>null, 'category'=>null
								);
			$request_uri = wp_parse_url( $_SERVER['REQUEST_URI'] );
			if( isset($request_uri['query'] ) )	{
// var_dump($request_uri['query']);
				$s_uri = trim($request_uri['query']);
				if( preg_match( "/^[-\w.]+\/[0-9]*$/m", $s_uri ) ) { // ex) post/1234
					$a_uri = explode('/', sanitize_text_field( $s_uri ) );
					$s_cmd = trim($a_uri[0]);
					$n_val = intval($a_uri[1]);
					switch($s_cmd) {
						case 'p':
							// $a_query_param['cmd'] = X2B_CMD_VIEW_LIST;
							$a_query_param['page'] = $n_val;  // page_no
							break;
						case X2B_CMD_VIEW_POST:         // old_post_id
						case X2B_CMD_VIEW_MODIFY_POST:  // old_post_id
						case X2B_CMD_VIEW_DELETE_POST:  // old_post_id
						case X2B_CMD_VIEW_REPLY_POST:   // parent_post_id
						case X2B_CMD_VIEW_WRITE_COMMENT:    // parent_post_id
							$a_query_param['cmd'] = $s_cmd;
							$a_query_param['post_id'] = $n_val;
							break;
						case X2B_CMD_VIEW_MODIFY_COMMENT:   // old_comment_id
						case X2B_CMD_VIEW_DELETE_COMMENT:    // old_comment_id
							$a_query_param['cmd'] = $s_cmd;
							$a_query_param['comment_id'] = $n_val;
							break;
					}
					unset($a_uri);
				}
				elseif( preg_match( "/^[-\w.]+$/m", $s_uri ) ) { // ex) X2B_CMD_VIEW_WRITE_POST
					$s_cmd = sanitize_text_field( trim($s_uri) );
					if( $s_cmd == X2B_CMD_VIEW_WRITE_POST) {
						$a_query_param['cmd'] = $s_cmd;	
					}
				}
				// elseif( preg_match( "/^[-\w.]+\/[0-9]+\/[0-9]*$/m", $s_uri ) ) { // ex) reply_comment/123/456
				// 	$a_uri = explode('/', sanitize_text_field( $s_uri ) );
				// 	$s_cmd = trim($a_uri[0]);
				// 	if( $s_cmd == X2B_CMD_VIEW_REPLY_COMMENT) {
				// 		$a_query_param['cmd'] = $s_cmd;	
				// 		$a_query_param['post_id'] = intval($a_uri[1]);  // parent_post_id
				// 		$a_query_param['comment_id'] = intval($a_uri[2]);  // parent_comment_id
				// 	}
				// 	unset($a_uri);
				// }
				// elseif( preg_match( "/^[-\w.]+\/[-\w.]*$/m", $s_uri ) ) { // ex) cat/category_value   한글 숫자 영문 혼합 preg_match 불가능
				// 	$a_uri = explode('/', sanitize_text_field( $s_uri ) );
				// 	$s_cmd = trim($a_uri[0]);
				// 	switch($s_cmd) {
				// 		case 'cat':
				// 			// $a_query_param['cmd'] = X2B_CMD_VIEW_LIST;
				// 			$a_query_param['category'] = trim($a_uri[1]);
				// 			break;
				// 		case 'tag':
				// 			// $a_query_param['cmd'] = X2B_CMD_VIEW_LIST;
				// 			$a_query_param['tag'] = trim($a_uri[1]);
				// 			break;
				// 	}
				// 	unset($a_uri);
				// }
				elseif( preg_match( "/^[-\w.]+\/[-\w.]+\/[-\w.]+\/[-\w.]*$/m", $s_uri ) ) { // ex) search/search_field/q/search_value
					$a_uri = explode('/', sanitize_text_field( $s_uri ) );
					$s_cmd = trim($a_uri[0]);
					$s_query = trim($a_uri[2]);
					if( $s_cmd == 'search' && $s_query == 'q' ) {  // q means query
						$a_query_param['search_target'] = trim($a_uri[1]);
						$a_query_param['search_keyword'] = trim($a_uri[3]);
					}
					elseif( $s_cmd == 'sort' && $s_query == 't' ) {  // t means type
						$a_query_param['search_target'] = trim($a_uri[1]);
						$a_query_param['sort_type'] = trim($a_uri[3]);
					}
					unset($a_uri);
				}
				else { // cascaded search   ex) cat/category_value 
					// $a_query_param['cmd'] = X2B_CMD_VIEW_LIST;
					$a_uri = explode('/', $s_uri );
					foreach( $a_uri as $n_idx => $s_val ) {
						if( $n_idx % 2 == 0 ) {
							if( isset( $a_cascaded_search_cmd[$s_val] ) ){
								$s_cmd = wp_unslash( $a_cascaded_search_cmd[$s_val]);
								$a_query_param[$s_cmd] = wp_unslash( $a_uri[$n_idx+1]);
							}
						}
					}
					unset($a_uri);					
				}
			}
// var_dump($a_query_param);
			// all command should be set to avoid error on skin rendering
			foreach($a_query_param as $s_qry_name => $s_qry_val ) {
				// var_dump($s_qry_name,self::get( $s_qry_name) );
				if( is_null(self::get( $s_qry_name) ) ){  // 기존 값이 없으면 쓰기, do not unset any value from conventional URI
					self::set( $s_qry_name, $s_qry_val );
				}
			}
			unset($a_cascaded_search_cmd);
			unset($a_query_param);
			unset($request_uri);
// var_dump(self::getAll4Skin());
		}

		private function _recursiveCheckVar($val) {
			if(is_string($val)) {
				foreach($this->_a_patterns as $pattern) {
					if(preg_match($pattern, $val)) {
						$this->isSuccessInit = FALSE;
						return;
					}
				}
			}
			else if(is_array($val)) {
				foreach($val as $val2) {
					$this->_recursiveCheckVar($val2);
				}
			}
		}

		/**
		 * Return request method
		 * @return string Request method type. (Optional - GET|POST|XMLRPC|JSON)
		 */
		public static function getRequestMethod() {
			$self = self::getInstance();
			return $self->request_method;
		}

		/**
		 * Finalize using resources, such as DB connection
		 *
		 * @return void
		 */
		public static function close() {
			// session_write_close();
		}

		/**
		 * Set a context value with a key
		 *
		 * @param string $key Key
		 * @param mixed $val Value
		 * @param mixed $set_to_get_vars If not FALSE, Set to get vars.
		 * @return void
		 */
		public static function set($key, $val, $set_to_get_vars = 0) {
			$self = self::getInstance();
			$self->context->{$key} = $val;
			if($set_to_get_vars === FALSE) {
				return;
			}
			if($val === NULL || $val === '') {
				unset($self->get_vars->{$key});
				return;
			}
// var_dump($key);
// var_dump($self->get_vars->{$key});
			if($set_to_get_vars || !isset($self->get_vars->{$key})) {
				$self->get_vars->{$key} = $val;
			}	
		}

		/**
		 * Return key's value
		 *
		 * @param string $key Key
		 * @return string Key
		 */
		public static function get($key) {
			$self = self::getInstance();
			if(!isset($self->context->{$key})) {
				return null;
			}
// var_dump($self->context);		
			return $self->context->{$key};
		}

		/**
		 * Get one more vars in object vars with given arguments(key1, key2, key3,...)
		 *
		 * @return object
		 */
		public static function gets() {
			$num_args = func_num_args();
			if($num_args < 1) {
				return;
			}
			$self = self::getInstance();
			$args_list = func_get_args();
			$output = new \stdClass();
			foreach($args_list as $v) {
				$output->{$v} = $self->get($v);
			}
			return $output;
		}

		/**
		 * Return all data for \X2board\Includes\Classes\Skin::load()
		 *
		 * @return object All context data
		 */
		public static function getAll4Skin() {
			$self = self::getInstance();
			return (array)$self->context;
		}

		/**
		 * Return values from the GET/POST/XMLRPC
		 *
		 * @return BaseObject Request variables.
		 */
		public static function getRequestVars() {
			$self = self::getInstance();
			if($self->get_vars) {
				return clone($self->get_vars);
			}
			return new \stdClass;
		}

		/**
		 * Determine request method
		 *
		 * @param string $type Request method. (Optional - GET|POST|XMLRPC|JSON)
		 * @return void
		 */
		public static function setRequestMethod($type = '') {
			$self = self::getInstance();
			// $self->js_callback_func = $self->getJSCallbackFunc();
			($type && $self->request_method = $type) or
			// ((strpos($_SERVER['CONTENT_TYPE'], 'json') || strpos($_SERVER['HTTP_CONTENT_TYPE'], 'json')) && $self->request_method = 'JSON') or
			// ($GLOBALS['HTTP_RAW_POST_DATA'] && $self->request_method = 'XMLRPC') or ($self->js_callback_func && $self->request_method = 'JS_CALLBACK') or ($self->request_method = $_SERVER['REQUEST_METHOD']);
			(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') && $self->request_method = 'JSON') or	($self->request_method = $_SERVER['REQUEST_METHOD']);
		}

		/**
		 * Make URL with args_list upon request URL
		 * warning: this method is for GET request only as this requires $this->_convert_pretty_command_uri() executed, if POST not work
		 * @param int $num_args Arguments nums
		 * @param array $args_list Argument list for set url
		 * @param string $domain Domain
		 * @param bool $encode If TRUE, use url encode.
		 * @param bool $autoEncode If TRUE, url encode automatically, detailed. Use this option, $encode value should be TRUE
		 * @return string URL
		 */
		// public static function getUrl($num_args = 0, $args_list = array(), $domain = null, $encode = TRUE, $autoEncode = FALSE) {
		public static function get_url($num_args = 0, $args_list = array(), $domain = null, $encode = TRUE, $autoEncode = FALSE) {
			// static $site_module_info = null;
			static $current_info = null;

			$self = self::getInstance();
			// // retrieve virtual site information
			// if(is_null($site_module_info)) {
			// 	$site_module_info = self::get('site_module_info');
			// }

			// // If $domain is set, handle it (if $domain is vid type, remove $domain and handle with $vid)
			// if($domain && isSiteID($domain)) {
			// 	$vid = $domain;
			// 	$domain = '';
			// }

			// // If $domain, $vid are not set, use current site information
			// if(!$domain && !$vid) {
			// 	if($site_module_info->domain && isSiteID($site_module_info->domain)) {
			// 		$vid = $site_module_info->domain;
			// 	}
			// 	else {
			// 		$domain = $site_module_info->domain;
			// 	}
			// }
			$domain = get_site_url().'/';
// error_log(print_r($domain, true));				
			// if $domain is set, compare current URL. If they are same, remove the domain, otherwise link to the domain.
			if($domain)	{
				$domain_info = parse_url($domain);
				if(is_null($current_info)) {
					if( !isset($_SERVER['HTTPS']) ) {
						$_SERVER['HTTPS'] = null;
					}
					$current_info = parse_url(($_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . \X2board\Includes\get_script_path());
				}
// error_log(print_r($current_info, true));									
				if($domain_info['host'] . $domain_info['path'] == $current_info['host'] . $current_info['path']) {
					$domain = null;// unset($domain);
				}
				else {
					$domain = preg_replace('/^(http|https):\/\//i', '', trim($domain));
					if(substr_compare($domain, '/', -1) !== 0) {
						$domain .= '/';
					}
				}
			}
			
			$get_vars = array();

			// If there is no GET variables or first argument is '' to reset variables
			if(!$self->get_vars || $args_list[0] == '') {
				// rearrange args_list
				if(is_array($args_list) && $args_list[0] == '') {
					array_shift($args_list);
				}
			}
			elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
				// Otherwise, make GET variables into array
				// $get_vars = get_object_vars($self->get_vars);
				$get_vars = get_object_vars($self->gets('cmd', 'post_id', 'page', 'category','search_target','search_keyword'));
// error_log(print_r($get_vars, true));
				// 이 조건문 작동하면 ?cmd=view_post&post_id=17&cpage=2#17_comment 와 같은 댓글 페이지 처리가 안됨
				// if( isset( $get_vars['cmd'] ) && $get_vars['cmd'] == X2B_CMD_VIEW_POST &&
				// 	isset( $get_vars['post_id'] ) && intval($get_vars['post_id']) > 0 ) {  // regarding view_post/10 as /10; view_post cmd malfunctions on title link of the view post UX 
						// $get_vars['cmd'] = null;
					// }
			}
			else { // POST method
				// if(!!$self->get_vars->module) $get_vars['module'] = $self->get_vars->module;
				// if(!!$self->get_vars->mid) $get_vars['mid'] = $self->get_vars->mid;
				// if(!!$self->get_vars->act) $get_vars['act'] = $self->get_vars->act;
				if(!!$self->get_vars->cmd) $get_vars['cmd'] = $self->get_vars->cmd;
				if(!!$self->get_vars->page) $get_vars['page'] = $self->get_vars->page;
				if(!!$self->get_vars->search_target) $get_vars['search_target'] = $self->get_vars->search_target;
				if(!!$self->get_vars->search_keyword) $get_vars['search_keyword'] = $self->get_vars->search_keyword;
				// if($get_vars['act'] == 'IS') {
				// 	if(!!$self->get_vars->is_keyword) $get_vars['is_keyword'] = $self->get_vars->is_keyword;
				// }
			}

			if( is_null($get_vars['search_target'])){
				unset($get_vars['search_target']);
			}
			if( is_null($get_vars['search_keyword'])){
				unset($get_vars['search_keyword']);
			}

			// arrange args_list
			for($i = 0, $c = count($args_list); $i < $c; $i += 2) {
				$key = $args_list[$i];
				$val = trim($args_list[$i + 1]);
// error_log(print_r($key, true));	
// error_log(print_r($val, true));	
				// If value is not set, remove the key
				if( $key != 'cmd') {  // keep cmd set always
					if(!isset($val) || !strlen($val)) {
						unset($get_vars[$key]);
						continue;
					}
				}
				// set new variables
				$get_vars[$key] = $val;
			}
			// remove vid, rnd
			// unset($get_vars['rnd']);
			// if($vid)
			// {
			// 	$get_vars['vid'] = $vid;
			// }
			// else
			// {
			// 	unset($get_vars['vid']);
			// }

			// for compatibility to lower versions
			// $cmd = $get_vars['cmd'];
			// $cmd_alias = array(
			// 	'dispMemberFriend' => 'dispCommunicationFriend',
			// 	'dispMemberMessages' => 'dispCommunicationMessages',
			// 	'dispDocumentAdminManageDocument' => 'dispDocumentManageDocument',
			// 	'dispModuleAdminSelectList' => 'dispModuleSelectList'
			// );
			// if($cmd_alias[$cmd]) {
			// 	$get_vars['cmd'] = $cmd_alias[$cmd];
			// }

			// organize URL
			$query = '';
// error_log(print_r($get_vars, true));				
			if(count($get_vars) > 0) {
				// if using rewrite mod
				// if($self->allow_rewrite)
				// {
				$cmd = $get_vars['cmd'];
				$page = isset( $get_vars['page'] ) ? $get_vars['page'] : ''; // $get_vars['page'];
				$post_id = isset( $get_vars['post_id'] ) ? $get_vars['post_id'] : '';
				$s_category_title = isset( $get_vars['category'] ) ? $get_vars['category'] : '';

				// $tmpArray = array('rss' => 1, 'atom' => 1, 'api' => 1);
				// $is_feed = isset($tmpArray[$act]);
// error_log(print_r($get_vars, true));					
				$target_map = array(
					'cmd' => get_the_permalink().( strlen($cmd) > 0 ? '?'.$cmd : '' ),  // X2B_CMD_VIEW_LIST equals with blank cmd
					'page' => get_the_permalink().'?p/'.$page,
					'post_id' => get_the_permalink().'?'.X2B_CMD_VIEW_POST.'/'.$post_id,
					'cmd.post_id' => '', // reserved for pretty post url  // get_the_permalink().'?'.$cmd.'/'.$post_id,
					'cmd.page' => get_the_permalink().'?p/'.$page,
					'category.cmd.post_id' => get_the_permalink().'?cat/'.$s_category_title,
					// 'vid' => $vid,
					// 'mid.vid' => "$vid/$mid",
					// 'entry.mid.vid' => "$vid/$mid/entry/" . $get_vars['entry'],
					// 'cmd.comment_id.post_id' => get_the_permalink().'?'.$cmd.'/'.$post_id.'/'.$comment_id,
					// 'document_srl.mid.vid' => "$vid/$mid/$srl",
					// 'act.document_srl.key.vid' => ($act == 'trackback') ? "$vid/$srl/$key/$act" : '',
				);

				$a_check_query = array( 'cmd', 'post_id', 'category', 'tag' );
				foreach( $a_check_query as $key_name ) {  // remove if null to avoid $target_map malfunction
// if( $key_name == 'category'){
	// error_log(print_r(strlen($get_vars[$key_name]) ==0, true));
	// var_dump($key_name,$get_vars[$key_name]);
// }
// error_log(print_r($key_name, true));
// error_log(print_r($get_vars[$key_name], true));
					if( array_key_exists($key_name, $get_vars) && is_null($get_vars[$key_name]) ) {
						unset($get_vars[$key_name]);
					}
				}
	
				if( array_key_exists('page', $get_vars) ) {
					if( is_null($get_vars['page']) || $get_vars['page'] == 1 ) {
						unset($get_vars['page']);
					}
				}
				
				$var_keys = array_keys($get_vars);
				sort($var_keys);						
				$target = join('.', $var_keys);
// if( strlen($s_category_title) )	{
	// error_log(print_r($target, true));	
// }
				$query = isset( $target_map[$target] ) ? $target_map[$target] : null;
				// try best to provie prettier post URL as possible
				if( self::get('use_rewrite') == 'Y' ) {
					if( $target == 'cmd.post_id' ) {
						$query = $cmd == X2B_CMD_VIEW_POST ? $self->_s_page_permlink.'/'.$post_id : $query .='?'.$cmd.'/'.$post_id;
					}
				}
			}
// error_log(print_r($query, true));
			if(!$query)	{
				$queries = array();
// error_log(print_r($get_vars, true));					
				foreach($get_vars as $key => $val) {
					if(is_array($val) && count($val) > 0) {
						foreach($val as $k => $v) {
							$queries[] = $key . '[' . $k . ']=' . urlencode($v);
						}
					}
					elseif(!is_array($val))	{
						$queries[] = $key . '=' . urlencode($val);
					}
				}
// if( $target == 'cmd.cpage.post_id'){
// error_log(print_r($queries, true));		
// }

				$query = get_the_permalink();
				$n_cnt_queires = count($queries);
				if($n_cnt_queires > 0) {
					$query .= '?' . join('&', $queries);
				}
				// }
			}

			// If using SSL always
			// $_use_ssl = $self->get('_use_ssl');
			// if($_use_ssl == 'always')
			// {
			// 	$query = $self->getRequestUri(ENFORCE_SSL, $domain) . $query;
			// 	// optional SSL use
			// }
			// elseif($_use_ssl == 'optional')
			// {
			// 	$ssl_mode = (($self->get('module') === 'admin') || ($get_vars['module'] === 'admin') || (isset($get_vars['act']) && $self->isExistsSSLAction($get_vars['act']))) ? ENFORCE_SSL : RELEASE_SSL;
			// 	$query = $self->getRequestUri($ssl_mode, $domain) . $query;
			// 	// no SSL
			// }
			// else
			// {
			// 	// currently on SSL but target is not based on SSL
			// 	if($_SERVER['HTTPS'] == 'on') {
			// 		$query = $self->get_request_uri(ENFORCE_SSL, $domain) . $query;
			// 	}
			// 	else if($domain) {  // if $domain is set
			// 		$query = $self->get_request_uri(FOLLOW_REQUEST_SSL, $domain) . $query;
			// 	}
			// 	// else {
			// 	// 	$query = \X2board\Includes\get_script_path() . $query;
			// 	// }
			// }

			if(!$encode) {
				return $query;
			}

			if(!$autoEncode) {
				return htmlspecialchars($query, ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
			}
			wp_die('bottom part of \X2board\Includes\Classes\get_url() executed specially');
			// $output = array();
			// $encode_queries = array();
			// $parsedUrl = parse_url($query);
			// parse_str($parsedUrl['query'], $output);
			// foreach($output as $key => $value) {
			// 	if(preg_match('/&([a-z]{2,}|#\d+);/', urldecode($value))) {
			// 		$value = urlencode(htmlspecialchars_decode(urldecode($value)));
			// 	}
			// 	$encode_queries[] = $key . '=' . $value;
			// }
			// return htmlspecialchars($parsedUrl['path'] . '?' . join('&', $encode_queries), ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
		}

		/**
		 * Get lang_type
		 *
		 * @return string Language type
		 */
		public static function getLangType() {
			$a_locale = array('ko_KR' => 'ko', 'en_GB'=>'en');
			if( !isset($a_locale[get_locale()]) ) {
				wp_die(__('undefined locale', 'x2board'));
			}
			return $a_locale[get_locale()];
			// $self = self::getInstance();
			// return $self->lang_type;
		}

		/**
		 * Return after removing an argument on the requested URL
		 *
		 * @param string $ssl_mode SSL mode
		 * @param string $domain Domain
		 * @retrun string converted URL
		 */
		// public static function getRequestUri($ssl_mode = FOLLOW_REQUEST_SSL, $domain = null)
		/*public static function get_request_uri($ssl_mode = FOLLOW_REQUEST_SSL, $domain = null) {
			static $url = array();

			// Check HTTP Request
			if(!isset($_SERVER['SERVER_PROTOCOL'])) {
				return;
			}

			// if(self::get('_use_ssl') == 'always') {
			// 	$ssl_mode = ENFORCE_SSL;
			// }

			if($domain) {
				$domain_key = md5($domain);
			}
			else {
				$domain_key = 'default';
			}

			if(isset($url[$ssl_mode][$domain_key])) {
				return $url[$ssl_mode][$domain_key];
			}

			$current_use_ssl = ($_SERVER['HTTPS'] == 'on');

			switch($ssl_mode) {
				case FOLLOW_REQUEST_SSL: $use_ssl = $current_use_ssl;
					break;
				case ENFORCE_SSL: $use_ssl = TRUE;
					break;
				case RELEASE_SSL: $use_ssl = FALSE;
					break;
			}

			if($domain) {
				$target_url = trim($domain);
				if(substr_compare($target_url, '/', -1) !== 0) {
					$target_url.= '/';
				}
			}
			else {
				$target_url = $_SERVER['HTTP_HOST'] . \X2board\Includes\get_script_path();
			}

			$url_info = parse_url('http://' . $target_url);

			if($current_use_ssl != $use_ssl) {
				unset($url_info['port']);
			}

			// if($use_ssl)
			// {
			// 	$port = self::get('_https_port');
			// 	if($port && $port != 443)
			// 	{
			// 		$url_info['port'] = $port;
			// 	}
			// 	elseif($url_info['port'] == 443)
			// 	{
			// 		unset($url_info['port']);
			// 	}
			// }
			// else
			{
				$port = self::get('_http_port');
				if($port && $port != 80) {
					$url_info['port'] = $port;
				}
				elseif($url_info['port'] == 80) {
					unset($url_info['port']);
				}
			}

			$url[$ssl_mode][$domain_key] = sprintf('%s://%s%s%s', $use_ssl ? 'https' : $url_info['scheme'], $url_info['host'], $url_info['port'] && $url_info['port'] != 80 ? ':' . $url_info['port'] : '', $url_info['path']);
error_log(print_r($url, true));
			return $url[$ssl_mode][$domain_key];
		}*/

		/**
		 * Load the database information
		 *
		 * @return void
		 */
		// function loadDBInfo()
		// {
		// 	$self = self::getInstance();

		// 	if(!$self->isInstalled())
		// 	{
		// 		return;
		// 	}

		// 	ob_start(); // trash BOM
		// 	include($self::getConfigFile());
		// 	ob_end_clean();

		// 	// If master_db information does not exist, the config file needs to be updated
		// 	if(!isset($db_info->master_db))
		// 	{
		// 		$db_info->master_db = array();
		// 		$db_info->master_db["db_type"] = $db_info->db_type;
		// 		unset($db_info->db_type);
		// 		$db_info->master_db["db_port"] = $db_info->db_port;
		// 		unset($db_info->db_port);
		// 		$db_info->master_db["db_hostname"] = $db_info->db_hostname;
		// 		unset($db_info->db_hostname);
		// 		$db_info->master_db["db_password"] = $db_info->db_password;
		// 		unset($db_info->db_password);
		// 		$db_info->master_db["db_database"] = $db_info->db_database;
		// 		unset($db_info->db_database);
		// 		$db_info->master_db["db_userid"] = $db_info->db_userid;
		// 		unset($db_info->db_userid);
		// 		$db_info->master_db["db_table_prefix"] = $db_info->db_table_prefix;
		// 		unset($db_info->db_table_prefix);

		// 		if(isset($db_info->master_db["db_table_prefix"]) && substr_compare($db_info->master_db["db_table_prefix"], '_', -1) !== 0)
		// 		{
		// 			$db_info->master_db["db_table_prefix"] .= '_';
		// 		}

		// 		$db_info->slave_db = array($db_info->master_db);
		// 		$self->setDBInfo($db_info);

		// 		$oInstallController = getController('install');
		// 		$oInstallController->makeConfigFile();
		// 	}

		// 	if(version_compare(PHP_VERSION, '7.0', '>='))
		// 	{
		// 		$db_info->master_db["db_type"] = preg_replace('/^mysql(_.+)?$/', 'mysqli$1', $db_info->master_db["db_type"]);
		// 		foreach($db_info->slave_db as &$slave_db_info)
		// 		{
		// 			$slave_db_info["db_type"] = preg_replace('/^mysql(_.+)?$/', 'mysqli$1', $slave_db_info["db_type"]);
		// 		}
		// 	}

		// 	if(!$db_info->use_prepared_statements)
		// 	{
		// 		$db_info->use_prepared_statements = 'Y';
		// 	}

		// 	if(!$db_info->time_zone)
		// 		$db_info->time_zone = date('O');
		// 	$GLOBALS['_time_zone'] = $db_info->time_zone;

		// 	if($db_info->qmail_compatibility != 'Y')
		// 		$db_info->qmail_compatibility = 'N';
		// 	$GLOBALS['_qmail_compatibility'] = $db_info->qmail_compatibility;

		// 	if(!$db_info->use_db_session)
		// 		$db_info->use_db_session = 'N';
		// 	if(!$db_info->use_ssl)
		// 		$db_info->use_ssl = 'none';
		// 	$this->set('_use_ssl', $db_info->use_ssl);

		// 	$self->set('_http_port', ($db_info->http_port) ? $db_info->http_port : NULL);
		// 	$self->set('_https_port', ($db_info->https_port) ? $db_info->https_port : NULL);

		// 	if(!$db_info->sitelock_whitelist) {
		// 		$db_info->sitelock_whitelist = '127.0.0.1';
		// 	}

		// 	if(is_string($db_info->sitelock_whitelist)) {
		// 		$db_info->sitelock_whitelist = explode(',', $db_info->sitelock_whitelist);
		// 	}

		// 	$self->setDBInfo($db_info);
		// }

		/**
		 * Get DB's db_type
		 *
		 * @return string DB's db_type
		 */
		// public static function getDBType()
		// {
		// 	$self = self::getInstance();
		// 	return $self->db_info->master_db["db_type"];
		// }

		/**
		 * Set DB information
		 *
		 * @param object $db_info DB information
		 * @return void
		 */
		// public static function setDBInfo($db_info)
		// {
		// 	$self = self::getInstance();
		// 	$self->db_info = $db_info;
		// }

		/**
		 * Get DB information
		 *
		 * @return object DB information
		 */
		// public static function getDBInfo()
		// {
		// 	$self = self::getInstance();
		// 	return $self->db_info;
		// }

		/**
		 * Return ssl status
		 *
		 * @return object SSL status (Optional - none|always|optional)
		 */
		// public static function getSslStatus()
		// {
		// 	$dbInfo = self::getDBInfo();
		// 	return $dbInfo->use_ssl;
		// }

		/**
		 * Return default URL
		 *
		 * @return string Default URL
		 */
		// public static function getDefaultUrl()
		// {
		// 	$db_info = self::getDBInfo();
		// 	return $db_info->default_url;
		// }

		/**
		 * Find supported languages
		 *
		 * @return array Supported languages
		 */
		// public static function loadLangSupported()
		// {
		// 	static $lang_supported = null;
		// 	if(!$lang_supported)
		// 	{
		// 		$langs = file(_XE_PATH_ . 'common/lang/lang.info');
		// 		foreach($langs as $val)
		// 		{
		// 			list($lang_prefix, $lang_text) = explode(',', $val);
		// 			$lang_text = trim($lang_text);
		// 			$lang_supported[$lang_prefix] = $lang_text;
		// 		}
		// 	}
		// 	return $lang_supported;
		// }

		/**
		 * Find selected languages to serve in the site
		 *
		 * @return array Selected languages
		 */
		// public static function loadLangSelected()
		// {
		// 	static $lang_selected = null;
		// 	if(!$lang_selected)
		// 	{
		// 		$orig_lang_file = _XE_PATH_ . 'common/lang/lang.info';
		// 		$selected_lang_file = _XE_PATH_ . 'files/config/lang_selected.info';
		// 		if(!FileHandler::hasContent($selected_lang_file))
		// 		{
		// 			$old_selected_lang_file = _XE_PATH_ . 'files/cache/lang_selected.info';
		// 			FileHandler::moveFile($old_selected_lang_file, $selected_lang_file);
		// 		}

		// 		if(!FileHandler::hasContent($selected_lang_file))
		// 		{
		// 			$buff = FileHandler::readFile($orig_lang_file);
		// 			FileHandler::writeFile($selected_lang_file, $buff);
		// 			$lang_selected = self::loadLangSupported();
		// 		}
		// 		else
		// 		{
		// 			$langs = file($selected_lang_file);
		// 			foreach($langs as $val)
		// 			{
		// 				list($lang_prefix, $lang_text) = explode(',', $val);
		// 				$lang_text = trim($lang_text);
		// 				$lang_selected[$lang_prefix] = $lang_text;
		// 			}
		// 		}
		// 	}
		// 	return $lang_selected;
		// }

		/**
		 * Single Sign On (SSO)
		 *
		 * @return bool True : Module handling is necessary in the control path of current request , False : Otherwise
		 */
		// function checkSSO()
		// {
		// 	// pass if it's not GET request or XE is not yet installed
		// 	if($this->db_info->use_sso != 'Y' || isCrawler())
		// 	{
		// 		return TRUE;
		// 	}
		// 	$checkActList = array('rss' => 1, 'atom' => 1);
		// 	if(self::getRequestMethod() != 'GET' || !self::isInstalled() || isset($checkActList[self::get('act')]))
		// 	{
		// 		return TRUE;
		// 	}

		// 	// pass if default URL is not set
		// 	$default_url = trim($this->db_info->default_url);
		// 	if(!$default_url)
		// 	{
		// 		return TRUE;
		// 	}

		// 	if(substr_compare($default_url, '/', -1) !== 0)
		// 	{
		// 		$default_url .= '/';
		// 	}

		// 	// for sites recieving SSO valdiation
		// 	if($default_url == self::getRequestUri())
		// 	{
		// 		if(self::get('url'))
		// 		{
		// 			$url = base64_decode(self::get('url'));
		// 			$url_info = parse_url($url);
		// 			if(!Password::checkSignature($url, self::get('sig')))
		// 			{
		// 				echo self::get('lang')->msg_invalid_request;
		// 				return false;
		// 			}

		// 			$oModuleModel = getModel('module');
		// 			$domain = $url_info['host'] . $url_info['path'];
		// 			if(substr_compare($domain, '/', -1) === 0) $domain = substr($domain, 0, -1);
		// 			$site_info = $oModuleModel->getSiteInfoByDomain($domain);

		// 			if($site_info->site_srl)
		// 			{
		// 			$url_info['query'].= ($url_info['query'] ? '&' : '') . 'SSOID=' . urlencode(session_id()) . '&sig=' . urlencode(Password::createSignature(session_id()));
		// 			$redirect_url = sprintf('%s://%s%s%s?%s', $url_info['scheme'], $url_info['host'], $url_info['port'] ? ':' . $url_info['port'] : '', $url_info['path'], $url_info['query']);
		// 			}
		// 			else
		// 			{
		// 				$redirect_url = $url;
		// 			}
		// 			header('location:' . $redirect_url);

		// 			return FALSE;
		// 		}
		// 		// for sites requesting SSO validation
		// 	}
		// 	else
		// 	{
		// 		// result handling : set session_name()
		// 		if($session_name = self::get('SSOID'))
		// 		{
		// 			if(!Password::checkSignature($session_name, self::get('sig')))
		// 			{
		// 				echo self::get('lang')->msg_invalid_request;
		// 				return false;
		// 			}

		// 			setcookie(session_name(), $session_name);

		// 			$url = preg_replace('/[\?\&]SSOID=.+$/', '', self::getRequestUrl());
		// 			header('location:' . $url);
		// 			return FALSE;
		// 			// send SSO request
		// 		}
		// 		else if(!self::get('SSOID') && $_COOKIE['sso'] != md5(self::getRequestUri()))
		// 		{
		// 			setcookie('sso', md5(self::getRequestUri()));
		// 			$origin_url = self::getRequestUrl();
		// 			$origin_sig = Password::createSignature($origin_url);
		// 			$url = sprintf("%s?url=%s&sig=%s", $default_url, urlencode(base64_encode($origin_url)), urlencode($origin_sig));
		// 			header('location:' . $url);
		// 			return FALSE;
		// 		}
		// 	}

		// 	return TRUE;
		// }

		/**
		 * Check if FTP info is registered
		 *
		 * @return bool True: FTP information is registered, False: otherwise
		 */
		// function isFTPRegisted()
		// {
		// 	return file_exists(self::getFTPConfigFile());
		// }

		/**
		 * Get FTP information
		 *
		 * @return object FTP information
		 */
		// public static function getFTPInfo()
		// {
		// 	$self = self::getInstance();

		// 	if(!$self->isFTPRegisted())
		// 	{
		// 		return null;
		// 	}

		// 	include($self->getFTPConfigFile());

		// 	return $ftp_info;
		// }

		/**
		 * Add string to browser title
		 *
		 * @param string $site_title Browser title to be added
		 * @return void
		 */
		// public static function addBrowserTitle($site_title)
		// {
		// 	if(!$site_title)
		// 	{
		// 		return;
		// 	}
		// 	$self = self::getInstance();

		// 	if($self->site_title)
		// 	{
		// 		$self->site_title .= ' - ' . $site_title;
		// 	}
		// 	else
		// 	{
		// 		$self->site_title = $site_title;
		// 	}
		// }

		/**
		 * Set string to browser title
		 *
		 * @param string $site_title Browser title  to be set
		 * @return void
		 */
		// public static function setBrowserTitle($site_title)
		// {
		// 	if(!$site_title)
		// 	{
		// 		return;
		// 	}
		// 	$self = self::getInstance();
		// 	$self->site_title = $site_title;
		// }

		/**
		 * Get browser title
		 *
		 * @return string Browser title(htmlspecialchars applied)
		 */
		// public static function getBrowserTitle()
		// {
		// 	$self = self::getInstance();

		// 	$oModuleController = getController('module');
		// 	$oModuleController->replaceDefinedLangCode($self->site_title);

		// 	return htmlspecialchars($self->site_title, ENT_COMPAT | ENT_HTML401, 'UTF-8', FALSE);
		// }

		/**
		 * Return layout's title
		 * @return string layout's title
		 */
		// public static function getSiteTitle()
		// {
		// 	$oModuleModel = getModel('module');
		// 	$moduleConfig = $oModuleModel->getModuleConfig('module');

		// 	if(isset($moduleConfig->siteTitle))
		// 	{
		// 		return $moduleConfig->siteTitle;
		// 	}
		// 	return '';
		// }

		/**
		 * Get browser title
		 * @deprecated
		 */
		// function _getBrowserTitle()
		// {
		// 	return $this->getBrowserTitle();
		// }

		/**
		 * Load language file according to language type
		 *
		 * @param string $path Path of the language file
		 * @return void
		 */
		// public static function loadLang($path)
		// {
		// 	global $lang;

		// 	$self = self::getInstance();
		// 	if(!$self->lang_type)
		// 	{
		// 		return;
		// 	}
		// 	if(!is_object($lang))
		// 	{
		// 		$lang = new stdClass;
		// 	}

		// 	if(!($filename = $self->_loadXmlLang($path)))
		// 	{
		// 		$filename = $self->_loadPhpLang($path);
		// 	}

		// 	if(!is_array($self->loaded_lang_files))
		// 	{
		// 		$self->loaded_lang_files = array();
		// 	}
		// 	if(in_array($filename, $self->loaded_lang_files))
		// 	{
		// 		return;
		// 	}

		// 	if($filename && is_readable($filename))
		// 	{
		// 		$self->loaded_lang_files[] = $filename;
		// 		include($filename);
		// 	}
		// 	else
		// 	{
		// 		$self->_evalxmlLang($path);
		// 	}
		// }

		/**
		 * Evaluation of xml language file
		 *
		 * @param string Path of the language file
		 * @return void
		 */
		// function _evalxmlLang($path)
		// {
		// 	global $lang;

		// 	if(!$path) return;

		// 	$_path = 'eval://' . $path;

		// 	if(in_array($_path, $this->loaded_lang_files))
		// 	{
		// 		return;
		// 	}

		// 	if(substr_compare($path, '/', -1) !== 0)
		// 	{
		// 		$path .= '/';
		// 	}

		// 	$oXmlLangParser = new XmlLangParser($path . 'lang.xml', $this->lang_type);
		// 	$content = $oXmlLangParser->getCompileContent();

		// 	if($content)
		// 	{
		// 		$this->loaded_lang_files[] = $_path;
		// 		eval($content);
		// 	}
		// }

		/**
		 * Load language file of xml type
		 *
		 * @param string $path Path of the language file
		 * @return string file name
		 */
		// function _loadXmlLang($path)
		// {
		// 	if(!$path) return;

		// 	$oXmlLangParser = new XmlLangParser($path . ((substr_compare($path, '/', -1) !== 0) ? '/' : '') . 'lang.xml', $this->lang_type);
		// 	return $oXmlLangParser->compile();
		// }

		/**
		 * Load language file of php type
		 *
		 * @param string $path Path of the language file
		 * @return string file name
		 */
		// function _loadPhpLang($path)
		// {
		// 	if(!$path) return;

		// 	if(substr_compare($path, '/', -1) !== 0)
		// 	{
		// 		$path .= '/';
		// 	}
		// 	$path_tpl = $path . '%s.lang.php';
		// 	$file = sprintf($path_tpl, $this->lang_type);

		// 	$langs = array('ko', 'en'); // this will be configurable.
		// 	while(!is_readable($file) && $langs[0])
		// 	{
		// 		$file = sprintf($path_tpl, array_shift($langs));
		// 	}

		// 	if(!is_readable($file))
		// 	{
		// 		return FALSE;
		// 	}
		// 	return $file;
		// }

		/**
		 * Set lang_type
		 *
		 * @param string $lang_type Language type.
		 * @return void
		 */
		// function setLangType($lang_type = 'ko')
		// {
		// 	$self = self::getInstance();

		// 	$self->lang_type = $lang_type;
		// 	$self->set('lang_type', $lang_type);

		// 	$_SESSION['lang_type'] = $lang_type;
		// }

		/**
		 * Return string accoring to the inputed code
		 *
		 * @param string $code Language variable name
		 * @return string If string for the code exists returns it, otherwise returns original code
		 */
		// public static function getLang($code)
		// {
		// 	if(!$code)
		// 	{
		// 		return;
		// 	}
		// 	if($GLOBALS['lang']->{$code})
		// 	{
		// 		return $GLOBALS['lang']->{$code};
		// 	}
		// 	return $code;
		// }

		/**
		 * Set data to lang variable
		 *
		 * @param string $code Language variable name
		 * @param string $val `$code`s value
		 * @return void
		 */
		// public static function setLang($code, $val)
		// {
		// 	if(!isset($GLOBALS['lang']))
		// 	{
		// 		$GLOBALS['lang'] = new stdClass();
		// 	}
		// 	$GLOBALS['lang']->{$code} = $val;
		// }

		/**
		 * Convert strings of variables in $source_object into UTF-8
		 *
		 * @param object $source_obj Conatins strings to convert
		 * @return object converted object
		 */
		// public static function convertEncoding($source_obj)
		// {
		// 	$charset_list = array(
		// 		'UTF-8', 'EUC-KR', 'CP949', 'ISO8859-1', 'EUC-JP', 'SHIFT_JIS',
		// 		'CP932', 'EUC-CN', 'HZ', 'GBK', 'GB18030', 'EUC-TW', 'BIG5',
		// 		'CP950', 'BIG5-HKSCS', 'ISO8859-6', 'ISO8859-8', 'JOHAB', 'CP1255',
		// 		'CP1256', 'CP862', 'ASCII', 'ISO8859-1', 'CP1250', 'CP1251',
		// 		'CP1252', 'CP1253', 'CP1254', 'CP1257', 'CP850', 'CP866'
		// 	);

		// 	$obj = clone $source_obj;

		// 	foreach($charset_list as $charset)
		// 	{
		// 		array_walk($obj,'Context::checkConvertFlag',$charset);
		// 		$flag = self::checkConvertFlag($flag = TRUE);
		// 		if($flag)
		// 		{
		// 			if($charset == 'UTF-8')
		// 			{
		// 				return $obj;
		// 			}
		// 			array_walk($obj,'Context::doConvertEncoding',$charset);
		// 			return $obj;
		// 		}
		// 	}
		// 	return $obj;
		// }

		/**
		 * Check flag
		 *
		 * @param mixed $val
		 * @param string $key
		 * @param mixed $charset charset
		 * @see arrayConvWalkCallback will replaced array_walk_recursive in >=PHP5
		 * @return void
		 */
		//public static function checkConvertFlag(&$val, $key = null, $charset = null)
		// public static function checkConvertFlag($val, $key = null, $charset = null)
		// {
		// 	static $flag = TRUE;
		// 	if($charset)
		// 	{
		// 		if(is_array($val))
		// 			array_walk($val,'Context::checkConvertFlag',$charset);
		// 		else if($val && iconv($charset,$charset,$val)!=$val) $flag = FALSE;
		// 		else $flag = FALSE;
		// 	}
		// 	else
		// 	{
		// 		$return = $flag;
		// 		$flag = TRUE;
		// 		return $return;
		// 	}
		// }

		/**
		 * Convert array type variables into UTF-8
		 *
		 * @param mixed $val
		 * @param string $key
		 * @param string $charset character set
		 * @see arrayConvWalkCallback will replaced array_walk_recursive in >=PHP5
		 * @return object converted object
		 */
		// function doConvertEncoding(&$val, $key = null, $charset)
		// {
		// 	if (is_array($val))
		// 	{
		// 		array_walk($val,'Context::doConvertEncoding',$charset);
		// 	}
		// 	else $val = iconv($charset,'UTF-8',$val);
		// }

		/**
		 * Convert strings into UTF-8
		 *
		 * @param string $str String to convert
		 * @return string converted string
		 */
		// public static function convertEncodingStr($str)
		// {
		//     if(!$str) return null;
		// 	$obj = new stdClass();
		// 	$obj->str = $str;
		// 	$obj = self::convertEncoding($obj);
		// 	return $obj->str;
		// }

		// function decodeIdna($domain)
		// {
		// 	if(strpos($domain, 'xn--') !== FALSE)
		// 	{
		// 		require_once(_XE_PATH_ . 'libs/idna_convert/idna_convert.class.php');
		// 		$IDN = new idna_convert(array('idn_version' => 2008));
		// 		$domain = $IDN->decode($domain);
		// 	}

		// 	return $domain;
		// }

		/**
		 * Force to set response method
		 *
		 * @param string $method Response method. [HTML|XMLRPC|JSON]
		 * @return void
		 */
		// public static function setResponseMethod($method = 'HTML')
		// {
		// 	$self = self::getInstance();

		// 	$methods = array('HTML' => 1, 'XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);
		// 	$self->response_method = isset($methods[$method]) ? $method : 'HTML';
		// }

		/**
		 * Get reponse method
		 *
		 * @return string Response method. If it's not set, returns request method.
		 */
		// public static function getResponseMethod()
		// {
		// 	$self = self::getInstance();

		// 	if($self->response_method)
		// 	{
		// 		return $self->response_method;
		// 	}

		// 	$method = $self->getRequestMethod();
		// 	$methods = array('HTML' => 1, 'XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);

		// 	return isset($methods[$method]) ? $method : 'HTML';
		// }

		/**
		 * handle global arguments
		 *
		 * @return void
		 */
		// function _checkGlobalVars()
		// {
		// 	$this->_recursiveCheckVar($_SERVER['HTTP_HOST']);

		// 	$pattern = "/[\,\"\'\{\}\[\]\(\);$]/";
		// 	if(preg_match($pattern, $_SERVER['HTTP_HOST']))
		// 	{
		// 		$this->isSuccessInit = FALSE;
		// 	}
		// }

		/**
		 * Handle request arguments for JSON
		 *
		 * @return void
		 */
		// function _setJSONRequestArgument()
		// {
		// 	if($this->getRequestMethod() != 'JSON')
		// 	{
		// 		return;
		// 	}

		// 	$params = array();
		// 	parse_str($GLOBALS['HTTP_RAW_POST_DATA'], $params);

		// 	foreach($params as $key => $val)
		// 	{
		// 		$key = htmlentities($key);
		// 		$this->set($key, $this->_filterRequestVar($key, $val, 1), TRUE);
		// 	}
		// }

		/**
		 * Handle request arguments for XML RPC
		 *
		 * @return void
		 */
		// function _setXmlRpcArgument()
		// {
		// 	if($this->getRequestMethod() != 'XMLRPC')
		// 	{
		// 		return;
		// 	}

		// 	$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		// 	if(Security::detectingXEE($xml))
		// 	{
		// 		header("HTTP/1.0 400 Bad Request");
		// 		exit;
		// 	}

		// 	$oXml = new XeXmlParser();
		// 	$xml_obj = $oXml->parse($xml);

		// 	$params = $xml_obj->methodcall->params;
		// 	unset($params->node_name, $params->attrs, $params->body);

		// 	if(!count(get_object_vars($params)))
		// 	{
		// 		return;
		// 	}

		// 	foreach($params as $key => $val)
		// 	{
		// 		$this->set($key, $this->_filterXmlVars($key, $val), TRUE);
		// 	}
		// }

		/**
		 * Filter xml variables
		 *
		 * @param string $key Variable key
		 * @param object $val Variable value
		 * @return mixed filtered value
		 */
		// function _filterXmlVars($key, $val)
		// {
		// 	if(is_array($val))
		// 	{
		// 		$stack = array();
		// 		foreach($val as $k => $v)
		// 		{
		// 			$stack[$k] = $this->_filterXmlVars($k, $v);
		// 		}

		// 		return $stack;
		// 	}

		// 	$body = $val->body;
		// 	unset($val->node_name, $val->attrs, $val->body);
		// 	if(!count(get_object_vars($val)))
		// 	{
		// 		return $this->_filterRequestVar($key, $body, 0);
		// 	}

		// 	$stack = new stdClass();
		// 	foreach($val as $k => $v)
		// 	{
		// 		$output = $this->_filterXmlVars($k, $v);
		// 		if(is_object($v) && $v->attrs->type == 'array')
		// 		{
		// 			$output = array($output);
		// 		}
		// 		if($k == 'value' && (is_array($v) || $v->attrs->type == 'array'))
		// 		{
		// 			return $output;
		// 		}

		// 		$stack->{$k} = $output;
		// 	}

		// 	if(!count(get_object_vars($stack)))
		// 	{
		// 		return NULL;
		// 	}

		// 	return $stack;
		// }

		/**
		 * Filter request variable
		 *
		 * @see Cast variables, such as _srl, page, and cpage, into interger
		 * @param string $key Variable key
		 * @param string $val Variable value
		 * @param string $do_stripslashes Whether to strip slashes
		 * @return mixed filtered value. Type are string or array
		 */
		function _filterRequestVar($key, $val, $do_stripslashes = true, $remove_hack = false)
		{
			if(!($isArray = is_array($val)))
			{
				$val = array($val);
			}

			$result = array();
			foreach($val as $k => $v)
			{
				$k =  \X2board\Includes\escape($k);
	// error_log(print_r($_SERVER, true));
	// error_log(print_r($v, true));				
				$result[$k] = $v;

				// if($remove_hack && !is_array($result[$k])) {
				// 	if(stripos($result[$k], '<script') || stripos($result[$k], 'lt;script') || stripos($result[$k], '%3Cscript'))
				// 	{
				// 		$result[$k] = escape($result[$k]);
				// 	}
				// }

				if( $_SERVER['SCRIPT_NAME'] == '/wp-admin/admin.php' ) {  // for admin screen
					$result[$k] = \X2board\Includes\escape($result[$k], false);
				}
				elseif($key === 'page' || $key === 'cpage' )
				{
					$result[$k] = !preg_match('/^[0-9,]+$/', $result[$k]) ? (int) $result[$k] : $result[$k];	
				}
				// elseif(in_array($key, array('mid','search_keyword','search_target','xe_validator_id'))) {
				// 	$result[$k] = escape($result[$k], false);
				// }
				// elseif($key === 'vid')
				// {
				// 	$result[$k] = urlencode($result[$k]);
				// }
				// elseif(stripos($key, 'XE_VALIDATOR', 0) === 0)
				// {
				// 	unset($result[$k]);
				// }
				// else
				// {
				// 	if(in_array($k, array(
				// 		'act',
				// 		'addon',
				// 		'cur_mid',
				// 		'full_browse',
				// 		'http_status_message',
				// 		'l',
				// 		'layout',
				// 		'm',
				// 		'mid',
				// 		'module',
				// 		'selected_addon',
				// 		'selected_layout',
				// 		'selected_widget',
				// 		'widget',
				// 		'widgetstyle',
				// 	)))
				// 	{
				// 		$result[$k] = urlencode(preg_replace("/[^a-z0-9-_]+/i", '', $result[$k]));
				// 	}

				// 	if($do_stripslashes && version_compare(PHP_VERSION, '5.4.0', '<') && get_magic_quotes_gpc())
				// 	{
				// 		if (is_array($result[$k]))
				// 		{
				// 			array_walk_recursive($result[$k], function(&$val) { $val = stripslashes($val); });
				// 		}
				// 		else
				// 		{
				// 			$result[$k] = stripslashes($result[$k]);
				// 		}
				// 	}

				// 	if(is_array($result[$k]))
				// 	{
				// 		array_walk_recursive($result[$k], function(&$val) { $val = trim($val); });
				// 	}
				// 	else
				// 	{
				// 		$result[$k] = trim($result[$k]);
				// 	}

				// 	if($remove_hack)
				// 	{
				// 		$result[$k] = escape($result[$k], false);
				// 	}
				// }
			}

			return $isArray ? $result : $result[0];
		}

		/**
		 * Check if there exists uploaded file
		 *
		 * @return bool True: exists, False: otherwise
		 */
		// function isUploaded()
		// {
		// 	$self = self::getInstance();
		// 	return $self->is_uploaded;
		// }

		/**
		 * Handle uploaded file
		 *
		 * @return void
		 */
		// function _setUploadedArgument()
		// {
		// 	if($_SERVER['REQUEST_METHOD'] != 'POST' || !$_FILES || (stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === FALSE && stripos($_SERVER['HTTP_CONTENT_TYPE'], 'multipart/form-data') === FALSE))
		// 	{
		// 		return;
		// 	}

		// 	foreach($_FILES as $key => $val)
		// 	{
		// 		$tmp_name = $val['tmp_name'];

		// 		if(!is_array($tmp_name))
		// 		{
		// 			if(!UploadFileFilter::check($tmp_name, $val['name']))
		// 			{
		// 				unset($_FILES[$key]);
		// 				continue;
		// 			}
		// 			$val['name'] = escape($val['name'], FALSE);
		// 			$this->set($key, $val, TRUE);
		// 			$this->is_uploaded = TRUE;
		// 		}
		// 		else
		// 		{
		// 			$files = array();
		// 			foreach ($tmp_name as $i => $j)
		// 			{
		// 				if(!UploadFileFilter::check($val['tmp_name'][$i], $val['name'][$i]))
		// 				{
		// 					$files = array();
		// 					unset($_FILES[$key]);
		// 					break;
		// 				}
		// 				$file = array();
		// 				$file['name'] = $val['name'][$i];
		// 				$file['type'] = $val['type'][$i];
		// 				$file['tmp_name'] = $val['tmp_name'][$i];
		// 				$file['error'] = $val['error'][$i];
		// 				$file['size'] = $val['size'][$i];
		// 				$files[] = $file;
		// 			}
		// 			if(count($files))
		// 			{
		// 				self::set($key, $files, true);
		// 			}
		// 		}
		// 	}
		// }

		/**
		 * Return request URL
		 * @return string request URL
		 */
		// public static function getRequestUrl()
		// {
		// 	static $url = null;
		// 	if(is_null($url))
		// 	{
		// 		$url = self::getRequestUri();
		// 		if(count($_GET) > 0)
		// 		{
		// 			foreach($_GET as $key => $val)
		// 			{
		// 				$vars[] = $key . '=' . ($val ? urlencode(self::convertEncodingStr($val)) : '');
		// 			}
		// 			$url .= '?' . join('&', $vars);
		// 		}
		// 	}
		// 	return $url;
		// }

		/**
		 * Return js callback func.
		 * @return string callback func.
		 */
		// function getJSCallbackFunc()
		// {
		// 	$self = self::getInstance();
		// 	$js_callback_func = isset($_GET['xe_js_callback']) ? $_GET['xe_js_callback'] : $_POST['xe_js_callback'];

		// 	if(!preg_match('/^[a-z0-9\.]+$/i', $js_callback_func))
		// 	{
		// 		unset($js_callback_func);
		// 		unset($_GET['xe_js_callback']);
		// 		unset($_POST['xe_js_callback']);
		// 	}

		// 	return $js_callback_func;
		// }

		/**
		 * Register if an action is to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
		 *
		 * @param string $action act name
		 * @return void
		 */
		// public static function addSSLAction($action)
		// {
		// 	$self = self::getInstance();

		// 	if(!is_readable($self->sslActionCacheFile))
		// 	{
		// 		$buff = '<?php if(!defined("__XE__"))exit;';
		// 		FileHandler::writeFile($self->sslActionCacheFile, $buff);
		// 	}

		// 	if(!isset($self->ssl_actions[$action]))
		// 	{
		// 		$self->ssl_actions[$action] = 1;
		// 		$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
		// 		FileHandler::writeFile($self->sslActionCacheFile, $sslActionCacheString, 'a');
		// 	}
		// }

		/**
		 * Register if actions are to be encrypted by SSL. Those actions are sent to https in common/js/xml_handler.js
		 *
		 * @param string $action act name
		 * @return void
		 */
		// public static function addSSLActions($action_array)
		// {
		// 	$self = self::getInstance();

		// 	if(!is_readable($self->sslActionCacheFile))
		// 	{
		// 		unset($self->ssl_actions);
		// 		$buff = '<?php if(!defined("__XE__"))exit;';
		// 		FileHandler::writeFile($self->sslActionCacheFile, $buff);
		// 	}

		// 	foreach($action_array as $action)
		// 	{
		// 		if(!isset($self->ssl_actions[$action]))
		// 		{
		// 			$self->ssl_actions[$action] = 1;
		// 			$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
		// 			FileHandler::writeFile($self->sslActionCacheFile, $sslActionCacheString, 'a');
		// 		}
		// 	}
		// }

		/**
		 * Delete if action is registerd to be encrypted by SSL.
		 *
		 * @param string $action act name
		 * @return void
		 */
		// function subtractSSLAction($action)
		// {
		// 	$self = self::getInstance();

		// 	if($self->isExistsSSLAction($action))
		// 	{
		// 		$sslActionCacheString = sprintf('$sslActions[\'%s\'] = 1;', $action);
		// 		$buff = FileHandler::readFile($self->sslActionCacheFile);
		// 		$buff = str_replace($sslActionCacheString, '', $buff);
		// 		FileHandler::writeFile($self->sslActionCacheFile, $buff);
		// 	}
		// }

		/**
		 * Get SSL Action
		 *
		 * @return string acts in array
		 */
		// public static function getSSLActions()
		// {
		// 	$self = self::getInstance();
		// 	if($self->getSslStatus() == 'optional')
		// 	{
		// 		return $self->ssl_actions;
		// 	}
		// }

		/**
		 * Check SSL action are existed
		 *
		 * @param string $action act name
		 * @return bool If SSL exists, return TRUE.
		 */
		// public static function isExistsSSLAction($action)
		// {
		// 	$self = self::getInstance();
		// 	return isset($self->ssl_actions[$action]);
		// }

		/**
		 * Normalize file path
		 *
		 * @deprecated
		 * @param string $file file path
		 * @return string normalized file path
		 */
		// function normalizeFilePath($file)
		// {
		// 	if($file[0] != '/' && $file[0] != '.' && strpos($file, '://') === FALSE)
		// 	{
		// 		$file = './' . $file;
		// 	}
		// 	$file = preg_replace('@/\./|(?<!:)\/\/@', '/', $file);
		// 	while(strpos($file, '/../') !== FALSE)
		// 	{
		// 		$file = preg_replace('/\/([^\/]+)\/\.\.\//s', '/', $file, 1);
		// 	}

		// 	return $file;
		// }

		/**
		 * Get abstract file url
		 *
		 * @deprecated
		 * @param string $file file path
		 * @return string Converted file path
		 */
		// function getAbsFileUrl($file)
		// {
		// 	$file = self::normalizeFilePath($file);
		// 	$script_path = getScriptPath();
		// 	if(strpos($file, './') === 0)
		// 	{
		// 		$file = $script_path . substr($file, 2);
		// 	}
		// 	elseif(strpos($file, '../') === 0)
		// 	{
		// 		$file = self::normalizeFilePath($script_path . $file);
		// 	}

		// 	return $file;
		// }

		/**
		 * Load front end file
		 *
		 * @param array $args array
		 * case js :
		 * 		$args[0]: file name,
		 * 		$args[1]: type (head | body),
		 * 		$args[2]: target IE,
		 * 		$args[3]: index
		 * case css :
		 * 		$args[0]: file name,
		 * 		$args[1]: media,
		 * 		$args[2]: target IE,
		 * 		$args[3]: index
		 *
		 */
		// public static function loadFile($args)
		// {
		// 	$self = self::getInstance();

		// 	$self->oFrontEndFileHandler->loadFile($args);
		// }

		/**
		 * Unload front end file
		 *
		 * @param string $file File name with path
		 * @param string $targetIe Target IE
		 * @param string $media Media query
		 * @return void
		 */
		// function unloadFile($file, $targetIe = '', $media = 'all')
		// {
		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->unloadFile($file, $targetIe, $media);
		// }

		/**
		 * Unload front end file all
		 *
		 * @param string $type Unload target (optional - all|css|js)
		 * @return void
		 */
		// function unloadAllFiles($type = 'all')
		// {
		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->unloadAllFiles($type);
		// }

		/**
		 * Add the js file
		 *
		 * @deprecated
		 * @param string $file File name with path
		 * @param string $optimized optimized (That seems to not use)
		 * @param string $targetie target IE
		 * @param string $index index
		 * @param string $type Added position. (head:<head>..</head>, body:<body>..</body>)
		 * @param bool $isRuleset Use ruleset
		 * @param string $autoPath If path not readed, set the path automatically.
		 * @return void
		 */
		// public static function addJsFile($file, $optimized = FALSE, $targetie = '', $index = 0, $type = 'head', $isRuleset = FALSE, $autoPath = null)
		// {
		// 	if($isRuleset)
		// 	{
		// 		if(strpos($file, '#') !== FALSE)
		// 		{
		// 			$file = str_replace('#', '', $file);
		// 			if(!is_readable($file))
		// 			{
		// 				$file = $autoPath;
		// 			}
		// 		}
		// 		$validator = new Validator($file);
		// 		$validator->setCacheDir('files/cache');
		// 		$file = $validator->getJsPath();
		// 	}

		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->loadFile(array($file, $type, $targetie, $index));
		// }

		/**
		 * Remove the js file
		 *
		 * @deprecated
		 * @param string $file File name with path
		 * @param string $optimized optimized (That seems to not use)
		 * @param string $targetie target IE
		 * @return void
		 */
		// function unloadJsFile($file, $optimized = FALSE, $targetie = '')
		// {
		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->unloadFile($file, $targetie);
		// }

		/**
		 * Unload all javascript files
		 *
		 * @return void
		 */
		// function unloadAllJsFiles()
		// {
		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->unloadAllFiles('js');
		// }

		/**
		 * Add javascript filter
		 *
		 * @param string $path File path
		 * @param string $filename File name
		 * @return void
		 */
		// public static function addJsFilter($path, $filename)
		// {
		// 	$oXmlFilter = new XmlJSFilter($path, $filename);
		// 	$oXmlFilter->compile();
		// }

		/**
		 * Same as array_unique but works only for file subscript
		 *
		 * @deprecated
		 * @param array $files File list
		 * @return array File list
		 */
		// function _getUniqueFileList($files)
		// {
		// 	ksort($files);
		// 	$files = array_values($files);
		// 	$filenames = array();
		// 	for($i = 0, $c = count($files); $i < $c; ++$i)
		// 	{
		// 		if(in_array($files[$i]['file'], $filenames))
		// 		{
		// 			unset($files[$i]);
		// 		}
		// 		$filenames[] = $files[$i]['file'];
		// 	}

		// 	return $files;
		// }

		/**
		 * Returns the list of javascripts that matches the given type.
		 *
		 * @param string $type Added position. (head:<head>..</head>, body:<body>..</body>)
		 * @return array Returns javascript file list. Array contains file, targetie.
		 */
		// public static function getJsFile($type = 'head')
		// {
		// 	$self = self::getInstance();
		// 	return $self->oFrontEndFileHandler->getJsFileList($type);
		// }

		/**
		 * Add CSS file
		 *
		 * @deprecated
		 * @param string $file File name with path
		 * @param string $optimized optimized (That seems to not use)
		 * @param string $media Media query
		 * @param string $targetie target IE
		 * @param string $index index
		 * @return void
		 *
		 */
		// public static function addCSSFile($file, $optimized = FALSE, $media = 'all', $targetie = '', $index = 0)
		// {
		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->loadFile(array($file, $media, $targetie, $index));
		// }

		/**
		 * Remove css file
		 *
		 * @deprecated
		 * @param string $file File name with path
		 * @param string $optimized optimized (That seems to not use)
		 * @param string $media Media query
		 * @param string $targetie target IE
		 * @return void
		 */
		// function unloadCSSFile($file, $optimized = FALSE, $media = 'all', $targetie = '')
		// {
		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->unloadFile($file, $targetie, $media);
		// }

		/**
		 * Unload all css files
		 *
		 * @return void
		 */
		// function unloadAllCSSFiles()
		// {
		// 	$self = self::getInstance();
		// 	$self->oFrontEndFileHandler->unloadAllFiles('css');
		// }

		/**
		 * Return a list of css files
		 *
		 * @return array Returns css file list. Array contains file, media, targetie.
		 */
		// public static function getCSSFile()
		// {
		// 	$self = self::getInstance();
		// 	return $self->oFrontEndFileHandler->getCssFileList();
		// }

		/**
		 * Returns javascript plugin file info
		 * @param string $pluginName
		 * @return stdClass
		 */
		// function getJavascriptPluginInfo($pluginName)
		// {
		// 	if($plugin_name == 'ui.datepicker')
		// 	{
		// 		$plugin_name = 'ui';
		// 	}

		// 	$plugin_path = './common/js/plugins/' . $pluginName . '/';
		// 	$info_file = $plugin_path . 'plugin.load';
		// 	if(!is_readable($info_file))
		// 	{
		// 		return;
		// 	}

		// 	$list = file($info_file);
		// 	$result = new stdClass();
		// 	$result->jsList = array();
		// 	$result->cssList = array();

		// 	foreach($list as $filename)
		// 	{
		// 		$filename = trim($filename);
		// 		if(!$filename)
		// 		{
		// 			continue;
		// 		}

		// 		if(strncasecmp('./', $filename, 2) === 0)
		// 		{
		// 			$filename = substr($filename, 2);
		// 		}

		// 		if(substr_compare($filename, '.js', -3) === 0)
		// 		{
		// 			$result->jsList[] = $plugin_path . $filename;
		// 		}
		// 		elseif(substr_compare($filename, '.css', -4) === 0)
		// 		{
		// 			$result->cssList[] = $plugin_path . $filename;
		// 		}
		// 	}

		// 	if(is_dir($plugin_path . 'lang'))
		// 	{
		// 		$result->langPath = $plugin_path . 'lang';
		// 	}

		// 	return $result;
		// }
		/**
		 * Load javascript plugin
		 *
		 * @param string $plugin_name plugin name
		 * @return void
		 */
		// public static function loadJavascriptPlugin($plugin_name)
		// {
		// 	static $loaded_plugins = array();

		// 	$self = self::getInstance();
		// 	if($plugin_name == 'ui.datepicker')
		// 	{
		// 		$plugin_name = 'ui';
		// 	}

		// 	if($loaded_plugins[$plugin_name])
		// 	{
		// 		return;
		// 	}
		// 	$loaded_plugins[$plugin_name] = TRUE;

		// 	$plugin_path = './common/js/plugins/' . $plugin_name . '/';
		// 	$info_file = $plugin_path . 'plugin.load';
		// 	if(!is_readable($info_file))
		// 	{
		// 		return;
		// 	}

		// 	$list = file($info_file);
		// 	foreach($list as $filename)
		// 	{
		// 		$filename = trim($filename);
		// 		if(!$filename)
		// 		{
		// 			continue;
		// 		}

		// 		if(strncasecmp('./', $filename, 2) === 0)
		// 		{
		// 			$filename = substr($filename, 2);
		// 		}
		// 		if(substr_compare($filename, '.js', -3) === 0)
		// 		{
		// 			$self->loadFile(array($plugin_path . $filename, 'body', '', 0), TRUE);
		// 		}
		// 		if(substr_compare($filename, '.css', -4) === 0)
		// 		{
		// 			$self->loadFile(array($plugin_path . $filename, 'all', '', 0), TRUE);
		// 		}
		// 	}

		// 	if(is_dir($plugin_path . 'lang'))
		// 	{
		// 		$self->loadLang($plugin_path . 'lang');
		// 	}
		// }

		/**
		 * Add html code before </head>
		 *
		 * @param string $header add html code before </head>.
		 * @return void
		 */
		// public static function addHtmlHeader($header)
		// {
		// 	$self = self::getInstance();
		// 	$self->html_header .= "\n" . $header;
		// }

		// public static function clearHtmlHeader()
		// {
		// 	$self = self::getInstance();
		// 	$self->html_header = '';
		// }

		/**
		 * Returns added html code by addHtmlHeader()
		 *
		 * @return string Added html code before </head>
		 */
		// public static function getHtmlHeader()
		// {
		// 	$self = self::getInstance();
		// 	return $self->html_header;
		// }

		/**
		 * Add css class to Html Body
		 *
		 * @param string $class_name class name
		 */
		// function addBodyClass($class_name)
		// {
		// 	$self = self::getInstance();
		// 	$self->body_class[] = $class_name;
		// }

		/**
		 * Return css class to Html Body
		 *
		 * @return string Return class to html body
		 */
		// public static function getBodyClass()
		// {
		// 	$self = self::getInstance();
		// 	$self->body_class = array_unique($self->body_class);

		// 	return (count($self->body_class) > 0) ? sprintf(' class="%s"', join(' ', $self->body_class)) : '';
		// }

		/**
		 * Add html code after <body>
		 *
		 * @param string $header Add html code after <body>
		 */
		// function addBodyHeader($header)
		// {
		// 	$self = self::getInstance();
		// 	$self->body_header .= "\n" . $header;
		// }

		/**
		 * Returns added html code by addBodyHeader()
		 *
		 * @return string Added html code after <body>
		 */
		// public static function getBodyHeader()
		// {
		// 	$self = self::getInstance();
		// 	return $self->body_header;
		// }

		/**
		 * Add html code before </body>
		 *
		 * @param string $footer Add html code before </body>
		 */
		// public static function addHtmlFooter($footer)
		// {
		// 	$self = self::getInstance();
		// 	$self->html_footer .= ($self->Htmlfooter ? "\n" : '') . $footer;
		// }

		/**
		 * Returns added html code by addHtmlHeader()
		 *
		 * @return string Added html code before </body>
		 */
		// public static function getHtmlFooter()
		// {
		// 	$self = self::getInstance();
		// 	return $self->html_footer;
		// }

		/**
		 * Get config file
		 *
		 * @retrun string The path of the config file that contains database settings
		 */
		// public static function getConfigFile()
		// {
		// 	return _XE_PATH_ . 'files/config/db.config.php';
		// }

		/**
		 * Get FTP config file
		 *
		 * @return string The path of the config file that contains FTP settings
		 */
		// function getFTPConfigFile()
		// {
		// 	return _XE_PATH_ . 'files/config/ftp.config.php';
		// }

		/**
		 * Checks whether XE is installed
		 *
		 * @return bool True if the config file exists, otherwise FALSE.
		 */
		// public static function isInstalled()
		// {
		// 	return FileHandler::hasContent(self::getConfigFile());
		// }

		/**
		 * Transforms codes about widget or other features into the actual code, deprecatred
		 *
		 * @param string Transforms codes
		 * @return string Transforms codes
		 */
		// function transContent($content)
		// {
		// 	return $content;
		// }

		/**
		 * Check whether it is allowed to use rewrite mod
		 *
		 * @return bool True if it is allowed to use rewrite mod, otherwise FALSE
		 */
		// public static function isAllowRewrite()
		// {
		// 	$oContext = self::getInstance();
		// 	return $oContext->allow_rewrite;
		// }

		/**
		 * Converts a local path into an URL
		 *
		 * @param string $path URL path
		 * @return string Converted path
		 */
		// public static function pathToUrl($path)
		// {
		// 	$xe = _XE_PATH_;
		// 	$path = strtr($path, "\\", "/");

		// 	$base_url = preg_replace('@^https?://[^/]+/?@', '', self::getRequestUri());

		// 	$_xe = explode('/', $xe);
		// 	$_path = explode('/', $path);
		// 	$_base = explode('/', $base_url);

		// 	if(!$_base[count($_base) - 1])
		// 	{
		// 		array_pop($_base);
		// 	}

		// 	foreach($_xe as $idx => $dir)
		// 	{
		// 		if($_path[0] != $dir)
		// 		{
		// 			break;
		// 		}
		// 		array_shift($_path);
		// 	}

		// 	$idx = count($_xe) - $idx - 1;
		// 	while($idx--)
		// 	{
		// 		if(count($_base) > 0)
		// 		{
		// 			array_shift($_base);
		// 		}
		// 		else
		// 		{
		// 			array_unshift($_base, '..');
		// 		}
		// 	}

		// 	if(count($_base) > 0)
		// 	{
		// 		array_unshift($_path, join('/', $_base));
		// 	}

		// 	$path = '/' . join('/', $_path);
		// 	if(substr_compare($path, '/', -1) !== 0)
		// 	{
		// 		$path .= '/';
		// 	}
		// 	return $path;
		// }

		/**
		 * Get meta tag
		 * @return array The list of meta tags
		 */
		// public static function getMetaTag()
		// {
		// 	$self = self::getInstance();

		// 	if(!is_array($self->meta_tags))
		// 	{
		// 		$self->meta_tags = array();
		// 	}

		// 	$ret = array();
		// 	foreach($self->meta_tags as $key => $val)
		// 	{
		// 		list($name, $is_http_equiv) = explode("\t", $key);
		// 		$ret[] = array('name' => $name, 'is_http_equiv' => $is_http_equiv, 'content' => $val);
		// 	}

		// 	return $ret;
		// }

		/**
		 * Add the meta tag
		 *
		 * @param string $name name of meta tag
		 * @param string $content content of meta tag
		 * @param mixed $is_http_equiv value of http_equiv
		 * @return void
		 */
		// public static function addMetaTag($name, $content, $is_http_equiv = FALSE)
		// {
		// 	$self = self::getInstance();
		// 	$self->meta_tags[$name . "\t" . ($is_http_equiv ? '1' : '0')] = $content;
		// }
	}  // END CLASS
}
/* End of file Context.class.php */
/* Location: ./classes/context/Context.class.php */
