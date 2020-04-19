<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use ArrayObject;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatasourceConfigurator extends AbstractConfigurator
{
    public function preCreateEditables(string $brickName, ConfiguratorData $data): void
    {
        $config = $this->resolveConfig($data->getConfig());
        $context = $data->getContext();

        $brickConfig = $this->resolveBrickconfig($config['areabricks'][$brickName]);
        $registry = new DatasourceRegistry();
        foreach ($brickConfig['datasources'] as $id => $datasourceConfig) {
            $dsId = $datasourceConfig['id'];
            $dataSource = $config['datasources'][$dsId];
            $dataCall = $registry->createMethodCall(
                $dataSource['service_id'],
                $dataSource['method'],
                $dataSource['args']
            );

            $wrappedCall = function () use ($context, $dataCall, $datasourceConfig) {
                return $this->callDatasource($context, $dataCall, $datasourceConfig);
            };

            $registry->add($id, $wrappedCall);
        }

        $data->getContext()->setDatasources($registry);
    }

    protected function callDatasource(ContextInterface $context, callable $dataCall, array $config)
    {
        $input = [];
        foreach ($config['args'] as $name => $value) {
            $input[$name] = $this->recurseExpression($value, $context->toArray());
        }

        return $dataCall($input);
    }

    protected function recurseExpression($value, array $context)
    {
        if (is_string($value)) {
            return $this
                ->getExpressionWrapper()
                ->evaluateExpression($value, $context);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->recurseExpression($item, $context);
            }
        }

        return $value;
    }

    public function generateDatasources(RenderArgumentEmitter $emitter, ConfiguratorData $data): void
    {
        $datasources = $data->getContext()->getDatasources();
        $data = $datasources->executeAll();
        foreach ($data as $name => $value) {
            $argument = new RenderArgument('data', $name, $value);
            $emitter->emitArgument($argument);
        }
    }

    public function doCreateEditables(RenderArgumentEmitter $emitter, string $name, ConfiguratorData $data): void
    {
        if (!$data->getContext()->getDatasources()) {
            return;
        }

        $this->generateDatasources($emitter, $data);

        $editable = $data->getConfig();
        if (
            !isset($editable['datasource'])
            || !isset($editable['datasource']['name'])
        ) {
            return;
        }

        $datasourceName = $editable['datasource']['name'];
        $datasourceIdExpression = @$editable['datasource']['id'];
        $dataArgument = $emitter->get($datasourceName);

        unset($editable['datasource']);
        $items = new ArrayObject();

        foreach ($dataArgument->getValue() as $i => $item) {
            if ($datasourceIdExpression) {
                $i = $this
                        ->getExpressionWrapper()
                        ->evaluateExpression($datasourceIdExpression, ['item' => $item]);
            }

            $itemArgument = new RenderArgument('editable', $i, $editable);
            $items[] = $itemArgument;
        }

        $argument = new RenderArgument(
            'collection',
            $name,
            $items
        );

        $emitter->emitArgument($argument);
    }

    public function supportsEditable(string $editableName, array $config): bool
    {
        return true;
    }

    public function configureEditableOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('datasource', []);
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     */
    protected function resolveConfig(array $config): array
    {
        $or = new OptionsResolver();
        $or->setDefaults(['datasources' => [], 'areabricks' => []]);
        $or->setDefined(array_keys($config));

        return $or->resolve($config);
    }
}
