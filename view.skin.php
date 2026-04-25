<?php
if (!defined('_GNUBOARD_')) exit;

include_once(dirname(__FILE__).'/_functions.php');

include_once(G5_LIB_PATH.'/thumbnail.lib.php');

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
$p_url="";
if ($view['wr_protect'] != '') {
    if (get_session("ss_secret_{$bo_table}_{$view['wr_num']}") || ($view['mb_id'] && $view['mb_id'] == $member['mb_id']) || $is_admin) {
        $is_viewer = true;
    } else {
        $is_viewer = false;
        $p_url = "./password.php?w=p&amp;bo_table=".$bo_table."&amp;wr_id=".$view['wr_id'].$qstr;
    }
}
else if (strstr($view['wr_option'], 'secret')) {
    if ($is_admin || ($view['mb_id'] && $view['mb_id'] == $member['mb_id'])) {
        $is_viewer = true;
    } else {
        $is_viewer = false;
        $p_url = "./password.php?w=s&amp;bo_table=".$bo_table."&amp;wr_id=".$view['wr_id'].$qstr;
    }
}
else if($view['wr_secret'] == '1') {
    if ($is_member) {
        $is_viewer = true;
    } else {
        $is_viewer = false;
        $p_url = "./login.php";
    }
}

if(!$is_viewer && $p_url!=''){
    if($p_url=="./login.php") alert("멤버공개 글입니다. 로그인 후 이용해주세요.",$p_url);
    else goto_url($p_url);
}
?>

<script src="<? echo G5_JS_URL; ?>/viewimageresize.js"></script>

<div id="board-viewer-wrap" class="board-viewer theme-box" <?if($board['bo_table_width']>0){?>style="max-width:<?=$board['bo_table_width']?><?=$board['bo_table_width']>100 ? "px":"%"?>;margin:0 auto;"<?}?>>
<hr class="padding">


    <?
    if ($view['file']['count']) {
        $cnt = 0;
        for ($i=0; $i<count($view['file']); $i++) {
            if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'] && !$view['file'][$i]['view'])
                $cnt++;
        }
    }
    ?>

    <div class="files">
        <ul>
        <?
        $html_attachments = array(); // HTML 파일 별도 수집

        for ($i=0; $i<count($view['file']); $i++) {
            if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'] && !$view['file'][$i]['view']) {
                $ext = strtolower(pathinfo($view['file'][$i]['source'], PATHINFO_EXTENSION));
                if ($ext === 'html' || $ext === 'htm') {
                    // HTML 파일 → 채팅 로그 렌더링 대상
                    $html_attachments[] = $view['file'][$i];
                } else {
         ?>
            <li>
                <a href="<? echo $view['file'][$i]['href'];  ?>" class="view_file_download">
                    <img src="<? echo $board_skin_url ?>/img/icon_file.gif" alt="첨부">
                    <strong><? echo $view['file'][$i]['source'] ?></strong>
                    <? echo $view['file'][$i]['content'] ?> (<? echo $view['file'][$i]['size'] ?>)
                </a>
                <span class="bo_v_file_cnt"><? echo $view['file'][$i]['download'] ?>회 다운로드</span>
                <span>DATE : <? echo $view['file'][$i]['datetime'] ?></span>
            </li>
        <?
                }
            }
        }
        ?>
        </ul>



<!-- 본문 폰   조절 버튼 -->
<div class="view-font-control">

<img id="modeToggleImg" src="/img/dark.png" alt="라이트모드 토글" style="cursor:pointer; width:24px; height:24px;">

<img id="indentToggleImg" src="/img/space_on.png" alt="들여쓰기 토글" style="cursor:pointer; width:24px; height:24px;">

    <button type="button" onclick="fontDown()">가−</button>
    <button type="button" onclick="fontReset()">가</button>
    <button type="button" onclick="fontUp()">가+</button>
</div>

<div class="subject">
    <strong><?=get_text($view['wr_subject'])?></strong>

    <?php if ($view['wr_1']) { ?>
        <div class="view-subtitle">
            <?=get_text($view['wr_1'])?>
        </div>
    <?php } ?>
</div>

<div class="info">
    <?if(!$view['is_notice']){?>

        <? if ($is_category && $view['ca_name']) { ?>
    <span class="view-category">
        <a href="<?php echo $list_href ?>&amp;sca=<? echo urlencode($view['ca_name']); ?>">
            <? echo get_text($view['ca_name']); ?>
        </a>
    </span>
<? } ?>


<span><? echo $view['name'] ?></span>
<span><? echo date("Y.m.d.", strtotime($view['wr_datetime'])) ?>
&nbsp;&nbsp;
<? echo date("H:i", strtotime($view['wr_datetime'])) ?></span>
<span class="view-date">
</span>

    <?}?></div>


    </div>
<hr class="line2">
    <div class="contents">
        <?
        $v_img_count = count($view['file']);
        if($v_img_count) {
            echo "<div id=\"bo_v_img\">\n";

            for ($i=0; $i<=count($view['file']); $i++) {
                if ($view['file'][$i]['view']) {
                    echo get_view_thumbnail($view['file'][$i]['view']);
                }
            }

            echo "</div>\n";
        }
         ?>
        <!-- 본문 내용 시작 { -->


<div id="bo_v_con"><?php
$content = get_view_thumbnail($view['content']);
$content = youtube_auto_embed_view($content);
$content = emote_ev($content); // ★ 이모티콘 치환 추가
echo $content;
?></div>
<?php
// HTML 첨부파일 채팅 로그 iframe 렌더링
if (!empty($html_attachments)) {
    echo '<div class="chat-log-section">';
    foreach ($html_attachments as $hf) {
        // board_file 테이블에서 실제 서버 저장 파일명 조회
        $bf_row = sql_fetch("
            SELECT bf_file
            FROM {$g5['board_file_table']}
            WHERE bo_table='".sql_real_escape_string($bo_table)."'
              AND wr_id='".intval($view['wr_id'])."'
              AND bf_source='".sql_real_escape_string($hf['source'])."'
            LIMIT 1
        ");
        if ($bf_row && $bf_row['bf_file']) {
            $file_url = G5_DATA_URL.'/file/'.$bo_table.'/'.$bf_row['bf_file'];
            echo '<div class="chat-log-container">';
            echo '<div class="chat-log-label">'.htmlspecialchars($hf['source'], ENT_QUOTES, 'UTF-8').'</div>';
            echo '<iframe'
                .' src="'.htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8').'"'
                .' class="chat-log-frame"'
                .' scrolling="auto"'
                .' frameborder="0"'
                .' onload="(function(f){try{f.style.height=f.contentWindow.document.body.scrollHeight+\'px\';}catch(e){f.style.height=\'600px\';}})(this)">'
                .'</iframe>';
            echo '</div>';
        }
    }
    unset($hf);
    echo '</div>';
}
?>
        <!-- } 본문 내용 끝 -->
    </div>


<?php
$prev_sub = null;
$next_sub = null;

if (!empty($prev_href)) {
    $href = html_entity_decode($prev_href);
    parse_str(parse_url($href, PHP_URL_QUERY), $q);

    if (!empty($q['wr_id'])) {
        $prev_sub = sql_fetch("
            select wr_1
            from {$write_table}
            where wr_id = '{$q['wr_id']}'
        ");
    }
}

if (!empty($next_href)) {
    $href = html_entity_decode($next_href);
    parse_str(parse_url($href, PHP_URL_QUERY), $q);

    if (!empty($q['wr_id'])) {
        $next_sub = sql_fetch("
            select wr_1
            from {$write_table}
            where wr_id = '{$q['wr_id']}'
        ");
    }
}
?>



    <!-- 링크 버튼 시작 { -->
    <div id="bo_v_nav">
        <?
         ?>
<?php if ($prev_href) { ?>
<a href="<?=$prev_href?>" class="view-nav-item prev">
    <span class="nav-label">다음글</span>
    <div class="nav-content">
        <strong class="nav-title">
            <?=get_text($prev_wr_subject)?>
        </strong>

        <?php if (!empty($prev_sub['wr_1'])) { ?>
        <div class="nav-subtitle">
            <?=get_text($prev_sub['wr_1'])?>
        </div>
        <?php } ?>
    </div>
</a>
<?php } ?>

<?php if ($next_href) { ?>
<a href="<?=$next_href?>" class="view-nav-item next">
    <span class="nav-label">이전글</span>
    <div class="nav-content">
        <strong class="nav-title">
            <?=get_text($next_wr_subject)?>
        </strong>

        <?php if (!empty($next_sub['wr_1'])) { ?>
        <div class="nav-subtitle">
            <?=get_text($next_sub['wr_1'])?>
        </div>
        <?php } ?>
    </div>
</a>
<?php } ?>

</div>


<div class="contents">
    <?php include_once(G5_BBS_PATH.'/view_comment.php'); ?>
</div>


        <div class="bo_v_com">
            <? if ($update_href) { ?><a href="<? echo $update_href ?>" class="ui-btn">수정</a><? } ?>


            <a href="<? echo $list_href ?>" class="ui-btn">목록</a>
                    </div>
        <?
         ?>
    </div>
    <!-- } 링크 버튼 끝 -->

</div>



<script>

$('.send_memo').on('click', function() {
    var target = $(this).attr('href');
    window.open(target, 'memo', "width=500, height=300");
    return false;
});


<? if ($board['bo_download_point'] < 0) { ?>
$(function() {
    $("a.view_file_download").click(function() {
        if(!g5_is_member) {
            alert("다운로드 권한이 없습니다.\n회원이시라면 로그인 후 이용해 보십시오.");
            return false;
        }

        var msg = "파일을 다운로드 하시면 포인트가 차감(<? echo number_format($board['bo_download_point']) ?>점)됩니다.\n\n포인트는 게시물당 한번만 차감되며 다음에 다시 다운로드 하셔도 중복하여 차감하지 않습니다.\n\n그래도 다운로드 하시겠습니까?";

        if(confirm(msg)) {
            var href = $(this).attr("href")+"&js=on";
            $(this).attr("href", href);

            return true;
        } else {
            return false;
        }
    });
});
<? } ?>

function board_move(href)
{
    window.open(href, "boardmove", "left=50, top=50, width=500, height=550, scrollbars=1");
}
</script>

<script>
$(function() {
    $("a.view_image").click(function() {
        window.open(this.href, "large_image", "location=yes,links=no,toolbar=no,top=10,left=10,width=10,height=10,resizable=yes,scrollbars=no,status=no");
        return false;
    });

    // 추천, 비추천
    $("#good_button, #nogood_button").click(function() {
        var $tx;
        if(this.id == "good_button")
            $tx = $("#bo_v_act_good");
        else
            $tx = $("#bo_v_act_nogood");

        excute_good(this.href, $(this), $tx);
        return false;
    });

    $("#bo_v_atc").viewimageresize();
});

function excute_good(href, $el, $tx)
{
    $.post(
        href,
        { js: "on" },
        function(data) {
            if(data.error) {
                alert(data.error);
                return false;
            }

            if(data.count) {
                $el.find("strong").text(number_format(String(data.count)));
                if($tx.attr("id").search("nogood") > -1) {
                    $tx.text("이 글을 비추천하셨습니다.");
                    $tx.fadeIn(200).delay(2500).fadeOut(200);
                } else {
                    $tx.text("이 글을 추천하셨습니다.");
                    $tx.fadeIn(200).delay(2500).fadeOut(200);
                }
            }
        }, "json"
    );
}
</script>
<!-- } 게시글 읽기 끝 -->
<script>
(function () {
    const target = document.getElementById('bo_v_con');
    if (!target) return;

    const elements = target.querySelectorAll('*');
    const originalSizes = new Map();

    const editorBasePt = 10;
    const minPx = 16;
    const PT_TO_PX = 1.333;

    elements.forEach(el => {
        const style = window.getComputedStyle(el);
        let px = parseFloat(style.fontSize);

        if (isNaN(px)) px = minPx;

        let base, ratio;

        if (px < minPx) {
            const pt = px / PT_TO_PX;
            base = minPx;
            ratio = pt / editorBasePt;
        } else {
            base = px;
            ratio = 1;
        }

        originalSizes.set(el, { base, ratio });
    });

    let scale = 1;

    function apply() {
        elements.forEach(el => {
            const { base, ratio } = originalSizes.get(el);
            el.style.fontSize = (base * ratio * scale) + 'px';
        });
    }

    window.fontUp = function () {
        scale += 0.1;
        apply();
    };

    window.fontDown = function () {
        if (scale > 0.5) {
            scale -= 0.1;
            apply();
        }
    };

    window.fontReset = function () {
        scale = 1;
        apply();
    };

    apply();
})();

</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleImg = document.getElementById('indentToggleImg');
    const content = document.getElementById('bo_v_con');
    const wrap = document.getElementById('board-viewer-wrap');
    const imgOn = '/img/space_on.png';
    const imgOff = '/img/space_off.png';
    const modeToggle = document.getElementById('modeToggleImg');
    const originImg = '/img/light.png';
    const darkImg = '/img/dark.png';
    let darkMode = false;

    wrap.classList.add('light-mode');
    modeToggle.src = darkImg;

    function fixIndentForMedia() {
        content.querySelectorAll('p').forEach(p => {
            if (p.querySelector('img') || p.querySelector('iframe')) {
                p.style.textIndent = '0';
                p.style.marginLeft = '0';
            } else {
                p.style.textIndent = '';
                p.style.marginLeft = '';
            }
        });
    }

    content.classList.add('indent-on');
    toggleImg.src = imgOn;
    fixIndentForMedia();

    toggleImg.addEventListener('click', function () {
        if (content.classList.contains('indent-on')) {
            content.classList.remove('indent-on');
            toggleImg.src = imgOff;
        } else {
            content.classList.add('indent-on');
            toggleImg.src = imgOn;
        }
        fixIndentForMedia();
    });

    if (modeToggle) {
        modeToggle.addEventListener('click', function () {
            darkMode = !darkMode;

            if (darkMode) {
                wrap.classList.remove('light-mode');
                wrap.classList.add('dark-mode');
                modeToggle.src = originImg;
            } else {
                wrap.classList.remove('dark-mode');
                wrap.classList.add('light-mode');
                modeToggle.src = darkImg;
            }
        });
    }
});

</script>
