<?php

namespace ProfiTravel\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class PaymentCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('payment.service.factory')) {
            return;
        }
        $definition = $container->findDefinition('payment.service.factory');
        $taggedServices = $container->findTaggedServiceIds('PaymentService');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('setPaymentService', [new Reference($id)]);
        }
    }
}