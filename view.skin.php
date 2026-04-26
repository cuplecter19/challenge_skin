<?php
if (!defined('_GNUBOARD_')) exit;

include_once(dirname(__FILE__).'/_functions.php');
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
// Font Awesome 6 Free (플로팅 버튼 아이콘용)
add_stylesheet('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">', 0);

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

// 플로팅 버튼용 삭제 URL 및 권한 계산
$_can_action = ($is_admin
    || ($view['mb_id'] && isset($member['mb_id']) && $view['mb_id'] == $member['mb_id'])
    || (!$view['mb_id'] && $update_href)); // 비회원 글: update_href 있으면 삭제도 허용(비번 확인은 delete.php가 처리)
$_delete_url = G5_BBS_URL.'/delete.php?bo_table='.urlencode($bo_table).'&wr_id='.(int)$view['wr_id'].$qstr;
?>

<style>
/* ===== 플로팅 액션 버튼 ===== */
.float-action-bar {
    position: fixed;
    right: 22px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 900;
}
.float-action-bar .fab {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    text-decoration: none;
    font-size: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.22);
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
    border: none;
    cursor: pointer;
    background: #888;
}
.float-action-bar .fab:hover {
    transform: scale(1.13);
    box-shadow: 0 4px 16px rgba(0,0,0,0.32);
}
.float-action-bar .fab-edit  { background: #6b8fa8; }
.float-action-bar .fab-del   { background: #a86b6b; }
.float-action-bar .fab-list  { background: #7a8a6b; }

/* 툴팁 */
.float-action-bar .fab-wrap {
    position: relative;
}
.float-action-bar .fab-tip {
    position: absolute;
    right: 52px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.72);
    color: #fff;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 4px;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.15s;
}
.float-action-bar .fab-wrap:hover .fab-tip {
    opacity: 1;
}

/* 모바일: 버튼 크기 축소 */
@media (max-width: 600px) {
    .float-action-bar {
        right: 10px;
        gap: 8px;
    }
    .float-action-bar .fab {
        width: 36px;
        height: 36px;
        font-size: 13px;
    }
    .float-action-bar .fab-tip {
        display: none;
    }
}
</style>

<script src="<? echo G5_JS_URL; ?>/viewimageresize.js"></script>

<!-- ★ 플로팅 액션 버튼 -->
<div class="float-action-bar">
    <?php if ($update_href) { ?>
    <div class="fab-wrap">
        <a href="<?php echo $update_href ?>" class="fab fab-edit" title="수정">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>
        <span class="fab-tip">수정</span>
    </div>
    <?php } ?>

    <?php if ($_can_action) { ?>
    <div class="fab-wrap">
        <a href="<?php echo htmlspecialchars($_delete_url, ENT_QUOTES, 'UTF-8') ?>"
           class="fab fab-del" title="삭제"
           onclick="return confirm('정말 삭제하시겠습니까?\n\n삭제한 글은 복구할 수 없습니다.');">
            <i class="fa-solid fa-trash"></i>
        </a>
        <span class="fab-tip">삭제</span>
    </div>
    <?php } ?>

    <div class="fab-wrap">
        <a href="<?php echo $list_href ?>" class="fab fab-list" title="목록">
            <i class="fa-solid fa-list"></i>
        </a>
        <span class="fab-tip">목록</span>
    </div>
</div>

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
        for ($i=0; $i<count($view['file']); $i++) {
            if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'] && !$view['file'][$i]['view']) {
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
        ?>
        </ul>

<!-- 본문 폰트 조절 버튼 -->
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
    <?}?>
</div>

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
$content = emote_ev($content);
echo $content;
?></div>

<?php
/* ===================================================================
 * ★ HTML 채팅 로그 렌더링 (본문 최하단)
 * html_log_view.php 프록시를 통해 서빙 → /data/file/ .htaccess 403 우회
 * =================================================================== */
$_vhl_file = isset($view['wr_html_log']) ? trim($view['wr_html_log']) : '';
if ($_vhl_file != '') {
    if (strpos($_vhl_file, '/') === false && strpos($_vhl_file, '\\') === false
        && preg_match('/^[A-Za-z0-9_.]+$/', $_vhl_file)) {
        $_vhl_proxy_url = $board_skin_url.'/html_log_view.php'
            .'?bo_table='.urlencode($bo_table)
            .'&wr_id='.(int)$view['wr_id'];
        // iframe 고유 ID (페이지에 여러 iframe이 있을 경우 구분용)
        $_vhl_iframe_id = 'chat_log_frame_'.(int)$view['wr_id'];
        ?>
<div class="chat-log-section">
    <div class="chat-log-container">
        <iframe
            id="<?php echo htmlspecialchars($_vhl_iframe_id, ENT_QUOTES, 'UTF-8'); ?>"
            src="<?php echo htmlspecialchars($_vhl_proxy_url, ENT_QUOTES, 'UTF-8'); ?>"
            class="chat-log-frame"
            scrolling="auto"
            frameborder="0">
        </iframe>
    </div>
</div>
        <?php
    }
}
unset($_vhl_file, $_vhl_proxy_url);
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
        $prev_sub = sql_fetch("select wr_1 from {$write_table} where wr_id = '{$q['wr_id']}'");
    }
}

if (!empty($next_href)) {
    $href = html_entity_decode($next_href);
    parse_str(parse_url($href, PHP_URL_QUERY), $q);
    if (!empty($q['wr_id'])) {
        $next_sub = sql_fetch("select wr_1 from {$write_table} where wr_id = '{$q['wr_id']}'");
    }
}
?>

    <!-- 링크 버튼 시작 { -->
    <div id="bo_v_nav">
<?php if ($prev_href) { ?>
<a href="<?=$prev_href?>" class="view-nav-item prev">
    <span class="nav-label">다음글</span>
    <div class="nav-content">
        <strong class="nav-title"><?=get_text($prev_wr_subject)?></strong>
        <?php if (!empty($prev_sub['wr_1'])) { ?>
        <div class="nav-subtitle"><?=get_text($prev_sub['wr_1'])?></div>
        <?php } ?>
    </div>
</a>
<?php } ?>

<?php if ($next_href) { ?>
<a href="<?=$next_href?>" class="view-nav-item next">
    <span class="nav-label">이전글</span>
    <div class="nav-content">
        <strong class="nav-title"><?=get_text($next_wr_subject)?></strong>
        <?php if (!empty($next_sub['wr_1'])) { ?>
        <div class="nav-subtitle"><?=get_text($next_sub['wr_1'])?></div>
        <?php } ?>
    </div>
</a>
<?php } ?>
</div>

<div class="contents">
    <?php include_once(G5_BBS_PATH.'/view_comment.php'); ?>
</div>

<!-- ★ 하단 수정/목록 버튼 제거 → 플로팅 버튼으로 대체됨 -->

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
        var msg = "파일을 다운로드 하시면 포인트가 차감(<? echo number_format($board['bo_download_point']) ?>점)됩니다.\n\n포인트는 게시물당 한번만 차감되며 다운로드 권한이 없으시면 차감되지 않습니다.\n\n다운로드 하시겠습니까?";
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

function board_move(href) {
    window.open(href, "boardmove", "left=50, top=50, width=500, height=550, scrollbars=1");
}
</script>

<script>
$(function() {
    $("a.view_image").click(function() {
        window.open(this.href, "large_image", "location=yes,links=no,toolbar=no,top=10,left=10,width=10,height=10,resizable=yes,scrollbars=no,status=no");
        return false;
    });

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

function excute_good(href, $el, $tx) {
    $.post(href, { js: "on" }, function(data) {
        if(data.error) { alert(data.error); return false; }
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
    }, "json");
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

    window.fontUp   = function () { scale += 0.1; apply(); };
    window.fontDown = function () { if (scale > 0.5) { scale -= 0.1; apply(); } };
    window.fontReset= function () { scale = 1; apply(); };
    apply();
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleImg  = document.getElementById('indentToggleImg');
    const content    = document.getElementById('bo_v_con');
    const wrap       = document.getElementById('board-viewer-wrap');
    const imgOn      = '/img/space_on.png';
    const imgOff     = '/img/space_off.png';
    const modeToggle = document.getElementById('modeToggleImg');
    const originImg  = '/img/light.png';
    const darkImg    = '/img/dark.png';
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
