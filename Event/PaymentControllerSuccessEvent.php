<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentControllerSuccessEvent extends AbstractPaymentControllerEvent
{
    const NAME = 'event.payment.success';
}