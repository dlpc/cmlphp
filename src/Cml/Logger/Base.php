<?php namespace Cml\Logger;
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 15-12-22 下午1:11
 * @version  2.5
 * cml框架 Logger 抽象类 参考 https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * *********************************************************** */

use Cml\Config;

abstract class Base
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * php错误相对应的错误等级
     *
     * @var array
     */
    public $phpErrorToLevel = array(
        E_ERROR             => self::EMERGENCY,
        E_WARNING           => self::WARNING,
        E_PARSE             => self::EMERGENCY,
        E_NOTICE            => self::NOTICE,
        E_CORE_ERROR        => self::EMERGENCY,
        E_CORE_WARNING      => self::EMERGENCY,
        E_COMPILE_ERROR     => self::EMERGENCY,
        E_COMPILE_WARNING   => self::EMERGENCY,
        E_USER_ERROR        => self::ERROR,
        E_USER_WARNING      => self::WARNING,
        E_USER_NOTICE       => self::NOTICE,
        E_STRICT            => self::NOTICE,
        E_RECOVERABLE_ERROR => self::ERROR,
        E_DEPRECATED        => self::NOTICE,
        E_USER_DEPRECATED   => self::NOTICE,
    );

   /* 系统不可用
    *
    * @param string $message
    * @param array $context
    *
    * @return null
    */
    public function emergency($message, array $context = array())
    {
        return $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * **必须**立刻采取行动
     *
     * 例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下，**应该**发送一条警报短信把你叫醒。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function alert($message, array $context = array())
    {
        return $this->log(self::ALERT, $message, $context);
    }

    /**
     * 紧急情况
     *
     * 例如：程序组件不可用或者出现非预期的异常。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function critical($message, array $context = array())
    {
        return $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function error($message, array $context = array())
    {
        return $this->log(self::ERROR, $message, $context);
    }

    /**
     * 出现非错误性的异常。
     *
     * 例如：使用了被弃用的API、错误地使用了API或者非预想的不必要错误。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function warning($message, array $context = array())
    {
        return $this->log(self::WARNING, $message, $context);
    }

    /**
     * 一般性重要的事件。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function notice($message, array $context = array())
    {
        return $this->log(self::NOTICE, $message, $context);
    }

    /**
     * 重要事件
     *
     * 例如：用户登录和SQL记录。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function info($message, array $context = array())
    {
        return $this->log(self::INFO, $message, $context);
    }

    /**
     * debug 详情
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function debug($message, array $context = array())
    {
        return $this->log(self::DEBUG, $message, $context);
    }

    /**
     * 任意等级的日志记录
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    abstract public function log($level, $message, array $context = array());

    /**
     * 格式化日志
     *
     * @param $message
     * @param array $context
     *
     * @return string
     */
    public function format($message, array $context = array())
    {
         return '[' . date('Y-m-d H:i:s') . '] ' . Config::get('log_prefix', 'cml_log') . ': ' . $message . ' ' .json_encode($context, PHP_VERSION >= '5.4.0' ? JSON_UNESCAPED_UNICODE : 0);
    }
}
