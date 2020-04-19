<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Pimcore\Templating\Model\ViewModelInterface;
use Symfony\Component\HttpFoundation\Request as RequestInterface;

/**
 * @codeCoverageIgnore
 */
class Context implements ContextInterface
{
    /**
     * @var ViewModelInterface
     */
    private $view;
    /**
     * @var DatasourceRegistry
     */
    private $datasources = [];
    /**
     * @var RequestInterface
     */
    private $request = null;

    public function __construct(
        ViewModelInterface $view,
        RequestInterface $request,
        ?DatasourceRegistry $datasourceRegistry = null
    ) {
        $this->view = $view;
        $this->request = $request;
        $this->datasources = $datasourceRegistry;
    }

    public function getView(): ViewModelInterface
    {
        return $this->view;
    }

    public function getDatasources(): ?DatasourceRegistry
    {
        return $this->datasources;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function setDatasources(DatasourceRegistry $datasourceRegistry): void
    {
        $this->datasources = $datasourceRegistry;
    }

    public function toArray(): array
    {
        return [
            'view' => $this->getView(),
            'request' => $this->getRequest(),
            'datasources' => $this->getDatasources(),
        ];
    }
}
