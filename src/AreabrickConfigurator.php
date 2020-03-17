<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Pimcore\Model\Document;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Renderer\TagRenderer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AreabrickConfigurator
{
    /** @var TagRenderer */
    private $tagRenderer;

    /** array  */
    private $config = [];

    public function __construct(TagRenderer $tagRenderer, array $config)
    {
        $or = new OptionsResolver();
        $or->setDefaults([
            'areabricks' => [],
            'datasources' => [],
        ]);
        $this->config = $or->resolve($config);
        $this->tagRenderer = $tagRenderer;
    }

    public function compileEditablesConfig(array $config)
    {
        $editablesConfig = $config['editables'];
        $or = new OptionsResolver();
        $or->setDefaults([
            'options' => []
        ]);
        $or->setRequired([
            'type'
        ]);

        foreach ($editablesConfig as $name => $config) {
            yield $name => $or->resolve($config);
        }
    }

    public function createEditables(string $areabrick, PageSnippet $document)
    {
        $compiledConfig = $this->compileEditablesConfig($this->config['areabricks'][$areabrick]);
        foreach ($compiledConfig as $name => $config) {
            yield $name => $this->render($document, $config['type'], $name, $config['options']);
        }
    }

    protected function render(PageSnippet $document, string $type, string $name, array $options = [])
    {
        return $this->tagRenderer->render($document, $type, $name, $options);
    }
}
