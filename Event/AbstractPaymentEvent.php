<?php

namespace Yamilovs\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Yamilovs\PaymentBundle\Entity\Payment;

abstract class AbstractPaymentEvent extends Event
{
    protected $payment;
    protected $request;
    protected $message = '';


    public function __construct(Payment $payment, array $request)
    {
        $this->payment = $payment;
        $this->request = $request;
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