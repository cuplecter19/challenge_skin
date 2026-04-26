<?php
/*
 * html_log_view.php — HTML 채팅 로그 프록시 뷰어
 */

$_hl_found = false;
foreach (array(
    $_SERVER['DOCUMENT_ROOT'].'/common.php',
    __DIR__ . '/../../../common.php',
    __DIR__ . '/../../common.php',
    __DIR__ . '/../common.php',
    __DIR__ . '/../../../../common.php',
) as $_hl_p) {
    if (file_exists($_hl_p)) {
        include_once($_hl_p);
        $_hl_found = true;
        break;
    }
}
if (!$_hl_found) { http_response_code(500); exit; }

// 파라미터 검증
$bo_table = isset($_GET['bo_table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['bo_table']) : '';
$wr_id    = isset($_GET['wr_id'])    ? (int)$_GET['wr_id'] : 0;
if ($bo_table === '' || $wr_id <= 0) { http_response_code(400); exit; }

$write_table = $g5['write_prefix'].$bo_table;
if (!preg_match('/^[A-Za-z0-9_]+$/', $write_table)) { http_response_code(400); exit; }

$_hl_tmp = sql_fetch("SELECT * FROM {$write_table} LIMIT 1");
if (!isset($_hl_tmp['wr_html_log'])) { http_response_code(404); exit; }
unset($_hl_tmp);

$_hl_row = sql_fetch("
    SELECT wr_html_log, wr_secret, wr_protect, wr_option, mb_id
    FROM {$write_table}
    WHERE wr_id = '{$wr_id}' AND wr_is_comment = 0
");
if (!$_hl_row || $_hl_row['wr_html_log'] == '') { http_response_code(404); exit; }

$_hl_board = sql_fetch("
    SELECT bo_read_level
    FROM {$g5['board_table']}
    WHERE bo_table = '".sql_real_escape_string($bo_table)."'
");
if (!$_hl_board) { http_response_code(404); exit; }

if (!$is_admin && (int)$member['mb_level'] < (int)$_hl_board['bo_read_level']) {
    http_response_code(403); exit;
}
if (strstr($_hl_row['wr_option'], 'secret')) {
    if (!$is_admin && (!isset($member['mb_id']) || $member['mb_id'] !== $_hl_row['mb_id'])) {
        http_response_code(403); exit;
    }
}
if ($_hl_row['wr_secret'] == '1' && !$is_member && !$is_admin) {
    http_response_code(403); exit;
}

// 파일명 보안 검증
$_hl_file = $_hl_row['wr_html_log'];
if (strpos($_hl_file, '/') !== false
    || strpos($_hl_file, '\\') !== false
    || !preg_match('/^[A-Za-z0-9_.]+$/', $_hl_file)) {
    http_response_code(400); exit;
}

$_hl_path = G5_DATA_PATH.'/file/'.$bo_table.'/'.$_hl_file;
if (!is_file($_hl_path)) { http_response_code(404); exit; }

// 파일 읽기
$_html = @file_get_contents($_hl_path);
if ($_html === false) { http_response_code(500); exit; }

// ★ 주입 스크립트 정의
$_inject = '<style>'
. 'html,body{margin:0;padding:0;overflow:auto;}'
. '.chat-body{padding-bottom:20px!important;}'
. '</style>';

// </head> 직전에 주입 (대소문자 무관, 첫 번째 등장 위치에만)
$_html_out = preg_replace('/<\/head>/i', $_inject . '</head>', $_html, 1);

// </head> 없으면 <body> 앞에 주입
if ($_html_out === null || $_html_out === $_html) {
    $_html_out = preg_replace('/<body/i', $_inject . '<body', $_html, 1);
}
// 그래도 없으면 그냥 선두에 붙임
if ($_html_out === null || $_html_out === $_html) {
    $_html_out = $_inject . $_html;
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store, no-cache');
echo $_html_out;
exit;
