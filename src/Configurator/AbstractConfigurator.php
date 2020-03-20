<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\ExpressionLanguage\ExpressionWrapper;
use Khusseini\PimcoreRadBrickBundle\RenderArgs;
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
     */
    abstract public function doCreateEditables(
        RenderArgs $renderArgs,
        array $data
    ): RenderArgs;

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
            ->setDefault('editable', function (OptionsResolver $or) {
                $or->setRequired('name');
                $or->setAllowedTypes('name', ['string']);
                $or->setDefault('config', []);
                $or->setAllowedTypes('config', ['array']);
            })
            ->setDefault('context', [])
        ;
        return $or->resolve($options);
    }

    public function createEditables(RenderArgs $renderArgs, array $data): RenderArgs
    {
        $data = $this->resolveDataOptions($data);
        $attributes = $this->getEditablesExpressionAttributes();
        $data = $this->evaluateExpressions($data, $attributes);
        return $this->doCreateEditables(
            $renderArgs,
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
