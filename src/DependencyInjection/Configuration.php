<?php

namespace Anfallnorr\FileManagerSystem\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('file_manager_system');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('default_directory')->defaultValue('/public/uploads')->end()
            ->end();
        return $treeBuilder;
    }
}
