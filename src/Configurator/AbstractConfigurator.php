<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\ExpressionLanguage\ExpressionWrapper;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
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
        RenderArgument $argument,
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

    public function createEditables(RenderArgument $argument, string $name, array $data): \Generator
    {
        $data = $this->resolveDataOptions($data);
        $attributes = $this->getEditablesExpressionAttributes();
        $data = $this->evaluateExpressions($data, $attributes);
        $argument = new RenderArgument(
            $argument->getType(),
            $argument->getName(),
            $data['editable']
        );

        yield from $this->doCreateEditables(
            $argument,
            $name,
            $data
        );
    }

    public function preCreateEditables(string $brickName, array $brickConfig, array $config, array $context): array
    {
        return $context;
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
