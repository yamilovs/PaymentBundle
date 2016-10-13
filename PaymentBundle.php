<?php

namespace Yamilovs\PaymentBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Yamilovs\PaymentBundle\DependencyInjection\Compiler\PaymentCompilerPass;

class PaymentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new PaymentCompilerPass());
    }
}
