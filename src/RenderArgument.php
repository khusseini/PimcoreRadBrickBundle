<?php

namespace Khusseini\PimcoreRadBrickBundle;

class RenderArgument
{
    /** @var string */
    private $type;

    /** @var string */
    private $name;

    /** @var mixed */
    private $value;

    public function __construct(
        string $type,
        string $name,
        $value = null
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->value = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
