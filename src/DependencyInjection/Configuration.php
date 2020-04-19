<?php

namespace Khusseini\PimcoreRadBrickBundle\DependencyInjection;

use SebastianBergmann\Type\VoidType;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
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
        /**
 * @var ArrayNodeDefinition $rootNode  
*/
        $rootNode = $treeBuilder->root('pimcore_rad_brick');

        $this->addDatasourcesSection($rootNode);
        $this->addAreabrickSection($rootNode);

        return $treeBuilder;
    }

    protected function addCommonAreabrick(NodeBuilder $builder): void
    {
        $builder
            ->scalarNode('class')
            ->info('Use a predefined service instead of a newly created one.')
            ->end();
        $builder
            ->scalarNode('label')
            ->info('Specify a label for the admin UI.')
            ->end();
        $builder
            ->scalarNode('icon')
            ->info('Specify an icon for the admin UI.')
            ->end();
        $builder
            ->scalarNode('open')
            ->info('Set HTML prepended to the brick\'s contents.')
            ->defaultValue('')
            ->end();
        $builder
            ->scalarNode('close')
            ->info('Set HTML appended to the brick\'s contents.')
            ->defaultValue('')
            ->end();
        $builder
            ->booleanNode('use_edit')
            ->info('Use a separate edit template.')
            ->defaultValue(false)
            ->end();
    }

    protected function addGroupsAreabrick(NodeBuilder $builder): void
    {
        $builder
            ->variableNode('groups')
            ->info('Define groups for areabrick.')
            ->defaultValue([])
            ->end();
    }

    protected function addDatasourcesAreabrick(NodeBuilder $builder): void
    {
        $builder
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
            ->variablePrototype()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    protected function addEditableAreabrick(NodeBuilder $builder): void
    {
        $builder
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
            ->scalarNode('group')
            ->info('Specify the name of the group this editable belongs to.')
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
            ->end();
    }

    protected function addAreabrickSection(ArrayNodeDefinition $node): void
    {
        $prototype = $node
            ->children()
            ->arrayNode('areabricks')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children();

        $this->addCommonAreabrick($prototype);
        $this->addGroupsAreabrick($prototype);
        $this->addDatasourcesAreabrick($prototype);
        $this->addEditableAreabrick($prototype);

        $prototype->end()->end()->end();
    }

    protected function addDatasourcesSection(ArrayNodeDefinition $node): void
    {
        $node
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
            ->end();
    }
}
