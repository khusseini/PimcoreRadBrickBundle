<?php

namespace Khusseini\PimcoreRadBrickBundle;

use InvalidArgumentException;

class Renderer
{
    /**
     * @var array<RenderArgument> 
     */
    private $arguments = [];

    /**
     * @var array<RenderArgument> 
     */
    private $emittedArguments = [];

    /**
     * @var array<RenderArgument> 
     */
    private $toEmit = [];

    public function set(RenderArgument $argument): void
    {
        $this->arguments[$argument->getName()] = $argument;
    }

    public function has(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function get(string $name): RenderArgument
    {
        return $this->arguments[$name];
    }

    public function emitArgument($nameOrArgument): void
    {
        if (! is_string($nameOrArgument)
            && ! $nameOrArgument instanceof RenderArgument
        ) {
            throw new InvalidArgumentException(sprintf("Argument of type %s not suppoert in %s.", gettype($nameOrArgument), __METHOD__));
        }

        $argument = $nameOrArgument;
        if (is_string($argument)) {
            $argument = $this->get($argument);
        }

        $this->set($argument);
        $this->toEmit[$argument->getName()] = $argument;
    }

    public function emit(): \Generator
    {
        foreach ($this->toEmit as $name => $argument) {
            if (isset($this->emittedArguments[$name])) {
                continue;
            }

            $this->emittedArguments[$name] = $argument;

            yield $name => $argument;
        }
    }

    public function isArgumentEmitted(string $name): bool
    {
        return isset($this->emittedArguments[$name]);
    }
}
