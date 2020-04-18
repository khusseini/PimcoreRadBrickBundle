<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
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
    public function createEditables(RenderArgumentEmitter $emitter, string $name, array $config): void;

    public function configureEditableOptions(OptionsResolver $or): void;

    /**
     * @param \ArrayObject<string,mixed> $data
     *
     * @return array<string,mixed>
     */
    public function preCreateEditables(string $brickName, \ArrayObject $data): array;

    /**
     * @param array<string,mixed> $config
     */
    public function postCreateEditables(string $brickName, array $config, RenderArgumentEmitter $emitter): void;
}
