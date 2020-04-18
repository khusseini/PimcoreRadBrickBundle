<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use ArrayObject;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatasourceConfigurator extends AbstractConfigurator
{
    public function preCreateEditables(string $brickName, \ArrayObject $data): array
    {
        $config = $this->resolveConfig($data['config']);
        $context = $data['context'];
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

            $registry->add(
                $id,
                function () use ($context, $dataCall, $datasourceConfig) {
                    $input = [];

                    foreach ($datasourceConfig['args'] as $name => $value) {
                        $input[$name] = $this->recurseExpression($value, $context);
                    }

                    return $dataCall($input);
                }
            );
        }

        return ['datasources' => $registry];
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

    public function generateDatasources(RenderArgumentEmitter $emitter, $data): void
    {
        foreach ($data['context']['datasources']->executeAll() as $name => $value) {
            $argument = new RenderArgument('data', $name, $value);
            $emitter->emitArgument($argument);
        }
    }

    public function doCreateEditables(RenderArgumentEmitter $emitter, string $name, array $data): void
    {
        $argument = $emitter->get($name);
        if (!$data['context']['datasources']) {
            $argument = new RenderArgument('null', $argument->getName());
            $emitter->emitArgument($argument);
            return;
        }

        $this->generateDatasources($emitter, $data);

        $editable = $data['editable'];
        if (isset($editable['datasource']['name'])) {
            $datasourceName = $editable['datasource']['name'];
            $datasourceIdExpression = @$editable['datasource']['id'];
            $dataArgument = $emitter->get($datasourceName);

            unset($editable['datasource']);
            $items = new ArrayObject();

            foreach ($dataArgument->getValue() as $i => $item) {
                if ($datasourceIdExpression) {
                    $i = $this
                        ->getExpressionWrapper()
                        ->evaluateExpression($datasourceIdExpression, ['item'=>$item]);
                }

                $itemArgument = new RenderArgument('editable', $i, $editable);
                $items[] = $itemArgument;
            }

            $argument = new RenderArgument(
                'collection',
                $argument->getName(),
                $items
            );
        }

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
        $or->setDefaults(['context' => [], 'datasources' => [], 'areabricks' => []]);
        $or->setDefined(array_keys($config));
        return $or->resolve($config);
    }
}
