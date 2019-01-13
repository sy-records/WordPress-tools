# WordPress-tools

:zap:总结一下平时在 WordPress 使用中的实用代码段，欢迎提交PR

## 更新 WordPress

使用 WP-CLI 更新 WordPress 

### 安装

```bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
php wp-cli.phar --info # 检查是否可用
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp # 简化命令
```

### 更新

```bash
wp core update  # 更新核心
wp core update-db  # 更新数据库
```

## 短代码使用方式

为了防止主题更新覆盖 functions.php 文件造成自定义代码丢失，将下面的内容保存为 utf-8 格式的 php 文件，并起名为 `functions-diy.php`，放入跟主题的`functions.php` 文件同目录

```php
<?php
/*
你可以把本来要写在 function.php 中的代码写在这里，
而无须担心更新主题时 function.php 被覆盖。
*/

```

在 `functions.php` 中引入该文件

```php
require get_template_directory() . '/functions-diy.php';
```

找到你需要的对应代码，放入 `functio-diy.php` 中保存即可

## 代码段

1、友链管理  
2、显示评论@回复了谁 不入数据库  
3、让WordPress小工具文本支持PHP  
4、最热文章  
5、防止作者信息泄露  
6、后台登陆数学验证码  
7、完全禁用 wp-json  
8、移除头部 wp-json 标签和 HTTP header 中的 link  
<del> 9、防止 WordPress 任意文件删除漏洞</del>  7月6日推送的 4.9.7 版本已修复  
10、博客后台登录失败时发送邮件通知管理员  
11、页面伪静态加上.html  
12、禁用所有文章类型的修订版本  
13、禁用自动保存  
14、纯代码实现熊掌号 H5 页面结构化改造添加 JSON_LD 数据  
15、防止网站被别人 iframe 框架调用的方法  
16、优化熊掌号主页显示 防止标题中的 - 被转义为实体符号  
17、纯代码屏蔽垃圾评论  
18、标题变为 |  
19、彻底禁 止WordPress 缩略图  
20、评论作者链接新窗口打开  
21、给外部链接加上跳转  
22、文章外链跳转伪静态版  
23、添加随便看看  
24、WordPress 有新评论微信提醒管理员 使用方糖服务 
25、WordPress 文章版权申明  
26、移除登录页面标题中的“ — WordPress”  
27、移除后台页面标题中的“ — WordPress”  
28、WordPress 禁止可视化编辑模式  
29、WordPress 移除管理员后台添加用户权限  
30、同时删除 head 和 feed 中的 WP 版本号  
31、移除 WordPress 文章标题前的“私密/密码保护”提示文字  
32、WordPress 中文名、数字名图片上传自动重命名  
33、移除 WordPress 头部加载 DNS 预获取（dns-prefetch）  
34、禁止 WordPress 动态文章 ID 访问  
35、为 WordPress Feed 订阅源添加 utm_source 跟踪参数  
36、使用 smtp 发邮件  
37、WordPress 首页排除隐藏指定分类文章  
38、去掉评论中网址超链接  
39、移除 Wordpress 后台顶部左上角的 W 图标  
40、替换 WordPress 默认 Emoji 资源地址  
41、WordPress 4.6 新增 SVG 格式资源  
42-47、WordPress RSS/Feed 源的一些设置  
48、搜索词为空跳转首页  
49、关闭文章的标签功能  
50、自定义代码高亮按钮  
51、清理 WordPress 菜单中的 classes  
52、修改 Gravatar 地址  
53、移除后台 Privacy 隐私政策设置页面及功能  
54、WordPress 上传文件重命名  
55、给评论框加上图片按钮，可评论回复图片  
56、一键实现 Wordpress 点维护功能  
57、防止在 WordPress 别人冒充博主发表评论  
58、增加评论字数限制  
59、允许非管理员用户在评论中插入图片等标签  
60、禁用 admin 用户名尝试登录  
61、禁止 WordPress5.0 使用 Gutenberg 块编辑器  
62、除子菜单  
63、另一种随便看看代码  
64、删除文章时删除图片附件  
65、文章内容添加文章目录  
