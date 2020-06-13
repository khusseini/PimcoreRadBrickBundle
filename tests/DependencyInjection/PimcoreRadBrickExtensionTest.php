<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Khusseini\PimcoreRadBrickBundle\Areabricks\AbstractAreabrick;
use Khusseini\PimcoreRadBrickBundle\DependencyInjection\PimcoreRadBrickExtension;
use PHPStan\Testing\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Yaml\Yaml;

class ExampleBaseAreabrick extends AbstractAreabrick
{
    private $options;

    public function configure(array $options): void
    {
        $this->options = $options;
    }
}

class PimcoreRadBrickExtensionTest extends TestCase
{
    public function examplesDataProdiver()
    {
        return [
            $this->getBaseClassExampleData(),
        ];
    }

    /**
     * @dataProvider examplesDataProdiver
     */
    public function testExamples(
        string $configFile,
        ContainerBuilder $containerBuilder,
        callable $customAssert = null
    ) {
        $extension = new PimcoreRadBrickExtension();
        $configs = Yaml::parseFile(__DIR__.'/config/'.$configFile);
        $extension->load($configs, $containerBuilder);
        $areabricks = $configs['pimcore_rad_brick']['areabricks'];

        foreach ($areabricks as $name => $config) {
            $this->assertTrue($containerBuilder->has('radbrick.'.$name));
        }

        if ($customAssert) {
            $customAssert($configs['pimcore_rad_brick'], $containerBuilder);
        }
    }

    private function getBaseClassExampleData(): array
    {
        $containerBuilder = new ContainerBuilder();
        $baseDefinition = new Definition();
        $baseDefinition->setAbstract(true);
        $baseDefinition->setClass(ExampleBaseAreabrick::class);

        $containerBuilder->setDefinition(
            'app.areabricks.base.example_custom_brick',
            $baseDefinition
        );

        return [
            'base_class.yml',
            $containerBuilder
        ];
    }
}