<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\ContextInterface;

class ConfiguratorData
{
    private $context;
    private $config = [];

    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
