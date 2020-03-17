<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Templating\Model\ViewModelInterface;
use Pimcore\Templating\Renderer\TagRenderer;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractAreabrick extends AbstractTemplateAreabrick
{
    /** @var string */
    private $openTag;
    /** @var string */
    private $closeTag;
    /** @var string */
    private $label = null;
    /** @var bool */
    private $useEdit = false;
    /** @var string */
    private $icon = null;
    /** @var DatasourceRegistry */
    private $datasourceRegistry;

    public function __construct(
        TagRenderer $tagRenderer, 
        DatasourceRegistry $datasourceRegistry,
        $label = null,
        $useEdit = false,
        string $open = '', 
        string $close = '',
        string $icon = null
    ) {
        $this->tagRenderer = $tagRenderer;
        $this->datasourceRegistry = $datasourceRegistry;
        $this->openTag = $open;
        $this->closeTag = $close;
        $this->label = $label;
        $this->useEdit = $useEdit;
        $this->icon = $icon;
    }

    public function getIcon()
    {
        return $this->icon ?: parent::getIcon();

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
        $this->processEditables($info);
        $this->processDatasources($info);
        return $this->doAction($info);
    }

    public function doAction(Info $info)
    {
        return null;
    }

    protected function processDatasources(Info $info)
    {
        $datasources = @$this->getConfig()['datasources'] ?: [];
        $view = $info->getView();
        $data = [
            'request' => $info->getRequest(),
            'view' => $view,
        ];
        
        foreach ($datasources as $name => $options) {
            if (!$options['args']) {
                continue;
            }
            $arguments = $options['args'];
            foreach ($arguments as $key => $value) {
                if (!is_string($value)) {
                    continue;
                }
                $arguments[$key] = $this->datasourceRegistry->getValue($data, $value);
            }
            $view[$name] = $this->datasourceRegistry->execute($name, $arguments) ?: [];
        }
    }

    protected function processEditables(Info $info)
    {
        $editables = $this->getConfig()['editables'] ? : [];
        $view = $info->getView();
        $tagRenderer = $this->getTagRenderer();
        $doc = $info->getDocument();

        foreach ($editables as $name => $elementConfig) {
            $elementConfig = $this->applyMap($elementConfig, $view);

            $type = $elementConfig['type'];
            $options = $elementConfig['options'] ?: [];
            $element = null;

            if (is_null($element)) {
                $element = $tagRenderer->render($doc, $type, $name, $options);
            }

            $view[$name] = $element;
        };
    }

    public function applyMap(array $editableConfig, ViewModelInterface $view)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        if (
            !isset($editableConfig['map'])
        ) {
            return $editableConfig;
        }

        foreach ($editableConfig['map'] as $map) {
            if (!$propertyAccessor->isReadable($view, $map['source'])) {
                continue;
            }

            $propertyAccessor
                ->setValue(
                    $editableConfig, 
                    $map['target'], 
                    $propertyAccessor->getValue($view, $map['source'])
                )
            ;    
        }
        

        return $editableConfig;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig(array $editableConfig)
    {
        $this->config = $editableConfig;
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
