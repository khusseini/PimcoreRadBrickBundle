<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use ArrayObject;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
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

    public function doCreateEditables(RenderArgument $argument, string $name, array $data): \Generator
    {
        if ($this->supportsEditable($name, $data['editable'])) {
            $datasourcesRegistry = $data['context']['datasources'];
            $editable = $data['editable'];
            $datasourceConfig = $editable['datasource'];
            $datasourceName = $datasourceConfig['name'];
            $datasourceIdExpression = @$datasourceConfig['id'];
            $dsData = $datasourcesRegistry->execute($datasourceName);
            yield $datasourceName => new RenderArgument('data', $datasourceName, $dsData);
            $config = $editable;
            unset($config['datasource']);

            $items = new ArrayObject();
            foreach ($dsData as $i => $item) {
                if ($datasourceIdExpression) {
                    $i = $this->getExpressionWrapper()->evaluateExpression($datasourceIdExpression, ['item'=>$item]);
                }

                $itemArgument = new RenderArgument('editable', $i, $config);
                $items[] = $itemArgument;
            }

            $argument = new RenderArgument(
                'collection',
                $argument->getName(),
                $items
            );
        }

        yield $name => $argument;
    }

    public function supportsEditable(string $editableName, array $config): bool
    {
        return isset($config['datasource']) && count($config['datasource']);
    }

    public function configureEditableOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('datasource', []);
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
        $or->setDefined(array_keys($config));
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
        $or->setDefined(array_keys($config));
        return $or->resolve($config);
    }
}
