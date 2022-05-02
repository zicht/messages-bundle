<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Page bundle configuration
 */
class Configuration implements ConfigurationInterface
{
    /** {@inheritDoc} */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('zicht_messages');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('locales')->prototype('scalar')->end()
            ->end();

        return $treeBuilder;
    }
}
