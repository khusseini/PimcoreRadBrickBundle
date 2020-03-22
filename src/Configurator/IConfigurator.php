<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface IConfigurator
{
    /**
     * @param array<array> $config
     */
    public function supportsEditable(string $editableName, array $config): bool;

    /**
     * @param array<array> $config
     * @return \Generator<RenderArgument>
     */
    public function createEditables(Renderer $renderer, string $name, array $config): \Generator;

    public function configureEditableOptions(OptionsResolver $or): void;

    /**
     * @param \ArrayObject<string,mixed> $data
     *
     * @return array<string,mixed>
     */
    public function preCreateEditables(string $brickName, \ArrayObject $data): array;

    /**
     * @param array<string,mixed> $config
     *
     * @return \Generator<string,RenderArgument>
     */
    public function postCreateEditables(string $brickName, array $config, Renderer $renderer): \Generator;
}
