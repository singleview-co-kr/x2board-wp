<?php
/**
 * @class  boardController
 * @author singleview.co.kr
 * @brief  board module Controller class
 **/
namespace X2board\Includes\Modules\Board;

if ( !defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly.
}

if (!class_exists('\\X2board\\Includes\\Modules\\Board\\boardController')) {

	class boardController extends board
	{
		/**
		 * @brief initialization
		 **/
		function init()	{
			$s_cmd = \X2board\Includes\Classes\Context::get('cmd');
			switch( $s_cmd ) {
				case X2B_CMD_PROC_WRITE_POST:
				case X2B_CMD_PROC_MODIFY_POST:
					$s_cmd = '_'.$s_cmd;
					$this->$s_cmd();
					break;
				default:
					return new BaseObject(-1,'msg_invalid_approach');
					break;
			}
		 }

		/**
		 * @brief insert document
		 **/
		// function procBoardInsertDocument()
		private function _proc_write_post() {
// var_dump($this->module_info);
			// check grant
			if($this->module_info->module != "board") {
				return new \X2board\Includes\Classes\BaseObject(-1, "msg_invalid_request");
			}
			if(!$this->grant->write_post) {
				return new \X2board\Includes\Classes\BaseObject(-1, 'msg_not_permitted');
			}
			// $logged_info = Context::get('logged_info');

			// setup variables
			$obj = \X2board\Includes\Classes\Context::gets('board_id', 'title', 'content', 'password', 'nick_name', 'wordpress_search');
			if(is_null($obj->board_id) || intval($obj->board_id) <= 0) {
				return new \X2board\Includes\Classes\BaseObject(-1,'msg_invalid_request');
			}
// var_dump($_REQUEST); 
// var_dump($obj);
// exit;
			$o_logged_info = \X2board\Includes\Classes\Context::get('logged_info');

			/////////// tmporary test block begin /////////////
			$obj->is_notice = '';
			$obj->post_id = '';//23423;
			$obj->post_author = $o_logged_info->ID;
			$obj->is_secret = '';
			$obj->status = 'PUBLIC'; // PUBLIC SECRET TEMP
			$obj->comment_status = ''; // DENY ALLOW
			$obj->email_address = '';
			$obj->category_id = null;
			
			/////////// tmporary test block end /////////////
			
			// $obj->module_srl = $this->module_srl;
			if($obj->is_notice!='Y'||!$this->grant->manager) {
				$obj->is_notice = 'N';
			}
			// $obj->commentStatus = $obj->comment_status;

			// $oModuleModel = getModel('module');
			// $module_config = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

			/////////// tmporary test block begin /////////////
			$module_config = new \stdClass();
			$module_config->mobile_use_editor = 'Y';
			$module_config->subject_len_count = null;
			/////////// tmporary test block end /////////////

			if($module_config->mobile_use_editor === 'Y') {
				if(!isset($obj->use_editor)) $obj->use_editor = 'Y';
				if(!isset($obj->use_html)) $obj->use_html = 'Y';
			}
			else {
				if(!isset($obj->use_editor)) $obj->use_editor = 'N';
				if(!isset($obj->use_html)) $obj->use_html = 'N';
			}

			settype($obj->title, "string");
			$nAutoSubjectLen = $module_config->subject_len_count ? (int)$module_config->subject_len_count : 20;
			if($obj->title == '') {
				$obj->title = cut_str(trim(strip_tags(nl2br($obj->content))),$nAutoSubjectLen,'...');
			}
			//setup dpcument title tp 'Untitled'
			if($obj->title == '') {
				$obj->title = 'Untitled';
			}

			// unset document style if the user is not the document manager
			if(!$this->grant->manager) {
				unset($obj->title_color);
				unset($obj->title_bold);
			}

			// generate document module model object
			$o_post_model = \X2board\Includes\getModel('post');
			// check if the document is existed
			$o_post = $o_post_model->get_post($obj->post_id, $this->grant->manager);
			unset($o_post_model);

			// update the document if it is existed
			$is_update = false;
			if($o_post->is_exists() && $o_post->post_id == $obj->post_id) {
				$is_update = true;
			}

			// if use anonymous is true
			if($this->module_info->use_anonymous == 'Y') {
				$this->module_info->admin_mail = '';
				$obj->notify_message = 'N';
				if($is_update===false) {
					$obj->post_author = 0;//-1*$o_logged_info->ID;
				}
				// $obj->email_address = $obj->homepage = $obj->user_id = '';
				// $obj->user_name = $obj->nick_name = 'anonymous';
				$obj->email_address = '';
				$obj->nick_name = __('Anonymous', 'x2board'); //'anonymous';
				$bAnonymous = true;
				if($is_update===false) {
					$o_post->add('post_author', $obj->post_author);
				}
			}
			else {
				$bAnonymous = false;
			}
			unset($o_logged_info);

			if($obj->is_secret == 'Y' || strtoupper($obj->status) == 'SECRET') {
				$use_status = explode('|@|', $this->module_info->use_status);
				if(!is_array($use_status) || !in_array('SECRET', $use_status)) {
					unset($obj->is_secret);
					$obj->status = 'PUBLIC';
				}
			}

			// update the document if it is existed
			if($is_update) {
				if(!$o_post->isGranted()) {
					return new \X2board\Includes\Classes\BaseObject(-1,'msg_not_permitted');
				}

				if($this->module_info->use_anonymous == 'Y') {
					$obj->post_author = abs($o_post->get('post_author')) * -1;
					$o_post->add('post_author', $obj->post_author);
				}

				if($this->module_info->protect_content=="Y" && $o_post->get('comment_count')>0 && $this->grant->manager==false) {
					return new \X2board\Includes\Classes\BaseObject(-1,'msg_protect_content');
				}

				if(!$this->grant->manager) {
					// notice & document style same as before if not manager
					$obj->is_notice = $o_post->get('is_notice');
					$obj->title_color = $o_post->get('title_color');
					$obj->title_bold = $o_post->get('title_bold');
				}
				
				// modify list_order if document status is temp
				if($o_post->get('status') == 'TEMP') {
					$obj->last_update = $obj->regdate = date('YmdHis');
					$obj->update_order = $obj->list_order = (getNextSequence() * -1);
				}
				// generate document module의 controller object
				$o_post_controller = \X2board\Includes\getController('post');
				$output = $o_post_controller->updateDocument($o_post, $obj, true);
				unset($o_post_controller);
				$msg_code = 'success_updated';
			} else {  // insert a new post otherwise
				// generate document module의 controller object
				$o_post_controller = \X2board\Includes\getController('post');
				$output = $o_post_controller->insert_post($obj, $bAnonymous);
				unset($o_post_controller);

				$msg_code = 'success_registed';
				$obj->post_id = $output->get('post_id');
// var_dump($output);
				// send an email to admin user
				if($output->toBool() && $this->module_info->admin_mail) {
					// $oModuleModel = getModel('module');
					// $member_config = $oModuleModel->getModuleConfig('member');
					
					// $oMail = new Mail();
					// $oMail->setTitle($obj->title);
					// $oMail->setContent( sprintf("From : <a href=\"%s\">%s</a><br/>\r\n%s", getFullUrl('','post_id',$obj->post_id), getFullUrl('','post_id',$obj->post_id), $obj->content));
					// $oMail->setSender($obj->user_name ? $obj->user_name : 'anonymous', $obj->email_address ? $obj->email_address : $member_config->webmaster_email);

					// $target_mail = explode(',',$this->module_info->admin_mail);
					// for($i=0;$i<count($target_mail);$i++)
					// {
					// 	$email_address = trim($target_mail[$i]);
					// 	if(!$email_address) continue;
					// 	$oMail->setReceiptor($email_address, $email_address);
					// 	$oMail->send();
					// }
				}
			}
			
			// if there is an error
			if(!$output->toBool()) {
				return $output;
			}
// var_dump($output);
// exit;
			// if s_wp_redirect_url is not added, automatically redirect to home_url
			$this->add('s_wp_redirect_url', '?'.X2B_CMD_VIEW_POST.'/'.$output->get('post_id'));
		}

		/**
		 * @brief delete the document
		 **/
		function procBoardDeleteDocument()
		{
			// get the document_srl
			$document_srl = Context::get('document_srl');

			// if the document is not existed
			if(!$document_srl)
			{
				return $this->doError('msg_invalid_document');
			}

			$oDocumentModel = &getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl);
			// check protect content
			if($this->module_info->protect_content=="Y" && $oDocument->get('comment_count')>0 && $this->grant->manager==false)
			{
				return new BaseObject(-1, 'msg_protect_content');
			}

			// generate document module controller object
			$oDocumentController = getController('document');

			// delete the document
			$output = $oDocumentController->deleteDocument($document_srl, $this->grant->manager);
			if(!$output->toBool())
			{
				return $output;
			}

			// alert an message
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '', 'page', Context::get('page'), 'document_srl', ''));
			$this->add('mid', Context::get('mid'));
			$this->add('page', Context::get('page'));
			if(Context::get('xeVirtualRequestMethod') !== 'xml')
			{
				$this->setMessage('success_deleted');
			}
		}

		/**
		 * @brief vote
		 **/
		function procBoardVoteDocument()
		{
			// generate document module controller object
			$oDocumentController = getController('document');

			$document_srl = Context::get('document_srl');
			return $oDocumentController->updateVotedCount($document_srl);
		}

		/**
		 * @brief insert comments
		 **/
		function procBoardInsertComment()
		{
			// check grant
			if(!$this->grant->write_comment)
			{
				return new BaseObject(-1, 'msg_not_permitted');
			}
			$logged_info = Context::get('logged_info');

			// get the relevant data for inserting comment
			$obj = Context::getRequestVars();
			$obj->module_srl = $this->module_srl;

			if(!$this->module_info->use_status) $this->module_info->use_status = 'PUBLIC';
			if(!is_array($this->module_info->use_status))
			{
				$this->module_info->use_status = explode('|@|', $this->module_info->use_status);
			}

			if(in_array('SECRET', $this->module_info->use_status))
			{
				$this->module_info->secret = 'Y';
			}
			else
			{
				unset($obj->is_secret);
				$this->module_info->secret = 'N';
			}

			$oModuleModel = getModel('module');
			$module_config = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);
			if($module_config->mobile_use_editor === 'Y')
			{
				if(!isset($obj->use_editor)) $obj->use_editor = 'Y';
				if(!isset($obj->use_html)) $obj->use_html = 'Y';
			}
			else
			{
				if(!isset($obj->use_editor)) $obj->use_editor = 'N';
				if(!isset($obj->use_html)) $obj->use_html = 'N';
			}

			// check if the doument is existed
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($obj->document_srl);
			if(!$oDocument->isExists())
			{
				return new BaseObject(-1,'msg_not_founded');
			}

			// For anonymous use, remove writer's information and notifying information
			if($this->module_info->use_anonymous == 'Y')
			{
				$this->module_info->admin_mail = '';
				$obj->notify_message = 'N';
				$obj->member_srl = -1*$logged_info->member_srl;
				$obj->email_address = $obj->homepage = $obj->user_id = '';
				$obj->user_name = $obj->nick_name = 'anonymous';
				$bAnonymous = true;
			}
			else
			{
				$bAnonymous = false;
			}

			// generate comment  module model object
			$oCommentModel = getModel('comment');

			// generate comment module controller object
			$oCommentController = getController('comment');

			// check the comment is existed
			// if the comment is not existed, then generate a new sequence
			if(!$obj->comment_srl)
			{
				$obj->comment_srl = getNextSequence();
			} else {
				$comment = $oCommentModel->getComment($obj->comment_srl, $this->grant->manager);
			}

			// if comment_srl is not existed, then insert the comment
			if($comment->comment_srl != $obj->comment_srl)
			{

				// parent_srl is existed
				if($obj->parent_srl)
				{
					$parent_comment = $oCommentModel->getComment($obj->parent_srl);
					if(!$parent_comment->comment_srl)
					{
						return new BaseObject(-1, 'msg_invalid_request');
					}

					$output = $oCommentController->insertComment($obj, $bAnonymous);

				// parent_srl is not existed
				} else {
					$output = $oCommentController->insertComment($obj, $bAnonymous);
				}
			// update the comment if it is not existed
			} else {
				// check the grant
				if(!$comment->isGranted())
				{
					return new BaseObject(-1,'msg_not_permitted');
				}

				$obj->parent_srl = $comment->parent_srl;
				$output = $oCommentController->updateComment($obj, $this->grant->manager);
				$comment_srl = $obj->comment_srl;
			}

			if(!$output->toBool())
			{
				return $output;
			}

			if(Context::get('xeVirtualRequestMethod') !== 'xml')
			{
				$this->setMessage('success_registed');
			}
			$this->add('mid', Context::get('mid'));
			$this->add('document_srl', $obj->document_srl);
			$this->add('comment_srl', $obj->comment_srl);
		}

		/**
		 * @brief delete the comment
		 **/
		function procBoardDeleteComment()
		{
			// get the comment_srl
			$comment_srl = Context::get('comment_srl');
			if(!$comment_srl)
			{
				return $this->doError('msg_invalid_request');
			}

			// generate comment  controller object
			$oCommentController = getController('comment');

			$output = $oCommentController->deleteComment($comment_srl, $this->grant->manager);
			if(!$output->toBool())
			{
				return $output;
			}

			$this->add('mid', Context::get('mid'));
			$this->add('page', Context::get('page'));
			$this->add('document_srl', $output->get('document_srl'));
			if(Context::get('xeVirtualRequestMethod') !== 'xml')
			{
				$this->setMessage('success_deleted');
			}
		}

		/**
		 * @brief check the password for document and comment
		 **/
		function procBoardVerificationPassword()
		{
			// get the id number of the document and the comment
			$password = Context::get('password');
			$document_srl = Context::get('document_srl');
			$comment_srl = Context::get('comment_srl');

			$oMemberModel = getModel('member');

			// if the comment exists
			if($comment_srl)
			{
				// get the comment information
				$oCommentModel = getModel('comment');
				$oComment = $oCommentModel->getComment($comment_srl);
				if(!$oComment->isExists())
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}

				// compare the comment password and the user input password
				if(!$oMemberModel->isValidPassword($oComment->get('password'),$password))
				{
					return new BaseObject(-1, 'msg_invalid_password');
				}

				$oComment->setGrant();
			} else {
				// get the document information
				$oDocumentModel = getModel('document');
				$oDocument = $oDocumentModel->getDocument($document_srl);
				if(!$oDocument->isExists())
				{
					return new BaseObject(-1, 'msg_invalid_request');
				}

				// compare the document password and the user input password
				if(!$oMemberModel->isValidPassword($oDocument->get('password'),$password))
				{
					return new BaseObject(-1, 'msg_invalid_password');
				}

				$oDocument->setGrant();
			}
		}
	}
}