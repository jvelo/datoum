<?php

namespace Jvelo\Datoum;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;


class BlueprintWithArbitraryColumns extends Blueprint
{

    public function addColumn($type, $name, array $parameters = [])
    {
        return parent::addColumn($type, $name, $parameters);
    }
}

class DataTest extends \PHPUnit_Framework_TestCase
{

    private $logger;

    private $schema;

    function __construct()
    {
        $this->logger = new Logger('DataTest_logger');
        $this->logger->pushHandler(new ErrorLogHandler());
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'datoum',
            'username' => 'jerome',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public'
        ]);

        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }


    protected function setUp()
    {
        parent::setUp();

        $this->schema = Capsule::schema();

        $this->schema->dropIfExists('documents');

        $this->schema->blueprintResolver(function ($table, $callback) {
            return new BlueprintWithArbitraryColumns($table, $callback);
        });

        $this->schema->create('documents', function (BlueprintWithArbitraryColumns $table) {
            $table->addColumn('uuid', 'id');
            $table->addColumn('jsonb', 'data');
            $table->addColumn('type', 'data');
            $table->timestamps();
        });

        Capsule::statement('CREATE INDEX data_gin ON documents USING GIN (data jsonb_path_ops);');

        $this->logger->info("Documents table created ...");
    }


    protected function tearDown()
    {
        //$this->schema->dropIfExists('documents');
    }


    public function testCreateNewEmptyData()
    {
        $this->logger->info("testCreateNewEmptyData ...");

        $data = new Data;
        $data->save();

        $this->assertEquals(1, Data::count());

        $fetched = Data::first();

        $this->assertEquals($fetched->data, '{}');

        $data = new Data;
        $data->save();

        $this->assertEquals(2, Data::count());
    }

    public function testCreateNewDataWithContents()
    {
        $this->logger->info("testCreateNewDataWithContents ...");

        $data = new Data;
        $data->save();
        $data->setJsonAttribute('data', 'foo', 'bar');
        $data->save();

        $this->assertEquals(1, Data::count());

        $fetched = Data::first();

        $this->assertEquals($fetched->foo, 'bar');
    }
}