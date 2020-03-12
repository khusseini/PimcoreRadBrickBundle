<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Templating\Model\ViewModelInterface;
use Pimcore\Templating\Renderer\TagRenderer;

abstract class AbstractAreabrick extends AbstractTemplateAreabrick
{
    /** @var TagRenderer */
    private $tagRenderer;
    /** @var string */
    private $openTag;
    /** @var string */
    private $closeTag;

    public function __construct(
        TagRenderer $tagRenderer, 
        string $open = '', 
        string $close = ''
    ) {
        $this->tagRenderer = $tagRenderer;
        $this->openTag = $this->open;
        $this->closeTag = $this->close;
    }

    protected function getTagRenderer(): TagRenderer
    {
        return $this->tagRenderer;
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

    public function action(Info $info)
    {
        if (!$this->getConfig()) return null;
        $view = $info->getView();
        $elements = iterator_to_array($this->getElements($info, $this->getConfig()));
        foreach ($elements as $name => $element) {
            $view[$name] = $element;
        }

        return $this->doAction($view);

    }

    public function doAction(ViewModelInterface $view)
    {
        return null;
    }

    protected function getElements(Info $info, array $config)
    {
        foreach ($config as $name => $elementConfig) {
            reset($elementConfig);
            $type = key($elementConfig);
            $options = $elementConfig[$type];
            $element = $this->getTagRenderer()->render($info->getDocument(), $type, $name, $options);
            yield $name => $element;
        };
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
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
