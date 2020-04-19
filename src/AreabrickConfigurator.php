<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Khusseini\PimcoreRadBrickBundle\Configurator\ConfiguratorData;
use Khusseini\PimcoreRadBrickBundle\Configurator\IConfigurator;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfigurator
{
    /**
     * @var array<mixed>
     */
    private $config = [];

    /**
     * @var IConfigurator[]
     */
    private $configurators = [];

    /**
     * @param array<mixed>    $config
     * @param IConfigurator[] $configurators
     */
    public function __construct(
        array $config,
        array $configurators = []
    ) {
        $or = new OptionsResolver();
        $or->setDefaults(
            [
            'areabricks' => [],
            'datasources' => [],
            ]
        );
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
     * @return \Generator<RenderArgument>
     */
    public function compileAreaBrick(string $name, ContextInterface $context): \Generator
    {
        $data = new ConfiguratorData($context);
        $data->setConfig($this->resolveAreaBrickConfig($name));

        /** @var IConfigurator $configurator */
        foreach ($this->configurators as $configurator) {
            $configurator->preCreateEditables($name, $data);
        }

        $this->config = $data->getConfig();
        return $this->createEditables($name, $context);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function setAreabrickConfig(string $name, array $config): void
    {
        $this->config['areabricks'][$name] = $config;
    }

    public function hasAreabrickConfig(string $name): bool
    {
        return (bool)$this->getAreabrickConfig($name);
    }

    /**
     * @return array<string,mixed>
     */
    public function getAreabrickConfig(string $name): array
    {
        return isset($this->config['areabricks'][$name]) ? $this->config['areabricks'][$name] : [];
    }

    /**
     * @return array<array>
     * @codeCoverageIgnore
     */
    protected function getDatasourceConfig(string $name): array
    {
        return $this->config['datasources'][$name];
    }

    /**
     * @return array<mixed>
     */
    public function resolveAreaBrickConfig(string $name): array
    {
        $or = new OptionsResolver();
        $or->setDefaults(
            [
            'icon' => null,
            'label' => null,
            'open' => '',
            'close' => '',
            'use_edit' => false,
            ]
        );

        $config = $this->getAreabrickConfig($name);
        $or->setDefined(array_keys($config));

        $this->setAreabrickConfig($name, $or->resolve($config));

        return $this->config;
    }

    /**
     * @param array<array> $config
     *
     * @return array<array>
     */
    protected function compileEditablesConfig(array $config): array
    {
        $editablesConfig = $config['editables'];
        $or = new OptionsResolver();
        $or->setDefaults(
            [
            'options' => [],
            ]
        );
        $or->setRequired(
            [
            'type'
            ]
        );

        foreach ($this->configurators as $configurator) {
            $configurator->configureEditableOptions($or);
        }

        $return = [];

        foreach ($editablesConfig as $name => $econfig) {
            $econfig = $or->resolve($econfig);
            $return[$name] = $econfig;
        }

        return $return;
    }

    /**
     * @return \Generator<RenderArgument>
     */
    public function createEditables(
        string $areabrick,
        ContextInterface $context
    ): \Generator {
        $editablesConfig = $this->compileEditablesConfig($this->config['areabricks'][$areabrick]);
        $areaBrickConfig = $this->getAreabrickConfig($areabrick);
        $emitter = new RenderArgumentEmitter();

        /** @var string $editableName */
        foreach ($editablesConfig as $editableName => $editableConfig) {
            $argument = new RenderArgument(
                'editable',
                $editableName,
                ['type' => $editableConfig['type'], 'options' => $editableConfig['options']]
            );

            $emitter->emitArgument($argument);

            $data = new ConfiguratorData($context);
            $data->setConfig($editableConfig);

            foreach ($this->configurators as $configurator) {
                if ($configurator->supportsEditable($editableName, $editableConfig)) {
                    $configurator->createEditables($emitter, $editableName, $data);
                }
            }

            yield from $emitter->emit();
        }

        /**
         * @var IConfigurator $configurator
        */
        foreach ($this->configurators as $configurator) {
            $configurator->postCreateEditables($areabrick, $areaBrickConfig, $emitter);
        }

        yield from $emitter->emit();
    }
}
