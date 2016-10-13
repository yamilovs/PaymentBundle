<?php

namespace ProfiTravel\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use ProfiTravel\PaymentBundle\Manager\PaymentFactory;

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
        $rootNode = $treeBuilder->root('payment');

        $rootNode
            ->children()
                ->arrayNode('services')
                    ->children()
                        ->scalarNode('default')->defaultFalse()->end()
                        ->arrayNode('platron')
                            ->children()
                                ->scalarNode('hostname')->end()
                                ->integerNode('merchant_id')->end()
                                ->scalarNode('secret_key')->end()
                                ->scalarNode('salt')->end()
                                ->scalarNode('api_url_init')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
