<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentControllerResultSuccessEvent extends AbstractPaymentControllerEvent
{
    const NAME = 'event.payment.result.success';
}