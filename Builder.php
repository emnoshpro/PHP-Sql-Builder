<?php
//
// +----------------------------------------------------------------------+
// | Builder.php                                                          |
// +----------------------------------------------------------------------+
// | helper to Build your SQL                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2020                                                   |
// +----------------------------------------------------------------------+
// | Authors:  <emnosh.pro@gmail.com.au>                                  |
// +----------------------------------------------------------------------+
//

/**
 * Sql class is responsible to create SQL queries.
 *
 * Important: Verify that every feature you use will work.
 * SQL Query Builder does not attempt to validate the generated SQL at all.
 * INSERT ON DUPLICATE was not implemented.
 * INSERT ... SELECT was not implemented.
 *
 */
    // namespace emnoshpro\Dbal\SQL_Builder;
    namespace Sql;

    class Builder
    {
        const AND = 'AND';
        const OR = 'OR';
        const ASC = 'ASC';
        const DESC = 'DESC';

        // operations
        const SELECT = 1;
        const UPDATE = 2;
        const DELETE = 3;
        const INSERT = 4;
        const INSERT_IGNORE = 5;
        const REPLACE = 6;

        // join types
        const INNER = 0;
        const OUTER = 1;
        const LEFT  = 2;
        const RIGHT = 3;

        private $_table_name = '';  // table name
        private $_table_alias = '';  // table alias
        private $_joins = [];  // joins
        private $_columns = [];  // columns efault is all
        private $_wheres = [];  // wheres
        private $_limits = [];  // offset, limit
        private $_when = [];  // case statements
        private $_groupBy = [];  // group by
        private $_having = [];  // having clauses
        private $_orderBy = [];  // order by

        const FUNCTION_SQL_AGGREGATE = ["AVG", "COUNT", "MAX", "MIN", "SUM"];

        // used internally
        private $_type = false;      // one has to set it
        private $_join_types = ['INNER JOIN', 'OUTER JOIN', 'LEFT JOIN', 'RIGHT JOIN'];
        private $_calcFoundRows = false;
        private $_distinct = false;
        private static $reserved = null;

        /**
         * __construct
         *
         * @param  integer $type
         * @param  string $table_name
         * @param  string $table_alias
         * @return void
         */
        public function __construct(
            int $type = self::SELECT,
            string $table_name,
            $table_alias = null
        )
        {
            $this->_type = $type;
            $this->_table_name = $table_name;

            if (!empty($table_alias)) {
                $this->_table_alias = $table_alias;
            }
        }

        /**
         * populateReserveWords
         *
         */
        public static function populateReserveWords()
        {
            if (empty(self::$reserved) && file_exists(__DIR__ . '/reserved.txt')) {
                self::$reserved = file(__DIR__ . '/reserved.txt');
                foreach (self::$reserved as $reserved_word) {
                    self::$reserved[] = rtrim($reserved_word, "\r\n");
                }
            }
        }

        /**
         * _validate
         *
         * @param  string $table_name
         * @param  string $table_alias
         * @return boolean
         */
        protected static function _validate(
            string $table_name,
            string $table_alias = null,
            &$error = []
        ):bool
        {
            // TODO: table name/alias length const
            // https://dev.mysql.com/doc/refman/5.7/en/identifier-length.html
            $table_name = trim($table_name);

            if (empty($table_name)) {
                $error[] = sprintf('Table name, can not be empty.');
            } else if (strlen($table_name) > 64) {
                $error[] = sprintf('Table name, %s, too lengthy', $table_name);
            }

            if (empty($error)
                && !empty($table_alias)
                && strlen($table_alias) > 256
            ) {
                $error[] = sprintf('Table alias, %s, too lengthy', $table_alias);
            }

            if (empty($error)) {
                self::populateReserveWords();
                if (isset(self::$reserved) && in_array(strtoupper($table_name), self::$reserved)) {
                    $error[] = sprintf('Table name, %s, is a reserved word', $table_alias);
                }
            }
            if (!empty($error) && count($error)) {
                return false;
            }

            return true;
        }

        /**
         * select
         *
         * @param  string $table_name
         * @param  string $table_alias
         * @return self
         */
        public static function select(
            string $table_name,
            string $table_alias = null
        ): self
        {
            if (self::_validate($table_name, $table_alias, $error) === false) {
                trigger_error(__METHOD__ . '()@' . __LINE__ . ' ' . join(',', $error), E_USER_ERROR);
            }

            return new Builder(self::SELECT, $table_name, $table_alias);
        }

        /**
         * update
         *
         * @param  string $table_name
         * @param  string $table_alias
         * @return self
         */
        public static function update(
            string $table_name,
            string $table_alias = null
        ):self
        {
            if (self::_validate($table_name, $table_alias, $error) === false) {
                trigger_error(__METHOD__ . '()@' . __LINE__ . ' ' . join(',', $error), E_USER_ERROR);
            }

            return new Builder(self::UPDATE, $table_name, $table_alias);
        }

        /**
         * insert
         *
         * @param  string $table_name
         * @param  string $table_alias
         * @return self
         */
        public static function insert(
            string $table_name,
            string $table_alias = null
        ):self
        {
            if (self::_validate($table_name, $table_alias, $error) === false) {
                trigger_error(__METHOD__ . '()@' . __LINE__ . ' ' . join(',', $error), E_USER_ERROR);
            }

            return new Builder(self::INSERT, $table_name, $table_alias);
        }

        /**
         * insertIgnore
         *
         * @param  string $table_name
         * @param  string $table_alias
         * @return self
         */
        public static function insertIgnore(
            string $table_name,
            string $table_alias = null
        ):self
        {
            if (self::_validate($table_name, $table_alias, $error) === false) {
                trigger_error(__METHOD__ . '()@' . __LINE__ . ' ' . join(',', $error), E_USER_ERROR);
            }

            return new Builder(self::INSERT_IGNORE, $table_name, $table_alias);
        }

        /**
         * delete
         *
         * @param  mixed $table_name
         * @param  mixed $table_alias
         * @return self
         */
        public static function delete(
            string $table_name,
            string $table_alias = null
        ):self
        {
            if (self::_validate($table_name, $table_alias, $error) === false) {
                trigger_error(__METHOD__ . '()@' . __LINE__ . ' ' . join(',', $error), E_USER_ERROR);
            }

            return new Builder(self::DELETE, $table_name, $table_alias);
        }

        /**
         * columns
         *
         * @param  mixed $column_data
         * @return self
         */
        public function columns(
            $column_data
        ):self
        {
            // TODO: open reserved word file and check for reserved word
            // TODO: check for column length and alias length
            // SELECT
            //     id,
            //     action_heading,
            //     CASE
            //         WHEN action_type = 'Income' THEN action_amount
            //         ELSE NULL
            //     END AS income_amt,
            //     CASE
            //         WHEN action_type = 'Expense' THEN action_amount
            //         ELSE NULL
            //     END AS expense_amt
            // FROM tbl_transaction;
            // SELECT
            //   id, action_heading,
            //       IF(action_type='Income',action_amount,0) income,
            //       IF(action_type='Expense', action_amount, 0) expense
            // FROM tbl_transaction
            if (is_string($column_data)) {
                if (strpos($column_data, ',') !== false) {
                    // a,b,c,d,e,f
                    // we explode and add them as array
                    $columns = explode(',', $column_data);
                } else {
                    $function = substr($column_data, 0, strcspn($column_data, '\(.*\)'));
                    // we have aggregate functions
                    // functions avg()/sum()/count()/min()/max()
                    if (!empty($function) && in_array($function, self::FUNCTION_SQL_AGGREGATE)) {
                        $columns[] = $column_data;
                        // TODO: addGroupBy default?
                    }
                }
            } else if (is_array($column_data) && array_keys($column_data) !== range(0, count($column_data) - 1)) {
                // [a, b, c, d] no aliases
                // [a=>1, b=>2, c=>3] (value is alias)
                // we detect if its a key value pair array
                // keys => value pair
                $columns = array_map(function($key, $value) {
                    if ($this->_type === self::SELECT) {
                        $function = substr($key, 0, strcspn($key, '\(.*\)'));
                        if (!empty($function) && in_array($function, self::FUNCTION_SQL_AGGREGATE)) {
                            // we have aggregate functions
                            // functions avg()/sum()/count()/min()/max()
                            return $function . ' AS ' . $value;
                            // TODO: addGroupBy default?
                        }
                        return $key . ' AS ' . $value;
                    } else {
                        // if (in_array($this->_type, [self::UPDATE, self::INSERT, self::INSERT_IGNORE, self::REPLACE)) {
                        // if ($this->_type === self::UPDATE || $this->_type === self::INSERT || $this-) {
                        $value = trim($value);
                        if (!is_numeric($value)) {
                            $value = '\'' . addcslashes($value, "\'") . '\'';
                        } else {
                            $value = intval($value);
                        }
                        return $key . ' = ' . $value;
                    }
                }, array_keys($column_data), $column_data);
            }
            $this->_columns[] = $columns;

            return $this;
        }

        // aggregate column functions
        /**
         * avg
         *
         * @param  string $column
         * @param  string $column_alias
         * @return self
         */
        public function avg(
            string $column,
            $column_alias = null
        ):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['AVG(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('AVG(' . $column . ')');
            }

            return $this;
        }

        /**
         * count
         *
         * @param  string $column
         * @param  string $column_alias
         * @return self
         */
        public function columnCount(
            string $column,
            $column_alias = null
        ):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['COUNT(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('COUNT(' . $column . ')');
            }

            return $this;
        }

        /**
         * max
         *
         * @param  string $column
         * @param  string $column_alias
         * @return self
         */
        public function max(
            string $column,
            $column_alias = null
        ):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['MAX(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('MAX(' . $column . ')');
            }

            return $this;
        }

        /**
         * min
         *
         * @param  string $column
         * @param  string $column_alias
         * @return self
         */
        public function min(
            string $column,
            $column_alias = null
        ):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['MIN(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('MIN(' . $column . ')');
            }

            return $this;
        }

        /**
         * sum
         *
         * @param  string $column
         * @param  string $column_alias
         * @return self
         */
        public function sum(
            string $column,
            $column_alias = null
        ):self
        {
            if (!is_null($column_alias)) {
                $this->columns(['SUM(' . $column . ')' => $column_alias]);
            } else {
                $this->columns('SUM(' . $column . ')');
            }

            return $this;
        }

        /**
         * if
         *
         * @param  mixed $expression
         * @return self
         */
        public function if(...$expression):self
        {
            if (count($expression[0]) > 3) {
                $alias = array_pop($expression[0]);
            }
            $condition = implode(',', $expression[0]);

            $if = 'IF(' . $condition . ')';
            if (!empty($alias)) {
                $if = [$if => $alias];
            }

            $this->columns($if);
            return $this;
        }

        /**
         * case
         *
         * @param  mixed $expression
         * @return self
         */
        public function case(...$expression):self
        {
// CASE
//     WHEN condition1 THEN result1
//     WHEN condition2 THEN result2
//     WHEN conditionN THEN resultN
//     ELSE result
// END;
// SELECT OrderID, Quantity,
// CASE
//     WHEN Quantity > 30 THEN "The quantity is greater than 30"
//     WHEN Quantity = 30 THEN "The quantity is 30"
//     ELSE "The quantity is under 30"
// END
// FROM OrderDetails;
// SELECT CustomerName, City, Country
// FROM Customers
// ORDER BY
// (CASE
//     WHEN City IS NULL THEN Country
//     ELSE City
// END);
            if (is_array($expression)) {
                $this->_when[] = $expression;
            }

            return $this;
        }

        /**
         * groupBy
         *
         * @param  mixed $expression
         * @return self
         */
        public function groupBy(...$expression):self
        {
            if (is_array($expression)) {
                $this->_groupBy[] = $expression;
            }
            return $this;
        }

        /**
         * having
         *
         * @param  mixed $expression
         * @return self
         */
        public function having(...$expression):self
        {
            // TODO:
            return $this;
        }

        function orderBy(...$expression): self
        {
            // default when not provided.
            $display_order = self::ASC;

            // basically func_num_args()
            $count = count($expression);
            if ($count === 2) {
                $display_order = array_pop($expression);
            }

            $expression = array_shift($expression);
            if (!is_array($expression)) {
                if (strpos($expression, ',') !== false) {
                    $expression = explode(',', $expression);
                } else {
                    // just a single value passed convert it to an array
                    // not an indexed one as we are generating a new one
                    // avoids flipping and array_fill_keys
                    $expression = [$expression => $display_order];
                }
            }
            // we wanna re-count the total number of elements of new expression and not use the previous count
            // that will give number of args passed func_num_args
            $is_assoc = array_keys($expression) !== range(0, count($expression) - 1);
            if (!$is_assoc) {
                // index array we flip the values to be as keys
                $expression = array_flip($expression);
                $expression = array_fill_keys(array_keys($expression), $display_order);
            }

            foreach ($expression as $key => $value) {
                if (!in_array($value, [self::ASC, self::DESC])) {
                    // default
                    $value = self::ASC;
                }
                $this->_orderBy[] = strtoupper($key) . ' ' . $value;
            }

            return $this;
        }

        /**
         * where
         * filters records based on the expressions, criteria, passed.
         * ->where(['a', '=', 1], 'OR']
         * ->where(['a', '=', 1])           // defaults to AND
         * ->where('a', '=', 1)             // defaults to AND
         * ->where('a', '=', 1, 'OR')
         * We eventually want to convert all of above as
         * ->where('a', '=', 1, 'OR') not an Array
         *
         * @param  mixed $expression
         * @return self
         */
        public function where(...$expression)
        {
            $count = count($expression);        // func_num_args();
            // the aim of below checks is to always have 4 parameters in WHERE
            // ->where('a', '=', 1, 'OR') count === 4 (arguments as string)
            // when logical_operator is not provided, we default it to be as AND.
            if ($count === 1 ||
                $count === 3
            ) {
                // these will not have logical operators
                // ->where(['a', '=', 1]) count === 1
                // ->where('a', '=', 1) count === 3
                // missing logical operator, hence we add default AND
                if ($count === 1) {
                    // we flatten to single array
                    $expression = array_merge(...$expression);
                }
                return $this->where($expression[0], $expression[1], $expression[2], self::AND);
            } else if ($count === 2) {
                // ->where(['a', '=', 1], 'OR') count === 2
                // we convert it to ->where('a', '=', 1, 'OR');
                $logical_operator = array_pop($expression);
                $expression = array_merge(...$expression);
                return $this->where($expression[0], $expression[1], $expression[2], $logical_operator);
            }

            if (count($this->_wheres) === 0) {
                // avoids overhead of a calling function
                $this->_wheres[] = $expression;
            } else {
                array_push($this->_wheres, $expression);
            }

            return $this;
        }

        /**
         * andWhere
         *
         * @param  mixed $expression
         * @return self
         */
        public function andWhere($expression):self
        {
            return self::where($expression, self::AND);
        }

        /**
         * orWhere
         *
         * @param  array $expression
         * @return self
         */
        public function orWhere(array $expression = []):self
        {
            return self::where($expression, self::OR);
        }

        /**
         * andWheres
         *
         * @param  array $expressions
         * @return self
         */
        public function andWheres(array $expressions = []):self
        {
            if (count($expressions)) {
                foreach ($expressions as $expression) {
                    self::andWhere($expression);
                }
            }
            return $this;
        }

        /**
         * orWheres
         *
         * @param  array $expressions
         * @return self
         */
        public function orWheres(array $expressions = []):self
        {
            if (count($expressions)) {
                foreach ($expressions as $expression) {
                    self::orWhere($expression);
                }
            }
            return $this;
        }

        /**
         * join
         *
         * @param  string $table_name
         * @param  string $expression
         * @param  string $table_alias
         * @param  mixed $type
         * @return self
         */
        public function join(
            string $table_name,
            $expression,
            $table_alias = null,
            $type = self::INNER
        ):self
        {
            if (empty($table_name) || empty($expression)) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing table_name || expression', E_USER_ERROR);
            }

            if (empty($table_alias)) {
                $table_alias = substr($table_name, 0, 1);
            }

            if (self::_validate($table_name, $table_alias, $error) === false) {
                trigger_error(__METHOD__ . '()@' . __LINE__ . ' ' . join(',', $error), E_USER_ERROR);
            }

            $join_key = sprintf('%s %s.%s', $this->_join_types[$type], trim($table_alias), trim($table_name));

            $this->_joins[] = [$expression, $join_key];
            return $this;
        }

        /**
         * innerJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function innerJoin(
            $table_name,
            $expression,
            $table_alias = null
        ):self
        {
            return self::join($table_name, $expression, $table_alias);
        }

        /**
         * leftJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function leftJoin(
            $table_name,
            $expression,
            $table_alias = null
        ):self
        {
            return self::join($table_name, $expression, $table_alias, self::LEFT);
        }

        /**
         * outerJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function outerJoin(
            $table_name,
            $expression,
            $table_alias = null
        ):self
        {
            return self::join($table_name, $expression, $table_alias, self::OUTER);
        }

        /**
         * rightJoin
         *
         * @param  mixed $table_name
         * @param  mixed $expression
         * @param  mixed $table_alias
         * @return self
         */
        public function rightJoin(
            $table_name,
            $expression,
            $table_alias = null
        ):self
        {
            return self::join($table_name, $expression, $table_alias, self::RIGHT);
        }

        /**
         * on
         *
         * @param  mixed $expression
         * @return self
         */
        public function on(...$expression):self
        {
            if (empty($expression[0])) {
                trigger_error(__METHOD__.'()@'.__LINE__.' invalid arguments missing ON expression ', E_USER_ERROR);
            }
            // adding extra expressions on joins
            // we simply get the recent join and append it
            $_join_count = count($this->_joins);
            if ($_join_count > 0) {
                if (isset($expression[1])) {
                    $this->_joins[$_join_count - 1][0][] = $expression[1];
                }
                if (isset($expression[0])) {
                    $this->_joins[$_join_count - 1][0] = array_merge($this->_joins[$_join_count - 1][0], array_values($expression[0]));
                }
            }
            return $this;
        }

        /**
         * andOn
         *
         * @param  mixed $expression
         * @return self
         */
        public function andOn($expression):self
        {
            return self::on($expression, self::AND);
        }

        /**
         * orOn
         *
         * @param  mixed $expression
         * @return self
         */
        public function orOn($expression):self
        {
            return self::on($expression, self::OR);
        }

        /**
         * offsetLimits
         *
         * @param  mixed $offset
         * @param  mixed $limit
         * @return self
         */
        public function offsetLimits($offset, $limit):self
        {
            $this->_limits = [$offset, $limit];
            return $this;
        }

        /**
         * setOffset
         *
         * @param  mixed $offset
         * @return self
         */
        public function setOffset($offset): self
        {
            $this->_limits[0] = $offset;
            return $this;
        }

        /**
         * setLimit
         *
         * @param  mixed $limit
         * @return self
         */
        public function setLimit($limit):self
        {
            $this->_limits[1] = $limit;
            return $this;
        }

        /**
         * calcFoundRows
         *
         * @return self
         */
        public function calcFoundRows():self
        {
            $this->_calcFoundRows = true;
            return $this;
        }

        /**
         * distinct
         *
         * @return self
         */
        public function distinct():self
        {
            $this->_distinct = true;
            return $this;
        }

        /**
         * get
         *
         * @return string
         */
        public function getSql(): string
        {
            $sql = '';
            switch ($this->_type) {
                case self::DELETE:
                    $sql = sprintf('DELETE FROM %s', $this->getTableName());
                    if ($this->getWheres()) {
                        $sql .= ' ' . $this->getWheres();
                    }

                    if ($this->getLimits()) {
                        $sql .= ' ' . $this->getLimits();
                    }
                    break;
                case self::UPDATE:
                    $sql = sprintf('UPDATE %s SET', $this->getTableName());
                    // %s %s %s', $this->getTableName(), $this->getColumnNames(), $this->getWheres(), $this->getLimits());
                    $sql .= ' ' . $this->getColumnNames();

                    if ($this->getWheres()) {
                        $sql .= ' ' . $this->getWheres();
                    }

                    if ($this->getLimits()) {
                        $sql .= ' ' . $this->getLimits();
                    }

                    break;
                case self::INSERT:
                    // INSERT INTO table (a,b) VALUES (1,2), (2,3), (3,4);
                    $data = $this->getColumnNames();
                    $data = explode(',', $data);

                    $fields = [];
                    $values = [];
                    foreach ($data as $details) {
                        list($field, $value) = explode(' = ', $details);
                        $field = '\'' . addcslashes(trim($field), "\'") . '\'';
                        $fields[] = $field;
                        $values[] = $value;
                    }

                    if ($fields && $values) {
                        if (count($fields) === count($values)) {
                            $fields = implode(',', $fields);
                            $values = implode(',', $values);
                            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->getTableName(), $fields, $values);
                        }
                    }
                    break;
                case self::REPLACE:
                    break;
                default:
                    $sql = 'SELECT';
                    if ($this->_calcFoundRows) {
                        $sql .= ' SQL_CALC_FOUND_ROWS ';
                    }
                    if ($this->_distinct) {
                        $sql .= ' DISTINCT ';
                    }
                    $sql .= ' ' . $this->getColumnNames();

                    if ($this->getCaseStatements()) {
                        $sql .= ' ' . $this->getCaseStatements();
                    }

                    $sql .= ' FROM ' . $this->getTableName();

                    if ($this->getJoins()) {
                        $sql .= ' ' . $this->getJoins();
                    }

                    if ($this->getWheres()) {
                        $sql .= ' ' . $this->getWheres();
                    }

                    if ($this->getGroupBy()) {
                        $sql .= ' ' . $this->getGroupBy();
                    }

                    if ($this->getHaving()) {
                        $sql .= ' ' . $this->getHaving();
                    }

                    if ($this->getOrderBy()) {
                        $sql .= ' ' . $this->getOrderBy();
                    }

                    if ($this->getLimits()) {
                        $sql .= ' ' . $this->getLimits();
                    }
            }

            return $sql;
        }

        /**
         * getColumnNames
         *
         * @return string
         */
        public function getColumnNames(): string
        {
            if (is_array($this->_columns) && count($this->_columns)) {
                // flatten to a single array
                $columns = call_user_func_array('array_merge', $this->_columns);
                $columns = implode(', ', $columns);
                return sprintf('%s', $columns);
            }
            return '*';
        }

        /**
         * getCaseStatements
         *
         * @return string
         */
        public function getCaseStatements(): string
        {
            if (count($this->_when) > 0) {
                // we can have multiple when clauses
                // apply quotes to the last value
                $when = array_map(function($when) {
                    $result = array_pop($when[0]);
                    if ($result) {
                        if (!is_numeric($result)) {
                            $result = '\'' . addcslashes($result, "\'") . '\'';
                        }
                    }
                    $column_name = array_shift($when[0]);
                    $condition = implode(' ', $when[0]);

                    return sprintf('WHEN %s %s THEN %s', $column_name, $condition, $result);

                }, $this->_when);
            }
            if (!empty($when)) {
                return sprintf('CASE (%s) END ', implode(' ', $when));
            }
            return '';
        }

        /**
         * getTableName
         *
         * @return string
         */
        public function getTableName(): string
        {
            $table_name = $this->_table_name;
            if ($this->_type === self::SELECT) {
                if (!empty($this->_table_alias)) {
                    $table_name .= ' AS ' . $this->_table_alias;
                }
            }
            return $table_name;
        }

        /**
         * getJoins
         *
         * @return string
         */
        public function getJoins(): string
        {
            if (count($this->_joins) > 0) {
                // we can have multiple where clauses
                $joins = array_map(function($join) {
                    if (isset($join[0]) && isset($join[1])) {
                        return $join[1] . ' ON ' . implode(' ', $join[0]);
                    }
                }, $this->_joins);
            }
            if (!empty($joins)) {
                return sprintf('%s', implode(' ', $joins));
            }
            return '';
        }

        /**
         * getWheres
         *
         * @return string
         */
        public function getWheres(): string
        {
            // we can have multiple where clauses
            // apply quotes to values
            $wheres = array_map(function($where) {
                if (!empty($where[2])) {
                    if (is_array($where[2])) {
                        // IN_ARRAY / NOT IN ARRAY
                        $where[2] = '(' . implode(',', $where[2]) . ')';
                    } else if (!is_numeric($where[2])) {
                        $where[2] = '\'' . addcslashes($where[2], "\'") . '\'';
                    }
                }

                return $where;

            }, $this->_wheres);

            $count = count($wheres);
            if ($count) {
                $last_index = $count - 1;
                if (isset($wheres[$last_index][3])) {
                    // remove the logical operator
                    unset($wheres[$last_index][3]);
                }
                $wheres = iterator_to_array($this->flatten($wheres), false);
                return sprintf('WHERE %s', implode(' ', $wheres));
            }
            return '';
        }

        public function flatten (array $array)
        {
            foreach ($array as $value) {
                if (is_array($value)) {
                    yield from $this->flatten($value);
                } else {
                    yield $value;
                }
            }
        }

        /**
         * getGroupBy
         *
         * @return string
         */
        public function getGroupBy(): string
        {
            if (!empty($this->_groupBy)) {
                return sprintf('GROUP BY %s', implode(', ', $this->_groupBy));
            }
            return '';
        }

        /**
         * getHaving
         *
         * @return string
         */
        public function getHaving(): string
        {
            // TODO:
            return '';
        }

        /**
         * getOrderBy
         *
         * @return string
         */
        public function getOrderBy(): string
        {
            if (!empty($this->_orderBy)) {
                return sprintf('ORDER BY %s', implode(', ', $this->_orderBy));
            }
            return '';
        }

        /**
         * getLimits
         *
         * @return string
         */
        public function getLimits(): string
        {
            $limit = '';
            if (!empty($this->_limits[0])) {
                $limit .= $this->_limits[0] . ', ';
            }

            if (!empty($this->_limits[1])) {
                $limit .= $this->_limits[1];
            }

            if (!empty($limit)) {
                return sprintf('LIMIT %s', $limit);
            }
            return '';
        }

        /**
         * __toString
         *
         * @return void
         */
        function __toString()
        {
            return $this->getSql();
        }
    }
