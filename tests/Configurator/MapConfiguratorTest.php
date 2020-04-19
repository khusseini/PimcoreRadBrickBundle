<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\MapConfigurator;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use PHPUnit\Framework\TestCase;

class MapConfiguratorTest extends TestCase
{
    public function testCanMap()
    {
        $source = (object)[
            'data' => 'hello world',
        ];

        $editables = [
            'test' => [
                'options' => ['overwrite' => 'me'],
                'map' => [
                    [
                        'source' => 'source.data',
                        'target' => '[options][overwrite]'
                    ]
                ]
            ]
        ];

        $mapConfig = new MapConfigurator();
        $arguments = new RenderArgument(
            'editable',
            'test',
            ['overwrite' => 'me']
        );

        $emitter = new RenderArgumentEmitter();
        $emitter->set($arguments);

        $context = $this->prophesize(ContextInterface::class);
        $context
            ->toArray()
            ->willReturn(['source' => $source])
        ;

        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($editables['test']);
        $mapConfig->createEditables($emitter, 'test', $data);

        $actual = iterator_to_array($emitter->emit());
        $this->assertCount(1, $actual);
        $actual = $actual['test'];

        $expected = 'hello world';
        $this->assertEquals($expected, $actual->getValue()['overwrite']);
    }
}
