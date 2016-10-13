<?php

namespace Yamilovs\PaymentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class YamilovsPaymentExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('admin_services.yml');

        $container->setParameter($this->getAlias().".services.default", $config['services']['default']);
        $container->setParameter($this->getAlias().".services.platron.hostname", $config['services']['platron']['hostname']);
        $container->setParameter($this->getAlias().".services.platron.merchant_id", $config['services']['platron']['merchant_id']);
        $container->setParameter($this->getAlias().".services.platron.secret_key", $config['services']['platron']['secret_key']);
        $container->setParameter($this->getAlias().".services.platron.salt", $config['services']['platron']['salt']);
        $container->setParameter($this->getAlias().".services.platron.api_url_init", $config['services']['platron']['api_url_init']);
        $container->setParameter($this->getAlias().".products", $config['products']);
    }
}
