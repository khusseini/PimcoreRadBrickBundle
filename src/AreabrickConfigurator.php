<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Khusseini\PimcoreRadBrickBundle\ExpressionLanguage\ExpressionWrapper;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfigurator
{
    /** @var array  */
    private $config = [];

    /** @var IConfigurator[] */
    private $configurators = [];

    /** @var DatasourceRegistry */
    private $datasources;

    public function __construct(
        array $config,
        array $configurators = [],
        DatasourceRegistry $datasources = null
    ) {
        $or = new OptionsResolver();
        $or->setDefaults([
            'areabricks' => [],
            'datasources' => [],
        ]);
        $this->datasources = $datasources;
        $this->config = $or->resolve($config);
        $this->configurators = $configurators;
    }

    public function setConfigurators(array $configurators)
    {
        $this->configurators = $configurators;
    }

    public function compileAreaBrick(string $name, array $context)
    {
        $or = new OptionsResolver();
        $or->setRequired(['view', 'request']);
        $or->setDefault('datasources', []);
        $context = $or->resolve($context);
        $config = $this->getAreabrickConfig($name);

        foreach ($this->configurators as $configurator) {
            $context = $configurator->preCreateEditable($name, $config, $this->config, $context);
        }

        return $this->createEditables($name, $context);
    }

    protected function getDatasourceConfig(string $name)
    {
        return $this->config['datasources'][$name];
    }

    public function getAreabrickConfig(string $name)
    {
        $or = new OptionsResolver();
        $or->setDefaults([
            'icon' => null,
            'label' => null,
            'open' => '',
            'close' => '',
        ]);
        $config = $this->config['areabricks'][$name] ?: [];
        $or->setDefined(array_keys($config));

        return $or->resolve($config);
    }

    protected function compileEditablesConfig(array $config)
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

    public function createEditables(
        string $areabrick,
        array $context = []
    ) {
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
