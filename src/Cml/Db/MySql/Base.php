<?php
/* * *********************************************************
 * [cml] (C)2012 - 3000 cml http://cmlphp.51beautylife.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  2.5
 * cml框架 Db MySql数据库抽象基类
 * *********************************************************** */
namespace Cml\Db\MySql;

use Cml\Lang;
use Cml\Model;
use Cml\Route;

abstract class Base
{
    /**
     * 执行sql时绑定的参数
     *
     * @var array
     */
    protected  $bindParams = array();

    /**
     * @var array
     */
    protected  $conf; //配置

    /**
     * @var string 表前缀方便外部读取
     */
    public $tablePrefix;

    /**
     * @var array sql组装
     */
    protected  $sql = array(
        'where' => '',
        'columns' => '',
        'limit' => '',
        'orderBy' => '',
        'groupBy' => '',
        'having' => '',
    );

    /**
     * @var array 操作的表
     */
    protected  $table = array();

    /**
     * @var array 是否内联 array(表名 => 条件)
     */
    protected  $join = array();

    /**
     * @var array 是否左联结 写法同内联
     */
    protected  $leftJoin = array();

    /**
     * @var array 是否右联 写法同内联
     */
    protected  $rightJoin = array();

    /**
     * @var string UNION 写法同内联
     */
    protected  $union = '';


    abstract public function __construct($conf);

    /**
     * 定义操作的表
     *
     * @param string|array $table 表名 要取别名时使用 array(不带前缀表名 => 别名)
     * @param mixed $tablePrefix 表前缀
     *
     * @return $this
     */
    public function table($table = '', $tablePrefix = null)
    {
        $hasAlias = is_array($table) ? true : false;
        is_null($tablePrefix) && $tablePrefix = $this->tablePrefix;
        $tableName = $tablePrefix.($hasAlias  ? key($table) : $table);

        $this->table[count($this->table) . '_' . $tableName] = $hasAlias ? current($table) : null;
        return $this;
    }

    /**
     * 获取当前db所有表名
     *
     * @return array
     */
    abstract public function getTables();

    /**
     * 获取表字段
     *
     * @param string $table 表名
     * @param mixed $tablePrefix 表前缀 为null时代表table已经带了前缀
     * @param int $filter 0 获取表字段详细信息数组 1获取字段以,号相隔组成的字符串
     *
     * @return mixed
     */
    abstract public function getDbFields($table, $tablePrefix = null, $filter = 0);

    /**
     * 魔术方法 自动获取相应db实例
     *
     * @param string $db 要连接的数据库类型
     *
     * @return  resource MySQL 连接标识
     */
    public function __get($db)
    {
        if ($db == 'rlink') {
            //如果没有指定从数据库，则使用 master
            if (empty($this->conf['slaves'])) {
                $this->rlink = $this->wlink;
                return $this->rlink;
            }

            $n = mt_rand(0, count($this->conf['slaves']) - 1);
            $conf = $this->conf['slaves'][$n];
            empty($conf['engine']) && $conf['engine'] = '';
            $this->rlink = $this->connect(
                $conf['host'],
                $conf['username'],
                $conf['password'],
                $conf['dbname'],
                $conf['charset'],
                $conf['engine'],
                $conf['pconnect']
            );
            return $this->rlink;
        } elseif ($db == 'wlink') {
            $conf = $this->conf['master'];
            empty($conf['engine']) && $conf['engine'] = '';
            $this->wlink = $this->connect(
                $conf['host'],
                $conf['username'],
                $conf['password'],
                $conf['dbname'],
                $conf['charset'],
                $conf['engine'],
                $conf['pconnect']
            );
            return $this->wlink;
        }
        return false;
    }

    /**
     * 根据key取出数据
     *
     * @param string $key get('user-uid-123');
     * @param bool $and 多个条件之间是否为and  true为and false为or
     *
     * @return array array('uid'=>123, 'username'=>'abc')
     */
    abstract public function get($key, $and = true);

    /**
     * 根据key 新增 一条数据
     *
     * @param string $key set('user-uid-123');
     * @param array $data eg: array('username'=>'admin', 'email'=>'linhechengbush@live.com')
     *
     * @return bool
     */
    abstract public function set($key, $data);

    /**
     * 根据key更新一条数据
     *
     * @param string $key eg 'user-uid-$uid'
     * @param array $data eg: array('username'=>'admin', 'email'=>'linhechengbush@live.com')
     * @param bool $and 多个条件之间是否为and  true为and false为or
     *
     * @return boolean
     */
    abstract public function update($key, $data, $and = true);

    /**
     * 根据key值删除数据
     *
     * @param string $key eg: 'user-uid-$uid'
     * @param bool $and 多个条件之间是否为and  true为and false为or
     *
     * @return boolean
     */
    abstract public function delete($key, $and = true);

    /**
     * 根据表名删除数据
     *
     * @param string $tableName
     *
     * @return boolean
     */
    abstract public function truncate($tableName);

    /**
     * 获取多条数据
     *
     * @return array
     */
    abstract public function select();

    /**
     * 获取表主键
     *
     * @param string $table
     * @param string $tablePrefix
     *
     * @return string || false
     */
    public function getPk($table, $tablePrefix = null)
    {
        $tablename = is_null($tablePrefix) ? $this->tablePrefix.$table : $tablePrefix.$table;
        $rows = $this->getDbFields($tablename);
        foreach ($rows as $val) {
            if ($val['primary']) {
                return $val['name'];
            }
        }
        return false;
    }

    /**
     * where条件组装 相等
     *
     * @param string|array $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名) 当$column为数组时 批量设置
     * @param string |int $value 当$column为数组时  此时$value为false时条件为or 否则为and
     *
     * @return $this
     */
    public function where($column, $value = '')
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                !empty($this->sql['where']) && ($this->sql['where'] .= ($value === false ? '  OR ' : ' AND '));
                $this->conditionFactory($key, $val, '=');
            }
        } else {
            $this->conditionFactory($column, $value, '=');
        }
        return $this;
    }

    /**
     * where条件组装 不等
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereNot($column, $value)
    {
        $this->conditionFactory($column, $value, '!=');
        return $this;
    }

    /**
     * where条件组装 大于
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereGt($column, $value)
    {
        $this->conditionFactory($column, $value, '>');
        return $this;
    }

    /**
     * where条件组装 小于
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereLt($column, $value)
    {
        $this->conditionFactory($column, $value, '<');
        return $this;
    }

    /**
     * where条件组装 大于等于
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereGte($column, $value)
    {
        $this->conditionFactory($column, $value, '>=');
        return $this;
    }

    /**
     * where条件组装 小于等于
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereLte($column, $value)
    {
        $this->conditionFactory($column, $value, '<=');
        return $this;
    }

    /**
     * where条件组装 in
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array $value
     *
     * @return $this
     */
    public function whereIn($column, $value)
    {
        $this->conditionFactory($column, $value, 'IN');
        return $this;
    }

    /**
     * where条件组装 not in
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param array $value array(1,2,3)
     *
     * @return $this
     */
    public function whereNotIn($column, $value)
    {
        $this->conditionFactory($column, $value, 'NOT IN');
        return $this;
    }

    /**
     * where条件组装 REGEXP
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int $value
     *
     * @return $this
     */
    public function whereRegExp($column, $value)
    {
        $this->conditionFactory($column, $value, 'REGEXP');
        return $this;
    }

    /**
     * where条件组装 LIKE
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param bool $leftBlur 是否开始左模糊匹配
     * @param string |int $value
     * @param bool $rightBlur 是否开始右模糊匹配
     *
     * @return $this
     */
    public function whereLike($column, $leftBlur = false, $value, $rightBlur = false)
    {
        $this->conditionFactory(
            $column,
            ($leftBlur ? '%' : '').$this->filterLike($value).($rightBlur ? '%' : ''),
            'LIKE'
        );
        return $this;
    }

    /**
     * where条件组装 LIKE
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param bool $leftBlur 是否开始左模糊匹配
     * @param string |int $value
     * @param bool $rightBlur 是否开始右模糊匹配
     *
     * @return $this
     */
    public function whereNotLike($column, $leftBlur = false, $value, $rightBlur = false)
    {
        $this->conditionFactory(
            $column,
            ($leftBlur ? '%' : '').$this->filterLike($value).($rightBlur ? '%' : ''),
            'NOT LIKE'
        );
        return $this;
    }

    /**
     * where 用户输入过滤
     *
     * @param string $val
     *
     * @return string
     */
    private function filterLike($val)
    {
        return str_replace(array('_', '%'), array('\_', '\%'), $val);
    }

    /**
     * where条件组装 BETWEEN
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int | array $value
     * @param string |int | null $value2
     *
     * @return $this
     */
    public function whereBetween($column, $value, $value2 = null)
    {
        if (is_null($value2)) {
            is_array($value) || \Cml\throwException(Lang::get('_DB_PARAM_ERROR_WHERE_BETWEEN_'));
            $val = $value;
        } else {
            $val = array($value, $value2);
        }
        $this->conditionFactory($column, $val, 'BETWEEN');
        return $this;
    }

    /**
     * where条件组装 NOT BETWEEN
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     * @param string |int | array $value
     * @param string |int | null $value2
     *
     * @return $this
     */
    public function whereNotBetween($column, $value, $value2 = null)
    {
        if (is_null($value2)) {
            is_array($value) || \Cml\throwException(Lang::get('_DB_PARAM_ERROR_WHERE_BETWEEN_'));
            $val = $value;
        } else {
            $val = array($value, $value2);
        }
        $this->conditionFactory($column, $val, 'NOT BETWEEN');
        return $this;
    }

    /**
     * where条件组装 IS NULL
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     *
     * @return $this
     */
    public function whereNull($column)
    {
        $this->conditionFactory($column, '', 'IS NULL');
        return $this;
    }

    /**
     * where条件组装 IS NOT NULL
     *
     * @param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     *
     * @return $this
     */
    public function whereNotNull($column)
    {
        $this->conditionFactory($column, '', 'IS NOT NULL');
        return $this;
    }

    /**
     *where 语句组装工厂
     *
     *@param string $column  如 id  user.id (这边的user为表别名如表pre_user as user 这边用user而非带前缀的原表名)
     *@param string |int|array $value
     *@param string $operator 操作符
     *
     *@return void
     */
    public function conditionFactory($column, $value, $operator = '=')
    {
        if ($this->sql['where'] == '') $this->sql['where'] = 'WHERE ';
        if (strpos($column, '.')) {
            $columnArr = explode('.', $column);
            $column = $columnArr[0].'.'.$columnArr[1];
        }

        if ($operator == 'IN' || $operator == 'NOT IN') {
            empty($value) && $value = array(0);
            //这边可直接跳过不组装sql，但是为了给用户提示无条件 便于调试还是加上where field in(0)
            $inValue = '(';
            foreach ($value as $val) {
                $inValue .= '%s ,';
                $this->bindParams[] = $val;
            }
            $this->sql['where'] .= "{$column} {$operator} ".rtrim($inValue, ',').') ';
        } elseif ($operator == 'BETWEEN' || $operator == 'NOT BETWEEN') {
            $betweenValue = '%s AND %s ';
            $this->bindParams[] = $value[0];
            $this->bindParams[] = $value[1];
            $this->sql['where'] .= "{$column} {$operator} {$betweenValue}";
        } else if ($operator == 'IS NULL' || $operator == 'IS NOT NULL') {
            $this->sql['where'] .= "{$column} {$operator}";
        } else {
            $this->bindParams[] = $value;
            $value = '%s';
            $this->sql['where'] .= "{$column} {$operator} {$value} ";
        }
    }

    /**
     *增加 and条件操作符
     *
     * @return $this
     */
    public function _and()
    {
        $this->sql['where'] .= ' AND ';
        return $this;
    }

    /**
     * 增加or条件操作符
     *
     * @return $this
     */
    public function _or()
    {
        $this->sql['where'] .= ' OR ';
        return $this;
    }

    /**
     * where条件增加左括号
     *
     * @return $this
     */
    public function lBrackets()
    {
        if ($this->sql['where'] == '') $this->sql['where'] = 'WHERE ';
        $this->sql['where'] .= ' (';
        return $this;
    }

    /**
     * where条件增加右括号
     *
     * @return $this
     */
    public function rBrackets()
    {
        $this->sql['where'] .= ') ';
        return $this;
    }

    /**
     * 选择列
     *
     * @param string|array $columns 选取所有 array('id, 'name')
     * 选取id,name两列，array('article.id' => 'aid', 'article.title' =>　'article_title') 别名
     *
     * @return $this
     */
    public function columns($columns = '*')
    {
        $result = '';
        if (is_array($columns)) {
            foreach ($columns as $key => $val) {
                $result .= ($result == '' ? '' : ', '). ( is_int($key) ? $val : ($key ." AS `{$val}`") );
            }
        } else {
            $args = func_get_args();
            while ($arg = current($args)) {
                $result .= ($result == '' ? '' : ', '). $arg;
                next($args);
            }
        }
        $this->sql['columns'] == '' || ($this->sql['columns'] .= ' ,');
        $this->sql['columns'] .= $result;
        return $this;
    }

    /**
     * LIMIT
     *
     * @param int $limit
     * @param int $offset
     *
     * @return $this
     */
    public function limit($limit = 0, $offset = 10)
    {
        $limit = intval($limit);
        $offset = intval($offset);
        $limit < 0 && $limit = 0;
        ($offset < 1 || $offset > 5000) && $offset = 100;
        $this->sql['limit'] = "LIMIT {$limit}, {$offset}";
        return $this;
    }

    /**
     * 排序
     *
     * @param $column
     * @param string $order
     *
     * @return $this
     */
    public function orderBy($column, $order = 'ASC')
    {
        $column = explode('.', $column);
        $column = "`{$column[0]}`" . (isset($column[1]) ? ".`{$column[1]}`" : '');
        if ($this->sql['orderBy'] == '') {
            $this->sql['orderBy'] = "ORDER BY {$column} {$order} ";
        } else {
            $this->sql['orderBy'] .= ", {$column} {$order} ";
        }
        return $this;
    }

    /**
     * 分组
     *
     * @param $column
     *
     * @return $this
     */
    public function groupBy($column)
    {
        if ($this->sql['groupBy'] == '') {
            $this->sql['groupBy'] = "GROUP BY {$column} ";
        } else {
            $this->sql['groupBy'] .= ",{$column} ";
        }
        return $this;
    }

    /**
     * having语句
     *
     * @param string $column
     * @param string $operator
     * @param string $value
     *
     * @return $this
     */
    public function having($column, $operator = '=', $value)
    {
        $having = $this->sql['having'] == '' ? 'HAVING' : ',';
        $this->sql['having'] = "{$having} {$column}{$operator}%s ";
        $this->bindParams[] = $value;
        return $this;
    }

    /**
     * join内联结
     *
     * @param string|array $table 表名 要取别名时使用 array(不带前缀表名 => 别名)
     * @param string $on 如：'c.cid = a.cid'
     *
     * @return $this
     */
    public function join($table, $on)
    {
        $this->table($table);
        $hasAlias = is_array($table) ? true : false;
        $tableName = $this->tablePrefix.($hasAlias  ? key($table) : $table);
        $this->join[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
        return $this;
    }

    /**
     * leftJoin左联结
     *
     * @param string|array $table 表名 要取别名时使用 array(不带前缀表名 => 别名)
     * @param string $on
     *
     * @return $this
     */
    public function leftJoin($table, $on)
    {
        $this->table($table);
        $hasAlias = is_array($table) ? true : false;
        $tableName = $this->tablePrefix.($hasAlias  ? key($table) : $table);
        $this->leftJoin[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
        return $this;
    }

    /**
     * rightJoin右联结
     *
     * @param string|array $table 表名 要取别名时使用 array(不带前缀表名 => 别名)
     * @param string $on
     *
     * @return $this
     */
    public function rightJoin($table, $on)
    {
        $this->table($table);
        $hasAlias = is_array($table) ? true : false;
        $tableName = $this->tablePrefix.($hasAlias  ? key($table) : $table);
        $this->rightJoin[count($this->table) - 1 . '_' . $tableName] = is_array($on) ? $this->parseOn($table, $on) : addslashes($on);
        return $this;
    }

    /**
     * union联结
     *
     * @param string|array $sql
     * @param bool $all
     *
     * @return $this
     */
    public function union($sql, $all = false)
    {
        if (is_array($sql)) {
            foreach($sql as $s) {
                $this->union .= $all ? ' UNION ALL ' : ' UNION ';
                $this->union .= $this->filterUnionSql($s);
            }
        } else {
            $this->union .= $all ? ' UNION ALL ' : ' UNION ';
            $this->union .= $this->filterUnionSql($sql);
        }
        return $this;
    }

    protected function filterUnionSql($sql)
    {
        return str_ireplace(array(
            'insert', "update", "delete", "\/\*", "\.\.\/", "\.\/", "union", "into", "load_file", "outfile"
        ),
            array("","","","","","","","","",""),
            $sql);
    }

    /**
     * 解析联结的on参数
     *
     * @param string $table 要联结的表名
     * @param array $on array('on条件1', 'on条件2' =>true) on条件为数字索引时多条件默认为and为非数字引时 条件=>true为and 条件=>false为or
     *
     * @return string
     */
    protected function parseOn(&$table, $on)
    {
        empty($on) && \Cml\throwException(Lang::get('_DB_PARAM_ERROR_PARSE_ON_', $table));
        $result = '';
        foreach ($on as $key => $val) {
            if (is_numeric($key)) {
                $result == '' || $result .= ' AND ';
                $result .= $val;
            } else {
                $result == '' || $result .= ($val === true ? ' AND ' : ' OR ');
                $result .= $key;
            }
        }
        return addslashes($result); //on条件是程序员自己写死的表字段名不存在注入以防万一还是过滤一下
    }

    /**
     * orm参数重置
     *
     */
    protected  function reset()
    {
        $this->sql = array(  //sql组装
            'where' => '',
            'columns' => '',
            'limit' => '',
            'orderBy' => '',
            'groupBy' => '',
            'having' => '',
        );

        $this->table = array(); //操作的表
        $this->join = array(); //是否内联
        $this->leftJoin = array(); //是否左联结
        $this->rightJoin = array(); //是否右联
    }

    /**
     *SQL语句条件组装
     *
     *@param array $arr; 要组装的数组
     *@param string $tableName 当前操作的数据表名
     *@param string $tablePrefix 表前缀
     *
     *@return string
     */
    protected function arrToCondition($arr, $tableName, $tablePrefix)
    {
        empty($tableName) && $tableName = Route::$urlParams['controller'];
        $dbFields = $this->getDbFields($tablePrefix.$tableName);
        foreach (array_keys($arr) as $key) {
            if (!isset($dbFields[$key]))  unset($arr[$key]); //过滤db表中不存在的字段
        }
        $s = $p = '';
        $params = array();
        foreach ($arr as $k => $v) {
            if (is_array($v)) { //自增或自减
                switch(key($v)) {
                    case 'inc':
                        $p = "`{$k}`= `{$k}`+" . abs(intval(current($v)));
                        break;
                    case 'dec':
                        $p = "`{$k}`= `{$k}`-" . abs(intval(current($v)));
                        break;
                    default ://计算类型
                        $conkey = key($v);
                        if (!isset($dbFields[$conkey])) $conkey = $k;
                        if (!in_array(key(current($v)), array('+', '-', '*', '/', '%', '^', '&', '|', '<<', '>>', '~'))) {
                            \Cml\throwException(Lang::get('_PARSE_UPDATE_SQL_PARAMS_ERROR_'));
                        }
                        $p = "`{$k}`= `{$conkey}`" . key(current($v)) . abs(intval(current(current($v))));
                        break;
                }
            } else {
                $p = "`{$k}`= %s";
                $params[] = $v;
            }

            $s .= (empty($s) ? '' : ',').$p;
        }
        $this->bindParams = array_merge($params, $this->bindParams);
        return $s;
    }

    /**
     *SQL语句条件组装
     *
     *@param string $key eg: 'forum-fid-1-uid-2'
     *@param bool $and 多个条件之间是否为and  true为and false为or
     *@param bool $noCondition 是否为无条件操作  set/delete/update操作的时候 condition为空是正常的不报异常
     *@param bool $noTable 是否可以没有数据表 当delete/update等操作的时候已经执行了table() table为空是正常的
     *
     *@return array eg: array('forum', "`fid` = '1' AND `uid` = '2'")
     */
    protected function parseKey($key, $and = true, $noCondition = false, $noTable = false)
    {
        $condition = '';
        $arr = explode('-', $key);
        $len = count($arr);
        for ($i = 1; $i < $len; $i += 2) {
            isset($arr[$i + 1]) &&  $condition .= ($condition ? ($and ? ' AND ' : ' OR ') : '')."`{$arr[$i]}` = %s";
            $this->bindParams[] = $arr[$i + 1];
        }
        $table = strtolower($arr[0]);
        (empty($table) && !$noTable) && \Cml\throwException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'table'));
        (empty($condition) && !$noCondition) && \Cml\throwException(Lang::get('_DB_PARAM_ERROR_PARSE_KEY_', $key, 'condition'));
        empty($condition) || $condition = "($condition)";
        return array($table, $condition);
    }

    /**
     * 获取count(字段名或*)的结果
     *
     * @param string $field
     *
     * @return int
     */
    public function count($field = '*')
    {
        $count = $this->columns(array("Count({$field})" => 'count'))->select();
        return intval($count[0]['count']);
    }

    /**
     * 返回INSERT，UPDATE 或 DELETE 查询所影响的记录行数。
     *
     * @param resource $handle mysql link
     *
     * @return int
     */
    abstract public function affectedRows($handle);

    /**
     *获取上一INSERT的主键值
     *
     *@param resource $link
     *
     *@return int
     */
    abstract public function insertId($link = null);

    /**
     * 指定字段的值+1
     *
     * @param string $key user-id-1
     * @param int $val
     * @param string $field 要改变的字段
     *
     * @return bool
     */
    abstract public function increment($key, $val = 1, $field = null);

    /**
     * 指定字段的值-1
     *
     * @param string $key user-id-1
     * @param int $val
     * @param string $field 要改变的字段
     *
     * @return bool
     */
    abstract public function decrement($key, $val = 1, $field = null);

    /**
     * Db连接
     *
     * @param $host
     * @param $username
     * @param $password
     * @param $dbName
     * @param string $charset
     * @param string $engine
     * @param bool|false $pConnect
     *
     * @return mixed
     */
    abstract public function connect($host, $username, $password, $dbName, $charset = 'utf8', $engine = '', $pConnect = false);

    /**
     *析构函数
     *
     */
    abstract public function __destruct();

    /**
     *获取mysql 版本
     *
     *@param resource $link
     *
     *@return string
     */
    abstract public function version($link = null);

    /**
     * 开启事务
     *
     * @return bool
     */
    abstract public function  startTransAction();

    /**
     * 提交事务
     *
     * @return bool
     */
    abstract public function commit();

    /**
     * 设置一个事务保存点
     *
     * @param string $pointName 保存点名称
     *
     * @return bool
     */
    abstract public function savePoint($pointName);

    /**
     * 回滚事务
     *
     * @param string $rollBackTo 是否为还原到某个保存点
     *
     * @return bool
     */
    abstract public function rollBack($rollBackTo = false);

    /**
     * 调用存储过程
     * 如 : callProcedure('user_check ?,?  ', array(1, 1), true) pdo
     *
     * @param string $procedureName 要调用的存储过程名称
     * @param array $bindParams 绑定的参数
     * @param bool|true $isSelect 是否为返回数据集的语句
     *
     * @return array|int
     */
    abstract public function callProcedure($procedureName = '', $bindParams = array(), $isSelect = true);

    /**
     * 根据表名获取cache版本号
     *
     * @param $table
     * @return mixed
     */
    public function getCacheVer($table)
    {
        $version = Model::getInstance()->cache()->get('db_cache_version_'.$table);
        if (!$version) {
            $version = microtime(true);
            Model::getInstance()->cache()->set('db_cache_version_'.$table, $version, $this->conf['cache_expire']);
        }
        return $version;
    }

    /**
     * 设置cache版本号
     *
     * @param $table
     */
    public function setCacheVer($table)
    {
        Model::getInstance()->cache()->set('db_cache_version_'.$table, microtime(true), $this->conf['cache_expire']);
    }

}