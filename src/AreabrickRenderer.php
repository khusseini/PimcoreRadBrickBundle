<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Model\Element\Tag;
use Pimcore\Templating\Renderer\TagRenderer;

class AreabrickRenderer
{
    /**
     * @var AreabrickConfigurator
     */
    private $configurator;

    /**
     * @var TagRenderer
     */
    private $tagRenderer;

    public function __construct(
        AreabrickConfigurator $areabrickConfigurator,
        TagRenderer $tagRenderer
    ) {
        $this->configurator = $areabrickConfigurator;
        $this->tagRenderer = $tagRenderer;
    }

    public function getConfigurator(): AreabrickConfigurator
    {
        return $this->configurator;
    }

    public function render(string $name, Info $info)
    {
        $configurator = $this->getConfigurator();
        if (!$configurator->hasAreabrickConfig($name)) {
            throw new \InvalidArgumentException(sprintf('Areabrick \'%s\' has no configuration.', $name));
        }

        $context = new Context($info->getView(), $info->getRequest());

        $renderArguments = $this
            ->getConfigurator()
            ->compileAreaBrick($name, $context);

        $this->processRenderArguments(
            $info,
            $renderArguments
        );
    }

    /**
     * @param Iterator<RenderArgument> $renderArguments
     */
    public function processRenderArguments(
        Info $info,
        \Iterator $renderArguments,
        \ArrayAccess $container = null,
        \ArrayAccess $referencesContainer = null,
        string $parentName = ''
    ): void {
        if (!$container) {
            $container = $info->getView();
        }

        if (!$referencesContainer) {
            $referencesContainer = new \ArrayObject();
        }

        foreach ($renderArguments as $name => $renderArgument) {
            $referenceId = $parentName ? $parentName.'_'.$name : $name;

            if ($renderArgument->getType() === 'null') {
                continue;
            }

            if ($renderArgument->getType() === 'collection') {
                $tag = new \ArrayObject();
                $this->processRenderArguments(
                    $info,
                    new \ArrayIterator($renderArgument->getValue()),
                    $tag,
                    $referencesContainer,
                    $referenceId
                );
                $tag = (array)$tag;
            } elseif ($renderArgument->getType() === 'editable') {
                $tag = $this->renderArgument($info, $renderArgument, $referenceId);
            } elseif ($renderArgument->getType() === 'reference') {
                $reference = $renderArgument->getValue();
                $tag = $referencesContainer[$reference];
            } else {
                $tag = $renderArgument->getValue();
            }

            $referencesContainer[$referenceId] = $container[$name] = $tag;
        }
    }

    protected function renderArgument(Info $info, RenderArgument $renderArgument, string $nameOverride = ''): ?Tag
    {
        $config = $renderArgument->getValue();

        if (!is_array($config)
            || !isset($config['type'])
        ) {
            return null;
        }

        if (!isset($config['options'])) {
            $config['options'] = [];
        }

        return $this
            ->tagRenderer
            ->render(
                $info->getDocument(),
                $config['type'],
                $nameOverride ?: $renderArgument->getName(),
                $config['options']
            );
    }

    public function getAreabrickConfig(string $name): array
    {
        $configurator = $this->getConfigurator();
        $configurator->resolveAreaBrickConfig($name);
        return $configurator->getAreabrickConfig($name);
    }
}
