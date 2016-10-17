<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentResultSuccessEvent extends AbstractPaymentEvent
{
    const NAME = 'event.payment.result.success';
}