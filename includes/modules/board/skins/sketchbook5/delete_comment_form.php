<?php if ( !defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly.
}

// <include target="_header.html" />
include $skin_path_abs.'_header.php';

if($o_the_comment->is_exists()):?>
<div class="context_data">
	<h3 class="author">
		<!-- <a cond="$oComment->homepage" href="{$oComment->homepage}">{$oComment->getNickName()}</a> -->
		<strong><?php echo esc_html($o_the_comment->get_nick_name())?></strong>
	</h3>
	<?php echo $o_the_comment->get_content(false)?>
</div>
<?php endif?>
<!-- onsubmit="return procFilter(this, delete_comment)" -->
<form action="./" method="get" class="context_message">
	<!-- <input type="hidden" name="mid" value="{$mid}" />
	<input type="hidden" name="page" value="{$page}" />
	<input type="hidden" name="document_srl" value="{$oComment->get('document_srl')}" />
	<input type="hidden" name="comment_srl" value="{$oComment->get('comment_srl')}" /> -->
	<input type="hidden" name="cmd" value="<?php echo X2B_CMD_PROC_DELETE_COMMENT?>" />	
	<input type="hidden" name="board_id" value="<?php echo intval($board_id)?>" />
	<input type="hidden" name="page" value="<?php echo intval($page)?>" />
	<input type="hidden" name="parent_post_id" value="<?php echo $o_the_comment->get('parent_post_id')?>" />
	<input type="hidden" name="comment_id" value="<?php echo $o_the_comment->get('comment_id')?>" />

	<h1><?php echo __('cmd_comment_do', X2B_DOMAIN)?> <?php echo __('cmd_confirm_delete', X2B_DOMAIN)?></h1>
	<div class="btnArea">
		<input class="bd_btn blue" type="submit" value="<?php echo __('cmd_delete', X2B_DOMAIN)?>" />
		<button class="bd_btn" type="button" onclick="history.back()"><?php echo __('cmd_cancel', X2B_DOMAIN)?></button>
	</div>
</form>
<?php
// <include target="_footer.html" />
include $skin_path_abs.'_footer.php';
?>