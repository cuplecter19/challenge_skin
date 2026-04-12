<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/common.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/head.sub.php');

$sql = "SELECT * FROM {$g5['emoticon_table']}";
$result = sql_query($sql);

add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/style.emoticon.css">', 0);
?>

<div id="emoticon_page">
    <div id="emoticon_head"></div>
    <div id="page_title">
        이모티콘
        <div class="subtitle">이모티콘 이름을 입력하면<br>글씨가 이모티콘으로 출력 돼요.</div>
        <i id="emoticon_line"></i>
    </div>

    <div id="emoticon_content">
        <ul>
        <?php
        $i = 0;
        while ($row = sql_fetch_array($result)) {
            $i++;
            $img_dir = rtrim(dirname($row['me_img']), '/');
            $img_file = rawurlencode(basename($row['me_img']));
            $img_path = G5_URL . ($img_dir ? "/$img_dir/$img_file" : "/$img_file");
        ?>
            <li>
                <em>
                    <img src="<?=htmlspecialchars($img_path)?>" alt="<?=htmlspecialchars($row['me_text'])?>" />
                </em>
                <span><?=htmlspecialchars($row['me_text'])?></span>
            </li>
        <?php
        }
        if ($i === 0) {
        ?>
            <li class="no-data">등록된 이모티콘이 없습니다.</li>
        <?php } ?>
        </ul>
    </div>
    <div id="emoticon_footer"></div>
</div>

<?php include_once($_SERVER['DOCUMENT_ROOT'].'/tail.sub.php'); ?>