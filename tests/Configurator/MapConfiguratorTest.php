<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\MapConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
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

        $renderer = new Renderer();
        $renderer->set($arguments);

        $actual = $mapConfig->createEditables(
            $renderer,
            'test',
            [
                'context' => ['source' => $source],
                'editable' => $editables['test']
            ]
        );

        $actual = iterator_to_array($actual);
        $this->assertCount(1, $actual);
        $actual = $actual['test'];

        $expected = 'hello world';

        $this->assertEquals($expected, $actual->getValue()['overwrite']);
    }
}
