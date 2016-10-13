<?php

namespace Yamilovs\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Yamilovs\PaymentBundle\Entity\Payment;

abstract class PaymentEventAbstract extends Event
{
    protected $payment;
    protected $request = [];
    protected $message = '';

    public function __construct(Payment $payment, array $request)
    {
        $this->payment = $payment;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->request = $message;
    }
}