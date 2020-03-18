<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface IConfigurator
{
    public function supports(string $action, string $editableName, array $config): bool;
    public function processConfig(string $action, OptionsResolver $or, array $data);
    public function configureEditableOptions(OptionsResolver $or): void;
    public function processValue($value, array $context);
}
