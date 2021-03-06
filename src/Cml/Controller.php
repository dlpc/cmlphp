<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 系统默认控制器类
 * *********************************************************** */
namespace Cml;

use Cml\Http\Response;

class Controller
{

    /**
     * 运行对应的控制器
     *
     * @return void
     */
    final public function runAppController()
    {
        //检测csrf跨站攻击
        Secure::checkCsrf(Config::get('check_csrf'));

        // 关闭GPC过滤 防止数据的正确性受到影响 在db层防注入
        if (get_magic_quotes_gpc()) {
            Secure::stripslashes($_GET);
            Secure::stripslashes($_POST);
            Secure::stripslashes($_COOKIE);
            Secure::stripslashes($_REQUEST); //在程序中对get post cookie的改变不影响 request的值
        }

        //session保存方式自定义
        if (Config::get('session_user')) {
            Session::init();
        } else {
            ini_get('session.auto_start') || session_start(); //自动开启session
        }

        header('Cache-control: '.Config::get('http_cache_control'));  // 页面缓存控制

        //如果有子类中有init()方法 执行Init() eg:做权限控制
        if (method_exists($this, "init")){
            $this->init();
        }

        //根据动作去找对应的方法
        $method = Route::$urlParams['action'];
        if (method_exists($this, $method)){
            $this->$method();
        } elseif ($GLOBALS['debug']) {
            Cml::montFor404Page();
            throwException(Lang::get('_ACTION_NOT_FOUND_', Route::$urlParams['action']));
        } else {
            Cml::montFor404Page();
            Response::show404Page();
        }
    }

    /**
     * 获取模型方法
     *
     * @return \Cml\Model
     */
    public function model()
    {
        return Model::getInstance();
    }

    /**
     * 获取Lock实例
     *
     * @param string|null $useCache
     *
     * @return \Cml\Lock\Redis | \Cml\Lock\Memcache | \Cml\Lock\File | false
     * @throws \Exception
     */
    public function locker($useCache = null)
    {
        is_null($useCache) && $useCache = Config::get('locker_use_cache', 'default_cache');
        static $_instance = array();
        $config = Config::get($useCache);
        if (isset($_instance[$useCache])) {
            return $_instance[$useCache];
        } else {
            if ($config['on']) {
                $lock = 'Cml\Lock\\'.$config['driver'];
                $_instance[$useCache] = new $lock($useCache);
                return $_instance[$useCache];
            } else {
                throwException($useCache.Lang::get('_NOT_OPEN_'));
                return false;
            }
        }
    }
}