<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use Khusseini\PimcoreRadBrickBundle\AreabrickRenderer;
use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Model\Document\Tag\Area\Info;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAreabrick extends AbstractTemplateAreabrick
{
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
     * @var AreabrickRenderer
     */
    private $areabrickRenderer;

    public function __construct(
        string $name,
        AreabrickRenderer $areabrickRenderer
    ) {
        $this->name = $name;
        $this->areabrickRenderer = $areabrickRenderer;
        $options = $areabrickRenderer->getAreabrickConfig($name);
        $this->icon = $options['icon'];
        $this->label = $options['label'];
        $this->openTag = $options['open'];
        $this->closeTag = $options['close'];
        $this->useEdit = $options['use_edit'];
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getIcon()
    {
        return $this->icon ?: parent::getIcon();
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getName()
    {
        return $this->label ?: parent::getName();
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function hasEditTemplate()
    {
        return $this->useEdit;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function getTemplateLocation()
    {
        return static::TEMPLATE_LOCATION_GLOBAL;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
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
        $this->areabrickRenderer->render($this->name, $info);
        return $this->doAction($info);
    }

    public function doAction(Info $info): ?Response
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function postRenderAction(Info $info)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getHtmlTagOpen(Info $info)
    {
        return $this->openTag;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getHtmlTagClose(Info $info)
    {
        return $this->closeTag;
    }
}
