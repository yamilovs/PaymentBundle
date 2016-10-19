<?php

namespace Yamilovs\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Yamilovs\PaymentBundle\Entity\Payment;

abstract class AbstractPaymentControllerEvent extends Event
{
    /** @var Payment */
    protected $payment;
    /** @var Response */
    protected $response;
    /** @var string */
    protected $template;
    /** @var array */
    protected $templateParameters;

    public function __construct(Payment $payment)
    {
        $this->setPayment($payment);
    }

    /**
     * @param Payment $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
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
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param array $templateParameters
     */
    public function setTemplateParameters(array $templateParameters)
    {
        $this->templateParameters = $templateParameters;
    }

    /**
     * @return array
     */
    public function getTemplateParameters()
    {
        return $this->templateParameters;
    }
}