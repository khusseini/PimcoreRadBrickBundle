<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\DatasourceConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\GroupConfigurator;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class GroupConfiguratorTest extends TestCase
{
    use ProphecyTrait;

    public function testCanModifyConfiguration()
    {
        $configurator = new GroupConfigurator();
        $config = ['areabricks' => [
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

        $context = $this->prophesize(ContextInterface::class);
        $data = new ConfiguratorData($context->reveal());
        $data->setConfig($config);
        $configurator->preCreateEditables('test', $data);
        $config = $data->getConfig();
        $brick = $config['areabricks']['test'];
        $editable = $brick['editables']['test'];
        $this->assertArrayHasKey('prop', $editable);
        $argument = new RenderArgument('editable', 'test', []);
        $emitter = new RenderArgumentEmitter();
        $emitter->set($argument);
        $data->setConfig($editable);
        $configurator->doCreateEditables($emitter, 'test', $data);
        $renderArguments = iterator_to_array($emitter->emit());
        $configurator->postCreateEditables('test', $brick, $emitter);
        $renderArguments = iterator_to_array($emitter->emit());
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
