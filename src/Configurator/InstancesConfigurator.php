<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Symfony\Component\OptionsResolver\OptionsResolver;

class InstancesConfigurator extends AbstractConfigurator
{
    public function supports(string $action, string $editableName, array $config): bool
    {
        return $action === 'create_editables' && isset($config['instances']);
    }

    public function processConfig(
        string $action,
        OptionsResolver $or,
        array $data
    ) {
        if ($action !== 'create_editables') {
            return $data['renderArgs'];
        }
        $config = $data['editable']['config'];
        $instances = $this->processValue($config['instances'], @$data['context'] ?: []);

        if ($instances == 1) {
            return $data['renderArgs'];
        }

        if ($instances < 1) {
            return [];
        }
        
        $name = $data['editable']['name'];
        $renderArgs = [];
        for ($i = 0; $i < $instances; ++$i) {
            $args = [];

            foreach ($or->getRequiredOptions() as $p) {
                $args[$p] = @$config[$p];
            }

            $renderArgs[$name.'_'.$i] = $args;
        }

        return $renderArgs;
    }

    public function configureEditableOptions(OptionsResolver $or): void
    {
        $or->setDefault('instances', 1);
        $or->setAllowedTypes('instances', ['string', 'int']);
    }
}
