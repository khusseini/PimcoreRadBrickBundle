<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancesConfigurator extends AbstractConfigurator
{
    public function supports(string $action, string $editableName, array $config): bool
    {
        return $action === 'create_editables' && isset($config['instances']);
    }

    public function getExpressionAttributes(): array
    {
        return array_merge(
            parent::getExpressionAttributes(),
            ['[instances]']
        );
    }

    public function doProcessConfig(
        string $action,
        RenderArgs $renderArgs,
        array $data
    ): RenderArgs {
        if ($action !== 'create_editables') {
            return $renderArgs;
        }

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
            $renderData[$name.'_'.$i] = $editableArgs;
        }

        $renderArgs->merge($renderData);
        $renderArgs->remove($name);

        return $renderArgs;
    }

    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('instances', 1);
        $or->setAllowedTypes('instances', ['string', 'int']);
    }
}
