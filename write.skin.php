<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

$setting = sql_fetch("SELECT * FROM {$write_table} WHERE wr_type = 'setting' ORDER BY wr_id desc LIMIT 1");
// 작성 모드: mode 파라미터 또는 기존 글의 wr_type으로 결정
$write_mode = isset($_GET['mode']) ? trim($_GET['mode']) : '';
if (!in_array($write_mode, array('challenge', 'log'))) $write_mode = 'challenge'; // 기본값: 챌린지
// 수정 시: 기존 글의 wr_type으로 초기값 설정
if ($w == 'u' && isset($write['wr_type'])) {
    if ($write['wr_type'] == 'challenge') $write_mode = 'challenge';
    else if (in_array($write['wr_type'], array('log', ''))) $write_mode = 'log';
}
$is_challenge_post = ($write_mode == 'challenge'); // 편의 변수

$wr_date_value = isset($_GET['date']) ? trim($_GET['date']) : '';
if ($wr_date_value == '' && isset($write['wr_date']) && $write['wr_date'] != '') $wr_date_value = $write['wr_date'];
if ($wr_date_value == '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $wr_date_value)) $wr_date_value = date('Y-m-d');
$checklist_result = sql_query("SELECT wr_id, wr_content FROM {$write_table} WHERE wr_type = 'checklist' ORDER BY wr_datetime ASC");
$dones = array_map('trim', explode(',', isset($write['wr_done']) ? $write['wr_done'] : ''));
?>


<hr class="padding">
<section id="bo_w" <?if($board['bo_table_width']>0){?>style="max-width:<?=$board['bo_table_width']?><?=$board['bo_table_width']>100 ? "px":"%"?>;margin:0 auto;"<?}?>>
	<!-- 게시물 작성/수정 시작 { -->
	<form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
	<input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
	<input type="hidden" name="w" value="<?php echo $w ?>">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
	<input type="hidden" name="sca" value="<?php echo $sca ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="spt" value="<?php echo $spt ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<!-- wr_type은 JS가 챌린지 체크박스 상태에 따라 동적으로 설정 -->
	<input type="hidden" name="wr_type" id="wr_type_hidden" value="<?php echo $is_challenge_post ? 'challenge' : 'log'; ?>">
	<input type="hidden" name="wr_done" id="wr_done" value="<?php echo isset($write['wr_done']) ? $write['wr_done'] : ''; ?>">
	<?php
	$option = '';
	$option_hidden = '';
	if ($is_notice || $is_html || $is_secret || $is_mail) {
		$option = '';
		if ($is_notice) {
			$option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">공지</label>';
		}
		if ($is_html) {
			if ($is_dhtml_editor) {
				$option_hidden .= '<input type="hidden" value="html1" name="html">';
			} else {
				$option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
			}
		}
		if ($is_secret) {
			if ($is_admin || $is_secret==1) {
				if($secret_checked) $sec_select="selected";
				$sec .='<option value="secret" '.$sec_select.'>비밀글</option>';
			} else {
				$option_hidden .= '<input type="hidden" name="secret" value="secret">';
			}
		}
		if ($is_mail) {
			$option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">답변메일받기</label>';
		}
	}
	echo $option_hidden;
	if($write['wr_secret']=='1') $mem_select="selected";
	if($write['wr_protect']!='') $pro_select="selected";
	if($is_member) {
		$sec .='<option value="protect" '.$pro_select.'>보호글</option>';
		$sec .='<option value="member"  '.$mem_select.'>멤버공개</option>';
	}
	?>

	<div class="board-write theme-box">
	<?php if ($is_category) { ?>
	<dl>
		<dt>카테고리</dt>
		<dd><nav id="write_category">
			<select name="ca_name" id="ca_name" required class="required">
				<option value="">선택하세요</option>
				<?php echo $category_option ?>
			</select>
		</nav></dd>
	</dl>
	<?php } ?>
	<dl>
		<dt>공개 설정</dt>
		<dd>
		<?php if($is_secret!=2||$is_admin){ ?>
		<select name="set_secret" id="set_secret">
			<option value="">전체공개</option>
			<?=$sec?>
		</select>
		<?php } ?>
		<?php echo $option ?></dd>
	</dl>
	<dl id="set_protect" style="display:<?=$w=='u' && $pro_select ? 'block':'none'?>;">
		<dt><label for="wr_protect">보호글 암호</label></dt>
		<dd><input type="text" name="wr_protect" id="wr_protect" value="<?=$write['wr_protect']?>" maxlength="20"></dd>
	</dl>
	<dl>
		<dt>제목</dt>
		<dd>
			<input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject"
				required class="frm_input required full" size="50" maxlength="255">
		</dd>
	</dl>

	<? if($board['bo_1']) { ?>
	<div class="write-notice"><?=$board['bo_1']?></div>
	<? } ?>

	<dl>
		<dt>부제목</dt>
		<dd>
			<input type="text" name="wr_1" id="wr_1" value="<?=get_text($write['wr_1'])?>"
				class="frm_input full" size="50" maxlength="255">
		</dd>
	</dl>

	<dl>
		<dt>구분</dt>
		<dd>
			<label>
				<input type="checkbox" id="is_challenge_chk" name="is_challenge" value="1"
				       <?php echo $is_challenge_post ? 'checked' : ''; ?>>
				챌린지 게시물
			</label>
			<small style="color:#999; font-size:12px; margin-left:8px;">체크 시 일일목표 달성 여부를 기록합니다.</small>
		</dd>
	</dl>

	<div id="challenge-fields" style="display:<?php echo $is_challenge_post ? 'block' : 'none'; ?>;">
	<dl>
		<dt>기록일</dt>
		<dd><input type="text" name="wr_date" id="wr_date" value="<?php echo $wr_date_value; ?>"
			class="frm_input full" maxlength="10" placeholder="YYYY-MM-DD"></dd>
	</dl>
	<dl>
		<dt>일일 목표</dt>
		<dd>
			<div class="write-goal-list">
				<?php while ($goal_row = sql_fetch_array($checklist_result)) {
					$goal_id      = $goal_row['wr_id'];
					$goal_content = trim($goal_row['wr_content']);
					$is_done      = in_array((string)$goal_id, $dones);
				?>
				<div class="goal-item<?php echo $is_done ? ' done' : ''; ?>" data-wr-id="<?php echo $goal_id; ?>">
					<span class="goal-checkbox"></span>
					<span class="goal-text"><?php echo htmlspecialchars($goal_content, ENT_QUOTES, 'UTF-8'); ?></span>
				</div>
				<?php } ?>
			</div>
		</dd>
	</dl>
	</div>

	<dl>
		<dt>내용</dt>
		<dd>
			<div class="wr_content">
				<?php if($write_min || $write_max) { ?>
				<p id="char_count_desc" style="margin-bottom:7px;">이 게시판은 최소 <strong><?php echo $write_min; ?></strong>자 이상, 최대 <strong><?php echo $write_max; ?></strong>자 이하까지 쓰실 수 있습니다.</p>
				<?php } ?>
				<?php echo $editor_html; ?>
			</div>
			<!-- ★ 이모티콘 버튼 (에디터 아래) -->
			<?php if (!$board['bo_use_dhtml_editor']) { ?>
			<div class="write-emoticon-toolbar" style="margin-top:6px;">
				<a href="#" class="emoticon-btn-write"
				   onclick="window.open('/skin/board/fiction/emoticon_list.php?target_id=wr_content', 'emoticon', 'width=400,height=600,scrollbars=yes'); return false;"
				   style="font-size:13px; opacity:.75; text-decoration:none;">
					&#128512; 이모티콘
				</a>
			</div>
			<?php } ?>
		</dd>
	</dl>

	<?php if(!$board['bo_use_dhtml_editor']){ ?>
	<dl>
		<dt>FILES</dt>
		<dd>
		<?php for($i=0;$i<$board['bo_upload_count'];$i++){ ?>
			<input type="file" name="bf_file[]"
				title="파일첨부 <?php echo $i+1 ?> : 용량 <?php echo $upload_max_filesize ?> 이하만 업로드 가능"
				class="frm_file frm_input full">
			<?php if ($is_file_content) { ?>
			<input type="text" name="bf_content[]" value="<?php echo ($w == 'u') ? $file[$i]['bf_content'] : ''; ?>"
				title="파일 설명을 입력해주세요." class="frm_file frm_input" size="50">
			<?php } ?>
			<?php if($w == 'u' && $file[$i]['file']) { ?>
			<input type="checkbox" id="bf_file_del<?php echo $i ?>" name="bf_file_del[<?php echo $i; ?>]" value="1">
			<label for="bf_file_del<?php echo $i ?>"><?php echo $file[$i]['source'].'('.$file[$i]['size'].')'; ?> 파일 삭제</label>
			<?php } ?>
		<?php } ?>
		</dd>
	</dl>
	<?php } ?>

	<?php if(!$is_member){ ?>
	<dl>
		<dt></dt>
		<dd class="txt-right">
		<?php if ($is_name) { ?>
			<label for="wr_name">NAME<strong class="sound_only">필수</strong></label>
			<input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input required">
		<?php } ?>
		<?php if ($is_password) { ?>
			&nbsp;&nbsp;
			<label for="wr_password">PASSWORD<strong class="sound_only">필수</strong></label>
			<input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input <?php echo $password_required ?>">
		<?php } ?>
		</dd>
	</dl>
	<?php } ?>

	</div>

	<hr class="padding" />
	<div class="btn_confirm txt-center">
		<input type="button" value="임시저장" id="btn-temp-save" class="btn_submit ui-btn point">
		<a href="#" id="btn-temp-list" class="ui-btn">
			임시 보관함 <span id="temp-count"></span>
		</a>
		<input type="submit" value="작성완료" id="btn_submit" accesskey="s" class="btn_submit ui-btn point">
		<a href="./board.php?bo_table=<?php echo $bo_table ?>" class="btn_cancel ui-btn">취소</a>
	</div>
	</form>

	<script>
	<?php if($write_min || $write_max) { ?>
	var char_min = parseInt(<?php echo $write_min; ?>);
	var char_max = parseInt(<?php echo $write_max; ?>);
	check_byte("wr_content", "char_count");
	$(function() {
		$("#wr_content").on("keyup", function() { check_byte("wr_content", "char_count"); });
	});
	<?php } ?>

	function html_auto_br(obj) {
		if (obj.checked) {
			result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
			if (result) obj.value = "html2";
			else        obj.value = "html1";
		} else {
			obj.value = "";
		}
	}

	function fwrite_submit(f) {
		<?php echo $editor_js; ?>
		// 챌린지 게시물일 때만 기록일 유효성 검사
		if ($('#is_challenge_chk').is(':checked')) {
			updateWrDoneField();
			if (!/^\d{4}-\d{2}-\d{2}$/.test($.trim($('#wr_date').val()))) {
				alert('기록일 형식은 YYYY-MM-DD 입니다.');
				$('#wr_date').focus();
				return false;
			}
		}

		var subject = "", content = "";
		$.ajax({
			url: g5_bbs_url+"/ajax.filter.php", type: "POST",
			data: { "subject": f.wr_subject.value, "content": f.wr_content.value },
			dataType: "json", async: false, cache: false,
			success: function(data) { subject = data.subject; content = data.content; }
		});

		if (subject) { alert("제목에 금지단어('"+subject+"')가 포함되어있습니다"); f.wr_subject.focus(); return false; }
		if (content) {
			alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
			if (typeof(ed_wr_content) != "undefined") ed_wr_content.returnFalse();
			else f.wr_content.focus();
			return false;
		}

		if (document.getElementById("char_count")) {
			if (char_min > 0 || char_max > 0) {
				var cnt = parseInt(check_byte("wr_content", "char_count"));
				if (char_min > 0 && char_min > cnt) { alert("내용은 "+char_min+"글자 이상 쓰셔야 합니다."); return false; }
				if (char_max > 0 && char_max < cnt) { alert("내용은 "+char_max+"글자 이하로 쓰셔야 합니다."); return false; }
			}
		}

		document.getElementById("btn_submit").disabled = "disabled";
		return true;
	}

	$('#set_secret').on('change', function() {
		var selection = $(this).val();
		if (selection=='protect') $('#set_protect').css('display','block');
		else { $('#set_protect').css('display','none'); $('#wr_protect').val(''); }
	});

	console.log('g5_bbs_url =', typeof g5_bbs_url !== 'undefined' ? g5_bbs_url : 'UNDEFINED');

	// 챌린지 체크박스 토글
	$('#is_challenge_chk').on('change', function() {
		if ($(this).is(':checked')) {
			$('#challenge-fields').slideDown(150);
			$('#wr_type_hidden').val('challenge');
		} else {
			$('#challenge-fields').slideUp(150);
			$('#wr_type_hidden').val('log');
		}
	});

	function updateWrDoneField() {
		var doneIds = [];
		$('.goal-item.done').each(function(){ doneIds.push($(this).data('wr-id')); });
		$('#wr_done').val(doneIds.join(','));
	}
	$(document).on('click', '.goal-item', function() {
		$(this).toggleClass('done');
		updateWrDoneField();
	});
	</script>
</section>

<!-- 임시저장 레이어 (기존 코드 그대로 유지) -->
<div id="temp-layer" style="
    display:none; position:fixed; top:50%; left:50%;
    transform:translate(-50%,-50%);
    background:#000; border:1px solid #ccc;
    padding:15px; z-index:9999;
    width:400px; max-height:500px; overflow-y:auto;">
    <div id="temp-list"></div>
    <button type="button" id="btn-temp-delete">선택 임시글 삭제</button>
</div>

<script>
/* 임시저장 스크립트 — 기존 코드 그대로 */
$(function() {
    function bearSyncIfPossible() {
        try {
            if (typeof window !== 'undefined' && typeof window.bearEditorSync_wr_content === 'function') {
                window.bearEditorSync_wr_content();
                return true;
            }
        } catch (e) {}
        return false;
    }
    function getEditorContent() {
        if (bearSyncIfPossible() && $('#wr_content').length) return $('#wr_content').val();
        if (document.getElementById('wr_content_editor')) return document.getElementById('wr_content_editor').innerHTML;
        if (typeof oEditors !== 'undefined' && oEditors.getById && oEditors.getById["wr_content"]) return oEditors.getById["wr_content"].getIR();
        if (typeof ed_wr_content !== 'undefined' && ed_wr_content && typeof ed_wr_content.getData === 'function') return ed_wr_content.getData();
        if ($('#wr_content').length) return $('#wr_content').val();
        return '';
    }
    function setEditorContent(content) {
        content = (content || '');
        if (document.getElementById('wr_content_editor')) document.getElementById('wr_content_editor').innerHTML = content;
        if ($('#wr_content').length) $('#wr_content').val(content);
        try {
            if (document.getElementById('wr_content_editor'))
                document.getElementById('wr_content_editor').dispatchEvent(new Event('input', { bubbles: true }));
        } catch (e) {}
        try {
            if (typeof oEditors !== 'undefined' && oEditors.getById && oEditors.getById["wr_content"])
                oEditors.getById["wr_content"].exec("SET_CONTENTS", [content]);
            else if (typeof ed_wr_content !== 'undefined' && ed_wr_content && typeof ed_wr_content.setData === 'function')
                ed_wr_content.setData(content);
        } catch (e) {}
    }
    function updateTempCount() {
        $.get(g5_bbs_url + '/write_temp_count.php', { bo_table: '<?php echo $bo_table ?>' }, function(cnt) {
            $('#temp-count').text(cnt);
        });
    }
    $('#btn-temp-save').on('click', function () {
        $.post(g5_bbs_url + '/write_temp_save.php', {
            bo_table: '<?php echo $bo_table ?>',
            subject: $('#wr_subject').val(),
            subtitle: $('#wr_1').val(),
            content: (function(){
                try { if (typeof window.bearEditorSync_wr_content === 'function') window.bearEditorSync_wr_content(); } catch (e) {}
                if ($('#wr_content').length) return $('#wr_content').val();
                var ed = document.getElementById('wr_content_editor');
                if (ed) return ed.innerHTML;
                return '';
            })()
        }, function(res) {
            if (typeof res === 'string' && $.trim(res) === 'OK') { alert('임시 저장 완료'); if (typeof updateTempCount === 'function') updateTempCount(); return; }
            if (typeof res === 'string') { try { res = JSON.parse(res); } catch(e) {} }
            if (res && res.success) { alert('임시 저장 완료'); if (typeof updateTempCount === 'function') updateTempCount(); }
            else if (res && res.message) { alert('임시 저장 실패: ' + res.message); }
            else { alert('임시 저장 실패(응답 확인 필요)'); console.log(res); }
        }).fail(function(xhr) { alert('임시 저장 실패! 서버 확인 필요'); console.log(xhr.status, xhr.responseText); });
    });
    $('#btn-temp-list').on('click', function() {
        $.getJSON(g5_bbs_url + '/write_temp_list.php', { bo_table: '<?php echo $bo_table ?>' }, function(list) {
            let html = `<button type="button" id="btn-temp-close" style="float:right;width:30px;background:#000;font-size:14px;color:#fff;border:none;cursor:pointer;">✖</button><h3 style="color:#fff;margin-bottom:15px;">임시 보관함</h3><label style="color:#eee;"><input type="checkbox" id="temp-select-all"> 전체선택</label><br><br>`;
            (list || []).forEach(v => {
                let date = v.datetime ? v.datetime.substr(0,16) : '';
                let tempDiv = document.createElement("div");
                tempDiv.innerHTML = (v.content || "").replace(/<(p|div|br)[^>]*>/gi, '\n');
                let pureText = (tempDiv.textContent || tempDiv.innerText || "").replace(/\xa0/g,' ').trim();
                html += `<div class="temp-item" data-id="${v.id}" style="padding:10px 5px;border-bottom:1px solid #444;cursor:pointer;position:relative;color:#fff;"><strong style="display:block;font-size:14px;padding-right:30px;">${v.subject||'제목 없음'}</strong>${v.subtitle?`<span style="display:block;font-size:13px;font-weight:bold;color:#ddd;margin-top:4px;">${v.subtitle}</span>`:''}<small style="color:#888;font-size:11px;display:block;margin-top:2px;">${date}</small><span style="display:inline-block;margin-top:5px;font-size:11px;color:#999;">약 ${pureText.replace(/\s/g,'').length}자 / ${pureText.length}자 (공미포/공포)</span><input type="checkbox" class="temp-check" data-id="${v.id}" style="position:absolute;top:12px;right:5px;"></div>`;
            });
            html += '<br><button type="button" id="btn-temp-delete" style="width:100px;padding:5px;background:#333;font-size:12px;color:#fff;border:none;cursor:pointer;">선택 임시글 삭제</button>';
            $('#temp-layer').html(html).show();
            $('#temp-select-all').on('change', function() { $('.temp-check').prop('checked', $(this).prop('checked')); });
        }).fail(function(xhr){ alert('임시 보관함 불러오기 실패'); console.log(xhr.status, xhr.responseText); });
        return false;
    });
    $(document).on('click', '.temp-item', function(e) {
        if ($(e.target).hasClass('temp-check')) return;
        const id = $(this).data('id');
        $.getJSON(g5_bbs_url + '/write_temp_load.php', { bo_table: '<?php echo $bo_table ?>', id: id, t: new Date().getTime() }, function(v) {
            $('#wr_subject').val(v.subject);
            $('#wr_1').val(v.subtitle);
            setEditorContent(v.content);
            $('#temp-layer').hide();
        }).fail(function(xhr){ alert('임시글 불러오기 실패'); console.log(xhr.status, xhr.responseText); });
    });
    $(document).on('click', '#btn-temp-delete', function() {
        let ids = [];
        $('.temp-check:checked').each(function() { ids.push($(this).data('id')); });
        if(ids.length===0){ alert('삭제할 글을 선택하세요.'); return; }
        if(!confirm('정말 삭제하시겠습니까?')){ return; }
        let pending = ids.length;
        ids.forEach(id => {
            $.post(g5_bbs_url+'/write_temp_delete.php', { bo_table:'<?php echo $bo_table ?>', id:id }).always(function(){
                pending--;
                if(pending===0){ alert('삭제 완료'); $('#temp-layer').hide(); updateTempCount(); }
            });
        });
    });
    $(document).on('click', '#btn-temp-close', function() { $('#temp-layer').hide(); });
    updateTempCount();
});
</script>
