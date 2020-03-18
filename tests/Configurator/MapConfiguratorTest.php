<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\MapConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use PHPUnit\Framework\TestCase;

class MapConfiguratorTest extends TestCase
{
    public function testCanMap()
    {
        $this->markTestSkipped();
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
        $renderArgs = new RenderArgs();
        $mapConfig->processConfig('create_editable', $renderArgs, $data);

        $this->assertTrue(true);
    }
}
