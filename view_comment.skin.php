<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<script>
var char_min = parseInt(<? echo $comment_min ?>);
var char_max = parseInt(<? echo $comment_max ?>);
</script>

<? if ($is_comment_write) { //@210403
	if($w == '')
		$w = 'c';
?>
<!-- 댓글 쓰기 시작 { -->
<div id="bo_vc_w" class="board-comment-write">
	<form name="fviewcomment"
      action="./write_comment_update.php"
      onsubmit="return fviewcomment_submit(this);"
      method="post"
      autocomplete="off"
      enctype="multipart/form-data">

		<input type="hidden" name="w" value="<? echo $w ?>" id="w">
		<input type="hidden" name="bo_table" value="<? echo $bo_table ?>">
		<input type="hidden" name="wr_id" value="<? echo $wr_id ?>">
		<input type="hidden" name="comment_id" value="<? echo $c_id ?>" id="comment_id">
		<input type="hidden" name="sca" value="<? echo $sca ?>">
		<input type="hidden" name="sfl" value="<? echo $sfl ?>">
		<input type="hidden" name="stx" value="<? echo $stx ?>">
		<input type="hidden" name="spt" value="<? echo $spt ?>">
		<input type="hidden" name="page" value="<? echo $page ?>">
		<input type="hidden" name="is_good" value="">
		
		<div class="comment-write-wrap">
			<div class="comment-write-main">
				<textarea id="wr_content" name="wr_content" maxlength="10000"
				title="내용"
				<? if ($comment_min || $comment_max) { ?>
				onkeyup="check_byte('wr_content', 'char_count');"
				<? } ?>
><? echo $c_wr_content; ?></textarea>
				<div class="comment-write-bottom">
					<p class="file_box"><span style="display:none;" class="file_del txt-right"><input type="checkbox" name="bf_file_del" id="file_del_<?=$list_item['wr_id']?>" value="1"><label for="file_del_<?=$list_item['wr_id']?>"> 파일삭제</label></span> <input type="file" name="bf_file[]" multiple title="로그등록 : 용량 <?php echo $upload_max_filesize ?> 이하만 업로드 가능" class="frm_file frm_input" /></p>
					<p class="comment-options">
						<span class="emoticon-btn">
							<a href="#" onclick="window.open('/skin/board/fiction/emoticon_list.php', 'emoticon', 'width=400,height=600,scrollbars=yes'); return false;">
								<span class="emoji">이모티콘&nbsp;</span>
							</a>
						</span>
						<input type="checkbox" name="secret" value="secret" id="wr_secret">
						<label for="wr_secret">비밀댓글</label>
					</p>
					<?php if ($is_guest) { ?>
					<p class="comment-guest">
						<input type="text" name="wr_name" id="wr_name"
							value="<?php echo get_cookie("ck_sns_name"); ?>"
							required class="frm_input required" placeholder="이름">
						<input type="password" name="wr_password" id="wr_password"
							required class="frm_input required" placeholder="비밀번호">
					</p>
					<?php } ?>
				</div>
			</div>
			<div class="comment-write-submit">
				<button type="submit" id="btn_submit" class="ui-btn">댓글등록</button>
			</div>
		</div>

<? if ($comment_min || $comment_max) { ?>
<script> check_byte('wr_content', 'char_count'); </script>
<? } ?>


		</div>
		
	</form>
</div>


<div class="board-comment-list theme-box">
	<?
	$cmt_amt = count($list);
	for ($i=0; $i<$cmt_amt; $i++) {
		$comment_id = $list[$i]['wr_id'];
		$cmt_depth = "";
		$cmt_depth = strlen($list[$i]['wr_comment_reply']) * 10;
		$comment = $list[$i]['content'];
		
		$list[$i]['name'] = "<a href='".G5_BBS_URL."/memo_form.php?me_recv_mb_id={$list[$i]['mb_id']}' class='send_memo'>{$list[$i]['wr_name']}</a>";

		$comment = preg_replace("/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i", "<script>doc_write(obj_movie('$1://$2.$3'));</script>", $comment);
		$cmt_sv = $cmt_amt - $i + 1;
	?>
	<? if($i == 0) { ?><hr class="co-line" /><? } ?>
	<div class="item <?=($cmt_depth ? "reply" : "")?>" id="c_<? echo $comment_id ?>" <? if ($cmt_depth) { ?>style="border-left-width: <? echo $cmt_depth ?>px;"<? } ?>>
		<div class="co-name txt-point">
    <span class="co-nick">
        <?php
        $depth = strlen($list[$i]['wr_comment_reply']);
        if ($depth > 0) {
            echo str_repeat('┗', $depth) . ' ';
        }
        ?>
        <?=$list[$i]['name'];?>
    </span>

    <? if (strstr($list[$i]['wr_option'], "secret")) { ?>
        <span class="secret">[비밀댓글]</span>
    <? } ?>
</div>



		<div class="co-content">
			<div class="co-inner">
				<?php
$file_sql = sql_query("
    SELECT *
    FROM {$g5['board_file_table']}
    WHERE bo_table = '{$bo_table}'
      AND wr_id = '{$comment_id}'
    ORDER BY bf_no ASC
    LIMIT 5
");

while ($file = sql_fetch_array($file_sql)) {

    $file_url  = G5_DATA_URL.'/file/'.$bo_table.'/'.$file['bf_file'];
    $file_path = G5_DATA_PATH.'/file/'.$bo_table.'/'.$file['bf_file'];

    if (!is_file($file_path)) continue;
    ?>
    <div class="comment-file">
        <a href="<?= $file_url ?>" target="_blank">
            <img src="<?= $file_url ?>"
                 style="max-width:160px; height:auto; display:block; margin-bottom:6px;">
        </a>
    </div>
    <?php
}
?>


				<?php
$comment_content = $list[$i]['content'];
$comment_content = autolink($comment_content, $bo_table, $stx);
$comment_content = emote_ev($comment_content);
$comment_content = preg_replace(
    '/<img src="(.*?)emoticon\/(.*?)"/',
    '<img src="$1emoticon/$2" style="max-width:160px !important; height:auto !important; width:auto !important; padding:0px 6px 0px 0px !important; margin-bottom:6px !important;"',
    $comment_content
);

$comment_content = html_entity_decode($comment_content);

$files = sql_query(" SELECT * FROM {$g5['board_file_table']} WHERE bo_table = '{$bo_table}' AND wr_id = '{$log_comment['wr_id']}' ORDER BY bf_no ASC LIMIT 5 "); while ($file = sql_fetch_array($files)) { $file_path = G5_DATA_PATH.'/file/'.$bo_table.'/'.$file['bf_file']; if(is_file($file_path)) { ?> <a class="lightbox_trigger" href="<?=G5_DATA_URL.'/file/'.$bo_table.'/'.$file['bf_file']?>"> <img src="<?=G5_DATA_URL.'/file/'.$bo_table.'/'.$file['bf_file']?>"  </a> <?php } } 

echo $comment_content;
?>



			</div>

			<div class="co-info">
				<? if ($is_ip_view) { ?>
					
				<? } ?>
				<span><? echo date('m.d H:i', strtotime($list[$i]['wr_datetime'])) ?></span>
				<? if($list[$i]['is_reply'] || $list[$i]['is_edit'] || $list[$i]['is_del']) {
					$query_string = clean_query_string($_SERVER['QUERY_STRING']);

					if($w == 'cu') {
						$sql = " select wr_id, wr_content, mb_id from $write_table where wr_id = '$c_id' and wr_is_comment = '1' ";
						$cmt = sql_fetch($sql);
						if (!($is_admin || ($member['mb_id'] == $cmt['mb_id'] && $cmt['mb_id'])))
							$cmt['wr_content'] = '';
						$c_wr_content = $cmt['wr_content'];
					}

					$c_reply_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w';
					$c_edit_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w';
				?>
				<? if ($list[$i]['is_reply']) { ?><span><a href="<? echo $c_reply_href;  ?>" onclick="comment_box('<? echo $comment_id ?>', 'c'); return false;">답변</a></span><? } ?>
				<? if ($list[$i]['is_edit']) { ?><span><a href="<? echo $c_edit_href;  ?>" onclick="comment_box('<? echo $comment_id ?>', 'cu'); return false;">수정</a></span><? } ?>
				<? if ($list[$i]['is_del'])  { ?><span><a href="<? echo $list[$i]['del_link'];  ?>" onclick="return comment_delete();">삭제</a></span><? } ?>
				<? } ?>
			</div>

			<span id="edit_<? echo $comment_id ?>"></span><!-- 수정 -->
			<span id="reply_<? echo $comment_id ?>"></span><!-- 답변 -->

			<input type="hidden" value="<? echo strstr($list[$i]['wr_option'],"secret") ?>" id="secret_comment_<? echo $comment_id ?>">
			<textarea id="save_comment_<? echo $comment_id ?>" style="display:none"><? echo get_text($list[$i]['content1'], 0) ?></textarea>
		</div>
	</div>
	<hr class="co-line" />
	<? } ?>

</div>

<? if($i == 0) { ?>
<script>
	$('.board-comment-list').remove();
</script>
<? } ?>


<script>
var save_before = '';
var save_html = document.getElementById('bo_vc_w').innerHTML;

function good_and_write()
{
	var f = document.fviewcomment;
	if (fviewcomment_submit(f)) {
		f.is_good.value = 1;
		f.submit();
	} else {
		f.is_good.value = 0;
	}
}

function fviewcomment_submit(f)
{
	var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자

	f.is_good.value = 0;

	var subject = "";
	var content = "";
	$.ajax({
		url: g5_bbs_url+"/ajax.filter.php",
		type: "POST",
		data: {
			"subject": "",
			"content": f.wr_content.value
		},
		dataType: "json",
		async: false,
		cache: false,
		success: function(data, textStatus) {
			subject = data.subject;
			content = data.content;
		}
	});

	if (content) {
		alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
		f.wr_content.focus();
		return false;
	}

	var pattern = /(^\s*)|(\s*$)/g; // \s 공백 문자
	document.getElementById('wr_content').value = document.getElementById('wr_content').value.replace(pattern, "");
	if (char_min > 0 || char_max > 0)
	{
		check_byte('wr_content', 'char_count');
		var cnt = parseInt(document.getElementById('char_count').innerHTML);
		if (char_min > 0 && char_min > cnt)
		{
			alert("댓글은 "+char_min+"글자 이상 쓰셔야 합니다.");
			return false;
		} else if (char_max > 0 && char_max < cnt)
		{
			alert("댓글은 "+char_max+"글자 이하로 쓰셔야 합니다.");
			return false;
		}
	}
	var content_val = document.getElementById('wr_content').value.replace(pattern, "");
    var file_val = f.elements['bf_file[]'].value;

    if (content_val == "" && file_val == "") {
        alert("내용을 입력하거나 파일을 첨부해 주세요.");
        f.wr_content.focus();
        return false;
    }

	if (typeof(f.wr_name) != 'undefined')
	{
		f.wr_name.value = f.wr_name.value.replace(pattern, "");
		if (f.wr_name.value == '')
		{
			alert('이름이 입력되지 않았습니다.');
			f.wr_name.focus();
			return false;
		}
	}

	if (typeof(f.wr_password) != 'undefined')
	{
		f.wr_password.value = f.wr_password.value.replace(pattern, "");
		if (f.wr_password.value == '')
		{
			alert('비밀번호가 입력되지 않았습니다.');
			f.wr_password.focus();
			return false;
		}
	}
 

	set_comment_token(f);

	document.getElementById("btn_submit").disabled = "disabled";

	return true;
}

function comment_box(comment_id, work)
{
	var el_id;
	if (comment_id)
	{
		if (work == 'c')
			el_id = 'reply_' + comment_id;
		else
			el_id = 'edit_' + comment_id;
	}
	else
		el_id = 'bo_vc_w';

	if (save_before != el_id)
	{
		if (save_before)
		{
			document.getElementById(save_before).style.display = 'none';
			document.getElementById(save_before).innerHTML = '';
		}

		document.getElementById(el_id).style.display = '';
		document.getElementById(el_id).innerHTML = save_html;
		if (work == 'cu')
		{
			document.getElementById('wr_content').value = document.getElementById('save_comment_' + comment_id).value;
			if (typeof char_count != 'undefined')
				check_byte('wr_content', 'char_count');
			if (document.getElementById('secret_comment_'+comment_id).value)
				document.getElementById('wr_secret').checked = true;
			else
				document.getElementById('wr_secret').checked = false;
		}

		document.getElementById('comment_id').value = comment_id;
		document.getElementById('w').value = work;
 

		save_before = el_id;
	}
}

function comment_delete()
{
	return confirm("이 댓글을 삭제하시겠습니까?");
}

comment_box('', 'c'); // 댓글 입력폼이 보이도록 처리하기위해서 추가 (root님)

<? if($board['bo_use_sns'] && ($config['cf_facebook_appid'] || $config['cf_twitter_key'])) { ?>
// sns 등록
$(function() {
	$("#bo_vc_send_sns").load(
		"<? echo G5_SNS_URL; ?>/view_comment_write.sns.skin.php?bo_table=<? echo $bo_table; ?>",
		function() {
			save_html = document.getElementById('bo_vc_w').innerHTML;
		}
	);
});
<? } ?>
</script>
<? } ?>