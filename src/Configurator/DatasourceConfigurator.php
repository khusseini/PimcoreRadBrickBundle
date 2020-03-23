<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use ArrayObject;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
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
                        $input[$name] = $this
                            ->getExpressionWrapper()
                            ->evaluateExpression($value, $context)
                        ;
                    }

                    return $dataCall($input);
                }
            );
        }

        return ['datasources' => $registry];
    }

    public function generateDatasources(Renderer $renderer, $data): \Generator
    {
        foreach ($data['context']['datasources']->executeAll() as $name => $value) {
            $argument = new RenderArgument('data', $name, $value);
            $renderer->set($argument);
            yield $argument->getName() => $argument;
        }
    }

    public function doCreateEditables(Renderer $renderer, string $name, array $data): \Generator
    {
        $argument = $renderer->get($name);
        if (!$data['context']['datasources']) {
            return;
        }

        yield from $this->generateDatasources($renderer, $data);

        $editable = $data['editable'];
        if (@$editable['datasource']['name']) {
            $datasourceName = $editable['datasource']['name'];
            $datasourceIdExpression = @$editable['datasource']['id'];
            $dataArgument = $renderer->get($datasourceName);

            unset($editable['datasource']);
            $items = new ArrayObject();

            foreach ($dataArgument->getValue() as $i => $item) {
                if ($datasourceIdExpression) {
                    $i = $this
                        ->getExpressionWrapper()
                        ->evaluateExpression($datasourceIdExpression, ['item'=>$item])
                    ;
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

        $renderer->set($argument);
        yield $name => $argument;
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
