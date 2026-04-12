<?php

if (empty($_FILES['bf_file']['name'][0])) {
    file_put_contents(
        G5_DATA_PATH.'/comment_file_debug.txt',
        "NO FILE UPLOADED\n",
        FILE_APPEND
    );
    return;
}

file_put_contents(
    G5_DATA_PATH.'/comment_file_debug.txt',
    print_r($_FILES, true),
    FILE_APPEND
);

// 파일 개수 제한 (최대 2개)
$max_files = 2;
$cnt = 0;

for ($i = 0; $i < count($_FILES['bf_file']['name']); $i++) {

    if ($cnt >= $max_files) break;
    if ($_FILES['bf_file']['error'][$i] != 0) continue;
    if (!$_FILES['bf_file']['tmp_name'][$i]) continue;

    $tmp_name = $_FILES['bf_file']['tmp_name'][$i];
    $origin   = $_FILES['bf_file']['name'][$i];

    $save_name = time().'_'.rand(1000,9999).'_'.preg_replace('/[^a-zA-Z0-9._-]/', '', $origin);

    $save_path = G5_DATA_PATH.'/file/'.$bo_table;
    if (!is_dir($save_path)) {
        mkdir($save_path, G5_DIR_PERMISSION, true);
    }

    $full_path = $save_path.'/'.$save_name;

    if (move_uploaded_file($tmp_name, $full_path)) {

        sql_query("
            INSERT INTO {$g5['board_file_table']}
            SET
                bo_table = '{$bo_table}',
                wr_id    = '{$comment_id}',
                bf_no    = '{$cnt}',
                bf_source= '".sql_real_escape_string($origin)."',
                bf_file  = '{$save_name}',
                bf_filesize = '".filesize($full_path)."',
                bf_datetime = '".G5_TIME_YMDHIS."'
        ");

        $cnt++;
    }
}
