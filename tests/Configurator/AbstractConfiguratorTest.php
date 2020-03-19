<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractConfiguratorTest extends TestCase
{
    protected function getInstance()
    {
        $configurator = new class() extends AbstractConfigurator {
            public function supportsEditable(string $editableName, array $config): bool
            {
                return true;
            }

            public function getEditablesExpressionAttributes(): array
            {
                return [
                    '[editable][config][options][prop]'
                ];
            }

            public function doCreateEditables(RenderArgs $renderArgs, array $data): RenderArgs
            {
                return $renderArgs->merge([
                    $data['editable']['name'] => $data['editable']['config']
                ]);
            }

            public function configureEditableOptions(OptionsResolver $or): void
            {
                $or->setDefault(
                    'options',
                    function (OptionsResolver $resolver) {
                        $resolver->setRequired('prop');
                    }
                );
                $or->setAllowedTypes('options', ['array']);
            }
        };

        return $configurator;
    }

    public function testProcessValue()
    {
        $c = $this->getInstance();
        $renderArgs = new RenderArgs();
        $cases = [
            [
                'context' => ['some' => ['context' => 'says hello']],
                'expected' => 'says hello',
            ], [
                'context' => ['nop' => ['context' => 'says hello']],
                'expected' => 'some["context"]',
            ],
        ];

        foreach ($cases as $case) {
            $actual = $c->createEditables($renderArgs, [
                'editable' => [
                    'config' => [
                        'options' => [
                            'prop' => 'some["context"]',
                        ],
                    ],
                    'name' => 'testedit'
                ],
                'context' => $case['context'],
            ]);
            $this->assertInstanceOf(RenderArgs::class, $actual);
            $actualData = $renderArgs->getAll();
            $this->assertEquals($case['expected'], $actualData['testedit']['options']['prop']);
        }
    }
}
