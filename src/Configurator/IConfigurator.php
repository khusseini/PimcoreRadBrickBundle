<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;

interface IConfigurator
{
    public function supportsEditable(string $editableName, array $config): bool;
    public function createEditables(RenderArgs $renderArgs, array $data): RenderArgs;
    public function configureEditableOptions(OptionsResolver $or): void;
    public function preCreateEditables(string $brickName, array $brickConfig, array $config, array $context): array;
}
