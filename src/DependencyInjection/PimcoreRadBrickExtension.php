<?php

namespace Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Khusseini\PimcoreRadBrickBundle\Areabricks\SimpleBrick;
use Khusseini\PimcoreRadBrickBundle\DatasourceRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Reference;

class PimcoreRadBrickExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $areabricks = $config['areabricks'];
        $datasources = $config['datasources'];
        
        $datasourceRegistry =  new Definition(DatasourceRegistry::class);

        foreach ($datasources as $name => $datasource) {
            $datasourceRegistry->addMethodCall('add', [
                $name,
                new Reference($datasource['service_id']),
                $datasource['method'],
                $datasource['args'],
            ]);
        }

        $container->setDefinition(
            'radbrick.datasource_registry',
            $datasourceRegistry
        );

        foreach ($areabricks as $id => $config) {
            $target = null;
            $definitionId = 'radbrick.'.$id;
            if ($class = $config['class']) {
                $definitionId = $class;
                $target = $container->getDefinition($class);
            }

            if (!$target) {
                $target = new Definition(SimpleBrick::class, [
                    new Reference('pimcore.templating.tag_renderer'),
                    new Reference('radbrick.datasource_registry'),
                    $config['label'],
                    $config['use_edit'],
                    $config['open'],
                    $config['close'],
                    $config['icon'],
                ]);
            }

            $target->addMethodCall('setConfig', [$config]);

            if (!$target->hasTag('pimcore.area.brick')) {
                $target->addTag('pimcore.area.brick', ['id' => $id]);
            }

            $container->setDefinition($definitionId, $target);
        }
    }
}
