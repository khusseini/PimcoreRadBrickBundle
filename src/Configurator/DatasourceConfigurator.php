<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use ArrayObject;
use Khusseini\PimcoreRadBrickBundle\ContextInterface;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use PHP_CodeSniffer\Config;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatasourceConfigurator extends AbstractConfigurator
{
    public function preCreateEditables(string $brickName, ConfiguratorData $data): void
    {
        $config = $this->resolveConfig($data->getConfig());
        $context = $data->getContext();
        $contextArray = $context->toArray();

        $brickConfig = $this->resolveBrickconfig($config['areabricks'][$brickName]);
        $registry = new DatasourceRegistry();
        foreach ($brickConfig['datasources'] as $id => $datasourceConfig) {
            $dsId = $datasourceConfig['id'];
            $dataSource = $config['datasources'][$dsId];
            $serviceId = $dataSource['service_id'];
            $serviceObject = null;

            if (is_string($serviceId)) {
                $serviceObject = $this
                    ->getExpressionWrapper()
                    ->evaluateExpression($serviceId, $contextArray)
                ;
            }

            if (!is_object($serviceObject)) {
                throw new \InvalidArgumentException(sprintf('Service with id "%s" is not an object. %s given.', $serviceId, gettype($serviceObject)));
            }

            $dataCall = $registry->createMethodCall(
                $serviceObject,
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

    /**
     * @param array<string, array<string, mixed>> $config
     *
     * @return mixed
     */
    protected function callDatasource(ContextInterface $context, callable $dataCall, array $config)
    {
        $input = [];
        if (
                isset($config['conditions'])
                && is_array($config['conditions'])
                && !$this->evaluateConditions($config['conditions'], $context)
        ) {
            return [];
        }

        foreach ($config['args'] as $name => $value) {
            $input[$name] = $this->recurseExpression($value, $context->toArray());
        }

        return $dataCall($input);
    }

    /**
     * @param string[] $conditions
     */
    protected function evaluateConditions(array $conditions, ContextInterface $context): bool
    {
        $result = false;
        $contextArray = $context->toArray();
        foreach ($conditions as $condition) {
            $value = $this->getExpressionWrapper()->evaluateExpression($condition, $contextArray);
            $result = ($value !== $condition) && (bool) $value;
            if (!$result) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * @param mixed                $value
     * @param array<string, mixed> $context
     *
     * @return mixed
     */
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
        if (!$datasources) {
            return;
        }

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

        $editable = $data->getConfig();
        if (
            !isset($editable['datasource'])
            || !isset($editable['datasource']['name'])
        ) {
            return;
        }

        $this->generateDatasources($emitter, $data);

        $datasourceName = $editable['datasource']['name'];
        $datasourceIdExpression = @$editable['datasource']['id'];

        if (!$emitter->has($datasourceName)) {
            return;
        }

        $dataArgument = $emitter->get($datasourceName);

        unset($editable['datasource']);
        $items = new ArrayObject();

        foreach ($dataArgument->getValue() as $i => $item) {
            //@codeCoverageIgnoreStart
            if ($datasourceIdExpression) {
                $i = $this
                        ->getExpressionWrapper()
                        ->evaluateExpression($datasourceIdExpression, ['item' => $item]);
            }
            //@codeCoverageIgnoreEnd

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
        return isset($config['datasource']) && isset($config['datasource']['id']);
    }

    public function configureEditableOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefault('datasource', []);
    }

    public function postCreateEditables(string $brickName, ConfiguratorData $data, RenderArgumentEmitter $emitter): void
    {
        $this->generateDatasources($emitter, $data);
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
