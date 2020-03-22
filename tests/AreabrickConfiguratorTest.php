<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Tests;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\IConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
use PHPUnit\Framework\TestCase;
use Pimcore\Templating\Model\ViewModel;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfiguratorTest extends TestCase
{
    public function testCanCompileAreabrick()
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
        $context = [
            'view' => $view,
            'request' => [],
        ];
        $configurator = $this->createConfigurator($config);
        $editables = $configurator->compileAreaBrick('test_brick', $context);

        foreach ($editables as $name => $editable) {
            $this->assertSame($expected[$name], $editable->getValue());
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
                    $this->assertSame($expectedConfig, $args->getValue());
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
            ->supportsEditable(Argument::Any(), Argument::cetera())
            ->willReturn($supports)
        ;
        $configuratorInterface
            ->configureEditableOptions(Argument::any())
        ;
        $emptyGenerator = function () {
            return;
            yield;
        };

        $configuratorInterface
            ->postCreateEditables(Argument::any(), Argument::cetera())
            ->willReturn($emptyGenerator())
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

        if ($supports) {
            $expected['testeditable'] = $expected[$name];
        }

        $config = [
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

        $generatedArgument = new RenderArgument('editable', $name, [
            'type' => 'input',
            'options' => [],
        ]);

        $generator = function () use ($name, $generatedArgument) {
            yield $name => $generatedArgument;
        };

        $configuratorInterface
            ->createEditables(Argument::any(), Argument::cetera())
            ->willReturn($generator())
        ;

        $assert = function ($areabrick, $editables) use ($expected) {
            foreach ($editables as $name => $args) {
                $this->assertArrayHasKey($name, $expected);
                $expectedConfig = $expected[$name];
                $this->assertSame($expectedConfig, $args->getValue());
            }
        };
        $ci = $configuratorInterface->reveal();
        return [$config, $assert, [$ci]];
    }

    public function testDeferredProcessing()
    {
        $editables = [
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
        ];

        $config = [
            'areabricks' => [
                'testbrick' => [
                    'editables' => $editables
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
                return ['[editable][options][placeholder]'];
            }

            public function doCreateEditables(
                Renderer $renderer,
                string $name,
                array $data
            ): \Generator {
                $argument = $renderer->get($name);
                yield $name => $argument;
            }
        };

        $configurator = $this->createConfigurator($config);
        $configurator->setConfigurators([$dummy]);
        $view = new ViewModel([]);

        $renderArguments = $configurator->createEditables('testbrick', ['view' => $view]);

        $actual = [];
        $argumentCalled = 0;
        foreach ($renderArguments as $name => $argument) {
            $view[$name] = $argument->getValue();
            $this->assertArrayHasKey($name, $editables);

            if ($name === 'testeditable') {
                ++$argumentCalled;
                if ($argumentCalled == 2) {
                    $actual = $argument->getValue()['options'];
                    $this->assertEquals('hello world', $actual['placeholder']);
                }
            }
        }
    }

    private function createConfigurator(array $config)
    {
        return new AreabrickConfigurator(
            $config
        );
    }
}
