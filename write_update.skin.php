<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가 
if (!preg_match('/^[A-Za-z0-9_]+$/', $write_table)) exit;

$temp = sql_fetch("select * from {$write_table} limit 1");
if(!isset($temp['wr_protect'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_protect` varchar(255) NOT NULL DEFAULT '' AFTER `wr_url` ");
}
if(!isset($temp['wr_type'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_type` varchar(20) NOT NULL DEFAULT '' AFTER `wr_10` ");
}
if(!isset($temp['wr_date'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_date` varchar(10) NOT NULL DEFAULT '' AFTER `wr_subject` ");
}
if(!isset($temp['wr_done'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_done` text NOT NULL AFTER `wr_date` ");
}
if(!isset($temp['wr_done_count'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_done_count` int NOT NULL DEFAULT 0 AFTER `wr_done` ");
}
if(!isset($temp['wr_goal_total'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_goal_total` int NOT NULL DEFAULT 0 AFTER `wr_done_count` ");
}
if(!isset($temp['wr_done_rate'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_done_rate` tinyint NOT NULL DEFAULT 0 AFTER `wr_goal_total` ");
}
if(!isset($temp['wr_html_log'])){
	sql_query(" ALTER TABLE `{$write_table}` ADD `wr_html_log` varchar(255) NOT NULL DEFAULT '' AFTER `wr_done_rate` ");
}
unset($temp);

if($w!='c' && $w!='cu'){

$sec=""; 
$mem=0;
if (!isset($wr_protect)) $wr_protect = '';
if($set_secret) {

	if($set_secret=='secret'){
		$sec="secret";
	} 
	else if ($set_secret=='member'){
		$mem=1;
	}
	else if($set_secret == 'protect' && $wr_protect!=''){
		$wr_protect = trim($wr_protect);
	}
}

$wr_type_value = isset($wr_type) ? trim($wr_type) : '';
if (!in_array($wr_type_value, array('', 'log', 'challenge', 'setting', 'checklist'))) $wr_type_value = 'log';

$wr_date_value = isset($wr_date) ? trim($wr_date) : '';
$wr_done_value = isset($wr_done) ? trim($wr_done) : '';
if (!preg_match('/^[0-9,]*$/', $wr_done_value)) $wr_done_value = '';

if ($wr_type_value == 'challenge') {
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $wr_date_value)) $wr_date_value = date('Y-m-d');
} else {
	$wr_date_value = '';
	$wr_done_value = ''; // 로그/일반 글은 done 데이터 불필요
}

/* ===== 달성률 계산 ===== */
$done_ids = array();
$wr_done_count = 0;
$wr_goal_total = 0;
$wr_done_rate = 0;

if ($wr_type_value == 'challenge') {
	if ($wr_done_value !== '') {
		$tmp = explode(',', $wr_done_value);
		foreach ($tmp as $v) {
			$v = trim($v);
			if ($v !== '' && ctype_digit($v)) $done_ids[$v] = true; // unique
		}
	}
	$wr_done_count = count($done_ids);

	// 현재 등록된 일일목표 총 개수(챌린지 글에서만 의미 있음)
	$cnt_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_type='checklist'");
	$wr_goal_total = isset($cnt_row['cnt']) ? (int)$cnt_row['cnt'] : 0;

	if ($wr_goal_total > 0) {
		$wr_done_rate = (int)floor(($wr_done_count / $wr_goal_total) * 100);
		if ($wr_done_rate > 100) $wr_done_rate = 100;
		if ($wr_done_rate < 0) $wr_done_rate = 0;
	}
}
// 로그/일반 글은 달성률 항상 0

$wr_id = (int)$wr_id;
$html_value = isset($html) ? $html : '';
$wr_option_value = sql_real_escape_string($html_value.','.$sec);

sql_query("
	update {$write_table}
	set
		wr_option='{$wr_option_value}',
		wr_secret='".(int)$mem."',
		wr_protect='".sql_real_escape_string($wr_protect)."',
		wr_type='".sql_real_escape_string($wr_type_value)."',
		wr_date='".sql_real_escape_string($wr_date_value)."',
		wr_done='".sql_real_escape_string($wr_done_value)."',
		wr_done_count='{$wr_done_count}',
		wr_goal_total='{$wr_goal_total}',
		wr_done_rate='{$wr_done_rate}'
	where wr_id='{$wr_id}'
");

/* =====================================================================
 * ★ HTML 로그 파일 처리 (로그 게시물 전용)
 * ===================================================================== */
$_hl_save_dir = G5_DATA_PATH.'/file/'.$bo_table;
if (!is_dir($_hl_save_dir)) @mkdir($_hl_save_dir, G5_DIR_PERMISSION, true);

$_hl_row     = sql_fetch("SELECT wr_html_log FROM {$write_table} WHERE wr_id='{$wr_id}'");
$_hl_current = (isset($_hl_row['wr_html_log']) && $_hl_row['wr_html_log'] != '')
               ? $_hl_row['wr_html_log'] : '';

if ($wr_type_value == 'log' || $wr_type_value == '') {

	if (!empty($_POST['html_log_del']) && $_hl_current != '') {
		$_hl_del = $_hl_save_dir.'/'.$_hl_current;
		if (is_file($_hl_del)) @unlink($_hl_del);
		sql_query("UPDATE {$write_table} SET wr_html_log='' WHERE wr_id='{$wr_id}'");
		$_hl_current = '';
	}

	if (isset($_FILES['html_log_file']) && is_array($_FILES['html_log_file'])
		&& (int)$_FILES['html_log_file']['error'] === UPLOAD_ERR_OK
		&& $_FILES['html_log_file']['tmp_name'] != '') {

		$_hl_orig = $_FILES['html_log_file']['name'];
		$_hl_ext  = strtolower(pathinfo($_hl_orig, PATHINFO_EXTENSION));

		if (in_array($_hl_ext, array('html', 'htm'))) {
			$_hl_sname = 'chatlog_'.$wr_id.'_'.time().'_'.rand(1000, 9999).'.'.$_hl_ext;
			$_hl_fpath = $_hl_save_dir.'/'.$_hl_sname;

			if (move_uploaded_file($_FILES['html_log_file']['tmp_name'], $_hl_fpath)) {
				if ($_hl_current != '') {
					$_hl_old = $_hl_save_dir.'/'.$_hl_current;
					if (is_file($_hl_old)) @unlink($_hl_old);
				}
				sql_query("UPDATE {$write_table} SET wr_html_log='".sql_real_escape_string($_hl_sname)."' WHERE wr_id='{$wr_id}'");
			}
		}
	}

} else {
	if ($_hl_current != '') {
		$_hl_del2 = $_hl_save_dir.'/'.$_hl_current;
		if (is_file($_hl_del2)) @unlink($_hl_del2);
		sql_query("UPDATE {$write_table} SET wr_html_log='' WHERE wr_id='{$wr_id}'");
	}
}

unset($_hl_save_dir, $_hl_row, $_hl_current, $_hl_orig, $_hl_ext,
      $_hl_sname, $_hl_fpath, $_hl_del, $_hl_del2, $_hl_old);
/* ★ HTML 로그 파일 처리 끝 ================================================ */

} // end if($w!='c' && $w!='cu')

if (isset($wr_type_value) && $wr_type_value == 'setting') goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table);

/*
 * ★ 리다이렉트 시 mode 파라미터를 붙이지 않음.
 * mode는 목록 표시 모드일 뿐이며 작성 완료가 이를 강제하지 않도록 함.
 * list.skin.php의 기본값(challenge) 또는 사용자가 마지막으로 선택한
 * mode($qstr 내 포함 시)로 자연스럽게 유지됨.
 */
goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr);
?>
