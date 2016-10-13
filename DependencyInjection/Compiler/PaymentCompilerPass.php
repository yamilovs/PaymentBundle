<?php

namespace Yamilovs\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class PaymentCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('yamilovs.payment.factory')) {
            return;
        }
        $definition = $container->findDefinition('yamilovs.payment.factory');
        $taggedServices = $container->findTaggedServiceIds('YamilovsPaymentService');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('setPaymentService', [new Reference($id)]);
        }
    }
}