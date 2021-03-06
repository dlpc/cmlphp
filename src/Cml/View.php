<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 视图渲染引擎 视图调度工厂
 * *********************************************************** */

namespace Cml;

class View {
    /**
     * 获取渲染引擎-单例
     *
     * @return \Cml\View\Html
     */
    public static function getEngine($engine = null) {
        is_null($engine) && $engine = Config::get('view_render_engine');
        static $_instance = array();
        $engine = '\Cml\View\\'.ucfirst($engine);
        if (!isset($_instance[$engine])) {
            $_instance[$engine] = new $engine();
        }
        return $_instance[$engine];
    }
}