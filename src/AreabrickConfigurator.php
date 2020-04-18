<?php

namespace Khusseini\PimcoreRadBrickBundle;

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
    public function compileAreaBrick(string $name, array $context): \Generator
    {
        $or = new OptionsResolver();
        $or->setRequired(['view', 'request']);
        $or->setDefault('datasources', []);

        $context = $or->resolve($context);
        $data = new \ArrayObject();
        $data['config'] = $this->resolveAreaBrickConfig($name);
        $data['context'] = $context;

        /**
 * @var IConfigurator $configurator 
*/
        foreach ($this->configurators as $configurator) {
            $newContext = $configurator->preCreateEditables($name, $data);
            $data['context'] = array_merge($data['context'], $newContext);
        }

        $this->config = $data['config'];

        return $this->createEditables($name, $data['context']);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function setAreabrickConfig(string $name, array $config): void
    {
        $this->config['areabricks'][$name] = $config;
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
     * @param array<mixed> $context
     *
     * @return \Generator<RenderArgument>
     */
    public function createEditables(
        string $areabrick,
        array $context = []
    ): \Generator {
        $editablesConfig = $this->compileEditablesConfig($this->config['areabricks'][$areabrick]);
        $areaBrickConfig = $this->getAreabrickConfig($areabrick);
        $renderer = new Renderer();

        /**
 * @var string $editableName 
*/
        foreach ($editablesConfig as $editableName => $editableConfig) {
            $argument = new RenderArgument(
                'editable',
                $editableName,
                ['type' => $editableConfig['type'], 'options' => $editableConfig['options']]
            );

            $renderer->emitArgument($argument);

            foreach ($this->configurators as $configurator) {
                if ($configurator->supportsEditable($editableName, $editableConfig)) {
                    $configurator->createEditables(
                        $renderer,
                        $editableName,
                        ['editable' => $editableConfig, 'context' => $context]
                    );
                }
            }

            yield from $renderer->emit();
        }

        /**
 * @var IConfigurator $configurator 
*/
        foreach ($this->configurators as $configurator) {
            $configurator->postCreateEditables($areabrick, $areaBrickConfig, $renderer);
        }

        yield from $renderer->emit();
    }
}
