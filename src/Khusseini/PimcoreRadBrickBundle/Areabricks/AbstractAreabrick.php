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

    private $label;
    private $useEdit;

    public function __construct(
        TagRenderer $tagRenderer, 
        $label = null,
        $useEdit = false,
        string $open = '', 
        string $close = ''
    ) {
        $this->tagRenderer = $tagRenderer;
        $this->openTag = $this->open;
        $this->closeTag = $this->close;
        $this->label = $label;
        $this->useEdit = $useEdit;
    }

    public function getName()
    {
        return $this->label ?: parent::getName();
    }

    protected function getTagRenderer(): TagRenderer
    {
        return $this->tagRenderer;
    }

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

    public function action(Info $info)
    {
        if (!$this->getConfig()) return null;
        $this->processElements($info);
        return $this->doAction($info);
    }

    public function doAction(Info $info)
    {
        return null;
    }

    protected function processElements(Info $info)
    {
        $config = $this->getConfig()['editables'] ? : [];
        $view = $info->getView();
        $tagRenderer = $this->getTagRenderer();
        $doc = $info->getDocument();

        foreach ($config as $name => $elementConfig) {
            $source = $elementConfig['source'];
            $type = $elementConfig['type'];
            $options = $elementConfig['options'] ?: [];
            $element = null;

            if (!is_null($source)) {
                if (!$view->has($source)) {
                    continue;
                }

                $sourceElement = $view->get($source);

                //TODO: Refactor to data provider
                $count = (int)$sourceElement->getData() ?: 1;
                $element = [];
                for($i = 1; $i <= $count; ++$i) {
                    $ename = $name.'_'.$i;
                    $e = $tagRenderer->render($doc, $type, $ename, $options);
                    $element[$i] = $e;
                }
            }

            if (is_null($element)) {
                $element = $this->getTagRenderer()->render($doc, $type, $name, $options);
            }

            $view[$name] = $element;
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
