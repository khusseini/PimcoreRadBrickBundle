<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatasourceConfigurator extends AbstractConfigurator
{
    public function preCreateEditables(string $brickName, array $brickConfig, array $config, array $context): array
    {
        $brickConfig = $this->resolveBrickconfig($brickConfig);
        $config = $this->resolveConfig($config);
        $registry = new DatasourceRegistry();
        foreach ($brickConfig['datasources'] as $id => $datasourceConfig) {
            $dsId = $datasourceConfig['id'];
            $dataSource = $config['datasources'][$dsId];
            $dataCall = $registry->createMethodCall(
                $dataSource['service_id'],
                $dataSource['method'],
                $dataSource['args']
            );

            $registry->add(
                $id,
                function () use ($context, $dataCall, $datasourceConfig) {
                    $input = [];

                    foreach ($datasourceConfig['args'] as $name => $value) {
                        $input[$name] = $this
                            ->getExpressionWrapper()
                            ->evaluateExpression($value, $context)
                        ;
                    }

                    return $dataCall($input);
                }
            );
        }

        $context['datasources'] = $registry;
        return $context;
    }

    public function doCreateEditables(RenderArgs $renderArgs, array $data): RenderArgs
    {
        if (!$this->supportsEditable($data['editable']['name'], $data['editable']['config'])) {
            return $renderArgs;
        }

        $datasources = $data['context']['datasources'];
        $editable = $data['editable'];
        if (!$datasources instanceof DatasourceRegistry) {
            return $renderArgs;
        }

        $datasource = $editable['config']['datasource'];

        $dsData = $datasources->execute($datasource['name']);
        $id = $datasource['id'];
        $config = $editable['config'];
        unset($config['datasource']);

        $items = [];
        foreach ($dsData as $i => $item) {
            if ($id) {
                $i = $this->getExpressionWrapper()->evaluateExpression($id, ['item'=>$item]);
            }
            $items[$i] = $config;
        }

        $renderArgs->merge([$editable['name'] => $items]);
        return $renderArgs;
    }

    public function supportsEditable(string $editableName, array $config): bool
    {
        return isset($config['datasource']);
    }

    public function configureEditableOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault(
            'datasource',
            function (OptionsResolver $or) {
                $or->setRequired('name');
                $or->setAllowedTypes('name', ['string']);
                $or->setDefault('id', null);
            }
        );
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    protected function resolveBrickconfig(array $config): array
    {
        $or = new OptionsResolver();
        $or->setDefaults(['datasources' => [], 'editables' => []]);
        return $or->resolve($config);
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    protected function resolveConfig(array $config): array
    {
        $or = new OptionsResolver();
        $or->setDefaults(['datasources' => [], 'areabricks' => []]);
        return $or->resolve($config);
    }
}
