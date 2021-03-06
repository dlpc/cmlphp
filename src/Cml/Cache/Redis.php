<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 Redis缓存驱动
 * *********************************************************** */
namespace Cml\Cache;

use Cml\Config;
use Cml\Lang;

class Redis extends namespace\Base
{
    /**
     * @var bool|array
     */
    private $conf;

    /**
     * @var array(\Redis)
     */
    private $redis = array();

    public function __construct($conf = false)
    {
        $this->conf = $conf ? $conf : Config::get('CACHE');

        if (!extension_loaded('redis') ) {
            \Cml\throwException(Lang::get('_CACHE_EXTEND_NOT_INSTALL_', 'Redis'));
        }
    }

    /**
     * 根据key获取redis实例
     *
     * @param $key
     *
     * @return \Redis
     */
    private function hash($key) {
        $len = strlen($key);
        $success = 0;
        for($i = 0; $i < $len; $i++) {
            $success += ord($key[$i]);
        }

        $success = $success % count($this->conf['server']);

        if(!isset($this->redis[$success]) || !is_object($this->redis[$success])) {
            $instance = new \Redis();
            if($instance->pconnect($this->conf['server'][$success]['host'], $this->conf['server'][$success]['port'], 1.5)) {
                $this->redis[$success] = $instance;
            } else {
                \Cml\throwException(Lang::get('_CACHE_CONNECT_FAIL_', 'Redis',
                    $this->conf['server'][$success]['host'] . ':' . $this->conf['server'][$success]['port']
                ));
            }
        }
        return $this->redis[$success];
    }

    /**
     * 根据key取值
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function get($key)
    {
        $return = json_decode($this->hash($key)->get($this->conf['prefix'] . $key), true);
        is_null($return) && $return = false;
        return $return; //orm层做判断用
    }

    /**
     * 存储对象
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $expire
     *
     * @return bool
     */
    public function set($key, $value, $expire = 0)
    {
        $value = (is_array($value) || is_object($value)) ? json_encode($value) : $value;
        if ($expire > 0) {
            return $this->hash($key)->setex($this->conf['prefix'] . $key, $expire, $value);
        } else {
            return $this->hash($key)->set($this->conf['prefix'] . $key, $value);
        }
    }

    /**
     * 更新对象
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $expire
     *
     * @return bool|int
     */
    public function update($key, $value, $expire = 0)
    {
        $arr = $this->get($key);
        if (!empty($arr)) {
            $arr = array_merge($arr, $value);
            return $this->set($key, $arr, $expire);
        }
        return 0;
    }

    /**
     * 删除对象
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function delete($key)
    {
        return $this->hash($key)->del($this->conf['prefix'] . $key);
    }

    /**
     * 清洗已经存储的所有元素
     *
     */
    public function truncate()
    {
        foreach ($this->conf['server'] as $key => $val) {
            if(!isset($this->redis[$key]) || !is_object($this->redis[$key])) {
                $instance = new \Redis();
                if($instance->pconnect($val['host'], $val['port'], 1.5)) {
                    $this->redis[$key] = $instance;
                } else {
                    \Cml\throwException(Lang::get('_CACHE_NEW_INSTANCE_ERROR_', 'Redis'));
                }
                $this->redis[$key]->flushDB();
            }
        }
        return true;
    }

    /**
     * 自增
     *
     * @param mixed $key
     * @param int $val
     *
     * @return bool
     */
    public function increment($key, $val = 1)
    {
        return $this->hash($key)->incr($this->conf['prefix'] . $key);
    }

    /**
     * 自减
     *
     * @param mixed $key
     * @param int $val
     *
     * @return bool
     */
    public function decrement($key, $val = 1)
    {
        return $this->hash($key)->decr($this->conf['prefix'] . $key);
    }

    /**
     * 判断key值是否存在
     *
     * @param mixed $key
     * @return mixed
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->hash($key)->exists($this->conf['prefix'] . $key);
    }

    /**
     * 返回实例便于操作未封装的方法
     *
     * @return \Redis
     */
    public function getInstance($key = '')
    {
        return $this->hash($key);
    }

}