<?php

namespace ckvsoft;

class Database extends \PDO {

    /** @var boolean $activeTransaction Whether a transaction is going on */
    public $activeTransaction;

    /** @var string $_sql Stores the last SQL command */
    private $_sql;

    /** @var constant $_fetchMode The select statement fetch mode */
    private $_fetchMode = \PDO::FETCH_ASSOC;

    /**
     * __construct - Initializes a PDO connection (Two ways of connecting)
     *
     * @param array $db An associative array containing the connection settings,
     * @param string $type Optional if using arugments to connect
     * @param string $host Optional if using arugments to connect
     * @param string $name Optional if using arugments to connect
     * @param string $user Optional if using arugments to connect
     * @param string $pass Optional if using arugments to connect
     *
     *  // First Way:
     *    $db = array(
     *        'type' => 'mysql'
     *        ,'host' => 'localhost'
     *        ,'name' => 'test'
     *        ,'user' => 'root'
     *        ,'pass' => ''
     *    );
     *  $db = new ckvsoft\Database($db);
     *
     *  // Second Way:
     *  $db = new ckvsoft\Database(null, 'mysql', 'localhost', 'test', 'root', '');
     */
    public function __construct($db, $type = null, $host = null, $name = null, $user = null, $pass = null, $persistent = false) {
        try {
            /** Connect with arguments */
            if ($db == false || $db == null) {
                parent::__construct("{$type}:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, array(\PDO::ATTR_PERSISTENT => $persistent));
            }
            /** Connect with assoc array */ else {
                $persistent = isset($db['persistent']) ? $db['persistent'] : false;
                parent::__construct("{$db['type']}:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], array(\PDO::ATTR_PERSISTENT => $persistent));
            }
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }

    /**
     * setFetchMode - Change the default mode for fetching a query
     *
     * @param constant $fetchMode Use the PDO fetch constants, eg: \PDO::FETCH_CLASS
     */
    public function setFetchMode($fetchMode) {
        $this->_fetchMode = $fetchMode;
    }

    /**
     * select - Run & Return a Select Query
     *
     * @param string $query Build a query with ? marks in the proper order,
     *    eg: SELECT :email, :password FROM tablename WHERE userid = :userid
     *
     * @param array $bindParams Fields The fields to select to replace the :colin marks,
     *    eg: array('email' => 'email', 'password' => 'password', 'userid' => 200);
     *
     * @param constant $overrideFetchMode Pass in a PDO::FETCH_MODE to override the default or the setFetchMode setting
     *
     * @return array
     */
    public function select($query, $bindParams = array(), $overrideFetchMode = null) {
        /** Store the SQL for use with fetching it when desired */
        $this->_sql = $query;

        /** Make sure bindParams is an array, I mess this up a lot when overriding fetch! */
        if (!is_array($bindParams))
                throw new \ckvsoft\CkvException("$bindParams must be an array");

        /** Run Query and Bind the Values */
        $sth = $this->_prepareAndBind($bindParams);

        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);

        /** Automatically return all the goods */
        if ($overrideFetchMode != null)
                return $sth->fetchAll($overrideFetchMode);
        else return $sth->fetchAll($this->_fetchMode);
    }

    /**
     * insert - Convenience method to insert data
     *
     * @param string $table    The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function insert($table, $data) {
        /** Prepare SQL Code */
        $insertString = $this->_prepareInsertString($data);

        /** Store the SQL for use with fetching it when desired */
        $this->_sql = "INSERT INTO `{$table}` (`{$insertString['names']}`) VALUES({$insertString['values']})";

        /** Bind Values */
        $sth = $this->_prepareAndBind($data);

        /** Execute Query */
        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);

        if ($result == false) return false;

        /** Return the insert id */
        return $this->lastInsertId();
    }

    /**
     * update - Convenience method to update the database
     *
     * @param string $table The table to update
     * @param array $data An associative array of fields to change: field => value
     * @param string $where A condition on where to apply this update
     * @param array $bindWhereParams If $where has parameters, apply them here
     *
     * @return boolean Successful or not
     */
    public function update($table, $data, $where, $bindWhereParams = array()) {
        /** Build the Update String */
        $updateString = $this->_prepareUpdateString($data);
        /** Store the SQL for use with fetching it when desired */
        $this->_sql = "UPDATE `{$table}` SET $updateString WHERE $where";

        /** Bind Values */
        $sth = $this->_prepareAndBind($data);

        /** Bind Where Params */
        $sth = $this->_prepareAndBind($bindWhereParams, $sth);

        /** Execute Query */
        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);

        /** Return Result */
        return $result;
    }

    /**
     * replace - Convenience method to replace into the database
     *              Note: Replace does a Delete and Insert
     *
     * @param string $table The table to update
     * @param array $data An associative array of fields to change: field => value
     *
     * @return boolean Successful or not
     */
    public function replace($table, $data) {
        /** Build the Update String */
        $updateString = $this->_prepareUpdateString($data);

        /** Prepare SQL Code */
        $this->_sql = "REPLACE INTO `{$table}` SET $updateString";

        /** Bind Values */
        $sth = $this->_prepareAndBind($data);

        /** Execute Query */
        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);

        /** Return Result */
        return $result;
    }

    /**
     * delete - Convenience method to delete rows
     *
     * @param string $table The table to delete from
     * @param string $where A condition on where to apply this call
     * @param array $bindWhereParams If $where has parameters, apply them here
     *
     * @return integer Total affected rows
     */
    public function delete($table, $where, $bindWhereParams = array()) {
        /** Prepare SQL Code */
        $this->_sql = "DELETE FROM `{$table}` WHERE $where";

        /** Bind Values */
        $sth = $this->_prepareAndBind($bindWhereParams);

        /** Execute Query */
        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);

        /** Return Result */
        return $sth->rowCount();
    }

    /**
     * insertUpdate - Convenience method to insert/if key exists update.
     *
     * @param string $table    The table to insert into
     * @param array $data    An associative array of data: field => value
     */
    public function insertUpdate($table, $data) {
        /** Prepare SQL Code */
        $insertString = $this->_prepareInsertString($data);
        $updateString = $this->_prepareUpdateString($data);

        /** Store the SQL for use with fetching it when desired */
        $this->_sql = "INSERT INTO `{$table}` (`{$insertString['names']}`) VALUES({$insertString['values']}) ON DUPLICATE KEY UPDATE {$updateString}";

        /** Bind Values */
        $sth = $this->_prepareAndBind($data);

        /** Execute Query */
        $result = $sth->execute();

        /** Throw an exception for an error */
        $this->_handleError($result, __FUNCTION__);

        /** Return the insert id */
        return $this->lastInsertId();
    }

    /**
     * getQuery - Return the last sql Query called
     *
     * @return string
     */
    public function showQuery() {
        return $this->_sql;
    }

    /*
     * showTables - Return all the tables of current Database
     *
     * @return string
     */

    public function showTables() {
        $tablesResult = $this->select("SHOW TABLES");
        $tables = array();
        foreach ($tablesResult as $tableRow) {
            $tableValues = array_values($tableRow);
            $tables[] = $tableValues[0];
        }
        return $tables;
    }

    /**
     * id - Gets the last inserted ID
     *
     * @return integer
     */
    public function id() {
        return $this->lastInsertId();
    }

    /**
     * beginTransaction - Overloading default method
     */
    public function beginTransaction(): bool {
        parent::beginTransaction();
        $this->activeTransaction = true;
    }

    /**
     * commit - Overloading default method
     */
    public function commit(): bool {
        parent::commit();
        $this->activeTransaction = false;
    }

    /**
     * rollback - Overloading default method
     */
    public function rollback(): bool {
        parent::rollback();
        $this->activeTransaction = false;
    }

    /**
     * showColumns - Display the columns for a table (MySQL)
     *
     * @param string $table Name of a MySQL table
     */
    public function showColumns($table) {
        $result = $this->select("SHOW COLUMNS FROM `$table`", array(), \PDO::FETCH_ASSOC);

        $output = array();
        foreach ($result as $key => $value) {

            if ($value['Key'] == 'PRI') $output['primary'] = $value['Field'];

            $output['column'][$value['Field']] = $value['Type'];
        }

        return $output;
    }

    /**
     * _prepareAndBind - Binds values to the Statement Handler
     *
     * @param array $data
     * @param object $reuseStatement If you need to reuse the statement to apply another bind
     *
     * @return object
     */
    private function _prepareAndBind($data, $reuseStatement = false) {
        if ($reuseStatement == false) {
            $sth = $this->prepare($this->_sql);
        } else {
            $sth = $reuseStatement;
        }

        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $sth->bindValue(":$key", $value, \PDO::PARAM_INT);
            } else {
                $sth->bindValue(":$key", $value, \PDO::PARAM_STR);
            }
        }

        return $sth;
    }

    /**
     * _prepareInsertString - Handles an array and turns it into SQL code
     *
     * @param array $data The data to turn into an SQL friendly string
     * @return array
     */
    private function _prepareInsertString($data) {
        /**
         * @ Incoming $data looks like:
         * $data = array('field' => 'value', 'field2'=> 'value2');
         */
        return array(
            'names' => implode("`, `", array_keys($data)),
            'values' => ':' . implode(', :', array_keys($data))
        );
    }

    /**
     * _prepareUpdateString - Handles an array and turn it into SQL code
     *
     * @param array $data
     * @return string
     */
    private function _prepareUpdateString($data) {
        /**
         * @ Incoming $data looks like:
         * $data = array('field' => 'value', 'field2'=> 'value2');
         */
        $fieldDetails = NULL;
        foreach ($data as $key => $value) {
            $fieldDetails .= "`$key`=:$key, ";/** Notice the space after the comma */
        }
//        if ($fieldDetails !== null) {
        $fieldDetails = rtrim($fieldDetails, ', ');
//        }

        return $fieldDetails;
    }

    /**
     * _handleError - Handles errors with PDO and throws an exception.
     */
    private function _handleError($result, $method) {

        /** If it's an SQL error */
        if ($this->errorCode() != '00000') {
            throw new \ckvsoft\CkvException("Error: " . implode(',', $this->errorInfo()));
        }

        if ($result == false) {
            $error = $method . " did not execute properly, " . $this->showQuery();
            throw new \ckvsoft\CkvException($error);
        }
    }

}
