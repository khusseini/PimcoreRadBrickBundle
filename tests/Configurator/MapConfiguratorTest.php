<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\MapConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MapConfiguratorTest extends TestCase
{
    public function testDoesNotSupport()
    {
        $instance = new MapConfigurator();
        $args = new RenderArgs();
        $or = new OptionsResolver();
        $instance->configureEditableOptions($or);
        $result = $instance->processConfig('no', $args, [
            'editable' => ['config' => [], 'name' => 'nop']
        ]);
        $this->assertSame($args, $result);
    }

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
        $renderArgs = new RenderArgs();
        $renderArgs->set([
            'test' => ['options' => ['overwrite' => 'me']]
        ]);

        $renderArgs = $mapConfig->processConfig(AbstractConfigurator::ACTION_CREATE_EDIT, $renderArgs, [
            'context' => ['source' => $source],
            'editable' => [
                'name' => 'test',
                'config' => $editables['test']
            ],
        ]);

        $expected = [
            'test' => [
                'options' => ['overwrite' => 'hello world']
            ]
        ];

        $this->assertEquals($expected, $renderArgs->getAll());
    }
}
