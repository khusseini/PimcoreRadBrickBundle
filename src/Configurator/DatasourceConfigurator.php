<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgs;

class DatasourceConfigurator extends AbstractConfigurator
{
    public function doProcessConfig(
        string $action,
        RenderArgs $renderArgs,
        array $data
    ): RenderArgs {
        return $renderArgs;
    }

    public function supports(string $action, string $editableName, array $config): bool
    {
        return false;
    }
}
