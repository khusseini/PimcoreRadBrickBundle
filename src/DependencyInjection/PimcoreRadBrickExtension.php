<?php

namespace Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\AreabrickRenderer;
use Khusseini\PimcoreRadBrickBundle\Areabricks\SimpleBrick;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PimcoreRadBrickExtension extends Extension
{
    /**
     * @param array<array> $configs
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $configurators = [];
        $ids = $container->findTaggedServiceIds('radbrick.configurator');
        foreach ($ids as $id => $tags) {
            $configurators[] = new Reference($id);
        }

        $datasources = $config['datasources'];
        foreach ($datasources as $key => $datasource) {
            $datasources[$key]['service_id'] = new Reference($datasource['service_id']);
        }
        $config['datasources'] = $datasources;

        $configurator = new Definition(
            AreabrickConfigurator::class,
            [
            $config,
            $configurators,
            ]
        );
        $container->setDefinition(AreabrickConfigurator::class, $configurator);

        $rendererDefinition = new Definition(AreabrickRenderer::class, [
            new Reference(AreabrickConfigurator::class),
            new Reference('pimcore.templating.tag_renderer'),
        ]);

        $container->setDefinition(AreabrickRenderer::class, $rendererDefinition);

        $areabricks = $config['areabricks'];
        foreach ($areabricks as $id => $areabrickConfig) {
            $definitionId = 'radbrick.'.$id;
            $parent = null;
            $options = $areabrickConfig['options'] ?: [];

            $target = null;
            if ($class = @$areabrickConfig['class']) {
                $target = new ChildDefinition($class);
                $target->setClass($class);
            }

            if (!$target) {
                $target = new Definition(SimpleBrick::class);
            }

            $target->setArgument('$name', $id);
            $target->setArgument('$areabrickRenderer', new Reference(AreabrickRenderer::class));
            $target->addMethodCall('configure', [$options]);

            if (!$target->hasTag('pimcore.area.brick')) {
                $target->addTag('pimcore.area.brick', ['id' => $id]);
            }

            $container->setDefinition($definitionId, $target);
        }
    }
}
