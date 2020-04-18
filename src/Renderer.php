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

    public function emitArgument(RenderArgument $argument): void
    {
        $this->set($argument);
        $this->toEmit[$argument->getName()] = $argument;
    }

    public function emit(): \Generator
    {
        foreach ($this->toEmit as $name => $argument) {
            if ($this->isArgumentEmitted($argument)) {
                continue;
            }
            $this->emittedArguments[$name] = $argument;
            yield $name => $argument;
            unset($this->toEmit[$name]);
        }
    }

    public function isArgumentEmitted(RenderArgument $renderArgument): bool
    {
        return isset($this->emittedArguments[$renderArgument->getName()]);
    }
}
