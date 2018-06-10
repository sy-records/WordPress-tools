<?php
/**
 * @authors ShenYan (52o@qq52o.cn)
 * @date    2018-06-06 09:43:21
 * @boke    https://qq52o.me
 */
//添加友链按钮
add_filter('pre_option_link_manager_enabled', '__return_true');

//显示评论@回复了谁 不入数据库
function comment_add_at($comment_text, $comment = '') {
	if ($comment->comment_parent > 0) {
		$comment_text = '<a href="#comment-' . $comment->comment_parent . '">@' . get_comment_author($comment->comment_parent) . '</a> ' . $comment_text;
	}

	return $comment_text;
}
add_filter('comment_text', 'comment_add_at', 20, 2);

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

//防止作者信息泄露
function change_comment_or_body_classes($classes, $comment_id) {
	global $wp_query;
	$comment = get_comment($comment_id);
	$user = get_userdata($comment->user_id);
	$comment_author = 'comment-author-' . sanitize_html_class($user->user_nicename, $comment->user_id);
	$author = $wp_query->get_queried_object();
	$archive_author = 'author-' . sanitize_html_class($author->user_nicename, $author->ID);
	$archive_author_id = 'author-' . $author->ID;
	foreach ($classes as $key => $class) {
		switch ($class) {
		case $comment_author:
// $classes[$key] = 'comment-author-' . sanitize_html_class( $comment->comment_author, $comment->comment_author );
			$classes[$key] = 'comment-author-' . sanitize_html_class($comment->user_id);
			break;
		case $archive_author:
// $classes[$key] = 'author-' . sanitize_html_class( get_the_author_meta( 'display_name' ), get_the_author_meta( 'display_name' ) );
			$classes[$key] = 'author-' . sanitize_html_class($author->ID);
			break;
		case $archive_author_id:
			$classes[$key] = '';
			break;
		}
	}
	return $classes;
}
add_filter('comment_class', 'change_comment_or_body_classes', 10, 4);
add_filter('body_class', 'change_comment_or_body_classes', 10, 4);