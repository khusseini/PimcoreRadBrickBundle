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
        $view = $info->getView();
        $elements = iterator_to_array($this->getElements($info, $this->getConfig()['editables'] ?: []));
        foreach ($elements as $name => $element) {
            $view[$name] = $element;
        }

        $generators = iterator_to_array($this->getGenerators($info, $this->getConfig()['generators'] ?: []));
        foreach ($generators as $name => $generator) {
            $view[$name] = $generator;
        }

        return $this->doAction($view);
    }

    public function doAction(ViewModelInterface $view)
    {
        return null;
    }
    
    protected function getGenerators(Info $info)
    {
        $config = $this->getConfig()['generators'] ? : [];
        $tr = $this->getTagRenderer();
        $doc = $info->getDocument();
        $view = $info->getView();
        
        foreach ($config as $name => $elementConfig) {
            $type = $elementConfig['type'];
            $options = $elementConfig['options'] ?: [];
            $source = $elementConfig['source'];
            $sourceElement = $view[$source];
            
            if (!$sourceElement) {
                continue;
            }
            
            $count = $sourceElement->getData();

            $elements = [];
            for($i = 1; $i <= $count; ++$i) {
                $ename = $name.'_'.$i;
                $element = $tr->render($doc, $type, $ename, $options);
                $elements[$i] = $element;
            }


            yield $name => $elements;
        }
    }

    protected function getElements(Info $info)
    {
        $config = $this->getConfig()['editables'] ? : [];
        foreach ($config as $name => $elementConfig) {
            $type = $elementConfig['type'];
            $options = $elementConfig['options'] ?: [];
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
