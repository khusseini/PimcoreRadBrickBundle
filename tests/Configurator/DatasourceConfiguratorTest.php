<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\DatasourceConfigurator;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use PHPUnit\Framework\TestCase;

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
        $context = $instance->preCreateEditables('testbrick', $testBrick, $config, []);
        $context['datasources']->execute('testsource');
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

        $registry->add('test_source', function ($input) use ($items) {
            return $items;
        });

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

        $argument = new RenderArgument('editable', 'test', [
            'options' => ['bla' => '']
        ]);

        $actualGenerator = $instance->doCreateEditables($argument, 'test', $config);
        $actual = iterator_to_array($actualGenerator);

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
