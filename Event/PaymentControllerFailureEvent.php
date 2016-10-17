<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentControllerFailureEvent extends AbstractPaymentControllerEvent
{
    const NAME = 'event.payment.failure';
}