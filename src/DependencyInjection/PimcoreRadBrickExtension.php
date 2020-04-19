<?php

namespace Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Khusseini\PimcoreRadBrickBundle\AreabrickConfigurator;
use Khusseini\PimcoreRadBrickBundle\Areabricks\SimpleBrick;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Reference;

class PimcoreRadBrickExtension extends Extension
{
    /**
     * @param array<array> $configs
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
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
            AreabrickConfigurator::class, [
            $config,
            $configurators
            ]
        );
        $container->setDefinition(AreabrickConfigurator::class, $configurator);

        $areabricks = $config['areabricks'];
        foreach ($areabricks as $id => $aconfig) {
            $target = null;
            $definitionId = 'radbrick.'.$id;
            if ($class = $aconfig['class']) {
                $definitionId = $class;
                $target = $container->getDefinition($class);
            }

            if (!$target) {
                $target = new Definition(
                    SimpleBrick::class, [
                    $id,
                    new Reference('pimcore.templating.tag_renderer'),
                    new Reference(AreabrickConfigurator::class),
                    ]
                );
            }

            if (!$target->hasTag('pimcore.area.brick')) {
                $target->addTag('pimcore.area.brick', ['id' => $id]);
            }

            $container->setDefinition($definitionId, $target);
        }
    }
}
