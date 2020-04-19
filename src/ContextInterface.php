<?php

namespace Khusseini\PimcoreRadBrickBundle;

interface ContextInterface
{
    public function setDatasources(DatasourceRegistry $datasourceRegistry): void;

    public function getDatasources(): ?DatasourceRegistry;

    public function toArray(): array;
}
