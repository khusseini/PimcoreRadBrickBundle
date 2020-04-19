<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractTestCase extends TestCase
{
    use ProphecyTrait;

    protected function parseYaml(string $input)
    {
        return Yaml::parse($input);
    }
}
