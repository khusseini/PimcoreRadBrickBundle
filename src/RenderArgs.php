<?php

namespace Khusseini\PimcoreRadBrickBundle;

class RenderArgs
{
    /** @var array<array> */
    private $data = [];

    /**
     * @param array<array> $data
     */
    public function set(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function remove(string $name): self
    {
        unset($this->data[$name]);
        return $this;
    }

    /**
     * @param array<array> $data
     */
    public function merge(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * @param array<array> $data
     */
    public function update(array $data): self
    {
        foreach ($this->data as $editable => $config) {
            if (!isset($data[$editable])) {
                continue;
            }
            $toMerge = array_intersect_key($data[$editable], $config);
            $this->data[$editable] = array_merge($config, $toMerge);
        }

        return $this;
    }

    /**
     * @return array<array>
     */
    public function get(string $editable): array
    {
        return $this->data[$editable];
    }

    /**
     * @return array<array>
     */
    public function getAll(): array
    {
        return $this->data;
    }
}
