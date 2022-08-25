<?php

namespace App\Unauthenticated\Model\DatabaseConnector;

use InvalidArgumentException;

/**
 * Query builder for building a select query
 */
class QueryBuilderSelect extends AbstractQueryBuilder
{

        /**
         * Variable for the fields
         *
         * @var array
         */
    private array $fields = [];
    // end sort()

    /**
     * Variable for FROM
     *
     * @var array
     */
    private array $from = [];
    // end select()

    /**
     * Variable for the limit
     *
     * @var integer
     */
    private int $limit = 50;
    // end from()

    /**
     * Variable for the offset
     *
     * @var integer
     */
    private int $offset = 0;
    // end limitOffset()

    /**
     * Variable for the sortBy
     *
     * @var string[]
     */
    private array $sortBy = [];

    /**
     * Columns to group by
     *
     * @var string[]
     */
    private array $groupBy = [];


    /**
     * Generate FROM part for the SQL
     *
     * @param string      $table table
     * @param string|null $alias alias for the table
     *
     * @return void
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
     * Returns a statement and parameters, to be used as a prepared statement.
     *
     * @return       array string statement and array with parameters
     * @throws       InvalidArgumentException when querybuilder is missing values, or was being used incorrect.
     * @noinspection SqlWithoutWhere
     */
    public function getPreparedStatement(): array
    {
        if (count($this->fields) < 1) {
            throw new InvalidArgumentException("Select query must contain fields.");
        }
        $groupByStr = $this->groupBy === [] ? "" : " GROUP BY ".implode(", ", $this->groupBy);
        $fieldsStr  = implode(', ', $this->fields);
        $fromStr    = implode(', ', $this->from);

        if ($this->limit === 50 && $this->offset === 0) {
            list($whereStr, $prepValues) = $this->buildWhere();
            $orderStr        = $this->sortBy === [] ? "" : " ORDER BY ".implode(", ", $this->sortBy);
            $stringStatement = "SELECT $fieldsStr FROM $fromStr$whereStr$groupByStr$orderStr";
        } else {
            list($whereStr, $prepValues) = $this->buildNoPreparationWhere();
            if ($this->sortBy === []) {
                throw new InvalidArgumentException("Query must contain sort by");
            }
            $orderStr        = implode(", ", $this->sortBy);
            $offset          = ($this->offset + 1);
            $innerSelectStr  = "SELECT $fieldsStr, ROW_NUMBER() OVER (ORDER BY $orderStr) AS RowNum FROM $fromStr$whereStr$groupByStr";
            $stringStatement = ";WITH Results_CTE AS ($innerSelectStr) SELECT * FROM Results_CTE WHERE RowNum >= ($offset) AND RowNum < $offset + $this->limit";
        }
        return [
            $stringStatement,
            $prepValues,
        ];
    }


    /**
     * Add group by clause
     *
     * @param string ...$columnName
     *
     * @return void
     */
    public function groupBy(string ...$columnName)
    {
        foreach ($columnName as $arg) {
            $this->groupBy[] = $arg;
        }
    }


    /**
     * Limit and offset
     *
     * @param int $limit  maximum amount of rows returned
     * @param int $offset amount of rows that will be skipped
     *
     * @return void
     */
    public function limitOffset(int $limit = 50, int $offset = 0)
    {
        $this->limit  = $limit;
        $this->offset = $offset;
    }


    /**
     * Generates an array from the select
     *
     * @param string ...$select select string
     *
     * @return void
     */
    public function select(string ...$select)
    {
        foreach ($select as $arg) {
            $this->fields[] = $arg;
        }
    }


    /**
     * Sort
     *
     * @param string ...$columnNameIsAscOrder column name and asc or desc for sorting order [NAME ASC]
     *
     * @return void
     */
    public function sort(string ...$columnNameIsAscOrder)
    {
        foreach ($columnNameIsAscOrder as $arg) {
            $this->sortBy[] = $arg;
        }
    }


}
