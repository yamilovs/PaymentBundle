<?php

namespace ProfiTravel\PaymentBundle\Event;

class PaymentResultFailureEvent extends PaymentEventAbstract
{
    const NAME = 'payment.result.failure.event';
}