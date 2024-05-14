<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
/* WP port by singleview.co.kr */

/**
 * postController class
 * post the module's controller class
 *
 * @author XEHub (developers@xpressengine.com)
 * @package /modules/post
 * @version 0.1
 */
namespace X2board\Includes\Modules\Post;

if ( !defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly.
}

if (!class_exists('\\X2board\\Includes\\Modules\\Post\\postController')) {

	class postController extends post {

		private $_o_wp_filesystem = null;

		function __construct() {
// var_dump('post controller __construct()');
			if(!isset($_SESSION['x2b_banned_post'])) {
				$_SESSION['x2b_banned_post'] = array();
			}
			if(!isset($_SESSION['x2b_readed_post'])) {
				$_SESSION['x2b_readed_post'] = array();
			}
			if(!isset($_SESSION['x2b_own_post'])) {
				$_SESSION['x2b_own_post'] = array();
			}

			require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
			require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
			$this->_o_wp_filesystem = new \WP_Filesystem_Direct(false);
		}

		/**
		 * Initialization
		 * @return void
		 */
		function init() { 
var_dump('post controller init()');
		}

		/**
		 * Insert new post
		 * @param object $obj
		 * @param bool $manual_inserted
		 * @param bool $isRestore
		 * @return object
		 */
		// function insertDocument($obj, $manual_inserted = false, $isRestore = false, $isLatest = true)
		public function insert_post($obj, $manual_inserted = false, $isRestore = false, $isLatest = true) {
			if(!$manual_inserted) {  // check WP nonce if a guest inserts a new post
				$wp_verify_nonce = \X2board\Includes\Classes\Context::get('x2b_'.X2B_CMD_PROC_WRITE_POST.'_nonce');
// var_dump($wp_verify_nonce);
				if( is_null( $wp_verify_nonce ) ){
					return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request1', 'x2board') );
				}
				if( !wp_verify_nonce($wp_verify_nonce, 'x2b_'.X2B_CMD_PROC_WRITE_POST) ){
					return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request2', 'x2board') );
				}
			}

			// begin transaction
			// $oDB = &DB::getInstance();
			// $oDB->begin();
			// List variables
			// if(!$obj->comment_status) {
			// 	$obj->comment_status = 'DENY';
			// }
			// if($obj->comment_status) {
			// 	$obj->commentStatus = $obj->comment_status;
			// }
			// if(!$obj->commentStatus) {
			// 	$obj->commentStatus = 'DENY';
			// }
			// if($obj->commentStatus == 'DENY') {
			// 	$this->_checkCommentStatusForOldVersion($obj);
			// } 
			// if($obj->allow_trackback!='Y') {
			// 	$obj->allow_trackback = 'N';
			// }
			// if($obj->homepage) {
			// 	$obj->homepage = escape($obj->homepage);
			// 	if(!preg_match('/^[a-z]+:\/\//i',$obj->homepage)) {
			// 		$obj->homepage = 'http://'.$obj->homepage;
			// 	}
			// }
			
			// if($obj->notify_message != 'Y') {
			// 	$obj->notify_message = 'N';
			// }
			if(!isset($obj->email_address)) {
				$obj->email_address = '';
			}
			if(!$isRestore) {
				$obj->ipaddress = $_SERVER['REMOTE_ADDR'];
			}

			// can modify regdate only manager
			// $grant = Context::get('grant');
			// if(!$grant->manager) {
			// 	unset($obj->regdate_dt);
			// }

			// Serialize the $extra_vars, check the extra_vars type, because duplicate serialized avoid
			// if(!is_string($obj->extra_vars)) {
			// 	$obj->extra_vars = serialize($obj->extra_vars);
			// }
			// Remove the columns for automatic saving
			// unset($obj->_saved_doc_srl);
			// unset($obj->_saved_doc_title);
			// unset($obj->_saved_doc_content);
			// unset($obj->_saved_doc_message);
			// Call a trigger (before)
			// $output = ModuleHandler::triggerCall('document.insertDocument', 'before', $obj);
			// if(!$output->toBool()) {
			// 	return $output;
			// }
			// Register it if no given document_srl exists
			if(!$obj->post_id) {
				$obj->post_id = \X2board\Includes\getNextSequence();
			}
			elseif(!$manual_inserted && !$isRestore && !\X2board\Includes\checkUserSequence($obj->post_id)) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_not_permitted', 'x2board') );
			}

			// Set to 0 if the category_id doesn't exist
			if($obj->category_id) {
				$o_category_model = \X2board\Includes\getModel('category');
				$o_category_model->set_board_id(\X2board\Includes\Classes\Context::get('board_id'));
				$a_linear_category = $o_category_model->build_linear_category();
				unset($o_category_model);
				if(count($a_linear_category) > 0 && !$a_linear_category[$obj->category_id]->grant) {
					return new \X2board\Includes\Classes\BaseObject(-1, __('msg_not_permitted', 'x2board') );
				}
				if(count($a_linear_category) > 0 && !$a_linear_category[$obj->category_id]) {
					$obj->category_id = 0;
				}
				unset($a_linear_category);
			}
			// Set the read counts and update order.
			// if(!$obj->readed_count) {
			// 	$obj->readed_count = 0;
			// }
			if($isLatest) {
				$obj->update_order = $obj->list_order = $obj->post_id * -1;
			}
			else {
				$obj->update_order = $obj->list_order;
			}

			if( !isset($obj->password_is_hashed) ) {
				$obj->password_is_hashed = false;
			}
			// Check the status of password hash for manually inserting. Apply hashing for otherwise.
			if($obj->password && !$obj->password_is_hashed) {
				$obj->password = \X2board\Includes\getModel('member')->hash_password($obj->password);
			}
			// Insert member's information only if the member is logged-in and not manually registered.
			$o_logged_info = \X2board\Includes\Classes\Context::get('logged_info');
			if(\X2board\Includes\Classes\Context::get('is_logged') && !$manual_inserted && !$isRestore) {
				$obj->post_author = $o_logged_info->ID;

				// user_id, user_name and nick_name already encoded
				// $obj->user_id = htmlspecialchars_decode($o_logged_info->user_id);
				// $obj->user_name = htmlspecialchars_decode($o_logged_info->user_name);
				$obj->nick_name = htmlspecialchars_decode($o_logged_info->display_name);
				$obj->email_address = $o_logged_info->email_address;
				// $obj->homepage = $o_logged_info->homepage;
			}
			// If the tile is empty, extract string from the contents.
			$obj->title = htmlspecialchars($obj->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
			settype($obj->title, "string");
			if($obj->title == '') {
				$obj->title = cut_str(trim(strip_tags(nl2br($obj->content))),20,'...');
			}
			// If no tile extracted from the contents, leave it untitled.
			if($obj->title == '') {
				$obj->title = __('Untitled', 'x2board'); //'Untitled';
			}
// Remove XE's own tags from the contents.
// $obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);
			// if(Mobile::isFromMobilePhone() && $obj->use_editor != 'Y') {
			if(wp_is_mobile() && $obj->use_editor != 'Y') {
				if($obj->use_html != 'Y') {
					$obj->content = htmlspecialchars($obj->content, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
				}
				$obj->content = nl2br($obj->content);
			}
			// Remove iframe and script if not a top adminisrator in the session.
			if($o_logged_info->is_admin != 'Y') {
				$obj->content = \X2board\Includes\removeHackTag($obj->content);
			}
			// An error appears if both log-in info and user name don't exist.
			if(!$o_logged_info->ID && !$obj->nick_name) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request3', 'x2board') );
			}
			unset($o_logged_info);

			// $obj->lang_code = Context::getLangType();
			// Insert data into the DB
			// if(!$obj->status) {
			// 	$this->_checkDocumentStatusForOldVersion($obj);
			// }
// var_dump($obj);
// 			// $output = executeQuery('document.insertDocument', $obj);

/////////////////////////////
			// $category->setCurrentCategory($this->board_id);
			// 카테고리 지정 권한없는 사람이 새글 작성했는데 강제 지정 카테고리가 설정되었다면
			// if( $pass_enforce_default_category ) {
			// 	$mandatory_cat_id = $category->getDefaultCategory();
			// 	if( $mandatory_cat_id ){
			// 		$this->category_id = $mandatory_cat_id;
			// 	}
			// }

			// encrypt password only if guest
			// if( strlen( $data['password'] ) ) {
			// 	$password_gen = new KBPassword();
			// 	$data['password'] = $password_gen->create_hash($data['password']);
			// 	unset($password_gen);
			// }

			// sanitize
			$a_new_post = array();
			$a_new_post['board_id'] = \X2board\Includes\Classes\Context::get('board_id');
			$a_new_post['post_id'] = intval($obj->post_id); //$n_new_wp_post_id;
			$a_new_post['password'] = $obj->password;
			$a_new_post['post_author'] = intval($obj->post_author);
			$a_new_post['nick_name'] = sanitize_text_field($obj->nick_name);
			$a_new_post['title'] = sanitize_text_field($obj->title); // isset($data['title'])?kboard_safeiframe(kboard_xssfilter($data['title'])):'';
			$a_new_post['content'] = $obj->content; // // sanitize_text_field eliminates all HTML tag  isset($data['content'])?kboard_safeiframe(kboard_xssfilter($data['content'])):'';
			$a_new_post['regdate_dt'] = date('Y-m-d H:i:s', current_time('timestamp'));  // $n_cur_unix_timestamp; // isset($data['date'])?sanitize_key($data['date']):date('YmdHis', current_time('timestamp'));
			$a_new_post['last_update_dt'] = $a_new_post['regdate_dt']; // $n_cur_unix_timestamp; //isset($data['update'])?sanitize_key($data['update']):$data['date'];
			$a_new_post['readed_count'] = 0; //isset($data['view'])?intval($data['view']):0;
			$a_new_post['comment_count'] = 0;//isset($data['comment'])?intval($data['comment']):0;
			$a_new_post['voted_count'] = 0; //isset($data['vote'])?intval($data['vote']):0;
			$a_new_post['category_id'] = intval($obj->category_id); //isset($data['category_id'])?intval($data['category_id']):0;
			$a_new_post['is_notice'] = sanitize_text_field($obj->is_notice); //isset($data['notice'])?sanitize_key($data['notice']):'';
			$a_new_post['update_order'] = intval($obj->update_order);
			$a_new_post['list_order'] = intval($obj->list_order);
			$a_new_post['status'] = sanitize_text_field($obj->status); //isset($data['status'])?sanitize_key($data['status']):'';
			$a_new_post['comment_status'] = sanitize_text_field($obj->comment_status); 
			// add user agent
			$a_new_post['ua'] = wp_is_mobile() ? 'M' : 'P';
			$a_new_post['ipaddress'] = \X2board\Includes\get_remote_ip();

			// 입력할 데이터 필터
			// $data = apply_filters('x2board_insert_data', $a_new_post); //, $this->board_id);
			
// var_Dump($a_new_post);
// exit;
			// if(!$obj->post_id) {
			// 	$obj->post_id = getNextSequence();
			// }
			// elseif(!$manual_inserted && !$isRestore && !checkUserSequence($obj->post_id)) {
			// return new \X2board\Includes\Classes\BaseObject(-1, __('msg_not_permitted', 'x2board') );
			// }

			// if(!$data['member_display']){
			// 	$data['member_display'] = __('Anonymous', 'x2board');
			// }

			// $status_list = kboard_content_status_list();
			// if(!in_array($data['status'], array_keys($status_list))){
			// 	$data['status'] = '';
			// }

			// $data['title'] = $this->titleStripTags($data['title']);
			// $data['title'] = $this->encodeEmoji($data['title']);
			// $data['content'] = $this->encodeEmoji($data['content']);

			// 불필요한 데이터 필터링
			// $data = kboard_array_filter($data, array('board_id', 'parent_uid', 'member_uid', 'member_display', 'title', 
			// 									'content', 'date', 'update', 'view', 'comment', 'vote', 
			// 									'category_id', 'secret', 'notice', 'allow_comment', 'search', 
			// 									'status', 'password', 'ipaddress', 'ua'));
// var_dump($a_new_post_param);
			// if(!$a_new_post_param['board_id'] || !$a_new_post_param['title']){
			// 	return new \X2board\Includes\Classes\BaseObject(-1, 'msg_invalid_request4');
			// }
			$a_insert_key = array();
			$a_insert_val = array();
			foreach($a_new_post as $key=>$value){
				// $this->{$key} = $value;
				$value = esc_sql($value);
				$a_insert_key[] = "`$key`";
				$a_insert_val[] = "'$value'";
			}
// var_dump($a_new_post);
// exit;
			// $board = $this->getBoard();
			// $board_total = $board->getTotal();
			// $board_list_total = $board->getListTotal();

			// if($this->status != 'trash'){
			// 	$board->meta->total = $board_total + 1;
			// 	$board->meta->list_total = $board_list_total + 1;
			// }
			// else{
			// 	$board->meta->total = $board_total + 1;
			// }
			global $wpdb;
			$query = "INSERT LOW_PRIORITY INTO `{$wpdb->prefix}x2b_posts` (".implode(',', $a_insert_key).") VALUES (".implode(',', $a_insert_val).")";
			if ($wpdb->query($query) === FALSE) {
				return new \X2board\Includes\Classes\BaseObject(-1, $wpdb->last_error);
			}
			
			unset($a_insert_key);
			unset($a_insert_data);

			// Insert all extended user defined variables if the post successfully inserted.
			$o_post_model = \X2board\Includes\getModel('post');
			$a_user_define_extended_fields = $o_post_model->get_user_define_extended_fields($a_new_post['board_id']);
			unset($o_post_model);

			// do not store default field into tbl::x2b_user_define_vars
			if(count($a_user_define_extended_fields)) {
				foreach($a_user_define_extended_fields as $idx => $o_user_define_item) {
					$o_user_input_value = \X2board\Includes\Classes\Context::get($o_user_define_item->eid);
					
					// if( !is_null($o_user_input_value) ) {
					// 	if(is_array($o_user_input_value))
					// 		$value = implode('|@|', sanitize_text_field($o_user_input_value));
					// 	else
					// 		$value = sanitize_text_field(trim($o_user_input_value));
					// }

					// if(isset($obj->{'extra_vars'.$idx})) {
					// 	$tmp = $obj->{'extra_vars'.$idx};
					// 	if(is_array($tmp))
					// 		$value = implode('|@|', $tmp);
					// 	else
					// 		$value = trim($tmp);
					// }
					// else if(isset($obj->{$o_user_define_item->name})) {
					// 	$value = trim($obj->{$o_user_define_item->name});
					// }
					if($o_user_input_value == NULL) {
						continue;
					}
// var_dump($o_user_input_value);					
					$this->_insert_user_defined_value($a_new_post['board_id'], $a_new_post['post_id'], $idx, $o_user_input_value, $o_user_define_item->eid);
				}
			}
// exit;		
			
			// Update the category if the category_id exists.
			if($obj->category_id) {
				// $this->updateCategoryCount($obj->board_id, $obj->category_id);
				$o_category_controller = \X2board\Includes\getController('category');
				$o_category_controller->set_board_id($obj->board_id);
				$o_category_controller->update_category_count($obj->category_id);
				unset($o_category_controller);
			}
// var_dump($obj->category_id);
// exit;
			if(!$manual_inserted) {
				$this->_add_grant($a_new_post['post_id']);
			}

			$o_file_controller = \X2board\Includes\getController('file');
			$o_file_controller->set_files_valid($a_new_post['post_id']);
			unset($o_file_controller);
			$this->update_uploaded_count(array($a_new_post['post_id']));

			// $n_new_wp_post_id = $this->_insert_wp_post($a_new_post);
			// $a_new_post['post_id'] = $n_new_wp_post_id;
			if( $this->_insert_wp_post($a_new_post) === false ) {
				unset($a_new_post);
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_wp_post_registration_failed', 'x2board') );
			}

			$o_rst = new \X2board\Includes\Classes\BaseObject();
			$o_rst->add('post_id',$a_new_post['post_id']);
			$o_rst->add('category_id',$obj->category_id);
			unset($a_new_post);
// var_dump('insert_post finished without redirection');
// exit;
			return $o_rst;
		}

		/**
		 * Insert extra vaiable to the documents table
		 * @param int $n_board_id
		 * @param int $n_post_id
		 * @param int $var_idx
		 * @param mixed $value
		 * @param int $eid
		 * @param string $lang_code
		 * @return BaseObject|void
		 */
		// function insertDocumentExtraVar($module_srl, $document_srl, $var_idx, $value, $eid = null, $lang_code = '')
		private function _insert_user_defined_value($n_board_id, $n_post_id, $var_idx, $o_user_input_value, $eid = null, $lang_code = '') {
// var_dump($n_board_id);
// var_dump($n_post_id);
// var_dump($var_idx);
// var_dump($o_user_input_value);
// var_dump($eid);
			if(!$n_board_id || !$n_post_id || !$var_idx || !isset($o_user_input_value)) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request1', 'x2board') );
			}
			// if(!$lang_code) $lang_code = Context::getLangType();
		
			if(is_array($o_user_input_value))
				$value = implode('|@|', sanitize_text_field($o_user_input_value));
			else
				$value = sanitize_text_field(trim($o_user_input_value));

			// $obj = new stdClass;
			// $obj->module_srl = $board_id;
			// $obj->document_srl = $document_srl;
			// $obj->var_idx = $var_idx;
			// $obj->value = $value;
			// $obj->lang_code = ''; //$lang_code;
			// $obj->eid = $eid;
			// executeQuery('document.insertDocumentExtraVar', $obj);
			$a_new_field = array();
			$a_new_field['board_id'] = $n_board_id;
			$a_new_field['post_id'] = $n_post_id;
			$a_new_field['var_idx'] = $var_idx;
			$a_new_field['value'] = $value;
			$a_new_field['lang_code'] = '';
			$a_new_field['eid'] = $eid;
			global $wpdb;
			$result = $wpdb->insert("{$wpdb->prefix}x2b_user_define_vars", $a_new_field);
			if( $result < 0 || $result === false ){
				unset($a_new_field);
				unset($result);
				return new \X2board\Includes\Classes\BaseObject(-1, $wpdb->last_error );
			}
			unset($result);
		}

		/**
		 * Update read counts of the post
		 * @param postItem $post
		 * @return bool|void
		 */
		public function update_readed_count(&$o_post) {  //   &$oDocument) {
			// Pass if Crawler access
			if(\X2board\Includes\is_crawler()) {
				return false;
			}
			
			$n_post_id = $o_post->post_id;
			// Pass if read count is increaded on the session information
			if(isset($_SESSION['x2b_readed_post'][$n_post_id])) {
				return false;
			}
			
			// Pass if the author's IP address is as same as visitor's.
			if($o_post->get('ipaddress') == \X2board\Includes\get_remote_ip() ) {  // $_SERVER['REMOTE_ADDR']) {
				$_SESSION['x2b_readed_post'][$n_post_id] = true;
				return false;
			}
			// Pass ater registering sesscion if the author is a member and has same information as the currently logged-in user.
			$o_logged_info = \X2board\Includes\Classes\Context::get('logged_info');
			$n_post_author = $o_post->get('post_author');
			if($n_post_author && $o_logged_info->ID == $n_post_author) {
				$_SESSION['x2b_readed_post'][$n_post_id] = true;
				return false;
			}
			unset($o_logged_info);

			// Update read counts
			// $args = new stdClass;
			// $args->document_srl = $n_post_id;
			// $output = executeQuery('document.updateReadedCount', $args);
			global $wpdb;
			$query = "UPDATE `{$wpdb->prefix}x2b_posts` SET `readed_count`=`readed_count`+1 WHERE `post_id`='".esc_sql(intval($n_post_id))."'";
			if ($wpdb->query($query) === FALSE) {
				return false;
			} 

			// Register session
			if(!isset($_SESSION['x2b_banned_post'][$n_post_id])) {
				$_SESSION['x2b_readed_post'][$n_post_id] = true;
			}
			return TRUE;
		}

		/**
		 * Update the post
		 * @param object $source_obj
		 * @param object $obj
		 * @param bool $manual_updated
		 * @return object
		 */
		// function updateDocument($source_obj, $obj, $manual_updated = FALSE)
		public function update_post($o_old_post, $o_new_obj, $manual_updated = FALSE) {
			// if(!$manual_updated && !checkCSRF()) {
			// 	return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request', 'x2board') );
			// }
			if(!$manual_updated) {  // check WP nonce if a guest update a old post
				$wp_verify_nonce = \X2board\Includes\Classes\Context::get('x2b_'.X2B_CMD_PROC_MODIFY_POST.'_nonce');
				if( is_null( $wp_verify_nonce ) ){
					return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request1', 'x2board') );
				}
				if( !wp_verify_nonce($wp_verify_nonce, 'x2b_'.X2B_CMD_PROC_MODIFY_POST) ){
					return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request2', 'x2board') );
				}
			}

			if(!$o_old_post->post_id || !$o_new_obj->post_id) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request', 'x2board') );
			}
			if(!$o_new_obj->status && $o_new_obj->is_secret == 'Y') {
				$o_new_obj->status = 'SECRET';
			}
			if(!$o_new_obj->status) {
				$o_new_obj->status = 'PUBLIC';
			}
			if(isset($o_new_obj->is_secret)) {  // is_secret is not a DB field
				unset($o_new_obj->is_secret);
			}
			
			// Call a trigger (before)
			// $output = ModuleHandler::triggerCall('document.updateDocument', 'before', $o_new_obj);
			// if(!$output->toBool()) return $output;

			// begin transaction
			// $oDB = &DB::getInstance();
			// $oDB->begin();

			// $oModuleModel = getModel('module');
			// if(!$o_new_obj->module_srl) $o_new_obj->module_srl = $o_old_post->get('module_srl');
			// $module_srl = $o_new_obj->module_srl;
			$document_config = null; // $oModuleModel->getModulePartConfig('document', $module_srl);
			if(!$document_config) {
				$document_config = new \stdClass();
			}
			if(!isset($document_config->use_history)) {
				$document_config->use_history = 'N';
			}
			$bUseHistory = $document_config->use_history == 'Y' || $document_config->use_history == 'Trace';

			if($bUseHistory) {
				$args = new \stdClass;
				$args->history_srl = \X2board\Includes\getNextSequence();
				$args->document_srl = $o_new_obj->document_srl;
				$args->module_srl = $module_srl;
				if($document_config->use_history == 'Y') {
					$args->content = $o_old_post->get('content');
				}
				$args->nick_name = $o_old_post->get('nick_name');
				$args->member_srl = $o_old_post->get('member_srl');
				$args->regdate_dt = $o_old_post->get('last_update_dt');
				$args->ipaddress = \X2board\Includes\get_remote_ip(); // $_SERVER['REMOTE_ADDR'];
				$output = executeQuery("document.insertHistory", $args);
			}
			else {
				$o_new_obj->ipaddress = $o_old_post->get('ipaddress');
			}
			// List variables
			if(!$o_new_obj->comment_status) {
				$o_new_obj->comment_status = 'DENY';
			}
			// if(!$o_new_obj->commentStatus) {
			// 	$o_new_obj->commentStatus = 'DENY';
			// }
			// if($o_new_obj->commentStatus == 'DENY') $this->_checkCommentStatusForOldVersion($o_new_obj);
			// if($o_new_obj->allow_trackback!='Y') $o_new_obj->allow_trackback = 'N';
			// if($o_new_obj->homepage)
			// {
			// 	$o_new_obj->homepage = escape($o_new_obj->homepage);
			// 	if(!preg_match('/^[a-z]+:\/\//i',$o_new_obj->homepage))
			// 	{
			// 		$o_new_obj->homepage = 'http://'.$o_new_obj->homepage;
			// 	}
			// }
			
			// if($o_new_obj->notify_message != 'Y') $o_new_obj->notify_message = 'N';
			
			// can modify regdate only manager
			$grant = \X2board\Includes\Classes\Context::get('grant');
			if(!$grant->manager) {
				unset($o_new_obj->regdate_dt);
			}
			
			// Serialize the $extra_vars
			// if(!is_string($o_new_obj->extra_vars)) $o_new_obj->extra_vars = serialize($o_new_obj->extra_vars);
			// Remove the columns for automatic saving
			unset($o_new_obj->_saved_doc_srl);
			unset($o_new_obj->_saved_doc_title);
			unset($o_new_obj->_saved_doc_content);
			unset($o_new_obj->_saved_doc_message);

			$o_post_model = \X2board\Includes\getModel('post');
// var_dump($o_old_post->get('category_id'));
// var_dump(intval($o_new_obj->category_id));
			// Set the category_srl to 0 if the changed category is not exsiting.
			if(intval($o_old_post->get('category_id'))!=intval($o_new_obj->category_id)) {
				$o_category_model = \X2board\Includes\getModel('category');
				$o_category_model->set_board_id(\X2board\Includes\Classes\Context::get('board_id'));
				$a_linear_category = $o_category_model->build_linear_category();
				unset($o_category_model);
				
				if(!$a_linear_category[$o_new_obj->category_id] || !$a_linear_category[$o_new_obj->category_id]->grant) {
					$o_new_obj->category_id = 0;
				}
				unset($a_linear_category);
			}
			// Change the update order
			$o_new_obj->update_order = \X2board\Includes\getNextSequence() * -1;
			// Hash the password if it exists
			if($o_new_obj->password) {
				$o_new_obj->password = \X2board\Includes\getModel('member')->hash_password($o_new_obj->password);
			}

			$o_logged_info = \X2board\Includes\Classes\Context::get('logged_info');
			// If an author is identical to the modifier or history is used, use the logged-in user's information.
			if(\X2board\Includes\Classes\Context::get('is_logged') && !$manual_updated)  {
				if($o_old_post->get('post_author')==$o_logged_info->ID) {
					$o_new_obj->post_author = $o_logged_info->ID;
					// $o_new_obj->user_name = htmlspecialchars_decode($logged_info->user_name);
					$o_new_obj->nick_name = htmlspecialchars_decode($o_logged_info->nick_name);
					$o_new_obj->email_address = $o_logged_info->email_address;
					// $o_new_obj->homepage = $logged_info->homepage;
				}
			}

			// For the post written by logged-in user however no nick_name exists
			if($o_old_post->get('post_author') && !$o_new_obj->nick_name) {
				$o_new_obj->post_author = $o_old_post->get('post_author');
				// $o_new_obj->user_name = $o_old_post->get('user_name');
				$o_new_obj->nick_name = $o_old_post->get('nick_name');
				$o_new_obj->email_address = $o_old_post->get('email_address');
				// $o_new_obj->homepage = $o_old_post->get('homepage');
			}
			// If the tile is empty, extract string from the contents.
			$o_new_obj->title = htmlspecialchars($o_new_obj->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
			settype($o_new_obj->title, "string");
			if($o_new_obj->title == '') {
				$o_new_obj->title = cut_str(strip_tags($o_new_obj->content),20,'...');
			}
			// If no tile extracted from the contents, leave it untitled.
			if($o_new_obj->title == '') {
				$o_new_obj->title = __('Untitled', 'x2board'); //'Untitled';
			}
			// Remove XE's own tags from the contents.
			// $o_new_obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $o_new_obj->content);
			if( !isset($o_new_obj->use_editor) ) {
				$o_new_obj->use_editor = 'N';
				$o_new_obj->use_html = 'N';
			}
			if(wp_is_mobile() && $o_new_obj->use_editor != 'Y') {
				if($o_new_obj->use_html != 'Y') {
					$o_new_obj->content = htmlspecialchars($o_new_obj->content, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
				}
				$o_new_obj->content = nl2br($o_new_obj->content);
			}
			// Remove iframe and script if not a top adminisrator in the session.
			if($o_logged_info->is_admin != 'Y') {
				$o_new_obj->content = \X2board\Includes\removeHackTag($o_new_obj->content);
			}
			// Change not extra vars but language code of the original document if document's lang_code is different from author's setting.
			// if($o_old_post->get('lang_code') != Context::getLangType())
			// {
			// 	// Change not extra vars but language code of the original document if document's lang_code doesn't exist.
			// 	if(!$o_old_post->get('lang_code'))
			// 	{
			// 		$lang_code_args->document_srl = $o_old_post->get('document_srl');
			// 		$lang_code_args->lang_code = Context::getLangType();
			// 		$output = executeQuery('document.updateDocumentsLangCode', $lang_code_args);
			// 	}
			// 	else
			// 	{
			// 		$extra_content = new stdClass;
			// 		$extra_content->title = $o_new_obj->title;
			// 		$extra_content->content = $o_new_obj->content;

			// 		$document_args = new stdClass;
			// 		$document_args->document_srl = $o_old_post->get('document_srl');
			// 		$document_output = executeQuery('document.getDocument', $document_args);
			// 		$o_new_obj->title = $document_output->data->title;
			// 		$o_new_obj->content = $document_output->data->content;
			// 	}
			// }
			// if temporary document, regdate_dt is now setting
			if($o_old_post->get('status') == $this->get_config_status('temp')) {
				$o_new_obj->regdate_dt = date('Y-m-d H:i:s', current_time('timestamp')); //date('YmdHis');
			}

			// Insert data into the DB
			// $output = executeQuery('document.updateDocument', $o_new_obj);
			// if(!$output->toBool())
			// {
			// 	$oDB->rollback();
			// 	return $output;
			// }

			// sanitize other user input fields, $o_new_obj->content has been sanitized enough
			$a_new_post = array();
			$a_ignore_key = array('use_editor', 'content', 'use_html');
			foreach($o_new_obj as $s_key => $s_val ) {
				if( !in_array($s_key, $a_ignore_key) && isset($s_val) ) {
					$a_new_post[$s_key] = esc_sql($s_val);
				}
			}
			$a_new_post['content'] = $o_new_obj->content;  // esc_sql() converts new line to \r\n again and again

// var_dump($a_new_post);
			global $wpdb;
			$result = $wpdb->update ( "{$wpdb->prefix}x2b_posts", $a_new_post, array ( 'post_id' => esc_sql(intval($a_new_post['post_id'] )) ) );
			if( $result < 0 || $result === false ){
// var_dump($wpdb->last_error);					
				return new \X2board\Includes\Classes\BaseObject(-1, $wpdb->last_error );
			}
			
			if( $this->_update_wp_post($a_new_post) === false ) {
				unset($a_new_post);
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_wp_post_update_failed', 'x2board') );
			}
			unset($a_ignore_key);
// exit;
			// Remove all extended user defined variables
			$this->_delete_extended_user_defined_vars_all($a_new_post['board_id'], $a_new_post['post_id']); //, null, //Context::getLangType()

			// store all extended user defined variables 
			$o_post_model = \X2board\Includes\getModel('post');
			$a_user_define_extended_fields = $o_post_model->get_user_define_extended_fields($a_new_post['board_id']);
			unset($o_post_model);

			// do not store default field into tbl::x2b_user_define_vars
			if(count($a_user_define_extended_fields)) {
				foreach($a_user_define_extended_fields as $idx => $o_user_define_item) {
					$o_user_input_value = \X2board\Includes\Classes\Context::get($o_user_define_item->eid);
					if($o_user_input_value == NULL) {
						continue;
					}
// var_dump($o_user_input_value);					
					$this->_insert_user_defined_value($a_new_post['board_id'], $a_new_post['post_id'], $idx, $o_user_input_value, $o_user_define_item->eid);
				}
			}
			$o_file_controller = \X2board\Includes\getController('file');
			$o_file_controller->set_files_valid($a_new_post['post_id']);
			unset($o_file_controller);
			$this->update_uploaded_count(array($a_new_post['post_id']));
			unset($a_new_post);
// exit;		

			// if(false) //Context::get('act')!='procFileDelete')
			// {
			// 	$this->deleteDocumentExtraVars($o_old_post->get('module_srl'), $o_new_obj->document_srl, null, Context::getLangType());
			// 	// Insert extra variables if the document successfully inserted.
			// 	$extra_keys = $o_post_model->getExtraKeys($o_new_obj->module_srl);
			// 	if(count($extra_keys))
			// 	{
			// 		foreach($extra_keys as $idx => $extra_item)
			// 		{
			// 			$value = NULL;
			// 			if(isset($o_new_obj->{'extra_vars'.$idx}))
			// 			{
			// 				$tmp = $o_new_obj->{'extra_vars'.$idx};
			// 				if(is_array($tmp))
			// 					$value = implode('|@|', $tmp);
			// 				else
			// 					$value = trim($tmp);
			// 			}
			// 			else if(isset($o_new_obj->{$extra_item->name})) $value = trim($o_new_obj->{$extra_item->name});
			// 			if($value == NULL) continue;
			// 			$this->insertDocumentExtraVar($o_new_obj->module_srl, $o_new_obj->document_srl, $idx, $value, $extra_item->eid);
			// 		}
			// 	}
			// 	// Inert extra vars for multi-language support of title and contents.
			// 	if($extra_content->title) {
			// 		$this->insertDocumentExtraVar($o_new_obj->module_srl, $o_new_obj->document_srl, -1, $extra_content->title, 'title_'.Context::getLangType());
			// 	}
			// 	if($extra_content->content) {
			// 		$this->insertDocumentExtraVar($o_new_obj->module_srl, $o_new_obj->document_srl, -2, $extra_content->content, 'content_'.Context::getLangType());
			// 	}
			// }
			// Update the category if the category_id exists.
			if($o_old_post->get('category_id') != $o_new_obj->category_id || $o_old_post->get('board_id') == $o_logged_info->ID) {
				$o_category_controller = \X2board\Includes\getController('category');
				$o_category_controller->set_board_id($o_new_obj->board_id);
				if($o_old_post->get('category_id') != $o_new_obj->category_id) {  // decrease post count from old category
					$o_category_controller->update_category_count($o_old_post->get('category_id'));
				} 
				if($o_new_obj->category_id) {  // increase post count from old category
					$o_category_controller->update_category_count($o_new_obj->category_id);
				}
				unset($o_category_controller);
			}
			unset($o_logged_info);
			unset($o_old_post);

			// commit
			// $oDB->commit();

			// Remove the thumbnail file
			// FileHandler::removeDir(sprintf('files/thumbnails/%s',getNumberingPath($o_new_obj->document_srl, 3)));
			$s_post_thumbnail_dir = wp_get_upload_dir()['basedir'].DIRECTORY_SEPARATOR.X2B_DOMAIN.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.\X2board\Includes\getNumberingPath($o_new_obj->post_id, 3);
			$this->_o_wp_filesystem->delete($s_post_thumbnail_dir);

			//remove from cache
			$o_cache_handler = \X2board\Includes\Classes\CacheHandler::getInstance('object');
			if($o_cache_handler->isSupport()) {
				//remove post item from cache
				$cache_key = 'post_item:'. \X2board\Includes\getNumberingPath($o_new_obj->post_id) . $o_new_obj->post_id;
				$o_cache_handler->delete($cache_key);
			}
			unset($o_cache_handler);
			// $oCacheHandler = CacheHandler::getInstance('object');
			// if($oCacheHandler->isSupport())
			// {
			// 	//remove document item from cache
			// 	$cache_key = 'post_item:'. getNumberingPath($o_new_obj->document_srl) . $o_new_obj->document_srl;
			// 	$oCacheHandler->delete($cache_key);
			// }
			$o_rst = new \X2board\Includes\Classes\BaseObject();
			$o_rst->add('post_id',$o_new_obj->post_id);
			$o_rst->add('category_id',$o_new_obj->category_id);
			unset($o_new_obj);
// var_dump('insert_post finished without redirection');
// exit;
			return $o_rst;
		}

		// public function updateUploaedCount($documentSrlList)
		public function update_uploaded_count($a_post_id) {
			if(is_array($a_post_id)) {
				global $wpdb;
				$o_file_model = \X2board\Includes\getModel('file');
				$a_post_id = array_unique($a_post_id);
				foreach($a_post_id AS $_ => $n_post_id) {
					$fileCount = $o_file_model->get_files_count($n_post_id);
					// $args = new stdClass();
					// $args->document_srl = $documentSrl;
					// $args->uploaded_count = $fileCount;
					// executeQuery('document.updateUploadedCount', $args);
					$result = $wpdb->update( "{$wpdb->prefix}x2b_posts", 
											 array( 'uploaded_count' => $fileCount), 
											 array ( 'post_id' => esc_sql(intval($n_post_id )) ) );
					if( $result < 0 || $result === false ){
						return new \X2board\Includes\Classes\BaseObject(-1, $wpdb->last_error );
					}
				}
				unset($o_file_model);
			}
		}

		/**
		 * Deleting post
		 * @param int $document_srl
		 * @param bool $is_admin
		 * @param bool $isEmptyTrash
		 * @param postItem $o_post
		 * @return object
		 */
		public function delete_post($n_post_id, $is_admin = false, $isEmptyTrash = false, $o_post = null) {
			// Call a trigger (before)
			// $trigger_obj = new stdClass();
			// $trigger_obj->document_srl = $n_post_id;
			// $output = ModuleHandler::triggerCall('document.deleteDocument', 'before', $trigger_obj);
			// if(!$output->toBool()) return $output;

			// begin transaction
			// $oDB = &DB::getInstance();
			// $oDB->begin();

			if(!$isEmptyTrash) {
				// get model object of the document
				$o_post_model = \X2board\Includes\getModel('post');
				// Check if the documnet exists
				$o_post = $o_post_model->get_post($n_post_id, $is_admin);
				unset($o_post_model);
			}
			else if($isEmptyTrash && $o_post == null) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('post is not exists', 'x2board') );
			}

			if(!$o_post->is_exists() || $o_post->post_id != $n_post_id) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_post', 'x2board') );
			}
			// Check if a permossion is granted
			if(!$o_post->is_granted()) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_not_permitted', 'x2board') );
			}

			//if empty trash, post already deleted, therefore post not delete
			if(!$isEmptyTrash) { // Delete the post
				global $wpdb;
				$result = $wpdb->delete(
					$wpdb->prefix . 'x2b_posts',
					array('post_id'  => $n_post_id ),
					array('%d'), // make sure the id format
				);
				if( $result < 0 || $result === false ){
// var_dump($wpdb->last_error);
					wp_die($wpdb->last_error );
				}				
			}
// var_dump($o_post->get('board_id'));
			
			$this->_delete_wp_post($n_post_id);

			// $this->deleteDocumentAliasByDocument($n_post_id);

			$this->_delete_post_history(null, $n_post_id, null);
			// Update category information if the category_id exists.
			$n_board_id = $o_post->get('board_id');
			$n_category_id = $o_post->get('category_id');
			if($n_category_id) {
				// $this->updateCategoryCount($oDocument->get('module_srl'),$oDocument->get('category_srl'));
				$o_category_controller = \X2board\Includes\getController('category');
				$o_category_controller->set_board_id($n_board_id);
				$o_category_controller->update_category_count($n_category_id);
				unset($o_category_controller);
			}

			// Delete a declared list
			// executeQuery('document.deleteDeclared', $args);

			// Delete extended user defined variables
			// $this->deleteDocumentExtraVars($o_post->get('board_id'), $o_post->post_id);
			$this->_delete_extended_user_defined_vars_all($n_board_id, $n_post_id);

			// Call a trigger (after)
			$o_comment_controller = \X2board\Includes\getController('comment');
			$o_rst = $o_comment_controller->trigger_after_delete_post_comments($n_post_id);
			if(!$o_rst->toBool()) {
				wp_die('weird error occured in \includes\modules\comment\comment.controller.php::trigger_after_delete_post_comments()');
			}
			unset($o_comment_controller);
			// if($output->toBool())
			// {
			// 	$trigger_obj = $oDocument->getObjectVars();
			// 	$trigger_output = ModuleHandler::triggerCall('document.deleteDocument', 'after', $trigger_obj);
			// 	if(!$trigger_output->toBool())
			// 	{
			// 		$oDB->rollback();
			// 		return $trigger_output;
			// 	}
			// }
			// declared post, log delete
			$this->_delete_declared_posts($n_board_id, $n_post_id);
			$this->_delete_post_readed_log($n_board_id, $n_post_id);
			$this->_delete_post_voted_log($n_board_id, $n_post_id);

			// Remove the thumbnail file
			// FileHandler::removeDir(sprintf('files/thumbnails/%s', \X2board\Includes\getNumberingPath($n_post_id, 3)));
			$s_post_thumbnail_dir = wp_get_upload_dir()['basedir'].DIRECTORY_SEPARATOR.X2B_DOMAIN.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.\X2board\Includes\getNumberingPath($n_post_id, 3);
			$this->_o_wp_filesystem->delete($s_post_thumbnail_dir);

			// commit
			// $oDB->commit();

			//remove from cache
			$o_cache_handler = \X2board\Includes\Classes\CacheHandler::getInstance('object');
			if($o_cache_handler->isSupport()) {
				$cache_key = 'post_item:'. \X2board\Includes\getNumberingPath($n_post_id) . $n_post_id;
				$o_cache_handler->delete($cache_key);
			}
			unset($o_cache_handler);
			// $oCacheHandler = CacheHandler::getInstance('object');
			// if($oCacheHandler->isSupport()) {
			// 	$cache_key = 'post_item:'. getNumberingPath($n_post_id) . $n_post_id;
			// 	$oCacheHandler->delete($cache_key);
			// }
			// unset($oCacheHandler);
			
			return new \X2board\Includes\Classes\BaseObject();
		}

		/**
		 * Delete post history
		 * @param int $history_srl
		 * @param int $n_post_id
		 * @param int $n_board_id
		 * @return void
		 */
		// function deleteDocumentHistory($history_srl, $document_srl, $module_srl)
		private function _delete_post_history($n_history_id, $n_post_id, $n_board_id) {
			// $args = new stdClass();
			// $args->history_srl = $history_srl;
			// $args->module_srl = $module_srl;
			// $args->document_srl = $document_srl;
			// if(!$args->history_srl && !$args->module_srl && !$args->document_srl) return;
			// executeQuery("document.deleteHistory", $args);
			return;
		}

		/**
		 * Delete declared post, log
		 * @param string $post_ids (ex: 1, 2,56, 88)
		 * @return void
		 */
		// function _deleteDeclaredDocuments($documentSrls)
		private function _delete_declared_posts($post_ids) {
			error_log(print_r('should activate _delete_declared_posts()', true));
			return;
			// executeQuery('document.deleteDeclaredDocuments', $documentSrls);
			// executeQuery('document.deleteDocumentDeclaredLog', $documentSrls);
		}

		/**
		 * Delete readed log
		 * @param string $post_ids (ex: 1, 2,56, 88)
		 * @return void
		 */
		// function _deleteDocumentReadedLog($documentSrls)
		private function _delete_post_readed_log($post_ids) {
			return;
			// executeQuery('document.deleteDocumentReadedLog', $documentSrls);
		}

		/**
		 * Delete voted log
		 * @param string $post_ids (ex: 1, 2,56, 88)
		 * @return void
		 */
		// function _deleteDocumentVotedLog($documentSrls)
		private function _delete_post_voted_log($post_ids) {
			return;
			// executeQuery('document.deleteDocumentVotedLog', $documentSrls);
		}

		/**
		 * x2b post를 WP post에 복제해야 하는가?
		 * @param int $a_post_param
		 */
		private function _is_post_public( $s_post_status ) {
			$o_module_info = \X2board\Includes\Classes\Context::get('current_module_info');;
			if( $o_module_info->grant_list == X2B_ALL_USERS ) { // || $o_module_info->grant_view == X2B_ALL_USERS ) {
				$o_post_class = \X2board\Includes\getClass('post');
				$s_post_status_public = $o_post_class->get_config_status('public');
				unset($o_post_class);
				if( $s_post_status == $s_post_status_public ) {
					unset($o_module_info);
					return true;
				}
			}
			unset($o_module_info);
			return false;
		}

		/**
		 * x2b post를 WP post에 복제함
		 * @param int $a_post_param
		 */
		private function _insert_wp_post($a_post_param) {
			if( $this->_is_post_public($a_post_param['status']) ) {
				$s_title = strip_tags( $a_post_param['title'] );
				$s_post_content = strip_tags( $a_post_param['content'] );
				$s_post_status = 'publish';
			}
			else {
				$s_title = '';
				$s_post_content = '';
				$s_post_status = 'private';
			}
			
			$a_params = array(
				'post_author'   => $a_post_param['post_author'],
				'post_title'    => $s_title, //$a_post_param['title'],
				'post_content'  => $s_post_content, // ( $a_post_param['status'] == 'SECRET' || $a_post_param['status'] == '2' ) ? '' : strip_tags( $a_post_param['content'] ), 
				'post_status'   => $s_post_status, // 'publish',
				'comment_status'=> 'closed',
				'ping_status'   => 'closed',
				'post_name'     => $a_post_param['post_id'],
				'post_parent'   => $a_post_param['board_id'],
				'post_type'     => X2B_DOMAIN,
				'post_date'     => $a_post_param['regdate_dt']
			);
			$result = wp_insert_post($a_params, true);
			unset($a_params);
			if( is_wp_error( $result ) ) {
				wp_die( $result->get_error_message() );
				return false;
			}
			// add_action('kboard_document_insert', array($this, '_setPostThumbnail'), 10, 4);
			return $result; // new WP post ID
		}

		/**
		 * x2b post를 WP post에 수정함
		 * @param int $a_post_param
		 */
		private function _update_wp_post($a_post_param){
			$n_wp_post_id = \X2board\Includes\get_wp_post_id_by_x2b_post_id( $a_post_param['post_id'] );
			$o_post = get_post( intval($n_wp_post_id) );
			$o_post->post_author = $a_post_param['post_author'];

			if( $this->_is_post_public($a_post_param['status']) ) {
				$s_title = strip_tags( $a_post_param['title'] );
				$s_post_content = strip_tags( $a_post_param['content'] );
				$s_post_status = 'publish';
			}
			else {
				$s_title = '';
				$s_post_content = '';
				$s_post_status = 'private';
			}

			$o_post->post_title = $s_title; //$a_post_param['title'];
			$o_post->post_content = $s_post_content; // ( $a_post_param['status'] == 'SECRET' || $a_post_param['status'] == '2' ) ? '' : strip_tags( $a_post_param['content'] );
			$o_post->post_status = $s_post_status; // 'private';
			$result = wp_update_post($o_post);
			unset($o_post);
			if( is_wp_error( $result ) ) {
				wp_die( $result->get_error_message() );
				return false;
			}
			// add_action('kboard_document_insert', array($this, '_setPostThumbnail'), 10, 4);
			return $result; // old WP post ID
		}

		/**
		 * delete from WP post 
		 * @param int $n_post_id
		 */
		private function _delete_wp_post($n_x2b_post_id) {
			$n_wp_post_id = \X2board\Includes\get_wp_post_id_by_x2b_post_id( $n_x2b_post_id );
			if(has_post_thumbnail($n_wp_post_id)) {
				$n_attachment_id = get_post_thumbnail_id($n_wp_post_id);
				wp_delete_attachment($n_attachment_id, true);
				delete_post_thumbnail($n_wp_post_id);
			}
			wp_delete_post($n_wp_post_id);
		}

		/**
		 * Increase the number of comments in the post
		 * Update modified date, modifier, and order with increasing comment count
		 * @param int $n_post_id
		 * @param int $comment_count
		 * @param string $s_last_updater
		 * @param bool $comment_inserted
		 * @return object
		 */
		// function updateCommentCount($document_srl, $comment_count, $last_updater, $comment_inserted = false)
		public function update_comment_count($n_post_id, $comment_count, $s_last_updater, $comment_inserted = false) {
			// $args = new stdClass();
			// $args->document_srl = $document_srl;
			// $args->comment_count = $comment_count;
			$a_param = array();
			if($comment_inserted) {
				$a_param['update_order'] = -1*\X2board\Includes\getNextSequence();
				$a_param['last_updater'] = $s_last_updater;

				$o_cache_handler = \X2board\Includes\Classes\CacheHandler::getInstance('object');
				if($o_cache_handler->isSupport()) {
					//remove post item from cache
					$cache_key = 'post_item:'. \X2board\Includes\getNumberingPath($n_post_id) . $n_post_id;
					$o_cache_handler->delete($cache_key);
				}
				unset($o_cache_handler);
				// $oCacheHandler = CacheHandler::getInstance('object');
				// if($oCacheHandler->isSupport())
				// {
				// 	//remove document item from cache
				// 	$cache_key = 'post_item:'. getNumberingPath($document_srl) . $document_srl;
				// 	$oCacheHandler->delete($cache_key);
				// }
			}
// var_dump($comment_count);
			$a_param['comment_count'] = $comment_count;
			$a_param['last_update_dt'] = date('Y-m-d H:i:s', current_time('timestamp'));

			$a_set = array();
			foreach($a_param as $key=>$value) {
				$a_set[] = "`$key` = '$value'";
			}
			unset($a_param);

			// increase comment_count
			global $wpdb;
			$query = "UPDATE `{$wpdb->prefix}x2b_posts` SET ".implode(',', $a_set)." WHERE `post_id` = $n_post_id";
			unset($a_set);
			if ($wpdb->query($query) === FALSE) {
				return new \X2board\Includes\Classes\BaseObject(-1, $wpdb->last_error);
			} 
			// $wpdb->query("UPDATE `{$wpdb->prefix}kboard_board_attached` SET `download_count`=`download_count`+1 WHERE `uid`='{$file_info->uid}'");
			// return executeQuery('document.updateCommentCount', $args);
			return new \X2board\Includes\Classes\BaseObject();
		}

		/**
		 * Grant a permisstion of the post
		 * Available in the current connection with session value
		 * @param int $document_srl
		 * @return void
		 */
		// function addGrant($document_srl)
		private function _add_grant($n_post_id) {
			$_SESSION['x2b_own_post'][$n_post_id] = true;
		}

		/**
		 * Remove values of extended user defined variable from the post
		 * @param int $n_board_id
		 * @param int $n_post_id
		 * @return 
		 */
		// function deleteDocumentExtraVars($module_srl, $document_srl = null, $var_idx = null, $lang_code = null, $eid = null)
		private function _delete_extended_user_defined_vars_all($n_board_id, $n_post_id) {  // , $var_idx = null, $lang_code = null, $eid = null) {
			// $obj = new stdClass();
			// $obj->module_srl = $module_srl;
			// if(!is_null($document_srl)) $obj->document_srl = $document_srl;
			// if(!is_null($var_idx)) $obj->var_idx = $var_idx;
			// if(!is_null($lang_code)) $obj->lang_code = $lang_code;
			// if(!is_null($eid)) $obj->eid = $eid;
			// $output = executeQuery('document.deleteDocumentExtraVars', $obj);
			// return $output;
			global $wpdb;
			$result = $wpdb->delete(
				$wpdb->prefix . 'x2b_user_define_vars',  // table name with dynamic prefix
				array('board_id' => $n_board_id,
					  'post_id'  => $n_post_id	),
				array('%d', '%d'), 						// make sure the id format
			);
			if( $result < 0 || $result === false ){
var_dump($wpdb->last_error);
				wp_die($wpdb->last_error );
			}
			// DELETE `document_extra_vars` FROM `xe_document_extra_vars` as `document_extra_vars`  
			// WHERE `module_srl` = ? and `document_srl` = ? and `lang_code` = ?
		}

		/**
		 * @brief mask multibyte string
		 * param 원본문자열, 마스킹하지 않는 전단부 글자수, 마스킹하지 않는 후단부 글자수, 마스킹 마크 최대 표시수, 마스킹마크
		 * echo _mask_mb_str('abc12234pro', 3, 2); => abc******ro
		 */	
		private function _mask_mb_str($str, $len1, $len2=0, $limit=0, $mark='*') {
			$arr_str = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
			$str_len = count($arr_str);

			$len1 = abs($len1);
			$len2 = abs($len2);
			if($str_len <= ($len1 + $len2)) {
				return $str;
			}

			$str_head = '';
			$str_body = '';
			$str_tail = '';

			$str_head = join('', array_slice($arr_str, 0, $len1));
			if($len2 > 0) {
				$str_tail = join('', array_slice($arr_str, $len2 * -1));
			}

			$arr_body = array_slice($arr_str, $len1, ($str_len - $len1 - $len2));

			if(!empty($arr_body)) {
				$len_body = count($arr_body);
				$limit = abs($limit);
				if($limit > 0 && $len_body > $limit) {
					$len_body = $limit;
				}
				$str_body = str_pad('', $len_body, $mark);
			}
			return $str_head.$str_body.$str_tail;
		}
		/**
		 * Secure personal private from an extra variable of the documents
		 * @param int $module_srl
		 * @param int $var_idx
		 * @return BaseObject
		 */
		// function secureDocumentExtraVars($nModuleSrl, $nVarIdx, $sBeginYyyymmdd, $sEndYyyymmdd)
		public function secure_post_user_defined_vars($nModuleSrl, $nVarIdx, $sBeginYyyymmdd, $sEndYyyymmdd) {
			if(!$nModuleSrl || !$nVarIdx) {
				return new \X2board\Includes\Classes\BaseObject(-1, __('msg_invalid_request', 'x2board') );
			}
				
			$oArg = new stdClass();
			$oArg->module_srl = $nModuleSrl;
			$oArg->var_idx = $nVarIdx;
			$oArg->begin_yyyymmdd = $sBeginYyyymmdd.'000001';
			$oArg->end_yyyymmdd = $sEndYyyymmdd.'235959';
			$oRst = executeQueryArray('document.getDocumentListWithExtraVarsPeriod', $oArg);
			unset($oArg);
			if(!count($oRst->data)) {
				return new \X2board\Includes\Classes\BaseObject();
			}
			
			foreach($oRst->data as $_ => $oSingleExtraVar) {
				if(strpos($oSingleExtraVar->value, '|@|')) {
					$aVal = explode('|@|', $oSingleExtraVar->value);
					$nCnt = count($aVal);
					if($nCnt == 3)  // maybe cell phone info
						$aVal[2] = '*';
					elseif($nCnt == 4 || $nCnt == 5) { // maybe addr info
						for($i = 2; $i <= $nCnt; $i++) {
							$aVal[$i] = '*';
						}
					}
					$oSingleExtraVar->value = implode('|@|', $aVal);
				}
				else { // maybe cell phone info
					$oSingleExtraVar->value = $this->_mask_mb_str($oSingleExtraVar->value, 3, 3);
				}
			}
			$oArg = new stdClass();
			foreach($oRst->data as $_ => $oSingleExtraVar) {
				$oArg->module_srl = $oSingleExtraVar->module_srl;
				$oArg->document_srl = $oSingleExtraVar->document_srl;
				$oArg->var_idx = $oSingleExtraVar->var_idx;
				$oArg->value = $oSingleExtraVar->value;
				$oRst = executeQuery('document.updateDocumentExtraVar', $oArg);
				if(!$oRst->toBool())
					return $oRst;
			}
			unset($oArg);
			unset($oRst);
			return new \X2board\Includes\Classes\BaseObject();
		}








/////////////////////////////////////////////////

		/**
		 * Action to handle vote-up of the post (Up)
		 * @return BaseObject
		 */
		function procDocumentVoteUp()
		{
			if(!Context::get('is_logged')) return new BaseObject(-1, 'msg_invalid_request');

			$document_srl = Context::get('target_srl');
			if(!$document_srl) return new BaseObject(-1, 'msg_invalid_request');

			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl, false, false);
			$module_srl = $oDocument->get('module_srl');
			if(!$module_srl) return new BaseObject(-1, 'msg_invalid_request');

			$oModuleModel = getModel('module');
			$document_config = $oModuleModel->getModulePartConfig('document',$module_srl);
			if($document_config->use_vote_up=='N') return new BaseObject(-1, 'msg_invalid_request');

			$point = 1;
			$output = $this->updateVotedCount($document_srl, $point);
			$this->add('voted_count', $output->get('voted_count'));
			return $output;
		}

		/**
		 * Action to handle vote-up of the post (Down)
		 * @return BaseObject
		 */
		function procDocumentVoteDown()
		{
			if(!Context::get('is_logged')) return new BaseObject(-1, 'msg_invalid_request');

			$document_srl = Context::get('target_srl');
			if(!$document_srl) return new BaseObject(-1, 'msg_invalid_request');

			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl, false, false);
			$module_srl = $oDocument->get('module_srl');
			if(!$module_srl) return new BaseObject(-1, 'msg_invalid_request');

			$oModuleModel = getModel('module');
			$document_config = $oModuleModel->getModulePartConfig('document',$module_srl);
			if($document_config->use_vote_down=='N') return new BaseObject(-1, 'msg_invalid_request');

			$point = -1;
			$output = $this->updateVotedCount($document_srl, $point);
			$this->add('blamed_count', $output->get('blamed_count'));
			return $output;
		}

		/**
		 * Action called when the post is reported by other member
		 * @return void|BaseObject
		 */
		function procDocumentDeclare()
		{
			if(!Context::get('is_logged')) return new BaseObject(-1, 'msg_invalid_request');

			$document_srl = Context::get('target_srl');
			if(!$document_srl) return new BaseObject(-1, 'msg_invalid_request');

			return $this->declaredDocument($document_srl);
		}

		/**
		 * Move the doc into the trash
		 * @param object $obj
		 * @return object
		 */
		function moveDocumentToTrash($obj)
		{
			$trash_args = new stdClass();
			// Get trash_srl if a given trash_srl doesn't exist
			if(!$obj->trash_srl) $trash_args->trash_srl = getNextSequence();
			else $trash_args->trash_srl = $obj->trash_srl;
			// Get its module_srl which the document belongs to
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($obj->document_srl);

			$trash_args->module_srl = $oDocument->get('module_srl');
			$obj->module_srl = $oDocument->get('module_srl');
			// Cannot throw data from the trash to the trash
			if($trash_args->module_srl == 0) return false;
			// Data setting
			$trash_args->document_srl = $obj->document_srl;
			$trash_args->description = $obj->description;
			// Insert member's information only if the member is logged-in and not manually registered.
			if(Context::get('is_logged')&&!$manual_inserted)
			{
				$logged_info = Context::get('logged_info');
				$trash_args->member_srl = $logged_info->member_srl;

				// user_id, user_name and nick_name already encoded
				$trash_args->user_id = htmlspecialchars_decode($logged_info->user_id);
				$trash_args->user_name = htmlspecialchars_decode($logged_info->user_name);
				$trash_args->nick_name = htmlspecialchars_decode($logged_info->nick_name);
			}
			// Date setting for updating documents
			$document_args = new stdClass;
			$document_args->module_srl = 0;
			$document_args->document_srl = $obj->document_srl;

			// begin transaction
			$oDB = &DB::getInstance();
			$oDB->begin();

			/*$output = executeQuery('document.insertTrash', $trash_args);
			if (!$output->toBool()) {
			$oDB->rollback();
			return $output;
			}*/

			// new trash module
			require_once(_XE_PATH_.'modules/trash/model/TrashVO.php');
			$oTrashVO = new TrashVO();
			$oTrashVO->setTrashSrl(getNextSequence());
			$oTrashVO->setTitle($oDocument->variables['title']);
			$oTrashVO->setOriginModule('document');
			$oTrashVO->setSerializedObject(serialize($oDocument->variables));
			$oTrashVO->setDescription($obj->description);

			$oTrashAdminController = getAdminController('trash');
			$output = $oTrashAdminController->insertTrash($oTrashVO);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			$output = executeQuery('document.deleteDocument', $trash_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			/*$output = executeQuery('document.updateDocument', $document_args);
			if (!$output->toBool()) {
			$oDB->rollback();
			return $output;
			}*/

			// update category
			if($oDocument->get('category_srl')) $this->updateCategoryCount($oDocument->get('module_srl'),$oDocument->get('category_srl'));

			// remove thumbnails
			FileHandler::removeDir(sprintf('files/thumbnails/%s',getNumberingPath($obj->document_srl, 3)));
			// Set the attachment to be invalid state
			if($oDocument->hasUploadedFiles())
			{
				$args = new stdClass();
				$args->upload_target_srl = $oDocument->document_srl;
				$args->isvalid = 'N';
				executeQuery('file.updateFileValid', $args);
			}
			// Call a trigger (after)
			if($output->toBool())
			{
				$trigger_output = ModuleHandler::triggerCall('document.moveDocumentToTrash', 'after', $obj);
				if(!$trigger_output->toBool())
				{
					$oDB->rollback();
					return $trigger_output;
				}
			}

			// commit
			$oDB->commit();

			// Clear cache
			$oCacheHandler = CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport())
			{
				$cache_key = 'post_item:'. getNumberingPath($oDocument->document_srl) . $oDocument->document_srl;
				$oCacheHandler->delete($cache_key);
			}

			return $output;
		}

		/**
		 * Increase the number of vote-up of the document
		 * @param int $document_srl
		 * @param int $point
		 * @return BaseObject
		 */
		function updateVotedCount($document_srl, $point = 1)
		{
			if($point > 0) $failed_voted = 'failed_voted';
			else $failed_voted = 'failed_blamed';
			// Return fail if session already has information about votes
			if($_SESSION['voted_document'][$document_srl])
			{
				return new BaseObject(-1, $failed_voted);
			}
			// Get the original document
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl, false, false);
			// Pass if the author's IP address is as same as visitor's.
			if($oDocument->get('ipaddress') == $_SERVER['REMOTE_ADDR'])
			{
				$_SESSION['voted_document'][$document_srl] = true;
				return new BaseObject(-1, $failed_voted);
			}

			// Create a member model object
			$oMemberModel = getModel('member');
			$member_srl = $oMemberModel->getLoggedMemberSrl();

			// Check if document's author is a member.
			if($oDocument->get('member_srl'))
			{
				// Pass after registering a session if author's information is same as the currently logged-in user's.
				if($member_srl && $member_srl == abs($oDocument->get('member_srl')))
				{
					$_SESSION['voted_document'][$document_srl] = true;
					return new BaseObject(-1, $failed_voted);
				}
			}

			// Use member_srl for logged-in members and IP address for non-members.
			$args = new stdClass;
			if($member_srl)
			{
				$args->member_srl = $member_srl;
			}
			else
			{
				$args->ipaddress = $_SERVER['REMOTE_ADDR'];
			}
			$args->document_srl = $document_srl;
			$output = executeQuery('document.getDocumentVotedLogInfo', $args);
			// Pass after registering a session if log information has vote-up logs
			if($output->data->count)
			{
				$_SESSION['voted_document'][$document_srl] = true;
				return new BaseObject(-1, $failed_voted);
			}

			// Call a trigger (before)
			$trigger_obj = new stdClass;
			$trigger_obj->member_srl = $oDocument->get('member_srl');
			$trigger_obj->module_srl = $oDocument->get('module_srl');
			$trigger_obj->document_srl = $oDocument->get('document_srl');
			$trigger_obj->update_target = ($point < 0) ? 'blamed_count' : 'voted_count';
			$trigger_obj->point = $point;
			$trigger_obj->before_point = ($point < 0) ? $oDocument->get('blamed_count') : $oDocument->get('voted_count');
			$trigger_obj->after_point = $trigger_obj->before_point + $point;
			$trigger_output = ModuleHandler::triggerCall('document.updateVotedCount', 'before', $trigger_obj);
			if(!$trigger_output->toBool())
			{
				return $trigger_output;
			}

			// begin transaction
			$oDB = DB::getInstance();
			$oDB->begin();

			// Update the voted count
			if($trigger_obj->update_target === 'blamed_count')
			{
				$args->blamed_count = $trigger_obj->after_point;
				$output = executeQuery('document.updateBlamedCount', $args);
			}
			else
			{
				$args->voted_count = $trigger_obj->after_point;
				$output = executeQuery('document.updateVotedCount', $args);
			}
			if(!$output->toBool()) return $output;

			// Leave logs
			$args->point = $trigger_obj->point;
			$output = executeQuery('document.insertDocumentVotedLog', $args);
			if(!$output->toBool()) return $output;

			// Call a trigger (after)
			$trigger_output = ModuleHandler::triggerCall('document.updateVotedCount', 'after', $trigger_obj);
			if(!$trigger_output->toBool())
			{
				$oDB->rollback();
				return $trigger_output;
			}

			$oDB->commit();

			$oCacheHandler = CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport())
			{
				//remove document item from cache
				$cache_key = 'post_item:'. getNumberingPath($document_srl) . $document_srl;
				$oCacheHandler->delete($cache_key);
			}

			// Leave in the session information
			$_SESSION['voted_document'][$document_srl] = true;

			// Return result
			$output = new BaseObject();
			if($trigger_obj->update_target === 'voted_count')
			{
				$output->setMessage('success_voted');
				$output->add('voted_count', $trigger_obj->after_point);
			}
			else
			{
				$output->setMessage('success_blamed');
				$output->add('blamed_count', $trigger_obj->after_point);
			}
			
			return $output;
		}

		/**
		 * Report posts
		 * @param int $document_srl
		 * @return void|BaseObject
		 */
		function declaredDocument($document_srl)
		{
			// Fail if session information already has a reported document
			if($_SESSION['declared_document'][$document_srl]) return new BaseObject(-1, 'failed_declared');

			// Check if previously reported
			$args = new stdClass();
			$args->document_srl = $document_srl;
			$output = executeQuery('document.getDeclaredDocument', $args);
			if(!$output->toBool()) return $output;

			$declared_count = ($output->data->declared_count) ? $output->data->declared_count : 0;

			$trigger_obj = new stdClass();
			$trigger_obj->document_srl = $document_srl;
			$trigger_obj->declared_count = $declared_count;

			// Call a trigger (before)
			$trigger_output = ModuleHandler::triggerCall('document.declaredDocument', 'before', $trigger_obj);
			if(!$trigger_output->toBool())
			{
				return $trigger_output;
			}

			// Get the original document
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl, false, false);

			// Pass if the author's IP address is as same as visitor's.
			if($oDocument->get('ipaddress') == $_SERVER['REMOTE_ADDR']) {
				$_SESSION['declared_document'][$document_srl] = true;
				return new BaseObject(-1, 'failed_declared');
			}

			// Check if document's author is a member.
			if($oDocument->get('member_srl'))
			{
				// Create a member model object
				$oMemberModel = getModel('member');
				$member_srl = $oMemberModel->getLoggedMemberSrl();
				// Pass after registering a session if author's information is same as the currently logged-in user's.
				if($member_srl && $member_srl == abs($oDocument->get('member_srl')))
				{
					$_SESSION['declared_document'][$document_srl] = true;
					return new BaseObject(-1, 'failed_declared');
				}
			}

			// Use member_srl for logged-in members and IP address for non-members.
			$args = new stdClass;
			if($member_srl)
			{
				$args->member_srl = $member_srl;
			}
			else
			{
				$args->ipaddress = $_SERVER['REMOTE_ADDR'];
			}

			$args->document_srl = $document_srl;
			$output = executeQuery('document.getDocumentDeclaredLogInfo', $args);

			// Pass after registering a sesson if reported/declared documents are in the logs.
			if($output->data->count)
			{
				$_SESSION['declared_document'][$document_srl] = true;
				return new BaseObject(-1, 'failed_declared');
			}

			// begin transaction
			$oDB = &DB::getInstance();
			$oDB->begin();

			// Add the declared document
			if($declared_count > 0) $output = executeQuery('document.updateDeclaredDocument', $args);
			else $output = executeQuery('document.insertDeclaredDocument', $args);
			if(!$output->toBool()) return $output;
			// Leave logs
			$output = executeQuery('document.insertDocumentDeclaredLog', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			$this->add('declared_count', $declared_count+1);

			// Call a trigger (after)
			$trigger_obj->declared_count = $declared_count + 1;
			$trigger_output = ModuleHandler::triggerCall('document.declaredDocument', 'after', $trigger_obj);
			if(!$trigger_output->toBool())
			{
				$oDB->rollback();
				return $trigger_output;
			}

			// commit
			$oDB->commit();

			// Leave in the session information
			$_SESSION['declared_document'][$document_srl] = true;

			$this->setMessage('success_declared');
		}
		
		/**
		 * Saved in the session when an administrator selects a post
		 * @return void|BaseObject
		 */
		function procDocumentAddCart()
		{
			if(!Context::get('is_logged')) return new BaseObject(-1, 'msg_not_permitted');

			// Get document_srl
			$srls = explode(',',Context::get('srls'));
			for($i = 0; $i < count($srls); $i++)
			{
				$srl = trim($srls[$i]);

				if(!$srl) continue;

				$document_srls[] = $srl;
			}
			if(!count($document_srls)) return;

			// Get module_srl of the documents
			$args = new stdClass;
			$args->list_count = count($document_srls);
			$args->document_srls = implode(',',$document_srls);
			$args->order_type = 'asc';
			$output = executeQueryArray('document.getDocuments', $args);
			if(!$output->data) return new BaseObject();

			unset($document_srls);
			foreach($output->data as $key => $val)
			{
				$document_srls[$val->module_srl][] = $val->document_srl;
			}
			if(!$document_srls || !count($document_srls)) return new BaseObject();

			// Check if each of module administrators exists. Top-level administator will have a permission to modify every document of all modules.(Even to modify temporarily saved or trashed documents)
			$oModuleModel = getModel('module');
			$module_srls = array_keys($document_srls);
			for($i=0;$i<count($module_srls);$i++)
			{
				$module_srl = $module_srls[$i];
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
				$logged_info = Context::get('logged_info');
				if($logged_info->is_admin != 'Y')
				{
					if(!$module_info)
					{
						unset($document_srls[$module_srl]);
						continue;
					}
					$grant = $oModuleModel->getGrant($module_info, $logged_info);
					if(!$grant->manager)
					{
						unset($document_srls[$module_srl]);
						continue;
					}
				}
			}
			if(!count($document_srls)) return new BaseObject();

			foreach($document_srls as $module_srl => $documents)
			{
				$cnt = count($documents);
				for($i=0;$i<$cnt;$i++)
				{
					$document_srl = (int)trim($documents[$i]);
					if(!$document_srls) continue;
					if($_SESSION['document_management'][$document_srl]) unset($_SESSION['document_management'][$document_srl]);
					else $_SESSION['document_management'][$document_srl] = true;
				}
			}
		}

		/**
		 * Move/ Delete the document in the seession
		 * @return void|BaseObject
		 */
		function procDocumentManageCheckedDocument()
		{
			@set_time_limit(0);
			if(!Context::get('is_logged')) return new BaseObject(-1,'msg_not_permitted');

			if(!checkCSRF())
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			$type = Context::get('type');
			$target_module = Context::get('target_module_srl');
			$module_srl = Context::get('module_srl');
			if($target_module && !$module_srl) $module_srl = $target_module;
			$category_srl = Context::get('target_category_srl');
			$message_content = strip_tags(Context::get('message_content'));
			if($message_content) $message_content = nl2br($message_content);

			$cart = Context::get('cart');
			$document_srl_list = (!is_array($cart)) ? explode('|@|', $cart) : $cart;

			array_map(function ($value) { return (int)$value; }, $document_srl_list);

			$document_srl_count = count($document_srl_list);

			$oDocumentModel = getModel('document');
			$document_items = array();
			foreach($document_srl_list as $document_srl)
			{
				$oDocument = $oDocumentModel->getDocument($document_srl);
				$document_items[] = $oDocument;
				if(!$oDocument->isGranted()) return $this->stop('msg_not_permitted');
			}

			// Send a message
			if($message_content)
			{

				$oCommunicationController = getController('communication');

				$logged_info = Context::get('logged_info');

				$title = cut_str($message_content,10,'...');
				$sender_member_srl = $logged_info->member_srl;

				foreach($document_items as $oDocument)
				{
					if(!$oDocument->get('member_srl') || $oDocument->get('member_srl')==$sender_member_srl) continue;

					if($type=='move') $purl = sprintf("<a href=\"%s\" target=\"_blank\">%s</a>", $oDocument->getPermanentUrl(), $oDocument->getPermanentUrl());
					else $purl = "";
					$content = sprintf("<div>%s</div><hr />%s<div style=\"font-weight:bold\">%s</div>%s",$message_content, $purl, $oDocument->getTitleText(), $oDocument->getContent(false, false, false));

					$oCommunicationController->sendMessage($sender_member_srl, $oDocument->get('member_srl'), $title, $content, false);
				}
			}
			// Set a spam-filer not to be filtered to spams
			$oSpamController = getController('spamfilter');
			$oSpamController->setAvoidLog();

			$oDocumentAdminController = getAdminController('document');
			if($type == 'move')
			{
				if(!$module_srl) return new BaseObject(-1, 'fail_to_move');

				$output = $oDocumentAdminController->moveDocumentModule($document_srl_list, $module_srl, $category_srl);
				if(!$output->toBool()) return new BaseObject(-1, 'fail_to_move');

				$msg_code = 'success_moved';

			}
			else if($type == 'copy')
			{
				if(!$module_srl) return new BaseObject(-1, 'fail_to_move');

				$output = $oDocumentAdminController->copyDocumentModule($document_srl_list, $module_srl, $category_srl);
				if(!$output->toBool()) return new BaseObject(-1, 'fail_to_move');

				$msg_code = 'success_copied';
			}
			else if($type =='delete')
			{
				$oDB = &DB::getInstance();
				$oDB->begin();
				for($i=0;$i<$document_srl_count;$i++)
				{
					$document_srl = $document_srl_list[$i];
					$output = $this->delete_post($document_srl, true);
					if(!$output->toBool()) return new BaseObject(-1, 'fail_to_delete');
				}
				$oDB->commit();
				$msg_code = 'success_deleted';
			}
			else if($type == 'trash')
			{
				$args = new stdClass();
				$args->description = $message_content;

				$oDB = &DB::getInstance();
				$oDB->begin();
				for($i=0;$i<$document_srl_count;$i++) {
					$args->document_srl = $document_srl_list[$i];
					$output = $this->moveDocumentToTrash($args);
					if(!$output || !$output->toBool()) return new BaseObject(-1, 'fail_to_trash');
				}
				$oDB->commit();
				$msg_code = 'success_trashed';
			}
			else if($type == 'cancelDeclare')
			{
				$args->document_srl = $document_srl_list;
				$output = executeQuery('document.deleteDeclaredDocuments', $args);
				$msg_code = 'success_declare_canceled';
			}

			$_SESSION['document_management'] = array();

			$this->setMessage($msg_code);

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispDocumentAdminList');
			$this->setRedirectUrl($returnUrl);
		}

		/**
		 * Document temporary save
		 * @return void|BaseObject
		 */
		function procDocumentTempSave()
		{
			// Check login information
			if(!Context::get('is_logged')) return new BaseObject(-1, 'msg_not_logged');
			$module_info = Context::get('module_info');
			$logged_info = Context::get('logged_info');

			// Get form information
			$obj = Context::getRequestVars();
			// Change the target module to log-in information
			$obj->module_srl = $module_info->module_srl;
			$obj->status = $this->getConfigStatus('temp');
			unset($obj->is_notice);

			// Extract from beginning part of contents in the guestbook
			if(!$obj->title)
			{
				$obj->title = cut_str(strip_tags($obj->content), 20, '...');
			}

			$oDocumentModel = getModel('document');
			$oDocumentController = getController('document');
			// Check if already exist geulinji
			$oDocument = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

			// Update if already exists
			if($oDocument->isExists() && $oDocument->document_srl == $obj->document_srl)
			{
				if($oDocument->get('module_srl') != $obj->module_srl)
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}
				if(!$oDocument->isGranted())
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}
				//if exist document status is already public, use temp status can point problem
				$obj->status = $oDocument->get('status');
				$output = $oDocumentController->updateDocument($oDocument, $obj);
				$msg_code = 'success_updated';
				// Otherwise, get a new
			}
			else
			{
				$output = $oDocumentController->insertDocument($obj);
				$msg_code = 'success_registed';
				$obj->document_srl = $output->get('document_srl');
				$oDocument = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);
			}
			// Set the attachment to be invalid state
			if($oDocument->hasUploadedFiles())
			{
				$args = new stdClass;
				$args->upload_target_srl = $oDocument->document_srl;
				$args->isvalid = 'N';
				executeQuery('file.updateFileValid', $args);
			}

			$this->setMessage('success_saved');
			$this->add('document_srl', $obj->document_srl);
		}

		/**
		 * Insert extra variables into the document table
		 * @param int $module_srl
		 * @param int $var_idx
		 * @param string $var_name
		 * @param string $var_type
		 * @param string $var_is_required
		 * @param string $var_search
		 * @param string $var_default
		 * @param string $var_desc
		 * @param int $eid
		 * @return object
		 */
		/*function insertDocumentExtraKey($module_srl, $var_idx, $var_name, $var_type, $var_is_required = 'N', $var_search = 'N', $var_default = '', $var_desc = '', $eid)
		{
			if(!$module_srl || !$var_idx || !$var_name || !$var_type || !$eid) return new BaseObject(-1,'msg_invalid_request');

			$obj = new stdClass();
			$obj->module_srl = $module_srl;
			$obj->var_idx = $var_idx;
			$obj->var_name = $var_name;
			$obj->var_type = $var_type;
			$obj->var_is_required = $var_is_required=='Y'?'Y':'N';
			$obj->var_search = $var_search=='Y'?'Y':'N';
			$obj->var_default = $var_default;
			$obj->var_desc = $var_desc;
			$obj->eid = $eid;

			$output = executeQuery('document.getDocumentExtraKeys', $obj);
			if(!$output->data)
			{
				$output = executeQuery('document.insertDocumentExtraKey', $obj);
			}
			else
			{
				$output = executeQuery('document.updateDocumentExtraKey', $obj);
				// Update the extra var(eid)
				$output = executeQuery('document.updateDocumentExtraVar', $obj);
			}

			$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
			if($oCacheHandler->isSupport())
			{
				$object_key = 'module_document_extra_keys:'.$module_srl;
				$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
				$oCacheHandler->delete($cache_key);
			}

			return $output;
		}*/

		/**
		 * Remove the extra variables of the documents
		 * @param int $module_srl
		 * @param int $var_idx
		 * @return BaseObject
		 */
		/*function deleteDocumentExtraKeys($module_srl, $var_idx = null)
		{
			if(!$module_srl) return new BaseObject(-1,'msg_invalid_request');
			$obj = new stdClass();
			$obj->module_srl = $module_srl;
			if(!is_null($var_idx)) $obj->var_idx = $var_idx;

			$oDB = DB::getInstance();
			$oDB->begin();

			$output = $oDB->executeQuery('document.deleteDocumentExtraKeys', $obj);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			if($var_idx != NULL)
			{
				$output = $oDB->executeQuery('document.updateDocumentExtraKeyIdxOrder', $obj);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}

			$output =  executeQuery('document.deleteDocumentExtraVars', $obj);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			if($var_idx != NULL)
			{
				$output = $oDB->executeQuery('document.updateDocumentExtraVarIdxOrder', $obj);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}

			$oDB->commit();

			$oCacheHandler = CacheHandler::getInstance('object', NULL, TRUE);
			if($oCacheHandler->isSupport())
			{
				$object_key = 'module_document_extra_keys:'.$module_srl;
				$cache_key = $oCacheHandler->getGroupKey('site_and_module', $object_key);
				$oCacheHandler->delete($cache_key);
			}

			return new BaseObject();
		}*/

		/**
		 * insert alias
		 * @param int $module_srl
		 * @param int $document_srl
		 * @param string $alias_title
		 * @return object
		 */
		/*function insertAlias($module_srl, $document_srl, $alias_title)
		{
			$args = new stdClass;
			$args->alias_srl = getNextSequence();
			$args->module_srl = $module_srl;
			$args->document_srl = $document_srl;
			$args->alias_title = urldecode($alias_title);
			$query = "document.insertAlias";
			$output = executeQuery($query, $args);
			return $output;
		}*/

		/**
		 * Delete alias when module deleted
		 * @param int $module_srl
		 * @return void
		 */
		/*function deleteDocumentAliasByModule($module_srl)
		{
			$args = new stdClass();
			$args->module_srl = $module_srl;
			executeQuery("document.deleteAlias", $args);
		}*/

		/**
		 * Delete alias when document deleted
		 * @param int $document_srl
		 * @return void
		 */
		/*function deleteDocumentAliasByDocument($document_srl)
		{
			$args = new stdClass();
			$args->document_srl = $document_srl;
			executeQuery("document.deleteAlias", $args);
		}*/

		/**
		 * Increase trackback count of the document
		 * @param int $document_srl
		 * @param int $trackback_count
		 * @return object
		 */
		/*function updateTrackbackCount($document_srl, $trackback_count)
		{
			$args = new stdClass;
			$args->document_srl = $document_srl;
			$args->trackback_count = $trackback_count;

			$oCacheHandler = CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport())
			{
				//remove document item from cache
				$cache_key = 'post_item:'. getNumberingPath($document_srl) . $document_srl;
				$oCacheHandler->delete($cache_key);
			}

			return executeQuery('document.updateTrackbackCount', $args);
		}*/

		/**
		 * Add a category
		 * @param object $obj
		 * @return object
		 */
		/*function insertCategory($obj)
		{
			// Sort the order to display if a child category is added
			if($obj->parent_srl)
			{
				// Get its parent category
				$oDocumentModel = getModel('document');
				$parent_category = $oDocumentModel->getCategory($obj->parent_srl);
				$obj->list_order = $parent_category->list_order;
				$this->updateCategoryListOrder($parent_category->module_srl, $parent_category->list_order+1);
				if(!$obj->category_srl) $obj->category_srl = getNextSequence();
			}
			else
			{
				$obj->list_order = $obj->category_srl = getNextSequence();
			}

			$output = executeQuery('document.insertCategory', $obj);
			if($output->toBool())
			{
				$output->add('category_srl', $obj->category_srl);
				$this->makeCategoryFile($obj->module_srl);
			}

			return $output;
		}*/

		/**
		 * Increase list_count from a specific category
		 * @param int $module_srl
		 * @param int $list_order
		 * @return object
		 */
		/*function updateCategoryListOrder($module_srl, $list_order)
		{
			$args = new stdClass;
			$args->module_srl = $module_srl;
			$args->list_order = $list_order;
			return executeQuery('document.updateCategoryOrder', $args);
		}*/

		/**
		 * Update document_count in the category.
		 * @param int $module_srl
		 * @param int $category_srl
		 * @param int $document_count
		 * @return object
		 */
		/*function updateCategoryCount($module_srl, $category_srl, $document_count = 0)
		{
			// Create a document model object
			$oDocumentModel = getModel('document');
			if(!$document_count) $document_count = $oDocumentModel->getCategoryDocumentCount($module_srl,$category_srl);

			$args = new stdClass;
			$args->category_srl = $category_srl;
			$args->document_count = $document_count;
			$output = executeQuery('document.updateCategoryCount', $args);
			if($output->toBool()) $this->makeCategoryFile($module_srl);

			return $output;
		}*/

		/**
		 * Update category information
		 * @param object $obj
		 * @return object
		 */
		/*function updateCategory($obj)
		{
			$output = executeQuery('document.updateCategory', $obj);
			if($output->toBool()) $this->makeCategoryFile($obj->module_srl);
			return $output;
		}*/

		/**
		 * Delete a category
		 * @param int $category_srl
		 * @return object
		 */
		/*function deleteCategory($category_srl)
		{
			$args = new stdClass();
			$args->category_srl = $category_srl;
			$oDocumentModel = getModel('document');
			$category_info = $oDocumentModel->getCategory($category_srl);
			// Display an error that the category cannot be deleted if it has a child
			$output = executeQuery('document.getChildCategoryCount', $args);
			if(!$output->toBool()) return $output;
			if($output->data->count>0) return new BaseObject(-1, 'msg_cannot_delete_for_child');
			// Delete a category information
			$output = executeQuery('document.deleteCategory', $args);
			if(!$output->toBool()) return $output;

			$this->makeCategoryFile($category_info->module_srl);
			// remvove cache
			$oCacheHandler = CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport())
			{
				$page = 0;
				while(true) {
					$args = new stdClass();
					$args->category_srl = $category_srl;
					$args->list_count = 100;
					$args->page = ++$page;
					$output = executeQuery('document.getDocumentList', $args, array('document_srl'));

					if($output->data == array())
						break;

					foreach($output->data as $val)
					{
						//remove document item from cache
						$cache_key = 'post_item:'. getNumberingPath($val->document_srl) . $val->document_srl;
						$oCacheHandler->delete($cache_key);
					}
				}
			}

			// Update category_srl of the documents in the same category to 0
			$args = new stdClass();
			$args->target_category_srl = 0;
			$args->source_category_srl = $category_srl;
			$output = executeQuery('document.updateDocumentCategory', $args);

			return $output;
		}*/

		/**
		 * Delete all categories in a module
		 * @param int $module_srl
		 * @return object
		 */
		/*function deleteModuleCategory($module_srl)
		{
			$args = new stdClass();
			$args->module_srl = $module_srl;
			$output = executeQuery('document.deleteModuleCategory', $args);
			return $output;
		}*/

		/**
		 * Move the category level to higher
		 * @param int $category_srl
		 * @return BaseObject
		 */
		/*function moveCategoryUp($category_srl)
		{
			$oDocumentModel = getModel('document');
			// Get information of the selected category
			$args = new stdClass;
			$args->category_srl = $category_srl;
			$output = executeQuery('document.getCategory', $args);

			$category = $output->data;
			$list_order = $category->list_order;
			$module_srl = $category->module_srl;
			// Seek a full list of categories
			$category_list = $oDocumentModel->getCategoryList($module_srl);
			$category_srl_list = array_keys($category_list);
			if(count($category_srl_list)<2) return new BaseObject();

			$prev_category = NULL;
			foreach($category_list as $key => $val)
			{
				if($key==$category_srl) break;
				$prev_category = $val;
			}
			// Return if the previous category doesn't exist
			if(!$prev_category) return new BaseObject(-1,Context::getLang('msg_category_not_moved'));
			// Return if the selected category is the top level
			if($category_srl_list[0]==$category_srl) return new BaseObject(-1,Context::getLang('msg_category_not_moved'));
			// Information of the selected category
			$cur_args = new stdClass;
			$cur_args->category_srl = $category_srl;
			$cur_args->list_order = $prev_category->list_order;
			$cur_args->title = $category->title;
			$this->updateCategory($cur_args);
			// Category information
			$prev_args = new stdClass;
			$prev_args->category_srl = $prev_category->category_srl;
			$prev_args->list_order = $list_order;
			$prev_args->title = $prev_category->title;
			$this->updateCategory($prev_args);

			return new BaseObject();
		}*/

		/**
		 * Move the category down
		 * @param int $category_srl
		 * @return BaseObject
		 */
		/*function moveCategoryDown($category_srl)
		{
			$oDocumentModel = getModel('document');
			// Get information of the selected category
			$args = new stdClass;
			$args->category_srl = $category_srl;
			$output = executeQuery('document.getCategory', $args);

			$category = $output->data;
			$list_order = $category->list_order;
			$module_srl = $category->module_srl;
			// Seek a full list of categories
			$category_list = $oDocumentModel->getCategoryList($module_srl);
			$category_srl_list = array_keys($category_list);
			if(count($category_srl_list)<2) return new BaseObject();

			for($i=0;$i<count($category_srl_list);$i++)
			{
				if($category_srl_list[$i]==$category_srl) break;
			}

			$next_category_srl = $category_srl_list[$i+1];
			if(!$category_list[$next_category_srl]) return new BaseObject(-1,Context::getLang('msg_category_not_moved'));
			$next_category = $category_list[$next_category_srl];
			// Information of the selected category
			$cur_args = new stdClass;
			$cur_args->category_srl = $category_srl;
			$cur_args->list_order = $next_category->list_order;
			$cur_args->title = $category->title;
			$this->updateCategory($cur_args);
			// Category information
			$next_args = new stdClass;
			$next_args->category_srl = $next_category->category_srl;
			$next_args->list_order = $list_order;
			$next_args->title = $next_category->title;
			$this->updateCategory($next_args);

			return new BaseObject();
		}*/

		/**
		 * Add javascript codes into the header by checking values of document_extra_keys type, required and others
		 * @param int $module_srl
		 * @return void
		 */
		/*function addXmlJsFilter($module_srl)
		{
			$oDocumentModel = getModel('document');
			$extra_keys = $oDocumentModel->getExtraKeys($module_srl);
			if(!count($extra_keys)) return;

			$js_code = array();
			$js_code[] = '<script>//<![CDATA[';
			$js_code[] = '(function($){';
			$js_code[] = 'var validator = xe.getApp("validator")[0];';
			$js_code[] = 'if(!validator) return false;';

			$logged_info = Context::get('logged_info');

			foreach($extra_keys as $idx => $val)
			{
				$idx = $val->idx;
				if($val->type == 'kr_zip')
				{
					$idx .= '[]';
				}
				$name = str_ireplace(array('<script', '</script'), array('<scr" + "ipt', '</scr" + "ipt'), $val->name);
				$js_code[] = sprintf('validator.cast("ADD_MESSAGE", ["extra_vars%s","%s"]);', $idx, $name);
				if($val->is_required == 'Y') $js_code[] = sprintf('validator.cast("ADD_EXTRA_FIELD", ["extra_vars%s", { required:true }]);', $idx);
			}

			$js_code[] = '})(jQuery);';
			$js_code[] = '//]]></script>';
			$js_code   = implode("\n", $js_code);

			Context::addHtmlHeader($js_code);
		}*/

		/**
		 * Add a category
		 * @param object $args
		 * @return void
		 */
		/*function procDocumentInsertCategory($args = null)
		{
			// List variables
			if(!$args) $args = Context::gets('module_srl','category_srl','parent_srl','category_title','category_description','expand','group_srls','category_color','mid');
			$args->title = $args->category_title;
			$args->description = $args->category_description;
			$args->color = $args->category_color;

			if(!$args->module_srl && $args->mid)
			{
				$mid = $args->mid;
				unset($args->mid);
				$args->module_srl = $this->module_srl;
			}
			// Check permissions
			$oModuleModel = getModel('module');
			$columnList = array('module_srl', 'module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl, $columnList);
			$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
			if(!$grant->manager) return new BaseObject(-1,'msg_not_permitted');

			if($args->expand !="Y") $args->expand = "N";
			if(!is_array($args->group_srls)) $args->group_srls = str_replace('|@|',',',$args->group_srls);
			else $args->group_srls = implode(',', $args->group_srls);
			$args->parent_srl = (int)$args->parent_srl;

			$oDocumentModel = getModel('document');

			$oDB = &DB::getInstance();
			$oDB->begin();
			// Check if already exists
			if($args->category_srl)
			{
				$category_info = $oDocumentModel->getCategory($args->category_srl);
				if($category_info->category_srl != $args->category_srl) $args->category_srl = null;
			}
			// Update if exists
			if($args->category_srl)
			{
				$output = $this->updateCategory($args);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
				// Insert if not exist
			}
			else
			{
				$output = $this->insertCategory($args);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
			// Update the xml file and get its location
			$xml_file = $this->makeCategoryFile($args->module_srl);

			$oDB->commit();

			$this->add('xml_file', $xml_file);
			$this->add('module_srl', $args->module_srl);
			$this->add('category_srl', $args->category_srl);
			$this->add('parent_srl', $args->parent_srl);

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : Context::get('error_return_url');
			$this->setRedirectUrl($returnUrl);
		}*/

		/**
		 * Move a category
		 * @return void
		 */
		/*function procDocumentMoveCategory()
		{
			$source_category_srl = Context::get('source_srl');
			// If parent_srl exists, be the first child
			$parent_category_srl = Context::get('parent_srl');
			// If target_srl exists, be a sibling
			$target_category_srl = Context::get('target_srl');

			$oDocumentModel = getModel('document');
			$source_category = $oDocumentModel->getCategory($source_category_srl);
			// Check permissions
			$oModuleModel = getModel('module');
			$columnList = array('module_srl', 'module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($source_category->module_srl, $columnList);
			$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
			if(!$grant->manager) return new BaseObject(-1,'msg_not_permitted');

			// First child of the parent_category_srl
			$source_args = new stdClass;
			if($parent_category_srl > 0 || ($parent_category_srl == 0 && $target_category_srl == 0))
			{
				$parent_category = $oDocumentModel->getCategory($parent_category_srl);

				$args = new stdClass;
				$args->module_srl = $source_category->module_srl;
				$args->parent_srl = $parent_category_srl;
				$output = executeQuery('document.getChildCategoryMinListOrder', $args);

				if(!$output->toBool()) return $output;
				$args->list_order = (int)$output->data->list_order;
				if(!$args->list_order) $args->list_order = 0;
				$args->list_order--;

				$source_args->category_srl = $source_category_srl;
				$source_args->parent_srl = $parent_category_srl;
				$source_args->list_order = $args->list_order;
				$output = $this->updateCategory($source_args);
				if(!$output->toBool()) return $output;
				// Sibling of the $target_category_srl
			}
			else if($target_category_srl > 0)
			{
				$target_category = $oDocumentModel->getCategory($target_category_srl);
				// Move all siblings of the $target_category down
				$output = $this->updateCategoryListOrder($target_category->module_srl, $target_category->list_order+1);
				if(!$output->toBool()) return $output;

				$source_args->category_srl = $source_category_srl;
				$source_args->parent_srl = $target_category->parent_srl;
				$source_args->list_order = $target_category->list_order+1;
				$output = $this->updateCategory($source_args);
				if(!$output->toBool()) return $output;
			}
			// Re-generate the xml file
			$xml_file = $this->makeCategoryFile($source_category->module_srl);
			// Variable settings
			$this->add('xml_file', $xml_file);
			$this->add('source_category_srl', $source_category_srl);
		}*/

		/**
		 * Delete a category
		 * @return void
		 */
		/*function procDocumentDeleteCategory()
		{
			// List variables
			$args = Context::gets('module_srl','category_srl');

			$oDB = &DB::getInstance();
			$oDB->begin();
			// Check permissions
			$oModuleModel = getModel('module');
			$columnList = array('module_srl', 'module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl, $columnList);
			$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
			if(!$grant->manager) return new BaseObject(-1,'msg_not_permitted');

			$oDocumentModel = getModel('document');
			// Get original information
			$category_info = $oDocumentModel->getCategory($args->category_srl);
			if($category_info->parent_srl) $parent_srl = $category_info->parent_srl;
			// Display an error that the category cannot be deleted if it has a child node
			if($oDocumentModel->getCategoryChlidCount($args->category_srl)) return new BaseObject(-1, 'msg_cannot_delete_for_child');
			// Remove from the DB
			$output = $this->deleteCategory($args->category_srl);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
			// Update the xml file and get its location
			$xml_file = $this->makeCategoryFile($args->module_srl);

			$oDB->commit();

			$this->add('xml_file', $xml_file);
			$this->add('category_srl', $parent_srl);
			$this->setMessage('success_deleted');
		}*/

		/**
		 * Xml files updated
		 * Occasionally the xml file is not generated after menu is configued on the admin page \n
		 * The administrator can manually update the file in this case \n
		 * Although the issue is not currently reproduced, it is unnecessay to remove.
		 * @return void
		 */
		/*function procDocumentMakeXmlFile()
		{
			// Check input values
			$module_srl = Context::get('module_srl');
			// Check permissions
			$oModuleModel = getModel('module');
			$columnList = array('module_srl', 'module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
			$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
			if(!$grant->manager) return new BaseObject(-1,'msg_not_permitted');

			$xml_file = $this->makeCategoryFile($module_srl);
			// Set return value
			$this->add('xml_file',$xml_file);
		}*/

		/**
		 * Save the category in a cache file
		 * @param int $module_srl
		 * @return string
		 */
		/*function makeCategoryFile($module_srl)
		{
			// Return if there is no information you need for creating a cache file
			if(!$module_srl) return false;

			$module_srl = (int)$module_srl;

			// Get module information (to obtain mid)
			$oModuleModel = getModel('module');
			$columnList = array('module_srl', 'mid', 'site_srl');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
			$mid = $module_info->mid;

			if(!is_dir('./files/cache/document_category')) FileHandler::makeDir('./files/cache/document_category');
			// Cache file's name
			$xml_file = sprintf("./files/cache/document_category/%s.xml.php", $module_srl);
			$php_file = sprintf("./files/cache/document_category/%s.php", $module_srl);
			// Get a category list
			$args = new stdClass();
			$args->module_srl = $module_srl;
			$args->sort_index = 'list_order';
			$output = executeQueryArray('document.getCategoryList', $args);

			$category_list = $output->data;

			if(!is_array($category_list)) $category_list = array($category_list);

			$category_count = count($category_list);
			for($i=0;$i<$category_count;$i++)
			{
				$category_srl = $category_list[$i]->category_srl;
				if(!preg_match('/^[0-9,]+$/', $category_list[$i]->group_srls)) $category_list[$i]->group_srls = '';
				$list[$category_srl] = $category_list[$i];
			}
			// Create the xml file without node data if no data is obtained
			if(!$list)
			{
				$xml_buff = "<root />";
				FileHandler::writeFile($xml_file, $xml_buff);
				FileHandler::writeFile($php_file, '<?php if(!defined("__XE__")) exit(); ?>');
				return $xml_file;
			}
			// Change to an array if only a single data is obtained
			if(!is_array($list)) $list = array($list);
			// Create a tree for loop
			foreach($list as $category_srl => $node)
			{
				$node->mid = $mid;
				$parent_srl = (int)$node->parent_srl;
				$tree[$parent_srl][$category_srl] = $node;
			}
			// A common header to set permissions and groups of the cache file
			$header_script =
				'$lang_type = Context::getLangType(); '.
				'$is_logged = Context::get(\'is_logged\'); '.
				'$logged_info = Context::get(\'logged_info\'); '.
				'if($is_logged) {'.
				'if($logged_info->is_admin=="Y") $is_admin = true; '.
				'else $is_admin = false; '.
				'$group_srls = array_keys($logged_info->group_list); '.
				'} else { '.
				'$is_admin = false; '.
				'$group_srsl = array(); '.
				'} '."\n";

			// Create the xml cache file (a separate session is needed for xml cache)
			$xml_header_buff = '';
			$xml_body_buff = $this->getXmlTree($tree[0], $tree, $module_info->site_srl, $xml_header_buff);
			$xml_buff = sprintf(
				'<?php '.
				'define(\'__XE__\', true); '.
				'require_once(\''.FileHandler::getRealPath('./config/config.inc.php').'\'); '.
				'$oContext = &Context::getInstance(); '.
				'$oContext->init(); '.
				'header("Content-Type: text/xml; charset=UTF-8"); '.
				'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); '.
				'header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); '.
				'header("Cache-Control: no-store, no-cache, must-revalidate"); '.
				'header("Cache-Control: post-check=0, pre-check=0", false); '.
				'header("Pragma: no-cache"); '.
				'%s'.
				'%s '.
				'$oContext->close();'.
				'?>'.
				'<root>%s</root>',
				$header_script,
				$xml_header_buff,
				$xml_body_buff
			);
			// Create php cache file
			$php_header_buff = '$_titles = array();';
			$php_header_buff .= '$_descriptions = array();';
			$php_output = $this->getPhpCacheCode($tree[0], $tree, $module_info->site_srl, $php_header_buff);
			$php_buff = sprintf(
				'<?php '.
				'if(!defined("__XE__")) exit(); '.
				'%s'.
				'%s'.
				'$menu = new stdClass;'.
				'$menu->list = array(%s); ',
				$header_script,
				$php_header_buff,
				$php_output['buff']
			);
			// Save File
			FileHandler::writeFile($xml_file, $xml_buff);
			FileHandler::writeFile($php_file, $php_buff);
			return $xml_file;
		}*/

		/**
		 * Create the xml data recursively referring to parent_srl
		 * In the menu xml file, node tag is nested and xml doc enables the admin page to have a menu\n
		 * (tree menu is implemented by reading xml file from the tree_menu.js)
		 * @param array $source_node
		 * @param array $tree
		 * @param int $site_srl
		 * @param string $xml_header_buff
		 * @return string
		 */
		/*function getXmlTree($source_node, $tree, $site_srl, &$xml_header_buff)
		{
			if(!$source_node) return;

			foreach($source_node as $category_srl => $node)
			{
				$child_buff = "";
				// Get data of the child nodes
				if($category_srl && $tree[$category_srl]) $child_buff = $this->getXmlTree($tree[$category_srl], $tree, $site_srl, $xml_header_buff);
				// List variables
				$expand = ($node->expand) ? $node->expand : 'N';
				$group_srls = ($node->group_srls) ? $node->group_srls : '';
				$mid = ($node->mid) ? $node->mid : '';
				$module_srl = ($node->module_srl) ? $node->parent_srl : '';
				$parent_srl = ($node->parent_srl) ? $node->parent_srl : '';
				$color = ($node->color) ? $node->color : '';
				$description = ($node->description) ? $node->description : '';
				// If node->group_srls value exists
				if($group_srls) $group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$group_srls);
				else $group_check_code = "true";

				$title = $node->title;
				$oModuleAdminModel = getAdminModel('module');

				$langs = $oModuleAdminModel->getLangCode($site_srl, $title);
				if(count($langs))
				{
					foreach($langs as $key => $val)
					{
						$xml_header_buff .= sprintf('$_titles[%d]["%s"] = %s; ', $category_srl, $key, var_export(str_replace('"','\\"',htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false)), true));
					}
				}

				$langx = $oModuleAdminModel->getLangCode($site_srl, $description);
				if(count($langx))
				{
					foreach($langx as $key => $val)
					{
						$xml_header_buff .= sprintf('$_descriptions[%d]["%s"] = %s; ', $category_srl, $key, var_export(str_replace('"','\\"',htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false)), true));
					}
				}

				$attribute = sprintf(
					'mid="%s" module_srl="%d" node_srl="%d" parent_srl="%d" category_srl="%d" text="<?php echo (%s?($_titles[%d][$lang_type]):"")?>" url=%s expand=%s color=%s description="<?php echo (%s?($_descriptions[%d][$lang_type]):"")?>" document_count="%d" ',
					$mid,
					$module_srl,
					$category_srl,
					$parent_srl,
					$category_srl,
					$group_check_code,
					$category_srl,
					var_export(getUrl('','mid',$node->mid,'category',$category_srl), true),
					var_export($expand, true),
					var_export($color, true),
					$group_check_code,
					$category_srl,
					$node->document_count
				);

				if($child_buff) $buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
				else $buff .=  sprintf('<node %s />', $attribute);
			}
			return $buff;
		}*/

		/**
		 * Change sorted nodes in an array to the php code and then return
		 * When using menu on tpl, you can directly xml data. howver you may need javascrips additionally.
		 * Therefore, you can configure the menu info directly from php cache file, not through DB.
		 * You may include the cache in the ModuleHandler::displayContent()
		 * @param array $source_node
		 * @param array $tree
		 * @param int $site_srl
		 * @param string $php_header_buff
		 * @return array
		 */
		/*function getPhpCacheCode($source_node, $tree, $site_srl, &$php_header_buff)
		{
			$output = array("buff"=>"", "category_srl_list"=>array());
			if(!$source_node) return $output;

			// Set to an arraty for looping and then generate php script codes to be included
			foreach($source_node as $category_srl => $node)
			{
				// Get data from child nodes first if exist.
				if($category_srl && $tree[$category_srl]){
					$child_output = $this->getPhpCacheCode($tree[$category_srl], $tree, $site_srl, $php_header_buff);
				} else {
					$child_output = array("buff"=>"", "category_srl_list"=>array());
				}

				// Set values into category_srl_list arrary if url of the current node is not empty
				$child_output['category_srl_list'][] = $node->category_srl;
				$output['category_srl_list'] = array_merge($output['category_srl_list'], $child_output['category_srl_list']);

				// If node->group_srls value exists
				if($node->group_srls) {
					$group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$node->group_srls);
				} else {
					$group_check_code = "true";
				}

				// List variables
				$selected = '"' . implode('","', $child_output['category_srl_list']) . '"';
				$child_buff = $child_output['buff'];
				$expand = $node->expand;

				$title = $node->title;
				$description = $node->description;
				$oModuleAdminModel = getAdminModel('module');
				$langs = $oModuleAdminModel->getLangCode($site_srl, $title);

				if(count($langs))
				{
					foreach($langs as $key => $val)
					{
						$val = htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
						$php_header_buff .= sprintf(
							'$_titles[%d]["%s"] = %s; ',
							$category_srl,
							$key,
							var_export(str_replace('"','\\"', $val), true)
						);
					}
				}

				$langx = $oModuleAdminModel->getLangCode($site_srl, $description);

				if(count($langx))
				{
					foreach($langx as $key => $val)
					{
						$val = htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
						$php_header_buff .= sprintf(
							'$_descriptions[%d]["%s"] = %s; ',
							$category_srl,
							$key,
							var_export(str_replace('"','\\"', $val), true)
						);
					}
				}

				// Create attributes(Use the category_srl_list to check whether to belong to the menu's node. It seems to be tricky but fast fast and powerful;)
				$attribute = sprintf(
					'"mid" => "%s", "module_srl" => "%d","node_srl"=>"%d","category_srl"=>"%d","parent_srl"=>"%d","text"=>$_titles[%d][$lang_type],"selected"=>(in_array(Context::get("category"),array(%s))?1:0),"expand"=>%s,"color"=>%s,"description"=>$_descriptions[%d][$lang_type],"list"=>array(%s),"document_count"=>"%d","grant"=>%s?true:false',
					$node->mid,
					$node->module_srl,
					$node->category_srl,
					$node->category_srl,
					$node->parent_srl,
					$node->category_srl,
					$selected,
					var_export($expand, true),
					var_export($node->color, true),
					$node->category_srl,
					$child_buff,
					$node->document_count,
					$group_check_code
				);

				// Generate buff data
				$output['buff'] .=  sprintf('%s=>array(%s),', $node->category_srl, $attribute);
			}

			return $output;
		}*/

		/**
		 * A method to add a pop-up menu which appears when clicking
		 * @param string $url
		 * @param string $str
		 * @param string $icon
		 * @param string $target
		 * @return void
		 */
		/*function addDocumentPopupMenu($url, $str, $icon = '', $target = 'self')
		{
			$document_popup_menu_list = Context::get('document_popup_menu_list');
			if(!is_array($document_popup_menu_list)) $document_popup_menu_list = array();

			$obj = new stdClass();
			$obj->url = $url;
			$obj->str = $str;
			$obj->icon = $icon;
			$obj->target = $target;
			$document_popup_menu_list[] = $obj;

			Context::set('document_popup_menu_list', $document_popup_menu_list);
		}*/

		/**
		 * Insert document module config
		 * @return void
		 */
		/*function procDocumentInsertModuleConfig()
		{
			$module_srl = Context::get('target_module_srl');
			if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
			else $module_srl = array($module_srl);

			$document_config = new stdClass();
			$document_config->use_history = Context::get('use_history');
			if(!$document_config->use_history) $document_config->use_history = 'N';

			$document_config->use_vote_up = Context::get('use_vote_up');
			if(!$document_config->use_vote_up) $document_config->use_vote_up = 'Y';

			$document_config->use_vote_down = Context::get('use_vote_down');
			if(!$document_config->use_vote_down) $document_config->use_vote_down = 'Y';

			$document_config->use_status = Context::get('use_status');

			$oModuleController = getController('module');
			for($i=0;$i<count($module_srl);$i++)
			{
				$srl = trim($module_srl[$i]);
				if(!$srl) continue;
				$output = $oModuleController->insertModulePartConfig('document',$srl,$document_config);
			}
			$this->setError(-1);
			$this->setMessage('success_updated', 'info');

			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
			$this->setRedirectUrl($returnUrl);
		}*/

		/**
		 * Return Document List for exec_xml
		 * @return void|BaseObject
		 */
		/*function procDocumentGetList()
		{
			if(!Context::get('is_logged')) return new BaseObject(-1,'msg_not_permitted');
			$documentSrls = Context::get('document_srls');
			if($documentSrls) $documentSrlList = explode(',', $documentSrls);

			if(count($documentSrlList) > 0)
			{
				$oDocumentModel = getModel('document');
				$columnList = array('document_srl', 'title', 'nick_name', 'status');
				$documentList = $oDocumentModel->getDocuments($documentSrlList, $this->grant->is_admin, false, $columnList);
			}
			else
			{
				global $lang;
				$documentList = array();
				$this->setMessage($lang->no_documents);
			}
			$oSecurity = new Security($documentList);
			$oSecurity->encodeHTML('..variables.');
			$this->add('document_list', $documentList);
		}*/

		/**
		 * A trigger to delete all posts together when the module is deleted
		 * @param object $obj
		 * @return BaseObject
		 */
		// function triggerDeleteModuleDocuments(&$obj)
		// {
		// 	$module_srl = $obj->module_srl;
		// 	if(!$module_srl) return new BaseObject();
		// 	// Delete the document
		// 	$oDocumentAdminController = getAdminController('document');
		// 	$output = $oDocumentAdminController->deleteModuleDocument($module_srl);
		// 	if(!$output->toBool()) return $output;
		// 	// Delete the category
		// 	$oDocumentController = getController('document');
		// 	$output = $oDocumentController->deleteModuleCategory($module_srl);
		// 	if(!$output->toBool()) return $output;
		// 	// Delete extra key and variable, because module deleted
		// 	$this->deleteDocumentExtraKeys($module_srl);

		// 	// remove aliases
		// 	$this->deleteDocumentAliasByModule($module_srl);

		// 	// remove histories
		// 	$this->deleteDocumentHistory(null, null, $module_srl);

		// 	return new BaseObject();
		// }

		/**
		 * For old version, comment allow status check.
		 * @param object $obj
		 * @return void
		 */
		// function _checkCommentStatusForOldVersion(&$obj)
		// {
		// 	if(!isset($obj->allow_comment)) $obj->allow_comment = 'N';
		// 	if(!isset($obj->lock_comment)) $obj->lock_comment = 'N';

		// 	if($obj->allow_comment == 'Y' && $obj->lock_comment == 'N') $obj->commentStatus = 'ALLOW';
		// 	else $obj->commentStatus = 'DENY';
		// }

		/**
		 * For old version, document status check.
		 * @param object $obj
		 * @return void
		 */
		// function _checkDocumentStatusForOldVersion(&$obj)
		// {
		// 	if(!$obj->status && $obj->is_secret == 'Y') $obj->status = $this->getConfigStatus('secret');
		// 	if(!$obj->status && $obj->is_secret != 'Y') $obj->status = $this->getConfigStatus('public');
		// }

		/**
		 * Copy extra keys when module copied
		 * @param object $obj
		 * @return void
		 */
		// function triggerCopyModuleExtraKeys(&$obj)
		// {
		// 	$oDocumentModel = getModel('document');
		// 	$documentExtraKeys = $oDocumentModel->getExtraKeys($obj->originModuleSrl);

		// 	if(is_array($documentExtraKeys) && is_array($obj->moduleSrlList))
		// 	{
		// 		$oDocumentController=getController('document');
		// 		foreach($obj->moduleSrlList AS $key=>$value)
		// 		{
		// 			foreach($documentExtraKeys AS $extraItem)
		// 			{
		// 				$oDocumentController->insertDocumentExtraKey($value, $extraItem->idx, $extraItem->name, $extraItem->type, $extraItem->is_required , $extraItem->search , $extraItem->default , $extraItem->desc, $extraItem->eid) ;
		// 			}
		// 		}
		// 	}
		// }

		// function triggerCopyModule(&$obj)
		// {
		// 	$oModuleModel = getModel('module');
		// 	$documentConfig = $oModuleModel->getModulePartConfig('document', $obj->originModuleSrl);

		// 	$oModuleController = getController('module');
		// 	if(is_array($obj->moduleSrlList))
		// 	{
		// 		foreach($obj->moduleSrlList AS $key=>$moduleSrl)
		// 		{
		// 			$oModuleController->insertModulePartConfig('document', $moduleSrl, $documentConfig);
		// 		}
		// 	}
		// }
	}
}
/* End of file post.controller.php */