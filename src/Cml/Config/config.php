<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-211 下午2:23
 * @version  2.5
 * cml框架惯例配置文件
 * *********************************************************** */

return array(
    //调试模式  默认关闭
    'debug' => false,
    'db_fields_cache' => true, //线上模式是否开启数据库字段缓存 在debug模式的时候还是使用*

    'time_zone' => 'PRC', //时区

    //数据库配置
    'default_db' => array(
        'driver' => 'MySql.Pdo', //数据库驱动
        'master' => array(
            'host' => 'localhost', //数据库主机
            'username' => 'root', //数据库用户名
            'password' => '', //数据库密码
            'dbname' => 'cmlphp', //数据库名
            'charset' => 'utf8', //数据库编码
            'tableprefix' => 'sun_', //数据表前缀
            'pconnect' => false, //是否开启数据库长连接
            'engine' => ''//数据库引擎
        ),
        'slaves'=>array(),
        'cache_expire' => 3600,//查询数据缓存时间
    ),
    // 缓存服务器的配置
    'default_cache' => array(
        'on' => 0, //为1则启用，或者不启用
        'driver' => 'Memcache',
        'prefix' => 'cml_',
        'server' => array(
            array(
                'host' => '127.0.0.1',
                'port' => '11211'
            ),
            //多台...
        ),
    ),
    /**
    //文件缓存
    'default_cache' => array(
        'on' => 0, //为1则启用，或者不启用
        'driver' => 'File',
        'prefix' => 'cml_'
    ),
    //apc缓存
    'default_cache' => array(
        'on' => 0, //为1则启用，或者不启用
        'driver' => 'Apc',
        'prefix' => 'cml_'
    ),
    //Redis缓存
    'default_cache' => array(
        'on' => 0, //为1则启用，或者不启用
        'driver' => 'Redis',
        'prefix' => 'cml_',
        'server' => array(
            array(
                'host' => '127.0.0.1',
                'port' => '6379'
            ),
            //多台...
        ),
    ),
    */

    //模板设置
    'view_render_engine' => 'Html',//视图渲染引擎，Html/Excel/Json/Xml
    'default_charset' => 'utf-8', // 默认输出编码
    'http_cache_control' => 'private', // 网页缓存控制
    'output_encode' => true, // 页面压缩输出

    //Html引擎配置
    'html_theme' =>'', //默认只有单主题
    'html_template_suffix' => '.html',     // 默认模板文件后缀
    'html_left_deper' => '{{', //模板左定界符
    'html_right_deper' => '}}', //模板右定界符
    'html_exception' => CML_PATH.'/Cml/Tpl/cmlException.tpl', // 默认成功跳转对应的模板文件
    '404_page' => CML_PATH.'/Cml/Tpl/404.tpl', // 404跳转页


    /* URL设置 */
    'url_model' => 1,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 1 (PATHINFO 模式显示index.php); 2 (PATHINFO 不显示index.php); 3 (兼容模式)  默认为PATHINFO 模式，提供最好的用户体验和SEO支持
    'url_pathinfo_depr' => '/', // PATHINFO模式下，各参数之间的分割符号
    'url_html_suffix' => '.html',  // URL伪静态后缀设置
    'url_default_action' => 'web/Default/index', //默认操作
    'var_pathinfo' => 'r',  // PATHINFO 兼容模式获取变量例如 ?r=/module/action/id/1中的s ,后面的分隔符/取决于url_pathinfo_depr
    //'static__path' => 'http://static.cml.com/', //模板替换的{{public}}静态地址(访问静态资源用)  默认为 /public 目录

    /*安全过滤*/
    'auth_key'=>'a5et3e41d', //Encry加密key
    'check_csrf' => 1, //检查csrf跨站攻击 0、不检查，1、只检查post数据提交方式，2、get/post都检查 默认只检查post
    'form_token'=> 0, //表单令牌 0不开启，1开启

    /*语言包设置*/
    'lang' =>'zh-cn',  //读取zh-cn.php文件

    /*cookie设置*/
    'cookie_prefix'=> 'cml_', //cookie前缀
    'cookie_expire' => 0,    // Coodie有效期
    'cookie_domain' => '',      // Cookie有效域名
    'cookie_path' => '/',     // Cookie路径
    'userauthid' => 'CmlUserAuth',  //用户登录成功之后的cookie标识

    /*Session设置*/
    'session_prefix' => 'cml_', //session前缀
    'session_user' => 0, //SESSION保存位置自定义 0不开启、1开启
    'session_user_loc' => 'db', //自定义保存SESSION的位置时 定义保存的地方  db、cache两种

    /**锁配置**/
    'lock_prefix' => 'cml_',
    //上锁使用的缓存
    'locker_use_cache' => 'default_cache',

    /**日志配置**/
    'log_warn_log' => false, //警告级别的日志默认不记录
    'log_driver' => 'File', //日志驱动,内置File/Redis两种
    'log_prefix' => 'cml_log', //会显示到日志内容中,同时当以redis为驱动的时候会做为队列的前缀
    //上锁使用的缓存
    'log_use_cache' => 'default_cache',//只有在该缓存的驱动为redis的时候才有效,否则会报错

    'is_multi_modules' => true, //是否为分模块设计,//默认为true 这个选项是为了兼容旧项目，新项目最好不要开启
    'application_dir' => 'Application',//分模块的时候主目录名称
    'modules_static_path_name' => 'Resource',//分模块的时候如有静态资源默认目录名

    /*系统路由*/
    'cmlframework_system_command' => array(
        'cmlframeworkstaticparse' => '\\Cml\\Tools\\StaticResource::parseResourceFile'
    ),
    'static_file_version' => 'v1', //开发模式会自动在静态文件后加时间缀，实时过期,线上模板版本号固定，如有需要在这里改版本号强制过期
);