<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface IConfigurator
{
    /**
     * @param array<array> $config
     */
    public function supportsEditable(string $editableName, array $config): bool;

    /**
     * @param array<array> $config
     */
    public function createEditables(RenderArgument $renderArgs, string $name, array $config): \Generator;

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
