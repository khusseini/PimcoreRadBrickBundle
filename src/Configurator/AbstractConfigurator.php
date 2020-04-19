<?php

namespace Khusseini\PimcoreRadBrickBundle\Configurator;

use Khusseini\PimcoreRadBrickBundle\ExpressionLanguage\ExpressionWrapper;
use Khusseini\PimcoreRadBrickBundle\RenderArgument;
use Khusseini\PimcoreRadBrickBundle\RenderArgumentEmitter;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractConfigurator implements IConfigurator
{
    /**
     * @var ExpressionWrapper
     */
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
        RenderArgumentEmitter $emitter,
        string $name,
        ConfiguratorData $data
    ): void;

    /**
     * @return array<string>
     * @codeCoverageIgnore
     */
    public function getEditablesExpressionAttributes(): array
    {
        return [];
    }

    public function createEditables(
        RenderArgumentEmitter $emitter,
        string $name,
        ConfiguratorData $data
    ): void {
        $argument = $emitter->get($name);
        $attributes = $this->getEditablesExpressionAttributes();
        $data = $this->evaluateExpressions($data, $attributes);

        $argument = new RenderArgument(
            $argument->getType(),
            $argument->getName(),
            $data->getConfig()
        );

        $emitter->set($argument);
        $this->doCreateEditables($emitter, $name, $data);
    }

    /**
     * @codeCoverageIgnore
     */
    public function preCreateEditables(string $brickName, ConfiguratorData $data): void
    {
        return;
    }

    /**
     * @codeCoverageIgnore
     */
    public function postCreateEditables(string $brickName, array $config, RenderArgumentEmitter $emitter): void
    {
        return;
    }

    /**
     * @param array<string> $attributes
     */
    protected function evaluateExpressions(ConfiguratorData $data, array $attributes): ConfiguratorData
    {
        $input = [
            'editable' => $data->getConfig(),
            'context' => $data->getContext()->toArray(),
        ];

        $config = $this->getExpressionWrapper()->evaluateExpressions($input, $attributes, '[context]');
        $data->setConfig($config['editable']);
        return $data;
    }

    protected function getExpressionWrapper(): ExpressionWrapper
    {
        return $this->expressionWrapper;
    }
}
