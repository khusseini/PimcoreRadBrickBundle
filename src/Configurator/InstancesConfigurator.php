<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgs;
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
            ['[editable][config][instances]']
        );
    }

    public function doCreateEditables(
        RenderArgs $renderArgs,
        array $data
    ): RenderArgs {
        $config = $data['editable']['config'];
        $instances = $config['instances'];
        $name = $data['editable']['name'];

        if ($instances == 1) {
            return $renderArgs;
        }

        if ($instances < 1) {
            $renderArgs->remove($name);
            return $renderArgs;
        }

        $editableArgs = $renderArgs->get($name);
        $renderData = [];

        for ($i = 0; $i < $instances; ++$i) {
            $renderData[$i] = $editableArgs;
        }

        $renderArgs->merge([$name => $renderData]);
        return $renderArgs;
    }

    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('instances', 1);
        $or->setAllowedTypes('instances', ['int', 'string']);
    }
}
