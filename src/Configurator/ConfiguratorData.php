<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

class ConfiguratorData
{
    private $data;
    private $context;

    public function __construct($data, $context)
    {
        $this->data = $data;
        $this->context = $context;
    }
}
