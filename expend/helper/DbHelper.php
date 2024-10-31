<?php

namespace tsim\expend\helper;
class DbHelper
{
    private $wpdb;
    private $table_name;
    private $fields = '*';
    private $alias = '';
    private $order = '';
    public $where = array();
    private $joins = array();
    private $query = '';
    private $limit = '';

    private function __construct($table_name)
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->table_name = $table_name;
    }

    public static function name($table_name)
    {
        global $wpdb;
        return new DbHelper($wpdb->prefix . $table_name);
    }

    public function field($fields = '*')
    {
        $this->fields = $fields;
        return $this;
    }

    public function order($order = '')
    {
        $this->order = $order;
        return $this;
    }

    public function where($conditions, $params = [])
    {

        $this->where[] = ['condition' => "(".$conditions.")", 'params' => $params];
        return $this;
    }

    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function join($table, $condition, $type = 'left')
    {
        $table = $this->wpdb->prefix . $table;
        $this->joins[] = "{$type} JOIN $table ON $condition";
        return $this;
    }

    public function select()
    {
        $this->query = $this->getQuerySql();
        return $this->wpdb->get_results($this->query);
    }

    public function getQuerySql()
    {
        $where_data = $this->getWhere();
        $where_clause = $where_data['where_clause'];
        $where_values = $where_data['where_values'];

        $table = $this->table_name;
        if (!empty($this->alias)) {
            $table = "{$this->table_name} {$this->alias}";
        }
        $query = "SELECT {$this->fields} FROM {$table}";


        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($where_clause)) {
            $query .= " WHERE $where_clause";
        }

        if (!empty($this->order)) {
            $query .= " order by $this->order";
        }

        if (!empty($this->limit)) {
            $query .= " LIMIT " . ($this->limit);
        }
        if (!empty($where_values)) {
            $query = $this->wpdb->prepare($query, $where_values);
        }

        return $query;
    }

    private function getWhere()
    {
        $where_clause = '';
        $where_values = array();
        foreach ($this->where as $value) {
            $where_clause .= $value['condition'] . ' AND ';
            if (isset($value['params']) && is_array($value['params'])) {
                foreach ($value['params'] as $v) {
                    $where_values[] = $v;
                }
            }
        }
        // 去除最后一个AND
        $where_clause = rtrim($where_clause, 'AND ');

        $rs = [
            'where_clause' => $where_clause,
            'where_values' => $where_values,
        ];
        return $rs;
    }

    public function limit($num, $page = 1)
    {
        $this->limit = $num;
        if ($page > 1) {
            $offset = ($page - 1) * $num;
            $this->limit = "$offset, $num";
        }

        return $this;
    }

    public function find($id = null)
    {
        if ($id !== null) {
            $this->where(array($this->getPrimaryKey(), $id));
        }
        $this->limit(1);
        $data = $this->select();
        return !empty($data) ? array_shift($data) : [];
    }

    public function getLastSql($id = null)
    {
        return $this->query;
    }

    protected function getPrimaryKey()
    {
        $primary_key = '';

        // 执行查询
        $results = $this->wpdb->get_results("SHOW KEYS FROM {$this->table_name} WHERE Key_name = 'PRIMARY'");

        // 提取主键字段
        if ($results) {
            foreach ($results as $result) {
                $primary_key = $result->Column_name;
                break;
            }
        }

        return $primary_key;
    }

    public function updateArrData($data = [], $where = [])
    {

        $table_name = $this->table_name;

//        error_log('sqlsql:' . var_export($sql, true));
        // 执行更新操作
        return $this->wpdb->update($table_name, $data, $where);
    }
    public function updateData($data, $where)
    {

        $table_name = $this->table_name;

        // 构建 SET 子句
        $set_clause = '';
        foreach ($data as $key => $value) {
            $set_clause .= "$key = '$value', ";
        }
        $set_clause = rtrim($set_clause, ', '); // 去除最后一个逗号和空格

        // 构建更新 SQL 语句
        $sql = "UPDATE $table_name SET $set_clause WHERE $where";
        if (!empty($this->limit)) {
            $sql .= " limit {$this->limit}";
        }
//        error_log('sqlsql:' . var_export($sql, true));
        // 执行更新操作
        return $this->wpdb->query($sql);
    }

    public function insertData($data_to_insert)
    {

        $table_name = $this->table_name;

        $rs = $this->wpdb->insert($table_name, $data_to_insert);
        // 检查插入是否成功
        if ($this->wpdb->last_error) {
            return false; // 插入失败
        }
        return $rs; // 插入成功
    }

}

