<?php

namespace Tests\Api\Model\DatabaseConnector;

use App\Unauthenticated\Model\DatabaseConnector\AbstractQueryBuilder;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderInsert;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderUpdate;
use PHPUnit\Framework\TestCase;

/**
 * AbstractQueryBuilderTest
 */
class AbstractQueryBuilderTest extends TestCase
{

    private AbstractQueryBuilder $queryInsert;

    private AbstractQueryBuilder $querySelect;

    private AbstractQueryBuilder $queryUpdate;

    private AbstractQueryBuilder $queryWhere;


    /**
     * __construct
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     *
     * @return void
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $query = new QueryBuilderSelect();
        $query->select("name", "id");
        $query->from("web_lb_folder");
        $this->querySelect = $query;

        $query = new QueryBuilderSelect();
        $query->select("name", "id");
        $query->from("web_lb_folder");
        $query->andWhereEqualsInt("a", 100);
        $query->andWhereEqualsStr("b", "test");
        $query->andWhereEqualsBool("c", true);
        $query->andWhereEqualsFunc("d", "TEST_FUNC()");
        $query->andWhereInFunc("e", "(SELECT d FROM web_lb_folder_user)");
        $query->andWhereIsNull("f");
        $query->andWhereGreaterEqual("g", 10);
        $query->andWhereSmallerEqual("h", 10);
        $this->queryWhere = $query;

        $query = new QueryBuilderUpdate();
        $query->update("web_lb_folder");
        $query->addSetStr("a", "test");
        $query->addSetInt("b", 100);
        $query->addSetFunc("c", "TEST_FUNC()");
        $this->queryUpdate = $query;

        $query = new QueryBuilderInsert();
        $query->insert("web_lb_folder");
        $query->insertValueBool("a", true);
        $query->insertValueInt("b", 100);
        $query->insertValueStr("c", "test");
        $query->insertValueFunc("d", "TEST_FUNC()");
        $this->queryInsert = $query;
    }

    
    /**
     * TestSelectToPreparedStmt
     *
     * @return void
     */
    public function testSelectToPreparedStmt(): void
    {
        list($stmt, $values) = $this->querySelect->getPreparedStatement();
        $this->assertEquals("SELECT name, id FROM web_lb_folder", $stmt);
        $this->assertEquals([], $values);
    }


    /**
     * TestWhereToPreparedStmt
     *
     * @return void
     */
    public function testWhereToPreparedStmt(): void
    {
        list($stmt, $values) = $this->queryWhere->getPreparedStatement();
        $this->assertEquals(
            "SELECT name, id FROM web_lb_folder WHERE a=? AND b=? AND c=? AND g>=? AND h<=? AND d=TEST_FUNC() AND e IN (SELECT d FROM web_lb_folder_user) AND f IS NULL",
            $stmt
        );
        $this->assertEquals([100, 'test', 'Y', 10, 10], $values);
    }

    
    /**
     * TestUpdateToPreparedStmt
     *
     * @noinspection SqlWithoutWhere
     * @return       void
     */
    public function testUpdateToPreparedStmt(): void
    {
        list($stmt, $values) = $this->queryUpdate->getPreparedStatement();
        $this->assertEquals("UPDATE web_lb_folder SET a=?, b=?, c=TEST_FUNC()", $stmt);
        $this->assertEquals(['test', 100], $values);
    }

    
    /**
     * TestInsertToPreparedStmt
     *
     * @return void
     */
    public function testInsertToPreparedStmt(): void
    {
        list($stmt, $values) = $this->queryInsert->getPreparedStatement();
        $this->assertEquals("INSERT INTO web_lb_folder (a, b, c, d) VALUES(?, ?, ?, TEST_FUNC())", $stmt);
        $this->assertEquals(['Y', 100, 'test'], $values);
    }


}
