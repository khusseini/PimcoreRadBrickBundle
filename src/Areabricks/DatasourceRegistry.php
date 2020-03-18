<?php

namespace Khusseini\PimcoreRadBrickBundle\Areabricks;

use stdClass;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DatasourceRegistry
{
    private $propertyAccessor;

    private $datasources = null;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->datasources = new stdClass();
    }

    public function execute(string $name, array $args)
    {
        if (! $ds = $this->datasources->{$name}) {
            return;
        }

        return $ds($args);
    }

    public function add(
        string $name,
        object $service,
        string $method,
        array $args
    ) {
        $this->datasources->{$name} = function (array $input) use ($service, $method, $args) {
            foreach ($args as $index => $content) {
                if (!preg_match('/!q:.*/', $content)) {
                    continue;
                }
                $content = substr(3, $content);
                if (!$this->propertyAccessor->isReadable($input, $content)) {
                    continue;
                }
                $args[$index] = $this->propertyAccessor->getValue($input, $content);
            }

            return call_user_func_array([$service, $method], $args);
        };
    }
}
