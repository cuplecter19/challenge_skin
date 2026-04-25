<?php
// /skin/board/fiction/stamp_update.php

$found = false;
$try_paths = array(
    $_SERVER['DOCUMENT_ROOT'].'/common.php',
    __DIR__ . '/_common.php',
    __DIR__ . '/chal__common.php',
    __DIR__ . '/../../../common.php',
    __DIR__ . '/../../common.php',
    __DIR__ . '/../common.php',
    __DIR__ . '/../../../../common.php',
);
foreach ($try_paths as $p) {
    if (file_exists($p)) {
        include_once($p);
        $found = true;
        break;
    }
}
if (!$found) {
    die('common.php를 찾을 수 없습니다.');
}

$bo_table = isset($_POST['bo_table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['bo_table']) : '';
if ($bo_table == '') { alert('게시판 정보가 없습니다.'); exit; }
if (!$is_admin) { alert('권한이 없습니다.'); exit; }

$write_table = $g5['write_prefix'] . $bo_table;

// write_table에 wr_type 컬럼이 없을 수도 있으니 보강
$tmp = sql_fetch("SELECT * FROM {$write_table} LIMIT 1");
if (!isset($tmp['wr_type'])) {
    sql_query("ALTER TABLE `{$write_table}` ADD `wr_type` varchar(20) NOT NULL DEFAULT '' AFTER `wr_10`");
}
unset($tmp);

// 1) setting 글 찾기
$setting = sql_fetch("SELECT wr_id, wr_1 FROM {$write_table} WHERE wr_type='setting' ORDER BY wr_id DESC LIMIT 1");
$setting_id = isset($setting['wr_id']) ? (int)$setting['wr_id'] : 0;

// 2) 없으면 생성 — 실제 테이블에 존재하는 컬럼만 사용
if ($setting_id <= 0) {
    $min = sql_fetch("SELECT MIN(wr_num) AS mn FROM {$write_table}");
    $wr_num = (isset($min['mn']) ? (int)$min['mn'] : 0) - 1;

    $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
    $mb_nick = isset($member['mb_nick']) ? $member['mb_nick'] : 'admin';

    sql_query("
        INSERT INTO {$write_table}
        SET
            wr_num       = '{$wr_num}',
            wr_reply     = '',
            wr_parent    = 0,
            wr_is_comment = 0,
            wr_comment   = 0,
            wr_comment_reply = '',
            ca_name      = '',
            wr_option    = '',
            wr_subject   = '설정',
            wr_content   = '게시판 설정',
            wr_datetime  = '".G5_TIME_YMDHIS."',
            wr_last      = '".G5_TIME_YMDHIS."',
            wr_ip        = '".sql_real_escape_string($_SERVER['REMOTE_ADDR'])."',
            mb_id        = '".sql_real_escape_string($mb_id)."',
            wr_name      = '".sql_real_escape_string($mb_nick)."',
            wr_type      = 'setting',
            wr_1         = ''
    ");
    $setting_id = (int)sql_insert_id();
    if ($setting_id > 0) {
        sql_query("UPDATE {$write_table} SET wr_parent='{$setting_id}' WHERE wr_id='{$setting_id}'");
    }
}

// 3) setting 확인
if ($setting_id <= 0) {
    alert('설정 글 생성에 실패했습니다. 관리자에게 문의하세요.');
    exit;
}

sql_query("UPDATE {$write_table} SET wr_type='setting' WHERE wr_id='{$setting_id}'");

// 4) plain_mode 저장
$plain_mode = (isset($_POST['plain_mode']) && $_POST['plain_mode'] == '1') ? '1' : '';
sql_query("UPDATE {$write_table} SET wr_1='".sql_real_escape_string($plain_mode)."' WHERE wr_id='{$setting_id}'");

// 업로드 저장 경로
$save_dir = G5_DATA_PATH.'/file/'.$bo_table;
if (!is_dir($save_dir)) @mkdir($save_dir, G5_DIR_PERMISSION, true);

function stamp_delete_slot($bo_table, $setting_id, $slot, $save_dir, $g5) {
    $row = sql_fetch("
        SELECT bf_file
        FROM {$g5['board_file_table']}
        WHERE bo_table='".sql_real_escape_string($bo_table)."'
          AND wr_id='{$setting_id}'
          AND bf_no='{$slot}'
        LIMIT 1
    ");
    if (isset($row['bf_file']) && $row['bf_file'] != '') {
        $path = $save_dir.'/'.$row['bf_file'];
        if (is_file($path)) @unlink($path);
    }
    sql_query("
        DELETE FROM {$g5['board_file_table']}
        WHERE bo_table='".sql_real_escape_string($bo_table)."'
          AND wr_id='{$setting_id}'
          AND bf_no='{$slot}'
    ");
}

function stamp_save_slot($bo_table, $setting_id, $slot, $file, $save_dir, $g5) {
    if (!isset($file['tmp_name']) || $file['tmp_name'] == '') return;

    $origin = $file['name'];
    $ext = strtolower(pathinfo($origin, PATHINFO_EXTENSION));
    $allow = array('jpg','jpeg','png','gif','webp');
    if (!in_array($ext, $allow)) { alert('이미지 파일만 업로드 가능합니다. (jpg/png/gif/webp)'); exit; }

    $safe_origin = preg_replace('/[^a-zA-Z0-9._-]/', '', $origin);
    $save_name = 'stamp_'.$slot.'_'.time().'_'.rand(1000,9999).'_'.$safe_origin;

    $full = $save_dir.'/'.$save_name;
    if (!move_uploaded_file($file['tmp_name'], $full)) { alert('파일 업로드에 실패했습니다.'); exit; }

    $size = filesize($full);

    sql_query("
        INSERT INTO {$g5['board_file_table']}
        SET
            bo_table   = '".sql_real_escape_string($bo_table)."',
            wr_id      = '{$setting_id}',
            bf_no      = '{$slot}',
            bf_source  = '".sql_real_escape_string($origin)."',
            bf_file    = '".sql_real_escape_string($save_name)."',
            bf_filesize = '{$size}',
            bf_datetime = '".G5_TIME_YMDHIS."'
    ");
}

// 업로드 필드 -> 슬롯
$map = array(
    0 => 'stamp_30',
    1 => 'stamp_60',
    2 => 'stamp_100',
);

foreach ($map as $slot => $field) {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) continue;

    if (isset($_FILES[$field]['error']) && $_FILES[$field]['error'] == 0 && $_FILES[$field]['tmp_name'] != '') {
        stamp_delete_slot($bo_table, $setting_id, $slot, $save_dir, $g5);
        stamp_save_slot($bo_table, $setting_id, $slot, $_FILES[$field], $save_dir, $g5);
    }
}

goto_url(G5_BBS_URL.'/board.php?bo_table='.$bo_table);
