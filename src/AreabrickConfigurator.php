<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Pimcore\Model\Document\PageSnippet;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfigurator
{
    /** @var array  */
    private $config = [];

    /** @var IConfigurator[] */
    private $configurators = [];

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

    public function setConfigurators(array $configurators)
    {
        $this->configurators = $configurators;
    }

    /** @var OptionsResolver */
    private $outOptionsResolver;
    protected function getOutOptionsResolver()
    {
        if (!$this->outOptionsResolver) {
            $this->outOptionsResolver = new OptionsResolver();
            $this->outOptionsResolver->setRequired(['type', 'options']);
        }

        return $this->outOptionsResolver;
    }

    public function compileEditablesConfig(array $config)
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

    public function createEditables(string $areabrick, PageSnippet $document)
    {
        $compiledConfig = $this->compileEditablesConfig($this->config['areabricks'][$areabrick]);
        $compiledConfig = iterator_to_array($compiledConfig);
        
        foreach ($compiledConfig as $name => $config) {
            $toRender = [
                $name => [
                    'type'=> $config['type'],
                    'options' => $config['options'],
                ]
            ];

            foreach ($this->configurators as $configurator) {
                if (!$configurator->supports('create_editables', $name, $config)) {
                    continue;
                }
                
                $toRender = $configurator->processConfig(
                    'create_editables',
                    $this->getOutOptionsResolver(),
                    [
                        'editable' => [
                            'name' => $name,
                            'config' => $config,
                        ],
                        'renderArgs' => $toRender,
                    ],
                    $compiledConfig,
                    $toRender,
                    ['options', 'type']
                );
            }
            yield from $toRender;
        }
    }
}
