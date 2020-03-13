<?php

namespace Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('pimcore_rad_brick');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->arrayNode('areabricks')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('class')
                                ->info('Use a predefined service instead of a newly created one.')
                            ->end()
                            ->scalarNode('label')
                                ->info('Specify a label for the admin UI.')
                            ->end()
                            ->scalarNode('icon')
                                ->info('Specify an icon for the admin UI.')
                            ->end()
                            ->scalarNode('open')
                                ->info('Set HTML prepended to the brick\'s contents.')
                                ->defaultValue('')
                            ->end()
                            ->scalarNode('close')
                                ->info('Set HTML appended to the brick\'s contents.')
                                ->defaultValue('')
                            ->end()
                            ->booleanNode('use_edit')
                                ->info('Use a separate edit template.')
                                ->defaultValue(false)
                            ->end()
                            ->arrayNode('editables')
                                ->info('Define editables available in templates.')
                                ->requiresAtLeastOneElement()
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('type')
                                            ->info('Editable type')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->variableNode('options')
                                            ->info('Editable options')
                                            ->defaultValue([])
                                        ->end()
                                        ->scalarNode('source')
                                            ->info('Specify an editable as a data source for multiple instances. (Must be numeric)')
                                        ->end()
                                        ->arrayNode('map')
                                            ->info('Map data from other editables')
                                            ->arrayPrototype()
                                                ->children()
                                                    ->scalarNode('source')
                                                        ->info('Path to data property')
                                                        ->isRequired()
                                                        ->cannotBeEmpty()
                                                    ->end()
                                                    ->scalarNode('target')
                                                        ->info('Path to target property')
                                                        ->isRequired()
                                                        ->cannotBeEmpty()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
