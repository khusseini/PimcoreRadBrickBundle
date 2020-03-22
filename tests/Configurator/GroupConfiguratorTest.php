<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\GroupConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
use PHPUnit\Framework\TestCase;

class GroupConfiguratorTest extends TestCase
{
    public function testCanModifyConfiguration()
    {
        $configurator = new GroupConfigurator();
        $data = new \ArrayObject();
        $data['config'] = ['areabricks' => [
            'test' => [
                'groups' => [
                    'boxes' => [
                        'prop' => 'value',
                    ],
                ],
                'editables' => [
                    'test' => [
                        'group' => 'boxes'
                    ],
                ],
            ],
        ]];

        $configurator->preCreateEditables('test', $data);
        $brick = $data['config']['areabricks']['test'];
        $editable = $brick['editables']['test'];
        $this->assertArrayHasKey('prop', $editable);
        $argument = new RenderArgument('editable', 'test', []);
        $renderer = new Renderer();
        $renderer->set($argument);
        $renderArguments = $configurator->doCreateEditables($renderer, 'test', $editable);
        $renderArguments = iterator_to_array($renderArguments);
        $renderArguments = $configurator->postCreateEditables('test', $brick, $renderer);
        $renderArguments = iterator_to_array($renderArguments);
        $this->assertArrayHasKey('boxes', $renderArguments);
        $boxes = $renderArguments['boxes'];
        $this->assertEquals('collection', $boxes->getType());
        $this->assertCount(1, $boxes->getValue());
        $boxes = $boxes->getValue();
        $box = $boxes[0];
        $boxValue = $box->getValue();
        $this->assertEquals('collection', $box->getType());
        $this->assertCount(1, $boxValue);
        $this->assertArrayHasKey('test', $boxValue);
    }
}
