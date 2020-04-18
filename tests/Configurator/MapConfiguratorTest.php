<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\MapConfigurator;
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

        $mapConfig->createEditables(
            $emitter,
            'test',
            [
                'context' => ['source' => $source],
                'editable' => $editables['test']
            ]
        );

        $actual = iterator_to_array($emitter->emit());
        $this->assertCount(1, $actual);
        $actual = $actual['test'];

        $expected = 'hello world';

        $this->assertEquals($expected, $actual->getValue()['overwrite']);
    }
}
