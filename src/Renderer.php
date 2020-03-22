<?php

namespace Khusseini\PimcoreRadBrickBundle;

class Renderer
{
    /** @var array<RenderArgument> */
    private $arguments = [];

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
}
