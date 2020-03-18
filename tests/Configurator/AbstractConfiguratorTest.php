<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\Areabricks\AbstractAreabrick;
use Khusseini\PimcoreRadBrickBundle\Configurator\AbstractConfigurator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractConfiguratorTest extends TestCase
{
    protected function getInstance()
    {
        $configurator = new class() extends AbstractConfigurator {
            public function supports(string $action, string $editableName, array $config): bool
            {
                return true;
            }

            public function getExpressionAttributes(): array
            {
                return [
                    '[options][prop]'
                ];
            }

            public function doProcessConfig(string $action, OptionsResolver $or, array $data)
            {
                return $data['editable']['config']['options']['prop'];
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
        $or = new OptionsResolver();
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
            $actual = $c->processConfig('doesntmatter', $or, [
                'editable' => [
                    'config' => [
                        'options' => [
                            'prop' => 'some["context"]',
                        ],
                    ],
                ],
                'context' => $case['context'],
            ]);

            $this->assertEquals($case['expected'], $actual);
        }
    }
}
