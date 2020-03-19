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

    abstract public function doCreateEditables(
        RenderArgs $renderArgs,
        array $data
    ): RenderArgs;

    public function getEditablesExpressionAttributes(): array
    {
        return [];
    }

    private $dataOptionsResolver;
    public function getDataOptionsResolver()
    {
        if (!$this->dataOptionsResolver) {
            $this->dataOptionsResolver = new OptionsResolver();
            $this
                ->dataOptionsResolver
                ->setDefault('editable', function (OptionsResolver $or) {
                    $or->setRequired('name');
                    $or->setAllowedTypes('name', ['string']);
                    $or->setDefault('config', []);
                    $or->setAllowedTypes('config', ['array']);
                })
                ->setDefault('context', [])
            ;
        }
        return $this->dataOptionsResolver;
    }

    protected function resolveDataOptions(array $options)
    {
        return $this->getDataOptionsResolver()->resolve($options);
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

    protected function evaluateExpressions(array $data, array $attributes)
    {
        return $this->getExpressionWrapper()->evaluateExpressions($data, $attributes, '[context]');
    }

    protected function getExpressionWrapper(): ExpressionWrapper
    {
        return $this->expressionWrapper;
    }

    public function preCreateEditables(string $brickName, array $brickConfig, array $config, array $context): array
    {
        return $context;
    }
}
