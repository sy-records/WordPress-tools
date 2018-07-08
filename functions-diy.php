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

// 最热文章
function most_comm_posts($days = 7, $nums = 10) {
	//$days参数限制时间值，单位为‘天’，默认是7天；$nums是要显示文章数量
	global $wpdb;
	$today = date("Y-m-d H:i:s"); //获取今天日期时间
	$daysago = date("Y-m-d H:i:s", strtotime($today) - ($days * 24 * 60 * 60)); //Today - $days
	$result = $wpdb->get_results("SELECT comment_count, ID, post_title, post_date FROM $wpdb->posts WHERE post_date BETWEEN '$daysago' AND '$today' ORDER BY comment_count DESC LIMIT 0 , $nums");
	$output = '';
	if (empty($result)) {
		$output = '<li>None data.</li>';
	} else {
		foreach ($result as $topten) {
			$postid = $topten->ID;
			$title = $topten->post_title;
			$commentcount = $topten->comment_count;
			if ($commentcount != 0) {
				$output .= '<li><a href="' . get_permalink($postid) . '" title="' . $title . '">' . $title . '</a></li>';
			}
		}
	}
	echo $output;
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

//后台登陆数学验证码
function myplugin_add_login_fields() {
	//获取两个随机数, 范围0~9
	$num1 = rand(0, 9);
	$num2 = rand(0, 9);
	//最终网页中的具体内容
	echo "<p><label for='math' class='small'>验证码</label><br /> $num1 + $num2 = ?<input type='text' name='sum' class='input' value='' size='25' tabindex='4'>"
		. "<input type='hidden' name='num1' value='$num1'>"
		. "<input type='hidden' name='num2' value='$num2'></p>";
}
add_action('login_form', 'myplugin_add_login_fields');
function login_val() {
	$sum = $_POST['sum']; //用户提交的计算结果
	switch ($sum) {
	//得到正确的计算结果则直接跳出
	case $_POST['num1'] + $_POST['num2']:break;
	//未填写结果时的错误讯息
	case null:wp_die('错误: 请输入验证码.');
		break;
	//计算错误时的错误讯息
	default:wp_die('错误: 验证码错误,请重试.');
	}
}
add_action('login_form_login', 'login_val');

//完全禁用 wp-json
if (version_compare(get_bloginfo('version'), '4.7', '>=')) {
	function disable_rest_api($access) {
		return new WP_Error('rest_cannot_acess', '无访问权限', array('status' => 403));
	}
	add_filter('rest_authentication_errors', 'disable_rest_api');
} else {
	// Filters for WP-API version 1.x
	add_filter('json_enabled', '__return_false');
	add_filter('json_jsonp_enabled', '__return_false');
	// Filters for WP-API version 2.x
	add_filter('rest_enabled', '__return_false');
	add_filter('rest_jsonp_enabled', '__return_false');
}

// 移除头部 wp-json 标签和 HTTP header 中的 link
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('template_redirect', 'rest_output_link_header', 11);

//防止 WordPress 任意文件删除漏洞
add_filter('wp_update_attachment_metadata', 'rips_unlink_tempfix');
function rips_unlink_tempfix($data) {
	if (isset($data['thumb'])) {
		$data['thumb'] = basename($data['thumb']);
	}

	return $data;
}

// 博客后台登录失败时发送邮件通知管理员
function wp_login_failed_notify() {
	date_default_timezone_set('PRC');
	$admin_email = get_bloginfo('admin_email');
	$to = $admin_email;
	$subject = '【登录失败】有人使用了错误的用户名或密码登录『' . get_bloginfo('name') . '』';
	$message = '<span style="color:red; font-weight: bold;">『' . get_bloginfo('name') . '』有一条登录失败的记录产生，若登录操作不是您产生的，请及时注意网站安全！</span><br /><br />';
	$message .= '登录名：' . $_POST['log'];
	$message .= '<br />尝试的密码：' . $_POST['pwd'];
	$message .= '<br />登录的时间：' . date("Y-m-d H:i:s");
	$message .= '<br />登录的 IP：' . $_SERVER['REMOTE_ADDR'];
	$message .= '<br /><br />';
	$message .= '您可以： <a href="' . get_bloginfo('url') . '" target="_target">进入' . get_bloginfo('name') . '»</a>';
	wp_mail($to, $subject, $message, "Content-Type: text/html; charset=UTF-8");
}
add_action('wp_login_failed', 'wp_login_failed_notify');

//页面伪静态
add_action('init', 'html_page_permalink', -1);

function html_page_permalink() {
	global $wp_rewrite;
	if (!strpos($wp_rewrite->get_page_permastruct(), '.html')) {
		$wp_rewrite->page_structure = $wp_rewrite->page_structure . '.html';
	}
}