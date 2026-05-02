<?php
include_once($_SERVER['DOCUMENT_ROOT'].'/common.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/head.sub.php');

$sql    = "SELECT * FROM {$g5['emoticon_table']}";
$result = sql_query($sql);

// 삽입 대상 textarea id (파라미터로 받음, 기본값 wr_content)
$target_id = isset($_GET['target_id'])
    ? htmlspecialchars(trim($_GET['target_id']), ENT_QUOTES, 'UTF-8')
    : 'wr_content';

add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/style.emoticon.css">', 0);
?>

<div id="emoticon_page">
    <div id="emoticon_head"></div>
    <div id="page_title">
        이모티콘
        <i id="emoticon_line"></i>
    </div>

    <div id="emoticon_content">
        <ul>
        <?php
        $i = 0;
        while ($row = sql_fetch_array($result)) {
            $i++;
            $img_dir  = rtrim(dirname($row['me_img']), '/');
            $img_file = rawurlencode(basename($row['me_img']));
            $img_path = G5_URL . ($img_dir ? "/$img_dir/$img_file" : "/$img_file");
        ?>
            <li data-text="<?php echo htmlspecialchars($row['me_text'], ENT_QUOTES, 'UTF-8') ?>"
                style="cursor:pointer;">
                <em>
                    <img src="<?php echo htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?php echo htmlspecialchars($row['me_text'], ENT_QUOTES, 'UTF-8') ?>" />
                </em>
                <span><?php echo htmlspecialchars($row['me_text'], ENT_QUOTES, 'UTF-8') ?></span>
            </li>
        <?php
        }
        if ($i === 0) { ?>
            <li class="no-data">등록된 이모티콘이 없습니다.</li>
        <?php } ?>
        </ul>
    </div>
    <div id="emoticon_footer"></div>
</div>

<!-- ★ 클릭 시 opener textarea에 이모티콘 텍스트 삽입 -->
<script>
(function () {
    var targetId = '<?php echo $target_id ?>';

    var items = document.querySelectorAll('#emoticon_content li[data-text]');
    for (var k = 0; k < items.length; k++) {
        (function (li) {
            li.addEventListener('click', function () {
                var text = li.getAttribute('data-text');
                try {
                    var op = window.opener;
                    if (!op) return;

                    var el = op.document.getElementById(targetId);
                    if (!el) return;

                    /* ── contenteditable 에디터(BearEditor 등) 대응 ── */
                    var editorEl = op.document.getElementById(targetId + '_editor');
                    if (editorEl && editorEl.isContentEditable) {
                        editorEl.focus();
                        var sel = op.getSelection();
                        if (sel && sel.rangeCount) {
                            var range = sel.getRangeAt(0);
                            range.deleteContents();
                            range.insertNode(op.document.createTextNode(text));
                            range.collapse(false);
                        } else {
                            editorEl.innerHTML += text;
                        }
                        /* hidden textarea도 동기화 */
                        el.value = editorEl.innerText || editorEl.textContent;
                        try {
                            if (typeof op.window['bearEditorSync_' + targetId] === 'function') {
                                op.window['bearEditorSync_' + targetId]();
                            }
                        } catch (e2) {}
                    } else {
                        /* ── 일반 textarea ── */
                        var s = el.selectionStart || 0;
                        var e = el.selectionEnd   || s;
                        el.value = el.value.substring(0, s) + text + el.value.substring(e);
                        el.focus();
                        el.selectionStart = el.selectionEnd = s + text.length;
                    }
                } catch (err) {
                    /* cross-origin 등 예외 무시 */
                }
                window.close();
            });
        })(items[k]);
    }
})();
</script>

<?php include_once($_SERVER['DOCUMENT_ROOT'].'/tail.sub.php'); ?>
