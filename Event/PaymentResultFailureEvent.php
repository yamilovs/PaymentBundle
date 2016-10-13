<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentResultFailureEvent extends PaymentEventAbstract
{
    const NAME = 'payment.result.failure.event';
}