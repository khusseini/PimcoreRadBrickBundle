<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;

interface IConfigurator
{
    /**
     * @param array<array> $config
     */
    public function supportsEditable(string $editableName, array $config): bool;
    /**
     * @param array<array> $data
     */
    public function createEditables(RenderArgs $renderArgs, array $data): RenderArgs;
    public function configureEditableOptions(OptionsResolver $or): void;
    /**
     * @param array<mixed> $brickConfig
     * @param array<mixed> $config
     * @param array<mixed> $context
     *
     * @return array<mixed>
     */
    public function preCreateEditables(string $brickName, array $brickConfig, array $config, array $context): array;
}
