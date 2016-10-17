<?php

namespace Yamilovs\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Yamilovs\PaymentBundle\Entity\Payment;

abstract class AbstractPaymentControllerEvent extends Event
{
    protected $payment;
    protected $response;
    protected $responseView;
    protected $responseParameters;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @param mixed $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $responseView
     */
    public function setResponseView($responseView)
    {
        $this->responseView = $responseView;
    }

    /**
     * @return mixed
     */
    public function getResponseView()
    {
        return $this->responseView;
    }

    /**
     * @param array $responseParameters
     */
    public function setResponseParameters(array $responseParameters)
    {
        $this->responseParameters = $responseParameters;
    }

    /**
     * @return mixed
     */
    public function getResponseParameters()
    {
        return $this->responseParameters;
    }
}