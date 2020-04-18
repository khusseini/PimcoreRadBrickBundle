<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use ArrayAccess;
use ArrayObject;
use Iterator;
use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Context;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Metadata\NullMetadata;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Templating\Renderer\TagRenderer;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAreabrick extends AbstractTemplateAreabrick
{
    /**
     * @var AreabrickConfigurator
     */
    private $areabrickConfigurator;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $icon;
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $openTag = '';
    /**
     * @var string
     */
    private $closeTag = '';
    /**
     * @var bool
     */
    private $useEdit = false;
    /**
     * @var TagRenderer
     */
    private $tagRenderer;

    public function __construct(
        string $name,
        TagRenderer $tagRenderer,
        AreabrickConfigurator $areabrickConfigurator
    ) {
        $this->name = $name;
        $this->tagRenderer = $tagRenderer;
        $this->areabrickConfigurator = $areabrickConfigurator;
        $this->areabrickConfigurator->resolveAreaBrickConfig($name);
        $options = $this->areabrickConfigurator->getAreabrickConfig($name);
        $this->icon = $options['icon'];
        $this->label = $options['label'];
        $this->openTag = $options['open'];
        $this->closeTag = $options['close'];
        $this->useEdit = $options['use_edit'];
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon ?: parent::getIcon();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->label ?: parent::getName();
    }

    protected function getTagRenderer(): TagRenderer
    {
        return $this->tagRenderer;
    }

    /**
     * @return bool
     */
    public function hasEditTemplate()
    {
        return $this->useEdit;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateLocation()
    {
        return static::TEMPLATE_LOCATION_GLOBAL;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateSuffix()
    {
        return static::TEMPLATE_SUFFIX_TWIG;
    }

    /**
     * @return null|Response
     */
    public function action(Info $info)
    {
        $view = $info->getView();
        $context = new Context($view, $info->getRequest());

        $renderArguments = $this
            ->areabrickConfigurator
            ->compileAreaBrick($this->name, $context);

        $this->processRenderArguments(
            $renderArguments,
            $info->getView(),
            $info->getDocument()
        );

        return $this->doAction($info);
    }

    /**
     * @param ArrayAccess<string,mixed> $container
     * @param Iterator<RenderArgument>  $renderArguments
     */
    private function processRenderArguments(
        Iterator $renderArguments,
        ArrayAccess $container,
        PageSnippet $document,
        ArrayAccess $referencesContainer = null,
        string $parentName = ''
    ): void {
        if (!$referencesContainer) {
            $referencesContainer = new \ArrayObject();
        }

        $render = function ($name, $config) use ($document) {
            return $this->tagRenderer->render($document, $config['type'], $name, $config['options']);
        };

        foreach ($renderArguments as $name => $renderArgument) {
            $referenceId = $parentName ? $parentName.'_'.$name : $name;

            if (!$renderArgument instanceof RenderArgument) {
                continue;
            }

            if ($renderArgument->getType() === 'null') {
                continue;
            }

            if ($renderArgument->getType() === 'collection') {
                $tag = new ArrayObject();
                $this->processRenderArguments(
                    new \ArrayIterator($renderArgument->getValue()),
                    $tag,
                    $document,
                    $referencesContainer,
                    $referenceId
                );
                $tag = (array)$tag;
            } elseif ($renderArgument->getType() === 'editable') {
                $tag = $render($referenceId, $renderArgument->getValue());
            } elseif ($renderArgument->getType() === 'reference') {
                $reference = $renderArgument->getValue();
                $tag = $referencesContainer[$reference];
            } else {
                $tag = $renderArgument->getValue();
            }

            $referencesContainer[$referenceId] = $container[$name] = $tag;
        }
    }

    public function doAction(Info $info): ?Response
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function postRenderAction(Info $info)
    {
        // noop - implement as needed
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagOpen(Info $info)
    {
        return $this->openTag;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagClose(Info $info)
    {
        return $this->closeTag;
    }
}
