<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;

interface IConfigurator
{
    public function supports(string $action, string $editableName, array $config): bool;
    public function processConfig(string $action, RenderArgs $renderArgs, array $data): RenderArgs;
    public function configureEditableOptions(OptionsResolver $or): void;
    public function processValue($value, array $context);
}
