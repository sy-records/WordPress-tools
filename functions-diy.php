<?php
// +----------------------------------------------------------------------
// | WordPress实用代码段
// +----------------------------------------------------------------------
// | IhadPHP [ 学无止境，编码不止，开源为盼 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://qq52o.me, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 沈唁 <52o@qq52o.cn>
// +----------------------------------------------------------------------

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

//禁用所有文章类型的修订版本
add_filter('wp_revisions_to_keep', 'specs_wp_revisions_to_keep', 10, 2);
function specs_wp_revisions_to_keep($num, $post) {
	return 0;
}

//禁用自动保存
add_action('wp_print_scripts', 'disable_autosave');
function disable_autosave() {
	wp_deregister_script('autosave');
}

/**
 * 纯代码实现熊掌号H5页面结构化改造添加 JSON_LD 数据
 * https://qq52o.me/1448.html
 */
//获取文章/页面摘要
function fanly_excerpt($len = 220) {
	if (is_single() || is_page()) {
		global $post;
		if ($post->post_excerpt) {
			$excerpt = $post->post_excerpt;
		} else {
			if (preg_match('/<p>(.*)<\/p>/iU', trim(strip_tags($post->post_content, "<p>")), $result)) {
				$post_content = $result['1'];
			} else {
				$post_content_r = explode("\n", trim(strip_tags($post->post_content)));
				$post_content = $post_content_r['0'];
			}
			$excerpt = preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,0}' . '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $len . '}).*#s', '$1', $post_content);
		}
		return str_replace(array("\r\n", "\r", "\n"), "", $excerpt);
	}
}
//优先获取文章中的三张图，否则依次获取自定义图片/特色缩略图/文章首图 last update 2017/11/23
function fanly_post_imgs() {
	global $post;
	$content = $post->post_content;
	preg_match_all('/<img .*?src=[\"|\'](.+?)[\"|\'].*?>/', $content, $strResult, PREG_PATTERN_ORDER);
	$n = count($strResult[1]);
	if ($n >= 3) {
		$src = $strResult[1][0] . '","' . $strResult[1][1] . '","' . $strResult[1][2];
	} else {
		// 这里的thumb 根据自己的主题而定
		if ($values = get_post_custom_values("thumb")) {
			//输出自定义域图片地址
			$values = get_post_custom_values("thumb");
			$src = $values[0];
		} elseif (has_post_thumbnail()) {
			//如果有特色缩略图，则输出缩略图地址
			$thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
			$src = $thumbnail_src[0];
		} else {
			//文章中获取
			if ($n > 0) {
				// 提取首图
				$src = $strResult[1][0];
			}
		}
	}
	return $src;
}

/**
 * WordPress防止网站被别人iframe框架调用的方法
 * link：https://qq52o.me/2393.html
 */
function break_out_of_frames() {
	if (!is_preview()) {
		echo "\n<script type=\"text/javascript\">";
		echo "\n<!--";
		echo "\nif (parent.frames.length > 0) { parent.location.href = location.href; }";
		echo "\n-->";
		echo "\n</script>\n\n";
	}
}
add_action('wp_head', 'break_out_of_frames');

//优化熊掌号主页显示 防止标题中的 - 被转义为实体符号
function html_entity_decode_title($title) {
	$title = html_entity_decode($title);
	return $title;
}
add_filter('the_title', 'html_entity_decode_title'); // 文章title - get_the_title()、the_title()
add_filter('wp_title', 'html_entity_decode_title'); // 网页title - wp_title()

/**
 * WordPress 站点纯代码屏蔽垃圾评论
 * https://qq52o.me/1723.html
 * @param  [type] $incoming_comment [description]
 * @return [type]                   [description]
 */
function syz_comment_post($incoming_comment) {
	$pattern = '/[一-龥]/u';
	$jpattern = '/[ぁ-ん]+|[ァ-ヴ]+/u';
	$ruattern = '/[А-я]+/u';
	$arattern = '/[؟-ض]+|[ط-ل]+|[م-م]+/u';
	$thattern = '/[ก-๛]+/u';
	if (preg_match($jpattern, $incoming_comment['comment_content'])) {
		wp_die("日文滚粗！Japanese Get out！日本語出て行け！");
	}
	if (preg_match($ruattern, $incoming_comment['comment_content'])) {
		wp_die("北方野人讲的话我们不欢迎！Russians, get away！Savage выйти из Русского Севера!");
	}
	if (preg_match($arattern, $incoming_comment['comment_content'])) {
		wp_die("不要用阿拉伯语！Please do not use Arabic！！من فضلك لا تستخدم اللغة العربية");
	}
	if (preg_match($thattern, $incoming_comment['comment_content'])) {
		wp_die("人妖你好，人妖再见！Please do not use Thai！กรุณาอย่าใช้ภาษาไทย！");
	}
	if (!preg_match($pattern, $incoming_comment['comment_content'])) {
		wp_die("写点汉字吧，博主外语很捉急！ Please write some chinese words！");
	}
	return ($incoming_comment);
}
add_filter('preprocess_comment', 'syz_comment_post');

//标题变为|
function Bing_title_separator_to_line() {
	return '|';
}
add_filter('document_title_separator', 'Bing_title_separator_to_line');

//彻底禁止WordPress缩略图
add_filter('add_image_size', create_function('', 'return 1;'));

//评论作者链接新窗口打开
function my_get_comment_author_link() {
	$url = get_comment_author_url($comment_ID);
	$author = get_comment_author($comment_ID);
	if (empty($url) || 'http://' == $url) {
		return $author;
	} else {
		return "<a href='$url' target='_blank' rel='external nofollow' class='url'>$author</a>";
	}

}
add_filter('get_comment_author_link', 'my_get_comment_author_link');

//给外部链接加上跳转
add_filter('the_content', 'the_content_nofollow', 999);
function the_content_nofollow($content) {
	preg_match_all('/<a(.*?)href="(.*?)"(.*?)>/', $content, $matches);
	if ($matches) {
		foreach ($matches[2] as $val) {
			if (strpos($val, '://') !== false && strpos($val, home_url()) === false && !preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i', $val)) {
				$content = str_replace("href=\"$val\"", "href=\"" . home_url() . "/go/?url=$val\" ", $content);
			}
		}
	}
	return $content;
}

//文章外链跳转伪静态版
add_filter('the_content', 'link_jump', 999);
function link_jump($content) {
	preg_match_all('/<a(.*?)href="(.*?)"(.*?)>/', $content, $matches);
	if ($matches) {
		foreach ($matches[2] as $val) {
			if (strpos($val, '://') !== false && strpos($val, home_url()) === false && !preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i', $val) && !preg_match('/(ed2k|thunder|Flashget|flashget|qqdl):\/\//i', $val)) {
				$content = str_replace("href=\"$val\"", "href=\"" . home_url() . "/go/" . base64_encode($val) . "\" rel=\"nofollow\"", $content);
			}
		}
	}
	return $content;
}

/**
 * WordPress添加随便看看 | 沈唁志
 * link：https://qq52o.me/165.html
 */
function random_postlite() {
	global $wpdb;
	$query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND post_password = '' AND post_status = 'publish' ORDER BY RAND() LIMIT 1";
	if (isset($_GET['random_cat_id'])) {
		$random_cat_id = (int) $_GET['random_cat_id'];
		$query = "SELECT DISTINCT ID FROM $wpdb->posts AS p INNER JOIN $wpdb->term_relationships AS tr ON (p.ID = tr.object_id AND tr.term_taxonomy_id = $random_cat_id) INNER JOIN $wpdb->term_taxonomy AS tt ON(tr.term_taxonomy_id = tt.term_taxonomy_id AND taxonomy = 'category') WHERE post_type = 'post' AND post_password = '' AND post_status = 'publish' ORDER BY RAND() LIMIT 1";
	}
	if (isset($_GET['random_post_type'])) {
		$post_type = preg_replace('|[^a-z]|i', '', $_GET['random_post_type']);
		$query = "SELECT ID FROM $wpdb->posts WHERE post_type = '$post_type' AND post_password = '' AND post_status = 'publish' ORDER BY RAND() LIMIT 1";
	}
	$random_id = $wpdb->get_var($query);
	wp_redirect(get_permalink($random_id));
	exit;
}
if (isset($_GET['random'])) {
	add_action('template_redirect', 'random_postlite');
}

/**
 * WordPress有新评论微信提醒管理员 | 沈唁志
 * link：https://qq52o.me/1092.html
 */
function sc_send($comment_id) {
	$text = '博客有一条新评论';
	$comment = get_comment($comment_id);
	$desp = $comment->comment_content;
	$key = '你的appkey';
	$postdata = http_build_query(
		array(
			'text' => $text,
			'desp' => $desp,
		)
	);

	$opts = array('http' => array(
		'method' => 'POST',
		'header' => 'Content-type: application/x-www-form-urlencoded',
		'content' => $postdata,
	),
	);
	$context = stream_context_create($opts);
	return $result = file_get_contents('http://sc.ftqq.com/' . $key . '.send', false, $context);
}
add_action('comment_post', 'sc_send', 19, 2);

//WordPress 文章版权申明
add_filter('the_content', 'syz_copyright');
function syz_copyright($content) {
	$content .= '<p>除非注明，否则均为<a href="' . get_bloginfo('url') . '" target="_blank">' . get_bloginfo('name') . '</a>原创文章，转载必须以链接形式标明本文链接</p>';
	$content .= '<p>本文链接：<a title="' . get_the_title() . '" href="' . get_permalink() . '" target="_blank">' . get_permalink() . '</a></p>';
	return $content;
}

//移除登录页面标题中的“ — WordPress”
add_filter('login_title', 'remove_login_title', 10, 2);
function remove_login_title($login_title, $title) {
	return $title . ' &lsaquo; ' . get_bloginfo('name');
}

//移除后台页面标题中的“ — WordPress”
add_filter('admin_title', 'remove_admin_title', 10, 2);
function remove_admin_title($admin_title, $title) {
	return $title . ' &lsaquo; ' . get_bloginfo('name');
}

//WordPress 禁止可视化编辑模式
add_filter('user_can_richedit','__return_false');

//WordPress 移除管理员后台添加用户权限
add_action('init', 'remove_create_users');
function remove_create_users() {
	global $wp_roles;
	if ( ! isset( $wp_roles ) )$wp_roles = new WP_Roles();
	//$wp_roles->add_cap( 'administrator', 'create_users' );//添加管理员添加用户的权限
	$wp_roles->remove_cap( 'administrator', 'create_users' );//移除管理员添加用户的权限
}

// 同时删除 head 和 feed 中的 WP 版本号
add_filter('the_generator', 'syz_remove_wp_version');
function syz_remove_wp_version() { return '';}

//移除 WordPress 文章标题前的“私密/密码保护”提示文字
function remove_title_prefix($content) {
	return '%s';//这个不能省略
}
add_filter('private_title_format', 'remove_title_prefix');//私密
add_filter('protected_title_format', 'remove_title_prefix');//密码保护

//WordPress 中文名、数字名图片上传自动重命名
add_filter('sanitize_file_name','custom_upload_name', 5, 1 );
function custom_upload_name($file){
	$info	= pathinfo($file);
	$ext	= empty($info['extension']) ? '' : '.' . $info['extension'];
	$name	= basename($file, $ext);
	if(preg_match("/[一-龥]/u",$file)){//中文换名
		$file	= substr(md5($name), 0, 20) . rand(00,99) . $ext;//截取前 20 位 MD5 长度，加上两位随机
	}elseif(is_numeric($name)){//数字换名
		$file	= substr(md5($name), 0, 20) . rand(00,99) . $ext;//截取前 20 位 MD5 长度，加上两位随机
	}
}

//移除 WordPress 头部加载 DNS 预获取（dns-prefetch）
function remove_dns_prefetch( $hints, $relation_type ) {
    if ( 'dns-prefetch' === $relation_type ) {
        return array_diff( wp_dependencies_unique_hosts(), $hints );
    }
 
    return $hints;
}
add_filter( 'wp_resource_hints', 'remove_dns_prefetch', 10, 2 );

//禁止 WordPress 动态文章 ID 访问
add_action('parse_query', 'disable_permalink_isvars_p');
function disable_permalink_isvars_p( $wp_query, $error = true ) {
	if(get_query_var('p') && !is_preview()){
		$wp_query->query_vars['p'] = false;
		$wp_query->query['p'] = false;
		// to error
		if ( $error == true ) $wp_query->is_404 = true;
	}
}

//WordPress FEED utm_source
//remove_filter( 'the_permalink_rss', 'filter_the_permalink_rss', 10, 2 ); 
add_filter( 'the_permalink_rss', 'filter_the_permalink_rss', 10, 2 );
function filter_the_permalink_rss() { 
	$link =  esc_url( get_permalink() . '?utm_source=feed' );
    return $link; 
};
//remove_filter( 'comments_link_feed', 'filter_comments_link_feed', 10, 2 ); 
add_filter( 'comments_link_feed', 'filter_comments_link_feed', 10, 2 );
function filter_comments_link_feed() {
	$hash = get_comments_number( $post_id ) ? '#comments' : '#respond';
    $comments_link = get_permalink( $post_id ) . '?utm_source=feed' . $hash;
	$link = esc_url( $comments_link );
    return $link; 
}
add_filter( 'the_guid', 'filter_the_guid', 10, 2 );
function filter_the_guid($link, $int){
	if(!is_feed())return;
	$link = esc_url( $link . '&utm_source=feed' );
    return $link; 
}

//使用 smtp 发邮件
add_action('phpmailer_init', 'syz_mail_smtp');
function syz_mail_smtp( $phpmailer ) {
	$phpmailer->IsSMTP();
	$phpmailer->SMTPAuth = true;//启用 SMTPAuth 服务
	$phpmailer->Port = 465;//MTP 邮件发送端口，这个和下面的 SSL 验证对应，如果这里填写 25，则下面参数为空
	$phpmailer->SMTPSecure ="ssl";//是否验证 ssl，与 MTP 邮件发送端口对应，如果不填写，则上面的端口须为 25
	$phpmailer->Host = "smtp.exmail.qq.com";//邮箱的 SMTP 服务器地址，目前 smtp.exmail.qq.com 为 QQ 邮箱和腾讯企业邮箱 SMTP
	$phpmailer->Username = "sy_records@qq.com";//你的邮箱地址
	$phpmailer->Password ="***************";//你的邮箱登录密码
}
//发件地址记得和 smtp 邮箱一致即可
add_filter( 'wp_mail_from', 'syz_wp_mail_from' );
function syz_wp_mail_from() {
	return 'sy_records@qq.com';
}

//WordPress首页排除隐藏指定分类文章
function exclude_category_in_home( $query ) {  
    if ( $query->is_home ) {//是否首页  
        $query->set( 'cat', '-1, -2' );  //排除的指定分类id  
    }  
    return $query;  
}  
add_filter( 'pre_get_posts', 'exclude_category_in_home' );

// 去掉评论中网址超链接
remove_filter('comment_text', 'make_clickable', 9);

//移除Wordpress后台顶部左上角的W图标
function annointed_admin_bar_remove() {
        global $wp_admin_bar;
        /* Remove their stuff */
        $wp_admin_bar->remove_menu('wp-logo');
}
add_action('wp_before_admin_bar_render', 'annointed_admin_bar_remove', 0);

//移除Wordpress后台顶部左上角的W图标
add_action( 'admin_bar_menu', 'remove_wp_logo', 999 );

function remove_wp_logo( $wp_admin_bar ) {
    $wp_admin_bar->remove_node( 'wp-logo' );
}

// 替换 WordPress 默认 Emoji 资源地址
function change_wp_emoji_baseurl($url) {
	return set_url_scheme('//twemoji.maxcdn.com/2/72x72/');
}
add_filter('emoji_url', 'change_wp_emoji_baseurl');

// WordPress 4.6 新增 SVG 格式资源
function change_wp_emoji_svgurl($url) {
	return set_url_scheme('//twemoji.maxcdn.com/svg/');
}
add_filter('emoji_svg_url', 'change_wp_emoji_svgurl');

//RSS Feed 延迟
function publish_later_on_feed($where) {
    global $wpdb;
    if ( is_feed() ) {
        $now = gmdate('Y-m-d H:i:s');
        //数据延迟2天显示，也就是feed只会输出截止到前天的数据，可根据实际需求自行修改
        $wait = '2';
        $device = 'DAY';
        $where .= " AND TIMESTAMPDIFF($device, $wpdb->posts.post_date_gmt, '$now') > $wait ";
    }
    return $where;
}
add_filter('posts_where', 'publish_later_on_feed');

// RSS 中添加查看全文链接
function feed_read_more($content) {
    return $content . '<p><a rel="bookmark" href="'.get_permalink().'" target="_blank">查看全文</a></p>';
}
add_filter ('the_excerpt_rss', 'feed_read_more');

//Feed 输出文章特色图像（缩略图）
function rss_post_thumbnail($content) {
	global $post; //查询全局文章
	if(has_post_thumbnail($post->ID)) { //如果有特色图像
		$output = get_the_post_thumbnail($post->ID) ; //获取缩略图
		$content = $output . $content ;
	}
	return $content;
}
add_filter('the_excerpt_rss', 'rss_post_thumbnail');
add_filter('the_content_feed', 'rss_post_thumbnail');

//禁用Feed订阅
function wp_disable_feed() {
	wp_die( __('<h1>抱歉，本站不支持订阅，请返回<a href="'. get_bloginfo('url') .'">首页</a></h1>') ); 
}
add_action('do_feed', 'wp_disable_feed', 1);
add_action('do_feed_rdf', 'wp_disable_feed', 1);
add_action('do_feed_rss', 'wp_disable_feed', 1);
add_action('do_feed_rss2', 'wp_disable_feed', 1);
add_action('do_feed_atom', 'wp_disable_feed', 1);

//feed输出自定义版权
function feed_copyright($content) {
        if(is_feed()) {
                $content.= "<blockquote>";
                $content.= '<div> 　&raquo; 转载请保留版权：<a title="沈唁志" href="https://qq52o.me/">沈唁志</a> &raquo; <a rel="bookmark" title="'.get_the_title().'" href="'.get_permalink().'">《'.get_the_title().'》</a></div>';
                $content.= '<div>　&raquo; 本文链接地址：<a rel="bookmark" title="'.get_the_title().'" href="'.get_permalink().'">'.get_permalink().'</a></div>';
                $content.= "</blockquote>";
        }
        return $content;
}
add_filter ('the_content', 'feed_copyright');

// 搜索关键字为空时自动跳转到首页
function redirect_blank_search( $query_variables ) {
    if ( isset( $_GET['s'] ) && empty( $_GET['s']) ) {
        wp_redirect( home_url() );
        exit;
    }
    return $query_variables;
}
add_filter( 'request', 'redirect_blank_search' );

// 关闭文章的标签功能
function unregister_post_tag() {
    unregister_taxonomy_for_object_type('post_tag', 'post');
}
add_action( 'init', 'unregister_post_tag' );

// 自定义代码高亮按钮
function appthemes_add_quicktags() {
    if (wp_script_is('quicktags')){
?>
		<script type="text/javascript">
			QTags.addButton( 'syz_PHP', 'PHP', '<pre><code class="language-php">', '</code></pre>', 'z', 'PHP 代码高亮');
			QTags.addButton( 'syz_HTML', 'HTML', '<pre ><code class="language-markup">', '</code></pre>', 'h', 'HTML 代码高亮');
			QTags.addButton( 'syz_CSS', 'CSS', '<pre><code class="language-css">', '</code></pre>', 'c', 'CSS 代码高亮');
			QTags.addButton( 'syz_Js', 'JavaScript', '<pre><code class="language-javascript">', '</code></pre>', 'j', 'JavaScript 代码高亮');
			QTags.addButton( 'syz_Bash', 'Bash', '<pre><code class="language-bash">', '</code></pre>', 'b', 'Bash 代码高亮');
			QTags.addButton( 'syz_Time', '时间轴', '<li><b>', '</b> </li>', 't', '发展历程和赞赏对应的时间轴');
		</script>
<?php
    }
}
add_action( 'admin_print_footer_scripts', 'appthemes_add_quicktags' );

// 清理 WordPress 菜单中的 classes
function cleanup_nav_menu_class( $classes ) {
    return array_intersect($classes, array(
        'current-menu-item',
        'menu-item-has-children'
    ));
}
add_filter('nav_menu_css_class', 'cleanup_nav_menu_class');
// 修改 gravatar 地址
function unblock_gravatar( $avatar ) {
    $avatar = str_replace( array( 'http://www.gravatar.com', 'http://0.gravatar.com', 'http://1.gravatar.com', 'http://2.gravatar.com' ), 'https://secure.gravatar.com', $avatar );
    return $avatar;
}
add_filter( 'get_avatar', 'unblock_gravatar' );

// 移除后台 Privacy 隐私政策设置页面及功能
add_action('admin_menu', function (){
	global $menu, $submenu;
 
	// 移除设置菜单下的隐私子菜单。
	unset($submenu['options-general.php'][45]);
 
	// 移除工具彩带下的相关页面
	remove_action( 'admin_menu', '_wp_privacy_hook_requests_page' );
 
	remove_filter( 'wp_privacy_personal_data_erasure_page', 'wp_privacy_process_personal_data_erasure_page', 10, 5 );
	remove_filter( 'wp_privacy_personal_data_export_page', 'wp_privacy_process_personal_data_export_page', 10, 7 );
	remove_filter( 'wp_privacy_personal_data_export_file', 'wp_privacy_generate_personal_data_export_file', 10 );
	remove_filter( 'wp_privacy_personal_data_erased', '_wp_privacy_send_erasure_fulfillment_notification', 10 );
 
	// Privacy policy text changes check.
	remove_action( 'admin_init', array( 'WP_Privacy_Policy_Content', 'text_change_check' ), 100 );
 
	// Show a "postbox" with the text suggestions for a privacy policy.
	remove_action( 'edit_form_after_title', array( 'WP_Privacy_Policy_Content', 'notice' ) );
 
	// Add the suggested policy text from WordPress.
	remove_action( 'admin_init', array( 'WP_Privacy_Policy_Content', 'add_suggested_content' ), 1 );
 
	// Update the cached policy info when the policy page is updated.
	remove_action( 'post_updated', array( 'WP_Privacy_Policy_Content', '_policy_page_updated' ) );
},9);

// wordpress上传文件重命名
function git_upload_filter($file) {
        $time = date("YmdHis");
        $file['name'] = $time . "" . mt_rand(1, 100) . "." . pathinfo($file['name'], PATHINFO_EXTENSION);
        return $file;
}
add_filter('wp_handle_upload_prefilter', 'git_upload_filter');

// 给评论框加上图片按钮，可评论回复图片
function auto_comment_image( $comment ) {
      global $allowedtags;
      $size = auto;
      $content = $comment["comment_content"];
      // alt部分自行填写
      $content = preg_replace('/((https|http|ftp):\/\/){1}.+?.(jpg|gif|bmp|bnp|png)$/is','<img src="$0" alt=""  style="width:'.$size.'; height:'.$size.'" />',$content);
      //允许发布img标签
      $allowedtags['img'] = array('src' => array (), 'alt' => array (),'class' =>array());
      // 重新给$comment赋值
      $comment["comment_content"] = $content;
      return $comment;
}

/**
 * 一键实现Wordpress站点维护功能
 */
function syz_wp_maintenance_mode(){
    if(!current_user_can('edit_themes') || !is_user_logged_in()){
        $logo = 'https://img.qq52o.me/wp-content/uploads/2018/10/qq52o-logo.png'; // 请将此图片地址换为自己站点的logo图片地址
        $blogname =  get_bloginfo('name');
        $blogdescription = get_bloginfo('description');
        wp_die('<div style="text-align:center"><img src="'.$logo.'" alt="'.$blogname.'" /><br /><br />'.$blogname.'正在例行维护中，请稍候...</div>', '站点维护中 - '.$blogname.' - '.$blogdescription ,array('response' => '503'));
    }
}
add_action('get_header', 'syz_wp_maintenance_mode');

// 防止在WordPress别人冒充博主发表评论
function syz_usecheck($incoming_comment) {
		$isSpam = 0;
		// 将以下代码中的 sy-records 改成博主昵称
		if (trim($incoming_comment['comment_author']) == 'sy-records')
				$isSpam = 1;
		// 将以下代码中的 email@qq52o.me 改成博主Email
		if (trim($incoming_comment['comment_author_email']) == 'email@qq52o.me')
				$isSpam = 1;
		if(!$isSpam)
				return $incoming_comment;
		wp_die('请勿冒充博主发表评论');
}
if(!is_user_logged_in()) add_filter( 'preprocess_comment', 'syz_usecheck' );

// 增加评论字数限制
function set_comments_length($commentdata) {
		$minCommentlength = 5; //最少字數限制，建议设置为5-10个字
		$maxCommentlength = 220; //最多字數限制，建议设置为150-200个字
		$pointCommentlength = mb_strlen($commentdata['comment_content'],'UTF8'); //mb_strlen 一个中文字符当做一个长度
		if ( ($pointCommentlength < $minCommentlength) && !is_user_logged_in() ){
				wp_die('抱歉，您的评论字数过少，最少输入' . $minCommentlength .'个字（目前字数：'. $pointCommentlength .'）');
		}
		if ( ($pointCommentlength > $maxCommentlength) && !is_user_logged_in() ){
				wp_die('抱歉，您的评论字数过多，最多输入' . $maxCommentlength .'个字（目前字数：'. $pointCommentlength .'）');
		}
		return $commentdata;
}
add_filter('preprocess_comment', 'set_comments_length');

// 允许非管理员用户在评论中插入图片等标签
function my_allowed_edittag() {
    define('CUSTOM_TAGS', true);
    global $allowedposttags, $allowedtags;
    $allowedposttags = array(
        'strong' => array(),
        'em' => array(),
        'ol' => array(),
        'li' => array(),
        'u' => array(),
        'ul' => array(),
        'blockquote' => array(),
        'code' => array(),
        'pre' => array(
            'style' => true,
            'class' => true,
        ),
        'a' => array(
        'href' => array (),
        'title' => array ()),
        'img' => array(
        'src' => array ()),
    );
 
    $allowedtags = array(
        'strong' => array(),
        'em' => array(),
        'ol' => array(),
        'li' => array(),
        'u' => array(),
        'ul' => array(),
        'blockquote' => array(),
        'code' => array(),
        'pre' => array(),
        'a' => array(
        'href' => array (),
        'title' => array ()),
        'img' => array(
        'src' => array ()),
    );
}
add_action('init', 'my_allowed_edittag', 10);

// 禁止使用admin用户名尝试登录
add_filter( 'wp_authenticate', 'wpjam_no_admin_user' );
function wpjam_no_admin_user($user){
	if($user == 'admin'){
		exit;
	}
}
add_filter('sanitize_user', 'wpjam_sanitize_user_no_admin',10,3);
function wpjam_sanitize_user_no_admin($username, $raw_username, $strict){
	if($raw_username == 'admin' || $username == 'admin'){
		exit;
	}
	return $username;
}
