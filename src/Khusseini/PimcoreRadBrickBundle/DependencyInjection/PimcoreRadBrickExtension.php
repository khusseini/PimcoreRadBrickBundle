<?php

namespace Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Khusseini\PimcoreRadBrickBundle\Areabricks\SimpleBrick;
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
        foreach ($areabricks as $id => $config) {
            $target = null;
            $definitionId = 'radbrick.'.$id;
            if ($class = $config['class']) {
                $definitionId = $class;
                $target = $container->getDefinition($class);
            }

            if (!$target) {
                $target = new Definition(SimpleBrick::class, [new Reference('pimcore.templating.tag_renderer')]);
            }

            $target->addMethodCall('setConfig', [$config]);

            if (!$target->hasTag('pimcore.area.brick')) {
                $target->addTag('pimcore.area.brick', ['id' => $id]);
            }

            $container->setDefinition($definitionId, $target);
        }
    }
}
