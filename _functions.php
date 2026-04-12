<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('youtube_auto_embed_view')) {
    function youtube_auto_embed_view($content) {
        $pattern = '#<a[^>]+href=["\']https?://(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]+)[^"\']*["\'][^>]*>.*?</a>#i';
        $content = preg_replace_callback($pattern, function ($m) {
            return '
<span class="video-inline">
    <iframe
        src="https://www.youtube.com/embed/'.$m[1].'"
        allowfullscreen
        loading="lazy">
    </iframe>
</span>';
        }, $content);
        return $content;
    }
}