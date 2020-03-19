<?php

namespace Khusseini\PimcoreRadBrickBundle;

use stdClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class DatasourceRegistry
{
    private $expressionLangauge;
    private $datasources = null;

    public function __construct(
        ExpressionLanguage $expressionLangauge = null
    ) {
        if (!$expressionLangauge) {
            $expressionLangauge = new ExpressionLanguage();
        }

        $this->expressionLangauge = $expressionLangauge;
        $this->datasources = new stdClass();
    }

    public function execute(string $name, array $args = [])
    {
        if (! $ds = $this->datasources->{$name}) {
            return;
        }

        return $ds($args);
    }

    public function __invoke(string $name, array $args = [])
    {
        return $this->execute($name, $args);
    }

    public function add(string $name, callable $callable): void
    {
        $this->datasources->{$name} = $callable;
    }

    public function createMethodCall(
        object $service,
        string $method,
        array $args
    ): callable {
        return function (array $input) use ($service, $method, $args) {
            foreach ($args as $index => $expression) {
                if (!is_string($expression)) {
                    continue;
                }
                $args[$index] = $this->getValue($input, $expression);
            }

            return call_user_func_array([$service, $method], $args);
        };
    }

    public function getValue(array $context, string $expression)
    {
        try {
            return $this->expressionLangauge->evaluate($expression, $context);
        } catch (\Exception $ex) {
            return $expression;
        }
    }
}
