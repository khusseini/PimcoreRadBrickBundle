<?php

namespace Khusseini\PimcoreRadBrickBundle;

use InvalidArgumentException;
use stdClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class DatasourceRegistry
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLangauge;

    /**
     * @var object
     */
    private $datasources = null;

    private $executedData = [];

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
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('Datasource \'%s\' not found.', $name));
        }

        $ds = $this->datasources->{$name};
        $this->executedData[$name] = $ds(...$args);

        return $this->executedData[$name];
    }

    public function hasData(string $name): bool
    {
        return isset($this->executedData[$name]) && (bool) $this->executedData[$name];
    }

    public function getDataContainer(): array
    {
        return $this->executedData;
    }

    public function has(string $name): bool
    {
        return property_exists($this->datasources, $name);
    }

    public function getData(string $name, bool $execute = false, array $execArgs = [])
    {
        if ($execute) {
            $this->execute($name, $execArgs);
        }

        if (!$this->hasData($name)) {
            return [];
        }

        return $this->executedData[$name];
    }

    public function executeAll(): \Generator
    {
        $ds = (array) $this->datasources;
        foreach ($ds as $name => $callback) {
            $this->executedData[$name] = $callback();
            $data = $this->executedData[$name];
            yield $name => $data;
        }
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
                $args[$index] = $this->getValueRecursive($input, $expression);
            }

            return \call_user_func_array([$service, $method], $args);
        };
    }

    protected function getValueRecursive(array $input, $expression)
    {
        if (\is_string($expression)) {
            return $this->getValue($input, $expression);
        }

        if (\is_array($expression)) {
            foreach ($expression as $key => $value) {
                $expression[$key] = $this->getValueRecursive($input, $value);
            }
        }

        return $expression;
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
}
