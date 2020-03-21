<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use ArrayObject;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancesConfigurator extends AbstractConfigurator
{
    public function supportsEditable(string $editableName, array $config): bool
    {
        return isset($config['instances']);
    }

    public function getEditablesExpressionAttributes(): array
    {
        return array_merge(
            parent::getEditablesExpressionAttributes(),
            ['[editable][instances]']
        );
    }

    public function doCreateEditables(
        RenderArgument $argument,
        string $name,
        array $data
    ): \Generator {
        $config = $data['editable'];
        $instances = $config['instances'];

        if ($instances < 1) {
            $argument = new RenderArgument('null', $name);
        }

        if ($instances > 1) {
            $editables = new ArrayObject();
            for ($i = 0; $i < $instances; ++$i) {
                $editables[] = new RenderArgument(
                    $argument->getType(),
                    (string) $i,
                    $argument->getValue()
                );
            }

            $argument = new RenderArgument('collection', $name, $editables);
        }

        yield $name => $argument;
    }

    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('instances', 1);
        $or->setAllowedTypes('instances', ['int', 'string']);
    }
}
