<?php
namespace Flint\ControllerExtender;
use PDO;
use PDOStatement;
use PDOException;

class EasyPDO extends PDO
{
    private $fetchMode = PDO::FETCH_ASSOC;

    /**
     * Class constructor
     *
     * @param  string $dsn Connection DSN
     * @param  string $user Connection user name
     * @param  string $passwd Connection password
     * @param  string $options PDO driver options
     * @return PDO
     */
    public function  __construct($dsn, $user = '', $passwd = '', $options = NULL)
    {
        $driverOptions = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        );
        if (!empty($options)) {
            $driverOptions = array_merge($driverOptions, $options);
        }

        parent::__construct($dsn, $user, $passwd, $driverOptions);
    }

    /**
     * Prepare and returns a PDOStatement
     *
     * @param  string $sql SQL statement
     * @param  array $bind parameters. A single value or an array of values
     * @return PDOStatement
     */
    private function _prepare($sql, $bind = array())
    {
        $stmt = $this->prepare($sql);

        if (!$stmt) {
            $errorInfo = $this->errorInfo();
            throw new PDOException("Database error [{$errorInfo[0]}]: {$errorInfo[2]}, driver error code is $errorInfo[1]");
        }
        if (!is_array($bind)) {
            $bind = empty($bind) ? array() : array($bind);
        }
        if (!$stmt->execute($bind) || $stmt->errorCode() != '00000') {
            $errorInfo = $stmt->errorInfo();
            throw new PDOException("Database error [{$errorInfo[0]}]: {$errorInfo[2]}, driver error code is $errorInfo[1]");
        }

        return $stmt;
    }

    /**
     * Execute sql and returns number of effected rows
     *
     * Should be used for query which doesn't return resultset
     *
     * @param  string $sql SQL statement
     * @param  array $bind parameters. A single value or an array of values
     * @return integer Number of effected rows
     */
    protected function run($sql, $bind = array())
    {
        $stmt = $this->_prepare($sql, $bind);
        return $stmt->rowCount();
    }

    /**
     * set fetch mode for PDO
     *
     * @param  string $fetchMode PDO fetch mode
     * @return PDO
     */
    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
        return $this;
    }

    /**
     * get where expression (if array, convert to sting)
     *
     * @param  string $where where string or array
     * @param  array  $andOr AND or OR
     *
     * @return string  where string
     */
    protected function where($where, $andOr = 'AND')
    {
        if (is_int($where)) {
            return ('id = ' . $where);
        }

        if (is_array($where)) {
            $tmp = array();
            foreach ($where as $k => $v) {
                $tmp[] = $k . '=' . $this->quote($v);
            }
            return '(' . implode(' ' . $andOr . ' ', $tmp) . ')';
        }
        return $where;
    }

    /**
     * insert a record to a table
     *
     * @param  string $table table name
     * @param  array $data data array
     * @return integer last insert id
     */
    public function insert($table, $data)
    {
        $fields = array_keys($data);
        $sql = 'INSERT INTO ' . $table . ' (' . implode($fields, ', ') . ') VALUES (:' . implode($fields, ', :') . ');';
        $bind = array();
        foreach ($fields as $field) {
            $bind[':' . $field] = $data[$field];
        }
        $this->run($sql, $bind);

        return $this->lastInsertId($table);
    }

    /**
     * update records for one table
     *
     * @param  string $table table name
     * @param  array $data data array
     * @param  string $where where string
     * @param  array $bind parameters. A single value or an array of values
     * @return array
     */
    public function update($table, $data, $where = '', $bind = array())
    {
        $sql = 'UPDATE ' . $table . ' SET ';
        $comma = '';
        if (!is_array($bind)) {
            $bind = empty($bind) ? array() : array($bind);
        }
        foreach ($data as $k => $v) {
            $sql .= $comma . $k . ' = :upd_' . $k;
            $comma = ', ';
            $bind[':upd_' . $k] = $v;
        }
        if (!empty($where)) {
            $where = $this->where($where);
            $sql .= ' WHERE ' . $where;
        }
        return $this->run($sql, $bind);
    }

    /**
     * delete records from table
     *
     * @param  string $table table name
     * @param  string $where where string
     * @param  array $bind parameters. A single value or an array of values
     * @return array
     */
    public function delete($table, $where, $bind = array())
    {
        $sql = 'DELETE FROM ' . $table;
        if (!empty($where)) {
            $where = $this->where($where);
            $sql .= ' WHERE ' . $where;
        }
        return $this->run($sql, $bind);
    }

    /**
     * save data to table (update is exists, else insert)
     *
     * @param  string $table table name
     * @param  array $data data array
     * @param  mixed $where SQL WHERE string or key/value array
     * @param  array $bind parameters. A single value or an array of values
     * @return mixed
     */
    public function save($table, $data, $where = '', $bind = array())
    {
        $count = 0;
        if (array_key_exists('id', $data) && !$where) {
            $where = ['id' => $data['id']];
            $count = 1;
        }
        if (!empty($where)) {
            $count = 1;
            //$where = $this->where($where);
            //$count = $this->fetchOne('SELECT COUNT(1) FROM ' . $table . ' WHERE ' . $where, $bind);
        }

        if ($count == 0) {
            return $this->insert($table, $data);
        } else {
            return $this->update($table, $data, $where, $bind);
        }
    }

    /**
     * Execute sql and returns a single value
     *
     * @param  string $sql SQL statement
     * @param  array $bind A single value or an array of values
     * @return mixed  Result value
     */
    public function fetchOne($sql, $bind = array())
    {
        $stmt = $this->_prepare($sql, $bind);
        return $stmt->fetchColumn(0);
    }

    /**
     * Execute sql and returns the first row
     *
     * @param  string $sql SQL statement
     * @param  array $bind A single value or an array of values
     * @return array   A result row
     */
    public function fetchRow($sql, $bind = array())
    {
        $stmt = $this->_prepare($sql, $bind);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Execute sql and returns row(s) as 2D array
     *
     * @param  string $sql SQL statement
     * @param  array $bind A single value or an array of values
     * @return array   Result rows
     */
    public function fetchAll($sql, $bind = array())
    {
        $stmt = $this->_prepare($sql, $bind);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * select records from a table
     *
     * @param  string $table table name
     * @param  string|array|integer $where where string
     * @param  string|null $fields fields list
     * @param  string $order order string
     * @param  string $limit limit string (MySQL is '[offset,] row_count')
     * @return array
     */
    protected function _select($table, $where, $fields = null, $order = NULL, $limit = NULL)
    {
        if (!$fields) {
            $fields = '*';
        }
        if (is_array($fields)) {
            $fields = '`' . implode('`, `', $fields) . '`';
        }

        $sql = 'SELECT ' . $fields . ' FROM ' . $table;
        if (!empty($where)) {
            $where = $this->where($where);
            $sql .= ' WHERE ' . $where;
        }
        if (!empty($order)) {
            $sql .= ' ORDER BY ' . $order;
        }
        if ($limit && $limit > 1) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $sql;
    }

    /**
     * select records from a table
     *
     * @param  string $table table name
     * @param  string|array|integer $where where string
     * @param  string|null $fields fields list
     * @param  array $bind parameters. A single value or an array of values
     * @param  string $order order string
     * @param  string $limit limit string (MySQL is '[offset,] row_count')
     * @return array
     */
    public function select($table, $where, $fields = null, $bind = array(), $order = NULL, $limit = NULL)
    {
        $sql = $this->_select($table, $where, $fields, $order, $limit);

        return $this->fetchAll($sql, $bind);
    }

    /**
     * Create sql, execute and returns first row
     *
     * @param  string      $table          Table name
     * @param  string|array|integer $where Where string
     * @param  string|null $fields         Fields list
     * @param  array       $bind           Parameters. A single value or an array of values
     *
     * @return array
     */
    public function selectRow($table, $where, $fields = null, $bind = array())
    {
        $sql = $this->_select($table, $where, $fields);

        return $this->fetchRow($sql, $bind);
    }

    public function selectOne($table, $where, $fields = null, $bind = array())
    {
        $sql = $this->_select($table, $where, $fields);

        return $this->fetchOne($sql, $bind);
    }
}
