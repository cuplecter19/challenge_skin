<?
// list.skin.php
if (!defined('_GNUBOARD_')) exit;
$colspan = 5;
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);

$category_option = get_category_option($bo_table, $sca);
if (!preg_match('/^[A-Za-z0-9_]+$/', $write_table)) exit;

$temp = sql_fetch("select * from {$write_table} limit 1");
if (!isset($temp['wr_type'])) {
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_type` varchar(20) NOT NULL DEFAULT '' AFTER `wr_10` ");
}
if (!isset($temp['wr_date'])) {
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_date` varchar(10) NOT NULL DEFAULT '' AFTER `wr_subject` ");
}
if (!isset($temp['wr_done'])) {
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_done` text NOT NULL AFTER `wr_date` ");
}
if (!isset($temp['wr_done_rate'])) {
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_done_rate` tinyint NOT NULL DEFAULT 0 AFTER `wr_done` ");
}
unset($temp);

$setting = sql_fetch("SELECT * FROM {$write_table} WHERE wr_type = 'setting' ORDER BY wr_id desc LIMIT 1");

// 표시 모드: 'challenge' 또는 'log'
$view_mode = isset($_GET['mode']) ? trim($_GET['mode']) : 'challenge';
if (!in_array($view_mode, array('challenge', 'log'))) $view_mode = 'challenge';

// challenge 모드에서만 date 필터 사용
$date = 'all';
if ($view_mode == 'challenge') {
	$date = isset($_GET['date']) ? trim($_GET['date']) : '';
	if ($date == '') $date = 'all';
	if ($date != 'all' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
}

/* 달성일/연속일 */
$date_list = array();
$date_set = array();
$streak = 0;
if ($view_mode == 'challenge') {
	$res_dates = sql_query("
		SELECT DISTINCT wr_date
		FROM {$write_table}
		WHERE wr_is_comment = 0
		  AND wr_date <> ''
		  AND wr_type = 'challenge'
		  AND wr_done_rate >= 100
		ORDER BY wr_date DESC
	");
	while ($row = sql_fetch_array($res_dates)) {
		$date_list[] = $row['wr_date'];
	}
	$date_set = array_flip($date_list);

	$today = date('Y-m-d');
	$yesterday = date('Y-m-d', strtotime('-1 day'));

	if (isset($date_set[$today])) {
		$streak = 0;
		$check_day = $today;
		while (isset($date_set[$check_day])) {
			$streak++;
			$check_day = date('Y-m-d', strtotime($check_day . ' -1 day'));
		}
	} else if (isset($date_set[$yesterday])) {
		$streak = 0;
		$check_day = $yesterday;
		while (isset($date_set[$check_day])) {
			$streak++;
			$check_day = date('Y-m-d', strtotime($check_day . ' -1 day'));
		}
	} else {
		$streak = 0;
	}
}

/* ===== 도장 이미지 URL 로딩 ===== */
$stamp_urls = array(30 => '', 60 => '', 100 => '');

function _load_stamp_urls($bo_table, $wr_id, $g5) {
	$urls = array(30 => '', 60 => '', 100 => '');
	if ((int)$wr_id <= 0) return $urls;

	$file_res = sql_query("
		SELECT bf_no, bf_file
		FROM {$g5['board_file_table']}
		WHERE bo_table = '".sql_real_escape_string($bo_table)."'
		  AND wr_id = '".(int)$wr_id."'
		  AND bf_file <> ''
		ORDER BY bf_no ASC
		LIMIT 10
	");
	while ($f = sql_fetch_array($file_res)) {
		$no = (int)$f['bf_no'];
		$url = G5_DATA_URL.'/file/'.$bo_table.'/'.$f['bf_file'];
		if ($no === 0) $urls[30]  = $url;
		if ($no === 1) $urls[60]  = $url;
		if ($no === 2) $urls[100] = $url;
	}
	return $urls;
}

$stamp_setting_wr_id = (isset($setting['wr_id']) && (int)$setting['wr_id'] > 0) ? (int)$setting['wr_id'] : 0;

if ($stamp_setting_wr_id > 0) {
	$stamp_urls = _load_stamp_urls($bo_table, $stamp_setting_wr_id, $g5);
}

if ($stamp_urls[30] == '' && $stamp_urls[60] == '' && $stamp_urls[100] == '') {
	$alt = sql_fetch("
		SELECT bf.wr_id AS wr_id
		FROM {$g5['board_file_table']} bf
		INNER JOIN {$write_table} w ON w.wr_id = bf.wr_id
		WHERE bf.bo_table = '".sql_real_escape_string($bo_table)."'
		  AND w.wr_type = 'setting'
		  AND bf.bf_file <> ''
		ORDER BY bf.wr_id DESC
		LIMIT 1
	");
	if (isset($alt['wr_id']) && (int)$alt['wr_id'] > 0) {
		$stamp_urls = _load_stamp_urls($bo_table, (int)$alt['wr_id'], $g5);
	}
}

/* 일일목표 */
$checklist_result = sql_query("SELECT wr_id, wr_content FROM {$write_table} WHERE wr_type = 'checklist' ORDER BY wr_datetime ASC");
$checklist_rows_for_display = array();
while ($goal_tmp = sql_fetch_array($checklist_result)) {
	$checklist_rows_for_display[] = $goal_tmp;
}

$done_map = array();
if ($view_mode == 'challenge' && $date != 'all') {
	$done_res = sql_query("
		SELECT wr_done
		FROM {$write_table}
		WHERE wr_is_comment = 0
		  AND wr_date = '".sql_real_escape_string($date)."'
		  AND wr_type = 'challenge'
	");
	while ($done_row = sql_fetch_array($done_res)) {
		$done_ids = explode(',', $done_row['wr_done']);
		for ($d = 0; $d < count($done_ids); $d++) {
			$done_id = trim($done_ids[$d]);
			if ($done_id !== '') $done_map[$done_id] = true;
		}
	}
}

/* ===================================================================
 * 모드별 목록 및 페이지네이션 재계산
 * =================================================================== */
$_need_repaginate = true;
$_filter_type = '';
$_filter_date = '';

if ($view_mode == 'challenge' && $date != 'all') {
	$_filter_type = 'challenge';
	$_filter_date = $date;
} else if ($view_mode == 'challenge') {
	$_filter_type = 'challenge';
} else {
	$_filter_type = 'log';
}

if ($_need_repaginate) {
	// 아보카도 에디션 관리자 설정 필드: bo_page_rows (페이지당 목록 수)
	$_rows_per_page = max(1, (int)$board['bo_page_rows']);
	$_current_page  = max(1, (int)$page);
	$_offset        = ($_current_page - 1) * $_rows_per_page;

	$_where = "wr_is_comment = 0 AND wr_type = '".sql_real_escape_string($_filter_type)."'";
	if ($_filter_date != '') {
		$_where .= " AND wr_date = '".sql_real_escape_string($_filter_date)."'";
	}

	$_cnt_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE {$_where}");
	$_filtered_total = (int)$_cnt_row['cnt'];

	$_fres = sql_query("
		SELECT *
		FROM {$write_table}
		WHERE {$_where}
		ORDER BY wr_num ASC, wr_reply ASC
		LIMIT ".intval($_offset).", ".intval($_rows_per_page)."
	");
	$_new_list = array();
	while ($_frow = sql_fetch_array($_fres)) {
		$_frow['is_notice']   = (!empty($_frow['wr_is_notice']) && (int)$_frow['wr_is_notice'] > 0);
		$_frow['href']        = G5_BBS_URL.'/board.php?bo_table='.urlencode($bo_table)
		                        .'&wr_id='.(int)$_frow['wr_id'];
		$_frow['subject']     = htmlspecialchars($_frow['wr_subject'], ENT_QUOTES, 'UTF-8');
		$_frow['name']        = isset($_frow['wr_name'])
		                        ? htmlspecialchars($_frow['wr_name'], ENT_QUOTES, 'UTF-8') : '';
		$_frow['comment_cnt'] = isset($_frow['wr_comment']) ? (int)$_frow['wr_comment'] : 0;
		if (!isset($_frow['wr_secret']))  $_frow['wr_secret']  = 0;
		if (!isset($_frow['wr_protect'])) $_frow['wr_protect'] = '';
		$_new_list[] = $_frow;
	}
	$list = $_new_list;

	// 페이지 그룹 크기: bo_page_count 값을 사용하되, 최소 10 보장
	$_page_count  = max(10, (int)$board['bo_page_count']);
	$_total_pages = max(1, (int)ceil($_filtered_total / $_rows_per_page));
	$_pg_start    = (int)(floor(($_current_page - 1) / $_page_count) * $_page_count) + 1;
	$_pg_end      = min($_pg_start + $_page_count - 1, $_total_pages);

	$_bp = 'bo_table='.urlencode($bo_table).'&mode='.urlencode($view_mode);
	if ($_filter_date != '') $_bp .= '&date='.urlencode($_filter_date);
	if ($sca) $_bp .= '&sca='.urlencode($sca);
	if ($sfl) $_bp .= '&sfl='.urlencode($sfl);
	if ($stx) $_bp .= '&stx='.urlencode(stripslashes($stx));

	if ($_total_pages <= 1) {
		$write_pages = '';
	} else {
		$write_pages = '<nav class="pg_wrap"><span class="pg">';
		if ($_pg_start > 1) {
			$write_pages .= '<a href="./board.php?'.$_bp.'&page='.($_pg_start - 1).'" class="pg_page pg_prev"><i class="sound_only">이전</i></a>';
		}
		for ($_p = $_pg_start; $_p <= $_pg_end; $_p++) {
			if ($_p == $_current_page) {
				$write_pages .= '<strong class="pg_page pg_current">'.$_p.'</strong>';
			} else {
				$write_pages .= '<a href="./board.php?'.$_bp.'&page='.$_p.'" class="pg_page">'.$_p.'</a>';
			}
		}
		if ($_pg_end < $_total_pages) {
			$write_pages .= '<a href="./board.php?'.$_bp.'&page='.($_pg_end + 1).'" class="pg_page pg_next"><i class="sound_only">다음</i></a>';
		}
		$write_pages .= '</span></nav>';
	}

	unset($_rows_per_page, $_current_page, $_offset, $_where, $_cnt_row, $_filtered_total,
	      $_fres, $_frow, $_new_list, $_page_count, $_total_pages,
	      $_pg_start, $_pg_end, $_bp, $_p, $_need_repaginate, $_filter_type, $_filter_date);
}
?>

<div <?if($board['bo_table_width']>0){?>style="max-width:<?=$board['bo_table_width']?><?=$board['bo_table_width']>100 ? "px":"%"?>;margin:0 auto;"<?}?>>
<hr class="padding">
<? if($board['bo_content_head']) { ?>
	<div class="board-notice">
             <div class="notice-box ellipsis">
                 <div class="notice-scroll">
                       <ul><li>
		<?=stripslashes($board['bo_content_head']);?>
	</li></ul></div></div></div><hr class="padding" />
<? } ?>

<div class="board-skin-basic">
	<? if ($is_category) { ?>
	<nav class="board-category">
		<select name="sca" id="sca" onchange="location.href='?bo_table=<?=$bo_table?>&sca=' + this.value;">
			<option value="">전체</option>
			<? echo $category_option ?>
		</select>
	</nav>
	<? } ?>

	<div class="challenge-toolbar">
		<a href="./board.php?bo_table=<?=$bo_table?>&mode=challenge"
		   class="ui-btn<?php echo ($view_mode=='challenge') ? ' active' : ''; ?>">챌린지</a>
		<a href="./board.php?bo_table=<?=$bo_table?>&mode=log"
		   class="ui-btn<?php echo ($view_mode=='log') ? ' active' : ''; ?>">로그</a>
		<a href="<?php echo $write_href ?>&mode=<?=$view_mode?>" class="ui-btn point">글쓰기</a>
		<? if($is_admin) { ?>
		<button type="button" class="ui-btn admin" onclick="toggleChallengeSetting()">게시판 설정</button>
		<? } ?>
	</div>

	<?php if($is_admin) { ?>
	<div id="challenge-setting-box" class="challenge-setting-box" style="display:none;">
		<form action="<?php echo $board_skin_url; ?>/stamp_update.php"
		      method="post"
		      enctype="multipart/form-data">
			<input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">

			<div style="font-weight:700; margin-bottom:6px;">달성 도장 이미지 설정(교체 업로드)</div>
			<div style="font-size:12px; color:#777; margin-bottom:10px;">
				업로드한 항목만 기존 파일을 삭제하고 새 파일로 교체됩니다. (미업로드=유지)<br>
				첨부파일이 없으면 도장은 표시되지 않습니다.
			</div>

			<div style="margin-bottom:10px;">
				<div style="font-size:12px; margin-bottom:4px;">30% 도장</div>
				<input type="file" name="stamp_30" class="frm_file frm_input full">
			</div>
			<div style="margin-bottom:10px;">
				<div style="font-size:12px; margin-bottom:4px;">60% 도장</div>
				<input type="file" name="stamp_60" class="frm_file frm_input full">
			</div>
			<div style="margin-bottom:10px;">
				<div style="font-size:12px; margin-bottom:4px;">100% 도장</div>
				<input type="file" name="stamp_100" class="frm_file frm_input full">
			</div>

			<button type="submit" class="ui-btn point">저장</button>
		</form>
	</div>
	<?php } ?>

	<?php if($view_mode == 'challenge') { ?>
	<div class="challenge-layout">
		<div class="challenge-side">
			<div class="challenge-side-left">
				<div class="challenge-calendar">
					<div id="challenge-calendar"></div>
				</div>
			</div>
			<div class="challenge-side-right">
				<div class="challenge-streak">👉연속 <?=$streak?>일👈</div>
				<div class="challenge-goal-box" <?if($date=='all'){?>style="display:none"<?}?>>
					<div class="challenge-goal-title">일일 목표</div>
					<div id="daily-goal-list">
						<? foreach ($checklist_rows_for_display as $goal_row) {
							$goal_id = $goal_row['wr_id'];
							$goal_content = trim($goal_row['wr_content']);
						?>
						<div class="goal-item <?php echo isset($done_map[$goal_id]) ? 'done' : ''; ?>" data-wr-id="<?php echo $goal_id; ?>">
							<span class="goal-checkbox"></span>
							<span class="goal-text"><?php echo htmlspecialchars($goal_content, ENT_QUOTES, 'UTF-8'); ?></span>
							<? if($member['mb_level'] >= $board['bo_write_level'] || $is_admin){ ?><span class="delete-btn">✕</span><? } ?>
						</div>
						<? } ?>
					</div>
					<? if($member['mb_level'] >= $board['bo_write_level'] || $is_admin){ ?><button id="add-goal-btn" type="button" class="ui-btn point">목표 추가</button><? } ?>
				</div>
			</div>
		</div>
		<div class="challenge-main">
	<?php } ?>

	<form name="fboardlist" id="fboardlist" action="./board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
	<input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="spt" value="<?php echo $spt ?>">
	<input type="hidden" name="sca" value="<?php echo $sca ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="sw" value="">

	<ul class="avocado-list">
	<?
	$visible_count = 0;
	for ($i=0; $i<count($list); $i++) {
		$row_type = '';
		if (isset($list[$i]['wr_type'])) {
			$row_type = $list[$i]['wr_type'];
		} else {
			$_type_row = sql_fetch("SELECT wr_type FROM {$write_table} WHERE wr_id = '{$list[$i]['wr_id']}'");
			$row_type = isset($_type_row['wr_type']) ? $_type_row['wr_type'] : '';
		}
		if ($row_type == 'setting' || $row_type == 'checklist') continue;

		if ($view_mode == 'challenge' && $row_type != 'challenge') continue;
		if ($view_mode == 'log' && !in_array($row_type, array('log', ''))) continue;

		$visible_count++;

		$_rate = 0;
		if (isset($list[$i]['wr_done_rate'])) {
			$_rate = (int)$list[$i]['wr_done_rate'];
		} else {
			$_rrow = sql_fetch("SELECT wr_done_rate FROM {$write_table} WHERE wr_id = '{$list[$i]['wr_id']}'");
			$_rate = isset($_rrow['wr_done_rate']) ? (int)$_rrow['wr_done_rate'] : 0;
		}

		$_stamp_url = '';
		if ($view_mode == 'challenge') {
			if ($_rate >= 100 && $stamp_urls[100] != '') $_stamp_url = $stamp_urls[100];
			else if ($_rate >= 60 && $stamp_urls[60] != '')  $_stamp_url = $stamp_urls[60];
			else if ($_rate >= 30 && $stamp_urls[30] != '')  $_stamp_url = $stamp_urls[30];
		}
	?>
		<li class="theme-box<?php echo ($view_mode=='log') ? ' log-item' : ''; ?> <? if ($list[$i]['is_notice']) echo "bo_notice"; ?>">
			<?php if ($view_mode == 'challenge' && $_stamp_url != '') { ?>
				<div class="done-stamp-overlay">
					<img src="<?php echo htmlspecialchars($_stamp_url, ENT_QUOTES, 'UTF-8'); ?>"
					     alt="달성 도장"
					     class="done-stamp-img">
				</div>
			<?php } ?>

			<?php if ($view_mode == 'log') { ?>
			<!-- ✅ 체크박스와 링크를 flex 래퍼로 감싸 체크박스를 넘버링 왼쪽에 배치 -->
			<div style="display:flex; align-items:center;">
				<?php if ($is_checkbox) { ?>
				<span class="td_chk" style="flex-shrink:0; margin-left:6px;">
					<label for="chk_wr_id_<?php echo $i ?>" class="sound_only">
						<?php echo $list[$i]['subject'] ?>
					</label>
					<input type="checkbox"
						   name="chk_wr_id[]"
						   value="<?php echo $list[$i]['wr_id'] ?>"
						   id="chk_wr_id_<?php echo $i ?>">
				</span>
				<?php } ?>
				<a href="<?php echo $list[$i]['href'] ?>" class="log-mode-row" style="flex:1;">
					<span class="log-row-num"><?php echo (int)$list[$i]['wr_id'] ?></span>
					<span class="log-row-title"><?php echo $list[$i]['subject']; ?></span>
					<?php if ($list[$i]['wr_protect'] != '') { ?>
					    <span class="highlight">보호글</span>
					<?php } elseif (strstr($list[$i]['wr_option'], 'secret')) { ?>
					    <span class="highlight">비밀글</span>
					<?php } elseif ($list[$i]['wr_secret']) { ?>
					    <span class="highlight">멤버글</span>
					<?php } ?>
					<span class="log-row-date">
					    <?php if (!$list[$i]['is_notice']) echo date('Y.m.d.', strtotime($list[$i]['wr_datetime'])); ?>
					</span>
				</a>
			</div>
			<?php } else { ?>
			<?php
			/* ===== 미리보기 변수 사전 계산 ===== */
			$is_secret_opt    = strstr($list[$i]['wr_option'], 'secret');
			$is_member_opt    = (isset($list[$i]['wr_secret']) && $list[$i]['wr_secret']);
			$is_protect_opt   = ($list[$i]['wr_protect'] != '');
			$is_mine          = ($list[$i]['mb_id'] && $list[$i]['mb_id'] == $member['mb_id']);
			$can_read         = ($member['mb_level'] >= $board['bo_read_level']);
			$show_preview     = false;
			$cookie_name      = 'custom_pw_ok_' . $list[$i]['wr_id'];
			$has_unlock_cookie = (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] == '1');

			if ($is_admin || $is_mine) {
				$show_preview = true;
			} else if ($is_protect_opt) {
				$show_preview = $has_unlock_cookie ? true : false;
			} else if ($is_secret_opt) {
				$show_preview = false;
			} else if ($is_member_opt) {
				$show_preview = ($is_member && $can_read) ? true : false;
			} else {
				$show_preview = $can_read ? true : false;
			}

			$first_img    = '';
			$preview_text = '';
			if ($show_preview) {
				$res_row = sql_fetch(" select wr_content from {$write_table} where wr_id = " . (int)$list[$i]['wr_id']);
				$content = $res_row['wr_content'];
				preg_match("/<img[^>]*src=[\"']?([^>\"']+)[\"']?[^>]*>/i", $content, $matches);
				$first_img = isset($matches[1]) ? $matches[1] : '';
				$text = html_entity_decode(htmlspecialchars_decode($content), ENT_QUOTES, 'UTF-8');
				$text = strip_tags($text);
				$text = preg_replace('/(\s|&nbsp;)+/u', ' ', $text);
				$text = preg_replace('/^\s+|\s+$/u', '', $text);
				if ($text) {
					$cut_len = ($list[$i]['comment_cnt'] > 0) ? 120 : 180;
					$preview_text = mb_strimwidth($text, 0, $cut_len, "⋯", "UTF-8");
				}
			}
			?>
			<span class="td_chk">
				<?php if ($is_checkbox) { ?>
					<label for="chk_wr_id_<?php echo $i ?>" class="sound_only">
						<?php echo $list[$i]['subject'] ?>
					</label>
					<input type="checkbox"
						   name="chk_wr_id[]"
						   value="<?php echo $list[$i]['wr_id'] ?>"
						   id="chk_wr_id_<?php echo $i ?>">
				<?php } ?>
			</span>

			<a href="<? echo $list[$i]['href'] ?>" class="bo_row">
				<?php if ($first_img) { ?>
				<div class="list-thumb">
					<img src="<?php echo htmlspecialchars($first_img, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="list-thumb-img">
				</div>
				<?php } ?>

				<div class="bo_row-content">
					<div class="bo_title">
						<strong class="list-title">
							<span class="title-main"><?php echo $list[$i]['subject']; ?></span>

							<?php if (isset($list[$i]['wr_1']) && $list[$i]['wr_1']) { ?>
								<span class="subtitle-wrap">
									<span class="list-subtitle">
										<?php echo get_text($list[$i]['wr_1']); ?>
									</span>
								</span>
							<?php } ?>
						</strong>

						<div class="list-preview">
						<?php if ($show_preview) { ?>
							<?php if ($preview_text) { echo htmlspecialchars($preview_text, ENT_QUOTES, 'UTF-8'); } ?>
						<?php } else { ?>
							<span class="list-preview-lock">
							<?php
							if ($is_protect_opt) {
								echo "🔒 보호글입니다. (비밀번호 입력 필요)";
							} else if ($is_secret_opt) {
								echo "🔒 비공개 글입니다.";
							} else if ($is_member_opt) {
								if (!$is_member) echo "🔒 멤버공개 글입니다.";
								else echo "🔒 읽기 권한이 부족합니다.";
							} else {
								echo "🔒 읽기 권한이 없습니다.";
							}
							?>
							</span>
						<?php } ?>
						</div>
					</div>

					<div class="info">
						<? if ($list[$i]['comment_cnt']) { ?>
							<span class="comment-count">댓글 (<?= $list[$i]['comment_cnt'] ?>)</span>
						<? } ?>

						<? if (strstr($list[$i]['wr_option'], 'secret')) { ?>
							<span class="highlight">비밀글</span>
						<? } else if ($list[$i]['wr_secret']) { ?>
							<span class="highlight">멤버글</span>
						<? } else if ($list[$i]['wr_protect'] != '') { ?>
							<span class="highlight">보호글</span>
						<? } ?>

						<span class="name">
							<? if (!$list[$i]['is_notice']) echo $list[$i]['name'] ?>
						</span>

						<span class="date">
						<?
						if (!$list[$i]['is_notice'])
							echo date('Y.m.d.', strtotime($list[$i]['wr_datetime'])) .
								 '&nbsp;&nbsp;' .
								 date('H:i', strtotime($list[$i]['wr_datetime']));
						?>
						</span>
					</div>
				</div>
			</a>
			<?php } ?>
		</li>
	<? } ?>

	<? if ($visible_count == 0) { ?>
		<li class="theme-box no-data">
			<div class="bo_row no-data-row">게시물이 없습니다.</div>
		</li>
	<? } ?>
	</ul>

	<? if ($list_href || $is_checkbox || $write_href) { ?>
	<div class="bo_fx txt-right">
		<? if ($list_href || $write_href) { ?>
			<?php if ($is_checkbox) { ?>
				<p class="chk_all">
					<label for="chkall" class="sound_only">현재 페이지 게시물 전체</label>
					<input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);">
				</p>
				<button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value" class="ui-btn admin">선택삭제</button>
				<?php /* ✅ 선택이동 버튼 제거: 이 스킨 구조와 호환되지 않아 사용 불가 */ ?>
			<?php } ?>

			<? if ($write_href) { ?><a href="<? echo $write_href ?>" class="ui-btn admin">글쓰기</a><? } ?>
		<? } ?>
		<? if($admin_href){?><a href="<?=$admin_href?>" class="ui-btn admin" target="_blank">관리자</a><?}?>
	</div>
	<? } ?>

	</form>

	<? echo $write_pages;  ?>

	<fieldset id="bo_sch" class="txt-center">
		<legend>게시물 검색</legend>
		<form name="fsearch" method="get">
			<input type="hidden" name="bo_table" value="<? echo $bo_table ?>">
			<input type="hidden" name="mode" value="<?php echo htmlspecialchars($view_mode, ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="sca" value="<? echo $sca ?>">
			<input type="hidden" name="sop" value="and">
			<select name="sfl" id="sfl">
				<option value="wr_subject"<? echo get_selected($sfl, 'wr_subject', true); ?>>제목</option>
				<option value="wr_1"<? echo get_selected($sfl, 'wr_1', true); ?>>부제목</option>
				<option value="wr_content"<? echo get_selected($sfl, 'wr_content'); ?>>내용</option>
				<option value="wr_subject||wr_1||wr_content"<? echo get_selected($sfl, 'wr_subject||wr_1||wr_content'); ?>>제목+부제목+내용</option>
			</select>
			<input type="text" name="stx" value="<? echo stripslashes($stx) ?>" required id="stx" class="frm_input required" size="15" maxlength="20">
			<button type="submit" class="ui-btn point ico search default">검색</button>
		</form>
	</fieldset>

	<?php if($view_mode == 'challenge') { ?>
		</div>
	</div>
	<?php } ?>
</div>
</div>

<script>
<?php if($view_mode == 'challenge') { ?>
var challengeWritten = <?=json_encode($date_set, JSON_UNESCAPED_UNICODE)?>;
var challengeSelectedDate = '<?=$date?>';
function renderChallengeCalendar(dateText) {
	var dateObj = dateText ? new Date(dateText) : new Date();
	var y = dateObj.getFullYear();
	var m = dateObj.getMonth();
	var first = new Date(y, m, 1);
	var last = new Date(y, m + 1, 0);
	var prevLast = new Date(y, m, 0);
	var prevMonthDate = new Date(y, m - 1, 1);
	var nextMonthDate = new Date(y, m + 1, 1);
	var prevMonthText = prevMonthDate.getFullYear() + '-' + ('0' + (prevMonthDate.getMonth() + 1)).slice(-2) + '-01';
	var nextMonthText = nextMonthDate.getFullYear() + '-' + ('0' + (nextMonthDate.getMonth() + 1)).slice(-2) + '-01';
	var html = '<div class="month-box"><button type="button" class="ui-btn admin" onclick="renderChallengeCalendar(\''+prevMonthText+'\')">&lt;</button><strong>' + y + '.' + (m + 1) + '</strong><button type="button" class="ui-btn admin" onclick="renderChallengeCalendar(\''+nextMonthText+'\')">&gt;</button></div>';
	html += '<div class="calendar-grid">';
	var week = ['일','월','화','수','목','금','토'];
	for (var w=0; w<7; w++) html += '<span class="month-date month-title">'+week[w]+'</span>';
	for (var p=first.getDay()-1; p>=0; p--) html += '<span class="month-date month-other">'+(prevLast.getDate()-p)+'</span>';
	for (var d=1; d<=last.getDate(); d++) {
		var mm = (m + 1 < 10) ? '0' + (m + 1) : (m + 1);
		var dd = (d < 10) ? '0' + d : d;
		var key = y + '-' + mm + '-' + dd;
		var cls = 'month-date month-this';
		if (typeof challengeWritten[key] !== 'undefined') cls += ' isdone';
		if (challengeSelectedDate == key) cls += ' selected';
		html += '<a class="'+cls+'" href="./board.php?bo_table=<?=$bo_table?>&date='+key+'">'+d+'</a>';
	}
	var tail = 42 - (first.getDay() + last.getDate());
	for (var n=1; n<=tail; n++) html += '<span class="month-date month-other">'+n+'</span>';
	html += '</div>';
	document.getElementById('challenge-calendar').innerHTML = html;
}
function toggleChallengeSetting() {
	$('#challenge-setting-box').slideToggle();
}
$(function(){
	renderChallengeCalendar(challengeSelectedDate == 'all' ? '' : challengeSelectedDate);
	$('#add-goal-btn').on('click', function() {
		var html = '<div class="goal-input-row"><input type="text" class="goal-input" placeholder="목표를 입력하세요"><button type="button" class="save-goal ui-btn point">저장</button><button type="button" class="cancel-goal ui-btn">취소</button></div>';
		$('#daily-goal-list').prepend(html);
	});
	$(document).on('click', '.cancel-goal', function(){ $(this).closest('.goal-input-row').remove(); });
	$(document).on('click', '.save-goal', function(){
		var $row = $(this).closest('.goal-input-row');
		var content = $.trim($row.find('.goal-input').val());
		if (!content) { alert('내용을 입력하세요.'); return; }
		$.ajax({
			url: '<?=$board_skin_url?>/checklist_update.php',
			type: 'POST',
			dataType: 'json',
			data: {
				mode: 'insert',
				bo_table: '<?=$bo_table?>',
				write_table: '<?=$write_table?>',
				wr_content: content
			}
		}).done(function(res){
			if (res.success) {
				$('#daily-goal-list').append('<div class="goal-item" data-wr-id="'+res.wr_id+'"><span class="goal-checkbox"></span><span class="goal-text"></span><span class="delete-btn">✕</span></div>');
				$('#daily-goal-list .goal-item:last .goal-text').text(content);
				$row.remove();
			} else {
				alert(res.message ? res.message : '저장 실패');
			}
		}).fail(function(){ alert('저장 실패'); });
	});
	$(document).on('click', '.delete-btn', function(){
		var $item = $(this).closest('.goal-item');
		var id = $item.data('wr-id');
		$.ajax({
			url: '<?=$board_skin_url?>/checklist_update.php',
			type: 'POST',
			dataType: 'json',
			data: {
				mode: 'delete',
				bo_table: '<?=$bo_table?>',
				write_table: '<?=$write_table?>',
				wr_id: id
			}
		}).done(function(res){
			if (res.success) $item.remove();
			else if (res.message) alert(res.message);
		}).fail(function(){ alert('삭제 실패'); });
	});
});
<?php } ?>
</script>

<? if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
	var f = document.fboardlist;
	for (var i=0; i<f.length; i++) {
		if (f.elements[i].name == "chk_wr_id[]")
			f.elements[i].checked = sw;
	}
}

function fboardlist_submit(f) {
	var chk_count = 0;
	for (var i=0; i<f.length; i++) {
		if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
			chk_count++;
	}
	if (!chk_count) {
		alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
		return false;
	}
	if(document.pressed == "선택복사") { select_copy("copy"); return; }
	<?php /* ✅ 선택이동 분기 제거 */ ?>
	if(document.pressed == "선택삭제") {
		if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다."))
			return false;
		f.removeAttribute("target");
		f.action = "./board_list_update.php";
	}
	return true;
}

function select_copy(sw) {
	var f = document.fboardlist;
	if (sw == "copy") str = "복사";
	else str = "이동";
	var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");
	f.sw.value = sw;
	f.target = "move";
	f.action = "./move.php";
	f.submit();
}
</script>
<? } ?>
<!-- } 게시판 목록 끝 -->
