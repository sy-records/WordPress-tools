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
function Bing_title_separator_to_line(){
    return '|';
}
add_filter( 'document_title_separator', 'Bing_title_separator_to_line' );

//彻底禁止WordPress缩略图
add_filter( 'add_image_size', create_function( '', 'return 1;' ) );

 //评论作者链接新窗口打开
function my_get_comment_author_link() {
	$url = get_comment_author_url( $comment_ID );
	$author = get_comment_author( $comment_ID );
	if ( empty( $url ) || 'http://' == $url )
	return $author;
	else
	return "<a href='$url' target='_blank' rel='external nofollow' class='url'>$author</a>";
}
add_filter('get_comment_author_link', 'my_get_comment_author_link');

 //给外部链接加上跳转
 add_filter('the_content','the_content_nofollow',999);
 function the_content_nofollow($content){
 	preg_match_all('/<a(.*?)href="(.*?)"(.*?)>/',$content,$matches);
 	if($matches){
 		foreach($matches[2] as $val){
 			if(strpos($val,'://')!==false && strpos($val,home_url())===false && !preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i',$val)){
 			    $content=str_replace("href=\"$val\"", "href=\"".home_url()."/go/?url=$val\" ",$content);
 			}
 		}
 	}
 	return $content;
 }

//文章外链跳转伪静态版
add_filter('the_content','link_jump',999);
function link_jump($content){
	preg_match_all('/<a(.*?)href="(.*?)"(.*?)>/',$content,$matches);
	if($matches){
	    foreach($matches[2] as $val){
	        if(strpos($val,'://')!==false && strpos($val,home_url())===false && !preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i',$val) && !preg_match('/(ed2k|thunder|Flashget|flashget|qqdl):\/\//i',$val)){
	        $content=str_replace("href=\"$val\"", "href=\"".home_url()."/go/".base64_encode($val)."\" rel=\"nofollow\"",$content);
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
    if ( isset( $_GET['random_cat_id'] ) ) {
        $random_cat_id = (int) $_GET['random_cat_id'];
        $query = "SELECT DISTINCT ID FROM $wpdb->posts AS p INNER JOIN $wpdb->term_relationships AS tr ON (p.ID = tr.object_id AND tr.term_taxonomy_id = $random_cat_id) INNER JOIN $wpdb->term_taxonomy AS tt ON(tr.term_taxonomy_id = tt.term_taxonomy_id AND taxonomy = 'category') WHERE post_type = 'post' AND post_password = '' AND post_status = 'publish' ORDER BY RAND() LIMIT 1";
    }
    if ( isset( $_GET['random_post_type'] ) ) {
        $post_type = preg_replace( '|[^a-z]|i', '', $_GET['random_post_type'] );
        $query = "SELECT ID FROM $wpdb->posts WHERE post_type = '$post_type' AND post_password = '' AND post_status = 'publish' ORDER BY RAND() LIMIT 1";
    }
    $random_id = $wpdb->get_var( $query );
    wp_redirect( get_permalink( $random_id ) );
    exit;
}
if ( isset( $_GET['random'] ) )
add_action( 'template_redirect', 'random_postlite' );

/**
 * WordPress有新评论微信提醒管理员 | 沈唁志
 * link：https://qq52o.me/1092.html
 */
function sc_send($comment_id)  
{  
$text = '博客有一条新评论';  
$comment = get_comment($comment_id);  
$desp = $comment->comment_content; 
$key = '你的appkey'; 
$postdata = http_build_query(  
array(  
'text' => $text,  
'desp' => $desp  
)  
);  
   
$opts = array('http' =>  
array(  
'method' => 'POST',  
'header' => 'Content-type: application/x-www-form-urlencoded',  
'content' => $postdata  
)  
);  
$context = stream_context_create($opts);  
return $result = file_get_contents('http://sc.ftqq.com/'.$key.'.send', false, $context);  
}  
add_action('comment_post', 'sc_send', 19, 2);

//WordPress 文章版权申明
add_filter ('the_content', 'syz_copyright');
function syz_copyright($content) {
    $content.= '<p>除非注明，否则均为<a href="'.get_bloginfo('url').'" target="_blank">'.get_bloginfo('name').'</a>原创文章，转载必须以链接形式标明本文链接</p>';
    $content.= '<p>本文链接：<a title="'.get_the_title().'" href="'.get_permalink().'" target="_blank">'.get_permalink().'</a></p>';
    return $content;
}