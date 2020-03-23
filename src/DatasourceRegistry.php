<?php

namespace Khusseini\PimcoreRadBrickBundle;

use stdClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class DatasourceRegistry
{
    /** @var ExpressionLanguage */
    private $expressionLangauge;

    /** @var object */
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

    /**
     * @param array<string> $args
     *
     * @return mixed
     */
    public function execute(string $name, array $args = [])
    {
        if (! $ds = $this->datasources->{$name}) {
            return;
        }

        return $ds($args);
    }

    public function executeAll(): \Generator
    {
        $ds = (array)$this->datasources;
        foreach ($ds as $name => $callback) {
            yield $name => $callback();
        }
    }

    /**
     * @param array<string> $args
     *
     * @return mixed
     */
    public function __invoke(string $name, array $args = [])
    {
        return $this->execute($name, $args);
    }

    public function add(string $name, callable $callable): void
    {
        $this->datasources->{$name} = $callable;
    }

    /**
     * @param array<string> $args
     */
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

    /**
     * @param array<array> $context
     *
     * @return mixed
     */
    public function getValue(array $context, string $expression)
    {
        try {
            return $this->expressionLangauge->evaluate($expression, $context);
        } catch (\Exception $ex) {
            return $expression;
        }
    }

    /**
     * @return array<callable>
     */
    public function getAll()
    {
        return (array)$this->datasources;
    }
}
