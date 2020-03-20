<?php

namespace Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        /** @var ArrayNodeDefinition $rootNode  */
        $rootNode = $treeBuilder->root('pimcore_rad_brick');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->arrayNode('datasources')
                    ->info('Define datasource available in areabricks.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('service_id')
                                ->info('Provide a Symfony service id')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('method')
                                ->info('Method to be called on service')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('args')
                                ->variablePrototype()
                                    ->info('Method arguments. Expressions can be used here')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
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
                            ->arrayNode('datasources')
                                ->info('Configure datasources to use  in view template')
                                ->useAttributeAsKey('name')
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('id')
                                                ->info('Provide the id of the datasource to use')
                                            ->end()
                                            ->arrayNode('args')
                                                ->info('Configure arguments to pass to method call')
                                                ->useAttributeAsKey('name')
                                                ->scalarPrototype()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
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
                                        ->scalarNode('instances')
                                            ->info('Provide the number of instances.')
                                        ->end()
                                        ->arrayNode('map')
                                            ->info('Map data from other editables')
                                            ->arrayPrototype()
                                                ->children()
                                                    ->scalarNode('source')
                                                        ->info('Expression to get the value')
                                                        ->isRequired()
                                                        ->cannotBeEmpty()
                                                    ->end()
                                                    ->scalarNode('target')
                                                        ->info('Path to property to be updated')
                                                        ->isRequired()
                                                        ->cannotBeEmpty()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('datasource')
                                            ->info('Bind editable to a datasource')
                                            ->children()
                                                ->scalarNode('name')
                                                    ->info('The name of the datasource')
                                                ->end()
                                                ->scalarNode('id')
                                                    ->info('The id to use for each item (uses expression language)')
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
