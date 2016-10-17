<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentControllerFailureSuccessEvent extends AbstractPaymentControllerEvent
{
    const NAME = 'event.payment.result.failure';
}