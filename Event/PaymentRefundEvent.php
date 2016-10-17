<?php

namespace Yamilovs\PaymentBundle\Event;

class PaymentRefundEvent extends AbstractPaymentEvent
{
    const NAME = 'payment.refund.event';
}