<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\InstancesConfigurator;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class InstancesConfiguratorTest extends TestCase
{
    use ProphecyTrait;

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

    /**
     * @dataProvider canCreateEditableProvider
     */
    public function testCanCreateEditable($config, $assert)
    {
        $configurator = new InstancesConfigurator();
        foreach ($config['areabricks'] as $name => $areabrickConfig) {
            foreach ($areabrickConfig['editables'] as $editableName => $editableConfig) {
                $actualSupports = $configurator->supportsEditable($editableName, $editableConfig);
                $expectedSupports = isset($editableConfig['instances']);

                $this->assertEquals($expectedSupports, $actualSupports);

                $renderArgs = new RenderArgument('editable', $editableName, $editableConfig);
                $emitter = new RenderArgumentEmitter();
                $emitter->set($renderArgs);

                $context = $this->prophesize(ContextInterface::class);
                $context->toArray()->willReturn([]);
                $data = new ConfiguratorData($context->reveal());
                $data->setConfig($editableConfig);

                $configurator->createEditables($emitter, $editableName, $data);
                $assert($name, $emitter->emit());
            }
        }
    }
}
