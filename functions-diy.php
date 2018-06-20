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
// 最热文章
function most_comm_posts($days=7, $nums=10) { //$days参数限制时间值，单位为‘天’，默认是7天；$nums是要显示文章数量
    global $wpdb;
    $today = date("Y-m-d H:i:s"); //获取今天日期时间
    $daysago = date( "Y-m-d H:i:s", strtotime($today) - ($days * 24 * 60 * 60) );  //Today - $days
    $result = $wpdb->get_results("SELECT comment_count, ID, post_title, post_date FROM $wpdb->posts WHERE post_date BETWEEN '$daysago' AND '$today' ORDER BY comment_count DESC LIMIT 0 , $nums");
    $output = '';
    if(empty($result)) {
        $output = '<li>None data.</li>';
    } else {
        foreach ($result as $topten) {
            $postid = $topten->ID;
            $title = $topten->post_title;
            $commentcount = $topten->comment_count;
            if ($commentcount != 0) {
                $output .= '<li><a href="'.get_permalink($postid).'" title="'.$title.'">'.$title.'</a></li>';
            }
        }
    }
    echo $output;
}