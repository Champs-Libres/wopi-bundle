<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wopi');

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        /* @phpstan-ignore-next-line */
        $rootNode
            ->children()
            ->scalarNode('server')->end()
            ->enumNode('version_management')->values(['version', 'timestamp'])
            ->info('Manager document versioning through version (Office 365) or last modified time (Collabora Online, CODE, etc.)')
            ->defaultValue('timestamp')
            ->end()
            ->booleanNode('enable_lock')->defaultTrue()->end()
            ->end();

        return $treeBuilder;
    }
}
