<?php
if (file_exists(__DIR__ . '/_common.php')) {
	include_once(__DIR__ . '/_common.php');
} else if (file_exists(__DIR__ . '/chal__common.php')) {
	include_once(__DIR__ . '/chal__common.php');
} else if (file_exists(__DIR__ . '/../../../common.php')) {
	include_once(__DIR__ . '/../../../common.php');
} else {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('success' => false, 'message' => 'common.php를 찾을 수 없습니다.'));
	exit;
}

header('Content-Type: application/json; charset=utf-8');

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$bo_table = isset($_POST['bo_table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['bo_table']) : '';

if ($bo_table == '') {
	echo json_encode(array('success' => false, 'message' => '게시판 정보가 없습니다.'));
	exit;
}

$write_table = $g5['write_prefix'] . $bo_table;
$board = sql_fetch("SELECT * FROM {$g5['board_table']} WHERE bo_table = '".sql_real_escape_string($bo_table)."'");
if (!isset($board['bo_table']) || $board['bo_table'] == '') {
	echo json_encode(array('success' => false, 'message' => '게시판 정보 없음'));
	exit;
}

$user_level = isset($member['mb_level']) ? (int)$member['mb_level'] : 1;
$write_level = isset($board['bo_write_level']) ? (int)$board['bo_write_level'] : 10;
if (!$is_admin && $user_level < $write_level) {
	echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
	exit;
}

if ($mode == 'insert') {
	$wr_content = trim(isset($_POST['wr_content']) ? $_POST['wr_content'] : '');
	if ($wr_content === '') {
		echo json_encode(array('success' => false, 'message' => '내용 없음'));
		exit;
	}

	$sql = "INSERT INTO {$write_table} (wr_datetime, wr_type, wr_content, wr_is_comment)
			VALUES (NOW(), 'checklist', '".sql_real_escape_string($wr_content)."', 1)";
	sql_query($sql);
	$wr_id = sql_insert_id();

	echo json_encode(array('success' => true, 'wr_id' => $wr_id));
	exit;
}

if ($mode == 'delete') {
	$wr_id = isset($_POST['wr_id']) ? (int)$_POST['wr_id'] : 0;
	sql_query("DELETE FROM {$write_table} WHERE wr_id = {$wr_id} AND wr_type = 'checklist'");
	echo json_encode(array('success' => true));
	exit;
}

echo json_encode(array('success' => false, 'message' => '지원하지 않는 요청'));
exit;
?>