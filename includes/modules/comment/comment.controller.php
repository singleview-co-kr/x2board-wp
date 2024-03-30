<?php
/**
 * commentController class
 * controller class of the comment module
 *
 * @author singleview.co.kr
 * @package /modules/comment
 * @version 0.1
 */
namespace X2board\Includes\Modules\Comment;

if ( !defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly.
}

if (!class_exists('\\X2board\\Includes\\Modules\\Comment\\commentController')) {

	class commentController extends comment
	{

		/**
		 * Initialization
		 * @return void
		 */
		function init()	{
var_dump('commentController::init()');
		 }

		/**
		 * Enter comments
		 * @param object $obj
		 * @param bool $manual_inserted
		 * @return object
		 */
		// function insertComment($obj, $manual_inserted = FALSE)
		public function insert_comment($obj, $manual_inserted = FALSE)
		{
			if(!$manual_inserted) {  // check WP nonce if a guest inserts a new post
				$wp_verify_nonce = \X2board\Includes\Classes\Context::get('x2b_'.X2B_CMD_PROC_WRITE_COMMENT.'_nonce');
				if( is_null( $wp_verify_nonce ) ){
					return new \X2board\Includes\Classes\BaseObject(-1, 'msg_invalid_request1');
				}
				if( !wp_verify_nonce($wp_verify_nonce, 'x2b_'.X2B_CMD_PROC_WRITE_COMMENT) ){
					return new \X2board\Includes\Classes\BaseObject(-1, 'msg_invalid_request2');
				}
			}

			if(!is_object($obj)) {
				$obj = new \stdClass();
			}

			$o_logged_info = \X2board\Includes\Classes\Context::get('logged_info');
			if(!$manual_inserted) {
				if( \X2board\Includes\Classes\Context::get('is_logged') ) {
					// $o_logged_info = \X2board\Includes\Classes\Context::get('logged_info');
					if($o_logged_info->is_admin == 'Y') {
						$is_admin = TRUE;
					}
					else {
						$is_admin = FALSE;
					}
				}
			}
			else {
				$is_admin = FALSE;
			}
var_dump($manual_inserted);
			// check if comment's module is using comment validation and set the publish status to 0 (false)
			// for inserting query, otherwise default is 1 (true - means comment is published)
			$using_validation = $this->isModuleUsingPublishValidation(); // $obj->module_srl);
			if(!$using_validation) {
				$obj->status = 1;
			}
			else {
				if($is_admin) {
					$obj->status = 1;
				}
				else {
					$obj->status = 0;
				}
			}
			// $obj->__isupdate = FALSE;

			// call a trigger (before)
			// $output = ModuleHandler::triggerCall('comment.insertComment', 'before', $obj);
			// if(!$output->toBool())
			// {
			// 	return $output;
			// }

			// check if a posting of the corresponding post_id exists
			$parent_post_id = $obj->parent_post_id;
			if(!$parent_post_id) {
				return new \X2board\Includes\Classes\BaseObject( -1, __('msg_invalid_request', 'x2board') );
			}

			// get a object of post model
			// $o_post_model = \X2board\Includes\getModel('post');

			// even for manual_inserted if password exists, hash it.
			if($obj->password) {
				$obj->password = \X2board\Includes\getModel('member')->hash_password($obj->password);
			}

			// get the original posting
			// if(!$manual_inserted) {
			if($manual_inserted) {
				// get a object of post model
				$o_post_model = \X2board\Includes\getModel('post');
				$o_post = $o_post_model->get_post($parent_post_id);
				unset($o_post_model);
	
				if($parent_post_id != $o_post->post_id) {
					return new \X2board\Includes\Classes\BaseObject( -1, __('msg_invalid_document', 'x2board') );
				}
				if($o_post->is_locked()) {
					return new \X2board\Includes\Classes\BaseObject( -1, __('msg_invalid_request', 'x2board') );
				}
				unset($o_post);

				// if($obj->homepage) {
				// 	$obj->homepage = escape($obj->homepage, false);
				// 	if(!preg_match('/^[a-z]+:\/\//i',$obj->homepage)) {
				// 		$obj->homepage = 'http://'.$obj->homepage;
				// 	}
				// }

				// input the member's information if logged-in
				if(\X2board\Includes\Classes\Context::get('is_logged')) {
					// $o_logged_info = \X2board\Includes\Classes\Context::get('logged_info');
					$obj->comment_author = $o_logged_info->ID;
					// user_id, user_name and nick_name already encoded
					// $obj->user_id = htmlspecialchars_decode($o_logged_info->user_id);
					// $obj->user_name = htmlspecialchars_decode($o_logged_info->user_nicename);
					$obj->nick_name = htmlspecialchars_decode($o_logged_info->display_name);
					$obj->email_address = $o_logged_info->user_email;
					// $obj->homepage = $o_logged_info->homepage;
					// unset($o_logged_info);
				}
			}

			// error display if neither of log-in info and user name exist.
			if(!$o_logged_info->ID && !$obj->nick_name) {
				return new \X2board\Includes\Classes\BaseObject( -1, __('msg_invalid_request', 'x2board') );
			}

			if(!$obj->comment_id) {
				$obj->comment_id = \X2board\Includes\getNextSequence();
			}
			elseif(!$is_admin && !$manual_inserted && !\X2board\Includes\checkUserSequence($obj->comment_id)) {
				return new \X2board\Includes\Classes\BaseObject( -1, __('msg_not_permitted', 'x2board') );
			}

			// determine the order
			$obj->list_order = \X2board\Includes\getNextSequence() * -1;

			// remove XE's own tags from the contents
			// $obj->comment_content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->comment_content);

			// if(Mobile::isFromMobilePhone() && $obj->use_editor != 'Y') {
			if(wp_is_mobile() && $obj->use_editor != 'Y') {
				if($obj->use_html != 'Y') {
					$obj->comment_content = htmlspecialchars($obj->comment_content, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
				}
				$obj->comment_content = nl2br($obj->comment_content);
			}

			if(!isset($obj->regdate)) {
				$obj->regdate = date('Y-m-d H:i:s', current_time('timestamp')); //date("YmdHis");
			}

			// remove iframe and script if not a top administrator on the session.
			if($o_logged_info->is_admin != 'Y') {
				$obj->comment_content = \X2board\Includes\removeHackTag($obj->comment_content);
			}
			unset($o_logged_info);

			// if(!$obj->notify_message) {
			// 	$obj->notify_message = 'N';
			// }

			if(!isset($obj->is_secret)) {
				$obj->is_secret = 'N';
			}

			// begin transaction
			// $oDB = DB::getInstance();
			// $oDB->begin();

			// Enter a list of comments first
			$list_args = new \stdClass();
			$list_args->comment_id = $obj->comment_id;
			$list_args->parent_post_id = $obj->parent_post_id;
			$list_args->board_id = $obj->board_id;
			$list_args->regdate = $obj->regdate;

			// If parent comment doesn't exist, set data directly
			if(!$obj->parent_comment_id) {  // parent comment
				$list_args->head = $list_args->arrange = $obj->comment_id;
				$list_args->depth = 0;
				// If parent comment exists, get information of the parent comment
			}
			else {  // child comment
				// get information of the parent comment posting
				$parent_args = new \stdClass();
				$parent_args->comment_id = $obj->parent_comment_id;
				$parent_output = executeQuery('comment.getCommentListItem', $parent_args);

				// return if no parent comment exists
				if(!$parent_output->toBool() || !$parent_output->data) {
					return;
				}

				$parent = $parent_output->data;

				$list_args->head = $parent->head;
				$list_args->depth = $parent->depth + 1;

				// if the depth of comments is less than 2, execute insert.
				if($list_args->depth < 2) {
					$list_args->arrange = $obj->comment_id;
					// if the depth of comments is greater than 2, execute update.
				}
				else {
					// get the top listed comment among those in lower depth and same head with parent's.
					$p_args = new stdClass();
					$p_args->head = $parent->head;
					$p_args->arrange = $parent->arrange;
					$p_args->depth = $parent->depth;
					$output = executeQuery('comment.getCommentParentNextSibling', $p_args);

					if($output->data->arrange) {
						$list_args->arrange = $output->data->arrange;
						$output = executeQuery('comment.updateCommentListArrange', $list_args);
					}
					else {
						$list_args->arrange = $obj->comment_id;
					}
				}
			}

			$a_new_comment_list = array();
			$a_new_comment_list['comment_id'] = $list_args->comment_id;
			$a_new_comment_list['parent_post_id'] = $list_args->parent_post_id;
			$a_new_comment_list['board_id'] = $list_args->board_id;
			$a_new_comment_list['regdate'] = $list_args->regdate;
			$a_new_comment_list['arrange'] = $list_args->arrange;
			$a_new_comment_list['head'] = $list_args->head;
			$a_new_comment_list['depth'] = $list_args->depth;

			$a_insert_key = array();
			$a_insert_val = array();
			foreach($a_new_comment_list as $key=>$value){
				// $this->{$key} = $value;
				$value = esc_sql($value);
				$a_insert_key[] = "`$key`";
				$a_insert_val[] = "'$value'";
			}
			unset($a_new_comment);

			global $wpdb;
			$query = "INSERT INTO `{$wpdb->prefix}x2b_comments_list` (".implode(',', $a_insert_key).") VALUES (".implode(',', $a_insert_val).")";
			if ($wpdb->query($query) === FALSE) {
				return new \X2board\Includes\Classes\BaseObject(-1, $wpdb->last_error);
			} 
			// $n_new_post_id = $wpdb->insert_id;
			unset($a_insert_key);
			unset($a_insert_data);
			// $output = executeQuery('comment.insertCommentList', $list_args);
			// if(!$output->toBool()) {
			// 	return $output;
			// }

			// sanitize
			$a_new_comment = array();
			$a_new_comment['board_id'] = $obj->board_id;
			$a_new_comment['parent_post_id'] = intval($obj->parent_post_id);
			$a_new_comment['content'] = sanitize_text_field($obj->comment_content);
			$a_new_comment['parent_comment_id'] = intval($obj->parent_comment_id);
			$a_new_comment['comment_id'] = intval($obj->comment_id);
			// $a_new_comment['use_editor'] = sanitize_text_field($obj->use_editor);
			// $a_new_comment['use_html'] = sanitize_text_field($obj->use_html);
			$a_new_comment['password'] = $obj->password;
			// $a_new_comment['notify_message'] = sanitize_text_field($obj->notify_message);
			$a_new_comment['comment_author'] = intval($obj->comment_author);
			$a_new_comment['email_address'] = sanitize_text_field($obj->email_address);
			$a_new_comment['nick_name'] = sanitize_text_field($obj->nick_name);
			$a_new_comment['status'] = intval($obj->status);
			$a_new_comment['list_order'] = intval($obj->list_order);
			$a_new_comment['regdate'] = $obj->regdate;
			$a_new_comment['last_update'] = $a_new_comment['regdate'];
			$a_new_comment['is_secret'] = sanitize_text_field($obj->is_secret);
			$a_new_comment['ua'] = wp_is_mobile() ? 'M' : 'P';  // add user agent
			$a_new_comment['ipaddress'] = \X2board\Includes\get_remote_ip();

			$a_insert_key = array();
			$a_insert_val = array();
			foreach($a_new_comment as $key=>$value){
				// $this->{$key} = $value;
				$value = esc_sql($value);
				$a_insert_key[] = "`$key`";
				$a_insert_val[] = "'$value'";
			}
			unset($a_new_comment);

			// insert comment
			$query = "INSERT INTO `{$wpdb->prefix}x2b_comments` (".implode(',', $a_insert_key).") VALUES (".implode(',', $a_insert_val).")";
			if ($wpdb->query($query) === FALSE) {
				return new \X2board\Includes\Classes\BaseObject(-1, $wpdb->last_error);
			} 
			unset($a_insert_key);
			unset($a_insert_data);
			// $output = executeQuery('comment.insertComment', $obj);
			// if(!$output->toBool()) {
			// 	// $oDB->rollback();
			// 	return $output;
			// }

			// creat the comment model object
			$o_comment_model = \X2board\Includes\getModel('comment');

			// get the number of all comments in the posting
			$n_comment_count = $o_comment_model->get_comment_count($parent_post_id);
			unset($o_comment_model);
// var_dump($n_comment_count);
// var_dump($is_admin);
			// create the controller object of the document
			$o_post_controller = \X2board\Includes\getController('post');

			// Update the number of comments in the post
			if(!$using_validation) {
				$output = $o_post_controller->update_comment_count($parent_post_id, $n_comment_count, $obj->nick_name, TRUE);
			}
			else {
				if($is_admin) {
					$output = $o_post_controller->update_comment_count($parent_post_id, $n_comment_count, $obj->nick_name, TRUE);
				}
			}
			unset($o_post_controller);

			// grant autority of the comment
			if(!$manual_inserted) {
				$this->_add_grant($n_new_comment_id);
			}
// exit;
			// call a trigger(after)
			// if($output->toBool()) {
			// 	$trigger_output = ModuleHandler::triggerCall('comment.insertComment', 'after', $obj);
			// 	if(!$trigger_output->toBool())
			// 	{
			// 		$oDB->rollback();
			// 		return $trigger_output;
			// 	}
			// }

			// commit
			// $oDB->commit();

			// if(!$manual_inserted) {
			// 	// send a message if notify_message option in enabled in the original article
			// 	$oDocument->notify(Context::getLang('comment'), $obj->comment_content);

			// 	// send a message if notify_message option in enabled in the original comment
			// 	if($obj->parent_srl)
			// 	{
			// 		$oParent = $oCommentModel->getComment($obj->parent_srl);
			// 		if($oParent->get('member_srl') != $oDocument->get('member_srl'))
			// 		{
			// 			$oParent->notify(Context::getLang('comment'), $obj->comment_content);
			// 		}
			// 	}
			// }

			// $this->sendEmailToAdminAfterInsertComment($obj);
			//////////////////////////////////////////
			///////////// begin - temp exception
			if( !isset($output)){
				$output = new \X2board\Includes\Classes\BaseObject();
			}
			///////////// end - temp exception
			//////////////////////////////////////////
			$output->add('comment_id', $obj->comment_id);
			return $output;
		}
		
		/**
		 * Check if module is using comment validation system
		 * @param int $module_srl
		 * @return bool
		 */
		// function isModuleUsingPublishValidation($module_srl = NULL)
		public function isModuleUsingPublishValidation() { // $module_srl = NULL) {
			return false;
			// if($module_srl == NULL)	{
			// 	return FALSE;
			// }
			// $oModuleModel = getModel('module');
			// $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			// $module_part_config = $oModuleModel->getModulePartConfig('comment', $module_info->module_srl);
			$module_part_config = new \stdClass();

			$use_validation = FALSE;
			if(isset($module_part_config->use_comment_validation) && $module_part_config->use_comment_validation == "Y") {
				$use_validation = TRUE;
			}
			return $use_validation;
		}

		/**
		 * Authorization of the comments
		 * available only in the current connection of the session value
		 * @return void
		 */
		// function addGrant($comment_srl)
		private function _add_grant($comment_id) {
			$_SESSION['own_comment'][$comment_id] = TRUE;
		}

/////////////////////////////////////////

		
		/**
		 * Fix the comment
		 * @param object $obj
		 * @param bool $is_admin
		 * @param bool $manual_updated
		 * @return object
		 */
		function updateComment($obj, $is_admin = FALSE, $manual_updated = FALSE)
		{
			if(!$manual_updated && !checkCSRF())
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			if(!is_object($obj))
			{
				$obj = new stdClass();
			}

			// $obj->__isupdate = TRUE;

			// call a trigger (before)
			$output = ModuleHandler::triggerCall('comment.updateComment', 'before', $obj);
			if(!$output->toBool())
			{
				return $output;
			}

			// create a comment model object
			$oCommentModel = getModel('comment');

			// get the original data
			$source_obj = $oCommentModel->getComment($obj->comment_srl);
			if(!$source_obj->getMemberSrl())
			{
				$obj->member_srl = $source_obj->get('member_srl');
				$obj->user_name = $source_obj->get('user_name');
				$obj->nick_name = $source_obj->get('nick_name');
				$obj->email_address = $source_obj->get('email_address');
				$obj->homepage = $source_obj->get('homepage');
			}

			// check if permission is granted
			if(!$is_admin && !$source_obj->isGranted())
			{
				return new BaseObject(-1, 'msg_not_permitted');
			}

			if($obj->password)
			{
				$obj->password = getModel('member')->hashPassword($obj->password);
			}

			if($obj->homepage) 
			{
				$obj->homepage = escape($obj->homepage);
				if(!preg_match('/^[a-z]+:\/\//i',$obj->homepage))
				{
					$obj->homepage = 'http://'.$obj->homepage;
				}
			}

			// set modifier's information if logged-in and posting author and modifier are matched.
			if(Context::get('is_logged'))
			{
				$logged_info = Context::get('logged_info');
				if($source_obj->member_srl == $logged_info->member_srl)
				{
					$obj->member_srl = $logged_info->member_srl;
					$obj->user_name = $logged_info->user_name;
					$obj->nick_name = $logged_info->nick_name;
					$obj->email_address = $logged_info->email_address;
					$obj->homepage = $logged_info->homepage;
				}
			}

			// if nick_name of the logged-in author doesn't exist
			if($source_obj->get('member_srl') && !$obj->nick_name)
			{
				$obj->member_srl = $source_obj->get('member_srl');
				$obj->user_name = $source_obj->get('user_name');
				$obj->nick_name = $source_obj->get('nick_name');
				$obj->email_address = $source_obj->get('email_address');
				$obj->homepage = $source_obj->get('homepage');
			}

			if(!$obj->content)
			{
				$obj->content = $source_obj->get('content');
			}

			// remove XE's wn tags from contents
			$obj->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $obj->content);

			if(Mobile::isFromMobilePhone() && $obj->use_editor != 'Y')
			{
				if($obj->use_html != 'Y')
				{
					$obj->content = htmlspecialchars($obj->content, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
				}
				$obj->content = nl2br($obj->content);
			}

			// remove iframe and script if not a top administrator on the session
			if($logged_info->is_admin != 'Y')
			{
				$obj->content = removeHackTag($obj->content);
			}

			// begin transaction
			$oDB = DB::getInstance();
			$oDB->begin();

			// Update
			$output = executeQuery('comment.updateComment', $obj);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			// call a trigger (after)
			if($output->toBool())
			{
				$trigger_output = ModuleHandler::triggerCall('comment.updateComment', 'after', $obj);
				if(!$trigger_output->toBool())
				{
					$oDB->rollback();
					return $trigger_output;
				}
			}

			// commit
			$oDB->commit();

			$output->add('comment_srl', $obj->comment_srl);

			return $output;
		}

		/**
		 * Delete comment
		 * @param int $comment_srl
		 * @param bool $is_admin
		 * @param bool $isMoveToTrash
		 * @return object
		 */
		function deleteComment($comment_srl, $is_admin = FALSE, $isMoveToTrash = FALSE)
		{
			// create the comment model object
			$oCommentModel = getModel('comment');

			// check if comment already exists
			$comment = $oCommentModel->getComment($comment_srl);
			if($comment->comment_srl != $comment_srl)
			{
				return new BaseObject(-1, 'msg_invalid_request');
			}

			$document_srl = $comment->document_srl;

			// call a trigger (before)
			$output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
			if(!$output->toBool())
			{
				return $output;
			}

			// check if permission is granted
			if(!$is_admin && !$comment->isGranted())
			{
				return new BaseObject(-1, 'msg_not_permitted');
			}

			// check if child comment exists on the comment
			$childs = $oCommentModel->getChildComments($comment_srl);
			if(count($childs) > 0)
			{
				$deleteAllComment = TRUE;
				if(!$is_admin)
				{
					$logged_info = Context::get('logged_info');
					foreach($childs as $val)
					{
						if($val->member_srl != $logged_info->member_srl)
						{
							$deleteAllComment = FALSE;
							break;
						}
					}
				}

				if(!$deleteAllComment)
				{
					return new BaseObject(-1, 'fail_to_delete_have_children');
				}
				else
				{
					foreach($childs as $val)
					{
						$output = $this->deleteComment($val->comment_srl, $is_admin, $isMoveToTrash);
						if(!$output->toBool())
						{
							return $output;
						}
					}
				}
			}

			// begin transaction
			$oDB = DB::getInstance();
			$oDB->begin();

			// Delete
			$args = new stdClass();
			$args->comment_srl = $comment_srl;
			$output = executeQuery('comment.deleteComment', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			$output = executeQuery('comment.deleteCommentList', $args);

			// update the number of comments
			$comment_count = $oCommentModel->getCommentCount($document_srl);

			// only document is exists
			if(isset($comment_count))
			{
				// create the controller object of the document
				$oDocumentController = getController('document');

				// update comment count of the article posting
				$output = $oDocumentController->updateCommentCount($document_srl, $comment_count, NULL, FALSE);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}

			// call a trigger (after)
			if($output->toBool())
			{
				$comment->isMoveToTrash = $isMoveToTrash;
				$trigger_output = ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);
				if(!$trigger_output->toBool())
				{
					$oDB->rollback();
					return $trigger_output;
				}
				unset($comment->isMoveToTrash);
			}

			if(!$isMoveToTrash)
			{
				$this->_deleteDeclaredComments($args);
				$this->_deleteVotedComments($args);
			} 
			else 
			{
				$args = new stdClass();
				$args->upload_target_srl = $comment_srl;
				$args->isvalid = 'N';
				$output = executeQuery('file.updateFileValid', $args);
			}

			// commit
			$oDB->commit();

			$output->add('document_srl', $document_srl);

			return $output;
		}

		/**
		 * Remove all comment relation log
		 * @return BaseObject
		 */
		function deleteCommentLog($args)
		{
			$this->_deleteDeclaredComments($args);
			$this->_deleteVotedComments($args);
			return new BaseObject(0, 'success');
		}

		/**
		 * Remove all comments of the article
		 * @param int $document_srl
		 * @return object
		 */
		function deleteComments($document_srl, $obj = NULL)
		{
			// create the document model object
			$oDocumentModel = getModel('document');
			$oCommentModel = getModel('comment');

			// check if permission is granted
			if(is_object($obj))
			{
				$oDocument = new documentItem();
				$oDocument->setAttribute($obj);
			}
			else
			{
				$oDocument = $oDocumentModel->getDocument($document_srl);
			}

			if(!$oDocument->isExists() || !$oDocument->isGranted())
			{
				return new BaseObject(-1, 'msg_not_permitted');
			}

			// get a list of comments and then execute a trigger(way to reduce the processing cost for delete all)
			$args = new stdClass();
			$args->document_srl = $document_srl;
			$comments = executeQueryArray('comment.getAllComments', $args);
			if($comments->data)
			{
				$commentSrlList = array();
				foreach($comments->data as $comment)
				{
					$commentSrlList[] = $comment->comment_srl;

					// call a trigger (before)
					$output = ModuleHandler::triggerCall('comment.deleteComment', 'before', $comment);
					if(!$output->toBool())
					{
						continue;
					}

					// call a trigger (after)
					$output = ModuleHandler::triggerCall('comment.deleteComment', 'after', $comment);
					if(!$output->toBool())
					{
						continue;
					}
				}
			}

			// delete the comment
			$args->document_srl = $document_srl;
			$output = executeQuery('comment.deleteComments', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			// Delete a list of comments
			$output = executeQuery('comment.deleteCommentsList', $args);

			//delete declared, declared_log, voted_log
			if(is_array($commentSrlList) && count($commentSrlList) > 0)
			{
				$args = new stdClass();
				$args->comment_srl = join(',', $commentSrlList);
				$this->_deleteDeclaredComments($args);
				$this->_deleteVotedComments($args);
			}

			return $output;
		}

		/**
		 * Get comment all list
		 * @return void
		 */
		function procCommentGetList()
		{
			if(!Context::get('is_logged'))
			{
				return new BaseObject(-1, 'msg_not_permitted');
			}

			$commentSrls = Context::get('comment_srls');
			if($commentSrls)
			{
				$commentSrlList = explode(',', $commentSrls);
			}

			if(count($commentSrlList) > 0)
			{
				$oCommentModel = getModel('comment');
				$commentList = $oCommentModel->getComments($commentSrlList);

				if(is_array($commentList))
				{
					foreach($commentList as $value)
					{
						$value->content = strip_tags($value->content);
					}
				}
			}
			else
			{
				global $lang;
				$commentList = array();
				$this->setMessage($lang->no_documents);
			}

			$oSecurity = new Security($commentList);
			$oSecurity->encodeHTML('..variables.', '..');

			$this->add('comment_list', $commentList);
		}

		/**
		 * Send email to module's admins after a new comment was interted successfully
		 * if Comments Approval System is used 
		 * @param object $obj 
		 * @return void
		 */
		// function sendEmailToAdminAfterInsertComment($obj)
		// {
		// 	$using_validation = $this->isModuleUsingPublishValidation($obj->module_srl);

		// 	$oDocumentModel = getModel('document');
		// 	$oDocument = $oDocumentModel->getDocument($obj->document_srl);

		// 	$oMemberModel = getModel("member");
		// 	if(isset($obj->member_srl) && !is_null($obj->member_srl))
		// 	{
		// 		$member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);
		// 	}
		// 	else
		// 	{
		// 		$member_info = new stdClass();
		// 		$member_info->is_admin = "N";
		// 		$member_info->nick_name = $obj->nick_name;
		// 		$member_info->user_name = $obj->user_name;
		// 		$member_info->email_address = $obj->email_address;
		// 	}

		// 	$oCommentModel = getModel("comment");
		// 	$nr_comments_not_approved = $oCommentModel->getCommentAllCount(NULL, FALSE);

		// 	$oModuleModel = getModel("module");
		// 	$module_info = $oModuleModel->getModuleInfoByDocumentSrl($obj->document_srl);

		// 	// If there is no problem to register comment then send an email to all admin were set in module admin panel
		// 	if($module_info->admin_mail && $member_info->is_admin != 'Y')
		// 	{
		// 		$oMail = new Mail();
		// 		$oMail->setSender($obj->email_address, $obj->email_address);
		// 		$mail_title = "[XE - " . Context::get('mid') . "] A new comment was posted on document: \"" . $oDocument->getTitleText() . "\"";
		// 		$oMail->setTitle($mail_title);
		// 		$url_comment = getFullUrl('','document_srl',$obj->document_srl).'#comment_'.$obj->comment_srl;
		// 		if($using_validation)
		// 		{
		// 			$url_approve = getFullUrl('', 'module', 'admin', 'act', 'procCommentAdminChangePublishedStatusChecked', 'cart[]', $obj->comment_srl, 'will_publish', '1', 'search_target', 'is_published', 'search_keyword', 'N');
		// 			$url_trash = getFullUrl('', 'module', 'admin', 'act', 'procCommentAdminDeleteChecked', 'cart[]', $obj->comment_srl, 'search_target', 'is_trash', 'search_keyword', 'true');
		// 			$mail_content = "
		// 				A new comment on the document \"" . $oDocument->getTitleText() . "\" is waiting for your approval.
		// 				<br />
		// 				<br />
		// 				Author: " . $member_info->nick_name . "
		// 				<br />Author e-mail: " . $member_info->email_address . "
		// 				<br />From : <a href=\"" . $url_comment . "\">" . $url_comment . "</a>
		// 				<br />Comment:
		// 				<br />\"" . $obj->content . "\"
		// 				<br />Document:
		// 				<br />\"" . $oDocument->getContentText(). "\"
		// 				<br />
		// 				<br />
		// 				Approve it: <a href=\"" . $url_approve . "\">" . $url_approve . "</a>
		// 				<br />Trash it: <a href=\"" . $url_trash . "\">" . $url_trash . "</a>
		// 				<br />Currently " . $nr_comments_not_approved . " comments on \"" . Context::get('mid') . "\" module are waiting for approval. Please visit the moderation panel:
		// 				<br /><a href=\"" . getFullUrl('', 'module', 'admin', 'act', 'dispCommentAdminList', 'search_target', 'module', 'search_keyword', $obj->module_srl) . "\">" . getFullUrl('', 'module', 'admin', 'act', 'dispCommentAdminList', 'search_target', 'module', 'search_keyword', $obj->module_srl) . "</a>
		// 				";
		// 			$oMail->setContent($mail_content);
		// 		}
		// 		else
		// 		{
		// 			$mail_content = "
		// 				Author: " . $member_info->nick_name . "
		// 				<br />Author e-mail: " . $member_info->email_address . "
		// 				<br />From : <a href=\"" . $url_comment . "\">" . $url_comment . "</a>
		// 				<br />Comment:
		// 				<br />\"" . $obj->content . "\"
		// 				<br />Document:
		// 				<br />\"" . $oDocument->getContentText(). "\"
		// 				";
		// 			$oMail->setContent($mail_content);

		// 			// get email of thread's author
		// 			$document_author_email = $oDocument->variables['email_address'];

		// 			//get admin info
		// 			$logged_info = Context::get('logged_info');

		// 			//mail to author of thread - START
		// 			/**
		// 			 * @todo Removed code send email to document author.
		// 			*/
		// 			/*
		// 			if($document_author_email != $obj->email_address && $logged_info->email_address != $document_author_email)
		// 			{
		// 				$oMail->setReceiptor($document_author_email, $document_author_email);
		// 				$oMail->send();
		// 			}
		// 			*/
		// 			// mail to author of thread - STOP
		// 		}

		// 		// get all admins emails
		// 		$admins_emails = $module_info->admin_mail;
		// 		$target_mail = explode(',', $admins_emails);

		// 		// send email to all admins - START
		// 		for($i = 0; $i < count($target_mail); $i++)
		// 		{
		// 			$email_address = trim($target_mail[$i]);
		// 			if(!$email_address)
		// 			{
		// 				continue;
		// 			}

		// 			$oMail->setReceiptor($email_address, $email_address);
		// 			$oMail->send();
		// 		}
		// 		//  send email to all admins - STOP
		// 	}

		// 	$comment_srl_list = array(0 => $obj->comment_srl);
		// 	// call a trigger for calling "send mail to subscribers" (for moment just for forum)
		// 	ModuleHandler::triggerCall("comment.sendEmailToAdminAfterInsertComment", "after", $comment_srl_list);

		// 	/*
		// 	// send email to author - START
		// 	$oMail = new Mail();
		// 	$mail_title = "[XE - ".Context::get('mid')."] your comment on document: \"".$oDocument->getTitleText()."\" have to be approved";
		// 	$oMail->setTitle($mail_title);
		// 	//$mail_content = sprintf("From : <a href=\"%s?document_srl=%s&comment_srl=%s#comment_%d\">%s?document_srl=%s&comment_srl=%s#comment_%d</a><br/>\r\n%s  ", getFullUrl(''),$comment->document_srl,$comment->comment_srl,$comment->comment_srl, getFullUrl(''),$comment->document_srl,$comment->comment_srl,$comment->comment_srl,$comment>content);
		// 	$mail_content = "
		// 	Your comment #".$obj->comment_srl." on document \"".$oDocument->getTitleText()."\" have to be approved by admin of <strong><i>".  strtoupper($module_info->mid)."</i></strong> module before to be publish.
		// 	<br />
		// 	<br />Comment content:
		// 	".$obj->content."
		// 	<br />
		// 	";
		// 	$oMail->setContent($mail_content);
		// 	$oMail->setSender($obj->email_address, $obj->email_address);
		// 	$oMail->setReceiptor($obj->email_address, $obj->email_address);
		// 	$oMail->send();
		// 	// send email to author - START
		// 	*/
		// 	return;
		// }
	}
}
/* End of file comment.controller.php */