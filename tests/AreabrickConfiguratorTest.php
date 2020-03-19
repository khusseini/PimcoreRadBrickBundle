<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Tests;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\IConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use PHPUnit\Framework\TestCase;
use Pimcore\Templating\Model\ViewModel;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfiguratorTest extends TestCase
{
    public function testCanCompileAreaBrick()
    {
        $config = [
            'areabricks' => [
                'test_brick' => [
                    'editables' => [
                        'test_edit' => [
                            'type' => 'input'
                        ]
                    ],
                ]
            ]
        ];

        $expected = [
            'test_edit' => [
                'type' => 'input',
                'options' => [],
            ]
        ];

        $view = new ViewModel();
        $configurator = $this->createConfigurator($config);
        $editables = $configurator->compileAreaBrick('test_brick', [
            'view' => $view,
            'request' => [],
        ]);

        foreach ($editables as $name => $editable) {
            $this->assertSame($expected[$name], $editable);
        }
    }

    protected function getSimpleBrickTestData(): array
    {
        $expected = [
            'wysiwyg_content' => [
                'type' => 'wysiwyg',
                'options' => ['random' => 'option']
            ]
        ];

        return [
            ['areabricks' => [
                'wysiwyg' => [
                    'editables' => $expected
                ]
            ]],
            function ($areabrick, $editables) use ($expected) {
                $this->assertEquals('wysiwyg', $areabrick);
                foreach ($editables as $name => $args) {
                    $this->assertArrayHasKey($name, $expected);
                    $expectedConfig = $expected[$name];
                    $this->assertSame($expectedConfig, $args);
                }
            },
        ];
    }

    public function canCreateEditablesProvider()
    {
        return [
            $this->getSimpleBrickTestData(),
            $this->getIConfiguratorIntegrationData(),
            $this->getIConfiguratorIntegrationData(false),
        ];
    }

    /**
     * @dataProvider canCreateEditablesProvider
     */
    public function testCanCreateEditables($config, $tests, $configurators = [])
    {
        $configurator = $this->createConfigurator($config);
        $configurator->setConfigurators($configurators);
        $areabricks = $config['areabricks'];
        foreach ($areabricks as $areabrick => $aconfig) {
            $editables = $configurator->createEditables($areabrick);
            $tests($areabrick, $editables);
        }
    }

    private function createIConfig($supports = true)
    {
        $configuratorInterface = $this->prophesize(IConfigurator::class);
        $configuratorInterface
            ->supportsEditable(Argument::cetera())
            ->willReturn($supports)
        ;
        $configuratorInterface
            ->configureEditableOptions(Argument::any())
        ;
        return $configuratorInterface;
    }

    public function getIConfiguratorIntegrationData($supports = true)
    {
        $configuratorInterface = $this->createIConfig($supports);
        $name = 'testeditable'. (!$supports ? '': '_tampered');
        $expected = [
            $name => [
                'type' => 'input',
                'options' => [],
            ]
        ];
        $config =[
            'areabricks' => [
                'testbrick' => [
                    'editables' => [
                        'testeditable' => [
                            'type' => 'input',
                            'options' => [],
                        ]
                    ]
                ]
            ]
        ];

        $expectedRenderArgs = new RenderArgs();
        $expectedRenderArgs->set($expected);

        $configuratorInterface
            ->createEditables(Argument::any(), Argument::cetera())
            ->willReturn($expectedRenderArgs)
        ;

        $assert = function ($areabrick, $editables) use ($expected) {
            foreach ($editables as $name => $args) {
                $this->assertArrayHasKey($name, $expected);
                $expectedConfig = $expected[$name];
                $this->assertSame($expectedConfig, $args);
            }
        };
        $ci = $configuratorInterface->reveal();
        return [$config, $assert, [$ci]];
    }

    public function testDeferredProcessing()
    {
        $config = [
            'areabricks' => [
                'testbrick' => [
                    'editables' => [
                        'dummy' => [
                            'type' => 'input',
                            'options' => [
                                'content' => 'hello world'
                            ],
                        ],
                        'testeditable' => [
                            'type' => 'input',
                            'options' => [
                                'placeholder' => 'view.get("dummy")["options"]["content"]'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $dummy = new class() extends AbstractConfigurator {
            public function configureEditableOptions(OptionsResolver $or): void
            {
                return ;
            }

            public function supportsEditable(string $editableName, array $config): bool
            {
                return true;
            }

            public function getEditablesExpressionAttributes(): array
            {
                return ['[editable][config][options][placeholder]'];
            }

            public function doCreateEditables(
                RenderArgs $renderArgs,
                array $data
            ): RenderArgs {
                $renderArgs->merge([
                    $data['editable']['name'] => $data['editable']['config'],
                ]);

                return $renderArgs;
            }
        };

        $configurator = $this->createConfigurator($config);
        $configurator->setConfigurators([$dummy]);
        $view = new ViewModel([]);

        $editables = $configurator->createEditables('testbrick', ['view' => $view]);
        $es = [];

        foreach ($editables as $name => $editable) {
            $view[$name] = $editable;
            $es[$name] = $editable;
        }

        $this->assertEquals($es['testeditable']['options']['placeholder'], 'hello world');
    }

    private function createConfigurator(array $config)
    {
        return new AreabrickConfigurator(
            $config
        );
    }
}
