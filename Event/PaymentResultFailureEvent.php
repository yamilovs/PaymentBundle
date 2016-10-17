<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentResultFailureEvent extends AbstractPaymentEvent
{
    const NAME = 'payment.result.failure.event';
}