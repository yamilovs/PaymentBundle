<?php

namespace Yamilovs\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Yamilovs\PaymentBundle\Manager\PaymentFactory;

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
        $rootNode = $treeBuilder->root('yamilovs_payment');

        $rootNode
            ->children()
                ->arrayNode('services')
                    ->children()
                        ->scalarNode('default')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('platron')
                            ->children()
                                ->scalarNode('hostname')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->integerNode('merchant_id')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('secret_key')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('salt')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('api_url_init')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('products')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('type')
                                ->isRequired()
                            ->end()
                            ->scalarNode('class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('primaryKey')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->defaultValue('id')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('success')
                            ->defaultValue('YamilovsPaymentBundle:Payment:success.html.twig')
                        ->end()
                        ->scalarNode('failure')
                            ->defaultValue('YamilovsPaymentBundle:Payment:failure.html.twig')
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
