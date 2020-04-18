<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
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
                    '[editable][options][prop]'
                ];
            }

            public function doCreateEditables(Renderer $renderer, string $name, array $data): void
            {
                $renderer->emitArgument($renderer->get($name));
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

    public function testCanEvaluateExpressions()
    {
        $c = $this->getInstance();
        $argument = new RenderArgument(
            'editable', 'testedit', [
            'options' => ['prop' => 'some["context"]'],
            ]
        );
        $cases = [
            [
                'context' => ['some' => ['context' => 'says hello']],
                'expected' => 'says hello',
            ], [
                'context' => ['nop' => ['context' => 'says hello']],
                'expected' => 'some["context"]',
            ],
        ];

        $renderer = new Renderer();
        $renderer->set($argument);

        foreach ($cases as $case) {
            $c->createEditables(
                $renderer, 'testedit', [
                'editable' => $argument->getValue(),
                'context' => $case['context'],
                ]
            );

            $actual = $renderer->emit();
            $actual = iterator_to_array($actual);

            $this->assertCount(1, $actual);
            $this->assertArrayHasKey('testedit', $actual);
            $actual = $actual['testedit'];
            $this->assertEquals('testedit', $actual->getName());
            $this->assertEquals($case['expected'], $actual->getValue()['options']['prop']);
        }
    }
}
