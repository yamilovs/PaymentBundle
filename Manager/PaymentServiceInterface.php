<?php

namespace Yamilovs\PaymentBundle\Manager;

interface PaymentServiceInterface
{
    public function getPayUrl(array $params);

    public function getAlias();

}