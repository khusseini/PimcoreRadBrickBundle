<?php


namespace Tests\Khusseini\PimcoreRadBrickBundle;

use InvalidArgumentException;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use PHPUnit\Framework\TestCase;

class DatasourceRegistryTest extends TestCase
{
    protected function getInstance()
    {
        $instance = new DatasourceRegistry();
        return $instance;
    }

    public function testExecuteByName()
    {
        $datasource = function(...$args) {
            return join(' ', $args);
        };

        $instance = $this->getInstance();
        $instance->add('dummy', $datasource);
        $data = $instance->execute('dummy', ['hello', 'world']);
        self::assertEquals('hello world', $data);
    }

    public function testExecuteByNameFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $data = $this->getInstance()->execute('datasource', ['hello', 'world']);
        self::assertEquals(['hello world'], $data);
    }

    public function testExecuteAll()
    {
        $datasource = function(...$args) {
            return join(' ', $args);
        };

        $dsWrapper = function() use ($datasource) {
            return $datasource('hello', 'world');
        };

        $instance = $this->getInstance();
        $instance->add('dummy', $dsWrapper);
        $data = $instance->executeAll();
        $data = iterator_to_array($data);

        self::assertArrayHasKey('dummy', $data);
        self::assertSame('hello world', $data['dummy']);
    }

    public function canCreateMethodCallProvider()
    {
        $stdObject = new \stdClass();
        return [
            [    // Simple case
                ['arg1', 'arg2'],
                ['arg1' => 'hello', 'arg2' => 'world'],
                ['hello', 'world']
            ], [ // Nested case
                [['arg' => [
                    'nested' => [
                        'arg1'
                    ]
                ]]],
                ['arg1' => 'hello'],
                [['arg' => [
                    'nested' => [
                        'hello'
                    ]
                ]]],
            ], [ // Nested case with mixed content
                [['arg' => [
                    'hello' => $stdObject,
                    'world' => 'arg1',
                ]]],
                ['arg1' => 'hello'],
                [['arg' => [
                    'hello' => $stdObject,
                    'world' => 'hello',
                ]]],
            ], [
                // Skip Expression
                ['invalid.expression', 'arg2'],
                ['arg1' => 'hello', 'arg2' => 'world'],
                ['invalid.expression', 'world'],
            ],
        ];
    }

    /**
     * @dataProvider canCreateMethodCallProvider
     */
    public function testCanCreateMethodCall($argumentsConfig, $arguments, $expectedData)
    {
        $dummyService = new class() {
            public function dummyMethod(...$args): array
            {
                return $args;
            }
        };

        $instance = $this->getInstance();

        $methodCall = $instance->createMethodCall(
            $dummyService,
            'dummyMethod',
            $argumentsConfig
        );

        $data = $methodCall($arguments);

        self::assertEquals($expectedData, $data);
    }
}
