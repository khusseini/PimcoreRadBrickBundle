<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\ExpressionLanguage\ExpressionWrapper;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\Renderer;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractConfigurator implements IConfigurator
{
    /** @var ExpressionWrapper */
    private $expressionWrapper;


    public function __construct(ExpressionWrapper $expressionWrapper = null)
    {
        if (!$expressionWrapper) {
            $expressionWrapper = new ExpressionWrapper();
        }

        $this->expressionWrapper = $expressionWrapper;
    }

    /**
     * @param array<array> $data
     * @return \Generator<RenderArgument>
     */
    abstract public function doCreateEditables(
        Renderer $rednerer,
        string $name,
        array $data
    ): \Generator;

    /**
     * @return array<string>
     */
    public function getEditablesExpressionAttributes(): array
    {
        return [];
    }

    /**
     * @param array<mixed> $options
     *
     * @return array<mixed>
     */
    protected function resolveDataOptions(array $options): array
    {
        $or = new OptionsResolver();
        $or
            ->setDefault('editable', [])
            ->setDefault('context', [])
        ;
        return $or->resolve($options);
    }

    public function createEditables(
        Renderer $renderer,
        string $name,
        array $data
    ): \Generator {
        $argument = $renderer->get($name);
        $data = $this->resolveDataOptions($data);
        $attributes = $this->getEditablesExpressionAttributes();
        $data = $this->evaluateExpressions($data, $attributes);
        $argument = new RenderArgument(
            $argument->getType(),
            $argument->getName(),
            $data['editable']
        );

        $renderer->set($argument);

        yield from $this->doCreateEditables(
            $renderer,
            $name,
            $data
        );
    }

    public function preCreateEditables(string $brickName, \ArrayObject $data): array
    {
        return [];
    }

    public function postCreateEditables(string $brickName, array $config, Renderer $renderer): \Generator
    {
        return;
        yield;
    }

    /**
     * @param array<mixed> $data
     * @param array<string> $attributes
     *
     * @return array<mixed>
     */
    protected function evaluateExpressions(array $data, array $attributes)
    {
        return $this->getExpressionWrapper()->evaluateExpressions($data, $attributes, '[context]');
    }

    protected function getExpressionWrapper(): ExpressionWrapper
    {
        return $this->expressionWrapper;
    }
}
