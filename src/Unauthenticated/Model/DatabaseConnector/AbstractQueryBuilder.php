<?php

namespace App\Unauthenticated\Model\DatabaseConnector;

use InvalidArgumentException;

/**
 * Query Builder
 */
abstract class AbstractQueryBuilder
{

    /**
     * Variable for the conditions
     *
     * @var array
     */
    protected array $conditions = [];

    /**
     * Where conditions that don't have a value to be used in the prepared statement.
     *
     * @var array
     */
    protected array $conditionsNoValue = [];


    /**
     * Used to prevent SQL injection attacks
     *
     * @param string $input field input text
     *
     * @return string
     */
    public static function sanitizeInput(string $input): string
    {
        return utf8_encode(str_replace("'", "", $input));
    }


    /**
     * Returns a statement and parameters, to be used as a prepared statement.
     *
     * @return       array string statement and array with parameters
     * @throws       InvalidArgumentException when querybuilder is missing values, or was being used incorrect.
     * @noinspection SqlWithoutWhere
     */
    abstract public function getPreparedStatement(): array;


    /**
     * Add a WHERE clause, where the column must contain a function
     * WHERE $column = $func
     *
     * @param string $column  column that needs to contain the function
     * @param string $func    func the column must contain
     * @param bool   $isEqual true by default, when set to false the where is set to not equal
     *
     * @return void
     */
    public function andWhereEqualsFunc(string $column, string $func, bool $isEqual = true)
    {
        if ($isEqual === true) {
            $condition = $column."=$func";
        } else {
            $condition = $column."!=$func";
        }
        $this->conditionsNoValue[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column must contain an int value
     * WHERE $column = '$value'
     *
     * @param string $column  column that needs to contain the value
     * @param int    $value   value the column must contain
     * @param bool   $isEqual true by default, when set to false the where is set to not equal
     *
     * @return void
     */
    public function andWhereEqualsInt(string $column, int $value, bool $isEqual = true)
    {
        if ($isEqual === true) {
            $condition = $column."=";
        } else {
            $condition = $column."!=";
        }
        $this->conditions[] = [
            $condition,
            $value,
        ];
    }


    /**
     * Add a WHERE clause, where the column must contain a string value
     * WHERE $column = '$value'
     *
     * @param string $column  column that needs to contain the value
     * @param string $value   value the column must contain
     * @param bool   $isEqual true by default, when set to false the where is set to not equal
     *
     * @return void
     */
    public function andWhereEqualsStr(string $column, string $value, bool $isEqual = true)
    {
        $value = self::sanitizeInput($value);
        if ($isEqual === true) {
            $condition = $column."=";
        } else {
            $condition = $column."!=";
        }
        $this->conditions[] = [
            $condition,
            $value,
        ];
    }


    /**
     * Add a WHERE clause, where the column is boolean
     * WHERE $column = 'Y' or 'N'
     *
     * @param string $column column that needs to contain the value
     * @param bool   $value  boolean that is being turned into 'Y' or 'N'
     *
     * @return void
     */
    public function andWhereEqualsBool(string $column, bool $value)
    {
        if ($value === true) {
            $condition = $column."=";
        } else {
            $condition = $column."!=";
        }
        $this->conditions[] = [
            $condition,
            "Y",
        ];
    }


    /**
     * Add a WHERE clause, where the column must be greater or equal to the value
     * WHERE $column >= $value
     *
     * @param string $column column that must be greater or equal
     * @param int    $value  value that must be smaller
     *
     * @return void
     */
    public function andWhereGreaterEqual(string $column, int $value)
    {
        $this->conditions[] = [
            $column.">=",
            $value,
        ];
    }


    /**
     * Add a WHERE clause, where the column must be contained in a function.
     *
     * @param string $column column which values must be contained in the function
     * @param string $func   func the columns values must be a part of
     * @param bool   $isIn   default=true, if false the values are not allowed to be part of the function
     *
     * @return void
     */
    public function andWhereInFunc(string $column, string $func, bool $isIn = true)
    {
        if ($isIn === true) {
            $condition = $column." IN ".$func;
        } else {
            $condition = $column." NOT IN ".$func;
        }
        $this->conditionsNoValue[] = $condition;
    }


    /**
     * Add a WHERE clause, where the column must be smaller or equal to the value
     * WHERE $column <= $value
     *
     * @param string $column column that must be smaller or equal
     * @param int    $value  value that must be greater
     *
     * @return void
     */
    public function andWhereSmallerEqual(string $column, int $value)
    {
        $this->conditions[] = [
            $column."<=",
            $value,
        ];
    }


    /**
     * Add a WHERE clause, where the column IS NULL
     * WHERE $column IS NULL
     *
     * @param string $column column that IS NULL
     *
     * @return void
     */
    public function andWhereIsNull(string $column)
    {
        $condition = $column." IS NULL";
        $this->conditionsNoValue[] = $condition;
    }


     /**
      * Add a WHERE clause, where the column IS NOT NULL
      * WHERE $column IS NOT NULL
      *
      * @param string $column column that IS NOT NULL
      *
      * @return void
      */
    public function andWhereIsNotNull(string $column)
    {
        $condition = $column." IS NOT NULL";
        $this->conditionsNoValue[] = $condition;
    }


    /**
     * Generate the conditions from the where part
     *
     * @param string ...$where the where part
     *
     * @return void
     *
     * @deprecated 2022-01-11 in order to add parameter cleaning
     */
    public function where(string ...$where)
    {
        foreach ($where as $arg) {
            $this->conditions[] = $arg;
        }
    }


    /**
     * Add a WHERE clause, where the value in the column must be in the $stack
     *
     * @param string   $column name of the column that must contain any value in the $stack
     * @param String[] $stack
     *
     * @return void
     */
    public function andWhereIsInStringArray(string $column, array $stack)
    {
        $stack     = self::sanitizeArrayInput($stack);
        $imploded  = "'".implode("', '", $stack)."'";
        $condition = $column." IN ($imploded)";
        $this->conditionsNoValue[] = $condition;
    }


    /**
     * Add a WHERE clause, where the value in the column must be in the $stack
     *
     * @param string $column name of the column that must contain any value in the $stack
     * @param array  $stack
     *
     * @return void
     */
    public function andWhereIsInIntArray(string $column, array $stack)
    {

        $imploded  = implode(", ", $stack);
        $condition = $column." IN ($imploded)";
        $this->conditionsNoValue[] = $condition;
    }


    /**
     * Build where part of the statement
     *
     * @return array where string, values to use in prepared statement
     */
    public function buildWhere(): array
    {
        $prepValues        = [];
        $conditionsPrepare = [];
        foreach ($this->conditions as $condition) {
            $conditionsPrepare[] = "$condition[0]?";
            $prepValues[]        = $condition[1];
        }
        $conditionsPrepare = array_merge($conditionsPrepare, $this->conditionsNoValue);
        $whereStr          = $conditionsPrepare === [] ? "" : " WHERE ".implode(" AND ", $conditionsPrepare);
        return [
            $whereStr,
            $prepValues,
        ];
    }


    /**
     * Build where part of the statement, whiout any values to prepare
     *
     * @return array
     */
    public function buildNoPreparationWhere(): array
    {
        $conditionsWithValues = [];

        foreach ($this->conditions as $condition) {
            $columnName = $condition[0];
            $value      = $condition[1];

            if (is_string($condition[1]) === true) {
                $conditionsWithValues[] = "$columnName'$value'";
            } else {
                $conditionsWithValues[] = "$columnName$value";
            }
        }
        $conditionsWithValues = array_merge($conditionsWithValues, $this->conditionsNoValue);
        $whereStr = $conditionsWithValues === [] ? "" : " WHERE ".implode(" AND ", $conditionsWithValues);
        return [
            $whereStr,
            [],
        ];
    }


    /**
     * Executes sanatizeInput on every item
     *
     * @param String[] $items will be sanatized
     *
     * @return String[] sanatized items
     */
    private static function sanitizeArrayInput(array $items): array
    {
        return array_map("self::sanitizeInput", $items);
    }


}
