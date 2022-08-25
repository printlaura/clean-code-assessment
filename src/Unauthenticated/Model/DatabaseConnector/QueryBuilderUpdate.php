<?php

namespace App\Unauthenticated\Model\DatabaseConnector;

use InvalidArgumentException;

/**
 * Query builder for building an update sql command
 */
class QueryBuilderUpdate extends AbstractQueryBuilder
{

    /**
     * Variable for the sets
     *
     * @var array
     */
    private array $sets = [];

    /**
     * Updaet sets that don't have a value to be used in the prepared statement.
     *
     * @var array
     */
    private array $setsNoValue = [];

    /**
     * Variable for the table
     *
     * @var string
     */
    private string $table;


    /**
     * Add SET-> column will be set to a function
     *
     * @param string $column   name of the column which values will be set by the function
     * @param string $function function that will be used to set the values. No sanitization will be applied to this argument.
     *
     * @return void
     */
    public function addSetFunc(string $column, string $function)
    {
        $set = "$column=$function";
        $this->setsNoValue[] = $set;
    }


    /**
     * Add SET-> column will be set to int value
     *
     * @param string $column name of the column which values will be set
     * @param int    $value  int value, the column will be set to
     *
     * @return void
     */
    public function addSetInt(string $column, int $value)
    {
        $set          = [
            $column,
            $value,
        ];
        $this->sets[] = $set;
    }


    /**
     * Add SET-> column will be set to string value
     *
     * @param string $column name of the column which values will be set
     * @param string $value  string value, the column will be set to
     *
     * @return void
     */
    public function addSetStr(string $column, string $value)
    {
        $value        = self::sanitizeInput($value);
        $this->sets[] = [
            $column,
            $value,
        ];
    }


    /**
     * Returns a statement and parameters, to be used as a prepared statement.
     *
     * @return       array string statement and array with parameters
     * @throws       InvalidArgumentException when querybuilder is missing values, or was being used incorrect.
     * @noinspection SqlWithoutWhere
     */
    public function getPreparedStatement(): array
    {

        list($whereStr, $prepValues) = $this->buildWhere();

        $setsCombined = [];
        $setValues    = [];
        foreach ($this->sets as $set) {
            $setsCombined[] = "$set[0]=?";
            $setValues[]    = $set[1];
        }
        $prepValues   = array_merge($setValues, $prepValues);
        $setsCombined = array_merge($setsCombined, $this->setsNoValue);

        if (count($setsCombined) < 1) {
            throw new InvalidArgumentException("Update query must contain sets.");
        }

        $setsStr         = implode(', ', $setsCombined);
        $stringStatement = "UPDATE $this->table SET $setsStr$whereStr";

        return [
            $stringStatement,
            $prepValues,
        ];
    }


    /**
     * Set
     *
     * @param string ...$set set
     *
     * @return void
     *
     * @deprecated 2022-01-11 in order to add parameter cleaning
     */
    public function set(string ...$set)
    {
        foreach ($set as $arg) {
            $this->sets[] = $arg;
        }
    }


    /**
     * Update
     *
     * @param string $table table in the database
     *
     * @return void
     */
    public function update(string $table)
    {
        $this->table = $table;
    }


}
