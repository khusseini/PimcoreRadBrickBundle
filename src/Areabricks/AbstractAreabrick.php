<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Templating\Model\ViewModelInterface;
use Pimcore\Templating\Renderer\TagRenderer;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractAreabrick extends AbstractTemplateAreabrick
{
    /** @var AreabrickConfigurator */
    private $areabrickConfigurator;

    /** @var string */
    private $name;

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
        $this->open = $options['open'];
        $this->close = $options['close'];
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
        $editables = $this
            ->areabrickConfigurator
            ->compileAreaBrick($this->name, [
                'view' => $info->getView(),
                'request' => $info->getRequest(),
            ])
        ;

        foreach ($editables as $name => $config) {
            $this->processEditable($name, $config, $info);
        }

        return $this->doAction($info);
    }

    private function processEditable(string $name, array $config, Info $info)
    {
        $view = $info->getView();
        if (isset($config['type'])) {
            $rendered = $this
                ->getTagRenderer()
                ->render($info->getDocument(), $config['type'], $name, $config['options'])
            ;
        }
        if (!isset($config['type'])) {
            $rendered = [];
            foreach ($config as $id => $c) {
                if (!is_array($c) && !isset($c['type'])) {
                    return;
                }
                $rendered[] = $this
                    ->getTagRenderer()
                    ->render($info->getDocument(), $c['type'], $name.'_'.$id, $c['options'])
                ;
            }
        }

        $view[$name] = $rendered;
    }

    public function doAction(Info $info)
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
