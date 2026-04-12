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
if (!in_array($wr_type_value, array('', 'log', 'setting', 'checklist'))) $wr_type_value = '';

$wr_date_value = isset($wr_date) ? trim($wr_date) : '';
if ($wr_type_value == 'log' || $wr_type_value == '') {
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $wr_date_value)) $wr_date_value = date('Y-m-d');
} else {
	$wr_date_value = '';
}

$wr_done_value = isset($wr_done) ? trim($wr_done) : '';
if (!preg_match('/^[0-9,]*$/', $wr_done_value)) $wr_done_value = '';

/* ===== 달성률 계산 ===== */
$done_ids = array();
if ($wr_done_value !== '') {
	$tmp = explode(',', $wr_done_value);
	foreach ($tmp as $v) {
		$v = trim($v);
		if ($v !== '' && ctype_digit($v)) $done_ids[$v] = true; // unique
	}
}
$wr_done_count = count($done_ids);

// 현재 등록된 일일목표 총 개수(로그 글에서만 의미 있음)
$wr_goal_total = 0;
if ($wr_type_value == 'log' || $wr_type_value == '') {
	$cnt_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_type='checklist'");
	$wr_goal_total = isset($cnt_row['cnt']) ? (int)$cnt_row['cnt'] : 0;
}

$wr_done_rate = 0;
if ($wr_goal_total > 0) {
	$wr_done_rate = (int)floor(($wr_done_count / $wr_goal_total) * 100);
	if ($wr_done_rate > 100) $wr_done_rate = 100;
	if ($wr_done_rate < 0) $wr_done_rate = 0;
} else {
	$wr_done_rate = 0;
}

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
} 

if (isset($wr_type_value) && $wr_type_value == 'setting') goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table);
goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr);
?>