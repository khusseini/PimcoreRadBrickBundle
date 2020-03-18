<?php

namespace Khusseini\PimcoreRadBrickBundle;

use Symfony\Component\OptionsResolver\OptionsResolver;

class RenderArgs
{
    /** @var array */
    private $data = [];

    /** @var OptionsResolver */
    private $or;

    public function __construct()
    {
        $this->or = new OptionsResolver();
    }

    public function getOptionsResolver(): OptionsResolver
    {
        return $this->or;
    }

    public function set(array $data)
    {
        if ($this->or->getDefinedOptions()) {
            $data = $this->or->resolve($data);
        }

        $this->data = $data;

        return $this;
    }

    public function remove(string $name)
    {
        unset($this->data[$name]);
        return $this;
    }

    public function merge(array $data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function update(array $data)
    {
        foreach ($this->data as $editable => $config) {
            if (!isset($data[$editable])) {
                continue;
            }
            $toMerge = array_intersect_key(
                $data[$editable], $config
            );

            $this->data[$editable] = array_merge($config, $toMerge);
        }

        return $this;
    }

    public function get(string $editable)
    {
        return $this->data[$editable];
    }

    public function getAll()
    {
        return $this->data;
    }
}
