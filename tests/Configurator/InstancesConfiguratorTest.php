<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\InstancesConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancesConfiguratorTest extends TestCase
{
    private function getStaticValueData($instances)
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

        $expected = ['testeditable' => []];
        if ($instances > 1) {
            for ($i = 0; $i < $instances; ++$i) {
                $expected['testeditable'][$i] = [];
            }
        }
        if ($instances === 0) {
            $expected = [];
        }

        return [
            $config,
            function ($areabrick, $renderArgs) use ($config, $expected) {
                $this->assertArrayHasKey($areabrick, $config['areabricks']);
                $this->assertSame($expected, $renderArgs->getAll());
            }
        ];
    }

    public function canProcessConfigProvider()
    {
        return [
            $this->getStaticValueData(0),
            $this->getStaticValueData(1),
            $this->getStaticValueData(2),
        ];
    }

    public function canIntegrateProvider()
    {
        $count = 2;
        $expected = [
            'testedit' => [
                'type' => 'input',
                'options' => [],
            ],
            'test' => [],
        ];

        for ($i = 0; $i < $count; ++$i) {
            $expected['test'][$i] = [
                'type' => 'input',
                'options' => [],
            ];
        }

        $editables  = [
            'testedit' => [
                'type' => 'input',
            ],
            'test' => [
                'type' => 'input',
                'instances' => $count,
            ],
        ];

        return [
            ['testbrick', $expected, $editables],
        ];
    }

    /**
     * @dataProvider canIntegrateProvider
     */
    public function testCanIntegrate($brickName, $expected, $editables)
    {
        $config = [
            'areabricks' => [
                $brickName => ['editables' => $editables],
            ],
        ];
        $ic = new InstancesConfigurator();
        $areabrickConf = new AreabrickConfigurator($config, [$ic]);
        $renderArgs = $areabrickConf->createEditables($brickName);
        $actual = iterator_to_array($renderArgs);

        $this->assertSame($expected, $actual);
    }

    /** @dataProvider canProcessConfigProvider */
    public function testCanProcessConfig($config, $assert)
    {
        $configurator = new InstancesConfigurator();
        foreach ($config['areabricks'] as $name => $areabrickConfig) {
            foreach ($areabrickConfig['editables'] as $editableName => $editableConfig) {
                $actualSupports = $configurator->supportsEditable($editableName, $editableConfig);
                $expectedSupports =
                    isset($editableConfig['instances'])
                ;
                $this->assertEquals($expectedSupports, $actualSupports);
                $renderArgs = new RenderArgs();
                $renderArgs->set([
                    $editableName => []
                ]);

                $renderArgs = $configurator->createEditables(
                    $renderArgs,
                    [
                        'editable' => [
                            'name' => $editableName,
                            'config' => $editableConfig
                        ],
                    ]
                );

                $assert($name, $renderArgs);
            }
        }
    }
}
