<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 视图渲染引擎 抽象基类
 * *********************************************************** */
namespace Cml\View;

abstract class Base {
    /**
     * 要传到模板的数据
     *
     * @var array
     */
    protected $args = array();

    /**
     * 变量赋值
     *
     * @param string | array $key 数组或字符串为数组时批量赋值
     * @param mixed $val
     */
    public function assign($key, $val = null) {
        if (is_array($key)) {
            $this->args = array_merge($this->args, $key);
        } else {
            $this->args[$key] = $val;
        }
        return $this;
    }

    /**
     * 引用赋值
     *
     * @param string | array $key 数组或字符串为数组时批量赋值
     * @param mixed $val
     */
    public function assignByRef($key, &$val = null) {
        if (is_array($key)) {
            foreach ($key as $k => &$v) {
                $this->args[$k] = $v;
            }
        } else {
            $this->args[$key] = $val;
        }
        return $this;
    }

    /**
     * 获取赋到模板的值
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getValue($key = null) {
        if (is_null($key)) {//返回所有
            return $this->args;
        } elseif (isset($this->args[$key])){
            return $this->args[$key];
        } else {
            return null;
        }
    }

    /**
     * 抽象display
     *
     * @return mixed
     */
    abstract public function display();
} 