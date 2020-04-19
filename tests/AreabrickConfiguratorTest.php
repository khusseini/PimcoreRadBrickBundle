<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\IConfigurator;
use Khusseini\PimcoreRadBrickBundle\Context;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use PHPUnit\Framework\TestCase;
use Pimcore\Templating\Model\ViewModel;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfiguratorTest extends TestCase
{
    use ProphecyTrait;

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

        $context = $this->prophesize(ContextInterface::class);
        $configurator = $this->createConfigurator($config);
        $editables = $configurator->compileAreaBrick('test_brick', $context->reveal());

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
        $context = $this->prophesize(ContextInterface::class);
        foreach ($areabricks as $areabrick => $aconfig) {
            $editables = $configurator->createEditables($areabrick, $context->reveal());
            $tests($areabrick, $editables);
        }
    }

    public function testCanPreCreateEditables()
    {
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
        $dummy = $this->createDummyWithPreCreate();

        $configurator = $this->createConfigurator($config);
        $configurator->setConfigurators([$dummy]);

        $areabrick = 'testbrick';
        $request = $this->prophesize(Request::class);
        $context = new Context(new ViewModel(), $request->reveal());
        $editables = $configurator->compileAreaBrick($areabrick, $context);
        $editables = iterator_to_array($editables);

        self::assertArrayHasKey('testeditable', $editables);
        $testEditable = $editables['testeditable'];
        $value = $testEditable->getValue();

        self::assertEquals('tampered', $value['type']);
    }

    private function createDummyWithPreCreate(): AbstractConfigurator
    {
        $dummy = new class() extends AbstractConfigurator {
            public function configureEditableOptions(OptionsResolver $or): void
            {
                return ;
            }

            public function supportsEditable(string $editableName, array $config): bool
            {
                return true;
            }

            public function preCreateEditables(string $brickName, ConfiguratorData $data): void
            {
                $config = $data->getConfig();
                $brick = $config['areabricks'][$brickName];
                $editable = $brick['editables']['testeditable'];
                $editable['type'] = 'tampered';

                $brick['editables']['testeditable'] = $editable;
                $config['areabricks'][$brickName] = $brick;

                $data->setConfig($config);
            }

            public function getEditablesExpressionAttributes(): array
            {
                return ['[editable][options][placeholder]'];
            }

            public function doCreateEditables(
                RenderArgumentEmitter $emitter,
                string $name,
                ConfiguratorData $data
            ): void {
                $argument = $emitter->get($name);
                $emitter->emitArgument($argument);
            }
        };

        return $dummy;
    }

    public function getIConfiguratorIntegrationData($supports = true)
    {
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

        $assert = function ($areabrick, $editables) use ($expected) {
            foreach ($editables as $name => $args) {
                $this->assertArrayHasKey($name, $expected);
                $expectedConfig = $expected[$name];
                $this->assertSame($expectedConfig, $args->getValue());
            }
        };

        return [$config, $assert, []];
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
                RenderArgumentEmitter $emitter,
                string $name,
                ConfiguratorData $data
            ): void {
                $argument = $emitter->get($name);
                $emitter->emitArgument($argument);
            }
        };

        $configurator = $this->createConfigurator($config);
        $configurator->setConfigurators([$dummy]);
        $view = new ViewModel([]);
        $request = $this->prophesize(Request::class);
        $context = new Context($view, $request->reveal());

        $renderArguments = $configurator->createEditables('testbrick', $context);

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
