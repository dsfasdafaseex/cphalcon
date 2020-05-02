<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Test\Database\Mvc\Model;

use DatabaseTester;
use PDO;
use Phalcon\Storage\Exception;
use Phalcon\Test\Fixtures\Migrations\InvoicesMigration;
use Phalcon\Test\Fixtures\Traits\DiTrait;
use Phalcon\Test\Fixtures\Traits\RecordsTrait;
use Phalcon\Test\Models\Customers;
use Phalcon\Test\Models\CustomersKeepSnapshots;
use Phalcon\Test\Models\InvoicesKeepSnapshots;

use function uniqid;

/**
 * Class QueryCest
 */
class QueryCest
{
    use DiTrait;
    use RecordsTrait;

    public function _before(DatabaseTester $I): void
    {
        try {
            $this->setNewFactoryDefault();
        } catch (Exception $e) {
            $I->fail($e->getMessage());
        }

        $this->setDatabase($I);

        (new InvoicesMigration($I->getConnection()));
    }

    /**
     * Tests Phalcon\Mvc\Model :: query()
     *
     * @param DatabaseTester $I
     *
     * @author Phalcon Team <team@phalcon.io>
     * @since  2018-11-13
     *
     * @group  mysql
     * @group  pgsql
     * @group  sqlite
     */
    public function mvcModelQuery(DatabaseTester $I)
    {
        $I->wantToTest('Mvc\Model - query()');
        $this->addTestData($I);

        $query = Customers::query();
        $query->limit(20, 0);
        $resultsets = $query->execute();

        $I->assertCount(20, $resultsets->toArray());
        foreach ($resultsets as $resultset) {
            $I->assertInstanceOf(Customers::class, $resultset);
        }
    }

    /**
     * Tests Phalcon\Mvc\Model :: query() - Issue 14783
     *
     * @param DatabaseTester $I
     *
     * @author Phalcon Team <team@phalcon.io>
     * @since  2018-11-13
     * @issue  14783
     *
     * @group  mysql
     * @group  pgsql
     * @group  sqlite
     */
    public function mvcModelQueryIssue14783(DatabaseTester $I)
    {
        $I->wantToTest('Mvc\Model - query()');

        $this->addTestData($I);

        $query = CustomersKeepSnapshots::query();
        $query->columns(
            [
                CustomersKeepSnapshots::class . '.*',
                'join_1.*',
            ]
        );
        $query->leftJoin(
            InvoicesKeepSnapshots::class,
            'join_1.inv_cst_id = ' . CustomersKeepSnapshots::class . '.cst_id',
            'join_1'
        );
        $query->limit(20, 0);
        $resultsets = $query->execute();

        $I->assertCount(20, $resultsets->toArray());
        foreach ($resultsets as $resultset) {
            $model = $this->transform($resultset);
            $I->assertInstanceOf(CustomersKeepSnapshots::class, $model);
            $I->assertInstanceOf(InvoicesKeepSnapshots::class, $model->invoices);
        }
    }

    /**
     * Transforming method used for test
     *
     * @param $resultset
     *
     * @issue 14783
     *
     * @return mixed
     */
    private function transform($resultset)
    {
        $invoice           = $resultset->readAttribute(lcfirst(InvoicesKeepSnapshots::class));
        $customer          = $resultset->readAttribute('join_1');
        $invoice->customer = $customer;

        return $invoice;
    }

    /**
     * Seed Invoices' table by some data.
     *
     * @param DatabaseTester $I
     * @return void
     */
    private function addTestData(DatabaseTester $I): void
    {
        $connection = $I->getConnection();
        $migration  = new InvoicesMigration($connection);

        for ($counter = 1; $counter <= 50; $counter++) {
            if (!$migration->insert($counter, 1, 1, uniqid('inv-', true))) {
                $I->fail(
                    sprintf(
                        "Failed to insert row #%d into table '%s' using '%s' driver",
                        $counter,
                        $migration->getTable(),
                        $migration->getDriverName()
                    )
                );
            }
        }
    }
}
