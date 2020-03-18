<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface IConfigurator
{
    public function supports(string $action, string $editableName, array $config): bool;
    public function processConfig(string $action, array $editableConfig, array $areabrickConfig): array;
    public function configureEditableOptions(OptionsResolver $or): void;
}
