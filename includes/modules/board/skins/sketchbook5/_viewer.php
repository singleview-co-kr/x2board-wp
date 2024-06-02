<?php if ( !defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly.
}?>

{Context::set('layout','none')}
{Context::set('admin_bar','false')}
{@Context::addHtmlHeader('<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=yes, target-densitydpi=medium-dpi" />')}
<!--@if($mi->viewer_style)-->
{@Context::addBodyClass('viewer')}
<!--@else-->
{@Context::addBodyClass('viewer_blk')}
<!--@end-->

<load target="css/print.css" media="print" />
<load target="js/viewer.js" type="body" />

{@
	$mi->rd_width = '';
	$mi->rd_box = '';
	$mi->rd_style = 'blog';
	$mi->rd_nav = N;
	$mi->rd_nav_side = '';
	$mi->prev_next = '';
	$mi->rd_lst = '';
	$mi->viewer_cmt = 'N';
	if(@!in_array('vote',$mi->viewer_itm)) $mi->votes = 'N';
	if(@!in_array('sns',$mi->viewer_itm)) $mi->to_sns = 'N';
}

<style>
body,input,textarea,select,button,table{font-family:{$_COOKIE['bd_viewer_font']};}
<block cond="@!in_array('fnt',$mi->viewer_itm)">
{@ $mi->show_files = 'N' }
#viewer .rd_trb,#viewer #trackback{display:none}
</block>
<block cond="@in_array('cmt',$mi->viewer_itm)">
{@ $mi->viewer_cmt = '' }
#viewer .rd_nav .comment{display:block}
</block>
</style>

<div id="viewer" class="{$mi->colorset} viewer_style{$mi->viewer_style}<!--@if(!$mi->viewer_style)--> rd_nav_blk<!--@end-->">
	<div id="bd_{$mi->module_srl}_{$oDocument->document_srl}" class="bd clear {$_COOKIE['use_np']} {$mi->fdb_count}<!--@if(!$mi->hover)--> hover_effect<!--@end-->" data-default_style="viewer" data-bdFilesType="{$mi->files_type}" data-bdImgOpt="Y"|cond="$mi->img_opt" data-bdImgLink="Y"|cond="!$mi->img_link && Mobile::isMobileCheckByAgent()" data-bdNavSide="N" style="max-width:{$mi->viewer_width}px">
		<!--// ie8; --><div id="rd_ie" class="ie8_only"><i class="tl"></i><i class="tc"></i><i class="tr"></i><i class="ml"></i><i class="mr"></i><i class="bl"></i><i class="bc"></i><i class="br"></i></div>

		<include target="_read.html" />

		<div cond="!$mi->viewer_lst" id="viewer_lst" class="{$_COOKIE['viewer_lst_cookie']}" style="left:-100px"|cond="$_COOKIE['viewer_lst_cookie']=='open'">
<load target="js/jquery.mousewheel.min.js" type="body" />
<load target="js/jquery.mCustomScrollbar.min.js" type="body" />
<load target="css/jquery.mCustomScrollbar.css" />
			<button type="button" id="viewer_lst_tg" class="ngeb bg_color">{$lang->cmd_list}<br /><span class="tx_open">{$lang->cmd_open}</span><span class="tx_close">{$lang->cmd_close}</span></button>
			<h3 class="ui_font">Articles</h3>
			<div id="viewer_lst_scroll">
				<ul>
					<li loop="$document_list=>$no,$document">
						<a class="clear<!--@if($document_srl==$document->document_srl)--> on<!--@end-->" href="{getUrl('document_srl',$document->document_srl,'listStyle',$listStyle, 'cpage','')}">
							<span cond="$document->thumbnailExists()" class="tmb"><img src="{$document->getThumbnail($mi->thumbnail_width, $mi->thumbnail_height, $mi->thumbnail_type)}" alt="" /></span>
							<span class="tl">{$document->getTitle(80)}<b cond="$document->getCommentCount()">{$document->getCommentCount()}</b></span>
							<span class="meta"><strong>{$document->getNickName()}</strong>{$document->getRegdate("Y.m.d H:i")}</span>
						</a>
					</li>
				</ul>
			</div>
			<div cond="$document_list" id="viewer_pn" class="bd_pg clear">
				<block loop="$page_no=$page_navigation->getNextPage()">
				<strong cond="$page==$page_no" class="this">{$page_no}</strong> 
				<a cond="$page!=$page_no" href="{getUrl('page',$page_no,'division',$division,'last_division',$last_division)}">{$page_no}</a>
				</block>
			</div>
			<button type="button" class="tg_close2" onClick="jQuery('#viewer_lst_tg').click();">X</button>
		</div>

	<include target="_footer.html" />
</div>