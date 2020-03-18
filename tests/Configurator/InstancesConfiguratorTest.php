<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\InstancesConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancesConfiguratorTest extends TestCase
{
    private function getStaticValueData($instances, $action = 'create_editables')
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

        $expected = [];

        if ($action !== 'create_editables' || $instances === 1) {
            $expected['testeditable'] = [];
        } elseif ($action === 'create_editables') {
            for ($i = 0; $i < $instances; ++$i) {
                $expected['testeditable_'.$i] = [];
            }
        }

        return [
            $action,
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
            $this->getStaticValueData(2, 'not_supported'),
        ];
    }

    public function canIntegrateProvider()
    {
        $count = 2;
        $expected = [
            'testedit' => [
                'type' => 'input',
                'options' => [],
            ]
        ];

        for ($i = 0; $i < $count; ++$i) {
            $expected['test_'.$i] = [
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
        $this->assertSame($expected, iterator_to_array($renderArgs));
    }

    /** @dataProvider canProcessConfigProvider */
    public function testCanProcessConfig($action, $config, $assert)
    {
        $configurator = new InstancesConfigurator();
        $or = new OptionsResolver();
        foreach ($config['areabricks'] as $name => $areabrickConfig) {
            foreach ($areabrickConfig['editables'] as $editableName => $editableConfig) {
                $actualSupports = $configurator->supports($action, $editableName, $editableConfig);
                $expectedSupports = isset($editableConfig['instances']) && $action === 'create_editables';
                $this->assertEquals($expectedSupports, $actualSupports);
                $renderArgs = new RenderArgs();
                $renderArgs->set([
                    $editableName => []
                ]);

                $renderArgs = $configurator->processConfig(
                    $action,
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
