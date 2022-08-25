<?php

namespace App\Unauthenticated\Model\DatabaseConnector;

use App\Unauthenticated\Model\CustomDateTimeModel;
use InvalidArgumentException;

/**
 * Query builder for building an insert sql command
 */
class QueryBuilderInsert extends AbstractQueryBuilder
{

    /**
     * Inserts a function, no parameter cleaning neccesarry.
     *
     * @var array
     */
    private array $insertFunc = [];

    /**
     * Variable for the insertKeys
     *
     * @var array
     */
    private array $insertKeys = [];

    /**
     * Insert keys that don't have a value to be used in the prepared statement.
     *
     * @var array
     */
    private array $insertKeysNoValue = [];

    /**
     * Variable for the insertValues
     *
     * @var array
     */
    private array $insertValues = [];

    /**
     * Variable for the outputFields
     *
     * @var array
     */
    private array $outputFields = [];

    /**
     * Variable for the table
     *
     * @var string
     */
    private string $table;


    /**
     * Returns a statement and parameters, to be used as a prepared statement.
     *
     * @return       array string statement and array with parameters
     * @throws       InvalidArgumentException when querybuilder is missing values, or was being used incorrect.
     * @noinspection SqlWithoutWhere
     */
    public function getPreparedStatement(): array
    {
        $prepValues = [];
        $outputStr  = $this->outputFields === [] ? "" : " OUTPUT ".implode(", ", $this->outputFields);
        $values     = [];
        for ($i = 0; $i < count($this->insertKeys); $i++) {
            $values[] = "?";
        }
        $prepValues = array_merge($prepValues, $this->insertValues);

        $insertKeysMerged   = array_merge($this->insertKeys, $this->insertKeysNoValue);
        $insertValuesMerged = array_merge($values, $this->insertFunc);

        if (count($insertKeysMerged) < 1) {
            throw new InvalidArgumentException("Insert query must contain insert values.");
        }

        $keysStr   = implode(', ', $insertKeysMerged);
        $valuesStr = implode(', ', $insertValuesMerged);

        $stringStatement = "INSERT INTO $this->table ($keysStr)$outputStr VALUES($valuesStr)";


        return [
            $stringStatement,
            $prepValues,
        ];
    }


    /**
     * Insert
     *
     * @param string $table table in the database
     *
     * @return void
     */
    public function insert(string $table)
    {
        $this->table = $table;
    }


    /**
     * Add INSERT value-> column will be set to boolean value.
     * Bool will be turned into 'Y' or 'N'.
     *
     * @param string $column name of the column which values will be set
     * @param bool   $value  boolean value, the column will be set to
     *
     * @return void
     */
    public function insertValueBool(string $column, bool $value)
    {
        $this->insertKeys[] = $column;
        if ($value === true) {
            $this->insertValues[] = "Y";
        } else {
            $this->insertValues[] = "N";
        }
    }


    /**
     * Add insert value for a  datetime colmun
     *
     * @param string              $column   name of the column which values will be set
     * @param CustomDateTimeModel $dateTime
     *
     * @return void
     */
    public function insertValueDateTime(string $column, CustomDateTimeModel $dateTime)
    {
        $this->insertValueFunc($column, $dateTime->toDatabase());
    }


    /**
     * Add INSERT value-> column will be set to a function
     *
     * @param string $column name of the column
     * @param string $func   string function, the column will be set to
     *
     * @return void
     */
    public function insertValueFunc(string $column, string $func)
    {
        $this->insertKeysNoValue[] = $column;
        $this->insertFunc[]        = $func;
    }


    /**
     * Add INSERT value-> column will be set to integer value
     *
     * @param string $column name of the column which values will be set
     * @param int    $value  int value, the column will be set to
     *
     * @return void
     */
    public function insertValueInt(string $column, int $value)
    {
        $this->insertKeys[]   = $column;
        $this->insertValues[] = $value;
    }


    /**
     * Add INSERT value-> column will be set to string value
     *
     * @param string $column name of the column which values will be set
     * @param string $value  string value, the column will be set to
     *
     * @return void
     */
    public function insertValueStr(string $column, string $value)
    {
        $value = self::sanitizeInput($value);
        $this->insertKeys[]   = $column;
        $this->insertValues[] = $value;
    }


    /**
     * Insert values
     *
     * @param array ...$keyValuePair key value pair [string key, string value]
     *
     * @return void
     *
     * @deprecated 2022-01-14 replaced with insertValueStr,Int,Func,Bool
     */
    public function insertValues(array ...$keyValuePair)
    {
        foreach ($keyValuePair as $arg) {
            $this->insertKeys[]   = $arg[0];
            $this->insertValues[] = $arg[1];
        }
    }


    /**
     * Output function
     *
     * @param string ...$output value(s) that will be returned
     *
     * @return void
     */
    public function output(string ...$output)
    {
        foreach ($output as $arg) {
            $this->outputFields[] = "INSERTED.".$arg;
        }
    }


}
