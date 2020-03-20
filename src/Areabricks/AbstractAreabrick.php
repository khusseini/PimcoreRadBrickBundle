<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
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
        $editables = $this
            ->areabrickConfigurator
            ->compileAreaBrick($this->name, $context)
        ;

        /** @var string $name */
        foreach ($editables as $name => $config) {
            $this->processEditable($name, $config, $info);
        }

        /** @var DatasourceRegistry $registry */
        if ($registry = @$context['datasources']) {
            foreach ($registry->getAll() as $name => $callable) {
                $view[$name] = $callable();
            }
        }

        return $this->doAction($info);
    }

    /**
     * @param array<mixed> $config
     */
    private function processEditable(string $name, array $config, Info $info): void
    {
        $view = $info->getView();
        $rendered = null;
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
