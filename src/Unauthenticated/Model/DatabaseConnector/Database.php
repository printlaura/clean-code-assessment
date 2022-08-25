<?php

namespace App\Unauthenticated\Model\DatabaseConnector;

use Psr\Log\LoggerInterface;
use App\Unauthenticated\Controller\Settings\Settings;
use UnexpectedValueException;

/**
 * Class for the connection of the database
 */
class Database
{
    const VERSION = '2.0.0';

    private const MSSQL_SERVER        = 'sqlintern';
    private const MSSQL_DATABASE      = 'imago_pakete';
    private const MSSQL_TEST_DATABASE = 'testdb';
    private const MSSQL_USER          = 'websuche_st';
    private const MSSQL_PASSWORD      = '20mv893m83jfFmUf2';

    private const MSSQL_STOCK_SERVER   = 'sqlserver001';
    private const MSSQL_STOCK_DATABASE = 'imagostock';
    private const MSSQL_STOCK_USER     = 'websuche_st';
    private const MSSQL_STOCK_PASSWORD = '20mv893m83jfFmUf2';

    private const MSSQL_SPORT_SERVER   = 'sqlserver002';
    private const MSSQL_SPORT_DATABASE = 'imago';
    private const MSSQL_SPORT_USER     = 'websuche_st';
    private const MSSQL_SPORT_PASSWORD = '20mv893m83jfFmUf2';

    /**
     * Holds the DB connection
     *
     * @var resource
     */
    private $connection;

    /**
     * Variable to check if the transaction was successful
     *
     * @var boolean
     */
    private bool $isTransactionSuccessful = true;
    
    /**
     * Variable for the logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * Constructor for initializing the logger and the DB connection
     *
     * @param LoggerInterface $logger   Logger-Variable
     * @param string          $database name of the database, default=intern
     */
    public function __construct(LoggerInterface $logger, string $database = "intern")
    {
        $this->logger     = $logger;
        $this->connection = $this->openConnection($database);
    }


    /**
     * Function to close the DB connection
     *
     * @return bool true when transaction was successful
     */
    public function close(): bool
    {
        $this->__destruct();
        return $this->isTransactionSuccessful;
    }


    /**
     * Destructor to rollback for the transaction
     */
    public function __destruct()
    {
        if ($this->isTransactionSuccessful === false) {
            odbc_rollback($this->connection);
            $this->logger->warning("SQL Transaction was rolled back");
        }
    }


    /**
     * Executes the SQL statement INSERT/UPDATE
     *
     * @param string $sqlCommand SQL Instruction
     * @param bool   $hasOutput  Output-Option
     *
     * @return int Output of the assembled request
     *
     * @deprecated use executePreparedStatement instead
     */
    public function executeSQL(string $sqlCommand, bool $hasOutput = false): ?int
    {
        $this->logger->debug($sqlCommand);
        $isSuccess = odbc_exec($this->connection, $sqlCommand);
        if ($isSuccess === false) {
            $this->isTransactionSuccessful = false;
        }
        if ($hasOutput === true) {
            $idRow = odbc_fetch_array($isSuccess);
            return $idRow["id"];
        }
        return null;
    }


    /**
     * Executes a sql command.
     *
     * @param AbstractQueryBuilder $queryBuilder query that will be executed.
     * @param bool                 $hasOutput    set true when the query is expected to have an output
     *
     * @return int when $hashOutput is set, the output will be returned.
     */
    public function executePreparedStatement(AbstractQueryBuilder $queryBuilder, bool $hasOutput = false): ?int
    {
        list($sqlCommand, $values) = $queryBuilder->getPreparedStatement();
        $this->logger->debug("SQL Command: $sqlCommand Values: ".implode(", ", $values));
        $stmt      = odbc_prepare($this->connection, $sqlCommand);
        $isSuccess = odbc_execute($stmt, $values);
        if ($isSuccess === false) {
            $this->isTransactionSuccessful = false;
        }
        if ($hasOutput === true) {
            $idRow = odbc_fetch_array($stmt);
            return $idRow["id"];
        }
        return null;
    }


    /**
     * Queries a sql query. Return results as an array.
     *
     * @param AbstractQueryBuilder $queryBuilder query that will be executed.
     * @param int|null             $expectedRows amount of rows that the query is expected to return.
     *                                           When this is set, an exception will be thrown on a different row amount. Default is null.
     *
     * @return array Query results
     * @throws UnexpectedValueException when $expecedRows is set and a different amount of rows was returned.
     */
    public function queryPreparedStatement(AbstractQueryBuilder $queryBuilder, ?int $expectedRows = null): array
    {
        list($sqlCommand, $values) = $queryBuilder->getPreparedStatement();
        $this->logger->debug("SQL Command: $sqlCommand Values: ".implode(", ", $values));

        $results = odbc_prepare($this->connection, $sqlCommand);
        @odbc_execute($results, $values);

        $odbcError = odbc_error($this->connection);
        if (empty($odbcError) === false) {
            throw new UnexpectedValueException("ODBC Error: $odbcError ".odbc_errormsg($this->connection));
        }

        $objectsList = [];
        $obj         = odbc_fetch_object($results);
        while ($obj !== false) {
            $objectsList[] = $obj;
            $obj           = odbc_fetch_object($results);
        }
        if (isset($expectedRows) === true) {
            $rowCount = count($objectsList);
            if ($rowCount !== $expectedRows) {
                throw new UnexpectedValueException("Query returned $rowCount rows instead of the expected $expectedRows");
            }
        }
        return $objectsList;
    }


    /**
     * Executes the SQL statement SELECT
     *
     * @param string $sqlCommand   SQL-Instruction
     * @param ?int   $expectedRows Expected amount of rows that should be returned by query
     *
     * @throws UnexpectedValueException when unexpected amount of rows were returned by query
     * @return array Output of the assembled response from the DB
     *
     * @deprecated use queryPreparedStatement instead
     */
    public function querySQL(string $sqlCommand, ?int $expectedRows = null): array
    {
        $this->logger->debug($sqlCommand);
        $results     = odbc_exec($this->connection, $sqlCommand);
        $objectsList = [];
        while ($obj = odbc_fetch_object($results)) { // phpcs:ignore
            $objectsList[] = $obj;
        }
        if (isset($expectedRows) === true) {
            $rowCount = count($objectsList);
            if ($rowCount !== $expectedRows) {
                throw new UnexpectedValueException("Query returned $rowCount rows instead of the expected $expectedRows");
            }
        }
        return $objectsList;
    }


    /**
     * Function to establish the database connection to the MS-SQL server
     *
     * @param string $database database reference, default=intern
     *
     * @return resource DB-Connection
     */
    private function openConnection(string $database = "intern")
    {
        switch ($database) {
            case "intern":
                $conStr = "Driver={SQL Server Native Client 10.0};Server=".self::MSSQL_SERVER;
                if (Settings::IS_PRODUCTION === true) {
                    $conStr .= ";Database=".self::MSSQL_DATABASE.";";
                } else {
                    $conStr .= ";Database=".self::MSSQL_TEST_DATABASE.";";
                }
                $conUser = self::MSSQL_USER;
                $conPass = self::MSSQL_PASSWORD;
                break;
            case "st":
                $conStr  = "Driver={SQL Server Native Client 10.0};Server=".self::MSSQL_STOCK_SERVER;
                $conStr .= ";Database=".self::MSSQL_STOCK_DATABASE.";";
                $conUser = self::MSSQL_STOCK_USER;
                $conPass = self::MSSQL_STOCK_PASSWORD;
                break;
            case "sp":
                $conStr  = "Driver={SQL Server Native Client 10.0};Server=".self::MSSQL_SPORT_SERVER;
                $conStr .= ";Database=".self::MSSQL_SPORT_DATABASE.";";
                $conUser = self::MSSQL_SPORT_USER;
                $conPass = self::MSSQL_SPORT_PASSWORD;
                break;
            default:
                $conStr  = "Driver={SQL Server Native Client 10.0};Server=".self::MSSQL_SERVER;
                $conStr .= ";Database=".self::MSSQL_DATABASE.";";
                $conUser = self::MSSQL_USER;
                $conPass = self::MSSQL_PASSWORD;
        }

        $conRes = odbc_connect($conStr, $conUser, $conPass, SQL_CUR_USE_ODBC);
        if ($conRes === false) {
            $this->logger->critical(
                "Failed to connect to database. Shutting Down..."
            );
            $this->logger->info(var_export(odbc_error()));
            die();
        }
        return $conRes;
    }


    /**
     * Turns bool database value into php bool
     *
     * @param string $value saved in the database
     *
     * @return bool
     */
    public static function getBoolFromDbValue(string $value): bool
    {
        return ($value === 'Y');
    }


    /**
     * Turns php bool value into database value
     *
     * @param bool $value php bool
     *
     * @return string database value representation
     */
    public static function getDbValueFromBool(bool $value): string
    {
        if ($value === true) {
            return utf8_encode('Y');
        }
        return utf8_encode('N');
    }


}
