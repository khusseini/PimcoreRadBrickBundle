<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use ArrayAccess;
use ArrayObject;
use Iterator;
use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Templating\Renderer\TagRenderer;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAreabrick extends AbstractTemplateAreabrick
{
    /** @var AreabrickConfigurator */
    private $areabrickConfigurator;
    /** @var string */
    private $name;
    /** @var string */
    private $icon;
    /** @var string */
    private $label;
    /** @var string */
    private $openTag = '';
    /** @var string */
    private $closeTag = '';
    /** @var bool */
    private $useEdit = false;
    /** @var TagRenderer */
    private $tagRenderer;

    public function __construct(
        string $name,
        TagRenderer $tagRenderer,
        AreabrickConfigurator $areabrickConfigurator
    ) {
        $this->name = $name;
        $this->tagRenderer = $tagRenderer;
        $this->areabrickConfigurator = $areabrickConfigurator;
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
        $context = [
            'view' => $view,
            'request' => $info->getRequest(),
        ];

        $renderArguments = $this
            ->areabrickConfigurator
            ->compileAreaBrick($this->name, $context)
        ;

        $this->processRenderArguments(
            $renderArguments,
            $info->getView(),
            $info->getDocument()
        );

        return $this->doAction($info);
    }

    /**
     * @param ArrayAccess<string,mixed> $container
     * @param Iterator<RenderArgument> $renderArguments
     */
    private function processRenderArguments(
        Iterator $renderArguments,
        ArrayAccess $container,
        PageSnippet $document
    ): void {
        $render = function ($name, $config) use ($document) {
            return $this->tagRenderer->render($document, $config['type'], $name, $config['options']);
        };

        foreach ($renderArguments as $name => $renderArgument) {
            if ($renderArgument->getType() === 'collection') {
                $tag = new ArrayObject();
                $this->processRenderArguments($renderArgument->getValue(), $tag, $document);
            } elseif ($renderArgument->getType() === 'editable') {
                $tag = $render($name, $renderArgument->getValue());
            } else {
                $tag = $renderArgument->getValue();
            }

            $container[$name] = $tag;
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
