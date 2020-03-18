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
        foreach ($this->data as $editable => $config) {
            if (!isset($data[$editable])) {
                continue;
            }

            $toMerge = $data[$editable];
            $config = array_merge($config, $toMerge);
            $this->data[$editable] = $config;
        }

        foreach ($data as $editable => $config) {
            if (isset($this->data[$editable])) {
                continue;
            }
            
            $this->data[$editable] = $config;
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
