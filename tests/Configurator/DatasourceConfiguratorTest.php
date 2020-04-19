<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\DatasourceConfigurator;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class DatasourceConfiguratorTest extends TestCase
{
    public function testCanPreCreate()
    {
        $service = new class($this) {
            private $tester;
            public function __construct($tester)
            {
                $this->tester = $tester;
            }
            public function getData($first, $second): array
            {
                $this->tester->assertEquals('hello', $first);
                $this->tester->assertEquals('world', $second);
                return  [0, 1, 2, 3];
            }
        };

        $testBrick = [
            'datasources' => [
                'testsource' => [
                    'id' => 'main_source',
                    'args' => [
                        'first' => 'hello',
                        'second' => 'world',
                    ]
                ]
            ]
        ];

        $config = [
            'datasources' => [
                'main_source' => [
                    'service_id' => $service,
                    'method' => 'getData',
                    'args' => ['first', 'second'],
                ]
            ],
            'areabricks' => [
                'testbrick' => $testBrick
            ]
        ];

        $instance = new DatasourceConfigurator();
        $container = new \ArrayObject();
        $context = $this->prophesize(ContextInterface::class);
        $context->toArray()->willReturn([]);
        $context->setDatasources(Argument::any())
        ->will(function($args) use($container) {
            $container['datasources'] = $args[0];
        });

        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($config);
        $instance->preCreateEditables('testbrick', $data);
        $context = $data->getContext();

        self::assertTrue(isset($container['datasources']));
        self::assertInstanceOf(DatasourceRegistry::class, $container['datasources']);
    }

    public function testCanCreateEditable()
    {
        $registry = new DatasourceRegistry();

        $itemCount = 2;
        $items = [];
        $createItem = function ($id) {
            return (object)['id' => $id];
        };

        for ($i = 0; $i < $itemCount; ++$i) {
            $items[] = $createItem($i+1);
        }

        $registry->add(
            'test_source', function () use ($items) {
                return $items;
            }
        );

        $instance = new DatasourceConfigurator();
        $config = [
            'editable' => [
                'options' => [
                    'bla' => ''
                ],
                'datasource' => [
                    'name' => 'test_source',
                    'id' => 'item.id',
                ]
            ],
            'context' => [
                'datasources' => $registry
            ]
        ];

        $argument = new RenderArgument(
            'editable', 'test', [
            'options' => ['bla' => '']
            ]
        );

        $emitter = new RenderArgumentEmitter();
        $emitter->set($argument);


        $context = $this->prophesize(ContextInterface::class);
        $context->toArray()->willReturn($config['context']);
        $context->getDatasources()->willReturn($config['context']['datasources']);
        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($config['editable']);

        $instance->doCreateEditables($emitter, 'test', $data);
        $actual = iterator_to_array($emitter->emit());

        $this->assertCount(2, $actual);

        $types = [];
        $collectionContent = [];
        foreach ($actual as $actualArgument) {
            $types[] = $actualArgument->getType();
            if ($actualArgument->getType() === 'collection') {
                $collectionContent = $actualArgument->getValue();
            }
        }

        $expectedTypes = ['data', 'collection'];
        $this->assertSame($expectedTypes, $types);
        $this->assertCount($itemCount, $collectionContent);
    }


}
