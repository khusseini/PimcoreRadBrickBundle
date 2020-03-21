<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\InstancesConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use PHPUnit\Framework\TestCase;

class InstancesConfiguratorTest extends TestCase
{
    private function createNumberOfInstancesTestsData($instances)
    {
        $config = [
            'areabricks' => [
                'testbrick' => [
                    'editables' => [
                        'testeditable' => [
                            'instances' => $instances,
                        ]
                    ]
                ]
            ]
        ];

        return [
            $config,
            function ($areabrick, \Generator $renderArgs) use ($instances) {
                $actual = iterator_to_array($renderArgs);
                $this->assertCount(1, $actual);
                $actual = $actual['testeditable'];
                if ($instances < 1) {
                    $this->assertSame('null', $actual->getType());
                }
                if ($instances === 1) {
                    $this->assertSame('editable', $actual->getType());
                }
                if ($instances > 1) {
                    $this->assertSame('collection', $actual->getType());
                    $this->assertCount($instances, $actual->getValue());
                }
            }
        ];
    }

    public function canCreateEditableProvider()
    {
        return [
            $this->createNumberOfInstancesTestsData(0),
            $this->createNumberOfInstancesTestsData(1),
            $this->createNumberOfInstancesTestsData(2),
        ];
    }

    /** @dataProvider canCreateEditableProvider */
    public function testCanCreateEditable($config, $assert)
    {
        $configurator = new InstancesConfigurator();
        foreach ($config['areabricks'] as $name => $areabrickConfig) {
            foreach ($areabrickConfig['editables'] as $editableName => $editableConfig) {
                $actualSupports = $configurator->supportsEditable($editableName, $editableConfig);
                $expectedSupports =
                    isset($editableConfig['instances'])
                ;
                $this->assertEquals($expectedSupports, $actualSupports);
                $renderArgs = new RenderArgument('editable', $editableName, $editableConfig);

                $renderArgs = $configurator->createEditables(
                    $renderArgs,
                    $editableName,
                    ['context' => [], 'editable'=> $editableConfig]
                );

                $assert($name, $renderArgs);
            }
        }
    }
}
