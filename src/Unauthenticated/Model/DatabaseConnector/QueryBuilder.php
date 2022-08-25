<?php

namespace App\Unauthenticated\Model\DatabaseConnector;

use Exception;

/**
 * Custom Date Time
 * Might be more readable if this gets split up into
 * QueryBuilderSelect, QueryBuilderUpdate, QueryBuilderInsert
 *
 * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
 */
class QueryBuilder
{

    /**
     * Variable for the conditions
     *
     * @var array
     */
    private array $conditions = [];

    /**
     * Variable for the fields
     *
     * @var array
     */
    private array $fields = [];

    /**
     * Variable for FROM
     *
     * @var array
     */
    private array $from = [];

    /**
     * Variable for the insertKeys
     *
     * @var array
     */
    private array $insertKeys = [];

    /**
     * Variable for the insertValues
     *
     * @var array
     */
    private array $insertValues = [];

    /**
     * Variable for the isInsert
     *
     * @var boolean
     */
    private bool $isInsert = false;

    /**
     * Variable for the isSelect
     *
     * @var boolean
     */
    private bool $isSelect = false;

    /**
     * Variable for the isUpdate
     *
     * @var boolean
     */
    private bool $isUpdate = false;

    /**
     * Variable for the limit
     *
     * @var integer
     */
    private int $limit;

    /**
     * Variable for the offset
     *
     * @var integer
     */
    private int $offset;

    /**
     * Variable for the outputFields
     *
     * @var array
     */
    private array $outputFields = [];

    /**
     * Variable for the sets
     *
     * @var array
     */
    private array $sets = [];

    /**
     * Variable for the sortBy
     *
     * @var array
     */
    private array $sortBy = [];

    /**
     * Variable for the table
     *
     * @var string
     */
    private string $table;


    /**
     * Used to prevent SQL injection attacks
     *
     * @param string $input field input text
     *
     * @return     string
     * @deprecated use AbstractQueryBuilder instead
     */
    public static function sanitizeInput(string $input): string
    {
        return utf8_encode(str_replace("'", "", $input));
    }


    /**
     * ToString function
     *
     * @return string
     * @throws Exception when query is neither select, update nor insert
     *
     * @noinspection SqlWithoutWhere
     * @deprecated   Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function __toString(): string
    {
        $whereStr = $this->conditions === [] ? "" : " WHERE ".implode(" AND ", $this->conditions);
        if ($this->isSelect === true) {
            $orderStr  = $this->sortBy === [] ? "" : " ORDER BY ".implode(", ", $this->sortBy);
            $fieldsStr = implode(', ', $this->fields);
            $fromStr   = implode(', ', $this->from);
            if (empty($this->limit) === true) {
                return "SELECT $fieldsStr FROM $fromStr$whereStr$orderStr";
            }
            if ($this->offset === 0) {
                return "SELECT TOP $this->limit $fieldsStr FROM $fromStr$whereStr$orderStr";
            }
            return "SELECT $fieldsStr FROM $fromStr$whereStr$orderStr OFFSET $this->offset ROWS FETCH NEXT $this->limit ROWS ONLY";
        }
        if ($this->isUpdate === true) {
            $setsStr = implode(', ', $this->sets);
            return "UPDATE $this->table SET $setsStr$whereStr";
        }
        if ($this->isInsert === true) {
            $outputStr = $this->outputFields === [] ? "" : " OUTPUT ".implode(", ", $this->outputFields);
            $keysStr   = implode(', ', $this->insertKeys);
            $valuesStr = implode(', ', $this->insertValues);
            return "INSERT INTO $this->table ($keysStr) $outputStr VALUES($valuesStr)";
        }
        throw new Exception();
    }


    /**
     * Add SET-> column will be set to a function
     *
     * @param string $column   name of the column which values will be set by the function
     * @param string $function function that will be used to set the values. No sanitization will be applied to this argument.
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function addSetFunc(string $column, string $function)
    {
        $set          = $column."=$function";
        $this->sets[] = $set;
    }


    /**
     * Add SET-> column will be set to int value
     *
     * @param string $column name of the column which values will be set
     * @param int    $value  int value, the column will be set to
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function addSetInt(string $column, int $value)
    {
        $set          = $column."=$value";
        $this->sets[] = $set;
    }


    /**
     * Add SET-> column will be set to string value
     *
     * @param string $column name of the column which values will be set
     * @param string $value  string value, the column will be set to
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function addSetStr(string $column, string $value)
    {
        $value        = AbstractQueryBuilder::sanitizeInput($value);
        $set          = $column."='$value'";
        $this->sets[] = $set;
    }


    /**
     * Add a WHERE clause, where the column must contain a function
     * WHERE $column = $func
     *
     * @param string $column  column that needs to contain the function
     * @param string $func    func the column must contain
     * @param bool   $isEqual true by default, when set to false the where is set to not equal
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereEqualsFunc(string $column, string $func, bool $isEqual = true)
    {
        if ($isEqual === true) {
            $condition = $column."=$func";
        } else {
            $condition = $column."!=$func";
        }
        $this->conditions[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column must contain an int value
     * WHERE $column = '$value'
     *
     * @param string $column  column that needs to contain the value
     * @param int    $value   value the column must contain
     * @param bool   $isEqual true by default, when set to false the where is set to not equal
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereEqualsInt(string $column, int $value, bool $isEqual = true)
    {
        if ($isEqual === true) {
            $condition = $column."=$value";
        } else {
            $condition = $column."!=$value";
        }
        $this->conditions[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column must contain a string value
     * WHERE $column = '$value'
     *
     * @param string $column  column that needs to contain the value
     * @param string $value   value the column must contain
     * @param bool   $isEqual true by default, when set to false the where is set to not equal
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereEqualsStr(string $column, string $value, bool $isEqual = true)
    {
        $value = AbstractQueryBuilder::sanitizeInput($value);
        if ($isEqual === true) {
            $condition = $column."='$value'";
        } else {
            $condition = $column."!='$value'";
        }
        $this->conditions[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column is boolean
     * WHERE $column = 'Y' or 'N'
     *
     * @param string $column column that needs to contain the value
     * @param bool   $value  boolean that is being turned into 'Y' or 'N'
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereEqualsBool(string $column, bool $value)
    {
        if ($value === true) {
            $condition = $column."='Y'";
        } else {
            $condition = $column."!='Y'";
        }
        $this->conditions[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column must be greater or equal to the value
     * WHERE $column >= $value
     *
     * @param string $column column that must be greater or equal
     * @param int    $value  value that must be smaller
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereGreaterEqual(string $column, int $value)
    {
        $condition          = $column.">=$value";
        $this->conditions[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column must be contained in a function.
     *
     * @param string $column column which values must be contained in the function
     * @param string $func   func the columns values must be a part of
     * @param bool   $isIn   default=true, if false the values are not allowed to be part of the function
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereInFunc(string $column, string $func, bool $isIn = true)
    {
        if ($isIn === true) {
            $condition = $column." IN ".$func;
        } else {
            $condition = $column." NOT IN ".$func;
        }
        $this->conditions[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column must be smaller or equal to the value
     * WHERE $column <= $value
     *
     * @param string $column column that must be smaller or equal
     * @param int    $value  value that must be greater
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereSmallerEqual(string $column, int $value)
    {
        $condition          = $column."<=$value";
        $this->conditions[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column IS NULL
     * WHERE $column IS NULL
     *
     * @param string $column column that IS NULL
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function andWhereIsNull(string $column)
    {
        $condition          = $column." IS NULL ";
        $this->conditions[] = $condition;
    }


    /**
     * Generate FROM part for the SQL
     *
     * @param string      $table table
     * @param string|null $alias alias for the table
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function from(string $table, ?string $alias = null)
    {
        if ($alias === null) {
            $this->from[] = $table;
        } else {
            $this->from[] = "$table AS $alias";
        }
    }


    /**
     * Insert
     *
     * @param string $table table in the database
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function insert(string $table)
    {
        $this->isInsert = true;
        $this->table    = $table;
    }


    /**
     * Add INSERT value-> column will be set to string value
     *
     * @param string $column name of the column which values will be set
     * @param string $value  string value, the column will be set to
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function insertValueStr(string $column, string $value)
    {
        $value = AbstractQueryBuilder::sanitizeInput($value);
        $this->insertKeys[]   = $column;
        $this->insertValues[] = "'".$value."'";
    }


    /**
     * Add INSERT value-> column will be set to integer value
     *
     * @param string $column name of the column which values will be set
     * @param int    $value  int value, the column will be set to
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function insertValueInt(string $column, int $value)
    {
        $this->insertKeys[]   = $column;
        $this->insertValues[] = $value;
    }


    /**
     * Add INSERT value-> column will be set to a function
     *
     * @param string $column name of the column
     * @param string $func   string function, the column will be set to
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function insertValueFunc(string $column, string $func)
    {
        $this->insertKeys[]   = $column;
        $this->insertValues[] = $func;
    }


    /**
     * Add INSERT value-> column will be set to boolean value.
     * Bool will be turned into 'Y' or 'N'.
     *
     * @param string $column name of the column which values will be set
     * @param bool   $value  boolean value, the column will be set to
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function insertValueBool(string $column, bool $value)
    {
        $this->insertKeys[] = $column;
        if ($value === true) {
            $this->insertValues[] = "'Y'";
        } else {
            $this->insertValues[] = "'N'";
        }
    }


    /**
     * Limit and offset
     *
     * @param int $limit  maximum amount of rows returned
     * @param int $offset amount of rows that will be skipped
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function limitOffset(int $limit, int $offset)
    {
        $this->limit  = $limit;
        $this->offset = $offset;
    }


    /**
     * Output function
     *
     * @param string ...$output value(s) that will be returned
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function output(string ...$output)
    {
        foreach ($output as $arg) {
            $this->outputFields[] = "INSERTED.".$arg;
        }
    }


    /**
     * Generates an array from the select
     *
     * @param string ...$select select string
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function select(string ...$select)
    {
        $this->isSelect = true;
        foreach ($select as $arg) {
            $this->fields[] = $arg;
        }
    }


    /**
     * Sort
     *
     * @param string ...$columnNameIsAscOrder column name and asc or desc for sorting order [NAME ASC]
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function sort(string ...$columnNameIsAscOrder)
    {
        foreach ($columnNameIsAscOrder as $arg) {
            $this->sortBy[] = $arg;
        }
    }


    /**
     * Update
     *
     * @param string $table table in the database
     *
     * @return     void
     * @deprecated Use AbstractQueryBuilder instead (QueryBuilderSelect/Insert/Update)
     */
    public function update(string $table)
    {
        $this->isUpdate = true;
        $this->table    = $table;
    }


}
