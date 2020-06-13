<?php

namespace Tests\Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Khusseini\PimcoreRadBrickBundle\Areabricks\AbstractAreabrick;
use Khusseini\PimcoreRadBrickBundle\DependencyInjection\PimcoreRadBrickExtension;
use PHPStan\Testing\TestCase;
use Pimcore\Templating\Renderer\TagRenderer;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Yaml\Yaml;

final class PublicForTestsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder): void
    {
        if (!$this->isPHPUnit()) {
            return;
        }

        foreach ($containerBuilder->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }

        foreach ($containerBuilder->getAliases() as $definition) {
            $definition->setPublic(true);
        }
    }

    private function isPHPUnit(): bool
    {
        // there constants are defined by PHPUnit
        return \defined('PHPUNIT_COMPOSER_INSTALL') || \defined('__PHPUNIT_PHAR__');
    }
}

class ExampleBaseAreabrick extends AbstractAreabrick
{
    private $options;

    public function configure(array $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }
}

class PimcoreRadBrickExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function examplesDataProdiver()
    {
        return [
            $this->getDefaultExampleData(),
            $this->getDatasourcesExampleData(),
            $this->getBaseClassExampleData(),
        ];
    }

    /**
     * @dataProvider examplesDataProdiver
     */
    public function testExamples(
        string $configFile,
        ContainerBuilder $container,
        callable $customAssert = null
    ) {
        $this->addDefaults($container);

        $extension = new PimcoreRadBrickExtension();
        $configs = Yaml::parseFile(__DIR__.'/config/'.$configFile);
        $extension->load($configs, $container);
        $areabricks = $configs['pimcore_rad_brick']['areabricks'];

        foreach ($areabricks as $name => $config) {
            $this->assertTrue($container->has('radbrick.'.$name));
        }

        $container->compile();

        if ($customAssert) {
            $customAssert($configs['pimcore_rad_brick'], $container);
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
            $containerBuilder,
            function (array $configs, ContainerBuilder $containerBuilder) {
                /** @var ExampleBaseAreabrick $areabrick */
                $areabrick = $containerBuilder->get('radbrick.example_with_base_service');
                $this->assertInstanceOf(ExampleBaseAreabrick::class, $areabrick);
                $options = $areabrick->getOptions();
                $expectedOptions = $configs['areabricks']['example_with_base_service']['options'];
                $this->assertEquals($expectedOptions, $options);
            },
        ];
    }

    private function getDefaultExampleData(): array
    {
        return [
            'default.yml',
            new ContainerBuilder(),
        ];
    }

    public function addDefaults(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PublicForTestsCompilerPass());
        $this->addTagRenderer($container);
    }

    private function addTagRenderer(ContainerBuilder $container)
    {
        $tagRenderer = $this->prophesize(TagRenderer::class);
        $container->set('pimcore.templating.tag_renderer', $tagRenderer->reveal());
    }

    private function getDatasourcesExampleData()
    {
        $container = new ContainerBuilder();
        $service = new class() {
            public function testMethod()
            {
                return 'hello world';
            }
        };
        $container->set('test_datasource', $service);

        return [
            'datasources.yml',
            $container,
            function (array $configs, ContainerInterface $container) {
                $this->assertTrue($container->has('test_datasource'));
            },
        ];
    }
}
