<?php
/**
 * @authors ShenYan (52o@qq52o.cn)
 * @date    2018-06-06 09:43:21
 * @boke    https://qq52o.me
 */
//添加友链按钮
add_filter('pre_option_link_manager_enabled', '__return_true');
//显示评论@回复了谁 不入数据库
function comment_add_at( $comment_text, $comment = '') {
    if( $comment->comment_parent > 0) {
        $comment_text = '<a href="#comment-' . $comment->comment_parent . '">@'.get_comment_author( $comment->comment_parent ) . '</a> ' . $comment_text;
    }

    return $comment_text;
}
add_filter( 'comment_text' , 'comment_add_at', 20, 2);
//让WordPress小工具文本支持PHP
add_filter('widget_text', 'php_text', 99);
function php_text($text) {
    if (strpos($text, '<' . '?') !== false) {
        ob_start();
        eval('?' . '>' . $text);
        $text = ob_get_contents();
        ob_end_clean();
    }
    return $text;
}