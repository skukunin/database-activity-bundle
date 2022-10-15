<?php

namespace SKukunin\DatabaseActivityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('database_activity');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('table_name')->defaultValue('database_activity_log')->end()
                ->booleanNode('auto_setup')->defaultTrue()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}