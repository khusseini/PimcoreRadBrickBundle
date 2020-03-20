<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Khusseini\PimcoreRadBrickBundle\Configurator\IConfigurator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfigurator
{
    /** @var array<mixed>  */
    private $config = [];

    /** @var IConfigurator[] */
    private $configurators = [];

    /**
     * @param array<mixed> $config
     * @param IConfigurator[] $configurators
     */
    public function __construct(
        array $config,
        array $configurators = []
    ) {
        $or = new OptionsResolver();
        $or->setDefaults([
            'areabricks' => [],
            'datasources' => [],
        ]);
        $this->config = $or->resolve($config);
        $this->configurators = $configurators;
    }

    /**
     * @param IConfigurator[] $configurators
     */
    public function setConfigurators(array $configurators): void
    {
        $this->configurators = $configurators;
    }

    /**
     * @param array<mixed> $context
     *
     * @return \Generator<array>
     */
    public function compileAreaBrick(string $name, array &$context): \Generator
    {
        $or = new OptionsResolver();
        $or->setRequired(['view', 'request']);
        $or->setDefault('datasources', []);
        $context = $or->resolve($context);
        $config = $this->getAreabrickConfig($name);

        /** @var IConfigurator $configurator */
        foreach ($this->configurators as $configurator) {
            $context = $configurator->preCreateEditables($name, $config, $this->config, $context);
        }

        return $this->createEditables($name, $context);
    }

    /**
     * @return array<array>
     */
    protected function getDatasourceConfig(string $name): array
    {
        return $this->config['datasources'][$name];
    }

    /**
     * @return array<mixed>
     */
    public function getAreabrickConfig(string $name): array
    {
        $or = new OptionsResolver();
        $or->setDefaults([
            'icon' => null,
            'label' => null,
            'open' => '',
            'close' => '',
            'use_edit' => false,
        ]);
        $config = $this->config['areabricks'][$name] ?: [];
        $or->setDefined(array_keys($config));

        return $or->resolve($config);
    }

    /**
     * @param array<array> $config
     *
     * @return \Generator<array>
     */
    protected function compileEditablesConfig(array $config): \Generator
    {
        $editablesConfig = $config['editables'];
        $or = new OptionsResolver();
        $or->setDefaults([
            'options' => [],
        ]);
        $or->setRequired([
            'type'
        ]);

        foreach ($this->configurators as $configurator) {
            $configurator->configureEditableOptions($or);
        }

        foreach ($editablesConfig as $name => $econfig) {
            $econfig = $or->resolve($econfig);
            yield $name => $econfig;
        }
    }

    /**
     * @param array<mixed> $context
     *
     * @return \Generator<array>
     */
    public function createEditables(
        string $areabrick,
        array $context = []
    ): \Generator {
        $compiledConfig = $this->compileEditablesConfig($this->config['areabricks'][$areabrick]);
        $compiledConfig = iterator_to_array($compiledConfig);

        foreach ($compiledConfig as $name => $config) {
            $renderArgs = new RenderArgs();
            $renderArgs->set(
                [$name => [
                    'type'=> $config['type'],
                    'options' => $config['options'],
                ]]
            );

            foreach ($this->configurators as $configurator) {
                if (!$configurator->supportsEditable($name, $config)) {
                    continue;
                }

                $renderArgs = $configurator->createEditables(
                    $renderArgs,
                    [
                        'editable' => [
                            'name' => $name,
                            'config' => $config,
                        ],
                        'context' =>  $context
                    ],
                );

                yield from $renderArgs->getAll();
            }

            yield from $renderArgs->getAll();
        }
    }
}
